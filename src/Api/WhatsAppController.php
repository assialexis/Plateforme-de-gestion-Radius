<?php
/**
 * Controller API pour les notifications WhatsApp via Green API
 */

require_once __DIR__ . '/../Services/WhatsAppNotifier.php';

class WhatsAppController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private WhatsAppNotifier $notifier;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->notifier = new WhatsAppNotifier($db->getPdo());
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /api/whatsapp/config
     */
    public function getConfig(): void
    {
        $pdo = $this->db->getPdo();

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("SELECT * FROM whatsapp_config WHERE admin_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$adminId]);
            $config = $stmt->fetch();

            if ($config && $config['api_token_instance']) {
                $token = $config['api_token_instance'];
                $config['api_token_masked'] = substr($token, 0, 8) . '...' . substr($token, -4);
            }

            // Convertir is_enabled en boolean pour Alpine.js
            if ($config) {
                $config['is_enabled'] = (bool)$config['is_enabled'];
            }

            jsonSuccess($config ?: [
                'id_instance' => '',
                'api_token_instance' => '',
                'api_url' => 'https://api.green-api.com',
                'default_phone' => '',
                'country_code' => '229',
                'is_enabled' => false
            ]);
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/whatsapp/config
     */
    public function saveConfig(): void
    {
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);

        $idInstance = $data['id_instance'] ?? '';
        $apiTokenInstance = $data['api_token_instance'] ?? '';
        $apiUrl = $data['api_url'] ?? 'https://api.green-api.com';
        $defaultPhone = $data['default_phone'] ?? '';
        $countryCode = $data['country_code'] ?? '229';
        $isEnabled = (bool)($data['is_enabled'] ?? false);

        try {
            $adminId = $this->getAdminId();
            $stmt = $pdo->prepare("SELECT id FROM whatsapp_config WHERE admin_id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $existing = $stmt->fetch();

            if ($existing) {
                if (!empty($apiTokenInstance) && strpos($apiTokenInstance, '...') === false) {
                    $stmt = $pdo->prepare("
                        UPDATE whatsapp_config
                        SET id_instance = ?, api_token_instance = ?, api_url = ?,
                            default_phone = ?, country_code = ?, is_enabled = ?, updated_at = NOW()
                        WHERE id = ? AND admin_id = ?
                    ");
                    $stmt->execute([$idInstance, $apiTokenInstance, $apiUrl, $defaultPhone, $countryCode, $isEnabled, $existing['id'], $adminId]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE whatsapp_config
                        SET id_instance = ?, api_url = ?, default_phone = ?,
                            country_code = ?, is_enabled = ?, updated_at = NOW()
                        WHERE id = ? AND admin_id = ?
                    ");
                    $stmt->execute([$idInstance, $apiUrl, $defaultPhone, $countryCode, $isEnabled, $existing['id'], $adminId]);
                }
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO whatsapp_config (id_instance, api_token_instance, api_url, default_phone, country_code, is_enabled, admin_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$idInstance, $apiTokenInstance, $apiUrl, $defaultPhone, $countryCode, $isEnabled, $adminId]);
            }

            jsonSuccess(null, __('api.config_saved'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/whatsapp/test
     */
    public function testConnection(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $phone = $data['phone'] ?? null;

        $result = $this->notifier->testConnection();

        if (!$result['success']) {
            jsonError(__('api.whatsapp_connection_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 400);
            return;
        }

        $accountInfo = $result['account_info'];

        if ($phone) {
            $testMessage = "✅ *Test de connexion réussi!*\n\n" .
                "WhatsApp ID: " . ($accountInfo['wid'] ?? 'N/A') . "\n" .
                "Date: " . date('d/m/Y H:i:s') . "\n\n" .
                "_Ce message confirme que Green API est correctement configuré._";

            $sendResult = $this->notifier->sendMessage($phone, $testMessage);

            if (!$sendResult['success']) {
                jsonError(__('api.whatsapp_connection_ok_send_failed') . ': ' . ($sendResult['error'] ?? 'Unknown error'), 400);
                return;
            }
        }

        jsonSuccess([
            'account_info' => $accountInfo,
            'message_sent' => !empty($phone)
        ], __('api.connection_success'));
    }

    /**
     * GET /api/whatsapp/variables
     */
    public function getVariables(): void
    {
        $rawVariables = $this->notifier->getAvailableVariables();

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
    private function ensureWhatsAppTemplates(?int $adminId): void
    {
        if ($adminId === null) {
            return;
        }

        $pdo = $this->db->getPdo();

        // Vérifier si le provisioning a déjà été fait pour cet admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM settings WHERE setting_key = 'whatsapp_templates_provisioned' AND admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) {
            return;
        }

        // Marquer comme provisionné
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description, admin_id) VALUES ('whatsapp_templates_provisioned', '1', 'Auto-provisioning des templates WhatsApp effectué', ?)")->execute([$adminId]);

        // Vérifier si l'admin a déjà des templates (via migration/seed)
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM whatsapp_templates WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) {
            return;
        }

        $defaults = [
            ['Rappel 7 jours', 'Notification 7 jours avant expiration', 'expiration_warning', 7,
             "🔔 *Rappel d'expiration*\n\nBonjour {{customer_name}},\n\nVotre abonnement Internet expire dans *{{days_remaining}} jours* (le {{expiration_date}}).\n\n📋 *Détails:*\n• Compte: {{username}}\n• Forfait: {{profile_name}}\n• Prix: {{profile_price}} FCFA\n\n💳 Pensez à renouveler pour éviter toute interruption de service.\n\n_{{company_name}}_"],
            ['Rappel 3 jours', 'Notification 3 jours avant expiration', 'expiration_warning', 3,
             "⚠️ *Expiration imminente!*\n\nBonjour {{customer_name}},\n\nVotre abonnement expire dans *{{days_remaining}} jours* seulement!\n\n📅 Date d'expiration: {{expiration_date}}\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n\n💰 Montant à payer: *{{profile_price}} FCFA*\n\nContactez-nous rapidement pour renouveler.\n\n_{{company_name}}_"],
            ['Rappel 1 jour', 'Notification 1 jour avant expiration', 'expiration_warning', 1,
             "🚨 *URGENT - Expiration demain!*\n\nBonjour {{customer_name}},\n\nVotre abonnement expire *DEMAIN* ({{expiration_date}})!\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n💰 Renouvellement: *{{profile_price}} FCFA*\n\n⚡ Renouvelez aujourd'hui pour éviter la coupure!\n\n📞 Contact: {{support_phone}}\n\n_{{company_name}}_"],
            ['Jour d\'expiration', 'Notification le jour de l\'expiration', 'expiration_warning', 0,
             "❌ *Expiration aujourd'hui!*\n\nBonjour {{customer_name}},\n\nVotre abonnement Internet expire *AUJOURD'HUI*!\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n\n🔴 Votre connexion sera coupée si vous ne renouvelez pas.\n\n💳 Montant: *{{profile_price}} FCFA*\n📞 Contact: {{support_phone}}\n\n_{{company_name}}_"],
            ['Compte expiré', 'Notification après expiration', 'expired', -1,
             "🔴 *Compte expiré*\n\nBonjour {{customer_name}},\n\nVotre abonnement Internet a expiré le {{expiration_date}}.\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n\n🔄 Pour réactiver votre connexion:\n💰 Montant: *{{profile_price}} FCFA*\n📞 Contact: {{support_phone}}\n\n_{{company_name}}_"],
            ['Bienvenue', 'Message de bienvenue pour nouveau client', 'welcome', 0,
             "🎉 *Bienvenue chez {{company_name}}!*\n\nBonjour {{customer_name}},\n\nVotre compte Internet est maintenant actif!\n\n📋 *Vos informations:*\n• Identifiant: {{username}}\n• Mot de passe: {{password}}\n• Forfait: {{profile_name}}\n• Vitesse: {{download_speed}} / {{upload_speed}}\n• Valide jusqu'au: {{expiration_date}}\n\n📞 Support: {{support_phone}}\n\nMerci de votre confiance!"],
            ['Compte suspendu', 'Notification de suspension', 'suspended', 0,
             "⛔ *Compte suspendu*\n\nBonjour {{customer_name}},\n\nVotre compte Internet a été suspendu.\n\n👤 Compte: {{username}}\n📅 Date: {{current_date}}\n\nPour réactiver votre connexion, veuillez nous contacter.\n📞 Contact: {{support_phone}}\n\n_{{company_name}}_"],
            ['Compte réactivé', 'Notification de réactivation', 'reactivated', 0,
             "✅ *Compte réactivé*\n\nBonjour {{customer_name}},\n\nVotre compte Internet est de nouveau actif!\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n📅 Valide jusqu'au: {{expiration_date}}\n\nMerci pour votre paiement!\n\n_{{company_name}}_"],
            ['Facture créée', 'Notification de nouvelle facture', 'invoice_created', 0,
             "📄 *Nouvelle facture*\n\nBonjour {{customer_name}},\n\nUne nouvelle facture a été générée pour votre compte.\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n💰 Montant: *{{profile_price}} FCFA*\n📅 Date: {{current_date}}\n\nMerci de procéder au paiement dans les meilleurs délais.\n📞 Contact: {{support_phone}}\n\n_{{company_name}}_"],
            ['Paiement reçu', 'Confirmation de paiement', 'payment_received', 0,
             "✅ *Paiement reçu*\n\nBonjour {{customer_name}},\n\nNous confirmons la réception de votre paiement.\n\n👤 Compte: {{username}}\n📦 Forfait: {{profile_name}}\n💰 Montant: *{{profile_price}} FCFA*\n📅 Valide jusqu'au: {{expiration_date}}\n\nMerci pour votre paiement!\n\n_{{company_name}}_"],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO whatsapp_templates (name, description, event_type, days_before, message_template, is_active, send_time, admin_id)
            VALUES (?, ?, ?, ?, ?, 1, '09:00:00', ?)
        ");

        foreach ($defaults as $t) {
            $stmt->execute([$t[0], $t[1], $t[2], $t[3], $t[4], $adminId]);
        }
    }

    /**
     * GET /api/whatsapp/templates
     */
    public function getTemplates(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $this->ensureWhatsAppTemplates($adminId);

        try {
            $stmt = $pdo->prepare("
                SELECT *,
                    (SELECT COUNT(*) FROM whatsapp_notifications WHERE template_id = whatsapp_templates.id) as usage_count
                FROM whatsapp_templates
                WHERE admin_id = ?
                ORDER BY event_type, days_before DESC
            ");
            $stmt->execute([$adminId]);
            jsonSuccess($stmt->fetchAll());
        } catch (PDOException $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/whatsapp/templates/{id}
     */
    public function getTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("SELECT * FROM whatsapp_templates WHERE id = ? AND admin_id = ?");
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
     * POST /api/whatsapp/templates
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
                INSERT INTO whatsapp_templates
                (name, description, event_type, message_template, days_before, is_active, send_time, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['event_type'],
                $data['message_template'],
                (int)($data['days_before'] ?? 0),
                (bool)($data['is_active'] ?? true),
                $data['send_time'] ?? '09:00:00',
                $adminId,
            ]);

            jsonSuccess(['id' => $pdo->lastInsertId()], __('api.template_created'));
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonError(__('api.template_event_exists'), 400);
            } else {
                jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * PUT /api/whatsapp/templates/{id}
     */
    public function updateTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $data = json_decode(file_get_contents('php://input'), true);
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("SELECT id FROM whatsapp_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
            if (!$stmt->fetch()) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            $stmt = $pdo->prepare("
                UPDATE whatsapp_templates
                SET name = ?, description = ?, event_type = ?, message_template = ?,
                    days_before = ?, is_active = ?, send_time = ?, updated_at = NOW()
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
                $id,
                $adminId
            ]);

            jsonSuccess(null, __('api.template_updated'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/whatsapp/templates/{id}
     */
    public function deleteTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("DELETE FROM whatsapp_templates WHERE id = ? AND admin_id = ?");
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
     * POST /api/whatsapp/templates/{id}/toggle
     */
    public function toggleTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();

        try {
            $stmt = $pdo->prepare("UPDATE whatsapp_templates SET is_active = NOT is_active, updated_at = NOW() WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            $stmt = $pdo->prepare("SELECT is_active FROM whatsapp_templates WHERE id = ? AND admin_id = ?");
            $stmt->execute([$id, $adminId]);
            $template = $stmt->fetch();

            jsonSuccess(['is_active' => (bool)$template['is_active']], __('api.template_toggled'));
        } catch (PDOException $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/whatsapp/templates/{id}/preview
     */
    public function previewTemplate(array $params): void
    {
        $id = (int)$params['id'];
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = (int)($data['user_id'] ?? 0);

        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("SELECT message_template FROM whatsapp_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();

            if (!$template) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            if ($userId) {
                $userData = $this->notifier->prepareUserData($userId);
            } else {
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
     * POST /api/whatsapp/test-template
     */
    public function testTemplate(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $templateId = (int)($data['template_id'] ?? 0);
        $phone = $data['phone'] ?? null;
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $useDemoData = (bool)($data['use_demo_data'] ?? true);

        if (!$templateId) {
            jsonError(__('api.whatsapp_template_id_required'), 400);
            return;
        }
        if (!$phone) {
            jsonError(__('api.phone_required'), 400);
            return;
        }

        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("SELECT * FROM whatsapp_templates WHERE id = ?");
            $stmt->execute([$templateId]);
            $template = $stmt->fetch();

            if (!$template) {
                jsonError(__('api.template_not_found'), 404);
                return;
            }

            if ($userId && !$useDemoData) {
                $userData = $this->notifier->prepareUserData($userId);
                if (empty($userData)) {
                    jsonError(__('api.whatsapp_user_not_found'), 404);
                    return;
                }
            } else {
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

            $message = $this->notifier->processTemplate($template['message_template'], $userData);
            $message = "🧪 *MESSAGE TEST*\n\n" . $message;

            $result = $this->notifier->sendMessage($phone, $message);

            if ($result['success']) {
                jsonSuccess([
                    'message_id' => $result['message_id'] ?? null,
                    'phone' => $phone,
                ], __('api.whatsapp_test_message_sent'));
            } else {
                jsonError(__('api.whatsapp_send_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 500);
            }
        } catch (PDOException $e) {
            jsonError(__('api.whatsapp_send_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/whatsapp/send
     */
    public function sendNotification(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $templateId = (int)($data['template_id'] ?? 0);
        $userId = (int)($data['user_id'] ?? 0);
        $phone = $data['phone'] ?? null;
        $customMessage = $data['message'] ?? null;

        if ($customMessage && $phone) {
            $result = $this->notifier->sendMessage($phone, $customMessage);
        } elseif ($templateId && $userId) {
            $result = $this->notifier->sendTemplateNotification($templateId, $userId, $phone);
        } else {
            jsonError(__('api.whatsapp_params_required'), 400);
            return;
        }

        if ($result['success']) {
            jsonSuccess($result, __('api.notification_sent'));
        } else {
            jsonError(__('api.whatsapp_send_failed') . ': ' . ($result['error'] ?? 'Unknown error'), 500);
        }
    }

    /**
     * POST /api/whatsapp/send-bulk
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

        if (empty($userIds)) {
            $where = ["whatsapp_notifications = 1"];
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
                SELECT id, whatsapp_phone, customer_phone
                FROM pppoe_users
                WHERE $whereClause AND (whatsapp_phone IS NOT NULL OR customer_phone IS NOT NULL)
            ");
            $stmt->execute($params);
            $users = $stmt->fetchAll();
        } else {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT id, whatsapp_phone, customer_phone
                FROM pppoe_users
                WHERE id IN ($placeholders)
            ");
            $stmt->execute($userIds);
            $users = $stmt->fetchAll();
        }

        foreach ($users as $user) {
            $phone = $user['whatsapp_phone'] ?: $user['customer_phone'];
            if (!$phone) continue;

            $result = $this->notifier->sendTemplateNotification($templateId, $user['id'], $phone);

            if ($result['success']) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    'user_id' => $user['id'],
                    'error' => $result['error'] ?? 'Unknown error'
                ];
            }

            usleep(200000); // 200ms
        }

        jsonSuccess($results, "{$results['sent']} " . __('api.whatsapp_sent') . ", {$results['failed']} " . __('api.whatsapp_failed'));
    }

    /**
     * POST /api/whatsapp/process-expirations
     */
    public function processExpirations(): void
    {
        $result = $this->notifier->processExpirationNotifications();

        if ($result['success']) {
            jsonSuccess($result['results'], __('api.whatsapp_expirations_processed'));
        } else {
            jsonError($result['error'] ?? __('api.whatsapp_processing_failed'), 500);
        }
    }

    /**
     * GET /api/whatsapp/history
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
            $where[] = "wn.admin_id = ?";
            $params[] = $adminId;
        }

        if ($status) {
            $where[] = "wn.status = ?";
            $params[] = $status;
        }
        if ($userId) {
            $where[] = "wn.pppoe_user_id = ?";
            $params[] = $userId;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM whatsapp_notifications wn $whereClause");
            $stmt->execute($params);
            $total = $stmt->fetch()['total'];

            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare("
                SELECT wn.*,
                    wt.name as template_name,
                    pu.customer_name,
                    pu.username
                FROM whatsapp_notifications wn
                LEFT JOIN whatsapp_templates wt ON wn.template_id = wt.id
                LEFT JOIN pppoe_users pu ON wn.pppoe_user_id = pu.id
                $whereClause
                ORDER BY wn.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);

            jsonSuccess([
                'data' => $stmt->fetchAll(),
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
     * GET /api/whatsapp/stats
     */
    public function getStats(): void
    {
        $pdo = $this->db->getPdo();
        $adminId = $this->getAdminId();
        $adminFilter = $adminId !== null ? "WHERE admin_id = ?" : "";
        $adminFilterAnd = $adminId !== null ? "AND wn.admin_id = ?" : "";
        $adminParams = $adminId !== null ? [$adminId] : [];

        try {
            $stats = [];

            $stmt = $pdo->prepare("
                SELECT status, COUNT(*) as count
                FROM whatsapp_notifications
                $adminFilter
                GROUP BY status
            ");
            $stmt->execute($adminParams);
            $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $params = $adminId !== null ? [$adminId] : [];
            $whereCreated = $adminId !== null ? "WHERE admin_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" : "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as count, status
                FROM whatsapp_notifications
                $whereCreated
                GROUP BY DATE(created_at), status
                ORDER BY date
            ");
            $stmt->execute($params);
            $stats['last_7_days'] = $stmt->fetchAll();

            $whereTop = $adminId !== null ? "WHERE wn.admin_id = ?" : "";
            $stmt = $pdo->prepare("
                SELECT wt.name, COUNT(wn.id) as count
                FROM whatsapp_notifications wn
                JOIN whatsapp_templates wt ON wn.template_id = wt.id
                $whereTop
                GROUP BY wn.template_id
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
                FROM whatsapp_notifications
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
