<?php
/**
 * Controller API Crédits & Abonnements
 * Gestion du solde, recharges Mobile Money, historique
 */

class CreditController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): int
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        // Le superadmin n'a pas de solde crédits personnel
        if ($user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Le SuperAdmin n\'utilise pas le système de crédits']);
            exit;
        }
        $adminId = $this->auth->getAdminId();
        if (!$adminId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => __('auth.unauthorized') ?? 'Non autorisé']);
            exit;
        }
        return $adminId;
    }

    /**
     * GET /credits/balance
     */
    public function getBalance(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        // Solde actuel
        $stmt = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $balance = (float)$stmt->fetchColumn();

        // Recharges en attente
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM payment_transactions
            WHERE admin_id = ? AND transaction_type = 'credit_recharge' AND status = 'pending'
        ");
        $stmt->execute([$adminId]);
        $pending = $stmt->fetch(PDO::FETCH_ASSOC);

        // Taux de change
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_exchange_rate'");
        $stmt->execute();
        $exchangeRate = (float)($stmt->fetchColumn() ?: 100);

        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_currency'");
        $stmt->execute();
        $currency = $stmt->fetchColumn() ?: 'XOF';

        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'nas_creation_cost'");
        $stmt->execute();
        $nasCost = (float)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'nas_validity_days'");
        $stmt->execute();
        $nasValidityDays = (int)($stmt->fetchColumn() ?: 0);

        echo json_encode([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'pending_recharges' => (int)$pending['count'],
                'pending_amount' => (float)$pending['total'],
                'exchange_rate' => $exchangeRate,
                'currency' => $currency,
                'nas_creation_cost' => $nasCost,
                'nas_validity_days' => $nasValidityDays
            ]
        ]);
    }

    /**
     * GET /credits/transactions
     */
    public function getTransactions(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 20)));
        $type = $_GET['type'] ?? null;
        $offset = ($page - 1) * $perPage;

        $where = "WHERE admin_id = ?";
        $params = [$adminId];

        if ($type && in_array($type, ['recharge', 'module_activation', 'module_renewal', 'adjustment', 'refund'])) {
            $where .= " AND type = ?";
            $params[] = $type;
        }

        // Total
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM credit_transactions $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Transactions
        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare("
            SELECT id, type, amount, balance_after, reference_type, reference_id, description, created_at
            FROM credit_transactions
            $where
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    /**
     * POST /credits/recharge
     */
    public function initiateRecharge(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();
        $data = getJsonBody();

        $amount = (float)($data['amount'] ?? 0);
        $gatewayCode = $data['gateway_code'] ?? '';
        $phone = $data['phone'] ?? '';

        if ($amount <= 0) {
            jsonError(__('credits.invalid_amount') ?? 'Montant invalide', 400);
            return;
        }
        if (empty($gatewayCode)) {
            jsonError(__('credits.gateway_required') ?? 'Mode de paiement requis', 400);
            return;
        }

        // Vérifier que le système de crédits est actif
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_system_enabled'");
        $stmt->execute();
        if ($stmt->fetchColumn() !== '1') {
            jsonError(__('credits.system_disabled') ?? 'Le système de crédits est désactivé', 403);
            return;
        }

        // Calculer les crédits
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_exchange_rate'");
        $stmt->execute();
        $exchangeRate = (float)($stmt->fetchColumn() ?: 100);
        $credits = $amount / $exchangeRate;

        // Récupérer les infos admin pour le paiement
        $stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $adminInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        try {
            $config = require __DIR__ . '/../../config/config.php';
            require_once __DIR__ . '/../Payment/PaymentService.php';
            $paymentService = new PaymentService($this->db, $config);

            $result = $paymentService->initiateRechargePayment($gatewayCode, $amount, $adminId, [
                'phone' => $phone ?: ($adminInfo['phone'] ?? ''),
                'email' => $adminInfo['email'] ?? '',
                'name' => $adminInfo['full_name'] ?? ''
            ]);

            $result['credits_preview'] = round($credits, 2);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 500);
        }
    }

    /**
     * GET /credits/recharge/status
     */
    public function checkRechargeStatus(): void
    {
        $adminId = $this->getAdminId();
        $txnId = $_GET['txn'] ?? '';

        if (empty($txnId)) {
            jsonError('Transaction ID requis', 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            SELECT transaction_id, status, amount, currency, gateway_code, paid_at, created_at
            FROM payment_transactions
            WHERE transaction_id = ? AND admin_id = ? AND transaction_type = 'credit_recharge'
        ");
        $stmt->execute([$txnId, $adminId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$txn) {
            jsonError('Transaction non trouvée', 404);
            return;
        }

        echo json_encode(['success' => true, 'data' => $txn]);
    }

    /**
     * GET /credits/module-prices
     */
    public function getModulePrices(): void
    {
        $this->auth->requireAuth();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->query("
            SELECT module_code, price_credits, billing_type, description, is_active
            FROM module_pricing
            ORDER BY module_code
        ");
        $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => ['prices' => $prices]]);
    }

    /**
     * GET /credits/recharge-gateways
     * Passerelles actives pour la recharge (globales, gérées par SuperAdmin)
     */
    public function getRechargeGateways(): void
    {
        $this->auth->requireAuth();
        $gateways = $this->db->getActiveGlobalRechargeGateways();

        // Retirer la config (les admins n'ont pas besoin des clés API)
        foreach ($gateways as &$gw) {
            unset($gw['config']);
        }

        echo json_encode(['success' => true, 'data' => ['gateways' => $gateways]]);
    }
}
