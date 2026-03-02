#!/usr/bin/env php
<?php
/**
 * Script d'installation RADIUS Manager
 *
 * Usage: php install.php
 */

echo "
╔═══════════════════════════════════════════════════════════╗
║           RADIUS Manager - Installation                   ║
╚═══════════════════════════════════════════════════════════╝

";

// Vérifications préalables
echo "[1/5] Vérification des prérequis...\n";

$errors = [];

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $errors[] = "PHP 8.0+ requis (version actuelle: " . PHP_VERSION . ")";
}

if (!extension_loaded('pdo_mysql')) {
    $errors[] = "Extension 'pdo_mysql' manquante";
}

if (!extension_loaded('sockets')) {
    $errors[] = "Extension 'sockets' manquante (requise pour le serveur RADIUS)";
}

if (!empty($errors)) {
    echo "\n❌ Erreurs détectées:\n";
    foreach ($errors as $error) {
        echo "   - {$error}\n";
    }
    echo "\nCorrigez ces erreurs avant de continuer.\n";
    exit(1);
}

echo "   ✓ PHP " . PHP_VERSION . "\n";
echo "   ✓ Extension PDO MySQL\n";
echo "   ✓ Extension sockets\n";

// Configuration base de données
echo "\n[2/5] Configuration de la base de données...\n";

$dbHost = readline("   Host MySQL [127.0.0.1]: ") ?: '127.0.0.1';
$dbPort = readline("   Port MySQL [3306]: ") ?: '3306';
$dbName = readline("   Nom de la base [radius_db]: ") ?: 'radius_db';
$dbUser = readline("   Utilisateur MySQL [root]: ") ?: 'root';
$dbPass = readline("   Mot de passe MySQL: ");

// Test de connexion
echo "\n[3/5] Test de connexion à MySQL...\n";

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "   ✓ Connexion réussie\n";
} catch (PDOException $e) {
    echo "   ❌ Erreur de connexion: " . $e->getMessage() . "\n";
    exit(1);
}

// Créer la base de données
echo "\n[4/5] Création de la base de données et des tables...\n";

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$dbName}`");
    echo "   ✓ Base de données '{$dbName}' créée/sélectionnée\n";

    // Exécuter le schéma
    $schemaFile = __DIR__ . '/database/schema.sql';
    if (file_exists($schemaFile)) {
        $schema = file_get_contents($schemaFile);
        // Ignorer les commandes CREATE DATABASE et USE
        $schema = preg_replace('/CREATE DATABASE.*?;/s', '', $schema);
        $schema = preg_replace('/USE.*?;/s', '', $schema);

        // Exécuter les requêtes une par une
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($queries as $query) {
            if (!empty($query) && strpos($query, '--') !== 0) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Ignorer les erreurs de tables/clés existantes
                    if (strpos($e->getMessage(), 'already exists') === false &&
                        strpos($e->getMessage(), 'Duplicate') === false) {
                        throw $e;
                    }
                }
            }
        }
        echo "   ✓ Tables créées\n";
    }
} catch (PDOException $e) {
    echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

// Mettre à jour la configuration
echo "\n[5/5] Mise à jour du fichier de configuration...\n";

$configContent = "<?php
/**
 * Configuration du serveur RADIUS
 * Généré automatiquement le " . date('Y-m-d H:i:s') . "
 */

return [
    // Configuration base de données
    'database' => [
        'host' => '{$dbHost}',
        'port' => {$dbPort},
        'dbname' => '{$dbName}',
        'username' => '{$dbUser}',
        'password' => '{$dbPass}',
        'charset' => 'utf8mb4'
    ],

    // Configuration serveur RADIUS
    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],

    // Configuration web
    'app' => [
        'name' => 'RADIUS Manager',
        'version' => '1.0.0',
        'timezone' => 'Africa/Douala',
        'language' => 'fr',
        'debug' => false,
        'session_lifetime' => 3600,
    ],

    // Sécurité
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
    ],

    // Options RADIUS
    'options' => [
        'debug' => true,
        'log_file' => __DIR__ . '/../logs/radius.log',
        'default_session_timeout' => 86400,
        'default_idle_timeout' => 300,
    ]
];
";

file_put_contents(__DIR__ . '/config/config.php', $configContent);
echo "   ✓ Configuration enregistrée\n";

// Créer les dossiers nécessaires
@mkdir(__DIR__ . '/logs', 0755, true);

// Résumé
echo "
╔═══════════════════════════════════════════════════════════╗
║           Installation terminée avec succès!              ║
╚═══════════════════════════════════════════════════════════╝

📋 Informations de connexion:
   - URL Interface: http://votre-serveur/radius-server/web/
   - Utilisateur:   admin
   - Mot de passe:  admin123 (à changer!)

🚀 Pour démarrer le serveur RADIUS:
   cd " . __DIR__ . "
   sudo php radius_server.php

📝 Notes importantes:
   - Changez le mot de passe admin par défaut
   - Le serveur RADIUS nécessite les droits root (ports < 1024)
   - Configurez votre MikroTik avec le secret 'testing123'

Bonne utilisation!
";
