<?php $pageTitle = __('superadmin.module_pricing_title') ?? 'Tarification des Modules';
$currentPage = 'superadmin-module-pricing'; ?>

<div x-data="modulePricingPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.module_pricing_subtitle') ?? 'Définir le coût en crédits pour chaque module' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading" class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.total_credits') ?? 'Total crédits en circulation' ?></p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="stats.total_credits_in_system?.toFixed(2) || '0.00'"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.admins_with_credits') ?? 'Admins avec crédits' ?></p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="stats.admins_with_credits || 0"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.recharges_month') ?? 'Recharges ce mois' ?></p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="'+' + (stats.recharges_this_month?.total?.toFixed(2) || '0.00')"></p>
                <p class="text-xs text-gray-500" x-text="(stats.recharges_this_month?.count || 0) + ' transactions'"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.activations_month') ?? 'Activations ce mois' ?></p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" x-text="stats.activations_this_month?.count || 0"></p>
                <p class="text-xs text-gray-500" x-text="(stats.activations_this_month?.total?.toFixed(2) || '0.00') + ' crédits'"></p>
            </div>
        </div>

        <!-- Credit Settings -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.credit_settings') ?? 'Configuration des crédits' ?>
                </h3>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.credit_exchange_rate') ?? 'Taux de change' ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="creditSettings.credit_exchange_rate" min="1" step="1"
                            class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <span class="text-xs text-gray-500" x-text="creditSettings.credit_currency + ' = 1 crédit'"></span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.credit_currency_label') ?? 'Devise' ?>
                    </label>
                    <select x-model="creditSettings.credit_currency"
                        class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <option value="XOF">XOF</option>
                        <option value="XAF">XAF</option>
                        <option value="GNF">GNF</option>
                        <option value="USD">USD</option>
                        <option value="EUR">EUR</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.free_initial_credits') ?? 'Crédits initiaux gratuits' ?>
                    </label>
                    <input type="number" x-model="creditSettings.free_initial_credits" min="0" step="1"
                        class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.nas_creation_cost') ?? 'Coût ajout NAS' ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="creditSettings.nas_creation_cost" min="0" step="1"
                            class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <span class="text-xs text-gray-500"><?= __('credits.credits') ?? 'crédits' ?></span>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.nas_validity_days') ?? 'Validité NAS (jours)' ?>
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" x-model="creditSettings.nas_validity_days" min="0" step="1"
                            class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <span class="text-xs text-gray-500"><?= __('superadmin.nas_validity_hint') ?? '0 = illimité' ?></span>
                    </div>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?= __('superadmin.credit_system_enabled') ?? 'Système actif' ?>
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" x-model="creditSystemEnabled"
                            @change="creditSettings.credit_system_enabled = creditSystemEnabled ? '1' : '0'">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                    </label>
                </div>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex justify-end">
                <button @click="saveCreditSettings()" :disabled="savingSettings"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingSettings"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="savingSettings"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>

        <!-- Module Pricing Table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.module_pricing_list') ?? 'Tarification par module' ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]/30">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_module') ?? 'Module' ?></th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_price_credits') ?? 'Prix (crédits)' ?></th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_billing_type') ?? 'Facturation' ?></th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_active_subs') ?? 'Abonnés' ?></th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_active') ?? 'Actif' ?></th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="mod in modules" :key="mod.module_code">
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize" x-text="mod.module_code"></span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="mod.description"></p>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <input type="number" x-model="mod.price_credits" min="0" step="0.01"
                                        class="w-24 px-2 py-1 text-sm text-center border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <select x-model="mod.billing_type"
                                        class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                                        <option value="one_time"><?= __('superadmin.billing_one_time') ?? 'Unique' ?></option>
                                        <option value="monthly"><?= __('superadmin.billing_monthly') ?? 'Mensuel' ?></option>
                                    </select>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-sm text-gray-600 dark:text-gray-400" x-text="mod.active_subscriptions || 0"></span>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" :checked="mod.is_active == 1"
                                            @change="mod.is_active = mod.is_active == 1 ? 0 : 1">
                                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                                    </label>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <button @click="saveModule(mod)" :disabled="mod.saving"
                                        class="px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 transition-colors disabled:opacity-50">
                                        <span x-show="!mod.saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
                                        <span x-show="mod.saving">...</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.recent_credit_transactions') ?? 'Dernières transactions de crédits' ?>
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]/30">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Admin</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                            <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Description</th>
                            <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="tx in stats.recent_transactions || []" :key="tx.id">
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/50">
                                <td class="px-4 py-2.5 text-sm text-gray-900 dark:text-gray-100" x-text="tx.full_name || tx.username"></td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': tx.type === 'recharge',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': tx.type === 'module_activation',
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': tx.type === 'adjustment'
                                        }"
                                        x-text="tx.type"></span>
                                </td>
                                <td class="px-4 py-2.5 text-right font-medium"
                                    :class="parseFloat(tx.amount) >= 0 ? 'text-emerald-600' : 'text-red-600'"
                                    x-text="(parseFloat(tx.amount) >= 0 ? '+' : '') + parseFloat(tx.amount).toFixed(2)"></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 max-w-xs truncate" x-text="tx.description"></td>
                                <td class="px-4 py-2.5 text-xs text-gray-500 whitespace-nowrap" x-text="new Date(tx.created_at).toLocaleString()"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                <div x-show="!stats.recent_transactions || stats.recent_transactions.length === 0" class="p-5 text-center text-sm text-gray-500">
                    <?= __('credits.no_transactions') ?? 'Aucune transaction' ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function modulePricingPage() {
    return {
        modules: [],
        stats: {},
        creditSettings: {},
        creditSystemEnabled: true,
        loading: true,
        savingSettings: false,

        async init() {
            await Promise.all([this.loadPricing(), this.loadStats()]);
            this.loading = false;
        },

        async loadPricing() {
            try {
                const res = await fetch('api.php?route=/superadmin/module-pricing', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.modules = data.data.modules.map(m => ({ ...m, saving: false }));
                    const s = data.data.settings;
                    this.creditSettings = s;
                    this.creditSystemEnabled = s.credit_system_enabled === '1';
                }
            } catch (e) { showToast('Erreur chargement', 'error'); }
        },

        async loadStats() {
            try {
                const res = await fetch('api.php?route=/superadmin/credit-stats', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (e) { console.error(e); }
        },

        async saveCreditSettings() {
            this.savingSettings = true;
            try {
                const res = await fetch('api.php?route=/superadmin/credit-settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.creditSettings)
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.savingSettings = false;
        },

        async saveModule(mod) {
            mod.saving = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/module-pricing/${mod.module_code}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        price_credits: parseFloat(mod.price_credits),
                        billing_type: mod.billing_type,
                        is_active: mod.is_active == 1
                    })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            mod.saving = false;
        }
    };
}
</script>
