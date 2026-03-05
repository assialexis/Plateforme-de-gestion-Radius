<?php
/**
 * Service de paiement plateforme (Paygate)
 * Gère les passerelles pré-configurées par le superadmin,
 * les soldes collectés par admin, et les demandes de retrait.
 */

class PlatformPaymentService
{
    private PDO $pdo;
    private array $config;
    private string $baseUrl;

    public function __construct(PDO $pdo, array $config = [])
    {
        $this->pdo = $pdo;
        $this->config = $config;
        $this->baseUrl = $config['app']['base_url'] ?? $this->detectBaseUrl();
    }

    private function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        return $protocol . '://' . $host . $basePath;
    }

    // ===================================================
    // GLOBAL SETTINGS
    // ===================================================

    public function getSettings(): array
    {
        $stmt = $this->pdo->query("
            SELECT setting_key, setting_value FROM global_settings
            WHERE setting_key IN ('paygate_enabled','paygate_commission_rate','paygate_min_withdrawal','paygate_withdrawal_currency')
        ");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return [
            'paygate_enabled' => $settings['paygate_enabled'] ?? '0',
            'paygate_commission_rate' => $settings['paygate_commission_rate'] ?? '5',
            'paygate_min_withdrawal' => $settings['paygate_min_withdrawal'] ?? '1000',
            'paygate_withdrawal_currency' => $settings['paygate_withdrawal_currency'] ?? 'XOF',
        ];
    }

    public function updateSettings(array $data): void
    {
        $allowed = ['paygate_enabled', 'paygate_commission_rate', 'paygate_min_withdrawal', 'paygate_withdrawal_currency'];
        $stmt = $this->pdo->prepare("
            INSERT INTO global_settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $stmt->execute([$key, $value]);
            }
        }
    }

    // ===================================================
    // PLATFORM GATEWAYS (SuperAdmin)
    // ===================================================

    /**
     * Résoudre la config API depuis la passerelle recharge (source unique de vérité)
     */
    public function getRechargeGatewayConfig(string $gatewayCode): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT config, is_sandbox FROM payment_gateways WHERE gateway_code = ? AND admin_id IS NULL"
        );
        $stmt->execute([$gatewayCode]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return [
            'config' => json_decode($row['config'] ?: '{}', true) ?: [],
            'is_sandbox' => (bool)$row['is_sandbox'],
        ];
    }

    public function getPlatformGateways(): array
    {
        $stmt = $this->pdo->query("
            SELECT pg.*, rg.is_sandbox AS recharge_is_sandbox,
                   (rg.config IS NOT NULL AND rg.config != '{}' AND rg.config != '') AS recharge_configured
            FROM platform_payment_gateways pg
            LEFT JOIN payment_gateways rg ON rg.gateway_code COLLATE utf8mb4_unicode_ci = pg.gateway_code COLLATE utf8mb4_unicode_ci AND rg.admin_id IS NULL
            ORDER BY pg.display_order, pg.id
        ");
        $gateways = $stmt->fetchAll();
        foreach ($gateways as &$gw) {
            $gw['is_sandbox'] = (int)($gw['recharge_is_sandbox'] ?? 1);
            $gw['recharge_configured'] = (bool)($gw['recharge_configured'] ?? false);
            unset($gw['recharge_is_sandbox'], $gw['config']);
        }
        return $gateways;
    }

    public function getPlatformGateway(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM platform_payment_gateways WHERE id = ?");
        $stmt->execute([$id]);
        $gw = $stmt->fetch();
        if ($gw) {
            $gw['config'] = json_decode($gw['config'] ?: '{}', true) ?: [];
        }
        return $gw ?: null;
    }

    public function getPlatformGatewayByCode(string $code): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM platform_payment_gateways WHERE gateway_code = ?");
        $stmt->execute([$code]);
        $gw = $stmt->fetch();
        if ($gw) {
            $gw['config'] = json_decode($gw['config'] ?: '{}', true) ?: [];
        }
        return $gw ?: null;
    }

    public function updatePlatformGateway(int $id, array $data): bool
    {
        $current = $this->getPlatformGateway($id);
        if (!$current) return false;

        $updates = [];
        $params = [];

        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $params[] = (int)$data['is_active'];
        }
        if (isset($data['commission_rate'])) {
            $updates[] = 'commission_rate = ?';
            $params[] = (float)$data['commission_rate'];
        }
        if (isset($data['min_withdrawal'])) {
            $updates[] = 'min_withdrawal = ?';
            $params[] = (float)$data['min_withdrawal'];
        }
        if (isset($data['withdrawal_currency'])) {
            $updates[] = 'withdrawal_currency = ?';
            $params[] = $data['withdrawal_currency'];
        }

        if (empty($updates)) return true;

        $params[] = $id;
        $stmt = $this->pdo->prepare("UPDATE platform_payment_gateways SET " . implode(', ', $updates) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Masquer les données sensibles dans la config
     */
    public function maskSensitiveConfig(array $config): array
    {
        $sensitiveKeys = ['secret_key', 'api_key', 'private_key', 'auth_token', 'password', 'client_secret', 'webhook_secret', 'api_secret'];
        foreach ($config as $key => $value) {
            if (in_array($key, $sensitiveKeys) && !empty($value)) {
                $config[$key] = '••••••';
            }
        }
        return $config;
    }

    // ===================================================
    // ADMIN PLATFORM GATEWAYS (Activation)
    // ===================================================

    /**
     * Provisionner les entrées admin_platform_gateways pour un admin
     */
    private function ensureAdminGatewaysProvisioned(int $adminId): void
    {
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO admin_platform_gateways (admin_id, platform_gateway_id, is_active)
            SELECT ?, id, 0 FROM platform_payment_gateways
        ");
        $stmt->execute([$adminId]);
    }

    public function getAdminPlatformGateways(int $adminId): array
    {
        $this->ensureAdminGatewaysProvisioned($adminId);

        $stmt = $this->pdo->prepare("
            SELECT pg.id, pg.gateway_code, pg.name, pg.description, pg.is_active as platform_active,
                   pg.is_sandbox, apg.is_active as admin_active, apg.id as admin_gateway_id
            FROM platform_payment_gateways pg
            LEFT JOIN admin_platform_gateways apg ON apg.platform_gateway_id = pg.id AND apg.admin_id = ?
            WHERE pg.is_active = 1
            ORDER BY pg.display_order, pg.id
        ");
        $stmt->execute([$adminId]);
        return $stmt->fetchAll();
    }

    public function toggleAdminPlatformGateway(int $adminId, int $platformGatewayId): bool
    {
        $this->ensureAdminGatewaysProvisioned($adminId);

        $stmt = $this->pdo->prepare("
            UPDATE admin_platform_gateways
            SET is_active = NOT is_active, updated_at = NOW()
            WHERE admin_id = ? AND platform_gateway_id = ?
        ");
        $stmt->execute([$adminId, $platformGatewayId]);

        // Retourner le nouvel état
        $stmt2 = $this->pdo->prepare("SELECT is_active FROM admin_platform_gateways WHERE admin_id = ? AND platform_gateway_id = ?");
        $stmt2->execute([$adminId, $platformGatewayId]);
        return (bool)$stmt2->fetchColumn();
    }

    /**
     * Passerelles plateforme actives pour un admin (pour pay.php)
     */
    public function getActiveForAdmin(int $adminId): array
    {
        $settings = $this->getSettings();
        if ($settings['paygate_enabled'] !== '1') return [];

        $stmt = $this->pdo->prepare("
            SELECT pg.id, pg.gateway_code, pg.name, pg.description
            FROM platform_payment_gateways pg
            INNER JOIN admin_platform_gateways apg ON apg.platform_gateway_id = pg.id
            WHERE pg.is_active = 1 AND apg.is_active = 1 AND apg.admin_id = ?
            ORDER BY pg.display_order
        ");
        $stmt->execute([$adminId]);
        $gateways = $stmt->fetchAll();

        foreach ($gateways as &$gw) {
            $gw['is_platform'] = true;
        }
        return $gateways;
    }

    // ===================================================
    // PAYMENT PROCESSING
    // ===================================================

    /**
     * Initier un paiement via passerelle plateforme.
     * Crée la transaction avec is_platform=1, puis délègue à la logique du gateway.
     */
    public function initiatePayment(string $gatewayCode, int $profileId, int $adminId, array $customerData = []): array
    {
        $settings = $this->getSettings();
        if ($settings['paygate_enabled'] !== '1') {
            throw new Exception('Le système Paygate n\'est pas activé');
        }

        $platformGw = $this->getPlatformGatewayByCode($gatewayCode);
        if (!$platformGw || !$platformGw['is_active']) {
            throw new Exception('Passerelle plateforme non disponible');
        }

        // Vérifier que l'admin a activé cette passerelle
        $stmt = $this->pdo->prepare("
            SELECT is_active FROM admin_platform_gateways
            WHERE admin_id = ? AND platform_gateway_id = ?
        ");
        $stmt->execute([$adminId, $platformGw['id']]);
        $adminActive = $stmt->fetchColumn();
        if (!$adminActive) {
            throw new Exception('Passerelle non activée par l\'administrateur');
        }

        // Charger le profil
        $profileStmt = $this->pdo->prepare("SELECT * FROM profiles WHERE id = ? AND is_active = 1");
        $profileStmt->execute([$profileId]);
        $profile = $profileStmt->fetch();
        if (!$profile) {
            throw new Exception('Profile not available');
        }

        // Normaliser le téléphone avec le code pays de l'admin
        if (!empty($customerData['phone'])) {
            $customerData['phone'] = $this->normalizePhone($customerData['phone'], $adminId);
        }

        $currency = $this->config['currency'] ?? 'XOF';
        if ($gatewayCode === 'fedapay' && $currency === 'XAF') {
            $currency = 'XOF';
        }

        // Créer la transaction avec flag plateforme
        $transactionId = 'TXN_' . strtoupper(bin2hex(random_bytes(12)));
        $deviceInfo = isset($customerData['device_info']) ? json_encode($customerData['device_info']) : null;

        $stmt = $this->pdo->prepare("
            INSERT INTO payment_transactions
            (transaction_id, gateway_code, profile_id, amount, currency, customer_phone, customer_email, customer_name, device_info, admin_id, status, is_platform, platform_gateway_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1, ?)
        ");
        $stmt->execute([
            $transactionId,
            $gatewayCode,
            $profileId,
            $profile['price'],
            $currency,
            $customerData['phone'] ?? null,
            $customerData['email'] ?? null,
            $customerData['name'] ?? null,
            $deviceInfo,
            $adminId,
            $platformGw['id']
        ]);

        // Recharger la transaction
        $txStmt = $this->pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
        $txStmt->execute([$transactionId]);
        $transaction = $txStmt->fetch();

        // Construire les URLs callback avec &platform=1
        $callbackUrl = $this->baseUrl . '/payment-callback.php?admin=' . $adminId . '&platform=1';
        $returnUrl = $this->baseUrl . '/payment-success.php?txn=' . $transactionId;

        // Résoudre la config API depuis la passerelle recharge (source unique)
        $rechargeGw = $this->getRechargeGatewayConfig($gatewayCode);
        if (!$rechargeGw || empty($rechargeGw['config'])) {
            throw new Exception('Aucune configuration API trouvée pour la passerelle: ' . $gatewayCode);
        }

        $gateway = [
            'config' => $rechargeGw['config'],
            'is_sandbox' => $rechargeGw['is_sandbox'],
            'gateway_code' => $gatewayCode,
        ];

        switch ($gatewayCode) {
            case 'fedapay':
                return $this->initiateFedaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'cinetpay':
                return $this->initiateCinetPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'yengapay':
                return $this->initiateYengaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'paygate':
            case 'paygate_global':
                return $this->initiatePayGate($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            default:
                throw new Exception('Passerelle plateforme non implémentée: ' . $gatewayCode);
        }
    }

    /**
     * FedaPay - Initier paiement (même logique que PaymentService)
     */
    private function initiateFedaPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;
        $apiUrl = $isSandbox ? 'https://sandbox-api.fedapay.com/v1' : 'https://api.fedapay.com/v1';
        $processUrl = $isSandbox ? 'https://sandbox-process.fedapay.com' : 'https://process.fedapay.com';

        $payload = [
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'amount' => (int)$transaction['amount'],
            'currency' => ['iso' => $transaction['currency']],
            'callback_url' => $callbackUrl
        ];

        $customer = [];
        $fullName = trim($customerData['name'] ?? '');
        if ($fullName) {
            $nameParts = explode(' ', $fullName, 2);
            $customer['firstname'] = $nameParts[0];
            $customer['lastname'] = $nameParts[1] ?? $nameParts[0];
        }
        if (!empty($customerData['email'])) {
            $customer['email'] = $customerData['email'];
        }
        if (!empty($customerData['phone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $customerData['phone']);
            $customer['phone_number'] = ['number' => $phone, 'country' => $this->getCountryIsoFromPhone($phone)];
        }
        if (!empty($customer)) {
            $payload['customer'] = $customer;
        }

        $payload['custom_metadata'] = [
            'transaction_id' => $transaction['transaction_id'],
            'profile_id' => (string)$profile['id'],
            'return_url' => $returnUrl
        ];

        $headers = [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/json'
        ];

        $response = $this->httpPost($apiUrl . '/transactions', $payload, $headers);
        $fedaTransaction = $response['v1/transaction'] ?? $response['v1']['transaction'] ?? $response['transaction'] ?? null;

        if (!$fedaTransaction || !isset($fedaTransaction['id'])) {
            error_log('Platform FedaPay create transaction error: ' . json_encode($response));
            $errorMsg = $response['message'] ?? '';
            if (isset($response['errors'])) {
                $errDetails = is_array($response['errors']) ? json_encode($response['errors'], JSON_UNESCAPED_UNICODE) : $response['errors'];
                $errorMsg = $errorMsg ? $errorMsg . ' - ' . $errDetails : $errDetails;
            }
            throw new Exception('FedaPay: ' . ($errorMsg ?: json_encode($response, JSON_UNESCAPED_UNICODE)));
        }

        $tokenResponse = $this->httpPost($apiUrl . '/transactions/' . $fedaTransaction['id'] . '/token', [], $headers);
        $token = $tokenResponse['v1/token'] ?? $tokenResponse['v1']['token'] ?? $tokenResponse['token'] ?? null;

        if (!$token) {
            throw new Exception('FedaPay: Impossible de générer le token de paiement');
        }

        $paymentUrl = $processUrl . '/' . $token;

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => (string)$fedaTransaction['id'],
            'gateway_response' => json_encode(['transaction' => $fedaTransaction, 'token' => $token])
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => (string)$fedaTransaction['id']
        ];
    }

    /**
     * CinetPay - Initier paiement (même logique que PaymentService)
     */
    private function initiateCinetPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api-checkout.cinetpay.com/v2/payment';

        $cinetPayTransId = preg_replace('/[^A-Za-z0-9]/', '', $transaction['transaction_id']);
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        $amount = (int)$transaction['amount'];
        if ($transaction['currency'] !== 'USD' && $amount % 5 !== 0) {
            $amount = (int)(ceil($amount / 5) * 5);
        }

        $payload = [
            'apikey' => $config['api_key'],
            'site_id' => $config['site_id'],
            'transaction_id' => $cinetPayTransId,
            'amount' => $amount,
            'currency' => $transaction['currency'],
            'description' => 'Voucher WiFi ' . $profile['name'],
            'notify_url' => $callbackUrl . '&gateway=cinetpay',
            'return_url' => $returnUrl,
            'channels' => 'ALL',
            'lang' => 'fr',
            'metadata' => json_encode([
                'profile_id' => $profile['id'],
                'original_transaction_id' => $transaction['transaction_id']
            ])
        ];

        $payload['customer_name'] = !empty($customerData['name']) ? $customerData['name'] : 'Client';
        $payload['customer_surname'] = !empty($customerData['surname']) ? $customerData['surname'] : 'WiFi';
        $payload['customer_email'] = !empty($customerData['email']) ? $customerData['email'] : 'client@wifi.local';
        $payload['customer_address'] = 'Adresse';
        $payload['customer_city'] = 'Douala';
        $payload['customer_country'] = 'CM';
        $payload['customer_state'] = 'CM';
        $payload['customer_zip_code'] = '00000';

        if (!empty($phone) && strlen($phone) >= 8) {
            $payload['customer_phone_number'] = $phone;
        }

        $response = $this->httpPost($apiUrl, $payload, ['Content-Type: application/json']);

        if (isset($response['code']) && $response['code'] === '201' && isset($response['data']['payment_url'])) {
            $this->updateTransaction($transaction['transaction_id'], [
                'gateway_transaction_id' => $response['data']['payment_token'] ?? $cinetPayTransId,
                'gateway_response' => json_encode($response)
            ]);

            return [
                'success' => true,
                'payment_url' => $response['data']['payment_url'],
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $response['data']['payment_token'] ?? null
            ];
        }

        $errorMsg = $response['message'] ?? 'Unknown error';
        throw new Exception("CinetPay: $errorMsg (" . ($response['code'] ?? '') . ")");
    }

    /**
     * YengaPay - Initier paiement
     */
    private function initiateYengaPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $groupeId = $config['groupe_id'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $projectId = $config['project_id'] ?? '';

        if (!$groupeId || !$apiKey || !$projectId) {
            throw new Exception('YengaPay: Configuration incomplète (groupe_id, api_key, project_id requis)');
        }

        $apiUrl = sprintf('https://api.yengapay.com/api/v1/groups/%s/payment-intent/%s', $groupeId, $projectId);

        $amount = (float)$transaction['amount'];
        $description = 'Voucher WiFi ' . $profile['name'];

        $payload = [
            'paymentAmount' => $amount,
            'reference' => $transaction['transaction_id'],
            'articles' => [
                [
                    'title' => $description,
                    'description' => $description,
                    'price' => $amount,
                    'pictures' => []
                ]
            ]
        ];

        $headers = [
            'x-api-key: ' . $apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $response = $this->httpPost($apiUrl, $payload, $headers);

        $paymentUrl = $response['checkoutPageUrlWithPaymentToken'] ?? null;
        if (!$paymentUrl) {
            error_log('Platform YengaPay error: ' . json_encode($response));
            throw new Exception('YengaPay: ' . ($response['message'] ?? 'Impossible de créer le paiement'));
        }

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => $response['id'] ?? $transaction['transaction_id'],
            'gateway_response' => json_encode($response)
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => $response['id'] ?? null
        ];
    }

    /**
     * PayGate Global - Initier paiement (redirection vers page PayGate)
     */
    private function initiatePayGate(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];

        if (empty($config['auth_token'])) {
            throw new Exception('PayGate: Configuration incomplète (auth_token requis)');
        }

        $params = [
            'token' => $config['auth_token'],
            'amount' => (int)$transaction['amount'],
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'identifier' => $transaction['transaction_id'],
            'url' => $returnUrl,
        ];

        // Téléphone et réseau optionnels
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');
        if ($phone) {
            if (strlen($phone) <= 8 && !str_starts_with($phone, '228')) {
                $phone = '228' . $phone;
            }
            $params['phone'] = $phone;
        }

        $network = strtoupper($customerData['network'] ?? '');
        if ($network) {
            $params['network'] = $network;
        }

        $paymentUrl = 'https://paygateglobal.com/v1/page?' . http_build_query($params);

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_response' => json_encode([
                'network' => $network,
                'phone' => $phone,
                'method' => 'redirect'
            ])
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'message' => 'Redirection vers PayGate...'
        ];
    }

    private function normalizePhone(string $phone, int $adminId): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return '';

        $countryCode = '229';
        $stmt = $this->pdo->prepare("SELECT country_code FROM otp_config WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $cc = $stmt->fetchColumn();
        if ($cc) $countryCode = preg_replace('/[^0-9]/', '', $cc);

        if (strlen($phone) <= 9 && !str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        return $phone;
    }

    private function getCountryIsoFromPhone(string $phone): string
    {
        $map = [
            '229' => 'bj', '228' => 'tg', '226' => 'bf', '225' => 'ci',
            '221' => 'sn', '237' => 'cm', '242' => 'cg', '241' => 'ga',
            '235' => 'td', '227' => 'ne', '223' => 'ml', '224' => 'gn',
        ];
        foreach ($map as $prefix => $iso) {
            if (str_starts_with($phone, $prefix)) return $iso;
        }
        return 'bj';
    }

    private function updateTransaction(string $transactionId, array $data): void
    {
        $updates = [];
        $params = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $transactionId;
        $stmt = $this->pdo->prepare("UPDATE payment_transactions SET " . implode(', ', $updates) . " WHERE transaction_id = ?");
        $stmt->execute($params);
    }

    private function httpPost(string $url, array $data, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) throw new Exception('HTTP error: ' . $error);
        if ($httpCode >= 400) {
            error_log("HTTP POST {$url} returned {$httpCode}: " . $response);
        }

        return json_decode($response, true) ?? [];
    }

    // ===================================================
    // ADMIN BALANCE & TRANSACTIONS
    // ===================================================

    /**
     * Créditer le solde paygate d'un admin (après paiement client réussi)
     */
    public function creditAdminBalance(int $adminId, float $amount, string $refType, string $refId, string $description): float
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$adminId]);
            $currentBalance = (float)$stmt->fetchColumn();

            $newBalance = $currentBalance + $amount;

            $this->pdo->prepare("UPDATE users SET paygate_balance = ? WHERE id = ?")->execute([$newBalance, $adminId]);

            $this->pdo->prepare("
                INSERT INTO paygate_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                VALUES (?, 'payment_received', ?, ?, ?, ?, ?)
            ")->execute([$adminId, $amount, $newBalance, $refType, $refId, $description]);

            $this->pdo->commit();
            return $newBalance;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Débiter le solde paygate d'un admin
     */
    private function debitAdminBalance(int $adminId, float $amount, string $type, string $refType, string $refId, string $description): float
    {
        $stmt = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$adminId]);
        $currentBalance = (float)$stmt->fetchColumn();

        if ($currentBalance < $amount) {
            throw new Exception('Solde insuffisant');
        }

        $newBalance = $currentBalance - $amount;

        $this->pdo->prepare("UPDATE users SET paygate_balance = ? WHERE id = ?")->execute([$newBalance, $adminId]);

        $this->pdo->prepare("
            INSERT INTO paygate_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$adminId, $type, -$amount, $newBalance, $refType, $refId, $description]);

        return $newBalance;
    }

    public function getBalance(int $adminId): array
    {
        $stmt = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $balance = (float)$stmt->fetchColumn();

        // Stats
        $stmtStats = $this->pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type = 'payment_received' THEN amount ELSE 0 END), 0) as total_collected,
                COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN ABS(amount) ELSE 0 END), 0) as total_withdrawn,
                COALESCE(SUM(CASE WHEN type = 'commission' THEN ABS(amount) ELSE 0 END), 0) as total_commission
            FROM paygate_transactions WHERE admin_id = ?
        ");
        $stmtStats->execute([$adminId]);
        $stats = $stmtStats->fetch();

        // Retraits en attente
        $stmtPending = $this->pdo->prepare("
            SELECT COALESCE(SUM(amount_requested), 0) as pending_amount,
                   COUNT(*) as pending_count
            FROM paygate_withdrawals
            WHERE admin_id = ? AND status IN ('pending', 'approved')
        ");
        $stmtPending->execute([$adminId]);
        $pending = $stmtPending->fetch();

        // Per-gateway settings
        $gwStmt = $this->pdo->query("
            SELECT id, gateway_code, name, commission_rate, min_withdrawal, withdrawal_currency
            FROM platform_payment_gateways WHERE is_active = 1 ORDER BY display_order, id
        ");
        $gateways = [];
        foreach ($gwStmt->fetchAll() as $gw) {
            $gateways[] = [
                'id' => (int)$gw['id'],
                'gateway_code' => $gw['gateway_code'],
                'name' => $gw['name'],
                'commission_rate' => (float)$gw['commission_rate'],
                'min_withdrawal' => (float)$gw['min_withdrawal'],
                'currency' => $gw['withdrawal_currency'],
            ];
        }

        return [
            'balance' => $balance,
            'total_collected' => (float)$stats['total_collected'],
            'total_withdrawn' => (float)$stats['total_withdrawn'],
            'total_commission' => (float)$stats['total_commission'],
            'pending_amount' => (float)$pending['pending_amount'],
            'pending_count' => (int)$pending['pending_count'],
            'gateways' => $gateways,
        ];
    }

    public function getTransactions(int $adminId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM paygate_transactions WHERE admin_id = ?");
        $stmtCount->execute([$adminId]);
        $total = (int)$stmtCount->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT * FROM paygate_transactions
            WHERE admin_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$adminId, $perPage, $offset]);

        return [
            'transactions' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => max(1, ceil($total / $perPage)),
        ];
    }

    // ===================================================
    // WITHDRAWALS (Admin)
    // ===================================================

    public function requestWithdrawal(int $adminId, float $amount, string $paymentMethod, array $paymentDetails, ?string $note = null, ?int $platformGatewayId = null): array
    {
        $settings = $this->getSettings();

        if ($settings['paygate_enabled'] !== '1') {
            throw new Exception('Le système Paygate n\'est pas activé');
        }

        // Lire les paramètres per-gateway
        if ($platformGatewayId) {
            $gwStmt = $this->pdo->prepare("SELECT commission_rate, min_withdrawal, withdrawal_currency FROM platform_payment_gateways WHERE id = ?");
            $gwStmt->execute([$platformGatewayId]);
            $gwSettings = $gwStmt->fetch();
            if (!$gwSettings) {
                throw new Exception('Passerelle non trouvée');
            }
            $commissionRate = (float)$gwSettings['commission_rate'];
            $minWithdrawal = (float)$gwSettings['min_withdrawal'];
            $currency = $gwSettings['withdrawal_currency'];
        } else {
            // Fallback global settings (rétrocompatibilité)
            $commissionRate = (float)$settings['paygate_commission_rate'];
            $minWithdrawal = (float)$settings['paygate_min_withdrawal'];
            $currency = $settings['paygate_withdrawal_currency'];
        }

        if ($amount < $minWithdrawal) {
            throw new Exception("Le montant minimum de retrait est de {$minWithdrawal} {$currency}");
        }

        // Vérifier le solde
        $stmt = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $balance = (float)$stmt->fetchColumn();

        if ($balance < $amount) {
            throw new Exception('Solde insuffisant pour ce retrait');
        }

        // Vérifier pas de retrait en cours
        $stmtPending = $this->pdo->prepare("
            SELECT COUNT(*) FROM paygate_withdrawals
            WHERE admin_id = ? AND status IN ('pending', 'approved')
        ");
        $stmtPending->execute([$adminId]);
        if ((int)$stmtPending->fetchColumn() > 0) {
            throw new Exception('Vous avez déjà une demande de retrait en cours');
        }

        // Calculer commission
        $commissionAmount = round($amount * $commissionRate / 100, 2);
        $amountNet = round($amount - $commissionAmount, 2);

        $stmt = $this->pdo->prepare("
            INSERT INTO paygate_withdrawals
            (admin_id, amount_requested, commission_rate, commission_amount, amount_net, currency, payment_method, payment_details, admin_note, platform_gateway_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminId, $amount, $commissionRate, $commissionAmount, $amountNet,
            $currency,
            $paymentMethod,
            json_encode($paymentDetails),
            $note,
            $platformGatewayId
        ]);

        return [
            'id' => (int)$this->pdo->lastInsertId(),
            'amount_requested' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'amount_net' => $amountNet,
            'currency' => $currency,
        ];
    }

    public function cancelWithdrawal(int $adminId, int $withdrawalId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE paygate_withdrawals SET status = 'cancelled'
            WHERE id = ? AND admin_id = ? AND status = 'pending'
        ");
        $stmt->execute([$withdrawalId, $adminId]);
        return $stmt->rowCount() > 0;
    }

    public function getWithdrawals(int $adminId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM paygate_withdrawals WHERE admin_id = ?");
        $stmtCount->execute([$adminId]);
        $total = (int)$stmtCount->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT * FROM paygate_withdrawals
            WHERE admin_id = ?
            ORDER BY requested_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$adminId, $perPage, $offset]);

        $withdrawals = $stmt->fetchAll();
        foreach ($withdrawals as &$w) {
            $w['payment_details'] = json_decode($w['payment_details'] ?: '{}', true);
        }

        return [
            'withdrawals' => $withdrawals,
            'total' => $total,
            'page' => $page,
            'total_pages' => max(1, ceil($total / $perPage)),
        ];
    }

    // ===================================================
    // WITHDRAWALS (SuperAdmin)
    // ===================================================

    public function listAllWithdrawals(array $filters = []): array
    {
        $page = (int)($filters['page'] ?? 1);
        $perPage = (int)($filters['per_page'] ?? 20);
        $offset = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'w.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['admin_id'])) {
            $where[] = 'w.admin_id = ?';
            $params[] = (int)$filters['admin_id'];
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM paygate_withdrawals w {$whereClause}");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare("
            SELECT w.*, u.username as admin_username, u.full_name as admin_display_name,
                   p.username as processed_by_username
            FROM paygate_withdrawals w
            LEFT JOIN users u ON u.id = w.admin_id
            LEFT JOIN users p ON p.id = w.processed_by
            {$whereClause}
            ORDER BY
                CASE w.status
                    WHEN 'pending' THEN 1
                    WHEN 'approved' THEN 2
                    ELSE 3
                END,
                w.requested_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute($params);

        $withdrawals = $stmt->fetchAll();
        foreach ($withdrawals as &$w) {
            $w['payment_details'] = json_decode($w['payment_details'] ?: '{}', true);
        }

        return [
            'withdrawals' => $withdrawals,
            'total' => $total,
            'page' => $page,
            'total_pages' => max(1, ceil($total / $perPage)),
        ];
    }

    public function approveWithdrawal(int $withdrawalId, int $superAdminId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE paygate_withdrawals
            SET status = 'approved', processed_by = ?, processed_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->execute([$superAdminId, $withdrawalId]);
        return $stmt->rowCount() > 0;
    }

    public function completeWithdrawal(int $withdrawalId, int $superAdminId, string $transferReference, ?string $note = null): array
    {
        $this->pdo->beginTransaction();
        try {
            // Charger le retrait
            $stmt = $this->pdo->prepare("SELECT * FROM paygate_withdrawals WHERE id = ? AND status IN ('pending', 'approved')");
            $stmt->execute([$withdrawalId]);
            $withdrawal = $stmt->fetch();

            if (!$withdrawal) {
                throw new Exception('Demande de retrait introuvable ou déjà traitée');
            }

            $adminId = (int)$withdrawal['admin_id'];
            $amount = (float)$withdrawal['amount_requested'];

            // Lock et vérifier le solde
            $stmtBal = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ? FOR UPDATE");
            $stmtBal->execute([$adminId]);
            $currentBalance = (float)$stmtBal->fetchColumn();

            if ($currentBalance < $amount) {
                throw new Exception("Solde insuffisant (solde: {$currentBalance}, retrait: {$amount})");
            }

            // Débiter
            $this->debitAdminBalance(
                $adminId, $amount, 'withdrawal',
                'withdrawal_request', (string)$withdrawalId,
                "Retrait #{$withdrawalId} - Réf: {$transferReference}"
            );

            // Mettre à jour le retrait
            $stmtUpdate = $this->pdo->prepare("
                UPDATE paygate_withdrawals
                SET status = 'completed', processed_by = ?, transfer_reference = ?,
                    superadmin_note = ?, processed_at = COALESCE(processed_at, NOW()), completed_at = NOW()
                WHERE id = ?
            ");
            $stmtUpdate->execute([$superAdminId, $transferReference, $note, $withdrawalId]);

            $this->pdo->commit();

            return [
                'withdrawal_id' => $withdrawalId,
                'amount_requested' => $amount,
                'commission_amount' => (float)$withdrawal['commission_amount'],
                'amount_net' => (float)$withdrawal['amount_net'],
                'transfer_reference' => $transferReference,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function rejectWithdrawal(int $withdrawalId, int $superAdminId, ?string $reason = null): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE paygate_withdrawals
            SET status = 'rejected', processed_by = ?, superadmin_note = ?, processed_at = NOW()
            WHERE id = ? AND status IN ('pending', 'approved')
        ");
        $stmt->execute([$superAdminId, $reason, $withdrawalId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Ajustement manuel du solde (superadmin)
     */
    public function adjustBalance(int $adminId, float $amount, int $superAdminId, ?string $reason = null): array
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT paygate_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$adminId]);
            $currentBalance = (float)$stmt->fetchColumn();

            $newBalance = $currentBalance + $amount;
            if ($newBalance < 0) {
                throw new Exception('Le solde ne peut pas être négatif');
            }

            $this->pdo->prepare("UPDATE users SET paygate_balance = ? WHERE id = ?")->execute([$newBalance, $adminId]);

            $this->pdo->prepare("
                INSERT INTO paygate_transactions (admin_id, type, amount, balance_after, reference_type, description, created_by)
                VALUES (?, 'adjustment', ?, ?, 'manual', ?, ?)
            ")->execute([$adminId, $amount, $newBalance, $reason ?? 'Ajustement manuel', $superAdminId]);

            $this->pdo->commit();

            return [
                'previous_balance' => $currentBalance,
                'adjustment' => $amount,
                'new_balance' => $newBalance,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // ===================================================
    // STATS (SuperAdmin)
    // ===================================================

    public function getPlatformStats(): array
    {
        // Total collecté (tous admins)
        $stmtCollected = $this->pdo->query("
            SELECT COALESCE(SUM(amount), 0) as total
            FROM paygate_transactions WHERE type = 'payment_received'
        ");
        $totalCollected = (float)$stmtCollected->fetch()['total'];

        // Retraits en attente
        $stmtPending = $this->pdo->query("
            SELECT COUNT(*) as count, COALESCE(SUM(amount_requested), 0) as total
            FROM paygate_withdrawals WHERE status IN ('pending', 'approved')
        ");
        $pending = $stmtPending->fetch();

        // Total retiré
        $stmtWithdrawn = $this->pdo->query("
            SELECT COALESCE(SUM(amount_requested), 0) as total
            FROM paygate_withdrawals WHERE status = 'completed'
        ");
        $totalWithdrawn = (float)$stmtWithdrawn->fetch()['total'];

        // Total commissions
        $stmtCommission = $this->pdo->query("
            SELECT COALESCE(SUM(commission_amount), 0) as total
            FROM paygate_withdrawals WHERE status = 'completed'
        ");
        $totalCommission = (float)$stmtCommission->fetch()['total'];

        // Admins actifs (avec solde > 0)
        $stmtAdmins = $this->pdo->query("SELECT COUNT(*) FROM users WHERE paygate_balance > 0");
        $activeAdmins = (int)$stmtAdmins->fetchColumn();

        return [
            'total_collected' => $totalCollected,
            'pending_withdrawals_count' => (int)$pending['count'],
            'pending_withdrawals_amount' => (float)$pending['total'],
            'total_withdrawn' => $totalWithdrawn,
            'total_commission' => $totalCommission,
            'active_admins' => $activeAdmins,
        ];
    }
}
