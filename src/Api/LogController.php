<?php
/**
 * Controller API Logs
 */

class LogController
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
     * GET /api/logs
     */
    public function index(): void
    {
        $filters = [
            'username' => get('username'),
            'action' => get('action'),
            'nas_ip' => get('nas_ip'),
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
        ];

        $page = max(1, (int)(get('page') ?: 1));
        $perPage = min(100, max(10, (int)(get('per_page') ?: 50)));

        $adminId = $this->getAdminId();
        $result = $this->db->getAuthLogs($filters, $page, $perPage, $adminId);
        jsonSuccess($result);
    }

    /**
     * GET /api/logs/export
     */
    public function export(): void
    {
        $filters = [
            'username' => get('username'),
            'action' => get('action'),
            'nas_ip' => get('nas_ip'),
            'date_from' => get('date_from'),
            'date_to' => get('date_to'),
        ];

        // Récupérer toutes les données
        $result = $this->db->getAuthLogs($filters, 1, 10000);
        $logs = $result['data'];

        // Générer le CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="auth_logs_' . date('Y-m-d_His') . '.csv"');

        $output = fopen('php://output', 'w');

        // En-têtes
        fputcsv($output, ['Date', 'Username', 'Action', 'Reason', 'NAS IP', 'NAS Name', 'Client MAC', 'Client IP']);

        // Données
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['username'],
                $log['action'],
                $log['reason'] ?? '',
                $log['nas_ip'],
                $log['nas_name'] ?? '',
                $log['client_mac'] ?? '',
                $log['client_ip'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }
}