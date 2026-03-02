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
            jsonSuccess($result['user'], $result['message']);
        } else {
            jsonError($result['message'], 401);
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
}
