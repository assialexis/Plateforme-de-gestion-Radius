<?php
/**
 * Controller API Commandes Routeur
 *
 * CRUD et gestion des commandes envoyées aux routeurs MikroTik.
 */

require_once __DIR__ . '/../MikroTik/CommandSender.php';

class RouterCommandController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private MikroTikCommandSender $commandSender;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;
        $this->commandSender = new MikroTikCommandSender($db->getPdo());
    }

    private function getAdminId(): ?int
    {
        return $this->auth->getAdminId();
    }

    /**
     * GET /api/router-commands
     * Liste des commandes avec filtres
     */
    public function index(): void
    {
        $adminId = $this->getAdminId();
        $routerId = $_GET['router_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;
        $limit = min((int)($_GET['limit'] ?? 50), 200);
        $offset = (int)($_GET['offset'] ?? 0);

        // Valider les dates
        if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = null;
        if ($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = null;

        $commands = $this->commandSender->getCommandHistory(
            $routerId, $adminId, $limit, $offset, $status, $dateFrom, $dateTo
        );

        $stats = $this->commandSender->getCommandStats($routerId, $adminId);

        jsonSuccess([
            'commands' => $commands,
            'stats' => $stats,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
            ]
        ]);
    }

    /**
     * GET /api/router-commands/{id}
     * Détail d'une commande
     */
    public function show(array $params): void
    {
        $id = (int)$params['id'];
        $command = $this->commandSender->getCommandById($id);

        if (!$command) {
            jsonError('Commande introuvable', 404);
            return;
        }

        // Vérifier l'accès
        $adminId = $this->getAdminId();
        if ($command['admin_id'] && $command['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        jsonSuccess($command);
    }

    /**
     * POST /api/router-commands
     * Créer une nouvelle commande
     */
    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);

        $routerId = $input['router_id'] ?? '';
        $command = $input['command'] ?? '';
        $description = $input['description'] ?? null;
        $priority = (int)($input['priority'] ?? 50);
        $commandType = $input['command_type'] ?? 'raw';

        if (empty($routerId) || empty($command)) {
            jsonError('router_id et command sont requis', 400);
            return;
        }

        // Vérifier que le routeur appartient à l'admin
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

        // Validation de sécurité
        if (strlen($command) > 65536) {
            jsonError('Commande trop longue (max 64KB)', 400);
            return;
        }

        // Gérer les commandes prédéfinies
        if ($commandType !== 'raw' && isset($input['params'])) {
            $cmdId = $this->handlePredefinedCommand($routerId, $commandType, $input['params']);
        } else {
            $cmdId = $this->commandSender->send($routerId, $command, $description, $priority, $commandType);
        }

        if ($cmdId) {
            jsonSuccess([
                'command_id' => $cmdId,
                'message' => 'Commande créée et en attente d\'exécution'
            ], 201);
        } else {
            jsonError('Erreur lors de la création de la commande', 500);
        }
    }

    /**
     * POST /api/router-commands/{id}/cancel
     * Annuler une commande
     */
    public function cancel(array $params): void
    {
        $id = (int)$params['id'];
        $command = $this->commandSender->getCommandById($id);

        if (!$command) {
            jsonError('Commande introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($command['admin_id'] && $command['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        if ($this->commandSender->cancelCommand($id)) {
            jsonSuccess(['message' => 'Commande annulée']);
        } else {
            jsonError('Impossible d\'annuler cette commande (statut non éligible)', 400);
        }
    }

    /**
     * POST /api/router-commands/{id}/retry
     * Relancer une commande échouée
     */
    public function retry(array $params): void
    {
        $id = (int)$params['id'];
        $command = $this->commandSender->getCommandById($id);

        if (!$command) {
            jsonError('Commande introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($command['admin_id'] && $command['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        if ($this->commandSender->retryCommand($id)) {
            jsonSuccess(['message' => 'Commande relancée']);
        } else {
            jsonError('Impossible de relancer cette commande', 400);
        }
    }

    /**
     * DELETE /api/router-commands/{id}
     * Supprimer une commande
     */
    public function destroy(array $params): void
    {
        $id = (int)$params['id'];
        $command = $this->commandSender->getCommandById($id);

        if (!$command) {
            jsonError('Commande introuvable', 404);
            return;
        }

        $adminId = $this->getAdminId();
        if ($command['admin_id'] && $command['admin_id'] != $adminId && !$this->auth->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
            return;
        }

        if ($this->commandSender->deleteCommand($id)) {
            jsonSuccess(['message' => 'Commande supprimée']);
        } else {
            jsonError('Erreur lors de la suppression', 500);
        }
    }

    /**
     * POST /api/router-commands/delete-bulk
     * Supprimer plusieurs commandes
     */
    public function deleteBulk(): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];

        if (empty($ids) || !is_array($ids)) {
            jsonError('Aucune commande sélectionnée', 400);
            return;
        }

        // Vérifier que toutes les commandes appartiennent à l'admin
        $adminId = $this->getAdminId();
        $pdo = $this->db->getPdo();
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        if (!$this->auth->isSuperAdmin()) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM router_commands WHERE id IN ($placeholders) AND admin_id != ?"
            );
            $stmt->execute([...$ids, $adminId]);
            if ($stmt->fetchColumn() > 0) {
                jsonError('Accès refusé', 403);
                return;
            }
        }

        $deleted = $this->commandSender->deleteCommands($ids);
        jsonSuccess([
            'deleted' => $deleted,
            'message' => 'Commandes supprimées'
        ]);
    }

    /**
     * POST /api/router-commands/clear-history
     * Vider tout l'historique
     */
    public function clearHistory(): void
    {
        $adminId = $this->getAdminId();
        $deleted = $this->commandSender->clearHistory($adminId);

        jsonSuccess([
            'deleted' => $deleted,
            'message' => 'Historique vidé'
        ]);
    }

    /**
     * GET /api/router-commands/export
     * Exporter l'historique en CSV
     */
    public function export(): void
    {
        $adminId = $this->getAdminId();
        $routerId = $_GET['router_id'] ?? null;
        $status = $_GET['status'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        if ($dateFrom && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = null;
        if ($dateTo && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = null;

        $commands = $this->commandSender->getCommandHistory(
            $routerId, $adminId, 10000, 0, $status, $dateFrom, $dateTo
        );

        $filename = 'router-commands-' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        // BOM UTF-8 pour Excel
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['ID', 'Router ID', 'Router', 'Type', 'Description', 'Status', 'Priority', 'Created', 'Sent', 'Executed', 'Error', 'Retries'], ';');

        foreach ($commands as $cmd) {
            fputcsv($output, [
                $cmd['id'],
                $cmd['router_id'],
                $cmd['router_name'] ?? '',
                $cmd['command_type'],
                $cmd['command_description'] ?? '',
                $cmd['status'],
                $cmd['priority'],
                $cmd['created_at'],
                $cmd['sent_at'] ?? '',
                $cmd['executed_at'] ?? '',
                $cmd['error_message'] ?? '',
                $cmd['retry_count'],
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * GET /api/router-commands/stats
     * Statistiques des commandes
     */
    public function stats(): void
    {
        $adminId = $this->getAdminId();
        $routerId = $_GET['router_id'] ?? null;

        $stats = $this->commandSender->getCommandStats($routerId, $adminId);

        jsonSuccess($stats);
    }

    /**
     * Exécuter une commande prédéfinie
     */
    private function handlePredefinedCommand(string $routerId, string $type, array $params): int|false
    {
        return match ($type) {
            'disconnect_hotspot' => $this->commandSender->disconnectHotspotUser(
                $routerId, $params['username'] ?? ''
            ),
            'disconnect_pppoe' => $this->commandSender->disconnectPPPoEUser(
                $routerId, $params['username'] ?? ''
            ),
            'create_pppoe' => $this->commandSender->createPPPoEUser($routerId, $params),
            'delete_pppoe' => $this->commandSender->deletePPPoEUser(
                $routerId, $params['username'] ?? ''
            ),
            'set_rate_limit' => $this->commandSender->setUserRateLimit(
                $routerId, $params['username'] ?? '', $params['rate_limit'] ?? ''
            ),
            'toggle_user' => $this->commandSender->setUserDisabled(
                $routerId, $params['username'] ?? '', $params['disabled'] ?? true
            ),
            'log' => $this->commandSender->sendLogMessage(
                $routerId, $params['message'] ?? 'Test'
            ),
            default => false,
        };
    }
}
