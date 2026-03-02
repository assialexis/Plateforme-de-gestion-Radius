<?php
/**
 * Controller API SuperAdmin
 * Gestion cross-tenant: admins, permissions, paramètres globaux
 */

class SuperAdminController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function requireSuperAdmin(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        if (!$user || !$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => __('auth.superadmin_required') ?? 'Accès réservé au SuperAdmin']);
            exit;
        }
    }

    // =============================================
    // GESTION DES ADMINS
    // =============================================

    public function listAdmins(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->query("
            SELECT u.id, u.username, u.email, u.phone, u.full_name, u.role, u.is_active,
                   u.last_login, u.created_at, u.credit_balance, u.sms_credit_balance,
                   (SELECT COUNT(*) FROM users u2 WHERE u2.parent_id = u.id) as sub_users,
                   (SELECT COUNT(*) FROM vouchers v WHERE v.admin_id = u.id) as total_vouchers,
                   (SELECT COUNT(*) FROM zones z WHERE z.admin_id = u.id) as total_zones,
                   (SELECT COUNT(*) FROM nas n WHERE n.admin_id = u.id) as total_nas
            FROM users u
            WHERE u.role = 'admin'
            ORDER BY u.created_at DESC
        ");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($admins as &$admin) {
            unset($admin['password']);
        }

        echo json_encode(['success' => true, 'admins' => $admins]);
    }

    public function showAdmin(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $adminId = (int)($params['id'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT u.id, u.username, u.email, u.phone, u.full_name, u.role, u.is_active,
                   u.last_login, u.created_at, u.credit_balance, u.sms_credit_balance,
                   (SELECT COUNT(*) FROM users u2 WHERE u2.parent_id = u.id) as sub_users,
                   (SELECT COUNT(*) FROM vouchers v WHERE v.admin_id = u.id) as total_vouchers,
                   (SELECT COUNT(*) FROM zones z WHERE z.admin_id = u.id) as total_zones,
                   (SELECT COUNT(*) FROM nas n WHERE n.admin_id = u.id) as total_nas
            FROM users u
            WHERE u.id = ? AND u.role = 'admin'
        ");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        unset($admin['password']);

        // Sous-utilisateurs
        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, role, is_active, last_login, created_at
            FROM users WHERE parent_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$adminId]);
        $admin['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Modules
        $stmt = $pdo->prepare("
            SELECT module_code, name, is_active FROM modules WHERE admin_id = ? ORDER BY display_order
        ");
        $stmt->execute([$adminId]);
        $admin['modules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'admin' => $admin]);
    }

    public function createAdmin(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['username']) || empty($data['password']) || empty($data['email'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => __('validation.required_fields') ?? 'Champs obligatoires manquants']);
            return;
        }

        // Vérifier unicité
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$data['username'], $data['email']]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => __('auth.username_exists')]);
            return;
        }

        $pdo->beginTransaction();
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

            // Créer le compte admin
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, email, phone, full_name, role, parent_id, is_active)
                VALUES (?, ?, ?, ?, ?, 'admin', NULL, ?)
            ");
            $stmt->execute([
                $data['username'],
                $hashedPassword,
                $data['email'],
                $data['phone'] ?? null,
                $data['full_name'] ?? null,
                $data['is_active'] ?? 1
            ]);
            $adminId = (int)$pdo->lastInsertId();

            // Créer une zone par défaut
            $zoneCode = 'zone_' . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $data['username'])) . '_' . substr(md5(time()), 0, 4);
            $stmt = $pdo->prepare("
                INSERT INTO zones (name, code, description, color, is_active, owner_id, admin_id)
                VALUES (?, ?, 'Zone principale', '#3b82f6', 1, ?, ?)
            ");
            $stmt->execute([
                'Zone de ' . ($data['full_name'] ?? $data['username']),
                $zoneCode,
                $adminId,
                $adminId
            ]);

            // Provisionner les modules par défaut
            $this->provisionDefaultModules($pdo, $adminId);

            $pdo->commit();

            $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_create_admin', 'user', $adminId, [
                'username' => $data['username']
            ]);

            echo json_encode(['success' => true, 'message' => __('superadmin.admin_created') ?? 'Admin créé avec succès', 'admin_id' => $adminId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    private function provisionDefaultModules(PDO $pdo, int $adminId): void
    {
        // Lire les modules par défaut depuis global_settings
        $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'default_modules'");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $defaultActiveModules = $row ? json_decode($row['setting_value'], true) : ['hotspot', 'captive-portal'];

        $allModules = [
            ['hotspot', 'Hotspot', 'Gestion des vouchers, profils et sessions WiFi hotspot', 'wifi'],
            ['loyalty', 'Programme de Fidélité', 'Récompensez vos clients fidèles avec des bonus automatiques', 'gift'],
            ['chat', 'Chat Support', 'Chat en temps réel avec les clients sur la page de paiement', 'chat-bubble-left-right'],
            ['sms', 'Notifications SMS', 'Envoi de SMS automatiques aux clients', 'device-phone-mobile'],
            ['analytics', 'Analytiques Avancées', 'Tableaux de bord et rapports détaillés', 'chart-bar'],
            ['pppoe', 'Gestion PPPoE', 'Gestion des abonnés PPPoE avec authentification RADIUS', 'signal'],
            ['whatsapp', 'WhatsApp', 'Notifications WhatsApp automatiques aux clients', 'chat-bubble-left'],
            ['telegram', 'Telegram', 'Notifications Telegram automatiques aux clients', 'paper-airplane'],
            ['captive-portal', 'Portail Captif', 'Personnalisation des pages de connexion du hotspot', 'wifi'],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO modules (module_code, name, description, icon, is_active, display_order, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $order = 1;
        foreach ($allModules as $module) {
            $isActive = in_array($module[0], $defaultActiveModules) ? 1 : 0;
            $stmt->execute([$module[0], $module[1], $module[2], $module[3], $isActive, $order, $adminId]);
            $order++;
        }
    }

    public function updateAdmin(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $adminId = (int)($params['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        $updates = [];
        $params_sql = [];

        if (isset($data['email'])) { $updates[] = 'email = ?'; $params_sql[] = $data['email']; }
        if (isset($data['phone'])) { $updates[] = 'phone = ?'; $params_sql[] = $data['phone']; }
        if (isset($data['full_name'])) { $updates[] = 'full_name = ?'; $params_sql[] = $data['full_name']; }
        if (isset($data['is_active'])) { $updates[] = 'is_active = ?'; $params_sql[] = $data['is_active'] ? 1 : 0; }
        if (!empty($data['password'])) { $updates[] = 'password = ?'; $params_sql[] = password_hash($data['password'], PASSWORD_DEFAULT); }

        if (!empty($updates)) {
            $params_sql[] = $adminId;
            $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params_sql);
        }

        $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_update_admin', 'user', $adminId);

        echo json_encode(['success' => true, 'message' => __('user.msg_updated')]);
    }

    public function toggleAdmin(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $adminId = (int)($params['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT id, is_active, username FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        $newStatus = $admin['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $adminId]);

        $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_toggle_admin', 'user', $adminId, [
            'username' => $admin['username'],
            'is_active' => $newStatus
        ]);

        $statusMsg = $newStatus ? (__('superadmin.admin_activated') ?? 'Admin activé') : (__('superadmin.admin_deactivated') ?? 'Admin désactivé');
        echo json_encode(['success' => true, 'message' => $statusMsg, 'is_active' => $newStatus]);
    }

    public function deleteAdmin(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $adminId = (int)($params['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$admin) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        $pdo->beginTransaction();
        try {
            // Supprimer les sous-utilisateurs
            $pdo->prepare("DELETE FROM users WHERE parent_id = ?")->execute([$adminId]);
            // Supprimer l'admin
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$adminId]);

            $pdo->commit();

            $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_delete_admin', 'user', $adminId, [
                'username' => $admin['username']
            ]);

            echo json_encode(['success' => true, 'message' => __('superadmin.admin_deleted') ?? 'Admin supprimé avec succès']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // STATISTIQUES CROSS-TENANT
    // =============================================

    public function adminStats(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $stats = [];

        $row = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'")->fetch();
        $stats['total_admins'] = (int)$row['total'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND is_active = 1")->fetch();
        $stats['active_admins'] = (int)$row['total'];

        $stats['inactive_admins'] = $stats['total_admins'] - $stats['active_admins'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch();
        $stats['recent_admins'] = (int)$row['total'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role != 'superadmin'")->fetch();
        $stats['total_users'] = (int)$row['total'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM vouchers")->fetch();
        $stats['total_vouchers'] = (int)$row['total'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM nas")->fetch();
        $stats['total_nas'] = (int)$row['total'];

        $row = $pdo->query("SELECT COUNT(*) as total FROM zones")->fetch();
        $stats['total_zones'] = (int)$row['total'];

        echo json_encode(['success' => true, 'stats' => $stats]);
    }

    // =============================================
    // GESTION DES PERMISSIONS
    // =============================================

    public function listPermissions(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->query("SELECT * FROM permissions ORDER BY category, display_order");
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Grouper par catégorie
        $grouped = [];
        foreach ($permissions as $perm) {
            $grouped[$perm['category']][] = $perm;
        }

        echo json_encode(['success' => true, 'permissions' => $grouped]);
    }

    public function getRolePermissions(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $role = $params['role'] ?? '';

        $validRoles = ['admin', 'gerant', 'vendeur', 'technicien'];
        if (!in_array($role, $validRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
            return;
        }

        // Permissions globales pour ce rôle (admin_id IS NULL)
        $stmt = $pdo->prepare("
            SELECT p.id, p.permission_code, p.name, p.description, p.category, p.display_order,
                   CASE WHEN rp.id IS NOT NULL THEN 1 ELSE 0 END as granted
            FROM permissions p
            LEFT JOIN role_permissions rp ON rp.permission_id = p.id AND rp.role = ? AND rp.admin_id IS NULL
            WHERE p.category != 'superadmin'
            ORDER BY p.category, p.display_order
        ");
        $stmt->execute([$role]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'role' => $role, 'permissions' => $permissions]);
    }

    public function updateRolePermissions(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $role = $params['role'] ?? '';
        $data = json_decode(file_get_contents('php://input'), true);

        $validRoles = ['admin', 'gerant', 'vendeur', 'technicien'];
        if (!in_array($role, $validRoles)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Rôle invalide']);
            return;
        }

        $permissionIds = $data['permission_ids'] ?? [];

        $pdo->beginTransaction();
        try {
            // Supprimer les permissions globales actuelles pour ce rôle
            $stmt = $pdo->prepare("DELETE FROM role_permissions WHERE role = ? AND admin_id IS NULL");
            $stmt->execute([$role]);

            // Insérer les nouvelles permissions
            if (!empty($permissionIds)) {
                $stmt = $pdo->prepare("INSERT INTO role_permissions (role, permission_id, admin_id) VALUES (?, ?, NULL)");
                foreach ($permissionIds as $permId) {
                    $stmt->execute([$role, (int)$permId]);
                }
            }

            $pdo->commit();

            $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_update_role_permissions', null, null, [
                'role' => $role,
                'permission_count' => count($permissionIds)
            ]);

            echo json_encode(['success' => true, 'message' => __('superadmin.permissions_updated') ?? 'Permissions mises à jour']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    public function getUserPermissions(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $userId = (int)($params['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        // Permissions avec surcharges utilisateur
        $stmt = $pdo->prepare("
            SELECT p.id, p.permission_code, p.name, p.description, p.category,
                   CASE WHEN rp.id IS NOT NULL THEN 1 ELSE 0 END as role_granted,
                   up.granted as user_override
            FROM permissions p
            LEFT JOIN role_permissions rp ON rp.permission_id = p.id AND rp.role = ? AND rp.admin_id IS NULL
            LEFT JOIN user_permissions up ON up.permission_id = p.id AND up.user_id = ?
            WHERE p.category != 'superadmin'
            ORDER BY p.category, p.display_order
        ");
        $stmt->execute([$user['role'], $userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'user' => $user, 'permissions' => $permissions]);
    }

    public function updateUserPermissions(array $params): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $userId = (int)($params['id'] ?? 0);
        $data = json_decode(file_get_contents('php://input'), true);

        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => __('user.not_found')]);
            return;
        }

        $overrides = $data['overrides'] ?? [];
        // Format: [{ permission_id: int, granted: 0|1|null }]
        // null = supprimer la surcharge (revenir au défaut du rôle)

        $pdo->beginTransaction();
        try {
            // Supprimer les surcharges existantes
            $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);

            // Insérer les nouvelles surcharges
            $stmt = $pdo->prepare("
                INSERT INTO user_permissions (user_id, permission_id, granted, granted_by)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($overrides as $override) {
                if ($override['granted'] !== null) {
                    $stmt->execute([
                        $userId,
                        (int)$override['permission_id'],
                        (int)$override['granted'],
                        $this->auth->getUser()->getId()
                    ]);
                }
            }

            $pdo->commit();

            echo json_encode(['success' => true, 'message' => __('superadmin.permissions_updated') ?? 'Permissions mises à jour']);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    // =============================================
    // PARAMÈTRES GLOBAUX
    // =============================================

    public function getGlobalSettings(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->query("SELECT * FROM global_settings ORDER BY id");
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convertir en map clé-valeur
        $map = [];
        foreach ($settings as $s) {
            $map[$s['setting_key']] = [
                'value' => $s['setting_value'],
                'description' => $s['description']
            ];
        }

        echo json_encode(['success' => true, 'settings' => $map]);
    }

    public function updateGlobalSettings(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['settings'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Aucun paramètre fourni']);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO global_settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ");

        foreach ($data['settings'] as $key => $value) {
            $stmt->execute([$key, is_array($value) ? json_encode($value) : (string)$value]);
        }

        $this->auth->logActivity($this->auth->getUser()->getId(), 'superadmin_update_settings', null, null, [
            'keys' => array_keys($data['settings'])
        ]);

        echo json_encode(['success' => true, 'message' => __('superadmin.settings_updated') ?? 'Paramètres mis à jour']);
    }
}
