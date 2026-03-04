<?php
/**
 * Controller API Authentification
 */

class AuthController
{
    private AuthService $auth;

    public function __construct(AuthService $auth)
    {
        $this->auth = $auth;
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        $data = getJsonBody();

        if (empty($data['username'])) {
            jsonError(__('api.field_required', ['field' => __('common.username')]), 400);
        }

        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }

        $result = $this->auth->login($data['username'], $data['password']);

        if ($result['success']) {
            if (!empty($result['requires_2fa'])) {
                // 2FA required - return temp token
                jsonSuccess([
                    'requires_2fa' => true,
                    'temp_token' => $result['temp_token']
                ], $result['message']);
            } else {
                jsonSuccess($result['user'], $result['message']);
            }
        } else {
            if (!empty($result['needs_verification'])) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => $result['message'],
                    'needs_verification' => true,
                    'email' => $result['email'] ?? ''
                ]);
                return;
            }
            jsonError($result['message'], 401);
        }
    }

    /**
     * POST /api/auth/verify-2fa
     */
    public function verify2fa(): void
    {
        $data = getJsonBody();

        if (empty($data['temp_token'])) {
            jsonError(__('auth.2fa_token_required'), 400);
        }

        if (empty($data['code'])) {
            jsonError(__('auth.2fa_code_required'), 400);
        }

        $result = $this->auth->verify2fa($data['temp_token'], $data['code']);

        if ($result['success']) {
            jsonSuccess($result['user'], $result['message']);
        } else {
            jsonError($result['message'], 401);
        }
    }

    /**
     * GET /api/auth/2fa/status
     */
    public function get2faStatus(): void
    {
        $this->auth->requireAuth();
        jsonSuccess($this->auth->get2faStatus());
    }

    /**
     * POST /api/auth/2fa/setup
     */
    public function setup2fa(): void
    {
        $this->auth->requireAuth();

        $user = $this->auth->getUser();
        if (!$user || !in_array($user->getRole(), ['superadmin', 'admin'])) {
            jsonError(__('auth.2fa_admin_only'), 403);
        }

        $result = $this->auth->setup2fa();

        if ($result['success']) {
            jsonSuccess($result);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/auth/2fa/enable
     */
    public function enable2fa(): void
    {
        $this->auth->requireAuth();

        $data = getJsonBody();
        if (empty($data['code'])) {
            jsonError(__('auth.2fa_code_required'), 400);
        }

        $result = $this->auth->enable2fa($data['code']);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/auth/2fa/disable
     */
    public function disable2fa(): void
    {
        $this->auth->requireAuth();

        $data = getJsonBody();
        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }

        $result = $this->auth->disable2fa($data['password']);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/auth/logout
     */
    public function logout(): void
    {
        $this->auth->logout();
        jsonSuccess(null, __('auth.logout'));
    }

    /**
     * GET /api/auth/check
     */
    public function check(): void
    {
        if ($this->auth->isAuthenticated()) {
            jsonSuccess([
                'authenticated' => true,
                'user' => $this->auth->getUser()->toArray()
            ]);
        } else {
            jsonSuccess([
                'authenticated' => false,
                'user' => null
            ]);
        }
    }

    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        $data = getJsonBody();

        if (empty($data['username'])) {
            jsonError(__('api.field_required', ['field' => __('common.username')]), 400);
        }
        if (empty($data['email'])) {
            jsonError(__('api.field_required', ['field' => __('common.email')]), 400);
        }
        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }
        if (strlen($data['password']) < 8) {
            jsonError(__('auth.password_min_length'), 400);
        }
        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            jsonError(__('auth.password_mismatch'), 400);
        }

        $result = $this->auth->registerAdmin($data);

        if ($result['success']) {
            jsonSuccess($result, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/auth/refresh
     */
    public function refresh(): void
    {
        if (!$this->auth->isAuthenticated()) {
            jsonError(__('auth.not_authenticated'), 401);
        }

        // Prolonger la session
        if (isset($_SESSION['session_id'])) {
            $stmt = $this->auth->pdo->prepare("
                UPDATE user_sessions
                SET expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR)
                WHERE id = ?
            ");
            $stmt->execute([$_SESSION['session_id']]);
        }

        jsonSuccess([
            'authenticated' => true,
            'user' => $this->auth->getUser()->toArray()
        ], __('api.session_extended'));
    }

    /**
     * POST /api/auth/forgot-password
     */
    public function forgotPassword(): void
    {
        $data = getJsonBody();

        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            jsonError(__('api.field_required', ['field' => __('common.email')]), 400);
        }

        $result = $this->auth->requestPasswordReset($data['email']);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 429);
        }
    }

    /**
     * POST /api/auth/reset-password
     */
    public function resetPassword(): void
    {
        $data = getJsonBody();

        if (empty($data['token'])) {
            jsonError(__('email.reset_invalid_token'), 400);
        }
        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }
        if (strlen($data['password']) < 8) {
            jsonError(__('auth.password_min_length'), 400);
        }
        if ($data['password'] !== ($data['password_confirm'] ?? '')) {
            jsonError(__('auth.password_mismatch'), 400);
        }

        $result = $this->auth->resetPassword($data['token'], $data['password']);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/auth/resend-verification
     */
    public function resendVerification(): void
    {
        $data = getJsonBody();

        if (empty($data['email'])) {
            jsonError(__('api.field_required', ['field' => __('common.email')]), 400);
        }

        $result = $this->auth->resendVerificationEmail($data['email']);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 429);
        }
    }
}
