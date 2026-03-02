<?php $pageTitle = __('page.transactions'); $currentPage = 'transactions'; ?>

<div x-data="transactionsPage()" x-init="init()">
    <!-- Header avec stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('transaction.total_transactions') ?></p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('transaction.completed') ?></p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="stats.completed || 0"></p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('transaction.pending') ?></p>
                    <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" x-text="stats.pending || 0"></p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('transaction.total_revenue') ?></p>
                    <p class="text-2xl font-bold text-primary-600 dark:text-primary-400" x-text="formatPrice(stats.total_amount || 0)"></p>
                </div>
                <div class="w-12 h-12 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Appareils -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" x-show="deviceStats.total > 0" x-cloak>
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2"><?= __('transaction.device_types') ?></p>
            <div class="flex gap-3">
                <template x-for="item in deviceStats.types" :key="item.name">
                    <div class="flex items-center gap-1.5">
                        <span x-text="getDeviceIcon(item.name)"></span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="item.count"></span>
                        <span class="text-xs text-gray-400 capitalize" x-text="item.name"></span>
                    </div>
                </template>
            </div>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2"><?= __('transaction.browsers') ?></p>
            <div class="flex gap-2 flex-wrap">
                <template x-for="item in deviceStats.browsers" :key="item.name">
                    <span class="text-xs px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400">
                        <span x-text="item.name"></span>: <span x-text="item.count" class="font-semibold"></span>
                    </span>
                </template>
            </div>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2"><?= __('transaction.systems') ?></p>
            <div class="flex gap-2 flex-wrap">
                <template x-for="item in deviceStats.oses" :key="item.name">
                    <span class="text-xs px-2 py-1 rounded-full bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                        <span x-text="item.name"></span>: <span x-text="item.count" class="font-semibold"></span>
                    </span>
                </template>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4 flex-wrap">
            <!-- Recherche -->
            <div class="relative">
                <input type="text" x-model="search" @input.debounce.300ms="loadTransactions()"
                       placeholder="<?= __('transaction.search_placeholder') ?>"
                       class="pl-10 pr-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent w-64">
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Filtre statut -->
            <select x-model="statusFilter" @change="loadTransactions()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value=""><?= __('transaction.all_statuses') ?></option>
                <option value="pending"><?= __('transaction.status_pending') ?></option>
                <option value="completed"><?= __('transaction.status_completed') ?></option>
                <option value="failed"><?= __('transaction.status_failed') ?></option>
                <option value="refunded"><?= __('transaction.status_refunded') ?></option>
            </select>

            <!-- Filtre passerelle -->
            <select x-model="gatewayFilter" @change="loadTransactions()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value=""><?= __('transaction.all_gateways') ?></option>
                <option value="fedapay">FedaPay</option>
                <option value="cinetpay">CinetPay</option>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
            </select>
        </div>

        <div class="flex items-center gap-2">
            <!-- Export Options -->
            <div class="relative" x-data="{ openExport: false }">
                <button @click="openExport = !openExport" @click.away="openExport = false" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('transaction.export') ?? 'Exporter' ?>
                    <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExport}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <div x-show="openExport" x-transition.opacity x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                    <button @click="exportData('csv'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>
                    <button @click="exportData('excel'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </button>
                    <button @click="exportData('json'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Export JSON
                    </button>
                    <button @click="exportData('pdf'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </button>
                </div>
            </div>

            <!-- Rafraîchir -->
            <button @click="loadTransactions(); loadStats()"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('transaction.refresh') ?>
            </button>
        </div>
    </div>

    <!-- Table des transactions -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.id') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.operator_ref') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.client') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.profile') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.amount') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.gateway') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.status') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.device') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.date') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('transaction.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-if="transactions.length === 0 && !loading">
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p x-text="__('transaction.no_transaction')"></p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="txn in transactions" :key="txn.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <!-- ID -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-mono text-sm text-gray-900 dark:text-white" x-text="txn.transaction_id"></span>
                                    <button @click="copyToClipboard(txn.transaction_id)" class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" :title="__('common.copy')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <!-- Référence Opérateur -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="txn.operator_reference">
                                    <div class="flex items-center">
                                        <span class="font-mono text-sm text-purple-600 dark:text-purple-400" x-text="txn.operator_reference"></span>
                                        <button @click="copyToClipboard(txn.operator_reference)" class="ml-2 text-gray-400 hover:text-purple-600 dark:hover:text-purple-400" :title="__('common.copy')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!txn.operator_reference">
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                </template>
                            </td>
                            <!-- Client -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="txn.customer_phone || '-'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="txn.customer_email || ''"></p>
                                </div>
                            </td>
                            <!-- Profil -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400" x-text="txn.profile_name || '-'"></td>
                            <!-- Montant -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="formatPrice(txn.amount) + ' ' + txn.currency"></span>
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
                                      :class="getStatusClass(txn.status)"
                                      x-text="getStatusLabel(txn.status)"></span>
                            </td>
                            <!-- Appareil -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="txn.device_info">
                                    <div class="flex items-center gap-2">
                                        <span class="text-base" x-text="getDeviceIcon(txn.device_info.device_type)"></span>
                                        <div>
                                            <p class="text-sm text-gray-900 dark:text-white" x-text="(txn.device_info.browser || '') + ' / ' + (txn.device_info.os || '')"></p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="txn.device_info.screen_resolution || ''"></p>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!txn.device_info">
                                    <span class="text-sm text-gray-400 dark:text-gray-500">-</span>
                                </template>
                            </td>
                            <!-- Date -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="formatDate(txn.created_at)"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(txn.created_at)"></p>
                                </div>
                            </td>
                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="viewTransaction(txn)" class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400" :title="__('common.details')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <template x-if="txn.status === 'completed' && txn.voucher_code">
                                        <button @click="copyToClipboard(txn.voucher_code)" class="text-gray-400 hover:text-green-600 dark:hover:text-green-400" :title="__('transaction.copy_voucher')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                                            </svg>
                                        </button>
                                    </template>
                                    <template x-if="txn.status === 'pending'">
                                        <button @click="recheckTransaction(txn)" class="text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400" :title="__('transaction.recheck_payment')"
                                                :class="txn._rechecking ? 'animate-spin' : ''">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.showing_results', {from: transactions.length > 0 ? ((currentPage - 1) * perPage) + 1 : 0, to: Math.min(currentPage * perPage, totalItems), total: totalItems})">
            </p>
            <div class="flex gap-2">
                <button @click="currentPage--; loadTransactions()" :disabled="currentPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]" x-text="__('common.previous')">
                </button>
                <button @click="currentPage++; loadTransactions()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]" x-text="__('common.next')">
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div x-show="showDetailsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="__('transaction.details_title')">
                </h3>
                <div x-show="selectedTransaction" class="space-y-4">
                    <!-- ID et Statut -->
                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.id')"></p>
                            <p class="font-mono font-semibold text-gray-900 dark:text-white" x-text="selectedTransaction?.transaction_id"></p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                              :class="getStatusClass(selectedTransaction?.status)"
                              x-text="getStatusLabel(selectedTransaction?.status)"></span>
                    </div>

                    <!-- Informations client -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.client_phone')"></p>
                            <p class="font-medium text-gray-900 dark:text-white" x-text="selectedTransaction?.customer_phone || '-'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.client_email')"></p>
                            <p class="font-medium text-gray-900 dark:text-white" x-text="selectedTransaction?.customer_email || '-'"></p>
                        </div>
                    </div>

                    <!-- Paiement -->
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.amount')"></p>
                            <p class="font-semibold text-lg text-primary-600 dark:text-primary-400" x-text="formatPrice(selectedTransaction?.amount) + ' ' + (selectedTransaction?.currency || '')"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.gateway_label')"></p>
                            <p class="font-medium text-gray-900 dark:text-white">
                                <span x-text="getGatewayLabel(selectedTransaction?.gateway_code)"></span>
                                <span x-show="selectedTransaction?.is_platform == 1"
                                      class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400">Plateforme</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.profile')"></p>
                            <p class="font-medium text-gray-900 dark:text-white" x-text="selectedTransaction?.profile_name || '-'"></p>
                        </div>
                    </div>

                    <!-- Voucher (si complété) -->
                    <template x-if="selectedTransaction?.voucher_code">
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <p class="text-sm text-green-600 dark:text-green-400 mb-1" x-text="__('transaction.voucher_generated')"></p>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xl font-bold text-green-700 dark:text-green-300" x-text="selectedTransaction?.voucher_code"></span>
                                <button @click="copyToClipboard(selectedTransaction?.voucher_code)" class="text-green-600 hover:text-green-800 dark:text-green-400" :title="__('common.copy')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.created_at')"></p>
                            <p class="text-gray-900 dark:text-white" x-text="selectedTransaction?.created_at ? new Date(selectedTransaction.created_at).toLocaleString('fr-FR') : '-'"></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.paid_at')"></p>
                            <p class="text-gray-900 dark:text-white" x-text="selectedTransaction?.paid_at ? new Date(selectedTransaction.paid_at).toLocaleString('fr-FR') : '-'"></p>
                        </div>
                    </div>

                    <!-- Références -->
                    <div class="grid grid-cols-2 gap-4">
                        <template x-if="selectedTransaction?.operator_reference">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.operator_reference')"></p>
                                <div class="flex items-center gap-2">
                                    <p class="font-mono text-sm font-semibold text-purple-600 dark:text-purple-400" x-text="selectedTransaction?.operator_reference"></p>
                                    <button @click="copyToClipboard(selectedTransaction?.operator_reference)" class="text-purple-400 hover:text-purple-600" :title="__('common.copy')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="selectedTransaction?.gateway_transaction_id">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('transaction.gateway_id')"></p>
                                <p class="font-mono text-sm text-gray-600 dark:text-gray-400" x-text="selectedTransaction?.gateway_transaction_id"></p>
                            </div>
                        </template>
                    </div>

                    <!-- Appareil du client -->
                    <template x-if="selectedTransaction?.device_info">
                        <div class="p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span x-text="__('transaction.client_device')"></span>
                            </p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="__('common.type')"></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white flex items-center gap-1">
                                        <span x-text="getDeviceIcon(selectedTransaction.device_info.device_type)"></span>
                                        <span class="capitalize" x-text="selectedTransaction.device_info.device_type"></span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="__('transaction.browser')"></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedTransaction.device_info.browser"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="__('transaction.system')"></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedTransaction.device_info.os"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="__('transaction.screen')"></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedTransaction.device_info.screen_resolution"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="__('transaction.language')"></p>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="selectedTransaction.device_info.language"></p>
                                </div>
                            </div>
                            <details class="mt-3">
                                <summary class="text-xs text-gray-400 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300" x-text="__('transaction.full_user_agent')"></summary>
                                <p class="text-xs text-gray-500 mt-1 break-all font-mono bg-gray-100 dark:bg-[#161b22] p-2 rounded" x-text="selectedTransaction.device_info.user_agent"></p>
                            </details>
                        </div>
                    </template>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="showDetailsModal = false"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" x-text="__('common.close')">
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function transactionsPage() {
    return {
        transactions: [],
        stats: {},
        deviceStats: { total: 0, types: [], browsers: [], oses: [] },
        search: '',
        statusFilter: '',
        gatewayFilter: '',
        currentPage: 1,
        perPage: 20,
        totalItems: 0,
        totalPages: 1,
        loading: false,
        showDetailsModal: false,
        selectedTransaction: null,

        async init() {
            await Promise.all([
                this.loadStats(),
                this.loadTransactions()
            ]);
        },

        async loadStats() {
            try {
                const response = await API.get('/payments/transactions/stats');
                this.stats = response.data;
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async loadTransactions() {
            this.loading = true;
            try {
                let url = `/payments/transactions?page=${this.currentPage}&limit=${this.perPage}`;
                if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                if (this.statusFilter) url += `&status=${this.statusFilter}`;
                if (this.gatewayFilter) url += `&gateway_code=${this.gatewayFilter}`;

                const response = await API.get(url);

                // Handle both array and paginated response
                if (Array.isArray(response.data)) {
                    this.transactions = response.data;
                    this.totalItems = response.data.length;
                    this.totalPages = 1;
                } else {
                    this.transactions = response.data.data || response.data;
                    this.totalItems = response.data.total || this.transactions.length;
                    this.totalPages = response.data.total_pages || 1;
                }
                this.computeDeviceStats();
            } catch (error) {
                showToast(__('transaction.load_error'), 'error');
            } finally {
                this.loading = false;
            }
        },

        computeDeviceStats() {
            const types = {}, browsers = {}, oses = {};
            let total = 0;
            this.transactions.forEach(txn => {
                if (txn.device_info) {
                    total++;
                    const dt = txn.device_info.device_type || 'unknown';
                    const br = txn.device_info.browser || __('transaction.other');
                    const os = txn.device_info.os || __('transaction.other');
                    types[dt] = (types[dt] || 0) + 1;
                    browsers[br] = (browsers[br] || 0) + 1;
                    oses[os] = (oses[os] || 0) + 1;
                }
            });
            const toArr = obj => Object.entries(obj).map(([name, count]) => ({name, count})).sort((a, b) => b.count - a.count);
            this.deviceStats = { total, types: toArr(types), browsers: toArr(browsers), oses: toArr(oses) };
        },

        viewTransaction(txn) {
            this.selectedTransaction = txn;
            this.showDetailsModal = true;
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text);
            showToast(__('common.copied'));
        },

        async exportData(format) {
            try {
                showToast('Préparation de l\'export', 'info');
                
                let url = `/payments/transactions?page=1&limit=1000000`;
                if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                if (this.statusFilter) url += `&status=${this.statusFilter}`;
                if (this.gatewayFilter) url += `&gateway_code=${this.gatewayFilter}`;

                const response = await API.get(url);
                let data = [];
                if (Array.isArray(response.data)) {
                    data = response.data;
                } else {
                    data = response.data?.data || response.data || [];
                }
                
                if (!data || data.length === 0) {
                    showToast(__('transaction.no_transaction') || 'Aucune donnée à exporter', 'warning');
                    return;
                }

                const headers = [
                    'ID', 'Ref Operateur', 'Client', 'Email', 'Profil',
                    'Montant', 'Devise', 'Passerelle', 'Statut', 'Voucher',
                    'Appareil', 'OS', 'Navigateur', 'Ecran', 'Date'
                ];

                const rows = data.map(txn => {
                    const di = txn.device_info || {};
                    return {
                        id: txn.transaction_id || '',
                        ref: txn.operator_reference || '',
                        client: txn.customer_phone || '',
                        email: txn.customer_email || '',
                        profile: txn.profile_name || '',
                        amount: txn.amount || 0,
                        currency: txn.currency || '',
                        gateway: (txn.gateway_code || '') + (txn.is_platform == 1 ? ' [P]' : ''),
                        status: txn.status || '',
                        voucher: txn.voucher_code || '',
                        device: di.device_type || '',
                        os: di.os || '',
                        browser: di.browser || '',
                        screen: di.screen_resolution || '',
                        date: txn.created_at || ''
                    };
                });

                // JSON Export
                if (format === 'json') {
                    const blob = new Blob([JSON.stringify(rows, null, 2)], {type: 'application/json'});
                    this.downloadFile(blob, 'transactions.json');
                    return;
                }

                // CSV Export
                if (format === 'csv') {
                    const csvContent = [
                        headers.join(','),
                        ...rows.map(row => 
                            Object.values(row).map(v => 
                                `"${String(v).replace(/"/g, '""')}"`
                            ).join(',')
                        )
                    ].join('\n');
                    const blob = new Blob(['\uFEFF' + csvContent], {type: 'text/csv;charset=utf-8;'});
                    this.downloadFile(blob, 'transactions.csv');
                    return;
                }

                // Excel Export
                if (format === 'excel') {
                    await this.loadXLSX();
                    const ws = XLSX.utils.json_to_sheet(rows);
                    XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Transactions");
                    XLSX.writeFile(wb, 'transactions.xlsx');
                    return;
                }

                // PDF Export
                if (format === 'pdf') {
                    await this.loadJSPDF();
                    const doc = new window.jspdf.jsPDF('landscape');
                    doc.text('Transactions', 14, 15);
                    
                    const pdfRows = rows.map(r => Object.values(r));
                    doc.autoTable({
                        head: [headers],
                        body: pdfRows,
                        startY: 20,
                        styles: { fontSize: 8 },
                        headStyles: { fillColor: [41, 128, 185] }
                    });
                    doc.save('transactions.pdf');
                    return;
                }

            } catch (error) {
                console.error('Erreur export:', error);
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
                'refunded': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
            };
            return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': __('transaction.status_pending'),
                'completed': __('transaction.status_completed'),
                'failed': __('transaction.status_failed'),
                'refunded': __('transaction.status_refunded')
            };
            return labels[status] || status;
        },

        getGatewayClass(gateway) {
            const classes = {
                'fedapay': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                'cinetpay': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                'feexpay': 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-400',
                'paygate': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                'paygate_global': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                'ligdicash': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                'paydunya': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
                'kkiapay': 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
                'moneroo': 'bg-pink-100 text-pink-800 dark:bg-pink-900/30 dark:text-pink-400',
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
                'paygate': 'PayGate',
                'paygate_global': 'PayGate Global',
                'ligdicash': 'LigdiCash',
                'paydunya': 'PayDunya',
                'kkiapay': 'Kkiapay',
                'moneroo': 'Moneroo',
                'stripe': 'Stripe',
                'paypal': 'PayPal',
                'yengapay': 'YengaPay'
            };
            return labels[gateway] || gateway;
        },

        async recheckTransaction(txn) {
            if (txn._rechecking) return;
            txn._rechecking = true;
            try {
                const response = await API.post('/payments/check-status', {
                    transaction_id: txn.transaction_id
                });
                const newStatus = response.data?.status;
                if (newStatus === 'completed') {
                    showToast(__('transaction.payment_confirmed'), 'success');
                    await this.loadTransactions();
                    await this.loadStats();
                } else if (newStatus === 'failed') {
                    showToast(__('transaction.payment_failed'), 'error');
                    await this.loadTransactions();
                    await this.loadStats();
                } else {
                    showToast(__('transaction.still_pending'), 'warning');
                }
            } catch (error) {
                showToast(__('transaction.check_error') + ': ' + (error.message || __('common.error')), 'error');
            } finally {
                txn._rechecking = false;
            }
        },

        getDeviceIcon(deviceType) {
            const icons = { 'mobile': '\uD83D\uDCF1', 'tablet': '\uD83D\uDCBB', 'desktop': '\uD83D\uDDA5\uFE0F' };
            return icons[deviceType] || '\u2753';
        }
    }
}
</script>
