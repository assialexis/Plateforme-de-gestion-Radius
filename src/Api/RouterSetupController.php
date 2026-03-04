<?php
/**
 * Controller API Setup Routeur
 *
 * Gère la génération de scripts setup et le statut des routeurs.
 */

require_once __DIR__ . '/../MikroTik/SetupScriptGenerator.php';

class RouterSetupController
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
     * GET /api/router-setup/{routerId}
     * Générer le script setup pour un routeur
     */
    public function getSetupScript(array $params): void
    {
        $routerId = $params['routerId'] ?? '';

        // Vérifier que le routeur appartient à l'admin
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT id, router_id, admin_id FROM nas WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nas) {
            jsonError('Routeur introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($nas['admin_id'] && $nas['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        // Générer le token si non existant
        $generator = new SetupScriptGenerator($pdo, $this->getServerUrl());

        $stmtToken = $pdo->prepare("SELECT polling_token FROM nas WHERE router_id = ?");
        $stmtToken->execute([$routerId]);
        $token = $stmtToken->fetchColumn();

        if (empty($token)) {
            $token = $generator->generatePollingToken($routerId);
        }

        $script = $generator->generate($routerId);

        jsonSuccess([
            'script' => $script,
            'router_id' => $routerId,
            'polling_token' => $token,
        ]);
    }

    /**
     * POST /api/router-setup/{routerId}/generate-token
     * Régénérer le polling token
     */
    public function generateToken(array $params): void
    {
        $routerId = $params['routerId'] ?? '';

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT id, admin_id FROM nas WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nas) {
            jsonError('Routeur introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($nas['admin_id'] && $nas['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        $generator = new SetupScriptGenerator($pdo, $this->getServerUrl());
        $token = $generator->generatePollingToken($routerId);

        if ($token) {
            jsonSuccess([
                'polling_token' => $token,
                'message' => 'Token régénéré. Réinstallez le script setup sur le routeur.'
            ]);
        } else {
            jsonError('Erreur lors de la génération du token', 500);
        }
    }

    /**
     * GET /api/router-setup/{routerId}/status
     * Obtenir le statut online/offline du routeur
     */
    public function getStatus(array $params): void
    {
        $routerId = $params['routerId'] ?? '';

        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare("SELECT id, admin_id FROM nas WHERE router_id = ?");
        $stmt->execute([$routerId]);
        $nas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$nas) {
            jsonError('Routeur introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($nas['admin_id'] && $nas['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        $generator = new SetupScriptGenerator($pdo, $this->getServerUrl());
        $status = $generator->getRouterStatus($routerId);

        jsonSuccess($status);
    }

    /**
     * GET /api/router-setup/statuses
     * Obtenir les statuts de tous les routeurs de l'admin
     */
    public function getAllStatuses(): void
    {
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();

        $sql = "SELECT router_id, shortname, last_seen, polling_token,
                       polling_interval, setup_installed_at,
                       TIMESTAMPDIFF(SECOND, last_seen, NOW()) as last_seen_ago
                FROM nas WHERE admin_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$adminId]);
        $routers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $statuses = [];
        foreach ($routers as $r) {
            $ago = $r['last_seen_ago'];
            $isOnline = $r['last_seen'] && $ago !== null && $ago < 30;

            $statuses[$r['router_id']] = [
                'online' => $isOnline,
                'last_seen' => $r['last_seen'],
                'last_seen_ago' => $ago,
                'has_token' => !empty($r['polling_token']),
                'setup_installed' => !empty($r['setup_installed_at']),
            ];
        }

        jsonSuccess($statuses);
    }

    /**
     * Déterminer l'URL du serveur (dossier web/)
     */
    private function getServerUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Chemin du dossier web/ (même dossier que api.php)
        $basePath = dirname($_SERVER['SCRIPT_NAME']);

        return $protocol . '://' . $host . $basePath;
    }
}
