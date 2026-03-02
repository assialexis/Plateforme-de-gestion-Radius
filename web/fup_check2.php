<?php
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 15 dernieres commandes ===\n";
$stmt = $pdo->query("SELECT id, router_id, command_type, status, command_description, created_at, executed_at FROM router_commands ORDER BY id DESC LIMIT 15");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "#{$row['id']} [{$row['status']}] {$row['router_id']} | {$row['command_type']} | {$row['command_description']} | {$row['created_at']}\n";
}

echo "\n=== Commandes pending pour NAS-8D87B889-60D2 ===\n";
$stmt = $pdo->query("SELECT id, command_type, command_description, status, command_content FROM router_commands WHERE router_id='NAS-8D87B889-60D2' AND status IN ('pending','sent') ORDER BY id DESC LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "#{$row['id']} [{$row['status']}] {$row['command_type']} - {$row['command_description']}\n";
    echo "CONTENU: " . substr($row['command_content'], 0, 200) . "\n\n";
}

echo "=== Statut FUP alex ===\n";
$stmt = $pdo->query("SELECT username, fup_triggered, fup_triggered_at, fup_data_used FROM pppoe_users WHERE username='alex'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

unlink(__FILE__);
