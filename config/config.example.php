<?php
/**
 * Configuration RADIUS Manager
 * Copiez ce fichier en config.php et modifiez les valeurs
 */

return [
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'radius_db',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],

    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],

    'app' => [
        'name' => 'RADIUS Manager',
        'version' => '1.0.0',
        'timezone' => 'Europe/London',
        'language' => 'fr',
        'debug' => false,
        'session_lifetime' => 3600,
    ],

    'currency' => 'XOF',

    'security' => [
        'csrf_token_name' => 'csrf_token',
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
    ],

    'options' => [
        'debug' => true,
        'log_file' => __DIR__ . '/../logs/radius_new.log',
        'default_session_timeout' => 86400,
        'default_idle_timeout' => 300,
    ]
];
