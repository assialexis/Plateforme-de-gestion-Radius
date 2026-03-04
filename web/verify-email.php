<?php
/**
 * Page de vérification d'email (publique, hors auth)
 */

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Auth/User.php';
require_once __DIR__ . '/../src/Auth/AuthService.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset=utf8mb4",
        $config['database']['username'],
        $config['database']['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die(__('auth.db_connection_error'));
}

$auth = new AuthService($pdo);
$appName = $config['app']['name'] ?? 'RADIUS Manager';

$token = $_GET['token'] ?? '';
$result = null;

if (!empty($token)) {
    $result = $auth->verifyEmail($token);
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('email.verify_page_title') ?? 'Vérification Email' ?> - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
        x-data="{ resending: false, resendResult: '', resendEmail: '' }">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-white">
                    <?= htmlspecialchars($appName) ?>
                </h2>
                <p class="mt-2 text-sm text-gray-400">
                    <?= __('email.verify_page_title') ?? 'Vérification Email' ?>
                </p>
            </div>

            <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl p-8">
                <?php if (empty($token)): ?>
                    <!-- No token provided -->
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                        <p class="text-gray-600 dark:text-gray-400 mb-4">
                            <?= __('email.no_token') ?? 'Aucun token de vérification fourni.' ?>
                        </p>
                    </div>

                <?php elseif ($result && $result['success']): ?>
                    <!-- Success -->
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            <?= __('email.verified_title') ?? 'Email vérifié !' ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            <?= $result['message'] ?>
                        </p>
                        <a href="login.php"
                            class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <?= __('email.go_to_login') ?? 'Se connecter' ?>
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Error -->
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            <?= __('email.verification_failed') ?? 'Vérification échouée' ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            <?= $result['message'] ?? __('email.invalid_token') ?>
                        </p>

                        <!-- Resend form -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                                <?= __('email.resend_prompt') ?? 'Entrez votre email pour recevoir un nouveau lien :' ?>
                            </p>
                            <div class="flex gap-2">
                                <input type="email" x-model="resendEmail" placeholder="votre@email.com"
                                    class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                                <button @click="resend()" :disabled="resending"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 whitespace-nowrap">
                                    <span x-show="!resending"><?= __('email.resend_btn') ?? 'Renvoyer' ?></span>
                                    <span x-show="resending">...</span>
                                </button>
                            </div>
                            <p x-show="resendResult" class="mt-2 text-sm text-green-600" x-text="resendResult"></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back to login link -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; <?= __('email.back_to_login') ?? 'Retour à la connexion' ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function resend() {
        return {
            async resend() {
                if (!this.resendEmail) return;
                this.resending = true;
                try {
                    const res = await fetch('api.php?route=/auth/resend-verification', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: this.resendEmail })
                    });
                    const data = await res.json();
                    this.resendResult = data.message;
                } catch (e) {
                    this.resendResult = 'Erreur';
                }
                this.resending = false;
            }
        };
    }
    </script>
</body>
</html>
