#!/usr/bin/env php
<?php
/**
 * Cron Job pour les notifications WhatsApp d'expiration via Green API
 *
 * Usage: php whatsapp_notifications.php
 *
 * Ajouter au crontab:
 * 0 9 * * * /usr/bin/php /path/to/nas/cron/whatsapp_notifications.php >> /var/log/whatsapp_cron.log 2>&1
 */

define('CRON_MODE', true);

$basePath = dirname(__DIR__);

require_once $basePath . '/src/Utils/helpers.php';
require_once $basePath . '/src/Radius/RadiusDatabase.php';
require_once $basePath . '/src/Services/WhatsAppNotifier.php';

$config = require $basePath . '/config/config.php';

function cronLog($message, $level = 'INFO') {
    $date = date('Y-m-d H:i:s');
    echo "[$date] [$level] $message\n";
}

cronLog("=== Demarrage du cron WhatsApp Notifications ===");

try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();

    cronLog("Connexion a la base de donnees etablie");

    $stmt = $pdo->query("SELECT is_enabled FROM whatsapp_config LIMIT 1");
    $configRow = $stmt->fetch();

    if (!$configRow || !$configRow['is_enabled']) {
        cronLog("Les notifications WhatsApp sont desactivees", 'WARN');
        exit(0);
    }

    cronLog("Notifications WhatsApp activees, traitement en cours...");

    $notifier = new WhatsAppNotifier($pdo);
    $result = $notifier->processExpirationNotifications();

    if ($result['success']) {
        $results = $result['results'];
        cronLog("Traitement termine:");
        cronLog("  - Traites: " . ($results['processed'] ?? 0));
        cronLog("  - Envoyes: " . ($results['sent'] ?? 0));
        cronLog("  - Ignores: " . ($results['skipped'] ?? 0));
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

cronLog("=== Fin du cron WhatsApp Notifications ===");
exit(0);
