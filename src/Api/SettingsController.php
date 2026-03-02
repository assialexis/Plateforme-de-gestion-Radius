<?php
/**
 * Controller API Paramètres généraux
 */

class SettingsController
{
    private RadiusDatabase $db;
    private AuthService $auth;

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
     * GET /api/settings
     */
    public function index(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE admin_id = ? OR admin_id IS NULL ORDER BY admin_id DESC");
        $stmt->execute([$adminId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Build key-value map, admin-specific settings override global ones
        $settings = [];
        foreach ($rows as $row) {
            if (!isset($settings[$row['setting_key']])) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }

        jsonSuccess($settings);
    }

    /**
     * PUT /api/settings
     */
    public function update(): void
    {
        $this->auth->requireAuth();
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        $allowedKeys = ['app_name', 'hotspot_title', 'currency', 'language', 'timezone', 'support_email', 'support_phone'];
        $pdo = $this->db->getPdo();

        foreach ($allowedKeys as $key) {
            if (isset($data[$key])) {
                $value = trim($data[$key]);
                $stmt = $pdo->prepare(
                    "INSERT INTO settings (setting_key, setting_value, admin_id) VALUES (?, ?, ?)
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );
                $stmt->execute([$key, $value, $adminId]);
            }
        }

        // Update session language if changed
        if (isset($data['language']) && in_array($data['language'], ['fr', 'en'])) {
            $_SESSION['lang'] = $data['language'];
        }

        jsonSuccess(null, __('settings.msg_saved'));
    }

    /**
     * POST /api/settings/password
     */
    public function changePassword(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        $data = getJsonBody();

        if (empty($data['current_password']) || empty($data['new_password'])) {
            jsonError(__('api.missing_fields'), 400);
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user->getId()]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row || !password_verify($data['current_password'], $row['password'])) {
            jsonError(__('settings.msg_wrong_password'), 400);
        }

        if (strlen($data['new_password']) < 6) {
            jsonError(__('settings.msg_password_too_short'), 400);
        }

        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([password_hash($data['new_password'], PASSWORD_DEFAULT), $user->getId()]);

        jsonSuccess(null, __('settings.msg_password_changed'));
    }
}
