<?php

class SmsService
{
    private PDO $pdo;

    private static array $providers = [
        'nghcorp' => [
            'name' => 'NGH Corp',
            'description' => 'SMS via NGH Corp API',
            'base_url' => 'https://extranet.nghcorp.net/api/',
            'fields' => [
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'text', 'required' => true, 'secret' => false],
                ['key' => 'api_secret', 'label' => 'API Secret', 'type' => 'password', 'required' => true, 'secret' => true],
                ['key' => 'sender_id', 'label' => 'Sender ID (From)', 'type' => 'text', 'required' => true, 'placeholder' => 'MonEntreprise', 'secret' => false],
            ],
            'supports_balance' => true,
        ],
        'platform' => [
            'name' => 'Plateforme SMS',
            'description' => 'SMS via crédits plateforme (CSMS)',
            'base_url' => '',
            'fields' => [],
            'supports_balance' => true,
            'is_platform' => true,
        ],
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public static function getProviderDefinitions(): array
    {
        return self::$providers;
    }

    public function loadGateway(int $gatewayId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sms_gateways WHERE id = ?");
        $stmt->execute([$gatewayId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function sendSms(int $gatewayId, string $phone, string $message, ?string $reference = null): array
    {
        $gateway = $this->loadGateway($gatewayId);
        if (!$gateway) {
            return ['success' => false, 'error' => 'Gateway non trouvée'];
        }

        $config = json_decode($gateway['config'], true) ?: [];

        // Platform provider uses backend config, not admin config
        if ($gateway['provider_code'] === 'platform') {
            $result = $this->sendViaPlatform((int)$gateway['admin_id'], $phone, $message, $reference);
            $this->logNotification($gatewayId, $phone, $message, $reference, $result, $gateway['admin_id']);
            return $result;
        }

        if (!$config) {
            return ['success' => false, 'error' => 'Configuration invalide'];
        }

        $result = match ($gateway['provider_code']) {
            'nghcorp' => $this->sendViaNghCorp($config, $phone, $message, $reference),
            default => ['success' => false, 'error' => 'Provider inconnu: ' . $gateway['provider_code']],
        };

        // Log the notification
        $this->logNotification($gatewayId, $phone, $message, $reference, $result, $gateway['admin_id']);

        return $result;
    }

    public function checkBalance(int $gatewayId): array
    {
        $gateway = $this->loadGateway($gatewayId);
        if (!$gateway) {
            return ['success' => false, 'error' => 'Gateway non trouvée'];
        }

        $config = json_decode($gateway['config'], true) ?: [];

        $result = match ($gateway['provider_code']) {
            'nghcorp' => $this->balanceNghCorp($config),
            'platform' => $this->balancePlatform((int)$gateway['admin_id']),
            default => ['success' => false, 'error' => 'Balance non supportée pour ce provider'],
        };

        if ($result['success']) {
            $stmt = $this->pdo->prepare("UPDATE sms_gateways SET balance = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$result['balance'], $gatewayId]);
        }

        return $result;
    }

    // --- NGH Corp Implementation ---

    private function sendViaNghCorp(array $config, string $phone, string $message, ?string $reference = null): array
    {
        $payload = [
            'api_key' => $config['api_key'] ?? '',
            'api_secret' => $config['api_secret'] ?? '',
            'from' => $config['sender_id'] ?? '',
            'to' => $phone,
            'text' => $message,
            'reference' => $reference ?? uniqid('sms_'),
        ];

        $response = $this->httpPost('https://extranet.nghcorp.net/api/send-sms', $payload);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data'];

        if (($data['status'] ?? 0) == 200) {
            $rawCredits = $data['credits'] ?? null;
            $credits = $rawCredits !== null ? (float)str_replace(',', '', (string)$rawCredits) : null;
            return [
                'success' => true,
                'message_id' => $data['messageid'] ?? null,
                'credits' => $credits,
                'response' => $data,
            ];
        }

        return [
            'success' => false,
            'error' => $this->nghCorpError($data['status'] ?? 0, $data['status_desc'] ?? ''),
            'response' => $data,
        ];
    }

    private function balanceNghCorp(array $config): array
    {
        $payload = [
            'api_key' => $config['api_key'] ?? '',
            'api_secret' => $config['api_secret'] ?? '',
        ];

        $response = $this->httpPost('https://extranet.nghcorp.net/api/balance', $payload);

        if (!$response['success']) {
            return $response;
        }

        $data = $response['data'];

        if (($data['status'] ?? 0) == 200) {
            // Balance can be a formatted string like "4,880.00" - clean it
            $rawBalance = $data['balance'] ?? 0;
            $balance = (float)str_replace(',', '', (string)$rawBalance);
            return [
                'success' => true,
                'balance' => $balance,
            ];
        }

        return [
            'success' => false,
            'error' => $this->nghCorpError($data['status'] ?? 0, $data['status_desc'] ?? ''),
        ];
    }

    private function nghCorpError(int $code, string $desc): string
    {
        $errors = [
            100 => 'Seule la méthode POST est autorisée',
            102 => 'Identifiants manquants',
            103 => 'Identifiants invalides',
            108 => 'Numéro de téléphone manquant',
            109 => 'Numéro de téléphone invalide',
            111 => 'Crédit insuffisant',
            113 => 'Erreur interne du serveur',
        ];

        return $errors[$code] ?? ($desc ?: "Erreur inconnue (code: $code)");
    }

    // --- Platform SMS (CSMS) ---

    private function sendViaPlatform(int $adminId, string $phone, string $message, ?string $reference = null): array
    {
        $this->pdo->beginTransaction();
        try {
            // 1. Check CSMS balance with row lock
            $stmt = $this->pdo->prepare("SELECT sms_credit_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$adminId]);
            $balance = (float)$stmt->fetchColumn();

            if ($balance < 1) {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'Solde CSMS insuffisant. Convertissez des CRT en CSMS.'];
            }

            // 2. Load platform backend config from global_settings
            $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ('platform_sms_provider', 'platform_sms_config')");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }

            $backendProvider = $settings['platform_sms_provider'] ?? '';
            $backendConfig = json_decode($settings['platform_sms_config'] ?? '{}', true) ?: [];

            if (empty($backendProvider) || empty($backendConfig)) {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'La passerelle SMS plateforme n\'est pas configurée par l\'administrateur système.'];
            }

            // 3. Send via backend provider
            $result = match ($backendProvider) {
                'nghcorp' => $this->sendViaNghCorp($backendConfig, $phone, $message, $reference),
                default => ['success' => false, 'error' => 'Provider backend inconnu: ' . $backendProvider],
            };

            // 4. Deduct 1 CSMS only on success
            if ($result['success']) {
                $newBalance = $balance - 1;
                $stmt = $this->pdo->prepare("UPDATE users SET sms_credit_balance = ? WHERE id = ?");
                $stmt->execute([$newBalance, $adminId]);

                $stmt = $this->pdo->prepare(
                    "INSERT INTO sms_credit_transactions (admin_id, type, amount, balance_after, reference_type, reference_id, description)
                     VALUES (?, 'sms_sent', -1, ?, 'sms_notification', ?, ?)"
                );
                $stmt->execute([$adminId, $newBalance, $reference, "SMS envoyé au $phone"]);

                $result['credits'] = $newBalance;
            }

            $this->pdo->commit();
            return $result;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'error' => 'Erreur plateforme SMS: ' . $e->getMessage()];
        }
    }

    private function balancePlatform(int $adminId): array
    {
        $stmt = $this->pdo->prepare("SELECT sms_credit_balance FROM users WHERE id = ?");
        $stmt->execute([$adminId]);
        $balance = (float)$stmt->fetchColumn();
        return ['success' => true, 'balance' => $balance];
    }

    // --- HTTP & Logging ---

    private function httpPost(string $url, array $payload): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "Erreur réseau: $error"];
        }

        $data = json_decode($body, true);
        if ($data === null) {
            return ['success' => false, 'error' => "Réponse invalide du serveur"];
        }

        return ['success' => true, 'data' => $data];
    }

    private function logNotification(int $gatewayId, string $phone, string $message, ?string $reference, array $result, int $adminId): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO sms_notifications (gateway_id, phone, message, reference, status, error_message, provider_response, admin_id, sent_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $gatewayId,
            $phone,
            $message,
            $result['message_id'] ?? $reference,
            $result['success'] ? 'sent' : 'failed',
            $result['error'] ?? null,
            isset($result['response']) ? json_encode($result['response']) : null,
            $adminId,
            $result['success'] ? date('Y-m-d H:i:s') : null,
        ]);
    }
}
