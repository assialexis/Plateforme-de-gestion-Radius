<?php
/**
 * Point d'entrée principal de l'interface web
 */

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Auth/User.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Api/ModuleController.php';

$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    $db = new RadiusDatabase($config['database']);
}
catch (Exception $e) {
    die(__('auth.db_connection_error'));
}

// Initialiser le service d'authentification
$auth = new AuthService($pdo);

// Vérifier l'authentification
if (!$auth->isAuthenticated()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'utilisateur connecté
$currentUser = $auth->getUser();

// Changer la langue via URL
if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), ['fr', 'en'])) {
    $_SESSION['lang'] = strtolower($_GET['lang']);

    // Retirer le paramètre 'lang' de l'url pour éviter que le lien persiste dans l'historique
    $params = $_GET;
    unset($params['lang']);
    header('Location: ?' . http_build_query($params));
    exit;
}

// Configuration pour les vues
$appName = $config['app']['name'] ?? 'RADIUS Manager';
$flashMessages = getFlash();

// Router les pages
$page = $_GET['page'] ?? 'dashboard';

// Pages accessibles selon le rôle
$allPages = [
    'dashboard' => ['admin', 'vendeur', 'gerant', 'technicien'],
    'vouchers' => ['admin', 'vendeur', 'gerant', 'technicien'],
    'pppoe' => ['admin', 'gerant', 'technicien'],
    'pppoe-user' => ['admin', 'gerant', 'technicien'],
    'pppoe-transactions' => ['admin', 'gerant'],
    'pppoe-reminders' => ['admin'],
    'network' => ['admin', 'gerant', 'technicien'],
    'billing' => ['admin', 'gerant'],
    'profiles' => ['admin'],
    'zones' => ['admin'],
    'nas' => ['admin', 'vendeur', 'technicien'],
    'nas-map' => ['admin', 'vendeur', 'technicien'],
    'router-commands' => ['admin', 'technicien'],
    'radius-servers' => ['superadmin'],
    'bandwidth' => ['admin'],
    'monitoring' => ['admin', 'gerant', 'technicien'],
    'users' => ['admin'],
    'sessions' => ['admin', 'vendeur', 'gerant', 'technicien'],
    'transactions' => ['admin', 'vendeur'],
    'logs' => ['admin'],
    'payments' => ['admin'],
    'library' => ['admin'],
    'voucher-templates' => ['admin'],
    'hotspot-templates' => ['admin'],
    'captive-portal' => ['admin'],
    'captive-portal-editor' => ['admin'],
    'settings' => ['admin'],
    'topology' => ['admin', 'vendeur', 'technicien'],
    'sales' => ['admin', 'gerant'],
    'loyalty' => ['admin'],
    'modules' => ['admin'],
    'chat' => ['admin', 'vendeur', 'technicien'],
    'telegram' => ['admin'],
    'whatsapp' => ['admin'],
    'sms' => ['admin'],
    'otp' => ['admin'],
    'marketing' => ['admin'],
    'subscription' => ['admin'],
    'superadmin-admins' => ['superadmin'],
    'superadmin-permissions' => ['superadmin'],
    'superadmin-settings' => ['superadmin'],
    'superadmin-module-pricing' => ['superadmin'],
    'superadmin-sms-config' => ['superadmin'],
    'superadmin-paygate-config' => ['superadmin'],
    'superadmin-recharge-gateways' => ['superadmin'],
    'superadmin-transactions' => ['superadmin'],
    'superadmin-notifications' => ['superadmin'],
    'superadmin-withdrawals' => ['superadmin'],
];

// Vérifier si la page existe et si l'utilisateur a accès
$allowedPages = array_keys($allPages);
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Vérifier les permissions pour cette page
// SuperAdmin a accès à toutes les pages
$allowedRoles = $allPages[$page] ?? [];
if (!$currentUser->isSuperAdmin() && !in_array($currentUser->getRole(), $allowedRoles)) {
    // Rediriger vers une page accessible
    header('Location: index.php?page=dashboard&error=forbidden');
    exit;
}

// Vérifier si la page est liée à un module désactivé
$modulePages = [
    'vouchers' => 'hotspot',
    'profiles' => 'hotspot',
    'sessions' => 'hotspot',
    'voucher-templates' => 'hotspot',
    'hotspot-templates' => 'hotspot',
    'transactions' => 'hotspot',
    'sales' => 'hotspot',
    'logs' => 'hotspot',
    'loyalty' => 'loyalty',
    'chat' => 'chat',
    'pppoe' => 'pppoe',
    'pppoe-user' => 'pppoe', // pppoe-user dépend du module pppoe
    'pppoe-transactions' => 'pppoe', // pppoe-transactions dépend du module pppoe
    'pppoe-reminders' => 'pppoe', // pppoe-reminders dépend du module pppoe
    'network' => 'pppoe', // network dépend du module pppoe
    'billing' => 'pppoe', // billing dépend du module pppoe
    'whatsapp' => 'whatsapp',
    'sms' => 'sms',
    'otp' => 'sms',
    'marketing' => 'sms',
    'captive-portal' => 'captive-portal',
    'captive-portal-editor' => 'captive-portal'
];
if (isset($modulePages[$page]) && !$currentUser->isSuperAdmin()) {
    $moduleCode = $modulePages[$page];
    if (!ModuleController::checkModuleActive($db, $moduleCode, $auth->getAdminId())) {
        header('Location: index.php?page=dashboard&error=module_disabled');
        exit;
    }
}

// Capturer le contenu de la vue
ob_start();
include __DIR__ . "/views/{$page}.php";
$content = ob_get_clean();

// Afficher le layout
include __DIR__ . '/views/layout.php';