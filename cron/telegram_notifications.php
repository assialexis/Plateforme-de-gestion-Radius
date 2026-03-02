#!/usr/bin/env php
<?php
/**
 * Cron Job pour les notifications Telegram d'expiration
 *
 * Usage: php telegram_notifications.php
 *
 * Ajouter au crontab pour executer toutes les heures:
 * 0 * * * * /usr/bin/php /path/to/nas/cron/telegram_notifications.php >> /var/log/telegram_cron.log 2>&1
 *
 * Ou pour executer a une heure specifique (ex: 9h du matin):
 * 0 9 * * * /usr/bin/php /path/to/nas/cron/telegram_notifications.php >> /var/log/telegram_cron.log 2>&1
 */

// Configuration
define('CRON_MODE', true);

// Chemin vers la racine du projet
$basePath = dirname(__DIR__);

// Charger les dependances
require_once $basePath . '/src/Utils/helpers.php';
require_once $basePath . '/src/Radius/RadiusDatabase.php';
require_once $basePath . '/src/Services/TelegramNotifier.php';

// Charger la configuration
$config = require $basePath . '/config/config.php';

// Logger simple pour le cron
function cronLog($message, $level = 'INFO') {
    $date = date('Y-m-d H:i:s');
    echo "[$date] [$level] $message\n";
}

cronLog("=== Demarrage du cron Telegram Notifications ===");

try {
    // Connexion a la base de donnees
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();

    cronLog("Connexion a la base de donnees etablie");

    // Verifier si les notifications sont activees
    $stmt = $pdo->query("SELECT is_enabled FROM telegram_config LIMIT 1");
    $configRow = $stmt->fetch();

    if (!$configRow || !$configRow['is_enabled']) {
        cronLog("Les notifications Telegram sont desactivees", 'WARN');
        exit(0);
    }

    cronLog("Notifications Telegram activees, traitement en cours...");

    // Initialiser le notifier
    $notifier = new TelegramNotifier($pdo);

    // Traiter les notifications d'expiration
    $result = $notifier->processExpirationNotifications();

    if ($result['success']) {
        $results = $result['results'];
        cronLog("Traitement termine:");
        cronLog("  - Envoyes: " . ($results['sent'] ?? 0));
        cronLog("  - Ignores (deja envoyes): " . ($results['skipped'] ?? 0));
        cronLog("  - Echecs: " . ($results['failed'] ?? 0));

        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                cronLog("  Erreur utilisateur {$error['user_id']}: {$error['error']}", 'ERROR');
            }
        }
    } else {
        cronLog("Erreur lors du traitement: " . ($result['error'] ?? 'Erreur inconnue'), 'ERROR');
        exit(1);
    }

} catch (Exception $e) {
    cronLog("Exception: " . $e->getMessage(), 'ERROR');
    cronLog("Trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

cronLog("=== Fin du cron Telegram Notifications ===");
exit(0);
