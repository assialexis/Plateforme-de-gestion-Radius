<?php
/**
 * NodePushService - Push temps réel vers les nœuds RADIUS
 *
 * Quand un changement est fait sur la plateforme (voucher créé, profil modifié, etc.),
 * ce service pousse immédiatement le changement vers le(s) nœud(s) RADIUS concerné(s).
 * Si le push échoue, le changement sera récupéré au prochain pull périodique.
 */

class NodePushService
{
    private RadiusDatabase $db;

    public function __construct(RadiusDatabase $db)
    {
        $this->db = $db;
    }

    /**
     * Notifier les nœuds d'un événement sur un voucher
     */
    public function notifyVoucherChange(string $event, array $voucher): void
    {
        $zoneId = $voucher['zone_id'] ?? null;
        $this->pushToZoneNodes($zoneId, "voucher.{$event}", $voucher);
    }

    /**
     * Notifier les nœuds d'un événement sur un profil
     */
    public function notifyProfileChange(string $event, array $profile): void
    {
        $zoneId = $profile['zone_id'] ?? null;
        if ($zoneId === null) {
            // Profil global : notifier TOUS les nœuds
            $this->pushToAllNodes("profile.{$event}", $profile);
        } else {
            $this->pushToZoneNodes($zoneId, "profile.{$event}", $profile);
        }
    }

    /**
     * Notifier les nœuds d'un événement sur un NAS
     */
    public function notifyNasChange(string $event, array $nas): void
    {
        $zoneId = $nas['zone_id'] ?? null;
        $this->pushToZoneNodes($zoneId, "nas.{$event}", $nas);
    }

    /**
     * Notifier les nœuds d'un événement sur une zone
     */
    public function notifyZoneChange(string $event, array $zone): void
    {
        $serverId = $zone['radius_server_id'] ?? null;
        if ($serverId) {
            $server = $this->db->getRadiusServerById($serverId);
            if ($server && $server['is_active']) {
                $this->pushToNode($server, "zone.{$event}", $zone);
            }
        }
    }

    /**
     * Notifier les nœuds d'un événement sur un utilisateur PPPoE
     */
    public function notifyPPPoEUserChange(string $event, array $user): void
    {
        // Trouver la zone via le profil PPPoE
        $profileZoneId = $user['zone_id'] ?? null;
        if ($profileZoneId === null) {
            $this->pushToAllNodes("pppoe_user.{$event}", $user);
        } else {
            $this->pushToZoneNodes($profileZoneId, "pppoe_user.{$event}", $user);
        }
    }

    /**
     * Notifier le nœud d'un reset FUP (instantané, sans attendre le pull sync)
     */
    public function notifyFupReset(array $user, array $fupStatus): void
    {
        $data = [
            'id' => $user['id'],
            'username' => $user['username'],
            'fup_triggered' => 0,
            'fup_triggered_at' => null,
            'fup_data_used' => 0,
            'fup_data_offset' => $fupStatus['fup_data_offset'] ?? 0,
            'fup_last_reset' => $fupStatus['fup_last_reset'] ?? date('Y-m-d H:i:s'),
        ];

        $zoneId = $user['zone_id'] ?? null;
        if ($zoneId === null) {
            $this->pushToAllNodes('pppoe_user.fup_reset', $data);
        } else {
            $this->pushToZoneNodes($zoneId, 'pppoe_user.fup_reset', $data);
        }
    }

    /**
     * Pousser un événement vers les nœuds d'une zone spécifique
     */
    private function pushToZoneNodes(?int $zoneId, string $event, array $data): void
    {
        if ($zoneId === null) {
            // Pas de zone : notifier tous les nœuds
            $this->pushToAllNodes($event, $data);
            return;
        }

        $server = $this->db->getRadiusServerForZone($zoneId);
        if ($server && $server['is_active']) {
            $this->pushToNode($server, $event, $data);
        }
    }

    /**
     * Pousser un événement vers TOUS les nœuds actifs
     */
    private function pushToAllNodes(string $event, array $data): void
    {
        $servers = $this->db->getAllRadiusServers();
        foreach ($servers as $server) {
            if ($server['is_active']) {
                $this->pushToNode($server, $event, $data);
            }
        }
    }

    /**
     * Construire l'URL de base du webhook d'un nœud
     */
    private function buildNodeUrl(array $server): string
    {
        $host = $server['host'] ?? '';
        $port = (int)($server['webhook_port'] ?? 443);

        // Si le host inclut déjà le protocole, l'utiliser tel quel
        if (preg_match('#^https?://#', $host)) {
            $url = rtrim($host, '/');
        } else {
            // Choisir le protocole selon le port
            $protocol = ($port === 443) ? 'https' : 'http';
            $url = $protocol . '://' . $host;
        }

        // Ajouter le port seulement si non-standard
        if ($port && $port != 443 && $port != 80) {
            // Vérifier que le port n'est pas déjà dans l'URL
            if (!preg_match('#:\d+$#', parse_url($url, PHP_URL_HOST) . '')) {
                $url .= ':' . $port;
            }
        }

        $url .= ($server['webhook_path'] ?? '/webhook.php');
        return $url;
    }

    /**
     * Envoyer un webhook à un nœud RADIUS spécifique
     * Non-bloquant : si le push échoue, le nœud récupérera les données au prochain pull
     */
    private function pushToNode(array $server, string $event, array $data): void
    {
        $url = $this->buildNodeUrl($server);

        $payload = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => time(),
            'server_code' => $server['code'],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Platform-Token: ' . $server['platform_token'],
                'X-Event: ' . $event,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // Timeout court pour ne pas bloquer
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false, // En production, mettre true avec certificats valides
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $error) {
            // Log l'échec mais ne bloque pas - le pull rattrapera
            error_log("[NodePush] Push failed to {$server['code']} ({$server['host']}): HTTP {$httpCode}, Error: {$error}");
        }
    }

    /**
     * Interroger le nœud RADIUS pour obtenir le statut FUP en temps réel
     * Retourne ['data' => ..., 'error' => null] ou ['data' => null, 'error' => '...']
     */
    public function queryNodeFupStatus(int $userId, ?int $zoneId): array
    {
        $server = null;

        if ($zoneId !== null) {
            $server = $this->db->getRadiusServerForZone($zoneId);
        }

        // Fallback : chercher parmi tous les serveurs actifs
        if (!$server) {
            $servers = $this->db->getAllRadiusServers();
            foreach ($servers as $s) {
                if ($s['is_active']) {
                    $server = $s;
                    break;
                }
            }
        }

        if (!$server) {
            return ['data' => null, 'error' => 'Aucun serveur RADIUS configuré'];
        }

        if (!$server['is_active']) {
            return ['data' => null, 'error' => "Serveur RADIUS '{$server['name']}' désactivé"];
        }

        if (empty($server['host'])) {
            return ['data' => null, 'error' => "Champ 'host' vide pour le serveur '{$server['name']}'"];
        }

        $url = $this->buildNodeUrl($server) . '?action=fup_status&user_id=' . $userId;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'X-Platform-Token: ' . ($server['platform_token'] ?? ''),
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[NodePush] FUP query failed to {$server['code']} ({$url}): {$error}");
            return ['data' => null, 'error' => "Connexion échouée vers {$server['host']}: {$error}"];
        }

        if ($httpCode === 403) {
            return ['data' => null, 'error' => 'Token rejeté par le nœud (vérifier platform_token)'];
        }

        if ($httpCode !== 200) {
            error_log("[NodePush] FUP query failed to {$server['code']} ({$url}): HTTP {$httpCode}");
            return ['data' => null, 'error' => "Nœud a répondu HTTP {$httpCode} ({$url})"];
        }

        $result = json_decode($response, true);
        if (!$result || ($result['status'] ?? '') !== 'ok') {
            return ['data' => null, 'error' => 'Réponse invalide du nœud: ' . substr($response, 0, 200)];
        }

        return ['data' => $result['data'] ?? null, 'error' => null];
    }
}
