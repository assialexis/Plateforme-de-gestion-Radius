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
     * Envoyer un webhook à un nœud RADIUS spécifique
     * Non-bloquant : si le push échoue, le nœud récupérera les données au prochain pull
     */
    private function pushToNode(array $server, string $event, array $data): void
    {
        $url = 'https://' . $server['host'];
        if (!empty($server['webhook_port']) && $server['webhook_port'] != 443) {
            $url .= ':' . $server['webhook_port'];
        }
        $url .= ($server['webhook_path'] ?? '/webhook.php');

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
}
