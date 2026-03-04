<?php
/**
 * RadiusDatabase - Gestion de la base de données RADIUS
 */

class RadiusDatabase
{
    private PDO $pdo;
    private static ?self$instance = null;

    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['dbname'],
            $config['charset']
        );

        $this->pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Migrations automatiques
        $this->runMigrations();
    }

    /**
     * Exécuter les migrations automatiques via MigrationRunner
     */
    private function runMigrations(): void
    {
        require_once __DIR__ . '/../Update/MigrationRunner.php';

        $runner = new MigrationRunner($this->pdo, __DIR__ . '/../../database/migrations');
        $runner->ensureTrackingTable();

        // Premier lancement : marquer toutes les migrations existantes comme appliquées (baseline)
        if (empty($runner->getExecutedMigrations())) {
            $runner->baseline();
        }

        // Exécuter les migrations en attente
        $runner->runAll();

        // Corrections de données runtime (doivent s'exécuter à chaque démarrage)
        $this->runRuntimeDataFixes();
    }

    /**
     * Corrections de données qui doivent s'exécuter à chaque démarrage
     */
    private function runRuntimeDataFixes(): void
    {
        // Générer router_id pour les NAS qui n'en ont pas
        try {
            $stmt = $this->pdo->query("SELECT id FROM nas WHERE router_id IS NULL OR router_id = ''");
            while ($row = $stmt->fetch()) {
                $randomPart = strtoupper(bin2hex(random_bytes(8)));
                $routerId = 'NAS-' . substr($randomPart, 0, 8) . '-' . substr($randomPart, 8, 4);
                $update = $this->pdo->prepare("UPDATE nas SET router_id = ? WHERE id = ?");
                $update->execute([$routerId, $row['id']]);
            }
        } catch (PDOException $e) {}

        // Calculer valid_until pour les vouchers actifs qui n'en ont pas
        try {
            $this->pdo->exec("
                UPDATE vouchers
                SET valid_until = DATE_ADD(first_use, INTERVAL time_limit SECOND)
                WHERE status = 'active'
                  AND first_use IS NOT NULL
                  AND time_limit IS NOT NULL
                  AND valid_until IS NULL
            ");
        } catch (PDOException $e) {}

        // Mettre à jour le statut des vouchers expirés
        $this->updateExpiredVouchers();
    }

    /**
     * Mettre à jour le statut des vouchers expirés
     */
    public function updateExpiredVouchers(): int
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers
                SET status = 'expired'
                WHERE status = 'active'
                  AND valid_until IS NOT NULL
                  AND valid_until < NOW()
            ");
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Obtenir l'instance PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Singleton
     */
    public static function getInstance(array $config = null): self
    {
        if (self::$instance === null && $config !== null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    // ==========================================
    // Zones
    // ==========================================

    /**
     * Obtenir toutes les zones
     */
    public function getAllZones(bool $activeOnly = false, ?int $adminId = null): array
    {
        $sql = "SELECT z.*,
                       (SELECT COUNT(*) FROM nas WHERE zone_id = z.id) as nas_count,
                       (SELECT COUNT(*) FROM profiles WHERE zone_id = z.id) as profiles_count,
                       (SELECT COUNT(*) FROM vouchers WHERE zone_id = z.id) as vouchers_count
                FROM zones z";
        $conditions = [];
        $params = [];
        if ($activeOnly) {
            $conditions[] = "z.is_active = 1";
        }
        if ($adminId !== null) {
            $conditions[] = "z.admin_id = ?";
            $params[] = $adminId;
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY z.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir une zone par ID
     */
    public function getZoneById(int $id, ?int $adminId = null): ?array
    {
        $sql = "
            SELECT z.*,
                   (SELECT COUNT(*) FROM nas WHERE zone_id = z.id) as nas_count,
                   (SELECT COUNT(*) FROM profiles WHERE zone_id = z.id) as profiles_count,
                   (SELECT COUNT(*) FROM vouchers WHERE zone_id = z.id) as vouchers_count
            FROM zones z WHERE z.id = ?
        ";
        $params = [$id];
        if ($adminId !== null) {
            $sql .= " AND z.admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir une zone par code
     */
    public function getZoneByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM zones WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer une zone
     */
    public function createZone(array $data): int
    {
        // Générer un code unique si non fourni ou si vide
        $code = !empty($data['code']) ? $data['code'] : $this->generateZoneCode();

        $stmt = $this->pdo->prepare("
            INSERT INTO zones (name, code, description, color, is_active, admin_id, dns_name, radius_server_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $code,
            $data['description'] ?? null,
            $data['color'] ?? '#3b82f6',
            $data['is_active'] ?? 1,
            $data['admin_id'] ?? null,
            trim($data['dns_name'] ?? '') ?: null,
            !empty($data['radius_server_id']) ? (int)$data['radius_server_id'] : null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour une zone
     */
    public function updateZone(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE zones SET
                name = ?,
                code = ?,
                description = ?,
                color = ?,
                is_active = ?,
                dns_name = ?,
                radius_server_id = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['code'],
            $data['description'] ?? null,
            $data['color'] ?? '#3b82f6',
            $data['is_active'] ?? 1,
            trim($data['dns_name'] ?? '') ?: null,
            !empty($data['radius_server_id']) ? (int)$data['radius_server_id'] : null,
            $id
        ]);
    }

    /**
     * Supprimer une zone
     */
    public function deleteZone(int $id): bool
    {
        // Mettre à NULL le zone_id des éléments associés
        $this->pdo->prepare("UPDATE nas SET zone_id = NULL WHERE zone_id = ?")->execute([$id]);
        $this->pdo->prepare("UPDATE profiles SET zone_id = NULL WHERE zone_id = ?")->execute([$id]);
        $this->pdo->prepare("UPDATE vouchers SET zone_id = NULL WHERE zone_id = ?")->execute([$id]);

        $stmt = $this->pdo->prepare("DELETE FROM zones WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Obtenir les NAS d'une zone
     */
    public function getNasByZone(int $zoneId, ?int $adminId = null): array
    {
        $sql = "
            SELECT n.*, z.name as zone_name, z.code as zone_code, z.color as zone_color
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
            WHERE n.zone_id = ?
        ";
        $params = [$zoneId];
        if ($adminId !== null) {
            $sql .= " AND n.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY n.shortname";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les profils d'une zone
     */
    public function getProfilesByZone(?int $zoneId, ?int $adminId = null): array
    {
        if ($zoneId === null) {
            // Profils sans zone (disponibles partout)
            $sql = "SELECT * FROM profiles WHERE zone_id IS NULL";
            $params = [];
            if ($adminId !== null) {
                $sql .= " AND admin_id = ?";
                $params[] = $adminId;
            }
            $sql .= " ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
        else {
            // Profils de la zone spécifique OU sans zone
            $sql = "SELECT * FROM profiles WHERE (zone_id = ? OR zone_id IS NULL)";
            $params = [$zoneId];
            if ($adminId !== null) {
                $sql .= " AND admin_id = ?";
                $params[] = $adminId;
            }
            $sql .= " ORDER BY name";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir la zone d'un NAS par son IP
     */
    public function getNasZoneByIp(string $nasIp): ?int
    {
        // Chercher correspondance exacte
        $stmt = $this->pdo->prepare("SELECT zone_id FROM nas WHERE nasname = ?");
        $stmt->execute([$nasIp]);
        $result = $stmt->fetch();

        if ($result && $result['zone_id']) {
            return (int)$result['zone_id'];
        }

        // Chercher wildcard
        $stmt = $this->pdo->prepare("SELECT zone_id FROM nas WHERE nasname = '0.0.0.0/0'");
        $stmt->execute();
        $result = $stmt->fetch();

        return ($result && $result['zone_id']) ? (int)$result['zone_id'] : null;
    }

    /**
     * Obtenir la zone d'un NAS par son Identifier (router_id ou shortname)
     */
    public function getNasZoneByIdentifier(string $nasIdentifier): ?int
    {
        // Chercher par router_id d'abord
        $stmt = $this->pdo->prepare("SELECT zone_id FROM nas WHERE router_id = ?");
        $stmt->execute([$nasIdentifier]);
        $result = $stmt->fetch();

        if ($result && $result['zone_id']) {
            return (int)$result['zone_id'];
        }

        // Chercher par shortname
        $stmt = $this->pdo->prepare("SELECT zone_id FROM nas WHERE shortname = ?");
        $stmt->execute([$nasIdentifier]);
        $result = $stmt->fetch();

        return ($result && $result['zone_id']) ? (int)$result['zone_id'] : null;
    }

    // ==========================================
    // NAS
    // ==========================================

    /**
     * Obtenir le secret d'un NAS par son IP
     */
    public function getNasSecret(string $nasIp): ?string
    {
        // Chercher correspondance exacte
        $stmt = $this->pdo->prepare("SELECT secret FROM nas WHERE nasname = ?");
        $stmt->execute([$nasIp]);
        $result = $stmt->fetch();

        if ($result) {
            return $result['secret'];
        }

        // Chercher wildcard
        $stmt = $this->pdo->prepare("SELECT secret FROM nas WHERE nasname = '0.0.0.0/0'");
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? $result['secret'] : null;
    }

    /**
     * Obtenir tous les NAS
     */
    public function getAllNas(?int $adminId = null): array
    {
        $sql = "
            SELECT n.*, z.name as zone_name, z.code as zone_code, z.color as zone_color
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " WHERE n.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY z.name, n.shortname";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un NAS par ID
     */
    public function getNasById(int $id, ?int $adminId = null): ?array
    {
        $sql = "
            SELECT n.*, z.name as zone_name, z.code as zone_code, z.color as zone_color
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
            WHERE n.id = ?
        ";
        $params = [$id];
        if ($adminId !== null) {
            $sql .= " AND n.admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Générer un Router ID unique et sécurisé
     * Format: NAS-XXXXXXXX-XXXX (16 caractères aléatoires)
     */
    public function generateRouterId(): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            if ($attempts++ >= $maxAttempts) {
                throw new RuntimeException('Unable to generate unique Router ID after ' . $maxAttempts . ' attempts');
            }
            // Générer un ID plus long et plus sécurisé (8 bytes = 16 hex chars)
            $randomPart = strtoupper(bin2hex(random_bytes(8)));
            $routerId = 'NAS-' . substr($randomPart, 0, 8) . '-' . substr($randomPart, 8, 4);
            $stmt = $this->pdo->prepare("SELECT id FROM nas WHERE router_id = ?");
            $stmt->execute([$routerId]);
        } while ($stmt->fetch());

        return $routerId;
    }

    /**
     * Générer un code de zone unique et sécurisé
     * Format: ZONE-XXXXXXXX (12 caractères aléatoires)
     */
    public function generateZoneCode(): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            if ($attempts++ >= $maxAttempts) {
                throw new RuntimeException('Unable to generate unique Zone code after ' . $maxAttempts . ' attempts');
            }
            // Générer un code unique (6 bytes = 12 hex chars)
            $randomPart = strtoupper(bin2hex(random_bytes(6)));
            $zoneCode = 'ZONE-' . $randomPart;
            $stmt = $this->pdo->prepare("SELECT id FROM zones WHERE code = ?");
            $stmt->execute([$zoneCode]);
        } while ($stmt->fetch());

        return $zoneCode;
    }

    /**
     * Créer un NAS
     */
    public function createNas(array $data): int
    {
        // Générer un router_id unique si non fourni
        $routerId = $data['router_id'] ?? $this->generateRouterId();

        $stmt = $this->pdo->prepare("
            INSERT INTO nas (router_id, zone_id, nasname, shortname, secret, description, type, ports, community, latitude, longitude, address, mikrotik_host, mikrotik_api_port, mikrotik_api_username, mikrotik_api_password, mikrotik_use_ssl, admin_id, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $routerId,
            !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
            !empty($data['nasname']) ? $data['nasname'] : '0.0.0.0/0',
            $data['shortname'],
            $data['secret'],
            $data['description'] ?? null,
            $data['type'] ?? 'mikrotik',
            !empty($data['ports']) ? (int)$data['ports'] : null,
            $data['community'] ?? null,
            !empty($data['latitude']) ? (float)$data['latitude'] : null,
            !empty($data['longitude']) ? (float)$data['longitude'] : null,
            $data['address'] ?? null,
            $data['mikrotik_host'] ?? null,
            !empty($data['mikrotik_api_port']) ? (int)$data['mikrotik_api_port'] : 8728,
            $data['mikrotik_api_username'] ?? null,
            $data['mikrotik_api_password'] ?? null,
            !empty($data['mikrotik_use_ssl']) ? 1 : 0,
            $data['admin_id'] ?? null,
            $data['expires_at'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un NAS
     */
    public function updateNas(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE nas SET
                router_id = ?,
                zone_id = ?,
                nasname = ?,
                shortname = ?,
                secret = ?,
                description = ?,
                type = ?,
                ports = ?,
                community = ?,
                latitude = ?,
                longitude = ?,
                address = ?,
                mikrotik_host = ?,
                mikrotik_api_port = ?,
                mikrotik_api_username = ?,
                mikrotik_api_password = ?,
                mikrotik_use_ssl = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['router_id'] ?? null,
            !empty($data['zone_id']) ? (int)$data['zone_id'] : null,
            !empty($data['nasname']) ? $data['nasname'] : '0.0.0.0/0',
            $data['shortname'],
            $data['secret'],
            $data['description'] ?? null,
            $data['type'] ?? 'mikrotik',
            !empty($data['ports']) ? (int)$data['ports'] : null,
            $data['community'] ?? null,
            !empty($data['latitude']) ? (float)$data['latitude'] : null,
            !empty($data['longitude']) ? (float)$data['longitude'] : null,
            $data['address'] ?? null,
            $data['mikrotik_host'] ?? null,
            !empty($data['mikrotik_api_port']) ? (int)$data['mikrotik_api_port'] : 8728,
            $data['mikrotik_api_username'] ?? null,
            $data['mikrotik_api_password'] ?? null,
            !empty($data['mikrotik_use_ssl']) ? 1 : 0,
            $id
        ]);
    }

    /**
     * Obtenir un NAS par Router ID
     */
    public function getNasByRouterId(string $routerId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM nas WHERE router_id = ?");
        $stmt->execute([$routerId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Supprimer un NAS
     */
    public function deleteNas(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM nas WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // Vouchers
    // ==========================================

    /**
     * Authentifier un voucher
     * @param string $username Le nom d'utilisateur du voucher
     * @param string $password Le mot de passe du voucher
     * @param string|null $nasIp L'IP du NAS (pour vérifier la zone)
     * @param string|null $nasIdentifier Le NAS-Identifier (attr 32) - identité système du MikroTik (/system/identity/print)
     */
    public function authenticateVoucher(string $username, string $password, ?string $nasIp = null, ?string $nasIdentifier = null): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE username = ?");
        $stmt->execute([$username]);
        $voucher = $stmt->fetch();

        if (!$voucher) {
            return ['success' => false, 'reason' => 'Voucher not found'];
        }

        if ($voucher['password'] !== $password) {
            return ['success' => false, 'reason' => 'Invalid password'];
        }

        // Vérifier la zone si le voucher a une zone
        if ($voucher['zone_id'] !== null) {
            $nasZone = null;

            // Utiliser NAS-Identifier (attr 32) - c'est l'identité système du MikroTik (/system/identity/print)
            if ($nasIdentifier !== null && $nasIdentifier !== '') {
                $nasZone = $this->getNasZoneByIdentifier($nasIdentifier);
                error_log("RADIUS Zone Check: NAS-Identifier='{$nasIdentifier}' -> zone_id=" . ($nasZone ?? 'NULL'));
            }

            // Fallback sur l'IP si pas trouvé par NAS-Identifier
            if ($nasZone === null && $nasIp !== null) {
                $nasZone = $this->getNasZoneByIp($nasIp);
                error_log("RADIUS Zone Check: Fallback IP='{$nasIp}' -> zone_id=" . ($nasZone ?? 'NULL'));
            }

            error_log("RADIUS Zone Check: Voucher zone_id={$voucher['zone_id']}, NAS zone_id=" . ($nasZone ?? 'NULL'));

            // Si le voucher a une zone et que le NAS a une zone différente, refuser
            if ($nasZone !== null && (int)$voucher['zone_id'] !== (int)$nasZone) {
                return ['success' => false, 'reason' => 'Voucher not valid for this zone'];
            }
        // Si le voucher a une zone mais le NAS n'en a pas (nasZone = null), accepter (NAS global)
        }

        if ($voucher['status'] === 'disabled') {
            return ['success' => false, 'reason' => 'Voucher disabled'];
        }

        if ($voucher['status'] === 'expired') {
            return ['success' => false, 'reason' => 'Voucher expired'];
        }

        if ($voucher['valid_from'] && strtotime($voucher['valid_from']) > time()) {
            return ['success' => false, 'reason' => 'Voucher not yet valid'];
        }

        if ($voucher['valid_until'] && strtotime($voucher['valid_until']) < time()) {
            $this->updateVoucherStatus($voucher['id'], 'expired');
            return ['success' => false, 'reason' => 'Voucher expired'];
        }

        if ($voucher['time_limit'] !== null) {
            $timeRemaining = $voucher['time_limit'] - $voucher['time_used'];
            if ($timeRemaining <= 0) {
                $this->updateVoucherStatus($voucher['id'], 'expired');
                return ['success' => false, 'reason' => 'Time limit exceeded'];
            }
        }

        if ($voucher['data_limit'] !== null) {
            $dataRemaining = $voucher['data_limit'] - $voucher['data_used'];
            if ($dataRemaining <= 0) {
                $this->updateVoucherStatus($voucher['id'], 'expired');
                return ['success' => false, 'reason' => 'Data limit exceeded'];
            }
        }

        $activeSessions = $this->countActiveSessions($voucher['id']);
        if ($activeSessions >= $voucher['simultaneous_use']) {
            return ['success' => false, 'reason' => 'Too many simultaneous connections'];
        }

        if ($voucher['status'] === 'unused') {
            $this->markFirstUse($voucher['id']);
            // Recharger le voucher pour avoir valid_until à jour
            $voucher = $this->getVoucherByUsername($username);

            // Si c'est un voucher bonus, marquer la récompense comme réclamée
            if (str_starts_with($username, 'BONUS-')) {
                $this->markBonusRewardAsClaimed($username);
            }
        }

        return ['success' => true, 'voucher' => $voucher];
    }

    /**
     * Marquer une récompense bonus comme réclamée lorsque le voucher est utilisé
     * @param string $voucherCode Le code du voucher bonus (BONUS-XXXXXX)
     */
    private function markBonusRewardAsClaimed(string $voucherCode): void
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE loyalty_rewards
                SET status = 'claimed', claimed_at = NOW()
                WHERE voucher_code = ? AND status = 'pending'
            ");
            $stmt->execute([$voucherCode]);

            if ($stmt->rowCount() > 0) {
                error_log("Loyalty: Bonus reward claimed for voucher code: {$voucherCode}");
            }
        }
        catch (\PDOException $e) {
            error_log("Error marking bonus reward as claimed: " . $e->getMessage());
        }
    }

    /**
     * Obtenir tous les vouchers
     */
    public function getAllVouchers(array $filters = [], int $page = 1, int $perPage = 20, ?int $adminId = null): array
    {
        // Mettre à jour les vouchers expirés avant de récupérer la liste
        $this->updateExpiredVouchers();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "v.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['status'])) {
            $where[] = "v.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "v.username LIKE ?";
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['profile_id'])) {
            $where[] = "v.profile_id = ?";
            $params[] = $filters['profile_id'];
        }

        if (!empty($filters['batch_id'])) {
            $where[] = "v.batch_id = ?";
            $params[] = $filters['batch_id'];
        }

        if (!empty($filters['notes'])) {
            $where[] = "v.notes = ?";
            $params[] = $filters['notes'];
        }

        // Filtre par type (voucher = username = password, ticket = username != password)
        if (!empty($filters['type'])) {
            if ($filters['type'] === 'voucher') {
                $where[] = "v.username = v.password";
            }
            elseif ($filters['type'] === 'ticket') {
                $where[] = "v.username != v.password";
            }
        }

        // Filtre par zone
        if (!empty($filters['zone'])) {
            if ($filters['zone'] === 'none') {
                $where[] = "v.zone_id IS NULL";
            }
            else {
                $where[] = "v.zone_id = ?";
                $params[] = $filters['zone'];
            }
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM vouchers v {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Données avec has_password, plain_password et zone info
        $stmt = $this->pdo->prepare("
            SELECT v.*,
                   p.name as profile_name,
                   z.name as zone_name,
                   z.color as zone_color,
                   (v.username != v.password) as has_password,
                   v.password as plain_password
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN zones z ON v.zone_id = z.id
            {$whereClause}
            ORDER BY v.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtenir un voucher par ID
     */
    public function getVoucherById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.*, p.name as profile_name,
                   uv.username as vendeur_name,
                   ug.username as gerant_name
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN users uv ON v.vendeur_id = uv.id
            LEFT JOIN users ug ON v.gerant_id = ug.id
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir un voucher par username
     */
    public function getVoucherByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un voucher
     */
    public function createVoucher(array $data): int
    {
        $gerantId = null;
        if (!empty($data['vendeur_id'])) {
            // Chercher par parent d'abord
            $stmtUser = $this->pdo->prepare("SELECT parent_id, role FROM users WHERE id = ?");
            $stmtUser->execute([$data['vendeur_id']]);
            $user = $stmtUser->fetch();
            if ($user) {
                if ($user['role'] === 'gerant') {
                    $gerantId = $data['vendeur_id'];
                    $data['vendeur_id'] = null; // C'est un gérant qui crée
                }
                else {
                    $stmtParent = $this->pdo->prepare("SELECT id, role FROM users WHERE id = ?");
                    $stmtParent->execute([$user['parent_id']]);
                    $parent = $stmtParent->fetch();
                    if ($parent && $parent['role'] === 'gerant') {
                        $gerantId = $parent['id'];
                    }
                    else {
                        // Chercher par zone assignée / routeur assigné
                        $stmtZone = $this->pdo->prepare("
                            SELECT u.id 
                            FROM user_zones uz 
                            JOIN users u ON uz.user_id = u.id 
                            WHERE u.role = 'gerant' 
                            AND uz.zone_id IN (
                                SELECT zone_id FROM user_zones WHERE user_id = ?
                                UNION
                                SELECT n.zone_id FROM user_nas un JOIN nas n ON un.nas_id = n.id WHERE un.user_id = ?
                            )
                            LIMIT 1
                        ");
                        $stmtZone->execute([$data['vendeur_id'], $data['vendeur_id']]);
                        $gerant = $stmtZone->fetch();
                        if ($gerant) {
                            $gerantId = $gerant['id'];
                        }
                    }
                }
            }
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO vouchers (
                username, password, profile_id, zone_id, time_limit, data_limit,
                upload_limit, download_limit, upload_speed, download_speed,
                simultaneous_use, price, valid_from, valid_until,
                customer_name, customer_phone,
                batch_id, created_by, notes, admin_id, vendeur_id, gerant_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $data['password'] ?? $data['username'],
            $data['profile_id'] ?? null,
            $data['zone_id'] ?? null,
            $data['time_limit'] ?? null,
            $data['data_limit'] ?? null,
            $data['upload_limit'] ?? null,
            $data['download_limit'] ?? null,
            $data['upload_speed'] ?? null,
            $data['download_speed'] ?? null,
            $data['simultaneous_use'] ?? 1,
            $data['price'] ?? 0,
            $data['valid_from'] ?? null,
            $data['valid_until'] ?? null,
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['batch_id'] ?? null,
            $data['created_by'] ?? null,
            $data['notes'] ?? null,
            $data['admin_id'] ?? null,
            $data['vendeur_id'] ?? null,
            $gerantId
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Créer plusieurs vouchers
     */
    public function createVouchersBatch(array $vouchers): array
    {
        $created = [];
        $this->pdo->beginTransaction();

        try {
            foreach ($vouchers as $data) {
                $id = $this->createVoucher($data);
                $created[] = ['id' => $id, 'username' => $data['username']];
            }
            $this->pdo->commit();
        }
        catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $created;
    }

    /**
     * Mettre à jour un voucher
     */
    public function updateVoucher(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowedFields = [
            'password', 'profile_id', 'zone_id', 'time_limit', 'data_limit',
            'upload_limit', 'download_limit', 'upload_speed', 'download_speed',
            'status', 'simultaneous_use', 'price', 'valid_from', 'valid_until',
            'customer_name', 'customer_phone', 'notes'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE vouchers SET " . implode(', ', $fields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Mettre à jour le statut
     */
    public function updateVoucherStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE vouchers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Réinitialiser un voucher
     */
    public function resetVoucher(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE vouchers SET
                status = 'unused',
                time_used = 0,
                data_used = 0,
                upload_used = 0,
                download_used = 0,
                first_use = NULL,
                valid_until = NULL
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Supprimer un voucher
     */
    public function deleteVoucher(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM vouchers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Supprimer des vouchers par lot
     */
    public function deleteVouchersByBatch(string $batchId, ?int $adminId = null): int
    {
        $sql = "DELETE FROM vouchers WHERE batch_id = ?";
        $params = [$batchId];

        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Marquer la première utilisation et calculer la date d'expiration
     * La date d'expiration (valid_until) est basée sur la VALIDITÉ du profil (durée de vie)
     * Le time_limit reste le temps d'utilisation effectif
     */
    private function markFirstUse(int $voucherId): void
    {
        // Récupérer le voucher et le profil pour calculer la date d'expiration
        $stmt = $this->pdo->prepare("
            SELECT v.time_limit, v.profile_id, p.validity, p.validity_unit
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            WHERE v.id = ?
        ");
        $stmt->execute([$voucherId]);
        $voucher = $stmt->fetch();

        if (!$voucher) {
            return;
        }

        // Calculer valid_until basé sur la VALIDITÉ du profil (durée de vie du voucher)
        // Le champ 'validity' est DÉJÀ stocké en secondes (le frontend fait la conversion)
        $validUntil = null;
        $validitySeconds = $voucher['validity'] ?? null;

        if ($validitySeconds !== null && $validitySeconds > 0) {
            $validUntil = date('Y-m-d H:i:s', time() + (int)$validitySeconds);
        }

        if ($validUntil) {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers SET status = 'active', first_use = NOW(), valid_until = ? WHERE id = ?
            ");
            $stmt->execute([$validUntil, $voucherId]);
        }
        else {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers SET status = 'active', first_use = NOW() WHERE id = ?
            ");
            $stmt->execute([$voucherId]);
        }
    }

    /**
     * Obtenir le timeout de session
     * Prend le minimum entre:
     * - valid_until (date d'expiration du voucher = validité)
     * - time_limit - time_used (temps d'utilisation restant)
     */
    public function getSessionTimeout(array $voucher): ?int
    {
        $timeouts = [];

        // 1. Vérifier valid_until (date d'expiration basée sur la validité)
        if (!empty($voucher['valid_until'])) {
            $expiresAt = strtotime($voucher['valid_until']);
            $remainingValidity = $expiresAt - time();
            if ($remainingValidity > 0) {
                $timeouts[] = $remainingValidity;
            }
            else {
                // Le voucher est déjà expiré
                return 0;
            }
        }

        // 2. Vérifier time_limit (temps d'utilisation effectif)
        if ($voucher['time_limit'] !== null) {
            $remainingTime = $voucher['time_limit'] - ($voucher['time_used'] ?? 0);
            if ($remainingTime > 0) {
                $timeouts[] = $remainingTime;
            }
            else {
                // Le temps d'utilisation est épuisé
                return 0;
            }
        }

        // Retourner le minimum des deux (la limite la plus restrictive)
        if (empty($timeouts)) {
            return null; // Pas de limite
        }

        return max(0, min($timeouts));
    }

    // ==========================================
    // Sessions
    // ==========================================

    /**
     * Compter les sessions actives
     */
    public function countActiveSessions(int $voucherId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM sessions
            WHERE voucher_id = ? AND stop_time IS NULL
        ");
        $stmt->execute([$voucherId]);
        return (int)$stmt->fetch()['count'];
    }

    /**
     * Démarrer une session
     */
    public function startSession(array $data): bool
    {
        try {
            $username = $data['username'] ?? '';

            // Nettoyer le username (supprimer caractères invisibles)
            $username = trim($username);
            $username = preg_replace('/[\x00-\x1F\x7F]/', '', $username);

            // Chercher le voucher avec toutes ses infos
            $stmt = $this->pdo->prepare("SELECT * FROM vouchers WHERE username = ?");
            $stmt->execute([$username]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voucher) {
                return false;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO sessions (
                    voucher_id, acct_session_id, nas_ip, nas_port,
                    username, client_ip, client_mac, start_time, admin_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE start_time = NOW(), stop_time = NULL
            ");

            $result = $stmt->execute([
                $voucher['id'],
                $data['session_id'],
                $data['nas_ip'],
                $data['nas_port'] ?? null,
                $username,
                $data['client_ip'] ?? null,
                $data['client_mac'] ?? null,
                $voucher['admin_id'] ?? null,
            ]);

            // Tracker la vente si pas encore associée à un NAS
            // Cas 1: sold_at vide = ticket jamais vendu -> tracking complet
            // Cas 2: sold_on_nas_id vide = ticket payé en ligne mais pas encore utilisé -> associer NAS/vendeur
            if ($result && (empty($voucher['sold_at']) || empty($voucher['sold_on_nas_id']))) {
                error_log("trackVoucherSale: Starting for voucher {$voucher['username']} (ID {$voucher['id']}), nas_ip={$data['nas_ip']}, nas_identifier=" . ($data['nas_identifier'] ?? 'NULL'));
                $this->trackVoucherSale($voucher, $data['nas_ip'], $data['nas_identifier'] ?? null);
            }
            else {
                error_log("trackVoucherSale: SKIPPED for voucher {$voucher['username']} - result=$result, sold_at={$voucher['sold_at']}, sold_on_nas_id={$voucher['sold_on_nas_id']}");
            }

            return $result;
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Tracker la vente d'un voucher lors de sa première utilisation
     * Utilise NAS-Identifier (attr 32 = router_id) pour identifier le NAS
     */
    private function trackVoucherSale(array $voucher, string $nasIp, ?string $nasIdentifier): void
    {
        try {
            $nas = null;

            // 1. Chercher par NAS-Identifier (router_id) - méthode la plus fiable
            if ($nasIdentifier !== null && $nasIdentifier !== '') {
                error_log("trackVoucherSale: Searching NAS by router_id='$nasIdentifier'");
                $stmt = $this->pdo->prepare("SELECT id, zone_id FROM nas WHERE router_id = ?");
                $stmt->execute([$nasIdentifier]);
                $nas = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($nas)
                    error_log("trackVoucherSale: Found NAS by router_id: ID={$nas['id']}");
            }

            // 2. Fallback: chercher par IP exacte
            if (!$nas) {
                error_log("trackVoucherSale: Searching NAS by IP='$nasIp'");
                $stmt = $this->pdo->prepare("SELECT id, zone_id FROM nas WHERE nasname = ?");
                $stmt->execute([$nasIp]);
                $nas = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($nas)
                    error_log("trackVoucherSale: Found NAS by IP: ID={$nas['id']}");
            }

            // 3. Fallback: chercher un NAS wildcard (0.0.0.0/0)
            if (!$nas) {
                error_log("trackVoucherSale: Searching NAS wildcard 0.0.0.0/0");
                $stmt = $this->pdo->prepare("SELECT id, zone_id FROM nas WHERE nasname = '0.0.0.0/0' LIMIT 1");
                $stmt->execute();
                $nas = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($nas)
                    error_log("trackVoucherSale: Found NAS wildcard: ID={$nas['id']}");
            }

            if (!$nas) {
                error_log("trackVoucherSale: NO NAS FOUND - aborting");
                return; // Aucun NAS trouvé
            }

            $nasId = $nas['id'];
            $zoneId = $nas['zone_id'];

            // Trouver le vendeur assigné à ce NAS (fallback)
            $stmt = $this->pdo->prepare("
                SELECT un.user_id, u.role, u.parent_id
                FROM user_nas un
                JOIN users u ON un.user_id = u.id
                WHERE un.nas_id = ? AND un.can_manage = 1 AND u.is_active = 1
                ORDER BY u.role = 'vendeur' DESC, un.assigned_at ASC
                LIMIT 1
            ");
            $stmt->execute([$nasId]);
            $nasSeller = $stmt->fetch(PDO::FETCH_ASSOC);

            // 1. Déterminer le vendeur_id (priorité au voucher, sinon NAS)
            $vendeurId = !empty($voucher['vendeur_id']) ? $voucher['vendeur_id'] : ($nasSeller ? $nasSeller['user_id'] : null);

            // 2. Retracer la hiérarchie pour associer le gérant et l'admin
            $gerantId = null;
            $adminId = $voucher['admin_id'] ?? null;

            // Si le ticket n'avait pas de vendeur, il est désormais affecté à celui défini par le NAS
            $soldBy = $vendeurId;

            if ($vendeurId) {
                $stmtUser = $this->pdo->prepare("SELECT id, role, parent_id FROM users WHERE id = ?");
                $stmtUser->execute([$vendeurId]);
                $userTarget = $stmtUser->fetch(PDO::FETCH_ASSOC);

                if ($userTarget) {
                    if ($userTarget['role'] === 'vendeur') {
                        $stmtParent = $this->pdo->prepare("SELECT id, role, parent_id FROM users WHERE id = ?");
                        $stmtParent->execute([$userTarget['parent_id']]);
                        $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
                        if ($parent) {
                            if ($parent['role'] === 'gerant') {
                                $gerantId = $parent['id'];
                                if (!$adminId)
                                    $adminId = $parent['parent_id'];
                            }
                            elseif ($parent['role'] === 'admin') {
                                if (!$adminId)
                                    $adminId = $parent['id'];

                                // Parent n'est pas gérant, on cherche par zone assignée!
                                $stmtZone = $this->pdo->prepare("
                                    SELECT u.id, u.parent_id 
                                    FROM user_zones uz 
                                    JOIN users u ON uz.user_id = u.id 
                                    WHERE uz.zone_id IN (SELECT zone_id FROM user_zones WHERE user_id = ?) 
                                    AND u.role = 'gerant' 
                                    LIMIT 1
                                ");
                                $stmtZone->execute([$vendeurId]);
                                $gerantCheck = $stmtZone->fetch(PDO::FETCH_ASSOC);
                                if ($gerantCheck) {
                                    $gerantId = $gerantCheck['id'];
                                    if (!$adminId) {
                                        $adminId = $gerantCheck['parent_id'];
                                    }
                                }
                            }
                        }
                    }
                    elseif ($userTarget['role'] === 'gerant') {
                        // Le 'vendeurId' est en fait un gérant qui vend en direct
                        $gerantId = $userTarget['id'];
                        $vendeurId = null; // Optionnel : annuler le vendeur_id si ce n'est pas un vendeur strict
                        if (!$adminId)
                            $adminId = $userTarget['parent_id'];
                    }
                    elseif ($userTarget['role'] === 'admin') {
                        if (!$adminId)
                            $adminId = $userTarget['id'];
                        $vendeurId = null;
                    }
                }
            }

            // Récupérer le prix du profil
            $profilePrice = 0;
            if ($voucher['profile_id']) {
                $stmt = $this->pdo->prepare("SELECT price FROM profiles WHERE id = ?");
                $stmt->execute([$voucher['profile_id']]);
                $profile = $stmt->fetch(PDO::FETCH_ASSOC);
                $profilePrice = $profile['price'] ?? 0;
            }

            // Calculer les commissions
            $commissions = $this->calculateSaleCommissions($profilePrice, $zoneId, $voucher['profile_id']);

            // Déterminer si c'est une mise à jour partielle (ticket déjà payé en ligne) ou complète
            $isOnlinePayment = !empty($voucher['sold_at']) && !empty($voucher['payment_method']);

            if ($isOnlinePayment) {
                // Ticket payé en ligne : ne mettre à jour que NAS, vendeur et commissions
                // Ne PAS écraser payment_method, sale_amount, sold_at
                error_log("trackVoucherSale: Online payment detected, updating only NAS/seller info");
                $stmt = $this->pdo->prepare("
                    UPDATE vouchers SET
                        vendeur_id = COALESCE(vendeur_id, ?),
                        gerant_id = COALESCE(gerant_id, ?),
                        sold_by = COALESCE(sold_by, ?),
                        sold_on_nas_id = ?,
                        commission_vendeur = ?,
                        commission_gerant = ?,
                        commission_admin = ?,
                        status = 'active',
                        first_use = COALESCE(first_use, NOW())
                    WHERE id = ?
                ");
                $stmt->execute([
                    $vendeurId,
                    $gerantId,
                    $soldBy,
                    $nasId,
                    $commissions['vendeur'],
                    $commissions['gerant'],
                    $commissions['admin'],
                    $voucher['id']
                ]);
            }
            else {
                // Ticket classique (cash) : mise à jour complète
                error_log("trackVoucherSale: Cash payment, full update");
                $stmt = $this->pdo->prepare("
                    UPDATE vouchers SET
                        vendeur_id = COALESCE(vendeur_id, ?),
                        gerant_id = COALESCE(gerant_id, ?),
                        sold_by = COALESCE(sold_by, ?),
                        sold_at = NOW(),
                        sold_on_nas_id = ?,
                        payment_method = 'cash',
                        sale_amount = ?,
                        commission_vendeur = ?,
                        commission_gerant = ?,
                        commission_admin = ?,
                        status = 'active',
                        first_use = COALESCE(first_use, NOW())
                    WHERE id = ?
                ");
                $stmt->execute([
                    $vendeurId,
                    $gerantId,
                    $soldBy,
                    $nasId,
                    $profilePrice,
                    $commissions['vendeur'],
                    $commissions['gerant'],
                    $commissions['admin'],
                    $voucher['id']
                ]);
            }
        }
        catch (Exception $e) {
            // Log l'erreur mais ne pas bloquer la session
            error_log("trackVoucherSale error: " . $e->getMessage());
        }
    }

    /**
     * Calculer les commissions pour une vente
     */
    private function calculateSaleCommissions(float $amount, ?int $zoneId, ?int $profileId): array
    {
        $commissions = ['vendeur' => 0, 'gerant' => 0, 'admin' => 0];

        if ($amount <= 0) {
            return $commissions;
        }

        $roles = ['vendeur', 'gerant', 'admin'];

        foreach ($roles as $role) {
            $stmt = $this->pdo->prepare("
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
                }
                else {
                    $commissions[$role] = (float)$rate['rate_value'];
                }
            }
        }

        return $commissions;
    }

    /**
     * Mettre à jour une session (seulement si elle est active)
     */
    /**
     * @return array{success: bool, limit_exceeded: ?array} Résultat avec info de dépassement éventuel
     */
    public function updateSession(array $data): array
    {
        // Mettre à jour les compteurs voucher AVANT de mettre à jour la session
        // car updateVoucherCounters() lit les anciennes valeurs de la session pour calculer les deltas
        $limitExceeded = $this->updateVoucherCounters($data);

        // Mettre à jour la session avec les nouvelles valeurs
        $stmt = $this->pdo->prepare("
            UPDATE sessions SET
                session_time = ?,
                input_octets = ?,
                output_octets = ?,
                input_packets = ?,
                output_packets = ?,
                last_update = NOW()
            WHERE acct_session_id = ? AND nas_ip = ? AND stop_time IS NULL
        ");

        $result = $stmt->execute([
            $data['session_time'] ?? 0,
            $data['input_octets'] ?? 0,
            $data['output_octets'] ?? 0,
            $data['input_packets'] ?? 0,
            $data['output_packets'] ?? 0,
            $data['session_id'],
            $data['nas_ip'],
        ]);

        return ['success' => $result, 'limit_exceeded' => $limitExceeded];
    }

    /**
     * Terminer une session
     */
    public function stopSession(array $data): bool
    {
        // Mettre à jour les compteurs voucher AVANT de mettre à jour la session
        // car updateVoucherCounters() lit les anciennes valeurs de la session pour calculer les deltas
        $this->updateVoucherCountersOnStop($data);

        $stmt = $this->pdo->prepare("
            UPDATE sessions SET
                session_time = ?,
                input_octets = ?,
                output_octets = ?,
                input_packets = ?,
                output_packets = ?,
                stop_time = NOW(),
                terminate_cause = ?
            WHERE acct_session_id = ? AND nas_ip = ?
        ");

        $result = $stmt->execute([
            $data['session_time'] ?? 0,
            $data['input_octets'] ?? 0,
            $data['output_octets'] ?? 0,
            $data['input_packets'] ?? 0,
            $data['output_packets'] ?? 0,
            $data['terminate_cause'] ?? null,
            $data['session_id'],
            $data['nas_ip'],
        ]);

        return $result;
    }

    /**
     * Obtenir les sessions actives
     */
    public function getActiveSessions(?int $adminId = null): array
    {
        // Trouver le NAS wildcard par défaut (le premier 0.0.0.0/0)
        $wildcardNas = $this->pdo->query("
            SELECT n.id, n.shortname, n.router_id, z.id as zone_id, z.name as zone_name
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
            WHERE n.nasname = '0.0.0.0/0'
            LIMIT 1
        ")->fetch();

        $where = ["s.stop_time IS NULL"];
        $params = [];

        if ($adminId !== null) {
            $where[] = "s.admin_id = ?";
            $params[] = $adminId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $stmt = $this->pdo->prepare("
            SELECT s.*, v.username as voucher_code, v.valid_until, v.time_limit,
                   n.shortname as nas_name, n.router_id,
                   z.id as zone_id, z.name as zone_name
            FROM sessions s
            JOIN vouchers v ON s.voucher_id = v.id
            LEFT JOIN nas n ON (s.nas_ip = n.nasname OR (s.nas_ip = n.mikrotik_host AND n.mikrotik_host != ''))
            LEFT JOIN zones z ON n.zone_id = z.id
            {$whereClause}
            ORDER BY s.start_time DESC
        ");
        $stmt->execute($params);

        $sessions = $stmt->fetchAll();

        // Appliquer le NAS wildcard pour les sessions sans correspondance
        if ($wildcardNas) {
            foreach ($sessions as &$session) {
                if (empty($session['nas_name'])) {
                    $session['nas_name'] = $wildcardNas['shortname'];
                    $session['router_id'] = $wildcardNas['router_id'];
                    $session['zone_id'] = $wildcardNas['zone_id'];
                    $session['zone_name'] = $wildcardNas['zone_name'];
                }
            }
        }

        return $sessions;
    }

    /**
     * Obtenir l'historique des sessions
     */
    public function getSessionHistory(array $filters = [], int $page = 1, int $perPage = 50, ?int $adminId = null): array
    {
        $where = [];
        $params = [];
        $zoneFilter = !empty($filters['zone_id']) ? (int)$filters['zone_id'] : null;

        if ($adminId !== null) {
            $where[] = "s.admin_id = ?";
            $params[] = $adminId;
        }

        // Récupérer le NAS wildcard par défaut
        $wildcardNas = $this->pdo->query("
            SELECT n.id, n.shortname, n.router_id, n.zone_id, z.name as zone_name
            FROM nas n
            LEFT JOIN zones z ON n.zone_id = z.id
            WHERE n.nasname = '0.0.0.0/0'
            LIMIT 1
        ")->fetch();

        if (!empty($filters['username'])) {
            $where[] = "s.username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }

        if (!empty($filters['nas_ip'])) {
            $where[] = "s.nas_ip = ?";
            $params[] = $filters['nas_ip'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "s.start_time >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "s.start_time <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $where[] = "s.stop_time IS NULL";
            }
            else {
                $where[] = "s.stop_time IS NOT NULL";
            }
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Compter le total
        $countStmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM sessions s
            {$whereClause}
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Récupérer les sessions
        $stmt = $this->pdo->prepare("
            SELECT s.*,
                   v.username as voucher_code,
                   p.name as profile_name,
                   p.price as profile_price,
                   n.shortname as nas_name,
                   n.router_id,
                   z.id as zone_id,
                   z.name as zone_name
            FROM sessions s
            JOIN vouchers v ON s.voucher_id = v.id
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN nas n ON (s.nas_ip = n.nasname OR (s.nas_ip = n.mikrotik_host AND n.mikrotik_host != ''))
            LEFT JOIN zones z ON n.zone_id = z.id
            {$whereClause}
            ORDER BY s.start_time DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();

        // Appliquer le NAS wildcard pour les sessions sans correspondance
        if ($wildcardNas) {
            foreach ($sessions as &$session) {
                if (empty($session['nas_name'])) {
                    $session['nas_name'] = $wildcardNas['shortname'];
                    $session['router_id'] = $wildcardNas['router_id'];
                    $session['zone_id'] = $wildcardNas['zone_id'];
                    $session['zone_name'] = $wildcardNas['zone_name'];
                }
            }
        }

        // Filtrer par zone après application du wildcard
        if ($zoneFilter) {
            $sessions = array_filter($sessions, function ($s) use ($zoneFilter) {
                return isset($s['zone_id']) && (int)$s['zone_id'] === $zoneFilter;
            });
            $sessions = array_values($sessions); // Réindexer
            $total = count($sessions);
        }

        return [
            'data' => $sessions,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, ceil($total / $perPage))
        ];
    }

    /**
     * Obtenir une session par ID
     */
    public function getSessionById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, v.username as voucher_code, n.shortname as nas_name
            FROM sessions s
            JOIN vouchers v ON s.voucher_id = v.id
            LEFT JOIN nas n ON (s.nas_ip = n.nasname OR (s.nas_ip = n.mikrotik_host AND n.mikrotik_host != ''))
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir une session par acct_session_id et nas_ip
     */
    public function getSessionByAcctId(string $acctSessionId, string $nasIp): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sessions WHERE acct_session_id = ? AND nas_ip = ?");
        $stmt->execute([$acctSessionId, $nasIp]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Mettre à jour les compteurs du voucher (interim)
     * @return array|null Infos de dépassement si data_limit ou time_limit atteint, null sinon
     */
    public function updateVoucherCounters(array $data): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT v.id, v.data_limit, v.data_used, v.time_limit, v.time_used, v.valid_until,
                   s.session_time as old_time, s.input_octets as old_input, s.output_octets as old_output,
                   s.acct_session_id, s.username
            FROM sessions s
            JOIN vouchers v ON s.voucher_id = v.id
            WHERE s.acct_session_id = ? AND s.nas_ip = ?
        ");
        $stmt->execute([$data['session_id'], $data['nas_ip']]);
        $session = $stmt->fetch();

        if (!$session)
            return null;

        $deltaTime = ($data['session_time'] ?? 0) - ($session['old_time'] ?? 0);
        $deltaInput = ($data['input_octets'] ?? 0) - ($session['old_input'] ?? 0);
        $deltaOutput = ($data['output_octets'] ?? 0) - ($session['old_output'] ?? 0);

        if ($deltaTime > 0 || $deltaInput > 0 || $deltaOutput > 0) {
            $stmt = $this->pdo->prepare("
                UPDATE vouchers SET
                    time_used = time_used + ?,
                    upload_used = upload_used + ?,
                    download_used = download_used + ?,
                    data_used = data_used + ?
                WHERE id = ?
            ");
            $stmt->execute([
                max(0, $deltaTime),
                max(0, $deltaInput),
                max(0, $deltaOutput),
                max(0, $deltaInput + $deltaOutput),
                $session['id']
            ]);
        }

        // Vérifier si les limites sont dépassées
        $newDataUsed = ($session['data_used'] ?? 0) + max(0, $deltaInput + $deltaOutput);
        $newTimeUsed = ($session['time_used'] ?? 0) + max(0, $deltaTime);

        $exceeded = false;
        $reason = null;

        if ($session['data_limit'] !== null && $newDataUsed >= $session['data_limit']) {
            $exceeded = true;
            $reason = 'Data limit exceeded';
            $this->updateVoucherStatus($session['id'], 'expired');
        }

        if ($session['time_limit'] !== null && $newTimeUsed >= $session['time_limit']) {
            $exceeded = true;
            $reason = 'Time limit exceeded';
            $this->updateVoucherStatus($session['id'], 'expired');
        }

        if ($session['valid_until'] !== null && strtotime($session['valid_until']) < time()) {
            $exceeded = true;
            $reason = 'Validity expired';
            $this->updateVoucherStatus($session['id'], 'expired');
        }

        if ($exceeded) {
            return [
                'exceeded' => true,
                'reason' => $reason,
                'username' => $session['username'],
                'session_id' => $session['acct_session_id'],
                'nas_ip' => $data['nas_ip']
            ];
        }

        return null;
    }

    /**
     * Mettre à jour les compteurs du voucher (stop)
     */
    private function updateVoucherCountersOnStop(array $data): void
    {
        $this->updateVoucherCounters($data);

        $stmt = $this->pdo->prepare("
            SELECT v.id, v.time_limit, v.time_used, v.data_limit, v.data_used, v.valid_until
            FROM vouchers v
            JOIN sessions s ON v.id = s.voucher_id
            WHERE s.acct_session_id = ? AND s.nas_ip = ?
        ");
        $stmt->execute([$data['session_id'], $data['nas_ip']]);
        $voucher = $stmt->fetch();

        if ($voucher) {
            $shouldExpire = false;

            if ($voucher['time_limit'] !== null && $voucher['time_used'] >= $voucher['time_limit']) {
                $shouldExpire = true;
            }
            if ($voucher['data_limit'] !== null && $voucher['data_used'] >= $voucher['data_limit']) {
                $shouldExpire = true;
            }
            if ($voucher['valid_until'] !== null && strtotime($voucher['valid_until']) < time()) {
                $shouldExpire = true;
            }

            if ($shouldExpire) {
                $this->updateVoucherStatus($voucher['id'], 'expired');
            }
        }
    }

    // ==========================================
    // Logs
    // ==========================================

    /**
     * Logger une authentification
     */
    public function logAuth(string $username, string $nasIp, string $action, ?string $reason = null, ?string $clientMac = null, ?string $clientIp = null, ?string $nasIdentifier = null, ?int $adminId = null): void
    {
        // Essayer de trouver le NAS par IP, ou par identifiant
        $stmt = $this->pdo->prepare("SELECT shortname FROM nas WHERE nasname = ? OR (router_id = ? AND router_id IS NOT NULL) OR (shortname = ?)");
        $stmt->execute([$nasIp, $nasIdentifier, $nasIdentifier]);
        $nas = $stmt->fetch();

        $nasNameToSave = $nas['shortname'] ?? $nasIdentifier ?? null;

        $stmt = $this->pdo->prepare("
            INSERT INTO auth_logs (username, nas_ip, nas_name, action, reason, client_mac, client_ip, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$username, $nasIp, $nasNameToSave, $action, $reason, $clientMac, $clientIp, $adminId]);
    }

    /**
     * Obtenir les logs d'authentification
     */
    public function getAuthLogs(array $filters = [], int $page = 1, int $perPage = 50, ?int $adminId = null): array
    {
        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "(a.admin_id = ? OR n.admin_id = ?)";
            $params[] = $adminId;
            $params[] = $adminId;
        }

        if (!empty($filters['username'])) {
            $where[] = "a.username LIKE ?";
            $params[] = '%' . $filters['username'] . '%';
        }

        if (!empty($filters['action'])) {
            $where[] = "a.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['nas_ip'])) {
            $where[] = "a.nas_ip = ?";
            $params[] = $filters['nas_ip'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "a.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "a.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM auth_logs a LEFT JOIN nas n ON a.nas_ip = n.nasname OR (a.nas_name = n.router_id AND n.router_id IS NOT NULL) OR a.nas_name = n.shortname {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        $stmt = $this->pdo->prepare("
            SELECT a.*, n.shortname as nas_display_name, n.router_id as nas_router_id
            FROM auth_logs a
            LEFT JOIN nas n ON (a.nas_ip = n.nasname) OR (a.nas_name = n.router_id AND n.router_id IS NOT NULL) OR (a.nas_name = n.shortname)
            {$whereClause}
            ORDER BY a.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    // ==========================================
    // Profils
    // ==========================================

    /**
     * Obtenir tous les profils
     */
    public function getAllProfiles(bool $activeOnly = false, ?int $adminId = null): array
    {
        $sql = "SELECT * FROM profiles";
        $conditions = [];
        $params = [];

        if ($activeOnly) {
            $conditions[] = "is_active = 1";
        }
        if ($adminId !== null) {
            $conditions[] = "admin_id = ?";
            $params[] = $adminId;
        }
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY name";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un profil par ID
     */
    public function getProfileById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM profiles WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un profil
     * Note: time_limit est configuré au niveau des vouchers, pas des profils
     */
    public function createProfile(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO profiles (
                name, description, time_limit, data_limit, upload_speed, download_speed,
                price, validity, validity_unit, simultaneous_use, zone_id, is_active, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['time_limit'] ?? null,
            $data['data_limit'] ?? null,
            $data['upload_speed'] ?? null,
            $data['download_speed'] ?? null,
            $data['price'] ?? 0,
            $data['validity'] ?? null,
            $data['validity_unit'] ?? 'days',
            $data['simultaneous_use'] ?? 1,
            $data['zone_id'] ?? null,
            $data['is_active'] ?? 1,
            $data['admin_id'] ?? null,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un profil
     * Note: time_limit est configuré au niveau des vouchers, pas des profils
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE profiles SET
                name = ?,
                description = ?,
                time_limit = ?,
                data_limit = ?,
                upload_speed = ?,
                download_speed = ?,
                price = ?,
                validity = ?,
                validity_unit = ?,
                simultaneous_use = ?,
                zone_id = ?,
                is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['time_limit'] ?? null,
            $data['data_limit'] ?? null,
            $data['upload_speed'] ?? null,
            $data['download_speed'] ?? null,
            $data['price'] ?? 0,
            $data['validity'] ?? null,
            $data['validity_unit'] ?? 'days',
            $data['simultaneous_use'] ?? 1,
            $data['zone_id'] ?? null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /**
     * Supprimer un profil
     */
    public function deleteProfile(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM profiles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // Statistiques / Dashboard
    // ==========================================

    /**
     * Obtenir les statistiques du dashboard
     */
    public function getDashboardStats(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null;

        // Sessions actives
        $sql = "SELECT COUNT(*) as count FROM sessions WHERE stop_time IS NULL";
        if ($adminFilter) {
            $sql .= " AND admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $activeSessions = (int)$stmt->fetch()['count'];

        // Vouchers par statut
        $sql = "SELECT status, COUNT(*) as count FROM vouchers";
        if ($adminFilter) {
            $sql .= " WHERE admin_id = ?";
        }
        $sql .= " GROUP BY status";
        if ($adminFilter) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $vouchersByStatus = [];
        while ($row = $stmt->fetch()) {
            $vouchersByStatus[$row['status']] = (int)$row['count'];
        }

        // Total vouchers
        $totalVouchers = array_sum($vouchersByStatus);

        // Data totale consommée aujourd'hui
        $sql = "SELECT COALESCE(SUM(input_octets + output_octets), 0) as total FROM sessions WHERE DATE(start_time) = CURDATE()";
        if ($adminFilter) {
            $sql .= " AND admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $dataToday = (int)$stmt->fetch()['total'];

        // Connexions aujourd'hui
        $sql = "SELECT COUNT(*) as count FROM auth_logs WHERE action = 'accept' AND DATE(created_at) = CURDATE()";
        if ($adminFilter) {
            $sql .= " AND admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $connectionsToday = (int)$stmt->fetch()['count'];

        // Revenus du jour (nouveaux vouchers utilisés)
        $sql = "SELECT COALESCE(SUM(price), 0) as total FROM vouchers WHERE DATE(first_use) = CURDATE()";
        if ($adminFilter) {
            $sql .= " AND admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $revenueToday = (float)$stmt->fetch()['total'];

        // NAS actifs
        $sql = "SELECT COUNT(*) as count FROM nas WHERE nasname != '0.0.0.0/0'";
        if ($adminFilter) {
            $sql .= " AND admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        $nasCount = (int)$stmt->fetch()['count'];

        return [
            'active_sessions' => $activeSessions,
            'total_vouchers' => $totalVouchers,
            'vouchers_by_status' => $vouchersByStatus,
            'data_today' => $dataToday,
            'connections_today' => $connectionsToday,
            'revenue_today' => $revenueToday,
            'nas_count' => $nasCount,
        ];
    }

    /**
     * Stats agrégées complètes pour le dashboard
     */
    public function getFullDashboardStats(?int $adminId = null): array
    {
        return [
            'main' => $this->getDashboardStats($adminId),
            'pppoe' => $this->getPPPoEStats($adminId),
            'billing' => $this->getBillingStats($adminId),
            'monitoring' => $this->getMonitoringStats($adminId),
        ];
    }

    /**
     * Statistiques de connexions par jour
     */
    public function getConnectionsPerDay(int $days = 7, ?int $adminId = null): array
    {
        $sql = "
            SELECT DATE(created_at) as date,
                   SUM(action = 'accept') as accepted,
                   SUM(action = 'reject') as rejected
            FROM auth_logs
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        $params = [$days];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " GROUP BY DATE(created_at) ORDER BY date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Statistiques de data par jour
     */
    public function getDataPerDay(int $days = 7, ?int $adminId = null): array
    {
        $sql = "
            SELECT DATE(start_time) as date,
                   COALESCE(SUM(input_octets), 0) as upload,
                   COALESCE(SUM(output_octets), 0) as download
            FROM sessions
            WHERE start_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        $params = [$days];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " GROUP BY DATE(start_time) ORDER BY date";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Dernières connexions
     */
    public function getRecentConnections(int $limit = 10, ?int $adminId = null): array
    {
        $sql = "SELECT * FROM auth_logs";
        $params = [];
        if ($adminId !== null) {
            $sql .= " WHERE admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ==========================================
    // Admins
    // ==========================================

    /**
     * Authentifier un admin
     */
    public function authenticateAdmin(string $username, string $password): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM admins WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin) {
            return null;
        }

        // Vérifier si le compte est verrouillé
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            return null;
        }

        if (!password_verify($password, $admin['password'])) {
            // Incrémenter les tentatives
            $this->pdo->prepare("
                UPDATE admins SET login_attempts = login_attempts + 1,
                locked_until = IF(login_attempts >= 4, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)
                WHERE id = ?
            ")->execute([$admin['id']]);
            return null;
        }

        // Réinitialiser les tentatives et mettre à jour last_login
        $this->pdo->prepare("
            UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login = NOW()
            WHERE id = ?
        ")->execute([$admin['id']]);

        unset($admin['password']);
        return $admin;
    }

    /**
     * Obtenir un admin par ID
     */
    public function getAdminById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, full_name, role, last_login, created_at FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ==========================================
    // Settings
    // ==========================================

    /**
     * Obtenir un paramètre
     */
    public function getSetting(string $key, $default = null)
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    }

    /**
     * Définir un paramètre
     */
    public function setSetting(string $key, $value): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        return $stmt->execute([$key, $value]);
    }

    /**
     * Obtenir tous les paramètres
     */
    public function getAllSettings(): array
    {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    // ==========================================
    // Payment Gateways
    // ==========================================

    /**
     * Obtenir toutes les passerelles de paiement
     */
    public function getAllPaymentGateways(?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM payment_gateways WHERE admin_id = ? ORDER BY display_order ASC"
            );
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM payment_gateways WHERE admin_id IS NULL ORDER BY display_order ASC");
        }
        $gateways = $stmt->fetchAll();

        // Décoder la config JSON pour chaque passerelle
        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }

        return $gateways;
    }

    /**
     * Obtenir une passerelle par ID
     */
    public function getPaymentGatewayById(int $id, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM payment_gateways WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM payment_gateways WHERE id = ? AND admin_id IS NULL");
            $stmt->execute([$id]);
        }
        $gateway = $stmt->fetch();

        if ($gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }

        return $gateway ?: null;
    }

    /**
     * Obtenir une passerelle par code
     */
    public function getPaymentGatewayByCode(string $code, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM payment_gateways WHERE gateway_code = ? AND admin_id = ?");
            $stmt->execute([$code, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM payment_gateways WHERE gateway_code = ? AND admin_id IS NULL");
            $stmt->execute([$code]);
        }
        $gateway = $stmt->fetch();

        if ($gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }

        return $gateway ?: null;
    }

    /**
     * Mettre à jour une passerelle de paiement
     */
    public function updatePaymentGateway(int $id, array $data): bool
    {
        $config = isset($data['config']) ? json_encode($data['config']) : null;

        $stmt = $this->pdo->prepare("
            UPDATE payment_gateways SET
                name = COALESCE(?, name),
                description = COALESCE(?, description),
                logo_url = COALESCE(?, logo_url),
                is_active = COALESCE(?, is_active),
                is_sandbox = COALESCE(?, is_sandbox),
                config = COALESCE(?, config)
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'] ?? null,
            $data['description'] ?? null,
            $data['logo_url'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : null,
            isset($data['is_sandbox']) ? (int)$data['is_sandbox'] : null,
            $config,
            $id
        ]);
    }

    /**
     * Activer/Désactiver une passerelle
     */
    public function togglePaymentGateway(int $id, bool $active): bool
    {
        $stmt = $this->pdo->prepare("UPDATE payment_gateways SET is_active = ? WHERE id = ?");
        return $stmt->execute([(int)$active, $id]);
    }

    /**
     * Obtenir les passerelles actives
     */
    public function getActivePaymentGateways(?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM payment_gateways WHERE is_active = 1 AND admin_id = ? ORDER BY display_order ASC"
            );
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM payment_gateways WHERE is_active = 1 AND admin_id IS NULL ORDER BY display_order ASC");
        }
        $gateways = $stmt->fetchAll();

        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }

        return $gateways;
    }

    /**
     * Obtenir les passerelles globales de recharge (admin_id IS NULL)
     */
    public function getGlobalRechargeGateways(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_gateways WHERE admin_id IS NULL ORDER BY display_order ASC");
        $gateways = $stmt->fetchAll();
        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        return $gateways;
    }

    /**
     * Obtenir les passerelles globales de recharge actives
     */
    public function getActiveGlobalRechargeGateways(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM payment_gateways WHERE admin_id IS NULL AND is_active = 1 ORDER BY display_order ASC");
        $gateways = $stmt->fetchAll();
        foreach ($gateways as &$gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        return $gateways;
    }

    /**
     * Obtenir une passerelle globale par code (admin_id IS NULL)
     */
    public function getGlobalGatewayByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM payment_gateways WHERE gateway_code = ? AND admin_id IS NULL");
        $stmt->execute([$code]);
        $gateway = $stmt->fetch();
        if ($gateway) {
            $gateway['config'] = json_decode($gateway['config'], true) ?? [];
        }
        return $gateway ?: null;
    }

    // ==========================================
    // Media Library
    // ==========================================

    /**
     * Obtenir tous les médias de la bibliothèque
     */
    public function getAllMedia(?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE admin_id = ? ORDER BY media_type, media_key");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM media_library ORDER BY media_type, media_key");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un média par clé
     */
    public function getMediaByKey(string $key, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE media_key = ? AND admin_id = ?");
            $stmt->execute([$key, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE media_key = ?");
            $stmt->execute([$key]);
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir un média par ID
     */
    public function getMediaById(int $id, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE id = ?");
            $stmt->execute([$id]);
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Mettre à jour un média
     */
    public function updateMedia(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE media_library SET
                original_name = ?,
                file_path = ?,
                file_size = ?,
                mime_type = ?,
                description = COALESCE(?, description)
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['original_name'] ?? null,
            $data['file_path'] ?? null,
            $data['file_size'] ?? null,
            $data['mime_type'] ?? null,
            $data['description'] ?? null,
            $id
        ]);
    }

    /**
     * Supprimer le fichier d'un média (reset)
     */
    public function clearMedia(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE media_library SET
                original_name = NULL,
                file_path = NULL,
                file_size = NULL,
                mime_type = NULL
            WHERE id = ?
        ");

        return $stmt->execute([$id]);
    }

    /**
     * Obtenir les médias par type
     */
    public function getMediaByType(string $type, ?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE media_type = ? AND admin_id = ? ORDER BY media_key");
            $stmt->execute([$type, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM media_library WHERE media_type = ? ORDER BY media_key");
            $stmt->execute([$type]);
        }
        return $stmt->fetchAll();
    }

    // ==========================================
    // Voucher Templates
    // ==========================================

    /**
     * Obtenir tous les templates de vouchers
     */
    public function getAllVoucherTemplates(?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM voucher_templates WHERE admin_id = ? ORDER BY is_default DESC, name ASC");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM voucher_templates ORDER BY is_default DESC, name ASC");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un template de voucher par ID
     */
    public function getVoucherTemplateById(int $id, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM voucher_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM voucher_templates WHERE id = ?");
            $stmt->execute([$id]);
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir le template par défaut
     */
    public function getDefaultVoucherTemplate(?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM voucher_templates WHERE is_default = 1 AND admin_id = ? LIMIT 1");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM voucher_templates WHERE is_default = 1 LIMIT 1");
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un template de voucher
     */
    public function createVoucherTemplate(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO voucher_templates (
                name, description, template_type, paper_size, orientation,
                columns_count, rows_count, show_logo, show_qr_code, show_password,
                show_validity, show_speed, show_price, header_text, footer_text,
                background_color, border_color, primary_color, text_color, custom_css, is_default, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['template_type'] ?? 'simple',
            $data['paper_size'] ?? 'A4',
            $data['orientation'] ?? 'portrait',
            $data['columns_count'] ?? 2,
            $data['rows_count'] ?? 5,
            $data['show_logo'] ?? 1,
            $data['show_qr_code'] ?? 0,
            $data['show_password'] ?? 1,
            $data['show_validity'] ?? 1,
            $data['show_speed'] ?? 0,
            $data['show_price'] ?? 1,
            $data['header_text'] ?? null,
            $data['footer_text'] ?? null,
            $data['background_color'] ?? '#ffffff',
            $data['border_color'] ?? '#e5e7eb',
            $data['primary_color'] ?? '#3b82f6',
            $data['text_color'] ?? '#1f2937',
            $data['custom_css'] ?? null,
            $data['is_default'] ?? 0,
            $data['admin_id'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un template de voucher
     */
    public function updateVoucherTemplate(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE voucher_templates SET
                name = ?,
                description = ?,
                template_type = ?,
                paper_size = ?,
                orientation = ?,
                columns_count = ?,
                rows_count = ?,
                show_logo = ?,
                show_qr_code = ?,
                show_password = ?,
                show_validity = ?,
                show_speed = ?,
                show_price = ?,
                header_text = ?,
                footer_text = ?,
                background_color = ?,
                border_color = ?,
                primary_color = ?,
                text_color = ?,
                custom_css = ?,
                is_default = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['template_type'] ?? 'simple',
            $data['paper_size'] ?? 'A4',
            $data['orientation'] ?? 'portrait',
            $data['columns_count'] ?? 2,
            $data['rows_count'] ?? 5,
            $data['show_logo'] ?? 1,
            $data['show_qr_code'] ?? 0,
            $data['show_password'] ?? 1,
            $data['show_validity'] ?? 1,
            $data['show_speed'] ?? 0,
            $data['show_price'] ?? 1,
            $data['header_text'] ?? null,
            $data['footer_text'] ?? null,
            $data['background_color'] ?? '#ffffff',
            $data['border_color'] ?? '#e5e7eb',
            $data['primary_color'] ?? '#3b82f6',
            $data['text_color'] ?? '#1f2937',
            $data['custom_css'] ?? null,
            $data['is_default'] ?? 0,
            $id
        ]);
    }

    /**
     * Supprimer un template de voucher
     */
    public function deleteVoucherTemplate(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM voucher_templates WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Définir un template comme défaut
     */
    public function setDefaultVoucherTemplate(int $id, ?int $adminId = null): bool
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("UPDATE voucher_templates SET is_default = 0 WHERE admin_id = ?");
            $stmt->execute([$adminId]);
        }
        else {
            $this->pdo->exec("UPDATE voucher_templates SET is_default = 0");
        }
        $stmt = $this->pdo->prepare("UPDATE voucher_templates SET is_default = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // Hotspot Templates
    // ==========================================

    /**
     * Obtenir tous les templates hotspot
     */
    public function getAllHotspotTemplates(?int $adminId = null): array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM hotspot_templates WHERE admin_id = ? ORDER BY is_default DESC, name ASC");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM hotspot_templates ORDER BY is_default DESC, name ASC");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un template hotspot par ID
     */
    public function getHotspotTemplateById(int $id, ?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM hotspot_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
        }
        else {
            $stmt = $this->pdo->prepare("SELECT * FROM hotspot_templates WHERE id = ?");
            $stmt->execute([$id]);
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir le template hotspot par défaut
     */
    public function getDefaultHotspotTemplate(?int $adminId = null): ?array
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("SELECT * FROM hotspot_templates WHERE is_default = 1 AND admin_id = ? LIMIT 1");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("SELECT * FROM hotspot_templates WHERE is_default = 1 LIMIT 1");
        }
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un template hotspot
     */
    public function createHotspotTemplate(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO hotspot_templates (
                name, description, template_code, logo_position, background_type,
                background_color, background_gradient_start, background_gradient_end, background_image,
                primary_color, secondary_color, text_color, card_bg_color, card_text_color,
                title_text, subtitle_text, login_button_text, username_placeholder, password_placeholder, footer_text,
                show_logo, show_password_field, show_remember_me, show_footer, show_social_login, show_terms_link, terms_url,
                show_chat_support, chat_support_type, chat_whatsapp_phone, chat_welcome_message,
                html_content, css_content, js_content, config, is_default, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['template_code'],
            $data['logo_position'] ?? 'center',
            $data['background_type'] ?? 'gradient',
            $data['background_color'] ?? '#1e3a5f',
            $data['background_gradient_start'] ?? '#1e3a5f',
            $data['background_gradient_end'] ?? '#0d1b2a',
            $data['background_image'] ?? null,
            $data['primary_color'] ?? '#3b82f6',
            $data['secondary_color'] ?? '#10b981',
            $data['text_color'] ?? '#ffffff',
            $data['card_bg_color'] ?? '#ffffff',
            $data['card_text_color'] ?? '#1f2937',
            $data['title_text'] ?? 'Bienvenue sur notre WiFi',
            $data['subtitle_text'] ?? 'Connectez-vous pour accéder à Internet',
            $data['login_button_text'] ?? 'Se connecter',
            $data['username_placeholder'] ?? 'Code voucher',
            $data['password_placeholder'] ?? 'Mot de passe',
            $data['footer_text'] ?? 'Powered by RADIUS Manager',
            (int)($data['show_logo'] ?? 1),
            (int)($data['show_password_field'] ?? 1),
            (int)($data['show_remember_me'] ?? 0),
            (int)($data['show_footer'] ?? 1),
            (int)($data['show_social_login'] ?? 0),
            (int)($data['show_terms_link'] ?? 0),
            $data['terms_url'] ?? null,
            (int)($data['show_chat_support'] ?? 0),
            $data['chat_support_type'] ?? 'whatsapp',
            $data['chat_whatsapp_phone'] ?? null,
            $data['chat_welcome_message'] ?? 'Bonjour ! Comment puis-je vous aider ?',
            $data['html_content'] ?? null,
            $data['css_content'] ?? null,
            $data['js_content'] ?? null,
            $data['config'] ?? null,
            (int)($data['is_default'] ?? 0),
            $data['admin_id'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un template hotspot
     */
    public function updateHotspotTemplate(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE hotspot_templates SET
                name = ?,
                description = ?,
                logo_position = ?,
                background_type = ?,
                background_color = ?,
                background_gradient_start = ?,
                background_gradient_end = ?,
                background_image = ?,
                primary_color = ?,
                secondary_color = ?,
                text_color = ?,
                card_bg_color = ?,
                card_text_color = ?,
                title_text = ?,
                subtitle_text = ?,
                login_button_text = ?,
                username_placeholder = ?,
                password_placeholder = ?,
                footer_text = ?,
                show_logo = ?,
                show_password_field = ?,
                show_remember_me = ?,
                show_footer = ?,
                show_social_login = ?,
                show_terms_link = ?,
                terms_url = ?,
                show_chat_support = ?,
                chat_support_type = ?,
                chat_whatsapp_phone = ?,
                chat_welcome_message = ?,
                html_content = ?,
                css_content = ?,
                js_content = ?,
                config = ?,
                is_default = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['logo_position'] ?? 'center',
            $data['background_type'] ?? 'gradient',
            $data['background_color'] ?? '#1e3a5f',
            $data['background_gradient_start'] ?? '#1e3a5f',
            $data['background_gradient_end'] ?? '#0d1b2a',
            $data['background_image'] ?? null,
            $data['primary_color'] ?? '#3b82f6',
            $data['secondary_color'] ?? '#10b981',
            $data['text_color'] ?? '#ffffff',
            $data['card_bg_color'] ?? '#ffffff',
            $data['card_text_color'] ?? '#1f2937',
            $data['title_text'] ?? 'Bienvenue sur notre WiFi',
            $data['subtitle_text'] ?? 'Connectez-vous pour accéder à Internet',
            $data['login_button_text'] ?? 'Se connecter',
            $data['username_placeholder'] ?? 'Code voucher',
            $data['password_placeholder'] ?? 'Mot de passe',
            $data['footer_text'] ?? 'Powered by RADIUS Manager',
            (int)($data['show_logo'] ?? 1),
            (int)($data['show_password_field'] ?? 1),
            (int)($data['show_remember_me'] ?? 0),
            (int)($data['show_footer'] ?? 1),
            (int)($data['show_social_login'] ?? 0),
            (int)($data['show_terms_link'] ?? 0),
            $data['terms_url'] ?? null,
            (int)($data['show_chat_support'] ?? 0),
            $data['chat_support_type'] ?? 'whatsapp',
            $data['chat_whatsapp_phone'] ?? null,
            $data['chat_welcome_message'] ?? 'Bonjour ! Comment puis-je vous aider ?',
            $data['html_content'] ?? null,
            $data['css_content'] ?? null,
            $data['js_content'] ?? null,
            $data['config'] ?? null,
            (int)($data['is_default'] ?? 0),
            $id
        ]);
    }

    /**
     * Supprimer un template hotspot
     */
    public function deleteHotspotTemplate(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM hotspot_templates WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Définir un template hotspot comme défaut
     */
    public function setDefaultHotspotTemplate(int $id, ?int $adminId = null): bool
    {
        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("UPDATE hotspot_templates SET is_default = 0 WHERE admin_id = ?");
            $stmt->execute([$adminId]);
        }
        else {
            $this->pdo->exec("UPDATE hotspot_templates SET is_default = 0");
        }
        $stmt = $this->pdo->prepare("UPDATE hotspot_templates SET is_default = 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // Payment Transactions
    // ==========================================

    /**
     * Migration: Créer la table payment_transactions
     */
    public function migratePaymentTransactions(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS payment_transactions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                transaction_id VARCHAR(100) NOT NULL UNIQUE,
                gateway_code VARCHAR(50) NOT NULL,
                profile_id INT NOT NULL,
                amount DECIMAL(10,2) NOT NULL,
                currency VARCHAR(10) DEFAULT 'XAF',
                status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
                customer_phone VARCHAR(50) DEFAULT NULL,
                customer_email VARCHAR(100) DEFAULT NULL,
                customer_name VARCHAR(100) DEFAULT NULL,
                gateway_transaction_id VARCHAR(100) DEFAULT NULL,
                gateway_response JSON DEFAULT NULL,
                voucher_id INT DEFAULT NULL,
                voucher_code VARCHAR(64) DEFAULT NULL,
                paid_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_gateway (gateway_code),
                INDEX idx_profile (profile_id),
                INDEX idx_voucher (voucher_id)
            ) ENGINE=InnoDB
        ";
        $this->pdo->exec($sql);
    }

    /**
     * Obtenir toutes les transactions
     */
    public function getAllTransactions(array $filters = [], ?int $adminId = null): array
    {
        $sql = "SELECT pt.*, p.name as profile_name
                FROM payment_transactions pt
                LEFT JOIN profiles p ON pt.profile_id = p.id
                WHERE 1=1";
        $params = [];

        if ($adminId !== null) {
            $sql .= " AND pt.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['status'])) {
            $sql .= " AND pt.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['gateway_code'])) {
            $sql .= " AND pt.gateway_code = ?";
            $params[] = $filters['gateway_code'];
        }

        if (!empty($filters['profile_id'])) {
            $sql .= " AND pt.profile_id = ?";
            $params[] = $filters['profile_id'];
        }

        $sql .= " ORDER BY pt.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        foreach ($transactions as &$t) {
            if ($t['gateway_response']) {
                $t['gateway_response'] = json_decode($t['gateway_response'], true);
            }
            if (!empty($t['device_info'])) {
                $t['device_info'] = json_decode($t['device_info'], true);
            }
        }

        return $transactions;
    }

    /**
     * Obtenir une transaction par ID
     */
    public function getTransactionById(string $transactionId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT pt.*, p.name as profile_name
            FROM payment_transactions pt
            LEFT JOIN profiles p ON pt.profile_id = p.id
            WHERE pt.transaction_id = ?
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if ($transaction && $transaction['gateway_response']) {
            $transaction['gateway_response'] = json_decode($transaction['gateway_response'], true);
        }
        if ($transaction && !empty($transaction['device_info'])) {
            $transaction['device_info'] = json_decode($transaction['device_info'], true);
        }

        return $transaction ?: null;
    }

    /**
     * Statistiques des transactions
     */
    public function getTransactionStats(?int $adminId = null): array
    {
        $sql = "
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_revenue,
                SUM(CASE WHEN status = 'completed' AND DATE(paid_at) = CURDATE() THEN amount ELSE 0 END) as today_revenue
            FROM payment_transactions
        ";
        if ($adminId !== null) {
            $sql .= " WHERE admin_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query($sql);
        }
        return $stmt->fetch();
    }

    // ==========================================
    // PPPoE Users & Authentication
    // ==========================================

    /**
     * Authentifier un utilisateur PPPoE
     */
    public function authenticatePPPoEUser(string $username, string $password, ?string $nasIp = null, ?string $nasIdentifier = null): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pu.*, pp.name as profile_name, pp.download_speed, pp.upload_speed,
                   pp.data_limit as profile_data_limit, pp.ip_pool_name, pp.mikrotik_group,
                   pp.simultaneous_use as profile_simultaneous_use,
                   pp.burst_download, pp.burst_upload, pp.burst_threshold, pp.burst_time
            FROM pppoe_users pu
            JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.username = ?
        ");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'reason' => 'PPPoE user not found', 'is_pppoe' => true];
        }

        // Vérifier le mot de passe
        if ($user['password'] !== $password && !password_verify($password, $user['password'])) {
            return ['success' => false, 'reason' => 'Invalid password', 'is_pppoe' => true];
        }

        // Vérifier la zone si l'utilisateur a une zone
        if ($user['zone_id'] !== null) {
            $nasZone = null;

            if ($nasIdentifier !== null && $nasIdentifier !== '') {
                $nasZone = $this->getNasZoneByIdentifier($nasIdentifier);
            }

            if ($nasZone === null && $nasIp !== null) {
                $nasZone = $this->getNasZoneByIp($nasIp);
            }

            if ($nasZone !== null && (int)$user['zone_id'] !== (int)$nasZone) {
                return ['success' => false, 'reason' => 'User not valid for this zone', 'is_pppoe' => true];
            }
        }

        // Vérifier le statut
        if ($user['status'] === 'disabled') {
            return ['success' => false, 'reason' => 'Account disabled', 'is_pppoe' => true];
        }

        if ($user['status'] === 'suspended') {
            return ['success' => false, 'reason' => 'Account suspended', 'is_pppoe' => true];
        }

        if ($user['status'] === 'expired') {
            return ['success' => false, 'reason' => 'Account expired', 'is_pppoe' => true];
        }

        // Vérifier la validité
        if ($user['valid_from'] && strtotime($user['valid_from']) > time()) {
            return ['success' => false, 'reason' => 'Account not yet valid', 'is_pppoe' => true];
        }

        if ($user['valid_until'] && strtotime($user['valid_until']) < time()) {
            $this->updatePPPoEUserStatus($user['id'], 'expired');
            return ['success' => false, 'reason' => 'Account expired', 'is_pppoe' => true];
        }

        // Vérifier la limite de données
        if ($user['profile_data_limit'] > 0 && $user['data_used'] >= $user['profile_data_limit']) {
            $this->updatePPPoEUserStatus($user['id'], 'expired');
            return ['success' => false, 'reason' => 'Data limit exceeded', 'is_pppoe' => true];
        }

        // Vérifier les sessions simultanées
        $activeSessions = $this->countActivePPPoESessions($user['id']);
        $maxSessions = $user['profile_simultaneous_use'] ?? 1;
        if ($activeSessions >= $maxSessions) {
            return ['success' => false, 'reason' => 'Too many simultaneous connections', 'is_pppoe' => true];
        }

        // Marquer première utilisation
        if ($user['first_use'] === null) {
            $this->markPPPoEFirstUse($user['id']);
            $user['first_use'] = date('Y-m-d H:i:s');
        }

        // Mettre à jour last_login
        $this->pdo->prepare("UPDATE pppoe_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

        return ['success' => true, 'user' => $user, 'is_pppoe' => true];
    }

    /**
     * Compter les sessions PPPoE actives
     * Exclut les sessions orphelines (pas de mise à jour depuis plus de 10 minutes)
     */
    public function countActivePPPoESessions(int $userId): int
    {
        // D'abord nettoyer les sessions orphelines
        $this->cleanOrphanedPPPoESessions($userId);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM pppoe_sessions
            WHERE pppoe_user_id = ? AND stop_time IS NULL
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Nettoyer les sessions PPPoE orphelines pour un utilisateur
     * Une session est considérée orpheline si:
     * - Pas de stop_time (session supposée active)
     * - last_update ou start_time > 10 minutes (2x l'interim-update typique)
     */
    public function cleanOrphanedPPPoESessions(int $userId): int
    {
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_sessions
            SET stop_time = NOW(), terminate_cause = 'Orphaned-Session'
            WHERE pppoe_user_id = ?
            AND stop_time IS NULL
            AND (
                (last_update IS NOT NULL AND last_update < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
                OR (last_update IS NULL AND start_time < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
            )
        ");
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    /**
     * Marquer la première utilisation PPPoE
     */
    private function markPPPoEFirstUse(int $userId): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users SET first_use = NOW(), status = 'active' WHERE id = ? AND first_use IS NULL
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Mettre à jour le statut d'un utilisateur PPPoE
     */
    public function updatePPPoEUserStatus(int $userId, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE pppoe_users SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $userId]);
    }

    /**
     * Démarrer une session PPPoE
     */
    public function startPPPoESession(array $data): bool
    {
        // Trouver l'utilisateur PPPoE avec son admin_id
        $stmt = $this->pdo->prepare("SELECT id, admin_id FROM pppoe_users WHERE username = ?");
        $stmt->execute([$data['username']]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_sessions (
                pppoe_user_id, acct_session_id, nas_ip, nas_port, nas_identifier,
                client_ip, client_mac, calling_station_id, called_station_id, start_time, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE start_time = NOW(), stop_time = NULL, admin_id = VALUES(admin_id)
        ");

        $result = $stmt->execute([
            $user['id'],
            $data['acct_session_id'],
            $data['nas_ip'],
            $data['nas_port'] ?? null,
            $data['nas_identifier'] ?? null,
            $data['client_ip'] ?? null,
            $data['client_mac'] ?? null,
            $data['calling_station_id'] ?? null,
            $data['called_station_id'] ?? null,
            $user['admin_id']
        ]);

        // Tracker la vente si pas encore fait
        if ($result) {
            $stmt = $this->pdo->prepare("SELECT * FROM pppoe_users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $pppoeUser = $stmt->fetch();

            if ($pppoeUser && empty($pppoeUser['sold_on_nas_id'])) {
                $this->trackPPPoEUserSale($pppoeUser, $data['nas_ip'], $data['nas_identifier'] ?? null);
            }

            // Mettre à jour last_mac et last_ip
            $mac = $data['calling_station_id'] ?? $data['client_mac'] ?? null;
            $ip = $data['client_ip'] ?? null;
            if ($mac || $ip) {
                $updateStmt = $this->pdo->prepare("UPDATE pppoe_users SET last_mac = COALESCE(?, last_mac), last_ip = COALESCE(?, last_ip) WHERE id = ?");
                $updateStmt->execute([$mac, $ip, $user['id']]);
            }

        }

        return $result;
    }

    /**
     * Mettre à jour une session PPPoE (interim-update)
     */
    public function updatePPPoESession(array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_sessions SET
                session_time = ?,
                input_octets = ?,
                output_octets = ?,
                input_packets = ?,
                output_packets = ?,
                last_update = NOW()
            WHERE acct_session_id = ? AND nas_ip = ? AND stop_time IS NULL
        ");

        $result = $stmt->execute([
            $data['session_time'] ?? 0,
            $data['input_octets'] ?? 0,
            $data['output_octets'] ?? 0,
            $data['input_packets'] ?? 0,
            $data['output_packets'] ?? 0,
            $data['acct_session_id'],
            $data['nas_ip']
        ]);

        // Mettre à jour les compteurs de l'utilisateur
        if ($result) {
            $stmt = $this->pdo->prepare("
                SELECT ps.pppoe_user_id, ps.input_octets, ps.output_octets, ps.session_time
                FROM pppoe_sessions ps
                WHERE ps.acct_session_id = ? AND ps.nas_ip = ?
            ");
            $stmt->execute([$data['acct_session_id'], $data['nas_ip']]);
            $session = $stmt->fetch();

            if ($session) {
                // Calculer le total des données utilisées pour cet utilisateur (toutes sessions)
                $stmt = $this->pdo->prepare("
                    SELECT
                        COALESCE(SUM(input_octets + output_octets), 0) as total_data,
                        COALESCE(SUM(session_time), 0) as total_time
                    FROM pppoe_sessions
                    WHERE pppoe_user_id = ?
                ");
                $stmt->execute([$session['pppoe_user_id']]);
                $totals = $stmt->fetch();

                // Récupérer l'offset FUP (consommation au moment du dernier reset)
                $stmt = $this->pdo->prepare("SELECT fup_data_offset FROM pppoe_users WHERE id = ?");
                $stmt->execute([$session['pppoe_user_id']]);
                $user = $stmt->fetch();
                $fupOffset = $user['fup_data_offset'] ?? 0;

                // Calculer la consommation FUP = total - offset
                $fupDataUsed = max(0, $totals['total_data'] - $fupOffset);

                // Mettre à jour data_used (total) et fup_data_used (depuis dernier reset)
                $this->pdo->prepare("
                    UPDATE pppoe_users SET data_used = ?, time_used = ?, fup_data_used = ? WHERE id = ?
                ")->execute([$totals['total_data'], $totals['total_time'], $fupDataUsed, $session['pppoe_user_id']]);

                // Vérifier et déclencher le FUP si nécessaire
                $this->checkAndTriggerFup($session['pppoe_user_id']);
            }
        }

        return $result;
    }

    /**
     * Terminer une session PPPoE
     */
    public function stopPPPoESession(array $data): bool
    {
        // D'abord mettre à jour les compteurs finaux
        $this->updatePPPoESession($data);

        $stmt = $this->pdo->prepare("
            UPDATE pppoe_sessions SET
                stop_time = NOW(),
                terminate_cause = ?
            WHERE acct_session_id = ? AND nas_ip = ? AND stop_time IS NULL
        ");

        return $stmt->execute([
            $data['terminate_cause'] ?? 'User-Request',
            $data['acct_session_id'],
            $data['nas_ip']
        ]);
    }

    /**
     * Tracker la vente d'un utilisateur PPPoE
     */
    private function trackPPPoEUserSale(array $user, string $nasIp, ?string $nasIdentifier): void
    {
        // Trouver le NAS
        $nasId = null;
        $zoneId = null;

        if ($nasIdentifier) {
            $stmt = $this->pdo->prepare("SELECT id, zone_id FROM nas WHERE router_id = ?");
            $stmt->execute([$nasIdentifier]);
            $nas = $stmt->fetch();
            if ($nas) {
                $nasId = $nas['id'];
                $zoneId = $nas['zone_id'];
            }
        }

        if (!$nasId) {
            $stmt = $this->pdo->prepare("SELECT id, zone_id FROM nas WHERE nasname = ?");
            $stmt->execute([$nasIp]);
            $nas = $stmt->fetch();
            if ($nas) {
                $nasId = $nas['id'];
                $zoneId = $nas['zone_id'];
            }
        }

        if (!$nasId) {
            return;
        }

        // Trouver le vendeur assigné à ce NAS
        $stmt = $this->pdo->prepare("
            SELECT un.user_id, u.role, u.parent_id
            FROM user_nas un
            JOIN users u ON un.user_id = u.id
            WHERE un.nas_id = ? AND un.can_manage = 1 AND u.is_active = 1 AND u.role IN ('vendeur', 'gerant', 'admin')
            ORDER BY FIELD(u.role, 'vendeur', 'gerant', 'admin') ASC
            LIMIT 1
        ");
        $stmt->execute([$nasId]);
        $nasSeller = $stmt->fetch();

        $vendeurId = !empty($user['vendeur_id']) ? $user['vendeur_id'] : ($nasSeller ? $nasSeller['user_id'] : null);
        $gerantId = null;
        $adminId = $user['admin_id'] ?? null;
        $soldBy = $vendeurId;

        if ($vendeurId) {
            $stmtUser = $this->pdo->prepare("SELECT id, role, parent_id FROM users WHERE id = ?");
            $stmtUser->execute([$vendeurId]);
            $userTarget = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if ($userTarget) {
                if ($userTarget['role'] === 'vendeur') {
                    $stmtParent = $this->pdo->prepare("SELECT id, role, parent_id FROM users WHERE id = ?");
                    $stmtParent->execute([$userTarget['parent_id']]);
                    $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
                    if ($parent) {
                        if ($parent['role'] === 'gerant') {
                            $gerantId = $parent['id'];
                            if (!$adminId)
                                $adminId = $parent['parent_id'];
                        }
                        elseif ($parent['role'] === 'admin') {
                            if (!$adminId)
                                $adminId = $parent['id'];

                            // Parent n'est pas gérant, on cherche par zone assignée!
                            $stmtZone = $this->pdo->prepare("
                                SELECT u.id, u.parent_id 
                                FROM user_zones uz 
                                JOIN users u ON uz.user_id = u.id 
                                WHERE uz.zone_id IN (SELECT zone_id FROM user_zones WHERE user_id = ?) 
                                AND u.role = 'gerant' 
                                LIMIT 1
                            ");
                            $stmtZone->execute([$vendeurId]);
                            $gerantCheck = $stmtZone->fetch(PDO::FETCH_ASSOC);
                            if ($gerantCheck) {
                                $gerantId = $gerantCheck['id'];
                                if (!$adminId) {
                                    $adminId = $gerantCheck['parent_id'];
                                }
                            }
                        }
                    }
                }
                elseif ($userTarget['role'] === 'gerant') {
                    $gerantId = $userTarget['id'];
                    $vendeurId = null;
                    if (!$adminId)
                        $adminId = $userTarget['parent_id'];
                }
                elseif ($userTarget['role'] === 'admin') {
                    if (!$adminId)
                        $adminId = $userTarget['id'];
                    $vendeurId = null;
                }
            }
        }

        // Calculer les commissions
        $commissions = $this->calculateSaleCommissions($user['sale_amount'] ?? 0, $zoneId, $user['profile_id']);

        // Mettre à jour l'utilisateur PPPoE
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users SET
                vendeur_id = COALESCE(vendeur_id, ?),
                gerant_id = COALESCE(gerant_id, ?),
                sold_by = COALESCE(sold_by, ?),
                sold_at = COALESCE(sold_at, NOW()),
                sold_on_nas_id = ?,
                commission_vendeur = ?,
                commission_gerant = ?,
                commission_admin = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $vendeurId,
            $gerantId,
            $soldBy,
            $nasId,
            $commissions['vendeur'],
            $commissions['gerant'],
            $commissions['admin'],
            $user['id']
        ]);
    }

    /**
     * Log d'authentification PPPoE
     */
    public function logPPPoEAuth(string $username, string $nasIp, ?string $nasIdentifier, string $action, ?string $reason = null, ?string $mac = null, ?string $callingStationId = null): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO pppoe_auth_logs (username, nas_ip, nas_identifier, action, reason, client_mac, calling_station_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$username, $nasIp, $nasIdentifier, $action, $reason, $mac, $callingStationId]);
        }
        catch (PDOException $e) {
            error_log("PPPoE Auth Log error: " . $e->getMessage());
        }
    }

    // ==========================================
    // PPPoE Profiles
    // ==========================================

    /**
     * Obtenir tous les profils PPPoE
     */
    public function getAllPPPoEProfiles(array $filters = [], ?int $adminId = null): array
    {
        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "pp.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['zone_id'])) {
            $where[] = "(pp.zone_id = ? OR pp.zone_id IS NULL)";
            $params[] = $filters['zone_id'];
        }

        if (isset($filters['is_active'])) {
            $where[] = "pp.is_active = ?";
            $params[] = (int)$filters['is_active'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->pdo->prepare("
            SELECT pp.*,
                   z.name as zone_name,
                   (SELECT COUNT(*) FROM pppoe_users pu WHERE pu.profile_id = pp.id) as users_count
            FROM pppoe_profiles pp
            LEFT JOIN zones z ON pp.zone_id = z.id
            {$whereClause}
            ORDER BY pp.name ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un profil PPPoE par ID
     */
    public function getPPPoEProfileById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT pp.*, z.name as zone_name
            FROM pppoe_profiles pp
            LEFT JOIN zones z ON pp.zone_id = z.id
            WHERE pp.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un profil PPPoE
     */
    public function createPPPoEProfile(array $data): int
    {
        // Vérifier si les colonnes FUP existent
        $this->ensureFupColumnsExist();

        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_profiles (
                zone_id, name, description, download_speed, upload_speed,
                data_limit, validity_days, price, ip_pool_name, mikrotik_group,
                bandwidth_policy_id, simultaneous_use, burst_download, burst_upload, burst_threshold, burst_time,
                fup_enabled, fup_quota, fup_download_speed, fup_upload_speed, fup_reset_day, fup_reset_type,
                is_active, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['zone_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['download_speed'] ?? 1048576,
            $data['upload_speed'] ?? 524288,
            $data['data_limit'] ?? 0,
            $data['validity_days'] ?? 30,
            $data['price'] ?? 0,
            $data['ip_pool_name'] ?? null,
            $data['mikrotik_group'] ?? null,
            !empty($data['bandwidth_policy_id']) ? (int)$data['bandwidth_policy_id'] : null,
            $data['simultaneous_use'] ?? 1,
            $data['burst_download'] ?? 0,
            $data['burst_upload'] ?? 0,
            $data['burst_threshold'] ?? 0,
            $data['burst_time'] ?? 0,
            (int)($data['fup_enabled'] ?? 0),
            (int)($data['fup_quota'] ?? 0),
            (int)($data['fup_download_speed'] ?? 0),
            (int)($data['fup_upload_speed'] ?? 0),
            (int)($data['fup_reset_day'] ?? 1),
            $data['fup_reset_type'] ?? 'monthly',
            $data['is_active'] ?? 1,
            $data['admin_id'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un profil PPPoE
     */
    public function updatePPPoEProfile(int $id, array $data): bool
    {
        // Vérifier si les colonnes FUP existent
        $this->ensureFupColumnsExist();

        $stmt = $this->pdo->prepare("
            UPDATE pppoe_profiles SET
                zone_id = ?,
                name = ?,
                description = ?,
                download_speed = ?,
                upload_speed = ?,
                data_limit = ?,
                validity_days = ?,
                price = ?,
                ip_pool_name = ?,
                mikrotik_group = ?,
                bandwidth_policy_id = ?,
                simultaneous_use = ?,
                burst_download = ?,
                burst_upload = ?,
                burst_threshold = ?,
                burst_time = ?,
                fup_enabled = ?,
                fup_quota = ?,
                fup_download_speed = ?,
                fup_upload_speed = ?,
                fup_reset_day = ?,
                fup_reset_type = ?,
                is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['zone_id'] ?? null,
            $data['name'],
            $data['description'] ?? null,
            $data['download_speed'] ?? 1048576,
            $data['upload_speed'] ?? 524288,
            $data['data_limit'] ?? 0,
            $data['validity_days'] ?? 30,
            $data['price'] ?? 0,
            $data['ip_pool_name'] ?? null,
            $data['mikrotik_group'] ?? null,
            !empty($data['bandwidth_policy_id']) ? (int)$data['bandwidth_policy_id'] : null,
            $data['simultaneous_use'] ?? 1,
            $data['burst_download'] ?? 0,
            $data['burst_upload'] ?? 0,
            $data['burst_threshold'] ?? 0,
            $data['burst_time'] ?? 0,
            (int)($data['fup_enabled'] ?? 0),
            (int)($data['fup_quota'] ?? 0),
            (int)($data['fup_download_speed'] ?? 0),
            (int)($data['fup_upload_speed'] ?? 0),
            (int)($data['fup_reset_day'] ?? 1),
            $data['fup_reset_type'] ?? 'monthly',
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /**
     * Supprimer un profil PPPoE
     */
    public function deletePPPoEProfile(int $id): bool
    {
        // Vérifier s'il y a des utilisateurs avec ce profil
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pppoe_users WHERE profile_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Impossible de supprimer: des utilisateurs utilisent ce profil");
        }

        $stmt = $this->pdo->prepare("DELETE FROM pppoe_profiles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ==========================================
    // PPPoE Users CRUD
    // ==========================================

    /**
     * Obtenir tous les utilisateurs PPPoE
     */
    public function getAllPPPoEUsers(array $filters = [], int $page = 1, int $perPage = 20, ?int $adminId = null): array
    {
        // Mettre à jour les comptes expirés
        $this->updateExpiredPPPoEUsers();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "pu.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['status'])) {
            $where[] = "pu.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(pu.username LIKE ? OR pu.customer_name LIKE ? OR pu.customer_phone LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['profile_id'])) {
            $where[] = "pu.profile_id = ?";
            $params[] = $filters['profile_id'];
        }

        if (!empty($filters['zone_id'])) {
            $where[] = "pu.zone_id = ?";
            $params[] = $filters['zone_id'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Total
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM pppoe_users pu {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Données
        $stmt = $this->pdo->prepare("
            SELECT pu.*,
                   pp.name as profile_name,
                   pp.download_speed,
                   pp.upload_speed,
                   pp.data_limit as profile_data_limit,
                   pp.price as profile_price,
                   z.name as zone_name,
                   z.color as zone_color,
                   u.username as seller_username,
                   u.full_name as seller_name,
                   (SELECT COUNT(*) FROM pppoe_sessions ps WHERE ps.pppoe_user_id = pu.id AND ps.stop_time IS NULL) as active_sessions
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            LEFT JOIN zones z ON pu.zone_id = z.id
            LEFT JOIN users u ON pu.sold_by = u.id
            {$whereClause}
            ORDER BY pu.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtenir les clients PPPoE assignés à un NAS avec leur statut en ligne
     */
    public function getPPPoEClientsByNas(int $nasId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pu.*,
                   pp.name as profile_name,
                   pp.download_speed,
                   pp.upload_speed,
                   z.name as zone_name,
                   (SELECT COUNT(*) FROM pppoe_sessions ps WHERE ps.pppoe_user_id = pu.id AND ps.stop_time IS NULL) > 0 as is_online
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            LEFT JOIN zones z ON pu.zone_id = z.id
            WHERE pu.nas_id = ?
            ORDER BY is_online DESC, pu.customer_name ASC
        ");
        $stmt->execute([$nasId]);
        $results = $stmt->fetchAll();

        // Convertir is_online en booléen
        foreach ($results as &$row) {
            $row['is_online'] = (bool)$row['is_online'];
        }

        return $results;
    }

    /**
     * Mettre à jour les utilisateurs PPPoE expirés
     */
    public function updateExpiredPPPoEUsers(): int
    {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE pppoe_users
                SET status = 'expired'
                WHERE status = 'active'
                  AND valid_until IS NOT NULL
                  AND valid_until < NOW()
            ");
            $stmt->execute();
            return $stmt->rowCount();
        }
        catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Obtenir un utilisateur PPPoE par ID
     */
    public function getPPPoEUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT pu.*,
                   pp.name as profile_name,
                   pp.download_speed,
                   pp.upload_speed,
                   pp.data_limit as profile_data_limit,
                   z.name as zone_name
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            LEFT JOIN zones z ON pu.zone_id = z.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir un utilisateur PPPoE par username
     */
    public function getPPPoEUserByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_users WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un utilisateur PPPoE
     */
    public function createPPPoEUser(array $data): int
    {
        // Récupérer le profil pour calculer valid_until
        $profile = $this->getPPPoEProfileById($data['profile_id']);
        if (!$profile) {
            throw new Exception("Profil PPPoE non trouvé");
        }

        $validFrom = $data['valid_from'] ?? date('Y-m-d H:i:s');
        $validUntil = $data['valid_until'] ?? date('Y-m-d H:i:s', strtotime($validFrom . ' + ' . $profile['validity_days'] . ' days'));

        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_users (
                zone_id, profile_id, username, password,
                customer_name, customer_phone, customer_secondary_phone, customer_email,
                customer_id_type, customer_id_number, customer_address,
                latitude, longitude, location_description,
                installation_date, installation_tech, equipment_serial,
                static_ip, ip_mode, pool_id, pool_ip, status, valid_from, valid_until,
                sold_by, payment_method, sale_amount, notes, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['zone_id'] ?? null,
            $data['profile_id'],
            $data['username'],
            $data['password'],
            $data['customer_name'] ?? null,
            $data['customer_phone'] ?? null,
            $data['customer_secondary_phone'] ?? null,
            $data['customer_email'] ?? null,
            $data['customer_id_type'] ?? null,
            $data['customer_id_number'] ?? null,
            $data['customer_address'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['location_description'] ?? null,
            $data['installation_date'] ?? null,
            $data['installation_tech'] ?? null,
            $data['equipment_serial'] ?? null,
            $data['static_ip'] ?? null,
            $data['ip_mode'] ?? 'router',
            $data['pool_id'] ?? null,
            $data['pool_ip'] ?? null,
            $data['status'] ?? 'active',
            $validFrom,
            $validUntil,
            $data['sold_by'] ?? null,
            $data['payment_method'] ?? 'cash',
            $data['sale_amount'] ?? $profile['price'],
            $data['notes'] ?? null,
            $data['admin_id'] ?? null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un utilisateur PPPoE
     */
    public function updatePPPoEUser(int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        $allowedFields = [
            'zone_id', 'nas_id', 'profile_id', 'username', 'password',
            'customer_name', 'customer_phone', 'customer_secondary_phone', 'customer_email',
            'customer_id_type', 'customer_id_number', 'customer_address',
            'latitude', 'longitude', 'location_description',
            'installation_date', 'installation_tech', 'equipment_serial',
            'static_ip', 'ip_mode', 'pool_id', 'pool_ip', 'status', 'valid_from', 'valid_until', 'notes',
            'admin_id'
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE pppoe_users SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprimer un utilisateur PPPoE
     */
    public function deletePPPoEUser(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM pppoe_users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Renouveler un abonnement PPPoE
     */
    public function renewPPPoEUser(int $id, ?int $days = null): bool
    {
        $user = $this->getPPPoEUserById($id);
        if (!$user) {
            return false;
        }

        $profile = $this->getPPPoEProfileById($user['profile_id']);
        $renewDays = $days ?? $profile['validity_days'] ?? 30;

        // Si l'utilisateur est expiré, renouveler à partir de maintenant
        // Sinon, ajouter les jours à la date d'expiration actuelle
        $baseDate = ($user['status'] === 'expired' || strtotime($user['valid_until']) < time())
            ? date('Y-m-d H:i:s')
            : $user['valid_until'];

        $newValidUntil = date('Y-m-d H:i:s', strtotime($baseDate . ' + ' . $renewDays . ' days'));

        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users SET
                valid_until = ?,
                status = 'active',
                data_used = 0
            WHERE id = ?
        ");
        return $stmt->execute([$newValidUntil, $id]);
    }

    /**
     * Obtenir les sessions PPPoE d'un utilisateur
     */
    public function getPPPoEUserSessions(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ps.*, n.shortname as nas_name
            FROM pppoe_sessions ps
            LEFT JOIN nas n ON ps.nas_ip = n.nasname
            WHERE ps.pppoe_user_id = ?
            ORDER BY ps.start_time DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Réinitialiser les compteurs de trafic d'un utilisateur PPPoE
     */
    public function resetPPPoEUserTraffic(int $userId): void
    {
        // Remettre à zéro les compteurs utilisateur
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users
            SET data_used = 0, time_used = 0, fup_data_used = 0, fup_data_offset = 0
            WHERE id = ?
        ");
        $stmt->execute([$userId]);

        // Supprimer les sessions terminées
        $stmt = $this->pdo->prepare("
            DELETE FROM pppoe_sessions
            WHERE pppoe_user_id = ? AND stop_time IS NOT NULL
        ");
        $stmt->execute([$userId]);
    }

    /**
     * Statistiques de trafic journalier d'un utilisateur PPPoE
     */
    public function getPPPoEUserDailyTraffic(int $userId, int $days = 30): array
    {
        // Données journalières
        $stmt = $this->pdo->prepare("
            SELECT DATE(start_time) as day,
                   DATE_FORMAT(start_time, '%d/%m') as label,
                   COALESCE(SUM(input_octets), 0) as download,
                   COALESCE(SUM(output_octets), 0) as upload,
                   COALESCE(SUM(input_octets + output_octets), 0) as total,
                   COUNT(*) as sessions,
                   COALESCE(SUM(session_time), 0) as total_time
            FROM pppoe_sessions
            WHERE pppoe_user_id = ?
              AND start_time >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
            GROUP BY DATE(start_time), DATE_FORMAT(start_time, '%d/%m')
            ORDER BY day ASC
        ");
        $stmt->execute([$userId, $days]);
        $daily = $stmt->fetchAll();

        // Résumé sur la période
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(input_octets), 0) as total_download,
                   COALESCE(SUM(output_octets), 0) as total_upload,
                   COALESCE(SUM(input_octets + output_octets), 0) as total,
                   COALESCE(SUM(session_time), 0) as total_time,
                   COUNT(*) as sessions_count
            FROM pppoe_sessions
            WHERE pppoe_user_id = ?
              AND start_time >= DATE_SUB(CURRENT_DATE(), INTERVAL ? DAY)
        ");
        $stmt->execute([$userId, $days]);
        $summary = $stmt->fetch();

        // Données globales (depuis toujours)
        $stmt = $this->pdo->prepare("SELECT data_used, time_used FROM pppoe_users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $summary['all_time_data'] = $user['data_used'] ?? 0;
        $summary['all_time_time'] = $user['time_used'] ?? 0;

        return ['daily' => $daily, 'summary' => $summary];
    }

    /**
     * Statistiques PPPoE
     */
    public function getPPPoEStats(?int $adminId = null): array
    {
        $adminFilter = '';
        $adminFilterProfiles = '';
        $adminFilterSessions = '';
        $params = [];

        if ($adminId !== null) {
            $adminFilter = ' WHERE admin_id = ?';
            $adminFilterProfiles = ' AND admin_id = ?';
            $adminFilterSessions = ' AND pppoe_user_id IN (SELECT id FROM pppoe_users WHERE admin_id = ?)';
            $params[] = $adminId;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total_users,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_users,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_users,
                SUM(CASE WHEN status = 'disabled' THEN 1 ELSE 0 END) as disabled_users,
                (SELECT COUNT(*) FROM pppoe_sessions WHERE stop_time IS NULL{$adminFilterSessions}) as active_sessions,
                (SELECT COUNT(*) FROM pppoe_profiles WHERE is_active = 1{$adminFilterProfiles}) as active_profiles
            FROM pppoe_users
            {$adminFilter}
        ");
        $allParams = [];
        if ($adminId !== null) {
            $allParams = [$adminId, $adminId, $adminId];
        }
        $stmt->execute($allParams);
        return $stmt->fetch();
    }

    // ==========================================
    // FACTURATION PPPoE
    // ==========================================

    /**
     * Obtenir toutes les factures
     */
    public function getAllInvoices(array $filters = [], int $page = 1, int $perPage = 20, ?int $adminId = null): array
    {
        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "i.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['status'])) {
            $where[] = "i.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = "i.pppoe_user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(i.invoice_number LIKE ? OR u.username LIKE ? OR u.customer_name LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($filters['date_from'])) {
            $where[] = "i.created_at >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "i.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM pppoe_invoices i LEFT JOIN pppoe_users u ON i.pppoe_user_id = u.id {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Data
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("
            SELECT i.*, u.username, u.customer_name, u.customer_phone, u.customer_email
            FROM pppoe_invoices i
            LEFT JOIN pppoe_users u ON i.pppoe_user_id = u.id
            {$whereClause}
            ORDER BY i.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtenir une facture par ID
     */
    public function getInvoiceById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, u.username, u.customer_name, u.customer_phone, u.customer_email, u.customer_address
            FROM pppoe_invoices i
            LEFT JOIN pppoe_users u ON i.pppoe_user_id = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir les lignes d'une facture
     */
    public function getInvoiceItems(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les paiements d'une facture
     */
    public function getInvoicePayments(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_payments WHERE invoice_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }

    /**
     * Créer une facture
     */
    public function createInvoice(array $data): int
    {
        // Générer le numéro de facture
        $invoiceNumber = $this->generateInvoiceNumber();

        // Calculer la date d'échéance
        $dueDays = $this->getBillingSetting('payment_due_days') ?: 7;
        $dueDate = $data['due_date'] ?? date('Y-m-d', strtotime("+{$dueDays} days"));

        // Calculer les taxes
        $taxRate = $data['tax_rate'] ?? ($this->getBillingSetting('default_tax_rate') ?: 0);
        $amount = $data['amount'] ?? 0;
        $taxAmount = $amount * ($taxRate / 100);
        $totalAmount = $amount + $taxAmount;

        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_invoices (
                invoice_number, pppoe_user_id, period_start, period_end,
                amount, tax_rate, tax_amount, total_amount,
                status, due_date, description, notes, created_by, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoiceNumber,
            $data['pppoe_user_id'],
            $data['period_start'] ?? date('Y-m-d'),
            $data['period_end'] ?? date('Y-m-d', strtotime('+30 days')),
            $amount,
            $taxRate,
            $taxAmount,
            $totalAmount,
            $data['status'] ?? 'pending',
            $dueDate,
            $data['description'] ?? null,
            $data['notes'] ?? null,
            $data['created_by'] ?? ($_SESSION['user_id'] ?? null),
            $data['admin_id'] ?? null
        ]);

        $invoiceId = (int)$this->pdo->lastInsertId();

        // Ajouter les lignes si fournies
        if (!empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $this->addInvoiceItem($invoiceId, $item);
            }
        }

        return $invoiceId;
    }

    /**
     * Ajouter une ligne à une facture
     */
    public function addInvoiceItem(int $invoiceId, array $item): int
    {
        $quantity = $item['quantity'] ?? 1;
        $unitPrice = $item['unit_price'] ?? 0;
        $totalPrice = $quantity * $unitPrice;

        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_invoice_items (invoice_id, description, quantity, unit_price, total_price, item_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $invoiceId,
            $item['description'],
            $quantity,
            $unitPrice,
            $totalPrice,
            $item['item_type'] ?? 'subscription'
        ]);

        // Recalculer le total de la facture
        $this->recalculateInvoiceTotal($invoiceId);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Recalculer le total d'une facture
     */
    private function recalculateInvoiceTotal(int $invoiceId): void
    {
        $stmt = $this->pdo->prepare("SELECT SUM(total_price) as total FROM pppoe_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $amount = (float)($stmt->fetch()['total'] ?? 0);

        $stmt = $this->pdo->prepare("SELECT tax_rate FROM pppoe_invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $taxRate = (float)($stmt->fetch()['tax_rate'] ?? 0);

        $taxAmount = $amount * ($taxRate / 100);
        $totalAmount = $amount + $taxAmount;

        $stmt = $this->pdo->prepare("UPDATE pppoe_invoices SET amount = ?, tax_amount = ?, total_amount = ? WHERE id = ?");
        $stmt->execute([$amount, $taxAmount, $totalAmount, $invoiceId]);
    }

    /**
     * Mettre à jour une facture
     */
    public function updateInvoice(int $id, array $data): bool
    {
        $sets = [];
        $params = [];

        $allowedFields = ['status', 'due_date', 'description', 'notes', 'tax_rate'];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($sets)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE pppoe_invoices SET " . implode(', ', $sets) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($params);

        // Recalculer si le taux de taxe a changé
        if (isset($data['tax_rate'])) {
            $this->recalculateInvoiceTotal($id);
        }

        return $result;
    }

    /**
     * Supprimer une facture
     */
    public function deleteInvoice(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM pppoe_invoices WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Générer une facture pour un utilisateur
     */
    public function generateInvoiceForUser(array $user, array $options = []): int
    {
        // Récupérer le profil
        $profile = $this->getPPPoEProfileById($user['profile_id']);
        if (!$profile) {
            throw new Exception("Profil non trouvé");
        }

        $periodStart = $options['period_start'] ?? date('Y-m-d');
        $periodEnd = $options['period_end'] ?? date('Y-m-d', strtotime($periodStart . ' + ' . $profile['validity_days'] . ' days'));

        $data = [
            'pppoe_user_id' => $user['id'],
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'amount' => $profile['price'],
            'description' => "Abonnement " . $profile['name'],
            'items' => [
                [
                    'description' => "Abonnement Internet - " . $profile['name'] . " ({$profile['validity_days']} jours)",
                    'quantity' => 1,
                    'unit_price' => $profile['price'],
                    'item_type' => 'subscription'
                ]
            ]
        ];

        // Ajouter des frais d'installation si demandé
        if (!empty($options['installation_fee'])) {
            $data['items'][] = [
                'description' => "Frais d'installation",
                'quantity' => 1,
                'unit_price' => $options['installation_fee'],
                'item_type' => 'installation'
            ];
            $data['amount'] += $options['installation_fee'];
        }

        // Passer l'admin_id si fourni
        if (!empty($options['admin_id'])) {
            $data['admin_id'] = $options['admin_id'];
        }

        return $this->createInvoice($data);
    }

    /**
     * Générer un numéro de facture unique
     */
    private function generateInvoiceNumber(): string
    {
        $prefix = $this->getBillingSetting('invoice_prefix') ?: 'FAC';
        $nextNumber = (int)($this->getBillingSetting('invoice_next_number') ?: 1);

        $invoiceNumber = $prefix . '-' . date('Y') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        // Incrémenter le compteur
        $this->updateBillingSetting('invoice_next_number', $nextNumber + 1);

        return $invoiceNumber;
    }

    // ==========================================
    // Paiements
    // ==========================================

    /**
     * Obtenir tous les paiements
     */
    public function getAllPayments(array $filters = [], int $page = 1, int $perPage = 20, ?int $adminId = null): array
    {
        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "p.admin_id = ?";
            $params[] = $adminId;
        }

        if (!empty($filters['user_id'])) {
            $where[] = "p.pppoe_user_id = ?";
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['invoice_id'])) {
            $where[] = "p.invoice_id = ?";
            $params[] = $filters['invoice_id'];
        }

        if (!empty($filters['method'])) {
            $where[] = "p.payment_method = ?";
            $params[] = $filters['method'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "p.payment_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "p.payment_date <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM pppoe_payments p {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetch()['total'];

        // Data
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare("
            SELECT p.*, i.invoice_number, u.username, u.customer_name
            FROM pppoe_payments p
            LEFT JOIN pppoe_invoices i ON p.invoice_id = i.id
            LEFT JOIN pppoe_users u ON p.pppoe_user_id = u.id
            {$whereClause}
            ORDER BY p.payment_date DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtenir un paiement par ID
     */
    public function getPaymentById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT p.*, i.invoice_number, u.username, u.customer_name
            FROM pppoe_payments p
            LEFT JOIN pppoe_invoices i ON p.invoice_id = i.id
            LEFT JOIN pppoe_users u ON p.pppoe_user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer un paiement
     */
    public function createPayment(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO pppoe_payments (
                invoice_id, pppoe_user_id, amount, payment_method,
                payment_reference, mobile_money_provider, mobile_money_number,
                transaction_id, notes, received_by, payment_date, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['invoice_id'],
            $data['pppoe_user_id'],
            $data['amount'],
            $data['payment_method'],
            $data['payment_reference'] ?? null,
            $data['mobile_money_provider'] ?? null,
            $data['mobile_money_number'] ?? null,
            $data['transaction_id'] ?? null,
            $data['notes'] ?? null,
            $data['received_by'] ?? null,
            $data['payment_date'] ?? date('Y-m-d H:i:s'),
            $data['admin_id'] ?? null
        ]);

        $paymentId = (int)$this->pdo->lastInsertId();

        // Mettre à jour la facture
        $this->updateInvoicePaymentStatus($data['invoice_id']);

        return $paymentId;
    }

    /**
     * Supprimer un paiement
     */
    public function deletePayment(int $id): bool
    {
        $payment = $this->getPaymentById($id);
        if (!$payment) {
            return false;
        }

        $stmt = $this->pdo->prepare("DELETE FROM pppoe_payments WHERE id = ?");
        $result = $stmt->execute([$id]);

        // Recalculer le statut de la facture
        if ($result && $payment['invoice_id']) {
            $this->updateInvoicePaymentStatus($payment['invoice_id']);
        }

        return $result;
    }

    /**
     * Mettre à jour le statut de paiement d'une facture
     */
    private function updateInvoicePaymentStatus(int $invoiceId): void
    {
        // Calculer le total payé
        $stmt = $this->pdo->prepare("SELECT SUM(amount) as total FROM pppoe_payments WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $paidAmount = (float)($stmt->fetch()['total'] ?? 0);

        // Récupérer la facture
        $stmt = $this->pdo->prepare("SELECT total_amount, due_date FROM pppoe_invoices WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();

        if (!$invoice)
            return;

        // Déterminer le statut
        $status = 'pending';
        if ($paidAmount >= $invoice['total_amount']) {
            $status = 'paid';
        }
        elseif ($paidAmount > 0) {
            $status = 'partial';
        }
        elseif (strtotime($invoice['due_date']) < time()) {
            $status = 'overdue';
        }

        // Mettre à jour
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_invoices
            SET paid_amount = ?, paid_date = ?, status = ?, payment_method = (
                SELECT payment_method FROM pppoe_payments WHERE invoice_id = ? ORDER BY payment_date DESC LIMIT 1
            )
            WHERE id = ?
        ");
        $stmt->execute([
            $paidAmount,
            $paidAmount > 0 ? date('Y-m-d H:i:s') : null,
            $status,
            $invoiceId,
            $invoiceId
        ]);

        // Activer le client si désactivé (première facture payée)
        if ($status === 'paid') {
            $stmt = $this->pdo->prepare("
                SELECT pu.id, pu.status, pp.validity_days
                FROM pppoe_users pu
                JOIN pppoe_invoices pi ON pi.pppoe_user_id = pu.id
                LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
                WHERE pi.id = ? AND pu.status = 'disabled'
            ");
            $stmt->execute([$invoiceId]);
            $user = $stmt->fetch();
            if ($user) {
                $validityDays = $user['validity_days'] ?? 30;
                $newValidUntil = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));
                $this->pdo->prepare("
                    UPDATE pppoe_users SET status = 'active', valid_from = NOW(), valid_until = ? WHERE id = ?
                ")->execute([$newValidUntil, $user['id']]);
            }

            // Appliquer le changement de plan si c'est une facture de type plan_change
            $stmt = $this->pdo->prepare("SELECT metadata, pppoe_user_id FROM pppoe_invoices WHERE id = ?");
            $stmt->execute([$invoiceId]);
            $inv = $stmt->fetch();
            if ($inv && $inv['metadata']) {
                $meta = json_decode($inv['metadata'], true);
                if (($meta['type'] ?? '') === 'plan_change' && !empty($meta['new_profile_id'])) {
                    $newProfile = $this->getPPPoEProfileById((int)$meta['new_profile_id']);
                    if ($newProfile) {
                        $validityDays = $newProfile['validity_days'] ?? 30;
                        $newValidUntil = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));
                        $this->pdo->prepare("
                            UPDATE pppoe_users SET profile_id = ?, valid_from = NOW(), valid_until = ? WHERE id = ?
                        ")->execute([$meta['new_profile_id'], $newValidUntil, $inv['pppoe_user_id']]);
                    }
                }
            }
        }
    }

    // ==========================================
    // Statistiques facturation
    // ==========================================

    /**
     * Statistiques de facturation
     */
    public function getBillingStats(?int $adminId = null): array
    {
        $adminFilter = '';
        $adminFilterPayments = '';
        $params = [];
        $paramsPayments = [];

        if ($adminId !== null) {
            $adminFilter = ' AND admin_id = ?';
            $adminFilterPayments = ' AND admin_id = ?';
            $params[] = $adminId;
            $paramsPayments[] = $adminId;
        }

        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_invoices,
                SUM(CASE WHEN status = 'partial' THEN 1 ELSE 0 END) as partial_invoices,
                SUM(total_amount) as total_billed,
                SUM(paid_amount) as total_collected,
                SUM(total_amount - paid_amount) as total_outstanding
            FROM pppoe_invoices
            WHERE status != 'cancelled'{$adminFilter}
        ");
        $stmt->execute($params);
        $stats = $stmt->fetch();

        // Revenus PPPoE du mois
        $stmt = $this->pdo->prepare("
            SELECT SUM(amount) as revenue
            FROM pppoe_payments
            WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())
            AND YEAR(payment_date) = YEAR(CURRENT_DATE()){$adminFilterPayments}
        ");
        $stmt->execute($paramsPayments);
        $pppoeMonthly = (float)($stmt->fetch()['revenue'] ?? 0);

        // Revenus PPPoE du jour
        $stmt = $this->pdo->prepare("
            SELECT SUM(amount) as revenue
            FROM pppoe_payments
            WHERE DATE(payment_date) = CURRENT_DATE(){$adminFilterPayments}
        ");
        $stmt->execute($paramsPayments);
        $pppoeDaily = (float)($stmt->fetch()['revenue'] ?? 0);

        // Revenus Hotspot (payment_transactions) du mois
        $hotspotFilter = $adminId !== null ? ' AND admin_id = ?' : '';
        $hotspotParams = $adminId !== null ? [$adminId] : [];

        $stmt = $this->pdo->prepare("
            SELECT SUM(amount) as revenue
            FROM payment_transactions
            WHERE status = 'completed'
            AND MONTH(paid_at) = MONTH(CURRENT_DATE())
            AND YEAR(paid_at) = YEAR(CURRENT_DATE()){$hotspotFilter}
        ");
        $stmt->execute($hotspotParams);
        $hotspotMonthly = (float)($stmt->fetch()['revenue'] ?? 0);

        // Revenus Hotspot du jour
        $stmt = $this->pdo->prepare("
            SELECT SUM(amount) as revenue
            FROM payment_transactions
            WHERE status = 'completed'
            AND DATE(paid_at) = CURRENT_DATE(){$hotspotFilter}
        ");
        $stmt->execute($hotspotParams);
        $hotspotDaily = (float)($stmt->fetch()['revenue'] ?? 0);

        $stats['daily_revenue'] = $pppoeDaily + $hotspotDaily;
        $stats['monthly_revenue'] = $pppoeMonthly + $hotspotMonthly;
        $stats['pppoe_daily_revenue'] = $pppoeDaily;
        $stats['pppoe_monthly_revenue'] = $pppoeMonthly;
        $stats['hotspot_daily_revenue'] = $hotspotDaily;
        $stats['hotspot_monthly_revenue'] = $hotspotMonthly;

        return $stats;
    }

    /**
     * Résumé facturation d'un client
     */
    public function getUserBillingSummary(int $userId): array
    {
        // Toutes les factures du client
        $stmt = $this->pdo->prepare("
            SELECT i.*,
                   DATE_FORMAT(i.created_at, '%Y-%m-%d') as invoice_date
            FROM pppoe_invoices i
            WHERE i.pppoe_user_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$userId]);
        $invoices = $stmt->fetchAll();

        // Calculer les totaux
        $totalUnpaid = 0;
        $totalPaid = 0;

        foreach ($invoices as $invoice) {
            if ($invoice['status'] === 'paid') {
                $totalPaid += (float)$invoice['total_amount'];
            }
            else if ($invoice['status'] !== 'cancelled') {
                $totalUnpaid += (float)$invoice['total_amount'] - (float)$invoice['paid_amount'];
            }
        }

        // Derniers paiements
        $stmt = $this->pdo->prepare("
            SELECT p.*, i.invoice_number
            FROM pppoe_payments p
            LEFT JOIN pppoe_invoices i ON p.invoice_id = i.id
            WHERE i.pppoe_user_id = ?
            ORDER BY p.payment_date DESC
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $payments = $stmt->fetchAll();

        return [
            'invoices' => $invoices,
            'total_unpaid' => $totalUnpaid,
            'total_paid' => $totalPaid,
            'recent_payments' => $payments
        ];
    }

    // ==========================================
    // Paramètres facturation
    // ==========================================

    /**
     * Obtenir tous les paramètres de facturation
     */
    public function getBillingSettings(): array
    {
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM billing_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    /**
     * Obtenir un paramètre de facturation
     */
    public function getBillingSetting(string $key): ?string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM billing_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : null;
    }

    /**
     * Mettre à jour un paramètre de facturation
     */
    public function updateBillingSetting(string $key, string $value): bool
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO billing_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = ?
        ");
        return $stmt->execute([$key, $value, $value]);
    }

    // ==========================================
    // BANDWIDTH MANAGEMENT
    // ==========================================

    /**
     * Initialiser les tables de bandwidth si nécessaires
     */
    public function initBandwidthTables(): void
    {
        // Table des politiques
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bandwidth_policies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                download_rate BIGINT NOT NULL DEFAULT 1048576,
                upload_rate BIGINT NOT NULL DEFAULT 524288,
                burst_download_rate BIGINT DEFAULT NULL,
                burst_upload_rate BIGINT DEFAULT NULL,
                burst_threshold_download BIGINT DEFAULT NULL,
                burst_threshold_upload BIGINT DEFAULT NULL,
                burst_time INT DEFAULT NULL,
                priority TINYINT DEFAULT 8,
                session_timeout INT DEFAULT NULL,
                idle_timeout INT DEFAULT NULL,
                color VARCHAR(7) DEFAULT '#3B82F6',
                admin_id INT DEFAULT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_name_admin (name, admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Table des planifications
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bandwidth_schedules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                default_policy_id INT NOT NULL,
                scheduled_policy_id INT NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                active_days TINYINT DEFAULT 127,
                apply_to ENUM('all', 'zone', 'profile', 'user') DEFAULT 'all',
                target_id INT DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Table des attributs RADIUS
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS bandwidth_radius_attributes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                policy_id INT NOT NULL,
                attribute_name VARCHAR(100) NOT NULL,
                attribute_value VARCHAR(255) NOT NULL,
                attribute_op CHAR(2) DEFAULT ':=',
                vendor VARCHAR(50) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_policy (policy_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Add admin_id column if missing (for existing installations)
        try {
            $this->pdo->exec("ALTER TABLE bandwidth_policies ADD COLUMN admin_id INT DEFAULT NULL AFTER color");
        }
        catch (PDOException $e) {
        }
        try {
            $this->pdo->exec("ALTER TABLE bandwidth_schedules ADD COLUMN admin_id INT DEFAULT NULL AFTER target_id");
        }
        catch (PDOException $e) {
        }
    }

    /**
     * Obtenir toutes les politiques de bande passante
     */
    public function getBandwidthPolicies(?int $adminId = null): array
    {
        $this->initBandwidthTables();

        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT * FROM bandwidth_policies
                WHERE admin_id = ?
                ORDER BY priority ASC, name ASC
            ");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("
                SELECT * FROM bandwidth_policies
                ORDER BY priority ASC, name ASC
            ");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir une politique par ID
     */
    public function getBandwidthPolicyById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM bandwidth_policies WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer une politique de bande passante
     */
    public function createBandwidthPolicy(array $data): int
    {
        $this->initBandwidthTables();

        $stmt = $this->pdo->prepare("
            INSERT INTO bandwidth_policies (
                name, description, download_rate, upload_rate,
                burst_download_rate, burst_upload_rate, burst_threshold_download, burst_threshold_upload,
                burst_time, priority, session_timeout, idle_timeout, color, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['download_rate'] ?? 1048576,
            $data['upload_rate'] ?? 524288,
            $data['burst_download_rate'] ?? null,
            $data['burst_upload_rate'] ?? null,
            $data['burst_threshold_download'] ?? null,
            $data['burst_threshold_upload'] ?? null,
            $data['burst_time'] ?? null,
            $data['priority'] ?? 8,
            $data['session_timeout'] ?? null,
            $data['idle_timeout'] ?? null,
            $data['color'] ?? '#3B82F6',
            $data['admin_id'] ?? null
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour une politique de bande passante
     */
    public function updateBandwidthPolicy(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE bandwidth_policies SET
                name = ?,
                description = ?,
                download_rate = ?,
                upload_rate = ?,
                burst_download_rate = ?,
                burst_upload_rate = ?,
                burst_threshold_download = ?,
                burst_threshold_upload = ?,
                burst_time = ?,
                priority = ?,
                session_timeout = ?,
                idle_timeout = ?,
                color = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['download_rate'] ?? 1048576,
            $data['upload_rate'] ?? 524288,
            $data['burst_download_rate'] ?? null,
            $data['burst_upload_rate'] ?? null,
            $data['burst_threshold_download'] ?? null,
            $data['burst_threshold_upload'] ?? null,
            $data['burst_time'] ?? null,
            $data['priority'] ?? 8,
            $data['session_timeout'] ?? null,
            $data['idle_timeout'] ?? null,
            $data['color'] ?? '#3B82F6',
            $id
        ]);
    }

    /**
     * Supprimer une politique de bande passante
     */
    public function deleteBandwidthPolicy(int $id): bool
    {
        // Supprimer les attributs associés
        $this->deleteBandwidthPolicyAttributes($id);

        $stmt = $this->pdo->prepare("DELETE FROM bandwidth_policies WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Vérifier si une politique est utilisée dans des planifications
     */
    public function isPolicyUsedInSchedules(int $policyId): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count FROM bandwidth_schedules
            WHERE default_policy_id = ? OR scheduled_policy_id = ?
        ");
        $stmt->execute([$policyId, $policyId]);
        return (int)$stmt->fetch()['count'] > 0;
    }

    /**
     * Obtenir les attributs RADIUS d'une politique
     */
    public function getBandwidthPolicyAttributes(int $policyId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM bandwidth_radius_attributes WHERE policy_id = ?
        ");
        $stmt->execute([$policyId]);
        return $stmt->fetchAll();
    }

    /**
     * Ajouter un attribut RADIUS à une politique
     */
    public function addBandwidthPolicyAttribute(int $policyId, string $name, string $value, string $op = ':=', ?string $vendor = null): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO bandwidth_radius_attributes (policy_id, attribute_name, attribute_value, attribute_op, vendor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$policyId, $name, $value, $op, $vendor]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Supprimer les attributs RADIUS d'une politique
     */
    public function deleteBandwidthPolicyAttributes(int $policyId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM bandwidth_radius_attributes WHERE policy_id = ?");
        return $stmt->execute([$policyId]);
    }

    // ==========================================
    // BANDWIDTH SCHEDULES
    // ==========================================

    /**
     * Obtenir toutes les planifications
     */
    public function getBandwidthSchedules(?int $adminId = null): array
    {
        $this->initBandwidthTables();

        if ($adminId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT bs.*,
                       dp.name as default_policy_name,
                       sp.name as scheduled_policy_name
                FROM bandwidth_schedules bs
                LEFT JOIN bandwidth_policies dp ON bs.default_policy_id = dp.id
                LEFT JOIN bandwidth_policies sp ON bs.scheduled_policy_id = sp.id
                WHERE bs.admin_id = ?
                ORDER BY bs.name ASC
            ");
            $stmt->execute([$adminId]);
        }
        else {
            $stmt = $this->pdo->query("
                SELECT bs.*,
                       dp.name as default_policy_name,
                       sp.name as scheduled_policy_name
                FROM bandwidth_schedules bs
                LEFT JOIN bandwidth_policies dp ON bs.default_policy_id = dp.id
                LEFT JOIN bandwidth_policies sp ON bs.scheduled_policy_id = sp.id
                ORDER BY bs.name ASC
            ");
        }
        return $stmt->fetchAll();
    }

    /**
     * Obtenir une planification par ID
     */
    public function getBandwidthScheduleById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT bs.*,
                   dp.name as default_policy_name,
                   sp.name as scheduled_policy_name
            FROM bandwidth_schedules bs
            LEFT JOIN bandwidth_policies dp ON bs.default_policy_id = dp.id
            LEFT JOIN bandwidth_policies sp ON bs.scheduled_policy_id = sp.id
            WHERE bs.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Créer une planification
     */
    public function createBandwidthSchedule(array $data): int
    {
        $this->initBandwidthTables();

        $stmt = $this->pdo->prepare("
            INSERT INTO bandwidth_schedules (
                name, description, default_policy_id, scheduled_policy_id,
                start_time, end_time, active_days, apply_to, target_id, admin_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['default_policy_id'],
            $data['scheduled_policy_id'],
            $data['start_time'],
            $data['end_time'],
            $data['active_days'] ?? 127,
            $data['apply_to'] ?? 'all',
            $data['target_id'] ?? null,
            $data['admin_id'] ?? null
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour une planification
     */
    public function updateBandwidthSchedule(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE bandwidth_schedules SET
                name = ?,
                description = ?,
                default_policy_id = ?,
                scheduled_policy_id = ?,
                start_time = ?,
                end_time = ?,
                active_days = ?,
                apply_to = ?,
                target_id = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['default_policy_id'],
            $data['scheduled_policy_id'],
            $data['start_time'],
            $data['end_time'],
            $data['active_days'] ?? 127,
            $data['apply_to'] ?? 'all',
            $data['target_id'] ?? null,
            $id
        ]);
    }

    /**
     * Supprimer une planification
     */
    public function deleteBandwidthSchedule(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM bandwidth_schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Activer/désactiver une planification
     */
    public function toggleBandwidthSchedule(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE bandwidth_schedules SET is_active = NOT is_active WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    /**
     * Obtenir la politique active pour un utilisateur à un moment donné
     */
    public function getActiveBandwidthPolicy(?int $zoneId = null, ?int $profileId = null, ?int $userId = null): ?array
    {
        $now = date('H:i:s');
        $dayOfWeek = date('N') - 1; // 0=Lundi ... 6=Dimanche
        $dayBit = 1 << $dayOfWeek;

        // Chercher les planifications actives
        $stmt = $this->pdo->prepare("
            SELECT bs.*, sp.* as policy
            FROM bandwidth_schedules bs
            JOIN bandwidth_policies sp ON bs.scheduled_policy_id = sp.id
            WHERE bs.is_active = 1
              AND (bs.active_days & ?) > 0
              AND (
                  (bs.start_time <= bs.end_time AND ? BETWEEN bs.start_time AND bs.end_time)
                  OR
                  (bs.start_time > bs.end_time AND (? >= bs.start_time OR ? <= bs.end_time))
              )
              AND (
                  bs.apply_to = 'all'
                  OR (bs.apply_to = 'zone' AND bs.target_id = ?)
                  OR (bs.apply_to = 'profile' AND bs.target_id = ?)
                  OR (bs.apply_to = 'user' AND bs.target_id = ?)
              )
            ORDER BY
                CASE bs.apply_to
                    WHEN 'user' THEN 1
                    WHEN 'profile' THEN 2
                    WHEN 'zone' THEN 3
                    ELSE 4
                END
            LIMIT 1
        ");

        $stmt->execute([$dayBit, $now, $now, $now, $zoneId, $profileId, $userId]);
        $schedule = $stmt->fetch();

        if ($schedule) {
            return $this->getBandwidthPolicyById($schedule['scheduled_policy_id']);
        }

        return null;
    }

    // ==========================================
    // FUP (FAIR USAGE POLICY) MANAGEMENT
    // ==========================================

    /**
     * S'assurer que les colonnes FUP existent dans les tables
     */
    public function ensureFupColumnsExist(): void
    {
        try {
            // Vérifier colonnes sur pppoe_profiles
            $stmt = $this->pdo->query("SHOW COLUMNS FROM pppoe_profiles LIKE 'fup_enabled'");
            if (!$stmt->fetch()) {
                $this->pdo->exec("
                    ALTER TABLE pppoe_profiles
                    ADD COLUMN fup_enabled TINYINT(1) DEFAULT 0 AFTER burst_time,
                    ADD COLUMN fup_quota BIGINT DEFAULT 0 AFTER fup_enabled,
                    ADD COLUMN fup_download_speed BIGINT DEFAULT 0 AFTER fup_quota,
                    ADD COLUMN fup_upload_speed BIGINT DEFAULT 0 AFTER fup_download_speed,
                    ADD COLUMN fup_reset_day INT DEFAULT 1 AFTER fup_upload_speed,
                    ADD COLUMN fup_reset_type ENUM('monthly', 'billing_cycle', 'manual') DEFAULT 'monthly' AFTER fup_reset_day
                ");
            }

            // Vérifier colonnes sur pppoe_users
            $stmt = $this->pdo->query("SHOW COLUMNS FROM pppoe_users LIKE 'fup_data_used'");
            if (!$stmt->fetch()) {
                $this->pdo->exec("
                    ALTER TABLE pppoe_users
                    ADD COLUMN fup_data_used BIGINT DEFAULT 0 AFTER data_used,
                    ADD COLUMN fup_triggered TINYINT(1) DEFAULT 0 AFTER fup_data_used,
                    ADD COLUMN fup_triggered_at TIMESTAMP NULL AFTER fup_triggered,
                    ADD COLUMN fup_last_reset TIMESTAMP NULL AFTER fup_triggered_at,
                    ADD COLUMN fup_override TINYINT(1) DEFAULT 0 AFTER fup_last_reset
                ");
            }

            // Créer table des logs FUP si n'existe pas
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS pppoe_fup_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    pppoe_user_id INT NOT NULL,
                    action ENUM('triggered', 'reset', 'override_enabled', 'override_disabled', 'manual_reset') NOT NULL,
                    data_used BIGINT DEFAULT 0,
                    quota BIGINT DEFAULT 0,
                    old_speed_down BIGINT DEFAULT NULL,
                    old_speed_up BIGINT DEFAULT NULL,
                    new_speed_down BIGINT DEFAULT NULL,
                    new_speed_up BIGINT DEFAULT NULL,
                    triggered_by VARCHAR(50) DEFAULT 'system',
                    notes TEXT DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_fup_logs_user (pppoe_user_id),
                    INDEX idx_fup_logs_action (action)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        catch (PDOException $e) {
        // Ignorer les erreurs si colonnes existent déjà
        }
    }

    /**
     * Obtenir le statut FUP d'un utilisateur
     */
    public function getPPPoEUserFupStatus(int $userId): ?array
    {
        $this->ensureFupColumnsExist();

        $stmt = $this->pdo->prepare("
            SELECT
                pu.id,
                pu.username,
                pu.fup_data_used,
                pu.fup_triggered,
                pu.fup_triggered_at,
                pu.fup_last_reset,
                pu.fup_override,
                pp.fup_enabled,
                pp.fup_quota,
                pp.fup_download_speed,
                pp.fup_upload_speed,
                pp.fup_reset_day,
                pp.fup_reset_type,
                pp.download_speed as normal_download_speed,
                pp.upload_speed as normal_upload_speed,
                CASE
                    WHEN pp.fup_quota > 0 THEN ROUND((pu.fup_data_used / pp.fup_quota) * 100, 2)
                    ELSE 0
                END as usage_percent,
                CASE
                    WHEN pp.fup_quota > 0 THEN pp.fup_quota - pu.fup_data_used
                    ELSE 0
                END as remaining
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Vérifier et déclencher FUP si nécessaire
     */
    public function checkAndTriggerFup(int $userId): array
    {
        $this->ensureFupColumnsExist();

        $status = $this->getPPPoEUserFupStatus($userId);
        if (!$status) {
            return ['triggered' => false, 'reason' => 'User not found'];
        }

        // FUP désactivé sur le profil
        if (!$status['fup_enabled']) {
            return ['triggered' => false, 'reason' => 'FUP disabled'];
        }

        // Override actif
        if ($status['fup_override']) {
            return ['triggered' => false, 'reason' => 'FUP override active'];
        }

        // Déjà déclenché
        if ($status['fup_triggered']) {
            return ['triggered' => true, 'reason' => 'Already triggered', 'already' => true];
        }

        // Vérifier si le quota est atteint
        if ($status['fup_quota'] > 0 && $status['fup_data_used'] >= $status['fup_quota']) {
            // Déclencher le FUP
            $this->triggerFup($userId, $status);
            return [
                'triggered' => true,
                'reason' => 'Quota exceeded',
                'data_used' => $status['fup_data_used'],
                'quota' => $status['fup_quota'],
                'new_download' => $status['fup_download_speed'],
                'new_upload' => $status['fup_upload_speed']
            ];
        }

        return ['triggered' => false, 'reason' => 'Under quota'];
    }

    /**
     * Déclencher le FUP pour un utilisateur
     */
    public function triggerFup(int $userId, ?array $status = null): bool
    {
        $this->ensureFupColumnsExist();

        if (!$status) {
            $status = $this->getPPPoEUserFupStatus($userId);
        }

        if (!$status)
            return false;

        // Mettre à jour l'utilisateur
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users
            SET fup_triggered = 1, fup_triggered_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$userId]);

        // Logger l'action
        $this->logFupAction($userId, 'triggered', [
            'data_used' => $status['fup_data_used'],
            'quota' => $status['fup_quota'],
            'old_speed_down' => $status['normal_download_speed'],
            'old_speed_up' => $status['normal_upload_speed'],
            'new_speed_down' => $status['fup_download_speed'],
            'new_speed_up' => $status['fup_upload_speed']
        ]);

        // Appliquer le nouveau débit en temps réel sur MikroTik
        $this->applyFupSpeedToMikrotik($userId, $status);

        return true;
    }

    /**
     * Appliquer le débit FUP sur MikroTik en temps réel
     */
    private function applyFupSpeedToMikrotik(int $userId, array $status): bool
    {
        require_once __DIR__ . '/../MikroTik/CommandSender.php';
        $commandSender = new \MikroTikCommandSender();

        // Récupérer le router_id du NAS
        $routerId = $this->getRouterIdForUser($userId);
        if (!$routerId) {
            return false;
        }

        // Utiliser la nouvelle méthode qui modifie la queue en temps réel
        return $commandSender->setActiveQueueSpeed(
            $routerId,
            $status['username'],
            $status['fup_download_speed'],
            $status['fup_upload_speed']
        );
    }

    /**
     * Obtenir le router_id pour un utilisateur PPPoE
     */
    private function getRouterIdForUser(int $userId): ?string
    {
        // 1. Essayer via nas_id de l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT n.router_id
            FROM pppoe_users pu
            LEFT JOIN nas n ON pu.nas_id = n.id
            WHERE pu.id = ? AND n.router_id IS NOT NULL AND n.router_id != ''
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        if ($result && $result['router_id']) {
            return $result['router_id'];
        }

        // 2. Essayer via zone_id de l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT n.router_id
            FROM pppoe_users pu
            JOIN nas n ON pu.zone_id = n.zone_id
            WHERE pu.id = ? AND n.router_id IS NOT NULL AND n.router_id != ''
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        if ($result && $result['router_id']) {
            return $result['router_id'];
        }

        // 3. Essayer via la session active
        $stmt = $this->pdo->prepare("
            SELECT n.router_id
            FROM pppoe_sessions ps
            JOIN nas n ON ps.nas_ip = n.nasname
            WHERE ps.pppoe_user_id = ? AND ps.stop_time IS NULL AND n.router_id IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        if ($result && $result['router_id']) {
            return $result['router_id'];
        }

        return null;
    }

    /**
     * Ajouter une commande à la file d'attente MikroTik
     */
    public function queueMikrotikCommand(int $userId, string $type, string $action, string $target, ?string $routerId = null): bool
    {
        // Récupérer le router_id du NAS associé à l'utilisateur si non spécifié
        if (!$routerId) {
            $stmt = $this->pdo->prepare("
                SELECT n.router_id
                FROM pppoe_users pu
                JOIN nas n ON pu.nas_id = n.id
                WHERE pu.id = ?
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            $routerId = $result['router_id'] ?? 'default';
        }

        // Créer le dossier du routeur
        $commandsDir = dirname(__DIR__, 2) . '/web/mikrotik-commands/' . $routerId;
        if (!is_dir($commandsDir)) {
            mkdir($commandsDir, 0777, true);
        }

        // Générer la commande MikroTik
        $command = '';
        switch ($type) {
            case 'ppp':
                if ($action === 'disconnect') {
                    $command = '/ppp active remove [find name="' . $target . '"]';
                }
                break;
            case 'hotspot':
                if ($action === 'disconnect') {
                    $command = '/ip hotspot active remove [find user="' . $target . '"]';
                }
                break;
        }

        if (empty($command)) {
            return false;
        }

        // Créer le fichier de commande avec timestamp unique
        $filename = time() . '_' . uniqid() . '.rsc';
        $filepath = $commandsDir . '/' . $filename;

        return file_put_contents($filepath, $command) !== false;
    }

    /**
     * Obtenir la liste des utilisateurs à déconnecter
     */
    public function getPendingDisconnects(): array
    {
        $stmt = $this->pdo->query("
            SELECT username
            FROM pppoe_users
            WHERE pending_disconnect = 1
        ");
        return $stmt->fetchAll();
    }

    /**
     * Confirmer qu'un utilisateur a été déconnecté
     */
    public function confirmDisconnect(string $username): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users
            SET pending_disconnect = 0
            WHERE username = ? AND pending_disconnect = 1
        ");
        $stmt->execute([$username]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Déconnecter un utilisateur PPPoE (envoyer Disconnect-Request au NAS)
     */
    public function disconnectPPPoEUser(int $userId): bool
    {
        // Récupérer la session active de l'utilisateur
        $stmt = $this->pdo->prepare("
            SELECT ps.acct_session_id, ps.nas_ip, pu.username
            FROM pppoe_sessions ps
            JOIN pppoe_users pu ON ps.pppoe_user_id = pu.id
            WHERE ps.pppoe_user_id = ? AND ps.stop_time IS NULL
            ORDER BY ps.start_time DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $session = $stmt->fetch();

        if (!$session) {
            return false; // Pas de session active
        }

        // Récupérer le secret du NAS
        $secret = $this->getNasSecret($session['nas_ip']);
        if (!$secret) {
            // Essayer avec le secret par défaut
            $secret = 'testing123';
        }

        // Port CoA/Disconnect standard MikroTik
        $coaPort = 3799;

        // Envoyer le Disconnect-Request
        return $this->sendDisconnectRequest(
            $session['nas_ip'],
            $coaPort,
            $secret,
            $session['acct_session_id'],
            $session['username']
        );
    }

    /**
     * Envoyer un paquet Disconnect-Request à un NAS
     */
    private function sendDisconnectRequest(string $nasIp, int $nasPort, string $secret, string $sessionId, string $username): bool
    {
        require_once __DIR__ . '/RadiusPacket.php';

        $attributes = [
            \RadiusPacket::ATTR_USER_NAME => $username,
            \RadiusPacket::ATTR_ACCT_SESSION_ID => $sessionId,
        ];

        $packet = \RadiusPacket::createRequest(\RadiusPacket::DISCONNECT_REQUEST, $secret, $attributes);

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
            return $code === \RadiusPacket::DISCONNECT_ACK;
        }

        return false;
    }

    /**
     * Réinitialiser le FUP d'un utilisateur
     */
    public function resetFup(int $userId, string $triggeredBy = 'system'): bool
    {
        $this->ensureFupColumnsExist();

        $status = $this->getPPPoEUserFupStatus($userId);

        // Calculer le vrai total depuis les sessions (pas data_used qui peut être en retard)
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(input_octets + output_octets), 0) as total_data
            FROM pppoe_sessions WHERE pppoe_user_id = ?
        ");
        $stmt->execute([$userId]);
        $realTotal = (int)$stmt->fetchColumn();

        // Mettre à jour: data_used = total réel, fup_data_offset = total réel, fup_data_used = 0
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users
            SET data_used = ?,
                fup_data_used = 0,
                fup_data_offset = ?,
                fup_triggered = 0,
                fup_triggered_at = NULL,
                fup_last_reset = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$realTotal, $realTotal, $userId]);

        if ($result && $status) {
            $this->logFupAction($userId, $triggeredBy === 'system' ? 'reset' : 'manual_reset', [
                'data_used' => $status['fup_data_used'],
                'quota' => $status['fup_quota'],
                'triggered_by' => $triggeredBy
            ]);
        }

        return $result;
    }

    /**
     * Appliquer le débit normal sur MikroTik (restauration FUP)
     */
    private function applyNormalSpeedToMikrotik(int $userId, array $status): bool
    {
        require_once __DIR__ . '/../MikroTik/CommandSender.php';
        $commandSender = new \MikroTikCommandSender();

        $routerId = $this->getRouterIdForUser($userId);
        if (!$routerId) {
            return false;
        }

        return $commandSender->restoreUserNormalSpeed(
            $routerId,
            $status['username']
        );
    }

    /**
     * Activer/désactiver l'override FUP
     */
    public function toggleFupOverride(int $userId): bool
    {
        $this->ensureFupColumnsExist();

        $status = $this->getPPPoEUserFupStatus($userId);
        $newValue = $status['fup_override'] ? 0 : 1;

        $stmt = $this->pdo->prepare("UPDATE pppoe_users SET fup_override = ? WHERE id = ?");
        $result = $stmt->execute([$newValue, $userId]);

        if ($result) {
            $this->logFupAction($userId, $newValue ? 'override_enabled' : 'override_disabled', [
                'data_used' => $status['fup_data_used'],
                'quota' => $status['fup_quota']
            ]);
        }

        return $result;
    }

    /**
     * Mettre à jour les données FUP utilisées
     */
    public function updateFupDataUsed(int $userId, int $bytesUsed): bool
    {
        $this->ensureFupColumnsExist();

        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users
            SET fup_data_used = fup_data_used + ?
            WHERE id = ?
        ");
        return $stmt->execute([$bytesUsed, $userId]);
    }

    /**
     * Obtenir la vitesse effective d'un utilisateur (tenant compte du FUP)
     */
    public function getEffectiveSpeed(int $userId): array
    {
        $status = $this->getPPPoEUserFupStatus($userId);

        if (!$status) {
            return ['download' => 0, 'upload' => 0, 'fup_active' => false];
        }

        // FUP déclenché et pas d'override
        if ($status['fup_triggered'] && !$status['fup_override'] && $status['fup_enabled']) {
            return [
                'download' => $status['fup_download_speed'],
                'upload' => $status['fup_upload_speed'],
                'fup_active' => true
            ];
        }

        return [
            'download' => $status['normal_download_speed'],
            'upload' => $status['normal_upload_speed'],
            'fup_active' => false
        ];
    }

    /**
     * Obtenir les utilisateurs avec FUP déclenché
     */
    public function getFupTriggeredUsers(?int $adminId = null): array
    {
        $this->ensureFupColumnsExist();

        $sql = "
            SELECT pu.*, pp.name as profile_name, pp.fup_quota,
                   pp.fup_download_speed, pp.fup_upload_speed,
                   pp.download_speed as normal_download_speed,
                   pp.upload_speed as normal_upload_speed
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.fup_triggered = 1";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND pu.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY pu.fup_triggered_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les utilisateurs proches du quota FUP
     */
    public function getFupWarningUsers(int $percentThreshold = 80, ?int $adminId = null): array
    {
        $this->ensureFupColumnsExist();

        $sql = "
            SELECT pu.*, pp.name as profile_name, pp.fup_quota,
                   pp.fup_download_speed, pp.fup_upload_speed,
                   ROUND((pu.fup_data_used / pp.fup_quota) * 100, 2) as usage_percent
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pp.fup_enabled = 1
              AND pp.fup_quota > 0
              AND pu.fup_triggered = 0
              AND (pu.fup_data_used / pp.fup_quota) * 100 >= ?";
        $params = [$percentThreshold];
        if ($adminId !== null) {
            $sql .= " AND pu.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY usage_percent DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Reset FUP mensuel pour tous les utilisateurs
     */
    public function resetMonthlyFup(): int
    {
        $this->ensureFupColumnsExist();

        $today = (int)date('d');

        // Mettre à jour fup_data_offset = data_used actuel pour que le calcul
        // fup_data_used = total_sessions - fup_data_offset reparte de 0
        $stmt = $this->pdo->prepare("
            UPDATE pppoe_users pu
            JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            SET pu.fup_data_used = 0,
                pu.fup_data_offset = pu.data_used,
                pu.fup_triggered = 0,
                pu.fup_triggered_at = NULL,
                pu.fup_last_reset = NOW()
            WHERE pp.fup_enabled = 1
              AND pp.fup_reset_type = 'monthly'
              AND pp.fup_reset_day = ?
              AND (pu.fup_last_reset IS NULL OR DATE(pu.fup_last_reset) < CURDATE())
        ");
        $stmt->execute([$today]);

        return $stmt->rowCount();
    }

    /**
     * Logger une action FUP
     */
    private function logFupAction(int $userId, string $action, array $data = []): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO pppoe_fup_logs (
                    pppoe_user_id, action, data_used, quota,
                    old_speed_down, old_speed_up, new_speed_down, new_speed_up,
                    triggered_by, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $data['data_used'] ?? 0,
                $data['quota'] ?? 0,
                $data['old_speed_down'] ?? null,
                $data['old_speed_up'] ?? null,
                $data['new_speed_down'] ?? null,
                $data['new_speed_up'] ?? null,
                $data['triggered_by'] ?? 'system',
                $data['notes'] ?? null
            ]);
        }
        catch (PDOException $e) {
        // Ignorer les erreurs de log
        }
    }

    /**
     * Obtenir l'historique FUP d'un utilisateur
     */
    public function getFupLogs(int $userId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM pppoe_fup_logs
            WHERE pppoe_user_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    // ==========================================
    // MONITORING - Statistiques de bande passante
    // ==========================================

    /**
     * Obtenir les statistiques globales de monitoring
     * Combine pppoe_sessions et sessions (hotspot)
     */
    public function getMonitoringStats(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $adminParams = $adminId !== null ? [$adminId] : [];

        // Sessions PPPoE actives
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) FROM pppoe_sessions WHERE stop_time IS NULL" . $adminFilter . "
        ");
        $stmt->execute($adminParams);
        $pppoeSessions = (int)$stmt->fetchColumn();

        // Sessions Hotspot actives (table sessions)
        $hotspotSessions = 0;
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM sessions WHERE stop_time IS NULL" . $adminFilter . "
            ");
            $stmt->execute($adminParams);
            $hotspotSessions = (int)$stmt->fetchColumn();
        }
        catch (PDOException $e) {
        }

        $activeSessions = $pppoeSessions + $hotspotSessions;

        // Trafic PPPoE
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(input_octets), 0) as total_upload, COALESCE(SUM(output_octets), 0) as total_download
            FROM pppoe_sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . "
        ");
        $stmt->execute($adminParams);
        $pppoeTraffic = $stmt->fetch();

        // Trafic Hotspot
        $hotspotUpload = 0;
        $hotspotDownload = 0;
        try {
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(SUM(input_octets), 0) as total_upload, COALESCE(SUM(output_octets), 0) as total_download
                FROM sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . "
            ");
            $stmt->execute($adminParams);
            $hotspotTraffic = $stmt->fetch();
            $hotspotUpload = (float)($hotspotTraffic['total_upload'] ?? 0);
            $hotspotDownload = (float)($hotspotTraffic['total_download'] ?? 0);
        }
        catch (PDOException $e) {
        }

        $upload = (float)$pppoeTraffic['total_upload'] + $hotspotUpload;
        $download = (float)$pppoeTraffic['total_download'] + $hotspotDownload;

        $uniqueUsers = 0;
        $sessions24h = 0;
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    (SELECT COUNT(DISTINCT pppoe_user_id) FROM pppoe_sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . ") + 
                    (SELECT COUNT(DISTINCT username) FROM sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . ") as unique_users,
                    (SELECT COUNT(*) FROM pppoe_sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . ") + 
                    (SELECT COUNT(*) FROM sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . ") as sessions_count
            ");
            $params = array_merge($adminParams, $adminParams, $adminParams, $adminParams);
            $stmt->execute($params);
            $res = $stmt->fetch();
            $uniqueUsers = (int)($res['unique_users'] ?? 0);
            $sessions24h = (int)($res['sessions_count'] ?? 0);
        }
        catch (\Exception $e) {
        }

        return [
            'active_sessions' => $activeSessions,
            'pppoe_sessions' => $pppoeSessions,
            'hotspot_sessions' => $hotspotSessions,
            'download_24h' => $download,
            'upload_24h' => $upload,
            'total_24h' => $download + $upload,
            'unique_users_24h' => $uniqueUsers,
            'sessions_24h' => $sessions24h
        ];
    }

    /**
     * Obtenir les top consommateurs (PPPoE + Hotspot)
     */
    public function getTopConsumers(int $limit = 10, ?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $params = $adminId !== null ? array_fill(0, 2, $adminId) : [];
        $params[] = $limit;

        $stmt = $this->pdo->prepare("
            SELECT * FROM (
                SELECT pu.username, pu.customer_name, pu.customer_phone as phone, 'pppoe' as type,
                       SUM(ps.input_octets) as upload, SUM(ps.output_octets) as download,
                       SUM(ps.input_octets) + SUM(ps.output_octets) as total,
                       COUNT(*) as session_count, MAX(ps.start_time) as last_session
                FROM pppoe_sessions ps
                JOIN pppoe_users pu ON ps.pppoe_user_id = pu.id
                WHERE ps.start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . ($adminId !== null ? " AND ps.admin_id = ?" : "") . "
                GROUP BY pu.id, pu.username, pu.customer_name, pu.customer_phone
                UNION ALL
                SELECT username, username as customer_name, NULL as phone, 'hotspot' as type,
                       SUM(input_octets) as upload, SUM(output_octets) as download,
                       SUM(input_octets) + SUM(output_octets) as total,
                       COUNT(*) as session_count, MAX(start_time) as last_session
                FROM sessions
                WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . "
                GROUP BY username
            ) as t
            ORDER BY total DESC
            LIMIT ?
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les sessions actives en direct (PPPoE + Hotspot)
     */
    public function getLiveSessions(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $params = $adminId !== null ? array_fill(0, 2, $adminId) : [];

        $stmt = $this->pdo->prepare("
            SELECT * FROM (
                SELECT ps.id as session_id, pu.username, ps.nas_ip as nasipaddress, ps.start_time, 'pppoe' as type,
                       ps.input_octets as upload, ps.output_octets as download, ps.input_octets + ps.output_octets as total,
                       ps.client_ip as ip_address, ps.client_mac as mac_address, TIMESTAMPDIFF(SECOND, ps.start_time, NOW()) as duration_seconds,
                       pu.customer_name, pu.customer_phone as phone, pp.name as profile_name
                FROM pppoe_sessions ps
                JOIN pppoe_users pu ON ps.pppoe_user_id = pu.id
                LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
                WHERE ps.stop_time IS NULL" . ($adminId !== null ? " AND ps.admin_id = ?" : "") . "
                UNION ALL
                SELECT id as session_id, username, nas_ip as nasipaddress, start_time, 'hotspot' as type,
                       input_octets as upload, output_octets as download, input_octets + output_octets as total,
                       client_ip as ip_address, client_mac as mac_address, TIMESTAMPDIFF(SECOND, start_time, NOW()) as duration_seconds,
                       username as customer_name, NULL as phone, NULL as profile_name
                FROM sessions
                WHERE stop_time IS NULL" . $adminFilter . "
            ) as t
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Statistiques horaires (24 dernières heures) - PPPoE + Hotspot
     */
    public function getHourlyStats(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $params = $adminId !== null ? array_fill(0, 2, $adminId) : [];

        $stmt = $this->pdo->prepare("
            SELECT hour, hour_num, SUM(upload) as upload, SUM(download) as download, SUM(total) as total, SUM(users) as users, SUM(sessions) as sessions FROM (
                SELECT DATE_FORMAT(start_time, '%Y-%m-%d %H:00:00') as hour, HOUR(start_time) as hour_num, COALESCE(SUM(input_octets), 0) as upload, COALESCE(SUM(output_octets), 0) as download, COALESCE(SUM(input_octets), 0) + COALESCE(SUM(output_octets), 0) as total, COUNT(DISTINCT pppoe_user_id) as users, COUNT(*) as sessions
                FROM pppoe_sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . " GROUP BY DATE_FORMAT(start_time, '%Y-%m-%d %H:00:00'), HOUR(start_time)
                UNION ALL
                SELECT DATE_FORMAT(start_time, '%Y-%m-%d %H:00:00') as hour, HOUR(start_time) as hour_num, COALESCE(SUM(input_octets), 0) as upload, COALESCE(SUM(output_octets), 0) as download, COALESCE(SUM(input_octets), 0) + COALESCE(SUM(output_octets), 0) as total, COUNT(DISTINCT username) as users, COUNT(*) as sessions
                FROM sessions WHERE start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)" . $adminFilter . " GROUP BY DATE_FORMAT(start_time, '%Y-%m-%d %H:00:00'), HOUR(start_time)
            ) as t
            GROUP BY hour, hour_num
            ORDER BY hour ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Statistiques journalières (7 derniers jours) - PPPoE + Hotspot
     */
    public function getDailyStats(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND admin_id = ?" : "";
        $params = $adminId !== null ? array_fill(0, 2, $adminId) : [];

        $stmt = $this->pdo->prepare("
            SELECT day, day_name, label, SUM(upload) as upload, SUM(download) as download, SUM(total) as total, SUM(users) as users, SUM(sessions) as sessions FROM (
                SELECT DATE(start_time) as day, DAYNAME(start_time) as day_name, DATE_FORMAT(start_time, '%d/%m') as label, COALESCE(SUM(input_octets), 0) as upload, COALESCE(SUM(output_octets), 0) as download, COALESCE(SUM(input_octets), 0) + COALESCE(SUM(output_octets), 0) as total, COUNT(DISTINCT pppoe_user_id) as users, COUNT(*) as sessions
                FROM pppoe_sessions WHERE MONTH(start_time) = MONTH(CURRENT_DATE()) AND YEAR(start_time) = YEAR(CURRENT_DATE())" . $adminFilter . " GROUP BY DATE(start_time), DAYNAME(start_time), DATE_FORMAT(start_time, '%d/%m')
                UNION ALL
                SELECT DATE(start_time) as day, DAYNAME(start_time) as day_name, DATE_FORMAT(start_time, '%d/%m') as label, COALESCE(SUM(input_octets), 0) as upload, COALESCE(SUM(output_octets), 0) as download, COALESCE(SUM(input_octets), 0) + COALESCE(SUM(output_octets), 0) as total, COUNT(DISTINCT username) as users, COUNT(*) as sessions
                FROM sessions WHERE MONTH(start_time) = MONTH(CURRENT_DATE()) AND YEAR(start_time) = YEAR(CURRENT_DATE())" . $adminFilter . " GROUP BY DATE(start_time), DAYNAME(start_time), DATE_FORMAT(start_time, '%d/%m')
            ) as t
            GROUP BY day, day_name, label
            ORDER BY day ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Alertes FUP (utilisateurs proches ou dépassant leur limite)
     */
    public function getFupAlerts(?int $adminId = null): array
    {
        $adminFilter = $adminId !== null ? " AND pu.admin_id = ?" : "";
        $adminParams = $adminId !== null ? [$adminId] : [];

        $stmt = $this->pdo->prepare("
            SELECT
                pu.id,
                pu.username,
                pu.customer_name as full_name,
                pu.customer_phone as phone,
                pu.fup_data_used,
                pp.fup_quota,
                pp.name as profile_name,
                CASE
                    WHEN pp.fup_quota > 0 THEN
                        ROUND((pu.fup_data_used / pp.fup_quota) * 100, 1)
                    ELSE 0
                END as usage_percent,
                pu.fup_triggered,
                pu.fup_triggered_at
            FROM pppoe_users pu
            JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pp.fup_enabled = 1
              AND pp.fup_quota > 0
              AND (
                  pu.fup_triggered = 1
                  OR (pu.fup_data_used / pp.fup_quota) >= 0.8
              )" . $adminFilter . "
            ORDER BY usage_percent DESC
        ");
        $stmt->execute($adminParams);
        return $stmt->fetchAll();
    }

    // =====================================================
    // Serveurs RADIUS distribués
    // =====================================================

    /**
     * Obtenir tous les serveurs RADIUS
     */
    public function getAllRadiusServers(?int $adminId = null): array
    {
        $sql = "SELECT rs.*,
                       (SELECT COUNT(*) FROM zones WHERE radius_server_id = rs.id) as zones_count,
                       (SELECT COUNT(*) FROM nas n INNER JOIN zones z ON n.zone_id = z.id WHERE z.radius_server_id = rs.id) as nas_count
                FROM radius_servers rs";
        $params = [];
        if ($adminId !== null) {
            $sql .= " WHERE rs.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY rs.name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir un serveur RADIUS par ID
     */
    public function getRadiusServerById(int $id, ?int $adminId = null): ?array
    {
        $sql = "SELECT rs.*,
                       (SELECT COUNT(*) FROM zones WHERE radius_server_id = rs.id) as zones_count,
                       (SELECT COUNT(*) FROM nas n INNER JOIN zones z ON n.zone_id = z.id WHERE z.radius_server_id = rs.id) as nas_count
                FROM radius_servers rs WHERE rs.id = ?";
        $params = [$id];
        if ($adminId !== null) {
            $sql .= " AND rs.admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir un serveur RADIUS par code
     */
    public function getRadiusServerByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM radius_servers WHERE code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Obtenir un serveur RADIUS par sync_token
     */
    public function getRadiusServerBySyncToken(string $token): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM radius_servers WHERE sync_token = ? AND is_active = 1");
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Générer un code unique pour un serveur RADIUS
     */
    public function generateRadiusServerCode(): string
    {
        do {
            $code = 'RS-' . strtoupper(bin2hex(random_bytes(4)));
            $stmt = $this->pdo->prepare("SELECT id FROM radius_servers WHERE code = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());
        return $code;
    }

    /**
     * Créer un serveur RADIUS
     */
    public function createRadiusServer(array $data): int
    {
        $code = !empty($data['code']) ? $data['code'] : $this->generateRadiusServerCode();
        $syncToken = bin2hex(random_bytes(64));
        $platformToken = bin2hex(random_bytes(64));

        $stmt = $this->pdo->prepare("
            INSERT INTO radius_servers (name, description, code, host, webhook_port, webhook_path, sync_token, platform_token, sync_interval, config, admin_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $code,
            $data['host'],
            $data['webhook_port'] ?? 443,
            $data['webhook_path'] ?? '/webhook.php',
            $syncToken,
            $platformToken,
            $data['sync_interval'] ?? 60,
            !empty($data['config']) ? json_encode($data['config']) : null,
            $data['admin_id'] ?? null,
            $data['is_active'] ?? 1
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Mettre à jour un serveur RADIUS
     */
    public function updateRadiusServer(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE radius_servers SET
                name = ?,
                description = ?,
                host = ?,
                webhook_port = ?,
                webhook_path = ?,
                sync_interval = ?,
                config = ?,
                is_active = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['host'],
            $data['webhook_port'] ?? 443,
            $data['webhook_path'] ?? '/webhook.php',
            $data['sync_interval'] ?? 60,
            !empty($data['config']) ? json_encode($data['config']) : null,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    /**
     * Supprimer un serveur RADIUS
     */
    public function deleteRadiusServer(int $id): bool
    {
        // Détacher les zones de ce serveur
        $this->pdo->prepare("UPDATE zones SET radius_server_id = NULL WHERE radius_server_id = ?")->execute([$id]);

        $stmt = $this->pdo->prepare("DELETE FROM radius_servers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Régénérer le sync_token d'un serveur
     */
    public function regenerateRadiusServerSyncToken(int $id): string
    {
        $newToken = bin2hex(random_bytes(64));
        $this->pdo->prepare("UPDATE radius_servers SET sync_token = ? WHERE id = ?")->execute([$newToken, $id]);
        return $newToken;
    }

    /**
     * Régénérer le platform_token d'un serveur
     */
    public function regenerateRadiusServerPlatformToken(int $id): string
    {
        $newToken = bin2hex(random_bytes(64));
        $this->pdo->prepare("UPDATE radius_servers SET platform_token = ? WHERE id = ?")->execute([$newToken, $id]);
        return $newToken;
    }

    /**
     * Mettre à jour le statut d'un serveur RADIUS
     */
    public function updateRadiusServerStatus(int $id, string $status): bool
    {
        $stmt = $this->pdo->prepare("UPDATE radius_servers SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    /**
     * Mettre à jour le heartbeat d'un serveur RADIUS
     */
    public function updateRadiusServerHeartbeat(string $code): bool
    {
        $stmt = $this->pdo->prepare("UPDATE radius_servers SET last_heartbeat_at = NOW(), status = 'online' WHERE code = ?");
        return $stmt->execute([$code]);
    }

    /**
     * Mettre à jour la dernière sync d'un serveur RADIUS
     */
    public function updateRadiusServerLastSync(string $code): bool
    {
        $stmt = $this->pdo->prepare("UPDATE radius_servers SET last_sync_at = NOW(), status = 'online' WHERE code = ?");
        return $stmt->execute([$code]);
    }

    /**
     * Obtenir les zones d'un serveur RADIUS
     */
    public function getZonesByRadiusServer(int $serverId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT z.*,
                   (SELECT COUNT(*) FROM nas WHERE zone_id = z.id) as nas_count,
                   (SELECT COUNT(*) FROM profiles WHERE zone_id = z.id) as profiles_count,
                   (SELECT COUNT(*) FROM vouchers WHERE zone_id = z.id) as vouchers_count
            FROM zones z
            WHERE z.radius_server_id = ?
            ORDER BY z.name
        ");
        $stmt->execute([$serverId]);
        return $stmt->fetchAll();
    }

    /**
     * Obtenir les statuts de tous les serveurs RADIUS
     */
    public function getRadiusServerStatuses(?int $adminId = null): array
    {
        $sql = "SELECT id, code, name, status, last_sync_at, last_heartbeat_at,
                       TIMESTAMPDIFF(SECOND, last_heartbeat_at, NOW()) as seconds_since_heartbeat
                FROM radius_servers";
        $params = [];
        if ($adminId !== null) {
            $sql .= " WHERE admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $servers = $stmt->fetchAll();

        // Marquer offline les serveurs sans heartbeat depuis > 3 minutes
        foreach ($servers as &$server) {
            if ($server['seconds_since_heartbeat'] !== null && $server['seconds_since_heartbeat'] > 180) {
                if ($server['status'] === 'online') {
                    $this->updateRadiusServerStatus($server['id'], 'offline');
                    $server['status'] = 'offline';
                }
            }
        }

        return $servers;
    }

    /**
     * Obtenir toutes les données de sync pour un nœud RADIUS
     * Agrège zones, NAS, vouchers, profils, utilisateurs PPPoE
     */
    public function getRadiusServerSyncData(int $serverId): array
    {
        // Zones de ce serveur
        $zones = $this->getZonesByRadiusServer($serverId);
        $zoneIds = array_column($zones, 'id');

        if (empty($zoneIds)) {
            return [
                'config_hash' => md5('empty'),
                'zones' => [],
                'nas' => [],
                'vouchers' => [],
                'profiles' => [],
                'pppoe_users' => [],
            ];
        }

        $placeholders = implode(',', array_fill(0, count($zoneIds), '?'));

        // NAS de ces zones
        $stmt = $this->pdo->prepare("SELECT * FROM nas WHERE zone_id IN ($placeholders)");
        $stmt->execute($zoneIds);
        $nas = $stmt->fetchAll();

        // Profils de ces zones (+ profils globaux zone_id IS NULL)
        $stmt = $this->pdo->prepare("SELECT * FROM profiles WHERE zone_id IN ($placeholders) OR zone_id IS NULL");
        $stmt->execute($zoneIds);
        $profiles = $stmt->fetchAll();

        // Vouchers actifs/non-utilisés de ces zones
        $stmt = $this->pdo->prepare("
            SELECT * FROM vouchers
            WHERE (zone_id IN ($placeholders) OR zone_id IS NULL)
            AND status IN ('unused', 'active')
        ");
        $stmt->execute($zoneIds);
        $vouchers = $stmt->fetchAll();

        // Utilisateurs PPPoE actifs de ces zones
        $pppoeUsers = [];
        try {
            $stmt = $this->pdo->prepare("
                SELECT pu.* FROM pppoe_users pu
                INNER JOIN pppoe_profiles pp ON pu.profile_id = pp.id
                WHERE (pp.zone_id IN ($placeholders) OR pp.zone_id IS NULL)
                AND pu.status = 'active'
            ");
            $stmt->execute($zoneIds);
            $pppoeUsers = $stmt->fetchAll();
        } catch (PDOException $e) {
            // Table pppoe_users peut ne pas exister
        }

        $data = [
            'zones' => $zones,
            'nas' => $nas,
            'vouchers' => $vouchers,
            'profiles' => $profiles,
            'pppoe_users' => $pppoeUsers,
        ];

        $data['config_hash'] = md5(json_encode($data));

        return $data;
    }

    /**
     * Importer les données de session/accounting depuis un nœud RADIUS
     */
    public function importNodeSyncData(array $data): array
    {
        $imported = ['sessions' => 0, 'auth_logs' => 0, 'voucher_updates' => 0];

        // Importer les sessions
        if (!empty($data['sessions'])) {
            foreach ($data['sessions'] as $session) {
                try {
                    // Vérifier si la session existe déjà
                    $existing = $this->getSessionByAcctId($session['acct_session_id'] ?? $session['session_id'], $session['nas_ip']);
                    if ($existing) {
                        // Mettre à jour
                        $this->updateSession($session);
                    } else {
                        // Créer
                        $this->startSession($session);
                    }
                    $imported['sessions']++;
                } catch (PDOException $e) {
                    // Ignorer les doublons
                }
            }
        }

        // Importer les logs d'auth
        if (!empty($data['auth_logs'])) {
            foreach ($data['auth_logs'] as $log) {
                try {
                    $this->logAuth(
                        $log['username'],
                        $log['nas_ip'],
                        $log['action'],
                        $log['reason'] ?? null,
                        $log['client_mac'] ?? null,
                        $log['client_ip'] ?? null,
                        $log['nas_identifier'] ?? null
                    );
                    $imported['auth_logs']++;
                } catch (PDOException $e) {
                    // Ignorer
                }
            }
        }

        // Mettre à jour les compteurs de vouchers
        if (!empty($data['voucher_updates'])) {
            foreach ($data['voucher_updates'] as $update) {
                try {
                    $this->updateVoucherCounters($update);
                    $imported['voucher_updates']++;
                } catch (PDOException $e) {
                    // Ignorer
                }
            }
        }

        return $imported;
    }

    /**
     * Obtenir le serveur RADIUS d'une zone
     */
    public function getRadiusServerForZone(int $zoneId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT rs.* FROM radius_servers rs
            INNER JOIN zones z ON z.radius_server_id = rs.id
            WHERE z.id = ? AND rs.is_active = 1
        ");
        $stmt->execute([$zoneId]);
        return $stmt->fetch() ?: null;
    }
}