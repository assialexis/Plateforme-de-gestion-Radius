<?php
/**
 * Endpoint de synchronisation pour les nœuds RADIUS (Pull-Based)
 *
 * Les nœuds RADIUS appellent cet endpoint périodiquement pour :
 * - Récupérer leur configuration (zones, NAS, vouchers, profils, PPPoE)
 * - Pousser leurs données (sessions, logs d'auth, compteurs vouchers)
 * - Envoyer un heartbeat
 *
 * Cet endpoint est léger : pas de session PHP.
 * L'authentification se fait via server_code + sync_token (header X-Node-Token).
 */

// Pas de session
ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

// Connexion BDD
try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode(['error' => 'DB_UNAVAILABLE']);
    exit;
}

// --- Paramètres ---
$action = $_GET['action'] ?? '';
$serverCode = $_GET['server'] ?? '';

// Sanitize
$serverCode = preg_replace('/[^a-zA-Z0-9_-]/', '', $serverCode);

if (empty($serverCode)) {
    http_response_code(400);
    echo json_encode(['error' => 'MISSING_SERVER_CODE']);
    exit;
}

if (empty($action)) {
    http_response_code(400);
    echo json_encode(['error' => 'MISSING_ACTION']);
    exit;
}

// --- Authentification par token ---
$providedToken = $_SERVER['HTTP_X_NODE_TOKEN'] ?? ($_GET['token'] ?? '');
if (empty($providedToken)) {
    http_response_code(401);
    echo json_encode(['error' => 'MISSING_TOKEN']);
    exit;
}

$server = $db->getRadiusServerByCode($serverCode);
if (!$server) {
    http_response_code(404);
    echo json_encode(['error' => 'UNKNOWN_SERVER']);
    exit;
}

if (!hash_equals($server['sync_token'], $providedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'INVALID_TOKEN']);
    exit;
}

if (!$server['is_active']) {
    http_response_code(403);
    echo json_encode(['error' => 'SERVER_DISABLED']);
    exit;
}

// --- Traitement selon l'action ---
switch ($action) {
    case 'heartbeat':
        handleHeartbeat($db, $server);
        break;

    case 'pull':
        handlePull($db, $server);
        break;

    case 'push':
        handlePush($db, $server);
        break;

    case 'download':
        handleDownload($server);
        break;

    case 'queue_command':
        handleQueueCommand($db, $server);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'INVALID_ACTION']);
        exit;
}

/**
 * Heartbeat - Met à jour le statut du serveur
 */
function handleHeartbeat(RadiusDatabase $db, array $server): void
{
    $db->updateRadiusServerHeartbeat($server['code']);

    // Calculer le hash de config pour détecter les changements
    $syncData = $db->getRadiusServerSyncData($server['id']);

    echo json_encode([
        'status' => 'ok',
        'config_hash' => $syncData['config_hash'],
        'server_time' => date('Y-m-d H:i:s'),
        'sync_interval' => $server['sync_interval'],
    ]);
}

/**
 * Pull - Le nœud récupère sa configuration
 */
function handlePull(RadiusDatabase $db, array $server): void
{
    // Vérifier si le client envoie un hash pour sync différentielle
    $clientHash = $_GET['hash'] ?? '';

    $syncData = $db->getRadiusServerSyncData($server['id']);

    // Si le hash n'a pas changé, renvoyer une réponse légère
    if (!empty($clientHash) && $clientHash === $syncData['config_hash']) {
        $db->updateRadiusServerLastSync($server['code']);
        echo json_encode([
            'status' => 'no_change',
            'config_hash' => $syncData['config_hash'],
        ]);
        return;
    }

    $db->updateRadiusServerLastSync($server['code']);

    // Compresser si le client le supporte
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    $response = json_encode([
        'status' => 'ok',
        'config_hash' => $syncData['config_hash'],
        'data' => $syncData,
    ]);

    if (strpos($acceptEncoding, 'gzip') !== false && function_exists('gzencode')) {
        header('Content-Encoding: gzip');
        echo gzencode($response);
    } else {
        echo $response;
    }
}

/**
 * Push - Le nœud envoie ses données (sessions, logs, compteurs)
 */
function handlePush(RadiusDatabase $db, array $server): void
{
    $debugLog = '/tmp/radius_sync_debug.log';
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    file_put_contents($debugLog, date('Y-m-d H:i:s') . " handlePush received from server '{$server['code']}' - raw length: " . strlen($input) . "\n", FILE_APPEND);
    file_put_contents($debugLog, "  has_data: " . ($data['has_data'] ?? 'N/A') . ", sessions: " . count($data['sessions'] ?? []) . ", auth_logs: " . count($data['auth_logs'] ?? []) . ", voucher_updates: " . count($data['voucher_updates'] ?? []) . "\n", FILE_APPEND);

    if (!$data) {
        file_put_contents($debugLog, "  ERROR: INVALID_JSON - raw input: " . substr($input, 0, 500) . "\n", FILE_APPEND);
        http_response_code(400);
        echo json_encode(['error' => 'INVALID_JSON']);
        return;
    }

    try {
        $imported = $db->importNodeSyncData($data);
        $db->updateRadiusServerLastSync($server['code']);

        file_put_contents($debugLog, "  Push response: " . json_encode($imported) . "\n", FILE_APPEND);

        echo json_encode([
            'status' => 'ok',
            'imported' => $imported,
        ]);
    } catch (Exception $e) {
        file_put_contents($debugLog, "  Push EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode([
            'error' => 'IMPORT_FAILED',
            'message' => $e->getMessage(),
        ]);
    }
}

/**
 * Download - Le nœud télécharge le package d'installation (tar.gz)
 */
function handleDownload(array $server): void
{
    $nodeDir = realpath(__DIR__ . '/../radius-node');

    if (!$nodeDir || !is_dir($nodeDir)) {
        http_response_code(404);
        echo json_encode(['error' => 'NODE_PACKAGE_NOT_FOUND']);
        return;
    }

    $tmpFile = tempnam(sys_get_temp_dir(), 'radius-node-') . '.tar.gz';

    $cmd = sprintf(
        'tar -czf %s -C %s --exclude=logs --exclude=config/config.php .',
        escapeshellarg($tmpFile),
        escapeshellarg($nodeDir)
    );
    exec($cmd, $output, $exitCode);

    if ($exitCode !== 0 || !file_exists($tmpFile)) {
        @unlink($tmpFile);
        http_response_code(500);
        echo json_encode(['error' => 'PACKAGE_BUILD_FAILED']);
        return;
    }

    // Remplacer le Content-Type JSON par gzip
    header('Content-Type: application/gzip');
    header('Content-Length: ' . filesize($tmpFile));
    header('Content-Disposition: attachment; filename="radius-node.tar.gz"');

    readfile($tmpFile);
    unlink($tmpFile);
}

/**
 * Queue Command - Le nœud envoie une commande MikroTik à exécuter via le central
 * Utilisé pour FUP trigger/reset (déconnexion PPPoE)
 */
function handleQueueCommand(RadiusDatabase $db, array $server): void
{
    $input = json_decode(file_get_contents('php://input'), true);

    $routerId = $input['router_id'] ?? '';
    $command = $input['command'] ?? '';
    $description = $input['description'] ?? null;
    $priority = (int)($input['priority'] ?? 10);
    $commandType = $input['command_type'] ?? 'raw';
    $adminId = isset($input['admin_id']) ? (int)$input['admin_id'] : null;

    if (empty($routerId) || empty($command)) {
        http_response_code(400);
        echo json_encode(['error' => 'MISSING_PARAMS', 'message' => 'router_id et command requis']);
        return;
    }

    try {
        require_once __DIR__ . '/../src/MikroTik/CommandSender.php';
        $commandSender = new MikroTikCommandSender($db->getPdo());

        $cmdId = $commandSender->send($routerId, $command, $description, $priority, $commandType, 600, $adminId);

        if ($cmdId) {
            echo json_encode(['status' => 'ok', 'command_id' => $cmdId]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'QUEUE_FAILED']);
        }
    } catch (Exception $e) {
        error_log("node_sync queue_command error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'QUEUE_ERROR', 'message' => $e->getMessage()]);
    }
}
