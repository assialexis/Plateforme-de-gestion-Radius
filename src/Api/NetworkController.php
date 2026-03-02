<?php
/**
 * Controller API Network - Gestion des pools IP
 */

class NetworkController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->ensureTablesExist();
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * S'assurer que les tables existent
     */
    private function ensureTablesExist(): void
    {
        $pdo = $this->db->getPdo();

        // Table des pools IP
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ip_pools (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL COMMENT 'Nom du pool',
                description VARCHAR(255) DEFAULT NULL,
                network VARCHAR(45) DEFAULT NULL COMMENT 'Adresse réseau (ex: 192.168.1.0)',
                cidr TINYINT DEFAULT NULL COMMENT 'Notation CIDR (ex: 24)',
                start_ip VARCHAR(45) NOT NULL COMMENT 'Première IP du range',
                end_ip VARCHAR(45) NOT NULL COMMENT 'Dernière IP du range',
                gateway VARCHAR(45) DEFAULT NULL COMMENT 'Passerelle par défaut',
                netmask VARCHAR(45) DEFAULT '255.255.255.0' COMMENT 'Masque de sous-réseau',
                dns_primary VARCHAR(45) DEFAULT '8.8.8.8',
                dns_secondary VARCHAR(45) DEFAULT '8.8.4.4',
                is_active TINYINT(1) DEFAULT 1,
                total_ips INT DEFAULT 0 COMMENT 'Nombre total d\'IPs dans le pool',
                used_ips INT DEFAULT 0 COMMENT 'Nombre d\'IPs utilisées',
                admin_id INT DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY idx_pool_name_admin (name, admin_id),
                INDEX idx_ip_pools_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Ajouter les colonnes manquantes si elles n'existent pas
        try {
            $pdo->exec("ALTER TABLE ip_pools ADD COLUMN network VARCHAR(45) DEFAULT NULL AFTER description");
        }
        catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ip_pools ADD COLUMN cidr TINYINT DEFAULT NULL AFTER network");
        }
        catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ip_pools ADD COLUMN admin_id INT DEFAULT NULL");
        }
        catch (PDOException $e) {
        }
        try {
            $pdo->exec("ALTER TABLE ip_pools ADD INDEX idx_ip_pools_admin_id (admin_id)");
        }
        catch (PDOException $e) {
        }
        // Changer le UNIQUE de name global vers (name, admin_id)
        try {
            $pdo->exec("ALTER TABLE ip_pools DROP INDEX name");
            $pdo->exec("ALTER TABLE ip_pools ADD UNIQUE INDEX idx_pool_name_admin (name, admin_id)");
        }
        catch (PDOException $e) {
        }

        // Table des allocations IP
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS ip_pool_allocations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pool_id INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_type ENUM('pppoe', 'hotspot', 'static') NOT NULL DEFAULT 'pppoe',
                user_id INT DEFAULT NULL COMMENT 'ID du user pppoe ou voucher',
                username VARCHAR(100) DEFAULT NULL,
                status ENUM('available', 'reserved', 'allocated', 'blocked') DEFAULT 'available',
                allocated_at TIMESTAMP NULL,
                expires_at TIMESTAMP NULL,
                notes VARCHAR(255) DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_pool_ip (pool_id, ip_address),
                INDEX idx_pool (pool_id),
                INDEX idx_status (status),
                INDEX idx_user (user_type, user_id),
                FOREIGN KEY (pool_id) REFERENCES ip_pools(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Vérifier qu'un pool appartient à l'admin courant
     */
    private function verifyPoolOwnership(?array $pool): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $pool !== null && isset($pool['admin_id']) && (int)$pool['admin_id'] !== $adminId) {
            jsonError(__('api.network_pool_not_found'), 404);
            exit;
        }
    }

    /**
     * Charger un pool et vérifier l'ownership
     */
    private function getPoolOrFail(int $id): array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);
        $pool = $stmt->fetch();
        if (!$pool) {
            jsonError(__('api.network_pool_not_found'), 404);
            exit;
        }
        $this->verifyPoolOwnership($pool);
        return $pool;
    }

    // ==========================================
    // IP Pools CRUD
    // ==========================================

    /**
     * GET /api/network/pools
     */
    public function listPools(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT p.*,
                   (SELECT COUNT(*) FROM ip_pool_allocations WHERE pool_id = p.id AND status = 'allocated') as used_ips,
                   (SELECT COUNT(*) FROM ip_pool_allocations WHERE pool_id = p.id AND status = 'available') as available_ips
            FROM ip_pools p
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " WHERE p.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY p.name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pools = $stmt->fetchAll();

        // Convertir les types
        foreach ($pools as &$pool) {
            $pool['is_active'] = (bool)$pool['is_active'];
            $pool['total_ips'] = (int)$pool['total_ips'];
            $pool['used_ips'] = (int)$pool['used_ips'];
            $pool['available_ips'] = (int)$pool['available_ips'];
        }

        jsonSuccess($pools);
    }

    /**
     * GET /api/network/pools/{id}
     */
    public function showPool(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);
        $pool = $stmt->fetch();

        if (!$pool) {
            jsonError(__('api.network_pool_not_found'), 404);
        }
        $this->verifyPoolOwnership($pool);

        $pool['is_active'] = (bool)$pool['is_active'];

        jsonSuccess($pool);
    }

    /**
     * POST /api/network/pools
     */
    public function createPool(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.network_pool_name_required'), 400);
        }
        if (empty($data['start_ip'])) {
            jsonError(__('api.network_start_ip_required'), 400);
        }
        if (empty($data['end_ip'])) {
            jsonError(__('api.network_end_ip_required'), 400);
        }

        // Valider les IPs
        if (!filter_var($data['start_ip'], FILTER_VALIDATE_IP)) {
            jsonError(__('api.network_invalid_start_ip'), 400);
        }
        if (!filter_var($data['end_ip'], FILTER_VALIDATE_IP)) {
            jsonError(__('api.network_invalid_end_ip'), 400);
        }

        // Vérifier que start <= end
        if (ip2long($data['start_ip']) > ip2long($data['end_ip'])) {
            jsonError(__('api.network_start_ip_must_be_less'), 400);
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Vérifier l'unicité du nom (scopé par admin)
        $uniqueSql = "SELECT id FROM ip_pools WHERE name = ?";
        $uniqueParams = [$data['name']];
        if ($adminId !== null) {
            $uniqueSql .= " AND admin_id = ?";
            $uniqueParams[] = $adminId;
        }
        $stmt = $pdo->prepare($uniqueSql);
        $stmt->execute($uniqueParams);
        if ($stmt->fetch()) {
            jsonError(__('api.network_pool_name_exists'), 400);
        }

        // Calculer le nombre total d'IPs
        $totalIps = ip2long($data['end_ip']) - ip2long($data['start_ip']) + 1;

        try {
            $pdo->beginTransaction();

            // Créer le pool
            $stmt = $pdo->prepare("
                INSERT INTO ip_pools (name, description, network, cidr, start_ip, end_ip, gateway, netmask, dns_primary, dns_secondary, total_ips, is_active, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? null,
                $data['network'] ?? null,
                isset($data['cidr']) ? (int)$data['cidr'] : null,
                $data['start_ip'],
                $data['end_ip'],
                $data['gateway'] ?? null,
                $data['netmask'] ?? '255.255.255.0',
                $data['dns_primary'] ?? '8.8.8.8',
                $data['dns_secondary'] ?? '8.8.4.4',
                $totalIps,
                isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
                $adminId
            ]);

            $poolId = $pdo->lastInsertId();

            // Générer les entrées IP dans la table d'allocations
            $this->generatePoolIPs($poolId, $data['start_ip'], $data['end_ip']);

            $pdo->commit();

            // Récupérer le pool créé
            $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
            $stmt->execute([$poolId]);
            $pool = $stmt->fetch();

            jsonSuccess($pool, __('api.network_pool_created'));
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError(__('api.network_error_creating_pool') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/network/pools/{id}
     */
    public function updatePool(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);
        $pool = $stmt->fetch();

        if (!$pool) {
            jsonError(__('api.network_pool_not_found'), 404);
        }
        $this->verifyPoolOwnership($pool);

        // Construire la requête de mise à jour
        $updates = [];
        $values = [];

        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $values[] = $data['name'];
        }
        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $values[] = $data['description'];
        }
        if (isset($data['gateway'])) {
            $updates[] = 'gateway = ?';
            $values[] = $data['gateway'];
        }
        if (isset($data['netmask'])) {
            $updates[] = 'netmask = ?';
            $values[] = $data['netmask'];
        }
        if (isset($data['dns_primary'])) {
            $updates[] = 'dns_primary = ?';
            $values[] = $data['dns_primary'];
        }
        if (isset($data['dns_secondary'])) {
            $updates[] = 'dns_secondary = ?';
            $values[] = $data['dns_secondary'];
        }
        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $values[] = $data['is_active'] ? 1 : 0;
        }

        if (empty($updates)) {
            jsonError(__('api.network_no_data_to_update'), 400);
        }

        $values[] = $id;
        $sql = "UPDATE ip_pools SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        // Récupérer le pool mis à jour
        $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);
        $pool = $stmt->fetch();

        jsonSuccess($pool, __('api.network_pool_updated'));
    }

    /**
     * DELETE /api/network/pools/{id}
     */
    public function deletePool(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);
        $pool = $stmt->fetch();

        if (!$pool) {
            jsonError(__('api.network_pool_not_found'), 404);
        }
        $this->verifyPoolOwnership($pool);

        // Vérifier si des IPs sont allouées
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ip_pool_allocations WHERE pool_id = ? AND status = 'allocated'");
        $stmt->execute([$id]);
        $allocated = $stmt->fetchColumn();

        if ($allocated > 0) {
            jsonError(__('api.network_cannot_delete_pool_allocated'), 400);
        }

        // Supprimer le pool (les allocations seront supprimées par CASCADE)
        $stmt = $pdo->prepare("DELETE FROM ip_pools WHERE id = ?");
        $stmt->execute([$id]);

        jsonSuccess(['deleted' => true], __('api.network_pool_deleted'));
    }

    // ==========================================
    // IP Allocations
    // ==========================================

    /**
     * GET /api/network/pools/{id}/ips
     */
    public function listPoolIPs(array $params): void
    {
        $poolId = (int)$params['id'];
        $this->getPoolOrFail($poolId);

        $status = get('status'); // available, allocated, reserved, blocked
        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 50)));
        $offset = ($page - 1) * $perPage;

        $pdo = $this->db->getPdo();

        // Compter le total
        $countSql = "SELECT COUNT(*) FROM ip_pool_allocations WHERE pool_id = ?";
        $countParams = [$poolId];
        if ($status) {
            $countSql .= " AND status = ?";
            $countParams[] = $status;
        }
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($countParams);
        $total = (int)$stmt->fetchColumn();

        // Récupérer les IPs
        $sql = "
            SELECT a.*, p.name as pool_name
            FROM ip_pool_allocations a
            JOIN ip_pools p ON a.pool_id = p.id
            WHERE a.pool_id = ?
        ";
        $params = [$poolId];

        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY INET_ATON(a.ip_address) LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ips = $stmt->fetchAll();

        jsonSuccess([
            'data' => $ips,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ]);
    }

    /**
     * GET /api/network/pools/{id}/next-available
     */
    public function getNextAvailableIPApi(array $params): void
    {
        $poolId = (int)$params['id'];
        $this->getPoolOrFail($poolId);
        $result = $this->getNextAvailableIP($poolId);

        if ($result) {
            jsonSuccess(['ip' => $result['ip_address']]);
        }
        else {
            jsonError(__('api.network_no_available_ip'), 404);
        }
    }

    /**
     * GET /api/network/pools/{id}/stats
     */
    public function poolStats(array $params): void
    {
        $poolId = (int)$params['id'];
        $this->getPoolOrFail($poolId);
        $pdo = $this->db->getPdo();

        // Stats par statut
        $stmt = $pdo->prepare("
            SELECT status, COUNT(*) as count
            FROM ip_pool_allocations
            WHERE pool_id = ?
            GROUP BY status
        ");
        $stmt->execute([$poolId]);
        $statsByStatus = [];
        while ($row = $stmt->fetch()) {
            $statsByStatus[$row['status']] = (int)$row['count'];
        }

        // Stats par type d'utilisateur
        $stmt = $pdo->prepare("
            SELECT user_type, COUNT(*) as count
            FROM ip_pool_allocations
            WHERE pool_id = ? AND status = 'allocated'
            GROUP BY user_type
        ");
        $stmt->execute([$poolId]);
        $statsByUserType = [];
        while ($row = $stmt->fetch()) {
            $statsByUserType[$row['user_type']] = (int)$row['count'];
        }

        jsonSuccess([
            'by_status' => $statsByStatus,
            'by_user_type' => $statsByUserType
        ]);
    }

    /**
     * POST /api/network/pools/{id}/allocate
     * Allouer la prochaine IP disponible
     */
    public function allocateIP(array $params): void
    {
        $poolId = (int)$params['id'];
        $data = getJsonBody();

        $userType = $data['user_type'] ?? 'pppoe';
        $userId = $data['user_id'] ?? null;
        $username = $data['username'] ?? null;

        $pdo = $this->db->getPdo();

        // Vérifier que le pool existe, appartient à l'admin et est actif
        $pool = $this->getPoolOrFail($poolId);

        if (!$pool['is_active']) {
            jsonError(__('api.network_pool_inactive'), 400);
        }

        try {
            $pdo->beginTransaction();

            // Trouver la première IP disponible
            $stmt = $pdo->prepare("
                SELECT * FROM ip_pool_allocations
                WHERE pool_id = ? AND status = 'available'
                ORDER BY INET_ATON(ip_address)
                LIMIT 1
                FOR UPDATE
            ");
            $stmt->execute([$poolId]);
            $ip = $stmt->fetch();

            if (!$ip) {
                $pdo->rollBack();
                jsonError(__('api.network_no_available_ip'), 400);
                return;
            }

            // Allouer l'IP
            $stmt = $pdo->prepare("
                UPDATE ip_pool_allocations
                SET status = 'allocated',
                    user_type = ?,
                    user_id = ?,
                    username = ?,
                    allocated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$userType, $userId, $username, $ip['id']]);

            // Mettre à jour le compteur du pool
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = used_ips + 1 WHERE id = ?");
            $stmt->execute([$poolId]);

            $pdo->commit();

            jsonSuccess([
                'ip_address' => $ip['ip_address'],
                'pool_name' => $pool['name'],
                'gateway' => $pool['gateway'],
                'netmask' => $pool['netmask'],
                'dns_primary' => $pool['dns_primary'],
                'dns_secondary' => $pool['dns_secondary']
            ], __('api.network_ip_allocated'));
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError(__('api.network_error_allocating_ip') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/network/ips/{id}/release
     * Libérer une IP allouée
     */
    public function releaseIP(array $params): void
    {
        $ipId = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pool_allocations WHERE id = ?");
        $stmt->execute([$ipId]);
        $ip = $stmt->fetch();

        if (!$ip) {
            jsonError(__('api.network_ip_allocation_not_found'), 404);
        }

        // Vérifier ownership du pool parent
        $this->getPoolOrFail($ip['pool_id']);

        if ($ip['status'] !== 'allocated') {
            jsonError(__('api.network_ip_not_allocated'), 400);
        }

        try {
            $pdo->beginTransaction();

            // Libérer l'IP
            $stmt = $pdo->prepare("
                UPDATE ip_pool_allocations
                SET status = 'available',
                    user_type = 'pppoe',
                    user_id = NULL,
                    username = NULL,
                    allocated_at = NULL
                WHERE id = ?
            ");
            $stmt->execute([$ipId]);

            // Mettre à jour le compteur du pool
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = GREATEST(0, used_ips - 1) WHERE id = ?");
            $stmt->execute([$ip['pool_id']]);

            $pdo->commit();

            jsonSuccess(['released' => true], __('api.network_ip_released'));
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError(__('api.network_error_releasing_ip') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/network/ips/{id}/status
     * Changer le statut d'une IP (bloquer/débloquer/réserver)
     */
    public function updateIPStatus(array $params): void
    {
        $ipId = (int)$params['id'];
        $data = getJsonBody();

        if (empty($data['status'])) {
            jsonError(__('api.network_status_required'), 400);
        }

        $validStatuses = ['available', 'reserved', 'blocked'];
        if (!in_array($data['status'], $validStatuses)) {
            jsonError(__('api.network_invalid_status'), 400);
        }

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pool_allocations WHERE id = ?");
        $stmt->execute([$ipId]);
        $ip = $stmt->fetch();

        if (!$ip) {
            jsonError(__('api.network_ip_allocation_not_found'), 404);
        }

        // Vérifier ownership du pool parent
        $this->getPoolOrFail($ip['pool_id']);

        if ($ip['status'] === 'allocated') {
            jsonError(__('api.network_cannot_change_allocated_ip'), 400);
        }

        $oldStatus = $ip['status'];
        $newStatus = $data['status'];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE ip_pool_allocations
                SET status = ?,
                    notes = ?,
                    username = CASE WHEN ? = 'available' THEN NULL ELSE username END,
                    allocated_at = CASE WHEN ? = 'available' THEN NULL ELSE allocated_at END
                WHERE id = ?
            ");
            $stmt->execute([$newStatus, $data['notes'] ?? null, $newStatus, $newStatus, $ipId]);

            // Gérer le compteur du pool
            // Si on passe de reserved/blocked à available, décrémenter
            if ($oldStatus === 'reserved' && $newStatus === 'available') {
                $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = GREATEST(0, used_ips - 1) WHERE id = ?");
                $stmt->execute([$ip['pool_id']]);
            }

            $pdo->commit();

            jsonSuccess(['updated' => true], __('api.network_ip_status_updated'));
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError(__('api.error_generic') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/network/ips/{id}/reserve
     * Réserver une IP avec informations (optionnellement liée à un client PPPoE)
     */
    public function reserveIP(array $params): void
    {
        $ipId = (int)$params['id'];
        $data = getJsonBody();

        if (empty($data['reserved_for'])) {
            jsonError(__('api.network_reservation_name_required'), 400);
        }

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM ip_pool_allocations WHERE id = ?");
        $stmt->execute([$ipId]);
        $ip = $stmt->fetch();

        if (!$ip) {
            jsonError(__('api.network_ip_allocation_not_found'), 404);
        }

        // Vérifier ownership du pool parent
        $this->getPoolOrFail($ip['pool_id']);

        if ($ip['status'] !== 'available') {
            jsonError(__('api.network_ip_not_available_for_reservation'), 400);
        }

        // Vérifier si un client PPPoE est spécifié
        $pppoeUserId = !empty($data['pppoe_user_id']) ? (int)$data['pppoe_user_id'] : null;
        $pppoeUsername = null;

        if ($pppoeUserId) {
            // Vérifier que le client PPPoE existe
            $pppoeUser = $this->db->getPPPoEUserById($pppoeUserId);
            if (!$pppoeUser) {
                jsonError(__('api.network_pppoe_client_not_found'), 404);
            }
            $pppoeUsername = $pppoeUser['username'];
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE ip_pool_allocations
                SET status = 'reserved',
                    user_type = 'pppoe',
                    user_id = ?,
                    username = ?,
                    notes = ?,
                    allocated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $pppoeUserId,
                $data['reserved_for'],
                $data['notes'] ?? null,
                $ipId
            ]);

            // Mettre à jour le compteur du pool (les réservées comptent comme utilisées)
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = used_ips + 1 WHERE id = ?");
            $stmt->execute([$ip['pool_id']]);

            $pdo->commit();

            $response = [
                'ip_address' => $ip['ip_address'],
                'reserved_for' => $data['reserved_for'],
                'status' => 'reserved'
            ];

            if ($pppoeUserId) {
                $response['pppoe_user_id'] = $pppoeUserId;
                $response['pppoe_username'] = $pppoeUsername;
            }

            jsonSuccess($response, __('api.network_ip_reserved'));
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError(__('api.network_error_reserving_ip') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/network/pools/{id}/reserved-for/{userId}
     * Récupérer l'IP réservée pour un client PPPoE spécifique dans un pool
     */
    public function getReservedIPForUser(array $params): void
    {
        $poolId = (int)$params['id'];
        $this->getPoolOrFail($poolId);
        $userId = (int)$params['userId'];

        $pdo = $this->db->getPdo();

        // Chercher une IP réservée pour ce client dans ce pool
        $stmt = $pdo->prepare("
            SELECT ip_address, username, notes
            FROM ip_pool_allocations
            WHERE pool_id = ? AND user_type = 'pppoe' AND user_id = ? AND status = 'reserved'
            LIMIT 1
        ");
        $stmt->execute([$poolId, $userId]);
        $reservation = $stmt->fetch();

        if ($reservation) {
            jsonSuccess([
                'ip' => $reservation['ip_address'],
                'reserved_for' => $reservation['username'],
                'notes' => $reservation['notes']
            ]);
        }
        else {
            // Pas d'IP réservée, retourner la prochaine disponible
            $result = $this->getNextAvailableIP($poolId);
            if ($result) {
                jsonSuccess(['ip' => $result['ip_address'], 'reserved' => false]);
            }
            else {
                jsonError(__('api.network_no_available_ip'), 404);
            }
        }
    }

    /**
     * GET /api/network/pools/available
     * Liste des pools avec IPs disponibles
     */
    public function availablePools(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT p.id, p.name, p.description, p.total_ips,
                   (SELECT COUNT(*) FROM ip_pool_allocations WHERE pool_id = p.id AND status = 'available') as available_ips
            FROM ip_pools p
            WHERE p.is_active = 1";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND p.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " HAVING available_ips > 0 ORDER BY p.name";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pools = $stmt->fetchAll();

        jsonSuccess($pools);
    }

    /**
     * GET /api/network/ips/check/{ip}
     * Vérifier si une IP est disponible (pour assignation statique)
     */
    public function checkIPAvailability(array $params): void
    {
        $ip = $params['ip'];

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            jsonError(__('api.network_invalid_ip_address'), 400);
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // D'abord vérifier si l'IP est déjà utilisée comme IP statique par un client PPPoE (scopé par admin)
        $sql = "SELECT id, username, customer_name FROM pppoe_users WHERE static_ip = ?";
        $sqlParams = [$ip];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $sqlParams[] = $adminId;
        }
        $sql .= " LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($sqlParams);
        $pppoeUser = $stmt->fetch();

        if ($pppoeUser) {
            $clientName = $pppoeUser['customer_name'] ?: $pppoeUser['username'];
            jsonSuccess([
                'available' => false,
                'message' => "IP utilisée par: {$clientName}",
                'user' => $pppoeUser['username'],
                'user_type' => 'pppoe_static'
            ]);
            return;
        }

        // Chercher l'IP dans les pools (scopé par admin)
        $poolSql = "
            SELECT a.*, p.name as pool_name
            FROM ip_pool_allocations a
            JOIN ip_pools p ON a.pool_id = p.id
            WHERE a.ip_address = ?";
        $poolParams = [$ip];
        if ($adminId !== null) {
            $poolSql .= " AND p.admin_id = ?";
            $poolParams[] = $adminId;
        }
        $stmt = $pdo->prepare($poolSql);
        $stmt->execute($poolParams);
        $allocation = $stmt->fetch();

        if (!$allocation) {
            // L'IP n'est dans aucun pool mais pas utilisée non plus
            jsonSuccess([
                'available' => true,
                'message' => 'IP disponible (hors pool)'
            ]);
            return;
        }

        // L'IP est dans un pool
        $isAvailable = $allocation['status'] === 'available';
        $message = '';

        if ($isAvailable) {
            $message = "IP disponible (Pool: {$allocation['pool_name']})";
        }
        else {
            switch ($allocation['status']) {
                case 'allocated':
                    $message = "IP allouée à: {$allocation['username']}";
                    break;
                case 'reserved':
                    $message = "IP réservée pour: {$allocation['username']}";
                    break;
                case 'blocked':
                    $message = "IP bloquée";
                    break;
                default:
                    $message = "IP non disponible";
            }
        }

        jsonSuccess([
            'available' => $isAvailable,
            'message' => $message,
            'status' => $allocation['status'],
            'pool_name' => $allocation['pool_name'],
            'user' => $allocation['username'],
            'user_type' => $allocation['user_type']
        ]);
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Générer les entrées IP pour un pool
     */
    private function generatePoolIPs(int $poolId, string $startIp, string $endIp): void
    {
        $pdo = $this->db->getPdo();

        $start = ip2long($startIp);
        $end = ip2long($endIp);

        // Limiter à 1000 IPs par pool pour éviter les problèmes de performance
        $maxIps = 1000;
        $count = min($end - $start + 1, $maxIps);

        $stmt = $pdo->prepare("
            INSERT INTO ip_pool_allocations (pool_id, ip_address, status)
            VALUES (?, ?, 'available')
        ");

        for ($i = 0; $i < $count; $i++) {
            $ip = long2ip($start + $i);
            $stmt->execute([$poolId, $ip]);
        }
    }

    /**
     * Obtenir la prochaine IP disponible d'un pool (utilisé par PPPoE)
     */
    public function getNextAvailableIP(int $poolId): ?array
    {
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            SELECT a.ip_address, p.gateway, p.netmask, p.dns_primary, p.dns_secondary
            FROM ip_pool_allocations a
            JOIN ip_pools p ON a.pool_id = p.id
            WHERE a.pool_id = ? AND a.status = 'available' AND p.is_active = 1
            ORDER BY INET_ATON(a.ip_address)
            LIMIT 1
        ");
        $stmt->execute([$poolId]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Allouer une IP spécifique à un utilisateur
     */
    public function allocateSpecificIP(string $ipAddress, string $userType, ?int $userId, ?string $username): bool
    {
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            UPDATE ip_pool_allocations
            SET status = 'allocated',
                user_type = ?,
                user_id = ?,
                username = ?,
                allocated_at = NOW()
            WHERE ip_address = ? AND status = 'available'
        ");
        $stmt->execute([$userType, $userId, $username, $ipAddress]);

        if ($stmt->rowCount() > 0) {
            // Mettre à jour le compteur du pool
            $stmt = $pdo->prepare("
                UPDATE ip_pools p
                JOIN ip_pool_allocations a ON a.pool_id = p.id
                SET p.used_ips = p.used_ips + 1
                WHERE a.ip_address = ?
            ");
            $stmt->execute([$ipAddress]);
            return true;
        }

        return false;
    }

    /**
     * Libérer l'IP d'un utilisateur
     */
    public function releaseUserIP(string $userType, int $userId): bool
    {
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            SELECT pool_id FROM ip_pool_allocations
            WHERE user_type = ? AND user_id = ? AND status = 'allocated'
        ");
        $stmt->execute([$userType, $userId]);
        $allocation = $stmt->fetch();

        if (!$allocation) {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE ip_pool_allocations
            SET status = 'available',
                user_id = NULL,
                username = NULL,
                allocated_at = NULL
            WHERE user_type = ? AND user_id = ? AND status = 'allocated'
        ");
        $stmt->execute([$userType, $userId]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE ip_pools SET used_ips = GREATEST(0, used_ips - 1) WHERE id = ?");
            $stmt->execute([$allocation['pool_id']]);
            return true;
        }

        return false;
    }
}