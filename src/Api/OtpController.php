<?php

class OtpController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private OtpService $otpService;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $pdo = $db->getPdo();
        $smsService = new SmsService($pdo);
        $this->otpService = new OtpService($pdo, $smsService);
    }

    private function getAdminId(): int
    {
        return $this->auth->getAdminId() ?? 1;
    }

    // ========================================
    // Public endpoints (captive portal)
    // ========================================

    /**
     * POST /otp/send
     * Envoyer un code OTP (public, pas d'auth requise)
     */
    public function sendOtp(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $adminId = (int)($data['admin_id'] ?? 0);
        $phone = trim($data['phone'] ?? '');
        $voucherUsername = trim($data['voucher_username'] ?? '');
        $voucherPassword = trim($data['voucher_password'] ?? '');
        $ip = trim($data['ip'] ?? '');
        $mac = trim($data['mac'] ?? '');
        $userAgent = trim($data['user_agent'] ?? '');

        if (!$adminId) {
            jsonError('admin_id requis', 400);
            return;
        }

        if (empty($phone)) {
            jsonError('Numéro de téléphone requis', 400);
            return;
        }

        if (empty($voucherUsername)) {
            jsonError('Code voucher requis', 400);
            return;
        }

        // Valider que le voucher existe
        $voucher = $this->db->getVoucherByUsername($voucherUsername);
        if (!$voucher) {
            jsonError('Code voucher invalide', 404);
            return;
        }

        // Vérifier que le voucher est utilisable
        if (in_array($voucher['status'], ['expired', 'disabled'])) {
            jsonError('Ce voucher est ' . ($voucher['status'] === 'expired' ? 'expiré' : 'désactivé'), 400);
            return;
        }

        // Appliquer le code pays si nécessaire
        $config = $this->otpService->getConfig($adminId);
        $countryCode = $config['country_code'] ?? '229';
        $phone = $this->formatPhone($phone, $countryCode);

        // Générer et envoyer l'OTP
        $result = $this->otpService->generateOtp($adminId, $phone, $voucherUsername, $voucherPassword, $ip, $mac, $userAgent);

        if ($result['success']) {
            jsonSuccess([
                'message' => $result['message'],
                'expires_in' => $result['expires_in'],
            ]);
        } else {
            jsonError($result['error'], 400);
        }
    }

    /**
     * POST /otp/verify
     * Vérifier un code OTP (public, pas d'auth requise)
     */
    public function verifyOtp(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $adminId = (int)($data['admin_id'] ?? 0);
        $phone = trim($data['phone'] ?? '');
        $otpCode = trim($data['otp_code'] ?? '');

        if (!$adminId || empty($phone) || empty($otpCode)) {
            jsonError('admin_id, phone et otp_code requis', 400);
            return;
        }

        // Appliquer le code pays
        $config = $this->otpService->getConfig($adminId);
        $countryCode = $config['country_code'] ?? '229';
        $phone = $this->formatPhone($phone, $countryCode);

        $result = $this->otpService->verifyOtp($adminId, $phone, $otpCode);

        if ($result['success']) {
            jsonSuccess([
                'message' => $result['message'],
                'redirect_url' => $result['redirect_url'],
            ]);
        } else {
            jsonError($result['error'], 400);
        }
    }

    /**
     * GET /otp/public-config?admin_id=X
     * Config publique (country_code, otp_length) pour la page OTP
     */
    public function getPublicConfig(): void
    {
        $adminId = (int)(get('admin_id') ?? 0);
        if (!$adminId) {
            jsonError('admin_id requis', 400);
            return;
        }

        $config = $this->otpService->getConfig($adminId);
        jsonSuccess([
            'country_code' => $config['country_code'] ?? '229',
            'otp_length' => (int)($config['otp_length'] ?? 6),
        ]);
    }

    // ========================================
    // Admin endpoints (authentifié)
    // ========================================

    /**
     * GET /otp/config
     */
    public function getConfig(): void
    {
        $config = $this->otpService->getConfig($this->getAdminId());

        // Joindre les gateways SMS actives pour le sélecteur
        $stmt = $this->db->getPdo()->prepare(
            "SELECT id, name, provider_code, is_active FROM sms_gateways WHERE admin_id = ? ORDER BY name"
        );
        $stmt->execute([$this->getAdminId()]);
        $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'config' => $config,
            'gateways' => $gateways,
        ]);
    }

    /**
     * PUT /otp/config
     */
    public function updateConfig(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->otpService->saveConfig($this->getAdminId(), $data);

        if ($result) {
            jsonSuccess(['message' => __('otp.config_saved')]);
        } else {
            jsonError(__('otp.config_save_error'), 500);
        }
    }

    /**
     * GET /otp/history
     */
    public function getHistory(): void
    {
        $page = (int)(get('page') ?? 1);
        $perPage = (int)(get('per_page') ?? 20);
        $search = get('search') ?: null;
        $status = get('status') ?: null;
        $dateFrom = get('date_from') ?: null;
        $dateTo = get('date_to') ?: null;

        $result = $this->otpService->getHistory($this->getAdminId(), $page, $perPage, $search, $status, $dateFrom, $dateTo);
        jsonSuccess($result);
    }

    /**
     * GET /otp/stats
     */
    public function getStats(): void
    {
        $stats = $this->otpService->getStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * DELETE /otp/history
     */
    public function deleteHistory(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $ids = $data['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            jsonError('IDs requis', 400);
            return;
        }

        $count = $this->otpService->deleteVerifications($this->getAdminId(), $ids);
        jsonSuccess(['message' => $count . ' enregistrement(s) supprimé(s)', 'deleted' => $count]);
    }

    /**
     * GET /otp/export
     */
    public function exportHistory(): void
    {
        $search = get('search') ?: null;
        $status = get('status') ?: null;
        $dateFrom = get('date_from') ?: null;
        $dateTo = get('date_to') ?: null;

        $rows = $this->otpService->exportHistory($this->getAdminId(), $search, $status, $dateFrom, $dateTo);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="otp_history_' . date('Y-m-d_His') . '.csv"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Téléphone', 'Voucher', 'Statut', 'Tentatives', 'IP', 'MAC', 'User Agent', 'Expire le', 'Vérifié le', 'Créé le']);

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['phone'],
                $row['voucher_username'],
                $row['status'],
                $row['attempts'],
                $row['ip_address'],
                $row['mac_address'],
                $row['user_agent'],
                $row['expires_at'],
                $row['verified_at'],
                $row['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * GET /otp/snippet
     * Génère le code snippet JavaScript à insérer dans login.html
     */
    public function getSnippet(): void
    {
        $config = $this->otpService->getConfig($this->getAdminId());

        // Déterminer l'URL de l'API (basée sur la requête actuelle)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $apiUrl = $protocol . '://' . $host . $scriptPath . '/api.php';
        $otpPageUrl = $protocol . '://' . $host . $scriptPath . '/public/otp-verify.html';

        $adminId = $this->getAdminId();

        $snippet = <<<JS
<!-- OTP Verification - Ajoutez ce code avant </body> dans login.html -->
<script>
(function() {
    var OTP_API = '{$apiUrl}';
    var OTP_PAGE = '{$otpPageUrl}';
    var ADMIN_ID = {$adminId};

    var origDoLogin = window.doLogin;
    window.doLogin = function() {
        var username = document.login.username.value;
        var password = document.login.password.value || username;
        var params = '?admin_id=' + ADMIN_ID
            + '&username=' + encodeURIComponent(username)
            + '&password=' + encodeURIComponent(password)
            + '&ip=' + encodeURIComponent('\$(ip)')
            + '&mac=' + encodeURIComponent('\$(mac)')
            + '&api_url=' + encodeURIComponent(OTP_API);
        window.location.href = OTP_PAGE + params;
        return false;
    };
})();
</script>
JS;

        jsonSuccess(['snippet' => $snippet]);
    }

    // ========================================
    // Registration endpoints (public)
    // ========================================

    /**
     * GET /registration/public-config?admin_id=X
     * Config publique pour la page d'inscription
     */
    public function getRegistrationPublicConfig(): void
    {
        $adminId = (int)(get('admin_id') ?? 0);
        if (!$adminId) {
            jsonError('admin_id requis', 400);
            return;
        }

        $config = $this->otpService->getConfig($adminId);
        jsonSuccess([
            'registration_enabled' => (bool)($config['registration_enabled'] ?? false),
            'country_code' => $config['country_code'] ?? '229',
            'otp_length' => (int)($config['otp_length'] ?? 6),
        ]);
    }

    /**
     * GET /registration/profiles?admin_id=X
     * Profils publics disponibles pour l'inscription
     */
    public function getPublicProfiles(): void
    {
        $adminId = (int)(get('admin_id') ?? 0);
        if (!$adminId) {
            jsonError('admin_id requis', 400);
            return;
        }

        $config = $this->otpService->getConfig($adminId);
        if (!$config || !$config['registration_enabled']) {
            jsonError('Inscription non disponible', 403);
            return;
        }

        // Return only the default registration profile
        $profileId = $config['registration_profile_id'] ?? null;
        if ($profileId) {
            $stmt = $this->db->getPdo()->prepare(
                "SELECT id, name, download_speed, upload_speed, price, validity, validity_unit FROM profiles WHERE id = ? AND admin_id = ?"
            );
            $stmt->execute([$profileId, $adminId]);
            $profile = $stmt->fetch(PDO::FETCH_ASSOC);
            jsonSuccess(['profile' => $profile ?: null]);
        } else {
            jsonSuccess(['profile' => null]);
        }
    }

    /**
     * POST /registration/send-otp
     * Inscription: vérifie le code du jour, crée un voucher, envoie OTP
     */
    public function registerAndSendOtp(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $adminId = (int)($data['admin_id'] ?? 0);
        $phone = trim($data['phone'] ?? '');
        $dailyCode = trim($data['daily_code'] ?? '');
        $ip = trim($data['ip'] ?? '');
        $mac = trim($data['mac'] ?? '');

        if (!$adminId) {
            jsonError('admin_id requis', 400);
            return;
        }

        if (empty($phone)) {
            jsonError('Numéro de téléphone requis', 400);
            return;
        }

        if (empty($dailyCode)) {
            jsonError('Code du jour requis', 400);
            return;
        }

        $config = $this->otpService->getConfig($adminId);
        if (!$config || !$config['registration_enabled']) {
            jsonError('Inscription non disponible', 403);
            return;
        }

        // Validate daily code
        if (!$this->otpService->validateDailyCode($adminId, $dailyCode)) {
            jsonError('Code du jour invalide', 400);
            return;
        }

        // Format phone
        $countryCode = $config['country_code'] ?? '229';
        $phone = $this->formatPhone($phone, $countryCode);

        // Check rate limit (max registrations per phone)
        $maxPerPhone = (int)($config['registration_max_per_phone'] ?? 1);
        $stmt = $this->db->getPdo()->prepare(
            "SELECT COUNT(*) FROM registration_log WHERE admin_id = ? AND phone = ? AND status = 'completed'"
        );
        $stmt->execute([$adminId, $phone]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= $maxPerPhone) {
            jsonError('Nombre maximum d\'inscriptions atteint pour ce numéro', 429);
            return;
        }

        // Check registration profile
        $profileId = $config['registration_profile_id'] ?? null;
        if (!$profileId) {
            jsonError('Aucun profil d\'inscription configuré', 500);
            return;
        }

        // Generate voucher credentials
        $username = 'REG_' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        $password = $username;

        // Calculate validity
        $validityDays = (int)($config['registration_validity_days'] ?? 1);
        $validFrom = date('Y-m-d H:i:s');
        $validUntil = date('Y-m-d H:i:s', strtotime("+{$validityDays} days"));

        // Create voucher
        try {
            $this->db->createVoucher([
                'username' => $username,
                'password' => $password,
                'profile_id' => $profileId,
                'customer_phone' => $phone,
                'valid_from' => $validFrom,
                'valid_until' => $validUntil,
                'admin_id' => $adminId,
                'notes' => 'Auto-registration',
            ]);
        } catch (\Throwable $e) {
            jsonError('Erreur lors de la création du compte', 500);
            return;
        }

        // Insert registration log
        $stmt = $this->db->getPdo()->prepare(
            "INSERT INTO registration_log (admin_id, phone, voucher_username, profile_id, ip_address, mac_address, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([$adminId, $phone, $username, $profileId, $ip, $mac]);

        // Send OTP
        $result = $this->otpService->generateOtp($adminId, $phone, $username, $password, $ip, $mac, $_SERVER['HTTP_USER_AGENT'] ?? '');

        if ($result['success']) {
            jsonSuccess([
                'message' => $result['message'],
                'expires_in' => $result['expires_in'],
            ]);
        } else {
            jsonError($result['error'], 400);
        }
    }

    /**
     * POST /registration/verify
     * Vérifie l'OTP d'inscription et complète l'inscription
     */
    public function verifyRegistration(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $adminId = (int)($data['admin_id'] ?? 0);
        $phone = trim($data['phone'] ?? '');
        $otpCode = trim($data['otp_code'] ?? '');

        if (!$adminId || empty($phone) || empty($otpCode)) {
            jsonError('admin_id, phone et otp_code requis', 400);
            return;
        }

        // Format phone
        $config = $this->otpService->getConfig($adminId);
        $countryCode = $config['country_code'] ?? '229';
        $phone = $this->formatPhone($phone, $countryCode);

        $result = $this->otpService->verifyOtp($adminId, $phone, $otpCode);

        if ($result['success']) {
            // Update registration log to completed
            $this->db->getPdo()->prepare(
                "UPDATE registration_log SET status = 'completed' WHERE admin_id = ? AND phone = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1"
            )->execute([$adminId, $phone]);

            jsonSuccess([
                'message' => $result['message'],
                'redirect_url' => $result['redirect_url'],
                'voucher_username' => $result['voucher_username'],
                'voucher_password' => $result['voucher_password'],
            ]);
        } else {
            jsonError($result['error'], 400);
        }
    }

    // ========================================
    // Registration admin endpoints
    // ========================================

    /**
     * GET /registration/config
     */
    public function getRegistrationConfig(): void
    {
        $config = $this->otpService->getConfig($this->getAdminId());

        // Get profiles for select
        $stmt = $this->db->getPdo()->prepare(
            "SELECT id, name, download_speed, upload_speed, price, validity, validity_unit FROM profiles WHERE admin_id = ? ORDER BY name"
        );
        $stmt->execute([$this->getAdminId()]);
        $profiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get current daily code
        $currentCode = $this->otpService->getCurrentDailyCode($this->getAdminId());

        jsonSuccess([
            'config' => [
                'registration_enabled' => (int)($config['registration_enabled'] ?? 0),
                'daily_code' => $config['daily_code'] ?? '',
                'daily_code_auto_rotate' => (int)($config['daily_code_auto_rotate'] ?? 0),
                'registration_profile_id' => $config['registration_profile_id'] ?? '',
                'registration_validity_days' => (int)($config['registration_validity_days'] ?? 1),
                'registration_max_per_phone' => (int)($config['registration_max_per_phone'] ?? 1),
                'registration_sms_template' => $config['registration_sms_template'] ?? '',
            ],
            'profiles' => $profiles,
            'current_daily_code' => $currentCode,
        ]);
    }

    /**
     * PUT /registration/config
     */
    public function updateRegistrationConfig(): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        $result = $this->otpService->saveRegistrationConfig($this->getAdminId(), $data);

        if ($result) {
            jsonSuccess(['message' => 'Configuration sauvegardée']);
        } else {
            jsonError('Erreur de sauvegarde', 500);
        }
    }

    /**
     * GET /registration/history
     */
    public function getRegistrationHistory(): void
    {
        $page = (int)(get('page') ?? 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $adminId = $this->getAdminId();

        $countStmt = $this->db->getPdo()->prepare("SELECT COUNT(*) FROM registration_log WHERE admin_id = ?");
        $countStmt->execute([$adminId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->getPdo()->prepare(
            "SELECT r.*, p.name as profile_name
             FROM registration_log r
             LEFT JOIN profiles p ON r.profile_id = p.id
             WHERE r.admin_id = ?
             ORDER BY r.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([$adminId, $perPage, $offset]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonSuccess([
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ]);
    }

    /**
     * GET /registration/stats
     */
    public function getRegistrationStats(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'completed') as completed,
                SUM(status = 'pending') as pending,
                SUM(status = 'failed') as failed
             FROM registration_log WHERE admin_id = ?"
        );
        $stmt->execute([$adminId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtToday = $pdo->prepare(
            "SELECT COUNT(*) as total, SUM(status = 'completed') as completed
             FROM registration_log WHERE admin_id = ? AND DATE(created_at) = CURDATE()"
        );
        $stmtToday->execute([$adminId]);
        $today = $stmtToday->fetch(PDO::FETCH_ASSOC);

        $stmtWeek = $pdo->prepare(
            "SELECT COUNT(*) as total, SUM(status = 'completed') as completed
             FROM registration_log WHERE admin_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );
        $stmtWeek->execute([$adminId]);
        $week = $stmtWeek->fetch(PDO::FETCH_ASSOC);

        jsonSuccess([
            'total' => (int)$stats['total'],
            'completed' => (int)$stats['completed'],
            'pending' => (int)$stats['pending'],
            'failed' => (int)$stats['failed'],
            'today_total' => (int)$today['total'],
            'today_completed' => (int)$today['completed'],
            'week_total' => (int)$week['total'],
            'week_completed' => (int)$week['completed'],
        ]);
    }

    /**
     * GET /registration/snippet
     * Génère le snippet JS pour la page de login hotspot
     */
    public function getRegistrationSnippet(): void
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
        $registrationPageUrl = $protocol . '://' . $host . $scriptPath . '/public/registration.html';

        $adminId = $this->getAdminId();

        $snippet = <<<JS
<!-- Inscription Hotspot - Ajoutez ce code avant </body> dans login.html -->
<script>
(function() {
    var REG_PAGE = '{$registrationPageUrl}';
    var ADMIN_ID = {$adminId};

    var btn = document.createElement('a');
    btn.href = REG_PAGE + '?admin_id=' + ADMIN_ID + '&ip=\$(ip)&mac=\$(mac)';
    btn.style.cssText = 'display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;margin-top:16px;box-shadow:0 4px 14px rgba(79,70,229,0.4);transition:transform 0.2s,box-shadow 0.2s;';
    btn.onmouseover = function() { this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 20px rgba(79,70,229,0.5)'; };
    btn.onmouseout = function() { this.style.transform=''; this.style.boxShadow='0 4px 14px rgba(79,70,229,0.4)'; };
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> S\\'inscrire';

    var container = document.createElement('div');
    container.style.cssText = 'text-align:center;margin-top:16px;';
    container.appendChild(btn);

    var form = document.querySelector('form') || document.querySelector('.login-form');
    if (form) {
        form.parentNode.insertBefore(container, form.nextSibling);
    } else {
        document.body.appendChild(container);
    }
})();
</script>
JS;

        jsonSuccess(['snippet' => $snippet]);
    }

    /**
     * Formater le numéro de téléphone avec le code pays
     */
    private function formatPhone(string $phone, string $countryCode): string
    {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si déjà au format international
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // Si commence par 00
        if (str_starts_with($phone, '00')) {
            return '+' . substr($phone, 2);
        }

        // Si commence par 0, remplacer par le code pays
        if (str_starts_with($phone, '0')) {
            return '+' . $countryCode . substr($phone, 1);
        }

        // Ajouter le code pays
        return '+' . $countryCode . $phone;
    }
}
