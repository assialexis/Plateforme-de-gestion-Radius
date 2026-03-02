<?php
/**
 * Controller API Bandwidth Management
 * Gestion de la bande passante RADIUS
 */

class BandwidthController
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

    private function verifyPolicyOwnership(?array $policy): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $policy !== null && isset($policy['admin_id']) && (int)$policy['admin_id'] !== $adminId) {
            jsonError(__('api.bandwidth_policy_not_found'), 404);
            exit;
        }
    }

    private function verifyScheduleOwnership(?array $schedule): void
    {
        $adminId = $this->getAdminId();
        if ($adminId !== null && $schedule !== null && isset($schedule['admin_id']) && (int)$schedule['admin_id'] !== $adminId) {
            jsonError(__('api.bandwidth_schedule_not_found'), 404);
            exit;
        }
    }

    private function getPolicyOrFail(int $id): array
    {
        $policy = $this->db->getBandwidthPolicyById($id);
        if (!$policy) {
            jsonError(__('api.bandwidth_policy_not_found'), 404);
            exit;
        }
        $this->verifyPolicyOwnership($policy);
        return $policy;
    }

    private function getScheduleOrFail(int $id): array
    {
        $schedule = $this->db->getBandwidthScheduleById($id);
        if (!$schedule) {
            jsonError(__('api.bandwidth_schedule_not_found'), 404);
            exit;
        }
        $this->verifyScheduleOwnership($schedule);
        return $schedule;
    }

    // ==========================================
    // Bandwidth Policies
    // ==========================================

    /**
     * GET /api/bandwidth/policies
     */
    public function listPolicies(): void
    {
        try {
            $adminId = $this->getAdminId();
            $this->ensureBandwidthPolicies($adminId);
            $policies = $this->db->getBandwidthPolicies($adminId);
            jsonSuccess($policies);
        } catch (Exception $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bandwidth/policies/{id}
     */
    public function showPolicy(array $params): void
    {
        $id = (int)$params['id'];

        try {
            $policy = $this->getPolicyOrFail($id);

            // Get associated RADIUS attributes
            $policy['attributes'] = $this->db->getBandwidthPolicyAttributes($id);
            jsonSuccess($policy);
        } catch (Exception $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bandwidth/policies
     */
    public function createPolicy(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.bandwidth_policy_name_required'), 400);
        }

        $adminId = $this->getAdminId();
        $data['admin_id'] = $adminId;

        try {
            $id = $this->db->createBandwidthPolicy($data);
            $policy = $this->db->getBandwidthPolicyById($id);

            // Generate RADIUS attributes
            $this->generateRadiusAttributes($id, $data);

            jsonSuccess($policy, __('api.bandwidth_policy_created'));
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonError(__('api.bandwidth_policy_name_exists'), 400);
            }
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/bandwidth/policies/{id}
     */
    public function updatePolicy(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $this->getPolicyOrFail($id);

        if (empty($data['name'])) {
            jsonError(__('api.bandwidth_policy_name_required'), 400);
        }

        try {
            $this->db->updateBandwidthPolicy($id, $data);

            // Regenerate RADIUS attributes
            $this->db->deleteBandwidthPolicyAttributes($id);
            $this->generateRadiusAttributes($id, $data);

            $policy = $this->db->getBandwidthPolicyById($id);
            jsonSuccess($policy, __('api.bandwidth_policy_updated'));
        } catch (Exception $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/bandwidth/policies/{id}
     */
    public function deletePolicy(array $params): void
    {
        $id = (int)$params['id'];

        $this->getPolicyOrFail($id);

        try {
            // Check if policy is used in schedules
            if ($this->db->isPolicyUsedInSchedules($id)) {
                jsonError(__('api.bandwidth_policy_used_in_schedules'), 400);
            }

            $this->db->deleteBandwidthPolicy($id);
            jsonSuccess(null, __('api.bandwidth_policy_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.error_deleting') . ': ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // Bandwidth Schedules
    // ==========================================

    /**
     * GET /api/bandwidth/schedules
     */
    public function listSchedules(): void
    {
        try {
            $adminId = $this->getAdminId();
            $schedules = $this->db->getBandwidthSchedules($adminId);
            jsonSuccess($schedules);
        } catch (Exception $e) {
            jsonError(__('api.error_loading') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bandwidth/schedules
     */
    public function createSchedule(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.bandwidth_schedule_name_required'), 400);
        }
        if (empty($data['default_policy_id']) || empty($data['scheduled_policy_id'])) {
            jsonError(__('api.bandwidth_both_policies_required'), 400);
        }
        if (empty($data['start_time']) || empty($data['end_time'])) {
            jsonError(__('api.bandwidth_times_required'), 400);
        }

        // Verify that referenced policies belong to this admin
        $this->getPolicyOrFail((int)$data['default_policy_id']);
        $this->getPolicyOrFail((int)$data['scheduled_policy_id']);

        $adminId = $this->getAdminId();
        $data['admin_id'] = $adminId;

        try {
            $id = $this->db->createBandwidthSchedule($data);
            $schedule = $this->db->getBandwidthScheduleById($id);
            jsonSuccess($schedule, __('api.bandwidth_schedule_created'));
        } catch (Exception $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/bandwidth/schedules/{id}
     */
    public function updateSchedule(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $this->getScheduleOrFail($id);

        // Verify that referenced policies belong to this admin
        if (!empty($data['default_policy_id'])) {
            $this->getPolicyOrFail((int)$data['default_policy_id']);
        }
        if (!empty($data['scheduled_policy_id'])) {
            $this->getPolicyOrFail((int)$data['scheduled_policy_id']);
        }

        try {
            $this->db->updateBandwidthSchedule($id, $data);
            $schedule = $this->db->getBandwidthScheduleById($id);
            jsonSuccess($schedule, __('api.bandwidth_schedule_updated'));
        } catch (Exception $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/bandwidth/schedules/{id}
     */
    public function deleteSchedule(array $params): void
    {
        $id = (int)$params['id'];

        $this->getScheduleOrFail($id);

        try {
            $this->db->deleteBandwidthSchedule($id);
            jsonSuccess(null, __('api.bandwidth_schedule_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.error_deleting') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bandwidth/schedules/{id}/toggle
     */
    public function toggleSchedule(array $params): void
    {
        $id = (int)$params['id'];

        $this->getScheduleOrFail($id);

        try {
            $this->db->toggleBandwidthSchedule($id);
            jsonSuccess(null, __('api.bandwidth_schedule_toggled'));
        } catch (Exception $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    // ==========================================
    // Auto-provisioning
    // ==========================================

    private function ensureBandwidthPolicies(?int $adminId): void
    {
        if ($adminId === null) return;

        $pdo = $this->db->getPdo();

        // Vérifier si le provisioning a déjà été fait pour cet admin
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM settings WHERE setting_key = 'bandwidth_policies_provisioned' AND admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) return;

        // Marquer comme provisionné
        $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value, description, admin_id) VALUES ('bandwidth_policies_provisioned', '1', 'Auto-provisioning des politiques de bande passante effectué', ?)")->execute([$adminId]);

        // Vérifier si l'admin a déjà des politiques (via migration/seed)
        $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM bandwidth_policies WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        if ((int)$stmt->fetch()['cnt'] > 0) return;

        // Insert default policies for this admin
        $defaults = [
            ['Basique 1M', 'Connexion basique 1 Mbps symétrique', 1048576, 1048576, 8, '#6B7280'],
            ['Standard 2M', 'Connexion standard 2 Mbps / 1 Mbps', 2097152, 1048576, 6, '#3B82F6'],
            ['Premium 5M', 'Connexion premium 5 Mbps / 2 Mbps', 5242880, 2097152, 4, '#8B5CF6'],
            ['Business 10M', 'Connexion business 10 Mbps / 5 Mbps', 10485760, 5242880, 2, '#F59E0B'],
            ['Illimité', 'Sans limite de bande passante', 0, 0, 1, '#10B981'],
            ['Heures creuses', 'Vitesse réduite pour heures de pointe', 524288, 262144, 8, '#EF4444'],
        ];

        $insertStmt = $pdo->prepare("
            INSERT IGNORE INTO bandwidth_policies (name, description, download_rate, upload_rate, priority, color, admin_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($defaults as $d) {
            $insertStmt->execute([$d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $adminId]);
        }

        // Generate MikroTik RADIUS attributes for new policies
        $stmt = $pdo->prepare("SELECT id, download_rate, upload_rate FROM bandwidth_policies WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $policies = $stmt->fetchAll();

        foreach ($policies as $p) {
            $mikrotikRate = $this->formatMikrotikRate((int)$p['download_rate'], (int)$p['upload_rate']);
            if ($mikrotikRate) {
                $this->db->addBandwidthPolicyAttribute((int)$p['id'], 'Mikrotik-Rate-Limit', $mikrotikRate, ':=', 'Mikrotik');
            }
        }
    }

    // ==========================================
    // Helpers
    // ==========================================

    /**
     * Generate RADIUS attributes for a policy
     */
    private function generateRadiusAttributes(int $policyId, array $data): void
    {
        $downloadRate = $data['download_rate'] ?? 0;
        $uploadRate = $data['upload_rate'] ?? 0;

        // MikroTik Rate-Limit format: rx/tx [burst-rx/burst-tx threshold-rx/threshold-tx burst-time priority]
        $mikrotikRate = $this->formatMikrotikRate($downloadRate, $uploadRate);

        if ($mikrotikRate) {
            // Add burst if enabled
            if (!empty($data['burst_download_rate']) && !empty($data['burst_upload_rate'])) {
                $burstDownload = $this->formatSpeed($data['burst_download_rate']);
                $burstUpload = $this->formatSpeed($data['burst_upload_rate']);
                $thresholdDown = $this->formatSpeed($downloadRate * 0.8); // 80% of normal
                $thresholdUp = $this->formatSpeed($uploadRate * 0.8);
                $burstTime = $data['burst_time'] ?? 10;

                $mikrotikRate .= " {$burstDownload}/{$burstUpload} {$thresholdDown}/{$thresholdUp} {$burstTime}";
            }

            $this->db->addBandwidthPolicyAttribute($policyId, 'Mikrotik-Rate-Limit', $mikrotikRate, ':=', 'Mikrotik');
        }

        // WISPr attributes (standard)
        if ($downloadRate > 0) {
            $this->db->addBandwidthPolicyAttribute($policyId, 'WISPr-Bandwidth-Max-Down', (string)$downloadRate, ':=', null);
        }
        if ($uploadRate > 0) {
            $this->db->addBandwidthPolicyAttribute($policyId, 'WISPr-Bandwidth-Max-Up', (string)$uploadRate, ':=', null);
        }
    }

    /**
     * Format speed for MikroTik (e.g., 5M, 512k)
     */
    private function formatSpeed(int $bps): string
    {
        if ($bps <= 0) return '';
        if ($bps >= 1073741824) return round($bps / 1073741824) . 'G';
        if ($bps >= 1048576) return round($bps / 1048576) . 'M';
        if ($bps >= 1024) return round($bps / 1024) . 'k';
        return $bps . '';
    }

    /**
     * Format MikroTik Rate-Limit string
     */
    private function formatMikrotikRate(int $downloadRate, int $uploadRate): string
    {
        if ($downloadRate <= 0 && $uploadRate <= 0) {
            return '';
        }

        $download = $this->formatSpeed($downloadRate);
        $upload = $this->formatSpeed($uploadRate);

        return "{$download}/{$upload}";
    }
}
