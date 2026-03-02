<?php $pageTitle = __('superadmin.paygate_settings') ?? 'Configuration Paygate';
$currentPage = 'superadmin-paygate-config'; ?>

<div x-data="paygateConfigPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.paygate_settings_desc') ?? 'Passerelles pré-configurées. Les admins activent sans clés API. L\'argent va au compte plateforme.' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading" class="space-y-6">
        <!-- Global Paygate Toggle -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.paygate_general') ?? 'Paramètres généraux' ?>
                </h3>
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?= __('superadmin.paygate_enabled') ?? 'Système Paygate actif' ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('superadmin.paygate_enabled_desc') ?? 'Activer/désactiver le système de passerelle plateforme pour tous les admins' ?></p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" x-model="paygateEnabled"
                            @change="paygateSettings.paygate_enabled = paygateEnabled ? '1' : '0'">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                    </label>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex justify-end">
                <button @click="savePaygateSettings()" :disabled="savingPaygateSettings"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingPaygateSettings"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="savingPaygateSettings"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>

        <!-- Platform Payment Gateways -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.paygate_available_gateways') ?? 'Passerelles disponibles' ?>
                </h3>
                <p class="text-xs text-gray-500 mt-1">
                    <?= __('superadmin.paygate_api_config_hint') ?? 'Les clés API se configurent sur la page' ?>
                    <a href="?page=superadmin-recharge-gateways" class="text-red-600 dark:text-red-400 underline font-medium">
                        <?= __('superadmin.recharge_gateways') ?? 'Passerelles de recharge' ?>
                    </a>
                </p>
            </div>
            <div class="space-y-0 divide-y divide-gray-100 dark:divide-[#21262d]/50">
                <template x-for="gw in paygateGateways" :key="gw.id">
                    <div class="p-5">
                        <!-- Warning if recharge gateway not configured -->
                        <div x-show="!gw.recharge_configured" class="mb-3 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <p class="text-xs text-amber-700 dark:text-amber-400">
                                <?= __('superadmin.paygate_not_configured') ?? 'Clés API non configurées.' ?>
                                <a href="?page=superadmin-recharge-gateways" class="underline font-semibold">
                                    <?= __('superadmin.paygate_configure_here') ?? 'Configurer ici' ?>
                                </a>
                            </p>
                        </div>

                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                    :class="{
                                        'bg-blue-600': gw.gateway_code === 'fedapay',
                                        'bg-orange-500': gw.gateway_code === 'cinetpay',
                                        'bg-green-600': gw.gateway_code === 'ligdicash',
                                        'bg-emerald-600': gw.gateway_code === 'paygate_global',
                                        'bg-cyan-600': gw.gateway_code === 'feexpay',
                                        'bg-indigo-600': gw.gateway_code === 'kkiapay',
                                        'bg-teal-600': gw.gateway_code === 'paydunya',
                                        'bg-rose-600': gw.gateway_code === 'yengapay',
                                        'bg-purple-600': !['fedapay','cinetpay','ligdicash','paygate_global','feexpay','kkiapay','paydunya','yengapay'].includes(gw.gateway_code)
                                    }"
                                    x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm" x-text="gw.name"></h4>
                                    <p class="text-xs text-gray-500" x-text="gw.description"></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs px-2 py-1 rounded font-medium"
                                    :class="gw.is_sandbox == 1 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                                    x-text="gw.is_sandbox == 1 ? 'Sandbox' : 'Production'"></span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer" :checked="gw.is_active == 1"
                                        @change="gw.is_active = gw.is_active == 1 ? 0 : 1">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </div>

                        <!-- Per-gateway withdrawal settings -->
                        <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-4">
                            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3">
                                <?= __('superadmin.paygate_withdrawal_settings') ?? 'Paramètres de retrait' ?>
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        <?= __('superadmin.paygate_commission_rate') ?? 'Commission sur retraits (%)' ?>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="number" x-model="gw.commission_rate" min="0" max="100" step="0.5"
                                            class="w-24 px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                                        <span class="text-xs text-gray-500">%</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        <?= __('superadmin.paygate_min_withdrawal') ?? 'Retrait minimum' ?>
                                    </label>
                                    <input type="number" x-model="gw.min_withdrawal" min="0" step="100"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                        <?= __('superadmin.paygate_currency') ?? 'Devise' ?>
                                    </label>
                                    <select x-model="gw.withdrawal_currency"
                                        class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                                        <option value="XOF">XOF</option>
                                        <option value="XAF">XAF</option>
                                        <option value="GNF">GNF</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-4">
                            <button @click="savePaygateGateway(gw)" :disabled="gw.saving"
                                class="px-4 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                                <span x-show="!gw.saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
                                <span x-show="gw.saving"><?= __('common.loading') ?? '...' ?></span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <div x-show="paygateGateways.length === 0" class="p-5 text-center text-sm text-gray-500">
                <?= __('superadmin.no_gateways') ?? 'Aucune passerelle configurée' ?>
            </div>
        </div>
    </div>
</div>

<script>
function paygateConfigPage() {
    return {
        paygateSettings: { paygate_enabled: '0' },
        paygateEnabled: false,
        paygateGateways: [],
        savingPaygateSettings: false,
        loading: true,

        async init() {
            await this.loadPaygateData();
            this.loading = false;
        },

        async loadPaygateData() {
            try {
                const res = await fetch('api.php?route=/superadmin/paygate/gateways', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.paygateGateways = data.data.gateways.map(gw => ({ ...gw, saving: false }));
                    const s = data.data.settings;
                    this.paygateSettings = s;
                    this.paygateEnabled = s.paygate_enabled === '1';
                }
            } catch (e) { console.error(e); }
        },

        async savePaygateSettings() {
            this.savingPaygateSettings = true;
            try {
                const res = await fetch('api.php?route=/superadmin/paygate/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.paygateSettings)
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.savingPaygateSettings = false;
        },

        async savePaygateGateway(gw) {
            gw.saving = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/paygate/gateways/${gw.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        is_active: gw.is_active == 1,
                        commission_rate: parseFloat(gw.commission_rate),
                        min_withdrawal: parseFloat(gw.min_withdrawal),
                        withdrawal_currency: gw.withdrawal_currency
                    })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            gw.saving = false;
        }
    };
}
</script>
