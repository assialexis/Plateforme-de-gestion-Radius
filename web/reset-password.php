<?php
/**
 * Page de réinitialisation de mot de passe (publique, hors auth)
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

$appName = $config['app']['name'] ?? 'RADIUS Manager';
$token = $_GET['token'] ?? '';

// Check if token is valid (just to show appropriate UI)
$tokenValid = false;
if (!empty($token)) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE password_reset_token = ? AND password_reset_expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenValid = (bool)$stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('email.reset_page_title') ?> - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8"
        x-data="resetPasswordForm()">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-red-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-white">
                    <?= htmlspecialchars($appName) ?>
                </h2>
                <p class="mt-2 text-sm text-gray-400">
                    <?= __('email.reset_page_title') ?>
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
                            <?= __('email.reset_invalid_token') ?>
                        </p>
                    </div>

                <?php elseif (!$tokenValid): ?>
                    <!-- Token expired/invalid -->
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            <?= __('email.reset_token_expired') ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-6">
                            <?= __('email.forgot_password_desc') ?>
                        </p>
                        <a href="login.php"
                            class="inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <?= __('auth.back_to_login') ?>
                        </a>
                    </div>

                <?php else: ?>
                    <!-- Valid token: show reset form -->
                    <!-- Success message -->
                    <div x-show="success" x-cloak class="text-center">
                        <svg class="mx-auto h-12 w-12 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-2">
                            <?= __('email.reset_success') ?>
                        </h3>
                        <a href="login.php"
                            class="mt-4 inline-flex items-center justify-center px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                            <?= __('email.go_to_login') ?>
                        </a>
                    </div>

                    <!-- Reset form -->
                    <form x-show="!success" @submit.prevent="submit()" class="space-y-5">
                        <!-- Error -->
                        <div x-show="error" x-cloak class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-600" x-text="error"></p>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.reset_new_password') ?>
                            </label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.password" required minlength="8"
                                    class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                                    placeholder="••••••••">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <svg x-show="!showPassword" class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.reset_confirm_password') ?>
                            </label>
                            <input type="password" x-model="form.password_confirm" required minlength="8"
                                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="••••••••">
                        </div>

                        <!-- Submit -->
                        <button type="submit" :disabled="loading"
                            class="w-full py-3 px-4 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 transition-colors">
                            <span x-show="!loading"><?= __('email.reset_submit') ?></span>
                            <span x-show="loading" x-cloak class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <?= __('email.reset_submitting') ?>
                            </span>
                        </button>
                    </form>
                <?php endif; ?>

                <!-- Back to login link -->
                <div class="mt-6 text-center">
                    <a href="login.php" class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        &larr; <?= __('auth.back_to_login') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    function resetPasswordForm() {
        return {
            form: {
                password: '',
                password_confirm: ''
            },
            loading: false,
            error: null,
            success: false,
            showPassword: false,

            async submit() {
                this.loading = true;
                this.error = null;

                if (this.form.password.length < 8) {
                    this.error = '<?= __js('auth.password_min_length') ?>';
                    this.loading = false;
                    return;
                }
                if (this.form.password !== this.form.password_confirm) {
                    this.error = '<?= __js('auth.password_mismatch') ?>';
                    this.loading = false;
                    return;
                }

                try {
                    const res = await fetch('api.php?route=/auth/reset-password', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            token: '<?= htmlspecialchars($token) ?>',
                            password: this.form.password,
                            password_confirm: this.form.password_confirm
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.success = true;
                    } else {
                        this.error = data.message;
                    }
                } catch (e) {
                    this.error = '<?= __js('auth.server_error') ?>';
                }
                this.loading = false;
            }
        };
    }
    </script>
</body>
</html>
