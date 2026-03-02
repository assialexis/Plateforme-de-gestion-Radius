<?php

class OtpService
{
    private PDO $pdo;
    private SmsService $smsService;

    public function __construct(PDO $pdo, SmsService $smsService)
    {
        $this->pdo = $pdo;
        $this->smsService = $smsService;
    }

    /**
     * Récupérer la configuration OTP d'un admin
     */
    public function getConfig(int $adminId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM otp_config WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$config) {
            // Auto-provision config for this admin
            $this->pdo->prepare(
                "INSERT IGNORE INTO otp_config (admin_id, is_enabled, hotspot_dns, otp_length, otp_expiry_seconds, sms_template, country_code)
                 VALUES (?, 0, '', 6, 300, 'Votre code de verification WiFi: {{otp_code}}. Ce code expire dans {{expiry_duration}}. {{company_name}}', '229')"
            )->execute([$adminId]);

            $stmt->execute([$adminId]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $config;
    }

    /**
     * Sauvegarder la configuration OTP
     */
    public function saveConfig(int $adminId, array $data): bool
    {
        $config = $this->getConfig($adminId);

        $stmt = $this->pdo->prepare(
            "UPDATE otp_config SET
                is_enabled = ?,
                hotspot_dns = ?,
                otp_length = ?,
                otp_expiry_seconds = ?,
                sms_gateway_id = ?,
                sms_template = ?,
                country_code = ?
             WHERE admin_id = ?"
        );

        return $stmt->execute([
            $data['is_enabled'] ?? $config['is_enabled'],
            $data['hotspot_dns'] ?? $config['hotspot_dns'],
            max(4, min(8, (int)($data['otp_length'] ?? $config['otp_length']))),
            max(60, min(600, (int)($data['otp_expiry_seconds'] ?? $config['otp_expiry_seconds']))),
            $data['sms_gateway_id'] ?? $config['sms_gateway_id'],
            $data['sms_template'] ?? $config['sms_template'],
            $data['country_code'] ?? $config['country_code'],
            $adminId,
        ]);
    }

    /**
     * Sauvegarder la configuration d'inscription
     */
    public function saveRegistrationConfig(int $adminId, array $data): bool
    {
        $config = $this->getConfig($adminId);

        $stmt = $this->pdo->prepare(
            "UPDATE otp_config SET
                registration_enabled = ?,
                daily_code = ?,
                daily_code_auto_rotate = ?,
                registration_profile_id = ?,
                registration_validity_days = ?,
                registration_max_per_phone = ?,
                registration_sms_template = ?
             WHERE admin_id = ?"
        );

        $profileId = $data['registration_profile_id'] ?? $config['registration_profile_id'] ?? null;
        if ($profileId === '' || $profileId === '0') {
            $profileId = null;
        }

        return $stmt->execute([
            $data['registration_enabled'] ?? $config['registration_enabled'] ?? 0,
            $data['daily_code'] ?? $config['daily_code'] ?? null,
            $data['daily_code_auto_rotate'] ?? $config['daily_code_auto_rotate'] ?? 0,
            $profileId,
            max(1, (int)($data['registration_validity_days'] ?? $config['registration_validity_days'] ?? 1)),
            max(1, (int)($data['registration_max_per_phone'] ?? $config['registration_max_per_phone'] ?? 1)),
            $data['registration_sms_template'] ?? $config['registration_sms_template'] ?? null,
            $adminId,
        ]);
    }

    /**
     * Générer et envoyer un OTP
     */
    public function generateOtp(int $adminId, string $phone, string $voucherUsername, string $voucherPassword, ?string $ip = null, ?string $mac = null, ?string $userAgent = null): array
    {
        $config = $this->getConfig($adminId);

        if (!$config || !$config['is_enabled']) {
            return ['success' => false, 'error' => 'OTP non activé'];
        }

        if (!$config['sms_gateway_id']) {
            return ['success' => false, 'error' => 'Aucune gateway SMS configurée pour OTP'];
        }

        // Expirer les OTP en attente pour ce numéro+admin
        $this->pdo->prepare(
            "UPDATE otp_verifications SET status = 'expired' WHERE phone = ? AND admin_id = ? AND status = 'pending'"
        )->execute([$phone, $adminId]);

        // Générer le code OTP
        $otpLength = (int)$config['otp_length'];
        $otpCode = $this->generateCode($otpLength);
        $expirySeconds = (int)$config['otp_expiry_seconds'];

        // Stocker l'OTP
        $stmt = $this->pdo->prepare(
            "INSERT INTO otp_verifications (admin_id, phone, otp_code, voucher_username, voucher_password, ip_address, mac_address, user_agent, status, expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL ? SECOND))"
        );
        $stmt->execute([$adminId, $phone, $otpCode, $voucherUsername, $voucherPassword, $ip, $mac, $userAgent, $expirySeconds]);

        // Préparer le message SMS
        $message = $config['sms_template'] ?: 'Votre code OTP: {{otp_code}}';
        $message = str_replace('{{otp_code}}', $otpCode, $message);

        // Récupérer le nom de l'entreprise depuis les settings
        $companyName = $this->getCompanyName($adminId);
        $message = str_replace('{{company_name}}', $companyName, $message);

        // Remplacer la durée de validité
        $expiryLabel = $this->getExpiryLabel($expirySeconds);
        $message = str_replace('{{expiry_duration}}', $expiryLabel, $message);

        // Envoyer le SMS
        $smsResult = $this->smsService->sendSms(
            (int)$config['sms_gateway_id'],
            $phone,
            $message,
            'otp_' . $otpCode
        );

        if (!$smsResult['success']) {
            return ['success' => false, 'error' => 'Erreur envoi SMS: ' . ($smsResult['error'] ?? 'Inconnu')];
        }

        return [
            'success' => true,
            'message' => 'Code OTP envoyé',
            'expires_in' => $expirySeconds,
        ];
    }

    /**
     * Vérifier un code OTP
     */
    public function verifyOtp(int $adminId, string $phone, string $code): array
    {
        // Chercher un OTP en attente correspondant
        $stmt = $this->pdo->prepare(
            "SELECT * FROM otp_verifications
             WHERE admin_id = ? AND phone = ? AND otp_code = ? AND status = 'pending' AND expires_at > NOW()
             ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->execute([$adminId, $phone, $code]);
        $otp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$otp) {
            // Vérifier s'il y a un OTP expiré
            $stmtExpired = $this->pdo->prepare(
                "SELECT * FROM otp_verifications
                 WHERE admin_id = ? AND phone = ? AND otp_code = ? AND status = 'pending' AND expires_at <= NOW()
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmtExpired->execute([$adminId, $phone, $code]);
            $expired = $stmtExpired->fetch(PDO::FETCH_ASSOC);

            if ($expired) {
                $this->pdo->prepare("UPDATE otp_verifications SET status = 'expired' WHERE id = ?")->execute([$expired['id']]);
                return ['success' => false, 'error' => 'Code OTP expiré'];
            }

            // Incrémenter les tentatives du dernier OTP pending pour ce numéro
            $stmtLast = $this->pdo->prepare(
                "SELECT * FROM otp_verifications
                 WHERE admin_id = ? AND phone = ? AND status = 'pending'
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmtLast->execute([$adminId, $phone]);
            $last = $stmtLast->fetch(PDO::FETCH_ASSOC);

            if ($last) {
                $newAttempts = (int)$last['attempts'] + 1;
                if ($newAttempts >= 3) {
                    $this->pdo->prepare("UPDATE otp_verifications SET status = 'failed', attempts = ? WHERE id = ?")->execute([$newAttempts, $last['id']]);
                    return ['success' => false, 'error' => 'Trop de tentatives. Demandez un nouveau code.'];
                }
                $this->pdo->prepare("UPDATE otp_verifications SET attempts = ? WHERE id = ?")->execute([$newAttempts, $last['id']]);
            }

            return ['success' => false, 'error' => 'Code OTP invalide'];
        }

        // Vérifier les tentatives
        if ((int)$otp['attempts'] >= 3) {
            $this->pdo->prepare("UPDATE otp_verifications SET status = 'failed' WHERE id = ?")->execute([$otp['id']]);
            return ['success' => false, 'error' => 'Trop de tentatives. Demandez un nouveau code.'];
        }

        // OTP valide - marquer comme vérifié
        $this->pdo->prepare(
            "UPDATE otp_verifications SET status = 'verified', verified_at = NOW() WHERE id = ?"
        )->execute([$otp['id']]);

        // Construire l'URL de redirection
        $config = $this->getConfig($adminId);
        $hotspotDns = $config['hotspot_dns'] ?? '';
        $redirectUrl = '';

        if ($hotspotDns) {
            $redirectUrl = 'http://' . $hotspotDns . '/login?username=' . urlencode($otp['voucher_username'])
                . '&password=' . urlencode($otp['voucher_password'])
                . '&dst=http://www.google.com';
        }

        return [
            'success' => true,
            'message' => 'OTP vérifié avec succès',
            'redirect_url' => $redirectUrl,
            'voucher_username' => $otp['voucher_username'],
            'voucher_password' => $otp['voucher_password'],
        ];
    }

    /**
     * Historique des vérifications OTP
     */
    public function getHistory(int $adminId, int $page = 1, int $perPage = 20, ?string $search = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $offset = ($page - 1) * $perPage;
        $where = 'WHERE admin_id = ?';
        $params = [$adminId];

        if ($search) {
            $where .= ' AND (phone LIKE ? OR voucher_username LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        if ($dateFrom) {
            $where .= ' AND DATE(created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where .= ' AND DATE(created_at) <= ?';
            $params[] = $dateTo;
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM otp_verifications $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $this->pdo->prepare(
            "SELECT id, phone, voucher_username, ip_address, mac_address, user_agent, status, attempts, expires_at, verified_at, created_at
             FROM otp_verifications $where ORDER BY created_at DESC LIMIT ? OFFSET ?"
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ];
    }

    /**
     * Statistiques OTP
     */
    public function getStats(int $adminId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'verified') as verified,
                SUM(status = 'failed') as failed,
                SUM(status = 'expired') as expired,
                SUM(status = 'pending') as pending
             FROM otp_verifications WHERE admin_id = ?"
        );
        $stmt->execute([$adminId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        // Stats aujourd'hui
        $stmtToday = $this->pdo->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'verified') as verified,
                SUM(status = 'failed') as failed
             FROM otp_verifications WHERE admin_id = ? AND DATE(created_at) = CURDATE()"
        );
        $stmtToday->execute([$adminId]);
        $today = $stmtToday->fetch(PDO::FETCH_ASSOC);

        // Top 10 clients récurrents
        $stmtTop = $this->pdo->prepare(
            "SELECT phone, COUNT(*) as total_requests,
                    SUM(status = 'verified') as verified,
                    SUM(status = 'failed') as failed,
                    MAX(created_at) as last_seen
             FROM otp_verifications WHERE admin_id = ?
             GROUP BY phone ORDER BY total_requests DESC LIMIT 10"
        );
        $stmtTop->execute([$adminId]);
        $topClients = $stmtTop->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => (int)$stats['total'],
            'verified' => (int)$stats['verified'],
            'failed' => (int)$stats['failed'],
            'expired' => (int)$stats['expired'],
            'pending' => (int)$stats['pending'],
            'today_total' => (int)$today['total'],
            'today_verified' => (int)$today['verified'],
            'today_failed' => (int)$today['failed'],
            'top_clients' => $topClients,
        ];
    }

    /**
     * Supprimer des vérifications OTP
     */
    public function deleteVerifications(int $adminId, array $ids): int
    {
        if (empty($ids)) return 0;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "DELETE FROM otp_verifications WHERE admin_id = ? AND id IN ($placeholders)"
        );
        $params = array_merge([$adminId], array_map('intval', $ids));
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Exporter l'historique OTP en CSV
     */
    public function exportHistory(int $adminId, ?string $search = null, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $where = 'WHERE admin_id = ?';
        $params = [$adminId];

        if ($search) {
            $where .= ' AND (phone LIKE ? OR voucher_username LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }
        if ($dateFrom) {
            $where .= ' AND DATE(created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where .= ' AND DATE(created_at) <= ?';
            $params[] = $dateTo;
        }

        $stmt = $this->pdo->prepare(
            "SELECT phone, voucher_username, status, attempts, ip_address, mac_address, user_agent, expires_at, verified_at, created_at
             FROM otp_verifications $where ORDER BY created_at DESC"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Valider le code du jour pour l'inscription
     */
    public function validateDailyCode(int $adminId, string $code): bool
    {
        $config = $this->getConfig($adminId);
        if (!$config) return false;

        if (!empty($config['daily_code_auto_rotate'])) {
            $expected = strtoupper(substr(md5(date('Y-m-d') . $adminId . 'radius_reg_salt'), 0, 6));
        } else {
            $expected = $config['daily_code'] ?? '';
        }

        if (empty($expected)) return false;

        return strcasecmp(trim($code), trim($expected)) === 0;
    }

    /**
     * Récupérer le code du jour actuel (pour affichage admin)
     */
    public function getCurrentDailyCode(int $adminId): string
    {
        $config = $this->getConfig($adminId);
        if (!$config) return '';

        if (!empty($config['daily_code_auto_rotate'])) {
            return strtoupper(substr(md5(date('Y-m-d') . $adminId . 'radius_reg_salt'), 0, 6));
        }

        return $config['daily_code'] ?? '';
    }

    /**
     * Générer un code OTP numérique
     */
    private function generateCode(int $length): string
    {
        $min = (int)pow(10, $length - 1);
        $max = (int)pow(10, $length) - 1;
        return (string)random_int($min, $max);
    }

    /**
     * Formater la durée d'expiration en texte lisible
     */
    private function getExpiryLabel(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds . 's';
        }
        $minutes = (int)floor($seconds / 60);
        return $minutes . ' min';
    }

    /**
     * Récupérer le nom de l'entreprise
     */
    private function getCompanyName(int $adminId): string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'company_name' AND admin_id = ?");
        $stmt->execute([$adminId]);
        $result = $stmt->fetchColumn();
        return $result ?: 'WiFi Zone';
    }
}
