<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('auth.register') ?> - <?= htmlspecialchars($appName ?? 'RADIUS Manager') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="registerForm()">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo et titre -->
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="h-10 w-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                </div>
                <h2 class="mt-6 text-3xl font-extrabold text-white">
                    <?= htmlspecialchars($appName ?? 'RADIUS Manager') ?>
                </h2>
                <p class="mt-2 text-sm text-gray-400">
                    <?= __('auth.register_subtitle') ?>
                </p>
            </div>

            <!-- Formulaire d'inscription -->
            <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl p-8">
                <!-- Message de succès -->
                <div x-show="success" x-cloak class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-600" x-text="success"></p>
                </div>

                <!-- Message d'erreur -->
                <div x-show="error" x-cloak class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>

                <form @submit.prevent="register()" class="space-y-5" x-show="!success">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="username" class="block text-sm font-medium text-gray-700"><?= __('auth.username') ?> *</label>
                            <input id="username" type="text" required x-model="form.username"
                                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="mon_identifiant">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="full_name" class="block text-sm font-medium text-gray-700"><?= __('auth.full_name') ?> *</label>
                            <input id="full_name" type="text" required x-model="form.full_name"
                                   class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="Jean Dupont">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700"><?= __('auth.email') ?> *</label>
                        <input id="email" type="email" required x-model="form.email"
                               class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="email@exemple.com">
                    </div>

                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700"><?= __('auth.phone') ?></label>
                        <input id="phone" type="tel" x-model="form.phone"
                               class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="+229 XX XX XX XX">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700"><?= __('auth.password_min_hint') ?></label>
                        <input id="password" type="password" required minlength="8" x-model="form.password"
                               class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="••••••••">
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700"><?= __('auth.confirm_password') ?> *</label>
                        <input id="password_confirm" type="password" required minlength="8" x-model="form.password_confirm"
                               class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               placeholder="••••••••">
                    </div>

                    <div>
                        <button type="submit" :disabled="loading"
                                class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!loading"><?= __('auth.create_my_account') ?></span>
                            <span x-show="loading" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <?= __('auth.signing_up') ?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Lien connexion -->
            <p class="text-center text-sm text-gray-400">
                <?= __('auth.have_account') ?>
                <a href="login.php" class="text-blue-400 hover:text-blue-300 font-medium">
                    <?= __('auth.sign_in') ?>
                </a>
            </p>
        </div>
    </div>

    <script>
        const __translations = <?= json_encode(
            file_exists(__DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'fr') . '.php')
                ? require __DIR__ . '/../../lang/' . ($_SESSION['lang'] ?? 'fr') . '.php'
                : [],
            JSON_UNESCAPED_UNICODE
        ) ?>;
        function __(key, replace = {}) {
            let text = __translations[key] ?? key;
            for (const [k, v] of Object.entries(replace)) {
                text = text.replaceAll(':' + k, v);
            }
            return text;
        }
    </script>

    <script>
        function registerForm() {
            return {
                form: {
                    username: '',
                    full_name: '',
                    email: '',
                    phone: '',
                    password: '',
                    password_confirm: ''
                },
                loading: false,
                error: null,
                success: null,

                async register() {
                    this.loading = true;
                    this.error = null;
                    this.success = null;

                    if (this.form.password !== this.form.password_confirm) {
                        this.error = __('auth.password_mismatch');
                        this.loading = false;
                        return;
                    }

                    if (this.form.password.length < 8) {
                        this.error = __('auth.password_min_length');
                        this.loading = false;
                        return;
                    }

                    try {
                        const response = await fetch('api.php?route=/auth/register', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.success = data.message || __('auth.registration_success_redirect');
                            if (!data.data?.requires_verification) {
                                setTimeout(() => {
                                    window.location.href = 'login.php';
                                }, 2000);
                            }
                        } else {
                            this.error = data.message || __('auth.registration_error');
                        }
                    } catch (error) {
                        this.error = __('auth.server_error');
                        console.error('Register error:', error);
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
