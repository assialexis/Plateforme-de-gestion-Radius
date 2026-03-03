<?php
/**
 * Configuration du nœud RADIUS
 * Copier ce fichier en config.php et modifier les valeurs
 */

return [
    // Connexion à la plateforme centrale
    'platform' => [
        'url' => 'https://votre-plateforme.com',  // URL de la plateforme centrale
        'server_code' => 'RS-XXXXXXXX',            // Code du serveur (depuis la plateforme)
        'sync_token' => '',                         // Token de sync (depuis la plateforme)
        'platform_token' => '',                     // Token webhook (depuis la plateforme)
        'sync_interval' => 60,                      // Intervalle de sync en secondes
    ],

    // Base de données locale
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'radius_node',
        'username' => 'radius_node',
        'password' => '',
        'charset' => 'utf8mb4',
    ],

    // Serveur RADIUS
    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],

    // Options
    'options' => [
        'debug' => false,
        'log_file' => __DIR__ . '/../logs/radius.log',
        'default_session_timeout' => 86400,
        'default_idle_timeout' => 300,
        'acct_interim_interval' => 60,
    ],
];
