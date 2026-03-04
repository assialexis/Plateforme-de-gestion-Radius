<!-- Page Mon Compte -->
<div x-data="profilePage()" x-init="init()" class="space-y-6">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('account.title') ?></h1>
        </div>
    </div>

    <!-- Layout 2 colonnes -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Colonne principale (2/3) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Informations personnelles -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-9 w-9 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('account.personal_info') ?></h2>
                </div>

                <form @submit.prevent="saveProfile()">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.full_name') ?></label>
                            <input type="text" x-model="form.full_name"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.email') ?></label>
                            <input type="email" x-model="form.email"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.phone') ?></label>
                            <input type="tel" x-model="form.phone"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" :disabled="saving"
                                class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium text-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <?= __('account.save_profile') ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Changer le mot de passe -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="h-9 w-9 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('account.change_password') ?></h2>
                </div>

                <form @submit.prevent="changePassword()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.current_password') ?></label>
                            <input type="password" x-model="passwordForm.current" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.new_password') ?></label>
                                <input type="password" x-model="passwordForm.new_password" required minlength="6"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('account.confirm_password') ?></label>
                                <input type="password" x-model="passwordForm.confirm" required minlength="6"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-colors">
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="submit" :disabled="changingPassword"
                                class="px-6 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 font-medium text-sm transition-colors disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="changingPassword" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <?= __('account.change_password') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Colonne latérale (1/3) -->
        <div class="space-y-6">

            <!-- Card Mon Compte -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex flex-col items-center text-center mb-6">
                    <!-- Avatar -->
                    <div class="h-20 w-20 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-3"
                         :style="'background-color: ' + avatarColor">
                        <span x-text="user.full_name ? user.full_name.charAt(0).toUpperCase() : (user.username ? user.username.charAt(0).toUpperCase() : '?')"></span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="user.full_name || user.username"></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="'@' + user.username"></p>
                </div>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('account.role') ?></span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize"
                              :class="{
                                  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': user.role === 'superadmin',
                                  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': user.role === 'admin',
                                  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': user.role === 'gerant',
                                  'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': user.role === 'vendeur',
                                  'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': user.role === 'technicien'
                              }"
                              x-text="user.role"></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500 dark:text-gray-400"><?= __('account.username') ?></span>
                        <span class="text-gray-900 dark:text-white font-mono text-xs" x-text="user.username"></span>
                    </div>
                    <template x-if="user.email">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400"><?= __('account.email') ?></span>
                            <template x-if="user.email_verified">
                                <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <?= __('account.email_verified') ?>
                                </span>
                            </template>
                            <template x-if="!user.email_verified">
                                <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500">
                                    <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                    <?= __('account.email_not_verified') ?>
                                </span>
                            </template>
                        </div>
                    </template>
                    <template x-if="user.created_at">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400"><?= __('account.member_since') ?></span>
                            <span class="text-gray-900 dark:text-white text-xs" x-text="formatDate(user.created_at)"></span>
                        </div>
                    </template>
                    <template x-if="user.last_login">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-500 dark:text-gray-400"><?= __('account.last_login') ?></span>
                            <span class="text-gray-900 dark:text-white text-xs" x-text="formatDate(user.last_login)"></span>
                        </div>
                    </template>
                </div>
            </div>

            <?php if (isset($currentUser) && in_array($currentUser->getRole(), ['superadmin', 'admin'])): ?>
            <!-- 2FA Google Authenticator -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="h-9 w-9 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                        <svg class="h-5 w-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('settings.2fa_title') ?></h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Google Authenticator</p>
                    </div>
                </div>

                <!-- Status -->
                <div class="flex items-center gap-2 mb-4">
                    <template x-if="twofa.enabled">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <?= __('settings.2fa_active') ?>
                        </span>
                    </template>
                    <template x-if="!twofa.enabled">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                            <?= __('settings.2fa_inactive') ?>
                        </span>
                    </template>
                </div>

                <!-- Enable button -->
                <template x-if="!twofa.enabled && !twofa.setupMode">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3"><?= __('settings.2fa_description') ?></p>
                        <button @click="setup2fa()" :disabled="twofa.loading"
                                class="w-full py-2 px-4 bg-amber-600 text-white rounded-lg hover:bg-amber-700 text-sm font-medium transition-colors disabled:opacity-50">
                            <?= __('settings.2fa_enable') ?>
                        </button>
                    </div>
                </template>

                <!-- Setup mode: QR code + verification -->
                <template x-if="twofa.setupMode">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('settings.2fa_scan_qr') ?></p>

                        <!-- QR Code -->
                        <div class="flex justify-center">
                            <div id="qrcode-profile" class="bg-white p-3 rounded-lg inline-block"></div>
                        </div>

                        <!-- Manual secret -->
                        <div class="bg-gray-50 dark:bg-[#21262d] rounded-lg p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1"><?= __('settings.2fa_manual_key') ?></p>
                            <div class="flex items-center gap-2">
                                <code class="text-sm font-mono text-gray-900 dark:text-white break-all flex-1" x-text="twofa.secret"></code>
                                <button @click="navigator.clipboard.writeText(twofa.secret); showToast('<?= __js('common.copied') ?>')" class="text-blue-600 hover:text-blue-700 p-1">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                        </div>

                        <!-- Verification code -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.2fa_verify_code') ?></label>
                            <input type="text" x-model="twofa.verifyCode" inputmode="numeric" pattern="[0-9]*" maxlength="6" autocomplete="one-time-code"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-center text-lg tracking-[0.3em] font-mono"
                                   placeholder="000000">
                        </div>

                        <div class="flex gap-3">
                            <button @click="twofa.setupMode = false; twofa.secret = ''; twofa.verifyCode = ''"
                                    class="flex-1 py-2 px-4 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm hover:bg-gray-50 dark:hover:bg-[#30363d]">
                                <?= __('common.cancel') ?>
                            </button>
                            <button @click="enable2fa()" :disabled="twofa.verifyCode.length !== 6 || twofa.loading"
                                    class="flex-1 py-2 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium disabled:opacity-50 transition-colors">
                                <?= __('settings.2fa_activate') ?>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- Disable button -->
                <template x-if="twofa.enabled && !twofa.setupMode">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3"><?= __('settings.2fa_enabled_desc') ?></p>
                        <button @click="twofa.showDisable = true"
                                class="w-full py-2 px-4 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 text-sm font-medium transition-colors">
                            <?= __('settings.2fa_disable') ?>
                        </button>
                    </div>
                </template>

                <!-- Disable confirmation -->
                <template x-if="twofa.showDisable">
                    <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-400 mb-3"><?= __('settings.2fa_disable_confirm') ?></p>
                        <input type="password" x-model="twofa.disablePassword" placeholder="<?= __('account.current_password') ?>"
                               class="w-full px-4 py-2 border border-red-300 dark:border-red-800 rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white mb-3">
                        <div class="flex gap-3">
                            <button @click="twofa.showDisable = false; twofa.disablePassword = ''"
                                    class="flex-1 py-2 px-4 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm">
                                <?= __('common.cancel') ?>
                            </button>
                            <button @click="disable2fa()" :disabled="!twofa.disablePassword || twofa.loading"
                                    class="flex-1 py-2 px-4 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-medium disabled:opacity-50 transition-colors">
                                <?= __('settings.2fa_disable') ?>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
<script>
function profilePage() {
    return {
        user: {},
        form: { full_name: '', email: '', phone: '' },
        passwordForm: { current: '', new_password: '', confirm: '' },
        saving: false,
        changingPassword: false,
        avatarColor: '#3b82f6',
        twofa: {
            enabled: false,
            loading: false,
            setupMode: false,
            secret: '',
            otpauthUri: '',
            verifyCode: '',
            showDisable: false,
            disablePassword: ''
        },

        async init() {
            await this.loadProfile();
            this.load2faStatus();
        },

        async loadProfile() {
            try {
                const response = await fetch('api.php?route=/users/me');
                const data = await response.json();
                if (data.success && data.data) {
                    this.user = data.data;
                    this.form.full_name = data.data.full_name || '';
                    this.form.email = data.data.email || '';
                    this.form.phone = data.data.phone || '';
                    this.avatarColor = this.getRoleColor(data.data.role);
                }
            } catch (e) {
                showToast('<?= __js('account.update_error') ?>', 'error');
            }
        },

        getRoleColor(role) {
            const colors = {
                superadmin: '#dc2626',
                admin: '#2563eb',
                gerant: '#16a34a',
                vendeur: '#ea580c',
                technicien: '#9333ea'
            };
            return colors[role] || '#3b82f6';
        },

        async saveProfile() {
            this.saving = true;
            try {
                const response = await fetch('api.php?route=/users/me', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const data = await response.json();
                if (data.success) {
                    showToast('<?= __js('account.profile_updated') ?>');
                    await this.loadProfile();
                } else {
                    showToast(data.message || '<?= __js('account.update_error') ?>', 'error');
                }
            } catch (e) {
                showToast('<?= __js('account.update_error') ?>', 'error');
            } finally {
                this.saving = false;
            }
        },

        async changePassword() {
            if (this.passwordForm.new_password !== this.passwordForm.confirm) {
                showToast('<?= __js('account.password_mismatch') ?>', 'error');
                return;
            }
            this.changingPassword = true;
            try {
                const response = await fetch('api.php?route=/users/me', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: this.passwordForm.current,
                        new_password: this.passwordForm.new_password
                    })
                });
                const data = await response.json();
                if (data.success) {
                    showToast('<?= __js('account.password_changed') ?>');
                    this.passwordForm = { current: '', new_password: '', confirm: '' };
                } else {
                    showToast(data.message || '<?= __js('account.password_error') ?>', 'error');
                }
            } catch (e) {
                showToast('<?= __js('account.password_error') ?>', 'error');
            } finally {
                this.changingPassword = false;
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('<?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'fr-FR' : 'en-US' ?>', {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
        },

        // 2FA Methods
        async load2faStatus() {
            try {
                const response = await fetch('api.php?route=/auth/2fa/status');
                const data = await response.json();
                if (data.success && data.data) {
                    this.twofa.enabled = data.data.enabled;
                }
            } catch (e) { /* ignore */ }
        },

        async setup2fa() {
            this.twofa.loading = true;
            try {
                const response = await fetch('api.php?route=/auth/2fa/setup', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.twofa.secret = data.data.secret;
                    this.twofa.otpauthUri = data.data.otpauth_uri;
                    this.twofa.setupMode = true;
                    this.twofa.verifyCode = '';
                    this.$nextTick(() => {
                        const container = document.getElementById('qrcode-profile');
                        if (container) {
                            container.innerHTML = '';
                            const qr = qrcode(0, 'M');
                            qr.addData(this.twofa.otpauthUri);
                            qr.make();
                            container.innerHTML = qr.createSvgTag(5, 0);
                        }
                    });
                } else {
                    showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                showToast('Erreur serveur', 'error');
            } finally {
                this.twofa.loading = false;
            }
        },

        async enable2fa() {
            if (this.twofa.verifyCode.length !== 6) return;
            this.twofa.loading = true;
            try {
                const response = await fetch('api.php?route=/auth/2fa/enable', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ code: this.twofa.verifyCode })
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || '<?= __js('settings.2fa_enabled_msg') ?>');
                    this.twofa.enabled = true;
                    this.twofa.setupMode = false;
                    this.twofa.secret = '';
                    this.twofa.verifyCode = '';
                } else {
                    showToast(data.message || '<?= __js('auth.2fa_invalid_code') ?>', 'error');
                    this.twofa.verifyCode = '';
                }
            } catch (e) {
                showToast('Erreur serveur', 'error');
            } finally {
                this.twofa.loading = false;
            }
        },

        async disable2fa() {
            if (!this.twofa.disablePassword) return;
            this.twofa.loading = true;
            try {
                const response = await fetch('api.php?route=/auth/2fa/disable', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ password: this.twofa.disablePassword })
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || '<?= __js('settings.2fa_disabled_msg') ?>');
                    this.twofa.enabled = false;
                    this.twofa.showDisable = false;
                    this.twofa.disablePassword = '';
                } else {
                    showToast(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                showToast('Erreur serveur', 'error');
            } finally {
                this.twofa.loading = false;
            }
        }
    }
}
</script>
