<?php
/**
 * Endpoint Webhook pour nœud RADIUS
 *
 * Reçoit les push temps réel depuis la plateforme centrale.
 * Quand un admin crée/modifie un voucher, profil, NAS ou zone,
 * la plateforme pousse immédiatement le changement ici.
 *
 * Auth : header X-Platform-Token
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache');

$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(503);
    echo json_encode(['error' => 'NOT_CONFIGURED']);
    exit;
}

$config = require $configFile;

// Vérifier le token
$providedToken = $_SERVER['HTTP_X_PLATFORM_TOKEN'] ?? '';
$expectedToken = $config['platform']['platform_token'] ?? '';

if (empty($expectedToken) || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'INVALID_TOKEN']);
    exit;
}

// Connexion DB locale (nécessaire pour GET et POST)
try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['port'],
        $config['database']['dbname'],
        $config['database']['charset']
    );
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(503);
    echo json_encode(['error' => 'DB_UNAVAILABLE']);
    exit;
}

// GET : requêtes de lecture (ex: statut FUP en temps réel)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action === 'fup_status') {
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'MISSING_USER_ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT pu.id, pu.username, pu.fup_data_used, pu.fup_data_offset,
                   pu.fup_triggered, pu.fup_triggered_at, pu.fup_last_reset,
                   pu.fup_override, pu.data_used, pu.time_used,
                   pp.fup_enabled, pp.fup_quota,
                   pp.fup_download_speed, pp.fup_upload_speed,
                   pp.download_speed as normal_download_speed,
                   pp.upload_speed as normal_upload_speed
            FROM pppoe_users pu
            LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
            WHERE pu.id = ?
        ");
        $stmt->execute([$userId]);
        $status = $stmt->fetch();

        if (!$status) {
            http_response_code(404);
            echo json_encode(['error' => 'USER_NOT_FOUND']);
            exit;
        }

        echo json_encode(['status' => 'ok', 'source' => 'node', 'data' => $status]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'INVALID_ACTION']);
    exit;
}

// Uniquement POST à partir d'ici
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'METHOD_NOT_ALLOWED']);
    exit;
}

// Lire le payload
$input = file_get_contents('php://input');
$payload = json_decode($input, true);

if (!$payload || empty($payload['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'INVALID_PAYLOAD']);
    exit;
}

$event = $payload['event'];
$data = $payload['data'] ?? [];

try {
    $result = handleEvent($pdo, $event, $data);
    echo json_encode(['status' => 'ok', 'event' => $event, 'result' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'HANDLER_FAILED', 'message' => $e->getMessage()]);
}

/**
 * Traiter un événement webhook
 */
function handleEvent(PDO $pdo, string $event, array $data): string
{
    switch ($event) {
        // --- Vouchers ---
        case 'voucher.created':
            return upsertVoucher($pdo, $data);

        case 'voucher.updated':
            return upsertVoucher($pdo, $data);

        case 'voucher.deleted':
            $pdo->prepare("DELETE FROM vouchers WHERE id = ?")->execute([$data['id']]);
            return 'deleted';

        // --- Profiles ---
        case 'profile.created':
        case 'profile.updated':
            return upsertProfile($pdo, $data);

        case 'profile.deleted':
            $pdo->prepare("DELETE FROM profiles WHERE id = ?")->execute([$data['id']]);
            return 'deleted';

        // --- NAS ---
        case 'nas.created':
        case 'nas.updated':
            return upsertNas($pdo, $data);

        case 'nas.deleted':
            $pdo->prepare("DELETE FROM nas WHERE id = ?")->execute([$data['id']]);
            return 'deleted';

        // --- Zones ---
        case 'zone.created':
        case 'zone.updated':
            return upsertZone($pdo, $data);

        case 'zone.deleted':
            $pdo->prepare("DELETE FROM zones WHERE id = ?")->execute([$data['id']]);
            return 'deleted';

        // --- PPPoE Users ---
        case 'pppoe_user.created':
        case 'pppoe_user.updated':
            return upsertPPPoEUser($pdo, $data);

        case 'pppoe_user.deleted':
            $pdo->prepare("DELETE FROM pppoe_users WHERE id = ?")->execute([$data['id']]);
            return 'deleted';

        // --- FUP Reset (push instantané depuis le central) ---
        case 'pppoe_user.fup_reset':
            return handleFupReset($pdo, $data);

        default:
            return 'unknown_event';
    }
}

function upsertVoucher(PDO $pdo, array $v): string
{
    $stmt = $pdo->prepare("
        INSERT INTO vouchers (id, username, password, profile_id, zone_id, time_limit, data_limit, upload_limit, download_limit, upload_speed, download_speed, status, simultaneous_use, price, valid_from, valid_until, first_use, time_used, data_used, upload_used, download_used, customer_name, customer_phone, batch_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            password = VALUES(password), profile_id = VALUES(profile_id), zone_id = VALUES(zone_id),
            time_limit = VALUES(time_limit), data_limit = VALUES(data_limit),
            upload_speed = VALUES(upload_speed), download_speed = VALUES(download_speed),
            status = VALUES(status), simultaneous_use = VALUES(simultaneous_use),
            valid_from = VALUES(valid_from), valid_until = VALUES(valid_until),
            time_used = VALUES(time_used), data_used = VALUES(data_used)
    ");
    $stmt->execute([
        $v['id'], $v['username'], $v['password'], $v['profile_id'] ?? null, $v['zone_id'] ?? null,
        $v['time_limit'] ?? null, $v['data_limit'] ?? null, $v['upload_limit'] ?? null, $v['download_limit'] ?? null,
        $v['upload_speed'] ?? null, $v['download_speed'] ?? null,
        $v['status'] ?? 'unused', $v['simultaneous_use'] ?? 1, $v['price'] ?? 0,
        $v['valid_from'] ?? null, $v['valid_until'] ?? null, $v['first_use'] ?? null,
        $v['time_used'] ?? 0, $v['data_used'] ?? 0, $v['upload_used'] ?? 0, $v['download_used'] ?? 0,
        $v['customer_name'] ?? null, $v['customer_phone'] ?? null, $v['batch_id'] ?? null
    ]);
    return 'upserted';
}

function upsertProfile(PDO $pdo, array $p): string
{
    $stmt = $pdo->prepare("
        INSERT INTO profiles (id, zone_id, name, description, time_limit, data_limit, upload_speed, download_speed, price, validity, validity_unit, simultaneous_use, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            zone_id = VALUES(zone_id), name = VALUES(name), description = VALUES(description),
            time_limit = VALUES(time_limit), data_limit = VALUES(data_limit),
            upload_speed = VALUES(upload_speed), download_speed = VALUES(download_speed),
            price = VALUES(price), validity = VALUES(validity), simultaneous_use = VALUES(simultaneous_use),
            is_active = VALUES(is_active)
    ");
    $stmt->execute([
        $p['id'], $p['zone_id'] ?? null, $p['name'], $p['description'] ?? null,
        $p['time_limit'] ?? null, $p['data_limit'] ?? null,
        $p['upload_speed'] ?? null, $p['download_speed'] ?? null,
        $p['price'] ?? 0, $p['validity'] ?? null, $p['validity_unit'] ?? 'days',
        $p['simultaneous_use'] ?? 1, $p['is_active'] ?? 1
    ]);
    return 'upserted';
}

function upsertNas(PDO $pdo, array $n): string
{
    $stmt = $pdo->prepare("
        INSERT INTO nas (id, router_id, zone_id, nasname, shortname, secret, description, type)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            router_id = VALUES(router_id), zone_id = VALUES(zone_id), nasname = VALUES(nasname),
            shortname = VALUES(shortname), secret = VALUES(secret), description = VALUES(description),
            type = VALUES(type)
    ");
    $stmt->execute([
        $n['id'], $n['router_id'] ?? null, $n['zone_id'] ?? null,
        $n['nasname'], $n['shortname'], $n['secret'],
        $n['description'] ?? null, $n['type'] ?? 'mikrotik'
    ]);
    return 'upserted';
}

function upsertZone(PDO $pdo, array $z): string
{
    $stmt = $pdo->prepare("
        INSERT INTO zones (id, name, code, description, color, dns_name, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name), code = VALUES(code), description = VALUES(description),
            color = VALUES(color), dns_name = VALUES(dns_name), is_active = VALUES(is_active)
    ");
    $stmt->execute([
        $z['id'], $z['name'], $z['code'], $z['description'] ?? null,
        $z['color'] ?? '#3b82f6', $z['dns_name'] ?? null, $z['is_active'] ?? 1
    ]);
    return 'upserted';
}

function upsertPPPoEUser(PDO $pdo, array $u): string
{
    $stmt = $pdo->prepare("
        INSERT INTO pppoe_users (id, username, password, profile_id, profile_name, customer_name, customer_phone, status, upload_speed, download_speed, ip_mode, static_ip, pool_ip, ip_pool_name, mikrotik_group, valid_until, simultaneous_use)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            password = VALUES(password), profile_id = VALUES(profile_id), profile_name = VALUES(profile_name),
            status = VALUES(status), upload_speed = VALUES(upload_speed), download_speed = VALUES(download_speed),
            ip_mode = VALUES(ip_mode), static_ip = VALUES(static_ip), valid_until = VALUES(valid_until),
            simultaneous_use = VALUES(simultaneous_use)
    ");
    $stmt->execute([
        $u['id'], $u['username'], $u['password'], $u['profile_id'] ?? null,
        $u['profile_name'] ?? null, $u['customer_name'] ?? null, $u['customer_phone'] ?? null,
        $u['status'] ?? 'active', $u['upload_speed'] ?? null, $u['download_speed'] ?? null,
        $u['ip_mode'] ?? 'router', $u['static_ip'] ?? null, $u['pool_ip'] ?? null,
        $u['ip_pool_name'] ?? null, $u['mikrotik_group'] ?? null,
        $u['valid_until'] ?? null, $u['simultaneous_use'] ?? 1
    ]);
    return 'upserted';
}

function handleFupReset(PDO $pdo, array $data): string
{
    $userId = $data['id'] ?? 0;
    if (!$userId) return 'missing_id';

    $stmt = $pdo->prepare("
        UPDATE pppoe_users SET
            fup_triggered = 0,
            fup_triggered_at = NULL,
            fup_data_used = 0,
            fup_data_offset = ?,
            fup_last_reset = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $data['fup_data_offset'] ?? 0,
        $data['fup_last_reset'] ?? date('Y-m-d H:i:s'),
        $userId
    ]);

    $username = $data['username'] ?? '?';
    error_log("[Webhook] FUP reset for user #{$userId} ({$username}) - fup_triggered=0");
    return 'fup_reset_applied';
}
