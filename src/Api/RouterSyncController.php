<?php
/**
 * Controller API Synchronisation Routeur
 *
 * Reçoit les données de synchronisation des routeurs MikroTik :
 * - Sessions hotspot et PPPoE actives
 * - Informations système (CPU, mémoire, uptime)
 *
 * Authentification par router_id + polling_token (pas de session web).
 */

class RouterSyncController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    /**
     * POST /api/router-sync/sync
     * Reçoit les sessions actives du routeur
     */
    public function sync(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || empty($input['router_id'])) {
            jsonError('router_id requis', 400);
            return;
        }

        $routerId = $input['router_id'];
        $pdo = $this->db->getPdo();

        // Authentifier le routeur
        $stmt = $pdo->prepare("SELECT id, router_id, admin_id, polling_token FROM nas WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nas) {
            jsonError('Routeur inconnu', 404);
            return;
        }

        // Vérifier le token si configuré
        if (!empty($nas['polling_token'])) {
            $providedToken = $_SERVER['HTTP_X_NAS_TOKEN'] ?? '';
            if (!hash_equals($nas['polling_token'], $providedToken)) {
                jsonError('Token invalide', 403);
                return;
            }
        }

        // Mettre à jour last_seen
        $pdo->prepare("UPDATE nas SET last_seen = NOW() WHERE id = ?")->execute([$nas['id']]);

        $response = ['commands' => []];

        // Traiter les sessions hotspot
        if (isset($input['hotspot']) && is_array($input['hotspot'])) {
            $this->processHotspotSessions($nas, $input['hotspot'], $response);
        }

        // Traiter les sessions PPPoE
        if (isset($input['pppoe']) && is_array($input['pppoe'])) {
            $this->processPPPoESessions($nas, $input['pppoe'], $response);
        }

        // Stocker les infos système
        if (isset($input['system']) && is_array($input['system'])) {
            $this->storeSystemInfo($nas['id'], $input['system']);
        }

        jsonSuccess([
            'synced' => true,
            'hotspot_count' => count($input['hotspot'] ?? []),
            'pppoe_count' => count($input['pppoe'] ?? []),
            'commands' => $response['commands'],
        ]);
    }

    /**
     * Traiter les sessions hotspot actives envoyées par le routeur
     */
    private function processHotspotSessions(array $nas, array $activeSessions, array &$response): void
    {
        $pdo = $this->db->getPdo();

        // Récupérer les usernames actifs sur le routeur
        $activeUsers = array_map(fn($s) => $s['u'] ?? '', $activeSessions);
        $activeUsers = array_filter($activeUsers);

        if (empty($activeUsers)) {
            return;
        }

        // Vérifier les vouchers expirés encore connectés
        $placeholders = str_repeat('?,', count($activeUsers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT v.username, v.status, v.valid_until, v.time_limit, v.time_used
            FROM vouchers v
            WHERE v.username IN ({$placeholders})
        ");
        $stmt->execute($activeUsers);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vouchers as $v) {
            $shouldDisconnect = false;
            $reason = '';

            // Voucher expiré
            if ($v['valid_until'] && strtotime($v['valid_until']) < time()) {
                $shouldDisconnect = true;
                $reason = 'voucher_expired';
            }

            // Voucher désactivé
            if ($v['status'] === 'disabled' || $v['status'] === 'expired') {
                $shouldDisconnect = true;
                $reason = 'voucher_' . $v['status'];
            }

            // Temps écoulé
            if ($v['time_limit'] && $v['time_used'] >= $v['time_limit']) {
                $shouldDisconnect = true;
                $reason = 'time_limit_exceeded';
            }

            if ($shouldDisconnect) {
                $response['commands'][] = [
                    'type' => 'disconnect_hotspot',
                    'username' => $v['username'],
                    'reason' => $reason,
                ];
            }
        }
    }

    /**
     * Traiter les sessions PPPoE actives
     */
    private function processPPPoESessions(array $nas, array $activeSessions, array &$response): void
    {
        $pdo = $this->db->getPdo();

        $activeUsers = array_map(fn($s) => $s['u'] ?? '', $activeSessions);
        $activeUsers = array_filter($activeUsers);

        if (empty($activeUsers)) {
            return;
        }

        // Vérifier les utilisateurs PPPoE expirés/suspendus
        $placeholders = str_repeat('?,', count($activeUsers) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT pu.username, pu.status, pu.valid_until
            FROM pppoe_users pu
            WHERE pu.username IN ({$placeholders})
        ");
        $stmt->execute($activeUsers);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as $u) {
            $shouldDisconnect = false;
            $reason = '';

            if ($u['status'] === 'suspended' || $u['status'] === 'disabled' || $u['status'] === 'expired') {
                $shouldDisconnect = true;
                $reason = 'user_' . $u['status'];
            }

            if ($u['valid_until'] && strtotime($u['valid_until']) < time()) {
                $shouldDisconnect = true;
                $reason = 'subscription_expired';
            }

            if ($shouldDisconnect) {
                $response['commands'][] = [
                    'type' => 'disconnect_pppoe',
                    'username' => $u['username'],
                    'reason' => $reason,
                ];
            }
        }
    }

    /**
     * Stocker les informations système du routeur
     */
    private function storeSystemInfo(int $nasId, array $systemInfo): void
    {
        // Stocker dans une colonne JSON simple sur nas
        // ou dans un champ dédié si on veut historiser
        $pdo = $this->db->getPdo();

        $info = json_encode([
            'cpu' => $systemInfo['cpu'] ?? null,
            'free_mem' => $systemInfo['free_mem'] ?? null,
            'total_mem' => $systemInfo['total_mem'] ?? null,
            'uptime' => $systemInfo['uptime'] ?? null,
            'version' => $systemInfo['version'] ?? null,
            'synced_at' => date('Y-m-d H:i:s'),
        ]);

        // On utilise la colonne description comme stockage temporaire
        // ou on pourrait ajouter une colonne system_info
        $pdo->prepare("UPDATE nas SET description = ? WHERE id = ?")
            ->execute([$info, $nasId]);
    }
}
