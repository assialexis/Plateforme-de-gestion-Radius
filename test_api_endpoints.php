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

$endpoints = ['stats', 'topUsers', 'liveSessions', 'hourlyStats', 'dailyStats', 'fupAlerts'];
foreach ($endpoints as $method) {
    try {
        ob_start();
        $controller->$method();
        ob_get_clean();
        echo "$method: OK\n";
    }
    catch (Exception $e) {
        ob_get_clean();
        echo "$method: ERROR - " . $e->getMessage() . "\n";
    }
}