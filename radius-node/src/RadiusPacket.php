<?php
/**
 * RadiusPacket - Gestion des paquets RADIUS
 * RFC 2865 (Authentication) et RFC 2866 (Accounting)
 */

class RadiusPacket
{
    // Codes RADIUS
    const ACCESS_REQUEST = 1;
    const ACCESS_ACCEPT = 2;
    const ACCESS_REJECT = 3;
    const ACCOUNTING_REQUEST = 4;
    const ACCOUNTING_RESPONSE = 5;
    const ACCESS_CHALLENGE = 11;
    const DISCONNECT_REQUEST = 40;
    const DISCONNECT_ACK = 41;
    const DISCONNECT_NAK = 42;
    const COA_REQUEST = 43;
    const COA_ACK = 44;
    const COA_NAK = 45;

    // Attributs RADIUS courants
    const ATTR_USER_NAME = 1;
    const ATTR_USER_PASSWORD = 2;
    const ATTR_CHAP_PASSWORD = 3;
    const ATTR_NAS_IP_ADDRESS = 4;
    const ATTR_NAS_PORT = 5;
    const ATTR_SERVICE_TYPE = 6;
    const ATTR_FRAMED_PROTOCOL = 7;
    const ATTR_FRAMED_IP_ADDRESS = 8;
    const ATTR_FRAMED_IP_NETMASK = 9;
    const ATTR_FILTER_ID = 11;
    const ATTR_REPLY_MESSAGE = 18;
    const ATTR_STATE = 24;
    const ATTR_CLASS = 25;
    const ATTR_SESSION_TIMEOUT = 27;
    const ATTR_IDLE_TIMEOUT = 28;
    const ATTR_CALLED_STATION_ID = 30;
    const ATTR_CALLING_STATION_ID = 31;
    const ATTR_NAS_IDENTIFIER = 32;
    const ATTR_ACCT_STATUS_TYPE = 40;
    const ATTR_ACCT_DELAY_TIME = 41;
    const ATTR_ACCT_INPUT_OCTETS = 42;
    const ATTR_ACCT_OUTPUT_OCTETS = 43;
    const ATTR_ACCT_SESSION_ID = 44;
    const ATTR_ACCT_AUTHENTIC = 45;
    const ATTR_ACCT_SESSION_TIME = 46;
    const ATTR_ACCT_INPUT_PACKETS = 47;
    const ATTR_ACCT_OUTPUT_PACKETS = 48;
    const ATTR_ACCT_TERMINATE_CAUSE = 49;
    const ATTR_ACCT_INPUT_GIGAWORDS = 52;
    const ATTR_ACCT_OUTPUT_GIGAWORDS = 53;
    const ATTR_ACCT_INTERIM_INTERVAL = 85;
    const ATTR_CHAP_CHALLENGE = 60;
    const ATTR_NAS_PORT_TYPE = 61;
    const ATTR_EVENT_TIMESTAMP = 55;
    const ATTR_ERROR_CAUSE = 101;

    // Acct-Status-Type values
    const ACCT_STATUS_START = 1;
    const ACCT_STATUS_STOP = 2;
    const ACCT_STATUS_INTERIM_UPDATE = 3;
    const ACCT_STATUS_ACCOUNTING_ON = 7;
    const ACCT_STATUS_ACCOUNTING_OFF = 8;

    // Terminate-Cause values
    const TERMINATE_USER_REQUEST = 1;
    const TERMINATE_LOST_CARRIER = 2;
    const TERMINATE_LOST_SERVICE = 3;
    const TERMINATE_IDLE_TIMEOUT = 4;
    const TERMINATE_SESSION_TIMEOUT = 5;
    const TERMINATE_ADMIN_RESET = 6;
    const TERMINATE_ADMIN_REBOOT = 7;
    const TERMINATE_PORT_ERROR = 8;
    const TERMINATE_NAS_ERROR = 9;
    const TERMINATE_NAS_REQUEST = 10;
    const TERMINATE_NAS_REBOOT = 11;
    const TERMINATE_PORT_UNNEEDED = 12;
    const TERMINATE_PORT_PREEMPTED = 13;
    const TERMINATE_PORT_SUSPENDED = 14;
    const TERMINATE_SERVICE_UNAVAILABLE = 15;
    const TERMINATE_CALLBACK = 16;
    const TERMINATE_USER_ERROR = 17;
    const TERMINATE_HOST_REQUEST = 18;

    // Vendor-Specific (Mikrotik = 14988)
    const ATTR_VENDOR_SPECIFIC = 26;
    const VENDOR_MIKROTIK = 14988;

    // Mikrotik VSA
    const MIKROTIK_RECV_LIMIT = 1;
    const MIKROTIK_XMIT_LIMIT = 2;
    const MIKROTIK_GROUP = 3;
    const MIKROTIK_WIRELESS_FORWARD = 4;
    const MIKROTIK_WIRELESS_SKIP_DOT1X = 5;
    const MIKROTIK_WIRELESS_ENC_ALGO = 6;
    const MIKROTIK_WIRELESS_ENC_KEY = 7;
    const MIKROTIK_RATE_LIMIT = 8;
    const MIKROTIK_REALM = 9;
    const MIKROTIK_HOST_IP = 10;
    const MIKROTIK_MARK_ID = 11;
    const MIKROTIK_ADVERTISE_URL = 12;
    const MIKROTIK_ADVERTISE_INTERVAL = 13;
    const MIKROTIK_RECV_LIMIT_GIGAWORDS = 14;
    const MIKROTIK_XMIT_LIMIT_GIGAWORDS = 15;
    const MIKROTIK_POOL_NAME = 17;
    const MIKROTIK_LOCAL_ADDRESS = 18;
    const MIKROTIK_REMOTE_ADDRESS = 19;

    public int $code;
    public int $identifier;
    public string $authenticator;
    public array $attributes = [];
    public string $secret;
    public string $rawPacket;

    /**
     * Décoder un paquet RADIUS brut
     */
    public static function decode(string $data, string $secret): ?self
    {
        if (strlen($data) < 20) {
            return null;
        }

        $packet = new self();
        $packet->rawPacket = $data;
        $packet->secret = $secret;

        // Header: Code (1) + Identifier (1) + Length (2) + Authenticator (16)
        $packet->code = ord($data[0]);
        $packet->identifier = ord($data[1]);
        $length = (ord($data[2]) << 8) | ord($data[3]);
        $packet->authenticator = substr($data, 4, 16);

        // Vérifier la longueur
        if ($length > strlen($data) || $length < 20) {
            return null;
        }

        // Décoder les attributs
        $pos = 20;
        while ($pos < $length) {
            if ($pos + 2 > $length)
                break;

            $attrType = ord($data[$pos]);
            $attrLen = ord($data[$pos + 1]);

            if ($attrLen < 2 || $pos + $attrLen > $length)
                break;

            $attrValue = substr($data, $pos + 2, $attrLen - 2);
            $packet->attributes[$attrType] = $packet->decodeAttribute($attrType, $attrValue);

            $pos += $attrLen;
        }

        // Décoder le mot de passe si présent
        if (isset($packet->attributes[self::ATTR_USER_PASSWORD])) {
            $packet->attributes[self::ATTR_USER_PASSWORD] = $packet->decodePassword(
                $packet->attributes[self::ATTR_USER_PASSWORD],
                $secret,
                $packet->authenticator
            );
        }

        return $packet;
    }

    /**
     * Décoder un attribut selon son type
     */
    private function decodeAttribute(int $type, string $value)
    {
        switch ($type) {
            // Attributs IP (4 octets)
            case self::ATTR_NAS_IP_ADDRESS:
            case self::ATTR_FRAMED_IP_ADDRESS:
                if (strlen($value) >= 4) {
                    return long2ip(unpack('N', $value)[1]);
                }
                return $value;

            // Attributs entiers (4 octets)
            case self::ATTR_NAS_PORT:
            case self::ATTR_SERVICE_TYPE:
            case self::ATTR_SESSION_TIMEOUT:
            case self::ATTR_IDLE_TIMEOUT:
            case self::ATTR_ACCT_STATUS_TYPE:
            case self::ATTR_ACCT_DELAY_TIME:
            case self::ATTR_ACCT_INPUT_OCTETS:
            case self::ATTR_ACCT_OUTPUT_OCTETS:
            case self::ATTR_ACCT_SESSION_TIME:
            case self::ATTR_ACCT_INPUT_PACKETS:
            case self::ATTR_ACCT_OUTPUT_PACKETS:
            case self::ATTR_ACCT_TERMINATE_CAUSE:
            case self::ATTR_ACCT_INPUT_GIGAWORDS:
            case self::ATTR_ACCT_OUTPUT_GIGAWORDS:
            case self::ATTR_NAS_PORT_TYPE:
            case self::ATTR_EVENT_TIMESTAMP:
            case self::ATTR_ERROR_CAUSE:
                if (strlen($value) >= 4) {
                    return unpack('N', $value)[1];
                }
                return 0;

            // Attributs texte
            case self::ATTR_USER_NAME:
            case self::ATTR_CALLING_STATION_ID:
            case self::ATTR_CALLED_STATION_ID:
            case self::ATTR_NAS_IDENTIFIER:
            case self::ATTR_ACCT_SESSION_ID:
            case self::ATTR_REPLY_MESSAGE:
            case self::ATTR_FILTER_ID:
            case self::ATTR_CLASS:
                return trim($value);

            // Mot de passe (sera décodé séparément)
            case self::ATTR_USER_PASSWORD:
                return $value;

            default:
                return $value;
        }
    }

    /**
     * Décoder le mot de passe RADIUS (PAP)
     */
    private function decodePassword(string $encryptedPassword, string $secret, string $authenticator): string
    {
        $password = '';
        $lastBlock = $authenticator;

        for ($i = 0; $i < strlen($encryptedPassword); $i += 16) {
            $block = substr($encryptedPassword, $i, 16);
            $hash = md5($secret . $lastBlock, true);

            for ($j = 0; $j < strlen($block); $j++) {
                $password .= chr(ord($block[$j]) ^ ord($hash[$j]));
            }

            $lastBlock = $block;
        }

        return rtrim($password, "\0");
    }

    /**
     * Créer un paquet de réponse
     */
    public static function createResponse(int $code, int $identifier, string $requestAuthenticator, string $secret, array $attributes = [], ?bool $addMessageAuth = null): string
    {
        // Si non spécifié, ajouter le Message-Authenticator uniquement pour les réponses d'accès (Requis par RFC 2869 et Mikrotik)
        if ($addMessageAuth === null) {
            $addMessageAuth = in_array($code, [self::ACCESS_ACCEPT, self::ACCESS_REJECT, self::ACCESS_CHALLENGE]);
        }

        // Construire les attributs
        $attrData = '';
        foreach ($attributes as $type => $value) {
            $encoded = self::encodeAttribute($type, $value);
            if ($encoded !== null) {
                $attrData .= $encoded;
            }
        }

        // Ajouter Message-Authenticator si demandé (standard RFC 2869)
        // Il doit être à côté des autres attributs
        if ($addMessageAuth) {
            // Ajouter avec 16 octets de zéros temporairement
            $attrData .= chr(80) . chr(18) . str_repeat("\0", 16);
        }

        // Longueur totale
        $length = 20 + strlen($attrData);

        // Construire le paquet avec le REQUEST authenticator temporairement
        $packet = chr($code) . chr($identifier) . pack('n', $length);
        $packet .= $requestAuthenticator;
        $packet .= $attrData;

        // Calculer l'HMAC-MD5 pour le Message-Authenticator si présent
        if ($addMessageAuth) {
            $hmac = hash_hmac('md5', $packet, $secret, true);
            // Remplacer les 16 zéros à la fin par le HMAC (puisque c'est le dernier attribut ajouté)
            $attrData = substr($attrData, 0, -16) . $hmac;

            // Reconstruire le paquet avec les données d'attributs à jour
            $packet = chr($code) . chr($identifier) . pack('n', $length) . $requestAuthenticator . $attrData;
        }

        // Calculer le Response Authenticator propre
        $responseAuth = md5($packet . $secret, true);

        // Reconstruire avec le bon Response Authenticator final
        $packet = chr($code) . chr($identifier) . pack('n', $length) . $responseAuth . $attrData;

        return $packet;
    }

    /**
     * Créer un paquet de requête (pour Disconnect/CoA)
     */
    public static function createRequest(int $code, string $secret, array $attributes = []): string
    {
        $identifier = random_int(0, 255);

        // Construire les attributs
        $attrData = '';
        foreach ($attributes as $type => $value) {
            $encoded = self::encodeAttribute($type, $value);
            if ($encoded !== null) {
                $attrData .= $encoded;
            }
        }

        // Longueur totale
        $length = 20 + strlen($attrData);

        // Authenticator temporaire
        $authenticator = random_bytes(16);

        // Construire le paquet
        $packet = chr($code) . chr($identifier) . pack('n', $length);
        $packet .= $authenticator;
        $packet .= $attrData;

        // Calculer le Request Authenticator
        $requestAuth = md5($packet . $secret, true);

        // Reconstruire avec le bon authenticator
        $packet = chr($code) . chr($identifier) . pack('n', $length) . $requestAuth . $attrData;

        return $packet;
    }

    /**
     * Encoder un attribut
     */
    public static function encodeAttribute(int $type, $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $encoded = '';

        switch ($type) {
            // Entiers
            case self::ATTR_SESSION_TIMEOUT:
            case self::ATTR_IDLE_TIMEOUT:
            case self::ATTR_SERVICE_TYPE:
            case self::ATTR_FRAMED_PROTOCOL:
            case self::ATTR_NAS_PORT:
            case self::ATTR_NAS_PORT_TYPE:
            case self::ATTR_ACCT_STATUS_TYPE:
            case self::ATTR_ACCT_TERMINATE_CAUSE:
            case self::ATTR_ERROR_CAUSE:
            case self::ATTR_ACCT_INTERIM_INTERVAL:
                $encoded = pack('N', (int)$value);
                break;

            // IP
            case self::ATTR_FRAMED_IP_ADDRESS:
            case self::ATTR_NAS_IP_ADDRESS:
                $encoded = pack('N', ip2long($value));
                break;

            // Vendor-Specific
            case self::ATTR_VENDOR_SPECIFIC:
                return $value; // Déjà encodé

            // Texte
            default:
                $encoded = (string)$value;
        }

        $length = strlen($encoded) + 2;
        if ($length > 255) {
            $encoded = substr($encoded, 0, 253);
            $length = 255;
        }

        return chr($type) . chr($length) . $encoded;
    }

    /**
     * Créer un attribut Mikrotik-Rate-Limit
     */
    public static function createMikrotikRateLimit(int $uploadBps, int $downloadBps): string
    {
        // Format Mikrotik: "rx/tx" où rx=download, tx=upload (point de vue NAS)
        $rateLimit = "{$downloadBps}/{$uploadBps}";

        return self::createMikrotikVSA(self::MIKROTIK_RATE_LIMIT, $rateLimit);
    }

    /**
     * Créer un Rate-Limit Mikrotik avec support burst
     * Format: rx-rate[/tx-rate] [rx-burst-rate[/tx-burst-rate] [rx-burst-threshold[/tx-burst-threshold] [rx-burst-time[/tx-burst-time]]]]
     */
    public static function createMikrotikRateLimitWithBurst(
        int $uploadBps,
        int $downloadBps,
        int $burstUpload = 0,
        int $burstDownload = 0,
        int $burstThreshold = 0,
        int $burstTime = 0
        ): string
    {
        // Format de base: rx/tx (download/upload)
        $rateLimit = "{$downloadBps}/{$uploadBps}";

        // Ajouter burst si configuré
        if ($burstDownload > 0 && $burstUpload > 0) {
            $rateLimit .= " {$burstDownload}/{$burstUpload}";

            if ($burstThreshold > 0) {
                $rateLimit .= " {$burstThreshold}/{$burstThreshold}";

                if ($burstTime > 0) {
                    $rateLimit .= " {$burstTime}s/{$burstTime}s";
                }
            }
        }

        return self::createMikrotikVSA(self::MIKROTIK_RATE_LIMIT, $rateLimit);
    }

    /**
     * Créer un attribut Mikrotik générique
     */
    public static function createMikrotikAttribute(int $vendorType, string $value): string
    {
        return self::createMikrotikVSA($vendorType, $value);
    }

    /**
     * Créer un attribut Mikrotik avec une adresse IP (format binaire 4 octets)
     */
    public static function createMikrotikIPAttribute(int $vendorType, string $ipAddress): string
    {
        $ipBinary = inet_pton($ipAddress);
        if ($ipBinary === false) {
            // Fallback: envoyer en string si l'IP est invalide
            return self::createMikrotikVSA($vendorType, $ipAddress);
        }
        return self::createMikrotikVSA($vendorType, $ipBinary);
    }

    /**
     * Créer un VSA Mikrotik générique
     */
    public static function createMikrotikVSA(int $vendorType, string $value): string
    {
        $vendorId = pack('N', self::VENDOR_MIKROTIK);
        $vendorTypeChar = chr($vendorType);
        $vendorLength = chr(strlen($value) + 2);
        $vsaValue = $vendorId . $vendorTypeChar . $vendorLength . $value;

        $totalLength = strlen($vsaValue) + 2;
        return chr(self::ATTR_VENDOR_SPECIFIC) . chr($totalLength) . $vsaValue;
    }

    /**
     * Créer un VSA Mikrotik avec une valeur entière (4 bytes, uint32)
     * Utilisé pour Recv-Limit, Xmit-Limit, Gigawords, etc.
     */
    public static function createMikrotikIntVSA(int $vendorType, int $value): string
    {
        return self::createMikrotikVSA($vendorType, pack('N', $value));
    }

    /**
     * Créer les attributs Mikrotik de limite de données (Recv-Limit + Xmit-Limit)
     * @param int $remainingBytes Bytes restants autorisés
     * @return string Attributs VSA concaténés
     */
    public static function createMikrotikDataLimit(int $remainingBytes): string
    {
        if ($remainingBytes <= 0) {
            $remainingBytes = 0;
        }

        $result = '';

        // Séparer en gigawords (4GB chunks) et octets restants
        $gigawords = (int)floor($remainingBytes / 4294967296); // 2^32
        $octets = (int)($remainingBytes % 4294967296);

        // Mikrotik-Recv-Limit (type 1) — limite download en bytes
        $result .= self::createMikrotikIntVSA(self::MIKROTIK_RECV_LIMIT, $octets);

        // Mikrotik-Xmit-Limit (type 2) — limite upload en bytes
        $result .= self::createMikrotikIntVSA(self::MIKROTIK_XMIT_LIMIT, $octets);

        // Si > 4GB, ajouter les gigawords
        if ($gigawords > 0) {
            $result .= self::createMikrotikIntVSA(self::MIKROTIK_RECV_LIMIT_GIGAWORDS, $gigawords);
            $result .= self::createMikrotikIntVSA(self::MIKROTIK_XMIT_LIMIT_GIGAWORDS, $gigawords);
        }

        return $result;
    }

    /**
     * Obtenir un attribut
     */
    public function getAttribute(int $type, $default = null)
    {
        return $this->attributes[$type] ?? $default;
    }

    /**
     * Obtenir le nom d'utilisateur
     */
    public function getUsername(): ?string
    {
        $username = $this->getAttribute(self::ATTR_USER_NAME);
        if ($username !== null) {
            // Supprimer les caractères null et autres caractères de contrôle
            $username = trim($username);
            $username = preg_replace('/[\x00-\x1F\x7F]/', '', $username);
        }
        return $username;
    }

    /**
     * Obtenir le mot de passe
     */
    public function getPassword(): ?string
    {
        return $this->getAttribute(self::ATTR_USER_PASSWORD);
    }

    /**
     * Obtenir l'adresse MAC du client
     */
    public function getClientMac(): ?string
    {
        return $this->getAttribute(self::ATTR_CALLING_STATION_ID);
    }

    /**
     * Obtenir l'ID de session
     */
    public function getSessionId(): ?string
    {
        return $this->getAttribute(self::ATTR_ACCT_SESSION_ID);
    }

    /**
     * Obtenir le type de statut accounting
     */
    public function getAcctStatusType(): ?int
    {
        return $this->getAttribute(self::ATTR_ACCT_STATUS_TYPE);
    }

    /**
     * Nom du code RADIUS
     */
    public static function getCodeName(int $code): string
    {
        $names = [
            1 => 'Access-Request',
            2 => 'Access-Accept',
            3 => 'Access-Reject',
            4 => 'Accounting-Request',
            5 => 'Accounting-Response',
            11 => 'Access-Challenge',
            40 => 'Disconnect-Request',
            41 => 'Disconnect-ACK',
            42 => 'Disconnect-NAK',
            43 => 'CoA-Request',
            44 => 'CoA-ACK',
            45 => 'CoA-NAK',
        ];
        return $names[$code] ?? "Unknown ($code)";
    }

    /**
     * Nom du statut accounting
     */
    public static function getAcctStatusName(int $status): string
    {
        $names = [
            1 => 'Start',
            2 => 'Stop',
            3 => 'Interim-Update',
            7 => 'Accounting-On',
            8 => 'Accounting-Off',
        ];
        return $names[$status] ?? "Unknown ($status)";
    }

    /**
     * Nom de la cause de terminaison
     */
    public static function getTerminateCauseName(int $cause): string
    {
        $names = [
            1 => 'User-Request',
            2 => 'Lost-Carrier',
            3 => 'Lost-Service',
            4 => 'Idle-Timeout',
            5 => 'Session-Timeout',
            6 => 'Admin-Reset',
            7 => 'Admin-Reboot',
            8 => 'Port-Error',
            9 => 'NAS-Error',
            10 => 'NAS-Request',
            11 => 'NAS-Reboot',
            12 => 'Port-Unneeded',
            13 => 'Port-Preempted',
            14 => 'Port-Suspended',
            15 => 'Service-Unavailable',
            16 => 'Callback',
            17 => 'User-Error',
            18 => 'Host-Request',
        ];
        return $names[$cause] ?? "Unknown ($cause)";
    }
}