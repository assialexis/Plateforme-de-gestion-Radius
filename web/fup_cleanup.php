<?php
require_once __DIR__ . '/../config/config.php';
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Annuler toutes les commandes FUP pending/sent
$stmt = $pdo->prepare("UPDATE router_commands SET status = 'cancelled' WHERE command_type IN ('set_fup', 'create_fup') AND status IN ('pending', 'sent')");
$stmt->execute();
echo "Commandes FUP annulees: " . $stmt->rowCount() . "\n";

// Reset FUP pour alex
$stmt = $pdo->prepare("UPDATE pppoe_users SET fup_triggered = 0, fup_triggered_at = NULL WHERE username = 'alex'");
$stmt->execute();
echo "FUP reset pour alex: " . $stmt->rowCount() . "\n";

// Auto-suppression
unlink(__FILE__);
