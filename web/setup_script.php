<?php
/**
 * Endpoint public pour télécharger le script setup MikroTik
 *
 * Permet l'installation par lien : le routeur MikroTik peut fetch et importer
 * le script directement via une commande one-liner.
 *
 * Auth: router_id + polling_token (en query string)
 * Retourne: texte brut (.rsc)
 */

ini_set('session.use_cookies', '0');
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Robots-Tag: noindex, nofollow');

$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/MikroTik/SetupScriptGenerator.php';

// Connexion BDD
try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    http_response_code(503);
    header('Content-Type: text/plain');
    echo "# ERROR: Database unavailable\n";
    exit;
}

$routerId = $_GET['router'] ?? '';
$token = $_GET['token'] ?? '';

if (empty($routerId) || empty($token)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo "# ERROR: Missing router or token parameter\n";
    exit;
}

// Sanitize
$routerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $routerId);

// Vérifier le routeur et le token
$pdo = $db->getPdo();
$stmt = $pdo->prepare("SELECT id, router_id, admin_id, polling_token FROM nas WHERE router_id = ?");
$stmt->execute([$routerId]);
$nas = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$nas) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo "# ERROR: Router not found\n";
    exit;
}

if (empty($nas['polling_token']) || !hash_equals($nas['polling_token'], $token)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "# ERROR: Invalid token\n";
    exit;
}

// Générer le script
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$serverUrl = $protocol . '://' . $host . $basePath;

$generator = new SetupScriptGenerator($pdo, $serverUrl);
$script = $generator->generate($routerId);

// Retourner le script en texte brut
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: inline; filename="setup-' . $routerId . '.rsc"');
echo $script;
