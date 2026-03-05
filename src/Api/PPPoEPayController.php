<?php
/**
 * Controller API pour les paiements PPPoE publics
 * Accessible sans authentification pour les clients
 */

class PPPoEPayController
{
    private RadiusDatabase $db;
    private array $config;
    private ?AuthService $auth;

    public function __construct(RadiusDatabase $db, array $config = [], AuthService $auth = null)
    {
        $this->db = $db;
        $this->config = $config;
        $this->auth = $auth;
    }

    private function getAdminId(): ?int
    {
        return $this->auth ? $this->auth->getAdminId() : null;
    }

    /**
     * GET /pppoe-pay/lookup
     * Rechercher un utilisateur PPPoE par nom d'utilisateur
     */
    public function lookup(): void
    {
        $username = get('username');
        $adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : $this->getAdminId();

        if (empty($username)) {
            jsonError(__('api.pppoe_pay_username_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();

        // Rechercher l'utilisateur (scopé par admin_id)
        $sql = "SELECT u.*, p.name as profile_name, p.price, p.validity_days
                FROM pppoe_users u
                LEFT JOIN pppoe_profiles p ON u.profile_id = p.id
                WHERE u.username = ?";
        $params = [$username];

        if ($adminId !== null) {
            $sql .= " AND u.admin_id = ?";
            $params[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError(__('api.pppoe_pay_user_not_found'), 404);
            return;
        }

        // Récupérer le profil complet
        $profile = $this->db->getPPPoEProfileById($user['profile_id']);

        // Récupérer les factures impayées
        $stmt = $pdo->prepare("
            SELECT id, invoice_number, due_date, amount, tax_amount, total_amount, paid_amount, status
            FROM pppoe_invoices
            WHERE pppoe_user_id = ?
            AND status IN ('pending', 'partial', 'overdue')
            ORDER BY due_date ASC
        ");
        $stmt->execute([$user['id']]);
        $unpaidInvoices = $stmt->fetchAll();

        // Déterminer si le client peut prolonger son abonnement
        $canExtend = false;
        if ($user['status'] === 'active' && $user['valid_until']) {
            $validUntil = strtotime($user['valid_until']);
            // Peut prolonger si l'abonnement n'est pas encore expiré
            $canExtend = $validUntil > time();
        }

        // Masquer les infos sensibles
        $safeUser = [
            'id' => $user['id'],
            'username' => $user['username'],
            'customer_name' => $user['customer_name'],
            'customer_phone' => $user['customer_phone'],
            'customer_email' => $user['customer_email'],
            'profile_id' => $user['profile_id'],
            'profile_name' => $user['profile_name'],
            'status' => $user['status'],
            'valid_until' => $user['valid_until'],
            'created_at' => $user['created_at']
        ];

        jsonSuccess([
            'user' => $safeUser,
            'profile' => $profile,
            'unpaid_invoices' => $unpaidInvoices,
            'can_extend' => $canExtend
        ]);
    }

    /**
     * POST /pppoe-pay/initiate
     * Initier un paiement PPPoE
     */
    public function initiate(): void
    {
        $data = getJsonBody();

        $username = $data['username'] ?? '';
        $gatewayCode = $data['gateway_code'] ?? '';
        $paymentType = $data['payment_type'] ?? ''; // invoice, extension, renewal
        $invoiceId = $data['invoice_id'] ?? null;
        $customerPhone = $data['customer_phone'] ?? '';
        $customerEmail = $data['customer_email'] ?? '';

        if (empty($username)) {
            jsonError(__('api.pppoe_pay_username_required'), 400);
            return;
        }

        if (empty($gatewayCode)) {
            jsonError(__('api.pppoe_pay_gateway_required'), 400);
            return;
        }

        if (empty($paymentType) || !in_array($paymentType, ['invoice', 'extension', 'renewal'])) {
            jsonError(__('api.pppoe_pay_invalid_payment_type'), 400);
            return;
        }

        if (empty($customerPhone)) {
            jsonError(__('api.phone_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();

        // Vérifier l'utilisateur (scopé par admin_id si disponible)
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : $this->getAdminId();
        $userSql = "SELECT * FROM pppoe_users WHERE username = ?";
        $userParams = [$username];
        if ($adminId !== null) {
            $userSql .= " AND admin_id = ?";
            $userParams[] = $adminId;
        }
        $stmt = $pdo->prepare($userSql);
        $stmt->execute($userParams);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError(__('api.pppoe_pay_user_not_found'), 404);
            return;
        }

        // Récupérer le profil
        $profile = $this->db->getPPPoEProfileById($user['profile_id']);
        if (!$profile) {
            jsonError(__('api.pppoe_pay_profile_not_found'), 404);
            return;
        }

        // Déterminer le montant selon le type de paiement
        $amount = 0;
        $description = '';

        if ($paymentType === 'invoice') {
            if (empty($invoiceId)) {
                jsonError(__('api.pppoe_pay_invoice_id_required'), 400);
                return;
            }

            $stmt = $pdo->prepare("SELECT * FROM pppoe_invoices WHERE id = ? AND pppoe_user_id = ?");
            $stmt->execute([$invoiceId, $user['id']]);
            $invoice = $stmt->fetch();

            if (!$invoice) {
                jsonError(__('api.pppoe_pay_invoice_not_found'), 404);
                return;
            }

            if ($invoice['status'] === 'paid') {
                jsonError(__('api.pppoe_pay_invoice_already_paid'), 400);
                return;
            }

            $amount = $invoice['total_amount'] - $invoice['paid_amount'];
            $description = 'Paiement facture ' . $invoice['invoice_number'];
        } else {
            // Extension ou renouvellement
            $amount = $profile['price'] ?? 0;
            $description = $paymentType === 'extension'
                ? 'Prolongation abonnement ' . $profile['name']
                : 'Renouvellement abonnement ' . $profile['name'];
        }

        if ($amount <= 0) {
            jsonError(__('api.pppoe_pay_invalid_amount'), 400);
            return;
        }

        // Vérifier la passerelle (scoped par admin_id de l'utilisateur PPPoE)
        $pppoeAdminId = $user['admin_id'] ?? $this->getAdminId();
        $isPlatform = !empty($data['is_platform']);
        $gateway = null;
        $platformGatewayId = null;

        if ($isPlatform) {
            // Passerelle plateforme
            require_once __DIR__ . '/../Payment/PlatformPaymentService.php';
            $platformService = new PlatformPaymentService($pdo, $this->config);
            $platformGw = $platformService->getPlatformGatewayByCode($gatewayCode);
            if (!$platformGw || !$platformGw['is_active']) {
                jsonError(__('api.pppoe_pay_gateway_unavailable'), 400);
                return;
            }
            // Vérifier que l'admin a activé cette passerelle
            $apgStmt = $pdo->prepare("SELECT is_active FROM admin_platform_gateways WHERE admin_id = ? AND platform_gateway_id = ?");
            $apgStmt->execute([$pppoeAdminId, $platformGw['id']]);
            if (!$apgStmt->fetchColumn()) {
                jsonError(__('api.pppoe_pay_gateway_unavailable'), 400);
                return;
            }
            $platformGatewayId = $platformGw['id'];
            // Résoudre config API depuis la passerelle recharge (source unique)
            $rechargeGw = $this->db->getGlobalGatewayByCode($gatewayCode);
            if (!$rechargeGw || empty($rechargeGw['config'])) {
                jsonError('Configuration API non trouvée pour cette passerelle', 400);
                return;
            }
            $gateway = [
                'gateway_code' => $gatewayCode,
                'config' => $rechargeGw['config'],
                'is_sandbox' => $rechargeGw['is_sandbox'],
                'is_active' => 1
            ];
        } else {
            $gateway = $this->db->getPaymentGatewayByCode($gatewayCode, $pppoeAdminId);
            if (!$gateway || !$gateway['is_active']) {
                jsonError(__('api.pppoe_pay_gateway_unavailable'), 400);
                return;
            }
        }

        // Générer un ID de transaction unique
        $transactionId = 'PPPOE_' . strtoupper(bin2hex(random_bytes(12)));

        // Créer la transaction
        $stmt = $pdo->prepare("
            INSERT INTO pppoe_payment_transactions (
                transaction_id, pppoe_user_id, admin_id, gateway_code, payment_type,
                invoice_id, amount, currency, customer_phone, customer_email,
                description, status, is_platform, platform_gateway_id, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW())
        ");
        $stmt->execute([
            $transactionId,
            $user['id'],
            $pppoeAdminId,
            $gatewayCode,
            $paymentType,
            $invoiceId,
            $amount,
            $this->config['currency'] ?? 'XAF',
            $customerPhone,
            $customerEmail,
            $description,
            $isPlatform ? 1 : 0,
            $platformGatewayId
        ]);

        // Initier le paiement avec la passerelle
        try {
            $customerName = $user['customer_name'] ?? 'Client PPPoE';
            $redirectUrl = $this->initiateGatewayPayment($gateway, $transactionId, $amount, $customerPhone, $customerEmail, $description, $customerName);

            jsonSuccess([
                'transaction_id' => $transactionId,
                'redirect_url' => $redirectUrl
            ]);
        } catch (Exception $e) {
            // Marquer la transaction comme échouée
            $stmt = $pdo->prepare("UPDATE pppoe_payment_transactions SET status = 'failed', error_message = ? WHERE transaction_id = ?");
            $stmt->execute([$e->getMessage(), $transactionId]);

            jsonError(__('api.pppoe_pay_initiation_error') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Initier le paiement avec une passerelle
     */
    public function initiateGatewayPayment(array $gateway, string $transactionId, float $amount, string $phone, string $email, string $description, string $customerName = 'Client'): string
    {
        // Le config peut être déjà un tableau ou une chaîne JSON
        $gatewayConfig = is_array($gateway['config']) ? $gateway['config'] : (json_decode($gateway['config'], true) ?? []);
        $baseUrl = $this->config['app']['base_url'] ?? $this->detectBaseUrl();
        $callbackUrl = $baseUrl . '/pppoe-payment-callback.php';
        $successUrl = $baseUrl . '/pppoe-payment-success.php?transaction=' . $transactionId;
        $cancelUrl = $baseUrl . '/pppoe-pay.php?cancelled=1';

        switch ($gateway['gateway_code']) {
            case 'fedapay':
                return $this->initiateFedaPay($gatewayConfig, $transactionId, $amount, $phone, $email, $description, $callbackUrl);

            case 'cinetpay':
                return $this->initiateCinetPay($gatewayConfig, $transactionId, $amount, $phone, $email, $description, $successUrl, $cancelUrl, $callbackUrl);

            case 'kkiapay':
                // Kkiapay utilise une intégration JS côté client
                return $baseUrl . '/pppoe-kkiapay.php?transaction=' . $transactionId;

            case 'feexpay':
                return $this->initiateFeexPay($gatewayConfig, $transactionId, $amount, $phone, $description, $callbackUrl);

            case 'moneroo':
                return $this->initiateMoneroo($gatewayConfig, $transactionId, $amount, $phone, $email, $description, $successUrl, $cancelUrl, $customerName);

            case 'paygate':
            case 'paygate_global':
                return $this->initiatePayGate($gatewayConfig, $transactionId, $amount, $phone, $email, $description, $successUrl);

            case 'paydunya':
                return $this->initiatePayDunya($gatewayConfig, $transactionId, $amount, $phone, $email, $description, $successUrl, $cancelUrl, $callbackUrl);

            case 'stripe':
                return $this->initiateStripe($gatewayConfig, $transactionId, $amount, $email, $description, $successUrl, $cancelUrl);

            case 'paypal':
                return $this->initiatePayPal($gatewayConfig, $transactionId, $amount, $description, $successUrl, $cancelUrl);

            case 'yengapay':
                return $this->initiateYengaPay($gatewayConfig, $transactionId, $amount, $description, $callbackUrl);

            default:
                throw new Exception('Passerelle non supportée: ' . $gateway['gateway_code']);
        }
    }

    /**
     * FedaPay
     */
    private function initiateFedaPay(array $config, string $transactionId, float $amount, string $phone, string $email, string $description, string $callbackUrl): string
    {
        // Utiliser secret_key ou api_key selon la configuration
        $apiKey = $config['secret_key'] ?? $config['api_key'] ?? '';
        $environment = $config['environment'] ?? 'live';
        $baseApiUrl = $environment === 'live'
            ? 'https://api.fedapay.com/v1'
            : 'https://sandbox-api.fedapay.com/v1';

        $customer = ['email' => $email ?: 'client@example.com'];
        if (!empty($phone)) {
            $customer['phone_number'] = ['number' => $phone, 'country' => 'BJ'];
        }

        $response = $this->httpPost($baseApiUrl . '/transactions', [
            'description' => $description,
            'amount' => (int)$amount,
            'currency' => ['iso' => 'XOF'],
            'callback_url' => $callbackUrl,
            'customer' => $customer,
            'metadata' => [
                'transaction_id' => $transactionId,
                'type' => 'pppoe'
            ]
        ], [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        // La réponse FedaPay peut avoir différents formats
        $transaction = $response['v1/transaction'] ?? $response['v1']['transaction'] ?? null;

        if (!$transaction || !isset($transaction['id'])) {
            throw new Exception('Erreur FedaPay: ' . json_encode($response));
        }

        $fedaId = $transaction['id'];

        // Sauvegarder l'ID FedaPay
        $this->updateTransaction($transactionId, ['gateway_transaction_id' => (string)$fedaId]);

        // Si payment_url est déjà fourni, l'utiliser directement
        if (!empty($transaction['payment_url'])) {
            return $transaction['payment_url'];
        }

        // Sinon générer le token de paiement
        $tokenResponse = $this->httpPost($baseApiUrl . '/transactions/' . $fedaId . '/token', [], [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        if (!isset($tokenResponse['token'])) {
            throw new Exception('Erreur génération token FedaPay');
        }

        return $environment === 'live'
            ? 'https://process.fedapay.com/' . $tokenResponse['token']
            : 'https://sandbox-process.fedapay.com/' . $tokenResponse['token'];
    }

    /**
     * CinetPay
     */
    private function initiateCinetPay(array $config, string $transactionId, float $amount, string $phone, string $email, string $description, string $successUrl, string $cancelUrl, string $callbackUrl): string
    {
        $apiKey = $config['api_key'] ?? '';
        $siteId = $config['site_id'] ?? '';

        $response = $this->httpPost('https://api-checkout.cinetpay.com/v2/payment', [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $transactionId,
            'amount' => (int)$amount,
            'currency' => 'XOF',
            'description' => $description,
            'notify_url' => $callbackUrl,
            'return_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'channels' => 'ALL',
            'customer_phone_number' => $phone,
            'customer_email' => $email ?: 'client@example.com',
            'customer_name' => 'Client PPPoE',
            'metadata' => json_encode(['type' => 'pppoe'])
        ], ['Content-Type: application/json']);

        if (($response['code'] ?? '') !== '201') {
            throw new Exception('Erreur CinetPay: ' . ($response['message'] ?? json_encode($response)));
        }

        return $response['data']['payment_url'];
    }

    /**
     * FeexPay
     */
    private function initiateFeexPay(array $config, string $transactionId, float $amount, string $phone, string $description, string $callbackUrl): string
    {
        if (empty($phone)) {
            throw new Exception('FeexPay nécessite un numéro de téléphone pour effectuer le paiement');
        }

        $apiKey = $config['api_key'] ?? '';
        $shopId = $config['shop_id'] ?? '';
        $operator = !empty($config['operator']) ? $config['operator'] : 'mtn';

        // Nettoyer le numéro et ajouter l'indicatif pays
        $phone = preg_replace('/[^0-9]/', '', $phone);
        $countryCodes = [
            'mtn' => '229', 'moov' => '229', 'celtiis_bj' => '229',
            'togocom_tg' => '228', 'moov_tg' => '228',
            'mtn_ci' => '225', 'moov_ci' => '225', 'orange_ci' => '225', 'wave_ci' => '225',
            'mtn_cg' => '242',
        ];
        $countryCode = $countryCodes[$operator] ?? '229';
        if (strlen($phone) <= 9 && !str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        $apiUrl = 'https://api.feexpay.me/api/transactions/public/requesttopay/' . $operator;

        $response = $this->httpPost($apiUrl, [
            'shop' => $shopId,
            'amount' => (int)$amount,
            'phoneNumber' => $phone,
            'description' => $description,
            'callback_info' => $transactionId,
            'firstName' => 'Client',
            'lastName' => ''
        ], [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        // FeexPay retourne un statut et un lien de référence
        if (isset($response['reference'])) {
            $this->updateTransaction($transactionId, ['gateway_transaction_id' => $response['reference']]);
            // FeexPay envoie un push USSD au client, pas de redirect
            $baseUrl = $this->config['app']['base_url'] ?? $this->detectBaseUrl();
            return $baseUrl . '/pppoe-payment-success.php?transaction=' . $transactionId . '&status=pending';
        }

        throw new Exception('Erreur FeexPay: ' . json_encode($response));
    }

    /**
     * Moneroo
     */
    private function initiateMoneroo(array $config, string $transactionId, float $amount, string $phone, string $email, string $description, string $successUrl, string $cancelUrl, string $customerName = 'Client'): string
    {
        $secretKey = $config['secret_key'] ?? '';

        // Séparer le nom en prénom et nom de famille
        $nameParts = explode(' ', trim($customerName), 2);
        $firstName = $nameParts[0] ?: 'Client';
        $lastName = $nameParts[1] ?? 'PPPoE';

        // Générer un email si non fourni
        $customerEmail = $email ?: 'client_' . preg_replace('/[^0-9]/', '', $phone) . '@pppoe.local';

        $response = $this->httpPost('https://api.moneroo.io/v1/payments/initialize', [
            'amount' => (int)$amount,
            'currency' => 'XOF',
            'description' => $description,
            'return_url' => $successUrl,
            'customer' => [
                'email' => $customerEmail,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone
            ],
            'metadata' => [
                'transaction_id' => $transactionId,
                'type' => 'pppoe'
            ]
        ], [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if (!isset($response['data']['checkout_url'])) {
            throw new Exception('Erreur Moneroo: ' . json_encode($response));
        }

        $this->updateTransaction($transactionId, ['gateway_transaction_id' => $response['data']['id'] ?? '']);

        return $response['data']['checkout_url'];
    }

    /**
     * PayGate Global - Redirection vers page de paiement
     * Documentation: https://paygateglobal.com
     * Services: FLOOZ, TMONEY (Togo)
     */
    private function initiatePayGate(array $config, string $transactionId, float $amount, string $phone, string $email, string $description, string $returnUrl): string
    {
        $token = $config['auth_token'] ?? $config['api_key'] ?? '';

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ajouter indicatif Togo si nécessaire
        if (strlen($phone) <= 8 && !str_starts_with($phone, '228')) {
            $phone = '228' . $phone;
        }

        // Construire l'URL de redirection vers la page PayGate
        return 'https://paygateglobal.com/v1/page?' . http_build_query([
            'token' => $token,
            'amount' => (int)$amount,
            'description' => $description,
            'identifier' => $transactionId,
            'url' => $returnUrl,
            'phone' => $phone
        ]);
    }

    /**
     * PayDunya
     */
    private function initiatePayDunya(array $config, string $transactionId, float $amount, string $phone, string $email, string $description, string $successUrl, string $cancelUrl, string $callbackUrl): string
    {
        $masterKey = $config['master_key'] ?? '';
        $privateKey = $config['private_key'] ?? '';
        $token = $config['token'] ?? '';

        $response = $this->httpPost('https://app.paydunya.com/api/v1/checkout-invoice/create', [
            'invoice' => [
                'total_amount' => (int)$amount,
                'description' => $description
            ],
            'store' => [
                'name' => 'PPPoE Payment'
            ],
            'custom_data' => [
                'transaction_id' => $transactionId,
                'type' => 'pppoe'
            ],
            'actions' => [
                'callback_url' => $callbackUrl,
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]
        ], [
            'Content-Type: application/json',
            'PAYDUNYA-MASTER-KEY: ' . $masterKey,
            'PAYDUNYA-PRIVATE-KEY: ' . $privateKey,
            'PAYDUNYA-TOKEN: ' . $token
        ]);

        if (($response['response_code'] ?? '') !== '00') {
            throw new Exception('Erreur PayDunya: ' . ($response['response_text'] ?? json_encode($response)));
        }

        $this->updateTransaction($transactionId, ['gateway_transaction_id' => $response['token'] ?? '']);

        return $response['response_text'] ?? '';
    }

    /**
     * Stripe
     */
    private function initiateStripe(array $config, string $transactionId, float $amount, string $email, string $description, string $successUrl, string $cancelUrl): string
    {
        $secretKey = $config['secret_key'] ?? '';

        $response = $this->httpPost('https://api.stripe.com/v1/checkout/sessions', http_build_query([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'xof',
                    'product_data' => ['name' => $description],
                    'unit_amount' => (int)$amount
                ],
                'quantity' => 1
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'transaction_id' => $transactionId,
                'type' => 'pppoe'
            ]
        ]), [
            'Authorization: Basic ' . base64_encode($secretKey . ':'),
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if (!isset($response['url'])) {
            throw new Exception('Erreur Stripe: ' . json_encode($response));
        }

        $this->updateTransaction($transactionId, ['gateway_transaction_id' => $response['id'] ?? '']);

        return $response['url'];
    }

    /**
     * PayPal
     */
    private function initiatePayPal(array $config, string $transactionId, float $amount, string $description, string $successUrl, string $cancelUrl): string
    {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $environment = $config['environment'] ?? 'sandbox';

        $baseUrl = $environment === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';

        // Obtenir le token d'accès
        $tokenResponse = $this->httpPost($baseUrl . '/v1/oauth2/token', 'grant_type=client_credentials', [
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret),
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        if (!isset($tokenResponse['access_token'])) {
            throw new Exception('Erreur authentification PayPal');
        }

        $accessToken = $tokenResponse['access_token'];

        // Créer l'ordre
        $orderResponse = $this->httpPost($baseUrl . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $transactionId,
                'description' => $description,
                'amount' => [
                    'currency_code' => 'EUR',
                    'value' => number_format($amount / 655.957, 2, '.', '') // Conversion XOF -> EUR
                ]
            ]],
            'application_context' => [
                'return_url' => $successUrl,
                'cancel_url' => $cancelUrl
            ]
        ], [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json'
        ]);

        if (!isset($orderResponse['id'])) {
            throw new Exception('Erreur création ordre PayPal: ' . json_encode($orderResponse));
        }

        $this->updateTransaction($transactionId, ['gateway_transaction_id' => $orderResponse['id']]);

        // Trouver le lien d'approbation
        foreach ($orderResponse['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return $link['href'];
            }
        }

        throw new Exception('Lien de paiement PayPal non trouvé');
    }

    /**
     * YengaPay
     */
    private function initiateYengaPay(array $config, string $transactionId, float $amount, string $description, string $callbackUrl): string
    {
        $groupeId = $config['groupe_id'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $projectId = $config['project_id'] ?? '';

        if (empty($groupeId) || empty($apiKey) || empty($projectId)) {
            throw new Exception('YengaPay: groupe_id, api_key et project_id sont requis');
        }

        $apiUrl = sprintf(
            'https://api.yengapay.com/api/v1/groups/%s/payment-intent/%s',
            $groupeId,
            $projectId
        );

        $response = $this->httpPost($apiUrl, [
            'paymentAmount' => $amount,
            'reference' => $transactionId,
            'articles' => [
                [
                    'title' => $description,
                    'description' => $description,
                    'price' => $amount,
                    'pictures' => []
                ]
            ]
        ], [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if (!isset($response['checkoutPageUrlWithPaymentToken'])) {
            throw new Exception('YengaPay: URL de paiement non trouvée. ' . json_encode($response));
        }

        $this->updateTransaction($transactionId, ['gateway_transaction_id' => (string)($response['id'] ?? '')]);

        return $response['checkoutPageUrlWithPaymentToken'];
    }

    /**
     * Mise à jour de la transaction
     */
    private function updateTransaction(string $transactionId, array $data): void
    {
        $pdo = $this->db->getPdo();
        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $transactionId;
        $sql = "UPDATE pppoe_payment_transactions SET " . implode(', ', $updates) . " WHERE transaction_id = ?";
        $pdo->prepare($sql)->execute($params);
    }

    /**
     * HTTP POST helper
     */
    private function httpPost(string $url, $data, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? json_encode($data) : $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Erreur HTTP: ' . $error);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Détecter l'URL de base
     */
    private function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Utiliser dirname(SCRIPT_NAME) pour détecter le répertoire web
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return $protocol . '://' . $host . $basePath;
    }

    /**
     * GET /pppoe-pay/transaction/{id}
     * Récupérer les détails d'une transaction
     */
    public function getTransaction(array $params): void
    {
        $transactionId = $params['id'] ?? '';

        if (empty($transactionId)) {
            jsonError(__('api.transaction_id_required'), 400);
            return;
        }

        $adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : $this->getAdminId();

        $pdo = $this->db->getPdo();
        $sql = "SELECT t.*, u.username, u.customer_name
                FROM pppoe_payment_transactions t
                JOIN pppoe_users u ON t.pppoe_user_id = u.id
                WHERE t.transaction_id = ?";
        $txParams = [$transactionId];

        if ($adminId !== null) {
            $sql .= " AND t.admin_id = ?";
            $txParams[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($txParams);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            jsonError(__('api.transaction_not_found'), 404);
            return;
        }

        jsonSuccess($transaction);
    }

    /**
     * POST /pppoe-pay/check-status
     * Vérification publique du statut d'une transaction (appelée depuis la page de succès)
     */
    public function checkStatus(): void
    {
        $data = getJsonBody();
        $transactionId = $data['transaction_id'] ?? '';

        if (empty($transactionId)) {
            jsonError('Transaction ID required', 400);
            return;
        }

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT * FROM pppoe_payment_transactions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            jsonError('Transaction not found', 404);
            return;
        }

        // Si déjà complétée ou échouée, retourner le statut directement
        if ($transaction['status'] !== 'pending') {
            jsonSuccess(['status' => $transaction['status']]);
            return;
        }

        // Vérifier avec la passerelle (résoudre config depuis recharge gateways si plateforme)
        $gatewayConfig = null;
        if (($transaction['is_platform'] ?? 0) == 1) {
            $rechargeGw = $this->db->getGlobalGatewayByCode($transaction['gateway_code']);
            if ($rechargeGw) {
                $gatewayConfig = $rechargeGw['config'];
            }
        } else {
            $gateway = $this->db->getPaymentGatewayByCode($transaction['gateway_code'], $transaction['admin_id'] ?? null);
            if ($gateway) {
                $gatewayConfig = is_array($gateway['config']) ? $gateway['config'] : (json_decode($gateway['config'], true) ?? []);
            }
        }

        if (!$gatewayConfig) {
            jsonSuccess(['status' => 'pending']);
            return;
        }

        try {
            $status = $this->checkGatewayStatus($transaction['gateway_code'], $gatewayConfig, $transaction);

            if ($status === 'completed') {
                $this->processCompletedPayment($transaction);
                jsonSuccess(['status' => 'completed']);
            } elseif ($status === 'failed') {
                $stmt = $pdo->prepare("UPDATE pppoe_payment_transactions SET status = 'failed' WHERE transaction_id = ?");
                $stmt->execute([$transactionId]);
                jsonSuccess(['status' => 'failed']);
            } else {
                jsonSuccess(['status' => 'pending']);
            }
        } catch (Exception $e) {
            jsonSuccess(['status' => 'pending']);
        }
    }

    /**
     * GET /pppoe/payments/transactions
     * Liste des transactions PPPoE avec filtres et pagination
     */
    public function listTransactions(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $page = (int)(get('page') ?? 1);
        $limit = min((int)(get('limit') ?? 20), 100);
        $offset = ($page - 1) * $limit;

        $search = get('search') ?? '';
        $status = get('status') ?? '';
        $gatewayCode = get('gateway_code') ?? '';
        $paymentType = get('payment_type') ?? '';

        $where = [];
        $params = [];

        // Isolation multi-tenant
        if ($adminId !== null) {
            $where[] = "t.admin_id = ?";
            $params[] = $adminId;
        }

        if ($search) {
            $where[] = "(t.transaction_id LIKE ? OR t.customer_phone LIKE ? OR t.customer_email LIKE ? OR u.customer_name LIKE ? OR u.username LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($status) {
            $where[] = "t.status = ?";
            $params[] = $status;
        }

        if ($gatewayCode) {
            $where[] = "t.gateway_code = ?";
            $params[] = $gatewayCode;
        }

        if ($paymentType) {
            $where[] = "t.payment_type = ?";
            $params[] = $paymentType;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Compter le total
        $countSql = "SELECT COUNT(*) FROM pppoe_payment_transactions t
                     JOIN pppoe_users u ON t.pppoe_user_id = u.id
                     $whereClause";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Récupérer les transactions
        $sql = "SELECT t.*, u.username, u.customer_name
                FROM pppoe_payment_transactions t
                JOIN pppoe_users u ON t.pppoe_user_id = u.id
                $whereClause
                ORDER BY t.created_at DESC
                LIMIT $limit OFFSET $offset";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll();

        jsonSuccess([
            'data' => $transactions,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit)
        ]);
    }

    /**
     * GET /pppoe/payments/stats
     * Statistiques des transactions PPPoE
     */
    public function transactionStats(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
            FROM pppoe_payment_transactions";

        if ($adminId !== null) {
            $sql .= " WHERE admin_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$adminId]);
        } else {
            $stmt = $pdo->query($sql);
        }
        $stats = $stmt->fetch();

        jsonSuccess($stats);
    }

    /**
     * POST /pppoe/payments/retry-callback
     * Relancer le callback pour vérifier le statut d'une transaction pending
     */
    public function retryCallback(): void
    {
        $data = getJsonBody();
        $transactionId = $data['transaction_id'] ?? '';

        if (empty($transactionId)) {
            jsonError(__('api.transaction_id_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Récupérer la transaction (scoped par admin)
        $sql = "SELECT * FROM pppoe_payment_transactions WHERE transaction_id = ?";
        $params = [$transactionId];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            jsonError(__('api.transaction_not_found'), 404);
            return;
        }

        if ($transaction['status'] !== 'pending') {
            jsonError(__('api.pppoe_pay_only_pending_can_retry'), 400);
            return;
        }

        // Récupérer la config de passerelle (résoudre depuis recharge gateways si plateforme)
        $gatewayConfig = null;
        if (($transaction['is_platform'] ?? 0) == 1) {
            $rechargeGw = $this->db->getGlobalGatewayByCode($transaction['gateway_code']);
            if ($rechargeGw) {
                $gatewayConfig = $rechargeGw['config'];
            }
        } else {
            $gateway = $this->db->getPaymentGatewayByCode($transaction['gateway_code'], $transaction['admin_id'] ?? null);
            if ($gateway) {
                $gatewayConfig = is_array($gateway['config']) ? $gateway['config'] : (json_decode($gateway['config'], true) ?? []);
            }
        }

        if (!$gatewayConfig) {
            jsonError(__('api.pppoe_pay_gateway_not_found'), 404);
            return;
        }

        // Vérifier le statut selon la passerelle
        try {
            $status = $this->checkGatewayStatus($transaction['gateway_code'], $gatewayConfig, $transaction);

            if ($status === 'completed') {
                // Traiter le paiement
                $this->processCompletedPayment($transaction);
                jsonSuccess(['message' => __('api.pppoe_pay_transaction_completed'), 'status' => 'completed']);
            } elseif ($status === 'failed') {
                $stmt = $pdo->prepare("UPDATE pppoe_payment_transactions SET status = 'failed' WHERE transaction_id = ?");
                $stmt->execute([$transactionId]);
                jsonSuccess(['message' => __('api.pppoe_pay_transaction_failed'), 'status' => 'failed']);
            } else {
                jsonSuccess(['message' => __('api.pppoe_pay_transaction_pending'), 'status' => 'pending']);
            }
        } catch (Exception $e) {
            jsonError(__('api.pppoe_pay_verification_error') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /pppoe/payments/mark-completed
     * Marquer manuellement une transaction comme complétée
     */
    public function markCompleted(): void
    {
        $data = getJsonBody();
        $transactionId = $data['transaction_id'] ?? '';

        if (empty($transactionId)) {
            jsonError(__('api.transaction_id_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        // Récupérer la transaction (scoped par admin)
        $sql = "SELECT * FROM pppoe_payment_transactions WHERE transaction_id = ?";
        $params = [$transactionId];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            jsonError(__('api.transaction_not_found'), 404);
            return;
        }

        if ($transaction['status'] === 'completed') {
            jsonError(__('api.pppoe_pay_transaction_already_completed'), 400);
            return;
        }

        try {
            $this->processCompletedPayment($transaction);
            jsonSuccess(['message' => __('api.pppoe_pay_transaction_marked_completed')]);
        } catch (Exception $e) {
            jsonError(__('api.error_generic') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Vérifier le statut auprès de la passerelle
     */
    private function checkGatewayStatus(string $gatewayCode, array $config, array $transaction): string
    {
        $gatewayTransactionId = $transaction['gateway_transaction_id'];

        switch ($gatewayCode) {
            case 'fedapay':
                return $this->checkFedaPayStatus($config, $gatewayTransactionId);

            case 'cinetpay':
                return $this->checkCinetPayStatus($config, $transaction['transaction_id']);

            case 'moneroo':
                return $this->checkMonerooStatus($config, $gatewayTransactionId);

            case 'paygate':
            case 'paygate_global':
                return $this->checkPayGateStatus($config, $transaction['transaction_id']);

            case 'feexpay':
                return $this->checkFeexPayStatus($config, $transaction['gateway_transaction_id']);

            default:
                // Pour les autres passerelles, retourner pending
                return 'pending';
        }
    }

    /**
     * Vérifier le statut FeexPay
     * GET https://api.feexpay.me/api/transactions/public/single/status/{reference}
     */
    private function checkFeexPayStatus(array $config, string $reference): string
    {
        if (empty($reference)) {
            return 'pending';
        }

        $apiKey = $config['api_key'] ?? '';

        $ch = curl_init('https://api.feexpay.me/api/transactions/public/single/status/' . $reference);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = $data['status'] ?? null;

        if ($status === 'SUCCESSFUL' || $status === 'successful') {
            return 'completed';
        } elseif (in_array($status, ['FAILED', 'failed', 'CANCELLED', 'cancelled'])) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * Vérifier le statut PayGate
     * Statuts: 0 = succès, 2 = en cours, 4 = expiré, 6 = annulé
     */
    private function checkPayGateStatus(array $config, string $identifier): string
    {
        $token = $config['auth_token'] ?? $config['api_key'] ?? '';

        $ch = curl_init('https://paygateglobal.com/api/v2/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'auth_token' => $token,
            'identifier' => $identifier
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = $data['status'] ?? null;

        if ($status === 0) {
            return 'completed';
        } elseif (in_array($status, [4, 6])) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * Vérifier le statut FedaPay
     */
    private function checkFedaPayStatus(array $config, string $transactionId): string
    {
        $apiKey = $config['secret_key'] ?? $config['api_key'] ?? '';
        $environment = $config['environment'] ?? 'live';
        $baseApiUrl = $environment === 'live'
            ? 'https://api.fedapay.com/v1'
            : 'https://sandbox-api.fedapay.com/v1';

        $ch = curl_init($baseApiUrl . '/transactions/' . $transactionId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = $data['v1/transaction']['status'] ?? $data['v1']['transaction']['status'] ?? null;

        if ($status === 'approved') {
            return 'completed';
        } elseif (in_array($status, ['declined', 'canceled', 'cancelled'])) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * Vérifier le statut CinetPay
     */
    private function checkCinetPayStatus(array $config, string $transactionId): string
    {
        $apiKey = $config['api_key'] ?? '';
        $siteId = $config['site_id'] ?? '';

        $ch = curl_init('https://api-checkout.cinetpay.com/v2/payment/check');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $transactionId
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = $data['data']['status'] ?? '';

        if ($status === 'ACCEPTED') {
            return 'completed';
        } elseif (in_array($status, ['REFUSED', 'CANCELLED'])) {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * Vérifier le statut Moneroo
     */
    private function checkMonerooStatus(array $config, string $transactionId): string
    {
        $secretKey = $config['secret_key'] ?? '';

        $ch = curl_init('https://api.moneroo.io/v1/payments/' . $transactionId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $status = $data['data']['status'] ?? '';

        if ($status === 'success') {
            return 'completed';
        } elseif ($status === 'failed') {
            return 'failed';
        }

        return 'pending';
    }

    /**
     * Traiter un paiement complété
     */
    private function processCompletedPayment(array $transaction): void
    {
        $pdo = $this->db->getPdo();
        $transactionId = $transaction['transaction_id'];

        // Charger les fonctions d'aide
        require_once __DIR__ . '/../Utils/pppoe-payment-helpers.php';

        // Traiter le paiement
        processSuccessfulPPPoEPayment($this->db, $pdo, $transaction);

        // Mettre à jour la transaction
        $stmt = $pdo->prepare("
            UPDATE pppoe_payment_transactions
            SET status = 'completed', completed_at = NOW()
            WHERE transaction_id = ?
        ");
        $stmt->execute([$transactionId]);

        // Si paiement via passerelle plateforme → créditer le solde paygate de l'admin
        $adminId = $transaction['admin_id'] ?? null;
        if (($transaction['is_platform'] ?? 0) == 1 && $adminId) {
            try {
                require_once __DIR__ . '/../Payment/PlatformPaymentService.php';
                $platformService = new PlatformPaymentService($pdo, $this->config);
                $platformService->creditAdminBalance(
                    (int)$adminId,
                    (float)$transaction['amount'],
                    'pppoe_payment_transaction',
                    $transactionId,
                    'Paiement PPPoE client via passerelle plateforme'
                );
            } catch (Exception $e) {
                error_log('Paygate PPPoE credit error: ' . $e->getMessage());
            }
        }
    }

    /**
     * DELETE /pppoe/payments/transactions/{id} - Supprimer une transaction
     */
    public function deleteTransaction(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if (!$id) {
            jsonError(__('pppoe_trans.delete_invalid_id'), 400);
        }

        $sql = "SELECT id FROM pppoe_payment_transactions WHERE id = ?";
        $p = [$id];
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $p[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);
        if (!$stmt->fetch()) {
            jsonError(__('pppoe_trans.delete_not_found'), 404);
        }

        $stmt = $pdo->prepare("DELETE FROM pppoe_payment_transactions WHERE id = ?");
        $stmt->execute([$id]);

        jsonSuccess(null, __('pppoe_trans.delete_success'));
    }

    /**
     * DELETE /pppoe/payments/transactions/batch - Supprimer plusieurs transactions
     */
    public function deleteTransactionsBatch(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if (empty($ids) || !is_array($ids)) {
            jsonError(__('pppoe_trans.delete_no_selection'), 400);
        }

        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "DELETE FROM pppoe_payment_transactions WHERE id IN ($placeholders)";
        $p = $ids;
        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $p[] = $adminId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($p);

        jsonSuccess(['deleted' => $stmt->rowCount()], __('pppoe_trans.delete_batch_success'));
    }
}
