<?php
/**
 * Endpoint de polling MikroTik (Pull-Based Remote Control)
 *
 * Le routeur MikroTik appelle cet endpoint toutes les 10 secondes pour :
 * - Récupérer la prochaine commande en attente
 * - Confirmer l'exécution d'une commande (?done=ID)
 * - Signaler une erreur (?fail=ID)
 *
 * Cet endpoint est léger : pas de session PHP, pas d'AuthService.
 * L'authentification se fait via router_id + polling_token (header X-NAS-Token).
 */

// Pas de session pour cet endpoint
ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Connexion BDD directe (légère, sans RadiusDatabase)
try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};port={$config['database']['port']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}",
        $config['database']['username'],
        $config['database']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(503);
    exit('# ERROR:DB_UNAVAILABLE');
}

// --- Paramètres ---
$routerId = $_GET['router'] ?? '';
$doneId   = $_GET['done'] ?? '';
$failId   = $_GET['fail'] ?? '';

// Sanitize router ID
$routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);

if (empty($routerId)) {
    http_response_code(400);
    exit('# ERROR:MISSING_ROUTER_ID');
}

// --- Valider que le routeur existe ---
$stmt = $pdo->prepare("SELECT id, router_id, polling_token, admin_id FROM nas WHERE router_id = ? LIMIT 1");
$stmt->execute([$routerId]);
$nas = $stmt->fetch();

if (!$nas) {
    http_response_code(404);
    exit('# ERROR:UNKNOWN_ROUTER');
}

// --- Authentification par token (si configuré) ---
$expectedToken = $nas['polling_token'];
if (!empty($expectedToken)) {
    $providedToken = $_SERVER['HTTP_X_NAS_TOKEN'] ?? ($_GET['token'] ?? '');
    if (!hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        exit('# ERROR:INVALID_TOKEN');
    }
}

// --- Mettre à jour last_seen ---
$pdo->prepare("UPDATE nas SET last_seen = NOW() WHERE id = ?")->execute([$nas['id']]);

// --- Traitement confirmation d'exécution ---
if (!empty($doneId)) {
    $cmdId = (int)$doneId;
    $pdo->prepare(
        "UPDATE router_commands SET status = 'executed', executed_at = NOW()
         WHERE id = ? AND router_id = ? AND status = 'sent'"
    )->execute([$cmdId, $routerId]);
    exit('# OK');
}

// --- Traitement signalement d'erreur ---
if (!empty($failId)) {
    $cmdId = (int)$failId;
    $errorMsg = substr($_GET['error'] ?? 'Execution failed', 0, 500);

    $stmt = $pdo->prepare(
        "SELECT retry_count, max_retries FROM router_commands WHERE id = ? AND router_id = ?"
    );
    $stmt->execute([$cmdId, $routerId]);
    $cmd = $stmt->fetch();

    if ($cmd) {
        $newRetry = $cmd['retry_count'] + 1;
        $newStatus = ($newRetry >= $cmd['max_retries']) ? 'failed' : 'pending';
        $pdo->prepare(
            "UPDATE router_commands SET status = ?, retry_count = ?, error_message = ?,
             sent_at = NULL WHERE id = ? AND router_id = ?"
        )->execute([$newStatus, $newRetry, $errorMsg, $cmdId, $routerId]);
    }
    exit('# OK');
}

// --- Auto-expiration des commandes périmées ---
$pdo->prepare(
    "UPDATE router_commands SET status = 'expired'
     WHERE router_id = ? AND status = 'pending'
     AND expires_at IS NOT NULL AND expires_at < NOW()"
)->execute([$routerId]);

// --- Reset des commandes 'sent' bloquées (>5 min sans confirmation) ---
$pdo->prepare(
    "UPDATE router_commands SET status = 'pending', sent_at = NULL
     WHERE router_id = ? AND status = 'sent'
     AND sent_at < DATE_SUB(NOW(), INTERVAL 5 MINUTE)"
)->execute([$routerId]);

// --- Récupérer la prochaine commande en attente ---
$stmt = $pdo->prepare(
    "SELECT id, command_content FROM router_commands
     WHERE router_id = ? AND status = 'pending'
     ORDER BY priority ASC, created_at ASC
     LIMIT 1"
);
$stmt->execute([$routerId]);
$command = $stmt->fetch();

if (!$command) {
    exit('# NOP');
}

// --- Marquer comme envoyée ---
$pdo->prepare(
    "UPDATE router_commands SET status = 'sent', sent_at = NOW() WHERE id = ?"
)->execute([$command['id']]);

// --- Retourner la commande ---
echo "# CMD:" . $command['id'] . "\n";
echo $command['command_content'];
