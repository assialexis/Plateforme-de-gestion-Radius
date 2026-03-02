<?php
/**
 * Controller API Chat en Temps Réel
 * Gestion des conversations et messages avec les clients
 */

class ChatController
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

    // ==========================================
    // Conversations
    // ==========================================

    /**
     * GET /api/chat/conversations
     * Liste des conversations (pour admin)
     */
    public function listConversations(): void
    {
        $status = get('status') ?: 'active';
        $search = get('search');
        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "c.admin_id = ?";
            $params[] = $adminId;
        }

        if ($status !== 'all') {
            $where[] = "c.status = ?";
            $params[] = $status;
        }

        if ($search) {
            $where[] = "(c.phone LIKE ? OR c.customer_name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Compter le total
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_conversations c $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Récupérer les conversations avec le dernier message
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT c.*,
                   (SELECT message FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                   (SELECT COUNT(*) FROM chat_messages WHERE conversation_id = c.id AND sender_type = 'customer' AND is_read = 0) as unread_admin
            FROM chat_conversations c
            $whereClause
            ORDER BY c.last_message_at DESC, c.created_at DESC
            LIMIT $perPage OFFSET $offset
        ");
        $stmt->execute($params);
        $conversations = $stmt->fetchAll();

        jsonSuccess([
            'conversations' => $conversations,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage)
            ]
        ]);
    }

    /**
     * GET /api/chat/conversations/unread-count
     * Nombre de conversations avec messages non lus
     */
    public function getUnreadCount(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        if ($adminId !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(DISTINCT cm.conversation_id) as count
                FROM chat_messages cm
                INNER JOIN chat_conversations cc ON cm.conversation_id = cc.id
                WHERE cm.sender_type = 'customer' AND cm.is_read = 0 AND cc.admin_id = ?
            ");
            $stmt->execute([$adminId]);
        } else {
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT conversation_id) as count
                FROM chat_messages
                WHERE sender_type = 'customer' AND is_read = 0
            ");
        }
        $result = $stmt->fetch();

        jsonSuccess(['unread_count' => (int)$result['count']]);
    }

    /**
     * POST /api/chat/conversations
     * Créer ou récupérer une conversation (pour client)
     */
    public function getOrCreateConversation(): void
    {
        $data = getJsonBody();
        $phone = trim($data['phone'] ?? '');
        $customerName = trim($data['customer_name'] ?? '');
        $adminId = $data['admin_id'] ?? $this->getAdminId();

        if (empty($phone)) {
            jsonError(__('api.phone_required'), 400);
        }

        // Valider le format du téléphone (au moins 8 chiffres)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($cleanPhone) < 8) {
            jsonError(__('api.phone_invalid'), 400);
        }

        $pdo = $this->db->getPdo();

        // Chercher une conversation existante (scopée par admin si disponible)
        if ($adminId !== null) {
            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE phone = ? AND admin_id = ?");
            $stmt->execute([$cleanPhone, $adminId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE phone = ?");
            $stmt->execute([$cleanPhone]);
        }
        $conversation = $stmt->fetch();

        if ($conversation) {
            // Mettre à jour le nom si fourni
            if (!empty($customerName) && $customerName !== $conversation['customer_name']) {
                $stmt = $pdo->prepare("UPDATE chat_conversations SET customer_name = ? WHERE id = ?");
                $stmt->execute([$customerName, $conversation['id']]);
                $conversation['customer_name'] = $customerName;
            }

            // Si la conversation était fermée, la réouvrir
            if ($conversation['status'] === 'closed' || $conversation['status'] === 'archived') {
                $stmt = $pdo->prepare("UPDATE chat_conversations SET status = 'active', closed_at = NULL, closed_by = NULL WHERE id = ?");
                $stmt->execute([$conversation['id']]);
                $conversation['status'] = 'active';
            }
        } else {
            // Créer une nouvelle conversation avec admin_id
            $stmt = $pdo->prepare("
                INSERT INTO chat_conversations (phone, customer_name, status, admin_id)
                VALUES (?, ?, 'active', ?)
            ");
            $stmt->execute([$cleanPhone, $customerName ?: null, $adminId]);
            $conversationId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
            $stmt->execute([$conversationId]);
            $conversation = $stmt->fetch();
        }

        jsonSuccess(['conversation' => $conversation]);
    }

    /**
     * GET /api/chat/conversations/:id
     * Détails d'une conversation
     */
    public function getConversation(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            jsonError(__('api.conversation_id_invalid'), 400);
        }

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
        $stmt->execute([$id]);
        $conversation = $stmt->fetch();

        if (!$conversation) {
            jsonError(__('api.conversation_not_found'), 404);
        }

        jsonSuccess(['conversation' => $conversation]);
    }

    /**
     * GET /api/chat/conversations/by-phone/:phone
     * Récupérer une conversation par téléphone (pour client)
     */
    public function getConversationByPhone(array $params): void
    {
        $phone = trim($params['phone'] ?? '');
        if (empty($phone)) {
            jsonError(__('api.phone_required'), 400);
        }

        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE phone = ?");
        $stmt->execute([$cleanPhone]);
        $conversation = $stmt->fetch();

        if (!$conversation) {
            jsonError(__('api.conversation_not_found'), 404);
        }

        jsonSuccess(['conversation' => $conversation]);
    }

    /**
     * PUT /api/chat/conversations/:id/close
     * Fermer une conversation
     */
    public function closeConversation(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            jsonError(__('api.conversation_id_invalid'), 400);
        }

        $pdo = $this->db->getPdo();

        // Vérifier que la conversation existe
        $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
        $stmt->execute([$id]);
        $conversation = $stmt->fetch();

        if (!$conversation) {
            jsonError(__('api.conversation_not_found'), 404);
        }

        // Fermer la conversation
        $stmt = $pdo->prepare("
            UPDATE chat_conversations
            SET status = 'closed', closed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$id]);

        jsonSuccess(['message' => __('api.conversation_closed')]);
    }

    /**
     * DELETE /api/chat/conversations/:id
     * Supprimer une conversation et ses messages
     */
    public function deleteConversation(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id <= 0) {
            jsonError(__('api.conversation_id_invalid'), 400);
        }

        $pdo = $this->db->getPdo();

        // Vérifier que la conversation existe
        $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            jsonError(__('api.conversation_not_found'), 404);
        }

        // Supprimer (les messages sont supprimés en cascade)
        $stmt = $pdo->prepare("DELETE FROM chat_conversations WHERE id = ?");
        $stmt->execute([$id]);

        jsonSuccess(['message' => __('api.conversation_deleted')]);
    }

    // ==========================================
    // Messages
    // ==========================================

    /**
     * GET /api/chat/conversations/:id/messages
     * Liste des messages d'une conversation
     */
    public function listMessages(array $params): void
    {
        try {
            $conversationId = (int)($params['id'] ?? 0);
            if ($conversationId <= 0) {
                jsonError(__('api.conversation_id_invalid'), 400);
                return;
            }

            $page = max(1, (int)(get('page') ?: 1));
            $perPage = min(100, max(20, (int)(get('per_page') ?: 50)));
            $afterId = (int)(get('after_id') ?: 0); // Pour le polling: nouveaux messages après cet ID

            $pdo = $this->db->getPdo();

            // Vérifier que la conversation existe
            $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE id = ?");
            $stmt->execute([$conversationId]);
            if (!$stmt->fetch()) {
                jsonError(__('api.conversation_not_found'), 404);
                return;
            }

            // Filtrer par type (text par défaut, webrtc pour signaling)
            $typeFilter = get('type') ?: 'text';

            // Si after_id est fourni, retourner seulement les nouveaux messages
            if ($afterId > 0) {
                $stmt = $pdo->prepare("
                    SELECT * FROM chat_messages
                    WHERE conversation_id = ? AND id > ? AND (message_type = ? OR message_type IS NULL)
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$conversationId, $afterId, $typeFilter]);
                $messages = $stmt->fetchAll();

                jsonSuccess(['messages' => $messages, 'is_poll' => true]);
                return;
            }

            // Pagination normale
            $offset = ($page - 1) * $perPage;

            // Compter le total
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM chat_messages WHERE conversation_id = ? AND (message_type = ? OR message_type IS NULL)");
            $countStmt->execute([$conversationId, $typeFilter]);
            $total = (int)$countStmt->fetchColumn();

            // Récupérer les messages (du plus récent au plus ancien pour la pagination, puis inverser)
            $stmt = $pdo->prepare("
                SELECT * FROM chat_messages
                WHERE conversation_id = ? AND (message_type = ? OR message_type IS NULL)
                ORDER BY created_at DESC
                LIMIT " . (int)$perPage . " OFFSET " . (int)$offset . "
            ");
            $stmt->execute([$conversationId, $typeFilter]);
            $messages = array_reverse($stmt->fetchAll()); // Inverser pour afficher chronologiquement

            jsonSuccess([
                'messages' => $messages,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'total_pages' => ceil($total / $perPage)
                ]
            ]);
        } catch (Exception $e) {
            error_log('listMessages error: ' . $e->getMessage());
            jsonError(__('api.error_loading_messages') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/chat/conversations/:id/messages
     * Envoyer un message
     */
    public function sendMessage(array $params): void
    {
        $conversationId = (int)($params['id'] ?? 0);
        if ($conversationId <= 0) {
            jsonError(__('api.conversation_id_invalid'), 400);
        }

        $data = getJsonBody();
        $message = trim($data['message'] ?? '');
        $senderType = $data['sender_type'] ?? 'customer';
        $adminId = isset($data['admin_id']) ? (int)$data['admin_id'] : null;
        $messageType = $data['message_type'] ?? 'text';

        if (empty($message)) {
            jsonError(__('api.message_empty'), 400);
        }

        // Limite plus grande pour les messages WebRTC (SDP peut être long)
        $maxLen = $messageType === 'webrtc' ? 10000 : 2000;
        if (strlen($message) > $maxLen) {
            jsonError(__('api.message_too_long', ['max' => $maxLen]), 400);
        }

        if (!in_array($senderType, ['customer', 'admin'])) {
            jsonError(__('api.sender_type_invalid'), 400);
        }

        if (!in_array($messageType, ['text', 'webrtc'])) {
            $messageType = 'text';
        }

        $pdo = $this->db->getPdo();

        // Vérifier que la conversation existe et est active
        $stmt = $pdo->prepare("SELECT * FROM chat_conversations WHERE id = ?");
        $stmt->execute([$conversationId]);
        $conversation = $stmt->fetch();

        if (!$conversation) {
            jsonError(__('api.conversation_not_found'), 404);
        }

        // Insérer le message
        $stmt = $pdo->prepare("
            INSERT INTO chat_messages (conversation_id, sender_type, admin_id, message, message_type)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$conversationId, $senderType, $adminId, $message, $messageType]);
        $messageId = $pdo->lastInsertId();

        // Mettre à jour la conversation (pas pour les messages WebRTC signaling)
        if ($messageType === 'text') {
            $updateSql = "UPDATE chat_conversations SET last_message_at = NOW()";
            if ($senderType === 'customer') {
                $updateSql .= ", unread_count = unread_count + 1";
            }
            $updateSql .= " WHERE id = ?";
            $pdo->prepare($updateSql)->execute([$conversationId]);
        }

        // Récupérer le message créé
        $stmt = $pdo->prepare("
            SELECT m.*, a.username as admin_name
            FROM chat_messages m
            LEFT JOIN admins a ON m.admin_id = a.id
            WHERE m.id = ?
        ");
        $stmt->execute([$messageId]);
        $newMessage = $stmt->fetch();

        jsonSuccess(['message' => $newMessage]);
    }

    /**
     * PUT /api/chat/conversations/:id/read
     * Marquer tous les messages d'une conversation comme lus (pour admin)
     */
    public function markAsRead(array $params): void
    {
        $conversationId = (int)($params['id'] ?? 0);
        if ($conversationId <= 0) {
            jsonError(__('api.conversation_id_invalid'), 400);
        }

        $pdo = $this->db->getPdo();

        // Marquer les messages clients comme lus
        $stmt = $pdo->prepare("
            UPDATE chat_messages
            SET is_read = 1, read_at = NOW()
            WHERE conversation_id = ? AND sender_type = 'customer' AND is_read = 0
        ");
        $stmt->execute([$conversationId]);
        $updated = $stmt->rowCount();

        // Réinitialiser le compteur de non lus
        $pdo->prepare("UPDATE chat_conversations SET unread_count = 0 WHERE id = ?")->execute([$conversationId]);

        jsonSuccess(['messages_read' => $updated]);
    }

    /**
     * GET /api/chat/messages/poll
     * Polling pour les nouveaux messages (côté client)
     */
    public function pollMessages(): void
    {
        try {
            $phone = get('phone');
            $afterId = (int)(get('after_id') ?: 0);
            $adminId = get('admin_id') ? (int)get('admin_id') : null;

            if (empty($phone)) {
                jsonError(__('api.phone_required'), 400);
                return;
            }

            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);

            $pdo = $this->db->getPdo();

            // Trouver la conversation (filtrer par admin_id si fourni)
            if ($adminId !== null) {
                $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE phone = ? AND admin_id = ?");
                $stmt->execute([$cleanPhone, $adminId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM chat_conversations WHERE phone = ?");
                $stmt->execute([$cleanPhone]);
            }
            $conversation = $stmt->fetch();

            if (!$conversation) {
                jsonSuccess(['messages' => [], 'conversation_id' => null]);
                return;
            }

            // Filtrer par type si spécifié
            $typeFilter = get('type');

            // Récupérer les nouveaux messages (sans JOIN pour éviter les erreurs)
            if ($typeFilter) {
                $stmt = $pdo->prepare("
                    SELECT * FROM chat_messages
                    WHERE conversation_id = ? AND id > ? AND message_type = ?
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$conversation['id'], $afterId, $typeFilter]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT * FROM chat_messages
                    WHERE conversation_id = ? AND id > ?
                    ORDER BY created_at ASC
                ");
                $stmt->execute([$conversation['id'], $afterId]);
            }
            $messages = $stmt->fetchAll();

            // Marquer les messages admin comme lus côté client (seulement les messages texte)
            if (!empty($messages)) {
                $adminTextMessages = array_filter($messages, fn($m) => $m['sender_type'] === 'admin' && ($m['message_type'] ?? 'text') === 'text');
                if (!empty($adminTextMessages)) {
                    $ids = array_column($adminTextMessages, 'id');
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("
                        UPDATE chat_messages
                        SET is_read = 1, read_at = NOW()
                        WHERE id IN ($placeholders) AND is_read = 0
                    ");
                    $stmt->execute($ids);
                }
            }

            jsonSuccess([
                'messages' => $messages,
                'conversation_id' => $conversation['id']
            ]);
        } catch (Exception $e) {
            error_log('pollMessages error: ' . $e->getMessage());
            jsonError(__('api.error_polling') . ': ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // Widget Embeddable
    // ==========================================

    /**
     * GET /api/chat/widget/code
     * Récupérer la clé widget et le code d'intégration (authentifié)
     */
    public function getWidgetCode(): void
    {
        $adminId = $this->getAdminId();
        if ($adminId === null) {
            jsonError(__('auth.authentication_required'), 401);
            return;
        }

        $pdo = $this->db->getPdo();

        // Récupérer la clé existante
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'chat_widget_key' AND admin_id = ?");
        $stmt->execute([$adminId]);
        $key = $stmt->fetchColumn();

        // Si pas de clé, en générer une
        if (!$key) {
            $key = bin2hex(random_bytes(16));
            $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value, description, admin_id)
                VALUES ('chat_widget_key', ?, 'Clé unique du widget chat embeddable', ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
            ")->execute([$key, $adminId]);
        }

        // Construire l'URL de base
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '/web/api.php');
        $baseUrl = $protocol . '://' . $host . $scriptPath;

        $widgetUrl = $baseUrl . '/chat-widget.js';
        $embedCode = '<script src="' . $widgetUrl . '" data-widget-key="' . $key . '"></script>';

        jsonSuccess([
            'key' => $key,
            'embed_code' => $embedCode,
            'widget_url' => $widgetUrl,
            'api_url' => $baseUrl . '/api.php'
        ]);
    }

    /**
     * POST /api/chat/widget/key
     * Générer ou régénérer la clé widget (authentifié)
     */
    public function generateWidgetKey(): void
    {
        $adminId = $this->getAdminId();
        if ($adminId === null) {
            jsonError(__('auth.authentication_required'), 401);
            return;
        }

        $pdo = $this->db->getPdo();
        $key = bin2hex(random_bytes(16));

        $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, description, admin_id)
            VALUES ('chat_widget_key', ?, 'Clé unique du widget chat embeddable', ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ")->execute([$key, $adminId]);

        jsonSuccess([
            'key' => $key,
            'message' => __('api.widget_key_regenerated')
        ]);
    }

    /**
     * GET /api/chat/widget/config
     * Configuration publique du widget (pas d'auth, validé par clé)
     */
    public function getWidgetConfig(): void
    {
        $key = get('key');
        if (empty($key)) {
            jsonError(__('api.widget_key_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();

        // Valider la clé et récupérer l'admin_id
        $stmt = $pdo->prepare("SELECT admin_id FROM settings WHERE setting_key = 'chat_widget_key' AND setting_value = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if (!$row) {
            jsonError(__('api.widget_key_invalid'), 403);
            return;
        }

        $adminId = (int)$row['admin_id'];

        // Charger le nom de l'app
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_name' AND admin_id = ?");
        $stmt->execute([$adminId]);
        $appName = $stmt->fetchColumn() ?: 'Support';

        // Vérifier que le module chat est actif
        $stmt = $pdo->prepare("SELECT is_active FROM modules WHERE module_code = 'chat' AND admin_id = ?");
        $stmt->execute([$adminId]);
        $chatRow = $stmt->fetch();
        $chatEnabled = $chatRow ? (bool)$chatRow['is_active'] : true;

        // Charger la langue de l'admin
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'language' AND admin_id = ?");
        $stmt->execute([$adminId]);
        $lang = $stmt->fetchColumn() ?: 'fr';
        $langFile = __DIR__ . '/../../lang/' . $lang . '.php';
        $allTranslations = file_exists($langFile) ? require $langFile : [];

        // Extraire uniquement les clés widget.*
        $widgetTranslations = [];
        foreach ($allTranslations as $k => $v) {
            if (str_starts_with($k, 'widget.')) {
                $widgetTranslations[substr($k, 7)] = $v;
            }
        }

        jsonSuccess([
            'admin_id' => $adminId,
            'app_name' => $appName,
            'chat_enabled' => $chatEnabled,
            'lang' => $lang,
            'translations' => $widgetTranslations
        ]);
    }
}
