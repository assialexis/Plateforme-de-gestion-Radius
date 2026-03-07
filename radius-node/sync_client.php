<?php
/**
 * Client de synchronisation pour nœud RADIUS
 *
 * Ce script est appelé par cron toutes les minutes.
 * Il effectue un cycle complet :
 * 1. Heartbeat vers la plateforme centrale
 * 2. Pull de la configuration (si changée)
 * 3. Push des sessions/logs non synchronisés
 */

$configFile = __DIR__ . '/config/config.php';

if (!file_exists($configFile)) {
    echo "[ERROR] Configuration manquante\n";
    exit(1);
}

$config = require $configFile;

// Vérifier la config plateforme
if (empty($config['platform']['url']) || empty($config['platform']['sync_token'])) {
    echo "[ERROR] Configuration plateforme incomplète\n";
    exit(1);
}

// Connexion DB locale
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
    echo "[ERROR] DB locale inaccessible: " . $e->getMessage() . "\n";
    exit(1);
}

$platformUrl = rtrim($config['platform']['url'], '/');
$serverCode = $config['platform']['server_code'];
$syncToken = $config['platform']['sync_token'];

$timestamp = date('Y-m-d H:i:s');
echo "[{$timestamp}] === Sync cycle start ===\n";

// 1. Heartbeat
echo "[{$timestamp}] 1. Sending heartbeat...\n";
$currentHash = getSyncMeta($pdo, 'config_hash');
$heartbeatResponse = apiCall('GET', "{$platformUrl}/node_sync.php?action=heartbeat&server={$serverCode}", null, $syncToken);

if ($heartbeatResponse && isset($heartbeatResponse['config_hash'])) {
    echo "[{$timestamp}]    Heartbeat OK - Remote hash: {$heartbeatResponse['config_hash']}\n";

    // 2. Pull si le hash a changé
    if ($currentHash !== $heartbeatResponse['config_hash']) {
        echo "[{$timestamp}] 2. Config changed, pulling...\n";
        $pullResponse = apiCall('GET', "{$platformUrl}/node_sync.php?action=pull&server={$serverCode}&hash={$currentHash}", null, $syncToken);

        if ($pullResponse && isset($pullResponse['status'])) {
            if ($pullResponse['status'] === 'no_change') {
                echo "[{$timestamp}]    No changes detected\n";
            } elseif ($pullResponse['status'] === 'ok' && isset($pullResponse['data'])) {
                $data = $pullResponse['data'];
                $stats = applyPullData($pdo, $data);
                updateSyncMeta($pdo, 'config_hash', $data['config_hash'] ?? '');
                updateSyncMeta($pdo, 'last_pull_at', date('Y-m-d H:i:s'));
                incrementSyncMeta($pdo, 'pull_count');
                echo "[{$timestamp}]    Pull OK - Zones: {$stats['zones']}, NAS: {$stats['nas']}, Vouchers: {$stats['vouchers']}, Profiles: {$stats['profiles']}, PPPoE: {$stats['pppoe_users']}\n";
            }
        } else {
            echo "[{$timestamp}]    Pull FAILED\n";
            updateSyncMeta($pdo, 'last_error', 'Pull failed at ' . date('Y-m-d H:i:s'));
        }
    } else {
        echo "[{$timestamp}] 2. Config unchanged, skipping pull\n";
    }
} else {
    echo "[{$timestamp}]    Heartbeat FAILED - Platform may be unreachable\n";
    updateSyncMeta($pdo, 'last_error', 'Heartbeat failed at ' . date('Y-m-d H:i:s'));
}

// 3. Push sessions et logs non synchronisés
echo "[{$timestamp}] 3. Pushing local data...\n";
$pushData = collectPushData($pdo);

if ($pushData['has_data']) {
    $pushResponse = apiCall('POST', "{$platformUrl}/node_sync.php?action=push&server={$serverCode}", $pushData, $syncToken);

    if ($pushResponse && isset($pushResponse['status']) && $pushResponse['status'] === 'ok') {
        markAsSynced($pdo, $pushData);
        updateSyncMeta($pdo, 'last_push_at', date('Y-m-d H:i:s'));
        incrementSyncMeta($pdo, 'push_count');
        $imported = $pushResponse['imported'] ?? [];
        echo "[{$timestamp}]    Push OK - Sessions: " . ($imported['sessions'] ?? 0) . ", Logs: " . ($imported['auth_logs'] ?? 0) . ", Updates: " . ($imported['voucher_updates'] ?? 0) . "\n";
    } else {
        echo "[{$timestamp}]    Push FAILED\n";
        updateSyncMeta($pdo, 'last_error', 'Push failed at ' . date('Y-m-d H:i:s'));
    }
} else {
    echo "[{$timestamp}]    No data to push\n";
}

echo "[{$timestamp}] === Sync cycle end ===\n\n";

// =====================================================
// Fonctions utilitaires
// =====================================================

/**
 * Appel API vers la plateforme centrale
 */
function apiCall(string $method, string $url, ?array $data, string $token): ?array
{
    $ch = curl_init($url);
    $headers = [
        'X-Node-Token: ' . $token,
        'Accept: application/json',
        'Accept-Encoding: gzip',
    ];

    if ($method === 'POST' && $data) {
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        $headers[] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false, // En production, mettre true
        CURLOPT_ENCODING => '', // Auto-décompression gzip
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || $httpCode !== 200) {
        echo "    API Error: HTTP {$httpCode}, Error: {$error}\n";
        return null;
    }

    return json_decode($response, true);
}

/**
 * Appliquer les données pull dans la DB locale
 */
function applyPullData(PDO $pdo, array $data): array
{
    $stats = ['zones' => 0, 'nas' => 0, 'vouchers' => 0, 'profiles' => 0, 'pppoe_users' => 0];

    $pdo->beginTransaction();
    try {
        // Sync zones
        if (!empty($data['zones'])) {
            $pdo->exec("DELETE FROM zones");
            $stmt = $pdo->prepare("INSERT INTO zones (id, name, code, description, color, dns_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['zones'] as $zone) {
                $stmt->execute([$zone['id'], $zone['name'], $zone['code'], $zone['description'] ?? null, $zone['color'] ?? '#3b82f6', $zone['dns_name'] ?? null, $zone['is_active'] ?? 1]);
                $stats['zones']++;
            }
        }

        // Sync NAS
        if (!empty($data['nas'])) {
            $pdo->exec("DELETE FROM nas");
            $stmt = $pdo->prepare("INSERT INTO nas (id, router_id, zone_id, nasname, shortname, secret, description, type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['nas'] as $nas) {
                $stmt->execute([$nas['id'], $nas['router_id'] ?? null, $nas['zone_id'] ?? null, $nas['nasname'], $nas['shortname'], $nas['secret'], $nas['description'] ?? null, $nas['type'] ?? 'mikrotik']);
                $stats['nas']++;
            }
        }

        // Sync profiles
        if (!empty($data['profiles'])) {
            $pdo->exec("DELETE FROM profiles");
            $stmt = $pdo->prepare("INSERT INTO profiles (id, zone_id, name, description, time_limit, data_limit, upload_speed, download_speed, price, validity, validity_unit, simultaneous_use, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['profiles'] as $profile) {
                $stmt->execute([
                    $profile['id'], $profile['zone_id'] ?? null, $profile['name'], $profile['description'] ?? null,
                    $profile['time_limit'] ?? null, $profile['data_limit'] ?? null,
                    $profile['upload_speed'] ?? null, $profile['download_speed'] ?? null,
                    $profile['price'] ?? 0, $profile['validity'] ?? null, $profile['validity_unit'] ?? 'days',
                    $profile['simultaneous_use'] ?? 1, $profile['is_active'] ?? 1
                ]);
                $stats['profiles']++;
            }
        }

        // Sync vouchers (merge, don't delete active sessions)
        if (!empty($data['vouchers'])) {
            // Supprimer uniquement les vouchers qui ne sont plus dans la liste
            $remoteIds = array_column($data['vouchers'], 'id');
            if (!empty($remoteIds)) {
                $placeholders = implode(',', array_fill(0, count($remoteIds), '?'));
                $pdo->prepare("DELETE FROM vouchers WHERE id NOT IN ($placeholders)")->execute($remoteIds);
            }

            $stmt = $pdo->prepare("
                INSERT INTO vouchers (id, username, password, profile_id, zone_id, time_limit, data_limit, upload_limit, download_limit, upload_speed, download_speed, status, simultaneous_use, price, valid_from, valid_until, first_use, time_used, data_used, upload_used, download_used, customer_name, customer_phone, batch_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    password = VALUES(password), profile_id = VALUES(profile_id), zone_id = VALUES(zone_id),
                    time_limit = VALUES(time_limit), data_limit = VALUES(data_limit),
                    upload_speed = VALUES(upload_speed), download_speed = VALUES(download_speed),
                    simultaneous_use = VALUES(simultaneous_use),
                    valid_from = VALUES(valid_from), valid_until = VALUES(valid_until),
                    time_used = GREATEST(time_used, VALUES(time_used)),
                    data_used = GREATEST(data_used, VALUES(data_used)),
                    upload_used = GREATEST(upload_used, VALUES(upload_used)),
                    download_used = GREATEST(download_used, VALUES(download_used)),
                    status = IF(time_used >= VALUES(time_limit) AND VALUES(time_limit) IS NOT NULL, 'expired',
                             IF(VALUES(status) = 'disabled', 'disabled', status))
            ");
            foreach ($data['vouchers'] as $v) {
                $stmt->execute([
                    $v['id'], $v['username'], $v['password'], $v['profile_id'] ?? null, $v['zone_id'] ?? null,
                    $v['time_limit'] ?? null, $v['data_limit'] ?? null, $v['upload_limit'] ?? null, $v['download_limit'] ?? null,
                    $v['upload_speed'] ?? null, $v['download_speed'] ?? null,
                    $v['status'] ?? 'unused', $v['simultaneous_use'] ?? 1, $v['price'] ?? 0,
                    $v['valid_from'] ?? null, $v['valid_until'] ?? null, $v['first_use'] ?? null,
                    $v['time_used'] ?? 0, $v['data_used'] ?? 0, $v['upload_used'] ?? 0, $v['download_used'] ?? 0,
                    $v['customer_name'] ?? null, $v['customer_phone'] ?? null, $v['batch_id'] ?? null
                ]);
                $stats['vouchers']++;
            }
        }

        // Sync PPPoE profiles (inclure colonnes FUP pour que le nœud applique les politiques)
        if (!empty($data['pppoe_profiles'])) {
            $pdo->exec("DELETE FROM pppoe_profiles");
            $stmt = $pdo->prepare("
                INSERT INTO pppoe_profiles (id, zone_id, name, description, download_speed, upload_speed, data_limit, validity_days, price, ip_pool_name, local_address, simultaneous_use, burst_download, burst_upload, burst_threshold, burst_time, is_active, fup_enabled, fup_quota, fup_download_speed, fup_upload_speed, fup_reset_day, fup_reset_type)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($data['pppoe_profiles'] as $pp) {
                $stmt->execute([
                    $pp['id'], $pp['zone_id'] ?? null, $pp['name'], $pp['description'] ?? null,
                    $pp['download_speed'] ?? 1048576, $pp['upload_speed'] ?? 524288,
                    $pp['data_limit'] ?? 0, $pp['validity_days'] ?? 30, $pp['price'] ?? 0,
                    $pp['ip_pool_name'] ?? null, $pp['local_address'] ?? null,
                    $pp['simultaneous_use'] ?? 1, $pp['burst_download'] ?? 0, $pp['burst_upload'] ?? 0,
                    $pp['burst_threshold'] ?? 0, $pp['burst_time'] ?? 0, $pp['is_active'] ?? 1,
                    $pp['fup_enabled'] ?? 0, $pp['fup_quota'] ?? 0,
                    $pp['fup_download_speed'] ?? 0, $pp['fup_upload_speed'] ?? 0,
                    $pp['fup_reset_day'] ?? 1, $pp['fup_reset_type'] ?? 'monthly'
                ]);
                $stats['pppoe_profiles'] = ($stats['pppoe_profiles'] ?? 0) + 1;
            }
        }

        // Sync PPPoE users (UPSERT: maj config sans écraser les compteurs FUP locaux)
        if (!empty($data['pppoe_users'])) {
            // Supprimer les users qui ne sont plus dans le pull (supprimés sur le central)
            $remoteIds = array_column($data['pppoe_users'], 'id');
            if ($remoteIds) {
                $ph = implode(',', array_fill(0, count($remoteIds), '?'));
                $pdo->prepare("DELETE FROM pppoe_users WHERE id NOT IN ($ph)")->execute($remoteIds);
            }

            $stmt = $pdo->prepare("
                INSERT INTO pppoe_users (id, username, password, profile_id, profile_name, customer_name, customer_phone, status,
                    upload_speed, download_speed, ip_mode, static_ip, pool_ip, ip_pool_name, mikrotik_group, valid_until,
                    burst_upload, burst_download, burst_threshold, burst_time, simultaneous_use,
                    zone_id, nas_id, admin_id, fup_override)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    username = VALUES(username), password = VALUES(password),
                    profile_id = VALUES(profile_id), profile_name = VALUES(profile_name),
                    customer_name = VALUES(customer_name), customer_phone = VALUES(customer_phone),
                    status = VALUES(status), upload_speed = VALUES(upload_speed), download_speed = VALUES(download_speed),
                    ip_mode = VALUES(ip_mode), static_ip = VALUES(static_ip), pool_ip = VALUES(pool_ip),
                    ip_pool_name = VALUES(ip_pool_name), mikrotik_group = VALUES(mikrotik_group),
                    valid_until = VALUES(valid_until), burst_upload = VALUES(burst_upload),
                    burst_download = VALUES(burst_download), burst_threshold = VALUES(burst_threshold),
                    burst_time = VALUES(burst_time), simultaneous_use = VALUES(simultaneous_use),
                    zone_id = VALUES(zone_id), nas_id = VALUES(nas_id), admin_id = VALUES(admin_id),
                    fup_override = VALUES(fup_override)
            ");
            // Requête pour recalculer le total local des sessions
            $stmtLocalTotal = $pdo->prepare("
                SELECT COALESCE(SUM(input_octets + output_octets), 0) FROM pppoe_sessions WHERE pppoe_user_id = ?
            ");

            // Requête pour propager le reset FUP du central vers le nœud
            $stmtFupSync = $pdo->prepare("
                UPDATE pppoe_users
                SET fup_data_used = 0, fup_data_offset = ?, fup_triggered = ?,
                    fup_triggered_at = ?, fup_last_reset = ?
                WHERE id = ? AND (fup_last_reset IS NULL OR fup_last_reset < ?)
            ");

            foreach ($data['pppoe_users'] as $user) {
                $stmt->execute([
                    $user['id'], $user['username'], $user['password'], $user['profile_id'] ?? null,
                    $user['profile_name'] ?? null, $user['customer_name'] ?? null, $user['customer_phone'] ?? null,
                    $user['status'] ?? 'active', $user['upload_speed'] ?? null, $user['download_speed'] ?? null,
                    $user['ip_mode'] ?? 'router', $user['static_ip'] ?? null, $user['pool_ip'] ?? null,
                    $user['ip_pool_name'] ?? null, $user['mikrotik_group'] ?? null,
                    $user['valid_until'] ?? null, $user['burst_upload'] ?? null, $user['burst_download'] ?? null,
                    $user['burst_threshold'] ?? null, $user['burst_time'] ?? null, $user['simultaneous_use'] ?? 1,
                    $user['zone_id'] ?? null, $user['nas_id'] ?? null, $user['admin_id'] ?? null,
                    $user['fup_override'] ?? 0
                ]);

                // Propager le reset FUP si le central a un reset plus récent que le nœud
                // Utiliser le total LOCAL des sessions comme offset (pas celui du central)
                $centralLastReset = $user['fup_last_reset'] ?? null;
                if ($centralLastReset) {
                    // Vérifier si c'est un nouveau reset (central plus récent que le nœud)
                    $stmtCheckReset = $pdo->prepare("SELECT fup_last_reset, fup_triggered FROM pppoe_users WHERE id = ?");
                    $stmtCheckReset->execute([$user['id']]);
                    $localUser = $stmtCheckReset->fetch();
                    $isNewReset = !$localUser || !$localUser['fup_last_reset'] || $centralLastReset > $localUser['fup_last_reset'];

                    $stmtLocalTotal->execute([$user['id']]);
                    $localTotal = (int)$stmtLocalTotal->fetchColumn();

                    $stmtFupSync->execute([
                        $localTotal,
                        $user['fup_triggered'] ?? 0,
                        $user['fup_triggered_at'] ?? null,
                        $centralLastReset,
                        $user['id'],
                        $centralLastReset
                    ]);

                    // Si c'est un nouveau reset et l'utilisateur était en FUP, le déconnecter via push command
                    if ($isNewReset && $localUser && $localUser['fup_triggered']) {
                        try {
                            // Trouver le router_id : last_nas_identifier > zone
                            $routerId = null;

                            // 1. last_nas_identifier stocké depuis le dernier accounting
                            try {
                                $lnStmt = $pdo->prepare("SELECT last_nas_identifier FROM pppoe_users WHERE id = ?");
                                $lnStmt->execute([$user['id']]);
                                $lastNas = $lnStmt->fetch();
                                if ($lastNas && !empty($lastNas['last_nas_identifier'])) {
                                    $riStmt = $pdo->prepare("SELECT router_id FROM nas WHERE router_id = ? OR shortname = ? OR nasname = ? LIMIT 1");
                                    $riStmt->execute([$lastNas['last_nas_identifier'], $lastNas['last_nas_identifier'], $lastNas['last_nas_identifier']]);
                                    $ri = $riStmt->fetch();
                                    if ($ri) $routerId = $ri['router_id'];
                                }
                            } catch (PDOException $e) {
                                // Colonne pas encore créée
                            }

                            // 2. NAS de la zone
                            if (!$routerId) {
                                $nasStmt = $pdo->prepare("SELECT n.router_id FROM pppoe_users pu JOIN nas n ON n.zone_id = pu.zone_id WHERE pu.id = ? AND n.router_id IS NOT NULL AND n.router_id != '' LIMIT 1");
                                $nasStmt->execute([$user['id']]);
                                $nasRow = $nasStmt->fetch();
                                if ($nasRow) $routerId = $nasRow['router_id'];
                            }

                            if ($routerId) {
                                // Récupérer l'admin_id du client PPPoE (isolation multi-tenant)
                                $adminIdForCmd = null;
                                $aiStmt = $pdo->prepare("SELECT admin_id FROM pppoe_users WHERE id = ?");
                                $aiStmt->execute([$user['id']]);
                                $aiRow = $aiStmt->fetch();
                                if ($aiRow && $aiRow['admin_id']) $adminIdForCmd = (int)$aiRow['admin_id'];

                                // Envoyer la commande de déconnexion via push (le routeur poll fetch_cmd.php)
                                $configFile = __DIR__ . '/config/config.php';
                                $config = file_exists($configFile) ? require $configFile : [];
                                $platformUrl = rtrim($config['platform']['url'] ?? '', '/');
                                $serverCode = $config['platform']['server_code'] ?? '';
                                $syncToken = $config['platform']['sync_token'] ?? '';

                                $escapedUsername = addslashes($user['username']);
                                $command = ":log info \"NAS: FUP reset sync - Deconnexion {$escapedUsername}\"\n:local found false\n:foreach activeId in=[/ppp active find name=\"{$escapedUsername}\"] do={\n    :set found true\n    /ppp active remove \$activeId\n    :log info \"NAS: Session PPPoE {$escapedUsername} deconnectee\"\n}\n:if (\$found = false) do={\n    :log info \"NAS: Utilisateur {$escapedUsername} n'est pas connecte\"\n}";

                                $url = "{$platformUrl}/node_sync.php?action=queue_command&server={$serverCode}";
                                $payload = [
                                    'router_id' => $routerId,
                                    'command' => $command,
                                    'description' => "FUP reset sync: Déconnexion PPPoE {$user['username']}",
                                    'command_type' => 'disconnect_pppoe',
                                    'priority' => 5,
                                ];
                                if ($adminIdForCmd) $payload['admin_id'] = $adminIdForCmd;
                                $postData = json_encode($payload);

                                $ch = curl_init($url);
                                curl_setopt_array($ch, [
                                    CURLOPT_POST => true,
                                    CURLOPT_POSTFIELDS => $postData,
                                    CURLOPT_HTTPHEADER => ['X-Node-Token: ' . $syncToken, 'Content-Type: application/json'],
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_TIMEOUT => 10,
                                    CURLOPT_SSL_VERIFYPEER => false,
                                ]);
                                $response = curl_exec($ch);
                                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                curl_close($ch);

                                // Fermer les sessions en DB
                                $pdo->prepare("UPDATE pppoe_sessions SET stop_time = NOW(), terminate_cause = 'FUP-Reset-Sync' WHERE pppoe_user_id = ? AND stop_time IS NULL")->execute([$user['id']]);

                                echo "    FUP reset: push disconnect " . ($httpCode === 200 ? 'QUEUED' : "FAILED (HTTP {$httpCode})") . " {$user['username']} → router {$routerId}\n";
                            } else {
                                echo "    FUP reset: no router_id found for user {$user['username']}\n";
                            }
                        } catch (\Throwable $e) {
                            echo "    FUP reset disconnect error: " . $e->getMessage() . "\n";
                        }
                    }
                }

                $stats['pppoe_users']++;
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "    Pull apply error: " . $e->getMessage() . "\n";
    }

    return $stats;
}

/**
 * Collecter les données locales à pousser vers le central
 */
function collectPushData(PDO $pdo): array
{
    $data = ['has_data' => false];

    // Sessions non synchronisées + sessions actives (pour mettre à jour les compteurs)
    $stmt = $pdo->query("SELECT * FROM sessions WHERE synced = 0 OR stop_time IS NULL LIMIT 500");
    $data['sessions'] = $stmt->fetchAll();

    // Auth logs non synchronisés
    $stmt = $pdo->query("SELECT * FROM auth_logs WHERE synced = 0 LIMIT 500");
    $data['auth_logs'] = $stmt->fetchAll();

    // Voucher updates (compteurs et ventes modifiés localement)
    $stmt = $pdo->query("
        SELECT id, username, time_used, data_used, upload_used, download_used, status, first_use,
               sold_at, sold_by, sold_on_nas_id, vendeur_id, gerant_id,
               payment_method, sale_amount, commission_vendeur, commission_gerant, commission_admin
        FROM vouchers
        WHERE (time_used > 0 OR data_used > 0 OR sold_at IS NOT NULL) AND status IN ('active', 'expired')
    ");
    $data['voucher_updates'] = $stmt->fetchAll();

    $data['has_data'] = !empty($data['sessions']) || !empty($data['auth_logs']) || !empty($data['voucher_updates']);

    // Aussi les sessions PPPoE (non sync + actives pour mettre à jour les compteurs)
    try {
        $stmt = $pdo->query("SELECT * FROM pppoe_sessions WHERE synced = 0 OR stop_time IS NULL LIMIT 500");
        $pppoe = $stmt->fetchAll();
        if (!empty($pppoe)) {
            $data['pppoe_sessions'] = $pppoe;
            $data['has_data'] = true;
        }
    } catch (PDOException $e) {
        // Table peut ne pas exister
    }

    // Compteurs FUP PPPoE (remonter vers le central)
    try {
        $stmt = $pdo->query("
            SELECT id, data_used, time_used, fup_data_used, fup_data_offset,
                   fup_triggered, fup_triggered_at, fup_last_reset,
                   last_nas_ip, last_acct_session_id, last_nas_identifier
            FROM pppoe_users
            WHERE data_used > 0 OR fup_data_used > 0 OR fup_triggered = 1
        ");
        $fupUpdates = $stmt->fetchAll();
        if (!empty($fupUpdates)) {
            $data['pppoe_user_updates'] = $fupUpdates;
            $data['has_data'] = true;
        }
    } catch (PDOException $e) {
        // Table peut ne pas exister
    }

    return $data;
}

/**
 * Marquer les données comme synchronisées
 */
function markAsSynced(PDO $pdo, array $pushData): void
{
    if (!empty($pushData['sessions'])) {
        $ids = array_column($pushData['sessions'], 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE sessions SET synced = 1 WHERE id IN ($placeholders)")->execute($ids);
    }

    if (!empty($pushData['auth_logs'])) {
        $ids = array_column($pushData['auth_logs'], 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE auth_logs SET synced = 1 WHERE id IN ($placeholders)")->execute($ids);
    }

    if (!empty($pushData['pppoe_sessions'])) {
        $ids = array_column($pushData['pppoe_sessions'], 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE pppoe_sessions SET synced = 1 WHERE id IN ($placeholders)")->execute($ids);
    }
}

/**
 * Lire une valeur de sync_meta
 */
function getSyncMeta(PDO $pdo, string $key): ?string
{
    try {
        $stmt = $pdo->prepare("SELECT {$key} FROM sync_meta WHERE id = 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row[$key] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Mettre à jour une valeur de sync_meta
 */
function updateSyncMeta(PDO $pdo, string $key, string $value): void
{
    try {
        $pdo->prepare("UPDATE sync_meta SET {$key} = ? WHERE id = 1")->execute([$value]);
    } catch (PDOException $e) {
        // Ignorer
    }
}

/**
 * Incrémenter un compteur de sync_meta
 */
function incrementSyncMeta(PDO $pdo, string $key): void
{
    try {
        $pdo->exec("UPDATE sync_meta SET {$key} = {$key} + 1 WHERE id = 1");
    } catch (PDOException $e) {
        // Ignorer
    }
}
