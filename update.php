<?php
/**
 * CLI de mise à jour - RADIUS Manager
 *
 * Usage:
 *   php update.php status     — Version actuelle + migrations en attente
 *   php update.php check      — Vérifier les mises à jour disponibles
 *   php update.php migrate    — Exécuter les migrations en attente
 *   php update.php baseline   — Marquer les migrations existantes comme appliquées
 *   php update.php backup     — Créer une sauvegarde
 *   php update.php restore    — Restaurer depuis la dernière sauvegarde
 *   php update.php history    — Historique des mises à jour
 *   php update.php preflight  — Vérifications pré-vol
 *   php update.php apply <zip> — Appliquer une mise à jour depuis un ZIP
 */

if (php_sapi_name() !== 'cli') {
    die('Ce script doit être exécuté en ligne de commande.');
}

// Couleurs terminal
function color(string $text, string $color): string
{
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'cyan' => "\033[36m",
        'bold' => "\033[1m",
        'dim' => "\033[2m",
        'reset' => "\033[0m",
    ];
    return ($colors[$color] ?? '') . $text . $colors['reset'];
}

function line(string $text = ''): void
{
    echo $text . PHP_EOL;
}

function title(string $text): void
{
    line();
    line(color("  {$text}", 'bold'));
    line(color('  ' . str_repeat('─', strlen($text)), 'dim'));
}

function success(string $text): void { line(color("  ✓ ", 'green') . $text); }
function error(string $text): void { line(color("  ✗ ", 'red') . $text); }
function info(string $text): void { line(color("  ℹ ", 'blue') . $text); }
function warning(string $text): void { line(color("  ⚠ ", 'yellow') . $text); }

// Charger la configuration
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    error('Fichier config/config.php non trouvé.');
    exit(1);
}

$config = require $configFile;
$dbConfig = $config['database'];

// Connexion DB
try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname'], $dbConfig['charset']);
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error('Connexion DB échouée: ' . $e->getMessage());
    exit(1);
}

require_once __DIR__ . '/src/Update/Updater.php';

$updateUrl = $config['app']['update_url'] ?? null;
$updater = new Updater($pdo, $updateUrl);

// Commande
$command = $argv[1] ?? 'status';

switch ($command) {
    case 'status':
        cmdStatus($updater);
        break;
    case 'check':
        cmdCheck($updater);
        break;
    case 'migrate':
        cmdMigrate($updater);
        break;
    case 'baseline':
        cmdBaseline($updater);
        break;
    case 'backup':
        cmdBackup($updater);
        break;
    case 'restore':
        cmdRestore($updater);
        break;
    case 'history':
        cmdHistory($updater);
        break;
    case 'preflight':
        cmdPreflight($updater);
        break;
    case 'apply':
        $zipPath = $argv[2] ?? null;
        cmdApply($updater, $zipPath);
        break;
    default:
        error("Commande inconnue: {$command}");
        line();
        showHelp();
        exit(1);
}

// ==============================
// Commandes
// ==============================

function cmdStatus(Updater $updater): void
{
    title('Statut du système');

    $version = $updater->getCurrentVersion();
    info("Version installée: " . color($version, 'cyan'));

    $status = $updater->getMigrationStatus();
    info("Migrations totales: {$status['total']}");
    info("Migrations exécutées: " . color((string)$status['executed'], 'green'));

    if ($status['pending'] > 0) {
        warning("Migrations en attente: " . color((string)$status['pending'], 'yellow'));
        line();
        foreach ($status['migrations'] as $m) {
            if ($m['status'] === 'pending') {
                line("    " . color('○', 'yellow') . " {$m['name']}");
            }
        }
    } else {
        success("Aucune migration en attente");
    }

    // Backups
    $backups = $updater->getBackups();
    line();
    info("Sauvegardes: " . count($backups));
    if (!empty($backups)) {
        $latest = $backups[0];
        info("Dernière: {$latest['created_at']} (v{$latest['version']})");
    }

    line();
}

function cmdCheck(Updater $updater): void
{
    title('Vérification des mises à jour');

    $update = $updater->checkForUpdates();
    if ($update === null) {
        success("Votre installation est à jour (v{$updater->getCurrentVersion()})");
    } else {
        warning("Mise à jour disponible: v{$update['version']}");
        if (!empty($update['changelog'])) {
            line();
            info("Changements:");
            foreach ($update['changelog'] as $change) {
                line("    • {$change}");
            }
        }
        line();
        info("Exécutez: php update.php apply <fichier.zip>");
    }
    line();
}

function cmdMigrate(Updater $updater): void
{
    title('Exécution des migrations');

    $status = $updater->getMigrationStatus();
    if ($status['pending'] === 0) {
        success("Aucune migration en attente");
        line();
        return;
    }

    info("Migrations en attente: {$status['pending']}");
    line();

    $result = $updater->applyMigrations();

    if ($result['run'] > 0) {
        success("{$result['run']} migration(s) exécutée(s) (batch #{$result['batch']})");
    }

    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $err) {
            error("Migration {$err['migration']}: {$err['error']}");
        }
    }
    line();
}

function cmdBaseline(Updater $updater): void
{
    title('Baseline des migrations');

    info("Marque toutes les migrations sur le disque comme déjà exécutées...");
    $count = $updater->baselineMigrations();

    if ($count > 0) {
        success("{$count} migration(s) marquée(s) comme appliquée(s)");
    } else {
        info("Aucune migration à marquer (toutes déjà enregistrées)");
    }
    line();
}

function cmdBackup(Updater $updater): void
{
    title('Sauvegarde');

    info("Création de la sauvegarde...");
    $path = $updater->createBackup('manual');
    success("Sauvegarde créée: " . basename($path));
    info("Chemin: {$path}");
    line();
}

function cmdRestore(Updater $updater): void
{
    title('Restauration');

    $backups = $updater->getBackups();
    if (empty($backups)) {
        error("Aucune sauvegarde trouvée");
        line();
        return;
    }

    $latest = $backups[0];
    warning("Restauration depuis: {$latest['name']}");
    info("Version: v{$latest['version']} | Date: {$latest['created_at']}");

    if (!$latest['has_database']) {
        error("Ce backup ne contient pas de dump de base de données");
        line();
        return;
    }

    line();
    echo "  Confirmer la restauration? (y/N): ";
    $confirm = trim(fgets(STDIN));

    if (strtolower($confirm) !== 'y') {
        info("Restauration annulée");
        line();
        return;
    }

    info("Restauration en cours...");
    $ok = $updater->restoreDatabase($latest['path']);

    if ($ok) {
        success("Base de données restaurée avec succès");
    } else {
        error("Échec de la restauration");
    }
    line();
}

function cmdHistory(Updater $updater): void
{
    title('Historique des mises à jour');

    $history = $updater->getUpdateHistory();

    if (empty($history)) {
        info("Aucune mise à jour enregistrée");
        line();
        return;
    }

    line(sprintf("  %-6s %-10s %-10s %-12s %-8s %-20s",
        'ID', 'De', 'Vers', 'Statut', 'Migr.', 'Date'));
    line(color('  ' . str_repeat('─', 70), 'dim'));

    foreach ($history as $h) {
        $statusColor = match($h['status']) {
            'completed' => 'green',
            'failed' => 'red',
            'started' => 'yellow',
            default => 'dim',
        };

        line(sprintf("  %-6s %-10s %-10s %-12s %-8s %-20s",
            $h['id'],
            $h['from_version'],
            $h['to_version'],
            color($h['status'], $statusColor),
            $h['migrations_run'],
            $h['started_at']
        ));
    }
    line();
}

function cmdPreflight(Updater $updater): void
{
    title('Vérifications pré-vol');

    $checks = $updater->preflightChecks();
    $allOk = true;

    foreach ($checks as $check) {
        if ($check['ok']) {
            success("{$check['label']}: {$check['value']}");
        } else {
            error("{$check['label']}: {$check['value']}");
            $allOk = false;
        }
    }

    line();
    if ($allOk) {
        success("Toutes les vérifications sont OK");
    } else {
        error("Certaines vérifications ont échoué");
    }
    line();
}

function cmdApply(Updater $updater, ?string $zipPath): void
{
    title('Application de mise à jour');

    if (empty($zipPath)) {
        error("Usage: php update.php apply <chemin/vers/fichier.zip>");
        line();
        return;
    }

    if (!file_exists($zipPath)) {
        error("Fichier non trouvé: {$zipPath}");
        line();
        return;
    }

    // Vérifications pré-vol
    if (!$updater->allChecksPassed()) {
        error("Les vérifications pré-vol ont échoué. Exécutez: php update.php preflight");
        line();
        return;
    }

    info("Fichier: {$zipPath}");
    info("Version actuelle: v{$updater->getCurrentVersion()}");
    line();
    echo "  Confirmer la mise à jour? (y/N): ";
    $confirm = trim(fgets(STDIN));

    if (strtolower($confirm) !== 'y') {
        info("Mise à jour annulée");
        line();
        return;
    }

    info("Application en cours...");
    line();

    $result = $updater->applyOfflineUpdate($zipPath);

    if ($result['success']) {
        success("Mise à jour réussie!");
        info("Version: v{$result['version_from']} → v{$result['version_to']}");
        info("Fichiers mis à jour: {$result['files_updated']}");
        info("Migrations exécutées: {$result['migrations_run']}");
        info("Backup: " . basename($result['backup_path']));
    } else {
        error("Mise à jour échouée");
        foreach ($result['errors'] as $err) {
            error($err);
        }
        if ($result['backup_path']) {
            info("Backup disponible pour restauration: " . basename($result['backup_path']));
            info("Exécutez: php update.php restore");
        }
    }
    line();
}

function showHelp(): void
{
    title('RADIUS Manager - Outil de mise à jour');
    line();
    line("  " . color('Usage:', 'bold') . " php update.php <commande> [options]");
    line();
    line("  " . color('Commandes:', 'bold'));
    line("    status      Version actuelle et migrations en attente");
    line("    check       Vérifier les mises à jour disponibles");
    line("    migrate     Exécuter les migrations en attente");
    line("    baseline    Marquer les migrations comme appliquées (première installation)");
    line("    backup      Créer une sauvegarde");
    line("    restore     Restaurer depuis la dernière sauvegarde");
    line("    history     Historique des mises à jour");
    line("    preflight   Vérifications pré-vol");
    line("    apply <zip> Appliquer une mise à jour depuis un fichier ZIP");
    line();
}
