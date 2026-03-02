<?php
/**
 * Page de connexion
 */

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Auth/User.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Connexion à la base de données
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

// Initialiser le service d'authentification
$auth = new AuthService($pdo);

// Si déjà connecté, rediriger vers le dashboard
if ($auth->isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$appName = $config['app']['name'] ?? 'RADIUS Manager';
$error = $_GET['error'] ?? null;

// Afficher la page de login
include __DIR__ . '/views/login.php';
