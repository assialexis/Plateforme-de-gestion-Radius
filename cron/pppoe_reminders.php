#!/usr/bin/env php
<?php
/**
 * Cron Job pour les rappels automatiques PPPoE (WhatsApp / SMS)
 *
 * Usage: php pppoe_reminders.php
 *
 * Ajouter au crontab:
 * 0 9 * * * /usr/bin/php /path/to/nas/cron/pppoe_reminders.php >> /var/log/pppoe_reminders_cron.log 2>&1
 */

define('CRON_MODE', true);

$basePath = dirname(__DIR__);

require_once $basePath . '/src/Utils/helpers.php';
require_once $basePath . '/src/Radius/RadiusDatabase.php';
require_once $basePath . '/src/Api/PppoeReminderController.php';
require_once $basePath . '/src/Services/WhatsAppNotifier.php';
require_once $basePath . '/src/Services/SmsService.php';

$config = require $basePath . '/config/config.php';

function cronLog($message, $level = 'INFO') {
    $date = date('Y-m-d H:i:s');
    echo "[$date] [$level] $message\n";
}

cronLog("=== Demarrage du cron PPPoE Reminders ===");

try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();

    cronLog("Connexion a la base de donnees etablie");

    // Trouver tous les admins ayant activé les rappels PPPoE
    $stmt = $pdo->query("
        SELECT DISTINCT s.admin_id
        FROM settings s
        WHERE s.setting_key = 'pppoe_reminders_enabled'
        AND s.setting_value = '1'
    ");
    $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($admins)) {
        cronLog("Aucun admin n'a active les rappels PPPoE", 'WARN');
        exit(0);
    }

    cronLog("Admins avec rappels actifs: " . count($admins));

    $totalProcessed = 0;
    $totalSent = 0;
    $totalFailed = 0;
    $totalSkipped = 0;

    foreach ($admins as $adminId) {
        cronLog("--- Traitement admin #{$adminId} ---");

        try {
            $results = PppoeReminderController::processForAdmin($pdo, (int)$adminId);

            $totalProcessed += $results['processed'];
            $totalSent += $results['sent'];
            $totalFailed += $results['failed'];
            $totalSkipped += $results['skipped'];

            cronLog("  Admin #{$adminId}: Traites={$results['processed']}, Envoyes={$results['sent']}, Echecs={$results['failed']}, Ignores={$results['skipped']}");
        } catch (Exception $e) {
            cronLog("  Erreur admin #{$adminId}: " . $e->getMessage(), 'ERROR');
        }
    }

    cronLog("=== Resume global ===");
    cronLog("  Admins traites: " . count($admins));
    cronLog("  Total traites: {$totalProcessed}");
    cronLog("  Total envoyes: {$totalSent}");
    cronLog("  Total echecs: {$totalFailed}");
    cronLog("  Total ignores: {$totalSkipped}");

} catch (Exception $e) {
    cronLog("Exception: " . $e->getMessage(), 'ERROR');
    cronLog("Trace: " . $e->getTraceAsString(), 'ERROR');
    exit(1);
}

cronLog("=== Fin du cron PPPoE Reminders ===");
exit(0);
