<?php
/**
 * Fonctions utilitaires
 */

/**
 * Formater les octets en format lisible
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    if ($bytes == 0) return '0 B';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $precision) . ' ' . $units[$i];
}

/**
 * Formater la vitesse en bps
 */
function formatSpeed(int $bps): string
{
    if ($bps == 0) return '0';
    if ($bps >= 1000000) {
        return round($bps / 1000000, 1) . ' Mbps';
    } elseif ($bps >= 1000) {
        return round($bps / 1000, 0) . ' Kbps';
    }
    return $bps . ' bps';
}

/**
 * Formater le temps en format lisible
 */
function formatTime(int $seconds): string
{
    if ($seconds <= 0) return '0' . __('time.s');

    $d = __('time.d');
    $h = __('time.h');
    $m = __('time.m');
    $s = __('time.s');

    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($days > 0) {
        return "{$days}{$d} {$hours}{$h}";
    } elseif ($hours > 0) {
        return "{$hours}{$h} {$minutes}{$m}";
    } elseif ($minutes > 0) {
        return "{$minutes}{$m} {$secs}{$s}";
    }
    return "{$secs}{$s}";
}

/**
 * Formater le temps en format détaillé
 */
function formatTimeDetailed(int $seconds): string
{
    if ($seconds <= 0) return '0 ' . __('time.second');

    $parts = [];
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($days > 0) $parts[] = $days . ' ' . ($days > 1 ? __('time.days') : __('time.day'));
    if ($hours > 0) $parts[] = $hours . ' ' . ($hours > 1 ? __('time.hours') : __('time.hour'));
    if ($minutes > 0) $parts[] = $minutes . ' ' . ($minutes > 1 ? __('time.minutes') : __('time.minute'));
    if ($secs > 0 && count($parts) == 0) $parts[] = $secs . ' ' . ($secs > 1 ? __('time.seconds') : __('time.second'));

    return implode(' ', $parts);
}

/**
 * Générer un code voucher aléatoire
 */
function generateVoucherCode(string $prefix = '', int $length = 8, bool $lowercase = false): string
{
    $chars = $lowercase
        ? 'abcdefghjkmnpqrstuvwxyz23456789'
        : 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = $prefix;
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

/**
 * Générer un UUID v4
 */
function generateUUID(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Générer un token CSRF
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 */
function verifyCSRFToken(?string $token): bool
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Nettoyer une chaîne pour l'affichage HTML
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Obtenir l'IP du client
 */
function getClientIP(): string
{
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = explode(',', $_SERVER[$header])[0];
            return trim($ip);
        }
    }
    return '0.0.0.0';
}

/**
 * Envoyer une réponse JSON
 */
function jsonResponse(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Réponse JSON succès
 */
function jsonSuccess($data = null, string $message = 'Success'): void
{
    jsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Réponse JSON erreur
 */
function jsonError(string $message, int $status = 400, array $errors = []): void
{
    jsonResponse([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ], $status);
}

/**
 * Redirection
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Obtenir un paramètre GET nettoyé
 */
function get(string $key, $default = null)
{
    return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
}

/**
 * Obtenir un paramètre POST nettoyé
 */
function post(string $key, $default = null)
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

/**
 * Obtenir les données JSON du body
 */
function getJsonBody(): array
{
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?: [];
}

/**
 * Vérifier si la requête est AJAX
 */
function isAjax(): bool
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Obtenir l'URL de base
 */
function baseUrl(): string
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return rtrim($protocol . '://' . $host . $path, '/');
}

/**
 * Message flash
 */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Obtenir et effacer les messages flash
 */
function getFlash(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/**
 * Convertir des secondes en intervalle lisible
 */
function secondsToInterval(int $seconds): string
{
    if ($seconds < 60) return "{$seconds} " . __('time.seconds');
    if ($seconds < 3600) return floor($seconds / 60) . " " . __('time.minutes');
    if ($seconds < 86400) return floor($seconds / 3600) . " " . __('time.hours');
    return floor($seconds / 86400) . " " . __('time.days');
}

/**
 * Parser une limite de vitesse "download/upload"
 */
function parseSpeedLimit(string $speed): array
{
    $parts = explode('/', $speed);
    return [
        'download' => (int)($parts[0] ?? 0),
        'upload' => (int)($parts[1] ?? $parts[0] ?? 0)
    ];
}

/**
 * Valider une adresse MAC
 */
function isValidMAC(string $mac): bool
{
    return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac) === 1;
}

/**
 * Valider une adresse IP
 */
function isValidIP(string $ip): bool
{
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Logger un message
 */
function appLog(string $message, string $level = 'INFO', ?string $file = null): void
{
    $logFile = $file ?: __DIR__ . '/../../logs/app.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $line = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Traduction simple
 */
function __(string $key, array $replace = []): string
{
    static $translations = null;

    if ($translations === null) {
        $lang = $_SESSION['lang'] ?? 'fr';
        $file = __DIR__ . "/../../lang/{$lang}.php";
        $translations = file_exists($file) ? require $file : [];
    }

    $text = $translations[$key] ?? $key;

    foreach ($replace as $k => $v) {
        $text = str_replace(':' . $k, $v, $text);
    }

    return $text;
}

/**
 * Traduction échappée pour utilisation dans du JavaScript (single-quoted strings)
 */
function __js(string $key, array $replace = []): string
{
    return addslashes(__($key, $replace));
}

/**
 * Obtenir le pourcentage d'utilisation
 */
function getUsagePercent($used, $limit): ?float
{
    if ($limit === null || $limit == 0) return null;
    return min(100, round(($used / $limit) * 100, 1));
}

/**
 * Couleur de progression basée sur le pourcentage
 */
function getProgressColor(?float $percent): string
{
    if ($percent === null) return 'bg-gray-400';
    if ($percent >= 90) return 'bg-red-500';
    if ($percent >= 70) return 'bg-yellow-500';
    return 'bg-green-500';
}

/**
 * Retourne la version de l'application
 */
function getAppVersion(): string
{
    $versionFile = __DIR__ . '/../../VERSION';
    if (file_exists($versionFile)) {
        return trim(file_get_contents($versionFile));
    }
    return '1.0.0';
}
