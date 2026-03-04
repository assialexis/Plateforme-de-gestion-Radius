<?php
/**
 * MigrationRunner - Exécute les migrations SQL depuis database/migrations/
 *
 * Suit les migrations appliquées dans la table schema_migrations.
 * Auto-crée la table de suivi si elle n'existe pas (bootstrap).
 */
class MigrationRunner
{
    private PDO $pdo;
    private string $migrationsDir;

    public function __construct(PDO $pdo, string $migrationsDir)
    {
        $this->pdo = $pdo;
        $this->migrationsDir = rtrim($migrationsDir, '/');
    }

    /**
     * Crée la table schema_migrations si elle n'existe pas
     */
    public function ensureTrackingTable(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL DEFAULT 1,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                execution_time_ms INT DEFAULT NULL,
                checksum VARCHAR(64) DEFAULT NULL,
                INDEX idx_batch (batch)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    /**
     * Vérifie si la table schema_migrations existe
     */
    public function trackingTableExists(): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM schema_migrations LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Retourne les noms des migrations déjà exécutées
     */
    public function getExecutedMigrations(): array
    {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM schema_migrations ORDER BY migration");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Retourne la liste des fichiers de migration sur le disque (triés)
     */
    public function getDiskMigrations(): array
    {
        $files = glob($this->migrationsDir . '/*.sql');
        if (!$files) return [];

        $migrations = [];
        foreach ($files as $file) {
            $migrations[] = pathinfo($file, PATHINFO_FILENAME);
        }
        sort($migrations);
        return $migrations;
    }

    /**
     * Retourne les migrations en attente (sur le disque mais pas encore exécutées)
     */
    public function getPendingMigrations(): array
    {
        $executed = $this->getExecutedMigrations();
        $disk = $this->getDiskMigrations();
        return array_values(array_diff($disk, $executed));
    }

    /**
     * Exécute une migration unique
     * @return array ['success' => bool, 'time_ms' => int, 'error' => string|null]
     */
    public function runMigration(string $migration, int $batch): array
    {
        $file = $this->migrationsDir . '/' . $migration . '.sql';
        if (!file_exists($file)) {
            return ['success' => false, 'time_ms' => 0, 'error' => "Fichier non trouvé: {$file}"];
        }

        $sql = file_get_contents($file);
        $checksum = hash('sha256', $sql);

        $start = microtime(true);

        try {
            // Découper en instructions individuelles
            $statements = $this->splitStatements($sql);

            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (empty($statement)) continue;
                $this->pdo->exec($statement);
            }

            $timeMs = (int)((microtime(true) - $start) * 1000);

            // Enregistrer la migration comme exécutée
            $stmt = $this->pdo->prepare("
                INSERT INTO schema_migrations (migration, batch, execution_time_ms, checksum)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$migration, $batch, $timeMs, $checksum]);

            return ['success' => true, 'time_ms' => $timeMs, 'error' => null];
        } catch (PDOException $e) {
            $timeMs = (int)((microtime(true) - $start) * 1000);
            return ['success' => false, 'time_ms' => $timeMs, 'error' => $e->getMessage()];
        }
    }

    /**
     * Exécute toutes les migrations en attente
     * @return array ['run' => int, 'errors' => array, 'batch' => int]
     */
    public function runAll(): array
    {
        $pending = $this->getPendingMigrations();
        if (empty($pending)) {
            return ['run' => 0, 'errors' => [], 'batch' => 0];
        }

        $batch = $this->getNextBatch();
        $run = 0;
        $errors = [];

        foreach ($pending as $migration) {
            $result = $this->runMigration($migration, $batch);
            if ($result['success']) {
                $run++;
            } else {
                $errors[] = [
                    'migration' => $migration,
                    'error' => $result['error'],
                    'time_ms' => $result['time_ms']
                ];
                // Arrêter à la première erreur
                break;
            }
        }

        return ['run' => $run, 'errors' => $errors, 'batch' => $batch];
    }

    /**
     * Marque toutes les migrations existantes comme déjà exécutées (baseline)
     * Utilisé pour les installations existantes qui passent au nouveau système
     */
    public function baseline(): int
    {
        $disk = $this->getDiskMigrations();
        $executed = $this->getExecutedMigrations();
        $toBaseline = array_diff($disk, $executed);

        if (empty($toBaseline)) return 0;

        $batch = $this->getNextBatch();
        $count = 0;

        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO schema_migrations (migration, batch, execution_time_ms, checksum)
            VALUES (?, ?, 0, ?)
        ");

        foreach ($toBaseline as $migration) {
            $file = $this->migrationsDir . '/' . $migration . '.sql';
            $checksum = file_exists($file) ? hash_file('sha256', $file) : null;
            $stmt->execute([$migration, $batch, $checksum]);
            $count++;
        }

        return $count;
    }

    /**
     * Retourne les détails de toutes les migrations (exécutées + en attente)
     */
    public function getStatus(): array
    {
        $disk = $this->getDiskMigrations();
        $executed = $this->getExecutedMigrations();

        // Charger les détails des migrations exécutées
        $details = [];
        try {
            $stmt = $this->pdo->query("SELECT * FROM schema_migrations ORDER BY migration");
            while ($row = $stmt->fetch()) {
                $details[$row['migration']] = $row;
            }
        } catch (PDOException $e) {}

        $migrations = [];
        foreach ($disk as $migration) {
            $isExecuted = in_array($migration, $executed);
            $migrations[] = [
                'name' => $migration,
                'status' => $isExecuted ? 'executed' : 'pending',
                'executed_at' => $details[$migration]['executed_at'] ?? null,
                'execution_time_ms' => $details[$migration]['execution_time_ms'] ?? null,
                'batch' => $details[$migration]['batch'] ?? null,
            ];
        }

        return [
            'total' => count($disk),
            'executed' => count($executed),
            'pending' => count($disk) - count($executed),
            'migrations' => $migrations,
        ];
    }

    /**
     * Prochain numéro de batch
     */
    private function getNextBatch(): int
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(batch) FROM schema_migrations");
            return ((int)$stmt->fetchColumn()) + 1;
        } catch (PDOException $e) {
            return 1;
        }
    }

    /**
     * Découpe le SQL en instructions individuelles (gère les DELIMITER, commentaires, etc.)
     */
    private function splitStatements(string $sql): array
    {
        // Supprimer les commentaires sur une ligne
        $sql = preg_replace('/^--.*$/m', '', $sql);
        // Supprimer les commentaires bloc
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        // Découper par point-virgule
        $statements = [];
        $current = '';

        foreach (explode(';', $sql) as $part) {
            $trimmed = trim($part);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
        }

        return $statements;
    }
}
