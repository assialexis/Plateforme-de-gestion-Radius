<?php
/**
 * Service de notifications WhatsApp via Green API
 * Gère l'envoi de notifications via WhatsApp avec templates personnalisables
 */

class WhatsAppNotifier
{
    private PDO $pdo;
    private ?string $idInstance = null;
    private ?string $apiTokenInstance = null;
    private string $apiUrl = 'https://api.green-api.com';
    private ?string $defaultPhone = null;
    private string $countryCode = '229';
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
        'invoice_number' => 'Numéro de facture',
        'invoice_amount' => 'Montant de la facture',
        'invoice_due_date' => 'Date d\'échéance',
        'invoice_description' => 'Description de la facture',
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->loadConfig();
    }

    /**
     * Charger la configuration WhatsApp depuis la base de données
     */
    private function loadConfig(): void
    {
        try {
            $stmt = $this->pdo->query("SELECT * FROM whatsapp_config ORDER BY id DESC LIMIT 1");
            $config = $stmt->fetch();

            if ($config) {
                $this->idInstance = $config['id_instance'];
                $this->apiTokenInstance = $config['api_token_instance'];
                $this->apiUrl = $config['api_url'] ?: 'https://api.green-api.com';
                $this->defaultPhone = $config['default_phone'];
                $this->countryCode = $config['country_code'] ?: '229';
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
        return $this->isEnabled && !empty($this->idInstance) && !empty($this->apiTokenInstance);
    }

    /**
     * Formater un numéro de téléphone pour WhatsApp
     * Retourne le format: countryCode + numero (chiffres uniquement)
     */
    public function formatPhone(string $phone): string
    {
        // Retirer tout sauf les chiffres
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si le numéro est court (local), ajouter le code pays
        if (strlen($phone) <= 10 && !str_starts_with($phone, $this->countryCode)) {
            $phone = $this->countryCode . $phone;
        }

        return $phone;
    }

    /**
     * Construire l'URL de l'API Green API
     */
    private function buildUrl(string $method): string
    {
        return "{$this->apiUrl}/waInstance{$this->idInstance}/{$method}/{$this->apiTokenInstance}";
    }

    /**
     * Envoyer un message WhatsApp via Green API
     */
    public function sendMessage(string $phone, string $message): array
    {
        if (!$this->idInstance || !$this->apiTokenInstance) {
            return ['success' => false, 'error' => 'Green API not configured'];
        }

        $formattedPhone = $this->formatPhone($phone);
        $url = $this->buildUrl('sendMessage');

        $data = [
            'chatId' => $formattedPhone . '@c.us',
            'message' => $message,
        ];

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

        if ($httpCode === 200 && isset($result['idMessage'])) {
            return [
                'success' => true,
                'message_id' => $result['idMessage']
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? $result['description'] ?? "HTTP $httpCode",
        ];
    }

    /**
     * Envoyer un fichier via URL (pour factures PDF, etc.)
     */
    public function sendFile(string $phone, string $fileUrl, string $fileName, string $caption = ''): array
    {
        if (!$this->idInstance || !$this->apiTokenInstance) {
            return ['success' => false, 'error' => 'Green API not configured'];
        }

        $formattedPhone = $this->formatPhone($phone);
        $url = $this->buildUrl('sendFileByUrl');

        $data = [
            'chatId' => $formattedPhone . '@c.us',
            'urlFile' => $fileUrl,
            'fileName' => $fileName,
            'caption' => $caption,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
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

        if ($httpCode === 200 && isset($result['idMessage'])) {
            return [
                'success' => true,
                'message_id' => $result['idMessage']
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? $result['description'] ?? "HTTP $httpCode",
        ];
    }

    /**
     * Envoyer un fichier par upload direct (multipart/form-data)
     */
    public function sendFileByUpload(string $phone, string $filePath, string $fileName, string $caption = ''): array
    {
        if (!$this->idInstance || !$this->apiTokenInstance) {
            return ['success' => false, 'error' => 'Green API not configured'];
        }

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'File not found: ' . $filePath];
        }

        $formattedPhone = $this->formatPhone($phone);
        $url = $this->buildUrl('sendFileByUpload');

        $data = [
            'chatId' => $formattedPhone . '@c.us',
            'file' => new CURLFile($filePath, mime_content_type($filePath), $fileName),
            'fileName' => $fileName,
            'caption' => $caption,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
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

        if ($httpCode === 200 && isset($result['idMessage'])) {
            return [
                'success' => true,
                'message_id' => $result['idMessage']
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? $result['description'] ?? "HTTP $httpCode",
        ];
    }

    /**
     * Générer le PDF d'une facture et l'envoyer via WhatsApp
     */
    public function sendInvoicePdf(int $invoiceId, string $phone, string $caption = ''): array
    {
        // Charger les données de la facture
        $stmt = $this->pdo->prepare("
            SELECT i.*, pu.username, pu.customer_name, pu.customer_phone,
                   pu.customer_email, pu.customer_address
            FROM pppoe_invoices i
            JOIN pppoe_users pu ON i.pppoe_user_id = pu.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            return ['success' => false, 'error' => 'Facture introuvable'];
        }

        // Charger items et paiements
        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $invoice['items'] = $stmt->fetchAll();

        $stmt = $this->pdo->prepare("SELECT * FROM pppoe_payments WHERE invoice_id = ?");
        $stmt->execute([$invoiceId]);
        $invoice['payments'] = $stmt->fetchAll();

        // Charger les paramètres de facturation
        $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM billing_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Générer le HTML
        $html = $this->buildInvoicePdfHtml($invoice, $settings);

        // Convertir en PDF avec Dompdf
        try {
            $fontDir = dirname(__DIR__, 2) . '/vendor/dompdf/dompdf/lib/fonts';
            $dompdf = new \Dompdf\Dompdf([
                'isRemoteEnabled' => true,
                'fontDir' => $fontDir,
                'fontCache' => $fontDir,
                'defaultFont' => 'Helvetica',
            ]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Erreur génération PDF: ' . $e->getMessage()];
        }

        if (empty($pdfContent)) {
            return ['success' => false, 'error' => 'Le PDF généré est vide'];
        }

        // Sauvegarder temporairement dans un dossier accessible
        $tmpDir = dirname(__DIR__, 2) . '/storage/tmp';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0777, true);
        }
        $tmpFile = $tmpDir . '/facture_' . $invoice['invoice_number'] . '_' . time() . '.pdf';
        $written = file_put_contents($tmpFile, $pdfContent);
        if ($written === false) {
            return ['success' => false, 'error' => 'Impossible de sauvegarder le PDF temporaire'];
        }

        // Envoyer via WhatsApp
        $fileName = 'Facture_' . $invoice['invoice_number'] . '.pdf';
        $result = $this->sendFileByUpload($phone, $tmpFile, $fileName, $caption);

        // Supprimer le fichier temporaire
        @unlink($tmpFile);

        return $result;
    }

    /**
     * Construire le HTML de facture pour le PDF
     */
    private function buildInvoicePdfHtml(array $invoice, array $settings): string
    {
        $statusLabels = [
            'draft' => 'Brouillon', 'pending' => 'En attente', 'paid' => 'Payée',
            'partial' => 'Partiel', 'overdue' => 'En retard', 'cancelled' => 'Annulée'
        ];
        $statusColors = [
            'draft' => '#6b7280', 'pending' => '#f59e0b', 'paid' => '#10b981',
            'partial' => '#3b82f6', 'overdue' => '#ef4444', 'cancelled' => '#6b7280'
        ];

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>
    body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
    .header { overflow: hidden; margin-bottom: 30px; }
    .company { float: left; width: 60%; }
    .company h1 { margin: 0; font-size: 22px; color: #2563eb; }
    .invoice-info { float: right; width: 35%; text-align: right; }
    .invoice-number { font-size: 18px; font-weight: bold; color: #2563eb; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 4px; color: white; font-weight: bold;
              background: ' . ($statusColors[$invoice['status']] ?? '#6b7280') . '; }
    .client-box { background: #f3f4f6; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .client-box h3 { margin: 0 0 10px 0; color: #374151; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.items th { background: #2563eb; color: white; padding: 10px; text-align: left; }
    table.items td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
    .totals { width: 250px; margin-left: auto; }
    .totals td { padding: 6px 8px; }
    .totals .total-row { font-weight: bold; font-size: 14px; background: #f3f4f6; }
    .footer { margin-top: 40px; text-align: center; color: #6b7280; font-size: 10px; }
</style></head><body>';

        $html .= '<div class="header">
    <div class="company">
        <h1>' . htmlspecialchars($settings['company_name'] ?? 'Mon Entreprise') . '</h1>
        <p>' . nl2br(htmlspecialchars($settings['company_address'] ?? '')) . '</p>
        <p>Tél: ' . htmlspecialchars($settings['company_phone'] ?? '') . '</p>
        <p>Email: ' . htmlspecialchars($settings['company_email'] ?? '') . '</p>
    </div>
    <div class="invoice-info">
        <div class="invoice-number">FACTURE</div>
        <div style="font-size: 16px; margin: 5px 0;">' . htmlspecialchars($invoice['invoice_number']) . '</div>
        <div class="status">' . ($statusLabels[$invoice['status']] ?? $invoice['status']) . '</div>
        <p style="margin-top: 10px;">
            <strong>Date:</strong> ' . date('d/m/Y', strtotime($invoice['created_at'])) . '<br>
            <strong>Échéance:</strong> ' . date('d/m/Y', strtotime($invoice['due_date'])) . '
        </p>
    </div>
</div>';

        $html .= '<div class="client-box">
    <h3>Facturé à:</h3>
    <strong>' . htmlspecialchars($invoice['customer_name'] ?? $invoice['username']) . '</strong><br>
    ' . htmlspecialchars($invoice['customer_phone'] ?? '') . '<br>
    ' . htmlspecialchars($invoice['customer_email'] ?? '') . '<br>
    ' . nl2br(htmlspecialchars($invoice['customer_address'] ?? '')) . '
</div>';

        $html .= '<table class="items"><thead><tr>
    <th>Description</th>
    <th style="width: 60px; text-align: center;">Qté</th>
    <th style="width: 100px; text-align: right;">Prix unit.</th>
    <th style="width: 100px; text-align: right;">Total</th>
</tr></thead><tbody>';

        foreach ($invoice['items'] as $item) {
            $html .= '<tr>
    <td>' . htmlspecialchars($item['description']) . '</td>
    <td style="text-align: center;">' . number_format($item['quantity'], 0) . '</td>
    <td style="text-align: right;">' . number_format($item['unit_price'], 0, ',', ' ') . ' FCFA</td>
    <td style="text-align: right;">' . number_format($item['total_price'], 0, ',', ' ') . ' FCFA</td>
</tr>';
        }

        $html .= '</tbody></table>';

        $html .= '<table class="totals"><tr>
    <td>Sous-total:</td>
    <td style="text-align: right;">' . number_format($invoice['amount'], 0, ',', ' ') . ' FCFA</td>
</tr>';

        if ($invoice['tax_amount'] > 0) {
            $html .= '<tr>
    <td>Taxe (' . $invoice['tax_rate'] . '%):</td>
    <td style="text-align: right;">' . number_format($invoice['tax_amount'], 0, ',', ' ') . ' FCFA</td>
</tr>';
        }

        $html .= '<tr class="total-row">
    <td>Total:</td>
    <td style="text-align: right;">' . number_format($invoice['total_amount'], 0, ',', ' ') . ' FCFA</td>
</tr>';

        if ($invoice['paid_amount'] > 0) {
            $html .= '<tr>
    <td>Payé:</td>
    <td style="text-align: right; color: #10b981;">-' . number_format($invoice['paid_amount'], 0, ',', ' ') . ' FCFA</td>
</tr><tr class="total-row">
    <td>Reste à payer:</td>
    <td style="text-align: right; color: #ef4444;">' . number_format($invoice['total_amount'] - $invoice['paid_amount'], 0, ',', ' ') . ' FCFA</td>
</tr>';
        }

        $html .= '</table>';
        $html .= '<div class="footer"><p>' . htmlspecialchars($settings['invoice_footer'] ?? '') . '</p></div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Tester la connexion à Green API
     */
    public function testConnection(): array
    {
        if (!$this->idInstance || !$this->apiTokenInstance) {
            return ['success' => false, 'error' => 'Green API credentials not configured'];
        }

        $url = $this->buildUrl('getSettings');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => "Connection error: $error"];
        }

        $result = json_decode($response, true);

        if ($httpCode === 200 && isset($result['wid'])) {
            return [
                'success' => true,
                'account_info' => [
                    'wid' => $result['wid'],
                    'phone' => $result['phone'] ?? null,
                ]
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Failed to get account settings'
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
    public function sendTemplateNotification(int $templateId, int $userId, ?string $phone = null): array
    {
        // Charger le template
        $stmt = $this->pdo->prepare("SELECT * FROM whatsapp_templates WHERE id = ? AND is_active = 1");
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

        // Déterminer le numéro de téléphone
        $targetPhone = $phone;
        if (!$targetPhone) {
            $stmt = $this->pdo->prepare("SELECT whatsapp_phone, customer_phone FROM pppoe_users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $targetPhone = $user['whatsapp_phone'] ?: $user['customer_phone'] ?: $this->defaultPhone;
        }

        if (!$targetPhone) {
            return ['success' => false, 'error' => 'No phone number available'];
        }

        // Traiter le template
        $message = $this->processTemplate($template['message_template'], $userData);

        // Envoyer le message
        $result = $this->sendMessage($targetPhone, $message);

        // Enregistrer dans l'historique
        $this->logNotification($templateId, $userId, $targetPhone, $message, $result);

        return $result;
    }

    /**
     * Déclencher un événement WhatsApp en temps réel (welcome, reactivated, suspended, invoice_created)
     */
    public function triggerEvent(string $eventType, int $userId): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        // Chercher le template actif pour cet événement
        $stmt = $this->pdo->prepare("
            SELECT * FROM whatsapp_templates
            WHERE event_type = ? AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute([$eventType]);
        $template = $stmt->fetch();

        if (!$template) {
            return ['success' => false, 'error' => 'No active template for event: ' . $eventType];
        }

        return $this->sendTemplateNotification($template['id'], $userId);
    }

    /**
     * Envoyer des notifications d'expiration programmées
     */
    public function processExpirationNotifications(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        $results = [
            'processed' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Récupérer les templates actifs de type expiration
        $stmt = $this->pdo->query("
            SELECT * FROM whatsapp_templates
            WHERE event_type IN ('expiration_warning', 'expired')
            AND is_active = 1
            ORDER BY days_before DESC
        ");
        $templates = $stmt->fetchAll();

        foreach ($templates as $template) {
            $daysBefore = (int)$template['days_before'];

            // Calculer la date cible
            if ($daysBefore >= 0) {
                $targetDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
            } else {
                $daysAfter = abs($daysBefore);
                $targetDate = date('Y-m-d', strtotime("-{$daysAfter} days"));
            }

            // Trouver les utilisateurs concernés
            $stmt = $this->pdo->prepare("
                SELECT pu.id, pu.whatsapp_phone, pu.customer_phone, pu.whatsapp_notifications
                FROM pppoe_users pu
                WHERE DATE(pu.valid_until) = ?
                AND pu.status IN ('active', 'expired')
                AND pu.whatsapp_notifications = 1
                AND NOT EXISTS (
                    SELECT 1 FROM whatsapp_notification_log wnl
                    WHERE wnl.pppoe_user_id = pu.id
                    AND wnl.template_id = ?
                    AND wnl.notification_date = CURDATE()
                )
            ");
            $stmt->execute([$targetDate, $template['id']]);
            $users = $stmt->fetchAll();

            foreach ($users as $user) {
                $results['processed']++;

                // Déterminer le numéro WhatsApp
                $phone = $user['whatsapp_phone'] ?: $user['customer_phone'];

                if (!$phone) {
                    $results['skipped']++;
                    continue;
                }

                // Envoyer la notification
                $sendResult = $this->sendTemplateNotification($template['id'], $user['id'], $phone);

                if ($sendResult['success']) {
                    $results['sent']++;
                    $this->markNotificationSent($user['id'], $template['id']);
                } else {
                    $results['failed']++;
                    $results['errors'][] = [
                        'user_id' => $user['id'],
                        'error' => $sendResult['error'] ?? 'Unknown error'
                    ];
                }

                // Pause pour éviter le rate limiting
                usleep(200000); // 200ms
            }
        }

        return ['success' => true, 'results' => $results];
    }

    /**
     * Préparer les données d'une facture pour les templates
     */
    public function prepareInvoiceData(int $invoiceId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, pu.id as user_id, pu.username, pu.customer_name, pu.customer_phone,
                   pu.whatsapp_phone, pu.customer_email, pu.customer_address,
                   pu.valid_until, pu.balance, pu.data_used,
                   pp.name as profile_name, pp.price as profile_price,
                   pp.download_speed, pp.upload_speed, pp.data_limit,
                   z.name as zone_name
            FROM pppoe_invoices i
            JOIN pppoe_users pu ON i.pppoe_user_id = pu.id
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            LEFT JOIN zones z ON pu.zone_id = z.id
            WHERE i.id = ?
        ");
        $stmt->execute([$invoiceId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [];
        }

        // Données utilisateur standard
        $data = $this->prepareUserData($row['user_id']);
        if (empty($data)) {
            return [];
        }

        // Ajouter les données facture
        $dueDate = $row['due_date'] ? new DateTime($row['due_date']) : null;
        $data['invoice_number'] = $row['invoice_number'] ?? '';
        $data['invoice_amount'] = number_format($row['total_amount'] ?? 0, 0, ',', ' ');
        $data['invoice_due_date'] = $dueDate ? $dueDate->format('d/m/Y') : 'N/A';
        $data['invoice_description'] = $row['description'] ?? '';

        // Retourner aussi le téléphone pour l'envoi
        $data['_phone'] = $row['whatsapp_phone'] ?: $row['customer_phone'] ?: null;
        $data['_user_id'] = $row['user_id'];

        return $data;
    }

    /**
     * Envoyer une notification de facture via WhatsApp
     */
    public function sendInvoiceNotification(int $invoiceId, ?string $phone = null): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'error' => 'WhatsApp non configuré'];
        }

        // Préparer les données
        $data = $this->prepareInvoiceData($invoiceId);
        if (empty($data)) {
            return ['success' => false, 'error' => 'Facture ou client introuvable'];
        }

        $userId = $data['_user_id'];
        $targetPhone = $phone ?: $data['_phone'];

        if (!$targetPhone) {
            return ['success' => false, 'error' => 'Aucun numéro de téléphone disponible'];
        }

        // Chercher le template invoice_created actif
        $stmt = $this->pdo->prepare("
            SELECT * FROM whatsapp_templates
            WHERE event_type = 'invoice_created' AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute();
        $template = $stmt->fetch();

        if ($template) {
            $message = $this->processTemplate($template['message_template'], $data);
            $templateId = (int)$template['id'];
        } else {
            // Message par défaut si pas de template
            $message = "📄 *Facture {$data['invoice_number']}*\n\n"
                . "Bonjour {$data['customer_name']},\n\n"
                . "Montant: *{$data['invoice_amount']} FCFA*\n"
                . "Échéance: {$data['invoice_due_date']}\n"
                . "Forfait: {$data['profile_name']}\n\n"
                . "Merci de procéder au paiement.\n"
                . "_{$data['company_name']}_";
            $templateId = 0;
        }

        // Envoyer
        $result = $this->sendMessage($targetPhone, $message);

        // Logger
        $this->logNotification($templateId, $userId, $targetPhone, $message, $result);

        return $result;
    }

    /**
     * Enregistrer une notification dans l'historique
     */
    private function logNotification(int $templateId, int $userId, string $phone, string $message, array $result): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO whatsapp_notifications
                (template_id, pppoe_user_id, phone, message, status, error_message, wa_message_id, sent_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $templateId,
                $userId,
                $phone,
                $message,
                $result['success'] ? 'sent' : 'failed',
                $result['error'] ?? null,
                $result['message_id'] ?? null
            ]);
        } catch (PDOException $e) {
            error_log("Failed to log WhatsApp notification: " . $e->getMessage());
        }
    }

    /**
     * Marquer une notification comme envoyée pour éviter les doublons
     */
    private function markNotificationSent(int $userId, int $templateId): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT IGNORE INTO whatsapp_notification_log
                (pppoe_user_id, template_id, notification_date)
                VALUES (?, ?, CURDATE())
            ");
            $stmt->execute([$userId, $templateId]);
        } catch (PDOException $e) {
            error_log("Failed to mark WhatsApp notification: " . $e->getMessage());
        }
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
