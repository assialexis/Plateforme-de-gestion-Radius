<?php $pageTitle = __('page.billing');
$currentPage = 'billing'; ?>

<div x-data="billingPage()" x-init="init()">
    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button @click="activeTab = 'invoices'"
                    :class="activeTab === 'invoices' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('billing.invoices')?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'payments'; loadPayments()"
                    :class="activeTab === 'payments' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <?= __('billing.payments')?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'notifications'; loadNotificationLogs()"
                    :class="activeTab === 'notifications' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <?= __('billing.notif_log_tab') ?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'settings'; loadSettings()"
                    :class="activeTab === 'settings' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <?= __('billing.settings')?>
                </button>
            </li>
        </ul>
    </div>

    <!-- Stats Cards -->
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
                        <?= __('billing.total_invoices')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="stats.total_invoices || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-red-500/10 dark:bg-red-500/5 rounded-full blur-2xl group-hover:bg-red-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('billing.unpaid')?>
                    </p>
                    <p class="text-3xl font-black text-red-600 dark:text-red-400" x-text="stats.unpaid_invoices || 0">
                    </p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500 to-red-600 shadow-[0_0_15px_rgba(239,68,68,0.3)] group-hover:shadow-[0_0_20px_rgba(239,68,68,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-orange-500/10 dark:bg-orange-500/5 rounded-full blur-2xl group-hover:bg-orange-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('billing.unpaid_amount')?>
                    </p>
                    <p class="text-3xl font-black text-orange-600 dark:text-orange-400"
                        x-text="formatCurrency(stats.total_unpaid || 0)"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-orange-400 to-orange-500 shadow-[0_0_15px_rgba(249,115,22,0.3)] group-hover:shadow-[0_0_20px_rgba(249,115,22,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-green-500/10 dark:bg-green-500/5 rounded-full blur-2xl group-hover:bg-green-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('billing.monthly_revenue')?>
                    </p>
                    <p class="text-3xl font-black text-green-600 dark:text-green-400"
                        x-text="formatCurrency(stats.monthly_revenue || 0)"></p>
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
    </div>

    <!-- Tab: Invoices -->
    <div x-show="activeTab === 'invoices'">
        <!-- Header avec actions -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-4 flex-wrap">
                <!-- Recherche -->
                <div class="relative">
                    <input type="text" x-model="search" @input.debounce.300ms="loadInvoices()"
                        placeholder="<?= __('billing.search')?>"
                        class="pl-10 pr-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Filtre statut -->
                <select x-model="statusFilter" @change="loadInvoices()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="">
                        <?= __('billing.all_statuses')?>
                    </option>
                    <option value="draft">
                        <?= __('billing.draft')?>
                    </option>
                    <option value="pending">
                        <?= __('billing.pending')?>
                    </option>
                    <option value="paid">
                        <?= __('billing.paid')?>
                    </option>
                    <option value="partial">
                        <?= __('billing.partial')?>
                    </option>
                    <option value="overdue">
                        <?= __('billing.overdue')?>
                    </option>
                    <option value="cancelled">
                        <?= __('billing.cancelled')?>
                    </option>
                </select>

                <!-- Période -->
                <select x-model="periodFilter" @change="loadInvoices()"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    <option value="">
                        <?= __('billing.all_periods')?>
                    </option>
                    <option value="today">
                        <?= __('billing.today')?>
                    </option>
                    <option value="week">
                        <?= __('billing.this_week')?>
                    </option>
                    <option value="month">
                        <?= __('billing.this_month')?>
                    </option>
                    <option value="year">
                        <?= __('billing.this_year')?>
                    </option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <!-- Export Options Invoices -->
                <div class="relative" x-data="{ openExportInfo: false }">
                    <button @click="openExportInfo = !openExportInfo" @click.away="openExportInfo = false"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <?= __('common.export') ?? 'Exporter'?>
                        <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExportInfo}" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div x-show="openExportInfo" x-transition.opacity x-cloak
                        class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                        <button @click="exportInvoices('csv'); openExportInfo = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export CSV
                        </button>
                        <button @click="exportInvoices('excel'); openExportInfo = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </button>
                        <button @click="exportInvoices('json'); openExportInfo = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            Export JSON
                        </button>
                        <button @click="exportInvoices('pdf'); openExportInfo = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                            <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Export PDF
                        </button>
                    </div>
                </div>

                <!-- Bouton créer -->
                <button @click="openCreateInvoiceModal()"
                    class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?= __('billing.new_invoice')?>
                </button>

                <!-- Bouton générer en lot -->
                <button @click="showBatchModal = true"
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <?= __('billing.batch_generate')?>
                </button>
            </div>
        </div>

        <!-- Tableau des factures -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.invoice_number')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.client')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.date')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.due_date')?>
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.amount')?>
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.paid_amount')?>
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.status')?>
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.actions')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loading">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center">
                                    <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loading && invoices.length === 0">
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <?= __('billing.no_invoice')?>
                                </td>
                            </tr>
                        </template>
                        <template x-for="invoice in invoices" :key="invoice.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                <td class="px-4 py-3">
                                    <span class="font-medium text-gray-900 dark:text-white"
                                        x-text="invoice.invoice_number"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="text-gray-900 dark:text-white"
                                            x-text="invoice.customer_name || invoice.username"></span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400"
                                            x-text="invoice.username"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300"
                                    x-text="formatDate(invoice.created_at)"></td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="isOverdue(invoice) ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-300'"
                                        x-text="formatDate(invoice.due_date)"></span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white"
                                    x-text="formatCurrency(invoice.total_amount)"></td>
                                <td class="px-4 py-3 text-right text-green-600 dark:text-green-400"
                                    x-text="formatCurrency(invoice.paid_amount)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span :class="getStatusClass(invoice.status)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                        x-text="getStatusLabel(invoice.status)"></span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="viewInvoice(invoice)"
                                            class="p-1 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                            title="<?= __('billing.view')?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button @click="printInvoice(invoice)"
                                            class="p-1 text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400"
                                            title="<?= __('billing.print')?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </button>
                                        <button x-show="invoice.status !== 'cancelled'"
                                            @click="openWhatsAppModal(invoice)"
                                            class="p-1 text-green-500 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300"
                                            title="<?= __('billing.send_whatsapp')?>">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                            </svg>
                                        </button>
                                        <button x-show="invoice.status !== 'paid' && invoice.status !== 'cancelled'"
                                            @click="openPaymentModal(invoice)"
                                            class="p-1 text-gray-500 hover:text-primary-600 dark:text-gray-400 dark:hover:text-primary-400"
                                            title="<?= __('billing.record_payment')?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </button>
                                        <button x-show="invoice.status === 'draft'" @click="editInvoice(invoice)"
                                            class="p-1 text-gray-500 hover:text-yellow-600 dark:text-gray-400 dark:hover:text-yellow-400"
                                            title="<?= __('billing.edit')?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button x-show="invoice.status !== 'cancelled' && invoice.status !== 'paid'"
                                            @click="cancelInvoice(invoice)"
                                            class="p-1 text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400"
                                            title="<?= __('billing.cancel_invoice')?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
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
            <div
                class="px-4 py-3 bg-gray-50 dark:bg-[#21262d]/50 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <?= __('common.page')?> <span x-text="pagination.current_page"></span> / <span
                        x-text="pagination.total_pages"></span>
                    (<span x-text="pagination.total"></span>
                    <?= __('billing.invoices')?>)
                </div>
                <div class="flex items-center gap-2">
                    <button @click="previousPage()" :disabled="pagination.current_page <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('common.previous')?>
                    </button>
                    <button @click="nextPage()" :disabled="pagination.current_page >= pagination.total_pages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('common.next')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Payments -->
    <div x-show="activeTab === 'payments'" x-cloak>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-4 mb-4">
            <!-- Export Options Payments -->
            <div class="relative" x-data="{ openExportPay: false }">
                <button @click="openExportPay = !openExportPay" @click.away="openExportPay = false"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('common.export') ?? 'Exporter'?>
                    <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExportPay}" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openExportPay" x-transition.opacity x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                    <button @click="exportPayments('csv'); openExportPay = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export CSV
                    </button>
                    <button @click="exportPayments('excel'); openExportPay = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export Excel
                    </button>
                    <button @click="exportPayments('json'); openExportPay = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                        </svg>
                        Export JSON
                    </button>
                    <button @click="exportPayments('pdf'); openExportPay = false"
                        class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Export PDF
                    </button>
                </div>
            </div>
        </div>
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_reference')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_invoice')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_client')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_date')?>
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_amount')?>
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_method')?>
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <?= __('billing.payment_notes')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loadingPayments">
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center">
                                    <svg class="animate-spin h-8 w-8 mx-auto text-primary-600" fill="none"
                                        viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loadingPayments && payments.length === 0">
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <?= __('billing.no_payment')?>
                                </td>
                            </tr>
                        </template>
                        <template x-for="payment in payments" :key="payment.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"
                                    x-text="payment.reference || '-'"></td>
                                <td class="px-4 py-3 text-primary-600 dark:text-primary-400"
                                    x-text="payment.invoice_number"></td>
                                <td class="px-4 py-3 text-gray-900 dark:text-white"
                                    x-text="payment.customer_name || payment.username"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300"
                                    x-text="formatDate(payment.payment_date)"></td>
                                <td class="px-4 py-3 text-right font-medium text-green-600 dark:text-green-400"
                                    x-text="formatCurrency(payment.amount)"></td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-[#21262d] text-gray-800 dark:text-gray-200 rounded-full"
                                        x-text="getPaymentMethodLabel(payment.payment_method)"></span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate"
                                    x-text="payment.notes || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab: Notification Logs -->
    <div x-show="activeTab === 'notifications'" x-cloak>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d]">
            <!-- Filters -->
            <div class="p-4 border-b border-gray-200 dark:border-[#30363d]">
                <div class="flex flex-wrap items-center gap-3">
                    <select x-model="notifFilter.channel" @change="loadNotificationLogs()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <option value=""><?= __('billing.notif_all_channels') ?></option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="sms">SMS</option>
                    </select>
                    <select x-model="notifFilter.status" @change="loadNotificationLogs()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        <option value=""><?= __('billing.notif_all_status') ?></option>
                        <option value="sent"><?= __('billing.notif_sent') ?></option>
                        <option value="failed"><?= __('billing.notif_failed') ?></option>
                    </select>
                    <input type="date" x-model="notifFilter.date_from" @change="loadNotificationLogs()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                        placeholder="<?= __('billing.notif_date_from') ?>">
                    <input type="date" x-model="notifFilter.date_to" @change="loadNotificationLogs()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                        placeholder="<?= __('billing.notif_date_to') ?>">
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-auto"
                        x-text="notifPagination.total + ' ' + (__('billing.notif_total_results') || 'résultat(s)')"></span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 dark:text-gray-400 uppercase bg-gray-50/50 dark:bg-[#161b22]">
                        <tr>
                            <th class="px-4 py-3"><?= __('billing.notif_date') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_client') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_phone') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_invoice') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_channel') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_status') ?></th>
                            <th class="px-4 py-3"><?= __('billing.notif_message') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loadingNotifLogs">
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="animate-spin h-6 w-6 mx-auto mb-2 text-primary-500" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    <?= __('common.loading') ?>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!loadingNotifLogs && notifLogs.length === 0">
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <?= __('billing.notif_no_logs') ?>
                                </td>
                            </tr>
                        </template>
                        <template x-for="log in notifLogs" :key="log.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1c2128]">
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap"
                                    x-text="formatDate(log.created_at)"></td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white"
                                    x-text="log.customer_name || '-'"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 font-mono text-xs"
                                    x-text="log.phone"></td>
                                <td class="px-4 py-3">
                                    <span class="text-primary-600 dark:text-primary-400 font-mono text-xs"
                                        x-text="log.invoice_number || '#' + log.invoice_id"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span x-show="log.channel === 'whatsapp'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                                        WhatsApp
                                    </span>
                                    <span x-show="log.channel === 'sms'"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                        SMS
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span x-show="log.status === 'sent'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                        <?= __('billing.notif_sent') ?>
                                    </span>
                                    <span x-show="log.status === 'failed'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"
                                        :title="log.error_message || ''">
                                        <?= __('billing.notif_failed') ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button @click="selectedNotifLog = log; showNotifMessageModal = true"
                                        class="text-primary-600 dark:text-primary-400 hover:underline text-xs">
                                        <?= __('billing.notif_view_message') ?>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="notifPagination.total_pages > 1"
                class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400"
                    x-text="'Page ' + notifPagination.current_page + ' / ' + notifPagination.total_pages"></p>
                <div class="flex gap-2">
                    <button @click="notifPagination.current_page--; loadNotificationLogs()"
                        :disabled="notifPagination.current_page <= 1"
                        class="px-3 py-1 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 text-gray-700 dark:text-gray-300">
                        &laquo; <?= __('common.previous') ?>
                    </button>
                    <button @click="notifPagination.current_page++; loadNotificationLogs()"
                        :disabled="notifPagination.current_page >= notifPagination.total_pages"
                        class="px-3 py-1 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 text-gray-700 dark:text-gray-300">
                        <?= __('common.next') ?> &raquo;
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal: View Message -->
        <div x-show="showNotifMessageModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showNotifMessageModal = false"></div>
                <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <?= __('billing.notif_message_detail') ?>
                        </h3>
                        <button @click="showNotifMessageModal = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-3">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-gray-500 dark:text-gray-400"><?= __('billing.notif_client') ?> :</span>
                            <span class="text-gray-900 dark:text-white" x-text="selectedNotifLog?.customer_name || '-'"></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-gray-500 dark:text-gray-400"><?= __('billing.notif_phone') ?> :</span>
                            <span class="text-gray-900 dark:text-white font-mono" x-text="selectedNotifLog?.phone"></span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-gray-500 dark:text-gray-400"><?= __('billing.notif_channel') ?> :</span>
                            <span x-show="selectedNotifLog?.channel === 'whatsapp'" class="text-green-600">WhatsApp</span>
                            <span x-show="selectedNotifLog?.channel === 'sms'" class="text-blue-600">SMS</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-gray-500 dark:text-gray-400"><?= __('billing.notif_date') ?> :</span>
                            <span class="text-gray-900 dark:text-white" x-text="formatDate(selectedNotifLog?.created_at)"></span>
                        </div>
                        <div x-show="selectedNotifLog?.status === 'failed'" class="text-sm">
                            <span class="font-medium text-red-500"><?= __('billing.notif_error') ?> :</span>
                            <span class="text-red-600 dark:text-red-400" x-text="selectedNotifLog?.error_message"></span>
                        </div>
                        <div class="mt-4 p-4 bg-gray-50 dark:bg-[#21262d] rounded-lg border border-gray-200 dark:border-[#30363d]">
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line"
                                x-text="selectedNotifLog?.message"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Settings -->
    <div x-show="activeTab === 'settings'" x-cloak>
        <!-- Payment Link -->
        <?php
        $paymentAdminId = isset($currentUser) ? $currentUser->getId() : null;
        if ($paymentAdminId):
            $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
            $paymentUrl = $baseUrl . $scriptDir . '/pppoe-pay.php?admin=' . $paymentAdminId;
        ?>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] p-5 mb-6"
            x-data="{ copied: false }">
            <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                        <?= __('billing.payment_link_title') ?>
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        <?= __('billing.payment_link_desc') ?>
                    </p>
                    <div class="flex items-center gap-2">
                        <div class="flex-1 min-w-0 px-3 py-2 bg-gray-50 dark:bg-[#21262d] border border-gray-200 dark:border-[#30363d] rounded-lg">
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-mono truncate"><?= htmlspecialchars($paymentUrl) ?></p>
                        </div>
                        <button type="button"
                            @click="navigator.clipboard.writeText('<?= htmlspecialchars($paymentUrl) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="flex-shrink-0 px-3 py-2 text-sm font-medium border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                            :class="copied ? 'text-green-600 border-green-300' : 'text-gray-700 dark:text-gray-300'">
                            <span x-show="!copied" class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                </svg>
                                <?= __('common.copy') ?>
                            </span>
                            <span x-show="copied" x-cloak class="flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?= __('common.copied') ?>
                            </span>
                        </button>
                        <a href="<?= htmlspecialchars($paymentUrl) ?>" target="_blank"
                            class="flex-shrink-0 px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            <?= __('common.open') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">
                <?= __('billing.settings_title')?>
            </h3>

            <form @submit.prevent="saveSettings()" class="space-y-6">
                <!-- Informations entreprise -->
                <div class="border-b border-gray-200 dark:border-[#30363d] pb-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                        <?= __('billing.company_info')?>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.company_name')?>
                            </label>
                            <input type="text" x-model="settings.company_name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.company_phone')?>
                            </label>
                            <input type="text" x-model="settings.company_phone"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.company_email')?>
                            </label>
                            <input type="email" x-model="settings.company_email"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.company_tax_number')?>
                            </label>
                            <input type="text" x-model="settings.company_tax_number"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.company_address')?>
                            </label>
                            <textarea x-model="settings.company_address" rows="2"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Portail Client -->
                <div class="border-b border-gray-200 dark:border-[#30363d] pb-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                        <svg class="w-5 h-5 inline mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <?= __('client_portal.portal_link') ?? 'Portail Client' ?>
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                        <?= __('client_portal.portal_link_desc') ?? 'Partagez ce lien avec vos clients PPPoE pour qu\'ils accèdent à leur espace client.' ?>
                    </p>
                    <div class="flex items-center gap-2">
                        <input type="text" readonly
                            :value="clientPortalUrl"
                            class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 font-mono">
                        <button type="button" @click="copyClientPortalLink()"
                            class="px-4 py-2 text-sm font-medium bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors whitespace-nowrap">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            <?= __('client_portal.copy_link') ?? 'Copier' ?>
                        </button>
                    </div>
                </div>

                <!-- Paramètres factures -->
                <div class="border-b border-gray-200 dark:border-[#30363d] pb-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                        <?= __('billing.invoice_settings')?>
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.invoice_prefix')?>
                            </label>
                            <input type="text" x-model="settings.invoice_prefix"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.payment_due_days')?>
                            </label>
                            <input type="number" x-model="settings.payment_due_days" min="0"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.currency')?>
                            </label>
                            <select x-model="settings.currency"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="XOF">XOF (Franc CFA)</option>
                                <option value="EUR">EUR (Euro)</option>
                                <option value="USD">USD (Dollar)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.tax_rate')?>
                            </label>
                            <input type="number" x-model="settings.default_tax_rate" min="0" max="100" step="0.01"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <!-- Notes par défaut -->
                <div class="border-b border-gray-200 dark:border-[#30363d] pb-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                        <?= __('billing.default_notes')?>
                    </h4>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.invoice_footer_notes')?>
                        </label>
                        <textarea x-model="settings.invoice_footer" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                            placeholder="Ex: Merci pour votre confiance. Paiement par Mobile Money accepté."></textarea>
                    </div>
                </div>

                <!-- Notifications de paiement -->
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">
                        <?= __('billing.payment_notif_title') ?>
                    </h4>

                    <!-- Toggle activation -->
                    <div class="flex items-center justify-between mb-4 p-3 bg-gray-50 dark:bg-[#21262d] rounded-lg">
                        <div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                <?= __('billing.payment_notif_enabled') ?>
                            </span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                <?= __('billing.payment_notif_enabled_help') ?>
                            </p>
                        </div>
                        <button type="button"
                            @click="settings.payment_notif_enabled = settings.payment_notif_enabled === '1' ? '0' : '1'"
                            :class="settings.payment_notif_enabled === '1' ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                            class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out">
                            <span :class="settings.payment_notif_enabled === '1' ? 'translate-x-5' : 'translate-x-0'"
                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out mt-0.5 ml-0.5"></span>
                        </button>
                    </div>

                    <!-- Options (visible si activé) -->
                    <div x-show="settings.payment_notif_enabled === '1'" class="space-y-4">
                        <!-- Canal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.payment_notif_channel') ?>
                            </label>
                            <div class="flex gap-3">
                                <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition-colors"
                                    :class="settings.payment_notif_channel === 'whatsapp' ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-300 dark:border-[#30363d]'">
                                    <input type="radio" x-model="settings.payment_notif_channel" value="whatsapp" class="sr-only">
                                    <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.932 1.396 5.608L.05 23.708a.5.5 0 00.612.612l5.994-1.31A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 01-5.332-1.543l-.382-.228-3.558.778.813-3.48-.253-.4A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">WhatsApp</span>
                                </label>
                                <label class="flex items-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition-colors"
                                    :class="settings.payment_notif_channel === 'sms' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-[#30363d]'">
                                    <input type="radio" x-model="settings.payment_notif_channel" value="sms" class="sr-only">
                                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">SMS</span>
                                </label>
                            </div>
                        </div>

                        <?php
                        $paymentVars = [
                            'customer_name', 'customer_phone', 'invoice_number', 'invoice_amount',
                            'invoice_due_date', 'invoice_description', 'profile_name', 'profile_price',
                            'username', 'expiration_date', 'company_name', 'support_phone', 'current_date'
                        ];
                        ?>

                        <!-- Template WhatsApp -->
                        <div x-show="settings.payment_notif_channel === 'whatsapp'" x-cloak>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                    <path d="M12 0C5.373 0 0 5.373 0 12c0 2.025.507 3.932 1.396 5.608L.05 23.708a.5.5 0 00.612.612l5.994-1.31A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.94 9.94 0 01-5.332-1.543l-.382-.228-3.558.778.813-3.48-.253-.4A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                                </svg>
                                <?= __('billing.payment_notif_template_whatsapp') ?>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                <?= __('billing.payment_notif_template_help') ?>
                            </p>
                            <textarea x-model="settings.payment_notif_template_whatsapp" rows="6"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm"
                                x-ref="tplWhatsapp"></textarea>
                            <div class="mt-2">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1.5">
                                    <?= __('billing.payment_notif_variables') ?>
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($paymentVars as $var): ?>
                                        <button type="button"
                                            @click="
                                                const ta = $refs.tplWhatsapp;
                                                const start = ta.selectionStart;
                                                const end = ta.selectionEnd;
                                                const text = settings.payment_notif_template_whatsapp || '';
                                                settings.payment_notif_template_whatsapp = text.substring(0, start) + '{{<?= $var ?>}}' + text.substring(end);
                                                $nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = start + '{{<?= $var ?>}}'.length; });
                                            "
                                            class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-[#30363d] text-gray-600 dark:text-gray-400 rounded hover:bg-green-100 hover:text-green-700 dark:hover:bg-green-900/30 dark:hover:text-green-400 transition-colors font-mono">
                                            {{<?= $var ?>}}
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <!-- Aperçu WhatsApp -->
                            <div x-show="settings.payment_notif_template_whatsapp" class="mt-3">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    <?= __('billing.payment_notif_preview') ?>
                                </label>
                                <div class="p-3 bg-[#e5ddd5] dark:bg-[#1a2e1a] rounded-lg border border-green-200 dark:border-green-900/50">
                                    <p class="text-sm text-gray-800 dark:text-green-100 whitespace-pre-line"
                                        x-text="previewTemplate(settings.payment_notif_template_whatsapp)"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Template SMS -->
                        <div x-show="settings.payment_notif_channel === 'sms'" x-cloak>
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                </svg>
                                <?= __('billing.payment_notif_template_sms') ?>
                            </label>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                <?= __('billing.payment_notif_template_sms_help') ?>
                            </p>
                            <textarea x-model="settings.payment_notif_template_sms" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono"
                                x-ref="tplSms"></textarea>
                            <div class="mt-1 flex items-center justify-between">
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($paymentVars as $var): ?>
                                        <button type="button"
                                            @click="
                                                const ta = $refs.tplSms;
                                                const start = ta.selectionStart;
                                                const end = ta.selectionEnd;
                                                const text = settings.payment_notif_template_sms || '';
                                                settings.payment_notif_template_sms = text.substring(0, start) + '{{<?= $var ?>}}' + text.substring(end);
                                                $nextTick(() => { ta.focus(); ta.selectionStart = ta.selectionEnd = start + '{{<?= $var ?>}}'.length; });
                                            "
                                            class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-[#30363d] text-gray-600 dark:text-gray-400 rounded hover:bg-blue-100 hover:text-blue-700 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors font-mono">
                                            {{<?= $var ?>}}
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <span class="text-xs text-gray-400 ml-2 flex-shrink-0"
                                    x-text="(settings.payment_notif_template_sms || '').length + ' car. · ' + ((l) => l <= 160 ? 1 : Math.ceil(l / 153))((settings.payment_notif_template_sms || '').length) + ' SMS'"></span>
                            </div>
                            <!-- Aperçu SMS -->
                            <div x-show="settings.payment_notif_template_sms" class="mt-3">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                    <?= __('billing.payment_notif_preview') ?>
                                </label>
                                <div class="p-3 bg-blue-50 dark:bg-[#1a1e2e] rounded-lg border border-blue-200 dark:border-blue-900/50">
                                    <p class="text-sm text-gray-800 dark:text-blue-100 whitespace-pre-line font-mono"
                                        x-text="previewTemplate(settings.payment_notif_template_sms)"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                        class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                        :disabled="savingSettings">
                        <span x-show="!savingSettings">
                            <?= __('billing.save_settings')?>
                        </span>
                        <span x-show="savingSettings" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <?= __('common.saving')?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Créer/Editer Facture -->
    <div x-show="showInvoiceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showInvoiceModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div
                    class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                        x-text="invoiceForm.id ? __('billing.edit_invoice') : __('billing.new_invoice')"></h3>
                    <button @click="showInvoiceModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveInvoice()" class="p-6 space-y-6">
                    <!-- Sélection client -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.pppoe_client')?> *
                        </label>
                        <select x-model="invoiceForm.pppoe_user_id" required @change="onClientChange()"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="">
                                <?= __('billing.select_client')?>
                            </option>
                            <template x-for="user in pppoeUsers" :key="user.id">
                                <option :value="user.id"
                                    x-text="user.customer_name ? user.customer_name + ' (' + user.username + ')' : user.username">
                                </option>
                            </template>
                        </select>
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.invoice_date')?>
                            </label>
                            <input type="date" x-model="invoiceForm.invoice_date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('billing.due_date')?>
                            </label>
                            <input type="date" x-model="invoiceForm.due_date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                    </div>

                    <!-- Lignes de facture -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <?= __('billing.invoice_lines')?>
                            </label>
                            <button type="button" @click="addInvoiceLine()"
                                class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                                +
                                <?= __('billing.add_line')?>
                            </button>
                        </div>
                        <div class="space-y-2">
                            <template x-for="(line, index) in invoiceForm.items" :key="index">
                                <div class="flex gap-2 items-start">
                                    <input type="text" x-model="line.description"
                                        placeholder="<?= __('common.description')?>" required
                                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                    <input type="number" x-model.number="line.quantity"
                                        placeholder="<?= __('billing.qty')?>" min="1" required
                                        @input="calculateLineTotal(index)"
                                        class="w-20 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                    <input type="number" x-model.number="line.unit_price"
                                        placeholder="<?= __('billing.price')?>" min="0" step="0.01" required
                                        @input="calculateLineTotal(index)"
                                        class="w-28 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                    <span
                                        class="w-28 px-3 py-2 bg-gray-100 dark:bg-[#21262d] rounded-lg text-sm text-gray-900 dark:text-white text-right"
                                        x-text="formatCurrency(line.total)"></span>
                                    <button type="button" @click="removeInvoiceLine(index)"
                                        x-show="invoiceForm.items.length > 1"
                                        class="p-2 text-red-500 hover:text-red-700">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Totaux -->
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('billing.subtotal')?>
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="formatCurrency(invoiceForm.subtotal)"></span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600 dark:text-gray-400">TVA (<span
                                    x-text="settings.default_tax_rate || 0"></span>%)</span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="formatCurrency(invoiceForm.tax_amount)"></span>
                        </div>
                        <div
                            class="flex justify-between items-center pt-2 border-t border-gray-200 dark:border-[#30363d]">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="text-lg font-bold text-primary-600 dark:text-primary-400"
                                x-text="formatCurrency(invoiceForm.total_amount)"></span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('common.notes')?>
                        </label>
                        <textarea x-model="invoiceForm.notes" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                            placeholder="<?= __('billing.optional_notes')?>"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showInvoiceModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50"
                            :disabled="saving">
                            <span x-show="!saving"
                                x-text="invoiceForm.id ? __('common.update') : __('billing.create_invoice')"></span>
                            <span x-show="saving">
                                <?= __('common.saving')?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Enregistrer Paiement -->
    <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPaymentModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('billing.record_payment')?>
                    </h3>
                    <button @click="showPaymentModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="savePayment()" class="p-6 space-y-4">
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('billing.payment_invoice')?>
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="selectedInvoice?.invoice_number"></span>
                        </div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('billing.total_amount')?>
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="formatCurrency(selectedInvoice?.total_amount)"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('billing.remaining_amount')?>
                            </span>
                            <span class="font-bold text-red-600 dark:text-red-400"
                                x-text="formatCurrency((selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0))"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.payment_amount_label')?> *
                        </label>
                        <input type="number" x-model.number="paymentForm.amount" min="0" step="0.01" required
                            :max="(selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0)"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.payment_method')?> *
                        </label>
                        <select x-model="paymentForm.payment_method" required
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="cash">
                                <?= __('billing.method_cash')?>
                            </option>
                            <option value="mobile_money">
                                <?= __('billing.method_mobile_money')?>
                            </option>
                            <option value="bank_transfer">
                                <?= __('billing.method_bank_transfer')?>
                            </option>
                            <option value="check">
                                <?= __('billing.method_check')?>
                            </option>
                            <option value="card">
                                <?= __('billing.method_card')?>
                            </option>
                            <option value="other">
                                <?= __('billing.method_other')?>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.payment_date')?>
                        </label>
                        <input type="date" x-model="paymentForm.payment_date"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.payment_reference')?>
                        </label>
                        <input type="text" x-model="paymentForm.reference"
                            placeholder="<?= __('billing.reference_placeholder')?>"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('common.notes')?>
                        </label>
                        <textarea x-model="paymentForm.notes" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showPaymentModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                            :disabled="savingPayment">
                            <span x-show="!savingPayment">
                                <?= __('billing.save_payment')?>
                            </span>
                            <span x-show="savingPayment">
                                <?= __('billing.saving_payment')?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Génération en lot -->
    <div x-show="showBatchModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showBatchModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('billing.batch_generate')?>
                    </h3>
                    <button @click="showBatchModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="generateBatchInvoices()" class="p-6 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <?= __('billing.batch_desc')?>
                    </p>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.billing_period')?>
                        </label>
                        <select x-model="batchForm.billing_period"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value="monthly">
                                <?= __('billing.monthly')?>
                            </option>
                            <option value="quarterly">
                                <?= __('billing.quarterly')?>
                            </option>
                            <option value="yearly">
                                <?= __('billing.yearly')?>
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.reference_month')?>
                        </label>
                        <input type="month" x-model="batchForm.reference_month"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" x-model="batchForm.only_active" id="only_active"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <label for="only_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            <?= __('billing.only_active_clients')?>
                        </label>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" x-model="batchForm.send_notifications" id="send_notifications"
                            class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <label for="send_notifications" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            <?= __('billing.send_notifications')?>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showBatchModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
                            :disabled="generatingBatch">
                            <span x-show="!generatingBatch">
                                <?= __('billing.generate_invoices')?>
                            </span>
                            <span x-show="generatingBatch">
                                <?= __('billing.generating')?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Aperçu Facture -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPreviewModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div
                    class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('billing.invoice_preview')?>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button @click="printInvoicePreview()"
                            class="px-3 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                            <?= __('billing.print')?>
                        </button>
                        <button @click="showPreviewModal = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div id="invoice-preview" x-html="invoicePreviewHtml"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Envoyer par WhatsApp -->
    <div x-show="showWhatsAppModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showWhatsAppModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                        </svg>
                        <?= __('billing.send_whatsapp')?>
                    </h3>
                    <button @click="showWhatsAppModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Résumé facture -->
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('billing.payment_invoice')?>
                            </span>
                            <span class="font-semibold text-gray-900 dark:text-white"
                                x-text="whatsAppForm.invoice?.invoice_number"></span>
                        </div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('billing.client')?>
                            </span>
                            <span class="text-gray-900 dark:text-white"
                                x-text="whatsAppForm.invoice?.customer_name || whatsAppForm.invoice?.username"></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('billing.amount')?>
                            </span>
                            <span class="font-bold text-primary-600 dark:text-primary-400"
                                x-text="formatCurrency(whatsAppForm.invoice?.total_amount)"></span>
                        </div>
                    </div>

                    <!-- Numéro WhatsApp -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('billing.whatsapp_number')?> *
                        </label>
                        <input type="tel" x-model="whatsAppForm.phone" required placeholder="229XXXXXXXX"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('billing.whatsapp_format_hint')?>
                        </p>
                    </div>

                    <!-- Option PDF -->
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                        <input type="checkbox" id="sendPdfCheck" x-model="whatsAppForm.send_pdf"
                            class="w-4 h-4 text-green-600 bg-gray-100 dark:bg-[#30363d] border-gray-300 dark:border-[#30363d] rounded focus:ring-green-500">
                        <label for="sendPdfCheck"
                            class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                            <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zM6 20V4h7v5h5v11H6zm2-6h8v2H8v-2zm0-3h8v2H8v-2z" />
                            </svg>
                            <?= __('billing.attach_pdf')?>
                        </label>
                    </div>

                    <!-- Message de succès/erreur -->
                    <div x-show="whatsAppResult" x-transition>
                        <div x-show="whatsAppResult === 'success'"
                            class="p-3 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span
                                    x-text="whatsAppForm.send_pdf ? __('billing.whatsapp_pdf_sent') : __('billing.whatsapp_sent')"></span>
                            </p>
                        </div>
                        <div x-show="whatsAppResult === 'error'"
                            class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-700 dark:text-red-300 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                <span x-text="whatsAppError"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showWhatsAppModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                            <?= __('common.close')?>
                        </button>
                        <button @click="sendInvoiceWhatsApp()"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2"
                            :disabled="sendingWhatsApp || !whatsAppForm.phone">
                            <svg x-show="sendingWhatsApp" class="animate-spin h-4 w-4 text-white" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="sendingWhatsApp ? __('billing.sending') : __('billing.send')"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function billingPage() {
        return {
            activeTab: 'invoices',
            loading: false,
            loadingPayments: false,
            saving: false,
            savingPayment: false,
            savingSettings: false,
            generatingBatch: false,

            // Data
            invoices: [],
            payments: [],
            pppoeUsers: [],
            stats: {},
            settings: {},

            // Client Portal
            clientPortalUrl: (() => {
                const base = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
                return base + '/client-login.php?admin=<?= $paymentAdminId ?? '' ?>';
            })(),

            // Filters
            search: '',
            statusFilter: '',
            periodFilter: '',

            // Pagination
            pagination: {
                current_page: 1,
                total_pages: 1,
                total: 0
            },

            // Modals
            showInvoiceModal: false,
            showPaymentModal: false,
            showBatchModal: false,
            showPreviewModal: false,
            showWhatsAppModal: false,

            // WhatsApp
            whatsAppForm: { invoice_id: null, phone: '', invoice: null, send_pdf: true },
            sendingWhatsApp: false,
            whatsAppResult: null,
            whatsAppError: '',

            // Forms
            invoiceForm: {
                id: null,
                pppoe_user_id: '',
                invoice_date: new Date().toISOString().split('T')[0],
                due_date: '',
                items: [{ description: '', quantity: 1, unit_price: 0, total: 0 }],
                subtotal: 0,
                tax_amount: 0,
                total_amount: 0,
                notes: ''
            },
            paymentForm: {
                invoice_id: null,
                amount: 0,
                payment_method: 'cash',
                payment_date: new Date().toISOString().split('T')[0],
                reference: '',
                notes: ''
            },
            batchForm: {
                billing_period: 'monthly',
                reference_month: new Date().toISOString().slice(0, 7),
                only_active: true,
                send_notifications: false
            },

            selectedInvoice: null,
            invoicePreviewHtml: '',

            // Notification logs
            notifLogs: [],
            loadingNotifLogs: false,
            notifFilter: { channel: '', status: '', date_from: '', date_to: '' },
            notifPagination: { current_page: 1, total_pages: 1, total: 0 },
            selectedNotifLog: null,
            showNotifMessageModal: false,

            async init() {
                await Promise.all([
                    this.loadInvoices(),
                    this.loadStats(),
                    this.loadPPPoEUsers(),
                    this.loadSettings()
                ]);

                // Set default due date based on settings
                this.setDefaultDueDate();
            },

            async loadInvoices() {
                this.loading = true;
                try {
                    const params = new URLSearchParams({
                        page: this.pagination.current_page,
                        search: this.search,
                        status: this.statusFilter,
                        period: this.periodFilter
                    });
                    const data = await API.get(`/billing/invoices?${params}`);
                    if (data.success) {
                        this.invoices = data.data.invoices || [];
                        this.pagination = data.data.pagination || this.pagination;
                    } else {
                        console.error('API error:', data);
                        this.showNotification(data.message || __('billing.msg_loading_error'), 'error');
                    }
                } catch (error) {
                    console.error('Error loading invoices:', error);
                    this.showNotification(__('billing.msg_loading_error') + ': ' + error.message, 'error');
                }
                this.loading = false;
            },

            async loadPayments() {
                this.loadingPayments = true;
                try {
                    const data = await API.get('/billing/payments');
                    if (data.success) {
                        this.payments = data.data.payments || [];
                    } else {
                        console.error('API error:', data);
                    }
                } catch (error) {
                    console.error('Error loading payments:', error);
                }
                this.loadingPayments = false;
            },

            async loadStats() {
                try {
                    const data = await API.get('/billing/stats');
                    if (data.success) {
                        this.stats = data.data || {};
                    }
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadPPPoEUsers() {
                try {
                    const data = await API.get('/pppoe/users?limit=1000');
                    if (data.success) {
                        this.pppoeUsers = data.data.users || [];
                    }
                } catch (error) {
                    console.error('Error loading PPPoE users:', error);
                }
            },

            async loadSettings() {
                try {
                    const data = await API.get('/billing/settings');
                    if (data.success) {
                        this.settings = data.data || {};
                        // Defaults for payment notifications
                        if (!this.settings.payment_notif_enabled) this.settings.payment_notif_enabled = '0';
                        if (!this.settings.payment_notif_channel) this.settings.payment_notif_channel = 'whatsapp';
                        // Migration: ancien template unique → deux templates séparés
                        if (this.settings.payment_notif_template && !this.settings.payment_notif_template_whatsapp) {
                            this.settings.payment_notif_template_whatsapp = this.settings.payment_notif_template;
                        }
                        if (this.settings.payment_notif_template && !this.settings.payment_notif_template_sms) {
                            this.settings.payment_notif_template_sms = this.settings.payment_notif_template;
                        }
                        if (!this.settings.payment_notif_template_whatsapp) this.settings.payment_notif_template_whatsapp = "\u2705 *Paiement re\u00e7u !*\n\nBonjour *{{customer_name}}* \ud83d\udc4b\n\nNous confirmons la r\u00e9ception de votre paiement de *{{invoice_amount}} " + APP_CURRENCY + "* pour la facture *{{invoice_number}}*.\n\n\ud83d\udcc5 Profil : {{profile_name}}\n\ud83d\udcc6 Valide jusqu'au : {{expiration_date}}\n\nMerci pour votre confiance ! \ud83d\ude4f\n\n{{company_name}}\n\ud83d\udcde {{support_phone}}";
                        if (!this.settings.payment_notif_template_sms) this.settings.payment_notif_template_sms = "Paiement de {{invoice_amount}} " + APP_CURRENCY + " recu pour la facture {{invoice_number}}. Profil: {{profile_name}}. Valide jusqu'au {{expiration_date}}. Merci - {{company_name}}";
                    }
                } catch (error) {
                    console.error('Error loading settings:', error);
                }
            },

            setDefaultDueDate() {
                const days = parseInt(this.settings.payment_due_days) || 30;
                const dueDate = new Date();
                dueDate.setDate(dueDate.getDate() + days);
                this.invoiceForm.due_date = dueDate.toISOString().split('T')[0];
            },

            openCreateInvoiceModal() {
                this.invoiceForm = {
                    id: null,
                    pppoe_user_id: '',
                    invoice_date: new Date().toISOString().split('T')[0],
                    due_date: '',
                    items: [{ description: __('billing.internet_subscription'), quantity: 1, unit_price: 0, total: 0 }],
                    subtotal: 0,
                    tax_amount: 0,
                    total_amount: 0,
                    notes: ''
                };
                this.setDefaultDueDate();
                this.showInvoiceModal = true;
            },

            editInvoice(invoice) {
                this.invoiceForm = {
                    id: invoice.id,
                    pppoe_user_id: invoice.pppoe_user_id,
                    invoice_date: invoice.invoice_date,
                    due_date: invoice.due_date,
                    items: invoice.items || [{ description: __('billing.internet_subscription'), quantity: 1, unit_price: invoice.total_amount, total: invoice.total_amount }],
                    subtotal: invoice.subtotal || invoice.total_amount,
                    tax_amount: invoice.tax_amount || 0,
                    total_amount: invoice.total_amount,
                    notes: invoice.notes || ''
                };
                this.showInvoiceModal = true;
            },

            onClientChange() {
                const user = this.pppoeUsers.find(u => u.id == this.invoiceForm.pppoe_user_id);
                if (user && user.profile_price) {
                    this.invoiceForm.items[0].unit_price = parseFloat(user.profile_price);
                    this.calculateLineTotal(0);
                }
            },

            addInvoiceLine() {
                this.invoiceForm.items.push({ description: '', quantity: 1, unit_price: 0, total: 0 });
            },

            removeInvoiceLine(index) {
                this.invoiceForm.items.splice(index, 1);
                this.calculateTotals();
            },

            calculateLineTotal(index) {
                const line = this.invoiceForm.items[index];
                line.total = (line.quantity || 0) * (line.unit_price || 0);
                this.calculateTotals();
            },

            calculateTotals() {
                this.invoiceForm.subtotal = this.invoiceForm.items.reduce((sum, line) => sum + (line.total || 0), 0);
                const taxRate = parseFloat(this.settings.default_tax_rate) || 0;
                this.invoiceForm.tax_amount = this.invoiceForm.subtotal * (taxRate / 100);
                this.invoiceForm.total_amount = this.invoiceForm.subtotal + this.invoiceForm.tax_amount;
            },

            async saveInvoice() {
                this.saving = true;
                try {
                    let data;
                    if (this.invoiceForm.id) {
                        data = await API.put(`/billing/invoices/${this.invoiceForm.id}`, this.invoiceForm);
                    } else {
                        data = await API.post('/billing/invoices', this.invoiceForm);
                    }

                    if (data.success) {
                        this.showNotification(this.invoiceForm.id ? __('billing.msg_invoice_updated') : __('billing.msg_invoice_created'), 'success');
                        this.showInvoiceModal = false;
                        await this.loadInvoices();
                        await this.loadStats();
                    } else {
                        this.showNotification(data.error || __('billing.msg_save_error'), 'error');
                    }
                } catch (error) {
                    console.error('Error saving invoice:', error);
                    this.showNotification(__('billing.msg_save_error'), 'error');
                }
                this.saving = false;
            },

            openPaymentModal(invoice) {
                this.selectedInvoice = invoice;
                this.paymentForm = {
                    invoice_id: invoice.id,
                    amount: invoice.total_amount - invoice.paid_amount,
                    payment_method: 'cash',
                    payment_date: new Date().toISOString().split('T')[0],
                    reference: '',
                    notes: ''
                };
                this.showPaymentModal = true;
            },

            async savePayment() {
                this.savingPayment = true;
                try {
                    const data = await API.post('/billing/payments', this.paymentForm);

                    if (data.success) {
                        this.showNotification(__('billing.msg_payment_recorded'), 'success');
                        this.showPaymentModal = false;
                        await this.loadInvoices();
                        await this.loadStats();
                        if (this.activeTab === 'payments') {
                            await this.loadPayments();
                        }
                    } else {
                        this.showNotification(data.error || __('billing.msg_save_error'), 'error');
                    }
                } catch (error) {
                    console.error('Error saving payment:', error);
                    this.showNotification(__('billing.msg_save_error'), 'error');
                }
                this.savingPayment = false;
            },

            async viewInvoice(invoice) {
                try {
                    const data = await API.get(`/billing/invoices/${invoice.id}/html`);
                    if (data.success) {
                        this.invoicePreviewHtml = data.data.html;
                        this.showPreviewModal = true;
                    }
                } catch (error) {
                    console.error('Error loading invoice preview:', error);
                }
            },

            async printInvoice(invoice) {
                try {
                    const data = await API.get(`/billing/invoices/${invoice.id}/html`);
                    if (data.success) {
                        const printWindow = window.open('', '_blank');
                        printWindow.document.write(data.data.html);
                        printWindow.document.close();
                        printWindow.print();
                    }
                } catch (error) {
                    console.error('Error printing invoice:', error);
                }
            },

            printInvoicePreview() {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(this.invoicePreviewHtml);
                printWindow.document.close();
                printWindow.print();
            },

            async cancelInvoice(invoice) {
                if (!confirm(__('billing.confirm_cancel_invoice'))) return;

                try {
                    const data = await API.post(`/billing/invoices/${invoice.id}/cancel`);
                    if (data.success) {
                        this.showNotification(__('billing.msg_invoice_cancelled'), 'success');
                        await this.loadInvoices();
                        await this.loadStats();
                    } else {
                        this.showNotification(data.error || __('common.error'), 'error');
                    }
                } catch (error) {
                    console.error('Error cancelling invoice:', error);
                }
            },

            async generateBatchInvoices() {
                this.generatingBatch = true;
                try {
                    const data = await API.post('/billing/invoices/batch', this.batchForm);

                    if (data.success) {
                        this.showNotification(__('billing.msg_batch_generated').replace(':count', data.data.generated), 'success');
                        this.showBatchModal = false;
                        await this.loadInvoices();
                        await this.loadStats();
                    } else {
                        this.showNotification(data.error || __('common.error'), 'error');
                    }
                } catch (error) {
                    console.error('Error generating batch:', error);
                    this.showNotification(__('billing.msg_generation_error'), 'error');
                }
                this.generatingBatch = false;
            },

            async copyClientPortalLink() {
                try {
                    await navigator.clipboard.writeText(this.clientPortalUrl);
                    this.showNotification('<?= __('client_portal.link_copied') ?? 'Lien copié !' ?>', 'success');
                } catch (e) {
                    // Fallback
                    const input = document.createElement('input');
                    input.value = this.clientPortalUrl;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                    this.showNotification('<?= __('client_portal.link_copied') ?? 'Lien copié !' ?>', 'success');
                }
            },

            async saveSettings() {
                this.savingSettings = true;
                try {
                    const data = await API.put('/billing/settings', this.settings);

                    if (data.success) {
                        this.showNotification(__('billing.msg_settings_saved'), 'success');
                    } else {
                        this.showNotification(data.error || __('common.error'), 'error');
                    }
                } catch (error) {
                    console.error('Error saving settings:', error);
                    this.showNotification(__('billing.msg_save_error'), 'error');
                }
                this.savingSettings = false;
            },

            async loadNotificationLogs() {
                this.loadingNotifLogs = true;
                try {
                    const params = new URLSearchParams({
                        page: this.notifPagination.current_page
                    });
                    if (this.notifFilter.channel) params.set('channel', this.notifFilter.channel);
                    if (this.notifFilter.status) params.set('status', this.notifFilter.status);
                    if (this.notifFilter.date_from) params.set('date_from', this.notifFilter.date_from);
                    if (this.notifFilter.date_to) params.set('date_to', this.notifFilter.date_to);

                    const data = await API.get('/billing/notification-logs?' + params.toString());
                    if (data.success) {
                        this.notifLogs = data.data.logs || [];
                        this.notifPagination = data.data.pagination || { current_page: 1, total_pages: 1, total: 0 };
                    }
                } catch (error) {
                    console.error('Error loading notification logs:', error);
                }
                this.loadingNotifLogs = false;
            },

            // Helpers
            previewTemplate(tpl) {
                return (tpl || '')
                    .replace(/\{\{customer_name\}\}/g, 'Jean Dupont')
                    .replace(/\{\{customer_phone\}\}/g, '+228 90 00 00 00')
                    .replace(/\{\{invoice_number\}\}/g, 'FAC-2026-00001')
                    .replace(/\{\{invoice_amount\}\}/g, '10 000')
                    .replace(/\{\{invoice_due_date\}\}/g, '28/02/2026')
                    .replace(/\{\{invoice_description\}\}/g, 'Abonnement mensuel')
                    .replace(/\{\{profile_name\}\}/g, 'Premium 20Mbps')
                    .replace(/\{\{profile_price\}\}/g, '10 000')
                    .replace(/\{\{username\}\}/g, 'jean.dupont')
                    .replace(/\{\{expiration_date\}\}/g, '31/03/2026')
                    .replace(/\{\{company_name\}\}/g, this.settings.company_name || 'Mon Entreprise')
                    .replace(/\{\{support_phone\}\}/g, this.settings.company_phone || '+228 00 00 00 00')
                    .replace(/\{\{current_date\}\}/g, new Date().toLocaleDateString('fr-FR'));
            },

            formatCurrency(amount) {
                const currency = this.settings.currency || 'XOF';
                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency }).format(amount || 0);
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                return new Date(dateStr).toLocaleDateString('fr-FR');
            },

            isOverdue(invoice) {
                if (invoice.status === 'paid' || invoice.status === 'cancelled') return false;
                return new Date(invoice.due_date) < new Date();
            },

            getStatusClass(status) {
                const classes = {
                    draft: 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300',
                    pending: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
                    paid: 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
                    partial: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
                    overdue: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
                    cancelled: 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-400'
                };
                return classes[status] || classes.draft;
            },

            getStatusLabel(status) {
                const labels = {
                    draft: __('billing.draft'),
                    pending: __('billing.pending'),
                    paid: __('billing.paid'),
                    partial: __('billing.partial'),
                    overdue: __('billing.overdue'),
                    cancelled: __('billing.cancelled')
                };
                return labels[status] || status;
            },

            getPaymentMethodLabel(method) {
                const labels = {
                    cash: __('billing.method_cash'),
                    mobile_money: __('billing.method_mobile_money'),
                    bank_transfer: __('billing.method_bank_transfer'),
                    check: __('billing.method_check'),
                    card: __('billing.method_card'),
                    other: __('billing.method_other')
                };
                return labels[method] || method;
            },

            previousPage() {
                if (this.pagination.current_page > 1) {
                    this.pagination.current_page--;
                    this.loadInvoices();
                }
            },

            nextPage() {
                if (this.pagination.current_page < this.pagination.total_pages) {
                    this.pagination.current_page++;
                    this.loadInvoices();
                }
            },

            openWhatsAppModal(invoice) {
                this.whatsAppForm = {
                    invoice_id: invoice.id,
                    phone: invoice.customer_phone || '',
                    invoice: invoice,
                    send_pdf: true
                };
                this.whatsAppResult = null;
                this.whatsAppError = '';
                this.showWhatsAppModal = true;
            },

            async sendInvoiceWhatsApp() {
                this.sendingWhatsApp = true;
                this.whatsAppResult = null;
                this.whatsAppError = '';

                try {
                    const data = await API.post(`/billing/invoices/${this.whatsAppForm.invoice_id}/send-whatsapp`, {
                        phone: this.whatsAppForm.phone,
                        send_pdf: this.whatsAppForm.send_pdf
                    });

                    if (data.success) {
                        this.whatsAppResult = 'success';
                    } else {
                        this.whatsAppResult = 'error';
                        this.whatsAppError = data.message || __('billing.msg_send_error');
                    }
                } catch (error) {
                    this.whatsAppResult = 'error';
                    this.whatsAppError = error.message || __('billing.msg_send_error');
                }

                this.sendingWhatsApp = false;
            },


            async exportInvoices(format) {
                try {
                    this.showNotification('Préparation de l\'export...', 'info');

                    const params = new URLSearchParams({
                        page: 1,
                        limit: 1000000,
                        search: this.search,
                        status: this.statusFilter,
                        period: this.periodFilter
                    });
                    const response = await API.get(`/billing/invoices?${params}`);
                    const data = response.data?.invoices || response.data?.data || response.data || [];

                    if (!data || data.length === 0) {
                        this.showNotification(__('billing.no_invoice') || 'Aucune donnée à exporter', 'error');
                        return;
                    }

                    const headers = [
                        __('billing.invoice_number') || 'N° Facture',
                        __('billing.client') || 'Client',
                        __('billing.date') || 'Date',
                        __('billing.due_date') || 'Date Echéance',
                        __('billing.amount') || 'Montant Total',
                        __('billing.paid_amount') || 'Montant Payé',
                        __('billing.status') || 'Statut'
                    ];

                    const rows = data.map(inv => {
                        return {
                            number: inv.invoice_number || '',
                            client: inv.customer_name || inv.username || '',
                            date: inv.invoice_date || inv.created_at || '',
                            due_date: inv.due_date || '',
                            amount: inv.total_amount || 0,
                            paid: inv.paid_amount || 0,
                            status: this.getStatusLabel(inv.status) || inv.status || ''
                        };
                    });

                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json' });
                        this.downloadFile(blob, 'factures.json');
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
                        this.downloadFile(blob, 'factures.csv');
                        return;
                    }

                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Factures");
                        XLSX.writeFile(wb, 'factures.xlsx');
                        return;
                    }

                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Factures', 14, 15);
                        const pdfRows = rows.map(r => Object.values(r));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('factures.pdf');
                        return;
                    }
                } catch (error) {
                    console.error('Erreur export factures:', error);
                    this.showNotification('Erreur lors de l\'export', 'error');
                }
            },

            async exportPayments(format) {
                try {
                    this.showNotification('Préparation de l\'export...', 'info');

                    const response = await API.get('/billing/payments?limit=1000000');
                    let data = [];
                    if (Array.isArray(response.data)) {
                        data = response.data;
                    } else {
                        data = response.data?.payments || response.data?.data || response.data || [];
                    }

                    if (!data || data.length === 0) {
                        this.showNotification(__('billing.no_payment') || 'Aucune donnée à exporter', 'error');
                        return;
                    }

                    const headers = [
                        __('billing.payment_reference') || 'Référence',
                        __('billing.payment_invoice') || 'Facture',
                        __('billing.payment_client') || 'Client',
                        __('billing.payment_date') || 'Date de Paiement',
                        __('billing.payment_amount') || 'Montant',
                        __('billing.payment_method') || 'Méthode',
                        __('billing.payment_notes') || 'Notes'
                    ];

                    const rows = data.map(pay => {
                        return {
                            reference: pay.reference || '-',
                            invoice: pay.invoice_number || '',
                            client: pay.customer_name || pay.username || '',
                            date: pay.payment_date || '',
                            amount: pay.amount || 0,
                            method: this.getPaymentMethodLabel(pay.payment_method) || pay.payment_method || '',
                            notes: pay.notes || '-'
                        };
                    });

                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], { type: 'application/json' });
                        this.downloadFile(blob, 'paiements.json');
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
                        this.downloadFile(blob, 'paiements.csv');
                        return;
                    }

                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Paiements");
                        XLSX.writeFile(wb, 'paiements.xlsx');
                        return;
                    }

                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Paiements', 14, 15);
                        const pdfRows = rows.map(r => Object.values(r));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('paiements.pdf');
                        return;
                    }
                } catch (error) {
                    console.error('Erreur export paiements:', error);
                    this.showNotification('Erreur lors de l\'export', 'error');
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

            showNotification(message, type = 'info') {
                if (type === 'error') {
                    alert(__('common.error') + ': ' + message);
                } else {
                    alert(message);
                }
            }
        };
    }
</script>