<?php
$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("SELECT command_content FROM router_commands WHERE id = 232");
echo $stmt->fetchColumn();

unlink(__FILE__);
