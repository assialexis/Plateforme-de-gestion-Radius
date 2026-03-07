<?php
/**
 * Controller API pour les notifications Telegram
 */

require_once __DIR__ . '/../Services/TelegramNotifier.php';

class TelegramController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private TelegramNotifier $notifier;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->notifier = new TelegramNotifier($db->getPdo());
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /api/telegram/config
     * Obtenir la configuration Telegram
     */
    public function getConfig(): void
    {
        $pdo = $this->db->getPdo();

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("SELECT * FROM telegram_config WHERE admin_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$adminId]);
            $config = $stmt->fetch();

            // Masquer partiellement le token
            if ($config && $config['bot_token']) {
                $token = $config['bot_token'];
                $config['bot_token_masked'] = substr($token, 0, 10) . '...' . substr($token, -5);
            }

            jsonSuccess($config ?: [
                'bot_token' => '',
                'default_chat_id' => '',
                'is_enabled' => false
            ]);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/config
     * Sauvegarder la configuration Telegram
     */
    public function saveConfig(): void
    {
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        $botToken = $data['bot_token'] ?? '';
        $defaultChatId = $data['default_chat_id'] ?? '';
        $isEnabled = (bool)($data['is_enabled'] ?? false);

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("SELECT id FROM telegram_config WHERE admin_id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $existing = $stmt->fetch();

            if ($existing) {
                if (!empty($botToken) && strpos($botToken, '...') === false) {
                    $stmt = $pdo->prepare("
                        UPDATE telegram_config
                        SET bot_token = ?, default_chat_id = ?, is_enabled = ?, updated_at = NOW()
                        WHERE id = ? AND admin_id = ?
                    ");
                    $stmt->execute([$botToken, $defaultChatId, $isEnabled, $existing['id'], $adminId]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE telegram_config
                        SET default_chat_id = ?, is_enabled = ?, updated_at = NOW()
                        WHERE id = ? AND admin_id = ?
                    ");
                    $stmt->execute([$defaultChatId, $isEnabled, $existing['id'], $adminId]);
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO telegram_config (bot_token, default_chat_id, is_enabled, admin_id)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$botToken, $defaultChatId, $isEnabled, $adminId]);
            }

            jsonSuccess(null, __('api.config_saved'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/test
     * Tester la connexion au bot et envoyer un message test
     */
    public function testConnection(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $chatId = $data['chat_id'] ?? null;

        // Tester la connexion au bot
        $result = $this->notifier->testConnection();

        if (!$result['success']) {
            jsonError(__('api.bot_connection_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 400);
            return;
        }

        $botInfo = $result['bot_info'];

        // Si un chat_id est fourni, envoyer un message test
        if ($chatId) {
            $testMessage = "Test de connexion reussi\n\n" .
                "Bot: " . $botInfo['username'] . "\n" .
                "Date: " . date('d/m/Y H:i:s') . "\n\n" .
                "Ce message confirme que le bot est correctement configure.";

            $sendResult = $this->notifier->sendMessage($chatId, $testMessage, 'HTML');

            if (!$sendResult['success']) {
                jsonError(__('api.send_message_failed') . ': ' . ($sendResult['error'] ?? 'Unknown error'), 400);
                return;
            }
        }

        jsonSuccess([
            'bot_info' => $botInfo,
            'message_sent' => !empty($chatId)
        ], __('api.connection_success'));
    }

    /**
     * GET /api/telegram/variables
     * Obtenir la liste des variables disponibles pour les templates
     */
    public function getVariables(): void
    {
        $rawVariables = $this->notifier->getAvailableVariables();

        // Transformer en array avec variable et description pour l'UI
        $variables = [];
        foreach ($rawVariables as $variable => $description) {
            $variables[] = [
                'variable' => $variable,
                'description' => $description,
                'placeholder' => '{{' . $variable . '}}'
            ];
        }

        jsonSuccess($variables);
    }

    /**
     * S'assurer que les templates par défaut existent pour cet admin
     */
    private function ensureTelegramTemplates(?int $adminId): void
    {
        if ($adminId === null) {
            return;
        }

        $pdo = $this->db->getPdo();

        // Vérifier si le provisioning a déjà été fait pour cet admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM settings WHERE setting_key = 'telegram_templates_provisioned' AND admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) {
            return;
        }

        // Marquer comme provisionné
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description, admin_id) VALUES ('telegram_templates_provisioned', '1', 'Auto-provisioning des templates Telegram effectué', ?)")->execute([$adminId]);

        // Vérifier si l'admin a déjà des templates (via migration/seed)
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM telegram_templates WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) {
            return;
        }

        $defaults = [
            ['Bienvenue', 'Message de bienvenue pour nouveau client', 'welcome', 0,
             "🎉 *Bienvenue chez nous!*\n\nBonjour {{customer_name}},\n\nVotre compte Internet est maintenant actif!\n\n📋 *Vos informations:*\n• Identifiant: `{{username}}`\n• Mot de passe: `{{password}}`\n• Forfait: {{profile_name}}\n• Vitesse: {{download_speed}} / {{upload_speed}}\n• Valide jusqu'au: {{expiration_date}}\n\n📞 Support: {{support_phone}}\n\nMerci de votre confiance!\n\n_Message automatique - NAS System_"],
            ['Compte suspendu', 'Notification de suspension', 'suspended', 0,
             "⛔ *Compte suspendu*\n\nBonjour {{customer_name}},\n\nVotre compte Internet a été suspendu.\n\n👤 Compte: `{{username}}`\n📅 Date: {{current_date}}\n\nPour réactiver votre connexion, veuillez nous contacter.\n📞 Contact: {{support_phone}}\n\n_Message automatique - NAS System_"],
            ['Compte réactivé', 'Notification de réactivation', 'reactivated', 0,
             "✅ *Compte réactivé*\n\nBonjour {{customer_name}},\n\nVotre compte Internet est de nouveau actif!\n\n👤 Compte: `{{username}}`\n📦 Forfait: {{profile_name}}\n📅 Valide jusqu'au: {{expiration_date}}\n\nMerci pour votre paiement!\n\n_Message automatique - NAS System_"],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO telegram_templates (name, description, event_type, days_before, message_template, is_active, send_time, admin_id)
            VALUES (?, ?, ?, ?, ?, 1, '09:00:00', ?)
        ");

        foreach ($defaults as $t) {
            $stmt->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $adminId]);
        }
    }

    /**
     * GET /api/telegram/templates
     * Lister tous les templates
     */
    public function getTemplates(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $this->ensureTelegramTemplates($adminId);

        try {
            $stmt = $pdo->prepare("
                SELECT *,
                    (SELECT COUNT(*) FROM telegram_notifications WHERE template_id = telegram_templates.id) as usage_count
                FROM telegram_templates
                WHERE admin_id = ?
                ORDER BY event_type, days_before DESC
            ");
            $stmt->execute([$adminId]);
            $templates = $stmt->fetchAll();

            jsonSuccess($templates);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/telegram/templates/{id}
     * Obtenir un template spécifique
     */
    public function getTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("SELECT * FROM telegram_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
            $template = $stmt->fetch();

            if (!$template) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            jsonSuccess($template);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/templates
     * Créer un nouveau template
     */
    public function createTemplate(): void
    {
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        $required = ['name', 'event_type', 'message_template'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonError(__('api.field_required', ['field' => $field]), 400);
                return;
            }
        }

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("
                INSERT INTO telegram_templates
                (name, description, event_type, message_template, days_before, is_active, send_time, whatsapp_button, whatsapp_button_text, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['event_type'],
                $data['message_template'],
                (int)($data['days_before'] ?? 0),
                (bool)($data['is_active'] ?? true),
                $data['send_time'] ?? '09:00:00',
                (bool)($data['whatsapp_button'] ?? false),
                $data['whatsapp_button_text'] ?? '📱 Envoyer sur WhatsApp',
                $adminId
            ]);

            $id = $pdo->lastInsertId();

            jsonSuccess(['id' => $id], __('api.template_created'));
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonError(__('api.template_event_exists'), 400);
            } else {
                jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * PUT /api/telegram/templates/{id}
     * Modifier un template
     */
    public function updateTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("SELECT id FROM telegram_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
            if (!$stmt->fetch()) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            $stmt = $pdo->prepare("
                UPDATE telegram_templates
                SET name = ?, description = ?, event_type = ?, message_template = ?,
                    days_before = ?, is_active = ?, send_time = ?,
                    whatsapp_button = ?, whatsapp_button_text = ?, updated_at = NOW()
                WHERE id = ? AND admin_id = ?
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['event_type'],
                $data['message_template'],
                (int)($data['days_before'] ?? 0),
                (bool)($data['is_active'] ?? true),
                $data['send_time'] ?? '09:00:00',
                (bool)($data['whatsapp_button'] ?? false),
                $data['whatsapp_button_text'] ?? '📱 Envoyer sur WhatsApp',
                $id,
                $adminId
            ]);

            jsonSuccess(null, __('api.template_updated'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/telegram/templates/{id}
     * Supprimer un template
     */
    public function deleteTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("DELETE FROM telegram_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            jsonSuccess(null, __('api.template_deleted'));
        } catch (PDOException $e) {
            jsonError(__('api.error_deleting') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/templates/{id}/toggle
     * Activer/désactiver un template
     */
    public function toggleTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("
                UPDATE telegram_templates
                SET is_active = NOT is_active, updated_at = NOW()
                WHERE id = ? AND admin_id = ?
            ");
            $stmt->execute([$id, $adminId]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            $stmt = $pdo->prepare("SELECT is_active FROM telegram_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
            $template = $stmt->fetch();

            jsonSuccess(['is_active' => (bool)$template['is_active']], __('api.template_toggled'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/templates/{id}/preview
     * Prévisualiser un template avec les données d'un utilisateur
     */
    public function previewTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);

        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("SELECT message_template FROM telegram_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            if ($userId) {
                $userData = $this->notifier->prepareUserData($userId);
            } else {
                // Données de démonstration
                $userData = [
                    'customer_name' => 'Jean Dupont',
                    'customer_phone' => '99 00 00 00',
                    'customer_email' => 'jean@example.com',
                    'customer_address' => '123 Rue Exemple',
                    'username' => 'DEMO12345',
                    'password' => 'demo123',
                    'profile_name' => 'Gold 10Mbps',
                    'profile_price' => '15 000',
                    'download_speed' => '10 Mbps',
                    'upload_speed' => '5 Mbps',
                    'expiration_date' => date('d/m/Y', strtotime('+7 days')),
                    'days_remaining' => '7',
                    'days_expired' => '0',
                    'current_date' => date('d/m/Y'),
                    'current_time' => date('H:i'),
                    'zone_name' => 'Zone Centre',
                    'nas_name' => 'NAS-01',
                    'data_used' => '5.2 Go',
                    'data_limit' => 'Illimité',
                    'balance' => '0',
                    'support_phone' => '99 00 00 00',
                    'company_name' => 'Mon ISP',
                ];
            }

            $preview = $this->notifier->processTemplate($template['message_template'], $userData);

            jsonSuccess([
                'preview' => $preview,
                'variables_used' => $userData
            ]);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/test-template
     * Envoyer un message test avec un template
     */
    public function testTemplate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $templateId = (int)($data['template_id'] ?? 0);
        $chatId = $data['chat_id'] ?? null;
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $useDemoData = (bool)($data['use_demo_data'] ?? true);

        if (!$templateId) {
            jsonError(__('api.whatsapp_template_id_required'), 400);
            return;
        }

        if (!$chatId) {
            jsonError(__('api.telegram_chat_id_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();

        try {
            // Charger le template
            $stmt = $pdo->prepare("SELECT * FROM telegram_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();

            if (!$template) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            // Preparer les donnees
            if ($userId && !$useDemoData) {
                $userData = $this->notifier->prepareUserData($userId);
                if (empty($userData)) {
                    jsonError(__('api.telegram_user_not_found'), 404);
                    return;
                }
            } else {
                // Donnees de demonstration
                $userData = [
                    'customer_name' => 'Jean Dupont (TEST)',
                    'customer_phone' => '99 00 11 22',
                    'customer_email' => 'jean.dupont@example.com',
                    'customer_address' => '123 Rue du Commerce, Cotonou',
                    'username' => 'TEST-12345',
                    'password' => 'demo123',
                    'profile_name' => 'Fibre Gold 20Mbps',
                    'profile_price' => '25 000',
                    'download_speed' => '20 Mbps',
                    'upload_speed' => '10 Mbps',
                    'expiration_date' => date('d/m/Y', strtotime('+7 days')),
                    'days_remaining' => '7',
                    'days_expired' => '0',
                    'current_date' => date('d/m/Y'),
                    'current_time' => date('H:i'),
                    'zone_name' => 'Zone Centre',
                    'nas_name' => 'NAS-Principal',
                    'data_used' => '45.2 Go',
                    'data_limit' => '100 Go',
                    'balance' => '5 000',
                    'support_phone' => '97 00 00 00',
                    'company_name' => 'MonISP Telecom',
                ];
            }

            // Traiter le template
            $message = $this->notifier->processTemplate($template['message_template'], $userData);

            // Ajouter un marqueur de test
            $message = "🧪 *MESSAGE TEST*\n\n" . $message;

            // Preparer le bouton WhatsApp si active
            $inlineKeyboard = null;
            if (!empty($template['whatsapp_button'])) {
                $phone = $userData['customer_phone'] ?? '';
                if ($phone) {
                    // Creer une version texte brut pour WhatsApp
                    $whatsappMessage = preg_replace('/\*([^*]+)\*/', '$1', $message);
                    $whatsappMessage = preg_replace('/_([^_]+)_/', '$1', $whatsappMessage);
                    $whatsappMessage = preg_replace('/`([^`]+)`/', '$1', $whatsappMessage);

                    $buttonText = $template['whatsapp_button_text'] ?? '📱 Envoyer sur WhatsApp';
                    $inlineKeyboard = $this->notifier->createWhatsAppButton($phone, $whatsappMessage, $buttonText);
                }
            }

            // Envoyer le message
            $result = $this->notifier->sendMessage($chatId, $message, 'Markdown', $inlineKeyboard);

            if ($result['success']) {
                jsonSuccess([
                    'message_id' => $result['message_id'] ?? null,
                    'chat_id' => $chatId,
                    'has_whatsapp_button' => !empty($inlineKeyboard)
                ], __('api.telegram_test_message_sent'));
            } else {
                jsonError(__('api.telegram_send_test_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 500);
            }
        } catch (PDOException $e) {
            jsonError(__('api.telegram_send_test_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/telegram/recipients
     * Lister les destinataires
     */
    public function getRecipients(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("SELECT * FROM telegram_recipients WHERE admin_id = ? ORDER BY name");
            $stmt->execute([$adminId]);
            $recipients = $stmt->fetchAll();

            jsonSuccess($recipients);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/recipients
     * Ajouter un destinataire
     */
    public function createRecipient(): void
    {
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['name']) || empty($data['chat_id'])) {
            jsonError(__('api.name_chatid_required'), 400);
            return;
        }

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("
                INSERT INTO telegram_recipients
                (user_id, chat_id, name, role, receive_expiration_alerts, receive_payment_alerts, receive_system_alerts, is_active, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['user_id'] ?? null,
                $data['chat_id'],
                $data['name'],
                $data['role'] ?? 'custom',
                (bool)($data['receive_expiration_alerts'] ?? true),
                (bool)($data['receive_payment_alerts'] ?? true),
                (bool)($data['receive_system_alerts'] ?? false),
                (bool)($data['is_active'] ?? true),
                $adminId
            ]);

            $id = $pdo->lastInsertId();

            jsonSuccess(['id' => $id], __('api.recipient_added'));
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonError(__('api.recipient_chat_id_exists'), 400);
            } else {
                jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * PUT /api/telegram/recipients/{id}
     * Modifier un destinataire
     */
    public function updateRecipient(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("
                UPDATE telegram_recipients
                SET chat_id = ?, name = ?, role = ?,
                    receive_expiration_alerts = ?, receive_payment_alerts = ?,
                    receive_system_alerts = ?, is_active = ?, updated_at = NOW()
                WHERE id = ? AND admin_id = ?
            ");
            $stmt->execute([
                $data['chat_id'],
                $data['name'],
                $data['role'] ?? 'custom',
                (bool)($data['receive_expiration_alerts'] ?? true),
                (bool)($data['receive_payment_alerts'] ?? true),
                (bool)($data['receive_system_alerts'] ?? false),
                (bool)($data['is_active'] ?? true),
                $id,
                $adminId
            ]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('api.recipient_not_found'), 404);
                return;
            }

            jsonSuccess(null, __('api.recipient_updated'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/telegram/recipients/{id}
     * Supprimer un destinataire
     */
    public function deleteRecipient(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("DELETE FROM telegram_recipients WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('api.recipient_not_found'), 404);
                return;
            }

            jsonSuccess(null, __('api.recipient_deleted'));
        } catch (PDOException $e) {
            jsonError(__('api.error_deleting') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/telegram/send
     * Envoyer une notification manuelle
     */
    public function sendNotification(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $templateId = (int)($data['template_id'] ?? 0);
        $userId = (int)($data['user_id'] ?? 0);
        $chatId = $data['chat_id'] ?? null;
        $customMessage = $data['message'] ?? null;

        if ($customMessage && $chatId) {
            // Message personnalisé
            $result = $this->notifier->sendMessage($chatId, $customMessage);
        } elseif ($templateId && $userId) {
            // Template avec utilisateur
            $result = $this->notifier->sendTemplateNotification($templateId, $userId, $chatId);
        } else {
            jsonError(__('api.telegram_params_required'), 400);
            return;
        }

        if ($result['success']) {
            jsonSuccess($result, __('api.notification_sent'));
        } else {
            jsonError(__('api.telegram_send_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 500);
        }
    }

    /**
     * POST /api/telegram/send-bulk
     * Envoyer des notifications en masse
     */
    public function sendBulkNotification(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $templateId = (int)($data['template_id'] ?? 0);
        $userIds = $data['user_ids'] ?? [];
        $filters = $data['filters'] ?? [];

        if (!$templateId) {
            jsonError(__('api.whatsapp_template_id_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();
        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        // Si pas d'IDs spécifiques, utiliser les filtres
        if (empty($userIds)) {
            $where = ["telegram_notifications = 1"];
            $params = [];

            if (!empty($filters['status'])) {
                $where[] = "status = ?";
                $params[] = $filters['status'];
            }

            if (!empty($filters['profile_id'])) {
                $where[] = "profile_id = ?";
                $params[] = $filters['profile_id'];
            }

            if (!empty($filters['expiring_in_days'])) {
                $where[] = "DATE(valid_until) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)";
                $params[] = (int)$filters['expiring_in_days'];
            }

            $whereClause = implode(' AND ', $where);
            $stmt = $pdo->prepare("
                SELECT id, telegram_chat_id
                FROM pppoe_users
                WHERE $whereClause AND telegram_chat_id IS NOT NULL
            ");
            $stmt->execute($params);
            $users = $stmt->fetchAll();
        } else {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT id, telegram_chat_id
                FROM pppoe_users
                WHERE id IN ($placeholders) AND telegram_chat_id IS NOT NULL
            ");
            $stmt->execute($userIds);
            $users = $stmt->fetchAll();
        }

        foreach ($users as $user) {
            $result = $this->notifier->sendTemplateNotification($templateId, $user['id'], $user['telegram_chat_id']);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user['id'],
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }

            // Pause pour éviter le rate limiting
            usleep(100000);
        }

        jsonSuccess($results, "{$results['sent']} " . __('api.whatsapp_sent') . ", {$results['failed']} " . __('api.whatsapp_failed'));
    }

    /**
     * POST /api/telegram/process-expirations
     * Traiter les notifications d'expiration programmées
     */
    public function processExpirations(): void
    {
        $result = $this->notifier->processExpirationNotifications();

        if ($result['success']) {
            jsonSuccess($result['results'], __('api.telegram_expirations_processed'));
        } else {
            jsonError($result['error'] ?? __('api.telegram_processing_failed'), 500);
        }
    }

    /**
     * GET /api/telegram/history
     * Historique des notifications
     */
    public function getHistory(): void
    {
        $pdo = $this->db->getPdo();
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? null;
        $userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $adminId = $this->getAdminId();

        $where = [];
        $params = [];

        if ($adminId !== null) {
            $where[] = "tn.admin_id = ?";
            $params[] = $adminId;
        }

        if ($status) {
            $where[] = "tn.status = ?";
            $params[] = $status;
        }

        if ($userId) {
            $where[] = "tn.pppoe_user_id = ?";
            $params[] = $userId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            // Count total
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM telegram_notifications tn $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            // Get records
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare("
                SELECT tn.*,
                    tt.name as template_name,
                    pu.customer_name,
                    pu.username
                FROM telegram_notifications tn
                LEFT JOIN telegram_templates tt ON tn.template_id = tt.id
                LEFT JOIN pppoe_users pu ON tn.pppoe_user_id = pu.id
                $whereClause
                ORDER BY tn.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            $notifications = $stmt->fetchAll();

            jsonSuccess([
                'data' => $notifications,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/telegram/stats
     * Statistiques des notifications
     */
    public function getStats(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? "WHERE admin_id = ?" : "";
        $adminParams = $adminId !== null ? [$adminId] : [];

        try {
            $stats = [];

            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count
                FROM telegram_notifications
                $adminFilter
                GROUP BY status
            ");
            $stmt->execute($adminParams);
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $whereCreated = $adminId !== null ? "WHERE admin_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" : "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as count, status
                FROM telegram_notifications
                $whereCreated
                GROUP BY DATE(created_at), status
                ORDER BY date
            ");
            $stmt->execute($adminParams);
            $stats['last_7_days'] = $stmt->fetchAll();

            $whereTop = $adminId !== null ? "WHERE tn.admin_id = ?" : "";
            $stmt = $pdo->prepare("
                SELECT tt.name, COUNT(tn.id) as count
                FROM telegram_notifications tn
                JOIN telegram_templates tt ON tn.template_id = tt.id
                $whereTop
                GROUP BY tn.template_id
                ORDER BY count DESC
                LIMIT 5
            ");
            $stmt->execute($adminParams);
            $stats['top_templates'] = $stmt->fetchAll();

            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
                FROM telegram_notifications
                $adminFilter
            ");
            $stmt->execute($adminParams);
            $totals = $stmt->fetch();
            $stats['success_rate'] = $totals['total'] > 0
                ? round(($totals['sent'] / $totals['total']) * 100, 2)
                : 0;

            jsonSuccess($stats);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }
}
