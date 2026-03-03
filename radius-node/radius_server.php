<?php
/**
 * Point d'entrée du serveur RADIUS pour un nœud distant
 * Utilise la DB locale pour l'authentification (faible latence)
 */

require_once __DIR__ . '/src/RadiusServer.php';

$configFile = __DIR__ . '/config/config.php';

if (!file_exists($configFile)) {
    echo "ERREUR: Fichier de configuration manquant.\n";
    echo "Copiez config/config.example.php vers config/config.php et modifiez les valeurs.\n";
    exit(1);
}

$config = require $configFile;

// Vérifier la config DB
if (empty($config['database']['dbname'])) {
    echo "ERREUR: Configuration de la base de données manquante.\n";
    exit(1);
}

echo "===========================================\n";
echo "   RADIUS Node Server\n";
echo "   Code: " . ($config['platform']['server_code'] ?? 'N/A') . "\n";
echo "===========================================\n";

try {
    $server = new RadiusServer($configFile);
    $server->start();
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
