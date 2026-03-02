<?php
/**
 * MonitoringController - Gestion du monitoring de bande passante
 */

class MonitoringController
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
     * GET /api/monitoring/stats
     * Statistiques globales de monitoring
     */
    public function stats(): void
    {
        $stats = $this->db->getMonitoringStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * GET /api/monitoring/top-users
     * Top consommateurs
     */
    public function topUsers(): void
    {
        $limit = (int)($_GET['limit'] ?? 10);
        $users = $this->db->getTopConsumers($limit, $this->getAdminId());
        jsonSuccess($users);
    }

    /**
     * GET /api/monitoring/live-sessions
     * Sessions actives en temps réel
     */
    public function liveSessions(): void
    {
        $sessions = $this->db->getLiveSessions($this->getAdminId());
        jsonSuccess($sessions);
    }

    /**
     * GET /api/monitoring/hourly-stats
     * Statistiques par heure (24h)
     */
    public function hourlyStats(): void
    {
        $stats = $this->db->getHourlyStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * GET /api/monitoring/daily-stats
     * Statistiques par jour (7 jours)
     */
    public function dailyStats(): void
    {
        $stats = $this->db->getDailyStats($this->getAdminId());
        jsonSuccess($stats);
    }

    /**
     * GET /api/monitoring/fup-alerts
     * Alertes FUP (utilisateurs proches ou dépassant leur limite)
     */
    public function fupAlerts(): void
    {
        $alerts = $this->db->getFupAlerts($this->getAdminId());
        jsonSuccess($alerts);
    }
}
