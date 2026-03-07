<?php
/**
 * Controller API Vouchers
 */

class VoucherController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private ?NodePushService $pushService;

    public function __construct(RadiusDatabase $db, AuthService $auth, ?NodePushService $pushService = null)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->pushService = $pushService;
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /api/vouchers
     */
    public function index(): void
    {
        $filters = [
            'status' => get('status'),
            'search' => get('search'),
            'profile_id' => get('profile_id'),
            'batch_id' => get('batch_id'),
            'type' => get('type'),
            'zone' => get('zone'),
            'notes' => get('notes'),
        ];

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 20)));

        $adminId = $this->getAdminId();
        $result = $this->db->getAllVouchers($filters, $page, $perPage, $adminId);
        jsonSuccess($result);
    }

    /**
     * GET /api/vouchers/notes
     */
    public function notes(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $sql = "SELECT DISTINCT notes FROM vouchers WHERE notes IS NOT NULL AND notes != ''";
        $params = [];

        if ($adminId !== null) {
            $sql .= " AND admin_id = ?";
            $params[] = $adminId;
        }

        $sql .= " ORDER BY notes ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $notes = $stmt->fetchAll(PDO::FETCH_COLUMN);

        jsonSuccess($notes);
    }

    /**
     * GET /api/vouchers/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $voucher = $this->db->getVoucherById($id);

        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        // Ajouter les sessions actives
        $voucher['active_sessions'] = $this->db->countActiveSessions($id);

        jsonSuccess($voucher);
    }

    /**
     * POST /api/vouchers
     */
    public function store(): void
    {
        $data = getJsonBody();

        if (empty($data['username'])) {
            jsonError(__('api.voucher_username_required'), 400);
        }

        // Vérifier si le voucher existe déjà
        if ($this->db->getVoucherByUsername($data['username'])) {
            jsonError(__('api.voucher_already_exists'), 400);
        }

        // Si pas de password fourni, utiliser le username (voucher classique)
        if (empty($data['password'])) {
            $data['password'] = $data['username'];
        }

        // Appliquer le profil si spécifié
        if (!empty($data['profile_id'])) {
            $profile = $this->db->getProfileById((int)$data['profile_id']);
            if ($profile) {
                $data = array_merge([
                    'time_limit' => $profile['time_limit'],
                    'data_limit' => $profile['data_limit'],
                    'upload_speed' => $profile['upload_speed'],
                    'download_speed' => $profile['download_speed'],
                    'price' => $profile['price'],
                    'simultaneous_use' => $profile['simultaneous_use'],
                ], $data);
            }
        }

        // Zone: utiliser la sélection du formulaire, sinon celle du profil
        if (empty($data['zone_id']) && !empty($profile['zone_id'])) {
            $data['zone_id'] = (int)$profile['zone_id'];
        } elseif (!empty($data['zone_id'])) {
            $data['zone_id'] = (int)$data['zone_id'];
        }

        $data['created_by'] = $_SESSION['admin_id'] ?? null;
        $data['admin_id'] = $this->getAdminId();

        // Infos client optionnelles
        $data['customer_name'] = !empty($data['customer_name']) ? trim($data['customer_name']) : null;
        $data['customer_phone'] = !empty($data['customer_phone']) ? trim($data['customer_phone']) : null;

        // Auto-générer le commentaire si vide
        if (empty($data['notes'])) {
            $profileName = '';
            if (!empty($data['profile_id'])) {
                $profile = $this->db->getProfileById((int)$data['profile_id']);
                $profileName = $profile['name'] ?? '';
            }
            $data['notes'] = date('d/m/Y H:i') . ' - ' . ($profileName ?: 'Sans profil');
        }

        $data['vendeur_id'] = !empty($data['vendeur_id']) ? (int)$data['vendeur_id'] : null;

        try {
            $id = $this->db->createVoucher($data);
            $voucher = $this->db->getVoucherById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('created', $voucher);
            }

            // Ajouter has_password et plain_password pour le retour
            $voucher['has_password'] = $voucher['username'] !== $voucher['password'];
            $voucher['plain_password'] = $voucher['password'];

            $isTicket = !empty($data['password']) && $data['password'] !== $data['username'];
            jsonSuccess($voucher, $isTicket ? __('api.ticket_created') : __('api.voucher_created'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vouchers/generate
     */
    public function generate(): void
    {
        $data = getJsonBody();

        $count = (int)($data['count'] ?? 1);
        if ($count < 1 || $count > 1000) {
            jsonError(__('api.voucher_count_invalid'), 400);
        }

        $type = $data['type'] ?? 'voucher'; // 'voucher' ou 'ticket'
        $prefix = $data['prefix'] ?? '';
        if ($type !== 'ticket') {
            $prefix = strtoupper($prefix);
        }
        $length = max(4, min(8, (int)($data['length'] ?? 8)));
        $passwordLength = (int)($data['password_length'] ?? 6);
        $passwordType = $data['password_type'] ?? 'alphanumeric';
        $batchId = generateUUID();

        // Récupérer les paramètres du profil si spécifié
        $profileData = [];
        if (!empty($data['profile_id'])) {
            $profile = $this->db->getProfileById((int)$data['profile_id']);
            if ($profile) {
                $profileData = [
                    'profile_id' => $profile['id'],
                    'time_limit' => $profile['time_limit'],
                    'data_limit' => $profile['data_limit'],
                    'upload_speed' => $profile['upload_speed'],
                    'download_speed' => $profile['download_speed'],
                    'price' => $profile['price'],
                    'simultaneous_use' => $profile['simultaneous_use'],
                ];
            }
        }

        // Auto-générer le commentaire si vide
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        if (empty($notes)) {
            $profileName = '';
            if (!empty($data['profile_id'])) {
                $profile = $this->db->getProfileById((int)$data['profile_id']);
                $profileName = $profile['name'] ?? '';
            }
            $notes = date('d/m/Y H:i') . ' - ' . ($profileName ?: 'Sans profil');
        }

        $vouchers = [];
        $attempts = 0;
        $maxAttempts = $count * 3;

        while (count($vouchers) < $count && $attempts < $maxAttempts) {
            $codeType = $data['code_type'] ?? ($type === 'ticket' ? 'mix' : 'mix_capital');
            $code = generateVoucherCode($prefix, $length, $codeType);
            $attempts++;

            // Vérifier si le code existe déjà
            if ($this->db->getVoucherByUsername($code)) {
                continue;
            }

            // Générer le password selon le type
            if ($type === 'ticket') {
                $password = $this->generatePassword($passwordLength, $codeType);
            }
            else {
                $password = $code; // Pour les vouchers, password = username
            }

            // Zone: utiliser la sélection du formulaire, sinon celle du profil
            $zoneId = !empty($data['zone_id']) ? (int)$data['zone_id'] : ($profile['zone_id'] ?? null);

            $voucherData = array_merge($profileData, [
                'username' => $code,
                'password' => $password,
                'plain_password' => $password,
                'time_limit' => $data['time_limit'] ?? $profileData['time_limit'] ?? null,
                'data_limit' => $data['data_limit'] ?? $profileData['data_limit'] ?? null,
                'upload_speed' => $data['upload_speed'] ?? $profileData['upload_speed'] ?? null,
                'download_speed' => $data['download_speed'] ?? $profileData['download_speed'] ?? null,
                'price' => $data['price'] ?? $profileData['price'] ?? 0,
                'simultaneous_use' => $data['simultaneous_use'] ?? $profileData['simultaneous_use'] ?? 1,
                'valid_until' => $data['valid_until'] ?? null,
                'zone_id' => $zoneId,
                'batch_id' => $batchId,
                'created_by' => $_SESSION['admin_id'] ?? null,
                'admin_id' => $this->getAdminId(),
                'vendeur_id' => !empty($data['vendeur_id']) ? (int)$data['vendeur_id'] : null,
                'notes' => $notes,
            ]);

            $vouchers[] = $voucherData;
        }

        if (count($vouchers) < $count) {
            jsonError(__('api.voucher_generate_unique_failed'), 500);
        }

        try {
            $created = $this->db->createVouchersBatch($vouchers);

            // Ajouter plain_password aux résultats pour l'affichage
            foreach ($created as &$v) {
                $found = array_filter($vouchers, fn($vd) => $vd['username'] === $v['username']);
                if (!empty($found)) {
                    $v['plain_password'] = reset($found)['plain_password'];
                }
            }

            jsonSuccess([
                'batch_id' => $batchId,
                'count' => count($created),
                'vouchers' => $created,
            ], $type === 'ticket' ? __('api.tickets_generated') : __('api.vouchers_generated'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_generate_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * Générer un password aléatoire
     */
    private function generatePassword(int $length, string $type): string
    {
        $charSets = [
            'number'       => '0123456789',
            'letter'       => 'abcdefghjkmnpqrstuvwxyz',
            'capital'      => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
            'mix'          => 'abcdefghjkmnpqrstuvwxyz23456789',
            'mix_capital'  => 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            'alphanumeric' => 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789',
            // Rétrocompatibilité anciens types
            'numeric'      => '0123456789',
            'alpha'        => 'ABCDEFGHJKLMNPQRSTUVWXYZ',
        ];
        $chars = $charSets[$type] ?? $charSets['mix_capital'];

        $password = '';
        $charsLength = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $charsLength - 1)];
        }
        return $password;
    }

    /**
     * PUT /api/vouchers/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $voucher = $this->db->getVoucherById($id);
        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        try {
            $this->db->updateVoucher($id, $data);
            $voucher = $this->db->getVoucherById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('updated', $voucher);
            }

            jsonSuccess($voucher, __('api.voucher_updated'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/vouchers/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $voucher = $this->db->getVoucherById($id);
        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        try {
            // Push temps réel vers les nœuds RADIUS (avant suppression)
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('deleted', $voucher);
            }

            $this->db->deleteVoucher($id);
            jsonSuccess(null, __('api.voucher_deleted'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vouchers/{id}/reset
     */
    public function reset(array $params): void
    {
        $id = (int)$params['id'];

        $voucher = $this->db->getVoucherById($id);
        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        try {
            $this->db->resetVoucher($id);
            $voucher = $this->db->getVoucherById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('updated', $voucher);
            }

            jsonSuccess($voucher, __('api.voucher_reset'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_reset_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vouchers/{id}/disable
     */
    public function disable(array $params): void
    {
        $id = (int)$params['id'];

        $voucher = $this->db->getVoucherById($id);
        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        try {
            $this->db->updateVoucherStatus($id, 'disabled');
            $voucher = $this->db->getVoucherById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('updated', $voucher);
            }

            jsonSuccess($voucher, __('api.voucher_disabled'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_disable_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vouchers/{id}/enable
     */
    public function enable(array $params): void
    {
        $id = (int)$params['id'];

        $voucher = $this->db->getVoucherById($id);
        if (!$voucher) {
            jsonError(__('api.voucher_not_found'), 404);
        }

        $newStatus = $voucher['first_use'] ? 'active' : 'unused';

        try {
            $this->db->updateVoucherStatus($id, $newStatus);
            $voucher = $this->db->getVoucherById($id);

            // Push temps réel vers les nœuds RADIUS
            if ($this->pushService && $voucher) {
                $this->pushService->notifyVoucherChange('updated', $voucher);
            }

            jsonSuccess($voucher, __('api.voucher_enabled'));
        }
        catch (Exception $e) {
            jsonError(__('api.voucher_enable_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/vouchers/batch/{batchId}
     */
    public function deleteBatch(array $params): void
    {
        $batchId = $params['batchId'];

        try {
            $adminId = $this->getAdminId();
            $count = $this->db->deleteVouchersByBatch($batchId, $adminId);
            jsonSuccess(['deleted' => $count], __('api.vouchers_batch_deleted'));
        }
        catch (Exception $e) {
            jsonError(__('api.vouchers_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/vouchers/import
     */
    public function import(): void
    {
        $data = getJsonBody();

        if (empty($data['vouchers']) || !is_array($data['vouchers'])) {
            jsonError(__('api.voucher_no_data_provided'), 400);
        }

        $batchId = generateUUID();
        $imported = 0;
        $errors = [];

        foreach ($data['vouchers'] as $index => $voucherData) {
            if (empty($voucherData['username'])) {
                $errors[] = "Row {$index}: Missing username";
                continue;
            }

            if ($this->db->getVoucherByUsername($voucherData['username'])) {
                $errors[] = "Row {$index}: Voucher {$voucherData['username']} already exists";
                continue;
            }

            $voucherData['batch_id'] = $batchId;
            $voucherData['password'] = $voucherData['password'] ?? $voucherData['username'];
            $voucherData['created_by'] = $_SESSION['admin_id'] ?? null;

            try {
                $this->db->createVoucher($voucherData);
                $imported++;
            }
            catch (Exception $e) {
                $errors[] = "Row {$index}: " . $e->getMessage();
            }
        }

        jsonSuccess([
            'batch_id' => $batchId,
            'imported' => $imported,
            'errors' => $errors,
        ], __('api.vouchers_imported'));
    }
}