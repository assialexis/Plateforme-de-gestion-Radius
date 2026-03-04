<?php
/**
 * Service d'envoi d'email via SMTP (PHP pur, sans dépendances)
 * Supporte TLS, SSL et connexion non chiffrée
 */

class EmailService
{
    private PDO $pdo;
    private array $config = [];
    private $socket = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->config = $this->loadConfig();
    }

    /**
     * Charger la configuration SMTP depuis global_settings
     */
    public function loadConfig(): array
    {
        $keys = [
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
            'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
            'email_verification_enabled'
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'host' => $rows['smtp_host'] ?? '',
            'port' => (int)($rows['smtp_port'] ?? 587),
            'username' => $rows['smtp_username'] ?? '',
            'password' => $rows['smtp_password'] ?? '',
            'encryption' => $rows['smtp_encryption'] ?? 'tls',
            'from_email' => $rows['smtp_from_email'] ?? '',
            'from_name' => $rows['smtp_from_name'] ?? 'RADIUS Manager',
            'verification_enabled' => (bool)($rows['email_verification_enabled'] ?? 0),
        ];
    }

    /**
     * Vérifie si le SMTP est configuré
     */
    public function isConfigured(): bool
    {
        return !empty($this->config['host'])
            && !empty($this->config['from_email']);
    }

    /**
     * Vérifie si la vérification email est activée
     */
    public function isVerificationEnabled(): bool
    {
        return $this->config['verification_enabled'] && $this->isConfigured();
    }

    /**
     * Tester la connexion SMTP
     */
    public function testConnection(): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => __('email.smtp_not_configured')];
        }

        try {
            $this->smtpConnect();
            $this->smtpCommand("EHLO " . gethostname(), 250);

            if ($this->config['encryption'] === 'tls') {
                $this->smtpCommand("STARTTLS", 220);
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $this->smtpCommand("EHLO " . gethostname(), 250);
            }

            if (!empty($this->config['username'])) {
                $this->smtpCommand("AUTH LOGIN", 334);
                $this->smtpCommand(base64_encode($this->config['username']), 334);
                $this->smtpCommand(base64_encode($this->config['password']), 235);
            }

            $this->smtpCommand("QUIT", 221);
            $this->smtpDisconnect();

            return ['success' => true, 'message' => __('email.smtp_test_success')];
        } catch (Exception $e) {
            $this->smtpDisconnect();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private string $currentEmailType = 'other';

    /**
     * Définir le type d'email pour le log (verification, reset, test, other)
     */
    public function setEmailType(string $type): self
    {
        $this->currentEmailType = $type;
        return $this;
    }

    /**
     * Envoyer un email
     */
    public function send(string $to, string $subject, string $htmlBody): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => __('email.smtp_not_configured')];
        }

        // Valider l'adresse email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => __('email.invalid_email')];
        }

        try {
            $this->smtpConnect();
            $this->smtpCommand("EHLO " . gethostname(), 250);

            if ($this->config['encryption'] === 'tls') {
                $this->smtpCommand("STARTTLS", 220);
                stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $this->smtpCommand("EHLO " . gethostname(), 250);
            }

            if (!empty($this->config['username'])) {
                $this->smtpCommand("AUTH LOGIN", 334);
                $this->smtpCommand(base64_encode($this->config['username']), 334);
                $this->smtpCommand(base64_encode($this->config['password']), 235);
            }

            // Expéditeur et destinataire
            $fromEmail = $this->config['from_email'];
            $this->smtpCommand("MAIL FROM:<{$fromEmail}>", 250);
            $this->smtpCommand("RCPT TO:<{$to}>", 250);

            // Corps du message
            $this->smtpCommand("DATA", 354);

            $fromName = $this->encodeHeader($this->config['from_name']);
            $encodedSubject = $this->encodeHeader($subject);
            $boundary = md5(uniqid(time()));
            $messageId = '<' . uniqid() . '@' . parse_url($fromEmail, PHP_URL_HOST) . '>';

            $headers = "From: {$fromName} <{$fromEmail}>\r\n";
            $headers .= "To: <{$to}>\r\n";
            $headers .= "Subject: {$encodedSubject}\r\n";
            $headers .= "Message-ID: {$messageId}\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $headers .= "\r\n";

            // Version texte
            $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            $headers .= "--{$boundary}\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $headers .= "\r\n";
            $headers .= chunk_split(base64_encode($textBody)) . "\r\n";

            // Version HTML
            $headers .= "--{$boundary}\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $headers .= "\r\n";
            $headers .= chunk_split(base64_encode($htmlBody)) . "\r\n";

            $headers .= "--{$boundary}--\r\n";

            // Transparence des points (RFC 5321 section 4.5.2)
            $headers = str_replace("\r\n.\r\n", "\r\n..\r\n", $headers);

            $this->smtpSend($headers);
            $this->smtpCommand(".", 250);
            $this->smtpCommand("QUIT", 221);
            $this->smtpDisconnect();

            $this->logEmail($to, $subject, 'sent');

            return ['success' => true, 'message' => __('email.sent_success')];
        } catch (Exception $e) {
            $this->smtpDisconnect();
            $this->logEmail($to, $subject, 'failed', $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Enregistrer un log d'email envoyé/échoué
     */
    private function logEmail(string $to, string $subject, string $status, ?string $errorMessage = null): void
    {
        try {
            $adminId = $_SESSION['admin_id'] ?? null;
            $stmt = $this->pdo->prepare("
                INSERT INTO email_logs (to_email, subject, email_type, status, error_message, smtp_host, admin_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $to,
                mb_substr($subject, 0, 500),
                $this->currentEmailType,
                $status,
                $errorMessage,
                $this->config['host'] ?? null,
                $adminId,
            ]);
        } catch (Exception $e) {
            // Ne pas bloquer l'envoi si le log échoue
        }
        // Reset type after logging
        $this->currentEmailType = 'other';
    }

    /**
     * Charger les templates email depuis global_settings
     */
    private function loadTemplates(): array
    {
        $keys = [
            'email_template_verification_subject',
            'email_template_verification_body',
            'email_template_reset_subject',
            'email_template_reset_body',
            'password_reset_expiry_hours',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $stmt = $this->pdo->prepare("SELECT setting_key, setting_value FROM global_settings WHERE setting_key IN ($placeholders)");
        $stmt->execute($keys);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Remplacer les placeholders {{key}} dans un template
     */
    private function renderTemplate(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }

    /**
     * Envoyer un email de vérification
     */
    public function sendVerificationEmail(string $email, string $token, string $username): array
    {
        $baseUrl = $this->getBaseUrl();
        $verifyUrl = $baseUrl . '/verify-email.php?token=' . urlencode($token);
        $appName = $this->config['from_name'] ?: 'RADIUS Manager';

        $templates = $this->loadTemplates();
        $variables = [
            'username' => htmlspecialchars($username),
            'link' => $verifyUrl,
            'app_name' => htmlspecialchars($appName),
            'expiry_hours' => '24',
        ];

        // Sujet: template DB ou défaut
        $subject = !empty($templates['email_template_verification_subject'])
            ? $this->renderTemplate($templates['email_template_verification_subject'], $variables)
            : __('email.verification_subject', ['app' => $appName]);

        // Corps: template DB ou défaut
        $html = !empty($templates['email_template_verification_body'])
            ? $this->renderTemplate($templates['email_template_verification_body'], $variables)
            : $this->buildVerificationEmailHtml($username, $verifyUrl, $appName);

        $this->setEmailType('verification');
        return $this->send($email, $subject, $html);
    }

    /**
     * Envoyer un email de réinitialisation de mot de passe
     */
    public function sendPasswordResetEmail(string $email, string $token, string $username): array
    {
        $baseUrl = $this->getBaseUrl();
        $resetUrl = $baseUrl . '/reset-password.php?token=' . urlencode($token);
        $appName = $this->config['from_name'] ?: 'RADIUS Manager';

        $templates = $this->loadTemplates();
        $expiryHours = $templates['password_reset_expiry_hours'] ?? '1';
        $variables = [
            'username' => htmlspecialchars($username),
            'link' => $resetUrl,
            'app_name' => htmlspecialchars($appName),
            'expiry_hours' => $expiryHours,
        ];

        // Sujet: template DB ou défaut
        $subject = !empty($templates['email_template_reset_subject'])
            ? $this->renderTemplate($templates['email_template_reset_subject'], $variables)
            : __('email.reset_subject_default', ['app' => $appName]);

        // Corps: template DB ou défaut
        $html = !empty($templates['email_template_reset_body'])
            ? $this->renderTemplate($templates['email_template_reset_body'], $variables)
            : $this->buildPasswordResetEmailHtml($username, $resetUrl, $appName, $expiryHours);

        $this->setEmailType('reset');
        return $this->send($email, $subject, $html);
    }

    /**
     * Envoyer un email de test
     */
    public function sendTestEmail(string $to): array
    {
        $appName = $this->config['from_name'] ?: 'RADIUS Manager';
        $subject = __('email.test_subject', ['app' => $appName]);
        $html = '<html><body style="font-family:Arial,sans-serif;padding:20px;">'
            . '<h2 style="color:#2563eb;">' . htmlspecialchars($appName) . '</h2>'
            . '<p>' . __('email.test_body') . '</p>'
            . '<p style="color:#6b7280;font-size:12px;">' . date('Y-m-d H:i:s') . '</p>'
            . '</body></html>';

        $this->setEmailType('test');
        return $this->send($to, $subject, $html);
    }

    // =============================================
    // SMTP Socket Communication
    // =============================================

    private function smtpConnect(): void
    {
        $host = $this->config['host'];
        $port = $this->config['port'];
        $timeout = 10;

        if ($this->config['encryption'] === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $this->socket = @stream_socket_client(
            "{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            stream_context_create(['ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]])
        );

        if (!$this->socket) {
            throw new Exception(__('email.smtp_connect_failed') . ": {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, $timeout);

        // Lire le banner
        $response = $this->smtpRead();
        if (substr($response, 0, 3) !== '220') {
            throw new Exception(__('email.smtp_banner_error') . ": {$response}");
        }
    }

    private function smtpCommand(string $command, int $expectedCode): string
    {
        $this->smtpSend($command . "\r\n");
        $response = $this->smtpRead();

        $code = (int)substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("SMTP error: expected {$expectedCode}, got {$code} - {$response}");
        }

        return $response;
    }

    private function smtpSend(string $data): void
    {
        if (!$this->socket) {
            throw new Exception('SMTP socket not connected');
        }
        fwrite($this->socket, $data);
    }

    private function smtpRead(): string
    {
        if (!$this->socket) {
            throw new Exception('SMTP socket not connected');
        }

        $response = '';
        while (true) {
            $line = fgets($this->socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Dernière ligne : code suivi d'espace (pas de tiret)
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        return trim($response);
    }

    private function smtpDisconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    // =============================================
    // Helpers
    // =============================================

    private function encodeHeader(string $value): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function getBaseUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        // Remove trailing /views or similar
        $basePath = rtrim(str_replace('/views', '', $scriptDir), '/');
        return $protocol . '://' . $host . $basePath;
    }

    private function buildVerificationEmailHtml(string $username, string $verifyUrl, string $appName): string
    {
        $escapedUsername = htmlspecialchars($username);
        $escapedApp = htmlspecialchars($appName);
        $escapedUrl = htmlspecialchars($verifyUrl);

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <!-- Header -->
    <tr>
        <td style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 30px; text-align: center;">
            <h1 style="color:#ffffff;margin:0;font-size:24px;">{$escapedApp}</h1>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:30px;">
            <h2 style="color:#1f2937;margin:0 0 15px 0;font-size:20px;">Vérification de votre adresse email</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.6;">
                Bonjour <strong>{$escapedUsername}</strong>,<br><br>
                Merci de vous être inscrit sur <strong>{$escapedApp}</strong>.
                Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :
            </p>
            <div style="text-align:center;margin:30px 0;">
                <a href="{$escapedUrl}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:bold;">
                    Vérifier mon email
                </a>
            </div>
            <p style="color:#6b7280;font-size:13px;line-height:1.5;">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                <a href="{$escapedUrl}" style="color:#2563eb;word-break:break-all;">{$escapedUrl}</a>
            </p>
            <p style="color:#9ca3af;font-size:12px;margin-top:20px;">
                Ce lien expire dans 24 heures. Si vous n'avez pas créé de compte, ignorez cet email.
            </p>
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td style="background-color:#f9fafb;padding:20px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; {$escapedApp} - Email de vérification automatique</p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    private function buildPasswordResetEmailHtml(string $username, string $resetUrl, string $appName, string $expiryHours = '1'): string
    {
        $escapedUsername = htmlspecialchars($username);
        $escapedApp = htmlspecialchars($appName);
        $escapedUrl = htmlspecialchars($resetUrl);

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <!-- Header -->
    <tr>
        <td style="background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 30px; text-align: center;">
            <h1 style="color:#ffffff;margin:0;font-size:24px;">{$escapedApp}</h1>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:30px;">
            <h2 style="color:#1f2937;margin:0 0 15px 0;font-size:20px;">Réinitialisation de votre mot de passe</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.6;">
                Bonjour <strong>{$escapedUsername}</strong>,<br><br>
                Vous avez demandé la réinitialisation de votre mot de passe sur <strong>{$escapedApp}</strong>.
                Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :
            </p>
            <div style="text-align:center;margin:30px 0;">
                <a href="{$escapedUrl}" style="display:inline-block;background-color:#dc2626;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:bold;">
                    Réinitialiser mon mot de passe
                </a>
            </div>
            <p style="color:#6b7280;font-size:13px;line-height:1.5;">
                Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
                <a href="{$escapedUrl}" style="color:#dc2626;word-break:break-all;">{$escapedUrl}</a>
            </p>
            <p style="color:#9ca3af;font-size:12px;margin-top:20px;">
                Ce lien expire dans {$expiryHours} heure(s). Si vous n'avez pas fait cette demande, ignorez cet email.
            </p>
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td style="background-color:#f9fafb;padding:20px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="color:#9ca3af;font-size:12px;margin:0;">&copy; {$escapedApp} - Email automatique</p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }

    /**
     * Retourner les templates par défaut (pour l'API / bouton "Réinitialiser")
     */
    public static function getDefaultTemplates(): array
    {
        return [
            'verification' => [
                'subject' => '{{app_name}} - Vérifiez votre adresse email',
                'body' => '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <tr><td style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 30px; text-align: center;"><h1 style="color:#ffffff;margin:0;font-size:24px;">{{app_name}}</h1></td></tr>
    <tr><td style="padding:30px;">
        <h2 style="color:#1f2937;margin:0 0 15px 0;font-size:20px;">Vérification de votre adresse email</h2>
        <p style="color:#4b5563;font-size:15px;line-height:1.6;">Bonjour <strong>{{username}}</strong>,<br><br>Merci de vous être inscrit sur <strong>{{app_name}}</strong>. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
        <div style="text-align:center;margin:30px 0;"><a href="{{link}}" style="display:inline-block;background-color:#2563eb;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:bold;">Vérifier mon email</a></div>
        <p style="color:#6b7280;font-size:13px;line-height:1.5;">Si le bouton ne fonctionne pas, copiez ce lien :<br><a href="{{link}}" style="color:#2563eb;word-break:break-all;">{{link}}</a></p>
        <p style="color:#9ca3af;font-size:12px;margin-top:20px;">Ce lien expire dans {{expiry_hours}} heures.</p>
    </td></tr>
    <tr><td style="background-color:#f9fafb;padding:20px;text-align:center;border-top:1px solid #e5e7eb;"><p style="color:#9ca3af;font-size:12px;margin:0;">&copy; {{app_name}}</p></td></tr>
</table>
</td></tr></table>
</body></html>',
            ],
            'reset' => [
                'subject' => '{{app_name}} - Réinitialisez votre mot de passe',
                'body' => '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;padding:40px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
    <tr><td style="background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 30px; text-align: center;"><h1 style="color:#ffffff;margin:0;font-size:24px;">{{app_name}}</h1></td></tr>
    <tr><td style="padding:30px;">
        <h2 style="color:#1f2937;margin:0 0 15px 0;font-size:20px;">Réinitialisation de votre mot de passe</h2>
        <p style="color:#4b5563;font-size:15px;line-height:1.6;">Bonjour <strong>{{username}}</strong>,<br><br>Vous avez demandé la réinitialisation de votre mot de passe sur <strong>{{app_name}}</strong>. Cliquez sur le bouton ci-dessous :</p>
        <div style="text-align:center;margin:30px 0;"><a href="{{link}}" style="display:inline-block;background-color:#dc2626;color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-size:16px;font-weight:bold;">Réinitialiser mon mot de passe</a></div>
        <p style="color:#6b7280;font-size:13px;line-height:1.5;">Si le bouton ne fonctionne pas, copiez ce lien :<br><a href="{{link}}" style="color:#dc2626;word-break:break-all;">{{link}}</a></p>
        <p style="color:#9ca3af;font-size:12px;margin-top:20px;">Ce lien expire dans {{expiry_hours}} heure(s).</p>
    </td></tr>
    <tr><td style="background-color:#f9fafb;padding:20px;text-align:center;border-top:1px solid #e5e7eb;"><p style="color:#9ca3af;font-size:12px;margin:0;">&copy; {{app_name}}</p></td></tr>
</table>
</td></tr></table>
</body></html>',
            ],
        ];
    }
}
