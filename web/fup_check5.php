<?php
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== 10 dernieres commandes ===\n";
$stmt = $pdo->query("SELECT id, router_id, command_type, status, command_description, created_at FROM router_commands ORDER BY id DESC LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "#{$row['id']} [{$row['status']}] {$row['router_id']} | {$row['command_type']} | {$row['command_description']} | {$row['created_at']}\n";
}

echo "\n=== FUP alex ===\n";
$stmt = $pdo->query("SELECT username, fup_triggered, fup_triggered_at, fup_data_used, zone_id, nas_id FROM pppoe_users WHERE username='alex'");
print_r($stmt->fetch(PDO::FETCH_ASSOC));

echo "\n=== NAS avec router_id ===\n";
$stmt = $pdo->query("SELECT id, router_id, shortname, zone_id FROM nas WHERE router_id IS NOT NULL AND router_id != ''");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "NAS #{$row['id']} | {$row['router_id']} | {$row['shortname']} | zone={$row['zone_id']}\n";
}

unlink(__FILE__);
