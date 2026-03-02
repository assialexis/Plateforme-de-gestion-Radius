<?php
/**
 * Controller API Profiles
 */

class ProfileController
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
     * GET /api/profiles
     */
    public function index(): void
    {
        $activeOnly = get('active') === '1';
        $adminId = $this->getAdminId();
        $profiles = $this->db->getAllProfiles($activeOnly, $adminId);
        jsonSuccess($profiles);
    }

    /**
     * GET /api/profiles/{id}
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $profile = $this->db->getProfileById($id);

        if (!$profile) {
            jsonError(__('api.profile_not_found'), 404);
        }

        jsonSuccess($profile);
    }

    /**
     * POST /api/profiles
     */
    public function store(): void
    {
        $data = getJsonBody();

        if (empty($data['name'])) {
            jsonError(__('api.profile_name_required'), 400);
        }

        if (empty($data['zone_id'])) {
            jsonError(__('api.profile_zone_required'), 400);
        }

        $data['admin_id'] = $this->getAdminId();

        try {
            $id = $this->db->createProfile($data);
            $profile = $this->db->getProfileById($id);
            jsonSuccess($profile, __('api.profile_created'));
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                jsonError(__('api.profile_name_exists'), 400);
            }
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * PUT /api/profiles/{id}
     */
    public function update(array $params): void
    {
        $id = (int)$params['id'];
        $data = getJsonBody();

        $profile = $this->db->getProfileById($id);
        if (!$profile) {
            jsonError(__('api.profile_not_found'), 404);
        }

        if (empty($data['name'])) {
            jsonError(__('api.profile_name_required'), 400);
        }

        if (empty($data['zone_id'])) {
            jsonError(__('api.profile_zone_required'), 400);
        }

        try {
            $this->db->updateProfile($id, $data);
            $profile = $this->db->getProfileById($id);
            jsonSuccess($profile, __('api.profile_updated'));
        } catch (Exception $e) {
            jsonError(__('api.error_saving') . ': ' . $e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/profiles/{id}
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];

        $profile = $this->db->getProfileById($id);
        if (!$profile) {
            jsonError(__('api.profile_not_found'), 404);
        }

        try {
            $this->db->deleteProfile($id);
            jsonSuccess(null, __('api.profile_deleted'));
        } catch (Exception $e) {
            jsonError(__('api.error_deleting') . ': ' . $e->getMessage(), 500);
        }
    }
}
