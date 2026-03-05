<?php
/**
 * Sert les fichiers de preview des templates Portail Captif
 * Utilise PATH_INFO pour que les URLs relatives (css/, js/, img/) fonctionnent
 *
 * URL: portal-preview.php/Template%2018/login.html
 *      portal-preview.php/Template%2018/css/style.css
 */

$pathInfo = $_SERVER['PATH_INFO'] ?? '';

if (empty($pathInfo) || $pathInfo === '/') {
    http_response_code(400);
    echo 'Usage: portal-preview.php/{template}/{file}';
    exit;
}

// Parser le chemin: /Template 18/login.html ou /Template 18/css/style.css
$pathInfo = ltrim($pathInfo, '/');
$parts = explode('/', $pathInfo, 2);

if (count($parts) < 2) {
    http_response_code(400);
    echo 'Missing file path';
    exit;
}

$template = $parts[0];
$file = $parts[1];

// Sécurité: empêcher la traversée de répertoire
if (strpos($template, '..') !== false || strpos($file, '..') !== false) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

$filePath = __DIR__ . '/../Portail Captif/' . $template . '/' . $file;
$filePath = realpath($filePath);

// Vérifier que le fichier est bien dans le dossier Portail Captif
$allowedBase = realpath(__DIR__ . '/../Portail Captif');
if (!$filePath || !$allowedBase || strpos($filePath, $allowedBase) !== 0) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Déterminer le Content-Type
$ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$mimeTypes = [
    'html' => 'text/html',
    'css'  => 'text/css',
    'js'   => 'application/javascript',
    'json' => 'application/json',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'svg'  => 'image/svg+xml',
    'ico'  => 'image/x-icon',
    'webp' => 'image/webp',
    'mp3'  => 'audio/mpeg',
    'ogg'  => 'audio/ogg',
    'wav'  => 'audio/wav',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
];

header('Content-Type: ' . ($mimeTypes[$ext] ?? 'application/octet-stream'));
header('Cache-Control: no-cache');
readfile($filePath);
