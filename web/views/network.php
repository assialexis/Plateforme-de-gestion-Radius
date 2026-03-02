<?php $pageTitle = __('page.network');
$currentPage = 'network'; ?>

<div x-data="networkPage()" x-init="init()">
    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button @click="activeTab = 'pools'"
                    :class="activeTab === 'pools' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <?= __('network.pools')?>
                </button>
            </li>
            <li class="mr-2">
                <button @click="activeTab = 'allocations'; loadAllocations()"
                    :class="activeTab === 'allocations' ? 'text-primary-600 border-primary-600 dark:text-primary-400 dark:border-primary-400' : 'text-gray-500 border-transparent hover:text-gray-600 hover:border-gray-300 dark:text-gray-400'"
                    class="inline-flex items-center p-4 border-b-2 rounded-t-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    <?= __('network.allocations')?>
                </button>
            </li>
        </ul>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('network.total_pools')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="pools.length"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-purple-500/10 dark:bg-purple-500/5 rounded-full blur-2xl group-hover:bg-purple-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('network.total_ips')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="getTotalIPs()"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500 to-purple-600 shadow-[0_0_15px_rgba(168,85,247,0.3)] group-hover:shadow-[0_0_20px_rgba(168,85,247,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-emerald-500/10 dark:bg-emerald-500/5 rounded-full blur-2xl group-hover:bg-emerald-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('network.used_ips')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="getUsedIPs()"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.3)] group-hover:shadow-[0_0_20px_rgba(16,185,129,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-amber-500/10 dark:bg-amber-500/5 rounded-full blur-2xl group-hover:bg-amber-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('network.available_ips')?>
                    </p>
                    <p class="text-3xl font-black text-gray-900 dark:text-white" x-text="getAvailableIPs()"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.3)] group-hover:shadow-[0_0_20px_rgba(245,158,11,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Pools -->
    <div x-show="activeTab === 'pools'">
        <div class="flex justify-end mb-4">
            <button @click="openCreatePoolModal()"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('network.new_pool')?>
            </button>
        </div>

        <!-- Liste des pools -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="pool in pools" :key="pool.id">
                <div
                    class="group relative bg-white dark:bg-[#161b22] rounded-2xl border border-gray-100 dark:border-[#30363d] shadow-sm hover:shadow-2xl hover:border-primary-500/30 transition-all duration-300 overflow-hidden flex flex-col h-full">
                    <div
                        class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-primary-500 to-indigo-500 transform origin-left scale-x-0 group-hover:scale-x-100 transition-transform duration-500">
                    </div>

                    <div class="p-6 flex-grow flex flex-col">
                        <!-- header -->
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                                    x-text="pool.name"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2"
                                    x-text="pool.description || __('network.no_description')"></p>
                            </div>
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold shadow-sm"
                                :class="pool.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20' : 'bg-gray-50 text-gray-600 border border-gray-200 dark:bg-[#21262d] dark:text-gray-400 dark:border-[#30363d]'"
                                x-text="pool.is_active ? __('common.active') : __('common.inactive')"></span>
                        </div>

                        <!-- IP Range (styled like a badge/code block) -->
                        <div
                            class="mt-4 mb-5 p-3.5 bg-gray-50 dark:bg-[#0d1117] rounded-xl border border-gray-100 dark:border-[#30363d] shadow-inner flex items-center justify-between">
                            <div class="flex flex-col w-full">
                                <span
                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-widest mb-1.5">
                                    <?= __('network.range_ip')?>
                                </span>
                                <div
                                    class="font-mono text-sm text-gray-800 dark:text-gray-200 flex items-center justify-between w-full">
                                    <span
                                        class="bg-white dark:bg-[#21262d] px-2 py-0.5 rounded border border-gray-200 dark:border-[#30363d] shadow-sm"
                                        x-text="pool.start_ip"></span>
                                    <div class="h-px bg-gray-300 dark:bg-gray-600 flex-grow mx-2 relative">
                                        <div
                                            class="absolute right-0 top-1/2 -mt-1 w-2 h-2 border-t border-r border-gray-300 dark:border-gray-600 transform rotate-45">
                                        </div>
                                    </div>
                                    <span
                                        class="bg-white dark:bg-[#21262d] px-2 py-0.5 rounded border border-gray-200 dark:border-[#30363d] shadow-sm"
                                        x-text="pool.end_ip"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-3 mb-5">
                            <div
                                class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3 text-center border border-gray-100 dark:border-[#30363d]/50 group-hover:bg-white dark:group-hover:bg-[#161b22] group-hover:shadow-sm transition-all">
                                <div
                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                                    <?= __('network.total')?>
                                </div>
                                <div class="font-bold text-lg text-gray-900 dark:text-white" x-text="pool.total_ips">
                                </div>
                            </div>
                            <div
                                class="bg-emerald-50 dark:bg-emerald-500/10 rounded-xl p-3 text-center border border-emerald-100 dark:border-emerald-500/20 group-hover:bg-emerald-100/50 dark:group-hover:bg-emerald-500/20 group-hover:shadow-sm transition-all">
                                <div
                                    class="text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">
                                    <?= __('network.used')?>
                                </div>
                                <div class="font-bold text-lg text-emerald-700 dark:text-emerald-400"
                                    x-text="pool.used_ips || 0"></div>
                            </div>
                            <div
                                class="bg-amber-50 dark:bg-amber-500/10 rounded-xl p-3 text-center border border-amber-100 dark:border-amber-500/20 group-hover:bg-amber-100/50 dark:group-hover:bg-amber-500/20 group-hover:shadow-sm transition-all">
                                <div
                                    class="text-[11px] font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider mb-1">
                                    <?= __('network.available')?>
                                </div>
                                <div class="font-bold text-lg text-amber-700 dark:text-amber-400"
                                    x-text="pool.available_ips || (pool.total_ips - (pool.used_ips || 0))"></div>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-5">
                            <div class="flex justify-between items-end mb-1.5">
                                <span
                                    class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 tracking-wider uppercase">Utilisation</span>
                                <span class="text-xs font-bold"
                                    :class="getPoolUsagePercent(pool) > 80 ? 'text-red-500 dark:text-red-400' : (getPoolUsagePercent(pool) > 50 ? 'text-amber-500 dark:text-amber-400' : 'text-emerald-500 dark:text-emerald-400')"
                                    x-text="getPoolUsagePercent(pool) + '%'"></span>
                            </div>
                            <div
                                class="w-full bg-gray-100 dark:bg-[#0d1117] rounded-full h-2.5 overflow-hidden border border-gray-200 dark:border-[#30363d]/50 shadow-inner">
                                <div class="h-full rounded-full transition-all duration-500 ease-out"
                                    :class="getPoolUsagePercent(pool) > 80 ? 'bg-gradient-to-r from-red-500 to-red-600' : (getPoolUsagePercent(pool) > 50 ? 'bg-gradient-to-r from-amber-400 to-amber-500' : 'bg-gradient-to-r from-emerald-400 to-emerald-500')"
                                    :style="'width: ' + getPoolUsagePercent(pool) + '%'"></div>
                            </div>
                        </div>

                        <!-- Network Config Tokens -->
                        <div class="flex flex-wrap gap-2 mt-auto">
                            <div x-show="pool.gateway"
                                class="inline-flex items-center px-2.5 py-1 bg-gray-50 dark:bg-[#21262d] text-gray-600 dark:text-gray-300 text-xs rounded-md border border-gray-200/80 dark:border-[#30363d] font-mono shadow-sm"
                                title="<?= __('network.gateway')?>">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <span x-text="pool.gateway"></span>
                            </div>
                            <div class="inline-flex items-center px-2.5 py-1 bg-gray-50 dark:bg-[#21262d] text-gray-600 dark:text-gray-300 text-xs rounded-md border border-gray-200/80 dark:border-[#30363d] font-mono shadow-sm"
                                title="<?= __('network.netmask')?>">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span x-text="pool.netmask"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Footer -->
                    <div
                        class="px-6 py-4 bg-gray-50/50 dark:bg-[#21262d]/20 border-t border-gray-100 dark:border-[#30363d]/80 flex justify-between items-center group-hover:bg-gray-50 dark:group-hover:bg-[#21262d]/50 transition-colors">
                        <button @click="viewPoolDetails(pool)"
                            class="inline-flex items-center text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 text-sm font-semibold transition-colors">
                            <?= __('network.view_ips')?>
                            <svg class="w-4 h-4 ml-1 transform group-hover:translate-x-1 transition-transform"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div
                            class="flex gap-1 border border-gray-200/80 dark:border-[#30363d] rounded-lg overflow-hidden shadow-sm bg-white dark:bg-[#161b22]">
                            <button @click="editPool(pool)"
                                class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-500/10 dark:text-gray-400 dark:hover:text-primary-400 transition-colors"
                                title="<?= __('common.edit')?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <div class="w-px bg-gray-200/80 dark:bg-[#30363d]"></div>
                            <button @click="deletePool(pool)"
                                class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 dark:text-gray-400 dark:hover:text-red-400 transition-colors"
                                title="<?= __('common.delete')?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty state -->
            <div x-show="pools.length === 0 && !loading" class="col-span-full text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="mt-2 text-gray-500 dark:text-gray-400">
                    <?= __('network.empty')?>
                </p>
                <button @click="openCreatePoolModal()"
                    class="mt-4 text-primary-600 hover:text-primary-800 dark:text-primary-400">
                    <?= __('network.create_first')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Tab: Allocations -->
    <div x-show="activeTab === 'allocations'">
        <!-- Filtres -->
        <div class="flex flex-wrap items-center gap-4 mb-6">
            <select x-model="allocationFilters.pool_id" @change="loadAllocations()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('network.all_pools')?>
                </option>
                <template x-for="pool in pools" :key="pool.id">
                    <option :value="pool.id" x-text="pool.name"></option>
                </template>
            </select>

            <select x-model="allocationFilters.status" @change="loadAllocations()"
                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('common.all_statuses')?>
                </option>
                <option value="available">
                    <?= __('network.status_available')?>
                </option>
                <option value="allocated">
                    <?= __('network.status_allocated')?>
                </option>
                <option value="reserved">
                    <?= __('network.status_reserved')?>
                </option>
                <option value="blocked">
                    <?= __('network.status_blocked')?>
                </option>
            </select>
        </div>

        <!-- Table des allocations -->
        <div
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('network.ip_address')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('network.pool')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('common.status')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('network.user')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('common.type')?>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('network.allocated_at')?>
                            </th>
                            <th
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                <?= __('common.actions')?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                        <template x-if="loadingAllocations">
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <div class="flex justify-center">
                                        <svg class="animate-spin h-8 w-8 text-primary-600" fill="none"
                                            viewBox="0 0 24 24">
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
                        <template x-for="ip in allocations" :key="ip.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                                <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-gray-900 dark:text-white"
                                    x-text="ip.ip_address"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                    x-text="ip.pool_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="getStatusClass(ip.status)" x-text="getStatusLabel(ip.status)"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"
                                    x-text="ip.username || '-'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                    x-text="ip.user_type"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"
                                    x-text="formatDate(ip.allocated_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <template x-if="ip.status === 'allocated'">
                                            <button @click="releaseIP(ip)"
                                                class="text-orange-600 hover:text-orange-800 dark:text-orange-400"
                                                title="<?= __('network.release')?>">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template x-if="ip.status === 'available'">
                                            <div class="flex items-center gap-1">
                                                <button @click="openReserveModal(ip)"
                                                    class="text-purple-600 hover:text-purple-800 dark:text-purple-400"
                                                    title="<?= __('network.reserve')?>">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                                                    </svg>
                                                </button>
                                                <button @click="blockIP(ip)"
                                                    class="text-red-600 hover:text-red-800 dark:text-red-400"
                                                    title="<?= __('network.block')?>">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <template x-if="ip.status === 'reserved'">
                                            <button @click="cancelReservation(ip)"
                                                class="text-orange-600 hover:text-orange-800 dark:text-orange-400"
                                                title="<?= __('network.cancel_reservation')?>">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template x-if="ip.status === 'blocked'">
                                            <button @click="unblockIP(ip)"
                                                class="text-green-600 hover:text-green-800 dark:text-green-400"
                                                title="<?= __('network.unblock')?>">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
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
            <div
                class="px-6 py-3 bg-gray-50 dark:bg-[#21262d]/50 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    <?= __('common.page')?> <span x-text="allocationPage"></span> / <span
                        x-text="allocationTotalPages"></span>
                    (<span x-text="allocationTotal"></span> IPs)
                </div>
                <div class="flex items-center gap-2">
                    <button @click="prevAllocationPage()" :disabled="allocationPage <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('common.previous')?>
                    </button>
                    <button @click="nextAllocationPage()" :disabled="allocationPage >= allocationTotalPages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <?= __('common.next')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Créer/Modifier Pool -->
    <div x-show="showPoolModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPoolModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="editingPool ? __('network.edit_pool') : __('network.new_pool_title')"></h3>

                <form @submit.prevent="savePool()" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('network.pool_name')?>
                        </label>
                        <input type="text" x-model="poolForm.name" required :disabled="editingPool"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white disabled:opacity-50"
                            placeholder="ex: pool-pppoe-1">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('common.description')?>
                        </label>
                        <input type="text" x-model="poolForm.description"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                            placeholder="<?= __('network.description_placeholder')?>">
                    </div>

                    <!-- Configuration réseau avec calculateur (seulement en création) -->
                    <div x-show="!editingPool" class="space-y-4">
                        <div class="grid grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('network.network_address')?>
                                </label>
                                <input type="text" x-model="poolForm.network_address" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono"
                                    placeholder="192.168.1.0" @input="subnetCalculated = false">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('network.cidr')?>
                                </label>
                                <select x-model="poolForm.cidr" required @change="subnetCalculated = false"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                    <option value="">--</option>
                                    <option value="30">/30 (4 IPs)</option>
                                    <option value="29">/29 (8 IPs)</option>
                                    <option value="28">/28 (16 IPs)</option>
                                    <option value="27">/27 (32 IPs)</option>
                                    <option value="26">/26 (64 IPs)</option>
                                    <option value="25">/25 (128 IPs)</option>
                                    <option value="24">/24 (256 IPs)</option>
                                    <option value="23">/23 (512 IPs)</option>
                                    <option value="22">/22 (1024 IPs)</option>
                                    <option value="21">/21 (2048 IPs)</option>
                                    <option value="20">/20 (4096 IPs)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Bouton calculer -->
                        <div class="flex justify-center">
                            <button type="button" @click="calculateSubnet()"
                                :disabled="!poolForm.network_address || !poolForm.cidr"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                <?= __('network.calculate_subnet')?>
                            </button>
                        </div>

                        <!-- Affichage de la plage calculée (lecture seule) -->
                        <div x-show="subnetCalculated"
                            class="p-3 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-2 mb-2">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-sm font-medium text-green-700 dark:text-green-400">
                                    <?= __('network.config_validated')?>
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="text-gray-600 dark:text-gray-400">
                                    <?= __('network.usable_range')?>
                                </div>
                                <div class="font-mono text-gray-900 dark:text-white"
                                    x-text="poolForm.start_ip + ' - ' + poolForm.end_ip"></div>
                                <div class="text-gray-600 dark:text-gray-400">
                                    <?= __('network.available_ips_label')?>
                                </div>
                                <div class="font-semibold text-green-600 dark:text-green-400"
                                    x-text="subnetInfo.usable_hosts"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Affichage plage en mode édition -->
                    <div x-show="editingPool" class="p-3 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <?= __('network.ip_range_readonly')?>
                        </div>
                        <div class="font-mono text-sm text-gray-900 dark:text-white"
                            x-text="poolForm.start_ip + ' - ' + poolForm.end_ip"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('network.gateway')?>
                            </label>
                            <input type="text" x-model="poolForm.gateway"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono"
                                placeholder="192.168.1.1">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('network.netmask')?>
                            </label>
                            <input type="text" x-model="poolForm.netmask" readonly
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-gray-100 dark:bg-[#30363d] text-gray-900 dark:text-white font-mono"
                                placeholder="255.255.255.0">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('network.dns_primary')?>
                            </label>
                            <input type="text" x-model="poolForm.dns_primary"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono"
                                placeholder="8.8.8.8">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('network.dns_secondary')?>
                            </label>
                            <input type="text" x-model="poolForm.dns_secondary"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono"
                                placeholder="8.8.4.4">
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" x-model="poolForm.is_active" id="pool_active"
                            class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                        <label for="pool_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            <?= __('network.pool_active')?>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button" @click="showPoolModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" :disabled="!editingPool && !subnetCalculated"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span x-text="editingPool ? __('common.edit') : __('common.create')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Calcul du sous-réseau (popup détaillé) -->
    <div x-show="showSubnetModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showSubnetModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('network.calculation_details')?>
                    </h3>
                    <button @click="showSubnetModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Réseau CIDR -->
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                        <div class="text-sm text-blue-600 dark:text-blue-400 mb-1">
                            <?= __('network.cidr_notation')?>
                        </div>
                        <div class="text-2xl font-bold font-mono text-blue-700 dark:text-blue-300"
                            x-text="subnetInfo.cidr_notation"></div>
                    </div>

                    <!-- Détails -->
                    <div class="space-y-3">
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.network_address_label')?>
                            </span>
                            <span class="font-mono font-medium text-gray-900 dark:text-white"
                                x-text="subnetInfo.network_address"></span>
                        </div>
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.subnet_mask')?>
                            </span>
                            <span class="font-mono font-medium text-gray-900 dark:text-white"
                                x-text="subnetInfo.netmask"></span>
                        </div>
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.broadcast_address')?>
                            </span>
                            <span class="font-mono font-medium text-gray-900 dark:text-white"
                                x-text="subnetInfo.broadcast"></span>
                        </div>
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.first_usable_ip')?>
                            </span>
                            <span class="font-mono font-medium text-green-600 dark:text-green-400"
                                x-text="subnetInfo.first_usable"></span>
                        </div>
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.last_usable_ip')?>
                            </span>
                            <span class="font-mono font-medium text-green-600 dark:text-green-400"
                                x-text="subnetInfo.last_usable"></span>
                        </div>
                        <div
                            class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-[#30363d]">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.total_addresses')?>
                            </span>
                            <span class="font-medium text-gray-900 dark:text-white"
                                x-text="subnetInfo.total_hosts"></span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                <?= __('network.usable_hosts')?>
                            </span>
                            <span class="font-bold text-xl text-green-600 dark:text-green-400"
                                x-text="subnetInfo.usable_hosts"></span>
                        </div>
                    </div>

                    <!-- Boutons -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showSubnetModal = false"
                            class="flex-1 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] hover:bg-gray-200 dark:hover:bg-[#30363d] rounded-lg">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="button" @click="applySubnetCalculation()"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <?= __('common.apply')?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Détails du Pool (liste des IPs) -->
    <div x-show="showPoolDetailsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPoolDetailsModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
                <div class="p-6 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('network.pool_ips')?> <span x-text="selectedPool?.name"></span>
                    </h3>
                    <button @click="showPoolDetailsModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto max-h-[60vh]">
                    <!-- Stats du pool -->
                    <div class="grid grid-cols-4 gap-4 mb-6">
                        <div class="text-center p-3 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                            <div class="text-2xl font-bold text-gray-900 dark:text-white"
                                x-text="poolDetailsStats.available || 0"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?= __('network.available')?>
                            </div>
                        </div>
                        <div class="text-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400"
                                x-text="poolDetailsStats.allocated || 0"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?= __('network.allocated_count')?>
                            </div>
                        </div>
                        <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400"
                                x-text="poolDetailsStats.reserved || 0"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?= __('network.reserved_count')?>
                            </div>
                        </div>
                        <div class="text-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400"
                                x-text="poolDetailsStats.blocked || 0"></div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                <?= __('network.blocked_count')?>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des IPs -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                                <tr>
                                    <th class="px-4 py-2 text-left">IP</th>
                                    <th class="px-4 py-2 text-left">
                                        <?= __('common.status')?>
                                    </th>
                                    <th class="px-4 py-2 text-left">
                                        <?= __('network.user')?>
                                    </th>
                                    <th class="px-4 py-2 text-left">
                                        <?= __('network.allocation_date')?>
                                    </th>
                                    <th class="px-4 py-2 text-right">
                                        <?= __('common.actions')?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                                <template x-for="ip in poolDetailsIPs" :key="ip.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                                        <td class="px-4 py-2 font-mono" x-text="ip.ip_address"></td>
                                        <td class="px-4 py-2">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                                :class="getStatusClass(ip.status)"
                                                x-text="getStatusLabel(ip.status)"></span>
                                        </td>
                                        <td class="px-4 py-2" x-text="ip.username || '-'"></td>
                                        <td class="px-4 py-2" x-text="formatDate(ip.allocated_at)"></td>
                                        <td class="px-4 py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <template x-if="ip.status === 'allocated'">
                                                    <button @click="releaseIP(ip)"
                                                        class="text-orange-600 hover:text-orange-800 text-xs">
                                                        <?= __('network.release')?>
                                                    </button>
                                                </template>
                                                <template x-if="ip.status === 'available'">
                                                    <div class="flex gap-2">
                                                        <button @click="openReserveModal(ip)"
                                                            class="text-purple-600 hover:text-purple-800 text-xs">
                                                            <?= __('network.reserve')?>
                                                        </button>
                                                        <button @click="blockIP(ip)"
                                                            class="text-red-600 hover:text-red-800 text-xs">
                                                            <?= __('network.block')?>
                                                        </button>
                                                    </div>
                                                </template>
                                                <template x-if="ip.status === 'reserved'">
                                                    <button @click="cancelReservation(ip)"
                                                        class="text-orange-600 hover:text-orange-800 text-xs">
                                                        <?= __('common.cancel')?>
                                                    </button>
                                                </template>
                                                <template x-if="ip.status === 'blocked'">
                                                    <button @click="unblockIP(ip)"
                                                        class="text-green-600 hover:text-green-800 text-xs">
                                                        <?= __('network.unblock')?>
                                                    </button>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Réservation IP -->
    <div x-show="showReserveModal" x-cloak class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showReserveModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('network.reserve_ip')?>
                    </h3>
                    <button @click="showReserveModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="confirmReservation()" class="space-y-4">
                    <!-- IP à réserver -->
                    <div class="p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg text-center">
                        <div class="text-sm text-purple-600 dark:text-purple-400 mb-1">
                            <?= __('network.ip_address')?>
                        </div>
                        <div class="text-2xl font-bold font-mono text-purple-700 dark:text-purple-300"
                            x-text="reserveForm.ip_address"></div>
                    </div>

                    <!-- Lier à un client PPPoE (optionnel) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('network.link_pppoe')?>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(
                                <?= __('common.optional')?>)
                            </span>
                        </label>
                        <div class="relative">
                            <select x-model="reserveForm.pppoe_user_id" @change="onPPPoEUserSelect()"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="">
                                    <?= __('network.no_client')?>
                                </option>
                                <template x-for="user in pppoeUsers" :key="user.id">
                                    <option :value="user.id"
                                        x-text="user.username + (user.customer_name ? ' (' + user.customer_name + ')' : '')">
                                    </option>
                                </template>
                            </select>
                            <div class="absolute inset-y-0 right-8 flex items-center" x-show="loadingPPPoEUsers">
                                <svg class="w-4 h-4 text-gray-400 animate-spin" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('network.ip_reserved_for_client')?>
                        </p>
                    </div>

                    <!-- Nom/Description de la réservation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('network.reservation_name')?>
                            <span x-show="!reserveForm.pppoe_user_id" class="text-red-500">*</span>
                        </label>
                        <input type="text" x-model="reserveForm.reserved_for" :required="!reserveForm.pppoe_user_id"
                            :disabled="reserveForm.pppoe_user_id"
                            :class="reserveForm.pppoe_user_id ? 'bg-gray-100 dark:bg-[#30363d]' : 'bg-white dark:bg-[#21262d]'"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white"
                            placeholder="ex: Serveur principal, Client VIP, etc.">
                        <p x-show="reserveForm.pppoe_user_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <?= __('network.client_name_auto')?>
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('network.notes_optional')?>
                        </label>
                        <textarea x-model="reserveForm.notes" rows="2"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                            placeholder="Informations supplémentaires..."></textarea>
                    </div>

                    <!-- Boutons -->
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="showReserveModal = false"
                            class="flex-1 px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] hover:bg-gray-200 dark:hover:bg-[#30363d] rounded-lg">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                            </svg>
                            <?= __('network.reserve')?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function networkPage() {
        return {
            activeTab: 'pools',
            loading: false,
            pools: [],

            // Allocations
            allocations: [],
            loadingAllocations: false,
            allocationFilters: {
                pool_id: '',
                status: ''
            },
            allocationPage: 1,
            allocationTotal: 0,
            allocationTotalPages: 1,

            // Pool modal
            showPoolModal: false,
            showSubnetModal: false,
            subnetCalculated: false,
            subnetInfo: {},
            editingPool: null,
            poolForm: {
                name: '',
                description: '',
                network_address: '',
                cidr: '',
                start_ip: '',
                end_ip: '',
                gateway: '',
                netmask: '255.255.255.0',
                dns_primary: '8.8.8.8',
                dns_secondary: '8.8.4.4',
                is_active: true
            },

            // Pool details modal
            showPoolDetailsModal: false,
            selectedPool: null,
            poolDetailsIPs: [],
            poolDetailsStats: {},

            // Reserve modal
            showReserveModal: false,
            reserveForm: {
                ip_id: null,
                ip_address: '',
                reserved_for: '',
                notes: '',
                pppoe_user_id: ''
            },

            // PPPoE users for reservation
            pppoeUsers: [],
            loadingPPPoEUsers: false,

            async init() {
                await this.loadPools();
            },

            async loadPools() {
                this.loading = true;
                try {
                    const response = await API.get('/network/pools');
                    this.pools = response.data || [];
                } catch (error) {
                    console.error('Error loading pools:', error);
                    showToast(__('network.msg_loading_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            async loadAllocations() {
                if (!this.allocationFilters.pool_id) {
                    this.allocations = [];
                    return;
                }

                this.loadingAllocations = true;
                try {
                    let url = `/network/pools/${this.allocationFilters.pool_id}/ips?page=${this.allocationPage}`;
                    if (this.allocationFilters.status) {
                        url += `&status=${this.allocationFilters.status}`;
                    }

                    const response = await API.get(url);
                    this.allocations = response.data?.data || [];
                    this.allocationTotal = response.data?.total || 0;
                    this.allocationTotalPages = response.data?.total_pages || 1;
                } catch (error) {
                    console.error('Error loading allocations:', error);
                } finally {
                    this.loadingAllocations = false;
                }
            },

            // Pool CRUD
            openCreatePoolModal() {
                this.editingPool = null;
                this.subnetCalculated = false;
                this.subnetInfo = {};
                this.poolForm = {
                    name: '',
                    description: '',
                    network_address: '',
                    cidr: '',
                    start_ip: '',
                    end_ip: '',
                    gateway: '',
                    netmask: '255.255.255.0',
                    dns_primary: '8.8.8.8',
                    dns_secondary: '8.8.4.4',
                    is_active: true
                };
                this.showPoolModal = true;
            },

            editPool(pool) {
                this.editingPool = pool;
                this.subnetCalculated = true; // En mode édition, on considère que c'est déjà calculé
                this.poolForm = {
                    name: pool.name,
                    description: pool.description || '',
                    network_address: pool.network || '',
                    cidr: pool.cidr || '',
                    start_ip: pool.start_ip,
                    end_ip: pool.end_ip,
                    gateway: pool.gateway || '',
                    netmask: pool.netmask || '255.255.255.0',
                    dns_primary: pool.dns_primary || '8.8.8.8',
                    dns_secondary: pool.dns_secondary || '8.8.4.4',
                    is_active: pool.is_active
                };
                this.showPoolModal = true;
            },

            // Calcul de sous-réseau
            calculateSubnet() {
                const networkAddress = this.poolForm.network_address.trim();
                const cidr = parseInt(this.poolForm.cidr);

                if (!networkAddress || !cidr) {
                    showToast(__('network.msg_enter_network_cidr'), 'error');
                    return;
                }

                // Valider l'adresse IP
                if (!this.isValidIP(networkAddress)) {
                    showToast(__('network.msg_invalid_ip'), 'error');
                    return;
                }

                // Calculer les informations du sous-réseau
                const info = this.computeSubnetInfo(networkAddress, cidr);

                if (!info) {
                    showToast(__('network.msg_subnet_error'), 'error');
                    return;
                }

                this.subnetInfo = info;
                this.showSubnetModal = true;
            },

            isValidIP(ip) {
                const parts = ip.split('.');
                if (parts.length !== 4) return false;
                return parts.every(part => {
                    const num = parseInt(part, 10);
                    return !isNaN(num) && num >= 0 && num <= 255 && part === String(num);
                });
            },

            ipToLong(ip) {
                const parts = ip.split('.').map(p => parseInt(p, 10));
                return (parts[0] << 24) + (parts[1] << 16) + (parts[2] << 8) + parts[3];
            },

            longToIP(long) {
                return [
                    (long >>> 24) & 255,
                    (long >>> 16) & 255,
                    (long >>> 8) & 255,
                    long & 255
                ].join('.');
            },

            computeSubnetInfo(networkAddress, cidr) {
                const ipLong = this.ipToLong(networkAddress);

                // Calculer le masque
                const mask = cidr === 0 ? 0 : (~0 << (32 - cidr)) >>> 0;

                // Adresse réseau (s'assurer qu'elle est correcte)
                const networkLong = (ipLong & mask) >>> 0;

                // Adresse de broadcast
                const broadcastLong = (networkLong | (~mask >>> 0)) >>> 0;

                // Total d'adresses
                const totalHosts = Math.pow(2, 32 - cidr);

                // Hôtes utilisables (moins réseau et broadcast)
                const usableHosts = cidr >= 31 ? totalHosts : totalHosts - 2;

                // Première et dernière IP utilisables
                let firstUsable, lastUsable;
                if (cidr >= 31) {
                    firstUsable = networkLong;
                    lastUsable = broadcastLong;
                } else {
                    firstUsable = networkLong + 1;
                    lastUsable = broadcastLong - 1;
                }

                // Calculer le netmask en notation décimale
                const netmask = this.longToIP(mask);

                return {
                    cidr_notation: this.longToIP(networkLong) + '/' + cidr,
                    network_address: this.longToIP(networkLong),
                    netmask: netmask,
                    broadcast: this.longToIP(broadcastLong),
                    first_usable: this.longToIP(firstUsable),
                    last_usable: this.longToIP(lastUsable),
                    total_hosts: totalHosts.toLocaleString(),
                    usable_hosts: usableHosts.toLocaleString(),
                    usable_hosts_num: usableHosts
                };
            },

            applySubnetCalculation() {
                // Appliquer les valeurs calculées au formulaire
                this.poolForm.network_address = this.subnetInfo.network_address;
                this.poolForm.start_ip = this.subnetInfo.first_usable;
                this.poolForm.end_ip = this.subnetInfo.last_usable;
                this.poolForm.netmask = this.subnetInfo.netmask;

                // Suggérer la gateway comme première IP utilisable
                if (!this.poolForm.gateway) {
                    this.poolForm.gateway = this.subnetInfo.first_usable;
                }

                this.subnetCalculated = true;
                this.showSubnetModal = false;
                showToast(__('network.msg_config_applied'), 'success');
            },

            async savePool() {
                try {
                    // Préparer les données à envoyer
                    const data = {
                        name: this.poolForm.name,
                        description: this.poolForm.description,
                        gateway: this.poolForm.gateway,
                        netmask: this.poolForm.netmask,
                        dns_primary: this.poolForm.dns_primary,
                        dns_secondary: this.poolForm.dns_secondary,
                        is_active: this.poolForm.is_active
                    };

                    if (this.editingPool) {
                        await API.put(`/network/pools/${this.editingPool.id}`, data);
                        showToast(__('network.msg_pool_modified'), 'success');
                    } else {
                        // En création, ajouter les infos de réseau
                        data.network = this.poolForm.network_address;
                        data.cidr = parseInt(this.poolForm.cidr);
                        data.start_ip = this.poolForm.start_ip;
                        data.end_ip = this.poolForm.end_ip;

                        await API.post('/network/pools', data);
                        showToast(__('network.msg_pool_created_js'), 'success');
                    }
                    this.showPoolModal = false;
                    await this.loadPools();
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async deletePool(pool) {
                if (!confirm(__('network.msg_confirm_delete_pool').replace(':name', pool.name))) return;

                try {
                    await API.delete(`/network/pools/${pool.id}`);
                    showToast(__('network.msg_pool_deleted_js'), 'success');
                    await this.loadPools();
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async viewPoolDetails(pool) {
                this.selectedPool = pool;
                this.showPoolDetailsModal = true;

                try {
                    // Charger les stats
                    const statsResponse = await API.get(`/network/pools/${pool.id}/stats`);
                    this.poolDetailsStats = statsResponse.data?.by_status || {};

                    // Charger les IPs
                    const ipsResponse = await API.get(`/network/pools/${pool.id}/ips?per_page=100`);
                    this.poolDetailsIPs = ipsResponse.data?.data || [];
                } catch (error) {
                    console.error('Error loading pool details:', error);
                }
            },

            // IP actions
            async releaseIP(ip) {
                if (!confirm(__('network.msg_confirm_release').replace(':ip', ip.ip_address))) return;

                try {
                    await API.post(`/network/ips/${ip.id}/release`);
                    showToast(__('network.msg_ip_released'), 'success');
                    await this.loadPools();
                    if (this.showPoolDetailsModal) {
                        await this.viewPoolDetails(this.selectedPool);
                    }
                    if (this.allocationFilters.pool_id) {
                        await this.loadAllocations();
                    }
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async blockIP(ip) {
                try {
                    await API.put(`/network/ips/${ip.id}/status`, { status: 'blocked' });
                    showToast(__('network.msg_ip_blocked'), 'success');
                    await this.loadPools();
                    if (this.showPoolDetailsModal) {
                        await this.viewPoolDetails(this.selectedPool);
                    }
                    if (this.allocationFilters.pool_id) {
                        await this.loadAllocations();
                    }
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async unblockIP(ip) {
                try {
                    await API.put(`/network/ips/${ip.id}/status`, { status: 'available' });
                    showToast(__('network.msg_ip_unblocked'), 'success');
                    await this.loadPools();
                    if (this.showPoolDetailsModal) {
                        await this.viewPoolDetails(this.selectedPool);
                    }
                    if (this.allocationFilters.pool_id) {
                        await this.loadAllocations();
                    }
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            // Réservation IP
            async openReserveModal(ip) {
                this.reserveForm = {
                    ip_id: ip.id,
                    ip_address: ip.ip_address,
                    reserved_for: '',
                    notes: '',
                    pppoe_user_id: ''
                };
                this.showReserveModal = true;

                // Charger les clients PPPoE
                await this.loadPPPoEUsers();
            },

            async loadPPPoEUsers() {
                this.loadingPPPoEUsers = true;
                try {
                    const response = await API.get('/pppoe/users?per_page=500&status=active');
                    this.pppoeUsers = response.data?.data || [];
                } catch (error) {
                    console.error('Error loading PPPoE users:', error);
                    this.pppoeUsers = [];
                } finally {
                    this.loadingPPPoEUsers = false;
                }
            },

            onPPPoEUserSelect() {
                if (this.reserveForm.pppoe_user_id) {
                    // Trouver l'utilisateur sélectionné et remplir le nom
                    const user = this.pppoeUsers.find(u => u.id == this.reserveForm.pppoe_user_id);
                    if (user) {
                        this.reserveForm.reserved_for = user.customer_name || user.username;
                    }
                } else {
                    this.reserveForm.reserved_for = '';
                }
            },

            async confirmReservation() {
                try {
                    const data = {
                        reserved_for: this.reserveForm.reserved_for,
                        notes: this.reserveForm.notes
                    };

                    // Ajouter l'ID du client PPPoE si sélectionné
                    if (this.reserveForm.pppoe_user_id) {
                        data.pppoe_user_id = parseInt(this.reserveForm.pppoe_user_id);
                    }

                    await API.put(`/network/ips/${this.reserveForm.ip_id}/reserve`, data);
                    showToast(__('network.msg_ip_reserved'), 'success');
                    this.showReserveModal = false;
                    await this.loadPools();
                    if (this.showPoolDetailsModal) {
                        await this.viewPoolDetails(this.selectedPool);
                    }
                    if (this.allocationFilters.pool_id || this.allocationFilters.status) {
                        await this.loadAllocations();
                    }
                } catch (error) {
                    showToast(error.message || __('network.msg_reservation_error'), 'error');
                }
            },

            async cancelReservation(ip) {
                if (!confirm(__('network.msg_confirm_cancel_reservation').replace(':ip', ip.ip_address))) return;

                try {
                    await API.put(`/network/ips/${ip.id}/status`, { status: 'available' });
                    showToast(__('network.msg_reservation_cancelled'), 'success');
                    await this.loadPools();
                    if (this.showPoolDetailsModal) {
                        await this.viewPoolDetails(this.selectedPool);
                    }
                    if (this.allocationFilters.pool_id || this.allocationFilters.status) {
                        await this.loadAllocations();
                    }
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            // Pagination
            prevAllocationPage() {
                if (this.allocationPage > 1) {
                    this.allocationPage--;
                    this.loadAllocations();
                }
            },

            nextAllocationPage() {
                if (this.allocationPage < this.allocationTotalPages) {
                    this.allocationPage++;
                    this.loadAllocations();
                }
            },

            // Helpers
            getTotalIPs() {
                return this.pools.reduce((sum, p) => sum + (p.total_ips || 0), 0);
            },

            getUsedIPs() {
                return this.pools.reduce((sum, p) => sum + (p.used_ips || 0), 0);
            },

            getAvailableIPs() {
                return this.pools.reduce((sum, p) => sum + (p.available_ips || (p.total_ips - (p.used_ips || 0))), 0);
            },

            getPoolUsagePercent(pool) {
                if (!pool.total_ips) return 0;
                return Math.round(((pool.used_ips || 0) / pool.total_ips) * 100);
            },

            getStatusClass(status) {
                const classes = {
                    'available': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'allocated': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                    'reserved': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                    'blocked': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'
                };
                return classes[status] || 'bg-gray-100 text-gray-800';
            },

            getStatusLabel(status) {
                const labels = {
                    'available': __('network.status_available'),
                    'allocated': __('network.status_allocated'),
                    'reserved': __('network.status_reserved'),
                    'blocked': __('network.status_blocked')
                };
                return labels[status] || status;
            },

            formatDate(date) {
                if (!date) return '-';
                return new Date(date).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        };
    }
</script>