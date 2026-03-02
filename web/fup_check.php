<?php
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== Dernieres commandes router ===\n";
$stmt = $pdo->query("SELECT id, router_id, command_type, command_description, status, created_at FROM router_commands ORDER BY id DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "#{$row['id']} [{$row['status']}] {$row['command_type']} - {$row['command_description']} ({$row['router_id']}) @ {$row['created_at']}\n";
}

echo "\n=== Statut FUP alex ===\n";
$stmt = $pdo->query("SELECT username, fup_triggered, fup_triggered_at, fup_data_used FROM pppoe_users WHERE username='alex'");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($user);

// Auto-suppression
unlink(__FILE__);
