<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . '/../config/config.php';
$pdo = new PDO(
    "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
    $config['database']['username'],
    $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

require_once __DIR__ . '/../src/Mikrotik/CommandSender.php';
$cmd = new MikroTikCommandSender($pdo);

echo "Test removeFupQueue:\n";
try {
    $result = $cmd->removeFupQueue('NAS-8D87B889-60D2', 'alex');
    echo "Resultat: " . var_export($result, true) . "\n";
} catch (Throwable $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\nTest disconnectPPPoEUser:\n";
try {
    $result = $cmd->disconnectPPPoEUser('NAS-8D87B889-60D2', 'alex');
    echo "Resultat: " . var_export($result, true) . "\n";
} catch (Throwable $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

unlink(__FILE__);
