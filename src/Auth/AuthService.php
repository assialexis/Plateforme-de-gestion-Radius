<?php
/**
 * Service d'authentification - Multi-tenant
 * Chaque admin est un tenant isolé avec ses propres données
 */

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/TwoFactorAuth.php';

class AuthService
{
    private PDO $pdo;
    private ?User $currentUser = null;
    private string $sessionName = 'radius_session';
    private int $sessionLifetime = 86400; // 24 heures

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->initSession();
    }

    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name($this->sessionName);
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            $this->loadUserFromSession();
        }
    }

    private function loadUserFromSession(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return;
        }

        $stmt = $this->pdo->prepare("
            SELECT us.*, u.*
            FROM user_sessions us
            JOIN users u ON us.user_id = u.id
            WHERE us.id = ? AND us.user_id = ? AND us.expires_at > NOW() AND u.is_active = 1
        ");
        $stmt->execute([$_SESSION['session_id'] ?? '', $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            $this->currentUser = new User($data);
            $this->loadUserZones();
            $this->loadUserNas();
        } else {
            $this->logout();
        }
    }

    /**
     * Résoudre l'admin_id (tenant) pour l'utilisateur courant.
     * Pour un admin: retourne son propre id.
     * Pour gérant/vendeur/client: remonte la chaîne parent_id jusqu'à trouver l'admin.
     */
    public function getAdminId(?int $userId = null): ?int
    {
        if (!$userId && $this->currentUser) {
            // SuperAdmin et Admin sont chacun leur propre tenant
            if ($this->currentUser->isSuperAdmin() || $this->currentUser->isAdmin()) {
                return $this->currentUser->getId();
            }
            $userId = $this->currentUser->getParentId();
        }

        // Remonter la chaîne parent_id (max 5 niveaux)
        $maxDepth = 5;
        $currentId = $userId;

        while ($currentId && $maxDepth > 0) {
            $stmt = $this->pdo->prepare("SELECT id, role, parent_id FROM users WHERE id = ?");
            $stmt->execute([$currentId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) return null;
            if ($row['role'] === 'superadmin' || $row['role'] === 'admin') return (int)$row['id'];

            $currentId = $row['parent_id'];
            $maxDepth--;
        }

        return null;
    }

    /**
     * Charger les zones de l'utilisateur (filtré par admin_id)
     */
    private function loadUserZones(): void
    {
        if (!$this->currentUser) {
            return;
        }

        // SuperAdmin: pas de zones propres (cross-tenant via pages dédiées)
        if ($this->currentUser->isSuperAdmin()) {
            $this->currentUser->setZones([]);
            return;
        }

        // Admin voit ses propres zones (filtrées par admin_id)
        if ($this->currentUser->isAdmin()) {
            $stmt = $this->pdo->prepare("
                SELECT id as zone_id, name, 1 as can_manage
                FROM zones
                WHERE admin_id = ? AND is_active = 1
            ");
            $stmt->execute([$this->currentUser->getId()]);
            $this->currentUser->setZones($stmt->fetchAll(PDO::FETCH_ASSOC));
            return;
        }

        // Gérant et Vendeur voient les zones assignées
        $stmt = $this->pdo->prepare("
            SELECT uz.zone_id, z.name, uz.can_manage
            FROM user_zones uz
            JOIN zones z ON uz.zone_id = z.id
            WHERE uz.user_id = ? AND z.is_active = 1
        ");
        $stmt->execute([$this->currentUser->getId()]);
        $this->currentUser->setZones($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Charger les NAS de l'utilisateur (filtré par admin_id)
     */
    private function loadUserNas(): void
    {
        if (!$this->currentUser) {
            return;
        }

        // SuperAdmin: pas de NAS propres
        if ($this->currentUser->isSuperAdmin()) {
            $this->currentUser->setNas([]);
            return;
        }

        // Admin voit ses propres NAS
        if ($this->currentUser->isAdmin()) {
            $stmt = $this->pdo->prepare("
                SELECT n.id as nas_id, n.nasname, n.shortname, n.router_id, 1 as can_manage
                FROM nas n
                WHERE n.admin_id = ?
            ");
            $stmt->execute([$this->currentUser->getId()]);
            $this->currentUser->setNas($stmt->fetchAll(PDO::FETCH_ASSOC));
            return;
        }

        // Vendeur voit uniquement les NAS qui lui sont assignés
        if ($this->currentUser->isVendeur()) {
            $stmt = $this->pdo->prepare("
                SELECT un.nas_id, n.nasname, n.shortname, n.router_id, un.can_manage
                FROM user_nas un
                JOIN nas n ON un.nas_id = n.id
                WHERE un.user_id = ?
            ");
            $stmt->execute([$this->currentUser->getId()]);
            $this->currentUser->setNas($stmt->fetchAll(PDO::FETCH_ASSOC));
            return;
        }

        $this->currentUser->setNas([]);
    }

    /**
     * Authentifier un utilisateur
     */
    public function login(string $username, string $password): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM users WHERE username = ? OR email = ?
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => __('auth.login_error')];
        }

        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
            return ['success' => false, 'message' => __('auth.login_locked')];
        }

        if (!$user['is_active']) {
            return ['success' => false, 'message' => __('auth.account_disabled')];
        }

        // Check email verification (only for admin/superadmin with verification enabled)
        if (in_array($user['role'], ['admin', 'superadmin']) && empty($user['email_verified'])) {
            // Check if verification is enabled
            $stmt = $this->pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'email_verification_enabled'");
            $stmt->execute();
            $verificationEnabled = $stmt->fetchColumn();
            if ($verificationEnabled === '1') {
                return [
                    'success' => false,
                    'message' => __('email.not_verified'),
                    'needs_verification' => true,
                    'email' => $user['email'] ?? ''
                ];
            }
        }

        if (!password_verify($password, $user['password'])) {
            $attempts = $user['login_attempts'] + 1;
            $lockedUntil = null;

            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 900);
            }

            $stmt = $this->pdo->prepare("UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$attempts, $lockedUntil, $user['id']]);

            $remaining = 5 - $attempts;
            if ($remaining > 0) {
                return ['success' => false, 'message' => __('auth.password_incorrect', ['remaining' => $remaining])];
            }
            return ['success' => false, 'message' => __('auth.login_locked')];
        }

        // Check if 2FA is enabled for this user
        if (!empty($user['totp_enabled']) && !empty($user['totp_secret'])) {
            // Generate temporary token for 2FA verification
            $tempToken = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 300); // 5 minutes

            // Clean old tokens for this user
            $stmt = $this->pdo->prepare("DELETE FROM two_factor_tokens WHERE user_id = ? OR expires_at < NOW()");
            $stmt->execute([$user['id']]);

            // Store temp token
            $stmt = $this->pdo->prepare("
                INSERT INTO two_factor_tokens (user_id, token, ip_address, expires_at)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user['id'], $tempToken, $_SERVER['REMOTE_ADDR'] ?? '', $expiresAt]);

            // Reset login attempts on correct password
            $stmt = $this->pdo->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            return [
                'success' => true,
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'message' => __('auth.2fa_required')
            ];
        }

        // Connexion réussie (sans 2FA)
        return $this->completeLogin($user);
    }

    /**
     * Complete the login process (create session, set language, etc.)
     */
    private function completeLogin(array $user): array
    {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->sessionLifetime);

        $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $stmt->execute([$user['id']]);

        $stmt = $this->pdo->prepare("
            INSERT INTO user_sessions (id, user_id, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sessionId,
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $expiresAt
        ]);

        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW(), login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['session_id'] = $sessionId;

        $this->currentUser = new User($user);
        $this->loadUserZones();
        $this->loadUserNas();

        // Load language preference for this admin
        $adminId = $this->getAdminId((int)$user['id']);
        if ($adminId) {
            $langStmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'language' AND admin_id = ?");
            $langStmt->execute([$adminId]);
            $langRow = $langStmt->fetch(\PDO::FETCH_ASSOC);
            $_SESSION['lang'] = $langRow['setting_value'] ?? 'fr';
        } else {
            // SuperAdmin: default language
            $_SESSION['lang'] = 'fr';
        }

        $this->logActivity($user['id'], 'login', null, null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        return [
            'success' => true,
            'message' => __('auth.login_success'),
            'user' => $this->currentUser->toArray()
        ];
    }

    /**
     * Inscrire un nouvel administrateur (tenant)
     */
    public function registerAdmin(array $data): array
    {
        // Vérifier unicité
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email'] ?? '']);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => __('auth.username_exists')];
        }

        // Check if email verification is enabled
        $requiresVerification = false;
        $stmt = $this->pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'email_verification_enabled'");
        $stmt->execute();
        $verificationEnabled = $stmt->fetchColumn();
        if ($verificationEnabled === '1' && !empty($data['email'])) {
            require_once __DIR__ . '/../Services/EmailService.php';
            $emailService = new EmailService($this->pdo);
            if ($emailService->isConfigured()) {
                $requiresVerification = true;
            }
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $this->pdo->beginTransaction();
        try {
            $emailVerified = $requiresVerification ? 0 : 1;
            $verificationToken = $requiresVerification ? bin2hex(random_bytes(32)) : null;
            $tokenExpires = $requiresVerification ? date('Y-m-d H:i:s', time() + 86400) : null;

            // Créer l'utilisateur admin
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, password, email, email_verified, email_verification_token, email_token_expires_at, phone, full_name, role, parent_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'admin', NULL, 1)
            ");
            $stmt->execute([
                $data['username'],
                $hashedPassword,
                $data['email'] ?? null,
                $emailVerified,
                $verificationToken,
                $tokenExpires,
                $data['phone'] ?? null,
                $data['full_name'] ?? null,
            ]);
            $adminId = (int)$this->pdo->lastInsertId();

            // Créer une zone par défaut pour le nouvel admin
            $zoneCode = 'zone_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['username'])) . '_' . substr(md5(time()), 0, 4);
            $stmt = $this->pdo->prepare("
                INSERT INTO zones (name, code, description, color, is_active, owner_id, admin_id)
                VALUES (?, ?, 'Zone principale', '#3b82f6', 1, ?, ?)
            ");
            $stmt->execute([
                'Zone de ' . ($data['full_name'] ?? $data['username']),
                $zoneCode,
                $adminId,
                $adminId,
            ]);

            $this->pdo->commit();

            // Send verification email if required
            if ($requiresVerification && $verificationToken) {
                $emailService->sendVerificationEmail($data['email'], $verificationToken, $data['username']);
            }

            return [
                'success' => true,
                'message' => $requiresVerification ? __('email.check_email') : __('auth.registration_success'),
                'user_id' => $adminId,
                'requires_verification' => $requiresVerification,
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['success' => false, 'message' => __('auth.registration_error') . ': ' . $e->getMessage()];
        }
    }

    /**
     * Vérifier un email via token
     */
    public function verifyEmail(string $token): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username, email_verified FROM users
            WHERE email_verification_token = ? AND email_token_expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => __('email.invalid_token')];
        }

        if ($user['email_verified']) {
            return ['success' => true, 'message' => __('email.already_verified')];
        }

        $stmt = $this->pdo->prepare("
            UPDATE users SET email_verified = 1, email_verification_token = NULL, email_token_expires_at = NULL
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        $this->logActivity($user['id'], 'email_verified');

        return ['success' => true, 'message' => __('email.verified_success')];
    }

    /**
     * Renvoyer l'email de vérification
     */
    public function resendVerificationEmail(string $email): array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, email_verified FROM users WHERE email = ? AND role IN ('admin', 'superadmin')");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            // Ne pas révéler si l'email existe ou non
            return ['success' => true, 'message' => __('email.resend_success')];
        }

        if ($user['email_verified']) {
            return ['success' => true, 'message' => __('email.already_verified')];
        }

        // Rate limit: check last token creation
        $stmt = $this->pdo->prepare("SELECT email_token_expires_at FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row['email_token_expires_at']) {
            // Token expire dans 24h, donc il a été créé à (expires - 24h)
            $createdAt = strtotime($row['email_token_expires_at']) - 86400;
            if (time() - $createdAt < 300) {
                // Moins de 5 minutes depuis le dernier envoi
                return ['success' => false, 'message' => __('email.resend_too_soon')];
            }
        }

        require_once __DIR__ . '/../Services/EmailService.php';
        $emailService = new EmailService($this->pdo);

        if (!$emailService->isConfigured()) {
            return ['success' => false, 'message' => __('email.smtp_not_configured')];
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare("UPDATE users SET email_verification_token = ?, email_token_expires_at = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?");
        $stmt->execute([$token, $user['id']]);

        $result = $emailService->sendVerificationEmail($user['email'], $token, $user['username']);

        if ($result['success']) {
            return ['success' => true, 'message' => __('email.resend_success')];
        }

        return ['success' => false, 'message' => $result['message']];
    }

    /**
     * Demander une réinitialisation de mot de passe
     */
    public function requestPasswordReset(string $email): array
    {
        $genericMsg = __('email.reset_sent');

        $stmt = $this->pdo->prepare("SELECT id, username, email, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$user['is_active']) {
            return ['success' => true, 'message' => $genericMsg];
        }

        // Get expiry hours from settings
        $stmtExpiry = $this->pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'password_reset_expiry_hours'");
        $stmtExpiry->execute();
        $expiryHours = (int)($stmtExpiry->fetchColumn() ?: 1);
        $expirySeconds = $expiryHours * 3600;

        // Rate limit: check last token creation
        $stmt = $this->pdo->prepare("SELECT password_reset_expires_at FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['password_reset_expires_at'])) {
            $createdAt = strtotime($row['password_reset_expires_at']) - $expirySeconds;
            if (time() - $createdAt < 300) {
                return ['success' => false, 'message' => __('email.reset_too_soon')];
            }
        }

        require_once __DIR__ . '/../Services/EmailService.php';
        $emailService = new EmailService($this->pdo);

        if (!$emailService->isConfigured()) {
            return ['success' => false, 'message' => __('email.smtp_not_configured')];
        }

        $token = bin2hex(random_bytes(32));
        $stmt = $this->pdo->prepare(
            "UPDATE users SET password_reset_token = ?, password_reset_expires_at = DATE_ADD(NOW(), INTERVAL ? HOUR) WHERE id = ?"
        );
        $stmt->execute([$token, $expiryHours, $user['id']]);

        $emailService->sendPasswordResetEmail($user['email'], $token, $user['username']);

        $this->logActivity($user['id'], 'password_reset_requested', null, null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);

        return ['success' => true, 'message' => $genericMsg];
    }

    /**
     * Réinitialiser le mot de passe avec un token
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, username FROM users
            WHERE password_reset_token = ? AND password_reset_expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => __('email.reset_invalid_token')];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("
            UPDATE users SET password = ?, password_reset_token = NULL, password_reset_expires_at = NULL,
            login_attempts = 0, locked_until = NULL WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $user['id']]);

        $this->logActivity($user['id'], 'password_reset_completed');

        return ['success' => true, 'message' => __('email.reset_success')];
    }

    public function logout(): void
    {
        if (isset($_SESSION['session_id'])) {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE id = ?");
            $stmt->execute([$_SESSION['session_id']]);
        }

        if ($this->currentUser) {
            $this->logActivity($this->currentUser->getId(), 'logout');
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $this->currentUser = null;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser !== null;
    }

    public function getUser(): ?User
    {
        return $this->currentUser;
    }

    public function can(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        return match($permission) {
            'manage_users' => $this->currentUser->canManageUsers(),
            'manage_admins' => $this->currentUser->canManageAdmins(),
            'create_admins' => $this->currentUser->canCreateAdmins(),
            'create_gerants' => $this->currentUser->canCreateGerants(),
            'create_vendeurs' => $this->currentUser->canCreateVendeurs(),
            'manage_zones' => $this->currentUser->canManageZones(),
            'manage_nas' => $this->currentUser->canManageNas(),
            'manage_profiles' => $this->currentUser->canManageProfiles(),
            'create_vouchers' => $this->currentUser->canCreateVouchers(),
            'view_stats' => $this->currentUser->canViewStats(),
            'access_settings' => $this->currentUser->canAccessSettings(),
            default => false
        };
    }

    public function require(string $permission): void
    {
        if (!$this->can($permission)) {
            http_response_code(403);
            if ($this->isApiRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => __('auth.unauthorized')]);
                exit;
            }
            header('Location: /web/?error=unauthorized');
            exit;
        }
    }

    public function requireAuth(): void
    {
        if (!$this->isAuthenticated()) {
            if ($this->isApiRequest()) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => __('auth.authentication_required')]);
                exit;
            }
            header('Location: /web/login.php');
            exit;
        }
    }

    public function requireRole(string ...$roles): void
    {
        $this->requireAuth();
        if (!in_array($this->currentUser->getRole(), $roles)) {
            http_response_code(403);
            if ($this->isApiRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => __('auth.insufficient_role')]);
                exit;
            }
            header('Location: /web/?error=forbidden');
            exit;
        }
    }

    private function isApiRequest(): bool
    {
        return strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    public function logActivity(int $userId, string $action, ?string $entityType = null, ?int $entityId = null, ?array $details = null): void
    {
        try {
            $adminId = $this->getAdminId($userId);
            $stmt = $this->pdo->prepare("
                INSERT INTO user_activity_logs (user_id, action, entity_type, entity_id, details, ip_address, user_agent, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $details ? json_encode($details) : null,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $adminId
            ]);
        } catch (Exception $e) {
            // Ignorer les erreurs de log
        }
    }

    /**
     * Créer un nouvel utilisateur (gérant, vendeur ou client)
     */
    public function createUser(array $data): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        $role = $data['role'] ?? User::ROLE_CLIENT;

        // Seul le superadmin peut créer des admins
        if ($role === User::ROLE_ADMIN && !$this->currentUser->isSuperAdmin()) {
            return ['success' => false, 'message' => __('auth.admin_register_only')];
        }

        // Personne ne peut créer un superadmin
        if ($role === User::ROLE_SUPERADMIN) {
            return ['success' => false, 'message' => __('auth.unauthorized')];
        }

        if ($role === User::ROLE_VENDEUR && !$this->currentUser->canCreateVendeurs()) {
            return ['success' => false, 'message' => __('auth.insufficient_permission_vendeur')];
        }

        if ($role === User::ROLE_GERANT && !$this->currentUser->canCreateGerants()) {
            return ['success' => false, 'message' => __('auth.insufficient_permission_gerant')];
        }

        if ($role === User::ROLE_TECHNICIEN && !$this->currentUser->canCreateTechniciens()) {
            return ['success' => false, 'message' => __('auth.insufficient_permission_technicien') ?? 'Permission insuffisante pour créer un technicien'];
        }

        // Vérifier unicité
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email'] ?? '']);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => __('auth.username_exists')];
        }

        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, password, email, phone, full_name, role, parent_id, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['username'],
            $hashedPassword,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['full_name'] ?? null,
            $role,
            $this->currentUser->getId(),
            $data['is_active'] ?? 1
        ]);

        $userId = (int)$this->pdo->lastInsertId();

        // Assigner les zones
        if (!empty($data['zones']) && is_array($data['zones'])) {
            $adminId = $this->getAdminId();
            foreach ($data['zones'] as $zoneId) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_zones (user_id, zone_id, can_manage, admin_id) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $zoneId, $data['can_manage_zones'] ?? 0, $adminId]);
            }
        }

        // Assigner les NAS (pour les vendeurs)
        if (!empty($data['nas']) && is_array($data['nas'])) {
            foreach ($data['nas'] as $nasId) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO user_nas (user_id, nas_id, can_manage, assigned_by) VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $nasId, $data['can_manage_nas'] ?? 1, $this->currentUser->getId()]);
            }
        }

        $this->logActivity($this->currentUser->getId(), 'create_user', 'user', $userId, [
            'username' => $data['username'],
            'role' => $role
        ]);

        return ['success' => true, 'message' => __('user.msg_created'), 'user_id' => $userId];
    }

    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser(int $userId, array $data): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            return ['success' => false, 'message' => __('user.not_found')];
        }

        // Personne ne peut modifier un superadmin (sauf lui-même)
        if ($targetUser['role'] === User::ROLE_SUPERADMIN && $targetUser['id'] != $this->currentUser->getId()) {
            return ['success' => false, 'message' => __('auth.unauthorized')];
        }

        // SuperAdmin peut modifier n'importe quel admin
        if ($this->currentUser->isSuperAdmin()) {
            // OK - bypass les checks ci-dessous
        } elseif ($targetUser['role'] === User::ROLE_ADMIN && $targetUser['id'] != $this->currentUser->getId()) {
            // Un admin ne peut modifier qu'un autre admin s'il s'agit de lui-même
            return ['success' => false, 'message' => __('user.cannot_modify_admin')];
        } else if ($targetUser['role'] !== User::ROLE_ADMIN) {
            // Vérifier que la cible est dans la hiérarchie de l'admin courant
            $targetAdminId = $this->getAdminId((int)$targetUser['id']);
            $myAdminId = $this->getAdminId();
            if ($targetAdminId !== $myAdminId) {
                return ['success' => false, 'message' => __('user.not_in_organization')];
            }
        }

        $updates = [];
        $params = [];

        if (isset($data['email'])) { $updates[] = 'email = ?'; $params[] = $data['email']; }
        if (isset($data['phone'])) { $updates[] = 'phone = ?'; $params[] = $data['phone']; }
        if (isset($data['full_name'])) { $updates[] = 'full_name = ?'; $params[] = $data['full_name']; }
        if (isset($data['is_active'])) { $updates[] = 'is_active = ?'; $params[] = $data['is_active'] ? 1 : 0; }
        if (!empty($data['password'])) { $updates[] = 'password = ?'; $params[] = password_hash($data['password'], PASSWORD_DEFAULT); }

        if (!empty($updates)) {
            $params[] = $userId;
            $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }

        // Mettre à jour les zones
        if (isset($data['zones'])) {
            $stmt = $this->pdo->prepare("DELETE FROM user_zones WHERE user_id = ?");
            $stmt->execute([$userId]);
            if (is_array($data['zones'])) {
                $adminId = $this->getAdminId();
                foreach ($data['zones'] as $zoneId) {
                    $stmt = $this->pdo->prepare("INSERT INTO user_zones (user_id, zone_id, can_manage, admin_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $zoneId, $data['can_manage_zones'] ?? 0, $adminId]);
                }
            }
        }

        // Mettre à jour les NAS
        if (isset($data['nas'])) {
            $stmt = $this->pdo->prepare("DELETE FROM user_nas WHERE user_id = ?");
            $stmt->execute([$userId]);
            if (is_array($data['nas'])) {
                foreach ($data['nas'] as $nasId) {
                    $stmt = $this->pdo->prepare("INSERT INTO user_nas (user_id, nas_id, can_manage, assigned_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $nasId, $data['can_manage_nas'] ?? 1, $this->currentUser->getId()]);
                }
            }
        }

        $this->logActivity($this->currentUser->getId(), 'update_user', 'user', $userId);
        return ['success' => true, 'message' => __('user.msg_updated')];
    }

    /**
     * Supprimer un utilisateur
     */
    public function deleteUser(int $userId): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$targetUser) {
            return ['success' => false, 'message' => __('user.not_found')];
        }

        // Impossible de supprimer un superadmin
        if ($targetUser['role'] === User::ROLE_SUPERADMIN) {
            return ['success' => false, 'message' => __('auth.unauthorized')];
        }

        // Empêcher de se supprimer soi-même
        if ($targetUser['id'] == $this->currentUser->getId()) {
            return ['success' => false, 'message' => __('user.cannot_delete_self')];
        }

        // SuperAdmin peut supprimer des admins
        if ($this->currentUser->isSuperAdmin()) {
            // OK - bypass les checks ci-dessous
        } elseif ($targetUser['role'] === User::ROLE_ADMIN) {
            // Un admin normal ne peut pas supprimer un autre admin
            return ['success' => false, 'message' => __('user.cannot_delete_admin')];
        } else {
            // Vérifier que la cible est dans la hiérarchie
            $targetAdminId = $this->getAdminId((int)$targetUser['id']);
            $myAdminId = $this->getAdminId();
            if ($targetAdminId !== $myAdminId) {
                return ['success' => false, 'message' => __('user.not_in_organization')];
            }
        }

        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        $this->logActivity($this->currentUser->getId(), 'delete_user', 'user', $userId, [
            'username' => $targetUser['username']
        ]);

        return ['success' => true, 'message' => __('user.msg_deleted')];
    }

    /**
     * Lister les utilisateurs du même tenant (admin_id)
     */
    public function listUsers(?string $role = null): array
    {
        if (!$this->currentUser || !$this->currentUser->canManageUsers()) {
            return [];
        }

        $adminId = $this->getAdminId();

        // Admin voit ses sous-utilisateurs directs + ceux créés par ses gérants
        $sql = "SELECT u.*,
                (SELECT COUNT(*) FROM vouchers v WHERE v.created_by = u.id) as vouchers_count
                FROM users u
                WHERE (u.parent_id = ? OR u.parent_id IN (
                    SELECT id FROM users WHERE parent_id = ? AND role IN ('gerant')
                ))";
        $params = [$this->currentUser->getId(), $this->currentUser->getId()];

        if ($role) {
            $rolesList = array_map('trim', explode(',', $role));
            $placeholders = implode(',', array_fill(0, count($rolesList), '?'));
            $sql .= " AND u.role IN ($placeholders)";
            $params = array_merge($params, $rolesList);
        }

        $sql .= " ORDER BY u.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$user) {
            unset($user['password']);

            $stmt = $this->pdo->prepare("
                SELECT uz.zone_id, z.name as zone_name, uz.can_manage
                FROM user_zones uz
                JOIN zones z ON uz.zone_id = z.id
                WHERE uz.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $user['zones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmt = $this->pdo->prepare("
                SELECT un.nas_id, n.nasname, n.shortname, n.router_id, n.zone_id, un.can_manage
                FROM user_nas un
                JOIN nas n ON un.nas_id = n.id
                WHERE un.user_id = ?
            ");
            $stmt->execute([$user['id']]);
            $user['nas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $users;
    }

    /**
     * Verify 2FA code during login
     */
    public function verify2fa(string $tempToken, string $code): array
    {
        // Find the temp token
        $stmt = $this->pdo->prepare("
            SELECT t.*, u.*
            FROM two_factor_tokens t
            JOIN users u ON t.user_id = u.id
            WHERE t.token = ? AND t.expires_at > NOW()
        ");
        $stmt->execute([$tempToken]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return ['success' => false, 'message' => __('auth.2fa_token_expired')];
        }

        // Verify the TOTP code
        if (!TwoFactorAuth::verifyCode($data['totp_secret'], $code)) {
            return ['success' => false, 'message' => __('auth.2fa_invalid_code')];
        }

        // Clean up the temp token
        $stmt = $this->pdo->prepare("DELETE FROM two_factor_tokens WHERE user_id = ?");
        $stmt->execute([$data['user_id']]);

        // Complete the login
        return $this->completeLogin($data);
    }

    /**
     * Generate a new 2FA secret for setup
     */
    public function setup2fa(): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        $secret = TwoFactorAuth::generateSecret();
        $accountName = $this->currentUser->getUsername();
        $uri = TwoFactorAuth::getOtpAuthUri($secret, $accountName);

        // Store the secret temporarily (not enabled yet)
        $stmt = $this->pdo->prepare("UPDATE users SET totp_secret = ? WHERE id = ?");
        $stmt->execute([$secret, $this->currentUser->getId()]);

        return [
            'success' => true,
            'secret' => $secret,
            'otpauth_uri' => $uri,
            'account' => $accountName
        ];
    }

    /**
     * Enable 2FA after verifying the code
     */
    public function enable2fa(string $code): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        // Get the current secret
        $stmt = $this->pdo->prepare("SELECT totp_secret FROM users WHERE id = ?");
        $stmt->execute([$this->currentUser->getId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (empty($row['totp_secret'])) {
            return ['success' => false, 'message' => __('auth.2fa_no_secret')];
        }

        // Verify the code to confirm setup
        if (!TwoFactorAuth::verifyCode($row['totp_secret'], $code)) {
            return ['success' => false, 'message' => __('auth.2fa_invalid_code')];
        }

        // Enable 2FA
        $stmt = $this->pdo->prepare("UPDATE users SET totp_enabled = 1 WHERE id = ?");
        $stmt->execute([$this->currentUser->getId()]);

        $this->logActivity($this->currentUser->getId(), '2fa_enabled');

        return ['success' => true, 'message' => __('auth.2fa_enabled')];
    }

    /**
     * Disable 2FA
     */
    public function disable2fa(string $password): array
    {
        if (!$this->currentUser) {
            return ['success' => false, 'message' => __('auth.not_authenticated')];
        }

        // Verify password before disabling
        $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$this->currentUser->getId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($password, $row['password'])) {
            return ['success' => false, 'message' => __('auth.password_incorrect', ['remaining' => ''])];
        }

        // Disable 2FA and clear secret
        $stmt = $this->pdo->prepare("UPDATE users SET totp_enabled = 0, totp_secret = NULL WHERE id = ?");
        $stmt->execute([$this->currentUser->getId()]);

        $this->logActivity($this->currentUser->getId(), '2fa_disabled');

        return ['success' => true, 'message' => __('auth.2fa_disabled')];
    }

    /**
     * Get 2FA status for current user
     */
    public function get2faStatus(): array
    {
        if (!$this->currentUser) {
            return ['enabled' => false];
        }

        $stmt = $this->pdo->prepare("SELECT totp_enabled FROM users WHERE id = ?");
        $stmt->execute([$this->currentUser->getId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return ['enabled' => (bool)($row['totp_enabled'] ?? false)];
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    public function cleanExpiredSessions(): int
    {
        $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
        $stmt->execute();
        $count = $stmt->rowCount();

        // Also clean expired 2FA tokens
        $this->pdo->exec("DELETE FROM two_factor_tokens WHERE expires_at < NOW()");

        // Clean expired password reset tokens
        $this->pdo->exec("UPDATE users SET password_reset_token = NULL, password_reset_expires_at = NULL WHERE password_reset_expires_at < NOW()");

        return $count;
    }
}