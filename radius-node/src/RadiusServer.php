<?php
/**
 * Serveur RADIUS
 */

require_once __DIR__ . '/RadiusPacket.php';
require_once __DIR__ . '/RadiusDatabase.php';

class RadiusServer
{
    private array $config;
    private RadiusDatabase $db;
    private $authSocket;
    private $acctSocket;
    private bool $running = true;
    private int $lastDbCheck = 0;
    private int $requestCount = 0;

    public function __construct(string $configFile)
    {
        $this->config = require $configFile;
        $this->db = new RadiusDatabase($this->config['database']);
        $this->lastDbCheck = time();

        $this->log("===========================================");
        $this->log("   RADIUS Manager Server - PHP Edition");
        $this->log("===========================================");
    }

    /**
     * Démarrer le serveur
     */
    public function start(): void
    {
        $this->createSockets();

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, [$this, 'shutdown']);
            pcntl_signal(SIGTERM, [$this, 'shutdown']);
        }

        $this->log("Server started successfully!");
        $this->log("Auth port: " . $this->config['radius']['auth_port']);
        $this->log("Acct port: " . $this->config['radius']['acct_port']);
        $this->log("Waiting for requests...");
        $this->log("-------------------------------------------");

        while ($this->running) {
            $read = [$this->authSocket, $this->acctSocket];
            $write = null;
            $except = null;

            $changed = @socket_select($read, $write, $except, 1);

            if ($changed === false) {
                $errno = socket_last_error();
                if ($errno !== 0 && $errno !== SOCKET_EINTR) {
                    $this->log("[ERROR] socket_select failed: " . socket_strerror($errno));
                    socket_clear_error();
                }
                continue;
            }

            if ($changed > 0) {
                foreach ($read as $socket) {
                    try {
                        $this->handlePacket($socket);
                        $this->requestCount++;
                    }
                    catch (\Throwable $e) {
                        $this->log("[ERROR] Exception handling packet: " . $e->getMessage());
                        $this->log("[ERROR] " . $e->getFile() . ":" . $e->getLine());
                        // Tenter de reconnecter la DB en cas d'erreur SQL
                        $this->reconnectDb();
                    }
                }
            }

            // Vérifier la connexion DB toutes les 60 secondes
            $now = time();
            if ($now - $this->lastDbCheck >= 60) {
                $this->lastDbCheck = $now;
                $this->ensureDbConnection();
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
    }

    /**
     * Créer les sockets UDP
     */
    private function createSockets(): void
    {
        $listenIp = $this->config['radius']['listen_ip'];

        // Socket authentification
        $this->authSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->authSocket) {
            throw new Exception("Cannot create auth socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->authSocket, SOL_SOCKET, SO_REUSEADDR, 1);

        if (!socket_bind($this->authSocket, $listenIp, $this->config['radius']['auth_port'])) {
            throw new Exception("Cannot bind auth socket: " . socket_strerror(socket_last_error()));
        }

        // Socket accounting
        $this->acctSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$this->acctSocket) {
            throw new Exception("Cannot create acct socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_option($this->acctSocket, SOL_SOCKET, SO_REUSEADDR, 1);

        if (!socket_bind($this->acctSocket, $listenIp, $this->config['radius']['acct_port'])) {
            throw new Exception("Cannot bind acct socket: " . socket_strerror(socket_last_error()));
        }

        socket_set_nonblock($this->authSocket);
        socket_set_nonblock($this->acctSocket);
    }

    /**
     * Gérer un paquet entrant
     */
    private function handlePacket($socket): void
    {
        $data = '';
        $clientIp = '';
        $clientPort = 0;

        $bytes = @socket_recvfrom($socket, $data, 4096, 0, $clientIp, $clientPort);

        if ($bytes === false || $bytes < 20) {
            return;
        }

        $this->log("Received {$bytes} bytes from {$clientIp}:{$clientPort}");

        $secret = $this->db->getNasSecret($clientIp);

        if (!$secret) {
            $this->log("  [ERROR] Unknown NAS: {$clientIp}");
            return;
        }

        $packet = RadiusPacket::decode($data, $secret);

        if (!$packet) {
            $this->log("  [ERROR] Invalid RADIUS packet");
            return;
        }

        $this->log("  Code: " . RadiusPacket::getCodeName($packet->code));
        $this->log("  ID: " . $packet->identifier);

        switch ($packet->code) {
            case RadiusPacket::ACCESS_REQUEST:
                $response = $this->handleAccessRequest($packet, $clientIp);
                break;

            case RadiusPacket::ACCOUNTING_REQUEST:
                $response = $this->handleAccountingRequest($packet, $clientIp);
                break;

            default:
                $this->log("  [ERROR] Unsupported packet type");
                return;
        }

        if ($response) {
            socket_sendto($socket, $response, strlen($response), 0, $clientIp, $clientPort);
            $this->log("  Response sent (" . strlen($response) . " bytes)");
        }

        $this->log("-------------------------------------------");
    }

    /**
     * Traiter une demande d'authentification
     */
    private function handleAccessRequest(RadiusPacket $packet, string $nasIp): ?string
    {
        $username = $packet->getUsername();
        $clientMac = $packet->getClientMac();
        $clientIp = $packet->getAttribute(RadiusPacket::ATTR_FRAMED_IP_ADDRESS);
        $nasIdentifier = $packet->getAttribute(RadiusPacket::ATTR_NAS_IDENTIFIER);

        $this->log("  User: {$username}");
        $this->log("  MAC: " . ($clientMac ?? 'N/A'));
        $this->log("  NAS-Identifier (attr 32): " . ($nasIdentifier ?? 'N/A'));
        $this->log("  NAS-IP-Address: {$nasIp}");

        // Autres attributs utiles pour identification
        $calledStationId = $packet->getAttribute(RadiusPacket::ATTR_CALLED_STATION_ID);
        $callingStationId = $packet->getAttribute(RadiusPacket::ATTR_CALLING_STATION_ID);
        $this->log("  Called-Station-Id (attr 30): " . ($calledStationId ?? 'N/A'));
        $this->log("  Calling-Station-Id (attr 31): " . ($callingStationId ?? 'N/A'));

        // Debug: afficher TOUS les attributs avec leurs valeurs
        $this->log("  === ALL ATTRIBUTES ===");
        foreach ($packet->attributes as $attrId => $attrValue) {
            if (is_string($attrValue) && strlen($attrValue) < 100) {
                // Afficher en hex si contient des caractères non-imprimables
                if (preg_match('/[^\x20-\x7E]/', $attrValue)) {
                    $this->log("    Attr {$attrId}: " . bin2hex($attrValue) . " (hex)");
                }
                else {
                    $this->log("    Attr {$attrId}: '{$attrValue}'");
                }
            }
            else {
                $this->log("    Attr {$attrId}: [binary or long data]");
            }
        }
        $this->log("  =======================");

        // Essayer PAP d'abord, puis CHAP
        $password = $packet->getPassword();
        $chapPassword = $packet->getAttribute(RadiusPacket::ATTR_CHAP_PASSWORD);

        if ($chapPassword) {
            $this->log("  Auth method: CHAP");
            // Pour CHAP, on doit vérifier différemment
            $password = $this->verifyChapPassword($packet, $username);
        }
        else if ($password) {
            $this->log("  Auth method: PAP");
        }

        if (!$username || !$password) {
            $this->log("  [REJECT] Missing credentials (PAP password: " . ($packet->getPassword() ? 'yes' : 'no') . ", CHAP: " . ($chapPassword ? 'yes' : 'no') . ")");
            $this->db->logAuth($username ?? 'unknown', $nasIp, 'reject', 'Missing credentials', $clientMac, $clientIp, $nasIdentifier);

            return RadiusPacket::createResponse(
                RadiusPacket::ACCESS_REJECT,
                $packet->identifier,
                $packet->authenticator,
                $packet->secret,
            [RadiusPacket::ATTR_REPLY_MESSAGE => 'Missing username or password']
            );
        }

        // Authentifier: d'abord essayer comme voucher, puis comme utilisateur PPPoE
        // On utilise NAS-Identifier (attr 32) car c'est l'identité système du MikroTik (/system/identity/print)
        $result = $this->db->authenticateVoucher($username, $password, $nasIp, $nasIdentifier);

        // Si le voucher n'est pas trouvé, essayer l'authentification PPPoE
        if (!$result['success'] && $result['reason'] === 'Voucher not found') {
            $this->log("  Voucher not found, trying PPPoE authentication...");
            $result = $this->db->authenticatePPPoEUser($username, $password, $nasIp, $nasIdentifier);
        }

        if (!$result['success']) {
            $this->log("  [REJECT] " . $result['reason']);

            // Logger selon le type d'authentification
            if (!empty($result['is_pppoe'])) {
                $this->db->logPPPoEAuth($username, $nasIp, $nasIdentifier, 'reject', $result['reason'], $clientMac, $callingStationId);
            }
            else {
                $this->db->logAuth($username, $nasIp, 'reject', $result['reason'], $clientMac, $clientIp, $nasIdentifier);
            }

            return RadiusPacket::createResponse(
                RadiusPacket::ACCESS_REJECT,
                $packet->identifier,
                $packet->authenticator,
                $packet->secret,
            [RadiusPacket::ATTR_REPLY_MESSAGE => $result['reason']]
            );
        }

        // Déterminer si c'est un voucher ou un utilisateur PPPoE
        $isPPPoE = !empty($result['is_pppoe']);
        $attributes = [];

        if ($isPPPoE) {
            // Authentification PPPoE réussie
            $user = $result['user'];
            $this->log("  [ACCEPT] PPPoE user valid (Profile: {$user['profile_name']})");
            $this->db->logPPPoEAuth($username, $nasIp, $nasIdentifier, 'accept', null, $clientMac, $callingStationId);

            // Session timeout (calculé depuis valid_until)
            if ($user['valid_until']) {
                $sessionTimeout = strtotime($user['valid_until']) - time();
                if ($sessionTimeout > 0) {
                    $attributes[RadiusPacket::ATTR_SESSION_TIMEOUT] = min($sessionTimeout, 86400); // Max 24h
                    $this->log("  Session-Timeout: {$sessionTimeout}s");
                }
            }

            // Idle timeout
            $idleTimeout = $this->config['options']['default_idle_timeout'] ?? 300;
            $attributes[RadiusPacket::ATTR_IDLE_TIMEOUT] = $idleTimeout;

            // Mikrotik Rate-Limit - Vérifier si FUP est actif
            $effectiveSpeed = $this->db->getEffectiveSpeed($user['id']);
            $upload = $effectiveSpeed['upload'] ?? $user['upload_speed'] ?? 0;
            $download = $effectiveSpeed['download'] ?? $user['download_speed'] ?? 0;
            $fupActive = $effectiveSpeed['fup_active'] ?? false;

            if ($upload || $download) {
                // Si FUP actif, pas de burst
                if ($fupActive) {
                    $rateLimitAttr = RadiusPacket::createMikrotikRateLimit($upload, $download);
                    $this->log("  [FUP ACTIVE] Rate-Limit réduit: {$download}/{$upload} bps");
                }
                elseif (!empty($user['burst_download']) && !empty($user['burst_upload'])) {
                    // Support burst si configuré et FUP non actif
                    $rateLimitAttr = RadiusPacket::createMikrotikRateLimitWithBurst(
                        $upload, $download,
                        $user['burst_upload'], $user['burst_download'],
                        $user['burst_threshold'] ?? 0, $user['burst_time'] ?? 0
                    );
                }
                else {
                    $rateLimitAttr = RadiusPacket::createMikrotikRateLimit($upload, $download);
                }
                $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] = $rateLimitAttr;
                $this->log("  Rate-Limit: {$download}/{$upload} bps");
            }

            // Gestion de l'adresse IP selon le mode configuré
            $ipMode = $user['ip_mode'] ?? 'router';
            $this->log("  IP-Mode: {$ipMode}");

            switch ($ipMode) {
                case 'static':
                    // IP statique définie manuellement
                    if (!empty($user['static_ip'])) {
                        $attributes[RadiusPacket::ATTR_FRAMED_IP_ADDRESS] = $user['static_ip'];
                        $this->log("  Framed-IP-Address (static): {$user['static_ip']}");
                    }
                    break;

                case 'pool':
                    // IP assignée depuis un pool géré par notre système
                    if (!empty($user['pool_ip'])) {
                        $attributes[RadiusPacket::ATTR_FRAMED_IP_ADDRESS] = $user['pool_ip'];
                        $this->log("  Framed-IP-Address (pool): {$user['pool_ip']}");
                    }
                    break;

                case 'router':
                default:
                    // Le routeur MikroTik gère l'attribution IP
                    // Utiliser le pool MikroTik si configuré dans le profil
                    if (!empty($user['ip_pool_name'])) {
                        $poolAttr = RadiusPacket::createMikrotikAttribute(RadiusPacket::MIKROTIK_POOL_NAME, $user['ip_pool_name']);
                        if (!isset($attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC])) {
                            $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] = $poolAttr;
                        }
                        else {
                            $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] .= $poolAttr;
                        }
                        $this->log("  Mikrotik-Pool: {$user['ip_pool_name']}");
                    }
                    else {
                        $this->log("  IP: Géré par le routeur (pas de pool spécifié)");
                    }
                    break;
            }

            // Ajouter Mikrotik-Group si configuré (profil PPP du routeur MikroTik)
            if (!empty($user['mikrotik_group'])) {
                $groupAttr = RadiusPacket::createMikrotikAttribute(RadiusPacket::MIKROTIK_GROUP, $user['mikrotik_group']);
                if (!isset($attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC])) {
                    $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] = $groupAttr;
                }
                else {
                    $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] .= $groupAttr;
                }
                $this->log("  Mikrotik-Group: {$user['mikrotik_group']}");
            }

            // Message de bienvenue
            $attributes[RadiusPacket::ATTR_REPLY_MESSAGE] = "Bienvenue {$user['customer_name']}! Profil: {$user['profile_name']}";

        }
        else {
            // Authentification voucher réussie
            $voucher = $result['voucher'];
            $this->log("  [ACCEPT] Voucher valid");
            $this->db->logAuth($username, $nasIp, 'accept', null, $clientMac, $clientIp, $nasIdentifier);

            // Session timeout
            $sessionTimeout = $this->db->getSessionTimeout($voucher);
            if ($sessionTimeout !== null) {
                $attributes[RadiusPacket::ATTR_SESSION_TIMEOUT] = $sessionTimeout;
                $this->log("  Session-Timeout: {$sessionTimeout}s");
            }

            // Idle timeout
            $idleTimeout = $this->config['options']['default_idle_timeout'] ?? 300;
            $attributes[RadiusPacket::ATTR_IDLE_TIMEOUT] = $idleTimeout;

            // Mikrotik Rate-Limit
            if ($voucher['upload_speed'] || $voucher['download_speed']) {
                $upload = $voucher['upload_speed'] ?? 0;
                $download = $voucher['download_speed'] ?? 0;
                $rateLimitAttr = RadiusPacket::createMikrotikRateLimit($upload, $download);
                $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] = $rateLimitAttr;
                $this->log("  Rate-Limit: {$download}/{$upload} bps");
            }

            // Mikrotik Data Limit (Recv-Limit + Xmit-Limit)
            if ($voucher['data_limit'] !== null && $voucher['data_limit'] > 0) {
                $remainingData = $voucher['data_limit'] - ($voucher['data_used'] ?? 0);
                if ($remainingData > 0) {
                    $dataLimitAttr = RadiusPacket::createMikrotikDataLimit($remainingData);
                    if (isset($attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC])) {
                        $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] .= $dataLimitAttr;
                    }
                    else {
                        $attributes[RadiusPacket::ATTR_VENDOR_SPECIFIC] = $dataLimitAttr;
                    }
                    $this->log("  Data-Limit: " . $this->formatBytes($remainingData) . " remaining");
                }
            }

            // Message de bienvenue
            $attributes[RadiusPacket::ATTR_REPLY_MESSAGE] = "Bienvenue! Voucher: {$username}";
        }

        // Acct-Interim-Interval : forcer MikroTik à envoyer des mises à jour comptables périodiques (60s)
        $interimInterval = $this->config['options']['acct_interim_interval'] ?? 60;
        $attributes[RadiusPacket::ATTR_ACCT_INTERIM_INTERVAL] = $interimInterval;

        return RadiusPacket::createResponse(
            RadiusPacket::ACCESS_ACCEPT,
            $packet->identifier,
            $packet->authenticator,
            $packet->secret,
            $attributes
        );
    }

    /**
     * Vérifier un mot de passe CHAP
     * Retourne le mot de passe en clair si vérifié, null sinon
     */
    private function verifyChapPassword(RadiusPacket $packet, string $username): ?string
    {
        $chapPassword = $packet->getAttribute(RadiusPacket::ATTR_CHAP_PASSWORD);
        if (!$chapPassword || strlen($chapPassword) < 17) {
            $this->log("  CHAP: Invalid CHAP-Password attribute (length: " . ($chapPassword ? strlen($chapPassword) : 0) . ")");
            return null;
        }

        // CHAP-Password = CHAP-Ident (1 byte) + MD5 hash (16 bytes)
        $chapIdent = $chapPassword[0];
        $chapHash = substr($chapPassword, 1, 16);

        // Récupérer le mot de passe en clair - d'abord essayer voucher, puis PPPoE
        $clearPassword = null;

        // Essayer le voucher d'abord
        $voucher = $this->db->getVoucherByUsername($username);
        if ($voucher) {
            $clearPassword = $voucher['password'];
            $this->log("  CHAP: Found voucher for user '{$username}'");
        }
        else {
            // Essayer l'utilisateur PPPoE
            $pppoeUser = $this->db->getPPPoEUserByUsername($username);
            if ($pppoeUser) {
                $clearPassword = $pppoeUser['password'];
                $this->log("  CHAP: Found PPPoE user for '{$username}'");
            }
        }

        if (!$clearPassword) {
            $this->log("  CHAP: No user found for '{$username}'");
            return null;
        }

        // Calculer le hash CHAP attendu: MD5(CHAP-Ident + Password + CHAP-Challenge)
        // Le CHAP-Challenge peut être dans un attribut dédié ou dans l'authenticator
        $chapChallenge = $packet->getAttribute(RadiusPacket::ATTR_CHAP_CHALLENGE) ?? $packet->authenticator;

        $expectedHash = md5($chapIdent . $clearPassword . $chapChallenge, true);

        $this->log("  CHAP: Ident=" . bin2hex($chapIdent) . ", Challenge=" . bin2hex($chapChallenge));
        $this->log("  CHAP: ReceivedHash=" . bin2hex($chapHash) . ", ExpectedHash=" . bin2hex($expectedHash));

        if ($chapHash === $expectedHash) {
            $this->log("  CHAP: Password verified successfully");
            return $clearPassword;
        }

        $this->log("  CHAP: Password verification FAILED");
        return null;
    }

    /**
     * Traiter une demande d'accounting
     */
    private function handleAccountingRequest(RadiusPacket $packet, string $nasIp): ?string
    {
        $statusType = $packet->getAcctStatusType();
        $sessionId = $packet->getSessionId();
        $username = $packet->getUsername();

        $this->log("  Status: " . RadiusPacket::getAcctStatusName($statusType));
        $this->log("  Session-ID: {$sessionId}");
        $this->log("  User: {$username}");

        $sessionData = [
            'session_id' => $sessionId,
            'username' => $username,
            'nas_ip' => $nasIp,
            'nas_identifier' => $packet->getAttribute(RadiusPacket::ATTR_NAS_IDENTIFIER),
            'nas_port' => $packet->getAttribute(RadiusPacket::ATTR_NAS_PORT),
            'client_ip' => $packet->getAttribute(RadiusPacket::ATTR_FRAMED_IP_ADDRESS),
            'client_mac' => $packet->getClientMac(),
            'session_time' => $packet->getAttribute(RadiusPacket::ATTR_ACCT_SESSION_TIME, 0),
            'input_octets' => $this->getFullOctets(
            $packet->getAttribute(RadiusPacket::ATTR_ACCT_INPUT_OCTETS, 0),
            $packet->getAttribute(RadiusPacket::ATTR_ACCT_INPUT_GIGAWORDS, 0)
        ),
            'output_octets' => $this->getFullOctets(
            $packet->getAttribute(RadiusPacket::ATTR_ACCT_OUTPUT_OCTETS, 0),
            $packet->getAttribute(RadiusPacket::ATTR_ACCT_OUTPUT_GIGAWORDS, 0)
        ),
            'input_packets' => $packet->getAttribute(RadiusPacket::ATTR_ACCT_INPUT_PACKETS, 0),
            'output_packets' => $packet->getAttribute(RadiusPacket::ATTR_ACCT_OUTPUT_PACKETS, 0),
            'terminate_cause' => $packet->getAttribute(RadiusPacket::ATTR_ACCT_TERMINATE_CAUSE),
        ];

        // Déterminer si c'est un utilisateur PPPoE ou un voucher
        $isPPPoE = $username ? ($this->db->getPPPoEUserByUsername($username) !== null) : false;

        // Préparer les données pour PPPoE
        $pppoeSessionData = [
            'acct_session_id' => $sessionId,
            'username' => $username,
            'nas_ip' => $nasIp,
            'nas_identifier' => $sessionData['nas_identifier'],
            'nas_port' => $sessionData['nas_port'],
            'client_ip' => $sessionData['client_ip'],
            'client_mac' => $sessionData['client_mac'],
            'calling_station_id' => $packet->getAttribute(RadiusPacket::ATTR_CALLING_STATION_ID),
            'called_station_id' => $packet->getAttribute(RadiusPacket::ATTR_CALLED_STATION_ID),
            'session_time' => $sessionData['session_time'],
            'input_octets' => $sessionData['input_octets'],
            'output_octets' => $sessionData['output_octets'],
            'input_packets' => $sessionData['input_packets'],
            'output_packets' => $sessionData['output_packets'],
            'terminate_cause' => $sessionData['terminate_cause'],
        ];

        switch ($statusType) {
            case RadiusPacket::ACCT_STATUS_START:
                if ($isPPPoE) {
                    if ($this->db->startPPPoESession($pppoeSessionData)) {
                        $this->log("  -> PPPoE Session started");
                    }
                    else {
                        $this->log("  -> PPPoE Session NOT started");
                    }
                }
                else {
                    if ($this->db->startSession($sessionData)) {
                        $this->log("  -> Voucher Session started");
                    }
                    else {
                        $this->log("  -> Session NOT started (voucher not found?)");
                    }
                }
                break;

            case RadiusPacket::ACCT_STATUS_INTERIM_UPDATE:
                if ($isPPPoE) {
                    $this->db->updatePPPoESession($pppoeSessionData);
                    $this->log("  -> PPPoE Session updated (Time: {$sessionData['session_time']}s, In: " .
                        $this->formatBytes($sessionData['input_octets']) . ", Out: " .
                        $this->formatBytes($sessionData['output_octets']) . ")");
                }
                else {
                    // Vérifier si la session existe
                    $existingSession = $this->db->getSessionByAcctId($sessionData['session_id'], $sessionData['nas_ip']);
                    if (!$existingSession) {
                        // Session n'existe pas du tout, la créer
                        $this->log("  -> Session not found, creating...");
                        if ($this->db->startSession($sessionData)) {
                            $this->log("  -> Session created");
                        }
                        else {
                            $this->log("  -> Failed to create session (voucher not found?)");
                        }
                    }
                    elseif ($existingSession['stop_time'] !== null) {
                        // Session existe mais est terminée, ignorer l'update
                        $this->log("  -> Session already terminated, ignoring update");
                        break;
                    }
                    $updateResult = $this->db->updateSession($sessionData);
                    $this->log("  -> Session updated (Time: {$sessionData['session_time']}s, In: " .
                        $this->formatBytes($sessionData['input_octets']) . ", Out: " .
                        $this->formatBytes($sessionData['output_octets']) . ")");

                    // Vérifier si la limite de données ou temps est dépassée
                    if (!empty($updateResult['limit_exceeded'])) {
                        $exceeded = $updateResult['limit_exceeded'];
                        $this->log("  [LIMIT] {$exceeded['reason']} for user {$exceeded['username']} - sending Disconnect-Request");

                        // Envoyer un Disconnect-Request au NAS
                        $secret = $this->db->getNasSecret($nasIp);
                        $disconnectPort = $this->config['radius']['disconnect_port'] ?? 3799;
                        $disconnected = self::sendDisconnect(
                            $nasIp,
                            $disconnectPort,
                            $secret,
                            $exceeded['session_id'],
                            $exceeded['username']
                        );

                        if ($disconnected) {
                            $this->log("  [LIMIT] Disconnect-Request ACK received - user disconnected");
                            // Fermer la session en base
                            $this->db->stopSession([
                                'session_id' => $exceeded['session_id'],
                                'nas_ip' => $nasIp,
                                'username' => $exceeded['username'],
                                'session_time' => $sessionData['session_time'],
                                'input_octets' => $sessionData['input_octets'],
                                'output_octets' => $sessionData['output_octets'],
                                'input_packets' => $sessionData['input_packets'] ?? 0,
                                'output_packets' => $sessionData['output_packets'] ?? 0,
                                'terminate_cause' => 'Session-Timeout',
                            ]);
                        }
                        else {
                            $this->log("  [LIMIT] Disconnect-Request failed or NAK - user may still be connected");
                        }
                    }
                }
                break;

            case RadiusPacket::ACCT_STATUS_STOP:
                if ($isPPPoE) {
                    $this->db->stopPPPoESession($pppoeSessionData);
                    $this->log("  -> PPPoE Session stopped (Total time: {$sessionData['session_time']}s)");
                }
                else {
                    $this->db->stopSession($sessionData);
                    $this->log("  -> Session stopped (Total time: {$sessionData['session_time']}s)");
                }
                break;
        }

        return RadiusPacket::createResponse(
            RadiusPacket::ACCOUNTING_RESPONSE,
            $packet->identifier,
            $packet->authenticator,
            $packet->secret
        );
    }

    /**
     * Calculer les octets avec gigawords
     */
    private function getFullOctets(int $octets, int $gigawords): int
    {
        return $octets + ($gigawords * 4294967296);
    }

    /**
     * Formater les bytes
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Vérifier que la connexion DB est active
     */
    private function ensureDbConnection(): void
    {
        try {
            $pdo = $this->db->getPdo();
            $pdo->query("SELECT 1");
        }
        catch (\Throwable $e) {
            $this->log("[WARN] DB connection lost, reconnecting...");
            $this->reconnectDb();
        }
    }

    /**
     * Reconnecter la base de données
     */
    private function reconnectDb(): void
    {
        try {
            $this->db = new RadiusDatabase($this->config['database']);
            $this->log("[INFO] DB reconnected successfully");
        }
        catch (\Throwable $e) {
            $this->log("[ERROR] DB reconnect failed: " . $e->getMessage());
        }
    }

    /**
     * Logger un message
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] {$message}";

        echo $line . PHP_EOL;

        if (!empty($this->config['options']['log_file'])) {
            $logDir = dirname($this->config['options']['log_file']);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents(
                $this->config['options']['log_file'],
                $line . PHP_EOL,
                FILE_APPEND
            );
        }
    }

    /**
     * Arrêter le serveur
     */
    public function shutdown(): void
    {
        $this->log("Shutting down server...");
        $this->running = false;

        if ($this->authSocket) {
            socket_close($this->authSocket);
        }
        if ($this->acctSocket) {
            socket_close($this->acctSocket);
        }

        $this->log("Server stopped.");
        exit(0);
    }

    /**
     * Envoyer un paquet Disconnect à un NAS
     */
    public static function sendDisconnect(string $nasIp, int $nasPort, string $secret, string $sessionId, string $username): bool
    {
        $attributes = [
            RadiusPacket::ATTR_USER_NAME => $username,
            RadiusPacket::ATTR_ACCT_SESSION_ID => $sessionId,
        ];

        $packet = RadiusPacket::createRequest(RadiusPacket::DISCONNECT_REQUEST, $secret, $attributes);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        $result = socket_sendto($socket, $packet, strlen($packet), 0, $nasIp, $nasPort);

        if ($result === false) {
            socket_close($socket);
            return false;
        }

        // Attendre la réponse
        $response = '';
        $from = '';
        $port = 0;
        $bytes = @socket_recvfrom($socket, $response, 4096, 0, $from, $port);

        socket_close($socket);

        if ($bytes >= 20) {
            $code = ord($response[0]);
            return $code === RadiusPacket::DISCONNECT_ACK;
        }

        return false;
    }
}