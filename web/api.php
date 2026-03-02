<?php
/**
 * Point d'entrée API REST
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Headers CORS et JSON
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Client-Session');
header('Content-Type: application/json; charset=utf-8');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoloader Composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Auth/User.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';
require_once __DIR__ . '/../src/Api/Router.php';
require_once __DIR__ . '/../src/Api/AuthController.php';
require_once __DIR__ . '/../src/Api/UserController.php';
require_once __DIR__ . '/../src/Api/DashboardController.php';
require_once __DIR__ . '/../src/Api/VoucherController.php';
require_once __DIR__ . '/../src/Api/NasController.php';
require_once __DIR__ . '/../src/Api/SessionController.php';
require_once __DIR__ . '/../src/Api/ProfileController.php';
require_once __DIR__ . '/../src/Api/LogController.php';
require_once __DIR__ . '/../src/Api/PaymentController.php';
require_once __DIR__ . '/../src/Api/PlatformPaymentController.php';
require_once __DIR__ . '/../src/Api/LibraryController.php';
require_once __DIR__ . '/../src/Api/TemplateController.php';
require_once __DIR__ . '/../src/Api/ZoneController.php';
require_once __DIR__ . '/../src/Api/SalesController.php';
require_once __DIR__ . '/../src/Api/PPPoEController.php';
require_once __DIR__ . '/../src/Api/LoyaltyController.php';
require_once __DIR__ . '/../src/Api/ModuleController.php';
require_once __DIR__ . '/../src/Api/ChatController.php';
require_once __DIR__ . '/../src/Api/NetworkController.php';
require_once __DIR__ . '/../src/Api/BillingController.php';
require_once __DIR__ . '/../src/Api/PPPoEPayController.php';
require_once __DIR__ . '/../src/Api/BandwidthController.php';
require_once __DIR__ . '/../src/Api/MonitoringController.php';
require_once __DIR__ . '/../src/Api/TelegramController.php';
require_once __DIR__ . '/../src/Api/WhatsAppController.php';
require_once __DIR__ . '/../src/Api/SettingsController.php';
require_once __DIR__ . '/../src/Api/CaptivePortalController.php';
require_once __DIR__ . '/../src/Services/SmsService.php';
require_once __DIR__ . '/../src/Api/SmsController.php';
require_once __DIR__ . '/../src/Services/OtpService.php';
require_once __DIR__ . '/../src/Api/OtpController.php';
require_once __DIR__ . '/../src/Services/MarketingService.php';
require_once __DIR__ . '/../src/Api/MarketingController.php';
require_once __DIR__ . '/../src/Api/SuperAdminController.php';
require_once __DIR__ . '/../src/Api/CreditController.php';
require_once __DIR__ . '/../src/Api/ModulePricingController.php';
require_once __DIR__ . '/../src/Api/SmsCreditController.php';
require_once __DIR__ . '/../src/Api/NotificationController.php';
require_once __DIR__ . '/../src/Api/PppoeReminderController.php';
require_once __DIR__ . '/../src/Api/ClientPortalController.php';
require_once __DIR__ . '/../src/Api/RouterSetupController.php';
require_once __DIR__ . '/../src/Api/RouterCommandController.php';
require_once __DIR__ . '/../src/Api/RouterSyncController.php';


// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();
}
catch (Exception $e) {
    jsonError(__('auth.db_connection_error'), 500);
}

// Initialiser le service d'authentification (démarre aussi la session)
$authService = new AuthService($pdo);

// Initialiser les contrôleurs
$authController = new AuthController($authService);
$userController = new UserController($authService, $pdo);
$dashboardController = new DashboardController($db, $authService);
$voucherController = new VoucherController($db, $authService);
$nasController = new NasController($db, $authService);
$sessionController = new SessionController($db, $authService);
$profileController = new ProfileController($db, $authService);
$logController = new LogController($db, $authService);
$paymentController = new PaymentController($db, $authService);
$libraryController = new LibraryController($db, $authService);
$templateController = new TemplateController($db, $authService);
$zoneController = new ZoneController($db, $authService);
$salesController = new SalesController($db, $authService);
$pppoeController = new PPPoEController($db, $authService);
$loyaltyController = new LoyaltyController($db, $authService);
$moduleController = new ModuleController($db, $authService);
$chatController = new ChatController($db, $authService);
$networkController = new NetworkController($db, $authService);
$billingController = new BillingController($db, $authService);
$pppoePayController = new PPPoEPayController($db, $config, $authService);
$bandwidthController = new BandwidthController($db, $authService);
$monitoringController = new MonitoringController($db, $authService);
$telegramController = new TelegramController($db, $authService);
$whatsappController = new WhatsAppController($db, $authService);
$settingsController = new SettingsController($db, $authService);
$captivePortalController = new CaptivePortalController($db, $authService);
$smsController = new SmsController($db, $authService);
$otpController = new OtpController($db, $authService);
$marketingController = new MarketingController($db, $authService);
$superAdminController = new SuperAdminController($db, $authService);
$creditController = new CreditController($db, $authService);
$modulePricingController = new ModulePricingController($db, $authService);
$platformPaymentController = new PlatformPaymentController($db, $authService);
$smsCreditController = new SmsCreditController($db, $authService);
$notificationController = new NotificationController($db, $authService);
$pppoeReminderController = new PppoeReminderController($db, $authService);
$clientPortalController = new ClientPortalController($db, $pdo, $config);
$routerSetupController = new RouterSetupController($db, $authService);
$routerCommandController = new RouterCommandController($db, $authService);
$routerSyncController = new RouterSyncController($db, $authService);

// Middleware d'authentification
$authMiddleware = function () {
    // Pour le développement, on peut désactiver l'auth
    // En production, vérifier la session ou le token API
    if (!isset($_SESSION['admin_id'])) {
        // Vérifier le header Authorization pour l'API
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader)) {
            // Pour le dev, on autorise
            return true;
        }
    }
    return true;
};

// Rate limiting middleware
$rateLimitMiddleware = function () use ($db, $config) {
    if (!($config['security']['rate_limit']['enabled'] ?? false)) {
        return true;
    }

    $ip = getClientIP();
    $endpoint = substr($_GET['route'] ?? explode('?', $_SERVER['REQUEST_URI'])[0], 0, 100);
    $maxRequests = $config['security']['rate_limit']['requests_per_minute'] ?? 60;

    $pdo = $db->getPdo();

    // Nettoyer les anciennes entrées
    $pdo->exec("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 MINUTE)");

    // Vérifier/créer l'entrée
    $stmt = $pdo->prepare("SELECT requests FROM rate_limits WHERE identifier = ? AND endpoint = ?");
    $stmt->execute([$ip, $endpoint]);
    $row = $stmt->fetch();

    if ($row) {
        if ($row['requests'] >= $maxRequests) {
            jsonError(__('common.error') . ': Too many requests', 429);
            return false;
        }
        $pdo->prepare("UPDATE rate_limits SET requests = requests + 1 WHERE identifier = ? AND endpoint = ?")->execute([$ip, $endpoint]);
    }
    else {
        $pdo->prepare("INSERT IGNORE INTO rate_limits (identifier, endpoint, requests) VALUES (?, ?, 1)")->execute([$ip, $endpoint]);
    }

    return true;
};

// Créer le routeur
// Pas de préfixe car les routes sont appelées directement via api.php/endpoint
$router = new Router('');

// Middlewares globaux
$router->middleware($rateLimitMiddleware);
$router->middleware($authMiddleware);

// Routes Auth (pas besoin d'être authentifié pour login)
$router->post('/auth/login', fn($p) => $authController->login());
$router->post('/auth/register', fn($p) => $authController->register());
$router->post('/auth/logout', fn($p) => $authController->logout());
$router->get('/auth/check', fn($p) => $authController->check());
$router->post('/auth/refresh', fn($p) => $authController->refresh());

// Routes Users
$router->get('/users', fn($p) => $userController->index());
$router->post('/users', fn($p) => $userController->store());
$router->get('/users/me', fn($p) => $userController->me());
$router->put('/users/me', fn($p) => $userController->updateMe());
$router->get('/users/stats', fn($p) => $userController->stats());
$router->get('/users/{id}', fn($p) => $userController->show($p));
$router->put('/users/{id}', fn($p) => $userController->update($p));
$router->delete('/users/{id}', fn($p) => $userController->destroy($p));
$router->post('/users/{id}/toggle', fn($p) => $userController->toggle($p));
$router->get('/users/{id}/activity', fn($p) => $userController->activity($p));

// Routes Dashboard
$router->get('/dashboard/full', fn($p) => $dashboardController->full());
$router->get('/dashboard/stats', fn($p) => $dashboardController->stats());
$router->get('/dashboard/connections', fn($p) => $dashboardController->connections($p));
$router->get('/dashboard/data', fn($p) => $dashboardController->dataUsage($p));
$router->get('/dashboard/recent', fn($p) => $dashboardController->recent());

// Routes Vouchers
$router->get('/vouchers', fn($p) => $voucherController->index());
$router->get('/vouchers/notes', fn($p) => $voucherController->notes());
$router->post('/vouchers', fn($p) => $voucherController->store());
$router->post('/vouchers/generate', fn($p) => $voucherController->generate());
$router->post('/vouchers/import', fn($p) => $voucherController->import());
$router->get('/vouchers/{id}', fn($p) => $voucherController->show($p));
$router->put('/vouchers/{id}', fn($p) => $voucherController->update($p));
$router->delete('/vouchers/{id}', fn($p) => $voucherController->destroy($p));
$router->post('/vouchers/{id}/reset', fn($p) => $voucherController->reset($p));
$router->post('/vouchers/{id}/disable', fn($p) => $voucherController->disable($p));
$router->post('/vouchers/{id}/enable', fn($p) => $voucherController->enable($p));
$router->delete('/vouchers/batch/{batchId}', fn($p) => $voucherController->deleteBatch($p));

// Routes Zones
$router->get('/zones', fn($p) => $zoneController->index());
$router->post('/zones', fn($p) => $zoneController->store());
$router->get('/zones/generate-code', fn($p) => $zoneController->generateCode());
$router->get('/zones/{id}', fn($p) => $zoneController->show($p));
$router->put('/zones/{id}', fn($p) => $zoneController->update($p));
$router->delete('/zones/{id}', fn($p) => $zoneController->destroy($p));
$router->post('/zones/{id}/toggle', fn($p) => $zoneController->toggle($p));
$router->get('/zones/{id}/nas', fn($p) => $zoneController->getNas($p));
$router->get('/zones/{id}/profiles', fn($p) => $zoneController->getProfiles($p));

// Routes NAS
$router->get('/nas', fn($p) => $nasController->index());
$router->post('/nas', fn($p) => $nasController->store());
$router->get('/nas/generate-id', fn($p) => $nasController->generateId());
$router->post('/nas/test-command', fn($p) => $nasController->testCommand());
$router->post('/nas/test-api', fn($p) => $nasController->testApi());
$router->get('/nas/{id}', fn($p) => $nasController->show($p));
$router->put('/nas/{id}', fn($p) => $nasController->update($p));
$router->delete('/nas/{id}', fn($p) => $nasController->destroy($p));
$router->post('/nas/{id}/ping', fn($p) => $nasController->ping($p));
$router->post('/nas/{id}/ping-api', fn($p) => $nasController->pingApiDirect($p));
$router->get('/nas/{id}/clients', fn($p) => $nasController->clients($p));

// Routes Router Setup (contrôle distant MikroTik)
$router->get('/router-setup/statuses', fn($p) => $routerSetupController->getAllStatuses());
$router->get('/router-setup/{routerId}', fn($p) => $routerSetupController->getSetupScript($p));
$router->post('/router-setup/{routerId}/generate-token', fn($p) => $routerSetupController->generateToken($p));
$router->get('/router-setup/{routerId}/status', fn($p) => $routerSetupController->getStatus($p));

// Routes Router Commands
$router->get('/router-commands', fn($p) => $routerCommandController->index());
$router->get('/router-commands/stats', fn($p) => $routerCommandController->stats());
$router->get('/router-commands/export', fn($p) => $routerCommandController->export());
$router->post('/router-commands', fn($p) => $routerCommandController->store());
$router->post('/router-commands/delete-bulk', fn($p) => $routerCommandController->deleteBulk());
$router->post('/router-commands/clear-history', fn($p) => $routerCommandController->clearHistory());
$router->get('/router-commands/{id}', fn($p) => $routerCommandController->show($p));
$router->post('/router-commands/{id}/cancel', fn($p) => $routerCommandController->cancel($p));
$router->post('/router-commands/{id}/retry', fn($p) => $routerCommandController->retry($p));
$router->delete('/router-commands/{id}', fn($p) => $routerCommandController->destroy($p));

// Routes Router Sync
$router->post('/router-sync/sync', fn($p) => $routerSyncController->sync());

// Routes Sessions
$router->get('/sessions', fn($p) => $sessionController->index());
$router->get('/sessions/active', fn($p) => $sessionController->active());
$router->post('/sessions/sync', fn($p) => $sessionController->sync());
$router->post('/sessions/login', fn($p) => $sessionController->login());
$router->post('/sessions/logout', fn($p) => $sessionController->logout());
$router->get('/sessions/pending-disconnects', fn($p) => $sessionController->pendingDisconnects());
$router->post('/sessions/confirm-disconnect', fn($p) => $sessionController->confirmDisconnect());
$router->get('/sessions/{id}', fn($p) => $sessionController->show($p));
$router->post('/sessions/{id}/disconnect', fn($p) => $sessionController->requestDisconnect($p));
$router->delete('/sessions/{id}', fn($p) => $sessionController->disconnect($p));
$router->post('/sessions/disconnect-all', fn($p) => $sessionController->disconnectAll());

// Routes Profiles
$router->get('/profiles', fn($p) => $profileController->index());
$router->post('/profiles', fn($p) => $profileController->store());
$router->get('/profiles/{id}', fn($p) => $profileController->show($p));
$router->put('/profiles/{id}', fn($p) => $profileController->update($p));
$router->delete('/profiles/{id}', fn($p) => $profileController->destroy($p));

// Routes Logs
$router->get('/logs', fn($p) => $logController->index());
$router->get('/logs/export', fn($p) => $logController->export());

// Routes Payment Gateways
$router->get('/payments/gateways', fn($p) => $paymentController->index());
$router->get('/payments/gateways/active', fn($p) => $paymentController->active());
$router->get('/payments/gateways/config-fields', fn($p) => $paymentController->getConfigFields());
$router->get('/payments/gateways/{id}', fn($p) => $paymentController->show($p));
$router->put('/payments/gateways/{id}', fn($p) => $paymentController->update($p));
$router->post('/payments/gateways/{id}/toggle', fn($p) => $paymentController->toggle($p));

// Routes Payment Links & Transactions
$router->post('/payments/initiate', fn($p) => $paymentController->initiate());
$router->get('/payments/link/{profileId}', fn($p) => $paymentController->getPaymentLink($p));
$router->get('/payments/transactions', fn($p) => $paymentController->transactions());
$router->get('/payments/transactions/stats', fn($p) => $paymentController->transactionStats());
$router->get('/payments/transactions/{id}', fn($p) => $paymentController->transactionDetails($p));
$router->post('/payments/check-status', fn($p) => $paymentController->checkStatus());
$router->post('/payments/kkiapay/complete', fn($p) => $paymentController->completeKkiapay());

// Routes Platform Payment Gateway (Admin)
$router->get('/platform-payments/gateways', fn($p) => $platformPaymentController->adminGateways());
$router->post('/platform-payments/gateways/{id}/toggle', fn($p) => $platformPaymentController->toggleGateway($p));
$router->get('/platform-payments/balance', fn($p) => $platformPaymentController->balance());
$router->get('/platform-payments/transactions', fn($p) => $platformPaymentController->transactions());
$router->post('/platform-payments/withdrawals', fn($p) => $platformPaymentController->requestWithdrawal());
$router->get('/platform-payments/withdrawals', fn($p) => $platformPaymentController->withdrawals());
$router->post('/platform-payments/withdrawals/{id}/cancel', fn($p) => $platformPaymentController->cancelWithdrawal($p));

// Routes Library (Media)
$router->get('/library', fn($p) => $libraryController->index());
$router->get('/library/type/{type}', fn($p) => $libraryController->byType($p));
$router->get('/library/{id}', fn($p) => $libraryController->show($p));
$router->put('/library/{id}', fn($p) => $libraryController->update($p));
$router->post('/library/{id}/upload', fn($p) => $libraryController->upload($p));
$router->delete('/library/{id}/file', fn($p) => $libraryController->deleteFile($p));

// Routes Templates - Vouchers
$router->get('/templates/vouchers', fn($p) => $templateController->indexVouchers());
$router->get('/templates/vouchers/default', fn($p) => $templateController->getDefaultVoucher());
$router->post('/templates/vouchers', fn($p) => $templateController->storeVoucher());
$router->get('/templates/vouchers/{id}', fn($p) => $templateController->showVoucher($p));
$router->put('/templates/vouchers/{id}', fn($p) => $templateController->updateVoucher($p));
$router->delete('/templates/vouchers/{id}', fn($p) => $templateController->destroyVoucher($p));
$router->post('/templates/vouchers/{id}/default', fn($p) => $templateController->setDefaultVoucher($p));
$router->post('/templates/vouchers/{id}/preview', fn($p) => $templateController->previewVoucher($p));

// Routes Templates - Hotspot
$router->get('/templates/hotspot', fn($p) => $templateController->indexHotspot());
$router->get('/templates/hotspot/default', fn($p) => $templateController->getDefaultHotspot());
$router->post('/templates/hotspot', fn($p) => $templateController->storeHotspot());
$router->get('/templates/hotspot/{id}', fn($p) => $templateController->showHotspot($p));
$router->put('/templates/hotspot/{id}', fn($p) => $templateController->updateHotspot($p));
$router->delete('/templates/hotspot/{id}', fn($p) => $templateController->destroyHotspot($p));
$router->post('/templates/hotspot/{id}/default', fn($p) => $templateController->setDefaultHotspot($p));
$router->post('/templates/hotspot/{id}/duplicate', fn($p) => $templateController->duplicateHotspot($p));
$router->post('/templates/hotspot/{id}/generate', fn($p) => $templateController->generateHotspotHtml($p));
$router->post('/templates/hotspot/preview-live', function ($p) use ($templateController) {
    $templateController->previewLiveHotspotHtml();
});

// Routes Sales (Ventes et Commissions)
$router->get('/sales', fn($p) => $salesController->index());
$router->get('/sales/stats', fn($p) => $salesController->stats());
$router->get('/sales/by-seller', fn($p) => $salesController->bySeller());
$router->get('/sales/by-gerant', fn($p) => $salesController->byGerant());
$router->get('/sales/by-zone', fn($p) => $salesController->byZone());
$router->get('/sales/by-nas', fn($p) => $salesController->byNas());
$router->get('/sales/by-profile', fn($p) => $salesController->byProfile());
$router->get('/sales/commissions', fn($p) => $salesController->commissions());
$router->get('/sales/commission-rates', fn($p) => $salesController->getCommissionRates());
$router->put('/sales/commission-rates/{id}', fn($p) => $salesController->updateCommissionRate($p));
$router->post('/sales/mark-paid', fn($p) => $salesController->markCommissionsPaid());
$router->get('/sales/seller/{id}', fn($p) => $salesController->sellerDetails($p));
$router->delete('/sales/batch', fn($p) => $salesController->deleteBatch());
$router->delete('/sales/{id}', fn($p) => $salesController->delete($p));

// Routes PPPoE Users
$router->get('/pppoe/users', fn($p) => $pppoeController->listUsers());
$router->post('/pppoe/users', fn($p) => $pppoeController->createUser());
$router->post('/pppoe/users/batch', fn($p) => $pppoeController->createBatch());
$router->get('/pppoe/users/{id}', fn($p) => $pppoeController->showUser($p));
$router->put('/pppoe/users/{id}', fn($p) => $pppoeController->updateUser($p));
$router->delete('/pppoe/users/{id}', fn($p) => $pppoeController->deleteUser($p));
$router->post('/pppoe/users/{id}/renew', fn($p) => $pppoeController->renewUser($p));
$router->post('/pppoe/users/{id}/suspend', fn($p) => $pppoeController->suspendUser($p));
$router->post('/pppoe/users/{id}/activate', fn($p) => $pppoeController->activateUser($p));
$router->get('/pppoe/users/{id}/sessions', fn($p) => $pppoeController->userSessions($p));
$router->get('/pppoe/users/{id}/traffic-stats', fn($p) => $pppoeController->userTrafficStats($p));
$router->post('/pppoe/users/{id}/reset-traffic', fn($p) => $pppoeController->resetTraffic($p));

// Routes PPPoE Profiles
$router->get('/pppoe/profiles', fn($p) => $pppoeController->listProfiles());
$router->post('/pppoe/profiles', fn($p) => $pppoeController->createProfile());
$router->get('/pppoe/profiles/{id}', fn($p) => $pppoeController->showProfile($p));
$router->put('/pppoe/profiles/{id}', fn($p) => $pppoeController->updateProfile($p));
$router->delete('/pppoe/profiles/{id}', fn($p) => $pppoeController->deleteProfile($p));

// Routes PPPoE Statistics
$router->get('/pppoe/stats', fn($p) => $pppoeController->stats());

// Routes PPPoE Sessions
$router->get('/pppoe/sessions', fn($p) => $pppoeController->listSessions());
$router->get('/pppoe/sessions/active', fn($p) => $pppoeController->activeSessions());
$router->get('/pppoe/sessions/stats', fn($p) => $pppoeController->sessionStats());
$router->delete('/pppoe/sessions/{id}', fn($p) => $pppoeController->terminateSession($p));
$router->post('/pppoe/sessions/cleanup', fn($p) => $pppoeController->cleanupSessions());
$router->post('/pppoe/sessions/terminate-all', fn($p) => $pppoeController->terminateAllSessions());
$router->post('/pppoe/users/{id}/disconnect', fn($p) => $pppoeController->disconnectUser($p));

// Routes PPPoE FUP (Fair Usage Policy)
$router->get('/pppoe/users/{id}/fup', fn($p) => $pppoeController->getUserFupStatus($p));
$router->post('/pppoe/users/{id}/fup/reset', fn($p) => $pppoeController->resetUserFup($p));
$router->post('/pppoe/users/{id}/fup/trigger', fn($p) => $pppoeController->triggerUserFup($p));
$router->post('/pppoe/users/{id}/fup/toggle-override', fn($p) => $pppoeController->toggleUserFupOverride($p));
$router->get('/pppoe/fup/triggered', fn($p) => $pppoeController->getFupTriggeredUsers());
$router->get('/pppoe/fup/warnings', fn($p) => $pppoeController->getFupWarnings());
$router->get('/pppoe/users/{id}/fup/logs', fn($p) => $pppoeController->getUserFupLogs($p));
$router->post('/pppoe/fup/reset-monthly', fn($p) => $pppoeController->resetMonthlyFup());

// Routes pour synchronisation MikroTik (polling)
$router->get('/pppoe/pending-disconnects', fn($p) => $pppoeController->getPendingDisconnects());
$router->post('/pppoe/confirm-disconnect/{username}', fn($p) => $pppoeController->confirmDisconnect($p));

// Routes Loyalty (Fidélité)
$router->get('/loyalty/customers', fn($p) => $loyaltyController->listCustomers());
$router->get('/loyalty/customers/phone/{phone}', fn($p) => $loyaltyController->findByPhone($p));
$router->get('/loyalty/customers/{id}', fn($p) => $loyaltyController->showCustomer($p));
$router->delete('/loyalty/customers/{id}', fn($p) => $loyaltyController->deleteCustomer($p));
$router->get('/loyalty/rules', fn($p) => $loyaltyController->listRules());
$router->post('/loyalty/rules', fn($p) => $loyaltyController->createRule());
$router->get('/loyalty/rules/{id}', fn($p) => $loyaltyController->showRule($p));
$router->put('/loyalty/rules/{id}', fn($p) => $loyaltyController->updateRule($p));
$router->delete('/loyalty/rules/{id}', fn($p) => $loyaltyController->deleteRule($p));
$router->get('/loyalty/rewards', fn($p) => $loyaltyController->listRewards());
$router->post('/loyalty/rewards/{id}/claim', fn($p) => $loyaltyController->claimReward($p));
$router->delete('/loyalty/rewards/{id}', fn($p) => $loyaltyController->deleteReward($p));
$router->get('/loyalty/stats', fn($p) => $loyaltyController->stats());
$router->post('/loyalty/import', fn($p) => $loyaltyController->importTransactions());
$router->post('/loyalty/sync-vouchers', fn($p) => $loyaltyController->syncFromVouchers());
$router->post('/loyalty/generate-pending-vouchers', fn($p) => $loyaltyController->generatePendingVouchers());
$router->post('/loyalty/reset', fn($p) => $loyaltyController->resetAndReimport());
$router->get('/loyalty/auto-record', fn($p) => $loyaltyController->getAutoRecordStatus());
$router->post('/loyalty/auto-record', fn($p) => $loyaltyController->toggleAutoRecord());

// Routes Modules
$router->get('/modules', fn($p) => $moduleController->listModules());
$router->get('/modules/{code}', fn($p) => $moduleController->getModule($p));
$router->get('/modules/{code}/status', fn($p) => $moduleController->isModuleActive($p));
$router->put('/modules/{code}', fn($p) => $moduleController->updateModule($p));
$router->put('/modules/{code}/toggle', fn($p) => $moduleController->toggleModule($p));
$router->post('/modules/{code}/renew', fn($p) => $moduleController->renewModule($p));
$router->put('/modules/{code}/auto-renew', fn($p) => $moduleController->toggleAutoRenew($p));

// Routes Chat - Widget (doit etre AVANT les routes avec {id} pour eviter les conflits)
$router->get('/chat/widget/config', fn($p) => $chatController->getWidgetConfig());
$router->get('/chat/widget/code', fn($p) => $chatController->getWidgetCode());
$router->post('/chat/widget/key', fn($p) => $chatController->generateWidgetKey());

// Routes Chat - Conversations et Messages
$router->get('/chat/conversations', fn($p) => $chatController->listConversations());
$router->get('/chat/conversations/unread-count', fn($p) => $chatController->getUnreadCount());
$router->post('/chat/conversations', fn($p) => $chatController->getOrCreateConversation());
$router->get('/chat/conversations/{id}', fn($p) => $chatController->getConversation($p));
$router->get('/chat/conversations/by-phone/{phone}', fn($p) => $chatController->getConversationByPhone($p));
$router->put('/chat/conversations/{id}/close', fn($p) => $chatController->closeConversation($p));
$router->delete('/chat/conversations/{id}', fn($p) => $chatController->deleteConversation($p));
$router->get('/chat/conversations/{id}/messages', fn($p) => $chatController->listMessages($p));
$router->post('/chat/conversations/{id}/messages', fn($p) => $chatController->sendMessage($p));
$router->put('/chat/conversations/{id}/read', fn($p) => $chatController->markAsRead($p));
$router->get('/chat/messages/poll', fn($p) => $chatController->pollMessages());

// Routes Network (IP Pools)
$router->get('/network/pools', fn($p) => $networkController->listPools());
$router->post('/network/pools', fn($p) => $networkController->createPool());
$router->get('/network/pools/{id}/stats', fn($p) => $networkController->poolStats($p));
$router->get('/network/pools/{id}', fn($p) => $networkController->showPool($p));
$router->put('/network/pools/{id}', fn($p) => $networkController->updatePool($p));
$router->delete('/network/pools/{id}', fn($p) => $networkController->deletePool($p));
$router->get('/network/pools/{id}/ips', fn($p) => $networkController->listPoolIPs($p));
$router->get('/network/pools/{id}/next-available', fn($p) => $networkController->getNextAvailableIPApi($p));
$router->get('/network/pools/{id}/reserved-for/{userId}', fn($p) => $networkController->getReservedIPForUser($p));
$router->post('/network/allocations', fn($p) => $networkController->allocateIP());
$router->delete('/network/allocations/{id}', fn($p) => $networkController->releaseIP($p));
$router->put('/network/allocations/{id}/status', fn($p) => $networkController->updateIPStatus($p));

// Routes IP individuelles (alias pour compatibilité)
$router->post('/network/ips/{id}/release', fn($p) => $networkController->releaseIP($p));
$router->put('/network/ips/{id}/status', fn($p) => $networkController->updateIPStatus($p));
$router->put('/network/ips/{id}/reserve', fn($p) => $networkController->reserveIP($p));
$router->get('/network/ips/check/{ip}', fn($p) => $networkController->checkIPAvailability($p));

// Routes Billing (Facturation PPPoE)
$router->get('/billing/invoices', fn($p) => $billingController->listInvoices());
$router->post('/billing/invoices', fn($p) => $billingController->createInvoice());
$router->post('/billing/invoices/batch', fn($p) => $billingController->generateBatchInvoices());
$router->get('/billing/invoices/{id}', fn($p) => $billingController->showInvoice($p));
$router->put('/billing/invoices/{id}', fn($p) => $billingController->updateInvoice($p));
$router->delete('/billing/invoices/{id}', fn($p) => $billingController->deleteInvoice($p));
$router->post('/billing/invoices/{id}/cancel', fn($p) => $billingController->cancelInvoice($p));
$router->post('/billing/invoices/{id}/send', fn($p) => $billingController->sendInvoice($p));
$router->post('/billing/invoices/{id}/send-whatsapp', fn($p) => $billingController->sendInvoiceWhatsApp($p));
$router->get('/billing/invoices/{id}/html', fn($p) => $billingController->generateInvoiceHtml($p));
$router->get('/billing/invoices/{id}/pdf', fn($p) => $billingController->generateInvoicePdf($p));
$router->get('/billing/payments', fn($p) => $billingController->listPayments());
$router->post('/billing/payments', fn($p) => $billingController->createPayment());
$router->get('/billing/payments/{id}', fn($p) => $billingController->showPayment($p));
$router->get('/billing/stats', fn($p) => $billingController->stats());
$router->get('/billing/settings', fn($p) => $billingController->getSettings());
$router->put('/billing/settings', fn($p) => $billingController->updateSettings());
$router->get('/billing/users/{id}/summary', fn($p) => $billingController->getUserBillingSummary($p));
$router->post('/billing/invoices/{id}/pay', fn($p) => $billingController->payInvoice($p));
$router->get('/billing/notification-logs', fn($p) => $billingController->getNotificationLogs());

// Routes PPPoE Payment (Public - pas d'authentification requise)
$router->get('/pppoe-pay/lookup', fn($p) => $pppoePayController->lookup());
$router->post('/pppoe-pay/initiate', fn($p) => $pppoePayController->initiate());
$router->get('/pppoe-pay/transaction/{id}', fn($p) => $pppoePayController->getTransaction($p));
$router->post('/pppoe-pay/check-status', fn($p) => $pppoePayController->checkStatus());

// Routes Client Portal (Auth via client_sessions, pas d'auth admin)
$router->post('/client/login', fn($p) => $clientPortalController->login());
$router->post('/client/logout', fn($p) => $clientPortalController->logout());
$router->get('/client/account', fn($p) => $clientPortalController->getAccount());
$router->get('/client/invoices', fn($p) => $clientPortalController->getInvoices());
$router->get('/client/transactions', fn($p) => $clientPortalController->getTransactions());
$router->get('/client/plans', fn($p) => $clientPortalController->getPlans());
$router->post('/client/change-plan', fn($p) => $clientPortalController->changePlan());
$router->get('/client/traffic', fn($p) => $clientPortalController->getTrafficStats());
$router->post('/client/pay', fn($p) => $clientPortalController->initiatePayment());

// Routes OTP (Public - pas d'authentification requise)
$router->get('/otp/public-config', fn($p) => $otpController->getPublicConfig());
$router->post('/otp/send', fn($p) => $otpController->sendOtp());
$router->post('/otp/verify', fn($p) => $otpController->verifyOtp());

// Routes Registration (Public - pas d'authentification requise)
$router->get('/registration/public-config', fn($p) => $otpController->getRegistrationPublicConfig());
$router->get('/registration/profiles', fn($p) => $otpController->getPublicProfiles());
$router->post('/registration/send-otp', fn($p) => $otpController->registerAndSendOtp());
$router->post('/registration/verify', fn($p) => $otpController->verifyRegistration());

// Routes PPPoE Payment Transactions (Admin)
$router->get('/pppoe/payments/transactions', fn($p) => $pppoePayController->listTransactions());
$router->get('/pppoe/payments/stats', fn($p) => $pppoePayController->transactionStats());
$router->post('/pppoe/payments/retry-callback', fn($p) => $pppoePayController->retryCallback());
$router->post('/pppoe/payments/mark-completed', fn($p) => $pppoePayController->markCompleted());
$router->delete('/pppoe/payments/transactions/batch', fn($p) => $pppoePayController->deleteTransactionsBatch());
$router->delete('/pppoe/payments/transactions/{id}', fn($p) => $pppoePayController->deleteTransaction($p));

// Routes Bandwidth Management
$router->get('/bandwidth/policies', fn($p) => $bandwidthController->listPolicies());
$router->post('/bandwidth/policies', fn($p) => $bandwidthController->createPolicy());
$router->get('/bandwidth/policies/{id}', fn($p) => $bandwidthController->showPolicy($p));
$router->put('/bandwidth/policies/{id}', fn($p) => $bandwidthController->updatePolicy($p));
$router->delete('/bandwidth/policies/{id}', fn($p) => $bandwidthController->deletePolicy($p));
$router->get('/bandwidth/schedules', fn($p) => $bandwidthController->listSchedules());
$router->post('/bandwidth/schedules', fn($p) => $bandwidthController->createSchedule());
$router->put('/bandwidth/schedules/{id}', fn($p) => $bandwidthController->updateSchedule($p));
$router->delete('/bandwidth/schedules/{id}', fn($p) => $bandwidthController->deleteSchedule($p));
$router->post('/bandwidth/schedules/{id}/toggle', fn($p) => $bandwidthController->toggleSchedule($p));

// Routes Monitoring (Bande Passante)
$router->get('/monitoring/stats', fn($p) => $monitoringController->stats());
$router->get('/monitoring/top-users', fn($p) => $monitoringController->topUsers());
$router->get('/monitoring/live-sessions', fn($p) => $monitoringController->liveSessions());
$router->get('/monitoring/hourly-stats', fn($p) => $monitoringController->hourlyStats());
$router->get('/monitoring/daily-stats', fn($p) => $monitoringController->dailyStats());
$router->get('/monitoring/fup-alerts', fn($p) => $monitoringController->fupAlerts());

// Routes Telegram Notifications
$router->get('/telegram/config', fn($p) => $telegramController->getConfig());
$router->post('/telegram/config', fn($p) => $telegramController->saveConfig());
$router->post('/telegram/test', fn($p) => $telegramController->testConnection());
$router->get('/telegram/variables', fn($p) => $telegramController->getVariables());
$router->get('/telegram/templates', fn($p) => $telegramController->getTemplates());
$router->post('/telegram/templates', fn($p) => $telegramController->createTemplate());
$router->get('/telegram/templates/{id}', fn($p) => $telegramController->getTemplate($p));
$router->put('/telegram/templates/{id}', fn($p) => $telegramController->updateTemplate($p));
$router->delete('/telegram/templates/{id}', fn($p) => $telegramController->deleteTemplate($p));
$router->post('/telegram/templates/{id}/toggle', fn($p) => $telegramController->toggleTemplate($p));
$router->post('/telegram/templates/{id}/preview', fn($p) => $telegramController->previewTemplate($p));
$router->post('/telegram/test-template', fn($p) => $telegramController->testTemplate());
$router->get('/telegram/recipients', fn($p) => $telegramController->getRecipients());
$router->post('/telegram/recipients', fn($p) => $telegramController->createRecipient());
$router->put('/telegram/recipients/{id}', fn($p) => $telegramController->updateRecipient($p));
$router->delete('/telegram/recipients/{id}', fn($p) => $telegramController->deleteRecipient($p));
$router->post('/telegram/send', fn($p) => $telegramController->sendNotification());
$router->post('/telegram/send-bulk', fn($p) => $telegramController->sendBulkNotification());
$router->post('/telegram/process-expirations', fn($p) => $telegramController->processExpirations());
$router->get('/telegram/history', fn($p) => $telegramController->getHistory());
$router->get('/telegram/stats', fn($p) => $telegramController->getStats());

// Routes WhatsApp (Green API)
$router->get('/whatsapp/config', fn($p) => $whatsappController->getConfig());
$router->post('/whatsapp/config', fn($p) => $whatsappController->saveConfig());
$router->post('/whatsapp/test', fn($p) => $whatsappController->testConnection());
$router->get('/whatsapp/variables', fn($p) => $whatsappController->getVariables());
$router->get('/whatsapp/templates', fn($p) => $whatsappController->getTemplates());
$router->post('/whatsapp/templates', fn($p) => $whatsappController->createTemplate());
$router->get('/whatsapp/templates/{id}', fn($p) => $whatsappController->getTemplate($p));
$router->put('/whatsapp/templates/{id}', fn($p) => $whatsappController->updateTemplate($p));
$router->delete('/whatsapp/templates/{id}', fn($p) => $whatsappController->deleteTemplate($p));
$router->post('/whatsapp/templates/{id}/toggle', fn($p) => $whatsappController->toggleTemplate($p));
$router->post('/whatsapp/templates/{id}/preview', fn($p) => $whatsappController->previewTemplate($p));
$router->post('/whatsapp/test-template', fn($p) => $whatsappController->testTemplate());
$router->post('/whatsapp/send', fn($p) => $whatsappController->sendNotification());
$router->post('/whatsapp/send-bulk', fn($p) => $whatsappController->sendBulkNotification());
$router->post('/whatsapp/process-expirations', fn($p) => $whatsappController->processExpirations());
$router->get('/whatsapp/history', fn($p) => $whatsappController->getHistory());
$router->get('/whatsapp/stats', fn($p) => $whatsappController->getStats());

// Routes SMS Gateways
$router->get('/sms/providers', fn($p) => $smsController->getProviders());
$router->get('/sms/gateways', fn($p) => $smsController->getGateways());
$router->get('/sms/gateways/{id}', fn($p) => $smsController->getGateway($p));
$router->put('/sms/gateways/{id}', fn($p) => $smsController->updateGateway($p));
$router->post('/sms/gateways/{id}/toggle', fn($p) => $smsController->toggleGateway($p));
$router->post('/sms/gateways/{id}/test', fn($p) => $smsController->testGateway($p));
$router->get('/sms/gateways/{id}/balance', fn($p) => $smsController->getBalance($p));
$router->get('/sms/history', fn($p) => $smsController->getHistory());
$router->get('/sms/stats', fn($p) => $smsController->getStats());

// Routes SMS Templates
$router->get('/sms/templates', fn($p) => $smsController->getTemplates());
$router->post('/sms/templates', fn($p) => $smsController->createTemplate());
$router->put('/sms/templates/{id}', fn($p) => $smsController->updateTemplate($p));
$router->delete('/sms/templates/{id}', fn($p) => $smsController->deleteTemplate($p));
$router->post('/sms/templates/{id}/toggle', fn($p) => $smsController->toggleTemplate($p));
$router->get('/sms/variables', fn($p) => $smsController->getVariables());

// Routes PPPoE Reminders
$router->get('/pppoe-reminders/settings', fn($p) => $pppoeReminderController->getSettings());
$router->put('/pppoe-reminders/settings', fn($p) => $pppoeReminderController->updateSettings());
$router->get('/pppoe-reminders/rules', fn($p) => $pppoeReminderController->getRules());
$router->post('/pppoe-reminders/rules', fn($p) => $pppoeReminderController->createRule());
$router->put('/pppoe-reminders/rules/{id}', fn($p) => $pppoeReminderController->updateRule($p));
$router->delete('/pppoe-reminders/rules/{id}', fn($p) => $pppoeReminderController->deleteRule($p));
$router->post('/pppoe-reminders/rules/{id}/toggle', fn($p) => $pppoeReminderController->toggleRule($p));
$router->post('/pppoe-reminders/process', fn($p) => $pppoeReminderController->processReminders());
$router->get('/pppoe-reminders/history', fn($p) => $pppoeReminderController->getHistory());
$router->get('/pppoe-reminders/stats', fn($p) => $pppoeReminderController->getStats());
$router->get('/pppoe-reminders/variables', fn($p) => $pppoeReminderController->getVariables());

// Routes SMS Credits (CSMS)
$router->get('/sms-credits/balance', fn($p) => $smsCreditController->getBalance());
$router->post('/sms-credits/convert', fn($p) => $smsCreditController->convertCredits());
$router->get('/sms-credits/transactions', fn($p) => $smsCreditController->getTransactions());

// Routes OTP (Admin)
$router->get('/otp/config', fn($p) => $otpController->getConfig());
$router->put('/otp/config', fn($p) => $otpController->updateConfig());
$router->get('/otp/history', fn($p) => $otpController->getHistory());
$router->delete('/otp/history', fn($p) => $otpController->deleteHistory());
$router->get('/otp/export', fn($p) => $otpController->exportHistory());
$router->get('/otp/stats', fn($p) => $otpController->getStats());
$router->get('/otp/snippet', fn($p) => $otpController->getSnippet());

// Routes Registration (Admin)
$router->get('/registration/config', fn($p) => $otpController->getRegistrationConfig());
$router->put('/registration/config', fn($p) => $otpController->updateRegistrationConfig());
$router->get('/registration/history', fn($p) => $otpController->getRegistrationHistory());
$router->get('/registration/stats', fn($p) => $otpController->getRegistrationStats());
$router->get('/registration/snippet', fn($p) => $otpController->getRegistrationSnippet());

// Routes Marketing
$router->get('/marketing/clients', fn($p) => $marketingController->getClients());
$router->get('/marketing/profiles', fn($p) => $marketingController->getProfiles());
$router->get('/marketing/gateways', fn($p) => $marketingController->getGateways());
$router->post('/marketing/send', fn($p) => $marketingController->send());
$router->get('/marketing/campaigns', fn($p) => $marketingController->getCampaigns());
$router->get('/marketing/campaigns/{id}', fn($p) => $marketingController->getCampaignDetails($p));

// Routes Settings (Paramètres généraux)
$router->get('/settings', fn($p) => $settingsController->index());
$router->put('/settings', fn($p) => $settingsController->update());
$router->post('/settings/password', fn($p) => $settingsController->changePassword());

// Routes Captive Portal Editor
$router->get('/captive-portal/templates', fn($p) => $captivePortalController->listTemplates());
$router->get('/captive-portal/templates/{id}', fn($p) => $captivePortalController->getTemplate($p));
$router->post('/captive-portal/templates/{id}/save', fn($p) => $captivePortalController->saveTemplate($p));

// Routes SuperAdmin
$router->get('/superadmin/admins', fn($p) => $superAdminController->listAdmins());
$router->get('/superadmin/stats', fn($p) => $superAdminController->adminStats());
$router->post('/superadmin/admins', fn($p) => $superAdminController->createAdmin());
$router->get('/superadmin/admins/{id}', fn($p) => $superAdminController->showAdmin($p));
$router->put('/superadmin/admins/{id}', fn($p) => $superAdminController->updateAdmin($p));
$router->delete('/superadmin/admins/{id}', fn($p) => $superAdminController->deleteAdmin($p));
$router->post('/superadmin/admins/{id}/toggle', fn($p) => $superAdminController->toggleAdmin($p));
$router->get('/superadmin/permissions', fn($p) => $superAdminController->listPermissions());
$router->get('/superadmin/roles/{role}/permissions', fn($p) => $superAdminController->getRolePermissions($p));
$router->put('/superadmin/roles/{role}/permissions', fn($p) => $superAdminController->updateRolePermissions($p));
$router->get('/superadmin/users/{id}/permissions', fn($p) => $superAdminController->getUserPermissions($p));
$router->put('/superadmin/users/{id}/permissions', fn($p) => $superAdminController->updateUserPermissions($p));
$router->get('/superadmin/settings', fn($p) => $superAdminController->getGlobalSettings());
$router->put('/superadmin/settings', fn($p) => $superAdminController->updateGlobalSettings());

// Routes Credits (Admin)
$router->get('/credits/balance', fn($p) => $creditController->getBalance());
$router->get('/credits/transactions', fn($p) => $creditController->getTransactions());
$router->post('/credits/recharge', fn($p) => $creditController->initiateRecharge());
$router->get('/credits/recharge/status', fn($p) => $creditController->checkRechargeStatus());
$router->get('/credits/module-prices', fn($p) => $creditController->getModulePrices());
$router->get('/credits/recharge-gateways', fn($p) => $creditController->getRechargeGateways());

// Routes Module Pricing (SuperAdmin)
$router->get('/superadmin/module-pricing', fn($p) => $modulePricingController->listPricing());
$router->put('/superadmin/module-pricing/{code}', fn($p) => $modulePricingController->updatePricing($p));
$router->put('/superadmin/credit-settings', fn($p) => $modulePricingController->updateExchangeRate());
$router->get('/superadmin/credit-stats', fn($p) => $modulePricingController->creditStats());
$router->get('/superadmin/credit-transactions', fn($p) => $modulePricingController->listTransactions());
$router->post('/superadmin/admins/{id}/adjust-credits', fn($p) => $modulePricingController->adjustCredits($p));
$router->put('/superadmin/sms-credit-settings', fn($p) => $modulePricingController->updateSmsCreditSettings());
$router->get('/superadmin/platform-sms-balance', fn($p) => $modulePricingController->getPlatformSmsBalance());
$router->post('/superadmin/admins/{id}/adjust-sms-credits', fn($p) => $modulePricingController->adjustSmsCredits($p));
$router->get('/superadmin/recharge-gateways', fn($p) => $modulePricingController->listRechargeGateways());
$router->put('/superadmin/recharge-gateways/{code}', fn($p) => $modulePricingController->updateRechargeGateway($p));

// Routes Platform Payment Gateway (SuperAdmin)
$router->get('/superadmin/paygate/gateways', fn($p) => $platformPaymentController->superadminListGateways());
$router->put('/superadmin/paygate/gateways/{id}', fn($p) => $platformPaymentController->superadminUpdateGateway($p));
$router->put('/superadmin/paygate/settings', fn($p) => $platformPaymentController->superadminUpdateSettings());
$router->get('/superadmin/paygate/withdrawals', fn($p) => $platformPaymentController->superadminListWithdrawals());
$router->post('/superadmin/paygate/withdrawals/{id}/approve', fn($p) => $platformPaymentController->superadminApproveWithdrawal($p));
$router->post('/superadmin/paygate/withdrawals/{id}/complete', fn($p) => $platformPaymentController->superadminCompleteWithdrawal($p));
$router->post('/superadmin/paygate/withdrawals/{id}/reject', fn($p) => $platformPaymentController->superadminRejectWithdrawal($p));
$router->get('/superadmin/paygate/stats', fn($p) => $platformPaymentController->superadminStats());
$router->post('/superadmin/paygate/admins/{id}/adjust-balance', fn($p) => $platformPaymentController->superadminAdjustBalance($p));

// Routes System Notifications (SuperAdmin)
$router->get('/superadmin/notifications', fn($p) => $notificationController->listAll());
$router->post('/superadmin/notifications', fn($p) => $notificationController->create());
$router->put('/superadmin/notifications/{id}', fn($p) => $notificationController->update($p));
$router->delete('/superadmin/notifications/{id}', fn($p) => $notificationController->delete($p));

// Routes System Notifications (Admin / Lecteurs)
$router->get('/notifications', fn($p) => $notificationController->getUserNotifications());
$router->post('/notifications/{id}/read', fn($p) => $notificationController->markAsRead($p));
$router->post('/notifications/read-all', fn($p) => $notificationController->markAllAsRead());

// Dispatcher la requête
$router->dispatch();