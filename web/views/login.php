<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('auth.login') ?> - <?= htmlspecialchars($appName ?? 'RADIUS Manager') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" x-data="loginForm()">
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
                    <span x-show="!show2fa"><?= __('auth.login_subtitle') ?></span>
                    <span x-show="show2fa" x-cloak><?= __('auth.2fa_subtitle') ?></span>
                </p>
            </div>

            <!-- Formulaire de connexion -->
            <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl p-8">
                <?php if ($error === 'unauthorized'): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?= __('auth.unauthorized_login') ?></p>
                </div>
                <?php endif; ?>
                <?php if ($error === 'forbidden'): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600"><?= __('auth.forbidden') ?></p>
                </div>
                <?php endif; ?>

                <!-- Message d'erreur -->
                <div x-show="error" x-cloak class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600" x-text="error"></p>
                </div>

                <!-- Email verification needed -->
                <div x-show="needsVerification" x-cloak class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <div>
                            <p class="text-sm text-amber-700" x-text="error"></p>
                            <button type="button" @click="resendVerification()" :disabled="resending"
                                class="mt-2 text-sm font-medium text-amber-700 underline hover:text-amber-800 disabled:opacity-50">
                                <span x-show="!resending"><?= __('email.resend_link') ?? 'Renvoyer le lien de vérification' ?></span>
                                <span x-show="resending"><?= __('common.loading') ?? 'Chargement...' ?></span>
                            </button>
                            <p x-show="resendMessage" class="mt-1 text-xs text-green-600" x-text="resendMessage"></p>
                        </div>
                    </div>
                </div>

                <!-- Step 1: Username + Password -->
                <form x-show="!show2fa && !showForgotPassword" @submit.prevent="login()" class="space-y-6">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">
                            <?= __('auth.username_or_email') ?>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input id="username" name="username" type="text" autocomplete="username" required
                                   x-model="form.username"
                                   class="appearance-none block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="admin">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            <?= __('auth.password') ?>
                        </label>
                        <div class="mt-1 relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input id="password" name="password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
                                   x-model="form.password"
                                   class="appearance-none block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
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

                    <div class="flex items-center justify-end">
                        <a href="#" @click.prevent="showForgotPassword = true; error = null; needsVerification = false"
                            class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            <?= __('auth.forgot_password') ?>
                        </a>
                    </div>

                    <div>
                        <button type="submit" :disabled="loading"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!loading"><?= __('auth.sign_in') ?></span>
                            <span x-show="loading" x-cloak class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <?= __('auth.signing_in') ?>
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Forgot Password Form -->
                <div x-show="showForgotPassword && !show2fa" x-cloak class="space-y-6">
                    <div class="text-center">
                        <div class="mx-auto h-14 w-14 bg-red-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="h-7 w-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"><?= __('email.forgot_password_title') ?></h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= __('email.forgot_password_desc') ?></p>
                    </div>

                    <!-- Success message -->
                    <div x-show="forgotMessage" x-cloak class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-700" x-text="forgotMessage"></p>
                    </div>

                    <!-- Error message -->
                    <div x-show="forgotError" x-cloak class="p-4 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-600" x-text="forgotError"></p>
                    </div>

                    <form @submit.prevent="submitForgotPassword()">
                        <div class="mb-4">
                            <label for="forgot_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('common.email') ?>
                            </label>
                            <input id="forgot_email" type="email" x-model="forgotEmail" required
                                class="w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                placeholder="admin@example.com">
                        </div>

                        <div class="flex gap-3">
                            <button type="button"
                                @click="showForgotPassword = false; forgotMessage = ''; forgotError = ''; forgotEmail = ''"
                                class="flex-1 py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                <?= __('common.back') ?>
                            </button>
                            <button type="submit" :disabled="forgotLoading"
                                class="flex-1 py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                <span x-show="!forgotLoading"><?= __('email.forgot_password_submit') ?></span>
                                <span x-show="forgotLoading" x-cloak><?= __('email.forgot_password_sending') ?></span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Step 2: 2FA Code -->
                <form x-show="show2fa" x-cloak @submit.prevent="verify2fa()" class="space-y-6">
                    <!-- Shield icon -->
                    <div class="text-center">
                        <div class="mx-auto h-14 w-14 bg-amber-100 rounded-full flex items-center justify-center mb-4">
                            <svg class="h-7 w-7 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900"><?= __('auth.2fa_title') ?></h3>
                        <p class="mt-1 text-sm text-gray-500"><?= __('auth.2fa_enter_code') ?></p>
                    </div>

                    <div>
                        <label for="totp_code" class="block text-sm font-medium text-gray-700">
                            <?= __('auth.2fa_code_label') ?>
                        </label>
                        <div class="mt-1">
                            <input id="totp_code" type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code" required
                                   x-model="totpCode"
                                   x-ref="totpInput"
                                   @input="if(totpCode.length === 6) $nextTick(() => verify2fa())"
                                   class="appearance-none block w-full px-4 py-3 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-2xl tracking-[0.5em] font-mono"
                                   placeholder="000000">
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="cancelTwoFactor()"
                                class="flex-1 py-3 px-4 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                            <?= __('common.back') ?>
                        </button>
                        <button type="submit" :disabled="loading || totpCode.length !== 6"
                                class="flex-1 py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <span x-show="!loading"><?= __('auth.2fa_verify') ?></span>
                            <span x-show="loading" x-cloak class="flex items-center justify-center">
                                <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Inscription -->
            <p x-show="!show2fa && !showForgotPassword" class="text-center text-sm text-gray-400">
                <?= __('auth.no_account') ?>
                <a href="register.php" class="text-blue-400 hover:text-blue-300 font-medium">
                    <?= __('auth.create_admin_account') ?>
                </a>
            </p>

            <!-- Info -->
            <p class="text-center text-xs text-gray-500">
                <?= __('auth.system_description') ?>
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
        function loginForm() {
            return {
                form: {
                    username: '',
                    password: ''
                },
                showPassword: false,
                loading: false,
                error: null,
                show2fa: false,
                tempToken: null,
                totpCode: '',
                needsVerification: false,
                verificationEmail: '',
                resending: false,
                resendMessage: '',
                showForgotPassword: false,
                forgotEmail: '',
                forgotLoading: false,
                forgotMessage: '',
                forgotError: '',

                async login() {
                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch('api.php?route=/auth/login', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });

                        const data = await response.json();

                        if (data.success) {
                            if (data.data && data.data.requires_2fa) {
                                // Show 2FA form
                                this.tempToken = data.data.temp_token;
                                this.show2fa = true;
                                this.totpCode = '';
                                this.$nextTick(() => {
                                    if (this.$refs.totpInput) this.$refs.totpInput.focus();
                                });
                            } else {
                                // Login complete
                                localStorage.setItem('user', JSON.stringify(data.data));
                                window.location.href = 'index.php';
                            }
                        } else {
                            if (data.needs_verification) {
                                this.needsVerification = true;
                                this.verificationEmail = data.email || '';
                            }
                            this.error = data.message || __('auth.login_error');
                        }
                    } catch (error) {
                        this.error = __('auth.server_error');
                        console.error('Login error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async resendVerification() {
                    if (!this.verificationEmail) return;
                    this.resending = true;
                    this.resendMessage = '';
                    try {
                        const response = await fetch('api.php?route=/auth/resend-verification', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: this.verificationEmail })
                        });
                        const data = await response.json();
                        this.resendMessage = data.message;
                    } catch (e) {
                        this.resendMessage = __('auth.server_error');
                    }
                    this.resending = false;
                },

                async submitForgotPassword() {
                    if (!this.forgotEmail) return;
                    this.forgotLoading = true;
                    this.forgotError = '';
                    this.forgotMessage = '';
                    try {
                        const response = await fetch('api.php?route=/auth/forgot-password', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ email: this.forgotEmail })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.forgotMessage = data.message;
                        } else {
                            this.forgotError = data.message;
                        }
                    } catch (e) {
                        this.forgotError = __('auth.server_error');
                    }
                    this.forgotLoading = false;
                },

                async verify2fa() {
                    if (this.totpCode.length !== 6) return;
                    this.loading = true;
                    this.error = null;

                    try {
                        const response = await fetch('api.php?route=/auth/verify-2fa', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                temp_token: this.tempToken,
                                code: this.totpCode
                            })
                        });

                        const data = await response.json();

                        if (data.success) {
                            localStorage.setItem('user', JSON.stringify(data.data));
                            window.location.href = 'index.php';
                        } else {
                            this.error = data.message || __('auth.2fa_invalid_code');
                            this.totpCode = '';
                            if (this.$refs.totpInput) this.$refs.totpInput.focus();
                        }
                    } catch (error) {
                        this.error = __('auth.server_error');
                        console.error('2FA verify error:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                cancelTwoFactor() {
                    this.show2fa = false;
                    this.tempToken = null;
                    this.totpCode = '';
                    this.error = null;
                }
            };
        }
    </script>
</body>
</html>
