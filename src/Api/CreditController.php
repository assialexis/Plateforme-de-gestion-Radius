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

        // Liste des recharges pending
        $stmt = $pdo->prepare("
            SELECT transaction_id, amount, currency, gateway_code, created_at
            FROM payment_transactions
            WHERE admin_id = ? AND transaction_type = 'credit_recharge' AND status = 'pending'
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute([$adminId]);
        $pendingList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => [
                'balance' => $balance,
                'pending_recharges' => (int)$pending['count'],
                'pending_amount' => (float)$pending['total'],
                'pending_list' => $pendingList,
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
     * POST /credits/recharge/verify
     * Vérifier manuellement le statut d'une recharge pending auprès de la passerelle
     */
    public function verifyRecharge(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();
        $data = getJsonBody();
        $txnId = $data['transaction_id'] ?? '';

        if (empty($txnId)) {
            jsonError('Transaction ID requis', 400);
            return;
        }

        // Récupérer la transaction pending
        $stmt = $pdo->prepare("
            SELECT * FROM payment_transactions
            WHERE transaction_id = ? AND admin_id = ? AND transaction_type = 'credit_recharge' AND status = 'pending'
        ");
        $stmt->execute([$txnId, $adminId]);
        $txn = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$txn) {
            jsonError('Transaction non trouvée ou déjà traitée', 404);
            return;
        }

        $config = require __DIR__ . '/../../config/config.php';
        require_once __DIR__ . '/../Payment/PaymentService.php';
        $paymentService = new PaymentService($this->db, $config);

        $gatewayCode = $txn['gateway_code'];
        $gatewayTransactionId = $txn['gateway_transaction_id'] ?? '';
        $verified = null;

        // Résoudre la config depuis les passerelles globales de recharge
        $gateway = $this->db->getGlobalGatewayByCode($gatewayCode);

        switch ($gatewayCode) {
            case 'fedapay':
                if ($gatewayTransactionId && $gateway) {
                    $apiKey = $gateway['config']['secret_key'] ?? $gateway['config']['api_key'] ?? '';
                    $apiUrl = ($gateway['is_sandbox'] ?? false)
                        ? 'https://sandbox-api.fedapay.com'
                        : 'https://api.fedapay.com';

                    $ch = curl_init($apiUrl . '/v1/transactions/' . $gatewayTransactionId);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($response, true);
                    $txData = $result['v1/transaction'] ?? $result['transaction'] ?? $result ?? [];
                    $status = $txData['status'] ?? '';

                    if ($status === 'approved') {
                        $operatorRef = $txData['transaction_key'] ?? $txData['reference'] ?? null;
                        $completed = $paymentService->completeTransaction($txnId, (string)$gatewayTransactionId, $txData, $operatorRef);
                        jsonSuccess(['status' => 'completed', 'result' => $completed]);
                        return;
                    }
                    $verified = ['gateway_status' => $status];
                }
                break;

            case 'cinetpay':
                if ($gateway) {
                    $apiConfig = $gateway['config'];
                    $cleanedId = preg_replace('/[^A-Za-z0-9]/', '', $txnId);
                    $payload = json_encode([
                        'apikey' => $apiConfig['api_key'],
                        'site_id' => $apiConfig['site_id'],
                        'transaction_id' => $cleanedId
                    ]);
                    $ch = curl_init('https://api-checkout.cinetpay.com/v2/payment/check');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($response, true);
                    if (isset($result['code']) && $result['code'] === '00' && isset($result['data'])) {
                        $cinetStatus = $result['data']['status'] ?? '';
                        if ($cinetStatus === 'ACCEPTED') {
                            $operatorRef = $result['data']['operator_id'] ?? null;
                            $completed = $paymentService->completeTransaction($txnId, $result['data']['payment_token'] ?? $cleanedId, $result['data'], $operatorRef);
                            jsonSuccess(['status' => 'completed', 'result' => $completed]);
                            return;
                        }
                        $verified = ['gateway_status' => $cinetStatus];
                    }
                }
                break;

            case 'paygate_global':
            case 'paygate':
                if ($gateway) {
                    $apiConfig = $gateway['config'];
                    $ch = curl_init('https://paygateglobal.com/api/v2/status');
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                        'auth_token' => $apiConfig['auth_token'],
                        'identifier' => $txnId
                    ]));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($response, true);
                    $pgStatus = $result['status'] ?? null;
                    if ($pgStatus === 0) {
                        $operatorRef = $result['payment_reference'] ?? null;
                        $completed = $paymentService->completeTransaction($txnId, $result['tx_reference'] ?? '', $result, $operatorRef);
                        jsonSuccess(['status' => 'completed', 'result' => $completed]);
                        return;
                    }
                    $verified = ['gateway_status' => $pgStatus === 2 ? 'pending' : ($pgStatus === 4 ? 'expired' : 'status_' . $pgStatus)];
                }
                break;

            case 'feexpay':
                if ($gateway) {
                    $apiConfig = $gateway['config'];
                    $apiKey = $apiConfig['api_key'] ?? '';
                    $ch = curl_init('https://api.feexpay.me/api/transactions/status/' . urlencode($txnId));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Authorization: Bearer ' . $apiKey,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $response = curl_exec($ch);
                    curl_close($ch);

                    $result = json_decode($response, true);
                    $feexStatus = $result['status'] ?? '';
                    if ($feexStatus === 'SUCCESSFUL') {
                        $completed = $paymentService->completeTransaction($txnId, $result['reference'] ?? '', $result, $result['reference'] ?? null);
                        jsonSuccess(['status' => 'completed', 'result' => $completed]);
                        return;
                    }
                    $verified = ['gateway_status' => $feexStatus];
                }
                break;

            default:
                jsonError('Vérification non supportée pour cette passerelle: ' . $gatewayCode, 400);
                return;
        }

        // Si on arrive ici, la transaction n'est pas encore approuvée
        jsonSuccess([
            'status' => 'still_pending',
            'gateway_code' => $gatewayCode,
            'details' => $verified
        ]);
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
