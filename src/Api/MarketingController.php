<?php

class MarketingController
{
    private $db;
    private $auth;
    private MarketingService $marketingService;

    public function __construct($db, $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->marketingService = new MarketingService($db->getPdo());
    }

    private function getAdminId(): int
    {
        return $this->auth->getAdminId() ?? 1;
    }

    /**
     * GET /marketing/clients
     */
    public function getClients(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();

        $source = get('source') ?? 'hotspot';
        $page = max(1, (int)(get('page') ?? 1));
        $perPage = min(100, max(1, (int)(get('per_page') ?? 50)));

        $filters = array_filter([
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
            'profile_id' => get('profile_id'),
            'payment_method' => get('payment_method'),
            'amount_min' => get('amount_min'),
            'amount_max' => get('amount_max'),
            'status' => get('status'),
            'expiry_from' => get('expiry_from'),
            'expiry_to' => get('expiry_to'),
        ], fn($v) => $v !== null && $v !== '');

        $result = $this->marketingService->getClients($adminId, $source, $filters, $page, $perPage);
        jsonSuccess($result);
    }

    /**
     * POST /marketing/send
     */
    public function send(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $data = getJsonBody();

        if (empty($data['channel']) || !in_array($data['channel'], ['sms', 'whatsapp'])) {
            jsonError('Canal invalide', 400);
            return;
        }
        if (empty($data['message'])) {
            jsonError('Message requis', 400);
            return;
        }
        if ($data['channel'] === 'sms' && empty($data['gateway_id'])) {
            jsonError('Passerelle SMS requise', 400);
            return;
        }
        if (empty($data['source']) || !in_array($data['source'], ['hotspot', 'otp', 'pppoe'])) {
            jsonError('Source client invalide', 400);
            return;
        }
        if (empty($data['client_phones']) || !is_array($data['client_phones'])) {
            jsonError('Aucun destinataire sélectionné', 400);
            return;
        }

        $result = $this->marketingService->sendCampaign($adminId, $data);

        if ($result['success']) {
            jsonSuccess($result['data'], $result['message']);
        } else {
            jsonError($result['error'] ?? 'Erreur lors de l\'envoi', 500);
        }
    }

    /**
     * GET /marketing/campaigns
     */
    public function getCampaigns(): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $page = max(1, (int)(get('page') ?? 1));

        $result = $this->marketingService->getCampaigns($adminId, $page);
        jsonSuccess($result);
    }

    /**
     * GET /marketing/campaigns/{id}
     */
    public function getCampaignDetails(array $params): void
    {
        $this->auth->requireRole('admin');
        $adminId = $this->getAdminId();
        $id = (int)($params['id'] ?? 0);

        if (!$id) {
            jsonError('ID campagne requis', 400);
            return;
        }

        $result = $this->marketingService->getCampaignDetails($adminId, $id);
        if (!$result) {
            jsonError('Campagne non trouvée', 404);
            return;
        }

        jsonSuccess($result);
    }

    /**
     * GET /marketing/profiles
     */
    public function getProfiles(): void
    {
        $this->auth->requireRole('admin');
        $result = $this->marketingService->getProfiles($this->getAdminId());
        jsonSuccess($result);
    }

    /**
     * GET /marketing/gateways
     */
    public function getGateways(): void
    {
        $this->auth->requireRole('admin');
        $result = $this->marketingService->getGateways($this->getAdminId());
        jsonSuccess($result);
    }
}
