<?php $pageTitle = __('settings.title'); $currentPage = 'settings'; ?>

<div x-data="settingsPage()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Paramètres généraux -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Application -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('settings.general') ?></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.app_name') ?></label>
                        <input type="text" x-model="settings.app_name"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.hotspot_title') ?></label>
                        <input type="text" x-model="settings.hotspot_title"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.currency') ?></label>
                            <select x-model="settings.currency"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="XAF">XAF (Franc CFA CEMAC)</option>
                                <option value="XOF">XOF (Franc CFA UEMOA)</option>
                                <option value="EUR">EUR (Euro)</option>
                                <option value="USD">USD (Dollar)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.language') ?></label>
                            <select x-model="settings.language"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="fr">Français</option>
                                <option value="en">English</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.timezone') ?></label>
                        <select x-model="settings.timezone"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="Africa/Douala">Africa/Douala (UTC+1)</option>
                            <option value="Africa/Lagos">Africa/Lagos (UTC+1)</option>
                            <option value="Europe/Paris">Europe/Paris (UTC+1/+2)</option>
                            <option value="UTC">UTC</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Support -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('settings.support_contact') ?></h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.support_email') ?></label>
                        <input type="email" x-model="settings.support_email"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('settings.support_phone') ?></label>
                        <input type="tel" x-model="settings.support_phone"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>
                </div>
            </div>

            <button @click="saveSettings()"
                    class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <?= __('settings.save_changes') ?>
            </button>
        </div>

        <?php if (isset($currentUser) && $currentUser->isSuperAdmin()): ?>
        <!-- Informations système (superadmin uniquement) -->
        <div class="space-y-6">
            <!-- Statut serveur RADIUS -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('settings.radius_server') ?></h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400"><?= __('settings.auth_port') ?></span>
                        <span class="font-mono text-gray-900 dark:text-white">1812</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400"><?= __('settings.acct_port') ?></span>
                        <span class="font-mono text-gray-900 dark:text-white">1813</span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">
                        <?= __('settings.radius_hint') ?>
                        <code class="block mt-1 font-mono">php radius_server.php</code>
                    </p>
                </div>
            </div>

            <!-- Infos système -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('settings.system_info') ?></h3>
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400"><?= __('settings.php_version') ?></span>
                        <span class="text-gray-900 dark:text-white"><?= PHP_VERSION ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400">OS</span>
                        <span class="text-gray-900 dark:text-white"><?= PHP_OS ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400"><?= __('settings.sockets_extension') ?></span>
                        <span class="<?= extension_loaded('sockets') ? 'text-green-600' : 'text-red-600' ?>">
                            <?= extension_loaded('sockets') ? __('status.ok') : __('status.missing') ?>
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400"><?= __('settings.pdo_mysql_extension') ?></span>
                        <span class="<?= extension_loaded('pdo_mysql') ? 'text-green-600' : 'text-red-600' ?>">
                            <?= extension_loaded('pdo_mysql') ? __('status.ok') : __('status.missing') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function settingsPage() {
    return {
        settings: {},
        initialLanguage: null,

        async init() {
            try {
                const response = await fetch('api.php?route=/settings');
                const data = await response.json();
                if (data.success && data.data) {
                    this.settings = {
                        app_name: data.data.app_name || 'RADIUS Manager',
                        hotspot_title: data.data.hotspot_title || 'WiFi Hotspot',
                        currency: data.data.currency || 'XAF',
                        language: data.data.language || 'fr',
                        timezone: data.data.timezone || 'Africa/Douala',
                        support_email: data.data.support_email || '',
                        support_phone: data.data.support_phone || ''
                    };
                } else {
                    this.settings = {
                        app_name: 'RADIUS Manager',
                        hotspot_title: 'WiFi Hotspot',
                        currency: 'XAF',
                        language: 'fr',
                        timezone: 'Africa/Douala',
                        support_email: '',
                        support_phone: ''
                    };
                }
                this.initialLanguage = this.settings.language;
            } catch (e) {
                this.settings = {
                    app_name: 'RADIUS Manager',
                    hotspot_title: 'WiFi Hotspot',
                    currency: 'XAF',
                    language: 'fr',
                    timezone: 'Africa/Douala',
                    support_email: '',
                    support_phone: ''
                };
                this.initialLanguage = this.settings.language;
            }
        },

        async saveSettings() {
            try {
                const response = await fetch('api.php?route=/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.settings)
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || __('settings.msg_saved'));
                    if (this.settings.language !== this.initialLanguage) {
                        setTimeout(() => window.location.reload(), 500);
                    }
                } else {
                    showToast(data.message || __('settings.msg_save_error'), 'error');
                }
            } catch (e) {
                showToast(__('settings.msg_save_error'), 'error');
            }
        }
    }
}
</script>
