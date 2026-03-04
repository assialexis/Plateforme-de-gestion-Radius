<?php
/**
 * Générateur dynamique de script setup MikroTik
 *
 * Génère un script .rsc personnalisé par routeur incluant :
 * - Nettoyage des anciens scripts/schedulers
 * - Walled Garden dynamique (basé sur les passerelles actives)
 * - Script de polling (nas-cmd) avec authentification par token
 * - Script de sync (nas-sync) pour synchronisation des sessions
 * - Schedulers pour l'exécution automatique
 *
 * @package NAS
 */

class SetupScriptGenerator
{
    private PDO $pdo;
    private string $serverUrl;

    public function __construct(PDO $pdo, string $serverUrl)
    {
        $this->pdo = $pdo;
        $this->serverUrl = rtrim($serverUrl, '/');
    }

    /**
     * Générer le script setup complet pour un routeur
     */
    public function generate(string $routerId): string
    {
        $nas = $this->getNasInfo($routerId);
        if (!$nas) {
            return "# ERROR: Router ID not found\n";
        }

        $adminId = $nas['admin_id'];
        $pollingToken = $nas['polling_token'] ?? '';
        $pollingInterval = $nas['polling_interval'] ?? 10;
        $fetchUrl = $this->serverUrl . '/fetch_cmd.php';
        $syncUrl = $this->serverUrl . '/api.php?route=/router-sync/sync';

        $walledGardenRules = $this->getWalledGardenRules($adminId);

        $script = '';
        $script .= $this->generateHeader($routerId, $nas);
        $script .= $this->generateCleanup();
        $script .= $this->generateWalledGarden($walledGardenRules);
        $script .= $this->generatePollingScript($routerId, $fetchUrl, $pollingToken);
        $script .= $this->generateSyncScript($routerId, $syncUrl, $pollingToken);
        $script .= $this->generateSchedulers($pollingInterval);
        $script .= $this->generateVerification();

        return $script;
    }

    /**
     * Obtenir les infos du NAS
     */
    private function getNasInfo(string $routerId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, router_id, admin_id, shortname, polling_token, polling_interval
             FROM nas WHERE router_id = ? LIMIT 1"
        );
        $stmt->execute([$routerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Récupérer les règles walled garden basées sur les passerelles actives
     */
    private function getWalledGardenRules(?int $adminId): array
    {
        // Récupérer les passerelles actives
        $stmt = $this->pdo->query(
            "SELECT DISTINCT pg.gateway_code, pg.name
             FROM payment_gateways pg
             WHERE pg.is_active = 1
             ORDER BY pg.gateway_code"
        );
        $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $rules = [];
        foreach ($gateways as $gw) {
            $stmt = $this->pdo->prepare(
                "SELECT domain, port, description FROM gateway_walled_garden WHERE gateway_code = ?"
            );
            $stmt->execute([$gw['gateway_code']]);
            $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($domains)) {
                $rules[] = [
                    'gateway_name' => $gw['name'],
                    'gateway_code' => $gw['gateway_code'],
                    'domains' => $domains
                ];
            }
        }

        return $rules;
    }

    /**
     * En-tête du script
     */
    private function generateHeader(string $routerId, array $nas): string
    {
        $date = date('d/m/Y H:i:s');
        $name = $nas['shortname'] ?? $routerId;
        return <<<RSC
# =====================================================
# NAS — INSTALLATION SCRIPT [RouterOS v7]
# Collez TOUT dans le Terminal MikroTik (Winbox ou SSH)
# Genere: {$date}
# Routeur: {$name} ({$routerId})
# Serveur: {$this->serverUrl}
# =====================================================


RSC;
    }

    /**
     * Nettoyage des anciens scripts et schedulers
     */
    private function generateCleanup(): string
    {
        return <<<'RSC'
# --- Nettoyage Scripts & Schedulers ---
/system scheduler remove [find where name~"nas-"]
:delay 1s
/system script remove [find where name~"nas-"]
:delay 1s

# --- Walled Garden: Nettoyage des regles NAS ---
:foreach i in=[/ip hotspot walled-garden find where comment~"NAS-"] do={
    /ip hotspot walled-garden remove $i
}
:delay 500ms
:put "=== Nettoyage termine ==="


RSC;
    }

    /**
     * Règles Walled Garden
     */
    private function generateWalledGarden(array $rules): string
    {
        $output = "# --- Walled Garden: Serveur NAS ---\n";

        // Toujours ajouter le serveur NAS lui-même
        $serverHost = parse_url($this->serverUrl, PHP_URL_HOST);
        if ($serverHost) {
            $output .= "/ip hotspot walled-garden add dst-host=\"{$serverHost}\" dst-port=80,443 action=allow comment=\"NAS-Server\"\n";
            // Ajouter aussi le wildcard pour les sous-domaines
            $output .= "/ip hotspot walled-garden add dst-host=\"*.{$serverHost}\" dst-port=80,443 action=allow comment=\"NAS-Server\"\n";
        }

        foreach ($rules as $rule) {
            $gwCode = strtoupper($rule['gateway_code']);
            $output .= "\n# --- Walled Garden: {$rule['gateway_name']} ---\n";
            foreach ($rule['domains'] as $domain) {
                $output .= "/ip hotspot walled-garden add dst-host=\"{$domain['domain']}\" ";
                $output .= "dst-port={$domain['port']} action=allow ";
                $output .= "comment=\"NAS-{$gwCode}\"\n";
            }
        }

        $output .= "\n:delay 500ms\n";
        $output .= ":put \"=== Walled Garden configure ===\"\n\n\n";
        return $output;
    }

    /**
     * Script de polling principal (nas-cmd)
     */
    private function generatePollingScript(string $routerId, string $fetchUrl, string $pollingToken): string
    {
        // Échapper pour l'intérieur du script RouterOS
        $tokenHeader = '';
        if (!empty($pollingToken)) {
            $tokenHeader = ",X-NAS-Token:{$pollingToken}";
        }

        // Déterminer le mode HTTP (http ou https)
        $mode = (str_starts_with($fetchUrl, 'https')) ? 'https' : 'http';
        $certCheck = ($mode === 'https') ? 'no' : '';
        $certOpt = ($mode === 'https') ? " check-certificate=no" : '';

        return <<<RSC
# --- Creation Script de Polling (nas-cmd) ---
/system script add name="nas-cmd" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
    :local routerId "{$routerId}"
    :local fetchUrl "{$fetchUrl}"

    :do {
        # Recuperer la prochaine commande
        /tool fetch url=(\$fetchUrl . "?router=" . \$routerId) mode={$mode}{$certOpt} http-header-field="Accept:text/plain{$tokenHeader}" output=file dst-path="nas-cmd.rsc"
        :delay 500ms

        # Lire le contenu du fichier
        :local fileContent ""
        :do {
            :set fileContent [/file get [find name="nas-cmd.rsc"] contents]
        } on-error={}

        # Verifier si c'est une commande (commence par "# CMD:")
        :if ([:pick \$fileContent 0 6] = "# CMD:") do={
            # Extraire l'ID de la commande
            :local lineEnd [:find \$fileContent "\\n"]
            :if ([:typeof \$lineEnd] = "nil") do={ :set lineEnd [:len \$fileContent] }
            :local cmdLine [:pick \$fileContent 6 \$lineEnd]

            # Nettoyer les caracteres \\r
            :local cmdId ""
            :for i from=0 to=([:len \$cmdLine] - 1) do={
                :local char [:pick \$cmdLine \$i (\$i + 1)]
                :if (\$char ~ "^[0-9]\\\$") do={
                    :set cmdId (\$cmdId . \$char)
                }
            }

            :log info ("NAS: Commande recue ID=" . \$cmdId)

            # Executer la commande
            :do {
                /import file-name="nas-cmd.rsc"
                :delay 1s

                # Confirmer l'execution
                :local confirmUrl (\$fetchUrl . "?router=" . \$routerId . "&done=" . \$cmdId)
                :do {
                    /tool fetch url=\$confirmUrl mode={$mode}{$certOpt} http-header-field="Accept:text/plain{$tokenHeader}" output=none
                    :log info ("NAS: Commande " . \$cmdId . " confirmee")
                } on-error={
                    :log warning ("NAS: Echec confirmation " . \$cmdId)
                }
            } on-error={
                :log error ("NAS: Erreur execution " . \$cmdId)
                # Signaler l'erreur
                :do {
                    :local failUrl (\$fetchUrl . "?router=" . \$routerId . "&fail=" . \$cmdId . "&error=import_error")
                    /tool fetch url=\$failUrl mode={$mode}{$certOpt} http-header-field="Accept:text/plain{$tokenHeader}" output=none
                } on-error={}
            }
        }

        # Supprimer le fichier local
        :do {
            /file remove "nas-cmd.rsc"
        } on-error={}

    } on-error={}
}
:delay 500ms


RSC;
    }

    /**
     * Script de synchronisation (nas-sync)
     */
    private function generateSyncScript(string $routerId, string $syncUrl, string $pollingToken): string
    {
        $tokenHeader = '';
        if (!empty($pollingToken)) {
            $tokenHeader = ",X-NAS-Token:{$pollingToken}";
        }

        $mode = (str_starts_with($syncUrl, 'https')) ? 'https' : 'http';
        $certOpt = ($mode === 'https') ? " check-certificate=no" : '';

        return <<<RSC
# --- Creation Script de Synchronisation (nas-sync) ---
/system script add name="nas-sync" policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive source={
    :local routerId "{$routerId}"
    :local syncUrl "{$syncUrl}"

    :do {
        # Collecter les sessions hotspot actives
        :local hotspotUsers ""
        :local hsCount 0
        :foreach i in=[/ip hotspot active find] do={
            :local user [/ip hotspot active get \$i user]
            :local ip [/ip hotspot active get \$i address]
            :local mac [/ip hotspot active get \$i mac-address]
            :local uptime [/ip hotspot active get \$i uptime]
            :if (\$hsCount > 0) do={ :set hotspotUsers (\$hotspotUsers . ",") }
            :set hotspotUsers (\$hotspotUsers . "{\"u\":\"" . \$user . "\",\"ip\":\"" . \$ip . "\",\"mac\":\"" . \$mac . "\"}")
            :set hsCount (\$hsCount + 1)
        }

        # Collecter les sessions PPPoE actives
        :local pppoeUsers ""
        :local ppCount 0
        :foreach i in=[/ppp active find] do={
            :local user [/ppp active get \$i name]
            :local ip [/ppp active get \$i address]
            :local uptime [/ppp active get \$i uptime]
            :local service [/ppp active get \$i service]
            :if (\$ppCount > 0) do={ :set pppoeUsers (\$pppoeUsers . ",") }
            :set pppoeUsers (\$pppoeUsers . "{\"u\":\"" . \$user . "\",\"ip\":\"" . \$ip . "\",\"svc\":\"" . \$service . "\"}")
            :set ppCount (\$ppCount + 1)
        }

        # Collecter les infos systeme
        :local cpuLoad [/system resource get cpu-load]
        :local freeMemory [/system resource get free-memory]
        :local totalMemory [/system resource get total-memory]
        :local uptime [/system resource get uptime]
        :local version [/system resource get version]

        # Construire le JSON
        :local jsonData ("{\"router_id\":\"" . \$routerId . "\",\"hotspot\":[" . \$hotspotUsers . "],\"pppoe\":[" . \$pppoeUsers . "],\"system\":{\"cpu\":" . \$cpuLoad . ",\"free_mem\":" . \$freeMemory . ",\"total_mem\":" . \$totalMemory . ",\"uptime\":\"" . \$uptime . "\",\"version\":\"" . \$version . "\"}}")

        # Envoyer au serveur
        /tool fetch url=\$syncUrl mode={$mode}{$certOpt} http-method=post http-data=\$jsonData http-header-field="Content-Type:application/json{$tokenHeader}" output=none
        :log info ("NAS-SYNC: Sync envoye - HS:" . \$hsCount . " PPPoE:" . \$ppCount)

    } on-error={
        :log warning "NAS-SYNC: Echec synchronisation"
    }
}
:delay 500ms


RSC;
    }

    /**
     * Schedulers pour l'exécution automatique
     */
    private function generateSchedulers(int $pollingInterval): string
    {
        $syncInterval = '5m';

        return <<<RSC
# --- Creation Schedulers ---
/system scheduler add name="nas-cmd" interval={$pollingInterval}s on-event="/system script run nas-cmd" start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive
/system scheduler add name="nas-sync" interval={$syncInterval} on-event="/system script run nas-sync" start-time=startup policy=ftp,reboot,read,write,policy,test,password,sniff,romon,sensitive
:delay 500ms


RSC;
    }

    /**
     * Vérification finale
     */
    private function generateVerification(): string
    {
        return <<<'RSC'
# --- Verification ---
:log info "NAS: Installation terminee!"
:put "=== NAS INSTALLE ==="
/system script print where name~"nas-"
/system scheduler print where name~"nas-"
:put "=== WALLED GARDEN ==="
/ip hotspot walled-garden print where comment~"NAS-"
:put "=== INSTALLATION COMPLETE ==="
RSC;
    }

    /**
     * Générer ou régénérer le polling token pour un routeur
     */
    public function generatePollingToken(string $routerId): ?string
    {
        $token = bin2hex(random_bytes(32)); // 64 caractères hex

        $stmt = $this->pdo->prepare("UPDATE nas SET polling_token = ? WHERE router_id = ?");
        $stmt->execute([$token, $routerId]);

        if ($stmt->rowCount() > 0) {
            return $token;
        }
        return null;
    }

    /**
     * Obtenir le statut de connexion d'un routeur
     */
    public function getRouterStatus(string $routerId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT last_seen, polling_token, polling_interval, setup_installed_at,
                    TIMESTAMPDIFF(SECOND, last_seen, NOW()) as last_seen_ago
             FROM nas WHERE router_id = ?"
        );
        $stmt->execute([$routerId]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nas) {
            return ['online' => false, 'last_seen' => null, 'has_token' => false];
        }

        $ago = $nas['last_seen_ago'];
        $isOnline = $nas['last_seen'] && $ago !== null && $ago < 30;

        return [
            'online' => $isOnline,
            'last_seen' => $nas['last_seen'],
            'last_seen_ago' => $ago,
            'has_token' => !empty($nas['polling_token']),
            'polling_interval' => $nas['polling_interval'],
            'setup_installed_at' => $nas['setup_installed_at'],
        ];
    }
}
