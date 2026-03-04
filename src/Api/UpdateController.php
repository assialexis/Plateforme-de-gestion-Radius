<?php
/**
 * UpdateController - API pour le système de mise à jour (SuperAdmin uniquement)
 */

require_once __DIR__ . '/../Update/Updater.php';

class UpdateController
{
    private RadiusDatabase $db;
    private AuthService $auth;
    private Updater $updater;

    public function __construct(RadiusDatabase $db, AuthService $auth)
    {
        $this->db = $db;
        $this->auth = $auth;

        $config = require __DIR__ . '/../../config/config.php';
        $updateUrl = $config['app']['update_url'] ?? null;
        $this->updater = new Updater($db->getPdo(), $updateUrl);
    }

    private function requireSuperAdmin(): void
    {
        $user = $this->auth->getUser();
        if (!$user || !$user->isSuperAdmin()) {
            jsonError('Accès refusé', 403);
        }
    }

    /**
     * GET /superadmin/updates/status
     */
    public function getStatus(): void
    {
        $this->requireSuperAdmin();

        $migrationStatus = $this->updater->getMigrationStatus();
        $backups = $this->updater->getBackups();

        jsonSuccess([
            'version' => $this->updater->getCurrentVersion(),
            'migrations' => $migrationStatus,
            'backups_count' => count($backups),
            'latest_backup' => !empty($backups) ? $backups[0] : null,
            'maintenance' => $this->updater->isInMaintenance(),
        ]);
    }

    /**
     * GET /superadmin/updates/check
     */
    public function checkUpdates(): void
    {
        $this->requireSuperAdmin();

        $update = $this->updater->checkForUpdates();
        jsonSuccess([
            'update_available' => $update !== null,
            'update' => $update,
            'current_version' => $this->updater->getCurrentVersion(),
        ]);
    }

    /**
     * GET /superadmin/updates/preflight
     */
    public function preflight(): void
    {
        $this->requireSuperAdmin();

        $checks = $this->updater->preflightChecks();
        $allOk = true;
        foreach ($checks as $check) {
            if (!$check['ok']) { $allOk = false; break; }
        }

        jsonSuccess([
            'all_ok' => $allOk,
            'checks' => $checks,
        ]);
    }

    /**
     * POST /superadmin/updates/migrate
     */
    public function runMigrations(): void
    {
        $this->requireSuperAdmin();

        $result = $this->updater->applyMigrations();
        if (!empty($result['errors'])) {
            jsonError('Certaines migrations ont échoué', 500, $result['errors']);
        }

        jsonSuccess($result, $result['run'] > 0
            ? "{$result['run']} migration(s) exécutée(s)"
            : 'Aucune migration en attente'
        );
    }

    /**
     * POST /superadmin/updates/backup
     */
    public function createBackup(): void
    {
        $this->requireSuperAdmin();

        try {
            $path = $this->updater->createBackup('web');
            jsonSuccess([
                'path' => $path,
                'name' => basename($path),
            ], 'Sauvegarde créée avec succès');
        } catch (Throwable $e) {
            jsonError('Erreur lors de la sauvegarde: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /superadmin/updates/backups
     */
    public function listBackups(): void
    {
        $this->requireSuperAdmin();

        $backups = $this->updater->getBackups();
        // Formater les tailles
        foreach ($backups as &$backup) {
            $backup['size_formatted'] = $this->formatBytes($backup['size']);
        }

        jsonSuccess($backups);
    }

    /**
     * POST /superadmin/updates/restore
     */
    public function restoreBackup(): void
    {
        $this->requireSuperAdmin();

        $body = getJsonBody();
        $backupName = $body['backup'] ?? null;

        if (!$backupName) {
            jsonError('Nom du backup requis', 400);
        }

        $backups = $this->updater->getBackups();
        $target = null;
        foreach ($backups as $b) {
            if ($b['name'] === $backupName) { $target = $b; break; }
        }

        if (!$target) {
            jsonError('Backup non trouvé', 404);
        }

        $ok = $this->updater->restoreDatabase($target['path']);
        if ($ok) {
            jsonSuccess(null, 'Base de données restaurée avec succès');
        } else {
            jsonError('Échec de la restauration', 500);
        }
    }

    /**
     * POST /superadmin/updates/upload
     */
    public function uploadAndApply(): void
    {
        $this->requireSuperAdmin();

        if (empty($_FILES['update_file'])) {
            jsonError('Aucun fichier uploadé', 400);
        }

        $file = $_FILES['update_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            jsonError('Erreur d\'upload: code ' . $file['error'], 400);
        }

        // Vérifier l'extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            jsonError('Seuls les fichiers .zip sont acceptés', 400);
        }

        // Déplacer vers tmp
        $tmpPath = $this->updater->getCurrentVersion() . '_update_' . time() . '.zip';
        $destPath = realpath(__DIR__ . '/../../storage/tmp') . '/' . $tmpPath;
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonError('Impossible de sauvegarder le fichier', 500);
        }

        // Appliquer
        $result = $this->updater->applyOfflineUpdate($destPath);

        // Supprimer le ZIP temporaire
        @unlink($destPath);

        if ($result['success']) {
            jsonSuccess($result, 'Mise à jour appliquée avec succès');
        } else {
            jsonError(implode('; ', $result['errors']), 500, $result);
        }
    }

    /**
     * GET /superadmin/updates/history
     */
    public function getHistory(): void
    {
        $this->requireSuperAdmin();
        jsonSuccess($this->updater->getUpdateHistory());
    }

    /**
     * GET /superadmin/updates/migrations
     */
    public function getMigrations(): void
    {
        $this->requireSuperAdmin();
        jsonSuccess($this->updater->getMigrationStatus());
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 1) . ' ' . $units[$i];
    }
}
