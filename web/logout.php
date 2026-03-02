<?php
/**
 * Déconnexion
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
    // En cas d'erreur, simplement détruire la session PHP
    session_start();
    $_SESSION = [];
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

// Utiliser le service d'authentification pour déconnecter proprement
$auth = new AuthService($pdo);
$auth->logout();

// Rediriger vers login
header('Location: login.php');
exit;
