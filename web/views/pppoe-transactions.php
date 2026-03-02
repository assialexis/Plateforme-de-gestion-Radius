<?php $pageTitle = __('pppoe_trans.page_title');
$currentPage = 'pppoe-transactions'; ?>

<div x-data="pppoeTransactionsPage()" x-init="init()">
    <!-- Header avec stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_trans.total_transactions')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_trans.completed')?>
                    </p>
                    <p class="text-3xl font-black text-green-600 dark:text-green-400" x-text="stats.completed || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-400 to-green-500 shadow-[0_0_15px_rgba(34,197,94,0.3)] group-hover:shadow-[0_0_20px_rgba(34,197,94,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-yellow-500/10 dark:bg-yellow-500/5 rounded-full blur-2xl group-hover:bg-yellow-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_trans.pending')?>
                    </p>
                    <p class="text-3xl font-black text-yellow-600 dark:text-yellow-400" x-text="stats.pending || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-yellow-400 to-yellow-500 shadow-[0_0_15px_rgba(234,179,8,0.3)] group-hover:shadow-[0_0_20px_rgba(234,179,8,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-primary-500/10 dark:bg-primary-500/5 rounded-full blur-2xl group-hover:bg-primary-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_trans.total_revenue')?>
                    </p>
                    <p class="text-3xl font-black text-primary-600 dark:text-primary-400"
                        x-text="formatPrice(stats.total_amount || 0)"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 shadow-[0_0_15px_rgba(14,165,233,0.3)] group-hover:shadow-[0_0_20px_rgba(14,165,233,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Recherche -->
            <div class="relative">
                <input type="text" x-model="search" @input.debounce.300ms="loadTransactions()"
                    placeholder="<?= __('pppoe_trans.search_placeholder')?>"
                    class="pl-10 pr-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent w-64">
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Filtre statut -->
            <select x-model="statusFilter" @change="loadTransactions()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('pppoe_trans.all_statuses')?>
                </option>
                <option value="pending">
                    <?= __('pppoe_trans.status_pending')?>
                </option>
                <option value="completed">
                    <?= __('pppoe_trans.status_completed')?>
                </option>
                <option value="failed">
                    <?= __('pppoe_trans.status_failed')?>
                </option>
                <option value="cancelled">
                    <?= __('pppoe_trans.status_cancelled')?>
                </option>
            </select>

            <!-- Filtre passerelle -->
            <select x-model="gatewayFilter" @change="loadTransactions()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('pppoe_trans.all_gateways')?>
                </option>
                <option value="fedapay">FedaPay</option>
                <option value="cinetpay">CinetPay</option>
                <option value="feexpay">FeexPay</option>
                <option value="moneroo">Moneroo</option>
                <option value="kkiapay">Kkiapay</option>
                <option value="paygate">PayGate</option>
                <option value="paydunya">PayDunya</option>
                <option value="stripe">Stripe</option>
            </select>

            <!-- Filtre type -->
            <select x-model="typeFilter" @change="loadTransactions()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('pppoe_trans.all_types')?>
                </option>
                <option value="invoice">
                    <?= __('pppoe_trans.type_invoice')?>
                </option>
                <option value="extension">
                    <?= __('pppoe_trans.type_extension')?>
                </option>
                <option value="renewal">
                    <?= __('pppoe_trans.type_renewal')?>
                </option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <!-- Export Options -->
            <div class="relative" x-data="{ openExport: false }">
                <button @click="openExport = !openExport" @click.away="openExport = false"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('pppoe_trans.export') ?? __('common.export')?>
                    <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExport}" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openExport" x-transition.opacity x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                    <button @click="exportData('csv'); openExport = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                    <button @click="exportData('excel'); openExport = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </button>
                    <button @click="exportData('json'); openExport = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Export JSON
                    </button>
                    <button @click="exportData('pdf'); openExport = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Supprimer sélection -->
            <button x-show="selectedTransactions.length > 0" x-cloak
                @click="showDeleteConfirm = true"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <?= __('pppoe_trans.delete_selected') ?> (<span x-text="selectedTransactions.length"></span>)
            </button>

            <!-- Rafraîchir -->
            <button @click="loadTransactions(); loadStats()"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('pppoe_trans.refresh')?>
            </button>
        </div>
    </div>

    <!-- Table des transactions -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" @change="toggleAllTransactions($event)" :checked="transactions.length > 0 && selectedTransactions.length === transactions.length"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_transaction_id')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_client')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_type')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_amount')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_gateway')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_status')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_date')?>
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('pppoe_trans.th_actions')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-if="loading">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex items-center justify-center">
                                    <svg class="animate-spin h-8 w-8 text-primary-600"
                                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <template x-if="transactions.length === 0 && !loading">
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p>
                                    <?= __('pppoe_trans.no_transactions')?>
                                </p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="txn in transactions" :key="txn.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-3 py-4 w-10">
                                <input type="checkbox" :value="txn.id" x-model.number="selectedTransactions"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </td>
                            <!-- ID -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white"
                                        x-text="txn.transaction_id"></span>
                                    <button @click="copyToClipboard(txn.transaction_id)"
                                        class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        :title="__('pppoe_trans.copy')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <!-- Client -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="txn.customer_name || txn.username"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="txn.customer_phone || ''"></p>
                                </div>
                            </td>
                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getTypeClass(txn.payment_type)"
                                    x-text="getTypeLabel(txn.payment_type)"></span>
                            </td>
                            <!-- Montant -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="formatPrice(txn.amount) + ' ' + txn.currency"></span>
                            </td>
                            <!-- Passerelle -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getGatewayClass(txn.gateway_code)"
                                        x-text="getGatewayLabel(txn.gateway_code)"></span>
                                    <span x-show="txn.is_platform == 1"
                                          class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400"
                                          title="Passerelle plateforme">P</span>
                                </div>
                            </td>
                            <!-- Statut -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getStatusClass(txn.status)" x-text="getStatusLabel(txn.status)"></span>
                            </td>
                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white"
                                        x-text="formatDate(txn.created_at)"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatTime(txn.created_at)"></p>
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Détails -->
                                    <button @click="viewTransaction(txn)"
                                        class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                                        :title="__('pppoe_trans.details')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <!-- Retry callback (pour pending) -->
                                    <template x-if="txn.status === 'pending'">
                                        <button @click="retryCallback(txn)"
                                            class="text-yellow-500 hover:text-yellow-600 dark:text-yellow-400 dark:hover:text-yellow-300"
                                            :title="__('pppoe_trans.retry_callback')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </template>
                                    <!-- Imprimer facture (pour completed avec invoice_id) -->
                                    <template x-if="txn.status === 'completed' && txn.invoice_id">
                                        <button @click="printInvoice(txn.invoice_id)"
                                            class="text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
                                            :title="__('pppoe_trans.print_invoice')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <!-- Marquer comme complété manuellement -->
                                    <template x-if="txn.status === 'pending'">
                                        <button @click="markAsCompleted(txn)"
                                            class="text-green-500 hover:text-green-600 dark:text-green-400 dark:hover:text-green-300"
                                            :title="__('pppoe_trans.mark_completed')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <!-- Supprimer -->
                                    <button @click="deleteSingleTransaction(txn.id)"
                                        class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                        :title="__('pppoe_trans.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('pppoe_trans.showing')?> <span
                    x-text="transactions.length > 0 ? ((currentPage - 1) * perPage) + 1 : 0"></span>
                <?= __('pppoe_trans.to')?>
                <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
                <?= __('pppoe_trans.of')?>
                <span x-text="totalItems"></span>
                <?= __('pppoe_trans.results')?>
            </p>
            <div class="flex gap-2">
                <button @click="currentPage--; loadTransactions()" :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('pppoe_trans.previous')?>
                </button>
                <button @click="currentPage++; loadTransactions()" :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('pppoe_trans.next')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div x-show="showDetailsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('pppoe_trans.transaction_details')?>
                </h3>
                <div x-show="selectedTransaction" class="space-y-4">
                    <!-- ID et Statut -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.th_transaction_id')?>
                            </p>
                            <p class="font-mono font-semibold text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.transaction_id"></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                            :class="getStatusClass(selectedTransaction?.status)"
                            x-text="getStatusLabel(selectedTransaction?.status)"></span>
                    </div>

                    <!-- Informations client -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.th_client')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.customer_name || selectedTransaction?.username || '-'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.phone')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.customer_phone || '-'"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.email')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.customer_email || '-'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.payment_type')?>
                            </p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getTypeClass(selectedTransaction?.payment_type)"
                                x-text="getTypeLabel(selectedTransaction?.payment_type)"></span>
                        </div>
                    </div>

                    <!-- Paiement -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.th_amount')?>
                            </p>
                            <p class="font-semibold text-lg text-primary-600 dark:text-primary-400"
                                x-text="formatPrice(selectedTransaction?.amount) + ' ' + (selectedTransaction?.currency || 'XAF')">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.th_gateway')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                <span x-text="getGatewayLabel(selectedTransaction?.gateway_code)"></span>
                                <span x-show="selectedTransaction?.is_platform == 1"
                                      class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">Plateforme</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.invoice_id')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.invoice_id || '-'"></p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= __('pppoe_trans.description')?>
                        </p>
                        <p class="text-gray-900 dark:text-white" x-text="selectedTransaction?.description || '-'"></p>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.created_at')?>
                            </p>
                            <p class="text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.created_at ? new Date(selectedTransaction.created_at).toLocaleString('fr-FR') : '-'">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.paid_at')?>
                            </p>
                            <p class="text-gray-900 dark:text-white"
                                x-text="selectedTransaction?.completed_at ? new Date(selectedTransaction.completed_at).toLocaleString('fr-FR') : '-'">
                            </p>
                        </div>
                    </div>

                    <!-- Références gateway -->
                    <template x-if="selectedTransaction?.gateway_transaction_id">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('pppoe_trans.gateway_id')?>
                            </p>
                            <div class="flex items-center gap-2">
                                <p class="font-mono text-sm text-purple-600 dark:text-purple-400"
                                    x-text="selectedTransaction?.gateway_transaction_id"></p>
                                <button @click="copyToClipboard(selectedTransaction?.gateway_transaction_id)"
                                    class="text-purple-400 hover:text-purple-600" :title="__('pppoe_trans.copy')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Message d'erreur si échec -->
                    <template x-if="selectedTransaction?.error_message">
                        <div
                            class="p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                            <p class="text-sm text-red-600 dark:text-red-400 mb-1">
                                <?= __('pppoe_trans.error')?>
                            </p>
                            <p class="text-red-700 dark:text-red-300" x-text="selectedTransaction?.error_message"></p>
                        </div>
                    </template>
                </div>
                <div class="mt-6 flex justify-between">
                    <div class="flex gap-2">
                        <template x-if="selectedTransaction?.status === 'pending'">
                            <button @click="retryCallback(selectedTransaction); showDetailsModal = false"
                                class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                                <?= __('pppoe_trans.retry_callback')?>
                            </button>
                        </template>
                        <template x-if="selectedTransaction?.invoice_id">
                            <button @click="printInvoice(selectedTransaction.invoice_id)"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                <?= __('pppoe_trans.print_invoice')?>
                            </button>
                        </template>
                    </div>
                    <button @click="showDetailsModal = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('pppoe_trans.close')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Confirmation marquer complété -->
    <div x-show="showConfirmModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConfirmModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('pppoe_trans.confirm_manual_payment')?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    <?= __('pppoe_trans.confirm_manual_payment_desc')?>
                </p>
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg mb-4">
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">
                        <strong>
                            <?= __('pppoe_trans.transaction')?>:
                        </strong> <span x-text="transactionToComplete?.transaction_id"></span><br>
                        <strong>
                            <?= __('pppoe_trans.th_amount')?>:
                        </strong> <span
                            x-text="formatPrice(transactionToComplete?.amount) + ' ' + (transactionToComplete?.currency || 'XAF')"></span>
                    </p>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="showConfirmModal = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('pppoe_trans.cancel')?>
                    </button>
                    <button @click="confirmMarkAsCompleted()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <?= __('pppoe_trans.confirm')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal confirmation suppression -->
    <div x-show="showDeleteConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showDeleteConfirm = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-md p-6" @click.stop>
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= __('pppoe_trans.delete_confirm_title') ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="deleteTargetId ? '<?= __('pppoe_trans.delete_confirm_single') ?>' : '<?= __('pppoe_trans.delete_confirm_batch') ?>'.replace(':count', selectedTransactions.length)"></span>
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="showDeleteConfirm = false; deleteTargetId = null"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                    <?= __('common.cancel') ?>
                </button>
                <button @click="confirmDeleteTransaction()"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    <?= __('pppoe_trans.delete_confirm_btn') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function pppoeTransactionsPage() {
        return {
            transactions: [],
            stats: {},
            search: '',
            statusFilter: '',
            gatewayFilter: '',
            typeFilter: '',
            currentPage: 1,
            perPage: 20,
            totalItems: 0,
            totalPages: 1,
            loading: false,
            showDetailsModal: false,
            showConfirmModal: false,
            selectedTransaction: null,
            transactionToComplete: null,
            selectedTransactions: [],
            showDeleteConfirm: false,
            deleteTargetId: null,

            async init() {
                await Promise.all([
                    this.loadStats(),
                    this.loadTransactions()
                ]);
            },

            async loadStats() {
                try {
                    const response = await API.get('/pppoe/payments/stats');
                    this.stats = response.data;
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadTransactions() {
                this.loading = true;
                try {
                    let url = `/pppoe/payments/transactions?page=${this.currentPage}&limit=${this.perPage}`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.statusFilter) url += `&status=${this.statusFilter}`;
                    if (this.gatewayFilter) url += `&gateway_code=${this.gatewayFilter}`;
                    if (this.typeFilter) url += `&payment_type=${this.typeFilter}`;

                    const response = await API.get(url);

                    if (Array.isArray(response.data)) {
                        this.transactions = response.data;
                        this.totalItems = response.data.length;
                        this.totalPages = 1;
                    } else {
                        this.transactions = response.data.data || response.data;
                        this.totalItems = response.data.total || this.transactions.length;
                        this.totalPages = response.data.total_pages || 1;
                    }
                } catch (error) {
                    showToast(__('pppoe_trans.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            viewTransaction(txn) {
                this.selectedTransaction = txn;
                this.showDetailsModal = true;
            },

            toggleAllTransactions(event) {
                if (event.target.checked) {
                    this.selectedTransactions = this.transactions.map(t => t.id);
                } else {
                    this.selectedTransactions = [];
                }
            },

            deleteSingleTransaction(id) {
                this.deleteTargetId = id;
                this.showDeleteConfirm = true;
            },

            async confirmDeleteTransaction() {
                try {
                    if (this.deleteTargetId) {
                        await API.delete(`/pppoe/payments/transactions/${this.deleteTargetId}`);
                    } else {
                        await API.delete('/pppoe/payments/transactions/batch', { ids: this.selectedTransactions });
                    }
                    showToast('<?= __('pppoe_trans.delete_success') ?>', 'success');
                    this.selectedTransactions = [];
                    this.deleteTargetId = null;
                    this.showDeleteConfirm = false;
                    this.loadTransactions();
                    this.loadStats();
                } catch (error) {
                    showToast(error.message || '<?= __('pppoe_trans.delete_error') ?>', 'error');
                }
            },

            async retryCallback(txn) {
                try {
                    showToast(__('pppoe_trans.msg_retrying_callback'), 'info');
                    const response = await API.post(`/pppoe/payments/retry-callback`, {
                        transaction_id: txn.transaction_id
                    });
                    showToast(response.message || __('pppoe_trans.msg_callback_success'));
                    this.loadTransactions();
                    this.loadStats();
                } catch (error) {
                    showToast(__('pppoe_trans.msg_callback_error') + (error.message ? ': ' + error.message : ''), 'error');
                }
            },

            markAsCompleted(txn) {
                this.transactionToComplete = txn;
                this.showConfirmModal = true;
            },

            async confirmMarkAsCompleted() {
                if (!this.transactionToComplete) return;

                try {
                    const response = await API.post(`/pppoe/payments/mark-completed`, {
                        transaction_id: this.transactionToComplete.transaction_id
                    });
                    showToast(response.message || __('pppoe_trans.msg_marked_completed'));
                    this.showConfirmModal = false;
                    this.transactionToComplete = null;
                    this.loadTransactions();
                    this.loadStats();
                } catch (error) {
                    showToast(__('pppoe_trans.msg_complete_error') + (error.message ? ': ' + error.message : ''), 'error');
                }
            },

            async printInvoice(invoiceId) {
                try {
                    const data = await API.get(`/billing/invoices/${invoiceId}/html`);
                    if (data.success) {
                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(data.data.html);
                        printWindow.document.close();
                        printWindow.print();
                    }
                } catch (error) {
                    console.error('Error printing invoice:', error);
                    showToast(__('pppoe_trans.msg_print_error'), 'error');
                }
            },

            copyToClipboard(text) {
                navigator.clipboard.writeText(text);
                showToast(__('pppoe_trans.msg_copied'));
            },

            async exportData(format) {
                try {
                    showToast('Préparation de l\'export...', 'info');

                    let url = `/pppoe/payments/transactions?page=1&limit=1000000`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.statusFilter) url += `&status=${this.statusFilter}`;
                    if (this.gatewayFilter) url += `&gateway_code=${this.gatewayFilter}`;
                    if (this.typeFilter) url += `&payment_type=${this.typeFilter}`;

                    const response = await API.get(url);
                    let dataToExport = [];
                    if (Array.isArray(response.data)) {
                        dataToExport = response.data;
                    } else {
                        dataToExport = response.data?.data || response.data || [];
                    }

                    if (!dataToExport || dataToExport.length === 0) {
                        showToast('Aucune donnée à exporter', 'error');
                        return;
                    }

                    const headers = [
                        'ID',
                        'Client',
                        'Téléphone',
                        'Email',
                        'Type',
                        'Montant',
                        'Devise',
                        'Passerelle',
                        'Statut',
                        'Date Création'
                    ];

                    const rows = dataToExport.map(txn => {
                        return {
                            id: txn.transaction_id || '',
                            client: txn.customer_name || txn.username || '',
                            phone: txn.customer_phone || '',
                            email: txn.customer_email || '',
                            type: this.getTypeLabel(txn.payment_type) || txn.payment_type || '',
                            amount: txn.amount || 0,
                            currency: txn.currency || 'XAF',
                            gateway: (this.getGatewayLabel(txn.gateway_code) || txn.gateway_code || '') + (txn.is_platform == 1 ? ' [P]' : ''),
                            status: this.getStatusLabel(txn.status) || txn.status || '',
                            date: this.formatDate(txn.created_at) + ' ' + this.formatTime(txn.created_at)
                        };
                    });

                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json' });
                        this.downloadFile(blob, 'pppoe_transactions.json');
                        return;
                    }

                    if (format === 'csv') {
                        const csvContent = [
                            headers.join(','),
                            ...rows.map(row =>
                                Object.values(row).map(v =>
                                    `"${String(v).replace(/"/g, '""')}"`
                                ).join(',')
                            )
                        ].join('\n');
                        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
                        this.downloadFile(blob, 'pppoe_transactions.csv');
                        return;
                    }

                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Transactions");
                        XLSX.writeFile(wb, 'pppoe_transactions.xlsx');
                        return;
                    }

                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Transactions PPPoE', 14, 15);
                        const pdfRows = rows.map(r => Object.values(r));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('pppoe_transactions.pdf');
                        return;
                    }
                } catch (error) {
                    console.error('Erreur export transactions:', error);
                    showToast('Erreur lors de l\'export', 'error');
                }
            },

            downloadFile(blob, filename) {
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            },

            async loadXLSX() {
                if (typeof XLSX !== 'undefined') return;
                return new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            },

            async loadJSPDF() {
                if (window.jspdf && window.jspdf.jsPDF && typeof window.jspdf.jsPDF.prototype.autoTable === 'function') return;

                if (!window.jspdf) {
                    await new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }

                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.25/jspdf.plugin.autotable.min.js';
                    script.onload = resolve;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            },

            formatPrice(amount) {
                return new Intl.NumberFormat('fr-FR').format(amount || 0);
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                return new Date(dateStr).toLocaleDateString('fr-FR');
            },

            formatTime(dateStr) {
                if (!dateStr) return '';
                return new Date(dateStr).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            },

            getStatusClass(status) {
                const classes = {
                    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'completed': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'failed': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'cancelled': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300'
                };
                return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
            },

            getStatusLabel(status) {
                const labels = {
                    'pending': __('pppoe_trans.status_pending'),
                    'completed': __('pppoe_trans.status_completed'),
                    'failed': __('pppoe_trans.status_failed'),
                    'cancelled': __('pppoe_trans.status_cancelled')
                };
                return labels[status] || status;
            },

            getTypeClass(type) {
                const classes = {
                    'invoice': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'extension': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                    'renewal': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400'
                };
                return classes[type] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
            },

            getTypeLabel(type) {
                const labels = {
                    'invoice': __('pppoe_trans.type_invoice'),
                    'extension': __('pppoe_trans.type_extension'),
                    'renewal': __('pppoe_trans.type_renewal')
                };
                return labels[type] || type;
            },

            getGatewayClass(gateway) {
                const classes = {
                    'fedapay': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'cinetpay': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'feexpay': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
                    'moneroo': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
                    'kkiapay': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                    'paygate': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                    'paygate_global': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                    'ligdicash': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'paydunya': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
                    'stripe': 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400',
                    'paypal': 'bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-400',
                    'yengapay': 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400'
                };
                return classes[gateway] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
            },

            getGatewayLabel(gateway) {
                const labels = {
                    'fedapay': 'FedaPay',
                    'cinetpay': 'CinetPay',
                    'feexpay': 'FeexPay',
                    'moneroo': 'Moneroo',
                    'kkiapay': 'Kkiapay',
                    'paygate': 'PayGate',
                    'paygate_global': 'PayGate Global',
                    'ligdicash': 'LigdiCash',
                    'paydunya': 'PayDunya',
                    'stripe': 'Stripe',
                    'paypal': 'PayPal',
                    'yengapay': 'YengaPay'
                };
                return labels[gateway] || gateway;
            }
        }
    }
</script>