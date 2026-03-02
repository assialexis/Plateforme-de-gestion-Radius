<?php $pageTitle = __('credits.subscription_title') ?? 'Abonnement & Crédits';
$currentPage = 'subscription'; ?>

<div x-data="subscriptionPage()" x-init="init()">

    <div x-show="loading" class="flex justify-center py-16">
        <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <div x-show="!loading" x-cloak>

        <!-- Balance Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
            <!-- CRT Card -->
            <div class="relative overflow-hidden bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('credits.balance_label') ?? 'Solde crédits' ?></p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white" x-text="balance.toFixed(2)">0.00</span>
                            <span class="text-sm font-medium text-gray-400">CRT</span>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1" x-show="pendingRecharges > 0">
                            <span x-text="pendingRecharges"></span> <?= __('credits.recharge_pending') ?? 'recharge(s) en attente' ?>
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-[11px] text-gray-400">1 CRT = <span x-text="exchangeRate"></span> <span x-text="currency"></span></p>
                    <button @click="window.dispatchEvent(new CustomEvent('open-recharge-modal'))"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-sm">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('credits.recharge_submit') ?? 'Recharger' ?>
                    </button>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-indigo-500/5 dark:bg-indigo-500/10"></div>
            </div>

            <!-- CSMS Card -->
            <div class="relative overflow-hidden bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('sms_credits.balance') ?? 'Solde SMS' ?></p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <span class="text-3xl font-bold text-gray-900 dark:text-white" x-text="smsBalance.toFixed(0)">0</span>
                            <span class="text-sm font-medium text-gray-400">CSMS</span>
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1" x-show="csmsPerCrt > 0">
                            1 CRT = <span x-text="csmsPerCrt"></span> CSMS
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    </div>
                </div>
                <!-- Quick convert -->
                <div class="mt-3 flex items-center gap-2" x-show="smsEnabled">
                    <input type="number" x-model.number="convertAmount" min="1" step="1" placeholder="CRT"
                        class="w-20 px-2 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-1 focus:ring-emerald-500">
                    <button @click="convertToCSMS()" :disabled="converting || !convertAmount || convertAmount > balance"
                        class="px-3 py-1 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors disabled:opacity-50">
                        <span x-show="!converting"><?= __('sms_credits.convert_btn') ?? 'Convertir' ?></span>
                        <span x-show="converting">...</span>
                    </button>
                    <span class="text-[11px] text-gray-400" x-show="convertAmount > 0" x-text="'→ ' + Math.floor(convertAmount * csmsPerCrt) + ' CSMS'"></span>
                </div>
                <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-emerald-500/5 dark:bg-emerald-500/10"></div>
            </div>
        </div>

        <!-- Recharge + Module Prices -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <!-- Recharge Form -->
            <div id="recharge-section" class="bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-[#21262d]">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('credits.recharge_title') ?? 'Recharger des crédits' ?>
                    </h3>
                </div>
                <div class="p-5 space-y-4">
                    <!-- Amount -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                            <?= __('credits.recharge_amount') ?? 'Montant' ?> (<span x-text="currency">XOF</span>)
                        </label>
                        <input type="number" x-model="rechargeForm.amount" min="100" step="100"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            placeholder="1000">
                        <p class="text-[11px] text-gray-400 mt-1" x-show="rechargeForm.amount > 0">
                            = <span class="font-semibold text-indigo-600 dark:text-indigo-400" x-text="(rechargeForm.amount / exchangeRate).toFixed(2)"></span> CRT
                        </p>
                    </div>

                    <!-- Quick amounts -->
                    <div class="flex gap-2">
                        <template x-for="q in quickAmounts" :key="q">
                            <button @click="rechargeForm.amount = q" type="button"
                                class="flex-1 py-1.5 text-xs font-medium rounded-lg transition-all"
                                :class="rechargeForm.amount == q
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                                x-text="q >= 1000 ? (q/1000) + 'k' : q"></button>
                        </template>
                    </div>

                    <!-- Gateway -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                            <?= __('credits.recharge_gateway') ?? 'Mode de paiement' ?>
                        </label>
                        <div class="grid grid-cols-2 gap-2" x-show="gateways.length > 0">
                            <template x-for="gw in gateways" :key="gw.gateway_code">
                                <button @click="rechargeForm.gateway_code = gw.gateway_code" type="button"
                                    class="flex items-center gap-2 px-3 py-2.5 text-xs font-medium rounded-xl border transition-all"
                                    :class="rechargeForm.gateway_code === gw.gateway_code
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 ring-1 ring-indigo-500'
                                        : 'border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'">
                                    <div class="w-6 h-6 rounded flex-shrink-0 flex items-center justify-center text-[9px] font-bold text-white"
                                        :class="{
                                            'bg-blue-600': gw.gateway_code === 'fedapay',
                                            'bg-orange-500': gw.gateway_code === 'cinetpay',
                                            'bg-green-600': gw.gateway_code === 'ligdicash',
                                            'bg-purple-600': gw.gateway_code === 'cryptomus'
                                        }"
                                        x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                                    <span x-text="gw.name" class="truncate"></span>
                                </button>
                            </template>
                        </div>
                        <p x-show="gateways.length === 0" class="text-xs text-gray-400 italic"><?= __('credits.no_gateways') ?? 'Aucune passerelle disponible' ?></p>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">
                            <?= __('credits.recharge_phone') ?? 'Numéro de téléphone' ?>
                        </label>
                        <input type="tel" x-model="rechargeForm.phone"
                            class="w-full px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-shadow"
                            placeholder="+229 XX XX XX XX">
                    </div>

                    <!-- Submit -->
                    <button @click="recharge()" :disabled="recharging || !rechargeForm.amount || !rechargeForm.gateway_code"
                        class="w-full py-3 px-4 text-sm font-semibold text-white bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm flex items-center justify-center gap-2">
                        <svg x-show="recharging" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-show="!recharging"><?= __('credits.recharge_submit') ?? 'Procéder au paiement' ?></span>
                        <span x-show="recharging"><?= __('common.loading') ?? 'Chargement...' ?></span>
                    </button>
                </div>
            </div>

            <!-- Module Prices -->
            <div class="bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-100 dark:border-[#21262d] flex items-center justify-between">
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                        <?= __('credits.module_prices') ?? 'Tarifs des modules' ?>
                    </h3>
                    <a href="index.php?page=modules" class="text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                        <?= __('module.tab_all') ?? 'Voir tous' ?> →
                    </a>
                </div>
                <div class="divide-y divide-gray-50 dark:divide-[#21262d]/30 max-h-[400px] overflow-y-auto">
                    <template x-for="mp in modulePrices" :key="mp.module_code">
                        <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                    :class="mp.is_active == 1 && mp.price_credits > 0
                                        ? 'bg-indigo-100 dark:bg-indigo-900/20 text-indigo-500 dark:text-indigo-400'
                                        : 'bg-gray-100 dark:bg-[#21262d] text-gray-400'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 capitalize" x-text="mp.module_code.replace(/-/g, ' ')"></p>
                                    <p class="text-[11px] text-gray-400 line-clamp-1" x-text="mp.description"></p>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0 ml-3">
                                <template x-if="mp.price_credits > 0 && mp.is_active == 1">
                                    <div>
                                        <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="parseFloat(mp.price_credits).toFixed(0)"></span>
                                        <span class="text-[11px] text-gray-400"> CRT</span>
                                        <p class="text-[10px] text-gray-400" x-text="mp.billing_type === 'monthly' ? '/<?= __('credits.month_short') ?? 'mois' ?>' : '(<?= __('credits.one_time') ?? 'unique' ?>)'"></p>
                                    </div>
                                </template>
                                <template x-if="mp.price_credits == 0 || mp.is_active != 1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-green-50 text-green-600 dark:bg-green-900/20 dark:text-green-400"><?= __('credits.free') ?? 'Gratuit' ?></span>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="modulePrices.length === 0" class="p-8 text-center text-sm text-gray-400">
                        <?= __('credits.no_pricing') ?? 'Aucune tarification configurée' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transaction History -->
        <div class="bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-[#21262d] flex items-center justify-between">
                <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <?= __('credits.transaction_history') ?? 'Historique' ?>
                </h3>
                <div class="flex items-center gap-2">
                    <select x-model="filterType" @change="currentPage = 1; loadTransactions()"
                        class="text-[11px] px-2 py-1 border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-indigo-500">
                        <option value=""><?= __('common.all') ?? 'Tous' ?></option>
                        <option value="recharge"><?= __('credits.type_recharge') ?? 'Recharge' ?></option>
                        <option value="module_activation"><?= __('credits.type_module_activation') ?? 'Activation' ?></option>
                        <option value="module_renewal"><?= __('credits.type_module_renewal') ?? 'Renouvellement' ?></option>
                        <option value="adjustment"><?= __('credits.type_adjustment') ?? 'Ajustement' ?></option>
                        <option value="refund"><?= __('credits.type_refund') ?? 'Remboursement' ?></option>
                    </select>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50/50 dark:bg-[#0d1117]/30">
                        <tr>
                            <th class="px-4 py-2.5 text-left text-[11px] font-medium text-gray-400 uppercase"><?= __('credits.col_date') ?? 'Date' ?></th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-medium text-gray-400 uppercase"><?= __('credits.col_type') ?? 'Type' ?></th>
                            <th class="px-4 py-2.5 text-right text-[11px] font-medium text-gray-400 uppercase"><?= __('credits.col_amount') ?? 'Montant' ?></th>
                            <th class="px-4 py-2.5 text-right text-[11px] font-medium text-gray-400 uppercase"><?= __('credits.col_balance') ?? 'Solde' ?></th>
                            <th class="px-4 py-2.5 text-left text-[11px] font-medium text-gray-400 uppercase"><?= __('credits.col_description') ?? 'Description' ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-[#21262d]/30">
                        <template x-for="tx in transactions" :key="tx.id">
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/30 transition-colors">
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap" x-text="formatTxDate(tx.created_at)"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400': tx.type === 'recharge',
                                            'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400': tx.type === 'module_activation',
                                            'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400': tx.type === 'module_renewal',
                                            'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400': tx.type === 'adjustment',
                                            'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400': tx.type === 'refund'
                                        }"
                                        x-text="typeLabels[tx.type] || tx.type"></span>
                                </td>
                                <td class="px-4 py-3 text-right text-sm font-semibold whitespace-nowrap"
                                    :class="parseFloat(tx.amount) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400'"
                                    x-text="(parseFloat(tx.amount) >= 0 ? '+' : '') + parseFloat(tx.amount).toFixed(2)"></td>
                                <td class="px-4 py-3 text-right text-xs text-gray-500 whitespace-nowrap" x-text="parseFloat(tx.balance_after).toFixed(2) + ' CRT'"></td>
                                <td class="px-4 py-3 text-xs text-gray-500 max-w-[200px] truncate" x-text="tx.description"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Mobile list -->
            <div class="sm:hidden divide-y divide-gray-50 dark:divide-[#21262d]/30">
                <template x-for="tx in transactions" :key="tx.id">
                    <div class="px-4 py-3">
                        <div class="flex items-center justify-between mb-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400': tx.type === 'recharge',
                                    'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400': tx.type === 'module_activation',
                                    'bg-purple-50 text-purple-700 dark:bg-purple-900/20 dark:text-purple-400': tx.type === 'module_renewal',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-900/20 dark:text-amber-400': tx.type === 'adjustment',
                                    'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400': tx.type === 'refund'
                                }"
                                x-text="typeLabels[tx.type] || tx.type"></span>
                            <span class="text-sm font-semibold"
                                :class="parseFloat(tx.amount) >= 0 ? 'text-emerald-600' : 'text-red-500'"
                                x-text="(parseFloat(tx.amount) >= 0 ? '+' : '') + parseFloat(tx.amount).toFixed(2) + ' CRT'"></span>
                        </div>
                        <p class="text-[11px] text-gray-500 truncate" x-text="tx.description"></p>
                        <p class="text-[10px] text-gray-400 mt-0.5" x-text="formatTxDate(tx.created_at)"></p>
                    </div>
                </template>
            </div>

            <div x-show="transactions.length === 0" class="p-10 text-center">
                <div class="w-12 h-12 mx-auto mb-3 rounded-xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p class="text-sm text-gray-400"><?= __('credits.no_transactions') ?? 'Aucune transaction' ?></p>
            </div>

            <!-- Pagination -->
            <div x-show="totalPages > 1" class="px-5 py-3 border-t border-gray-100 dark:border-[#21262d] flex items-center justify-between">
                <p class="text-[11px] text-gray-400">
                    Page <span x-text="currentPage"></span> / <span x-text="totalPages"></span>
                </p>
                <div class="flex gap-2">
                    <button @click="currentPage--; loadTransactions()" :disabled="currentPage <= 1"
                        class="px-3 py-1.5 text-xs font-medium border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-[#1c2128] disabled:opacity-40 transition-colors">
                        <?= __('common.previous') ?? 'Précédent' ?>
                    </button>
                    <button @click="currentPage++; loadTransactions()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1.5 text-xs font-medium border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-[#1c2128] disabled:opacity-40 transition-colors">
                        <?= __('common.next') ?? 'Suivant' ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function subscriptionPage() {
    return {
        balance: 0,
        smsBalance: 0,
        smsEnabled: false,
        csmsPerCrt: 0,
        pendingRecharges: 0,
        exchangeRate: 100,
        currency: 'XOF',
        transactions: [],
        gateways: [],
        modulePrices: [],
        rechargeForm: { amount: '', gateway_code: '', phone: '' },
        quickAmounts: [500, 1000, 2000, 5000, 10000],
        loading: true,
        recharging: false,
        convertAmount: '',
        converting: false,
        filterType: '',
        currentPage: 1,
        totalPages: 1,
        typeLabels: {
            recharge: '<?= __('credits.type_recharge') ?? 'Recharge' ?>',
            module_activation: '<?= __('credits.type_module_activation') ?? 'Activation' ?>',
            module_renewal: '<?= __('credits.type_module_renewal') ?? 'Renouvellement' ?>',
            adjustment: '<?= __('credits.type_adjustment') ?? 'Ajustement' ?>',
            refund: '<?= __('credits.type_refund') ?? 'Remboursement' ?>'
        },

        async init() {
            await Promise.all([
                this.loadBalance(),
                this.loadSmsBalance(),
                this.loadTransactions(),
                this.loadGateways(),
                this.loadModulePrices()
            ]);
            this.loading = false;

            const urlParams = new URLSearchParams(window.location.search);
            const txn = urlParams.get('txn');
            if (txn) this.checkRechargeResult(txn);
        },

        async loadBalance() {
            try {
                const res = await fetch('api.php?route=/credits/balance', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.balance = data.data.balance;
                    this.pendingRecharges = data.data.pending_recharges;
                    this.exchangeRate = data.data.exchange_rate;
                    this.currency = data.data.currency;
                }
            } catch (e) { console.error(e); }
        },

        async loadSmsBalance() {
            try {
                const res = await fetch('api.php?route=/sms-credits/balance', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success && data.data) {
                    this.smsBalance = parseFloat(data.data.balance || 0);
                    this.smsEnabled = data.data.enabled !== false;
                    this.csmsPerCrt = parseFloat(data.data.csms_per_crt || 0);
                }
            } catch (e) {
                this.smsBalance = 0;
                this.smsEnabled = false;
            }
        },

        async loadTransactions() {
            try {
                let url = `api.php?route=/credits/transactions&page=${this.currentPage}`;
                if (this.filterType) url += `&type=${this.filterType}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.transactions = data.data.transactions;
                    this.totalPages = data.data.total_pages;
                }
            } catch (e) { console.error(e); }
        },

        async loadGateways() {
            try {
                const res = await fetch('api.php?route=/credits/recharge-gateways', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.gateways = data.data?.gateways || [];
            } catch (e) { console.error(e); }
        },

        async loadModulePrices() {
            try {
                const res = await fetch('api.php?route=/credits/module-prices', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.modulePrices = data.data.prices;
            } catch (e) { console.error(e); }
        },

        async recharge() {
            if (!this.rechargeForm.amount || !this.rechargeForm.gateway_code) return;
            this.recharging = true;
            try {
                const res = await fetch('api.php?route=/credits/recharge', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.rechargeForm)
                });
                const data = await res.json();
                if (data.success && data.data) {
                    if (data.data.payment_url) {
                        window.location.href = data.data.payment_url;
                    } else if (data.data.transaction_id) {
                        this.notify('<?= __('credits.recharge_pending') ?? 'Paiement en attente' ?>', 'info');
                        await this.loadBalance();
                        await this.loadTransactions();
                    }
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur de connexion', 'error');
            }
            this.recharging = false;
        },

        async convertToCSMS() {
            if (!this.convertAmount || this.convertAmount <= 0 || this.convertAmount > this.balance) return;
            this.converting = true;
            try {
                const res = await fetch('api.php?route=/sms-credits/convert', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ crt_amount: this.convertAmount })
                });
                const data = await res.json();
                if (data.success) {
                    this.notify(data.data?.message || '<?= __('sms_credits.convert_success') ?? 'Conversion réussie' ?>', 'success');
                    this.convertAmount = '';
                    await Promise.all([this.loadBalance(), this.loadSmsBalance()]);
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
            this.converting = false;
        },

        async checkRechargeResult(txnId) {
            try {
                const res = await fetch(`api.php?route=/credits/recharge/status&txn=${txnId}`, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success && data.data) {
                    if (data.data.status === 'completed') {
                        this.notify('<?= __('credits.recharge_success') ?? 'Recharge effectuée !' ?>', 'success');
                        await this.loadBalance();
                        await this.loadTransactions();
                    } else if (data.data.status === 'pending') {
                        this.notify('<?= __('credits.recharge_pending') ?? 'Paiement en attente' ?>', 'info');
                    } else {
                        this.notify('<?= __('credits.recharge_failed') ?? 'Échec de la recharge' ?>', 'error');
                    }
                }
            } catch (e) { console.error(e); }
        },

        formatTxDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            const now = new Date();
            const diffMs = now - d;
            const diffMin = Math.floor(diffMs / 60000);
            if (diffMin < 1) return '<?= __('common.just_now') ?? 'À l\'instant' ?>';
            if (diffMin < 60) return diffMin + ' min';
            const diffH = Math.floor(diffMin / 60);
            if (diffH < 24) return diffH + 'h';
            return d.toLocaleDateString('<?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'fr-FR' : 'en-US' ?>', {
                day: 'numeric', month: 'short', year: d.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
            });
        },

        notify(message, type = 'info') {
            if (typeof window.showToast === 'function') window.showToast(message, type);
            else if (typeof window.showNotification === 'function') window.showNotification(message, type);
            else alert(message);
        }
    };
}
</script>
