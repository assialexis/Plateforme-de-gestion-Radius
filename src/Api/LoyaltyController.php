<?php
/**
 * Controller API Système de Fidélité
 * Gestion des clients fidèles, règles et récompenses
 */

class LoyaltyController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    // ==========================================
    // Ownership verification helpers
    // ==========================================

    private function verifyCustomerOwnership(?array $customer): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $customer !== null && isset($customer['admin_id']) && (int)$customer['admin_id'] !== $adminId) {
            jsonError(__('api.loyalty_customer_not_found'), 404);
            exit;
        }
    }

    private function verifyRuleOwnership(?array $rule): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $rule !== null && isset($rule['admin_id']) && (int)$rule['admin_id'] !== $adminId) {
            jsonError(__('api.loyalty_rule_not_found'), 404);
            exit;
        }
    }

    private function verifyRewardOwnership(?array $reward): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $reward !== null && isset($reward['admin_id']) && (int)$reward['admin_id'] !== $adminId) {
            jsonError(__('api.loyalty_reward_not_found'), 404);
            exit;
        }
    }

    // ==========================================
    // Auto-provisioning des règles par défaut
    // ==========================================

    private function ensureLoyaltyRules(?int $adminId): void
    {
        if ($adminId === null) return;

        $pdo = $this->db->getPdo();

        // Vérifier si le provisioning a déjà été fait pour cet admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM settings WHERE setting_key = 'loyalty_rules_provisioned' AND admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) return;

        // Marquer comme provisionné (avant insert pour éviter les doublons)
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description, admin_id) VALUES ('loyalty_rules_provisioned', '1', 'Auto-provisioning des règles de fidélité effectué', ?)")->execute([$adminId]);

        // Vérifier si l'admin a déjà des règles (via migration/seed)
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM loyalty_rules WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) return;

        // Insérer les règles par défaut pour cet admin
        $pdo->prepare("
            INSERT INTO loyalty_rules (name, description, rule_type, threshold_value, reward_type, points_per_purchase, is_active, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            '5 achats = 1 gratuit',
            'Après 5 achats, recevez un voucher gratuit du même profil que votre dernier achat',
            'purchase_count', 5, 'free_voucher', 1, 1, $adminId
        ]);

        $pdo->prepare("
            INSERT INTO loyalty_rules (name, description, rule_type, threshold_value, reward_type, points_per_purchase, is_active, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            'Programme points',
            'Gagnez 1 point par achat, échangez 10 points contre un voucher',
            'points', 10, 'free_voucher', 1, 0, $adminId
        ]);
    }

    // ==========================================
    // Clients Fidèles
    // ==========================================

    /**
     * GET /api/loyalty/customers
     */
    public function listCustomers(): void
    {
        $search = get('search');
        $sortBy = get('sort_by') ?: 'total_purchases';
        $sortOrder = get('sort_order') ?: 'DESC';
        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "admin_id = ?";
            $params[] = $adminId;
        }

        if ($search) {
            $where[] = "(phone LIKE ? OR customer_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $allowedSorts = ['total_purchases', 'total_spent', 'points_balance', 'rewards_earned', 'last_purchase_at', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'total_purchases';
        }
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_customers $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT * FROM loyalty_customers
            $whereClause
            ORDER BY $sortBy $sortOrder
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $customers = $stmt->fetchAll();

        jsonSuccess([
            'customers' => $customers,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    /**
     * GET /api/loyalty/customers/{id}
     */
    public function showCustomer(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM loyalty_customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            jsonError(__('api.loyalty_customer_not_found'), 404);
        }
        $this->verifyCustomerOwnership($customer);

        $purchasesStmt = $pdo->prepare("
            SELECT * FROM loyalty_purchases
            WHERE customer_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $purchasesStmt->execute([$id]);
        $customer['purchases'] = $purchasesStmt->fetchAll();

        $rewardsStmt = $pdo->prepare("
            SELECT * FROM loyalty_rewards
            WHERE customer_id = ?
            ORDER BY created_at DESC
            LIMIT 20
        ");
        $rewardsStmt->execute([$id]);
        $customer['rewards'] = $rewardsStmt->fetchAll();

        jsonSuccess($customer);
    }

    /**
     * GET /api/loyalty/customers/phone/{phone}
     */
    public function findByPhone(array $params): void
    {
        $phone = $this->normalizePhone($params['phone']);
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "SELECT * FROM loyalty_customers WHERE phone = ?";
        $sqlParams = [$phone];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $sqlParams[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($sqlParams);
        $customer = $stmt->fetch();

        if (!$customer) {
            jsonSuccess(['found' => false, 'customer' => null]);
            return;
        }

        $rewardsStmt = $pdo->prepare("
            SELECT * FROM loyalty_rewards
            WHERE customer_id = ? AND status = 'pending'
            ORDER BY created_at DESC
        ");
        $rewardsStmt->execute([$customer['id']]);
        $customer['pending_rewards'] = $rewardsStmt->fetchAll();

        $customer['progress'] = $this->calculateProgress($customer);

        jsonSuccess(['found' => true, 'customer' => $customer]);
    }

    /**
     * DELETE /api/loyalty/customers/{id}
     */
    public function deleteCustomer(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM loyalty_customers WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();
        if (!$customer) {
            jsonError(__('api.loyalty_customer_not_found'), 404);
        }
        $this->verifyCustomerOwnership($customer);

        // Les loyalty_purchases et loyalty_rewards sont supprimés en cascade (FK ON DELETE CASCADE)
        $pdo->prepare("DELETE FROM loyalty_customers WHERE id = ?")->execute([$id]);
        jsonSuccess(null, __('api.loyalty_customer_deleted'));
    }

    // ==========================================
    // Règles de Fidélité
    // ==========================================

    /**
     * GET /api/loyalty/rules
     */
    public function listRules(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $activeOnly = get('active_only') === '1';

        $this->ensureLoyaltyRules($adminId);

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "lr.admin_id = ?";
            $params[] = $adminId;
        }
        if ($activeOnly) {
            $where[] = "lr.is_active = 1";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("
            SELECT lr.*, p.name as profile_name
            FROM loyalty_rules lr
            LEFT JOIN profiles p ON lr.reward_profile_id = p.id
            $whereClause
            ORDER BY lr.id
        ");
        $stmt->execute($params);
        $rules = $stmt->fetchAll();

        jsonSuccess($rules);
    }

    /**
     * GET /api/loyalty/rules/{id}
     */
    public function showRule(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            SELECT lr.*, p.name as profile_name
            FROM loyalty_rules lr
            LEFT JOIN profiles p ON lr.reward_profile_id = p.id
            WHERE lr.id = ?
        ");
        $stmt->execute([$id]);
        $rule = $stmt->fetch();

        if (!$rule) {
            jsonError(__('api.loyalty_rule_not_found'), 404);
        }
        $this->verifyRuleOwnership($rule);

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_rewards WHERE rule_id = ?");
        $countStmt->execute([$id]);
        $rule['rewards_generated'] = (int)$countStmt->fetchColumn();

        jsonSuccess($rule);
    }

    /**
     * POST /api/loyalty/rules
     */
    public function createRule(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.loyalty_rule_name_required'), 400);
        }
        if (!isset($data['threshold_value']) || $data['threshold_value'] < 1) {
            jsonError(__('api.loyalty_threshold_required'), 400);
        }

        $adminId = $this->getAdminId();
        $rewardProfileId = !empty($data['reward_profile_id']) ? (int)$data['reward_profile_id'] : null;
        $maxRewards = !empty($data['max_rewards_per_customer']) ? (int)$data['max_rewards_per_customer'] : null;
        $validFrom = !empty($data['valid_from']) ? $data['valid_from'] : null;
        $validUntil = !empty($data['valid_until']) ? $data['valid_until'] : null;
        $description = !empty($data['description']) ? $data['description'] : null;
        $rewardValue = !empty($data['reward_value']) ? $data['reward_value'] : null;

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_rules
            (name, description, rule_type, threshold_value, reward_type, reward_profile_id, reward_value,
             points_per_purchase, points_per_amount, points_amount_unit, is_active, is_cumulative,
             max_rewards_per_customer, valid_from, valid_until, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $description,
            $data['rule_type'] ?? 'purchase_count',
            (int)$data['threshold_value'],
            $data['reward_type'] ?? 'free_voucher',
            $rewardProfileId,
            $rewardValue,
            (int)($data['points_per_purchase'] ?? 1),
            (int)($data['points_per_amount'] ?? 0),
            (int)($data['points_amount_unit'] ?? 100),
            (int)($data['is_active'] ?? 1),
            (int)($data['is_cumulative'] ?? 0),
            $maxRewards,
            $validFrom,
            $validUntil,
            $adminId
        ]);

        $id = $pdo->lastInsertId();
        jsonSuccess(['id' => $id], __('api.loyalty_rule_created'));
    }

    /**
     * PUT /api/loyalty/rules/{id}
     */
    public function updateRule(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM loyalty_rules WHERE id = ?");
        $stmt->execute([$id]);
        $rule = $stmt->fetch();
        if (!$rule) {
            jsonError(__('api.loyalty_rule_not_found'), 404);
        }
        $this->verifyRuleOwnership($rule);

        $nullableFields = ['reward_profile_id', 'max_rewards_per_customer', 'valid_from', 'valid_until', 'description', 'reward_value'];
        foreach ($nullableFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] === '') {
                $data[$field] = null;
            }
        }

        $intFields = ['reward_profile_id', 'max_rewards_per_customer', 'threshold_value', 'points_per_purchase', 'points_per_amount', 'points_amount_unit', 'is_active', 'is_cumulative'];
        foreach ($intFields as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $data[$field] = (int)$data[$field];
            }
        }

        $updates = [];
        $values = [];

        $fields = ['name', 'description', 'rule_type', 'threshold_value', 'reward_type',
                   'reward_profile_id', 'reward_value', 'points_per_purchase', 'points_per_amount',
                   'points_amount_unit', 'is_active', 'is_cumulative', 'max_rewards_per_customer',
                   'valid_from', 'valid_until'];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "$field = ?";
                $values[] = $data[$field];
            }
        }

        if (empty($updates)) {
            jsonError(__('api.no_data_to_update'), 400);
        }

        $values[] = $id;
        $stmt = $pdo->prepare("UPDATE loyalty_rules SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($values);

        jsonSuccess(null, __('api.loyalty_rule_updated'));
    }

    /**
     * DELETE /api/loyalty/rules/{id}
     */
    public function deleteRule(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM loyalty_rules WHERE id = ?");
        $stmt->execute([$id]);
        $rule = $stmt->fetch();
        if (!$rule) {
            jsonError(__('api.loyalty_rule_not_found'), 404);
        }
        $this->verifyRuleOwnership($rule);

        $pdo->prepare("DELETE FROM loyalty_rules WHERE id = ?")->execute([$id]);
        jsonSuccess(null, __('api.loyalty_rule_deleted'));
    }

    // ==========================================
    // Récompenses
    // ==========================================

    /**
     * GET /api/loyalty/rewards
     */
    public function listRewards(): void
    {
        $status = get('status');
        $customerId = get('customer_id');
        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "lr.admin_id = ?";
            $params[] = $adminId;
        }
        if ($status) {
            $where[] = "lr.status = ?";
            $params[] = $status;
        }
        if ($customerId) {
            $where[] = "lr.customer_id = ?";
            $params[] = (int)$customerId;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_rewards lr $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT lr.*, lc.phone, lc.customer_name, ru.name as rule_name
            FROM loyalty_rewards lr
            LEFT JOIN loyalty_customers lc ON lr.customer_id = lc.id
            LEFT JOIN loyalty_rules ru ON lr.rule_id = ru.id
            $whereClause
            ORDER BY lr.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $rewards = $stmt->fetchAll();

        jsonSuccess([
            'rewards' => $rewards,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    /**
     * POST /api/loyalty/rewards/{id}/claim
     */
    public function claimReward(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            SELECT lr.*, lc.phone, lc.customer_name
            FROM loyalty_rewards lr
            JOIN loyalty_customers lc ON lr.customer_id = lc.id
            WHERE lr.id = ?
        ");
        $stmt->execute([$id]);
        $reward = $stmt->fetch();

        if (!$reward) {
            jsonError(__('api.loyalty_reward_not_found'), 404);
        }
        $this->verifyRewardOwnership($reward);

        if ($reward['status'] !== 'pending') {
            jsonError(__('api.loyalty_reward_already_claimed'), 400);
        }

        if ($reward['reward_type'] !== 'free_voucher') {
            jsonError(__('api.loyalty_reward_type_unsupported'), 400);
        }

        if (!empty($reward['voucher_id'])) {
            jsonError(__('api.loyalty_voucher_already_generated'), 400);
        }

        $lastPurchaseStmt = $pdo->prepare("
            SELECT profile_id FROM loyalty_purchases
            WHERE customer_id = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $lastPurchaseStmt->execute([$reward['customer_id']]);
        $lastPurchase = $lastPurchaseStmt->fetch();

        $profileId = null;
        if ($lastPurchase && $lastPurchase['profile_id']) {
            $profileId = $lastPurchase['profile_id'];
        }

        $profile = null;
        if ($profileId) {
            $profile = $this->db->getProfileById($profileId);
        }

        if (!$profile) {
            $adminId = $this->getAdminId();
            $sql = "SELECT * FROM profiles WHERE is_active = 1";
            $sqlParams = [];
            if ($adminId !== null) {
                $sql .= " AND admin_id = ?";
                $sqlParams[] = $adminId;
            }
            $sql .= " ORDER BY price LIMIT 1";
            $fallbackStmt = $pdo->prepare($sql);
            $fallbackStmt->execute($sqlParams);
            $profile = $fallbackStmt->fetch();
        }

        if (!$profile) {
            jsonError(__('api.no_profile_available'), 500);
        }

        $voucherCode = 'BONUS-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $voucherPassword = $voucherCode;

        try {
            $validFrom = null;
            $validUntil = null;
            if (!empty($profile['validity']) && $profile['validity'] > 0) {
                $validFrom = date('Y-m-d H:i:s');
                $validUntil = date('Y-m-d H:i:s', time() + (int)$profile['validity']);
            }

            $voucherId = $this->db->createVoucher([
                'username' => $voucherCode,
                'password' => $voucherPassword,
                'time_limit' => $profile['time_limit'],
                'data_limit' => $profile['data_limit'],
                'download_speed' => $profile['download_speed'],
                'upload_speed' => $profile['upload_speed'],
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'simultaneous_use' => $profile['simultaneous_use'] ?? 1,
                'price' => 0,
                'profile_id' => $profile['id'],
                'batch_id' => 'LOYALTY_' . date('Ymd'),
                'notes' => 'Récompense fidélité pour ' . $reward['phone']
            ]);

            $updateStmt = $pdo->prepare("
                UPDATE loyalty_rewards SET
                    voucher_id = ?,
                    voucher_code = ?,
                    profile_name = ?,
                    status = 'claimed',
                    claimed_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$voucherId, $voucherCode, $profile['name'], $id]);

            $pdo->prepare("UPDATE loyalty_customers SET rewards_earned = rewards_earned + 1, last_reward_at = NOW() WHERE id = ?")
                ->execute([$reward['customer_id']]);

            jsonSuccess([
                'voucher_code' => $voucherCode,
                'voucher_password' => $voucherPassword,
                'profile_name' => $profile['name'],
                'customer_phone' => $reward['phone']
            ], __('api.loyalty_voucher_generated'));

        } catch (Exception $e) {
            jsonError(__('api.loyalty_voucher_generation_error') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/loyalty/rewards/{id}
     */
    public function deleteReward(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM loyalty_rewards WHERE id = ?");
        $stmt->execute([$id]);
        $reward = $stmt->fetch();
        if (!$reward) {
            jsonError(__('api.loyalty_reward_not_found'), 404);
        }
        $this->verifyRewardOwnership($reward);

        $pdo->prepare("DELETE FROM loyalty_rewards WHERE id = ?")->execute([$id]);
        jsonSuccess(null, __('api.loyalty_reward_deleted'));
    }

    // ==========================================
    // Statistiques
    // ==========================================

    /**
     * GET /api/loyalty/stats
     */
    public function stats(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $adminFilter = '';
        $adminParams = [];
        if ($adminId !== null) {
            $adminFilter = ' WHERE admin_id = ?';
            $adminParams = [$adminId];
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_customers" . $adminFilter);
        $stmt->execute($adminParams);
        $totalCustomers = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_purchases), 0) FROM loyalty_customers" . $adminFilter);
        $stmt->execute($adminParams);
        $totalPurchases = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_spent), 0) FROM loyalty_customers" . $adminFilter);
        $stmt->execute($adminParams);
        $totalSpent = (float)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_rewards WHERE status = 'claimed'" . ($adminId !== null ? ' AND admin_id = ?' : ''));
        $stmt->execute($adminParams);
        $totalRewardsGiven = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_rewards WHERE status = 'pending'" . ($adminId !== null ? ' AND admin_id = ?' : ''));
        $stmt->execute($adminParams);
        $pendingRewards = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loyalty_rules WHERE is_active = 1" . ($adminId !== null ? ' AND admin_id = ?' : ''));
        $stmt->execute($adminParams);
        $activeRules = (int)$stmt->fetchColumn();

        $stats = [
            'total_customers' => $totalCustomers,
            'total_purchases' => $totalPurchases,
            'total_spent' => $totalSpent,
            'total_rewards_given' => $totalRewardsGiven,
            'pending_rewards' => $pendingRewards,
            'active_rules' => $activeRules,
        ];

        $stmt = $pdo->prepare("
            SELECT phone, customer_name, total_purchases, total_spent, rewards_earned
            FROM loyalty_customers
            " . $adminFilter . "
            ORDER BY total_purchases DESC
            LIMIT 5
        ");
        $stmt->execute($adminParams);
        $stats['top_customers'] = $stmt->fetchAll();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM loyalty_purchases
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            " . ($adminId !== null ? ' AND admin_id = ?' : '') . "
        ");
        $stmt->execute($adminParams);
        $stats['recent_purchases'] = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM loyalty_rewards
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status = 'claimed'
            " . ($adminId !== null ? ' AND admin_id = ?' : '') . "
        ");
        $stmt->execute($adminParams);
        $stats['recent_rewards'] = (int)$stmt->fetchColumn();

        jsonSuccess($stats);
    }

    // ==========================================
    // Méthodes Utilitaires
    // ==========================================

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }
        return $phone;
    }

    private function calculateProgress(array $customer): array
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "SELECT * FROM loyalty_rules WHERE is_active = 1 AND rule_type = 'purchase_count'";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY threshold_value LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rule = $stmt->fetch();

        if (!$rule) {
            return ['has_active_rule' => false];
        }

        $purchasesSinceLastReward = $customer['total_purchases'] % $rule['threshold_value'];
        $remaining = $rule['threshold_value'] - $purchasesSinceLastReward;
        $percentage = ($purchasesSinceLastReward / $rule['threshold_value']) * 100;

        return [
            'has_active_rule' => true,
            'rule_name' => $rule['name'],
            'threshold' => $rule['threshold_value'],
            'current' => $purchasesSinceLastReward,
            'remaining' => $remaining,
            'percentage' => round($percentage, 1)
        ];
    }

    /**
     * Enregistrer un achat et vérifier les récompenses
     */
    public function recordPurchase(string $phone, string $transactionId, float $amount, ?int $profileId, ?string $profileName): array
    {
        $phone = $this->normalizePhone($phone);
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Créer ou récupérer le client scoped par admin
        $sql = "SELECT * FROM loyalty_customers WHERE phone = ?";
        $sqlParams = [$phone];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $sqlParams[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($sqlParams);
        $customer = $stmt->fetch();

        if (!$customer) {
            $insertStmt = $pdo->prepare("
                INSERT INTO loyalty_customers (phone, total_purchases, total_spent, first_purchase_at, last_purchase_at, admin_id)
                VALUES (?, 1, ?, NOW(), NOW(), ?)
            ");
            $insertStmt->execute([$phone, $amount, $adminId]);
            $customerId = $pdo->lastInsertId();

            $stmt->execute($sqlParams);
            $customer = $stmt->fetch();
        } else {
            $customerId = $customer['id'];
            $updateStmt = $pdo->prepare("
                UPDATE loyalty_customers SET
                    total_purchases = total_purchases + 1,
                    total_spent = total_spent + ?,
                    last_purchase_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$amount, $customerId]);

            $stmt->execute($sqlParams);
            $customer = $stmt->fetch();
        }

        // Enregistrer l'achat avec admin_id
        $purchaseStmt = $pdo->prepare("
            INSERT INTO loyalty_purchases (customer_id, transaction_id, amount, profile_id, profile_name, points_earned, admin_id)
            VALUES (?, ?, ?, ?, ?, 1, ?)
        ");
        $purchaseStmt->execute([$customerId, $transactionId, $amount, $profileId, $profileName, $adminId]);

        $pdo->prepare("UPDATE loyalty_customers SET points_earned = points_earned + 1, points_balance = points_balance + 1 WHERE id = ?")
            ->execute([$customerId]);

        $reward = $this->checkForReward($customerId, $customer['total_purchases'] + 1);

        return [
            'customer_id' => $customerId,
            'total_purchases' => $customer['total_purchases'] + 1,
            'reward_earned' => $reward
        ];
    }

    /**
     * Vérifier si un client a gagné une récompense
     */
    private function checkForReward(int $customerId, int $totalPurchases): ?array
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT lr.*, p.name as profile_name, p.time_limit, p.data_limit,
                   p.download_speed, p.upload_speed, p.validity, p.simultaneous_use
            FROM loyalty_rules lr
            LEFT JOIN profiles p ON lr.reward_profile_id = p.id
            WHERE lr.is_active = 1
            AND lr.rule_type = 'purchase_count'
            AND (lr.valid_from IS NULL OR lr.valid_from <= NOW())
            AND (lr.valid_until IS NULL OR lr.valid_until >= NOW())
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND lr.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY lr.threshold_value";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rules = $stmt->fetchAll();

        foreach ($rules as $rule) {
            if ($totalPurchases > 0 && $totalPurchases % $rule['threshold_value'] === 0) {
                if ($rule['max_rewards_per_customer']) {
                    $countStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM loyalty_rewards
                        WHERE customer_id = ? AND rule_id = ?
                    ");
                    $countStmt->execute([$customerId, $rule['id']]);
                    if ($countStmt->fetchColumn() >= $rule['max_rewards_per_customer']) {
                        continue;
                    }
                }

                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                $voucherCode = null;
                $voucherId = null;
                $profileName = null;
                $status = 'pending';

                if ($rule['reward_type'] === 'free_voucher') {
                    // Déterminer le profil à utiliser
                    $rewardProfileId = $rule['reward_profile_id'];
                    $rewardProfileName = $rule['profile_name'];
                    $rewardProfile = null;

                    if (!empty($rewardProfileId)) {
                        // Profil spécifique configuré dans la règle
                        $rewardProfile = [
                            'time_limit' => $rule['time_limit'],
                            'data_limit' => $rule['data_limit'],
                            'download_speed' => $rule['download_speed'],
                            'upload_speed' => $rule['upload_speed'],
                            'validity' => $rule['validity'],
                            'simultaneous_use' => $rule['simultaneous_use'],
                        ];
                    } else {
                        // Même profil que le dernier achat du client
                        $lastPurchaseStmt = $pdo->prepare("
                            SELECT lp.profile_id, lp.profile_name
                            FROM loyalty_purchases lp
                            WHERE lp.customer_id = ?
                            ORDER BY lp.created_at DESC LIMIT 1
                        ");
                        $lastPurchaseStmt->execute([$customerId]);
                        $lastPurchase = $lastPurchaseStmt->fetch();

                        if ($lastPurchase && !empty($lastPurchase['profile_id'])) {
                            $rewardProfileId = $lastPurchase['profile_id'];
                            $profile = $this->db->getProfileById($rewardProfileId);
                            if ($profile) {
                                $rewardProfileName = $profile['name'];
                                $rewardProfile = $profile;
                            }
                        }
                    }

                    if ($rewardProfile && $rewardProfileId) {
                        $voucherCode = 'BONUS-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
                        $profileName = $rewardProfileName;

                        $validFrom = null;
                        $validUntil = null;
                        $validity = $rewardProfile['validity'] ?? null;
                        if (!empty($validity) && $validity > 0) {
                            $validFrom = date('Y-m-d H:i:s');
                            $validUntil = date('Y-m-d H:i:s', time() + (int)$validity);
                        }

                        $voucherId = $this->db->createVoucher([
                            'username' => $voucherCode,
                            'password' => $voucherCode,
                            'time_limit' => $rewardProfile['time_limit'],
                            'data_limit' => $rewardProfile['data_limit'],
                            'download_speed' => $rewardProfile['download_speed'],
                            'upload_speed' => $rewardProfile['upload_speed'],
                            'valid_from' => $validFrom,
                            'valid_until' => $validUntil,
                            'simultaneous_use' => $rewardProfile['simultaneous_use'] ?? 1,
                            'price' => 0,
                            'profile_id' => $rewardProfileId,
                            'batch_id' => 'LOYALTY_' . date('Ymd')
                        ]);

                        $stmt = $pdo->prepare("
                            UPDATE vouchers SET
                                payment_method = 'free',
                                sale_amount = 0,
                                notes = CONCAT(IFNULL(notes, ''), ' [LOYALTY_BONUS]')
                            WHERE id = ?
                        ");
                        $stmt->execute([$voucherId]);
                    }
                }

                $insertStmt = $pdo->prepare("
                    INSERT INTO loyalty_rewards
                    (customer_id, rule_id, reward_type, reward_value, status, expires_at, voucher_id, voucher_code, profile_name, admin_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insertStmt->execute([
                    $customerId,
                    $rule['id'],
                    $rule['reward_type'],
                    $rule['reward_value'],
                    $status,
                    $expiresAt,
                    $voucherId,
                    $voucherCode,
                    $profileName,
                    $adminId
                ]);

                return [
                    'rule_name' => $rule['name'],
                    'reward_type' => $rule['reward_type'],
                    'voucher_code' => $voucherCode,
                    'profile_name' => $profileName,
                    'message' => 'Félicitations! Vous avez gagné une récompense: ' . $rule['name'] . ($voucherCode ? ' - Code: ' . $voucherCode : '')
                ];
            }
        }

        return null;
    }

    /**
     * POST /api/loyalty/import
     */
    public function importTransactions(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT t.id, t.customer_phone, t.amount, t.profile_id, p.name as profile_name, t.created_at
            FROM payment_transactions t
            LEFT JOIN profiles p ON t.profile_id = p.id
            WHERE t.status = 'completed'
              AND t.customer_phone IS NOT NULL
              AND t.customer_phone != ''
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND t.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY t.created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        $imported = 0;
        $skipped = 0;
        $customersCreated = 0;
        $rewardsCreated = 0;

        foreach ($transactions as $transaction) {
            $phone = $this->normalizePhone($transaction['customer_phone']);

            $checkStmt = $pdo->prepare("SELECT id FROM loyalty_purchases WHERE transaction_id = ?");
            $checkStmt->execute([$transaction['id']]);
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }

            // Créer ou récupérer le client scoped par admin
            $custSql = "SELECT * FROM loyalty_customers WHERE phone = ?";
            $custParams = [$phone];
            if ($adminId !== null) {
                $custSql .= " AND admin_id = ?";
                $custParams[] = $adminId;
            }
            $customerStmt = $pdo->prepare($custSql);
            $customerStmt->execute($custParams);
            $customer = $customerStmt->fetch();

            if (!$customer) {
                $insertCustomer = $pdo->prepare("
                    INSERT INTO loyalty_customers (phone, total_purchases, total_spent, points_earned, points_balance, first_purchase_at, last_purchase_at, admin_id)
                    VALUES (?, 0, 0, 0, 0, ?, ?, ?)
                ");
                $insertCustomer->execute([$phone, $transaction['created_at'], $transaction['created_at'], $adminId]);
                $customerId = $pdo->lastInsertId();
                $customersCreated++;

                $customerStmt->execute($custParams);
                $customer = $customerStmt->fetch();
            } else {
                $customerId = $customer['id'];
            }

            $purchaseStmt = $pdo->prepare("
                INSERT INTO loyalty_purchases (customer_id, transaction_id, amount, profile_id, profile_name, points_earned, created_at, admin_id)
                VALUES (?, ?, ?, ?, ?, 1, ?, ?)
            ");
            $purchaseStmt->execute([
                $customerId,
                $transaction['id'],
                $transaction['amount'],
                $transaction['profile_id'],
                $transaction['profile_name'],
                $transaction['created_at'],
                $adminId
            ]);

            $updateCustomer = $pdo->prepare("
                UPDATE loyalty_customers SET
                    total_purchases = total_purchases + 1,
                    total_spent = total_spent + ?,
                    points_earned = points_earned + 1,
                    points_balance = points_balance + 1,
                    last_purchase_at = ?
                WHERE id = ?
            ");
            $updateCustomer->execute([$transaction['amount'], $transaction['created_at'], $customerId]);

            $customerStmt->execute($custParams);
            $customer = $customerStmt->fetch();

            $reward = $this->checkForReward($customerId, (int)$customer['total_purchases']);
            if ($reward) {
                $rewardsCreated++;
            }

            $imported++;
        }

        $vouchersGenerated = $this->generatePendingVouchersInternal();

        jsonSuccess([
            'imported' => $imported,
            'skipped' => $skipped,
            'customers_created' => $customersCreated,
            'rewards_created' => $rewardsCreated,
            'vouchers_generated' => $vouchersGenerated
        ], __('api.loyalty_import_completed'));
    }

    /**
     * POST /api/loyalty/sync-vouchers
     */
    public function syncFromVouchers(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT v.id, v.username, v.price, v.profile_id, p.name as profile_name, v.created_at,
                   COALESCE(t.customer_phone, NULL) as customer_phone
            FROM vouchers v
            LEFT JOIN profiles p ON v.profile_id = p.id
            LEFT JOIN payment_transactions t ON t.voucher_id = v.id
            WHERE v.price > 0
              AND v.status != 'unused'
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND v.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY v.created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $vouchers = $stmt->fetchAll();
        $synced = 0;
        $skipped = 0;

        foreach ($vouchers as $voucher) {
            if (empty($voucher['customer_phone'])) {
                $skipped++;
                continue;
            }

            $phone = $this->normalizePhone($voucher['customer_phone']);
            $transactionId = 'VOUCHER-' . $voucher['id'];

            $checkStmt = $pdo->prepare("SELECT id FROM loyalty_purchases WHERE transaction_id = ?");
            $checkStmt->execute([$transactionId]);
            if ($checkStmt->fetch()) {
                $skipped++;
                continue;
            }

            // Créer ou récupérer le client scoped par admin
            $custSql = "SELECT id FROM loyalty_customers WHERE phone = ?";
            $custParams = [$phone];
            if ($adminId !== null) {
                $custSql .= " AND admin_id = ?";
                $custParams[] = $adminId;
            }
            $customerStmt = $pdo->prepare($custSql);
            $customerStmt->execute($custParams);
            $customer = $customerStmt->fetch();

            if (!$customer) {
                $insertCustomer = $pdo->prepare("
                    INSERT INTO loyalty_customers (phone, total_purchases, total_spent, points_earned, points_balance, first_purchase_at, last_purchase_at, admin_id)
                    VALUES (?, 1, ?, 1, 1, ?, ?, ?)
                ");
                $insertCustomer->execute([$phone, $voucher['price'], $voucher['created_at'], $voucher['created_at'], $adminId]);
                $customerId = $pdo->lastInsertId();
            } else {
                $customerId = $customer['id'];
                $pdo->prepare("
                    UPDATE loyalty_customers SET
                        total_purchases = total_purchases + 1,
                        total_spent = total_spent + ?,
                        points_earned = points_earned + 1,
                        points_balance = points_balance + 1,
                        last_purchase_at = ?
                    WHERE id = ?
                ")->execute([$voucher['price'], $voucher['created_at'], $customerId]);
            }

            $pdo->prepare("
                INSERT INTO loyalty_purchases (customer_id, transaction_id, amount, profile_id, profile_name, points_earned, created_at, admin_id)
                VALUES (?, ?, ?, ?, ?, 1, ?, ?)
            ")->execute([
                $customerId,
                $transactionId,
                $voucher['price'],
                $voucher['profile_id'],
                $voucher['profile_name'],
                $voucher['created_at'],
                $adminId
            ]);

            $synced++;
        }

        jsonSuccess([
            'synced' => $synced,
            'skipped' => $skipped
        ], __('api.loyalty_sync_completed'));
    }

    /**
     * POST /api/loyalty/generate-pending-vouchers
     */
    public function generatePendingVouchers(): void
    {
        $generated = $this->generatePendingVouchersInternal();
        jsonSuccess([
            'generated' => $generated
        ], __('api.loyalty_vouchers_generation_completed'));
    }

    private function generatePendingVouchersInternal(): int
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "
            SELECT lr.*, lc.phone, lc.customer_name,
                   rule.reward_profile_id, p.name as profile_name, p.time_limit, p.data_limit,
                   p.download_speed, p.upload_speed, p.validity, p.simultaneous_use
            FROM loyalty_rewards lr
            JOIN loyalty_customers lc ON lr.customer_id = lc.id
            JOIN loyalty_rules rule ON lr.rule_id = rule.id
            LEFT JOIN profiles p ON rule.reward_profile_id = p.id
            WHERE lr.status = 'pending'
              AND lr.voucher_code IS NULL
              AND lr.reward_type = 'free_voucher'
              AND (lr.expires_at IS NULL OR lr.expires_at > NOW())
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND lr.admin_id = ?";
            $params[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rewards = $stmt->fetchAll();
        $generated = 0;

        foreach ($rewards as $reward) {
            // Déterminer le profil à utiliser
            $rewardProfileId = $reward['reward_profile_id'];
            $rewardProfileName = $reward['profile_name'];
            $rewardProfile = null;

            if (!empty($rewardProfileId)) {
                // Profil spécifique configuré dans la règle
                $rewardProfile = [
                    'time_limit' => $reward['time_limit'],
                    'data_limit' => $reward['data_limit'],
                    'download_speed' => $reward['download_speed'],
                    'upload_speed' => $reward['upload_speed'],
                    'validity' => $reward['validity'],
                    'simultaneous_use' => $reward['simultaneous_use'],
                ];
            } else {
                // Même profil que le dernier achat du client
                $lastPurchaseStmt = $pdo->prepare("
                    SELECT lp.profile_id, lp.profile_name
                    FROM loyalty_purchases lp
                    WHERE lp.customer_id = ?
                    ORDER BY lp.created_at DESC LIMIT 1
                ");
                $lastPurchaseStmt->execute([$reward['customer_id']]);
                $lastPurchase = $lastPurchaseStmt->fetch();

                if ($lastPurchase && !empty($lastPurchase['profile_id'])) {
                    $rewardProfileId = $lastPurchase['profile_id'];
                    $profile = $this->db->getProfileById($rewardProfileId);
                    if ($profile) {
                        $rewardProfileName = $profile['name'];
                        $rewardProfile = $profile;
                    }
                }
            }

            if (!$rewardProfile || !$rewardProfileId) {
                continue;
            }

            $voucherCode = 'BONUS-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));

            try {
                $validFrom = null;
                $validUntil = null;
                $validity = $rewardProfile['validity'] ?? null;
                if (!empty($validity) && $validity > 0) {
                    $validFrom = date('Y-m-d H:i:s');
                    $validUntil = date('Y-m-d H:i:s', time() + (int)$validity);
                }

                $voucherId = $this->db->createVoucher([
                    'username' => $voucherCode,
                    'password' => $voucherCode,
                    'time_limit' => $rewardProfile['time_limit'],
                    'data_limit' => $rewardProfile['data_limit'],
                    'download_speed' => $rewardProfile['download_speed'],
                    'upload_speed' => $rewardProfile['upload_speed'],
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'simultaneous_use' => $rewardProfile['simultaneous_use'] ?? 1,
                    'price' => 0,
                    'profile_id' => $rewardProfileId,
                    'batch_id' => 'LOYALTY_' . date('Ymd'),
                    'notes' => 'Récompense fidélité pour ' . $reward['phone']
                ]);

                $updateVoucher = $pdo->prepare("
                    UPDATE vouchers SET
                        payment_method = 'free',
                        sale_amount = 0,
                        notes = CONCAT(IFNULL(notes, ''), ' [LOYALTY_BONUS]')
                    WHERE id = ?
                ");
                $updateVoucher->execute([$voucherId]);

                $updateReward = $pdo->prepare("
                    UPDATE loyalty_rewards SET
                        voucher_id = ?,
                        voucher_code = ?,
                        profile_name = ?
                    WHERE id = ?
                ");
                $updateReward->execute([
                    $voucherId,
                    $voucherCode,
                    $rewardProfileName,
                    $reward['id']
                ]);

                $generated++;

            } catch (Exception $e) {
                error_log('Error generating voucher for reward ' . $reward['id'] . ': ' . $e->getMessage());
            }
        }

        return $generated;
    }

    /**
     * POST /api/loyalty/reset
     */
    public function resetAndReimport(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId !== null) {
            // Supprimer uniquement les données de cet admin
            $pdo->prepare("UPDATE vouchers SET deleted_at = NOW() WHERE (batch_id LIKE 'LOYALTY_%' OR notes LIKE '%[LOYALTY_BONUS]%') AND admin_id = ? AND deleted_at IS NULL")->execute([$adminId]);
            $pdo->prepare("DELETE FROM loyalty_rewards WHERE admin_id = ?")->execute([$adminId]);
            $pdo->prepare("DELETE FROM loyalty_purchases WHERE admin_id = ?")->execute([$adminId]);
            $pdo->prepare("DELETE FROM loyalty_customers WHERE admin_id = ?")->execute([$adminId]);
        } else {
            $pdo->exec("UPDATE vouchers SET deleted_at = NOW() WHERE (batch_id LIKE 'LOYALTY_%' OR notes LIKE '%[LOYALTY_BONUS]%') AND deleted_at IS NULL");
            $pdo->exec("DELETE FROM loyalty_rewards");
            $pdo->exec("DELETE FROM loyalty_purchases");
            $pdo->exec("DELETE FROM loyalty_customers");
        }

        // Réimporter les transactions de cet admin
        $sql = "
            SELECT t.id, t.customer_phone, t.amount, t.profile_id, p.name as profile_name, t.created_at
            FROM payment_transactions t
            LEFT JOIN profiles p ON t.profile_id = p.id
            WHERE t.status = 'completed'
              AND t.customer_phone IS NOT NULL
              AND t.customer_phone != ''
        ";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND t.admin_id = ?";
            $params[] = $adminId;
        }
        $sql .= " ORDER BY t.created_at ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();
        $imported = 0;
        $customersCreated = 0;
        $rewardsCreated = 0;

        foreach ($transactions as $transaction) {
            $phone = $this->normalizePhone($transaction['customer_phone']);

            $custSql = "SELECT * FROM loyalty_customers WHERE phone = ?";
            $custParams = [$phone];
            if ($adminId !== null) {
                $custSql .= " AND admin_id = ?";
                $custParams[] = $adminId;
            }
            $customerStmt = $pdo->prepare($custSql);
            $customerStmt->execute($custParams);
            $customer = $customerStmt->fetch();

            if (!$customer) {
                $insertCustomer = $pdo->prepare("
                    INSERT INTO loyalty_customers (phone, total_purchases, total_spent, points_earned, points_balance, first_purchase_at, last_purchase_at, admin_id)
                    VALUES (?, 0, 0, 0, 0, ?, ?, ?)
                ");
                $insertCustomer->execute([$phone, $transaction['created_at'], $transaction['created_at'], $adminId]);
                $customerId = $pdo->lastInsertId();
                $customersCreated++;

                $customerStmt->execute($custParams);
                $customer = $customerStmt->fetch();
            } else {
                $customerId = $customer['id'];
            }

            $purchaseStmt = $pdo->prepare("
                INSERT INTO loyalty_purchases (customer_id, transaction_id, amount, profile_id, profile_name, points_earned, created_at, admin_id)
                VALUES (?, ?, ?, ?, ?, 1, ?, ?)
            ");
            $purchaseStmt->execute([
                $customerId,
                $transaction['id'],
                $transaction['amount'],
                $transaction['profile_id'],
                $transaction['profile_name'],
                $transaction['created_at'],
                $adminId
            ]);

            $updateCustomer = $pdo->prepare("
                UPDATE loyalty_customers SET
                    total_purchases = total_purchases + 1,
                    total_spent = total_spent + ?,
                    points_earned = points_earned + 1,
                    points_balance = points_balance + 1,
                    last_purchase_at = ?
                WHERE id = ?
            ");
            $updateCustomer->execute([$transaction['amount'], $transaction['created_at'], $customerId]);

            $customerStmt->execute($custParams);
            $customer = $customerStmt->fetch();

            $reward = $this->checkForReward($customerId, (int)$customer['total_purchases']);
            if ($reward) {
                $rewardsCreated++;
            }

            $imported++;
        }

        jsonSuccess([
            'imported' => $imported,
            'customers_created' => $customersCreated,
            'rewards_created' => $rewardsCreated
        ], __('api.loyalty_reset_completed'));
    }

    // ==========================================
    // Enregistrement automatique
    // ==========================================

    /**
     * GET /api/loyalty/auto-record
     */
    public function getAutoRecordStatus(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "SELECT setting_value FROM settings WHERE setting_key = 'loyalty_auto_record'";
        $params = [];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $value = $stmt->fetchColumn();

        jsonSuccess([
            'enabled' => $value === '1'
        ]);
    }

    /**
     * POST /api/loyalty/auto-record
     */
    public function toggleAutoRecord(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $data = getJsonBody();
        $enabled = !empty($data['enabled']) ? '1' : '0';

        // Upsert the setting
        $checkSql = "SELECT id FROM settings WHERE setting_key = 'loyalty_auto_record'";
        $checkParams = [];
        if ($adminId !== null) {
            $checkSql .= " AND admin_id = ?";
            $checkParams[] = $adminId;
        }
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute($checkParams);

        if ($checkStmt->fetch()) {
            $updateSql = "UPDATE settings SET setting_value = ? WHERE setting_key = 'loyalty_auto_record'";
            $updateParams = [$enabled];
            if ($adminId !== null) {
                $updateSql .= " AND admin_id = ?";
                $updateParams[] = $adminId;
            }
            $pdo->prepare($updateSql)->execute($updateParams);
        } else {
            $pdo->prepare(
                "INSERT INTO settings (setting_key, setting_value, description, admin_id) VALUES ('loyalty_auto_record', ?, ?, ?)"
            )->execute([$enabled, 'Enregistrement automatique des transactions hotspot pour la fidélité', $adminId]);
        }

        $label = $enabled === '1' ? __('api.loyalty_auto_record_enabled') : __('api.loyalty_auto_record_disabled');
        jsonSuccess(['enabled' => $enabled === '1'], $label);
    }
}
