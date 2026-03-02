<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Auth/User.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/src/Api/NotificationController.php';
require_once __DIR__ . '/src/Utils/helpers.php';

$config = require __DIR__ . '/config/config.php';
$db = new RadiusDatabase($config['database']);
$pdo = $db->getPdo();

// find superadmin
$stmt = $pdo->query("SELECT id FROM users WHERE role = 'superadmin' LIMIT 1");
$superadminId = $stmt->fetchColumn() ?: 1;
$_SESSION['admin_id'] = $superadminId;

// Fake the request
$_SERVER['REQUEST_METHOD'] = 'POST';
$json = json_encode(['title' => 'Test HTTP', 'message' => 'Hello', 'type' => 'info', 'is_active' => 1]);
file_put_contents('php://memory', $json);
// We can't easily mock php://input without stream wrappers, so we will replace getJsonBody() temporarily or use curl locally

