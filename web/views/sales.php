<?php $pageTitle = __('sales.title');
$currentPage = 'sales'; ?>

<div x-data="salesPage()" x-init="init()">
    <!-- Header avec stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Aujourd'hui -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/40 dark:from-[#1c2128] dark:to-blue-900/10 border border-blue-100 dark:border-blue-500/20 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p
                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        Aujourd'hui
                    </p>
                    <h3 class="text-3xl font-black text-blue-600 dark:text-blue-400 mt-2 tracking-tight"
                        x-text="formatPrice(stats.summary?.revenue_today || 0)"></h3>
                </div>
                <div
                    class="p-3 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-day fa-xl"></i>
                </div>
            </div>
            <div class="flex items-center mt-6">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-blue-100 dark:border-[#444c56] shadow-sm">
                    <i class="fas fa-ticket-alt text-blue-400 dark:text-blue-500 mr-2"></i>
                    <span x-text="(stats.summary?.tickets_today || 0) + ' tickets'"></span>
                </span>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-blue-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-chart-line text-8xl"></i>
            </div>
        </div>

        <!-- Ce mois-ci -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/40 dark:from-[#1c2128] dark:to-emerald-900/10 border border-emerald-100 dark:border-emerald-500/20 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p
                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        Ce mois-ci
                    </p>
                    <h3 class="text-3xl font-black text-emerald-600 dark:text-emerald-400 mt-2 tracking-tight"
                        x-text="formatPrice(stats.summary?.revenue_month || 0)"></h3>
                </div>
                <div
                    class="p-3 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-alt fa-xl"></i>
                </div>
            </div>
            <div class="flex items-center mt-6">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-emerald-100 dark:border-[#444c56] shadow-sm">
                    <i class="fas fa-ticket-alt text-emerald-400 dark:text-emerald-500 mr-2"></i>
                    <span x-text="(stats.summary?.tickets_month || 0) + ' tickets'"></span>
                </span>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-emerald-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-money-bill-wave text-8xl"></i>
            </div>
        </div>

        <!-- Cette année -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-white to-purple-50/40 dark:from-[#1c2128] dark:to-purple-900/10 border border-purple-100 dark:border-purple-500/20 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p
                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                        Cette année
                    </p>
                    <h3 class="text-3xl font-black text-purple-600 dark:text-purple-400 mt-2 tracking-tight"
                        x-text="formatPrice(stats.summary?.revenue_year || 0)"></h3>
                </div>
                <div
                    class="p-3 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                    <i class="fas fa-calendar-check fa-xl"></i>
                </div>
            </div>
            <div class="flex items-center mt-6">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-purple-100 dark:border-[#444c56] shadow-sm">
                    <i class="fas fa-ticket-alt text-purple-400 dark:text-purple-500 mr-2"></i>
                    <span x-text="(stats.summary?.tickets_year || 0) + ' tickets'"></span>
                </span>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-purple-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-crown text-8xl"></i>
            </div>
        </div>

        <!-- Période Sélectionnée (Global) -->
        <div
            class="relative overflow-hidden bg-gradient-to-br from-gray-50 to-gray-100/50 dark:from-[#1c2128] dark:to-[#21262d] border border-gray-200 dark:border-[#30363d] rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <p
                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">
                        <?= __('sales.selected_period') ?? 'Période Sélectionnée'?>
                    </p>
                    <h3 class="text-3xl font-black text-gray-800 dark:text-white mt-2 tracking-tight"
                        x-text="formatPrice(stats.total_amount || 0)"></h3>
                </div>
                <div
                    class="p-3 bg-gray-200 dark:bg-[#30363d] text-gray-600 dark:text-gray-300 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                    <i class="fas fa-filter fa-xl"></i>
                </div>
            </div>
            <div class="flex items-center mt-6">
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#444c56] shadow-sm">
                    <i class="fas fa-ticket-alt text-gray-400 dark:text-gray-500 mr-2"></i>
                    <span x-text="(stats.total_sales || 0) + ' tickets'"></span>
                </span>
            </div>
            <div
                class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-gray-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                <i class="fas fa-calculator text-8xl"></i>
            </div>
        </div>
    </div>

    <!-- Filtres globaux (date) + filtres specifiques ventes -->
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-6">
        <div class="flex items-end gap-4 flex-wrap">
            <!-- Date debut -->
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('sales.date_from')?>
                </label>
                <input type="date" x-model="filters.date_from" @change="onDateFilterChange()"
                    class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
            </div>

            <!-- Date fin -->
            <div>
                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                    <?= __('sales.date_to')?>
                </label>
                <input type="date" x-model="filters.date_to" @change="onDateFilterChange()"
                    class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
            </div>

            <!-- Filtres specifiques a l'onglet Ventes -->
            <template x-if="activeTab === 'sales'">
                <div class="flex items-end gap-4 flex-wrap">
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <?= __('sales.role_gerant') ?? 'Gérant'?>
                        </label>
                        <select x-model="filters.gerant_id" @change="loadSales()"
                            class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <option value="">
                                Tous
                            </option>
                            <template x-for="gerant in sellers.filter(s => s.role === 'gerant')" :key="gerant.id">
                                <option :value="gerant.id" x-text="gerant.full_name || gerant.username"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <?= __('sales.seller')?>
                        </label>
                        <select x-model="filters.seller_id" @change="loadSales()"
                            class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <option value="">
                                <?= __('sales.all')?>
                            </option>
                            <template x-for="seller in sellers.filter(s => s.role === 'vendeur')" :key="seller.id">
                                <option :value="seller.id" x-text="seller.full_name || seller.username"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <?= __('sales.zone')?>
                        </label>
                        <select x-model="filters.zone_id" @change="loadSales()"
                            class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <option value="">
                                <?= __('sales.all_zones')?>
                            </option>
                            <template x-for="zone in zones" :key="zone.id">
                                <option :value="zone.id" x-text="zone.name"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <?= __('sales.payment')?>
                        </label>
                        <select x-model="filters.payment_method" @change="loadSales()"
                            class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <option value="">
                                <?= __('sales.all_payments')?>
                            </option>
                            <option value="cash">
                                <?= __('sales.cash')?>
                            </option>
                            <option value="mobile_money">
                                <?= __('sales.mobile_money')?>
                            </option>
                            <option value="online">
                                <?= __('sales.online')?>
                            </option>
                            <option value="free">
                                <?= __('sales.free')?>
                            </option>
                        </select>
                    </div>
                </div>
            </template>
        </div>

        <div class="flex items-center gap-2">
            <div x-show="activeTab === 'sales'" class="relative" x-data="{ openExport: false }">
                <button @click="openExport = !openExport" @click.away="openExport = false" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('sales.export') ?? 'Exporter' ?>
                    <svg class="w-4 h-4 ml-2" :class="{'rotate-180': openExport}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition: transform 0.2s;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                
                <div x-show="openExport" x-transition.opacity x-cloak
                    class="absolute right-0 mt-2 w-48 bg-white dark:bg-[#161b22] rounded-xl shadow-lg border border-gray-200 dark:border-[#30363d] py-1 z-50">
                    <button @click="exportSales('csv'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export CSV
                    </button>
                    <button @click="exportSales('excel'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export Excel
                    </button>
                    <button @click="exportSales('json'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                        Export JSON
                    </button>
                    <button @click="exportSales('pdf'); openExport = false" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] flex items-center">
                        <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Export PDF
                    </button>
                </div>
            </div>
            <!-- Supprimer sélection -->
            <button x-show="activeTab === 'sales' && selectedSales.length > 0" x-cloak
                @click="showDeleteConfirm = true"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <?= __('sales.delete_selected') ?> (<span x-text="selectedSales.length"></span>)
            </button>
            <button @click="refreshCurrentTab()"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('sales.refresh')?>
            </button>
        </div>
    </div>

    <!-- Onglets -->
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-[#30363d]">
            <nav class="-mb-px flex space-x-4 overflow-x-auto">
                <button @click="activeTab = 'sales'"
                    :class="activeTab === 'sales' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_sales')?>
                </button>
                <button @click="activeTab = 'charts'; loadCharts()"
                    :class="activeTab === 'charts' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_stats')?>
                </button>
                <button @click="activeTab = 'by-profile'; loadByProfile()"
                    :class="activeTab === 'by-profile' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_by_profile')?>
                </button>
                <button @click="activeTab = 'by-seller'; loadBySeller()"
                    :class="activeTab === 'by-seller' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_by_seller')?>
                </button>
                <button @click="activeTab = 'by-gerant'; loadByGerant()"
                    :class="activeTab === 'by-gerant' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_by_gerant')?>
                </button>
                <button @click="activeTab = 'by-zone'; loadByZone()"
                    :class="activeTab === 'by-zone' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_by_zone')?>
                </button>
                <button @click="activeTab = 'by-nas'; loadByNas()"
                    :class="activeTab === 'by-nas' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_by_router')?>
                </button>
                <button @click="activeTab = 'commissions'; loadCommissions()"
                    :class="activeTab === 'commissions' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_commissions')?>
                </button>
                <button @click="activeTab = 'rates'; loadCommissionRates()"
                    :class="activeTab === 'rates' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    <?= __('sales.tab_rates')?>
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab: Ventes -->
    <div x-show="activeTab === 'sales'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-3 py-3 w-10">
                            <input type="checkbox" @change="toggleAllSales($event)" :checked="sales.length > 0 && selectedSales.length === sales.length"
                                class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.ticket')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.seller')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.profile')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.zone_nas')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Consommation
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.payment')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.amount')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.commissions')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.date')?>
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-if="sales.length === 0 && !loading">
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p>
                                    <?= __('sales.no_sales')?>
                                </p>
                            </td>
                        </tr>
                    </template>
                    <template x-for="sale in sales" :key="sale.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-3 py-4 w-10">
                                <input type="checkbox" :value="sale.id" x-model.number="selectedSales"
                                    class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm font-medium text-gray-900 dark:text-white"
                                    x-text="sale.ticket_code"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="sale.seller_name || sale.seller_username || '-'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="getRoleLabel(sale.seller_role)"></p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="sale.profile_name || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="sale.zone_name || '-'"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="sale.nas_name || ''">
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"
                                        x-text="formatDataBytes(sale.data_used)">
                                    </span>
                                    <span
                                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                        x-text="formatTimeDuration(sale.time_used)">
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getPaymentMethodClass(sale.payment_method)"
                                    x-text="getPaymentMethodLabel(sale.payment_method)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="formatPrice(sale.sale_amount || sale.profile_price)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-orange-600 dark:text-orange-400"
                                        x-text="formatPrice(sale.commission_vendeur)"></span>
                                    <span class="text-gray-300 dark:text-gray-500">/</span>
                                    <span class="text-xs text-emerald-600 dark:text-emerald-400"
                                        x-text="formatPrice(sale.commission_gerant)"></span>
                                    <span class="text-gray-300 dark:text-gray-500">/</span>
                                    <span class="text-xs text-purple-600 dark:text-purple-400"
                                        x-text="formatPrice(sale.commission_admin)"></span>
                                    <template x-if="sale.commission_paid">
                                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <p class="text-sm text-gray-900 dark:text-white" x-text="formatDate(sale.sold_at)">
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatTime(sale.sold_at)"></p>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-right">
                                <button @click="deleteSingleSale(sale.id)"
                                    class="text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                    :title="__('sales.delete')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <?= __('common.showing')?> <span
                    x-text="sales.length > 0 ? ((currentPage - 1) * perPage) + 1 : 0"></span>
                <?= __('common.to')?>
                <span x-text="Math.min(currentPage * perPage, totalItems)"></span>
                <?= __('common.of')?>
                <span x-text="totalItems"></span>
                <?= __('common.results')?>
            </p>
            <div class="flex gap-2">
                <button @click="currentPage--; loadSales()" :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.previous')?>
                </button>
                <button @click="currentPage++; loadSales()" :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.next')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Tab: Statistiques (Graphiques) -->
    <div x-show="activeTab === 'charts'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Ventes par jour -->
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('sales.daily_sales')?>
                </h3>
                <div class="h-72">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>

            <!-- Ventes par mois -->
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('sales.monthly_sales')?>
                </h3>
                <div class="h-72">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>

            <!-- Repartition par moyen de paiement -->
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('sales.payment_methods')?>
                </h3>
                <div class="h-72 flex items-center justify-center">
                    <div class="w-64 h-64">
                        <canvas id="paymentMethodChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Repartition par profil -->
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('sales.top_profiles')?>
                </h3>
                <div class="h-72 flex items-center justify-center">
                    <div class="w-64 h-64">
                        <canvas id="profileSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Par Profil -->
    <div x-show="activeTab === 'by-profile'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.profile')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.price')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.tickets')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>Cash</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-orange-500 mr-1"></span>Mobile</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span>Online</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.turnover')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.commissions')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider min-w-[140px]">
                            <?= __('sales.share')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="p in profileStats" :key="p.profile_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white font-bold text-sm"
                                        x-text="(p.profile_name || 'P')[0].toUpperCase()"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="p.profile_name"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="formatPrice(p.profile_price)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white"
                                    x-text="p.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400"
                                        x-text="p.cash_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(p.cash_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400"
                                        x-text="p.mobile_money_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(p.mobile_money_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400"
                                        x-text="p.online_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(p.online_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(p.total_sales)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-primary-600 dark:text-primary-400"
                                    x-text="formatPrice(p.total_commissions)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 dark:bg-[#21262d] rounded-full h-2">
                                        <div class="bg-indigo-500 h-2 rounded-full transition-all"
                                            :style="'width:' + getProfilePercent(p) + '%'"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="getProfilePercent(p) + '%'"></span>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Par Vendeur -->
    <div x-show="activeTab === 'by-seller'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.seller')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.role')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.tickets_sold')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.revenue')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.commission')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.supervisor')?>
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('common.actions')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="seller in sellerStats" :key="seller.user_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-400 to-orange-600 flex items-center justify-center text-white font-bold text-sm"
                                        x-text="(seller.full_name || seller.username || 'U')[0].toUpperCase()"></div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="seller.full_name || seller.username"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="seller.username">
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getRoleClass(seller.role)" x-text="getRoleLabel(seller.role)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white"
                                    x-text="seller.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(seller.total_sales)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400"
                                    x-text="formatPrice(seller.commission_earned)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="seller.parent_name || seller.parent_username || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button @click="viewSellerDetails(seller)"
                                    class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-sm">
                                    <?= __('sales.details')?>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Par Gérant -->
    <div x-show="activeTab === 'by-gerant'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.role_gerant')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.role')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.tickets_sold')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.revenue')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.commission')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.supervisor')?>
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('common.actions')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="gerant in gerantStats" :key="gerant.user_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white font-bold text-sm"
                                        x-text="(gerant.full_name || gerant.username || 'U')[0].toUpperCase()"></div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="gerant.full_name || gerant.username"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="gerant.username">
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getRoleClass(gerant.role)" x-text="getRoleLabel(gerant.role)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white"
                                    x-text="gerant.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(gerant.total_sales)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400"
                                    x-text="formatPrice(gerant.commission_earned)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="gerant.parent_name || gerant.parent_username || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button @click="viewSellerDetails(gerant)"
                                    class="text-primary-600 hover:text-primary-800 dark:text-primary-400 text-sm">
                                    <?= __('sales.details')?>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Par Zone -->
    <div x-show="activeTab === 'by-zone'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.zone')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.tickets_sold')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.revenue')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.total_commissions')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="zone in zoneStats" :key="zone.zone_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <span class="ml-3 text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="zone.zone_name || __('sales.unknown_zone')"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white"
                                    x-text="zone.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(zone.total_sales)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-primary-600 dark:text-primary-400"
                                    x-text="formatPrice(zone.total_sales)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Par NAS -->
    <div x-show="activeTab === 'by-nas'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.router')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.zone')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.total')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>Cash</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-orange-500 mr-1"></span>Mobile Money</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <span class="inline-flex items-center"><span
                                    class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span>Online</span>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.revenue')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="nas in nasStats" :key="nas.nas_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center text-white">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="nas.nas_name"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="nas.router_id"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="nas.zone_name || '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-semibold text-gray-900 dark:text-white"
                                    x-text="nas.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-green-600 dark:text-green-400"
                                        x-text="nas.cash_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(nas.cash_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-orange-600 dark:text-orange-400"
                                        x-text="nas.mobile_money_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(nas.mobile_money_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium text-blue-600 dark:text-blue-400"
                                        x-text="nas.online_count || 0"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400"
                                        x-text="formatPrice(nas.online_amount || 0)"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(nas.total_sales)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Commissions a payer -->
    <div x-show="activeTab === 'commissions'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <?= __('sales.pending_commissions')?>
            </h3>
            <button @click="markSelectedPaid()" x-show="selectedCommissions.length > 0"
                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <?= __('sales.mark_paid')?> (<span x-text="selectedCommissions.length"></span>)
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" @change="toggleAllCommissions($event)"
                                class="rounded border-gray-300 dark:border-[#30363d]">
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.beneficiary')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.role')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.tickets')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.total_sales')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('sales.commission_due')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="comm in commissions" :key="comm.user_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors">
                            <td class="px-6 py-4">
                                <input type="checkbox" :value="comm.user_id" x-model="selectedCommissions"
                                    class="rounded border-gray-300 dark:border-[#30363d]">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                                        :class="comm.role === 'vendeur' ? 'bg-gradient-to-br from-orange-400 to-orange-600' : (comm.role === 'gerant' ? 'bg-gradient-to-br from-emerald-400 to-emerald-600' : 'bg-gradient-to-br from-purple-400 to-purple-600')"
                                        x-text="(comm.full_name || comm.username || 'U')[0].toUpperCase()"></div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="comm.full_name || comm.username"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getRoleClass(comm.role)" x-text="getRoleLabel(comm.role)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-900 dark:text-white"
                                    x-text="comm.sales_count || 0"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white"
                                    x-text="formatPrice(comm.total_sales)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-lg font-bold text-green-600 dark:text-green-400"
                                    x-text="formatPrice(comm.pending_commission || comm.total_commission)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tab: Taux de commission -->
    <div x-show="activeTab === 'rates'"
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="p-4 border-b border-gray-200 dark:border-[#30363d]">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                <?= __('sales.rates_config')?>
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                <?= __('sales.rates_config_desc')?>
            </p>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <template x-for="rate in commissionRates" :key="rate.id">
                    <div class="border border-gray-200/60 dark:border-[#30363d] rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="font-semibold text-gray-900 dark:text-white" x-text="getRoleLabel(rate.role)">
                            </h4>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="getRoleClass(rate.role)"
                                x-text="rate.is_active ? __('sales.active') : __('sales.inactive')"></span>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">
                                    <?= __('sales.type')?>
                                </label>
                                <select x-model="rate.rate_type" @change="updateRate(rate)"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                    <option value="percentage">
                                        <?= __('sales.percentage')?>
                                    </option>
                                    <option value="fixed">
                                        <?= __('sales.fixed_amount')?>
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-500 dark:text-gray-400 mb-1">
                                    <?= __('sales.value')?>
                                </label>
                                <div class="relative">
                                    <input type="number" step="0.01" x-model="rate.rate_value"
                                        @change="updateRate(rate)"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white pr-10">
                                    <span class="absolute right-3 top-2 text-gray-500 dark:text-gray-400"
                                        x-text="rate.rate_type === 'percentage' ? '%' : 'FCFA'"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal Details Vendeur -->
    <div x-show="showSellerModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showSellerModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <?= __('sales.seller_details')?>: <span
                        x-text="selectedSeller?.full_name || selectedSeller?.username"></span>
                </h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-gray-900 dark:text-white"
                                x-text="selectedSeller?.sales_count || 0"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('sales.tickets_sold')?>
                            </p>
                        </div>
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400"
                                x-text="formatPrice(selectedSeller?.total_sales || 0)"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('sales.revenue')?>
                            </p>
                        </div>
                        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-orange-600 dark:text-orange-400"
                                x-text="formatPrice(selectedSeller?.commission_earned || 0)"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('sales.total_commission')?>
                            </p>
                        </div>
                    </div>

                    <!-- Résumé global de vente par période -->
                    <template x-if="sellerDetailsData">
                        <div class="space-y-6 mt-6">

                            <!-- Section: Résumé des Ventes -->
                            <div>
                                <h4
                                    class="text-sm border-b border-gray-100 dark:border-[#30363d] pb-2 font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                                    Résumé des ventes
                                </h4>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                    <div
                                        class="relative overflow-hidden bg-gradient-to-br from-white to-blue-50/40 dark:from-[#1c2128] dark:to-blue-900/10 border border-blue-100 dark:border-blue-500/20 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                                    Aujourd'hui</p>
                                                <h3 class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-1.5 tracking-tight"
                                                    x-text="formatPrice(sellerDetailsData.summary.revenue_today)"></h3>
                                            </div>
                                            <div
                                                class="p-2.5 bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                                                <i class="fas fa-calendar-day fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="flex items-center mt-5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-blue-100 dark:border-[#444c56] shadow-sm">
                                                <i
                                                    class="fas fa-ticket-alt text-blue-400 dark:text-blue-500 mr-1.5"></i>
                                                <span
                                                    x-text="sellerDetailsData.summary.tickets_today + ' tickets'"></span>
                                            </span>
                                        </div>
                                        <div
                                            class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-blue-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                                            <i class="fas fa-chart-line text-8xl"></i>
                                        </div>
                                    </div>

                                    <div
                                        class="relative overflow-hidden bg-gradient-to-br from-white to-emerald-50/40 dark:from-[#1c2128] dark:to-emerald-900/10 border border-emerald-100 dark:border-emerald-500/20 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                                                    Ce mois-ci</p>
                                                <h3 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1.5 tracking-tight"
                                                    x-text="formatPrice(sellerDetailsData.summary.revenue_month)"></h3>
                                            </div>
                                            <div
                                                class="p-2.5 bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                                                <i class="fas fa-calendar-alt fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="flex items-center mt-5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-emerald-100 dark:border-[#444c56] shadow-sm">
                                                <i
                                                    class="fas fa-ticket-alt text-emerald-400 dark:text-emerald-500 mr-1.5"></i>
                                                <span
                                                    x-text="sellerDetailsData.summary.tickets_month + ' tickets'"></span>
                                            </span>
                                        </div>
                                        <div
                                            class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-emerald-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                                            <i class="fas fa-money-bill-wave text-8xl"></i>
                                        </div>
                                    </div>

                                    <div
                                        class="relative overflow-hidden bg-gradient-to-br from-white to-purple-50/40 dark:from-[#1c2128] dark:to-purple-900/10 border border-purple-100 dark:border-purple-500/20 rounded-2xl p-5 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300 group">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <p
                                                    class="text-sm font-semibold text-gray-500 dark:text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                                                    Cette année</p>
                                                <h3 class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1.5 tracking-tight"
                                                    x-text="formatPrice(sellerDetailsData.summary.revenue_year)"></h3>
                                            </div>
                                            <div
                                                class="p-2.5 bg-purple-100 dark:bg-purple-500/20 text-purple-600 dark:text-purple-400 rounded-xl shadow-inner group-hover:scale-110 transition-transform">
                                                <i class="fas fa-calendar-check fa-lg"></i>
                                            </div>
                                        </div>
                                        <div class="flex items-center mt-5">
                                            <span
                                                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-white dark:bg-[#2d333b] text-gray-700 dark:text-gray-300 border border-purple-100 dark:border-[#444c56] shadow-sm">
                                                <i
                                                    class="fas fa-ticket-alt text-purple-400 dark:text-purple-500 mr-1.5"></i>
                                                <span
                                                    x-text="sellerDetailsData.summary.tickets_year + ' tickets'"></span>
                                            </span>
                                        </div>
                                        <div
                                            class="absolute -right-4 -bottom-4 opacity-5 dark:opacity-[0.03] text-purple-600 pointer-events-none transform -rotate-12 group-hover:rotate-0 transition-transform duration-500">
                                            <i class="fas fa-crown text-8xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section: Historique ou Gérant Vendeurs -->
                            <div>
                                <h4
                                    class="text-sm border-b border-gray-100 dark:border-[#30363d] pb-2 font-semibold text-gray-900 dark:text-white uppercase tracking-wider mb-4">
                                    <template x-if="sellerDetailsData.seller.role === 'gerant'">
                                        <span>Vendeurs de la zone (Performances globales)</span>
                                    </template>
                                    <template x-if="sellerDetailsData.seller.role !== 'gerant'">
                                        <span>Ventes sur la période sélectionnée</span>
                                    </template>
                                </h4>

                                <template x-if="sellerDetailsData.seller.role !== 'gerant'">
                                    <div
                                        class="overflow-y-auto max-h-[250px] custom-scrollbar border border-gray-100 dark:border-[#30363d] rounded-xl bg-white dark:bg-[#161b22] shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] dark:shadow-none">
                                        <table class="w-full text-left border-collapse">
                                            <thead
                                                class="bg-gray-50/90 dark:bg-[#21262d]/90 sticky top-0 backdrop-blur-md z-10">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d]">
                                                        Ticket</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d]">
                                                        Profil</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d]">
                                                        Date</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d] text-right">
                                                        Montant</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-[#30363d]">
                                                <template x-for="sale in sellerDetailsData.recent_sales" :key="sale.id">
                                                    <tr
                                                        class="hover:bg-blue-50/50 dark:hover:bg-[#30363d]/50 transition-colors group">
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <div
                                                                    class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#21262d] text-gray-500 dark:text-gray-400 flex items-center justify-center text-[11px] mr-3 group-hover:bg-blue-100 group-hover:text-blue-600 dark:group-hover:bg-blue-900/30 dark:group-hover:text-blue-400 transition-colors">
                                                                    <i class="fas fa-ticket-alt"></i>
                                                                </div>
                                                                <span
                                                                    class="font-medium text-sm text-gray-900 dark:text-white"
                                                                    x-text="sale.ticket_code"></span>
                                                            </div>
                                                        </td>
                                                        <td
                                                            class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                            <span
                                                                class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-medium bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#30363d]"
                                                                x-text="sale.profile_name"></span>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                                            x-text="formatDate(sale.sold_at)"></td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400 text-right"
                                                            x-text="formatPrice(sale.sale_amount)"></td>
                                                    </tr>
                                                </template>
                                                <template x-if="sellerDetailsData.recent_sales.length === 0">
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <div
                                                                class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-50 dark:bg-[#21262d] mb-3">
                                                                <i
                                                                    class="fas fa-inbox text-gray-300 dark:text-gray-600 text-2xl"></i>
                                                            </div>
                                                            <p
                                                                class="text-sm font-medium text-gray-900 dark:text-white">
                                                                Aucune vente enregistrée.</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Il
                                                                n'y a pas de ventes pour ce vendeur sur la période
                                                                sélectionnée.</p>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>

                                <template x-if="sellerDetailsData.seller.role === 'gerant'">
                                    <div
                                        class="overflow-y-auto max-h-[250px] custom-scrollbar border border-gray-100 dark:border-[#30363d] rounded-xl bg-white dark:bg-[#161b22] shadow-[0_2px_10px_-3px_rgba(6,81,237,0.1)] dark:shadow-none">
                                        <table class="w-full text-left border-collapse">
                                            <thead
                                                class="bg-gray-50/90 dark:bg-[#21262d]/90 sticky top-0 backdrop-blur-md z-10">
                                                <tr>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d]">
                                                        Vendeur</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d] text-right">
                                                        Aujourd'hui</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d] text-right">
                                                        Ce mois-ci</th>
                                                    <th
                                                        class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider border-b border-gray-100 dark:border-[#30363d] text-right">
                                                        Cette année</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50 dark:divide-[#30363d]">
                                                <template x-for="vendeur in sellerDetailsData.gerant_sellers"
                                                    :key="vendeur.id">
                                                    <tr
                                                        class="hover:bg-blue-50/50 dark:hover:bg-[#30363d]/50 transition-colors group">
                                                        <td class="px-4 py-3 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <div
                                                                    class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-[#21262d] text-indigo-500 dark:text-indigo-400 flex items-center justify-center text-[11px] mr-3 group-hover:bg-indigo-100 group-hover:text-indigo-600 dark:group-hover:bg-indigo-900/40 dark:group-hover:text-indigo-300 transition-colors">
                                                                    <i class="fas fa-user-tie"></i>
                                                                </div>
                                                                <span
                                                                    class="font-medium text-sm text-gray-900 dark:text-white"
                                                                    x-text="vendeur.full_name || vendeur.username"></span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                                            <div class="font-bold text-gray-900 dark:text-white"
                                                                x-text="formatPrice(vendeur.revenue_today)"></div>
                                                            <div
                                                                class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                                <span x-text="vendeur.tickets_today"></span> ticket(s)
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                                            <div class="font-bold text-gray-900 dark:text-white"
                                                                x-text="formatPrice(vendeur.revenue_month)"></div>
                                                            <div
                                                                class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                                <span x-text="vendeur.tickets_month"></span> ticket(s)
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-3 whitespace-nowrap text-right">
                                                            <div class="font-bold text-gray-900 dark:text-white"
                                                                x-text="formatPrice(vendeur.revenue_year)"></div>
                                                            <div
                                                                class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                                                <span x-text="vendeur.tickets_year"></span> ticket(s)
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                                <template
                                                    x-if="!sellerDetailsData.gerant_sellers || sellerDetailsData.gerant_sellers.length === 0">
                                                    <tr>
                                                        <td colspan="4" class="px-4 py-10 text-center">
                                                            <div
                                                                class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gray-50 dark:bg-[#21262d] mb-3">
                                                                <i
                                                                    class="fas fa-users text-gray-300 dark:text-gray-600 text-2xl"></i>
                                                            </div>
                                                            <p
                                                                class="text-sm font-medium text-gray-900 dark:text-white">
                                                                Aucun vendeur.</p>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Il
                                                                n'y a pas de performances vendeurs pour ce gérant.</p>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                    <template x-if="!sellerDetailsData">
                        <div class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-gray-400 text-2xl"></i>
                        </div>
                    </template>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="showSellerModal = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('common.close')?>
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
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= __('sales.delete_confirm_title') ?></h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="deleteTargetId ? '<?= __('sales.delete_confirm_single') ?>' : '<?= __('sales.delete_confirm_batch') ?>'.replace(':count', selectedSales.length)"></span>
                    </p>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button @click="showDeleteConfirm = false; deleteTargetId = null"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                    <?= __('common.cancel') ?>
                </button>
                <button @click="confirmDelete()"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                    <?= __('sales.delete_confirm_btn') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function salesPage() {
        return {
            activeTab: 'sales',
            sales: [],
            sellerStats: [],
            gerantStats: [],
            zoneStats: [],
            nasStats: [],
            profileStats: [],
            commissions: [],
            commissionRates: [],
            stats: {},
            chartData: {},
            sellers: [],
            zones: [],
            filters: {
                date_from: '',
                date_to: '',
                gerant_id: '',
                seller_id: '',
                zone_id: '',
                payment_method: ''
            },
            currentPage: 1,
            perPage: 20,
            totalItems: 0,
            totalPages: 1,
            loading: false,
            showSellerModal: false,
            selectedSeller: null,
            sellerDetailsData: null,
            selectedCommissions: [],
            selectedSales: [],
            showDeleteConfirm: false,
            deleteTargetId: null,
            _dailyChart: null,
            _monthlyChart: null,
            _paymentChart: null,
            _profileChart: null,

            async init() {
                await Promise.all([
                    this.loadStats(),
                    this.loadSales(),
                    this.loadFiltersData()
                ]);
            },

            dateParams() {
                let params = '';
                if (this.filters.date_from) params += `&date_from=${this.filters.date_from}`;
                if (this.filters.date_to) params += `&date_to=${this.filters.date_to}`;
                return params;
            },

            onDateFilterChange() {
                this.loadStats();
                const tab = this.activeTab;
                if (tab === 'sales') this.loadSales();
                else if (tab === 'by-seller') this.loadBySeller();
                else if (tab === 'by-zone') this.loadByZone();
                else if (tab === 'by-nas') this.loadByNas();
                else if (tab === 'by-profile') this.loadByProfile();
                else if (tab === 'charts') this.loadCharts();
            },

            refreshCurrentTab() {
                this.loadStats();
                const tab = this.activeTab;
                if (tab === 'sales') this.loadSales();
                else if (tab === 'by-seller') this.loadBySeller();
                else if (tab === 'by-zone') this.loadByZone();
                else if (tab === 'by-nas') this.loadByNas();
                else if (tab === 'by-profile') this.loadByProfile();
                else if (tab === 'charts') this.loadCharts();
                else if (tab === 'commissions') this.loadCommissions();
                else if (tab === 'rates') this.loadCommissionRates();
            },

            toggleAllSales(event) {
                if (event.target.checked) {
                    this.selectedSales = this.sales.map(s => s.id);
                } else {
                    this.selectedSales = [];
                }
            },

            deleteSingleSale(id) {
                this.deleteTargetId = id;
                this.showDeleteConfirm = true;
            },

            async confirmDelete() {
                try {
                    if (this.deleteTargetId) {
                        await API.delete(`/sales/${this.deleteTargetId}`);
                    } else {
                        await API.delete('/sales/batch', { ids: this.selectedSales });
                    }
                    showToast('<?= __('sales.delete_success') ?>', 'success');
                    this.selectedSales = [];
                    this.deleteTargetId = null;
                    this.showDeleteConfirm = false;
                    this.loadSales();
                    this.loadStats();
                } catch (error) {
                    showToast(error.message || '<?= __('sales.delete_error') ?>', 'error');
                }
            },

            async loadFiltersData() {
                try {
                    const [sellersRes, zonesRes] = await Promise.all([
                        API.get('/users?role=vendeur,gerant'),
                        API.get('/zones')
                    ]);
                    this.sellers = sellersRes.data || [];
                    this.zones = zonesRes.data || [];
                } catch (error) {
                    console.error('Error loading filters data:', error);
                }
            },

            async loadStats() {
                try {
                    const response = await API.get('/sales/stats?' + this.dateParams());
                    const data = response.data || {};
                    const global = data.global || {};
                    this.stats = {
                        total_sales: global.total_sales || 0,
                        total_amount: global.total_revenue || global.total_amount || 0,
                        total_commission_vendeur: global.total_commission_vendeur || 0,
                        total_commission_gerant: global.total_commission_gerant || 0,
                        total_commission_admin: global.total_commission_admin || 0,
                        summary: data.summary || {}
                    };
                    this.chartData = {
                        by_day: data.by_day || [],
                        by_month: data.by_month || [],
                        by_payment_method: data.by_payment_method || []
                    };
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            },

            async loadSales() {
                this.loading = true;
                try {
                    const offset = (this.currentPage - 1) * this.perPage;
                    let url = `/sales?limit=${this.perPage}&offset=${offset}`;
                    url += '&' + this.dateParams();
                    if (this.filters.gerant_id) url += `&gerant_id=${this.filters.gerant_id}`;
                    if (this.filters.seller_id) url += `&seller_id=${this.filters.seller_id}`;
                    if (this.filters.zone_id) url += `&zone_id=${this.filters.zone_id}`;
                    if (this.filters.payment_method) url += `&payment_method=${this.filters.payment_method}`;

                    const response = await API.get(url);
                    this.sales = response.data?.sales || response.data?.data || response.data || [];
                    this.totalItems = response.data?.total || this.sales.length;
                    this.totalPages = Math.ceil(this.totalItems / this.perPage) || 1;
                } catch (error) {
                    console.error('Error loading sales:', error);
                    showToast(__('sales.msg_loading_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadBySeller() {
                try {
                    const response = await API.get('/sales/by-seller?' + this.dateParams());
                    this.sellerStats = response.data?.sellers || response.data || [];
                } catch (error) {
                    console.error('Error loading seller stats:', error);
                }
            },

            async loadByGerant() {
                try {
                    const response = await API.get('/sales/by-gerant?' + this.dateParams());
                    this.gerantStats = response.data?.gerants || response.data || [];
                } catch (error) {
                    console.error('Error loading gerant stats:', error);
                }
            },

            async loadByZone() {
                try {
                    const response = await API.get('/sales/by-zone?' + this.dateParams());
                    this.zoneStats = response.data?.zones || response.data || [];
                } catch (error) {
                    console.error('Error loading zone stats:', error);
                }
            },

            async loadByNas() {
                try {
                    const response = await API.get('/sales/by-nas?' + this.dateParams());
                    this.nasStats = response.data?.nas || response.data || [];
                } catch (error) {
                    console.error('Error loading NAS stats:', error);
                }
            },

            async loadByProfile() {
                try {
                    const response = await API.get('/sales/by-profile?' + this.dateParams());
                    this.profileStats = response.data?.profiles || response.data || [];
                } catch (error) {
                    console.error('Error loading profile stats:', error);
                }
            },

            async loadCommissions() {
                try {
                    const response = await API.get('/sales/commissions');
                    this.commissions = response.data?.commissions || response.data || [];
                } catch (error) {
                    console.error('Error loading commissions:', error);
                }
            },

            async loadCommissionRates() {
                try {
                    const response = await API.get('/sales/commission-rates');
                    this.commissionRates = response.data || [];
                } catch (error) {
                    showToast(__('sales.msg_loading_rates_error'), 'error');
                }
            },

            async loadCharts() {
                await this.loadStats();
                if (this.profileStats.length === 0) await this.loadByProfile();
                this.$nextTick(() => {
                    this.renderDailyChart();
                    this.renderMonthlyChart();
                    this.renderPaymentChart();
                    this.renderProfileChart();
                });
            },

            chartDefaults() {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    gridColor: isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.03)',
                    tickColor: isDark ? '#8b949e' : '#9ca3af',
                    tooltipBg: isDark ? 'rgba(22, 27, 34, 0.95)' : 'rgba(255, 255, 255, 0.95)',
                    tooltipTitle: isDark ? '#f0f6fc' : '#111827',
                    tooltipBody: isDark ? '#8b949e' : '#4b5563',
                    tooltipBorder: isDark ? 'rgba(48, 54, 61, 0.8)' : 'rgba(229, 231, 235, 0.8)',
                    legendColor: isDark ? '#8b949e' : '#6b7280',
                };
            },

            createGradient(ctx, colorStart, colorEnd) {
                const canvasCtx = ctx.getContext ? ctx.getContext('2d') : ctx;
                const gradient = canvasCtx.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, colorStart);
                gradient.addColorStop(1, colorEnd);
                return gradient;
            },

            renderDailyChart() {
                const ctx = document.getElementById('dailySalesChart');
                if (!ctx) return;
                if (this._dailyChart) this._dailyChart.destroy();
                const c = this.chartDefaults();
                const data = this.chartData.by_day || [];

                const revGradient = this.createGradient(ctx, 'rgba(99, 102, 241, 0.4)', 'rgba(99, 102, 241, 0.0)');
                const tktGradient = this.createGradient(ctx, 'rgba(245, 158, 11, 0.9)', 'rgba(245, 158, 11, 0.3)');

                this._dailyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })),
                        datasets: [
                            {
                                label: __('sales.amount'),
                                data: data.map(d => parseFloat(d.total) || 0),
                                type: 'line',
                                borderColor: '#6366f1',
                                backgroundColor: revGradient,
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#6366f1',
                                pointBorderWidth: 2,
                                yAxisID: 'y'
                            },
                            {
                                label: __('sales.tickets'),
                                data: data.map(d => parseInt(d.count) || 0),
                                type: 'bar',
                                backgroundColor: tktGradient,
                                borderRadius: { topLeft: 4, topRight: 4 },
                                barPercentage: 0.3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'rectRounded', padding: 20, font: { size: 12, weight: '500' } } },
                            tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 12, cornerRadius: 8, titleFont: { size: 13, weight: 'bold' }, bodyFont: { size: 13 }, boxPadding: 6 }
                        },
                        scales: {
                            x: { grid: { display: false, drawBorder: false }, ticks: { color: c.tickColor, font: { size: 11 }, maxRotation: 45 } },
                            y: { beginAtZero: true, position: 'left', border: { display: false }, grid: { color: c.gridColor, drawBorder: false }, ticks: { color: c.tickColor, font: { size: 11 }, padding: 10, callback: v => this.formatPrice(v) } },
                            y1: { beginAtZero: true, position: 'right', border: { display: false }, grid: { drawOnChartArea: false }, ticks: { color: '#f59e0b', font: { size: 11 }, padding: 10 } }
                        }
                    }
                });
            },

            renderMonthlyChart() {
                const ctx = document.getElementById('monthlySalesChart');
                if (!ctx) return;
                if (this._monthlyChart) this._monthlyChart.destroy();
                const c = this.chartDefaults();
                const data = this.chartData.by_month || [];
                const monthNames = [__('sales.month_jan'), __('sales.month_feb'), __('sales.month_mar'), __('sales.month_apr'), __('sales.month_may'), __('sales.month_jun'), __('sales.month_jul'), __('sales.month_aug'), __('sales.month_sep'), __('sales.month_oct'), __('sales.month_nov'), __('sales.month_dec')];

                const revGradient = this.createGradient(ctx, 'rgba(16, 185, 129, 0.4)', 'rgba(16, 185, 129, 0.0)');
                const tktGradient = this.createGradient(ctx, 'rgba(139, 92, 246, 0.9)', 'rgba(139, 92, 246, 0.3)');

                this._monthlyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.map(d => { const [y, m] = d.month.split('-'); return monthNames[parseInt(m) - 1] + ' ' + y; }),
                        datasets: [
                            {
                                label: __('sales.amount'),
                                data: data.map(d => parseFloat(d.total) || 0),
                                type: 'line',
                                borderColor: '#10b981',
                                backgroundColor: revGradient,
                                fill: true,
                                tension: 0.4,
                                borderWidth: 3,
                                pointRadius: 0,
                                pointHoverRadius: 6,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#10b981',
                                pointBorderWidth: 2,
                                yAxisID: 'y'
                            },
                            {
                                label: __('sales.tickets'),
                                data: data.map(d => parseInt(d.count) || 0),
                                type: 'bar',
                                backgroundColor: tktGradient,
                                borderRadius: { topLeft: 4, topRight: 4 },
                                barPercentage: 0.4,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { position: 'top', align: 'end', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'rectRounded', padding: 20, font: { size: 12, weight: '500' } } },
                            tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 12, cornerRadius: 8, titleFont: { size: 13, weight: 'bold' }, bodyFont: { size: 13 }, boxPadding: 6 }
                        },
                        scales: {
                            x: { grid: { display: false, drawBorder: false }, ticks: { color: c.tickColor, font: { size: 11 } } },
                            y: { beginAtZero: true, position: 'left', border: { display: false }, grid: { color: c.gridColor, drawBorder: false }, ticks: { color: c.tickColor, font: { size: 11 }, padding: 10, callback: v => this.formatPrice(v) } },
                            y1: { beginAtZero: true, position: 'right', border: { display: false }, grid: { drawOnChartArea: false }, ticks: { color: '#8b5cf6', font: { size: 11 }, padding: 10 } }
                        }
                    }
                });
            },

            renderPaymentChart() {
                const ctx = document.getElementById('paymentMethodChart');
                if (!ctx) return;
                if (this._paymentChart) this._paymentChart.destroy();
                const c = this.chartDefaults();
                const data = this.chartData.by_payment_method || [];
                const colorMap = { cash: '#10b981', mobile_money: '#f59e0b', online: '#3b82f6', free: '#9ca3af' };
                const labelMap = { cash: __('sales.cash'), mobile_money: __('sales.mobile_money'), online: __('sales.online'), free: __('sales.free') };

                this._paymentChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.map(d => labelMap[d.payment_method] || d.payment_method),
                        datasets: [{ data: data.map(d => parseFloat(d.total) || 0), backgroundColor: data.map(d => colorMap[d.payment_method] || '#6b7280'), borderWidth: 2, borderColor: document.documentElement.classList.contains('dark') ? '#161b22' : '#ffffff', hoverOffset: 6 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '75%',
                        plugins: {
                            legend: { position: 'right', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 } } },
                            tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 12, cornerRadius: 8, callbacks: { label: ctx => ' ' + ctx.label + ': ' + this.formatPrice(ctx.raw) } }
                        }
                    }
                });
            },

            renderProfileChart() {
                const ctx = document.getElementById('profileSalesChart');
                if (!ctx) return;
                if (this._profileChart) this._profileChart.destroy();
                const c = this.chartDefaults();
                const top5 = (this.profileStats || []).filter(p => p.sales_count > 0).slice(0, 5);
                const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

                this._profileChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: top5.map(p => p.profile_name),
                        datasets: [{ data: top5.map(p => parseFloat(p.total_sales) || 0), backgroundColor: colors.slice(0, top5.length), borderWidth: 2, borderColor: document.documentElement.classList.contains('dark') ? '#161b22' : '#ffffff', hoverOffset: 6 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, cutout: '75%',
                        plugins: {
                            legend: { position: 'right', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 12 } } },
                            tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 12, cornerRadius: 8, callbacks: { label: ctx => ' ' + ctx.label + ': ' + this.formatPrice(ctx.raw) } }
                        }
                    }
                });
            },

            getProfilePercent(profile) {
                const maxSales = Math.max(...this.profileStats.map(p => parseFloat(p.total_sales) || 0), 1);
                return Math.round(((parseFloat(profile.total_sales) || 0) / maxSales) * 100);
            },

            async updateRate(rate) {
                try {
                    await API.put(`/sales/commission-rates/${rate.id}`, {
                        rate_type: rate.rate_type,
                        rate_value: rate.rate_value
                    });
                    showToast(__('sales.msg_rate_updated'));
                } catch (error) {
                    showToast(__('sales.msg_rate_update_error'), 'error');
                }
            },

            async viewSellerDetails(seller) {
                this.selectedSeller = seller;
                this.sellerDetailsData = null;
                this.showSellerModal = true;

                try {
                    const response = await API.get(`/sales/seller/${seller.user_id}?` + this.dateParams());
                    this.sellerDetailsData = response.data || {};
                } catch (error) {
                    console.error('Error loading seller details:', error);
                    showToast(__('sales.msg_loading_error') || 'Erreur', 'error');
                }
            },

            toggleAllCommissions(event) {
                if (event.target.checked) {
                    this.selectedCommissions = this.commissions.map(c => c.user_id);
                } else {
                    this.selectedCommissions = [];
                }
            },

            async markSelectedPaid() {
                if (this.selectedCommissions.length === 0) return;
                try {
                    await API.post('/sales/mark-paid', { user_ids: this.selectedCommissions });
                    showToast(__('sales.msg_commissions_paid'));
                    this.selectedCommissions = [];
                    await this.loadCommissions();
                } catch (error) {
                    showToast(__('sales.msg_mark_paid_error'), 'error');
                }
            },

            async exportSales(format) {
                try {
                    showToast('Préparation de l\'export', 'info');
                    
                    let url = `/sales?limit=1000000&offset=0`;
                    url += '&' + this.dateParams();
                    if (this.filters.gerant_id) url += `&gerant_id=${this.filters.gerant_id}`;
                    if (this.filters.seller_id) url += `&seller_id=${this.filters.seller_id}`;
                    if (this.filters.zone_id) url += `&zone_id=${this.filters.zone_id}`;
                    if (this.filters.payment_method) url += `&payment_method=${this.filters.payment_method}`;

                    const response = await API.get(url);
                    const data = response.data?.sales || response.data?.data || response.data || [];
                    
                    if (!data || data.length === 0) {
                        showToast(__('sales.msg_no_data') || 'Aucune donnée à exporter', 'warning');
                        return;
                    }

                    const headers = [
                        __('sales.ticket'), __('sales.seller'), __('sales.role'), __('sales.profile'),
                        __('sales.zone'), 'NAS', __('sales.payment'), __('sales.amount'),
                        __('sales.seller_commission'), __('sales.manager_commission'),
                        __('sales.admin_commission'), __('sales.date')
                    ].map(h => h || '');

                    const rows = data.map(sale => {
                        return {
                            ticket_code: sale.ticket_code || '',
                            seller_name: sale.seller_name || sale.seller_username || '',
                            seller_role: sale.seller_role || '',
                            profile: sale.profile_name || '',
                            zone: sale.zone_name || '',
                            nas: sale.nas_name || '',
                            payment: sale.payment_method || '',
                            amount: sale.sale_amount || sale.profile_price || 0,
                            comm_seller: sale.commission_vendeur || 0,
                            comm_manager: sale.commission_gerant || 0,
                            comm_admin: sale.commission_admin || 0,
                            date: sale.sold_at || ''
                        };
                    });

                    // JSON Export
                    if (format === 'json') {
                        const blob = new Blob([JSON.stringify(rows, null, 2)], {type: 'application/json'});
                        this.downloadFile(blob, 'rapport_ventes.json');
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
                        this.downloadFile(blob, 'rapport_ventes.csv');
                        return;
                    }

                    // Requires external libraries for Excel and PDF
                    if (format === 'excel') {
                        await this.loadXLSX();
                        const ws = XLSX.utils.json_to_sheet(rows);
                        // Rename headers
                        XLSX.utils.sheet_add_aoa(ws, [headers], { origin: "A1" });
                        const wb = XLSX.utils.book_new();
                        XLSX.utils.book_append_sheet(wb, ws, "Ventes");
                        XLSX.writeFile(wb, 'rapport_ventes.xlsx');
                        return;
                    }

                    if (format === 'pdf') {
                        await this.loadJSPDF();
                        const doc = new window.jspdf.jsPDF('landscape');
                        doc.text('Rapport des Ventes', 14, 15);
                        
                        const pdfRows = rows.map(r => Object.values(r));
                        doc.autoTable({
                            head: [headers],
                            body: pdfRows,
                            startY: 20,
                            styles: { fontSize: 8 },
                            headStyles: { fillColor: [41, 128, 185] }
                        });
                        doc.save('rapport_ventes.pdf');
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

            formatDataBytes(bytes) {
                if (!bytes || bytes == 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },

            formatTimeDuration(seconds) {
                if (!seconds || seconds == 0) return '0s';
                const h = Math.floor(seconds / 3600);
                const m = Math.floor((seconds % 3600) / 60);
                const s = seconds % 60;
                let res = [];
                if (h > 0) res.push(Math.floor(h) + 'h');
                if (m > 0 || h > 0) res.push(Math.floor(m) + 'm');
                res.push(Math.floor(s) + 's');
                return res.join(' ');
            },

            getRoleLabel(role) {
                const labels = { 'vendeur': __('sales.role_vendeur'), 'gerant': __('sales.role_gerant'), 'technicien': __('sales.role_technicien') || 'Technicien', 'admin': __('sales.role_admin') };
                return labels[role] || role || '-';
            },

            getRoleClass(role) {
                const classes = {
                    'vendeur': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'gerant': 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                    'technicien': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                    'admin': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'
                };
                return classes[role] || 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
            },

            getPaymentMethodLabel(method) {
                const labels = { 'cash': __('sales.cash'), 'mobile_money': __('sales.mobile_money'), 'online': __('sales.online'), 'free': __('sales.free') };
                return labels[method] || method || '-';
            },

            getPaymentMethodClass(method) {
                const classes = {
                    'cash': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'mobile_money': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                    'online': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'free': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300'
                };
                return classes[method] || 'bg-gray-100 text-gray-800 dark:2d] daray-300';
            }
        }
    }
</script>