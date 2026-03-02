<?php
/**
 * Nettoyage des commandes routeur
 *
 * À exécuter périodiquement via cron (toutes les 5 minutes) :
 * */5 * * * * php /path/to/nas/cron/router_commands_cleanup.php
 *
 * Actions :
 * 1. Expirer les commandes pending dépassées
 * 2. Supprimer les anciennes commandes (>30 jours)
 * 3. Reset les commandes sent bloquées (>5 min sans confirmation)
 */

$config = require __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    error_log("Router commands cleanup: DB error - " . $e->getMessage());
    exit(1);
}

// 1. Expirer les commandes pending avec expires_at dépassé
$stmt = $pdo->exec(
    "UPDATE router_commands SET status = 'expired'
     WHERE status = 'pending' AND expires_at IS NOT NULL AND expires_at < NOW()"
);
$expired = $stmt;

// 2. Reset les commandes 'sent' bloquées (>5 minutes sans confirmation)
$stmt = $pdo->exec(
    "UPDATE router_commands SET status = 'pending', sent_at = NULL
     WHERE status = 'sent' AND sent_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
);
$reset = $stmt;

// 3. Supprimer les anciennes commandes (>30 jours, statuts terminaux uniquement)
$stmt = $pdo->exec(
    "DELETE FROM router_commands
     WHERE status IN ('executed', 'failed', 'expired', 'cancelled')
     AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
$deleted = $stmt;

// Log
$msg = sprintf(
    "Router commands cleanup: %d expired, %d reset, %d deleted",
    $expired, $reset, $deleted
);

if ($expired > 0 || $reset > 0 || $deleted > 0) {
    error_log($msg);
}

echo $msg . "\n";
