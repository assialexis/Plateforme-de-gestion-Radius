<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_GET['lang']) && in_array(strtolower($_GET['lang']), ['fr', 'en'])) {
    $_SESSION['lang'] = strtolower($_GET['lang']);
}
/**
 * Page de connexion client PPPoE
 * Brandée avec les informations de l'admin (nom d'entreprise, téléphone)
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();
} catch (Exception $e) {
    die('Database connection failed');
}

$adminId = isset($_GET['admin']) ? (int)$_GET['admin'] : null;

// Vérifier que l'admin existe
if ($adminId) {
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND role IN ('admin', 'superadmin') AND is_active = 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    if (!$admin) {
        $adminId = null;
    }
}

// Si pas d'admin spécifié, prendre le premier
if (!$adminId) {
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'superadmin') AND is_active = 1 ORDER BY id LIMIT 1");
    $row = $stmt->fetch();
    if ($row) $adminId = (int)$row['id'];
}

// Charger les infos de l'entreprise
$companyName = 'RADIUS Manager';
$companyPhone = '';
$companyEmail = '';
$appName = 'RADIUS Manager';

if ($adminId) {
    // Depuis billing_settings
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM billing_settings WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        $billingSettings = [];
        while ($row = $stmt->fetch()) {
            $billingSettings[$row['setting_key']] = $row['setting_value'];
        }
        if (!empty($billingSettings['company_name'])) $companyName = $billingSettings['company_name'];
        if (!empty($billingSettings['company_phone'])) $companyPhone = $billingSettings['company_phone'];
        if (!empty($billingSettings['company_email'])) $companyEmail = $billingSettings['company_email'];
    } catch (Exception $e) {}

    // Fallback depuis settings
    if ($companyName === 'RADIUS Manager') {
        try {
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE admin_id = ? AND setting_key IN ('app_name', 'company_name', 'support_phone')");
            $stmt->execute([$adminId]);
            while ($row = $stmt->fetch()) {
                if ($row['setting_key'] === 'app_name' && $appName === 'RADIUS Manager') $appName = $row['setting_value'];
                if ($row['setting_key'] === 'company_name' && !empty($row['setting_value'])) $companyName = $row['setting_value'];
                if ($row['setting_key'] === 'support_phone' && !empty($row['setting_value'])) $companyPhone = $row['setting_value'];
            }
        } catch (Exception $e) {}
    }
}


// Langue
$lang = $_SESSION['lang'] ?? 'fr';
function t(string $key, string $default = ''): string {
    return __($key);
}

?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= t('client_portal.login_title', 'Espace Client') ?> - <?= htmlspecialchars($companyName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%); }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">

<div x-data="clientLogin()" class="w-full max-w-md">

    <!-- Language Selector -->
    <div class="absolute top-4 right-4" x-data="{ openLang: false }">
        <button @click="openLang = !openLang" @click.away="openLang = false" 
                class="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-3 py-1.5 rounded-full backdrop-blur-sm transition-all text-sm font-medium">
            <i class="fas fa-globe"></i>
            <span class="uppercase"><?= $_SESSION['lang'] ?? 'fr' ?></span>
        </button>
        
        <div x-show="openLang" x-cloak 
             x-transition.opacity.duration.200ms
             class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-lg py-1 z-50 overflow-hidden">
            <a href="?lang=fr<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                <span class="mr-2">🇫🇷</span> Français
            </a>
            <a href="?lang=en<?= isset($_GET['admin']) ? '&admin='.(int)$_GET['admin'] : '' ?>" 
               class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-600 <?= ($_SESSION['lang'] ?? '') === 'en' ? 'bg-indigo-50 text-indigo-600 font-medium' : '' ?>">
                <span class="mr-2">🇬🇧</span> English
            </a>
        </div>
    </div>

    <!-- Card -->
    <div class="glass-card rounded-2xl shadow-2xl p-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-600 text-white mb-4 shadow-lg">
                <i class="fas fa-wifi text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800"><?= htmlspecialchars($companyName) ?></h1>
            <?php if ($companyPhone): ?>
            <p class="text-sm text-gray-500 mt-1">
                <i class="fas fa-phone mr-1"></i>
                <a href="tel:<?= htmlspecialchars($companyPhone) ?>" class="hover:text-indigo-600"><?= htmlspecialchars($companyPhone) ?></a>
            </p>
            <?php endif; ?>
            <p class="text-gray-600 mt-3 text-sm"><?= t('client_portal.login_subtitle', 'Connectez-vous avec vos identifiants PPPoE') ?></p>
        </div>

        <!-- Error -->
        <div x-show="error" x-cloak
             class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span x-text="error"></span>
        </div>

        <!-- Form -->
        <form @submit.prevent="login()">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    <i class="fas fa-user mr-1 text-gray-400"></i>
                    <?= t('client_portal.username', 'Nom d\'utilisateur') ?>
                </label>
                <input type="text" x-model="form.username" required autocomplete="username"
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 placeholder-gray-400 transition-all"
                       placeholder="<?= t('client_portal.username', 'Nom d\'utilisateur') ?>">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    <i class="fas fa-lock mr-1 text-gray-400"></i>
                    <?= t('client_portal.password', 'Mot de passe') ?>
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" x-model="form.password" required autocomplete="current-password"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-gray-900 placeholder-gray-400 transition-all pr-12"
                           placeholder="<?= t('client_portal.password', 'Mot de passe') ?>">
                    <button type="button" @click="showPassword = !showPassword"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                    </button>
                </div>
            </div>

            <button type="submit" :disabled="loading"
                    class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center shadow-lg shadow-indigo-600/30">
                <template x-if="loading">
                    <svg class="animate-spin h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                </template>
                <span x-text="loading ? '<?= t('common.loading', 'Chargement...') ?>' : '<?= t('client_portal.login_btn', 'Se connecter') ?>'"></span>
            </button>
        </form>
    </div>

    <!-- Footer -->
    <p class="text-center text-white/60 text-xs mt-6">
        &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>
    </p>
</div>

<script>
function clientLogin() {
    return {
        form: { username: '', password: '' },
        showPassword: false,
        loading: false,
        error: null,
        adminId: <?= (int)$adminId ?>,

        async login() {
            if (!this.form.username.trim() || !this.form.password) {
                this.error = '<?= t('client_portal.login_error', 'Veuillez remplir tous les champs') ?>';
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const response = await fetch('api.php?route=/client/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        username: this.form.username.trim(),
                        password: this.form.password,
                        admin_id: this.adminId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Stocker le session_id en localStorage aussi (pour les appels API)
                    localStorage.setItem('client_session', data.data.session_id);
                    localStorage.setItem('client_admin_id', this.adminId);
                    window.location.href = 'client.php?admin=' + this.adminId;
                } else {
                    this.error = data.error || '<?= t('client_portal.login_error', 'Identifiants incorrects') ?>';
                }
            } catch (e) {
                this.error = '<?= t('common.error', 'Erreur de connexion') ?>';
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
</body>
</html>
