<?php

require_once __DIR__ . '/../Services/WhatsAppNotifier.php';
require_once __DIR__ . '/../Services/SmsService.php';

class PppoeReminderController
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
    // RULES CRUD
    // ==========================================

    /**
     * GET /pppoe-reminders/rules
     */
    public function getRules(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        // Auto-provision defaults for new admins
        $this->ensureRulesProvisioned($adminId);

        $stmt = $pdo->prepare("SELECT * FROM pppoe_reminder_rules WHERE admin_id = ? ORDER BY days_before DESC");
        $stmt->execute([$adminId]);
        $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        jsonSuccess($rules);
    }

    /**
     * POST /pppoe-reminders/rules
     */
    public function createRule(): void
    {
        $adminId = $this->getAdminId();
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        if (empty($data['name'])) {
            jsonError(__('pppoe_reminders.name_required') ?? 'Le nom est requis', 400);
            return;
        }
        if (!isset($data['days_before'])) {
            jsonError(__('pppoe_reminders.days_required') ?? 'Les jours sont requis', 400);
            return;
        }
        if (empty($data['channel']) || !in_array($data['channel'], ['whatsapp', 'sms'])) {
            jsonError(__('pppoe_reminders.invalid_channel') ?? 'Canal invalide', 400);
            return;
        }
        if (empty($data['message_template'])) {
            jsonError(__('pppoe_reminders.template_required') ?? 'Le template est requis', 400);
            return;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO pppoe_reminder_rules (admin_id, name, days_before, channel, message_template, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $adminId,
                $data['name'],
                (int)$data['days_before'],
                $data['channel'],
                $data['message_template'],
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);

            jsonSuccess(['id' => (int)$pdo->lastInsertId()], __('pppoe_reminders.rule_created') ?? 'Règle créée');
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonError(__('pppoe_reminders.rule_exists') ?? 'Une règle existe déjà pour ce timing et ce canal', 409);
            } else {
                jsonError($e->getMessage(), 500);
            }
        }
    }

    /**
     * PUT /pppoe-reminders/rules/{id}
     */
    public function updateRule(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $data = getJsonBody();
        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare("
                UPDATE pppoe_reminder_rules
                SET name = ?, days_before = ?, channel = ?, message_template = ?, is_active = ?
                WHERE id = ? AND admin_id = ?
            ");
            $stmt->execute([
                $data['name'],
                (int)$data['days_before'],
                $data['channel'],
                $data['message_template'],
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $id,
                $adminId
            ]);

            if ($stmt->rowCount() === 0) {
                jsonError(__('pppoe_reminders.rule_not_found') ?? 'Règle non trouvée', 404);
                return;
            }

            jsonSuccess(null, __('pppoe_reminders.rule_updated') ?? 'Règle mise à jour');
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonError(__('pppoe_reminders.rule_exists') ?? 'Une règle existe déjà pour ce timing et ce canal', 409);
            } else {
                jsonError($e->getMessage(), 500);
            }
        }
    }

    /**
     * DELETE /pppoe-reminders/rules/{id}
     */
    public function deleteRule(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("DELETE FROM pppoe_reminder_rules WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);

        if ($stmt->rowCount() === 0) {
            jsonError(__('pppoe_reminders.rule_not_found') ?? 'Règle non trouvée', 404);
            return;
        }

        jsonSuccess(null, __('pppoe_reminders.rule_deleted') ?? 'Règle supprimée');
    }

    /**
     * POST /pppoe-reminders/rules/{id}/toggle
     */
    public function toggleRule(array $params): void
    {
        $id = (int)$params['id'];
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("
            UPDATE pppoe_reminder_rules SET is_active = NOT is_active
            WHERE id = ? AND admin_id = ?
        ");
        $stmt->execute([$id, $adminId]);

        if ($stmt->rowCount() === 0) {
            jsonError(__('pppoe_reminders.rule_not_found') ?? 'Règle non trouvée', 404);
            return;
        }

        $stmt = $pdo->prepare("SELECT is_active FROM pppoe_reminder_rules WHERE id = ?");
        $stmt->execute([$id]);
        $isActive = (bool)$stmt->fetchColumn();

        jsonSuccess(['is_active' => $isActive]);
    }

    // ==========================================
    // PROCESSING
    // ==========================================

    /**
     * POST /pppoe-reminders/process
     */
    public function processReminders(): void
    {
        $adminId = $this->getAdminId();
        $results = self::processForAdmin($this->db->getPdo(), $adminId);
        jsonSuccess($results, __('pppoe_reminders.process_done') ?? 'Traitement terminé');
    }

    /**
     * Core processing logic - used by both controller and cron
     */
    public static function processForAdmin(\PDO $pdo, int $adminId): array
    {
        $results = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0];

        // Load active rules
        $stmt = $pdo->prepare("SELECT * FROM pppoe_reminder_rules WHERE admin_id = ? AND is_active = 1");
        $stmt->execute([$adminId]);
        $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($rules)) {
            return $results;
        }

        // Load settings for template variables
        $settings = self::loadSettings($pdo, $adminId);

        // Initialize notifiers lazily
        $whatsappNotifier = null;
        $smsService = null;
        $smsGatewayId = null;

        foreach ($rules as $rule) {
            $daysBefore = (int)$rule['days_before'];

            // Find eligible users - use MySQL CURDATE() for date calculations
            // to avoid PHP/MySQL timezone mismatch
            $userSql = "
                SELECT pu.id, pu.customer_name, pu.customer_phone, pu.whatsapp_phone,
                       pu.username, pu.password, pu.valid_until, pu.whatsapp_notifications,
                       pu.customer_email, pu.customer_address,
                       pp.name as profile_name, pp.price as profile_price,
                       pp.download_speed, pp.upload_speed, pp.data_limit,
                       z.name as zone_name,
                       (SELECT n.shortname FROM pppoe_user_nas pun JOIN nas n ON pun.nas_id = n.id WHERE pun.pppoe_user_id = pu.id LIMIT 1) as nas_name
                FROM pppoe_users pu
                LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
                LEFT JOIN zones z ON pu.zone_id = z.id
                WHERE pu.admin_id = ?
                AND DATE(pu.valid_until) = DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND pu.status IN ('active', 'expired')
                AND NOT EXISTS (
                    SELECT 1 FROM pppoe_reminder_log prl
                    WHERE prl.admin_id = ?
                    AND prl.rule_id = ?
                    AND prl.pppoe_user_id = pu.id
                    AND prl.notification_date = CURDATE()
                )
            ";
            $userStmt = $pdo->prepare($userSql);
            $userStmt->execute([$adminId, $daysBefore, $adminId, $rule['id']]);
            $users = $userStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($users as $user) {
                $results['processed']++;

                // Determine phone
                $phone = null;
                if ($rule['channel'] === 'whatsapp') {
                    if (empty($user['whatsapp_notifications'])) {
                        $results['skipped']++;
                        continue;
                    }
                    $phone = $user['whatsapp_phone'] ?: $user['customer_phone'];
                } else {
                    $phone = $user['customer_phone'];
                }

                if (empty($phone)) {
                    $results['skipped']++;
                    continue;
                }

                // Prepare template data
                $templateData = self::prepareTemplateData($user, $settings);

                // Process template
                $message = self::processTemplate($rule['message_template'], $templateData);

                // Send via appropriate channel
                $sendResult = ['success' => false, 'error' => 'Unknown channel'];

                if ($rule['channel'] === 'whatsapp') {
                    if (!$whatsappNotifier) {
                        $whatsappNotifier = new WhatsAppNotifier($pdo);
                    }
                    $formattedPhone = $whatsappNotifier->formatPhone($phone);
                    $sendResult = $whatsappNotifier->sendMessage($formattedPhone, $message);
                } elseif ($rule['channel'] === 'sms') {
                    if (!$smsService) {
                        $smsService = new SmsService($pdo);
                        // Find first active SMS gateway for this admin
                        $gwStmt = $pdo->prepare("SELECT id FROM sms_gateways WHERE admin_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1");
                        $gwStmt->execute([$adminId]);
                        $smsGatewayId = $gwStmt->fetchColumn();
                    }
                    if ($smsGatewayId) {
                        $sendResult = $smsService->sendSms((int)$smsGatewayId, $phone, $message);
                    } else {
                        $sendResult = ['success' => false, 'error' => 'Aucune passerelle SMS active'];
                    }
                }

                // Log result
                $status = $sendResult['success'] ? 'sent' : 'failed';
                $errorMsg = $sendResult['success'] ? null : ($sendResult['error'] ?? 'Erreur inconnue');

                try {
                    $logStmt = $pdo->prepare("
                        INSERT INTO pppoe_reminder_log
                        (admin_id, rule_id, pppoe_user_id, channel, phone, message, status, error_message, notification_date)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
                    ");
                    $logStmt->execute([
                        $adminId, $rule['id'], $user['id'], $rule['channel'],
                        $phone, $message, $status, $errorMsg
                    ]);
                } catch (\PDOException $e) {
                    // Dedup constraint - already sent
                    $results['skipped']++;
                    continue;
                }

                if ($sendResult['success']) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                }

                // Rate limiting
                usleep(200000);
            }
        }

        return $results;
    }

    /**
     * Prepare template variable data from user record
     */
    private static function prepareTemplateData(array $user, array $settings): array
    {
        $validUntil = $user['valid_until'] ? new \DateTime($user['valid_until']) : null;
        $now = new \DateTime();

        $daysRemaining = 0;
        $daysExpired = 0;
        if ($validUntil) {
            $diff = $now->diff($validUntil);
            if ($validUntil > $now) {
                $daysRemaining = $diff->days;
            } else {
                $daysExpired = $diff->days;
            }
        }

        $downloadMbps = round(($user['download_speed'] ?? 0) / 1048576, 1);
        $uploadMbps = round(($user['upload_speed'] ?? 0) / 1048576, 1);

        return [
            'customer_name' => $user['customer_name'] ?? '',
            'customer_phone' => $user['customer_phone'] ?? '',
            'customer_email' => $user['customer_email'] ?? '',
            'customer_address' => $user['customer_address'] ?? '',
            'username' => $user['username'] ?? '',
            'password' => $user['password'] ?? '',
            'profile_name' => $user['profile_name'] ?? '',
            'profile_price' => number_format((float)($user['profile_price'] ?? 0), 0, ',', ' '),
            'expiration_date' => $validUntil ? $validUntil->format('d/m/Y') : '',
            'days_remaining' => (string)$daysRemaining,
            'days_expired' => (string)$daysExpired,
            'download_speed' => $downloadMbps . ' Mbps',
            'upload_speed' => $uploadMbps . ' Mbps',
            'zone_name' => $user['zone_name'] ?? '',
            'nas_name' => $user['nas_name'] ?? '',
            'data_limit' => ($user['data_limit'] ?? 0) > 0 ? round(($user['data_limit']) / (1024 * 1024 * 1024), 1) . ' Go' : 'Illimité',
            'company_name' => $settings['company_name'] ?? '',
            'support_phone' => $settings['support_phone'] ?? '',
            'current_date' => date('d/m/Y'),
            'current_time' => date('H:i'),
        ];
    }

    /**
     * Process template by replacing {{variables}}
     */
    private static function processTemplate(string $template, array $data): string
    {
        $message = $template;
        foreach ($data as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value ?? '', $message);
        }
        // Clean unreplaced variables
        $message = preg_replace('/\{\{[a-z_]+\}\}/', '', $message);
        return trim($message);
    }

    /**
     * Load system settings for template variables
     */
    private static function loadSettings(\PDO $pdo, int $adminId): array
    {
        $settings = [];
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE admin_id = ? AND setting_key IN ('company_name', 'support_phone', 'support_email', 'app_name')");
            $stmt->execute([$adminId]);
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Exception $e) {
            // Settings table might not exist
        }

        // Fallback to global settings
        if (empty($settings['company_name'])) {
            try {
                $stmt = $pdo->prepare("SELECT setting_value FROM global_settings WHERE setting_key = 'app_name'");
                $stmt->execute();
                $val = $stmt->fetchColumn();
                if ($val) $settings['company_name'] = $val;
            } catch (\Exception $e) {}
        }

        return $settings;
    }

    // ==========================================
    // HISTORY & STATS
    // ==========================================

    /**
     * GET /pppoe-reminders/history
     */
    public function getHistory(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(10, (int)($_GET['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $channel = $_GET['channel'] ?? '';
        $status = $_GET['status'] ?? '';
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo = $_GET['date_to'] ?? '';

        $where = "prl.admin_id = ?";
        $params = [$adminId];

        if ($channel && in_array($channel, ['whatsapp', 'sms'])) {
            $where .= " AND prl.channel = ?";
            $params[] = $channel;
        }
        if ($status && in_array($status, ['sent', 'failed'])) {
            $where .= " AND prl.status = ?";
            $params[] = $status;
        }
        if ($dateFrom) {
            $where .= " AND DATE(prl.sent_at) >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where .= " AND DATE(prl.sent_at) <= ?";
            $params[] = $dateTo;
        }

        // Count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM pppoe_reminder_log prl WHERE {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Data
        $sql = "
            SELECT prl.*, pu.customer_name, pu.username, prr.name as rule_name
            FROM pppoe_reminder_log prl
            LEFT JOIN pppoe_users pu ON prl.pppoe_user_id = pu.id
            LEFT JOIN pppoe_reminder_rules prr ON prl.rule_id = prr.id
            WHERE {$where}
            ORDER BY prl.sent_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        jsonSuccess([
            'data' => $history,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    /**
     * GET /pppoe-reminders/stats
     */
    public function getStats(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        // Global counts
        $stmt = $pdo->prepare("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as total_sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as total_failed
            FROM pppoe_reminder_log
            WHERE admin_id = ?
        ");
        $stmt->execute([$adminId]);
        $counts = $stmt->fetch(\PDO::FETCH_ASSOC);

        $total = (int)$counts['total'];
        $totalSent = (int)$counts['total_sent'];
        $totalFailed = (int)$counts['total_failed'];
        $successRate = $total > 0 ? round(($totalSent / $total) * 100, 1) : 0;

        // By channel
        $stmt = $pdo->prepare("
            SELECT channel, COUNT(*) as count, SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent
            FROM pppoe_reminder_log
            WHERE admin_id = ?
            GROUP BY channel
        ");
        $stmt->execute([$adminId]);
        $byChannel = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Last 7 days
        $stmt = $pdo->prepare("
            SELECT DATE(sent_at) as date,
                   COUNT(*) as total,
                   SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                   SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
            FROM pppoe_reminder_log
            WHERE admin_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY DATE(sent_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$adminId]);
        $last7Days = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Pending today: count users matching active rules not yet processed
        $pendingToday = 0;
        try {
            $stmt = $pdo->prepare("SELECT * FROM pppoe_reminder_rules WHERE admin_id = ? AND is_active = 1");
            $stmt->execute([$adminId]);
            $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rules as $rule) {
                $daysBefore = (int)$rule['days_before'];
                $targetDate = $daysBefore >= 0
                    ? date('Y-m-d', strtotime("+{$daysBefore} days"))
                    : date('Y-m-d', strtotime("-" . abs($daysBefore) . " days"));

                $pStmt = $pdo->prepare("
                    SELECT COUNT(*) FROM pppoe_users pu
                    WHERE pu.admin_id = ?
                    AND DATE(pu.valid_until) = ?
                    AND pu.status IN ('active', 'expired')
                    AND NOT EXISTS (
                        SELECT 1 FROM pppoe_reminder_log prl
                        WHERE prl.admin_id = ? AND prl.rule_id = ? AND prl.pppoe_user_id = pu.id AND prl.notification_date = CURDATE()
                    )
                ");
                $pStmt->execute([$adminId, $targetDate, $adminId, $rule['id']]);
                $pendingToday += (int)$pStmt->fetchColumn();
            }
        } catch (\Exception $e) {}

        jsonSuccess([
            'total_sent' => $totalSent,
            'total_failed' => $totalFailed,
            'success_rate' => $successRate,
            'pending_today' => $pendingToday,
            'by_channel' => $byChannel,
            'last_7_days' => $last7Days
        ]);
    }

    /**
     * GET /pppoe-reminders/variables
     */
    public function getVariables(): void
    {
        $variables = [
            ['category' => 'Client', 'variables' => [
                ['name' => 'customer_name', 'description' => 'Nom du client'],
                ['name' => 'customer_phone', 'description' => 'Téléphone du client'],
                ['name' => 'customer_email', 'description' => 'Email du client'],
                ['name' => 'customer_address', 'description' => 'Adresse du client'],
                ['name' => 'username', 'description' => 'Identifiant PPPoE'],
                ['name' => 'password', 'description' => 'Mot de passe PPPoE'],
            ]],
            ['category' => 'Abonnement', 'variables' => [
                ['name' => 'profile_name', 'description' => 'Nom du profil/forfait'],
                ['name' => 'profile_price', 'description' => 'Prix du profil (FCFA)'],
                ['name' => 'expiration_date', 'description' => 'Date d\'expiration (jj/mm/aaaa)'],
                ['name' => 'days_remaining', 'description' => 'Jours restants avant expiration'],
                ['name' => 'days_expired', 'description' => 'Jours depuis l\'expiration'],
                ['name' => 'download_speed', 'description' => 'Vitesse de téléchargement'],
                ['name' => 'upload_speed', 'description' => 'Vitesse d\'upload'],
                ['name' => 'data_limit', 'description' => 'Limite de données'],
            ]],
            ['category' => 'Système', 'variables' => [
                ['name' => 'zone_name', 'description' => 'Nom de la zone'],
                ['name' => 'nas_name', 'description' => 'Nom du routeur NAS'],
                ['name' => 'company_name', 'description' => 'Nom de l\'entreprise'],
                ['name' => 'support_phone', 'description' => 'Téléphone du support'],
                ['name' => 'current_date', 'description' => 'Date actuelle'],
                ['name' => 'current_time', 'description' => 'Heure actuelle'],
            ]],
        ];

        jsonSuccess($variables);
    }

    // ==========================================
    // SETTINGS
    // ==========================================

    /**
     * GET /pppoe-reminders/settings
     */
    public function getSettings(): void
    {
        $adminId = $this->getAdminId();
        $enabled = $this->db->getSetting('pppoe_reminders_enabled') ?? '0';
        jsonSuccess(['enabled' => $enabled === '1']);
    }

    /**
     * PUT /pppoe-reminders/settings
     */
    public function updateSettings(): void
    {
        $data = getJsonBody();
        $this->db->setSetting('pppoe_reminders_enabled', !empty($data['enabled']) ? '1' : '0');
        jsonSuccess(null, __('pppoe_reminders.settings_saved') ?? 'Paramètres enregistrés');
    }

    // ==========================================
    // AUTO-PROVISIONING
    // ==========================================

    private function ensureRulesProvisioned(?int $adminId): void
    {
        if ($adminId === null) return;
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pppoe_reminder_rules WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetchColumn() > 0) return;

        $defaults = [
            ['Rappel 7 jours (WhatsApp)', 7, 'whatsapp',
                "Bonjour {{customer_name}},\n\nVotre abonnement Internet ({{profile_name}}) expire dans {{days_remaining}} jours, le {{expiration_date}}.\n\nMontant du renouvellement : {{profile_price}} FCFA\n\nContact : {{support_phone}}\n{{company_name}}"],
            ['Rappel 3 jours (WhatsApp)', 3, 'whatsapp',
                "Bonjour {{customer_name}},\n\nATTENTION : votre abonnement {{profile_name}} expire dans {{days_remaining}} jours ({{expiration_date}}).\n\nRenouvelez maintenant : {{profile_price}} FCFA\nContact : {{support_phone}}\n{{company_name}}"],
            ['Rappel 1 jour (WhatsApp)', 1, 'whatsapp',
                "URGENT {{customer_name}} : votre abonnement {{profile_name}} expire DEMAIN ({{expiration_date}}) !\n\nRenouvelez : {{profile_price}} FCFA\nContact : {{support_phone}}\n{{company_name}}"],
            ['Jour d\'expiration (WhatsApp)', 0, 'whatsapp',
                "{{customer_name}}, votre abonnement {{profile_name}} expire AUJOURD'HUI !\n\nVotre connexion sera coupée sans renouvellement.\nMontant : {{profile_price}} FCFA\nContact : {{support_phone}}\n{{company_name}}"],
            ['Après expiration (WhatsApp)', -1, 'whatsapp',
                "{{customer_name}}, votre abonnement Internet a expiré le {{expiration_date}}.\n\nPour réactiver : {{profile_price}} FCFA\nContact : {{support_phone}}\n{{company_name}}"],
        ];

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO pppoe_reminder_rules (admin_id, name, days_before, channel, message_template, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");

        foreach ($defaults as $d) {
            $stmt->execute([$adminId, $d[0], $d[1], $d[2], $d[3]]);
        }
    }
}
