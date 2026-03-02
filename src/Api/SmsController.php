<?php

class SmsController
{
    private $db;
    private $auth;
    private SmsService $smsService;

    public function __construct($db, $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->smsService = new SmsService($db->getPdo());
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * Returns available provider definitions (fields, names) for frontend forms
     */
    public function getProviders(): void
    {
        $this->auth->requireRole('admin');
        jsonResponse(['providers' => SmsService::getProviderDefinitions()]);
    }

    /**
     * List configured gateways for current admin (auto-provisions if needed)
     */
    public function getGateways(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $this->ensureGatewaysProvisioned($adminId);

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM sms_gateways WHERE admin_id = ? ORDER BY name ASC");
        $stmt->execute([$adminId]);
        $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Mask secret fields
        $providers = SmsService::getProviderDefinitions();
        foreach ($gateways as &$gw) {
            $config = json_decode($gw['config'], true) ?: [];
            $providerDef = $providers[$gw['provider_code']] ?? null;
            if ($providerDef) {
                foreach ($providerDef['fields'] as $field) {
                    if (!empty($field['secret']) && !empty($config[$field['key']])) {
                        $val = $config[$field['key']];
                        $config[$field['key'] . '_masked'] = substr($val, 0, 4) . '...' . substr($val, -4);
                        unset($config[$field['key']]);
                    }
                }
            }
            $gw['config'] = $config;
            $gw['provider_name'] = $providerDef['name'] ?? $gw['provider_code'];
            $gw['supports_balance'] = $providerDef['supports_balance'] ?? false;
            $gw['is_platform'] = !empty($providerDef['is_platform']);

            // For platform provider, show CSMS balance
            if ($gw['provider_code'] === 'platform') {
                $stmtBal = $pdo->prepare("SELECT sms_credit_balance FROM users WHERE id = ?");
                $stmtBal->execute([$adminId]);
                $gw['balance'] = (float)$stmtBal->fetchColumn();
                $gw['balance_unit'] = 'CSMS';
            }
        }

        jsonResponse(['gateways' => $gateways]);
    }

    /**
     * Get a single gateway
     */
    public function getGateway($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM sms_gateways WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        $gw = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gw) {
            jsonError('Gateway non trouvée', 404);
            return;
        }

        $config = json_decode($gw['config'], true) ?: [];
        $providers = SmsService::getProviderDefinitions();
        $providerDef = $providers[$gw['provider_code']] ?? null;

        if ($providerDef) {
            foreach ($providerDef['fields'] as $field) {
                if (!empty($field['secret']) && !empty($config[$field['key']])) {
                    $val = $config[$field['key']];
                    $config[$field['key'] . '_masked'] = substr($val, 0, 4) . '...' . substr($val, -4);
                    unset($config[$field['key']]);
                }
            }
        }
        $gw['config'] = $config;

        jsonResponse(['gateway' => $gw]);
    }

    /**
     * Update gateway configuration
     */
    public function updateGateway($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT * FROM sms_gateways WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            jsonError('Gateway non trouvée', 404);
            return;
        }

        // Merge config: keep existing secret values if masked ones sent
        $existingConfig = json_decode($existing['config'], true) ?: [];
        $newConfig = $data['config'] ?? [];

        $providers = SmsService::getProviderDefinitions();
        $providerDef = $providers[$existing['provider_code']] ?? null;

        if ($providerDef) {
            foreach ($providerDef['fields'] as $field) {
                $key = $field['key'];
                if (!empty($field['secret'])) {
                    // If client sent masked value or empty, keep existing
                    if (!isset($newConfig[$key]) || $newConfig[$key] === '' || str_contains($newConfig[$key], '...')) {
                        $newConfig[$key] = $existingConfig[$key] ?? '';
                    }
                }
            }
        }

        $stmt = $pdo->prepare(
            "UPDATE sms_gateways SET config = ?, updated_at = NOW() WHERE id = ? AND admin_id = ?"
        );
        $stmt->execute([json_encode($newConfig), $id, $adminId]);

        jsonSuccess(null, __('sms.config_saved'));
    }

    /**
     * Toggle gateway active status
     */
    public function toggleGateway($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT is_active FROM sms_gateways WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        $gw = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$gw) {
            jsonError('Gateway non trouvée', 404);
            return;
        }

        $newStatus = $gw['is_active'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE sms_gateways SET is_active = ?, updated_at = NOW() WHERE id = ? AND admin_id = ?");
        $stmt->execute([$newStatus, $id, $adminId]);

        jsonSuccess(['is_active' => (bool)$newStatus]);
    }

    /**
     * Send test SMS
     */
    public function testGateway($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        $phone = trim($data['phone'] ?? '');
        $message = trim($data['message'] ?? '');

        if (empty($phone)) {
            jsonError('Numéro de téléphone requis');
            return;
        }
        if (empty($message)) {
            jsonError('Message requis');
            return;
        }

        // Verify gateway belongs to admin
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT id FROM sms_gateways WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        if (!$stmt->fetch()) {
            jsonError('Gateway non trouvée', 404);
            return;
        }

        $result = $this->smsService->sendSms($id, $phone, $message, 'test_' . uniqid());

        if ($result['success']) {
            jsonSuccess([
                'message_id' => $result['message_id'] ?? null,
                'credits' => $result['credits'] ?? null,
            ], __('sms.test_success'));
        } else {
            jsonError($result['error'] ?? __('sms.test_failed'));
        }
    }

    /**
     * Check balance for a gateway
     */
    public function getBalance($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT id FROM sms_gateways WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        if (!$stmt->fetch()) {
            jsonError('Gateway non trouvée', 404);
            return;
        }

        $result = $this->smsService->checkBalance($id);

        if ($result['success']) {
            jsonSuccess(['balance' => $result['balance']]);
        } else {
            jsonError($result['error'] ?? 'Impossible de vérifier le solde');
        }
    }

    /**
     * Get SMS send history with pagination
     */
    public function getHistory(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $status = $_GET['status'] ?? '';

        $pdo = $this->db->getPdo();

        $where = "n.admin_id = ?";
        $params = [$adminId];

        if ($status && in_array($status, ['sent', 'failed', 'pending'])) {
            $where .= " AND n.status = ?";
            $params[] = $status;
        }

        // Count total
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_notifications n WHERE $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        // Fetch page
        $stmt = $pdo->prepare(
            "SELECT n.*, g.name as gateway_name, g.provider_code
             FROM sms_notifications n
             LEFT JOIN sms_gateways g ON n.gateway_id = g.id
             WHERE $where
             ORDER BY n.created_at DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse([
            'history' => $history,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'pages' => max(1, ceil($total / $perPage)),
            ],
        ]);
    }

    /**
     * Get SMS statistics
     */
    public function getStats(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(status = 'sent') as sent,
                SUM(status = 'failed') as failed,
                SUM(status = 'pending') as pending
             FROM sms_notifications
             WHERE admin_id = ?"
        );
        $stmt->execute([$adminId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonResponse(['stats' => $stats]);
    }

    // =============================================
    // SMS TEMPLATES
    // =============================================

    /**
     * Get all templates for current admin, optionally filtered by category
     */
    public function getTemplates(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $this->ensureTemplatesProvisioned($adminId);

        $pdo = $this->db->getPdo();
        $category = $_GET['category'] ?? '';

        $where = "admin_id = ?";
        $params = [$adminId];

        if ($category && in_array($category, ['pppoe', 'hotspot'])) {
            $where .= " AND category = ?";
            $params[] = $category;
        }

        $stmt = $pdo->prepare(
            "SELECT * FROM sms_templates WHERE $where ORDER BY category, event_type, days_before DESC"
        );
        $stmt->execute($params);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['templates' => $templates]);
    }

    /**
     * Create a new SMS template
     */
    public function createTemplate(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        $required = ['name', 'category', 'event_type', 'message_template'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonError("Le champ '$field' est requis", 400);
                return;
            }
        }

        if (!in_array($data['category'], ['pppoe', 'hotspot'])) {
            jsonError('Catégorie invalide', 400);
            return;
        }

        try {
            $pdo = $this->db->getPdo();
            $stmt = $pdo->prepare(
                "INSERT INTO sms_templates (name, description, category, event_type, message_template, days_before, is_active, admin_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['category'],
                $data['event_type'],
                $data['message_template'],
                (int)($data['days_before'] ?? 0),
                (int)($data['is_active'] ?? 1),
                $adminId,
            ]);

            jsonSuccess(['id' => $pdo->lastInsertId()], __('sms.template_created'));
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                jsonError(__('sms.template_exists'), 400);
            } else {
                jsonError('Erreur: ' . $e->getMessage(), 500);
            }
        }
    }

    /**
     * Update an existing SMS template
     */
    public function updateTemplate($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("SELECT id FROM sms_templates WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);
        if (!$stmt->fetch()) {
            jsonError('Template non trouvé', 404);
            return;
        }

        try {
            $stmt = $pdo->prepare(
                "UPDATE sms_templates
                 SET name = ?, description = ?, event_type = ?, message_template = ?,
                     days_before = ?, is_active = ?, updated_at = NOW()
                 WHERE id = ? AND admin_id = ?"
            );
            $stmt->execute([
                $data['name'],
                $data['description'] ?? '',
                $data['event_type'],
                $data['message_template'],
                (int)($data['days_before'] ?? 0),
                (int)($data['is_active'] ?? 1),
                $id,
                $adminId,
            ]);

            jsonSuccess(null, __('sms.template_updated'));
        } catch (\PDOException $e) {
            jsonError('Erreur: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an SMS template
     */
    public function deleteTemplate($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("DELETE FROM sms_templates WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);

        if ($stmt->rowCount() === 0) {
            jsonError('Template non trouvé', 404);
            return;
        }

        jsonSuccess(null, __('sms.template_deleted'));
    }

    /**
     * Toggle template active/inactive
     */
    public function toggleTemplate($params): void
    {
        $this->auth->requireRole('admin');
        $id = (int)($params['id'] ?? 0);
        $adminId = $this->getAdminId();

        $pdo = $this->db->getPdo();

        $stmt = $pdo->prepare("UPDATE sms_templates SET is_active = NOT is_active, updated_at = NOW() WHERE id = ? AND admin_id = ?");
        $stmt->execute([$id, $adminId]);

        if ($stmt->rowCount() === 0) {
            jsonError('Template non trouvé', 404);
            return;
        }

        $stmt = $pdo->prepare("SELECT is_active FROM sms_templates WHERE id = ?");
        $stmt->execute([$id]);
        $tpl = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonSuccess(['is_active' => (bool)$tpl['is_active']]);
    }

    /**
     * Get available template variables by category
     */
    public function getVariables(): void
    {
        $this->auth->requireRole('admin');
        $category = $_GET['category'] ?? 'pppoe';

        $pppoeVars = [
            'customer_name' => 'Nom du client',
            'username' => 'Identifiant PPPoE',
            'password' => 'Mot de passe',
            'profile_name' => 'Nom du forfait',
            'profile_price' => 'Prix du forfait',
            'expiration_date' => 'Date d\'expiration',
            'days_remaining' => 'Jours restants',
            'download_speed' => 'Débit descendant',
            'upload_speed' => 'Débit montant',
            'company_name' => 'Nom de l\'entreprise',
            'support_phone' => 'Téléphone support',
            'current_date' => 'Date actuelle',
        ];

        $hotspotVars = [
            'customer_name' => 'Nom du client',
            'voucher_code' => 'Code voucher',
            'profile_name' => 'Nom du forfait',
            'duration' => 'Durée du forfait',
            'hotspot_name' => 'Nom du hotspot',
            'download_speed' => 'Débit descendant',
            'upload_speed' => 'Débit montant',
            'company_name' => 'Nom de l\'entreprise',
            'support_phone' => 'Téléphone support',
            'current_date' => 'Date actuelle',
        ];

        $vars = $category === 'hotspot' ? $hotspotVars : $pppoeVars;

        $result = [];
        foreach ($vars as $key => $desc) {
            $result[] = [
                'variable' => $key,
                'description' => $desc,
                'placeholder' => '{{' . $key . '}}',
            ];
        }

        jsonResponse(['variables' => $result]);
    }

    // =============================================
    // PROVISIONING
    // =============================================

    /**
     * Auto-provision default gateways for a new admin
     */
    private function ensureGatewaysProvisioned(?int $adminId): void
    {
        if ($adminId === null) return;

        try {
            $pdo = $this->db->getPdo();

            // Check if admin already has gateways
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_gateways WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            $hasGateways = (int)$stmt->fetchColumn() > 0;

            if ($hasGateways) {
                // Even if gateways exist, ensure platform provider is provisioned
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_gateways WHERE admin_id = ? AND provider_code = 'platform'");
                $stmt->execute([$adminId]);
                if ((int)$stmt->fetchColumn() === 0) {
                    $providers = SmsService::getProviderDefinitions();
                    if (isset($providers['platform'])) {
                        $def = $providers['platform'];
                        $stmt = $pdo->prepare(
                            "INSERT IGNORE INTO sms_gateways (provider_code, name, description, config, is_active, admin_id) VALUES (?, ?, ?, ?, 0, ?)"
                        );
                        $stmt->execute(['platform', $def['name'], $def['description'] ?? '', '{}', $adminId]);
                    }
                }
                return;
            }

            // Seed all defined providers (unconfigured, inactive)
            $providers = SmsService::getProviderDefinitions();
            foreach ($providers as $code => $def) {
                $defaultConfig = [];
                foreach ($def['fields'] as $field) {
                    $defaultConfig[$field['key']] = '';
                }

                $stmt = $pdo->prepare(
                    "INSERT IGNORE INTO sms_gateways (provider_code, name, description, config, is_active, admin_id) VALUES (?, ?, ?, ?, 0, ?)"
                );
                $stmt->execute([
                    $code,
                    $def['name'],
                    $def['description'] ?? '',
                    json_encode($defaultConfig),
                    $adminId,
                ]);
            }
        } catch (\Exception $e) {
            // Silently fail - gateways will be empty but page still loads
        }
    }

    /**
     * Auto-provision default SMS templates for a new admin
     */
    private function ensureTemplatesProvisioned(?int $adminId): void
    {
        if ($adminId === null) return;

        try {
            $pdo = $this->db->getPdo();

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_templates WHERE admin_id = ?");
            $stmt->execute([$adminId]);
            if ((int)$stmt->fetchColumn() > 0) return;

            // Copy default templates (admin_id=1) for this admin, or seed defaults
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_templates WHERE admin_id = 1");
            $stmt->execute();
            $hasDefaults = (int)$stmt->fetchColumn() > 0;

            if ($hasDefaults && $adminId !== 1) {
                $stmt = $pdo->prepare(
                    "INSERT INTO sms_templates (name, description, category, event_type, message_template, days_before, is_active, admin_id)
                     SELECT name, description, category, event_type, message_template, days_before, is_active, ?
                     FROM sms_templates WHERE admin_id = 1"
                );
                $stmt->execute([$adminId]);
            }
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
