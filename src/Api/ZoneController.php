<?php
/**
 * Controller API Zones
 */

class ZoneController
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

    /**
     * GET /api/zones
     */
    public function index(): void
    {
        $activeOnly = isset($_GET['active']) && $_GET['active'] === '1';
        $adminId = $this->getAdminId();
        $zones = $this->db->getAllZones($activeOnly, $adminId);
        jsonSuccess($zones);
    }

    /**
     * GET /api/zones/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $zone = $this->db->getZoneById($id);

        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        // Ajouter les NAS de cette zone
        $zone['nas'] = $this->db->getNasByZone($id);

        jsonSuccess($zone);
    }

    /**
     * POST /api/zones
     */
    public function store(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.zone_name_required'), 400);
        }

        if (empty($data['code'])) {
            // Générer un code unique sécurisé
            $data['code'] = $this->db->generateZoneCode();
        }

        // Vérifier que le code est unique
        $existing = $this->db->getZoneByCode($data['code']);
        if ($existing) {
            jsonError(__('api.zone_code_exists'), 400);
        }

        $data['admin_id'] = $this->getAdminId();
        $data['owner_id'] = $this->getAdminId();

        try {
            $id = $this->db->createZone($data);
            $zone = $this->db->getZoneById($id);
            jsonSuccess($zone, __('api.zone_created'));
        }
        catch (Exception $e) {
            jsonError(__('api.zone_create_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/zones/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $zone = $this->db->getZoneById($id);
        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        if (empty($data['name'])) {
            jsonError(__('api.zone_name_required'), 400);
        }

        // Le code ne peut pas être modifié après création - garder le code original
        $data['code'] = $zone['code'];

        try {
            $this->db->updateZone($id, $data);
            $zone = $this->db->getZoneById($id);
            jsonSuccess($zone, __('api.zone_updated'));
        }
        catch (Exception $e) {
            jsonError(__('api.zone_update_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/zones/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $zone = $this->db->getZoneById($id);
        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        // Avertir si des éléments sont associés
        if ($zone['nas_count'] > 0 || $zone['profiles_count'] > 0 || $zone['vouchers_count'] > 0) {
        // Continuer quand même mais informer
        }

        try {
            $this->db->deleteZone($id);
            jsonSuccess(null, __('api.zone_deleted'));
        }
        catch (Exception $e) {
            jsonError(__('api.zone_delete_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/zones/{id}/nas
     */
    public function getNas(array $params): void
    {
        $id = (int)$params['id'];

        $zone = $this->db->getZoneById($id);
        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        $nas = $this->db->getNasByZone($id);
        jsonSuccess($nas);
    }

    /**
     * GET /api/zones/{id}/profiles
     */
    public function getProfiles(array $params): void
    {
        $id = (int)$params['id'];

        $zone = $this->db->getZoneById($id);
        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        $profiles = $this->db->getProfilesByZone($id);
        jsonSuccess($profiles);
    }

    /**
     * GET /api/zones/generate-code
     */
    public function generateCode(): void
    {
        try {
            $code = $this->db->generateZoneCode();
            jsonSuccess(['code' => $code]);
        }
        catch (Exception $e) {
            jsonError(__('api.zone_generate_code_failed') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/zones/{id}/toggle
     */
    public function toggle(array $params): void
    {
        $id = (int)$params['id'];

        $zone = $this->db->getZoneById($id);
        if (!$zone) {
            jsonError(__('api.zone_not_found'), 404);
        }

        try {
            $this->db->updateZone($id, [
                'name' => $zone['name'],
                'code' => $zone['code'],
                'description' => $zone['description'],
                'color' => $zone['color'],
                'dns_name' => $zone['dns_name'] ?? null,
                'is_active' => $zone['is_active'] ? 0 : 1
            ]);
            $zone = $this->db->getZoneById($id);
            jsonSuccess($zone, $zone['is_active'] ? __('api.zone_activated') : __('api.zone_deactivated'));
        }
        catch (Exception $e) {
            jsonError(__('api.zone_toggle_failed') . ': ' . $e->getMessage(), 500);
        }
    }

}