<?php
/**
 * Controller API Utilisateurs
 */

class UserController
{
    private AuthService $auth;
    private PDO $pdo;

    public function __construct(AuthService $auth, PDO $pdo)
    {
        $this->auth = $auth;
        $this->pdo = $pdo;
    }

    /**
     * GET /api/users
     */
    public function index(): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $role = get('role');
        $users = $this->auth->listUsers($role);

        // Masquer les mots de passe
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);

        jsonSuccess($users);
    }

    /**
     * GET /api/users/{id}
     */
    public function show(array $params): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $id = (int)$params['id'];
        $currentUser = $this->auth->getUser();
        $myAdminId = $this->auth->getAdminId();

        $stmt = $this->pdo->prepare("
            SELECT u.*,
                   (SELECT COUNT(*) FROM vouchers v WHERE v.created_by = u.id) as vouchers_count
            FROM users u
            WHERE u.id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonError(__('api.user_not_found'), 404);
        }

        // Vérifier que l'utilisateur appartient au même admin
        if ($user['role'] !== 'admin') {
            $targetAdminId = $this->auth->getAdminId((int)$user['id']);
            if ($targetAdminId !== $myAdminId) {
                jsonError(__('api.user_not_found'), 404);
            }
        } elseif ((int)$user['id'] !== $currentUser->getId()) {
            jsonError(__('api.access_denied'), 403);
        }

        unset($user['password']);

        // Charger les zones assignées
        $stmt = $this->pdo->prepare("
            SELECT uz.zone_id, z.name as zone_name, z.color as zone_color, uz.can_manage
            FROM user_zones uz
            JOIN zones z ON uz.zone_id = z.id
            WHERE uz.user_id = ?
        ");
        $stmt->execute([$id]);
        $user['zones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Charger les NAS assignés (pour les vendeurs)
        $stmt = $this->pdo->prepare("
            SELECT un.nas_id, n.nasname, n.shortname, n.router_id, n.zone_id, un.can_manage
            FROM user_nas un
            JOIN nas n ON un.nas_id = n.id
            WHERE un.user_id = ?
        ");
        $stmt->execute([$id]);
        $user['nas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess($user);
    }

    /**
     * POST /api/users
     */
    public function store(): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $data = getJsonBody();

        if (empty($data['username'])) {
            jsonError(__('api.user_username_required'), 400);
        }

        if (empty($data['password'])) {
            jsonError(__('api.password_required'), 400);
        }

        if (strlen($data['password']) < 6) {
            jsonError(__('api.password_min_length'), 400);
        }

        $result = $this->auth->createUser($data);

        if ($result['success']) {
            jsonSuccess(['id' => $result['user_id']], $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * PUT /api/users/{id}
     */
    public function update(array $params): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $id = (int)$params['id'];
        $data = getJsonBody();

        $result = $this->auth->updateUser($id, $data);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * DELETE /api/users/{id}
     */
    public function destroy(array $params): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $id = (int)$params['id'];

        $result = $this->auth->deleteUser($id);

        if ($result['success']) {
            jsonSuccess(null, $result['message']);
        } else {
            jsonError($result['message'], 400);
        }
    }

    /**
     * POST /api/users/{id}/toggle
     */
    public function toggle(array $params): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $id = (int)$params['id'];

        // Récupérer l'utilisateur
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            jsonError(__('api.user_not_found'), 404);
        }

        // Empêcher de désactiver un admin
        if ($user['role'] === 'admin') {
            jsonError(__('api.user_cannot_disable_admin'), 400);
        }

        // Vérifier que l'utilisateur appartient au même admin
        $targetAdminId = $this->auth->getAdminId((int)$user['id']);
        $myAdminId = $this->auth->getAdminId();
        if ($targetAdminId !== $myAdminId) {
            jsonError(__('api.user_not_found'), 404);
        }

        // Basculer le statut
        $newStatus = $user['is_active'] ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        $this->auth->logActivity(
            $this->auth->getUser()->getId(),
            $newStatus ? 'activate_user' : 'deactivate_user',
            'user',
            $id
        );

        jsonSuccess(
            ['is_active' => $newStatus],
            $newStatus ? __('api.user_activated') : __('api.user_deactivated')
        );
    }

    /**
     * GET /api/users/me
     */
    public function me(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        jsonSuccess($user->toArray());
    }

    /**
     * PUT /api/users/me
     */
    public function updateMe(): void
    {
        $this->auth->requireAuth();
        $user = $this->auth->getUser();
        $data = getJsonBody();

        // L'utilisateur peut modifier son propre profil (mais pas son rôle)
        $allowedFields = ['email', 'phone', 'full_name'];
        $updates = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }

        // Changement de mot de passe
        if (!empty($data['current_password']) && !empty($data['new_password'])) {
            // Vérifier l'ancien mot de passe
            $stmt = $this->pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user->getId()]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($data['current_password'], $row['password'])) {
                jsonError(__('api.current_password_incorrect'), 400);
            }

            if (strlen($data['new_password']) < 6) {
                jsonError(__('api.new_password_min_length'), 400);
            }

            $updates[] = "password = ?";
            $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
        }

        if (!empty($updates)) {
            $params[] = $user->getId();
            $stmt = $this->pdo->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params);
        }

        jsonSuccess(null, __('api.user_profile_updated'));
    }

    /**
     * GET /api/users/{id}/activity
     */
    public function activity(array $params): void
    {
        $this->auth->requireAuth();

        $id = (int)$params['id'];
        $currentUser = $this->auth->getUser();

        // Un utilisateur peut voir sa propre activité, ou les admins peuvent voir celle des autres
        if ($id !== $currentUser->getId() && !$currentUser->canManageUsers()) {
            jsonError(__('api.access_denied'), 403);
        }

        $limit = min((int)(get('limit') ?? 50), 100);
        $offset = (int)(get('offset') ?? 0);

        $stmt = $this->pdo->prepare("
            SELECT * FROM user_activity_logs
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$id, $limit, $offset]);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Décoder les détails JSON
        foreach ($activities as &$activity) {
            if ($activity['details']) {
                $activity['details'] = json_decode($activity['details'], true);
            }
        }

        jsonSuccess($activities);
    }

    /**
     * GET /api/users/stats
     */
    public function stats(): void
    {
        $this->auth->requireAuth();
        $this->auth->require('manage_users');

        $currentUser = $this->auth->getUser();
        $currentId = $currentUser->getId();

        // Compter uniquement les sous-utilisateurs de cet admin
        $stmt = $this->pdo->prepare("
            SELECT
                role,
                COUNT(*) as count,
                SUM(is_active) as active_count
            FROM users
            WHERE parent_id = ? OR parent_id IN (
                SELECT id FROM users WHERE parent_id = ? AND role IN ('gerant')
            )
            GROUP BY role
        ");
        $stmt->execute([$currentId, $currentId]);
        $byRole = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total,
                   SUM(is_active) as active,
                   SUM(CASE WHEN last_login > DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as recent_active
            FROM users
            WHERE parent_id = ? OR parent_id IN (
                SELECT id FROM users WHERE parent_id = ? AND role IN ('gerant')
            )
        ");
        $stmt->execute([$currentId, $currentId]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonSuccess([
            'by_role' => $byRole,
            'totals' => $totals
        ]);
    }
}
