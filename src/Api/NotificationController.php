<?php
/**
 * Controller API pour les Notifications Système
 * Gère les notifications globales du Super Admin vers les Admins
 */

class NotificationController
{
    private RadiusDatabase $db;
    private AuthService $auth;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
    }

    private function requireAuth(): User
    {
        $this->auth->requireAuth();
        return $this->auth->getUser();
    }

    private function requireSuperAdmin(): void
    {
        $user = $this->requireAuth();
        if (!$user->isSuperAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => __('auth.superadmin_required') ?? 'Accès réservé au Super Admin']);
            exit;
        }
    }

    // =============================================
    // SUPER ADMIN ENDPOINTS (CRUD)
    // =============================================

    /**
     * GET /api/superadmin/notifications
     * Lister toutes les notifications pour la gestion (Super Admin)
     */
    public function listAll(): void
    {
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        // Récupérer les stats de lecture en même temps
        $stmt = $pdo->query("
            SELECT sn.*, 
                   u.username as created_by_name,
                   (SELECT COUNT(*) FROM system_notification_reads snr WHERE snr.notification_id = sn.id) as read_count,
                   (SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1) as total_admins
            FROM system_notifications sn
            LEFT JOIN users u ON sn.created_by = u.id
            ORDER BY sn.created_at DESC
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess($notifications);
    }

    /**
     * POST /api/superadmin/notifications
     * Créer une notification
     */
    public function create(): void
    {
        $user = $this->auth->getUser();
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();

        $data = getJsonBody();

        if (empty($data['title']) || empty($data['message'])) {
            jsonError(__('validation.required_fields') ?? 'Champs obligatoires manquants', 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO system_notifications (title, message, type, is_active, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");

        $type = $data['type'] ?? 'info';
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        $stmt->execute([
            $data['title'],
            $data['message'],
            $type,
            $isActive,
            $user->getId()
        ]);

        $notificationId = (int)$pdo->lastInsertId();

        $this->auth->logActivity($user->getId(), 'create_system_notification', 'notification', $notificationId, [
            'title' => $data['title']
        ]);

        echo json_encode(['success' => true, 'message' => __('superadmin.notification_created') ?? 'Notification créée', 'id' => $notificationId]);
    }

    /**
     * PUT /api/superadmin/notifications/{id}
     * Mettre à jour une notification
     */
    public function update(array $params): void
    {
        $user = $this->auth->getUser();
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $notificationId = (int)$params['id'];

        $data = getJsonBody();

        $stmt = $pdo->prepare("SELECT id FROM system_notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        if (!$stmt->fetch()) {
            jsonError(__('global.not_found') ?? 'Introuvable', 404);
        }

        $updates = [];
        $params_sql = [];

        if (isset($data['title'])) {
            $updates[] = 'title = ?';
            $params_sql[] = $data['title'];
        }
        if (isset($data['message'])) {
            $updates[] = 'message = ?';
            $params_sql[] = $data['message'];
        }
        if (isset($data['type'])) {
            $updates[] = 'type = ?';
            $params_sql[] = $data['type'];
        }
        if (isset($data['is_active'])) {
            $updates[] = 'is_active = ?';
            $params_sql[] = (int)$data['is_active'];
        }

        if (!empty($updates)) {
            $params_sql[] = $notificationId;
            $stmt = $pdo->prepare("UPDATE system_notifications SET " . implode(', ', $updates) . " WHERE id = ?");
            $stmt->execute($params_sql);
        }

        $this->auth->logActivity($user->getId(), 'update_system_notification', 'notification', $notificationId);

        echo json_encode(['success' => true, 'message' => __('global.updated') ?? 'Mis à jour']);
    }

    /**
     * DELETE /api/superadmin/notifications/{id}
     * Supprimer une notification
     */
    public function delete(array $params): void
    {
        $user = $this->auth->getUser();
        $this->requireSuperAdmin();
        $pdo = $this->db->getPdo();
        $notificationId = (int)$params['id'];

        $pdo->beginTransaction();
        try {
            // Delete reads first
            $pdo->prepare("DELETE FROM system_notification_reads WHERE notification_id = ?")->execute([$notificationId]);
            // Delete notification
            $pdo->prepare("DELETE FROM system_notifications WHERE id = ?")->execute([$notificationId]);

            $pdo->commit();

            $this->auth->logActivity($user->getId(), 'delete_system_notification', 'notification', $notificationId);
            echo json_encode(['success' => true, 'message' => __('global.deleted') ?? 'Supprimé']);
        }
        catch (Exception $e) {
            $pdo->rollBack();
            jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }

    // =============================================
    // ADMIN ENDPOINTS (LECTURE)
    // =============================================

    /**
     * GET /api/notifications
     * Récupérer les notifications actives pour l'utilisateur courant
     */
    public function getUserNotifications(): void
    {
        $user = $this->requireAuth();
        // Optionnel : ne lister que si l'utilisateur est admin/superadmin selon la demande. 
        if (!$user->isAtLeastAdmin()) {
            jsonSuccess(['notifications' => [], 'unread' => 0]);
            return;
        }

        $pdo = $this->db->getPdo();
        $userId = $user->getId();

        // On récupère toutes les actives, et on join avec reads pour voir le statut
        $stmt = $pdo->prepare("
            SELECT sn.*, 
                   CASE WHEN snr.id IS NOT NULL THEN 1 ELSE 0 END as is_read
            FROM system_notifications sn
            LEFT JOIN system_notification_reads snr ON snr.notification_id = sn.id AND snr.user_id = ?
            WHERE sn.is_active = 1
            ORDER BY sn.created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unreadCount = 0;
        foreach ($notifications as $n) {
            if (!$n['is_read'])
                $unreadCount++;
        }

        jsonSuccess([
            'notifications' => $notifications,
            'unread' => $unreadCount
        ]);
    }

    /**
     * POST /api/notifications/{id}/read
     * Marquer une notification spécifique comme lue
     */
    public function markAsRead(array $params): void
    {
        $user = $this->requireAuth();
        $pdo = $this->db->getPdo();
        $notificationId = (int)$params['id'];
        $userId = $user->getId();

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO system_notification_reads (notification_id, user_id, read_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$notificationId, $userId]);

        echo json_encode(['success' => true]);
    }

    /**
     * POST /api/notifications/read-all
     * Marquer toutes les notifications actives comme lues
     */
    public function markAllAsRead(): void
    {
        $user = $this->requireAuth();
        $pdo = $this->db->getPdo();
        $userId = $user->getId();

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO system_notification_reads (notification_id, user_id, read_at)
            SELECT id, ?, NOW() FROM system_notifications WHERE is_active = 1
        ");
        $stmt->execute([$userId]);

        echo json_encode(['success' => true]);
    }
}