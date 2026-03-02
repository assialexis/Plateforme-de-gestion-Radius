<?php
$config = require 'config/config.php';
$db = $config['database'];
try {
    $pdo = new PDO("mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}", $db['username'], $db['password']);
    
    // Check tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(", ", $tables) . "\n";
    
    // Check if user table exists and its structure
    foreach (['admin', 'admins', 'users', 'auth_admins'] as $table) {
        if (in_array($table, $tables)) {
            echo "Found table: $table\n";
            $stmt = $pdo->query("SELECT id, username, role FROM $table");
            print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;
        }
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
