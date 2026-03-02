<?php
/**
 * Controller API Passerelle de paiement plateforme (Paygate)
 * Routes admin (activation, solde, retraits) + superadmin (config, gestion retraits)
 */

require_once __DIR__ . '/../Payment/PlatformPaymentService.php';

class PlatformPaymentController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private ?PlatformPaymentService $service = null;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    private function getService(): PlatformPaymentService
    {
        if (!$this->service) {
            $config = require __DIR__ . '/../../config/config.php';
            $this->service = new PlatformPaymentService($this->db->getPdo(), $config);
        }
        return $this->service;
    }

    private function requireSuperAdmin(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        if (!$user || !$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Accès réservé au SuperAdmin']);
            exit;
        }
    }

    private function jsonResponse(array $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    private function getRequestBody(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // ======================================================
    // ADMIN ENDPOINTS
    // ======================================================

    /**
     * GET /platform-payments/gateways
     * Liste les passerelles plateforme avec statut d'activation pour cet admin
     */
    public function adminGateways(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();

        try {
            $service = $this->getService();
            $gateways = $service->getAdminPlatformGateways($adminId);
            $settings = $service->getSettings();

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'gateways' => $gateways,
                    'paygate_enabled' => $settings['paygate_enabled'] === '1',
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /platform-payments/gateways/{id}/toggle
     */
    public function toggleGateway(array $params): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $platformGatewayId = (int)($params['id'] ?? 0);

        try {
            $service = $this->getService();
            $newState = $service->toggleAdminPlatformGateway($adminId, $platformGatewayId);

            $this->jsonResponse([
                'success' => true,
                'data' => ['is_active' => $newState],
                'message' => $newState ? __('paygate.activated') : __('paygate.deactivated')
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /platform-payments/balance
     */
    public function balance(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();

        try {
            $data = $this->getService()->getBalance($adminId);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /platform-payments/transactions
     */
    public function transactions(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $page = (int)($_GET['page'] ?? 1);

        try {
            $data = $this->getService()->getTransactions($adminId, $page);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /platform-payments/withdrawals
     */
    public function requestWithdrawal(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $body = $this->getRequestBody();

        $amount = (float)($body['amount'] ?? 0);
        $paymentMethod = $body['payment_method'] ?? '';
        $paymentDetails = $body['payment_details'] ?? [];
        $note = $body['note'] ?? null;

        if ($amount <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Montant invalide'], 400);
            return;
        }
        if (!in_array($paymentMethod, ['mobile_money', 'bank_transfer'])) {
            $this->jsonResponse(['success' => false, 'message' => 'Méthode de paiement invalide'], 400);
            return;
        }

        try {
            $platformGatewayId = isset($body['platform_gateway_id']) ? (int)$body['platform_gateway_id'] : null;
            $data = $this->getService()->requestWithdrawal($adminId, $amount, $paymentMethod, $paymentDetails, $note, $platformGatewayId);
            $this->jsonResponse(['success' => true, 'data' => $data, 'message' => __('paygate.withdrawal_submitted')]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /platform-payments/withdrawals
     */
    public function withdrawals(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $page = (int)($_GET['page'] ?? 1);

        try {
            $data = $this->getService()->getWithdrawals($adminId, $page);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /platform-payments/withdrawals/{id}/cancel
     */
    public function cancelWithdrawal(array $params): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $withdrawalId = (int)($params['id'] ?? 0);

        try {
            $cancelled = $this->getService()->cancelWithdrawal($adminId, $withdrawalId);
            if ($cancelled) {
                $this->jsonResponse(['success' => true, 'message' => __('paygate.withdrawal_cancelled')]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Impossible d\'annuler cette demande'], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // ======================================================
    // SUPERADMIN ENDPOINTS
    // ======================================================

    /**
     * GET /superadmin/paygate/gateways
     */
    public function superadminListGateways(): void
    {
        $this->requireSuperAdmin();

        try {
            $service = $this->getService();
            $gateways = $service->getPlatformGateways();

            $settings = $service->getSettings();

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'gateways' => $gateways,
                    'settings' => $settings,
                ]
            ]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * PUT /superadmin/paygate/gateways/{id}
     */
    public function superadminUpdateGateway(array $params): void
    {
        $this->requireSuperAdmin();
        $id = (int)($params['id'] ?? 0);
        $body = $this->getRequestBody();

        try {
            $this->getService()->updatePlatformGateway($id, $body);
            $this->jsonResponse(['success' => true, 'message' => __('superadmin.paygate_gateway_saved')]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /superadmin/paygate/settings
     */
    public function superadminUpdateSettings(): void
    {
        $this->requireSuperAdmin();
        $body = $this->getRequestBody();

        try {
            $this->getService()->updateSettings($body);
            $this->jsonResponse(['success' => true, 'message' => __('superadmin.paygate_settings_saved')]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /superadmin/paygate/withdrawals
     */
    public function superadminListWithdrawals(): void
    {
        $this->requireSuperAdmin();

        $filters = [
            'page' => (int)($_GET['page'] ?? 1),
            'status' => $_GET['status'] ?? null,
            'admin_id' => $_GET['admin_id'] ?? null,
        ];

        try {
            $data = $this->getService()->listAllWithdrawals($filters);
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /superadmin/paygate/withdrawals/{id}/approve
     */
    public function superadminApproveWithdrawal(array $params): void
    {
        $this->requireSuperAdmin();
        $withdrawalId = (int)($params['id'] ?? 0);
        $user = $this->auth->getUser();

        try {
            $approved = $this->getService()->approveWithdrawal($withdrawalId, $user->getId());
            if ($approved) {
                $this->jsonResponse(['success' => true, 'message' => __('superadmin.paygate_withdrawal_approved')]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Impossible d\'approuver cette demande'], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /superadmin/paygate/withdrawals/{id}/complete
     */
    public function superadminCompleteWithdrawal(array $params): void
    {
        $this->requireSuperAdmin();
        $withdrawalId = (int)($params['id'] ?? 0);
        $body = $this->getRequestBody();
        $user = $this->auth->getUser();

        $transferReference = $body['transfer_reference'] ?? '';
        $note = $body['note'] ?? null;

        if (empty($transferReference)) {
            $this->jsonResponse(['success' => false, 'message' => 'La référence du virement est requise'], 400);
            return;
        }

        try {
            $data = $this->getService()->completeWithdrawal($withdrawalId, $user->getId(), $transferReference, $note);
            $this->jsonResponse(['success' => true, 'data' => $data, 'message' => __('superadmin.paygate_withdrawal_completed')]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * POST /superadmin/paygate/withdrawals/{id}/reject
     */
    public function superadminRejectWithdrawal(array $params): void
    {
        $this->requireSuperAdmin();
        $withdrawalId = (int)($params['id'] ?? 0);
        $body = $this->getRequestBody();
        $user = $this->auth->getUser();

        try {
            $rejected = $this->getService()->rejectWithdrawal($withdrawalId, $user->getId(), $body['reason'] ?? null);
            if ($rejected) {
                $this->jsonResponse(['success' => true, 'message' => __('superadmin.paygate_withdrawal_rejected')]);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Impossible de rejeter cette demande'], 400);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /superadmin/paygate/stats
     */
    public function superadminStats(): void
    {
        $this->requireSuperAdmin();

        try {
            $data = $this->getService()->getPlatformStats();
            $this->jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /superadmin/paygate/admins/{id}/adjust-balance
     */
    public function superadminAdjustBalance(array $params): void
    {
        $this->requireSuperAdmin();
        $targetAdminId = (int)($params['id'] ?? 0);
        $body = $this->getRequestBody();
        $user = $this->auth->getUser();

        $amount = (float)($body['amount'] ?? 0);
        $reason = $body['reason'] ?? null;

        if ($amount == 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Montant invalide'], 400);
            return;
        }

        try {
            $data = $this->getService()->adjustBalance($targetAdminId, $amount, $user->getId(), $reason);
            $this->jsonResponse(['success' => true, 'data' => $data, 'message' => __('superadmin.paygate_balance_adjusted')]);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
