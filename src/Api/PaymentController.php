<?php
/**
 * Controller API Payment Gateways
 */

require_once __DIR__ . '/../Payment/PaymentService.php';

class PaymentController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private ?PaymentService $paymentService = null;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * Obtenir le service de paiement
     */
    private function getPaymentService(): PaymentService
    {
        if (!$this->paymentService) {
            $config = require __DIR__ . '/../../config/config.php';
            $this->paymentService = new PaymentService($this->db, $config);
        }
        return $this->paymentService;
    }

    /**
     * S'assurer que toutes les passerelles par défaut existent pour cet admin
     */
    private function ensurePaymentGateways(?int $adminId): void
    {
        if ($adminId === null) {
            return;
        }

        $pdo = $this->db->getPdo();

        // Provisionner les gateways manquantes pour cet admin à partir des gateways globales
        // Copie dynamiquement depuis admin_id IS NULL, sans config (chaque admin configure ses propres clés)
        $pdo->prepare("
            INSERT IGNORE INTO payment_gateways (gateway_code, name, description, logo_url, is_active, is_sandbox, config, display_order, admin_id)
            SELECT gateway_code, name, description, logo_url, 0, 1, '{}', display_order, ?
            FROM payment_gateways
            WHERE admin_id IS NULL
              AND gateway_code NOT IN (SELECT gateway_code FROM payment_gateways WHERE admin_id = ?)
        ")->execute([$adminId, $adminId]);
    }

    /**
     * GET /api/payments/gateways
     * Liste toutes les passerelles de paiement
     */
    public function index(): void
    {
        $adminId = $this->getAdminId();
        $this->ensurePaymentGateways($adminId);
        $gateways = $this->db->getAllPaymentGateways($adminId);

        // Masquer les clés secrètes pour la sécurité
        foreach ($gateways as &$gateway) {
            $gateway['config'] = $this->maskSensitiveData($gateway['config']);
        }

        jsonSuccess($gateways);
    }

    /**
     * GET /api/payments/gateways/{id}
     * Obtenir une passerelle par ID
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $gateway = $this->db->getPaymentGatewayById($id, $this->getAdminId());

        if (!$gateway) {
            jsonError(__('api.payment_gateway_not_found'), 404);
        }

        // Masquer les clés secrètes
        $gateway['config'] = $this->maskSensitiveData($gateway['config']);

        jsonSuccess($gateway);
    }

    /**
     * PUT /api/payments/gateways/{id}
     * Mettre à jour une passerelle
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $gateway = $this->db->getPaymentGatewayById($id, $this->getAdminId());
        if (!$gateway) {
            jsonError(__('api.payment_gateway_not_found'), 404);
        }

        // Fusionner la nouvelle config avec l'ancienne (pour préserver les valeurs non modifiées)
        if (isset($data['config']) && is_array($data['config'])) {
            $existingConfig = $gateway['config'];
            foreach ($data['config'] as $key => $value) {
                // Ne pas écraser avec des valeurs masquées
                if ($value !== '••••••••' && $value !== '') {
                    $existingConfig[$key] = $value;
                }
            }
            $data['config'] = $existingConfig;
        }

        try {
            $this->db->updatePaymentGateway($id, $data);
            $gateway = $this->db->getPaymentGatewayById($id, $this->getAdminId());
            $gateway['config'] = $this->maskSensitiveData($gateway['config']);
            jsonSuccess($gateway, __('api.payment_gateway_updated'));
        } catch (Exception $e) {
            jsonError(__('api.payment_gateway_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/payments/gateways/{id}/toggle
     * Activer/Désactiver une passerelle
     */
    public function toggle(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $adminId = $this->getAdminId();
        $gateway = $this->db->getPaymentGatewayById($id, $adminId);
        if (!$gateway) {
            jsonError(__('api.payment_gateway_not_found'), 404);
        }

        $active = isset($data['is_active']) ? (bool)$data['is_active'] : !$gateway['is_active'];

        try {
            $this->db->togglePaymentGateway($id, $active);
            $gateway = $this->db->getPaymentGatewayById($id, $adminId);
            $gateway['config'] = $this->maskSensitiveData($gateway['config']);
            jsonSuccess($gateway, $active ? __('api.payment_gateway_activated') : __('api.payment_gateway_deactivated'));
        } catch (Exception $e) {
            jsonError(__('api.payment_gateway_toggle_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/payments/gateways/active
     * Obtenir uniquement les passerelles actives
     */
    public function active(): void
    {
        $adminId = $this->getAdminId();
        $this->ensurePaymentGateways($adminId);
        $gateways = $this->db->getActivePaymentGateways($adminId);

        // Masquer les clés secrètes
        foreach ($gateways as &$gateway) {
            $gateway['config'] = $this->maskSensitiveData($gateway['config']);
        }

        jsonSuccess($gateways);
    }

    /**
     * Masquer les données sensibles (clés API, secrets, etc.)
     */
    private function maskSensitiveData(array $config): array
    {
        $sensitiveKeys = ['secret_key', 'secret', 'api_key', 'private_key', 'password', 'client_secret', 'webhook_secret'];

        foreach ($config as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys) && !empty($value)) {
                $config[$key] = '••••••••';
            }
        }

        return $config;
    }

    /**
     * Obtenir les champs de configuration pour chaque type de passerelle
     */
    public function getConfigFields(): void
    {
        $fields = [
            'fedapay' => [
                ['key' => 'account_name', 'label' => 'Nom du compte', 'type' => 'text', 'required' => true],
                ['key' => 'public_key', 'label' => 'Public Key', 'type' => 'text', 'required' => true],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
            ],
            'cinetpay' => [
                ['key' => 'site_id', 'label' => 'Site ID', 'type' => 'text', 'required' => true],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'text', 'required' => true],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
            ],
            'orange_money' => [
                ['key' => 'merchant_key', 'label' => 'Merchant Key', 'type' => 'text', 'required' => true],
                ['key' => 'username', 'label' => 'Username', 'type' => 'text', 'required' => true],
                ['key' => 'password', 'label' => 'Password', 'type' => 'password', 'required' => true],
                ['key' => 'auth_header', 'label' => 'Auth Header', 'type' => 'text', 'required' => false],
            ],
            'mtn_momo' => [
                ['key' => 'subscription_key', 'label' => 'Subscription Key', 'type' => 'text', 'required' => true],
                ['key' => 'api_user', 'label' => 'API User', 'type' => 'text', 'required' => true],
                ['key' => 'api_key', 'label' => 'API Key', 'type' => 'password', 'required' => true],
                ['key' => 'environment', 'label' => 'Environment', 'type' => 'select', 'options' => ['sandbox', 'production'], 'required' => true],
            ],
            'paypal' => [
                ['key' => 'client_id', 'label' => 'Client ID', 'type' => 'text', 'required' => true],
                ['key' => 'client_secret', 'label' => 'Client Secret', 'type' => 'password', 'required' => true],
            ],
            'stripe' => [
                ['key' => 'publishable_key', 'label' => 'Publishable Key', 'type' => 'text', 'required' => true],
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
                ['key' => 'webhook_secret', 'label' => 'Webhook Secret', 'type' => 'password', 'required' => false],
            ],
            'kkiapay' => [
                ['key' => 'public_key', 'label' => 'Public Key', 'type' => 'text', 'required' => true],
                ['key' => 'private_key', 'label' => 'Private Key', 'type' => 'password', 'required' => true],
                ['key' => 'secret', 'label' => 'Secret', 'type' => 'password', 'required' => false],
            ],
            'moneroo' => [
                ['key' => 'secret_key', 'label' => 'Secret Key', 'type' => 'password', 'required' => true],
                ['key' => 'public_key', 'label' => 'Public Key', 'type' => 'text', 'required' => false],
            ],
        ];

        jsonSuccess($fields);
    }

    // ==========================================
    // Payment Links & Transactions
    // ==========================================

    /**
     * POST /api/payments/initiate
     * Initialiser un paiement pour un profil
     */
    public function initiate(): void
    {
        $data = getJsonBody();

        if (empty($data['gateway_code'])) {
            jsonError(__('api.gateway_code_required'), 400);
        }

        if (empty($data['profile_id'])) {
            jsonError(__('api.profile_id_required'), 400);
        }

        try {
            // Si paiement via passerelle plateforme
            if (!empty($data['is_platform'])) {
                require_once __DIR__ . '/../Payment/PlatformPaymentService.php';
                $config = require __DIR__ . '/../../config/config.php';
                $platformService = new PlatformPaymentService($this->db->getPdo(), $config);
                $result = $platformService->initiatePayment(
                    $data['gateway_code'],
                    (int)$data['profile_id'],
                    $this->getAdminId(),
                    [
                        'phone' => $data['customer_phone'] ?? null,
                        'email' => $data['customer_email'] ?? null,
                        'name' => $data['customer_name'] ?? null,
                        'device_info' => $data['device_info'] ?? null
                    ]
                );
            } else {
                $service = $this->getPaymentService();
                $result = $service->initiatePayment(
                    $data['gateway_code'],
                    (int)$data['profile_id'],
                    [
                        'phone' => $data['customer_phone'] ?? null,
                        'email' => $data['customer_email'] ?? null,
                        'name' => $data['customer_name'] ?? null,
                        'operator' => $data['operator'] ?? null,  // Pour FeexPay
                        'network' => $data['network'] ?? null,    // Pour PayGate (FLOOZ, TMONEY)
                        'device_info' => $data['device_info'] ?? null
                    ]
                );
            }

            jsonSuccess($result);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/payments/link/{profileId}
     * Générer un lien de paiement pour un profil
     */
    public function getPaymentLink(array $params): void
    {
        $profileId = (int)$params['profileId'];
        $adminId = $this->getAdminId();

        try {
            $service = $this->getPaymentService();
            $link = $service->generatePaymentLink($profileId, $adminId);

            jsonSuccess([
                'payment_link' => $link,
                'profile_id' => $profileId
            ]);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * GET /api/payments/transactions
     * Liste des transactions
     */
    public function transactions(): void
    {
        $filters = [];

        if (!empty($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }

        if (!empty($_GET['gateway_code'])) {
            $filters['gateway_code'] = $_GET['gateway_code'];
        }

        if (!empty($_GET['profile_id'])) {
            $filters['profile_id'] = (int)$_GET['profile_id'];
        }

        if (!empty($_GET['limit'])) {
            $filters['limit'] = (int)$_GET['limit'];
        }

        $transactions = $this->db->getAllTransactions($filters, $this->getAdminId());
        jsonSuccess($transactions);
    }

    /**
     * GET /api/payments/transactions/{id}
     * Détails d'une transaction
     */
    public function transactionDetails(array $params): void
    {
        $transactionId = $params['id'];
        $transaction = $this->db->getTransactionById($transactionId);

        if (!$transaction) {
            jsonError(__('api.transaction_not_found'), 404);
        }

        jsonSuccess($transaction);
    }

    /**
     * GET /api/payments/transactions/stats
     * Statistiques des transactions
     */
    public function transactionStats(): void
    {
        $stats = $this->db->getTransactionStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * POST /api/payments/check-status
     * Vérifier le statut d'un paiement
     */
    public function checkStatus(): void
    {
        $data = getJsonBody();

        if (empty($data['transaction_id'])) {
            jsonError(__('api.transaction_id_required'), 400);
        }

        try {
            $service = $this->getPaymentService();
            $result = $service->checkPaymentStatus($data['transaction_id']);
            jsonSuccess($result);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }

    /**
     * POST /api/payments/kkiapay/complete
     * Finaliser un paiement Kkiapay après validation côté client
     */
    public function completeKkiapay(): void
    {
        $data = getJsonBody();

        if (empty($data['transaction_id'])) {
            jsonError(__('api.transaction_id_required'), 400);
        }

        if (empty($data['kkiapay_transaction_id'])) {
            jsonError(__('api.kkiapay_transaction_id_required'), 400);
        }

        try {
            $service = $this->getPaymentService();
            $result = $service->completeKkiapayPayment(
                $data['transaction_id'],
                $data['kkiapay_transaction_id']
            );
            jsonSuccess($result);
        } catch (Exception $e) {
            jsonError($e->getMessage(), 400);
        }
    }
}
