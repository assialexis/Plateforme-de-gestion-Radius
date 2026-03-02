<?php
/**
 * Service de notifications Telegram
 * Gère l'envoi de notifications via un bot Telegram avec templates personnalisables
 */

class TelegramNotifier
{
    private PDO $pdo;
    private ?string $botToken = null;
    private ?string $defaultChatId = null;
    private bool $isEnabled = false;

    // Variables disponibles pour les templates
    private array $availableVariables = [
        'customer_name' => 'Nom du client',
        'customer_phone' => 'Téléphone du client',
        'customer_email' => 'Email du client',
        'customer_address' => 'Adresse du client',
        'username' => 'Identifiant PPPoE',
        'password' => 'Mot de passe PPPoE',
        'profile_name' => 'Nom du forfait',
        'profile_price' => 'Prix du forfait',
        'download_speed' => 'Vitesse download',
        'upload_speed' => 'Vitesse upload',
        'expiration_date' => 'Date d\'expiration',
        'days_remaining' => 'Jours restants',
        'days_expired' => 'Jours depuis expiration',
        'current_date' => 'Date actuelle',
        'current_time' => 'Heure actuelle',
        'zone_name' => 'Nom de la zone',
        'nas_name' => 'Nom du NAS',
        'data_used' => 'Données consommées',
        'data_limit' => 'Limite de données',
        'balance' => 'Solde du compte',
        'support_phone' => 'Téléphone support',
        'company_name' => 'Nom de l\'entreprise',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    /**
     * Charger la configuration Telegram depuis la base de données
     */
    private function loadConfig(): void
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM telegram_config ORDER BY id DESC LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                $this->botToken = $config['bot_token'];
                $this->defaultChatId = $config['default_chat_id'];
                $this->isEnabled = (bool)$config['is_enabled'];
            }
        } catch (PDOException $e) {
            // Table n'existe pas encore, ignorer
        }
    }

    /**
     * Obtenir la liste des variables disponibles
     */
    public function getAvailableVariables(): array
    {
        return $this->availableVariables;
    }

    /**
     * Vérifier si le service est configuré et actif
     */
    public function isConfigured(): bool
    {
        return $this->isEnabled && !empty($this->botToken);
    }

    /**
     * Envoyer un message Telegram
     *
     * @param string $chatId Chat ID du destinataire
     * @param string $message Message à envoyer
     * @param string $parseMode Mode de parsing (Markdown ou HTML)
     * @param array|null $inlineKeyboard Boutons inline optionnels
     */
    public function sendMessage(string $chatId, string $message, string $parseMode = 'Markdown', ?array $inlineKeyboard = null): array
    {
        if (!$this->botToken) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        $data = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => $parseMode,
            'disable_web_page_preview' => true,
        ];

        // Ajouter les boutons inline si fournis
        if ($inlineKeyboard) {
            $data['reply_markup'] = json_encode([
                'inline_keyboard' => $inlineKeyboard
            ]);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "CURL error: $error"];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['ok']) && $result['ok']) {
            return [
                'success' => true,
                'message_id' => $result['result']['message_id'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $result['description'] ?? 'Unknown error',
            'error_code' => $result['error_code'] ?? null
        ];
    }

    /**
     * Générer le lien WhatsApp pour un message
     */
    public function generateWhatsAppLink(string $phone, string $message): string
    {
        // Nettoyer le numéro de téléphone (garder uniquement les chiffres)
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ajouter le préfixe pays si nécessaire (229 pour le Bénin par défaut)
        if (strlen($phone) <= 10 && !str_starts_with($phone, '229')) {
            $phone = '229' . $phone;
        }

        // Encoder le message pour l'URL
        $encodedMessage = urlencode($message);

        return "https://wa.me/{$phone}?text={$encodedMessage}";
    }

    /**
     * Créer le clavier inline avec bouton WhatsApp
     */
    public function createWhatsAppButton(string $phone, string $message, string $buttonText = '📱 Envoyer sur WhatsApp'): array
    {
        $whatsappLink = $this->generateWhatsAppLink($phone, $message);

        return [
            [
                [
                    'text' => $buttonText,
                    'url' => $whatsappLink
                ]
            ]
        ];
    }

    /**
     * Remplacer les variables dans un template
     */
    public function processTemplate(string $template, array $data): string
    {
        $message = $template;

        foreach ($data as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $value ?? '', $message);
        }

        // Nettoyer les variables non remplacées
        $message = preg_replace('/\{\{[a-z_]+\}\}/', '', $message);

        return $message;
    }

    /**
     * Préparer les données d'un utilisateur PPPoE pour les templates
     */
    public function prepareUserData(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                pu.*,
                pp.name as profile_name,
                pp.price as profile_price,
                pp.download_speed,
                pp.upload_speed,
                pp.data_limit,
                z.name as zone_name
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            LEFT JOIN zones z ON pu.zone_id = z.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return [];
        }

        // Calculer les jours restants/expirés
        $expirationDate = $user['valid_until'] ? new DateTime($user['valid_until']) : null;
        $now = new DateTime();
        $daysRemaining = 0;
        $daysExpired = 0;

        if ($expirationDate) {
            $diff = $now->diff($expirationDate);
            if ($expirationDate > $now) {
                $daysRemaining = $diff->days;
            } else {
                $daysExpired = $diff->days;
            }
        }

        // Charger les paramètres système pour support_phone et company_name
        $supportPhone = $this->getSystemSetting('support_phone', '');
        $companyName = $this->getSystemSetting('company_name', 'NAS System');

        return [
            'customer_name' => $user['customer_name'] ?? '',
            'customer_phone' => $user['customer_phone'] ?? '',
            'customer_email' => $user['customer_email'] ?? '',
            'customer_address' => $user['customer_address'] ?? '',
            'username' => $user['username'] ?? '',
            'password' => $user['password'] ?? '',
            'profile_name' => $user['profile_name'] ?? '',
            'profile_price' => number_format($user['profile_price'] ?? 0, 0, ',', ' '),
            'download_speed' => $this->formatSpeed($user['download_speed'] ?? 0),
            'upload_speed' => $this->formatSpeed($user['upload_speed'] ?? 0),
            'expiration_date' => $expirationDate ? $expirationDate->format('d/m/Y') : 'N/A',
            'days_remaining' => $daysRemaining,
            'days_expired' => $daysExpired,
            'current_date' => $now->format('d/m/Y'),
            'current_time' => $now->format('H:i'),
            'zone_name' => $user['zone_name'] ?? '',
            'nas_name' => $user['nas_name'] ?? '',
            'data_used' => $this->formatBytes($user['data_used'] ?? 0),
            'data_limit' => $user['data_limit'] ? $this->formatBytes($user['data_limit']) : 'Illimité',
            'balance' => number_format($user['balance'] ?? 0, 0, ',', ' '),
            'support_phone' => $supportPhone,
            'company_name' => $companyName,
        ];
    }

    /**
     * Envoyer une notification basée sur un template
     */
    public function sendTemplateNotification(int $templateId, int $userId, ?string $chatId = null): array
    {
        // Charger le template
        $stmt = $this->pdo->prepare("SELECT * FROM telegram_templates WHERE id = ? AND is_active = 1");
        $stmt->execute([$templateId]);
        $template = $stmt->fetch();

        if (!$template) {
            return ['success' => false, 'error' => 'Template not found or inactive'];
        }

        // Préparer les données utilisateur
        $userData = $this->prepareUserData($userId);
        if (empty($userData)) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Déterminer le chat_id
        $targetChatId = $chatId;
        if (!$targetChatId) {
            // Essayer le chat_id du client
            $stmt = $this->pdo->prepare("SELECT telegram_chat_id, customer_phone FROM pppoe_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $targetChatId = $user['telegram_chat_id'] ?? $this->defaultChatId;
        } else {
            // Récupérer le téléphone du client
            $stmt = $this->pdo->prepare("SELECT customer_phone FROM pppoe_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }

        if (!$targetChatId) {
            return ['success' => false, 'error' => 'No chat ID available'];
        }

        // Traiter le template
        $message = $this->processTemplate($template['message_template'], $userData);

        // Préparer le bouton WhatsApp si activé et si le client a un téléphone
        $inlineKeyboard = null;
        if (!empty($template['whatsapp_button']) && !empty($user['customer_phone'])) {
            // Créer une version texte brut du message pour WhatsApp (sans markdown)
            $whatsappMessage = $this->stripMarkdown($message);
            $buttonText = $template['whatsapp_button_text'] ?? '📱 Envoyer sur WhatsApp';
            $inlineKeyboard = $this->createWhatsAppButton($user['customer_phone'], $whatsappMessage, $buttonText);
        }

        // Envoyer le message
        $result = $this->sendMessage($targetChatId, $message, 'Markdown', $inlineKeyboard);

        // Enregistrer dans l'historique
        $this->logNotification($templateId, $userId, $targetChatId, $message, $result);

        return $result;
    }

    /**
     * Supprimer le formatage Markdown d'un message
     */
    private function stripMarkdown(string $message): string
    {
        // Supprimer les astérisques pour gras
        $message = preg_replace('/\*([^*]+)\*/', '$1', $message);
        // Supprimer les underscores pour italique
        $message = preg_replace('/_([^_]+)_/', '$1', $message);
        // Supprimer les backticks pour code
        $message = preg_replace('/`([^`]+)`/', '$1', $message);

        return $message;
    }

    /**
     * Envoyer des notifications d'expiration programmées
     */
    public function processExpirationNotifications(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'Telegram not configured'];
        }

        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Récupérer les templates actifs de type expiration_warning
        $stmt = $this->pdo->query("
            SELECT * FROM telegram_templates
            WHERE event_type IN ('expiration_warning', 'expired')
            AND is_active = 1
            ORDER BY days_before DESC
        ");
        $templates = $stmt->fetchAll();

        foreach ($templates as $template) {
            $daysBefore = (int)$template['days_before'];

            // Calculer la date cible
            if ($daysBefore >= 0) {
                // Avant ou jour d'expiration
                $targetDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
                $dateCondition = "DATE(pu.valid_until) = ?";
            } else {
                // Après expiration (daysBefore est négatif)
                $daysAfter = abs($daysBefore);
                $targetDate = date('Y-m-d', strtotime("-{$daysAfter} days"));
                $dateCondition = "DATE(pu.valid_until) = ?";
            }

            // Trouver les utilisateurs concernés
            $stmt = $this->pdo->prepare("
                SELECT pu.id, pu.telegram_chat_id, pu.telegram_notifications
                FROM pppoe_users pu
                WHERE $dateCondition
                AND pu.status = 'active'
                AND pu.telegram_notifications = 1
                AND NOT EXISTS (
                    SELECT 1 FROM telegram_notification_log tnl
                    WHERE tnl.pppoe_user_id = pu.id
                    AND tnl.template_id = ?
                    AND tnl.notification_date = CURDATE()
                )
            ");
            $stmt->execute([$targetDate, $template['id']]);
            $users = $stmt->fetchAll();

            foreach ($users as $user) {
                $results['processed']++;

                // Déterminer le chat_id
                $chatId = $user['telegram_chat_id'] ?: $this->defaultChatId;

                if (!$chatId) {
                    $results['skipped']++;
                    continue;
                }

                // Envoyer la notification
                $sendResult = $this->sendTemplateNotification($template['id'], $user['id'], $chatId);

                if ($sendResult['success']) {
                    $results['sent']++;

                    // Marquer comme envoyé pour éviter les doublons
                    $this->markNotificationSent($user['id'], $template['id']);
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'user_id' => $user['id'],
                        'error' => $sendResult['error'] ?? 'Unknown error'
                    ];
                }

                // Pause pour éviter le rate limiting de Telegram
                usleep(100000); // 100ms
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Envoyer une notification à tous les destinataires configurés
     */
    public function notifyRecipients(string $eventType, int $userId): array
    {
        $results = [];

        // Récupérer les destinataires actifs
        $stmt = $this->pdo->prepare("
            SELECT * FROM telegram_recipients
            WHERE is_active = 1
            AND (
                (? = 'expiration_warning' AND receive_expiration_alerts = 1)
                OR (? = 'expired' AND receive_expiration_alerts = 1)
                OR (? IN ('payment_reminder', 'reactivated') AND receive_payment_alerts = 1)
                OR (? IN ('suspended', 'welcome') AND receive_system_alerts = 1)
            )
        ");
        $stmt->execute([$eventType, $eventType, $eventType, $eventType]);
        $recipients = $stmt->fetchAll();

        // Récupérer le template pour cet événement
        $stmt = $this->pdo->prepare("
            SELECT * FROM telegram_templates
            WHERE event_type = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$eventType]);
        $template = $stmt->fetch();

        if (!$template) {
            return ['success' => false, 'error' => 'No template for this event type'];
        }

        foreach ($recipients as $recipient) {
            $result = $this->sendTemplateNotification($template['id'], $userId, $recipient['chat_id']);
            $results[] = [
                'recipient' => $recipient['name'],
                'chat_id' => $recipient['chat_id'],
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Enregistrer une notification dans l'historique
     */
    private function logNotification(int $templateId, int $userId, string $chatId, string $message, array $result): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO telegram_notifications
                (template_id, pppoe_user_id, chat_id, message, status, error_message, telegram_message_id, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $templateId,
                $userId,
                $chatId,
                $message,
                $result['success'] ? 'sent' : 'failed',
                $result['error'] ?? null,
                $result['message_id'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log notification: " . $e->getMessage());
        }
    }

    /**
     * Marquer une notification comme envoyée pour éviter les doublons
     */
    private function markNotificationSent(int $userId, int $templateId): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO telegram_notification_log
                (pppoe_user_id, template_id, notification_date)
                VALUES (?, ?, CURDATE())
            ");
            $stmt->execute([$userId, $templateId]);
        } catch (PDOException $e) {
            error_log("Failed to mark notification: " . $e->getMessage());
        }
    }

    /**
     * Tester la connexion au bot Telegram
     */
    public function testConnection(): array
    {
        if (!$this->botToken) {
            return ['success' => false, 'error' => 'Bot token not configured'];
        }

        $url = "https://api.telegram.org/bot{$this->botToken}/getMe";

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "Connection error: $error"];
        }

        $result = json_decode($response, true);

        if (isset($result['ok']) && $result['ok']) {
            return [
                'success' => true,
                'bot_info' => $result['result']
            ];
        }

        return [
            'success' => false,
            'error' => $result['description'] ?? 'Unknown error'
        ];
    }

    /**
     * Obtenir un paramètre système
     */
    private function getSystemSetting(string $key, $default = null)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT value FROM settings WHERE `key` = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            return $result ? $result['value'] : $default;
        } catch (PDOException $e) {
            return $default;
        }
    }

    /**
     * Formater une vitesse en bps
     */
    private function formatSpeed(int $bps): string
    {
        if ($bps >= 1000000) {
            return round($bps / 1000000, 1) . ' Mbps';
        } elseif ($bps >= 1000) {
            return round($bps / 1000) . ' Kbps';
        }
        return $bps . ' bps';
    }

    /**
     * Formater des bytes
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' Go';
        } elseif ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' Mo';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' Ko';
        }
        return $bytes . ' o';
    }
}
