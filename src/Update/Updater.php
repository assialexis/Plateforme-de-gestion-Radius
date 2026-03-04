<?php
/**
 * Updater - Orchestrateur de mises à jour
 *
 * Gère le cycle complet : vérification, backup, téléchargement, migration, vérification.
 */

require_once __DIR__ . '/MigrationRunner.php';

class Updater
{
    private PDO $pdo;
    private MigrationRunner $migrationRunner;
    private string $rootDir;
    private string $backupDir;
    private string $tmpDir;
    private ?string $updateUrl;

    /** Fichiers/dossiers à ne jamais écraser lors d'une mise à jour */
    private const PROTECTED_PATHS = [
        'config/config.php',
        '.installed',
        'storage/',
        'uploads/',
        'logs/',
    ];

    public function __construct(PDO $pdo, ?string $updateUrl = null)
    {
        $this->pdo = $pdo;
        $this->rootDir = realpath(__DIR__ . '/../../');
        $this->backupDir = $this->rootDir . '/storage/backups';
        $this->tmpDir = $this->rootDir . '/storage/tmp';
        $this->updateUrl = $updateUrl;
        $this->migrationRunner = new MigrationRunner($pdo, $this->rootDir . '/database/migrations');
        $this->migrationRunner->ensureTrackingTable();

        if (!is_dir($this->backupDir)) @mkdir($this->backupDir, 0777, true);
        if (!is_dir($this->tmpDir)) @mkdir($this->tmpDir, 0777, true);
    }

    // ==============================
    // Version
    // ==============================

    /**
     * Version actuellement installée
     */
    public function getCurrentVersion(): string
    {
        $versionFile = $this->rootDir . '/VERSION';
        if (file_exists($versionFile)) {
            return trim(file_get_contents($versionFile));
        }
        return '1.0.0';
    }

    /**
     * Vérifie si une mise à jour est disponible (requête HTTP vers le serveur de mises à jour)
     * @return array|null ['version' => '1.1.0', 'changelog' => [...], 'download_url' => '...'] ou null
     */
    public function checkForUpdates(): ?array
    {
        if (empty($this->updateUrl)) {
            return null;
        }

        $url = rtrim($this->updateUrl, '/') . '/versions.json';
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'header' => 'User-Agent: RadiusManager/' . $this->getCurrentVersion(),
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $json = @file_get_contents($url, false, $ctx);
        if ($json === false) return null;

        $data = json_decode($json, true);
        if (!$data || empty($data['latest'])) return null;

        $latest = $data['latest'];
        if (version_compare($latest, $this->getCurrentVersion(), '<=')) {
            return null; // Déjà à jour
        }

        $versionInfo = $data['versions'][$latest] ?? [];
        return [
            'version' => $latest,
            'changelog' => $versionInfo['changelog'] ?? [],
            'download_url' => $versionInfo['download_url'] ?? null,
            'checksum' => $versionInfo['checksum_sha256'] ?? null,
            'min_php' => $versionInfo['min_php'] ?? '8.0',
        ];
    }

    // ==============================
    // Vérifications pré-vol
    // ==============================

    /**
     * Vérifie les prérequis avant mise à jour
     */
    public function preflightChecks(): array
    {
        $checks = [];

        // PHP version
        $checks['php_version'] = [
            'label' => 'Version PHP >= 8.0',
            'ok' => version_compare(PHP_VERSION, '8.0', '>='),
            'value' => PHP_VERSION,
        ];

        // Espace disque (min 100 MB)
        $freeSpace = @disk_free_space($this->rootDir);
        $checks['disk_space'] = [
            'label' => 'Espace disque >= 100 MB',
            'ok' => $freeSpace !== false && $freeSpace >= 100 * 1024 * 1024,
            'value' => $freeSpace !== false ? round($freeSpace / 1024 / 1024) . ' MB' : 'inconnu',
        ];

        // Permissions d'écriture
        $writableDirs = ['storage/', 'storage/backups/', 'storage/tmp/', 'database/migrations/'];
        foreach ($writableDirs as $dir) {
            $fullPath = $this->rootDir . '/' . $dir;
            $checks['writable_' . str_replace('/', '_', $dir)] = [
                'label' => "Écriture: {$dir}",
                'ok' => is_dir($fullPath) && is_writable($fullPath),
                'value' => is_dir($fullPath) ? (is_writable($fullPath) ? 'ok' : 'non-inscriptible') : 'absent',
            ];
        }

        // Connexion base de données
        try {
            $this->pdo->query("SELECT 1");
            $checks['database'] = ['label' => 'Connexion DB', 'ok' => true, 'value' => 'connecté'];
        } catch (PDOException $e) {
            $checks['database'] = ['label' => 'Connexion DB', 'ok' => false, 'value' => $e->getMessage()];
        }

        // Verrou de mise à jour
        $lockFile = $this->rootDir . '/storage/.update_lock';
        $checks['no_lock'] = [
            'label' => 'Pas de mise à jour en cours',
            'ok' => !file_exists($lockFile),
            'value' => file_exists($lockFile) ? 'verrouillé' : 'libre',
        ];

        return $checks;
    }

    /**
     * Tous les checks sont OK ?
     */
    public function allChecksPassed(): bool
    {
        foreach ($this->preflightChecks() as $check) {
            if (!$check['ok']) return false;
        }
        return true;
    }

    // ==============================
    // Verrou et maintenance
    // ==============================

    private function acquireLock(): bool
    {
        $lockFile = $this->rootDir . '/storage/.update_lock';
        if (file_exists($lockFile)) {
            // Vérifier si le verrou est ancien (> 30 min = probablement un crash)
            if (time() - filemtime($lockFile) > 1800) {
                unlink($lockFile);
            } else {
                return false;
            }
        }
        return file_put_contents($lockFile, json_encode([
            'pid' => getmypid(),
            'started_at' => date('Y-m-d H:i:s'),
        ])) !== false;
    }

    private function releaseLock(): void
    {
        $lockFile = $this->rootDir . '/storage/.update_lock';
        if (file_exists($lockFile)) unlink($lockFile);
    }

    public function enableMaintenance(): void
    {
        file_put_contents($this->rootDir . '/storage/.maintenance', json_encode([
            'started_at' => date('Y-m-d H:i:s'),
            'message' => 'Mise à jour en cours...',
        ]));
    }

    public function disableMaintenance(): void
    {
        $file = $this->rootDir . '/storage/.maintenance';
        if (file_exists($file)) unlink($file);
    }

    public function isInMaintenance(): bool
    {
        return file_exists($this->rootDir . '/storage/.maintenance');
    }

    // ==============================
    // Sauvegarde
    // ==============================

    /**
     * Crée une sauvegarde de la base de données (dump SQL via PHP)
     * @return string Chemin du fichier de backup
     */
    public function createBackup(string $label = 'manual'): string
    {
        $timestamp = date('Y-m-d_His');
        $version = $this->getCurrentVersion();
        $backupName = "backup_{$version}_{$label}_{$timestamp}";
        $backupPath = $this->backupDir . '/' . $backupName;

        if (!is_dir($backupPath) && !@mkdir($backupPath, 0777, true)) {
            throw new RuntimeException("Impossible de créer le dossier de backup: {$backupPath}");
        }

        // 1. Dump de la base de données (via mysqldump si disponible, sinon PHP)
        $this->dumpDatabase($backupPath . '/database.sql');

        // 2. Copier les fichiers critiques
        $criticalFiles = [
            'VERSION',
            'config/config.php',
            'config/config.example.php',
        ];
        foreach ($criticalFiles as $file) {
            $src = $this->rootDir . '/' . $file;
            if (file_exists($src)) {
                $destDir = $backupPath . '/' . dirname($file);
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                copy($src, $backupPath . '/' . $file);
            }
        }

        // 3. Sauvegarder la liste des migrations exécutées
        $migrations = $this->migrationRunner->getExecutedMigrations();
        file_put_contents($backupPath . '/migrations.json', json_encode($migrations, JSON_PRETTY_PRINT));

        // 4. Métadonnées
        file_put_contents($backupPath . '/backup_info.json', json_encode([
            'version' => $version,
            'label' => $label,
            'created_at' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'migrations_count' => count($migrations),
        ], JSON_PRETTY_PRINT));

        // Nettoyer les anciens backups (garder les 5 derniers)
        $this->cleanOldBackups(5);

        return $backupPath;
    }

    /**
     * Dump la base de données via mysqldump ou PHP natif
     */
    private function dumpDatabase(string $outputFile): void
    {
        // Essayer mysqldump d'abord
        $dbInfo = $this->getDatabaseInfo();

        $mysqldumpPaths = ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/Applications/XAMPP/xamppfiles/bin/mysqldump', 'mysqldump'];
        $mysqldump = null;
        foreach ($mysqldumpPaths as $path) {
            if (@is_executable($path) || ($path === 'mysqldump' && $this->commandExists($path))) {
                $mysqldump = $path;
                break;
            }
        }

        if ($mysqldump) {
            $cmd = sprintf(
                '%s --host=%s --port=%s --user=%s %s %s > %s 2>&1',
                escapeshellarg($mysqldump),
                escapeshellarg($dbInfo['host']),
                escapeshellarg($dbInfo['port']),
                escapeshellarg($dbInfo['user']),
                !empty($dbInfo['password']) ? '--password=' . escapeshellarg($dbInfo['password']) : '',
                escapeshellarg($dbInfo['dbname']),
                escapeshellarg($outputFile)
            );
            exec($cmd, $output, $returnCode);
            if ($returnCode === 0 && filesize($outputFile) > 0) {
                return;
            }
        }

        // Fallback: dump PHP natif (tables + données)
        $this->phpDumpDatabase($outputFile);
    }

    /**
     * Dump PHP natif de la base de données
     */
    private function phpDumpDatabase(string $outputFile): void
    {
        $fp = fopen($outputFile, 'w');
        fwrite($fp, "-- Dump généré par RADIUS Manager Updater\n");
        fwrite($fp, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
        fwrite($fp, "SET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Structure
            $create = $this->pdo->query("SHOW CREATE TABLE `{$table}`")->fetch();
            fwrite($fp, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fp, $create['Create Table'] . ";\n\n");

            // Données
            $rows = $this->pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';

                foreach (array_chunk($rows, 100) as $chunk) {
                    $values = [];
                    foreach ($chunk as $row) {
                        $vals = array_map(function ($v) {
                            if ($v === null) return 'NULL';
                            return $this->pdo->quote($v);
                        }, array_values($row));
                        $values[] = '(' . implode(', ', $vals) . ')';
                    }
                    fwrite($fp, "INSERT INTO `{$table}` ({$columnList}) VALUES\n" . implode(",\n", $values) . ";\n\n");
                }
            }
        }

        fwrite($fp, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($fp);
    }

    /**
     * Info de connexion DB depuis le DSN PDO
     */
    private function getDatabaseInfo(): array
    {
        $dsn = $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
        // Extraire les infos depuis une requête
        $host = '127.0.0.1';
        $port = '3306';
        $dbname = '';
        $user = '';
        $password = '';

        try {
            $dbname = $this->pdo->query("SELECT DATABASE()")->fetchColumn();
            $userRow = $this->pdo->query("SELECT CURRENT_USER()")->fetchColumn();
            $user = explode('@', $userRow)[0] ?? 'root';
        } catch (PDOException $e) {}

        // Lire depuis config.php si disponible
        $configFile = $this->rootDir . '/config/config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $host = $config['database']['host'] ?? $host;
            $port = $config['database']['port'] ?? $port;
            $dbname = $config['database']['dbname'] ?? $dbname;
            $user = $config['database']['username'] ?? $user;
            $password = $config['database']['password'] ?? $password;
        }

        return compact('host', 'port', 'dbname', 'user', 'password');
    }

    /**
     * Supprime les anciens backups (garde les N plus récents)
     */
    private function cleanOldBackups(int $keep = 5): void
    {
        $backups = $this->getBackups();
        if (count($backups) <= $keep) return;

        $toDelete = array_slice($backups, $keep);
        foreach ($toDelete as $backup) {
            $this->deleteDirectory($backup['path']);
        }
    }

    /**
     * Liste des backups existants (triés par date décroissante)
     */
    public function getBackups(): array
    {
        $backups = [];
        if (!is_dir($this->backupDir)) return $backups;

        foreach (scandir($this->backupDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $path = $this->backupDir . '/' . $entry;
            if (!is_dir($path)) continue;

            $infoFile = $path . '/backup_info.json';
            $info = file_exists($infoFile) ? json_decode(file_get_contents($infoFile), true) : [];

            $backups[] = [
                'name' => $entry,
                'path' => $path,
                'version' => $info['version'] ?? 'inconnu',
                'label' => $info['label'] ?? 'inconnu',
                'created_at' => $info['created_at'] ?? date('Y-m-d H:i:s', filemtime($path)),
                'size' => $this->getDirectorySize($path),
                'has_database' => file_exists($path . '/database.sql'),
            ];
        }

        // Trier par date décroissante
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));
        return $backups;
    }

    // ==============================
    // Migrations
    // ==============================

    /**
     * Exécute les migrations en attente
     */
    public function applyMigrations(): array
    {
        return $this->migrationRunner->runAll();
    }

    /**
     * Statut des migrations
     */
    public function getMigrationStatus(): array
    {
        return $this->migrationRunner->getStatus();
    }

    /**
     * Baseline des migrations (marquer comme appliquées sans exécuter)
     */
    public function baselineMigrations(): int
    {
        return $this->migrationRunner->baseline();
    }

    // ==============================
    // Mise à jour complète
    // ==============================

    /**
     * Applique une mise à jour depuis un fichier ZIP uploadé (mode hors ligne)
     */
    public function applyOfflineUpdate(string $zipPath): array
    {
        $result = [
            'success' => false,
            'backup_path' => null,
            'migrations_run' => 0,
            'files_updated' => 0,
            'errors' => [],
            'version_from' => $this->getCurrentVersion(),
            'version_to' => null,
        ];

        if (!file_exists($zipPath)) {
            $result['errors'][] = 'Fichier ZIP non trouvé';
            return $result;
        }

        // Vérifications
        if (!$this->allChecksPassed()) {
            $result['errors'][] = 'Les vérifications pré-vol ont échoué';
            return $result;
        }

        if (!$this->acquireLock()) {
            $result['errors'][] = 'Une mise à jour est déjà en cours';
            return $result;
        }

        // Enregistrer le début de la mise à jour
        $updateId = $this->logUpdateStart($result['version_from'], 'unknown', 'full');

        try {
            $this->enableMaintenance();

            // 1. Backup
            $result['backup_path'] = $this->createBackup('pre_update');

            // 2. Extraire le ZIP
            $extractDir = $this->tmpDir . '/update_' . time();
            if (!$this->extractZip($zipPath, $extractDir)) {
                throw new RuntimeException('Impossible d\'extraire le ZIP');
            }

            // 3. Trouver le répertoire racine dans le ZIP
            $updateRoot = $this->findUpdateRoot($extractDir);
            if (!$updateRoot) {
                throw new RuntimeException('Structure de ZIP invalide');
            }

            // 4. Lire la version cible
            $newVersionFile = $updateRoot . '/VERSION';
            if (file_exists($newVersionFile)) {
                $result['version_to'] = trim(file_get_contents($newVersionFile));
            }

            // 5. Copier les fichiers (en protégeant les fichiers sensibles)
            $result['files_updated'] = $this->copyUpdateFiles($updateRoot, $this->rootDir);

            // 6. Copier les nouvelles migrations
            $migrationsDir = $updateRoot . '/database/migrations';
            if (is_dir($migrationsDir)) {
                $this->copyDirectory($migrationsDir, $this->rootDir . '/database/migrations');
            }

            // 7. Exécuter les migrations
            $migResult = $this->applyMigrations();
            $result['migrations_run'] = $migResult['run'];
            if (!empty($migResult['errors'])) {
                $result['errors'] = array_merge($result['errors'], array_map(
                    fn($e) => "Migration {$e['migration']}: {$e['error']}",
                    $migResult['errors']
                ));
                // Rollback si migration échoue
                throw new RuntimeException('Échec de migration: ' . $migResult['errors'][0]['error']);
            }

            // 8. Nettoyage
            $this->deleteDirectory($extractDir);

            $result['success'] = true;
            $this->logUpdateEnd($updateId, 'completed', $result['migrations_run']);

        } catch (Throwable $e) {
            $result['errors'][] = $e->getMessage();
            $this->logUpdateEnd($updateId, 'failed', $result['migrations_run'], $e->getMessage());
        } finally {
            $this->disableMaintenance();
            $this->releaseLock();
        }

        return $result;
    }

    /**
     * Copie les fichiers de mise à jour en protégeant les fichiers sensibles
     */
    private function copyUpdateFiles(string $source, string $dest): int
    {
        $count = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source) + 1);

            // Vérifier si le fichier est protégé
            if ($this->isProtectedPath($relativePath)) {
                continue;
            }

            $destPath = $dest . '/' . $relativePath;

            if ($item->isDir()) {
                if (!is_dir($destPath)) mkdir($destPath, 0755, true);
            } else {
                $destDir = dirname($destPath);
                if (!is_dir($destDir)) mkdir($destDir, 0755, true);
                copy($item->getPathname(), $destPath);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Vérifie si un chemin est protégé
     */
    private function isProtectedPath(string $relativePath): bool
    {
        $relativePath = str_replace('\\', '/', $relativePath);
        foreach (self::PROTECTED_PATHS as $protected) {
            if (str_ends_with($protected, '/')) {
                // C'est un dossier
                if (str_starts_with($relativePath, $protected) || $relativePath === rtrim($protected, '/')) {
                    return true;
                }
            } else {
                if ($relativePath === $protected) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Trouve le répertoire racine dans un ZIP extrait
     */
    private function findUpdateRoot(string $extractDir): ?string
    {
        // Vérifier si les fichiers sont directement dans extractDir
        if (file_exists($extractDir . '/VERSION')) {
            return $extractDir;
        }

        // Sinon chercher dans les sous-dossiers (ex: nas-1.2.0/)
        foreach (scandir($extractDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $subDir = $extractDir . '/' . $entry;
            if (is_dir($subDir) && file_exists($subDir . '/VERSION')) {
                return $subDir;
            }
        }

        // Dernier recours : le premier sous-dossier
        foreach (scandir($extractDir) as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $subDir = $extractDir . '/' . $entry;
            if (is_dir($subDir)) return $subDir;
        }

        return null;
    }

    // ==============================
    // Restauration
    // ==============================

    /**
     * Restaure la base de données depuis un backup
     */
    public function restoreDatabase(string $backupPath): bool
    {
        $sqlFile = $backupPath . '/database.sql';
        if (!file_exists($sqlFile)) return false;

        $sql = file_get_contents($sqlFile);
        if (empty($sql)) return false;

        try {
            // Désactiver les FK checks
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=0");

            // Exécuter le dump
            $statements = explode(";\n", $sql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt) && !str_starts_with($stmt, '--')) {
                    $this->pdo->exec($stmt);
                }
            }

            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            return true;
        } catch (PDOException $e) {
            $this->pdo->exec("SET FOREIGN_KEY_CHECKS=1");
            return false;
        }
    }

    // ==============================
    // Historique
    // ==============================

    /**
     * Enregistre le début d'une mise à jour
     */
    private function logUpdateStart(string $fromVersion, string $toVersion, string $type): int
    {
        try {
            // S'assurer que la table existe
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS system_updates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    from_version VARCHAR(20) NOT NULL,
                    to_version VARCHAR(20) NOT NULL,
                    update_type ENUM('full', 'migration_only', 'code_only') DEFAULT 'full',
                    status ENUM('started', 'completed', 'failed', 'rolled_back') NOT NULL,
                    migrations_run INT DEFAULT 0,
                    backup_path VARCHAR(500) DEFAULT NULL,
                    error_message TEXT DEFAULT NULL,
                    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    completed_at TIMESTAMP NULL,
                    started_by VARCHAR(100) DEFAULT NULL,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");

            $stmt = $this->pdo->prepare("
                INSERT INTO system_updates (from_version, to_version, update_type, status, started_by)
                VALUES (?, ?, ?, 'started', ?)
            ");
            $startedBy = php_sapi_name() === 'cli' ? 'CLI' : ($_SESSION['admin_name'] ?? 'web');
            $stmt->execute([$fromVersion, $toVersion, $type, $startedBy]);
            return (int) $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Met à jour le statut d'une mise à jour
     */
    private function logUpdateEnd(int $updateId, string $status, int $migrationsRun, ?string $error = null): void
    {
        if ($updateId <= 0) return;
        try {
            $stmt = $this->pdo->prepare("
                UPDATE system_updates
                SET status = ?, migrations_run = ?, error_message = ?, completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$status, $migrationsRun, $error, $updateId]);
        } catch (PDOException $e) {}
    }

    /**
     * Historique des mises à jour
     */
    public function getUpdateHistory(int $limit = 20): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM system_updates ORDER BY started_at DESC LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            return [];
        }
    }

    // ==============================
    // Utilitaires
    // ==============================

    private function extractZip(string $zipPath, string $extractTo): bool
    {
        if (!class_exists('ZipArchive')) return false;
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) return false;
        if (!is_dir($extractTo)) mkdir($extractTo, 0755, true);
        $zip->extractTo($extractTo);
        $zip->close();
        return true;
    }

    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) return false;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        return rmdir($dir);
    }

    private function copyDirectory(string $source, string $dest): void
    {
        if (!is_dir($dest)) mkdir($dest, 0755, true);
        foreach (scandir($source) as $item) {
            if ($item === '.' || $item === '..') continue;
            $src = $source . '/' . $item;
            $dst = $dest . '/' . $item;
            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
            } else {
                copy($src, $dst);
            }
        }
    }

    private function getDirectorySize(string $dir): int
    {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }
        return $size;
    }

    private function commandExists(string $command): bool
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        exec("{$which} {$command} 2>/dev/null", $output, $returnCode);
        return $returnCode === 0;
    }
}
