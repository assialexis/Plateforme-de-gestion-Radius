<?php
/**
 * Controller API Dashboard
 */

class DashboardController
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
     * GET /api/dashboard/stats
     */
    public function stats(): void
    {
        $adminId = $this->getAdminId();
        $stats = $this->db->getDashboardStats($adminId);
        jsonSuccess($stats);
    }

    /**
     * GET /api/dashboard/full - Stats agrégées complètes
     */
    public function full(): void
    {
        $adminId = $this->getAdminId();
        $stats = $this->db->getFullDashboardStats($adminId);
        jsonSuccess($stats);
    }

    /**
     * GET /api/dashboard/connections
     */
    public function connections(array $params): void
    {
        $days = (int)(get('days') ?: 7);
        $adminId = $this->getAdminId();
        $data = $this->db->getConnectionsPerDay($days, $adminId);
        jsonSuccess($data);
    }

    /**
     * GET /api/dashboard/data
     */
    public function dataUsage(array $params): void
    {
        $days = (int)(get('days') ?: 7);
        $adminId = $this->getAdminId();
        $data = $this->db->getDataPerDay($days, $adminId);
        jsonSuccess($data);
    }

    /**
     * GET /api/dashboard/recent
     */
    public function recent(): void
    {
        $limit = (int)(get('limit') ?: 10);
        $adminId = $this->getAdminId();
        $data = $this->db->getRecentConnections($limit, $adminId);
        jsonSuccess($data);
    }
}
