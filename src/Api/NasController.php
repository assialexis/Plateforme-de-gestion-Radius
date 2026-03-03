<?php
/**
 * Controller API NAS
 */

require_once __DIR__ . '/../MikroTik/CommandSender.php';

class NasController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private MikroTikCommandSender $commandSender;
    private ?NodePushService $pushService;

    public function __construct(RadiusDatabase $db, AuthService $auth, ?NodePushService $pushService = null)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->commandSender = new MikroTikCommandSender($db->getPdo());
        $this->pushService = $pushService;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /api/nas
     */
    public function index(): void
    {
        $zoneId = isset($_GET['zone']) ? (int)$_GET['zone'] : null;

        if ($zoneId) {
            $nasList = $this->db->getNasByZone($zoneId);
        } else {
            $nasList = $this->db->getAllNas($this->getAdminId());
        }

        jsonSuccess($nasList);
    }

    /**
     * GET /api/nas/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $nas = $this->db->getNasById($id);

        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        jsonSuccess($nas);
    }

    /**
     * POST /api/nas
     */
    public function store(): void
    {
        $data = getJsonBody();

        if (empty($data['shortname'])) {
            jsonError(__('api.nas_name_required'), 400);
        }

        if (empty($data['secret'])) {
            jsonError(__('api.nas_secret_required'), 400);
        }

        $adminId = $this->getAdminId();
        $data['admin_id'] = $adminId;

        // Calculer la date d'expiration du NAS selon la durée de validité configurée
        try {
            $pdo = $this->db->getPdo();
            $stmtValidity = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'nas_validity_days'");
            $stmtValidity->execute();
            $validityDays = (int)$stmtValidity->fetchColumn();
            if ($validityDays > 0) {
                $data['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));
            }
        } catch (\Exception $e) {
            // Table/setting not yet created, no expiration
        }

        // Vérification des crédits pour l'ajout d'un NAS (seulement pour les admins)
        $creditDeducted = false;
        $newBalance = 0;
        if ($adminId !== null) {
            try {
                $pdo = $this->db->getPdo();

                $stmtSys = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_system_enabled'");
                $stmtSys->execute();
                $creditSystemEnabled = $stmtSys->fetchColumn() === '1';

                $stmtCost = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'nas_creation_cost'");
                $stmtCost->execute();
                $nasCost = (float)$stmtCost->fetchColumn();

                if ($creditSystemEnabled && $nasCost > 0) {
                    $stmtBal = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ?");
                    $stmtBal->execute([$adminId]);
                    $balance = (float)$stmtBal->fetchColumn();

                    if ($balance < $nasCost) {
                        jsonError(__('credits.insufficient_balance_nas') ?? 'Solde de crédits insuffisant pour ajouter un routeur (' . $nasCost . ' crédits requis)', 402);
                        return;
                    }

                    // Déduire les crédits
                    $newBalance = $balance - $nasCost;
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare("UPDATE users SET credit_balance = ? WHERE id = ?")
                            ->execute([$newBalance, $adminId]);

                        $pdo->prepare("
                            INSERT INTO credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                            VALUES (?, 'module_activation', ?, ?, 'nas', ?, ?)
                        ")->execute([
                            $adminId, -$nasCost, $newBalance, $data['shortname'],
                            'Ajout routeur NAS: ' . $data['shortname']
                        ]);

                        $id = $this->db->createNas($data);
                        $nas = $this->db->getNasById($id);

                        $pdo->commit();
                        $creditDeducted = true;

                        // Push temps réel vers les nœuds RADIUS
                        if ($this->pushService && $nas) {
                            $this->pushService->notifyNasChange('created', $nas);
                        }

                        jsonSuccess(array_merge($nas, [
                            'credits_deducted' => $nasCost,
                            'new_balance' => $newBalance
                        ]), __('api.nas_created'));
                        return;
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        if (strpos($e->getMessage(), 'Duplicate') !== false) {
                            jsonError(__('api.nas_router_id_exists'), 400);
                            return;
                        }
                        jsonError(__('api.nas_create_failed') . ': ' . $e->getMessage(), 500);
                        return;
                    }
                }
            } catch (Exception $e) {
                // Tables pas encore créées, continuer normalement
            }
        }

        // Création sans facturation (superadmin ou système crédits désactivé)
        try {
            $id = $this->db->createNas($data);
            $nas = $this->db->getNasById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $nas) {
                $this->pushService->notifyNasChange('created', $nas);
            }

            jsonSuccess($nas, __('api.nas_created'));
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonError(__('api.nas_router_id_exists'), 400);
            }
            jsonError(__('api.nas_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/nas/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $nas = $this->db->getNasById($id);
        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        if (empty($data['shortname'])) {
            jsonError(__('api.nas_name_required'), 400);
        }

        if (empty($data['secret'])) {
            jsonError(__('api.nas_secret_required'), 400);
        }

        try {
            $this->db->updateNas($id, $data);
            $nas = $this->db->getNasById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $nas) {
                $this->pushService->notifyNasChange('updated', $nas);
            }

            jsonSuccess($nas, __('api.nas_updated'));
        } catch (Exception $e) {
            jsonError(__('api.nas_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/nas/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $nas = $this->db->getNasById($id);
        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        try {
            // Push temps réel vers les nœuds RADIUS (avant suppression)
            if ($this->pushService && $nas) {
                $this->pushService->notifyNasChange('deleted', $nas);
            }

            $this->db->deleteNas($id);
            jsonSuccess(null, __('api.nas_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.nas_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/nas/generate-id
     */
    public function generateId(): void
    {
        try {
            $routerId = $this->db->generateRouterId();
            jsonSuccess(['router_id' => $routerId]);
        } catch (Exception $e) {
            jsonError(__('api.nas_generate_id_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/nas/{id}/ping
     * Ping ICMP classique (ne passe plus par l'API automatiquement)
     */
    public function ping(array $params): void
    {
        $id = (int)$params['id'];

        $nas = $this->db->getNasById($id);
        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        // Ping ICMP classique - utiliser mikrotik_host si nasname est wildcard
        $host = $nas['nasname'];
        if (strpos($host, '/') !== false && !empty($nas['mikrotik_host'])) {
            $host = $nas['mikrotik_host'];
        }

        if (strpos($host, '/') !== false) {
            jsonSuccess([
                'reachable' => false,
                'method' => 'icmp',
                'message' => __('api.nas_wildcard_address')
            ]);
            return;
        }

        $output = [];
        $returnCode = 0;

        if (PHP_OS_FAMILY === 'Windows') {
            exec("ping -n 1 -w 2000 " . escapeshellarg($host), $output, $returnCode);
        } else {
            exec("ping -c 1 -W 2 " . escapeshellarg($host) . " 2>&1", $output, $returnCode);
        }

        $reachable = ($returnCode === 0);
        $latency = null;

        if ($reachable) {
            foreach ($output as $line) {
                if (preg_match('/time[=<](\d+(?:\.\d+)?)\s*ms/i', $line, $matches)) {
                    $latency = (float)$matches[1];
                    break;
                }
            }
        }

        jsonSuccess([
            'reachable' => $reachable,
            'method' => 'icmp',
            'latency' => $latency,
            'host' => $host,
            'message' => $reachable ? __('api.nas_reachable_icmp') : __('api.nas_unreachable')
        ]);
    }

    /**
     * POST /api/nas/{id}/ping-api
     * Test connexion API RouterOS séparément
     */
    public function pingApiDirect(array $params): void
    {
        $id = (int)$params['id'];

        $nas = $this->db->getNasById($id);
        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        if (empty($nas['mikrotik_host']) || empty($nas['mikrotik_api_username'])) {
            jsonSuccess([
                'reachable' => false,
                'method' => 'api',
                'configured' => false,
                'message' => __('api.nas_api_not_configured')
            ]);
            return;
        }

        $this->pingViaApi($nas);
    }

    /**
     * Ping via API RouterOS - retourne identité, uptime, version, etc.
     */
    private function pingViaApi(array $nas): void
    {
        $host = $nas['mikrotik_host'];
        $port = (int)($nas['mikrotik_api_port'] ?: 8728);
        $username = $nas['mikrotik_api_username'];
        $password = $nas['mikrotik_api_password'] ?? '';
        $useSsl = !empty($nas['mikrotik_use_ssl']);

        $scheme = $useSsl ? 'ssl' : 'tcp';
        $context = stream_context_create();
        if ($useSsl) {
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        }

        $startTime = microtime(true);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            jsonSuccess([
                'reachable' => false,
                'method' => 'api',
                'message' => __('api.nas_api_unreachable') . ': ' . $errstr
            ]);
            return;
        }

        try {
            $this->mikrotikApiLogin($socket, $username, $password);
            $latency = round((microtime(true) - $startTime) * 1000);

            // Recuperer identite
            $identity = $this->mikrotikApiCommand($socket, '/system/identity/print');
            $name = $identity[0]['name'] ?? '';

            // Recuperer ressources (uptime, version, cpu, memoire)
            $resources = $this->mikrotikApiCommand($socket, '/system/resource/print');
            $uptime = $resources[0]['uptime'] ?? '';
            $version = $resources[0]['version'] ?? '';
            $cpuLoad = $resources[0]['cpu-load'] ?? '';
            $freeMemory = $resources[0]['free-memory'] ?? '';
            $totalMemory = $resources[0]['total-memory'] ?? '';
            $boardName = $resources[0]['board-name'] ?? '';

            // Calculer pourcentage memoire
            $memoryPercent = null;
            if ($totalMemory && $freeMemory) {
                $used = (int)$totalMemory - (int)$freeMemory;
                $memoryPercent = round(($used / (int)$totalMemory) * 100);
            }

            fclose($socket);

            jsonSuccess([
                'reachable' => true,
                'method' => 'api',
                'latency' => $latency,
                'identity' => $name,
                'uptime' => $uptime,
                'version' => $version,
                'board' => $boardName,
                'cpu_load' => $cpuLoad ? (int)$cpuLoad : null,
                'memory_percent' => $memoryPercent,
                'message' => __('api.nas_online') . ' - ' . $name
            ]);
        } catch (Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            jsonSuccess([
                'reachable' => false,
                'method' => 'api',
                'message' => 'API: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET /api/nas/{id}/clients
     * Obtenir les clients PPPoE assignés à ce NAS avec leur statut en ligne
     */
    public function clients(array $params): void
    {
        $id = (int)$params['id'];

        $nas = $this->db->getNasById($id);
        if (!$nas) {
            jsonError(__('api.nas_not_found'), 404);
        }

        try {
            $clients = $this->db->getPPPoEClientsByNas($id);
            jsonSuccess($clients);
        } catch (Exception $e) {
            jsonError(__('api.nas_get_clients_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/nas/test-api
     * Tester la connexion API MikroTik
     */
    public function testApi(): void
    {
        $data = getJsonBody();

        $host = $data['host'] ?? null;
        $port = (int)($data['port'] ?? 8728);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $useSsl = !empty($data['use_ssl']);

        if (empty($host)) {
            jsonError(__('api.nas_router_address_required'), 400);
            return;
        }

        // Test de connexion socket vers le port API
        $scheme = $useSsl ? 'ssl' : 'tcp';
        $context = stream_context_create();
        if ($useSsl) {
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
            stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
        }

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            "{$scheme}://{$host}:{$port}",
            $errno,
            $errstr,
            5,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            jsonError(__('api.nas_connection_failed') . ": {$errstr} ({$errno})", 400);
            return;
        }

        try {
            // Tentative d'authentification via l'API RouterOS
            $this->mikrotikApiLogin($socket, $username, $password);

            // Si login OK, recuperer l'identite
            $identity = $this->mikrotikApiCommand($socket, '/system/identity/print');

            fclose($socket);

            $name = '';
            if ($identity && isset($identity[0]['name'])) {
                $name = $identity[0]['name'];
            }

            jsonSuccess([
                'connected' => true,
                'identity' => $name,
                'message' => __('api.nas_api_connection_success')
            ]);
        } catch (Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            jsonError(__('api.nas_auth_failed') . ': ' . $e->getMessage(), 400);
        }
    }

    /**
     * Login via RouterOS API protocol
     */
    private function mikrotikApiLogin($socket, string $username, string $password): void
    {
        // Send /login command
        $this->mikrotikApiWrite($socket, '/login', ['=name=' . $username, '=password=' . $password]);
        $response = $this->mikrotikApiRead($socket);

        if (empty($response)) {
            throw new RuntimeException('Pas de reponse du routeur');
        }

        // Check for error
        foreach ($response as $line) {
            if (strpos($line, '!trap') === 0) {
                throw new RuntimeException('Identifiants incorrects');
            }
        }

        // RouterOS v6 challenge-response
        foreach ($response as $line) {
            if (strpos($line, '=ret=') === 0) {
                $challenge = substr($line, 5);
                $challengeBin = hex2bin($challenge);
                $hash = md5(chr(0) . $password . $challengeBin, true);
                $hexHash = '00' . bin2hex($hash);

                $this->mikrotikApiWrite($socket, '/login', ['=name=' . $username, '=response=' . $hexHash]);
                $response2 = $this->mikrotikApiRead($socket);

                foreach ($response2 as $line2) {
                    if (strpos($line2, '!trap') === 0) {
                        throw new RuntimeException('Identifiants incorrects');
                    }
                }
                return;
            }
        }
    }

    /**
     * Send a command via RouterOS API protocol
     */
    private function mikrotikApiCommand($socket, string $command): array
    {
        $this->mikrotikApiWrite($socket, $command);
        $response = $this->mikrotikApiRead($socket);

        $result = [];
        $current = [];
        foreach ($response as $line) {
            if ($line === '!re') {
                if (!empty($current)) {
                    $result[] = $current;
                }
                $current = [];
            } elseif (strpos($line, '=') === 0) {
                $parts = explode('=', substr($line, 1), 2);
                if (count($parts) === 2) {
                    $current[$parts[0]] = $parts[1];
                }
            }
        }
        if (!empty($current)) {
            $result[] = $current;
        }

        return $result;
    }

    /**
     * Write a word to RouterOS API socket
     */
    private function mikrotikApiWrite($socket, string $command, array $attributes = []): void
    {
        $this->mikrotikApiWriteWord($socket, $command);
        foreach ($attributes as $attr) {
            $this->mikrotikApiWriteWord($socket, $attr);
        }
        // End of sentence
        $this->mikrotikApiWriteWord($socket, '');
    }

    private function mikrotikApiWriteWord($socket, string $word): void
    {
        $len = strlen($word);
        if ($len < 0x80) {
            fwrite($socket, chr($len));
        } elseif ($len < 0x4000) {
            $len |= 0x8000;
            fwrite($socket, chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x200000) {
            $len |= 0xC00000;
            fwrite($socket, chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } elseif ($len < 0x10000000) {
            $len |= 0xE0000000;
            fwrite($socket, chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        } else {
            fwrite($socket, chr(0xF0) . chr(($len >> 24) & 0xFF) . chr(($len >> 16) & 0xFF) . chr(($len >> 8) & 0xFF) . chr($len & 0xFF));
        }
        if ($len > 0) {
            fwrite($socket, $word);
        }
    }

    /**
     * Read response from RouterOS API socket
     */
    private function mikrotikApiRead($socket): array
    {
        $response = [];
        stream_set_timeout($socket, 5);

        while (true) {
            $word = $this->mikrotikApiReadWord($socket);
            if ($word === false || $word === null) {
                break;
            }
            $response[] = $word;
            if ($word === '!done' || $word === '!fatal') {
                // Read remaining words in the sentence
                while (true) {
                    $extra = $this->mikrotikApiReadWord($socket);
                    if ($extra === false || $extra === null || $extra === '') {
                        break;
                    }
                    $response[] = $extra;
                }
                break;
            }
        }

        return $response;
    }

    private function mikrotikApiReadWord($socket)
    {
        $byte = fread($socket, 1);
        if ($byte === false || $byte === '') {
            return false;
        }

        $len = ord($byte);
        if ($len === 0) {
            return '';
        }

        if (($len & 0x80) === 0) {
            // 1 byte length
        } elseif (($len & 0xC0) === 0x80) {
            $len = (($len & ~0x80) << 8) | ord(fread($socket, 1));
        } elseif (($len & 0xE0) === 0xC0) {
            $next = fread($socket, 2);
            $len = (($len & ~0xC0) << 16) | (ord($next[0]) << 8) | ord($next[1]);
        } elseif (($len & 0xF0) === 0xE0) {
            $next = fread($socket, 3);
            $len = (($len & ~0xE0) << 24) | (ord($next[0]) << 16) | (ord($next[1]) << 8) | ord($next[2]);
        } elseif ($len === 0xF0) {
            $next = fread($socket, 4);
            $len = (ord($next[0]) << 24) | (ord($next[1]) << 16) | (ord($next[2]) << 8) | ord($next[3]);
        }

        $word = '';
        $remaining = $len;
        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $word .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $word;
    }

    /**
     * POST /api/nas/test-command
     * Envoyer une commande de test à tous les NAS pour vérifier le système de commandes
     */
    public function testCommand(): void
    {
        $nasList = $this->db->getAllNas();
        $sentTo = 0;

        foreach ($nasList as $nas) {
            $routerId = $nas['router_id'] ?? null;
            if (!$routerId) {
                continue;
            }

            // Créer un bridge de test avec un nom unique basé sur le timestamp
            $timestamp = time();
            $bridgeName = "nas-test-{$timestamp}";

            $command = <<<RSC
:log info "NAS: Test systeme de commandes"
:do {
    /interface bridge add name="{$bridgeName}" comment="Test NAS - A supprimer"
    :delay 2s
    /interface bridge remove [find name="{$bridgeName}"]
    :log info "NAS: Test reussi - Bridge cree et supprime"
} on-error={
    :log error "NAS: Echec du test"
}
RSC;

            if ($this->commandSender->send($routerId, $command, "test-cmd-{$timestamp}.rsc", 10)) {
                $sentTo++;
            }
        }

        if ($sentTo === 0) {
            jsonError(__('api.no_nas_with_router_id'), 400);
            return;
        }

        jsonSuccess([
            'sent_to' => $sentTo,
            'message' => __('api.nas_test_command_sent')
        ]);
    }
}
