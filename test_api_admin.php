<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$config = require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Utils/helpers.php';
require_once __DIR__ . '/src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/src/Api/MonitoringController.php';
require_once __DIR__ . '/src/Auth/AuthService.php';

$pdo = new PDO(
    "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
    $config['database']['username'],
    $config['database']['password'],
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
$db = new RadiusDatabase($config['database']);

class FakeAuth extends AuthService
{
    public function __construct()
    {
    }
    public function getAdminId(?int $userId = null): ?int
    {
        return 1;
    }
}

$controller = new MonitoringController($db, new FakeAuth());
$method = $argv[1] ?? 'stats';
try {
    $controller->$method();
}
catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}