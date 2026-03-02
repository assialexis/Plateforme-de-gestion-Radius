#!/usr/bin/env php
<?php
/**
 * Serveur RADIUS - Point d'entrée
 *
 * Usage:
 *   php radius_server.php           - Démarrer en premier plan
 *   php radius_server.php start     - Démarrer en premier plan
 *   php radius_server.php daemon    - Démarrer en arrière-plan (Linux)
 *
 * Ports:
 *   1812 - Authentification
 *   1813 - Accounting
 *
 * Note: Les ports < 1024 nécessitent les droits root
 */

declare(strict_types=1);

// Vérifier PHP CLI
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande.\n");
}

// Vérifier l'extension sockets
if (!extension_loaded('sockets')) {
    die("L'extension PHP 'sockets' est requise.\n");
}

// Charger les classes
require_once __DIR__ . '/src/Radius/RadiusPacket.php';
require_once __DIR__ . '/src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/src/Radius/RadiusServer.php';

// Créer le dossier de logs
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Gérer les arguments
$command = $argv[1] ?? 'start';

if ($command === 'daemon' && PHP_OS_FAMILY !== 'Windows') {
    // Mode démon (Linux/Mac)
    $pid = pcntl_fork();

    if ($pid === -1) {
        die("Impossible de créer le processus fils.\n");
    } elseif ($pid > 0) {
        // Processus parent
        file_put_contents(__DIR__ . '/radius.pid', $pid);
        echo "Serveur RADIUS démarré en arrière-plan (PID: {$pid})\n";
        exit(0);
    }

    // Processus fils - devenir leader de session
    posix_setsid();
}

// Démarrer le serveur
try {
    $configFile = __DIR__ . '/config/config.php';

    if (!file_exists($configFile)) {
        die("Fichier de configuration non trouvé: {$configFile}\n");
    }

    $server = new RadiusServer($configFile);
    $server->start();
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
