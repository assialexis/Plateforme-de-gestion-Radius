<?php
/**
 * Controller API Sessions
 */

require_once __DIR__ . '/../Radius/RadiusServer.php';
require_once __DIR__ . '/../MikroTik/CommandSender.php';
require_once __DIR__ . '/../MikroTik/RouterOS.php';

class SessionController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private MikroTikCommandSender $commandSender;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->commandSender = new MikroTikCommandSender($db->getPdo());
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * POST /api/sessions/sync
     * Reçoit les sessions actives depuis le MikroTik
     */
    public function sync(): void
    {
        $data = getJsonBody();
        $nasIp = $data['nas_ip'] ?? $_SERVER['REMOTE_ADDR'];
        $sessions = $data['sessions'] ?? [];

        if (empty($sessions)) {
            // Si pas de sessions, marquer toutes les sessions de ce NAS comme terminées
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare("
                UPDATE sessions
                SET stop_time = NOW(), terminate_cause = 'NAS-Sync'
                WHERE nas_ip = ? AND stop_time IS NULL
            ");
            $stmt->execute([$nasIp]);
            jsonSuccess(['synced' => 0, 'closed' => $stmt->rowCount()]);
            return;
        }

        $pdo = $this->db->getPdo();
        $synced = 0;
        $activeSessionIds = [];

        foreach ($sessions as $session) {
            $username = $session['user'] ?? null;
            if (!$username) continue;

            // Chercher le voucher
            $voucher = $this->db->getVoucherByUsername($username);
            if (!$voucher) continue;

            $sessionId = $session['session-id'] ?? $session['.id'] ?? uniqid();
            $activeSessionIds[] = $sessionId;

            // Vérifier si la session existe déjà
            $stmt = $pdo->prepare("SELECT id FROM sessions WHERE acct_session_id = ? AND nas_ip = ?");
            $stmt->execute([$sessionId, $nasIp]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Mettre à jour la session existante
                $stmt = $pdo->prepare("
                    UPDATE sessions SET
                        session_time = ?,
                        input_octets = ?,
                        output_octets = ?,
                        last_update = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $this->parseUptime($session['uptime'] ?? '0s'),
                    $this->parseBytes($session['bytes-in'] ?? 0),
                    $this->parseBytes($session['bytes-out'] ?? 0),
                    $existing['id']
                ]);
            } else {
                // Créer une nouvelle session
                $stmt = $pdo->prepare("
                    INSERT INTO sessions (
                        voucher_id, acct_session_id, nas_ip, username,
                        client_ip, client_mac, start_time, last_update
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $voucher['id'],
                    $sessionId,
                    $nasIp,
                    $username,
                    $session['address'] ?? null,
                    $session['mac-address'] ?? null
                ]);
            }
            $synced++;
        }

        // Fermer les sessions qui ne sont plus actives sur le NAS
        // Utiliser les usernames car les session-id peuvent différer entre RADIUS et MikroTik
        $activeUsernames = array_map(fn($s) => $s['user'] ?? null, $sessions);
        $activeUsernames = array_filter($activeUsernames);

        if (!empty($activeUsernames)) {
            $placeholders = str_repeat('?,', count($activeUsernames) - 1) . '?';
            $stmt = $pdo->prepare("
                UPDATE sessions
                SET stop_time = NOW(), terminate_cause = 'NAS-Sync'
                WHERE nas_ip = ? AND stop_time IS NULL AND username NOT IN ($placeholders)
            ");
            $stmt->execute(array_merge([$nasIp], $activeUsernames));
        }

        jsonSuccess(['synced' => $synced]);
    }

    /**
     * Parser le format uptime MikroTik (ex: "1h30m45s")
     */
    private function parseUptime(string $uptime): int
    {
        $seconds = 0;
        if (preg_match('/(\d+)w/', $uptime, $m)) $seconds += $m[1] * 604800;
        if (preg_match('/(\d+)d/', $uptime, $m)) $seconds += $m[1] * 86400;
        if (preg_match('/(\d+)h/', $uptime, $m)) $seconds += $m[1] * 3600;
        if (preg_match('/(\d+)m/', $uptime, $m)) $seconds += $m[1] * 60;
        if (preg_match('/(\d+)s/', $uptime, $m)) $seconds += $m[1];
        return $seconds;
    }

    /**
     * Parser les bytes (peut être un nombre ou une string)
     */
    private function parseBytes($bytes): int
    {
        if (is_numeric($bytes)) return (int)$bytes;
        return 0;
    }

    /**
     * GET /api/sessions
     */
    public function index(): void
    {
        $filters = [
            'username' => get('username'),
            'nas_ip' => get('nas_ip'),
            'zone_id' => get('zone_id'),
            'status' => get('status'),
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
        ];

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 50)));

        $adminId = $this->getAdminId();
        $result = $this->db->getSessionHistory($filters, $page, $perPage, $adminId);
        jsonSuccess($result);
    }

    /**
     * GET /api/sessions/active
     */
    public function active(): void
    {
        // Nettoyer les sessions des vouchers expirés avant de lister
        $this->cleanupExpiredSessions();

        $adminId = $this->getAdminId();
        $sessions = $this->db->getActiveSessions($adminId);
        jsonSuccess($sessions);
    }

    /**
     * Ferme automatiquement les sessions expirées et orphelines
     */
    private function cleanupExpiredSessions(): void
    {
        $pdo = $this->db->getPdo();

        // Fermer les sessions des vouchers expirés
        $stmt = $pdo->prepare("
            UPDATE sessions s
            JOIN vouchers v ON s.username = v.username
            SET s.stop_time = NOW(), s.terminate_cause = 'Session-Timeout'
            WHERE s.stop_time IS NULL
            AND v.valid_until IS NOT NULL
            AND v.valid_until < NOW()
        ");
        $stmt->execute();

        // Fermer les sessions orphelines (pas d'activité depuis 5 minutes)
        // Ces sessions n'ont pas reçu d'accounting update et sont probablement déconnectées
        $stmt = $pdo->prepare("
            UPDATE sessions
            SET stop_time = NOW(), terminate_cause = 'Lost-Connection'
            WHERE stop_time IS NULL
            AND last_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $stmt->execute();

        // Fermer aussi les sessions PPPoE expirées si la table existe
        try {
            $stmt = $pdo->prepare("
                UPDATE pppoe_sessions ps
                JOIN pppoe_users pu ON ps.pppoe_user_id = pu.id
                SET ps.stop_time = NOW(), ps.terminate_cause = 'Session-Timeout'
                WHERE ps.stop_time IS NULL
                AND pu.valid_until IS NOT NULL
                AND pu.valid_until < NOW()
            ");
            $stmt->execute();

            // Fermer les sessions PPPoE orphelines
            $stmt = $pdo->prepare("
                UPDATE pppoe_sessions
                SET stop_time = NOW(), terminate_cause = 'Lost-Connection'
                WHERE stop_time IS NULL
                AND last_update < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->execute();
        } catch (PDOException $e) {
            // Table PPPoE n'existe peut-être pas encore, ignorer
        }
    }

    /**
     * GET /api/sessions/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $session = $this->db->getSessionById($id);

        if (!$session) {
            jsonError(__('api.session_not_found'), 404);
        }

        jsonSuccess($session);
    }

    /**
     * DELETE /api/sessions/{id} - Déconnecter un utilisateur
     */
    public function disconnect(array $params): void
    {
        $id = (int)$params['id'];
        $session = $this->db->getSessionById($id);

        if (!$session) {
            jsonError(__('api.session_not_found'), 404);
        }

        if ($session['stop_time'] !== null) {
            jsonError(__('api.session_already_terminated'), 400);
        }

        // Obtenir les infos du NAS (correspondance exacte ou wildcard)
        $nas = $this->findNasForSession($session);

        $username = $session['username'] ?? $session['voucher_code'] ?? '';
        $methods = [];
        $disconnectedViaApi = false;

        error_log("Disconnect session #{$id}: username={$username}, nas_ip=" . ($session['nas_ip'] ?? 'null') . ", nas_found=" . ($nas ? $nas['shortname'] . '/' . ($nas['router_id'] ?? 'no-router') : 'null'));

        if ($nas) {
            // 1. Essayer l'API MikroTik directe (instantané)
            $disconnectedViaApi = $this->tryDirectApiDisconnect($nas, $session, $username);
            if ($disconnectedViaApi) {
                $methods[] = 'API MikroTik';
            }

            // 2. TOUJOURS envoyer aussi la commande polling (filet de sécurité)
            $routerId = $nas['router_id'] ?? null;
            if ($routerId) {
                $this->commandSender->disconnectHotspotUser($routerId, $username);
                $methods[] = 'commande polling';
            }
        }

        // Marquer la session comme terminée en base (toujours, même sans NAS)
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            UPDATE sessions SET stop_time = NOW(), terminate_cause = 'Admin-Reset'
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        $methodStr = !empty($methods) ? implode(' + ', $methods) : 'marquage DB';
        jsonSuccess([
            'method' => $methodStr,
            'api_success' => $disconnectedViaApi
        ], __('api.session_user_disconnected'));
    }

    /**
     * Tenter la déconnexion via l'API MikroTik directe
     * Essaie mikrotik_host en priorité, puis nas_ip comme fallback
     */
    private function tryDirectApiDisconnect(array $nas, array $session, string $username): bool
    {
        $apiUser = $nas['mikrotik_api_username'] ?? null;
        $apiPass = $nas['mikrotik_api_password'] ?? null;

        if (!$apiUser || !$apiPass) {
            return false;
        }

        // Liste des hosts à essayer: mikrotik_host d'abord, puis nas_ip de la session
        $hosts = [];
        if (!empty($nas['mikrotik_host'])) {
            $hosts[] = $nas['mikrotik_host'];
        }
        if (!empty($session['nas_ip']) && $session['nas_ip'] !== ($nas['mikrotik_host'] ?? '')) {
            $hosts[] = $session['nas_ip'];
        }

        $apiPort = (int)($nas['mikrotik_api_port'] ?? 8728);

        foreach ($hosts as $host) {
            try {
                $router = new RouterOS($host, $apiPort, 3);
                if ($router->connect($apiUser, $apiPass)) {
                    $result = $router->hotspotDisconnectByUser($username);
                    $router->disconnect();
                    if ($result) {
                        return true;
                    }
                }
            } catch (Exception $e) {
                error_log("MikroTik API disconnect via {$host} failed for {$username}: " . $e->getMessage());
            }
        }

        return false;
    }

    /**
     * Trouver le NAS correspondant à une session (match exact ou wildcard)
     */
    private function findNasForSession(array $session): ?array
    {
        $nas = null;
        $mikrotikHostMatch = null;
        $wildcardNas = null;
        $sessionIp = $session['nas_ip'] ?? '';

        foreach ($this->db->getAllNas() as $n) {
            // Match exact sur nasname (priorité 1)
            if ($n['nasname'] === $sessionIp) {
                $nas = $n;
                break;
            }
            // Match sur mikrotik_host (priorité 2)
            if (!empty($n['mikrotik_host']) && $n['mikrotik_host'] === $sessionIp) {
                $mikrotikHostMatch = $n;
            }
            // Wildcard (priorité 3 - préférer celui qui poll activement)
            if ($n['nasname'] === '0.0.0.0/0') {
                if (!$wildcardNas || (!empty($n['last_seen']) && empty($wildcardNas['last_seen']))) {
                    $wildcardNas = $n;
                }
            }
        }

        return $nas ?? $mikrotikHostMatch ?? $wildcardNas;
    }

    /**
     * POST /api/sessions/disconnect-all
     * Déconnecte toutes les sessions actives, ou celles d'un voucher spécifique
     */
    public function disconnectAll(): void
    {
        $data = getJsonBody();
        $voucherId = $data['voucher_id'] ?? null;

        $sessions = $this->db->getActiveSessions();
        $disconnected = 0;

        // Grouper les sessions par NAS pour optimiser les connexions API
        $sessionsByNas = [];
        foreach ($sessions as $session) {
            if ($voucherId && $session['voucher_id'] != $voucherId) {
                continue;
            }
            $nasIp = $session['nas_ip'] ?? 'unknown';
            $sessionsByNas[$nasIp][] = $session;
        }

        foreach ($sessionsByNas as $nasIp => $nasSessions) {
            $nas = $this->findNasForSession($nasSessions[0]);
            if (!$nas) continue;

            // Essayer l'API directe pour ce NAS (une seule connexion)
            $apiRouter = $this->connectToRouterApi($nas, $nasSessions[0]);

            $pdo = $this->db->getPdo();
            $routerId = $nas['router_id'] ?? null;

            foreach ($nasSessions as $session) {
                $username = $session['username'];

                // 1. API directe (instantané)
                if ($apiRouter) {
                    try {
                        $apiRouter->hotspotDisconnectByUser($username);
                    } catch (Exception $e) {
                        error_log("MikroTik API disconnect failed for {$username}: " . $e->getMessage());
                    }
                }

                // 2. TOUJOURS envoyer aussi la commande polling (filet de sécurité)
                if ($routerId) {
                    $this->commandSender->disconnectHotspotUser($routerId, $username);
                }

                // Marquer comme terminé
                $stmt = $pdo->prepare("
                    UPDATE sessions SET stop_time = NOW(), terminate_cause = 'Admin-Reset'
                    WHERE id = ?
                ");
                $stmt->execute([$session['id']]);
                $disconnected++;
            }

            if ($apiRouter) {
                $apiRouter->disconnect();
            }
        }

        jsonSuccess(['disconnected' => $disconnected], __('api.sessions_disconnected'));
    }

    /**
     * Connecter à l'API RouterOS d'un NAS
     * Essaie mikrotik_host en priorité, puis nas_ip comme fallback
     */
    private function connectToRouterApi(array $nas, array $session): ?RouterOS
    {
        $apiUser = $nas['mikrotik_api_username'] ?? null;
        $apiPass = $nas['mikrotik_api_password'] ?? null;

        if (!$apiUser || !$apiPass) {
            return null;
        }

        $apiPort = (int)($nas['mikrotik_api_port'] ?? 8728);

        $hosts = [];
        if (!empty($nas['mikrotik_host'])) {
            $hosts[] = $nas['mikrotik_host'];
        }
        if (!empty($session['nas_ip']) && $session['nas_ip'] !== ($nas['mikrotik_host'] ?? '')) {
            $hosts[] = $session['nas_ip'];
        }

        foreach ($hosts as $host) {
            try {
                $router = new RouterOS($host, $apiPort, 3);
                if ($router->connect($apiUser, $apiPass)) {
                    return $router;
                }
            } catch (Exception $e) {
                error_log("MikroTik API connect failed for {$host}: " . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * GET /api/sessions/pending-disconnects
     * Retourne les utilisateurs à déconnecter (pour le polling MikroTik)
     */
    public function pendingDisconnects(): void
    {
        $pdo = $this->db->getPdo();

        // Récupérer les demandes de déconnexion en attente
        $stmt = $pdo->query("
            SELECT id, username, acct_session_id, requested_at
            FROM disconnect_requests
            WHERE processed = 0
            ORDER BY requested_at ASC
        ");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess(['disconnects' => $requests]);
    }

    /**
     * POST /api/sessions/confirm-disconnect
     * Confirme qu'une déconnexion a été effectuée par le MikroTik
     */
    public function confirmDisconnect(): void
    {
        $data = getJsonBody();
        $username = $data['username'] ?? null;
        $requestId = $data['request_id'] ?? null;

        if (!$username && !$requestId) {
            jsonError(__('api.session_username_or_request_id_required'), 400);
        }

        $pdo = $this->db->getPdo();

        if ($requestId) {
            // Marquer la demande comme traitée
            $stmt = $pdo->prepare("UPDATE disconnect_requests SET processed = 1, processed_at = NOW() WHERE id = ?");
            $stmt->execute([$requestId]);
        }

        if ($username) {
            // Marquer toutes les demandes pour cet utilisateur comme traitées
            $stmt = $pdo->prepare("UPDATE disconnect_requests SET processed = 1, processed_at = NOW() WHERE username = ? AND processed = 0");
            $stmt->execute([$username]);

            // Fermer la session dans la base
            $stmt = $pdo->prepare("
                UPDATE sessions
                SET stop_time = NOW(), terminate_cause = 'Admin-Reset'
                WHERE username = ? AND stop_time IS NULL
            ");
            $stmt->execute([$username]);
        }

        jsonSuccess(null, __('api.session_disconnect_confirmed'));
    }

    /**
     * POST /api/sessions/request-disconnect
     * Ajoute une demande de déconnexion pour un utilisateur
     * Le MikroTik viendra la récupérer lors du prochain sync
     */
    public function requestDisconnect(array $params): void
    {
        $id = (int)$params['id'];
        $session = $this->db->getSessionById($id);

        if (!$session) {
            jsonError(__('api.session_not_found'), 404);
        }

        if ($session['stop_time'] !== null) {
            jsonError(__('api.session_already_terminated'), 400);
        }

        $pdo = $this->db->getPdo();

        // Vérifier si la table existe, sinon la créer
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS disconnect_requests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(64) NOT NULL,
                acct_session_id VARCHAR(64),
                requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                processed TINYINT(1) DEFAULT 0,
                processed_at DATETIME NULL,
                INDEX idx_processed (processed),
                INDEX idx_username (username)
            )
        ");

        // Ajouter la demande de déconnexion
        $stmt = $pdo->prepare("
            INSERT INTO disconnect_requests (username, acct_session_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$session['username'], $session['acct_session_id']]);

        jsonSuccess([
            'request_id' => $pdo->lastInsertId(),
            'username' => $session['username']
        ], __('api.session_disconnect_queued'));
    }

    /**
     * POST /api/sessions/login
     * Appelé par le hook on-login du hotspot MikroTik
     */
    public function login(): void
    {
        $data = getJsonBody();
        $username = $data['user'] ?? null;
        $nasIp = $data['nas_ip'] ?? $_SERVER['REMOTE_ADDR'];

        if (!$username) {
            jsonError(__('api.session_username_required'), 400);
        }

        $voucher = $this->db->getVoucherByUsername($username);
        if (!$voucher) {
            jsonSuccess(['status' => 'ignored', 'reason' => 'Unknown user']);
            return;
        }

        $pdo = $this->db->getPdo();
        $sessionId = $data['session-id'] ?? uniqid();

        // Créer la session (inclure admin_id pour le filtrage multi-tenant)
        $stmt = $pdo->prepare("
            INSERT INTO sessions (
                voucher_id, acct_session_id, nas_ip, username,
                client_ip, client_mac, start_time, last_update, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
            ON DUPLICATE KEY UPDATE last_update = NOW()
        ");
        $stmt->execute([
            $voucher['id'],
            $sessionId,
            $nasIp,
            $username,
            $data['address'] ?? null,
            $data['mac-address'] ?? null,
            $voucher['admin_id'] ?? null
        ]);

        // Mettre à jour first_use si c'est la première connexion
        if ($voucher['status'] === 'unused') {
            $this->db->updateVoucherStatus($voucher['id'], 'active');
            $pdo->prepare("UPDATE vouchers SET first_use = NOW() WHERE id = ? AND first_use IS NULL")
                ->execute([$voucher['id']]);

            // Tracker la vente : trouver le NAS et le vendeur assigné
            $this->trackSale($voucher, $nasIp, $pdo);
        }

        jsonSuccess(['status' => 'ok', 'session_id' => $sessionId]);
    }

    /**
     * Track la vente d'un voucher lors de la première connexion
     */
    private function trackSale(array $voucher, string $nasIp, PDO $pdo): void
    {
        // Trouver le NAS par son IP exacte
        $stmt = $pdo->prepare("SELECT id, zone_id FROM nas WHERE nasname = ?");
        $stmt->execute([$nasIp]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        // Si pas trouvé, chercher un NAS wildcard (0.0.0.0/0)
        if (!$nas) {
            $stmt = $pdo->prepare("SELECT id, zone_id FROM nas WHERE nasname = '0.0.0.0/0' LIMIT 1");
            $stmt->execute();
            $nas = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$nas) {
            return; // Aucun NAS trouvé, on ne peut pas tracker
        }

        $nasId = $nas['id'];
        $zoneId = $nas['zone_id'];

        // Trouver le vendeur assigné à ce NAS (le premier avec can_manage = 1)
        $stmt = $pdo->prepare("
            SELECT un.user_id, u.role, u.parent_id
            FROM user_nas un
            JOIN users u ON un.user_id = u.id
            WHERE un.nas_id = ? AND un.can_manage = 1 AND u.is_active = 1
            ORDER BY u.role = 'vendeur' DESC, un.assigned_at ASC
            LIMIT 1
        ");
        $stmt->execute([$nasId]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        $sellerId = $seller['user_id'] ?? null;
        $sellerRole = $seller['role'] ?? null;
        $parentId = $seller['parent_id'] ?? null;

        // Récupérer le prix du profil
        $profilePrice = 0;
        if ($voucher['profile_id']) {
            $stmt = $pdo->prepare("SELECT price FROM profiles WHERE id = ?");
            $stmt->execute([$voucher['profile_id']]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            $profilePrice = $profile['price'] ?? 0;
        }

        // Calculer les commissions selon les taux configurés
        $commissions = $this->calculateCommissions($pdo, $profilePrice, $sellerId, $zoneId, $voucher['profile_id']);

        // Détecter si c'est un paiement en ligne (sold_at et payment_method déjà définis)
        $isOnlinePayment = !empty($voucher['sold_at']) && !empty($voucher['payment_method']);

        if ($isOnlinePayment) {
            // Ticket payé en ligne : ne mettre à jour que NAS, vendeur et commissions
            // Ne PAS écraser payment_method, sale_amount, sold_at
            $stmt = $pdo->prepare("
                UPDATE vouchers SET
                    sold_by = COALESCE(sold_by, ?),
                    sold_on_nas_id = ?,
                    commission_vendeur = ?,
                    commission_gerant = ?,
                    commission_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $sellerId,
                $nasId,
                $commissions['vendeur'],
                $commissions['gerant'],
                $commissions['admin'],
                $voucher['id']
            ]);
        } else {
            // Ticket classique (cash) : mise à jour complète
            $stmt = $pdo->prepare("
                UPDATE vouchers SET
                    sold_by = ?,
                    sold_at = NOW(),
                    sold_on_nas_id = ?,
                    payment_method = 'cash',
                    sale_amount = ?,
                    commission_vendeur = ?,
                    commission_gerant = ?,
                    commission_admin = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $sellerId,
                $nasId,
                $profilePrice,
                $commissions['vendeur'],
                $commissions['gerant'],
                $commissions['admin'],
                $voucher['id']
            ]);
        }
    }

    /**
     * Calculer les commissions pour une vente
     */
    private function calculateCommissions(PDO $pdo, float $amount, ?int $sellerId, ?int $zoneId, ?int $profileId): array
    {
        $commissions = [
            'vendeur' => 0,
            'gerant' => 0,
            'admin' => 0
        ];

        if ($amount <= 0) {
            return $commissions;
        }

        // Récupérer les taux de commission pour chaque rôle
        $roles = ['vendeur', 'gerant', 'admin'];

        foreach ($roles as $role) {
            // Chercher le taux le plus spécifique (zone + profil > zone > profil > global)
            $stmt = $pdo->prepare("
                SELECT rate_type, rate_value
                FROM commission_rates
                WHERE role = ? AND is_active = 1
                  AND (zone_id IS NULL OR zone_id = ?)
                  AND (profile_id IS NULL OR profile_id = ?)
                ORDER BY
                    (zone_id IS NOT NULL AND profile_id IS NOT NULL) DESC,
                    (zone_id IS NOT NULL) DESC,
                    (profile_id IS NOT NULL) DESC
                LIMIT 1
            ");
            $stmt->execute([$role, $zoneId, $profileId]);
            $rate = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rate) {
                if ($rate['rate_type'] === 'percentage') {
                    $commissions[$role] = round($amount * ($rate['rate_value'] / 100), 2);
                } else {
                    $commissions[$role] = (float)$rate['rate_value'];
                }
            }
        }

        return $commissions;
    }

    /**
     * POST /api/sessions/logout
     * Appelé par le hook on-logout du hotspot MikroTik
     */
    public function logout(): void
    {
        $data = getJsonBody();
        $username = $data['user'] ?? null;
        $sessionId = $data['session-id'] ?? null;
        $nasIp = $data['nas_ip'] ?? $_SERVER['REMOTE_ADDR'];

        if (!$username && !$sessionId) {
            jsonError(__('api.session_username_or_session_id_required'), 400);
        }

        $pdo = $this->db->getPdo();

        if ($sessionId) {
            $stmt = $pdo->prepare("
                UPDATE sessions
                SET stop_time = NOW(), terminate_cause = 'User-Request'
                WHERE acct_session_id = ? AND stop_time IS NULL
            ");
            $stmt->execute([$sessionId]);
        } elseif ($username) {
            $stmt = $pdo->prepare("
                UPDATE sessions
                SET stop_time = NOW(), terminate_cause = 'User-Request'
                WHERE username = ? AND nas_ip = ? AND stop_time IS NULL
            ");
            $stmt->execute([$username, $nasIp]);
        }

        jsonSuccess(['status' => 'ok', 'closed' => $stmt->rowCount()]);
    }
}
