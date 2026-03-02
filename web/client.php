<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), ['fr', 'en'])) {
    $_SESSION['lang'] = strtolower($_GET['lang']);
}
/**
 * Portail Client PPPoE
 * Dashboard avec tabs : Compte, Factures, Transactions, Offres, Trafic
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();
}
catch (Exception $e) {
    die('Database connection failed');
}

$adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : null;
$cancelled = isset($_GET['cancelled']);
$currency = $config['currency'] ?? 'XAF';

// Charger infos entreprise
$companyName = 'RADIUS Manager';
$companyPhone = '';
if ($adminId) {
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM billing_settings WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        while ($row = $stmt->fetch()) {
            if ($row['setting_key'] === 'company_name' && $row['setting_value'])
                $companyName = $row['setting_value'];
            if ($row['setting_key'] === 'company_phone' && $row['setting_value'])
                $companyPhone = $row['setting_value'];
        }
    }
    catch (Exception $e) {
    }

    if ($companyName === 'RADIUS Manager') {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE admin_id = ? AND setting_key = 'app_name'");
            $stmt->execute([$adminId]);
            $val = $stmt->fetchColumn();
            if ($val)
                $companyName = $val;
        }
        catch (Exception $e) {
        }
    }
}


// Langue
$lang = $_SESSION['lang'] ?? 'fr';
function t(string $key, string $default = ''): string
{
    return __($key);
}

?>
<!DOCTYPE html>
<html lang="<?= $lang?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= t('client_portal.login_title', 'Espace Client')?> -
        <?= htmlspecialchars($companyName)?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Inter', system-ui, sans-serif;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">

    <div x-data="clientPortal()" x-init="init()">

        <!-- Header -->
        <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
            <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <i class="fas fa-wifi text-white text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-bold text-gray-800">
                            <?= htmlspecialchars($companyName)?>
                        </h1>
                        <p class="text-xs text-gray-500"
                            x-text="account?.user?.customer_name || account?.user?.username || ''"></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">

                    <!-- Language Selector -->
                    <div class="relative" x-data="{ openLang: false }">
                        <button @click="openLang = !openLang" @click.away="openLang = false"
                            class="flex items-center gap-2 text-gray-400 hover:text-indigo-600 transition-colors">
                            <i class="fas fa-globe text-sm"></i>
                            <span class="text-xs font-medium uppercase">
                                <?= $_SESSION['lang'] ?? 'fr'?>
                            </span>
                            <i class="fas fa-chevron-down text-[10px]"></i>
                        </button>

                        <div x-show="openLang" x-cloak x-transition.opacity.duration.200ms
                            class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                            <a href="?lang=fr<?= isset($_GET['admin']) ? '&admin=' . (int)$_GET['admin'] : ''?>"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 <?=($_SESSION['lang'] ?? 'fr') === 'fr' ? 'bg-indigo-50 text-indigo-600 font-medium' : ''?>">
                                <span class="mr-2">🇫🇷</span> Français
                            </a>
                            <a href="?lang=en<?= isset($_GET['admin']) ? '&admin=' . (int)$_GET['admin'] : ''?>"
                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-indigo-600 <?=($_SESSION['lang'] ?? '') === 'en' ? 'bg-indigo-50 text-indigo-600 font-medium' : ''?>">
                                <span class="mr-2">🇬🇧</span> English
                            </a>
                        </div>
                    </div>

                    <?php if ($companyPhone): ?>
                    <a href="tel:<?= htmlspecialchars($companyPhone)?>"
                        class="text-gray-400 hover:text-indigo-600 transition-colors"
                        title="<?= htmlspecialchars($companyPhone)?>">
                        <i class="fas fa-phone text-sm"></i>
                    </a>
                    <?php
endif; ?>
                    <button @click="logout()" class="text-gray-400 hover:text-red-600 transition-colors"
                        title="<?= t('client_portal.logout', 'Déconnexion')?>">
                        <i class="fas fa-sign-out-alt text-sm"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-5xl mx-auto px-4 flex gap-1 overflow-x-auto">
                <template x-for="tab in visibleTabs" :key="tab.id">
                    <button @click="activeTab = tab.id"
                        class="px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors" :class="activeTab === tab.id
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'">
                        <i :class="tab.icon + ' mr-1.5'"></i>
                        <span x-text="tab.label"></span>
                    </button>
                </template>
            </div>
        </nav>

        <?php if ($cancelled): ?>
        <div class="max-w-5xl mx-auto px-4 mt-4">
            <div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded-lg text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Paiement annulé. Vous pouvez réessayer quand vous voulez.
            </div>
        </div>
        <?php
endif; ?>

        <!-- Content -->
        <main class="max-w-5xl mx-auto px-4 py-6">

            <!-- Loading -->
            <div x-show="loading" class="flex justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>

            <!-- =============== TAB COMPTE =============== -->
            <div x-show="activeTab === 'account' && !loading" x-cloak>

                <div class="mb-6">
                    <h1 class="text-2xl md:text-3xl font-extrabold text-gray-800">
                        <span x-text="getGreeting()"></span> <span
                            x-text="account?.user?.customer_name || account?.user?.username"></span> ! <span
                            x-text="getGreetingEmoji()"></span>
                    </h1>
                    <p class="text-gray-500 mt-1">
                        <?= t('client_portal.welcome_subtitle', 'Bienvenue sur votre espace client')?>
                    </p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Infos personnelles -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-user-circle mr-2 text-indigo-500"></i>
                            <?= t('client_portal.account_info', 'Informations du compte')?>
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    <?= t('client_portal.username', 'Utilisateur')?>
                                </span>
                                <span class="font-medium text-gray-800" x-text="account?.user?.username"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Nom</span>
                                <span class="font-medium text-gray-800"
                                    x-text="account?.user?.customer_name || '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    <?= t('common.phone', 'Téléphone')?>
                                </span>
                                <span class="font-medium text-gray-800"
                                    x-text="account?.user?.customer_phone || '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-gray-800"
                                    x-text="account?.user?.customer_email || '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    <?= t('client_portal.created_at', 'Créé le')?>
                                </span>
                                <span class="font-medium text-gray-800"
                                    x-text="formatDate(account?.user?.created_at)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Abonnement -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-signal mr-2 text-indigo-500"></i>
                            <?= t('client_portal.current_plan', 'Offre actuelle')?>
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Offre</span>
                                <span class="font-semibold text-indigo-600"
                                    x-text="account?.user?.profile_name || '-'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    <?= t('client_portal.speed_download', 'Download')?>
                                </span>
                                <span class="font-medium text-gray-800"
                                    x-text="formatSpeed(account?.user?.download_speed)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">
                                    <?= t('client_portal.speed_upload', 'Upload')?>
                                </span>
                                <span class="font-medium text-gray-800"
                                    x-text="formatSpeed(account?.user?.upload_speed)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Prix</span>
                                <span class="font-semibold text-gray-800"
                                    x-text="formatPrice(account?.user?.profile_price)"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-500">
                                    <?= t('client_portal.account_status', 'Statut')?>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="statusClass(account?.user?.status)"
                                    x-text="statusLabel(account?.user?.status)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Validité -->
                    <div class="bg-white rounded-xl border border-gray-200 p-5 md:col-span-2">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-indigo-500"></i>
                            Validité
                        </h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm mb-4">
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <p class="text-gray-500 text-xs mb-1">
                                    <?= t('client_portal.valid_from', 'Début')?>
                                </p>
                                <p class="font-semibold text-gray-800" x-text="formatDate(account?.user?.valid_from)">
                                </p>
                            </div>
                            <div class="text-center p-3 bg-gray-50 rounded-lg">
                                <p class="text-gray-500 text-xs mb-1">
                                    <?= t('client_portal.valid_until', 'Fin')?>
                                </p>
                                <p class="font-semibold text-gray-800" x-text="formatDate(account?.user?.valid_until)">
                                </p>
                            </div>
                            <div class="text-center p-3 rounded-lg"
                                :class="account?.days_remaining > 7 ? 'bg-emerald-50' : account?.days_remaining > 0 ? 'bg-amber-50' : 'bg-red-50'">
                                <p class="text-gray-500 text-xs mb-1">
                                    <?= t('client_portal.days_remaining', 'Jours restants')?>
                                </p>
                                <p class="font-bold text-lg"
                                    :class="account?.days_remaining > 7 ? 'text-emerald-600' : account?.days_remaining > 0 ? 'text-amber-600' : 'text-red-600'"
                                    x-text="account?.days_remaining ?? 0"></p>
                            </div>
                        </div>
                        <!-- Barre de progression -->
                        <div class="w-full bg-gray-200 rounded-full h-2.5" x-show="account?.user?.validity_days">
                            <div class="h-2.5 rounded-full transition-all"
                                :class="account?.days_remaining > 7 ? 'bg-emerald-500' : account?.days_remaining > 0 ? 'bg-amber-500' : 'bg-red-500'"
                                :style="'width: ' + Math.min(100, Math.max(0, ((account?.days_remaining || 0) / (account?.user?.validity_days || 30)) * 100)) + '%'">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- =============== TAB FACTURES =============== -->
            <div x-show="activeTab === 'invoices' && !loading" x-cloak>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.invoice_number', 'N° Facture')?>
                                    </th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.invoice_date', 'Date')?>
                                    </th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.invoice_amount', 'Montant')?>
                                    </th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Payé
                                    </th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.invoice_status', 'Statut')?>
                                    </th>
                                    <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="inv in invoices" :key="inv.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-gray-800" x-text="inv.invoice_number">
                                        </td>
                                        <td class="px-4 py-3 text-gray-600" x-text="formatDate(inv.created_at)"></td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-800"
                                            x-text="formatPrice(inv.total_amount)"></td>
                                        <td class="px-4 py-3 text-right text-gray-600"
                                            x-text="formatPrice(inv.paid_amount)"></td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="invoiceStatusClass(inv.status)"
                                                x-text="invoiceStatusLabel(inv.status)"></span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button x-show="inv.status !== 'paid' && inv.status !== 'cancelled'"
                                                @click="payInvoice(inv)"
                                                class="px-3 py-1 text-xs font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                                                <i class="fas fa-credit-card mr-1"></i>
                                                <?= t('client_portal.pay_btn', 'Payer')?>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="invoices.length === 0">
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-file-invoice text-3xl mb-2"></i>
                                        <p>
                                            <?= t('client_portal.no_invoices', 'Aucune facture')?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============== TAB TRANSACTIONS =============== -->
            <div x-show="activeTab === 'transactions' && !loading" x-cloak>
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.tx_date', 'Date')?>
                                    </th>
                                    <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.tx_amount', 'Montant')?>
                                    </th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.tx_method', 'Méthode')?>
                                    </th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        <?= t('client_portal.tx_reference', 'Référence')?>
                                    </th>
                                    <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">
                                        Facture</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(tx, i) in transactions" :key="i">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-gray-600" x-text="formatDateTime(tx.payment_date)">
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-600"
                                            x-text="'+' + formatPrice(tx.amount)"></td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700"
                                                x-text="tx.payment_method"></span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 text-xs"
                                            x-text="tx.payment_reference || tx.transaction_id || '-'"></td>
                                        <td class="px-4 py-3 text-gray-600 text-xs" x-text="tx.invoice_number || '-'">
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="transactions.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-receipt text-3xl mb-2"></i>
                                        <p>
                                            <?= t('client_portal.no_transactions', 'Aucune transaction')?>
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- =============== TAB OFFRES =============== -->
            <div x-show="activeTab === 'plans' && !loading" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="plan in plans" :key="plan.id">
                        <div class="bg-white rounded-xl border-2 transition-all"
                            :class="plan.id == currentProfileId ? 'border-indigo-500 shadow-lg shadow-indigo-100' : 'border-gray-200 hover:border-gray-300'">
                            <div class="p-5">
                                <!-- Badge actuel -->
                                <div x-show="plan.id == currentProfileId" class="mb-3">
                                    <span
                                        class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        <?= t('client_portal.current_offer', 'Offre actuelle')?>
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-800" x-text="plan.name"></h3>
                                <p class="text-xs text-gray-500 mt-1" x-text="plan.description || ''"></p>

                                <!-- Prix -->
                                <div class="mt-4 mb-4">
                                    <span class="text-2xl font-bold text-gray-800"
                                        x-text="formatPrice(plan.price)"></span>
                                    <span class="text-sm text-gray-500"
                                        x-text="'/ ' + plan.validity_days + ' jours'"></span>
                                </div>

                                <!-- Specs -->
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-download w-5 text-indigo-400"></i>
                                        <span x-text="formatSpeed(plan.download_speed)"></span>
                                    </div>
                                    <div class="flex items-center text-gray-600">
                                        <i class="fas fa-upload w-5 text-indigo-400"></i>
                                        <span x-text="formatSpeed(plan.upload_speed)"></span>
                                    </div>
                                    <div class="flex items-center text-gray-600" x-show="plan.data_limit > 0">
                                        <i class="fas fa-database w-5 text-indigo-400"></i>
                                        <span x-text="formatBytes(plan.data_limit)"></span>
                                    </div>
                                    <div class="flex items-center text-gray-600"
                                        x-show="!plan.data_limit || plan.data_limit == 0">
                                        <i class="fas fa-infinity w-5 text-indigo-400"></i>
                                        <span>Illimité</span>
                                    </div>
                                </div>

                                <!-- Bouton -->
                                <button x-show="plan.id != currentProfileId" @click="selectPlan(plan)"
                                    class="mt-4 w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                                    <i class="fas fa-exchange-alt mr-1"></i>
                                    <?= t('client_portal.choose_plan', 'Choisir cette offre')?>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- =============== TAB TRAFIC =============== -->
            <div x-show="activeTab === 'traffic' && !loading" x-cloak>
                <!-- Cards résumé -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                        <i class="fas fa-chart-bar text-indigo-400 text-2xl mb-2"></i>
                        <p class="text-xs text-gray-500 mb-1">
                            <?= t('client_portal.data_used', 'Données consommées')?>
                        </p>
                        <p class="text-xl font-bold text-gray-800" x-text="formatBytes(trafficStats?.data_used || 0)">
                        </p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                        <i class="fas fa-clock text-emerald-400 text-2xl mb-2"></i>
                        <p class="text-xs text-gray-500 mb-1">
                            <?= t('client_portal.time_connected', 'Temps de connexion')?>
                        </p>
                        <p class="text-xl font-bold text-gray-800" x-text="formatTime(trafficStats?.time_used || 0)">
                        </p>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-200 p-5 text-center">
                        <i class="fas fa-sign-in-alt text-amber-400 text-2xl mb-2"></i>
                        <p class="text-xs text-gray-500 mb-1">Dernière connexion</p>
                        <p class="text-sm font-medium text-gray-800"
                            x-text="formatDateTime(trafficStats?.last_login) || '-'"></p>
                    </div>
                </div>

                <!-- Sessions récentes -->
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-gray-200 bg-gray-50">
                        <h3 class="text-sm font-semibold text-gray-700">
                            <i class="fas fa-history mr-1"></i>
                            <?= t('client_portal.recent_sessions', 'Sessions récentes')?>
                        </h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Début</th>
                                    <th class="text-left px-4 py-2 text-xs font-semibold text-gray-500">Fin</th>
                                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500">Durée</th>
                                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500">
                                        <i class="fas fa-download text-blue-400"></i> Download
                                    </th>
                                    <th class="text-right px-4 py-2 text-xs font-semibold text-gray-500">
                                        <i class="fas fa-upload text-green-400"></i> Upload
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <template x-for="(s, i) in (trafficStats?.sessions || [])" :key="i">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-gray-600" x-text="formatDateTime(s.start_time)"></td>
                                        <td class="px-4 py-2 text-gray-600"
                                            x-text="s.stop_time ? formatDateTime(s.stop_time) : '🟢 En cours'"></td>
                                        <td class="px-4 py-2 text-right text-gray-800"
                                            x-text="formatTime(s.session_time || 0)"></td>
                                        <td class="px-4 py-2 text-right text-blue-600 font-medium"
                                            x-text="formatBytes(s.output_octets || 0)"></td>
                                        <td class="px-4 py-2 text-right text-green-600 font-medium"
                                            x-text="formatBytes(s.input_octets || 0)"></td>
                                    </tr>
                                </template>
                                <tr x-show="!trafficStats?.sessions?.length">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-400">
                                        <i class="fas fa-chart-line text-3xl mb-2"></i>
                                        <p>Aucune session</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>

        <!-- =============== MODAL PAIEMENT =============== -->
        <div x-show="showPayModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="showPayModal = false">
            <div class="fixed inset-0 bg-black/50" @click="showPayModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 max-h-[90vh] overflow-y-auto">
                <button @click="showPayModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>

                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-credit-card mr-2 text-indigo-500"></i>
                    Paiement en ligne
                </h3>

                <!-- Résumé -->
                <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm">
                    <template x-if="payContext.type === 'invoice'">
                        <div>
                            <p class="text-gray-500">Facture: <span class="font-medium text-gray-800"
                                    x-text="payContext.invoice?.invoice_number"></span></p>
                            <p class="text-gray-500 mt-1">Montant: <span class="font-bold text-indigo-600"
                                    x-text="formatPrice(payContext.amount)"></span></p>
                        </div>
                    </template>
                    <template x-if="payContext.type === 'plan_change'">
                        <div>
                            <p class="text-gray-500">Changement d'offre: <span class="font-medium text-gray-800"
                                    x-text="payContext.plan?.name"></span></p>
                            <p class="text-gray-500 mt-1">Montant: <span class="font-bold text-indigo-600"
                                    x-text="formatPrice(payContext.amount)"></span></p>
                        </div>
                    </template>
                </div>

                <!-- Gateways -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Moyen de paiement</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="gw in availableGateways" :key="gw.code">
                            <button @click="payForm.gateway = gw.code; payForm.is_platform = gw.is_platform || false"
                                class="p-3 border-2 rounded-lg text-center text-sm font-medium transition-all"
                                :class="payForm.gateway === gw.code ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600'">
                                <span x-text="gw.name"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Opérateur + Téléphone (uniquement pour les gateways qui le requièrent) -->
                <template x-if="availableGateways.find(g => g.code === payForm.gateway && g.requires_phone)">
                    <div class="space-y-3 mb-4">
                        <template x-if="payForm.gateway === 'feexpay'">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pays</label>
                                    <select x-model="payForm.feexpayCountry" @change="payForm.operator = ''"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 text-sm bg-white">
                                        <option value="">-- Choisir le pays --</option>
                                        <option value="bj">Bénin</option>
                                        <option value="tg">Togo</option>
                                        <option value="ci">Côte d'Ivoire</option>
                                        <option value="cg">Congo Brazzaville</option>
                                    </select>
                                </div>
                                <div x-show="payForm.feexpayCountry">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Opérateur</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <template x-for="op in (payForm.feexpayCountry === 'bj' ? [{code:'mtn',label:'MTN'},{code:'moov',label:'Moov'}] : payForm.feexpayCountry === 'tg' ? [{code:'togocom_tg',label:'Togocom'},{code:'moov_tg',label:'Moov'}] : payForm.feexpayCountry === 'ci' ? [{code:'mtn_ci',label:'MTN'},{code:'moov_ci',label:'Moov'},{code:'orange_ci',label:'Orange'},{code:'wave_ci',label:'Wave'}] : payForm.feexpayCountry === 'cg' ? [{code:'mtn_cg',label:'MTN'}] : [])" :key="op.code">
                                            <button type="button" @click="payForm.operator = op.code"
                                                class="py-2.5 px-3 border-2 rounded-lg text-center text-sm font-medium transition-all"
                                                :class="payForm.operator === op.code ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600'"
                                                x-text="op.label"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Numéro de paiement</label>
                            <input type="tel" x-model="payForm.phone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 placeholder-gray-400 text-sm"
                                placeholder="Ex: 97000000">
                        </div>
                    </div>
                </template>

                <!-- Bouton payer -->
                <button @click="processPayment()" :disabled="processingPayment || !payForm.gateway || (availableGateways.find(g => g.code === payForm.gateway && g.requires_phone) && !payForm.phone) || (payForm.gateway === 'feexpay' && !payForm.operator)"
                    class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 text-sm">
                    <span x-show="!processingPayment">
                        <i class="fas fa-lock mr-1"></i>
                        Payer <span x-text="formatPrice(payContext.amount)"></span>
                    </span>
                    <span x-show="processingPayment">
                        <svg class="animate-spin h-5 w-5 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Traitement...
                    </span>
                </button>
            </div>
        </div>

        <!-- =============== MODAL CHANGEMENT OFFRE =============== -->
        <div x-show="showPlanModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="showPlanModal = false">
            <div class="fixed inset-0 bg-black/50" @click="showPlanModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
                <button @click="showPlanModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>

                <h3 class="text-lg font-bold text-gray-800 mb-4">
                    <i class="fas fa-exchange-alt mr-2 text-indigo-500"></i>
                    <?= t('client_portal.confirm_change', 'Confirmer le changement')?>
                </h3>

                <div class="bg-gray-50 rounded-lg p-4 mb-4 text-sm space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nouvelle offre</span>
                        <span class="font-semibold text-indigo-600" x-text="selectedPlan?.name"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Vitesse</span>
                        <span class="text-gray-800"
                            x-text="formatSpeed(selectedPlan?.download_speed) + ' / ' + formatSpeed(selectedPlan?.upload_speed)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Prix</span>
                        <span class="font-bold text-gray-800" x-text="formatPrice(selectedPlan?.price)"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Durée</span>
                        <span class="text-gray-800" x-text="(selectedPlan?.validity_days || 30) + ' jours'"></span>
                    </div>
                </div>

                <p class="text-xs text-gray-500 mb-4">
                    Une facture sera créée et vous devrez la payer en ligne pour activer le changement.
                </p>

                <div class="flex gap-3">
                    <button @click="showPlanModal = false"
                        class="flex-1 py-2.5 px-4 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 text-sm">
                        Annuler
                    </button>
                    <button @click="confirmPlanChange()" :disabled="changingPlan"
                        class="flex-1 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 text-sm">
                        <span x-show="!changingPlan">Confirmer</span>
                        <span x-show="changingPlan">
                            <svg class="animate-spin h-4 w-4 inline mr-1" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <div x-show="toast.show" x-cloak x-transition:enter="transform ease-out duration-300 transition"
            x-transition:enter-start="translate-y-2 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed bottom-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium text-white max-w-sm"
            :class="toast.type === 'success' ? 'bg-emerald-600' : toast.type === 'error' ? 'bg-red-600' : 'bg-indigo-600'">
            <span x-text="toast.message"></span>
        </div>

    </div>

    <script>
        function clientPortal() {
            const currency = '<?= $currency?>';
            const adminId = <?=(int)$adminId?>;

            return {
                // State
                loading: true,
                activeTab: 'account',
                account: null,
                invoices: [],
                transactions: [],
                plans: [],
                currentProfileId: 0,
                trafficStats: null,
                availableGateways: [],

                // Modals
                showPayModal: false,
                showPlanModal: false,
                selectedPlan: null,
                changingPlan: false,
                processingPayment: false,

                payContext: { type: '', invoice: null, plan: null, amount: 0 },
                payForm: { gateway: '', phone: '', operator: '', feexpayCountry: '', is_platform: false },

                // Toast
                toast: { show: false, message: '', type: 'success' },

                // Tabs visibles selon permissions
                get visibleTabs() {
                    const perms = this.account?.permissions || [];
                    const tabs = [];
                    if (perms.includes('client_view_account')) tabs.push({ id: 'account', label: '<?= t('client_portal.tab_account', 'Mon Compte')?>', icon: 'fas fa-user' });
                    if (perms.includes('client_view_invoices')) tabs.push({ id: 'invoices', label: '<?= t('client_portal.tab_invoices', 'Factures')?>', icon: 'fas fa-file-invoice' });
                    if (perms.includes('client_view_transactions')) tabs.push({ id: 'transactions', label: '<?= t('client_portal.tab_transactions', 'Transactions')?>', icon: 'fas fa-receipt' });
                    if (perms.includes('client_change_plan')) tabs.push({ id: 'plans', label: '<?= t('client_portal.tab_plans', 'Offres')?>', icon: 'fas fa-tags' });
                    if (perms.includes('client_view_traffic')) tabs.push({ id: 'traffic', label: '<?= t('client_portal.tab_traffic', 'Trafic')?>', icon: 'fas fa-chart-bar' });
                    // Fallback si aucune permission chargée
                    if (tabs.length === 0) return [
                        { id: 'account', label: '<?= t('client_portal.tab_account', 'Mon Compte')?>', icon: 'fas fa-user' },
                        { id: 'invoices', label: '<?= t('client_portal.tab_invoices', 'Factures')?>', icon: 'fas fa-file-invoice' },
                        { id: 'transactions', label: '<?= t('client_portal.tab_transactions', 'Transactions')?>', icon: 'fas fa-receipt' },
                        { id: 'plans', label: '<?= t('client_portal.tab_plans', 'Offres')?>', icon: 'fas fa-tags' },
                        { id: 'traffic', label: '<?= t('client_portal.tab_traffic', 'Trafic')?>', icon: 'fas fa-chart-bar' }
                    ];
                    return tabs;
                },

                getGreeting() {
                    const h = new Date().getHours();
                    if (h >= 5 && h < 12) return '<?= t('client_portal.greeting_morning', 'Bonjour')?>';
                    if (h >= 12 && h < 18) return '<?= t('client_portal.greeting_afternoon', 'Bon après - midi')?>';
                    return '<?= t('client_portal.greeting_evening', 'Bonsoir')?>';
                },

                getGreetingEmoji() {
                    const h = new Date().getHours();
                    if (h >= 5 && h < 7) return '🌅';
                    if (h >= 7 && h < 12) return '☀️';
                    if (h >= 12 && h < 17) return '🌤️';
                    if (h >= 17 && h < 20) return '🌇';
                    return '🌙';
                },

                getGreetingSubtext() {
                    const h = new Date().getHours();
                    if (h >= 5 && h < 12) return '<?= t('client_portal.greeting_subtext_morning', 'Bonne matinée!')?>';
                    if (h >= 12 && h < 18) return '<?= t('client_portal.greeting_subtext_afternoon', 'Bon après - midi!')?>';
                    return '<?= t('client_portal.greeting_subtext_evening', 'Bonne soirée!')?>';
                },

                async init() {
                    // Vérifier la session côté JS (localStorage)
                    const session = localStorage.getItem('client_session');
                    if (!session) {
                        window.location.href = 'client-login.php?admin=' + adminId;
                        return;
                    }
                    await this.loadAccount();
                    // Charger le reste en parallèle
                    this.loadInvoices();
                    this.loadTransactions();
                    this.loadPlans();
                    this.loadTraffic();
                },

                // API helper — envoie le session_id via header X-Client-Session
                async api(endpoint, options = {}) {
                    const session = localStorage.getItem('client_session');
                    if (!session) {
                        window.location.href = 'client-login.php?admin=' + adminId;
                        throw new Error('Non authentifié');
                    }
                    const res = await fetch('api.php?route=' + endpoint, {
                        ...options,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Client-Session': session,
                            ...(options.headers || {})
                        }
                    });
                    if (res.status === 401) {
                        localStorage.removeItem('client_session');
                        localStorage.removeItem('client_admin_id');
                        window.location.href = 'client-login.php?admin=' + adminId;
                        throw new Error('Session expirée');
                    }
                    const data = await res.json();
                    return data;
                },

                // Chargement données
                async loadAccount() {
                    try {
                        const data = await this.api('/client/account');
                        if (data.success) {
                            this.account = data.data;
                            // Set default phone for payments
                            this.payForm.phone = this.account?.user?.customer_phone || '';
                        }
                    } catch (e) { console.error(e); }
                    this.loading = false;
                },

                async loadInvoices() {
                    try {
                        const data = await this.api('/client/invoices');
                        if (data.success) this.invoices = data.data.invoices || [];
                    } catch (e) { }
                },

                async loadTransactions() {
                    try {
                        const data = await this.api('/client/transactions');
                        if (data.success) this.transactions = data.data.transactions || [];
                    } catch (e) { }
                },

                async loadPlans() {
                    try {
                        const data = await this.api('/client/plans');
                        if (data.success) {
                            this.plans = data.data.profiles || [];
                            this.currentProfileId = data.data.current_profile_id || 0;
                            this.availableGateways = data.data.gateways || [];
                        }
                    } catch (e) { }
                },

                async loadTraffic() {
                    try {
                        const data = await this.api('/client/traffic');
                        if (data.success) this.trafficStats = data.data;
                    } catch (e) { }
                },

                // Actions
                async logout() {
                    try { await this.api('/client/logout', { method: 'POST' }); } catch (e) { }
                    localStorage.removeItem('client_session');
                    localStorage.removeItem('client_admin_id');
                    window.location.href = 'client-login.php?admin=' + adminId;
                },

                payInvoice(invoice) {
                    this.payForm = { gateway: '', phone: '', operator: '', feexpayCountry: '', is_platform: false };
                    this.payContext = {
                        type: 'invoice',
                        invoice: invoice,
                        plan: null,
                        amount: parseFloat(invoice.total_amount) - parseFloat(invoice.paid_amount || 0),
                        invoiceId: invoice.id
                    };
                    this.showPayModal = true;
                },

                selectPlan(plan) {
                    this.selectedPlan = plan;
                    this.showPlanModal = true;
                },

                async confirmPlanChange() {
                    this.changingPlan = true;
                    try {
                        const data = await this.api('/client/change-plan', {
                            method: 'POST',
                            body: JSON.stringify({ profile_id: this.selectedPlan.id })
                        });
                        if (data.success) {
                            this.showPlanModal = false;
                            // Ouvrir le modal de paiement pour la facture créée
                            this.payContext = {
                                type: 'plan_change',
                                invoice: null,
                                plan: this.selectedPlan,
                                amount: data.data.amount,
                                invoiceId: data.data.invoice_id
                            };
                            this.availableGateways = data.data.gateways || this.availableGateways;
                            this.showPayModal = true;
                            this.showToast(data.message, 'success');
                            await this.loadInvoices();
                        } else {
                            this.showToast(data.error || 'Erreur', 'error');
                        }
                    } catch (e) {
                        this.showToast('Erreur: ' + e.message, 'error');
                    }
                    this.changingPlan = false;
                },

                async processPayment() {
                    if (!this.payForm.gateway) return;
                    this.processingPayment = true;
                    try {
                        const payload = {
                            invoice_id: this.payContext.invoiceId,
                            gateway_code: this.payForm.gateway,
                            payment_type: 'invoice',
                            customer_phone: this.payForm.phone,
                            operator: this.payForm.operator || '',
                            is_platform: this.payForm.is_platform || false
                        };
                        const data = await this.api('/client/pay', {
                            method: 'POST',
                            body: JSON.stringify(payload)
                        });
                        if (data.success && data.data.redirect_url) {
                            window.location.href = data.data.redirect_url;
                        } else {
                            this.showToast(data.error || 'Erreur paiement', 'error');
                        }
                    } catch (e) {
                        this.showToast('Erreur: ' + e.message, 'error');
                    }
                    this.processingPayment = false;
                },

                // Formatage
                formatPrice(amount) {
                    if (amount === null || amount === undefined) return '-';
                    return new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 0 }).format(amount) + ' ' + currency;
                },
                formatDate(date) {
                    if (!date) return '-';
                    return new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                },
                formatDateTime(date) {
                    if (!date) return '-';
                    return new Date(date).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                },
                formatSpeed(bps) {
                    if (!bps) return '-';
                    bps = parseInt(bps);
                    if (bps >= 1000000) return (bps / 1000000).toFixed(0) + ' Mbps';
                    if (bps >= 1000) return (bps / 1000).toFixed(0) + ' Kbps';
                    return bps + ' bps';
                },
                formatBytes(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    bytes = parseInt(bytes);
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(1024));
                    return (bytes / Math.pow(1024, i)).toFixed(i > 1 ? 2 : 0) + ' ' + sizes[i];
                },
                formatTime(seconds) {
                    if (!seconds) return '0s';
                    seconds = parseInt(seconds);
                    const d = Math.floor(seconds / 86400);
                    const h = Math.floor((seconds % 86400) / 3600);
                    const m = Math.floor((seconds % 3600) / 60);
                    if (d > 0) return d + 'j ' + h + 'h';
                    if (h > 0) return h + 'h ' + m + 'm';
                    return m + 'm';
                },

                // Statut
                statusClass(status) {
                    const classes = {
                        active: 'bg-emerald-100 text-emerald-700',
                        suspended: 'bg-red-100 text-red-700',
                        expired: 'bg-gray-100 text-gray-700',
                        disabled: 'bg-amber-100 text-amber-700'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-700';
                },
                statusLabel(status) {
                    const labels = { active: 'Actif', suspended: 'Suspendu', expired: 'Expiré', disabled: 'Désactivé' };
                    return labels[status] || status;
                },
                invoiceStatusClass(status) {
                    const classes = {
                        paid: 'bg-emerald-100 text-emerald-700',
                        pending: 'bg-amber-100 text-amber-700',
                        partial: 'bg-blue-100 text-blue-700',
                        overdue: 'bg-red-100 text-red-700',
                        cancelled: 'bg-gray-100 text-gray-600'
                    };
                    return classes[status] || 'bg-gray-100 text-gray-700';
                },
                invoiceStatusLabel(status) {
                    const labels = { paid: 'Payée', pending: 'En attente', partial: 'Partielle', overdue: 'En retard', cancelled: 'Annulée' };
                    return labels[status] || status;
                },

                // Toast
                showToast(message, type = 'success') {
                    this.toast = { show: true, message, type };
                    setTimeout(() => { this.toast.show = false; }, 4000);
                }
            };
        }
    </script>
</body>

</html>