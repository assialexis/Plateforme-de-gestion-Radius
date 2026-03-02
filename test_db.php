<?php
session_start();
$_SESSION['lang'] = 'fr';

require_once __DIR__ . '/config/config.php';
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/src/Auth/User.php';
require_once __DIR__ . '/src/Auth/AuthService.php';
require_once __DIR__ . '/src/Api/NotificationController.php';
require_once __DIR__ . '/src/Utils/helpers.php';

$db = new RadiusDatabase($config['database']);
$auth = new AuthService($db);

// Mock getJsonBody
function getJsonBodyMock() {
    return ['title' => 'Test', 'message' => 'Hello', 'type' => 'info', 'is_active' => 1];
}
// Override getJsonBody
runkit7_function_redefine('getJsonBody', '', 'return getJsonBodyMock();');

// But since runkit isn't installed likely, let me just mock input stream instead.
