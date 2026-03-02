<?php
/**
 * Page d'inscription administrateur
 */

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Auth/User.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die(__('auth.db_connection_error'));
}

$auth = new AuthService($pdo);

// Si déjà connecté, rediriger
if ($auth->isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$appName = $config['app']['name'] ?? 'RADIUS Manager';

include __DIR__ . '/views/register.php';
