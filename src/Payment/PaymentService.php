<?php
/**
 * Service de paiement - Gestion des passerelles de paiement
 */

class PaymentService
{
    private RadiusDatabase $db;
    private array $config;
    private string $baseUrl;

    public function __construct(RadiusDatabase $db, array $config = [])
    {
        $this->db = $db;
        $this->config = $config;
        $this->baseUrl = $config['app']['base_url'] ?? $this->detectBaseUrl();
    }

    /**
     * Détecter l'URL de base automatiquement
     * Retourne l'URL du répertoire web (où se trouvent les scripts PHP)
     */
    private function detectBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Utiliser dirname(SCRIPT_NAME) pour détecter le répertoire web
        // Ex: /nas/web/api.php → /nas/web, /api.php → /
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(dirname($scriptName), '/\\');
        if ($basePath === '.' || $basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }

        return $protocol . '://' . $host . $basePath;
    }

    /**
     * Normaliser un numéro de téléphone en ajoutant le code pays si nécessaire
     */
    private function normalizePhone(string $phone, ?int $adminId): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($phone)) return '';

        // Récupérer le code pays de l'admin depuis otp_config
        $countryCode = '229'; // Bénin par défaut
        if ($adminId) {
            $stmt = $this->db->getPdo()->prepare("SELECT country_code FROM otp_config WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $cc = $stmt->fetchColumn();
            if ($cc) $countryCode = preg_replace('/[^0-9]/', '', $cc);
        }

        // Ajouter le code pays si le numéro est local (max 9 chiffres)
        if (strlen($phone) <= 9 && !str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        return $phone;
    }

    /**
     * Déterminer le code ISO pays à partir du préfixe téléphonique
     */
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
        return 'bj'; // défaut Bénin
    }

    /**
     * Générer un ID de transaction unique
     */
    public function generateTransactionId(): string
    {
        return 'TXN_' . strtoupper(bin2hex(random_bytes(12)));
    }

    /**
     * Créer une nouvelle transaction de paiement
     */
    public function createTransaction(array $data): array
    {
        $transactionId = $this->generateTransactionId();

        $pdo = $this->db->getPdo();
        $deviceInfo = isset($data['device_info']) ? json_encode($data['device_info']) : null;

        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions
            (transaction_id, gateway_code, profile_id, amount, currency, customer_phone, customer_email, customer_name, device_info, admin_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->execute([
            $transactionId,
            $data['gateway_code'],
            $data['profile_id'],
            $data['amount'],
            $data['currency'] ?? 'XAF',
            $data['customer_phone'] ?? null,
            $data['customer_email'] ?? null,
            $data['customer_name'] ?? null,
            $deviceInfo,
            $data['admin_id'] ?? null
        ]);

        return $this->getTransaction($transactionId);
    }

    /**
     * Obtenir une transaction par son ID
     */
    public function getTransaction(string $transactionId): ?array
    {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
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
     * Mettre à jour une transaction
     */
    public function updateTransaction(string $transactionId, array $data): bool
    {
        $pdo = $this->db->getPdo();

        $updates = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key === 'gateway_response') {
                $value = json_encode($value);
            }
            $updates[] = "$key = ?";
            $params[] = $value;
        }

        $params[] = $transactionId;

        $stmt = $pdo->prepare("UPDATE payment_transactions SET " . implode(', ', $updates) . " WHERE transaction_id = ?");
        return $stmt->execute($params);
    }

    /**
     * Marquer une transaction comme payée et générer le voucher
     */
    public function completeTransaction(string $transactionId, string $gatewayTransactionId, array $gatewayResponse = [], ?string $operatorReference = null): array
    {
        $transaction = $this->getTransaction($transactionId);

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        if ($transaction['status'] === 'completed') {
            return [
                'success' => true,
                'voucher_code' => $transaction['voucher_code'] ?? null,
                'message' => 'Transaction already completed'
            ];
        }

        // Branche credit_recharge : ajouter des crédits au lieu de générer un voucher
        if (($transaction['transaction_type'] ?? 'voucher_purchase') === 'credit_recharge') {
            return $this->completeCreditRecharge($transaction, $gatewayTransactionId, $gatewayResponse, $operatorReference);
        }

        // Récupérer le profil
        $profile = $this->db->getProfileById($transaction['profile_id']);
        if (!$profile) {
            throw new Exception('Profile not found');
        }

        // Générer le voucher
        $voucherData = $this->generateVoucherFromProfile($profile);

        // Résoudre l'admin_id : depuis le profil ou la transaction
        $adminId = $profile['admin_id'] ?? $transaction['admin_id'] ?? null;

        // Calculer la validité depuis le profil (en secondes)
        $validFrom = null;
        $validUntil = null;
        if (!empty($profile['validity']) && $profile['validity'] > 0) {
            $validFrom = date('Y-m-d H:i:s');
            $validUntil = date('Y-m-d H:i:s', time() + (int)$profile['validity']);
        }

        // Créer le voucher dans la base de données
        $voucherId = $this->db->createVoucher([
            'username' => $voucherData['code'],
            'password' => $voucherData['password'],
            'time_limit' => $profile['time_limit'],
            'data_limit' => $profile['data_limit'],
            'download_speed' => $profile['download_speed'],
            'upload_speed' => $profile['upload_speed'],
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'simultaneous_use' => $profile['simultaneous_use'] ?? 1,
            'price' => $transaction['amount'],
            'profile_id' => $profile['id'],
            'batch_id' => 'PAYMENT_' . date('Ymd'),
            'admin_id' => $adminId,
            'customer_phone' => $transaction['customer_phone'] ?? null,
            'customer_name' => $transaction['customer_name'] ?? null,
        ]);

        // Déterminer la méthode de paiement selon la passerelle
        $paymentMethod = $this->determinePaymentMethod($transaction['gateway_code']);

        // Marquer le voucher comme vendu immédiatement avec la bonne méthode de paiement
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            UPDATE vouchers SET
                payment_method = ?,
                sale_amount = ?,
                sold_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$paymentMethod, $transaction['amount'], $voucherId]);

        // Préparer les données de mise à jour
        $updateData = [
            'status' => 'completed',
            'gateway_transaction_id' => $gatewayTransactionId,
            'gateway_response' => $gatewayResponse,
            'voucher_id' => $voucherId,
            'voucher_code' => $voucherData['code'],
            'paid_at' => date('Y-m-d H:i:s')
        ];

        // Ajouter la référence opérateur si fournie
        if ($operatorReference) {
            $updateData['operator_reference'] = $operatorReference;
        }

        // Mettre à jour la transaction
        $this->updateTransaction($transactionId, $updateData);

        // Si paiement via passerelle plateforme → créditer le solde paygate de l'admin
        if (($transaction['is_platform'] ?? 0) == 1 && $adminId) {
            try {
                require_once __DIR__ . '/PlatformPaymentService.php';
                $platformService = new PlatformPaymentService($this->db->getPdo(), $this->config);
                $platformService->creditAdminBalance(
                    (int)$adminId,
                    (float)$transaction['amount'],
                    'payment_transaction',
                    $transactionId,
                    'Paiement client via passerelle plateforme'
                );
            } catch (Exception $e) {
                error_log('Paygate credit error: ' . $e->getMessage());
            }
        }

        // Enregistrer l'achat dans le système de fidélité si activé et le client a un numéro de téléphone
        $loyaltyResult = null;
        if (!empty($transaction['customer_phone']) && $this->isLoyaltyAutoRecordEnabled($adminId)) {
            $loyaltyResult = $this->recordLoyaltyPurchase(
                $transaction['customer_phone'],
                $transactionId,
                (float)$transaction['amount'],
                (int)$profile['id'],
                $profile['name'],
                $adminId
            );
        }

        return [
            'success' => true,
            'voucher_code' => $voucherData['code'],
            'voucher_password' => $voucherData['password'],
            'profile' => $profile,
            'loyalty' => $loyaltyResult
        ];
    }

    /**
     * Vérifier si l'enregistrement automatique fidélité est activé
     */
    private function isLoyaltyAutoRecordEnabled(?int $adminId): bool
    {
        try {
            $pdo = $this->db->getPdo();
            $sql = "SELECT setting_value FROM settings WHERE setting_key = 'loyalty_auto_record'";
            $params = [];
            if ($adminId !== null) {
                $sql .= " AND admin_id = ?";
                $params[] = $adminId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $value = $stmt->fetchColumn();
            return $value === '1';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Enregistrer un achat dans le système de fidélité
     */
    private function recordLoyaltyPurchase(string $phone, string $transactionId, float $amount, int $profileId, string $profileName, ?int $adminId = null): ?array
    {
        try {
            $pdo = $this->db->getPdo();

            // Normaliser le numéro de téléphone
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            if (str_starts_with($phone, '00')) {
                $phone = '+' . substr($phone, 2);
            }

            // Créer ou récupérer le client fidèle (scoped par admin)
            $custSql = "SELECT * FROM loyalty_customers WHERE phone = ?";
            $custParams = [$phone];
            if ($adminId !== null) {
                $custSql .= " AND admin_id = ?";
                $custParams[] = $adminId;
            }
            $stmt = $pdo->prepare($custSql);
            $stmt->execute($custParams);
            $customer = $stmt->fetch();

            if (!$customer) {
                // Créer le client
                $insertStmt = $pdo->prepare("
                    INSERT INTO loyalty_customers (phone, total_purchases, total_spent, first_purchase_at, last_purchase_at, admin_id)
                    VALUES (?, 1, ?, NOW(), NOW(), ?)
                ");
                $insertStmt->execute([$phone, $amount, $adminId]);
                $customerId = $pdo->lastInsertId();
                $newPurchaseCount = 1;
            } else {
                // Mettre à jour le client
                $customerId = $customer['id'];
                $newPurchaseCount = (int)$customer['total_purchases'] + 1;
                $updateStmt = $pdo->prepare("
                    UPDATE loyalty_customers SET
                        total_purchases = total_purchases + 1,
                        total_spent = total_spent + ?,
                        points_earned = points_earned + 1,
                        points_balance = points_balance + 1,
                        last_purchase_at = NOW()
                    WHERE id = ?
                ");
                $updateStmt->execute([$amount, $customerId]);
            }

            // Enregistrer l'achat
            $purchaseStmt = $pdo->prepare("
                INSERT INTO loyalty_purchases (customer_id, transaction_id, amount, profile_id, profile_name, points_earned, admin_id)
                VALUES (?, ?, ?, ?, ?, 1, ?)
            ");
            $purchaseStmt->execute([$customerId, $transactionId, $amount, $profileId, $profileName, $adminId]);

            // Vérifier si le client a gagné une récompense
            $reward = $this->checkLoyaltyReward($customerId, $newPurchaseCount, $adminId);

            return [
                'customer_id' => $customerId,
                'total_purchases' => $newPurchaseCount,
                'reward_earned' => $reward
            ];

        } catch (Exception $e) {
            // Log l'erreur mais ne pas bloquer le paiement
            error_log('Loyalty error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifier si un client a gagné une récompense et générer automatiquement le voucher bonus
     */
    private function checkLoyaltyReward(int $customerId, int $totalPurchases, ?int $adminId = null): ?array
    {
        $pdo = $this->db->getPdo();

        // Récupérer les règles actives basées sur le nombre d'achats
        $rulesSql = "
            SELECT lr.*, p.name as profile_name, p.time_limit, p.data_limit,
                   p.download_speed, p.upload_speed, p.validity, p.simultaneous_use
            FROM loyalty_rules lr
            LEFT JOIN profiles p ON lr.reward_profile_id = p.id
            WHERE lr.is_active = 1
            AND lr.rule_type = 'purchase_count'
            AND (lr.valid_from IS NULL OR lr.valid_from <= NOW())
            AND (lr.valid_until IS NULL OR lr.valid_until >= NOW())
        ";
        $rulesParams = [];
        if ($adminId !== null) {
            $rulesSql .= " AND lr.admin_id = ?";
            $rulesParams[] = $adminId;
        }
        $rulesSql .= " ORDER BY lr.threshold_value";
        $rulesStmt = $pdo->prepare($rulesSql);
        $rulesStmt->execute($rulesParams);
        $rules = $rulesStmt->fetchAll();

        foreach ($rules as $rule) {
            // Vérifier si le seuil est atteint (ex: 5, 10, 15 achats)
            if ($totalPurchases > 0 && $totalPurchases % $rule['threshold_value'] === 0) {
                // Vérifier la limite de récompenses par client
                if ($rule['max_rewards_per_customer']) {
                    $countStmt = $pdo->prepare("
                        SELECT COUNT(*) FROM loyalty_rewards
                        WHERE customer_id = ? AND rule_id = ?
                    ");
                    $countStmt->execute([$customerId, $rule['id']]);
                    if ($countStmt->fetchColumn() >= $rule['max_rewards_per_customer']) {
                        continue; // Limite atteinte
                    }
                }

                // Créer la récompense
                $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

                // Générer automatiquement le voucher bonus si c'est un type free_voucher et qu'un profil est configuré
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

                        $status = 'pending';
                    }
                }

                // Insérer la récompense
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
     * Déterminer la méthode de paiement selon le code de la passerelle
     */
    private function determinePaymentMethod(string $gatewayCode): string
    {
        // Passerelles Mobile Money
        $mobileMoneyGateways = ['fedapay', 'cinetpay', 'feexpay', 'paygate', 'paydunya', 'kkiapay', 'moneroo', 'ligdicash'];

        // Passerelles paiement en ligne (carte bancaire, crypto, etc.)
        $onlineGateways = ['stripe', 'paypal', 'cryptomus'];

        if (in_array($gatewayCode, $mobileMoneyGateways)) {
            return 'mobile_money';
        } elseif (in_array($gatewayCode, $onlineGateways)) {
            return 'online';
        }

        // Par défaut, considérer comme paiement en ligne
        return 'online';
    }

    /**
     * Générer les données d'un voucher à partir d'un profil
     */
    private function generateVoucherFromProfile(array $profile): array
    {
        // Générer un code alphanumérique unique
        $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        $password = $code; // Même code pour le mot de passe par défaut

        return [
            'code' => $code,
            'password' => $password
        ];
    }

    /**
     * Initialiser un paiement avec une passerelle
     */
    public function initiatePayment(string $gatewayCode, int $profileId, array $customerData = []): array
    {
        $profile = $this->db->getProfileById($profileId);

        if (!$profile || !$profile['is_active']) {
            throw new Exception('Profile not available');
        }

        // Utiliser l'admin_id du profil pour scoper la passerelle
        $profileAdminId = $profile['admin_id'] ?? null;
        $gateway = $this->db->getPaymentGatewayByCode($gatewayCode, $profileAdminId);

        if (!$gateway || !$gateway['is_active']) {
            throw new Exception('Payment gateway not available');
        }

        // Normaliser le téléphone avec le code pays de l'admin
        if (!empty($customerData['phone'])) {
            $customerData['phone'] = $this->normalizePhone($customerData['phone'], $profileAdminId);
        }

        // Déterminer la devise (XOF par défaut pour FedaPay)
        $currency = $this->config['currency'] ?? 'XOF';

        // FedaPay ne supporte que XOF, GNF, USD, EUR - convertir XAF en XOF si nécessaire
        if ($gatewayCode === 'fedapay' && $currency === 'XAF') {
            $currency = 'XOF'; // XAF et XOF ont la même valeur
        }

        // Créer la transaction
        $transaction = $this->createTransaction([
            'gateway_code' => $gatewayCode,
            'profile_id' => $profileId,
            'amount' => $profile['price'],
            'currency' => $currency,
            'customer_phone' => $customerData['phone'] ?? null,
            'customer_email' => $customerData['email'] ?? null,
            'customer_name' => $customerData['name'] ?? null,
            'device_info' => $customerData['device_info'] ?? null,
            'admin_id' => $profileAdminId
        ]);

        // Préparer les URLs de callback
        $adminCallbackParam = !empty($profile['admin_id']) ? '?admin=' . $profile['admin_id'] : '';
        $callbackUrl = $this->baseUrl . '/payment-callback.php' . $adminCallbackParam;
        $returnUrl = $this->baseUrl . '/payment-success.php?txn=' . $transaction['transaction_id'];
        $adminParam = !empty($profile['admin_id']) ? '&admin=' . $profile['admin_id'] : '';
        $cancelUrl = $this->baseUrl . '/pay.php?profile=' . $profileId . $adminParam . '&cancelled=1';

        // Initialiser le paiement selon la passerelle
        switch ($gatewayCode) {
            case 'fedapay':
                return $this->initiateFedaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'cinetpay':
                return $this->initiateCinetPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'stripe':
                return $this->initiateStripe($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'paypal':
                return $this->initiatePayPal($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'feexpay':
                return $this->initiateFeexPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'paygate':
            case 'paygate_global':
                return $this->initiatePayGate($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'paydunya':
                return $this->initiatePayDunya($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'kkiapay':
                return $this->initiateKkiapay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'moneroo':
                return $this->initiateMoneroo($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'ligdicash':
                return $this->initiateLigdiCash($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'cryptomus':
                return $this->initiateCryptomus($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            case 'yengapay':
                return $this->initiateYengaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);

            default:
                throw new Exception('Gateway not implemented: ' . $gatewayCode);
        }
    }

    /**
     * FedaPay - Initialiser le paiement
     * Documentation: https://docs.fedapay.com/
     */
    private function initiateFedaPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;
        $apiUrl = $isSandbox ? 'https://sandbox-api.fedapay.com/v1' : 'https://api.fedapay.com/v1';
        $processUrl = $isSandbox ? 'https://sandbox-process.fedapay.com' : 'https://process.fedapay.com';

        // Étape 1: Créer la transaction FedaPay (format simplifié selon la doc)
        $payload = [
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'amount' => (int)$transaction['amount'],
            'currency' => [
                'iso' => $transaction['currency']
            ],
            'callback_url' => $callbackUrl
        ];

        // Pré-remplir les infos client pour simplifier le formulaire FedaPay
        $customer = [];

        // Nom et prénom
        $fullName = trim($customerData['name'] ?? '');
        if ($fullName) {
            $nameParts = explode(' ', $fullName, 2);
            $customer['firstname'] = $nameParts[0];
            $customer['lastname'] = $nameParts[1] ?? $nameParts[0];
        }

        // Email
        if (!empty($customerData['email'])) {
            $customer['email'] = $customerData['email'];
        }

        // Téléphone (déjà normalisé avec code pays par normalizePhone())
        if (!empty($customerData['phone'])) {
            $phone = preg_replace('/[^0-9+]/', '', $customerData['phone']);
            $countryIso = $this->getCountryIsoFromPhone($phone);
            $customer['phone_number'] = [
                'number' => $phone,
                'country' => $countryIso
            ];
        }

        if (!empty($customer)) {
            $payload['customer'] = $customer;
        }

        // Ajouter custom_metadata pour notre référence
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

        // FedaPay retourne les données avec la clé 'v1/transaction' (avec slash)
        // Essayer plusieurs formats possibles de la réponse
        $fedaTransaction = $response['v1/transaction'] ?? $response['v1']['transaction'] ?? $response['transaction'] ?? null;

        if (!$fedaTransaction || !isset($fedaTransaction['id'])) {
            // Log l'erreur pour debug
            error_log('FedaPay create transaction error: ' . json_encode($response));
            $errorMsg = $response['message'] ?? '';
            // Toujours inclure les détails d'erreur de FedaPay
            if (isset($response['errors'])) {
                $errDetails = is_array($response['errors']) ? json_encode($response['errors'], JSON_UNESCAPED_UNICODE) : $response['errors'];
                $errorMsg = $errorMsg ? $errorMsg . ' - ' . $errDetails : $errDetails;
            }
            if (empty($errorMsg)) {
                $errorMsg = 'Réponse inattendue: ' . json_encode($response, JSON_UNESCAPED_UNICODE);
            }
            throw new Exception('FedaPay: ' . $errorMsg);
        }

        // Étape 2: Générer le token de paiement
        $tokenResponse = $this->httpPost($apiUrl . '/transactions/' . $fedaTransaction['id'] . '/token', [], $headers);

        // Essayer plusieurs formats pour le token également
        $token = $tokenResponse['v1/token'] ?? $tokenResponse['v1']['token'] ?? $tokenResponse['token'] ?? null;

        if (!$token) {
            error_log('FedaPay token error: ' . json_encode($tokenResponse));
            throw new Exception('FedaPay: Impossible de générer le token de paiement');
        }

        // Construire l'URL de paiement
        $paymentUrl = $processUrl . '/' . $token;

        // Mettre à jour notre transaction avec l'ID FedaPay
        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => (string)$fedaTransaction['id'],
            'gateway_response' => [
                'transaction' => $fedaTransaction,
                'token' => $token
            ]
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => (string)$fedaTransaction['id']
        ];
    }

    /**
     * CinetPay - Initialiser le paiement
     * Documentation: https://docs.cinetpay.com/api/1.0-fr/checkout/initialisation
     */
    private function initiateCinetPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api-checkout.cinetpay.com/v2/payment';

        // Nettoyer le transaction_id (CinetPay n'accepte pas certains caractères)
        // Notre format TXN_XXXX est valide mais on s'assure qu'il n'y a pas de caractères spéciaux
        $cinetPayTransId = preg_replace('/[^A-Za-z0-9]/', '', $transaction['transaction_id']);

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        // CinetPay: le montant doit être un multiple de 5 (sauf USD)
        $amount = (int)$transaction['amount'];
        if ($transaction['currency'] !== 'USD' && $amount % 5 !== 0) {
            // Arrondir au multiple de 5 supérieur
            $amount = (int)(ceil($amount / 5) * 5);
        }

        $payload = [
            'apikey' => $config['api_key'],
            'site_id' => $config['site_id'],
            'transaction_id' => $cinetPayTransId,
            'amount' => $amount,
            'currency' => $transaction['currency'],
            'description' => 'Voucher WiFi ' . $profile['name'],
            'notify_url' => $callbackUrl . '?gateway=cinetpay',
            'return_url' => $returnUrl,
            'channels' => 'ALL',
            'lang' => 'fr',
            'metadata' => json_encode([
                'profile_id' => $profile['id'],
                'original_transaction_id' => $transaction['transaction_id']
            ])
        ];

        // Ajouter les infos client (requis par CinetPay)
        $payload['customer_name'] = !empty($customerData['name']) ? $customerData['name'] : 'Client';
        $payload['customer_surname'] = !empty($customerData['surname']) ? $customerData['surname'] : 'WiFi';
        $payload['customer_email'] = !empty($customerData['email']) ? $customerData['email'] : 'client@wifi.local';

        // Champs requis par CinetPay (ne peuvent pas être vides)
        $payload['customer_address'] = 'Adresse';
        $payload['customer_city'] = 'Douala';
        $payload['customer_country'] = 'CM'; // Cameroun par défaut
        $payload['customer_state'] = 'CM';
        $payload['customer_zip_code'] = '00000';

        // Numéro de téléphone - seulement si fourni et valide (au moins 8 chiffres)
        if (!empty($phone) && strlen($phone) >= 8) {
            $payload['customer_phone_number'] = $phone;
            // Ne pas utiliser lock_phone_number car il requiert un numéro d'opérateur supporté
            // L'utilisateur pourra modifier le numéro sur la page CinetPay si besoin
        }

        error_log('CinetPay initiate payload: ' . json_encode($payload));

        $response = $this->httpPost($apiUrl, $payload, [
            'Content-Type: application/json'
        ]);

        error_log('CinetPay initiate response: ' . json_encode($response));

        // Vérifier le code de réponse CinetPay
        if (isset($response['code']) && $response['code'] === '201' && isset($response['data']['payment_url'])) {
            // Stocker le payment_token pour la vérification ultérieure
            $this->updateTransaction($transaction['transaction_id'], [
                'gateway_transaction_id' => $response['data']['payment_token'] ?? $cinetPayTransId,
                'gateway_response' => $response
            ]);

            return [
                'success' => true,
                'payment_url' => $response['data']['payment_url'],
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $response['data']['payment_token'] ?? null
            ];
        }

        // Gérer les erreurs CinetPay
        $errorMsg = $response['message'] ?? 'Unknown error';
        $errorCode = $response['code'] ?? '';
        $errorDesc = $response['description'] ?? '';

        error_log("CinetPay error: code=$errorCode, message=$errorMsg, description=$errorDesc");

        throw new Exception("CinetPay: $errorMsg ($errorCode) - $errorDesc");
    }

    /**
     * Stripe - Initialiser le paiement
     */
    private function initiateStripe(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api.stripe.com/v1/checkout/sessions';

        $payload = http_build_query([
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => strtolower($transaction['currency']),
            'line_items[0][price_data][product_data][name]' => 'Voucher WiFi - ' . $profile['name'],
            'line_items[0][price_data][unit_amount]' => (int)($transaction['amount'] * 100),
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => $returnUrl,
            'cancel_url' => $returnUrl . '&cancelled=1',
            'metadata[transaction_id]' => $transaction['transaction_id'],
            'metadata[profile_id]' => $profile['id']
        ]);

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_USERPWD, $config['secret_key'] . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (isset($response['url'])) {
            $this->updateTransaction($transaction['transaction_id'], [
                'gateway_transaction_id' => $response['id'],
                'gateway_response' => $response
            ]);

            return [
                'success' => true,
                'payment_url' => $response['url'],
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $response['id']
            ];
        }

        throw new Exception('Stripe API error: ' . json_encode($response));
    }

    /**
     * PayPal - Initialiser le paiement
     */
    private function initiatePayPal(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = $gateway['is_sandbox'] ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

        // Obtenir le token d'accès
        $ch = curl_init($apiUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $config['client_id'] . ':' . $config['client_secret']);

        $tokenResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!isset($tokenResponse['access_token'])) {
            throw new Exception('PayPal auth error');
        }

        // Créer l'ordre
        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $transaction['transaction_id'],
                'description' => 'Voucher WiFi - ' . $profile['name'],
                'amount' => [
                    'currency_code' => $transaction['currency'],
                    'value' => number_format($transaction['amount'], 2, '.', '')
                ]
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $returnUrl . '&cancelled=1'
            ]
        ];

        $response = $this->httpPost($apiUrl . '/v2/checkout/orders', $payload, [
            'Authorization: Bearer ' . $tokenResponse['access_token'],
            'Content-Type: application/json'
        ]);

        if (isset($response['id'])) {
            $approveLink = '';
            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approveLink = $link['href'];
                    break;
                }
            }

            $this->updateTransaction($transaction['transaction_id'], [
                'gateway_transaction_id' => $response['id'],
                'gateway_response' => $response
            ]);

            return [
                'success' => true,
                'payment_url' => $approveLink,
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $response['id']
            ];
        }

        throw new Exception('PayPal API error: ' . json_encode($response));
    }

    /**
     * Vérifier le statut d'un paiement
     */
    public function checkPaymentStatus(string $transactionId): array
    {
        $transaction = $this->getTransaction($transactionId);

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        if ($transaction['status'] === 'completed') {
            return [
                'status' => 'completed',
                'voucher_code' => $transaction['voucher_code'],
                'profile_id' => $transaction['profile_id']
            ];
        }

        // Si pending, vérifier auprès de la passerelle
        if ($transaction['status'] === 'pending' && $transaction['gateway_transaction_id']) {
            $gatewayStatus = $this->checkGatewayStatus($transaction);
            if ($gatewayStatus && $gatewayStatus !== 'pending') {
                return [
                    'status' => $gatewayStatus,
                    'transaction_id' => $transactionId
                ];
            }
        }

        return [
            'status' => $transaction['status'],
            'transaction_id' => $transactionId
        ];
    }

    /**
     * Vérifier le statut auprès de la passerelle de paiement
     */
    public function checkGatewayStatus(array $transaction): ?string
    {
        $adminId = $transaction['admin_id'] ?? null;
        $gateway = $this->db->getPaymentGatewayByCode($transaction['gateway_code'], $adminId);

        if (!$gateway) {
            return null;
        }

        switch ($transaction['gateway_code']) {
            case 'fedapay':
                return $this->checkFedaPayStatus($gateway, $transaction);
            case 'cinetpay':
                return $this->checkCinetPayStatus($gateway, $transaction);
            case 'feexpay':
                return $this->checkFeexPayStatus($gateway, $transaction);
            case 'paygate':
            case 'paygate_global':
                return $this->checkPayGateStatus($gateway, $transaction);
            case 'paydunya':
                return $this->checkPayDunyaStatus($gateway, $transaction);
            case 'kkiapay':
                return $this->checkKkiapayStatus($gateway, $transaction);
            case 'moneroo':
                return $this->checkMonerooStatus($gateway, $transaction);
            default:
                return null;
        }
    }

    /**
     * Vérifier le statut FedaPay d'une transaction
     */
    private function checkFedaPayStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;
        $apiUrl = $isSandbox ? 'https://sandbox-api.fedapay.com/v1' : 'https://api.fedapay.com/v1';

        $headers = [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/json'
        ];

        try {
            $response = $this->httpGet($apiUrl . '/transactions/' . $transaction['gateway_transaction_id'], $headers);

            // FedaPay retourne les données avec la clé 'v1/transaction' (avec slash)
            $fedaTransaction = $response['v1/transaction'] ?? $response['v1']['transaction'] ?? $response['transaction'] ?? null;

            if ($fedaTransaction) {
                $status = $fedaTransaction['status'] ?? null;

                // Mapper les statuts FedaPay vers nos statuts
                // FedaPay: pending, approved, canceled, declined, refunded
                if ($status === 'approved') {
                    // Compléter la transaction
                    $this->completeTransaction(
                        $transaction['transaction_id'],
                        (string)$fedaTransaction['id'],
                        $fedaTransaction
                    );
                    return 'completed';
                } elseif (in_array($status, ['canceled', 'declined', 'refunded'])) {
                    $this->updateTransaction($transaction['transaction_id'], [
                        'status' => 'failed',
                        'gateway_response' => $fedaTransaction
                    ]);
                    return 'failed';
                }

                return 'pending';
            }
        } catch (Exception $e) {
            error_log('FedaPay check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier le statut CinetPay d'une transaction
     * Documentation: https://docs.cinetpay.com/api/1.0-fr/checkout/verification
     */
    private function checkCinetPayStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api-checkout.cinetpay.com/v2/payment/check';

        // CinetPay utilise le transaction_id sans caractères spéciaux
        $cinetPayTransId = preg_replace('/[^A-Za-z0-9]/', '', $transaction['transaction_id']);

        $payload = [
            'apikey' => $config['api_key'],
            'site_id' => $config['site_id'],
            'transaction_id' => $cinetPayTransId
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, [
                'Content-Type: application/json'
            ]);

            error_log('CinetPay check status response: ' . json_encode($response));

            // CinetPay retourne code "00" pour succès et status "ACCEPTED" ou "REFUSED"
            if (isset($response['code']) && $response['code'] === '00') {
                $status = $response['data']['status'] ?? '';
                $operatorId = $response['data']['operator_id'] ?? null;

                if ($status === 'ACCEPTED') {
                    // Extraire la référence opérateur
                    $operatorReference = $operatorId;

                    // Compléter la transaction
                    $result = $this->completeTransaction(
                        $transaction['transaction_id'],
                        $response['data']['payment_token'] ?? $cinetPayTransId,
                        $response['data'],
                        $operatorReference
                    );
                    return 'completed';
                } elseif (in_array($status, ['REFUSED', 'CANCELLED'])) {
                    $this->updateTransaction($transaction['transaction_id'], [
                        'status' => 'failed',
                        'gateway_response' => $response['data'] ?? $response
                    ]);
                    return 'failed';
                }

                // Statut en attente (PENDING, WAITING_FOR_CUSTOMER)
                return 'pending';
            }

            // Erreur de vérification (transaction non trouvée, etc.)
            error_log('CinetPay check status error: ' . json_encode($response));

        } catch (Exception $e) {
            error_log('CinetPay check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier le statut CinetPay et retourner les détails complets
     * Utilisé par le callback pour obtenir le vrai statut
     */
    public function verifyCinetPayTransaction(string $transactionId, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('cinetpay', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];
        $apiUrl = 'https://api-checkout.cinetpay.com/v2/payment/check';

        // CinetPay utilise le transaction_id sans caractères spéciaux
        $cinetPayTransId = preg_replace('/[^A-Za-z0-9]/', '', $transactionId);

        $payload = [
            'apikey' => $config['api_key'],
            'site_id' => $config['site_id'],
            'transaction_id' => $cinetPayTransId
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, [
                'Content-Type: application/json'
            ]);

            error_log('CinetPay verify transaction response: ' . json_encode($response));

            if (isset($response['code']) && $response['code'] === '00' && isset($response['data'])) {
                return $response['data'];
            }

            return null;

        } catch (Exception $e) {
            error_log('CinetPay verify transaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * FeexPay - Initialiser le paiement
     * Documentation: https://docs.feexpay.me/api_rest.html
     */
    private function initiateFeexPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;

        // Opérateur: priorité au choix client, sinon config admin, sinon mtn par défaut
        $operator = $customerData['operator'] ?? $config['operator'] ?? 'mtn';

        // FeexPay utilise le même endpoint en sandbox et production, le mode est déterminé par l'API key
        $apiUrl = 'https://api.feexpay.me/api/transactions/public/requesttopay/' . $operator;

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        // Déterminer le code pays selon l'opérateur
        $countryCodes = [
            'mtn' => '229', 'moov' => '229', 'celtiis_bj' => '229', 'coris' => '229', // Bénin
            'togocom_tg' => '228', 'moov_tg' => '228', // Togo
            'moov_bf' => '226', 'orange_bf' => '226', // Burkina Faso
            'orange_sn' => '221', 'free_sn' => '221', // Sénégal
            'mtn_ci' => '225', 'moov_ci' => '225', 'wave_ci' => '225', 'orange_ci' => '225', // Côte d'Ivoire
            'mtn_cm' => '237', 'orange_cm' => '237', // Cameroun
            'mtn_cg' => '242', // Congo
        ];
        $countryCode = $countryCodes[$operator] ?? '229';

        // Ajouter le code pays si le numéro est local (8-9 chiffres selon le pays)
        if (strlen($phone) <= 9 && !str_starts_with($phone, $countryCode)) {
            $phone = $countryCode . $phone;
        }

        $payload = [
            'shop' => $config['shop_id'],
            'amount' => (int)$transaction['amount'],
            'phoneNumber' => $phone,
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'callback_info' => $transaction['transaction_id'], // Notre ID pour identifier la transaction au callback
            'firstName' => $customerData['name'] ?? 'Client',
            'lastName' => ''
        ];

        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        error_log('FeexPay initiate request: ' . json_encode($payload));

        try {
            $response = $this->httpPost($apiUrl, $payload, $headers);
            error_log('FeexPay initiate response: ' . json_encode($response));

            // FeexPay renvoie une référence de transaction en cas de succès
            if (isset($response['reference'])) {
                // Mettre à jour la transaction avec la référence FeexPay
                $this->updateTransaction($transaction['transaction_id'], [
                    'gateway_transaction_id' => $response['reference'],
                    'gateway_response' => $response
                ]);

                // FeexPay utilise un système USSD (push vers le téléphone du client)
                // Il n'y a pas d'URL de paiement, le client reçoit un pop-up USSD
                // On redirige vers la page de succès en mode "pending"
                // Note: $returnUrl contient déjà ?txn=XXX, donc on l'utilise directement
                return [
                    'success' => true,
                    'payment_url' => $returnUrl,
                    'transaction_id' => $transaction['transaction_id'],
                    'gateway_transaction_id' => $response['reference'],
                    'message' => 'Un message USSD a été envoyé sur votre téléphone. Validez le paiement.'
                ];
            }

            // Erreur FeexPay
            $errorMsg = $response['responsemsg'] ?? $response['message'] ?? 'Erreur FeexPay inconnue';
            throw new Exception($errorMsg);

        } catch (Exception $e) {
            error_log('FeexPay initiate error: ' . $e->getMessage());

            // Marquer la transaction comme échouée
            $this->updateTransaction($transaction['transaction_id'], [
                'status' => 'failed',
                'gateway_response' => ['error' => $e->getMessage()]
            ]);

            throw new Exception('Erreur FeexPay: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier le statut FeexPay d'une transaction
     * Documentation: https://docs.feexpay.me/api_rest.html
     */
    private function checkFeexPayStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api.feexpay.me/api/transactions/public/single/status/' . $transaction['gateway_transaction_id'];

        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('FeexPay check status response: ' . json_encode($response));

            // FeexPay renvoie: SUCCESSFUL, FAILED, PENDING
            $status = $response['status'] ?? null;

            if ($status === 'SUCCESSFUL') {
                // Extraire la référence opérateur si disponible
                $operatorReference = $response['reference'] ?? null;

                // Compléter la transaction
                $this->completeTransaction(
                    $transaction['transaction_id'],
                    $response['reference'] ?? $transaction['gateway_transaction_id'],
                    $response,
                    $operatorReference
                );
                return 'completed';
            } elseif ($status === 'FAILED') {
                $this->updateTransaction($transaction['transaction_id'], [
                    'status' => 'failed',
                    'gateway_response' => $response
                ]);
                return 'failed';
            }

            // Statut en attente
            return 'pending';

        } catch (Exception $e) {
            error_log('FeexPay check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier le statut FeexPay et retourner les détails complets
     * Utilisé par le callback pour obtenir le vrai statut
     */
    public function verifyFeexPayTransaction(string $reference, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('feexpay', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];
        $apiUrl = 'https://api.feexpay.me/api/transactions/public/single/status/' . $reference;

        $headers = [
            'Authorization: Bearer ' . $config['api_key'],
            'Content-Type: application/json'
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('FeexPay verify transaction response: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            error_log('FeexPay verify transaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * PayGate Global - Initialiser le paiement
     * Documentation: https://paygateglobal.com
     *
     * Utilise la Méthode 2 (redirection vers page de paiement)
     * Services: FLOOZ, TMONEY (Togo)
     */
    private function initiatePayGate(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];

        // Réseau: priorité au choix client, sinon FLOOZ par défaut
        $network = strtoupper($customerData['network'] ?? 'FLOOZ');

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        // Ajouter indicatif Togo si nécessaire
        if (strlen($phone) <= 8 && !str_starts_with($phone, '228')) {
            $phone = '228' . $phone;
        }

        // Construire l'URL de redirection vers la page PayGate (Méthode 2)
        $paymentUrl = 'https://paygateglobal.com/v1/page?' . http_build_query([
            'token' => $config['auth_token'],
            'amount' => (int)$transaction['amount'],
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'identifier' => $transaction['transaction_id'],
            'url' => $returnUrl,
            'phone' => $phone,
            'network' => $network
        ]);

        // Mettre à jour la transaction avec les infos PayGate
        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_response' => [
                'network' => $network,
                'phone' => $phone,
                'method' => 'redirect'
            ]
        ]);

        error_log('PayGate initiate: ' . json_encode([
            'transaction_id' => $transaction['transaction_id'],
            'network' => $network,
            'amount' => $transaction['amount']
        ]));

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'message' => 'Redirection vers PayGate...'
        ];
    }

    /**
     * Vérifier le statut PayGate d'une transaction
     * Documentation: https://paygateglobal.com/api/v2/status
     */
    private function checkPayGateStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $apiUrl = 'https://paygateglobal.com/api/v2/status';

        $payload = [
            'auth_token' => $config['auth_token'],
            'identifier' => $transaction['transaction_id']
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, ['Content-Type: application/json']);
            error_log('PayGate check status response: ' . json_encode($response));

            // Statuts PayGate: 0 = succès, 2 = en cours, 4 = expiré, 6 = annulé
            $status = $response['status'] ?? null;

            if ($status === 0) {
                // Extraire la référence de paiement opérateur
                $paymentReference = $response['payment_reference'] ?? null;

                // Compléter la transaction
                $this->completeTransaction(
                    $transaction['transaction_id'],
                    $response['tx_reference'] ?? $transaction['transaction_id'],
                    $response,
                    $paymentReference
                );
                return 'completed';
            } elseif (in_array($status, [4, 6])) {
                // Expiré ou annulé
                $this->updateTransaction($transaction['transaction_id'], [
                    'status' => 'failed',
                    'gateway_response' => $response
                ]);
                return 'failed';
            }

            // Status 2 = en cours
            return 'pending';

        } catch (Exception $e) {
            error_log('PayGate check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier le statut PayGate et retourner les détails complets
     * Utilisé par le callback pour obtenir le vrai statut
     */
    public function verifyPayGateTransaction(string $identifier, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('paygate', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];
        $apiUrl = 'https://paygateglobal.com/api/v2/status';

        $payload = [
            'auth_token' => $config['auth_token'],
            'identifier' => $identifier
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, ['Content-Type: application/json']);
            error_log('PayGate verify transaction response: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            error_log('PayGate verify transaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * PayDunya - Initialiser le paiement
     * Documentation: https://developers.paydunya.com/doc/FR/http_json
     *
     * Utilise l'API PSR (redirection vers page de paiement)
     * Supporte: Orange Money, Wave, MTN, Moov (Sénégal, Bénin, CI, Togo, Mali, BF)
     */
    private function initiatePayDunya(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;

        // Endpoints selon le mode
        $apiUrl = $isSandbox
            ? 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/create'
            : 'https://app.paydunya.com/api/v1/checkout-invoice/create';

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        // Construire le payload selon la documentation PayDunya
        $payload = [
            'invoice' => [
                'total_amount' => (int)$transaction['amount'],
                'description' => 'Achat voucher WiFi - ' . $profile['name'],
                'customer' => [
                    'name' => $customerData['name'] ?? 'Client',
                    'phone' => $phone,
                    'email' => $customerData['email'] ?? ''
                ]
            ],
            'store' => [
                'name' => $config['store_name'] ?? 'WiFi Hotspot'
            ],
            'actions' => [
                'callback_url' => $callbackUrl,
                'return_url' => $returnUrl,
                'cancel_url' => $returnUrl . '?cancelled=1'
            ],
            'custom_data' => [
                'transaction_id' => $transaction['transaction_id'],
                'profile_id' => $profile['id']
            ]
        ];

        // Headers d'authentification PayDunya
        $headers = [
            'Content-Type: application/json',
            'PAYDUNYA-MASTER-KEY: ' . $config['master_key'],
            'PAYDUNYA-PRIVATE-KEY: ' . $config['private_key'],
            'PAYDUNYA-TOKEN: ' . $config['token']
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, $headers);
            error_log('PayDunya initiate response: ' . json_encode($response));

            // Vérifier la réponse
            if (isset($response['response_code']) && $response['response_code'] === '00') {
                // Succès - récupérer l'URL de paiement et le token
                $paymentUrl = $response['response_text'];
                $invoiceToken = $response['token'];

                // Mettre à jour la transaction avec le token PayDunya
                $this->updateTransaction($transaction['transaction_id'], [
                    'gateway_transaction_id' => $invoiceToken,
                    'gateway_response' => [
                        'invoice_token' => $invoiceToken,
                        'payment_url' => $paymentUrl,
                        'sandbox' => $isSandbox
                    ]
                ]);

                return [
                    'success' => true,
                    'payment_url' => $paymentUrl,
                    'transaction_id' => $transaction['transaction_id'],
                    'invoice_token' => $invoiceToken,
                    'message' => 'Redirection vers PayDunya...'
                ];
            } else {
                throw new Exception($response['response_text'] ?? 'Erreur PayDunya inconnue');
            }
        } catch (Exception $e) {
            error_log('PayDunya initiate error: ' . $e->getMessage());
            throw new Exception('Erreur PayDunya: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier le statut PayDunya d'une transaction
     * Documentation: https://developers.paydunya.com/doc/FR/http_json
     */
    private function checkPayDunyaStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;

        // Récupérer le token de la facture
        $gatewayResponse = $transaction['gateway_response'] ?? [];
        $invoiceToken = $gatewayResponse['invoice_token'] ?? $transaction['gateway_transaction_id'] ?? null;

        if (!$invoiceToken) {
            return null;
        }

        // Endpoint de vérification
        $apiUrl = $isSandbox
            ? 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/confirm/' . $invoiceToken
            : 'https://app.paydunya.com/api/v1/checkout-invoice/confirm/' . $invoiceToken;

        // Headers d'authentification PayDunya
        $headers = [
            'PAYDUNYA-MASTER-KEY: ' . $config['master_key'],
            'PAYDUNYA-PRIVATE-KEY: ' . $config['private_key'],
            'PAYDUNYA-TOKEN: ' . $config['token']
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('PayDunya check status response: ' . json_encode($response));

            // Statuts PayDunya: pending, completed, cancelled, failed
            $status = $response['status'] ?? null;

            if ($status === 'completed') {
                // Extraire les détails du paiement
                $receiptUrl = $response['receipt_url'] ?? null;
                $customerInfo = $response['customer'] ?? [];

                // Compléter la transaction
                $this->completeTransaction(
                    $transaction['transaction_id'],
                    $invoiceToken,
                    $response,
                    $response['receipt_identifier'] ?? null
                );
                return 'completed';
            } elseif (in_array($status, ['cancelled', 'failed'])) {
                // Marquée comme échouée
                $this->updateTransaction($transaction['transaction_id'], [
                    'status' => 'failed',
                    'gateway_response' => $response
                ]);
                return 'failed';
            }

            // Status pending
            return 'pending';

        } catch (Exception $e) {
            error_log('PayDunya check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier le statut PayDunya et retourner les détails complets
     * Utilisé par le callback pour obtenir le vrai statut
     */
    public function verifyPayDunyaTransaction(string $invoiceToken, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('paydunya', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;

        $apiUrl = $isSandbox
            ? 'https://app.paydunya.com/sandbox-api/v1/checkout-invoice/confirm/' . $invoiceToken
            : 'https://app.paydunya.com/api/v1/checkout-invoice/confirm/' . $invoiceToken;

        $headers = [
            'PAYDUNYA-MASTER-KEY: ' . $config['master_key'],
            'PAYDUNYA-PRIVATE-KEY: ' . $config['private_key'],
            'PAYDUNYA-TOKEN: ' . $config['token']
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('PayDunya verify transaction response: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            error_log('PayDunya verify transaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Kkiapay - Initialiser le paiement
     * Documentation: https://docs.kkiapay.me/v1/plugin-et-sdk/sdk-javascript
     *
     * Note: Kkiapay utilise un SDK JavaScript côté client.
     * Cette méthode retourne les données de configuration pour le widget JS.
     */
    private function initiateKkiapay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        // Convertir explicitement en booléen (PDO peut retourner "0" ou "1" comme string)
        $isSandbox = (bool)($gateway['is_sandbox'] ?? true);

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9]/', '', $customerData['phone'] ?? '');

        // Mettre à jour la transaction avec les infos initiales
        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_response' => [
                'method' => 'kkiapay_widget',
                'sandbox' => $isSandbox,
                'phone' => $phone
            ]
        ]);

        // Pour Kkiapay, on retourne les paramètres du widget JS
        // Le paiement sera géré côté client avec le SDK JavaScript
        return [
            'success' => true,
            'method' => 'kkiapay_widget',
            'transaction_id' => $transaction['transaction_id'],
            'widget_config' => [
                'amount' => (int)$transaction['amount'],
                'key' => $config['public_key'],
                'sandbox' => $isSandbox,
                'phone' => $phone,
                'name' => $customerData['name'] ?? 'Client',
                'email' => $customerData['email'] ?? '',
                'reason' => 'Achat voucher WiFi - ' . $profile['name'],
                'data' => [
                    'transaction_id' => $transaction['transaction_id'],
                    'profile_id' => $profile['id']
                ],
                'callback' => $returnUrl
            ],
            'return_url' => $returnUrl,
            'message' => 'Cliquez pour ouvrir le widget de paiement Kkiapay'
        ];
    }

    /**
     * Vérifier le statut Kkiapay d'une transaction
     * Documentation: https://docs.kkiapay.me/v1/plugin-et-sdk/admin-sdks-server-side/php-admin-sdk
     */
    private function checkKkiapayStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;

        // Kkiapay API de vérification
        $apiUrl = 'https://api.kkiapay.me/api/v1/transactions/status';

        // Récupérer l'ID de transaction Kkiapay
        $kkiapayTransactionId = $transaction['gateway_transaction_id'] ?? null;

        if (!$kkiapayTransactionId) {
            return null;
        }

        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $config['private_key']
        ];

        try {
            $response = $this->httpPost($apiUrl, ['transactionId' => $kkiapayTransactionId], $headers);
            error_log('Kkiapay check status response: ' . json_encode($response));

            // Kkiapay retourne le statut de la transaction
            $status = $response['status'] ?? null;

            if ($status === 'SUCCESS') {
                // Extraire les détails du paiement
                $this->completeTransaction(
                    $transaction['transaction_id'],
                    $kkiapayTransactionId,
                    $response,
                    $response['externalTransactionId'] ?? null
                );
                return 'completed';
            } elseif (in_array($status, ['FAILED', 'CANCELLED'])) {
                $this->updateTransaction($transaction['transaction_id'], [
                    'status' => 'failed',
                    'gateway_response' => $response
                ]);
                return 'failed';
            }

            // Status pending
            return 'pending';

        } catch (Exception $e) {
            error_log('Kkiapay check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier une transaction Kkiapay avec l'ID de transaction
     * Utilisé par le callback après paiement réussi
     */
    public function verifyKkiapayTransaction(string $kkiapayTransactionId, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('kkiapay', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];

        $apiUrl = 'https://api.kkiapay.me/api/v1/transactions/status';

        $headers = [
            'Content-Type: application/json',
            'X-API-KEY: ' . $config['private_key']
        ];

        try {
            $response = $this->httpPost($apiUrl, ['transactionId' => $kkiapayTransactionId], $headers);
            error_log('Kkiapay verify transaction response: ' . json_encode($response));
            return $response;
        } catch (Exception $e) {
            error_log('Kkiapay verify transaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compléter une transaction Kkiapay après callback du widget JS
     */
    public function completeKkiapayPayment(string $transactionId, string $kkiapayTransactionId): array
    {
        $transaction = $this->getTransaction($transactionId);

        if (!$transaction) {
            throw new Exception('Transaction not found');
        }

        if ($transaction['status'] === 'completed') {
            return [
                'success' => true,
                'voucher_code' => $transaction['voucher_code'],
                'message' => 'Transaction already completed'
            ];
        }

        // Vérifier la transaction auprès de Kkiapay
        $kkiapayData = $this->verifyKkiapayTransaction($kkiapayTransactionId);

        if (!$kkiapayData || ($kkiapayData['status'] ?? '') !== 'SUCCESS') {
            throw new Exception('Transaction Kkiapay non valide ou échouée');
        }

        // Vérifier que le montant correspond
        if (isset($kkiapayData['amount']) && (int)$kkiapayData['amount'] !== (int)$transaction['amount']) {
            throw new Exception('Montant de la transaction ne correspond pas');
        }

        // Mettre à jour avec l'ID gateway
        $this->updateTransaction($transactionId, [
            'gateway_transaction_id' => $kkiapayTransactionId
        ]);

        // Compléter la transaction
        return $this->completeTransaction(
            $transactionId,
            $kkiapayTransactionId,
            $kkiapayData,
            $kkiapayData['externalTransactionId'] ?? null
        );
    }

    /**
     * Générer un lien de paiement pour un profil
     */
    public function generatePaymentLink(int $profileId, ?int $adminId = null): string
    {
        $profile = $this->db->getProfileById($profileId);

        if (!$profile) {
            throw new Exception('Profile not found');
        }

        $url = $this->baseUrl . '/pay.php?profile=' . $profileId;

        // Ajouter l'admin_id pour l'isolation multi-tenant
        $effectiveAdminId = $adminId ?? ($profile['admin_id'] ?? null);
        if ($effectiveAdminId) {
            $url .= '&admin=' . $effectiveAdminId;
        }

        return $url;
    }

    /**
     * Requête HTTP POST
     */
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

        if ($error) {
            throw new Exception('HTTP error: ' . $error);
        }

        $decoded = json_decode($response, true) ?? [];
        // Ajouter le code HTTP pour le debug si erreur
        if ($httpCode >= 400) {
            error_log("HTTP POST {$url} returned {$httpCode}: " . $response);
        }

        return $decoded;
    }

    /**
     * Requête HTTP GET
     */
    private function httpGet(string $url, array $headers = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('HTTP error: ' . $error);
        }

        return json_decode($response, true) ?? [];
    }

    // ==========================================
    // Moneroo Payment Gateway
    // ==========================================

    /**
     * Moneroo - Initialiser le paiement
     * Documentation: https://docs.moneroo.io/payments/standard-integration
     */
    private function initiateMoneroo(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api.moneroo.io/v1/payments/initialize';

        // Nettoyer le numéro de téléphone
        $phone = preg_replace('/[^0-9+]/', '', $customerData['phone'] ?? '');

        // Préparer les données du client
        $customerEmail = $customerData['email'] ?? '';
        if (empty($customerEmail)) {
            // Générer un email temporaire si non fourni
            $customerEmail = 'client_' . $phone . '@hotspot.local';
        }

        // Préparer le payload selon la doc Moneroo
        $payload = [
            'amount' => (int)$transaction['amount'],
            'currency' => $transaction['currency'] ?? 'XOF',
            'description' => 'Achat voucher WiFi - ' . $profile['name'],
            'customer' => [
                'email' => $customerEmail,
                'first_name' => $customerData['name'] ?? 'Client',
                'last_name' => 'WiFi',
                'phone' => $phone
            ],
            'return_url' => $returnUrl,
            'metadata' => [
                'transaction_id' => $transaction['transaction_id'],
                'profile_id' => (string)$profile['id'],
                'phone' => $phone
            ]
        ];

        $headers = [
            'Authorization: Bearer ' . $config['secret_key'],
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        try {
            $response = $this->httpPost($apiUrl, $payload, $headers);
            error_log('Moneroo init response: ' . json_encode($response));

            if (!isset($response['data']['id']) || !isset($response['data']['checkout_url'])) {
                throw new Exception('Réponse Moneroo invalide: ' . json_encode($response));
            }

            $monerooPaymentId = $response['data']['id'];
            $checkoutUrl = $response['data']['checkout_url'];

            // Mettre à jour la transaction avec l'ID Moneroo
            $this->updateTransaction($transaction['transaction_id'], [
                'gateway_transaction_id' => $monerooPaymentId,
                'gateway_response' => $response
            ]);

            return [
                'success' => true,
                'redirect_url' => $checkoutUrl,
                'transaction_id' => $transaction['transaction_id'],
                'gateway_transaction_id' => $monerooPaymentId,
                'message' => 'Redirection vers Moneroo...'
            ];

        } catch (Exception $e) {
            error_log('Moneroo init error: ' . $e->getMessage());
            throw new Exception('Erreur Moneroo: ' . $e->getMessage());
        }
    }

    /**
     * Vérifier le statut Moneroo d'une transaction
     * Documentation: https://docs.moneroo.io/payments/retrieve-payment
     */
    private function checkMonerooStatus(array $gateway, array $transaction): ?string
    {
        $config = $gateway['config'];
        $monerooPaymentId = $transaction['gateway_transaction_id'] ?? null;

        if (!$monerooPaymentId) {
            return null;
        }

        $apiUrl = 'https://api.moneroo.io/v1/payments/' . $monerooPaymentId;

        $headers = [
            'Authorization: Bearer ' . $config['secret_key'],
            'Accept: application/json'
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('Moneroo check status response: ' . json_encode($response));

            $status = $response['data']['status'] ?? null;

            if ($status === 'success') {
                // Transaction réussie
                $this->completeTransaction(
                    $transaction['transaction_id'],
                    $monerooPaymentId,
                    $response['data'] ?? [],
                    $response['data']['reference'] ?? null
                );
                return 'completed';
            } elseif (in_array($status, ['failed', 'cancelled', 'expired'])) {
                $this->updateTransaction($transaction['transaction_id'], [
                    'status' => 'failed',
                    'gateway_response' => $response
                ]);
                return 'failed';
            }

            // Status pending
            return 'pending';

        } catch (Exception $e) {
            error_log('Moneroo check status error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Vérifier et compléter une transaction Moneroo
     * Utilisé par le callback après paiement
     */
    public function verifyMonerooPayment(string $monerooPaymentId, ?int $adminId = null): ?array
    {
        $gateway = $this->db->getPaymentGatewayByCode('moneroo', $adminId);
        if (!$gateway) {
            return null;
        }

        $config = $gateway['config'];
        $apiUrl = 'https://api.moneroo.io/v1/payments/' . $monerooPaymentId;

        $headers = [
            'Authorization: Bearer ' . $config['secret_key'],
            'Accept: application/json'
        ];

        try {
            $response = $this->httpGet($apiUrl, $headers);
            error_log('Moneroo verify response: ' . json_encode($response));
            return $response['data'] ?? null;
        } catch (Exception $e) {
            error_log('Moneroo verify error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compléter une transaction Moneroo après callback
     */
    public function completeMonerooPayment(string $monerooPaymentId): array
    {
        // Trouver la transaction par l'ID Moneroo
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE gateway_transaction_id = ? AND gateway_code = 'moneroo'");
        $stmt->execute([$monerooPaymentId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception('Transaction Moneroo non trouvée');
        }

        if ($transaction['status'] === 'completed') {
            return [
                'success' => true,
                'voucher_code' => $transaction['voucher_code'],
                'message' => 'Transaction déjà complétée'
            ];
        }

        // Vérifier le statut auprès de Moneroo
        $monerooData = $this->verifyMonerooPayment($monerooPaymentId);

        if (!$monerooData || ($monerooData['status'] ?? '') !== 'success') {
            throw new Exception('Transaction Moneroo non valide ou échouée');
        }

        // Vérifier le montant
        if (isset($monerooData['amount']) && (int)$monerooData['amount'] !== (int)$transaction['amount']) {
            throw new Exception('Montant de la transaction ne correspond pas');
        }

        // Compléter la transaction
        return $this->completeTransaction(
            $transaction['transaction_id'],
            $monerooPaymentId,
            $monerooData,
            $monerooData['reference'] ?? null
        );
    }

    // =============================================
    // CREDIT RECHARGE METHODS
    // =============================================

    /**
     * Initier un paiement de recharge de crédits
     */
    public function initiateRechargePayment(string $gatewayCode, float $amount, int $adminId, array $customerData = []): array
    {
        // Chercher la passerelle globale de recharge (admin_id IS NULL)
        $gateway = $this->db->getGlobalGatewayByCode($gatewayCode);
        if (!$gateway || !$gateway['is_active']) {
            throw new Exception('Payment gateway not available');
        }

        $currency = $this->config['currency'] ?? 'XOF';
        if ($gatewayCode === 'fedapay' && $currency === 'XAF') {
            $currency = 'XOF';
        }

        // Créer la transaction avec transaction_type = credit_recharge
        $transactionId = $this->generateTransactionId();
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("
            INSERT INTO payment_transactions
            (transaction_id, gateway_code, profile_id, amount, currency, customer_phone, customer_email, customer_name, admin_id, status, transaction_type)
            VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, 'pending', 'credit_recharge')
        ");
        $stmt->execute([
            $transactionId, $gatewayCode, $amount, $currency,
            $customerData['phone'] ?? null,
            $customerData['email'] ?? null,
            $customerData['name'] ?? null,
            $adminId
        ]);

        $transaction = $this->getTransaction($transactionId);

        // URLs de callback et retour — rediriger vers le dashboard admin après paiement
        $callbackUrl = $this->baseUrl . '/payment-callback.php?admin=' . $adminId;
        $returnUrl = $this->baseUrl . '/index.php?page=dashboard&recharge=success&txn=' . $transactionId;
        $cancelUrl = $this->baseUrl . '/index.php?page=dashboard';

        // Profil factice pour la description des gateways
        $profile = ['name' => 'Recharge crédits', 'id' => 0, 'price' => $amount];

        switch ($gatewayCode) {
            case 'fedapay':
                return $this->initiateFedaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'cinetpay':
                return $this->initiateCinetPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'stripe':
                return $this->initiateStripe($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'paypal':
                return $this->initiatePayPal($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'feexpay':
                return $this->initiateFeexPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'paygate':
            case 'paygate_global':
                return $this->initiatePayGate($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'paydunya':
                return $this->initiatePayDunya($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'kkiapay':
                return $this->initiateKkiapay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'moneroo':
                return $this->initiateMoneroo($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'ligdicash':
                return $this->initiateLigdiCash($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'cryptomus':
                return $this->initiateCryptomus($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            case 'yengapay':
                return $this->initiateYengaPay($gateway, $transaction, $profile, $customerData, $callbackUrl, $returnUrl);
            default:
                throw new Exception('Gateway not implemented: ' . $gatewayCode);
        }
    }

    /**
     * LigdiCash - Initialiser le paiement
     * Documentation: https://developers.ligdicash.com
     */
    private function initiateLigdiCash(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $isSandbox = $gateway['is_sandbox'] ?? true;
        $apiUrl = $isSandbox
            ? 'https://app.ligdicash.com/pay/v01/redirect/checkout-invoice/create'
            : 'https://app.ligdicash.com/pay/v01/redirect/checkout-invoice/create';

        $payload = [
            'commande' => [
                'invoice' => [
                    'items' => [
                        [
                            'name' => $profile['name'] ?? 'Recharge crédits',
                            'description' => 'Transaction ' . $transaction['transaction_id'],
                            'quantity' => 1,
                            'unit_price' => (int)$transaction['amount'],
                            'total_price' => (int)$transaction['amount']
                        ]
                    ],
                    'total_amount' => (int)$transaction['amount'],
                    'devise' => $transaction['currency'] ?? 'XOF',
                    'description' => $profile['name'] ?? 'Recharge crédits',
                    'customer' => '',
                    'customer_firstname' => $customerData['name'] ?? '',
                    'customer_lastname' => '',
                    'customer_email' => $customerData['email'] ?? 'client@example.com'
                ],
                'store' => [
                    'name' => $config['platform'] ?? 'RADIUS Manager',
                    'website_url' => $this->baseUrl ?? ''
                ],
                'actions' => [
                    'cancel_url' => $returnUrl . '&cancelled=1',
                    'return_url' => $returnUrl,
                    'callback_url' => $callbackUrl
                ],
                'custom_data' => [
                    'transaction_id' => $transaction['transaction_id']
                ]
            ]
        ];

        $headers = [
            'Apikey: ' . ($config['api_key'] ?? ''),
            'Authorization: Bearer ' . ($config['auth_token'] ?? ''),
            'Accept: application/json',
            'Content-Type: application/json'
        ];

        $response = $this->httpPost($apiUrl, $payload, $headers);

        if (empty($response['response_code']) || $response['response_code'] !== '00') {
            $errorMsg = $response['response_text'] ?? $response['description'] ?? 'Erreur LigdiCash inconnue';
            error_log('LigdiCash error: ' . json_encode($response));
            throw new Exception('LigdiCash: ' . $errorMsg);
        }

        $paymentUrl = $response['response_text'] ?? '';
        $ligdiToken = $response['token'] ?? $response['custom_data']['transaction_id'] ?? '';

        if (empty($paymentUrl) || !filter_var($paymentUrl, FILTER_VALIDATE_URL)) {
            throw new Exception('LigdiCash: URL de paiement invalide');
        }

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => $ligdiToken,
            'gateway_response' => $response
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => $ligdiToken
        ];
    }

    /**
     * Cryptomus - Initialiser le paiement crypto
     * Documentation: https://doc.cryptomus.com/merchant-api/payments/creating-invoice
     */
    private function initiateCryptomus(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
        $apiUrl = 'https://api.cryptomus.com/v1/payment';

        $merchantUuid = $config['merchant_uuid'] ?? '';
        $apiKey = $config['payment_key'] ?? $config['api_key'] ?? '';

        if (empty($merchantUuid) || empty($apiKey)) {
            throw new Exception('Cryptomus: merchant_uuid et payment_key sont requis');
        }

        // Cryptomus accepte uniquement USD, EUR et les cryptos — pas XOF/XAF
        // Convertir le montant en USD si la devise n'est pas supportée
        $amount = (float)$transaction['amount'];
        $currency = strtoupper($transaction['currency'] ?? 'USD');
        $cryptomusCurrencies = ['USD', 'EUR', 'GBP', 'RUB', 'UAH', 'BYN', 'KZT', 'UZS', 'AZN', 'TRY', 'BRL', 'INR', 'PLN', 'CZK', 'ARS', 'BDT', 'BOB', 'CLP', 'COP', 'DOP', 'EGP', 'GEL', 'GHS', 'IDR', 'ILS', 'JOD', 'KES', 'KWD', 'LKR', 'MAD', 'MXN', 'MYR', 'NGN', 'NOK', 'NZD', 'PEN', 'PHP', 'PKR', 'QAR', 'SAR', 'SEK', 'SGD', 'THB', 'TND', 'TWD', 'VND', 'ZAR'];

        if (!in_array($currency, $cryptomusCurrencies)) {
            // Convertir XOF/XAF en USD (taux approximatif: 1 USD ≈ 600 XOF)
            $xofToUsd = 600;
            $amount = round($amount / $xofToUsd, 2);
            $currency = 'USD';
            if ($amount < 0.50) {
                $amount = 0.50; // Minimum Cryptomus
            }
        }

        $payload = [
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'order_id' => $transaction['transaction_id'],
            'url_return' => $returnUrl,
            'url_success' => $returnUrl,
            'url_callback' => $callbackUrl,
            'lifetime' => 3600,
        ];

        // Reproduire exactement le SDK officiel Cryptomus:
        // https://github.com/CryptomusCom/api-php-sdk/blob/master/src/RequestBuilder.php
        $jsonBody = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $sign = md5(base64_encode($jsonBody) . $apiKey);

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json;charset=UTF-8',
            'Content-Length: ' . strlen($jsonBody),
            'merchant: ' . $merchantUuid,
            'sign: ' . $sign,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_TIMEOUT => 30,
        ]);

        $rawResponse = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception('Cryptomus HTTP error: ' . $error);
        }

        $response = json_decode($rawResponse, true) ?? [];

        if ($httpCode >= 400) {
            error_log("Cryptomus API {$httpCode}: " . $rawResponse);
        }

        if (!isset($response['result']['uuid']) || empty($response['result']['url'])) {
            $errorMsg = $response['message'] ?? 'Erreur Cryptomus inconnue';
            if (isset($response['errors'])) {
                $errDetails = is_array($response['errors']) ? json_encode($response['errors'], JSON_UNESCAPED_UNICODE) : $response['errors'];
                $errorMsg .= ' - ' . $errDetails;
            }
            error_log('Cryptomus error: ' . json_encode($response));
            throw new Exception('Cryptomus: ' . $errorMsg);
        }

        $paymentUrl = $response['result']['url'];
        $cryptomusUuid = $response['result']['uuid'];

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => $cryptomusUuid,
            'gateway_response' => $response['result']
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => $cryptomusUuid
        ];
    }

    /**
     * YengaPay - Initialiser le paiement
     * Documentation: https://api.yengapay.com
     */
    private function initiateYengaPay(array $gateway, array $transaction, array $profile, array $customerData, string $callbackUrl, string $returnUrl): array
    {
        $config = $gateway['config'];
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

        $amount = (float)$transaction['amount'];
        $currency = strtoupper($transaction['currency'] ?? 'XOF');

        // YengaPay requiert XOF — convertir XAF en XOF (parité fixe 1:1)
        if ($currency === 'XAF') {
            $currency = 'XOF';
        }

        $payload = [
            'paymentAmount' => $amount,
            'reference' => $transaction['transaction_id'],
            'articles' => [
                [
                    'title' => $profile['name'] ?? 'Paiement',
                    'description' => 'Paiement ' . ($profile['name'] ?? ''),
                    'price' => $amount,
                    'pictures' => []
                ]
            ]
        ];

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'x-api-key: ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 60
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception('YengaPay HTTP error: ' . $error);
        }

        $body = json_decode($response, true) ?? [];

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception('YengaPay API error (' . $httpCode . '): ' . ($response ?: 'No response'));
        }

        if (!isset($body['checkoutPageUrlWithPaymentToken'])) {
            throw new Exception('YengaPay: URL de paiement non trouvée. ' . json_encode($body));
        }

        $paymentUrl = $body['checkoutPageUrlWithPaymentToken'];
        $yengaId = $body['id'] ?? '';

        $this->updateTransaction($transaction['transaction_id'], [
            'gateway_transaction_id' => (string)$yengaId,
            'gateway_response' => $body
        ]);

        return [
            'success' => true,
            'payment_url' => $paymentUrl,
            'transaction_id' => $transaction['transaction_id'],
            'gateway_transaction_id' => $yengaId
        ];
    }

    /**
     * Compléter une recharge de crédits après paiement réussi
     */
    private function completeCreditRecharge(array $transaction, string $gatewayTransactionId, array $gatewayResponse, ?string $operatorReference): array
    {
        $pdo = $this->db->getPdo();
        $adminId = (int)$transaction['admin_id'];
        $amount = (float)$transaction['amount'];

        // Lire le taux de change
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'credit_exchange_rate'");
        $stmt->execute();
        $rate = (float)($stmt->fetchColumn() ?: 100);

        $credits = round($amount / $rate, 2);

        $pdo->beginTransaction();
        try {
            // Ajouter les crédits à l'admin
            $pdo->prepare("UPDATE users SET credit_balance = credit_balance + ? WHERE id = ?")
                ->execute([$credits, $adminId]);

            // Récupérer le nouveau solde
            $stmt = $pdo->prepare("SELECT credit_balance FROM users WHERE id = ?");
            $stmt->execute([$adminId]);
            $newBalance = (float)$stmt->fetchColumn();

            // Enregistrer dans credit_transactions
            $pdo->prepare("
                INSERT INTO credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                VALUES (?, 'recharge', ?, ?, 'payment_transaction', ?, ?)
            ")->execute([
                $adminId, $credits, $newBalance, $transaction['transaction_id'],
                "Recharge {$amount} {$transaction['currency']} via {$transaction['gateway_code']}"
            ]);

            // Marquer le paiement comme complété
            $updateData = [
                'status' => 'completed',
                'gateway_transaction_id' => $gatewayTransactionId,
                'gateway_response' => $gatewayResponse,
                'paid_at' => date('Y-m-d H:i:s')
            ];
            if ($operatorReference) {
                $updateData['operator_reference'] = $operatorReference;
            }
            $this->updateTransaction($transaction['transaction_id'], $updateData);

            $pdo->commit();

            return [
                'success' => true,
                'credits_added' => $credits,
                'new_balance' => $newBalance,
                'message' => 'Credits recharged successfully'
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
