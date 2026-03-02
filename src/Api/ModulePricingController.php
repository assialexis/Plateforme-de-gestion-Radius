<?php
/**
 * Controller API Tarification des Modules (SuperAdmin)
 * Définir le coût en crédits pour chaque module
 */

class ModulePricingController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function requireSuperAdmin(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        if (!$user || !$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => __('auth.superadmin_required') ?? 'Accès réservé au SuperAdmin']);
            exit;
        }
    }

    /**
     * GET /superadmin/module-pricing
     */
    public function listPricing(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->query("
            SELECT mp.*,
                   (SELECT COUNT(*) FROM admin_module_subscriptions ams WHERE ams.module_code = mp.module_code AND ams.is_paid = 1) as active_subscriptions
            FROM module_pricing mp
            ORDER BY mp.module_code
        ");
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Taux de change et config
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('credit_exchange_rate', 'credit_currency', 'credit_system_enabled', 'free_initial_credits', 'nas_creation_cost', 'nas_validity_days', 'sms_credit_cost_fcfa', 'sms_credit_enabled', 'platform_sms_provider', 'platform_sms_config')");
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'modules' => $modules,
                'settings' => $settings
            ]
        ]);
    }

    /**
     * PUT /superadmin/module-pricing/{code}
     */
    public function updatePricing(array $params): void
    {
        $this->requireSuperAdmin();
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError('Code module requis', 400);
            return;
        }

        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        // Vérifier que le module existe dans la table pricing
        $stmt = $pdo->prepare("SELECT id FROM module_pricing WHERE module_code = ?");
        $stmt->execute([$code]);
        if (!$stmt->fetch()) {
            jsonError('Module non trouvé', 404);
            return;
        }

        $updates = [];
        $values = [];

        if (isset($data['price_credits'])) {
            $updates[] = 'price_credits = ?';
            $values[] = max(0, (float)$data['price_credits']);
        }
        if (isset($data['billing_type']) && in_array($data['billing_type'], ['one_time', 'monthly'])) {
            $updates[] = 'billing_type = ?';
            $values[] = $data['billing_type'];
        }
        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $values[] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['description'])) {
            $updates[] = 'description = ?';
            $values[] = $data['description'];
        }

        if (empty($updates)) {
            jsonError('Aucune donnée à mettre à jour', 400);
            return;
        }

        $values[] = $code;
        $stmt = $pdo->prepare("UPDATE module_pricing SET " . implode(', ', $updates) . " WHERE module_code = ?");
        $stmt->execute($values);

        echo json_encode(['success' => true, 'message' => __('superadmin.pricing_saved') ?? 'Tarification mise à jour']);
    }

    /**
     * PUT /superadmin/credit-settings
     */
    public function updateExchangeRate(): void
    {
        $this->requireSuperAdmin();
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $allowedKeys = ['credit_exchange_rate', 'credit_currency', 'credit_system_enabled', 'free_initial_credits', 'nas_creation_cost', 'nas_validity_days'];

        $stmt = $pdo->prepare("UPDATE global_settings SET setting_value = ? WHERE setting_key = ?");

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $stmt->execute([(string)$value, $key]);
            }
        }

        echo json_encode(['success' => true, 'message' => __('superadmin.settings_saved') ?? 'Paramètres mis à jour']);
    }

    /**
     * GET /superadmin/credit-stats
     */
    public function creditStats(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        // Total crédits en circulation
        $stmt = $pdo->query("SELECT COALESCE(SUM(credit_balance), 0) FROM users WHERE role = 'admin'");
        $totalCredits = (float)$stmt->fetchColumn();

        // Recharges ce mois
        $stmt = $pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM credit_transactions
            WHERE type = 'recharge' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
        ");
        $rechargesThisMonth = $stmt->fetch(PDO::FETCH_ASSOC);

        // Activations modules ce mois
        $stmt = $pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(ABS(amount)), 0) as total
            FROM credit_transactions
            WHERE type = 'module_activation' AND MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW())
        ");
        $activationsThisMonth = $stmt->fetch(PDO::FETCH_ASSOC);

        // Admins avec solde > 0
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND credit_balance > 0");
        $adminsWithCredits = (int)$stmt->fetchColumn();

        // Dernières transactions
        $stmt = $pdo->query("
            SELECT ct.*, u.username, u.full_name
            FROM credit_transactions ct
            JOIN users u ON ct.admin_id = u.id
            ORDER BY ct.created_at DESC
            LIMIT 10
        ");
        $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_credits_in_system' => $totalCredits,
                'admins_with_credits' => $adminsWithCredits,
                'recharges_this_month' => [
                    'count' => (int)$rechargesThisMonth['count'],
                    'total' => (float)$rechargesThisMonth['total']
                ],
                'activations_this_month' => [
                    'count' => (int)$activationsThisMonth['count'],
                    'total' => (float)$activationsThisMonth['total']
                ],
                'recent_transactions' => $recentTransactions
            ]
        ]);
    }

    /**
     * GET /superadmin/credit-transactions
     * Liste complète des transactions crédits avec filtres et pagination
     */
    public function listTransactions(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 25)));
        $type = $_GET['type'] ?? '';
        $adminId = $_GET['admin_id'] ?? '';
        $search = trim($_GET['search'] ?? '');
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $where = [];
        $params = [];

        if ($type && in_array($type, ['recharge', 'module_activation', 'module_renewal', 'adjustment', 'refund'])) {
            $where[] = 'ct.type = ?';
            $params[] = $type;
        }

        if ($adminId) {
            $where[] = 'ct.admin_id = ?';
            $params[] = (int)$adminId;
        }

        if ($search) {
            $where[] = '(u.username LIKE ? OR u.full_name LIKE ? OR ct.description LIKE ? OR ct.reference_id LIKE ?)';
            $like = "%{$search}%";
            $params = array_merge($params, [$like, $like, $like, $like]);
        }

        if ($dateFrom) {
            $where[] = 'ct.created_at >= ?';
            $params[] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo) {
            $where[] = 'ct.created_at <= ?';
            $params[] = $dateTo . ' 23:59:59';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Count total
        $countSql = "SELECT COUNT(*) FROM credit_transactions ct JOIN users u ON ct.admin_id = u.id {$whereClause}";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch data
        $offset = ($page - 1) * $perPage;
        $sql = "
            SELECT ct.*, u.username, u.full_name, u.email,
                   cb.username as created_by_username
            FROM credit_transactions ct
            JOIN users u ON ct.admin_id = u.id
            LEFT JOIN users cb ON ct.created_by = cb.id
            {$whereClause}
            ORDER BY ct.created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats globales pour les filtres actifs
        $statsSql = "
            SELECT
                COALESCE(SUM(CASE WHEN ct.amount > 0 THEN ct.amount ELSE 0 END), 0) as total_credits_in,
                COALESCE(SUM(CASE WHEN ct.amount < 0 THEN ABS(ct.amount) ELSE 0 END), 0) as total_credits_out,
                COUNT(CASE WHEN ct.type = 'recharge' THEN 1 END) as recharge_count,
                COUNT(CASE WHEN ct.type = 'module_activation' THEN 1 END) as activation_count,
                COUNT(CASE WHEN ct.type = 'adjustment' THEN 1 END) as adjustment_count
            FROM credit_transactions ct
            JOIN users u ON ct.admin_id = u.id
            {$whereClause}
        ";
        $stmt = $pdo->prepare($statsSql);
        $stmt->execute($params);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Liste des admins pour le filtre
        $admins = $pdo->query("
            SELECT id, username, full_name FROM users WHERE role = 'admin' ORDER BY username
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
                'stats' => $stats,
                'admins' => $admins
            ]
        ]);
    }

    /**
     * Structure des champs de config attendus par chaque passerelle
     */
    private function getGatewayConfigSchema(): array
    {
        return [
            'fedapay' => ['account_name' => '', 'public_key' => '', 'secret_key' => ''],
            'cinetpay' => ['site_id' => '', 'api_key' => '', 'secret_key' => ''],
            'ligdicash' => ['api_key' => '', 'auth_token' => '', 'platform' => ''],
            'cryptomus' => ['merchant_uuid' => '', 'payment_key' => ''],
            'paygate_global' => ['auth_token' => ''],
            'feexpay' => ['shop_id' => '', 'api_key' => '', 'operator' => 'mtn'],
            'kkiapay' => ['public_key' => '', 'private_key' => '', 'secret' => ''],
            'paydunya' => ['master_key' => '', 'private_key' => '', 'token' => '', 'store_name' => ''],
            'yengapay' => ['groupe_id' => '', 'api_key' => '', 'project_id' => ''],
            'moneroo' => ['secret_key' => '', 'public_key' => ''],
        ];
    }

    /**
     * GET /superadmin/recharge-gateways
     * Liste des passerelles de recharge globales
     */
    public function listRechargeGateways(): void
    {
        $this->requireSuperAdmin();
        $gateways = $this->db->getGlobalRechargeGateways();
        $schemas = $this->getGatewayConfigSchema();

        // Remplir les clés manquantes dans config à partir du schéma
        $sensitiveKeys = ['secret_key', 'secret', 'api_key', 'private_key', 'auth_token', 'password', 'client_secret', 'payment_key'];
        foreach ($gateways as &$gw) {
            $code = $gw['gateway_code'];
            $schema = $schemas[$code] ?? [];
            // Fusionner : schéma + valeurs existantes, en ne gardant que les clés du schéma
            $existing = is_array($gw['config']) ? $gw['config'] : [];
            $gw['config'] = array_merge($schema, array_intersect_key($existing, $schema));
            // Masquer les clés sensibles
            foreach ($gw['config'] as $key => $value) {
                if (in_array($key, $sensitiveKeys) && !empty($value)) {
                    $gw['config'][$key] = '••••••••';
                }
            }
        }

        echo json_encode(['success' => true, 'data' => ['gateways' => $gateways]]);
    }

    /**
     * PUT /superadmin/recharge-gateways/{code}
     * Mettre à jour une passerelle de recharge
     */
    public function updateRechargeGateway(array $params): void
    {
        $this->requireSuperAdmin();
        $code = $params['code'] ?? '';
        if (empty($code)) {
            jsonError('Code passerelle requis', 400);
            return;
        }

        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        // Vérifier que la passerelle globale existe
        $stmt = $pdo->prepare("SELECT * FROM payment_gateways WHERE gateway_code = ? AND admin_id IS NULL");
        $stmt->execute([$code]);
        $gateway = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gateway) {
            jsonError('Passerelle non trouvée', 404);
            return;
        }

        $updates = [];
        $values = [];

        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $values[] = $data['is_active'] ? 1 : 0;
        }
        if (isset($data['is_sandbox'])) {
            $updates[] = 'is_sandbox = ?';
            $values[] = $data['is_sandbox'] ? 1 : 0;
        }
        if (isset($data['config']) && is_array($data['config'])) {
            // Fusionner avec la config existante (ne pas écraser les champs masqués)
            $currentConfig = json_decode($gateway['config'], true) ?? [];
            foreach ($data['config'] as $key => $value) {
                if ($value !== '••••••••') {
                    $currentConfig[$key] = $value;
                }
            }
            $updates[] = 'config = ?';
            $values[] = json_encode($currentConfig);
        }

        if (empty($updates)) {
            jsonError('Aucune donnée à mettre à jour', 400);
            return;
        }

        $values[] = $gateway['id'];
        $stmt = $pdo->prepare("UPDATE payment_gateways SET " . implode(', ', $updates) . " WHERE id = ?");
        $stmt->execute($values);

        echo json_encode(['success' => true, 'message' => __('superadmin.gateway_saved') ?? 'Passerelle mise à jour']);
    }

    /**
     * POST /superadmin/admins/{id}/adjust-credits
     */
    public function adjustCredits(array $params): void
    {
        $this->requireSuperAdmin();
        $adminId = (int)($params['id'] ?? 0);
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $amount = (float)($data['amount'] ?? 0);
        $reason = trim($data['reason'] ?? '');

        if ($amount == 0) {
            jsonError('Montant invalide', 400);
            return;
        }

        // Vérifier que l'admin existe
        $stmt = $pdo->prepare("SELECT id, credit_balance, username FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            jsonError('Admin non trouvé', 404);
            return;
        }

        $currentBalance = (float)$admin['credit_balance'];
        $newBalance = $currentBalance + $amount;

        if ($newBalance < 0) {
            jsonError('Le solde ne peut pas être négatif. Solde actuel: ' . $currentBalance, 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Mettre à jour le solde
            $stmt = $pdo->prepare("UPDATE users SET credit_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $adminId]);

            // Enregistrer la transaction
            $superAdminId = $this->auth->getUser()->getId();
            $description = $reason ?: ($amount > 0 ? 'Ajout manuel de crédits' : 'Retrait manuel de crédits');
            $stmt = $pdo->prepare("
                INSERT INTO credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description, created_by)
                VALUES (?, 'adjustment', ?, ?, 'manual', ?, ?, ?)
            ");
            $stmt->execute([$adminId, $amount, $newBalance, 'SA-' . $superAdminId, $description, $superAdminId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => __('superadmin.credits_adjusted') ?? 'Crédits ajustés avec succès',
                'data' => [
                    'previous_balance' => $currentBalance,
                    'adjustment' => $amount,
                    'new_balance' => $newBalance
                ]
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonError('Erreur lors de l\'ajustement: ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /superadmin/sms-credit-settings
     */
    public function updateSmsCreditSettings(): void
    {
        $this->requireSuperAdmin();
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $allowedKeys = ['sms_credit_cost_fcfa', 'sms_credit_enabled', 'platform_sms_provider', 'platform_sms_config'];
        $stmt = $pdo->prepare("INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $val = is_array($value) ? json_encode($value) : (string)$value;
                $stmt->execute([$key, $val]);
            }
        }

        echo json_encode(['success' => true, 'message' => __('superadmin.sms_settings_saved') ?? 'Paramètres SMS mis à jour']);
    }

    /**
     * GET /superadmin/platform-sms-balance
     */
    public function getPlatformSmsBalance(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        // Load platform SMS config from global_settings
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('platform_sms_provider', 'platform_sms_config')");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        $provider = $rows['platform_sms_provider'] ?? '';
        $config = json_decode($rows['platform_sms_config'] ?? '{}', true) ?: [];

        if (empty($provider)) {
            echo json_encode(['success' => false, 'message' => 'Aucun provider SMS configuré']);
            return;
        }

        if ($provider === 'nghcorp') {
            $apiKey = $config['api_key'] ?? '';
            $apiSecret = $config['api_secret'] ?? '';

            if (empty($apiKey) || empty($apiSecret)) {
                echo json_encode(['success' => false, 'message' => 'Clés API manquantes']);
                return;
            }

            $ch = curl_init('https://extranet.nghcorp.net/api/balance');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode(['api_key' => $apiKey, 'api_secret' => $apiSecret]),
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                echo json_encode(['success' => false, 'message' => 'Erreur connexion: ' . $curlError]);
                return;
            }

            $data = json_decode($response, true);

            if (($data['status'] ?? 0) == 200) {
                $rawBalance = $data['balance'] ?? 0;
                $balance = (float)str_replace(',', '', (string)$rawBalance);
                echo json_encode(['success' => true, 'data' => ['balance' => $balance, 'provider' => 'NGH Corp']]);
                return;
            }

            echo json_encode(['success' => false, 'message' => $data['status_desc'] ?? 'Erreur inconnue']);
            return;
        }

        echo json_encode(['success' => false, 'message' => 'Provider non supporté: ' . $provider]);
    }

    /**
     * POST /superadmin/admins/{id}/adjust-sms-credits
     */
    public function adjustSmsCredits(array $params): void
    {
        $this->requireSuperAdmin();
        $adminId = (int)($params['id'] ?? 0);
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        $amount = (float)($data['amount'] ?? 0);
        $reason = trim($data['reason'] ?? '');

        if ($amount == 0) {
            jsonError('Montant invalide', 400);
            return;
        }

        $stmt = $pdo->prepare("SELECT id, sms_credit_balance, username FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            jsonError('Admin non trouvé', 404);
            return;
        }

        $currentBalance = (float)$admin['sms_credit_balance'];
        $newBalance = $currentBalance + $amount;

        if ($newBalance < 0) {
            jsonError('Le solde CSMS ne peut pas être négatif. Solde actuel: ' . $currentBalance, 400);
            return;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE users SET sms_credit_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $adminId]);

            $superAdminId = $this->auth->getUser()->getId();
            $description = $reason ?: ($amount > 0 ? 'Ajout manuel de CSMS' : 'Retrait manuel de CSMS');
            $stmt = $pdo->prepare(
                "INSERT INTO sms_credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description, created_by)
                 VALUES (?, 'adjustment', ?, ?, 'manual', ?, ?, ?)"
            );
            $stmt->execute([$adminId, $amount, $newBalance, 'SA-' . $superAdminId, $description, $superAdminId]);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => __('superadmin.sms_credits_adjusted') ?? 'Crédits SMS ajustés avec succès',
                'data' => [
                    'previous_balance' => $currentBalance,
                    'adjustment' => $amount,
                    'new_balance' => $newBalance
                ]
            ]);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonError('Erreur lors de l\'ajustement: ' . $e->getMessage(), 500);
        }
    }
}
