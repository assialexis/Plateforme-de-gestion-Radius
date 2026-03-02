<?php
/**
 * Page publique de paiement pour acheter un voucher
 */

// Configuration
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Session et Langue
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en'])) {
    $_SESSION['lang'] = $_GET['lang'];
}

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Payment/PaymentService.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    die('Database connection failed');
}

// Récupérer le profil et l'admin
$profileId = isset($_GET['profile']) ? (int)$_GET['profile'] : 0;
$adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : null;
$cancelled = isset($_GET['cancelled']);

$profile = null;
$profiles = [];
$gateways = [];
$currency = $config['currency'] ?? 'XAF';
$appName = 'WiFi Hotspot';

// Si pas d'admin spécifié mais un profil est donné, déduire l'admin depuis le profil
if ($adminId === null && $profileId > 0) {
    $profileRow = $db->getProfileById($profileId);
    if ($profileRow && !empty($profileRow['admin_id'])) {
        $adminId = (int)$profileRow['admin_id'];
    }
}

// Si toujours pas d'admin, utiliser le premier admin
if ($adminId === null) {
    try {
        $stmt = $db->getPdo()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            $adminId = (int)$row['id'];
        }
    } catch (Exception $e) {}
}

// Charger le nom de l'app depuis les settings (scopé par admin)
try {
    if ($adminId !== null) {
        $stmt = $db->getPdo()->prepare("SELECT setting_value FROM settings WHERE setting_key = 'app_name' AND admin_id = ?");
        $stmt->execute([$adminId]);
    } else {
        $stmt = $db->getPdo()->query("SELECT setting_value FROM settings WHERE setting_key = 'app_name'");
    }
    $row = $stmt->fetch();
    if ($row) {
        $appName = $row['setting_value'];
    }
} catch (Exception $e) {}

// Charger les passerelles actives (scopées par admin)
$gateways = $db->getActivePaymentGateways($adminId);

// Charger les passerelles plateforme actives pour cet admin
$platformGateways = [];
try {
    require_once __DIR__ . '/../src/Payment/PlatformPaymentService.php';
    $config = require __DIR__ . '/../config/config.php';
    $platformPaymentService = new PlatformPaymentService($db->getPdo(), $config);
    if ($adminId) {
        $platformGateways = $platformPaymentService->getActiveForAdmin($adminId);
    }
} catch (Exception $e) {
    // Silently fail — platform gateways optional
}

// Vérifier si les modules sont activés
$chatEnabled = false;
$loyaltyEnabled = false;
try {
    // S'assurer que la table modules existe
    $db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            module_code VARCHAR(50) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            icon VARCHAR(50) DEFAULT 'cube',
            is_active TINYINT(1) DEFAULT 0,
            config JSON DEFAULT NULL,
            display_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // Insérer les modules par défaut si manquants
    $db->getPdo()->exec("
        INSERT IGNORE INTO modules (module_code, name, description, icon, is_active, display_order) VALUES
        ('loyalty', 'Programme de Fidélité', 'Récompenses automatiques pour les clients fidèles', 'gift', 1, 1),
        ('chat', 'Chat Client', 'Chat en temps réel avec les clients sur la page d''achat', 'chat-bubble-left-right', 1, 2),
        ('sms', 'Notifications SMS', 'Envoi de SMS aux clients', 'device-phone-mobile', 0, 3),
        ('analytics', 'Statistiques Avancées', 'Tableaux de bord et rapports détaillés', 'chart-bar', 0, 4)
    ");

    // Créer les tables de chat si elles n'existent pas
    $db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS chat_conversations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone VARCHAR(50) NOT NULL,
            customer_name VARCHAR(100) DEFAULT NULL,
            status ENUM('active', 'closed', 'archived') DEFAULT 'active',
            unread_count INT DEFAULT 0,
            last_message_at TIMESTAMP NULL DEFAULT NULL,
            closed_at TIMESTAMP NULL DEFAULT NULL,
            closed_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_phone (phone),
            INDEX idx_status (status)
        ) ENGINE=InnoDB
    ");

    $db->getPdo()->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            conversation_id INT NOT NULL,
            sender_type ENUM('customer', 'admin') NOT NULL,
            admin_id INT DEFAULT NULL,
            message TEXT NOT NULL,
            message_type VARCHAR(50) DEFAULT 'text',
            is_read TINYINT(1) DEFAULT 0,
            read_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_conversation (conversation_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB
    ");

    // Vérifier l'état des modules chat et loyalty (scopé par admin)
    if ($adminId !== null) {
        $stmt = $db->getPdo()->prepare("SELECT module_code, is_active FROM modules WHERE module_code IN ('chat', 'loyalty') AND admin_id = ?");
        $stmt->execute([$adminId]);
    } else {
        $stmt = $db->getPdo()->query("SELECT module_code, is_active FROM modules WHERE module_code IN ('chat', 'loyalty')");
    }
    while ($row = $stmt->fetch()) {
        if ($row['module_code'] === 'chat') {
            $chatEnabled = (bool)$row['is_active'];
        } elseif ($row['module_code'] === 'loyalty') {
            $loyaltyEnabled = (bool)$row['is_active'];
        }
    }
} catch (Exception $e) {
    // En cas d'erreur, log pour debug
    error_log('Module setup error: ' . $e->getMessage());
}

// Si un profil est spécifié, le charger
if ($profileId > 0) {
    $profile = $db->getProfileById($profileId);
}

// Charger tous les profils actifs (scopés par admin)
$allProfiles = $db->getAllProfiles(false, $adminId);
$profiles = array_filter($allProfiles, fn($p) => $p['is_active'] && $p['price'] > 0);

// Fonctions de formatage locales (pour éviter conflit avec helpers.php)
function payFormatPrice($amount, $currency) {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function payFormatDuration($seconds) {
    if (!$seconds) return 'Illimité';
    if ($seconds < 3600) return floor($seconds / 60) . ' min';
    if ($seconds < 86400) return floor($seconds / 3600) . 'h';
    return floor($seconds / 86400) . ' jour(s)';
}

function payFormatData($bytes) {
    if (!$bytes) return 'Illimité';
    if ($bytes < 1073741824) return round($bytes / 1048576) . ' MB';
    return round($bytes / 1073741824, 1) . ' GB';
}

function payFormatSpeed($bps) {
    if (!$bps) return 'Illimité';
    if ($bps < 1000000) return round($bps / 1000) . ' Kbps';
    return round($bps / 1000000) . ' Mbps';
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= __('pay.title') ?> -
        <?= htmlspecialchars($appName) ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Kkiapay SDK -->
    <script src="https://cdn.kkiapay.me/k.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }

        .profile-card {
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
        }

        .profile-card.selected {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }

        .gateway-btn {
            transition: all 0.2s ease;
        }

        .gateway-btn:hover:not(:disabled) {
            transform: translateY(-2px);
        }

        .gateway-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen py-8 px-4" x-data="paymentPage()">
        <div class="max-w-2xl mx-auto relative relative">

            <!-- Language Switcher -->
            <div class="absolute top-0 right-0 flex gap-2">
                <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'fr'])) ?>"
                    class="px-2 py-1 text-xs rounded font-medium <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'bg-white text-blue-900' : 'bg-white/20 text-white hover:bg-white/30 transition' ?>">FR</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'en'])) ?>"
                    class="px-2 py-1 text-xs rounded font-medium <?= ($_SESSION['lang'] ?? 'fr') === 'en' ? 'bg-white text-blue-900' : 'bg-white/20 text-white hover:bg-white/30 transition' ?>">EN</a>
            </div>

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                    <i class="fas fa-wifi text-3xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">
                    <?= htmlspecialchars($appName) ?>
                </h1>
                <p class="text-white/70">
                    <?= __('pay.subtitle') ?>
                </p>
                <a href="retrieve-ticket.php<?= $adminId ? '?admin=' . $adminId : '' ?>"
                    class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl border border-white/20 transition-all text-sm font-medium">
                    <i class="fas fa-search"></i>
                    <?= __('pay.find_ticket') ?>
                </a>
            </div>

            <?php if ($cancelled): ?>
            <!-- Message d'annulation -->
            <div class="glass-card rounded-xl p-4 mb-6 border-l-4 border-yellow-500">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    <p class="text-gray-700">
                        <?= __('pay.payment_cancelled') ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($gateways) && empty($platformGateways)): ?>
            <!-- Pas de passerelles actives -->
            <div class="glass-card rounded-xl p-8 text-center">
                <i class="fas fa-credit-card text-4xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">
                    <?= __('pay.no_payment_av') ?>
                </h2>
                <p class="text-gray-500">
                    <?= __('pay.no_payment_methods') ?>
                </p>
            </div>
            <?php elseif (empty($profiles)): ?>
            <!-- Pas de profils disponibles -->
            <div class="glass-card rounded-xl p-8 text-center">
                <i class="fas fa-ticket text-4xl text-gray-300 mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">
                    <?= __('pay.no_plan_av') ?>
                </h2>
                <p class="text-gray-500">
                    <?= __('pay.no_plans') ?>
                </p>
            </div>
            <?php else: ?>

            <!-- Étape 1: Sélection du profil -->
            <div class="glass-card rounded-xl p-6 mb-6" x-show="step === 1">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white text-sm mr-2">1</span>
                    <?= __('pay.choose_plan') ?>
                </h2>

                <div class="grid gap-4">
                    <?php foreach ($profiles as $p): ?>
                    <div class="profile-card border-2 rounded-xl p-4 cursor-pointer"
                        :class="selectedProfile?.id === <?= $p['id'] ?> ? 'selected border-blue-500 bg-blue-50' : 'border-gray-200 bg-white'"
                        @click="selectProfile(<?= htmlspecialchars(json_encode($p)) ?>)">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h3>
                                <?php if ($p['description']): ?>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= htmlspecialchars($p['description']) ?>
                                </p>
                                <?php endif; ?>
                                <div class="flex flex-wrap gap-3 mt-3 text-sm text-gray-600">
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-clock text-blue-500"></i>
                                        <?= payFormatDuration($p['time_limit']) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-database text-green-500"></i>
                                        <?= payFormatData($p['data_limit']) ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <i class="fas fa-tachometer-alt text-purple-500"></i>
                                        <?= payFormatSpeed($p['download_speed']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-blue-600">
                                    <?= payFormatPrice($p['price'], $currency) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button @click="step = 2" :disabled="!selectedProfile"
                    class="w-full mt-6 py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    <?= __('pay.continue') ?>
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>

            <!-- Étape 2: Numéro de téléphone et Paiement -->
            <div class="glass-card rounded-xl p-6" x-show="step === 2" x-cloak>
                <h2 class="text-lg font-semibold text-gray-800 mb-4">
                    <span
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-500 text-white text-sm mr-2">2</span>
                    Paiement
                </h2>

                <!-- Résumé du forfait -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-500">Forfait sélectionné</p>
                            <p class="font-semibold text-gray-800" x-text="selectedProfile?.name"></p>
                        </div>
                        <div class="text-xl font-bold text-blue-600" x-text="formatPrice(selectedProfile?.price)"></div>
                    </div>
                </div>

                <!-- Numéro de téléphone -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?= __('pay.phone_label') ?>
                    </label>
                    <div class="flex gap-2">
                        <input type="tel" x-model="customerPhone" <?php if ($loyaltyEnabled):
                            ?>@input.debounce.500ms="checkLoyalty()"
                        <?php endif; ?>
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500
                        focus:border-blue-500 text-lg"
                        placeholder="Ex: 90000000">
                        <?php if ($loyaltyEnabled): ?>
                        <button @click="checkLoyalty()" type="button"
                            class="px-4 py-3 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200 transition-colors"
                            :disabled="!customerPhone || loadingLoyalty">
                            <i class="fas fa-search" :class="loadingLoyalty ? 'animate-spin' : ''"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        <?= __('pay.phone_help') ?>
                        <?php if ($loyaltyEnabled): ?>
                        <?= __('pay.phone_loyalty_help') ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($loyaltyEnabled): ?>
                <!-- Carte de fidélité -->
                <div x-show="loyaltyChecked" x-cloak class="mb-6">
                    <!-- Client fidèle trouvé -->
                    <div x-show="loyaltyCustomer"
                        class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-full bg-yellow-500 flex items-center justify-center">
                                <i class="fas fa-star text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">
                                    <?= __('pay.loyal_customer') ?>
                                </p>
                                <p class="text-sm text-gray-600" x-text="loyaltyCustomer?.phone"></p>
                            </div>
                        </div>

                        <!-- Statistiques -->
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div class="bg-white/70 rounded-lg p-3 text-center">
                                <p class="text-3xl font-bold text-blue-600"
                                    x-text="loyaltyCustomer?.total_purchases || 0"></p>
                                <p class="text-xs text-gray-500">
                                    <?= __('pay.purchases_made') ?>
                                </p>
                            </div>
                            <div class="bg-white/70 rounded-lg p-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <i class="fas fa-coins text-yellow-500"></i>
                                    <p class="text-3xl font-bold text-yellow-600"
                                        x-text="loyaltyCustomer?.points_balance || 0"></p>
                                </div>
                                <p class="text-xs text-gray-500">
                                    <?= __('pay.points_earned') ?>
                                </p>
                            </div>
                        </div>

                        <!-- Détails points et bonus -->
                        <div class="flex justify-between text-sm bg-white/50 rounded-lg p-2 mb-3">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-gift text-green-500"></i>
                                <span class="text-gray-600">
                                    <?= __('pay.bonuses_earned') ?>
                                </span>
                                <span class="font-semibold text-green-600"
                                    x-text="loyaltyCustomer?.rewards_earned || 0"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-wallet text-blue-500"></i>
                                <span class="text-gray-600">
                                    <?= __('pay.total_spent') ?>
                                </span>
                                <span class="font-semibold text-blue-600"
                                    x-text="formatPrice(loyaltyCustomer?.total_spent || 0)"></span>
                            </div>
                        </div>

                        <!-- Progression vers le prochain bonus -->
                        <div x-show="loyaltyProgress?.has_active_rule" class="bg-white/70 rounded-lg p-3">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">
                                    <?= __('pay.next_bonus') ?>
                                </span>
                                <span class="text-sm text-gray-500"
                                    x-text="loyaltyProgress?.current + '/' + loyaltyProgress?.threshold + ' achats'"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-3 rounded-full transition-all duration-500"
                                    :style="`width: ${loyaltyProgress?.percentage || 0}%`"></div>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-gift text-orange-500 mr-1"></i>
                                <?= __('pay.only') ?> <strong x-text="loyaltyProgress?.remaining"></strong>
                                <?= __('pay.purchases_for_free_voucher') ?>
                            </p>
                        </div>

                        <!-- Récompenses en attente / Vouchers bonus -->
                        <div x-show="loyaltyCustomer?.pending_rewards?.length > 0" class="mt-3">
                            <div class="bg-green-100 border border-green-300 rounded-lg p-3 mb-2">
                                <div class="flex items-center gap-2 mb-2">
                                    <i class="fas fa-gift text-green-600 text-xl"></i>
                                    <p class="font-semibold text-green-800">
                                        <?= __('pay.bonus_vouchers_available') ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Liste des vouchers bonus -->
                            <div class="space-y-2">
                                <template x-for="reward in loyaltyCustomer?.pending_rewards || []" :key="reward.id">
                                    <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-300 border-dashed rounded-xl p-4 relative overflow-hidden cursor-pointer transition-all hover:shadow-lg"
                                        @click="reward.voucher_code && toggleCodeReveal(reward.id)">
                                        <!-- Décoration ticket -->
                                        <div
                                            class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full border-2 border-green-300">
                                        </div>
                                        <div
                                            class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white rounded-full border-2 border-green-300">
                                        </div>

                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div
                                                    class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center shadow-lg">
                                                    <i class="fas fa-ticket-alt text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-green-600 font-medium uppercase">
                                                        <?= __('pay.free_voucher') ?>
                                                    </p>
                                                    <template x-if="reward.voucher_code">
                                                        <div>
                                                            <!-- Code masqué par défaut -->
                                                            <template x-if="!isCodeRevealed(reward.id)">
                                                                <div>
                                                                    <p
                                                                        class="text-2xl font-bold text-gray-400 font-mono tracking-wider">
                                                                        <span
                                                                            x-text="maskCode(reward.voucher_code)"></span>
                                                                    </p>
                                                                    <p class="text-xs text-blue-500 mt-1">
                                                                        <i class="fas fa-eye mr-1"></i>
                                                                        <?= __('pay.tap_to_reveal') ?>
                                                                    </p>
                                                                </div>
                                                            </template>
                                                            <!-- Code révélé -->
                                                            <template x-if="isCodeRevealed(reward.id)">
                                                                <div>
                                                                    <p class="text-2xl font-bold text-green-800 font-mono tracking-wider"
                                                                        x-text="reward.voucher_code"></p>
                                                                    <p class="text-xs text-gray-500"
                                                                        x-text="'Profil: ' + (reward.profile_name || 'Standard')">
                                                                    </p>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </template>
                                                    <template x-if="!reward.voucher_code">
                                                        <div>
                                                            <p class="text-sm text-orange-600 font-medium">
                                                                <i class="fas fa-clock mr-1"></i>
                                                                <?= __('pay.pending_generation') ?>
                                                            </p>
                                                            <p class="text-xs text-gray-500">
                                                                <?= __('pay.contact_seller') ?>
                                                            </p>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <template x-if="reward.voucher_code && isCodeRevealed(reward.id)">
                                                    <button @click.stop="copyVoucherCode(reward.voucher_code)"
                                                        class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                                                        <i class="fas fa-copy mr-1"></i> Copier
                                                    </button>
                                                </template>
                                                <template x-if="reward.voucher_code && !isCodeRevealed(reward.id)">
                                                    <div
                                                        class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                        <i class="fas fa-eye text-blue-500"></i>
                                                    </div>
                                                </template>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    <?= __('pay.expires') ?> <span
                                                        x-text="formatExpireDate(reward.expires_at)"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Nouveau client -->
                    <div x-show="!loyaltyCustomer && loyaltyChecked"
                        class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center">
                                <i class="fas fa-user-plus text-white"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-800">
                                    <?= __('pay.welcome') ?>
                                </p>
                                <p class="text-sm text-gray-600">
                                    <?= __('pay.first_purchase') ?>
                                </p>
                                <p class="text-xs text-blue-600 mt-1">
                                    <i class="fas fa-star mr-1"></i>
                                    <?= __('pay.after_5_purchases') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Méthodes de paiement -->
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">
                        <?= __('pay.choose_payment_method') ?>
                    </p>
                </div>

                <div class="grid gap-3">
                    <?php foreach ($gateways as $gateway): ?>
                    <?php if ($gateway['gateway_code'] === 'feexpay'): ?>
                    <!-- FeexPay avec sélection d'opérateur -->
                    <button @click="showFeexPayModal = true" :disabled="loading"
                        class="gateway-btn flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all">
                        <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-money-check-alt text-xl text-gray-600"></i>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-semibold text-gray-800">
                                <?= htmlspecialchars($gateway['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">Mobile Money (MTN, Moov, Orange...)</div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <?php elseif ($gateway['gateway_code'] === 'kkiapay'): ?>
                    <!-- Kkiapay - Widget JavaScript -->
                    <button @click="initiateKkiapay()" :disabled="loading"
                        class="gateway-btn flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-purple-500 hover:bg-purple-50 transition-all">
                        <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                            <i class="fas fa-wallet text-xl text-purple-600"></i>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-semibold text-gray-800">
                                <?= htmlspecialchars($gateway['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($gateway['description'] ?? 'Mobile Money & Cartes') ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <?php else: ?>
                    <button @click="initiatePayment('<?= $gateway['gateway_code'] ?>')" :disabled="loading"
                        class="gateway-btn flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-blue-500 hover:bg-blue-50 transition-all">
                        <div class="w-12 h-12 rounded-lg bg-gray-100 flex items-center justify-center">
                            <?php
                            $icon = match($gateway['gateway_code']) {
                                'fedapay' => 'fa-credit-card',
                                'cinetpay' => 'fa-money-bill-wave',
                                'orange_money' => 'fa-mobile-alt',
                                'mtn_momo' => 'fa-mobile',
                                'paypal' => 'fa-paypal',
                                'stripe' => 'fa-stripe',
                                'moneroo' => 'fa-globe-africa',
                                'yengapay' => 'fa-money-check-alt',
                                default => 'fa-wallet'
                            };
                            ?>
                            <i class="fas <?= $icon ?> text-xl text-gray-600"></i>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-semibold text-gray-800">
                                <?= htmlspecialchars($gateway['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($gateway['description'] ?? '') ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <?php endif; ?>
                    <?php endforeach; ?>

                    <?php foreach ($platformGateways as $pgw): ?>
                    <button @click="initiatePayment('<?= $pgw['gateway_code'] ?>', null, null, true)" :disabled="loading"
                        class="gateway-btn flex items-center gap-4 p-4 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                        <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <?php
                            $pgIcon = match($pgw['gateway_code']) {
                                'fedapay' => 'fa-credit-card',
                                'cinetpay' => 'fa-money-bill-wave',
                                'kkiapay' => 'fa-wallet',
                                'feexpay' => 'fa-exchange-alt',
                                'ligdicash' => 'fa-coins',
                                'paydunya' => 'fa-hand-holding-usd',
                                'paygate_global' => 'fa-globe',
                                'yengapay' => 'fa-money-check-alt',
                                default => 'fa-wallet'
                            };
                            ?>
                            <i class="fas <?= $pgIcon ?> text-xl text-indigo-600"></i>
                        </div>
                        <div class="flex-1 text-left">
                            <div class="font-semibold text-gray-800">
                                <?= htmlspecialchars($pgw['name']) ?>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?= htmlspecialchars($pgw['description'] ?? '') ?>
                            </div>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400"></i>
                    </button>
                    <?php endforeach; ?>
                </div>

                <button @click="step = 1"
                    class="w-full mt-6 py-3 px-4 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <?= __('pay.change_plan') ?>
                </button>
            </div>

            <!-- Modal FeexPay - Sélection opérateur -->
            <div x-show="showFeexPayModal" x-cloak
                class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
                <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto"
                    @click.away="showFeexPayModal = false">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">
                                <?= __('pay.choose_operator') ?>
                            </h3>
                            <button @click="showFeexPayModal = false" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <!-- Opérateurs groupés par pays -->
                        <div class="space-y-4">
                            <!-- Bénin -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇧🇯 Bénin</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('mtn')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">
                                            MTN</div>
                                        <span class="text-sm font-medium">MTN</span>
                                    </button>
                                    <button @click="initiateFeexPay('moov')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold text-xs">
                                            M</div>
                                        <span class="text-sm font-medium">Moov</span>
                                    </button>
                                    <button @click="initiateFeexPay('celtiis_bj')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-green-500 hover:bg-green-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-green-600 flex items-center justify-center text-white font-bold text-xs">
                                            C</div>
                                        <span class="text-sm font-medium">Celtiis</span>
                                    </button>
                                    <button @click="initiateFeexPay('coris')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-purple-500 hover:bg-purple-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-purple-600 flex items-center justify-center text-white font-bold text-xs">
                                            CB</div>
                                        <span class="text-sm font-medium">Coris</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Togo -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇹🇬 Togo</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('togocom_tg')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-700 flex items-center justify-center text-white font-bold text-xs">
                                            TG</div>
                                        <span class="text-sm font-medium">Togocom</span>
                                    </button>
                                    <button @click="initiateFeexPay('moov_tg')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold text-xs">
                                            M</div>
                                        <span class="text-sm font-medium">Moov</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Côte d'Ivoire -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇨🇮 Côte d'Ivoire</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('mtn_ci')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">
                                            MTN</div>
                                        <span class="text-sm font-medium">MTN</span>
                                    </button>
                                    <button @click="initiateFeexPay('moov_ci')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold text-xs">
                                            M</div>
                                        <span class="text-sm font-medium">Moov</span>
                                    </button>
                                    <button @click="initiateFeexPay('orange_ci')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                            O</div>
                                        <span class="text-sm font-medium">Orange</span>
                                    </button>
                                    <button @click="initiateFeexPay('wave_ci')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-cyan-500 hover:bg-cyan-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-cyan-500 flex items-center justify-center text-white font-bold text-xs">
                                            W</div>
                                        <span class="text-sm font-medium">Wave</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Burkina Faso -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇧🇫 Burkina Faso</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('moov_bf')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-blue-600 flex items-center justify-center text-white font-bold text-xs">
                                            M</div>
                                        <span class="text-sm font-medium">Moov</span>
                                    </button>
                                    <button @click="initiateFeexPay('orange_bf')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                            O</div>
                                        <span class="text-sm font-medium">Orange</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Sénégal -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇸🇳 Sénégal</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('orange_sn')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                            O</div>
                                        <span class="text-sm font-medium">Orange</span>
                                    </button>
                                    <button @click="initiateFeexPay('free_sn')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-red-500 hover:bg-red-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-red-600 flex items-center justify-center text-white font-bold text-xs">
                                            F</div>
                                        <span class="text-sm font-medium">Free</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Cameroun -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇨🇲 Cameroun</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('mtn_cm')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">
                                            MTN</div>
                                        <span class="text-sm font-medium">MTN</span>
                                    </button>
                                    <button @click="initiateFeexPay('orange_cm')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-orange-500 flex items-center justify-center text-white font-bold text-xs">
                                            O</div>
                                        <span class="text-sm font-medium">Orange</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Congo -->
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🇨🇬 Congo</p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button @click="initiateFeexPay('mtn_cg')"
                                        class="flex items-center gap-2 p-3 border-2 border-gray-200 rounded-lg hover:border-yellow-500 hover:bg-yellow-50 transition-all">
                                        <div
                                            class="w-8 h-8 rounded bg-yellow-500 flex items-center justify-center text-white font-bold text-xs">
                                            MTN</div>
                                        <span class="text-sm font-medium">MTN</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div x-show="loading" x-cloak class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <div class="bg-white rounded-xl p-8 text-center">
                    <div
                        class="w-12 h-12 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mx-auto mb-4">
                    </div>
                    <p class="text-gray-600">
                        <?= __('pay.init_payment') ?>
                    </p>
                </div>
            </div>

            <?php endif; ?>

            <!-- Footer -->
            <div class="text-center mt-8 text-white/50 text-sm">
                <p>
                    <?= __('pay.secure_payment') ?>
                </p>
                <p class="mt-1">Powered by RADIUS Manager</p>
            </div>
        </div>
    </div>

    <script>
        function collectDeviceInfo() {
            const ua = navigator.userAgent;

            // Type d'appareil
            let deviceType = 'desktop';
            if (/Mobi|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)) {
                deviceType = 'mobile';
            } else if (/iPad|Android(?!.*Mobile)|Tablet/i.test(ua)) {
                deviceType = 'tablet';
            }

            // Système d'exploitation
            let os = 'Autre';
            if (/Windows/i.test(ua)) os = 'Windows';
            else if (/iPhone|iPad|iPod/i.test(ua)) os = 'iOS';
            else if (/Mac OS/i.test(ua)) os = 'macOS';
            else if (/Android/i.test(ua)) os = 'Android';
            else if (/Linux/i.test(ua)) os = 'Linux';
            else if (/CrOS/i.test(ua)) os = 'ChromeOS';

            // Navigateur
            let browser = 'Autre';
            if (/Edg\//i.test(ua)) browser = 'Edge';
            else if (/OPR\//i.test(ua) || /Opera/i.test(ua)) browser = 'Opera';
            else if (/SamsungBrowser/i.test(ua)) browser = 'Samsung Internet';
            else if (/Firefox/i.test(ua)) browser = 'Firefox';
            else if (/Chrome/i.test(ua)) browser = 'Chrome';
            else if (/Safari/i.test(ua)) browser = 'Safari';

            return {
                user_agent: ua,
                device_type: deviceType,
                os: os,
                browser: browser,
                screen_resolution: screen.width + 'x' + screen.height,
                language: navigator.language || 'unknown'
            };
        }

        function paymentPage() {
            return {
                step: <?= $profile ? 2 : 1 ?>,
                selectedProfile: <?= $profile ? json_encode($profile) : 'null' ?>,
                customerPhone: '',
                loading: false,
                showFeexPayModal: false,
                currency: '<?= $currency ?>',

                // Fidélité
                loyaltyChecked: false,
                loadingLoyalty: false,
                loyaltyCustomer: null,
                loyaltyProgress: null,
                revealedCodes: [], // Codes révélés par l'utilisateur

                selectProfile(profile) {
                    this.selectedProfile = profile;
                },

                formatPrice(amount) {
                    if (!amount) return '';
                    return new Intl.NumberFormat('fr-FR').format(amount) + ' ' + this.currency;
                },

                async checkLoyalty() {
                    if (!this.customerPhone || this.customerPhone.length < 8) {
                        this.loyaltyChecked = false;
                        this.loyaltyCustomer = null;
                        this.loyaltyProgress = null;
                        return;
                    }

                    this.loadingLoyalty = true;

                    try {
                        const response = await fetch(`api.php?route=/loyalty/customers/phone/${encodeURIComponent(this.customerPhone)}`);
                        const result = await response.json();

                        this.loyaltyChecked = true;

                        if (result.success && result.data.found) {
                            this.loyaltyCustomer = result.data.customer;
                            this.loyaltyProgress = result.data.customer.progress;
                        } else {
                            this.loyaltyCustomer = null;
                            this.loyaltyProgress = null;
                        }
                    } catch (error) {
                        console.error('Loyalty check error:', error);
                        this.loyaltyChecked = true;
                        this.loyaltyCustomer = null;
                    } finally {
                        this.loadingLoyalty = false;
                    }
                },

                async initiatePayment(gatewayCode, operator = null, network = null, isPlatform = false) {
                    if (!this.selectedProfile) {
                        alert('Veuillez sélectionner un forfait');
                        return;
                    }

                    if (!this.customerPhone) {
                        alert('Veuillez entrer votre numéro de téléphone');
                        return;
                    }

                    this.loading = true;
                    this.showFeexPayModal = false;

                    try {
                        const payload = {
                            gateway_code: gatewayCode,
                            profile_id: this.selectedProfile.id,
                            customer_phone: this.customerPhone,
                            device_info: collectDeviceInfo()
                        };

                        // Ajouter l'opérateur si spécifié (pour FeexPay)
                        if (operator) {
                            payload.operator = operator;
                        }

                        // Ajouter le réseau si spécifié (pour PayGate)
                        if (network) {
                            payload.network = network;
                        }

                        // Paiement via passerelle plateforme
                        if (isPlatform) {
                            payload.is_platform = true;
                        }

                        const response = await fetch('api.php?route=/payments/initiate', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        const result = await response.json();

                        // Vérifier les différentes clés de redirection selon la passerelle
                        const redirectUrl = result.data?.payment_url || result.data?.redirect_url;

                        if (result.success && redirectUrl) {
                            // Rediriger vers la page de paiement
                            window.location.href = redirectUrl;
                        } else {
                            throw new Error(result.message || 'Erreur lors de l\'initialisation du paiement');
                        }
                    } catch (error) {
                        console.error('Payment error:', error);
                        alert(error.message || 'Une erreur est survenue. Veuillez réessayer.');
                        this.loading = false;
                    }
                },

                async initiateFeexPay(operator) {
                    await this.initiatePayment('feexpay', operator);
                },

                async initiateKkiapay() {
                    if (!this.selectedProfile) {
                        alert('Veuillez sélectionner un forfait');
                        return;
                    }

                    if (!this.customerPhone) {
                        alert('Veuillez entrer votre numéro de téléphone');
                        return;
                    }

                    // Vérifier que le SDK Kkiapay est chargé
                    if (typeof openKkiapayWidget !== 'function') {
                        alert('Le SDK Kkiapay n\'est pas chargé. Veuillez rafraîchir la page.');
                        return;
                    }

                    this.loading = true;

                    try {
                        // D'abord créer la transaction côté serveur
                        const response = await fetch('api.php?route=/payments/initiate', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                gateway_code: 'kkiapay',
                                profile_id: this.selectedProfile.id,
                                customer_phone: this.customerPhone,
                                device_info: collectDeviceInfo()
                            })
                        });

                        const result = await response.json();
                        console.log('Kkiapay init response:', result);

                        if (!result.success) {
                            throw new Error(result.message || 'Erreur lors de l\'initialisation');
                        }

                        // Récupérer la configuration du widget
                        const widgetConfig = result.data.widget_config;
                        const transactionId = result.data.transaction_id;
                        const returnUrl = result.data.return_url;

                        // Vérifier que la clé publique est configurée
                        if (!widgetConfig.key) {
                            throw new Error('La clé publique Kkiapay n\'est pas configurée.');
                        }

                        console.log('Kkiapay config:', widgetConfig);
                        console.log('Sandbox mode:', widgetConfig.sandbox);

                        // Stocker les infos pour le callback
                        window.kkiapayTransactionId = transactionId;
                        window.kkiapayReturnUrl = returnUrl;

                        this.loading = false;

                        // Configurer les listeners AVANT d'ouvrir le widget
                        if (typeof addSuccessListener === 'function') {
                            addSuccessListener(async (kkResponse) => {
                                console.log('Kkiapay success:', kkResponse);

                                try {
                                    // Valider la transaction côté serveur
                                    const verifyResponse = await fetch('api.php?route=/payments/kkiapay/complete', {
                                        method: 'POST',
                                        headers: { 'Content-Type': 'application/json' },
                                        body: JSON.stringify({
                                            transaction_id: window.kkiapayTransactionId,
                                            kkiapay_transaction_id: kkResponse.transactionId
                                        })
                                    });

                                    const verifyResult = await verifyResponse.json();

                                    if (verifyResult.success) {
                                        window.location.href = window.kkiapayReturnUrl;
                                    } else {
                                        throw new Error(verifyResult.message || 'Erreur de vérification');
                                    }
                                } catch (error) {
                                    console.error('Kkiapay verify error:', error);
                                    alert('Paiement réussi! Référence: ' + kkResponse.transactionId);
                                    window.location.href = window.kkiapayReturnUrl;
                                }
                            });
                        }

                        if (typeof addFailedListener === 'function') {
                            addFailedListener((error) => {
                                console.error('Kkiapay failed:', error);
                                alert('Le paiement a échoué. Veuillez réessayer.');
                            });
                        }

                        // Ouvrir le widget Kkiapay avec l'API JavaScript
                        // Documentation: https://docs.kkiapay.me/v1/plugin-et-sdk/sdk-javascript
                        const kkConfig = {
                            amount: parseInt(widgetConfig.amount),
                            key: widgetConfig.key,
                            sandbox: widgetConfig.sandbox === true || widgetConfig.sandbox === 'true' || widgetConfig.sandbox === 1,
                            data: transactionId,
                            theme: '#4F46E5',
                            position: 'center'
                        };

                        // Ajouter le téléphone seulement s'il est défini
                        if (widgetConfig.phone) {
                            kkConfig.phone = widgetConfig.phone;
                        }

                        // Ajouter le nom seulement s'il est défini
                        if (widgetConfig.name && widgetConfig.name !== 'Client') {
                            kkConfig.name = widgetConfig.name;
                        }

                        console.log('Opening Kkiapay widget with config:', kkConfig);
                        console.log('sandbox value type:', typeof kkConfig.sandbox, 'value:', kkConfig.sandbox);

                        try {
                            openKkiapayWidget(kkConfig);
                            console.log('Widget opened successfully');
                        } catch (widgetError) {
                            console.error('Error opening widget:', widgetError);
                            alert('Erreur lors de l\'ouverture du widget: ' + widgetError.message);
                        }

                    } catch (error) {
                        console.error('Kkiapay init error:', error);
                        alert(error.message || 'Une erreur est survenue');
                        this.loading = false;
                    }
                },

                copyVoucherCode(code) {
                    navigator.clipboard.writeText(code).then(() => {
                        alert('Code copié: ' + code);
                    }).catch(() => {
                        // Fallback pour les anciens navigateurs
                        const input = document.createElement('input');
                        input.value = code;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        alert('Code copié: ' + code);
                    });
                },

                formatExpireDate(dateStr) {
                    if (!dateStr) return '-';
                    const date = new Date(dateStr);
                    const now = new Date();
                    const diffDays = Math.ceil((date - now) / (1000 * 60 * 60 * 24));

                    if (diffDays < 0) return 'Expiré';
                    if (diffDays === 0) return "Aujourd'hui";
                    if (diffDays === 1) return 'Demain';
                    if (diffDays < 7) return diffDays + ' jours';

                    return date.toLocaleDateString('fr-FR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric'
                    });
                },

                // Masquer le code voucher (ex: BONUS-XXXXXX -> BONUS-******)
                maskCode(code) {
                    if (!code) return '';
                    // Garder le préfixe BONUS- et masquer le reste
                    if (code.startsWith('BONUS-')) {
                        return 'BONUS-******';
                    }
                    // Pour les autres codes, masquer tous sauf les 2 premiers et 2 derniers caractères
                    if (code.length <= 4) return '*'.repeat(code.length);
                    return code.substring(0, 2) + '*'.repeat(code.length - 4) + code.substring(code.length - 2);
                },

                // Vérifier si un code est révélé
                isCodeRevealed(rewardId) {
                    return this.revealedCodes.includes(rewardId);
                },

                // Basculer l'état révélé/masqué d'un code
                toggleCodeReveal(rewardId) {
                    if (this.revealedCodes.includes(rewardId)) {
                        // Re-masquer le code
                        this.revealedCodes = this.revealedCodes.filter(id => id !== rewardId);
                    } else {
                        // Révéler le code
                        this.revealedCodes.push(rewardId);
                    }
                }
            };
        }
    </script>

    <?php if ($chatEnabled): ?>
    <!-- Widget Chat Support -->
    <div x-data="chatWidget()" x-init="init()" x-cloak>
        <!-- Bouton flottant pour ouvrir le chat -->
        <button @click="toggleChat()"
            class="fixed bottom-6 right-6 w-14 h-14 bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-lg flex items-center justify-center transition-all z-50 hover:scale-110">
            <template x-if="!isOpen">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </template>
            <template x-if="isOpen">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </template>
            <!-- Badge messages non lus -->
            <span x-show="unreadCount > 0" x-cloak
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold w-5 h-5 rounded-full flex items-center justify-center"
                x-text="unreadCount"></span>
        </button>

        <!-- Fenetre de chat -->
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="fixed bottom-24 right-6 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl overflow-hidden z-50 flex flex-col"
            style="max-height: 500px;">

            <!-- Audio element for remote stream -->
            <audio id="clientRemoteAudio" autoplay></audio>

            <!-- Header -->
            <div class="bg-blue-600 text-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold">Support Client</h3>
                        <p class="text-sm text-blue-100" x-show="callState === 'idle'">Nous sommes la pour vous aider
                        </p>
                        <!-- Call status in header -->
                        <p class="text-sm text-green-200 flex items-center gap-1" x-show="callState === 'connected'">
                            <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse inline-block"></span>
                            Appel en cours - <span x-text="callDurationFormatted"></span>
                        </p>
                        <p class="text-sm text-yellow-200" x-show="callState === 'calling'">Appel en cours...</p>
                    </div>
                    <!-- Call controls in header -->
                    <div class="flex items-center gap-2" x-show="callState === 'connected'">
                        <button @click="toggleMute()"
                            class="w-8 h-8 rounded-full flex items-center justify-center transition-colors"
                            :class="isMuted ? 'bg-red-500' : 'bg-white/20 hover:bg-white/30'">
                            <svg x-show="!isMuted" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                            </svg>
                            <svg x-show="isMuted" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                            </svg>
                        </button>
                        <button @click="endCall()"
                            class="w-8 h-8 rounded-full bg-red-500 hover:bg-red-600 flex items-center justify-center transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.516l2.257-1.13a1 1 0 00.502-1.21L8.228 3.684A1 1 0 007.28 3H5z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Incoming call banner -->
            <div x-show="callState === 'incoming'" x-transition
                class="bg-gradient-to-r from-green-500 to-emerald-600 p-4 flex items-center justify-between">
                <div class="flex items-center gap-3 text-white">
                    <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center animate-pulse">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-semibold text-sm">Appel entrant</p>
                        <p class="text-xs text-white/80">Le support vous appelle</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="acceptCall()"
                        class="px-3 py-1.5 bg-white text-green-600 font-semibold rounded-lg hover:bg-green-50 transition-colors text-sm">
                        Accepter
                    </button>
                    <button @click="rejectCall()"
                        class="px-3 py-1.5 bg-red-500 text-white font-semibold rounded-lg hover:bg-red-600 transition-colors text-sm">
                        Refuser
                    </button>
                </div>
            </div>

            <!-- Formulaire telephone (avant de chatter) -->
            <template x-if="!conversationId && !phone">
                <div class="p-6 flex-1 flex flex-col justify-center">
                    <div class="text-center mb-4">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <p class="text-gray-600 text-sm">Entrez votre numero de telephone pour commencer a discuter</p>
                    </div>
                    <form @submit.prevent="startChat()">
                        <input type="tel" x-model="phoneInput" placeholder="Ex: 90123456" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-3"
                            pattern="[0-9+]{8,15}">
                        <input type="text" x-model="nameInput" placeholder="Votre nom (optionnel)"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent mb-4">
                        <button type="submit" :disabled="!phoneInput || phoneInput.length < 8 || loading"
                            class="w-full py-3 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-show="!loading">Demarrer le chat</span>
                            <span x-show="loading">Chargement...</span>
                        </button>
                    </form>
                </div>
            </template>

            <!-- Interface de chat -->
            <div x-show="conversationId || phone" class="flex-1 flex flex-col" style="min-height: 350px;">
                <!-- Messages -->
                <div class="flex-1 overflow-y-auto p-4 space-y-3" id="chatMessagesContainer" style="max-height: 300px;">
                    <!-- Loading -->
                    <div x-show="loading && messages.length === 0" class="text-center text-gray-500 py-8">
                        <svg class="animate-spin h-6 w-6 mx-auto text-blue-600" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>

                    <!-- Empty state -->
                    <div x-show="messages.length === 0 && !loading" class="text-center text-gray-500 py-8">
                        <p class="text-sm">Commencez la conversation!</p>
                    </div>

                    <!-- Messages list -->
                    <template x-for="msg in messages" :key="msg.id">
                        <div>
                            <!-- Call history message -->
                            <div x-show="isCallMessage(msg)" class="flex justify-center my-2">
                                <div class="bg-gray-100 rounded-full px-3 py-1.5 flex items-center gap-2">
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                    </svg>
                                    <span class="text-xs text-gray-600" x-text="getCallText(msg)"></span>
                                    <span class="text-xs text-gray-400" x-text="formatTime(msg.created_at)"></span>
                                </div>
                            </div>
                            <!-- Regular message -->
                            <div x-show="!isCallMessage(msg)"
                                :class="msg.sender_type === 'customer' ? 'flex justify-end' : 'flex justify-start'">
                                <div :class="msg.sender_type === 'customer'
                                        ? 'bg-blue-600 text-white rounded-tl-2xl rounded-tr-2xl rounded-bl-2xl'
                                        : 'bg-gray-100 text-gray-900 rounded-tl-2xl rounded-tr-2xl rounded-br-2xl'"
                                    class="max-w-[80%] px-4 py-2">
                                    <p class="text-sm whitespace-pre-wrap" x-text="msg.message"></p>
                                    <span :class="msg.sender_type === 'customer' ? 'text-blue-200' : 'text-gray-400'"
                                        class="text-xs block mt-1 text-right"
                                        x-text="formatTime(msg.created_at)"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Zone de saisie -->
                <div class="p-3 border-t border-gray-200">
                    <form @submit.prevent="sendMessage()" class="flex gap-2">
                        <input type="text" x-model="newMessage" :disabled="sending" placeholder="Votre message..."
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                        <button type="submit" :disabled="(!newMessage.trim() && !sending) || sending"
                            class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <template x-if="!sending">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </template>
                            <template x-if="sending">
                                <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            </template>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script>
        function chatWidget() {
            return {
                isOpen: false,
                phone: localStorage.getItem('chat_phone') || '',
                conversationId: (() => {
                    const id = localStorage.getItem('chat_conversation_id');
                    return (id && id !== 'null' && id !== 'undefined') ? id : null;
                })(),
                phoneInput: '',
                nameInput: '',
                messages: [],
                newMessage: '',
                loading: false,
                sending: false,
                pollInterval: null,
                lastMessageId: 0,
                unreadCount: 0,

                // WebRTC
                callState: 'idle', // idle, calling, incoming, connected
                peerConnection: null,
                localStream: null,
                isMuted: false,
                callDuration: 0,
                callDurationFormatted: '00:00',
                callTimer: null,
                rtcPollInterval: null,
                lastRtcMessageId: 0,
                pendingCandidates: [],
                incomingOffer: null,

                toggleChat() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen && this.phone) {
                        this.loadMessages();
                        // Le polling est deja demarre dans init(), pas besoin de le redemarrer
                    }
                    if (this.isOpen) {
                        this.unreadCount = 0;
                    }
                },

                async startChat() {
                    if (!this.phoneInput || this.phoneInput.length < 8) return;

                    this.loading = true;
                    try {
                        const response = await fetch('api.php?route=/chat/conversations', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                phone: this.phoneInput,
                                customer_name: this.nameInput || null,
                                admin_id: <?= $adminId ?? 'null' ?>
                            })
                        });
                        const data = await response.json();
                        if (data.success && data.data?.conversation) {
                            this.phone = this.phoneInput;
                            this.conversationId = data.data.conversation.id;
                            localStorage.setItem('chat_phone', this.phone);
                            localStorage.setItem('chat_conversation_id', this.conversationId);
                            await this.loadMessages();
                            this.lastRtcMessageId = this.lastMessageId;
                            this.startPolling();
                            this.startRtcPolling();
                        }
                    } catch (error) {
                        console.error('Erreur demarrage chat:', error);
                        alert('Erreur lors du demarrage du chat');
                    } finally {
                        this.loading = false;
                    }
                },

                async loadMessages() {
                    if (!this.conversationId) {
                        this.loading = false;
                        return;
                    }

                    this.loading = true;
                    try {
                        const response = await fetch(`api.php?route=/chat/conversations/${this.conversationId}/messages`);
                        if (!response.ok) {
                            throw new Error('HTTP error: ' + response.status);
                        }
                        const data = await response.json();
                        if (data.success && data.data) {
                            this.messages = data.data.messages || [];
                            if (this.messages.length > 0) {
                                this.lastMessageId = this.messages[this.messages.length - 1].id;
                            }
                            this.$nextTick(() => this.scrollToBottom());
                        } else if (!data.success) {
                            // Conversation non trouvee - nettoyer
                            console.warn('Conversation non trouvee, reset du chat');
                            throw new Error(data.message || 'Conversation not found');
                        }
                    } catch (error) {
                        console.error('Erreur chargement messages:', error);
                        throw error; // Propager l'erreur pour que init() puisse la gerer
                    } finally {
                        this.loading = false;
                    }
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || !this.conversationId) {
                        console.log('sendMessage blocked:', { newMessage: this.newMessage, conversationId: this.conversationId });
                        return;
                    }

                    const messageText = this.newMessage.trim();
                    this.newMessage = ''; // Vider immédiatement pour feedback
                    this.sending = true;

                    // Ajouter un message temporaire pour feedback instantané
                    const tempId = 'temp_' + Date.now();
                    const tempMessage = {
                        id: tempId,
                        message: messageText,
                        sender_type: 'customer',
                        created_at: new Date().toISOString(),
                        _pending: true
                    };
                    this.messages.push(tempMessage);
                    console.log('Message temporaire ajouté:', tempMessage, 'Total messages:', this.messages.length);
                    this.$nextTick(() => this.scrollToBottom());

                    try {
                        const response = await fetch(`api.php?route=/chat/conversations/${this.conversationId}/messages`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                message: messageText,
                                sender_type: 'customer'
                            })
                        });
                        const data = await response.json();
                        console.log('Réponse API sendMessage:', data);
                        if (data.success && data.data?.message) {
                            // Remplacer le message temporaire par le vrai
                            const idx = this.messages.findIndex(m => m.id === tempId);
                            if (idx !== -1) {
                                this.messages.splice(idx, 1, data.data.message);
                            }
                            this.lastMessageId = data.data.message.id;
                            console.log('Message confirmé, total:', this.messages.length);
                        } else {
                            // Erreur - retirer le message temporaire
                            this.messages = this.messages.filter(m => m.id !== tempId);
                            console.error('Erreur envoi:', data);
                        }
                    } catch (error) {
                        console.error('Erreur envoi message:', error);
                        // Retirer le message temporaire en cas d'erreur
                        this.messages = this.messages.filter(m => m.id !== tempId);
                    } finally {
                        this.sending = false;
                    }
                },

                startPolling() {
                    if (this.pollInterval) clearInterval(this.pollInterval);
                    this.pollInterval = setInterval(() => this.pollNewMessages(), 3000);
                },

                stopPolling() {
                    if (this.pollInterval) {
                        clearInterval(this.pollInterval);
                        this.pollInterval = null;
                    }
                },

                async pollNewMessages() {
                    if (!this.phone || !this.conversationId) return;

                    try {
                        const response = await fetch(`api.php?route=/chat/messages/poll&phone=${encodeURIComponent(this.phone)}&after_id=${this.lastMessageId}&type=text`);
                        const data = await response.json();
                        const messages = data.data?.messages || [];
                        if (data.success && messages.length > 0) {
                            this.messages.push(...messages);
                            this.lastMessageId = messages[messages.length - 1].id;
                            this.$nextTick(() => this.scrollToBottom());

                            // Compter les messages admin non lus si le chat est ferme
                            if (!this.isOpen) {
                                const adminMsgs = messages.filter(m => m.sender_type === 'admin');
                                this.unreadCount += adminMsgs.length;
                            }
                        }
                        // Mettre a jour l'ID de conversation si besoin
                        if (data.data?.conversation_id && !this.conversationId) {
                            this.conversationId = data.data.conversation_id;
                            localStorage.setItem('chat_conversation_id', this.conversationId);
                        }
                    } catch (error) {
                        console.error('Erreur polling:', error);
                    }
                },

                scrollToBottom() {
                    const container = document.getElementById('chatMessagesContainer');
                    if (container) {
                        container.scrollTop = container.scrollHeight;
                    }
                    // Reset unread quand on voit les messages
                    if (this.isOpen) {
                        this.unreadCount = 0;
                    }
                },

                formatTime(dateStr) {
                    if (!dateStr) return '';
                    const date = new Date(dateStr);
                    return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                },

                isCallMessage(msg) {
                    try {
                        return JSON.parse(msg.message)?._type === 'call';
                    } catch { return false; }
                },

                getCallText(msg) {
                    try {
                        const data = JSON.parse(msg.message);
                        if (data._type !== 'call') return msg.message;
                        const d = data.duration || 0;
                        const m = String(Math.floor(d / 60)).padStart(2, '0');
                        const s = String(d % 60).padStart(2, '0');
                        switch (data.status) {
                            case 'completed': return `Appel vocal - ${m}:${s}`;
                            case 'rejected': return 'Appel refuse';
                            case 'missed': return 'Appel manque';
                            case 'failed': return 'Appel echoue';
                            default: return 'Appel';
                        }
                    } catch { return msg.message; }
                },

                async init() {
                    console.log('Chat widget init - phone:', this.phone, 'conversationId:', this.conversationId);

                    // Si on a deja un telephone enregistre, charger les messages et commencer le polling
                    if (this.phone && this.conversationId) {
                        try {
                            // Charger les messages existants en arriere-plan
                            await this.loadMessages();
                            this.lastRtcMessageId = this.lastMessageId;
                            // Demarrer le polling pour les nouveaux messages seulement si loadMessages a reussi
                            this.startPolling();
                            this.startRtcPolling();
                        } catch (error) {
                            console.error('Erreur init chat:', error);
                            // En cas d'erreur, nettoyer le localStorage
                            this.resetChat();
                        }
                    } else if (this.phone && !this.conversationId) {
                        // On a un telephone mais pas de conversation - nettoyer pour eviter les erreurs
                        this.resetChat();
                    }
                },

                resetChat() {
                    this.phone = '';
                    this.conversationId = null;
                    this.messages = [];
                    this.loading = false;
                    this.cleanupCall();
                    this.stopRtcPolling();
                    localStorage.removeItem('chat_phone');
                    localStorage.removeItem('chat_conversation_id');
                },

                destroy() {
                    this.stopPolling();
                    this.stopRtcPolling();
                    this.cleanupCall();
                },

                // ==========================================
                // WebRTC Audio Call
                // ==========================================

                getRtcConfig() {
                    return {
                        iceServers: [
                            { urls: 'stun:stun.l.google.com:19302' },
                            { urls: 'stun:stun1.l.google.com:19302' }
                        ]
                    };
                },

                async sendRtcSignal(action, payload = {}) {
                    if (!this.conversationId) return;
                    const data = JSON.stringify({ action, ...payload });
                    await fetch(`api.php?route=/chat/conversations/${this.conversationId}/messages`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            message: data,
                            sender_type: 'customer',
                            message_type: 'webrtc'
                        })
                    });
                },

                startRtcPolling() {
                    this.stopRtcPolling();
                    this.rtcPollInterval = setInterval(() => this.pollRtcMessages(), 1000);
                },

                stopRtcPolling() {
                    if (this.rtcPollInterval) {
                        clearInterval(this.rtcPollInterval);
                        this.rtcPollInterval = null;
                    }
                },

                async pollRtcMessages() {
                    if (!this.phone || !this.conversationId) return;
                    try {
                        const response = await fetch(
                            `api.php?route=/chat/messages/poll&phone=${encodeURIComponent(this.phone)}&after_id=${this.lastRtcMessageId}&type=webrtc`,
                            { cache: 'no-store' }
                        );
                        const data = await response.json();
                        const messages = data.data?.messages || [];
                        for (const msg of messages) {
                            this.lastRtcMessageId = Math.max(this.lastRtcMessageId, parseInt(msg.id));
                            // Ignorer les signaux de plus de 30 secondes
                            const msgAge = (Date.now() - new Date(msg.created_at).getTime()) / 1000;
                            if (msgAge > 30) continue;
                            // Only process messages from admin
                            if (msg.sender_type === 'admin') {
                                try {
                                    const signal = JSON.parse(msg.message);
                                    await this.handleRtcSignal(signal);
                                } catch (e) { /* ignore parse errors */ }
                            }
                        }
                    } catch (e) { /* ignore poll errors */ }
                },

                async handleRtcSignal(signal) {
                    switch (signal.action) {
                        case 'call_offer':
                            if (this.callState !== 'idle') break;
                            this.incomingOffer = signal.sdp;
                            this.callState = 'incoming';
                            // Ouvrir le chat automatiquement si ferme
                            if (!this.isOpen) this.isOpen = true;
                            break;
                        case 'call_answer':
                            if (this.peerConnection && this.callState === 'calling') {
                                await this.peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'answer', sdp: signal.sdp }));
                                this.pendingCandidates.forEach(c => {
                                    try { this.peerConnection.addIceCandidate(new RTCIceCandidate(c)); } catch (e) { }
                                });
                                this.pendingCandidates = [];
                                this.callState = 'connected';
                                this.startCallTimer();
                            }
                            break;
                        case 'ice_candidate':
                            if (this.peerConnection && this.peerConnection.remoteDescription) {
                                try { await this.peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate)); } catch (e) { }
                            } else {
                                this.pendingCandidates.push(signal.candidate);
                            }
                            break;
                        case 'call_reject':
                            this.cleanupCall();
                            break;
                        case 'call_end':
                            this.cleanupCall();
                            break;
                    }
                },

                async acceptCall() {
                    if (this.callState !== 'incoming' || !this.incomingOffer) return;

                    try {
                        this.localStream = await navigator.mediaDevices.getUserMedia({ audio: true, video: false });
                        this.peerConnection = new RTCPeerConnection(this.getRtcConfig());

                        this.localStream.getTracks().forEach(track => {
                            this.peerConnection.addTrack(track, this.localStream);
                        });

                        this.peerConnection.ontrack = (event) => {
                            const audio = document.getElementById('clientRemoteAudio');
                            if (audio) audio.srcObject = event.streams[0];
                        };

                        this.peerConnection.onicecandidate = (event) => {
                            if (event.candidate) {
                                this.sendRtcSignal('ice_candidate', { candidate: event.candidate.toJSON() });
                            }
                        };

                        this.peerConnection.onconnectionstatechange = () => {
                            if (['disconnected', 'failed', 'closed'].includes(this.peerConnection?.connectionState)) {
                                this.cleanupCall();
                            }
                        };

                        await this.peerConnection.setRemoteDescription(new RTCSessionDescription({ type: 'offer', sdp: this.incomingOffer }));

                        this.pendingCandidates.forEach(c => {
                            try { this.peerConnection.addIceCandidate(new RTCIceCandidate(c)); } catch (e) { }
                        });
                        this.pendingCandidates = [];

                        const answer = await this.peerConnection.createAnswer();
                        await this.peerConnection.setLocalDescription(answer);
                        await this.sendRtcSignal('call_answer', { sdp: answer.sdp });

                        this.callState = 'connected';
                        this.incomingOffer = null;
                        this.startCallTimer();
                    } catch (error) {
                        console.error('Erreur acceptation appel:', error);
                        this.cleanupCall();
                    }
                },

                async rejectCall() {
                    await this.sendRtcSignal('call_reject');
                    this.cleanupCall();
                },

                async endCall() {
                    if (this.callState !== 'idle') {
                        await this.sendRtcSignal('call_end');
                    }
                    this.cleanupCall();
                },

                toggleMute() {
                    if (this.localStream) {
                        this.isMuted = !this.isMuted;
                        this.localStream.getAudioTracks().forEach(track => {
                            track.enabled = !this.isMuted;
                        });
                    }
                },

                startCallTimer() {
                    this.callDuration = 0;
                    this.callDurationFormatted = '00:00';
                    if (this.callTimer) clearInterval(this.callTimer);
                    this.callTimer = setInterval(() => {
                        this.callDuration++;
                        const m = String(Math.floor(this.callDuration / 60)).padStart(2, '0');
                        const s = String(this.callDuration % 60).padStart(2, '0');
                        this.callDurationFormatted = `${m}:${s}`;
                    }, 1000);
                },

                cleanupCall() {
                    if (this.peerConnection) {
                        this.peerConnection.close();
                        this.peerConnection = null;
                    }
                    if (this.localStream) {
                        this.localStream.getTracks().forEach(t => t.stop());
                        this.localStream = null;
                    }
                    const audio = document.getElementById('clientRemoteAudio');
                    if (audio) audio.srcObject = null;
                    if (this.callTimer) { clearInterval(this.callTimer); this.callTimer = null; }
                    this.callState = 'idle';
                    this.isMuted = false;
                    this.callDuration = 0;
                    this.callDurationFormatted = '00:00';
                    this.incomingOffer = null;
                    this.pendingCandidates = [];
                }
            };
        }
    </script>
    <?php endif; ?>
</body>

</html>