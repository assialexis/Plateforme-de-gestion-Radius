<?php $pageTitle = __('superadmin.sms_credit_settings') ?? 'Configuration SMS (CSMS)';
$currentPage = 'superadmin-sms-config'; ?>

<div x-data="smsConfigPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.sms_credit_settings_desc') ?? 'Passerelle SMS plateforme facturée aux admins en CSMS' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading" class="space-y-6">
        <!-- Provider Balance Card -->
        <div x-show="smsSettings.platform_sms_provider"
            class="bg-gradient-to-r from-violet-600 to-indigo-600 rounded-xl p-5 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute right-16 bottom-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-white/70 uppercase tracking-wider mb-1">
                        <i class="fas fa-sms mr-1"></i>
                        <?= __('superadmin.sms_provider_balance') ?? 'Solde provider SMS' ?>
                    </p>
                    <div class="flex items-baseline gap-3">
                        <template x-if="providerBalance !== null">
                            <p class="text-3xl font-bold" x-text="Number(providerBalance).toLocaleString('fr-FR') + ' SMS'"></p>
                        </template>
                        <template x-if="providerBalance === null && !loadingBalance">
                            <p class="text-lg text-white/60">—</p>
                        </template>
                        <template x-if="loadingBalance">
                            <p class="text-lg text-white/60"><i class="fas fa-spinner fa-spin mr-1"></i> <?= __('common.loading') ?? 'Chargement...' ?></p>
                        </template>
                    </div>
                    <p class="text-xs text-white/50 mt-1" x-show="providerName" x-text="'via ' + providerName"></p>
                    <p class="text-xs text-red-200 mt-1" x-show="balanceError" x-text="balanceError"></p>
                </div>
                <button @click="checkProviderBalance()" :disabled="loadingBalance"
                    class="self-start sm:self-center px-4 py-2 bg-white/15 hover:bg-white/25 border border-white/20 rounded-lg text-sm font-medium transition-colors disabled:opacity-50">
                    <i class="fas fa-sync-alt mr-1.5" :class="loadingBalance && 'fa-spin'"></i>
                    <?= __('superadmin.check_balance') ?? 'Vérifier le solde' ?>
                </button>
            </div>
        </div>

        <!-- SMS Credits (CSMS) Settings -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.sms_credit_settings') ?? 'Configuration des crédits SMS (CSMS)' ?>
                </h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('superadmin.sms_cost_per_sms') ?? 'Coût par SMS (FCFA)' ?>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" x-model="smsSettings.sms_credit_cost_fcfa" min="1" step="1"
                                class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                            <span class="text-xs text-gray-500">FCFA = 1 CSMS</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('superadmin.platform_sms_provider') ?? 'Provider SMS backend' ?>
                        </label>
                        <select x-model="smsSettings.platform_sms_provider"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                            <option value="">-- Sélectionner --</option>
                            <option value="nghcorp">NGH Corp</option>
                        </select>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                <?= __('superadmin.sms_credit_enabled') ?? 'Système CSMS actif' ?>
                            </p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" x-model="smsCreditEnabled"
                                @change="smsSettings.sms_credit_enabled = smsCreditEnabled ? '1' : '0'">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                        </label>
                    </div>
                </div>
                <!-- Platform SMS Provider Config -->
                <div x-show="smsSettings.platform_sms_provider" class="border-t border-gray-200 dark:border-[#30363d] pt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        <?= __('superadmin.platform_sms_config') ?? 'Configuration du provider backend' ?>
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">API Key</label>
                            <input type="text" x-model="platformSmsConfig.api_key"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">API Secret</label>
                            <input type="password" x-model="platformSmsConfig.api_secret"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Sender ID</label>
                            <input type="text" x-model="platformSmsConfig.sender_id" placeholder="MonEntreprise"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 font-mono">
                        </div>
                    </div>
                </div>
                <!-- Info conversion -->
                <div class="bg-blue-50 dark:bg-blue-900/10 rounded-lg p-3">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        <strong>Taux de conversion :</strong>
                        1 CRT = <span x-text="creditExchangeRate || 100"></span> FCFA =
                        <span x-text="smsSettings.sms_credit_cost_fcfa > 0 ? Math.floor((creditExchangeRate || 100) / smsSettings.sms_credit_cost_fcfa) : 0"></span> CSMS
                    </p>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex justify-end">
                <button @click="saveSmsSettings()" :disabled="savingSmsSettings"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingSmsSettings"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="savingSmsSettings"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function smsConfigPage() {
    return {
        smsSettings: { sms_credit_cost_fcfa: '25', sms_credit_enabled: '1', platform_sms_provider: '' },
        smsCreditEnabled: true,
        platformSmsConfig: { api_key: '', api_secret: '', sender_id: '' },
        creditExchangeRate: 100,
        savingSmsSettings: false,
        loading: true,
        providerBalance: null,
        providerName: '',
        loadingBalance: false,
        balanceError: '',

        async init() {
            await this.loadSettings();
            this.loading = false;
            if (this.smsSettings.platform_sms_provider) {
                this.checkProviderBalance();
            }
        },

        async loadSettings() {
            try {
                const res = await fetch('api.php?route=/superadmin/module-pricing', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    const s = data.data.settings;
                    this.creditExchangeRate = s.credit_exchange_rate || 100;
                    this.smsSettings.sms_credit_cost_fcfa = s.sms_credit_cost_fcfa || '25';
                    this.smsSettings.sms_credit_enabled = s.sms_credit_enabled || '1';
                    this.smsSettings.platform_sms_provider = s.platform_sms_provider || '';
                    this.smsCreditEnabled = this.smsSettings.sms_credit_enabled === '1';
                    try {
                        this.platformSmsConfig = JSON.parse(s.platform_sms_config || '{}');
                    } catch(e) { this.platformSmsConfig = {}; }
                }
            } catch (e) { showToast('Erreur chargement', 'error'); }
        },

        async saveSmsSettings() {
            this.savingSmsSettings = true;
            try {
                const res = await fetch('api.php?route=/superadmin/sms-credit-settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        sms_credit_cost_fcfa: this.smsSettings.sms_credit_cost_fcfa,
                        sms_credit_enabled: this.smsSettings.sms_credit_enabled,
                        platform_sms_provider: this.smsSettings.platform_sms_provider,
                        platform_sms_config: this.platformSmsConfig
                    })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.savingSmsSettings = false;
        },

        async checkProviderBalance() {
            this.loadingBalance = true;
            this.balanceError = '';
            try {
                const res = await fetch('api.php?route=/superadmin/platform-sms-balance', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.providerBalance = data.data.balance;
                    this.providerName = data.data.provider || '';
                } else {
                    this.balanceError = data.message || 'Erreur';
                }
            } catch (e) {
                this.balanceError = 'Erreur de connexion';
            }
            this.loadingBalance = false;
        }
    };
}
</script>
