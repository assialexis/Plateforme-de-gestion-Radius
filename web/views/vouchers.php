<?php $pageTitle = __('page.vouchers');
$currentPage = 'vouchers'; ?>

<div x-data="vouchersPage()" x-init="init()">
    <!-- Header : Boutons d'action -->
    <div class="flex items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <p class="text-gray-500 dark:text-gray-400 text-sm">
                <span x-text="totalItems"></span>
                <?= __('common.results')?>
            </p>

            <!-- Actions sélection -->
            <template x-if="selectedIds.length > 0">
                <div class="flex items-center gap-2 ml-2 pl-3 border-l border-gray-300 dark:border-[#30363d]">
                    <span class="text-sm font-medium text-primary-600 dark:text-primary-400"
                        x-text="selectedIds.length + ' ' + __('profile.selected')"></span>
                    <button @click="printSelected('normal')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors"
                        title="A4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>
                    <button @click="printSelected('mini')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors"
                        title="Mini Imprimante">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h5M8 14h8M8 18h8" />
                        </svg>
                    </button>
                    <button @click="printSelected('qr')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors"
                        title="QR Code">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                        </svg>
                    </button>
                    <button @click="deleteSelected()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <?= __('common.delete')?>
                    </button>
                </div>
            </template>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <button @click="showCreateModal = true; createType = 'voucher'"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('voucher.create')?>
            </button>
            <button @click="showGenerateModal = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <?= __('voucher.generate')?>
            </button>
        </div>
    </div>

    <!-- Barre de filtres -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] px-4 py-3 mb-4">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
            <!-- Recherche -->
            <div class="relative col-span-2 md:col-span-1">
                <input type="text" x-model="search" @input.debounce.300ms="loadVouchers()"
                    placeholder="<?= __('voucher.search_code')?>"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-gray-400">
                <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <!-- Filtre statut -->
            <select x-model="statusFilter" @change="loadVouchers()"
                class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('common.all_statuses')?>
                </option>
                <option value="unused">
                    <?= __('voucher.unused')?>
                </option>
                <option value="active">
                    <?= __('voucher.active')?>
                </option>
                <option value="expired">
                    <?= __('voucher.expired')?>
                </option>
                <option value="disabled">
                    <?= __('voucher.disabled')?>
                </option>
            </select>

            <!-- Filtre type -->
            <select x-model="typeFilter" @change="loadVouchers()"
                class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('common.all_types')?>
                </option>
                <option value="voucher">
                    <?= __('voucher.vouchers')?>
                </option>
                <option value="ticket">
                    <?= __('voucher.tickets')?>
                </option>
            </select>

            <!-- Filtre zone -->
            <select x-model="zoneFilter" @change="loadVouchers()"
                class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('common.all_zones')?>
                </option>
                <option value="none">
                    <?= __('common.no_zone')?>
                </option>
                <template x-for="zone in zones" :key="zone.id">
                    <option :value="zone.id" x-text="zone.name"></option>
                </template>
            </select>

            <!-- Filtre commentaire -->
            <select x-model="notesFilter" @change="loadVouchers()"
                class="w-full py-2 px-3 text-sm border border-gray-200 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('common.all_comments')?>
                </option>
                <template x-for="note in availableNotes" :key="note">
                    <option :value="note" x-text="note.length > 35 ? note.substring(0, 35) + '...' : note"></option>
                </template>
            </select>
        </div>

        <!-- Indicateur filtres actifs + bouton reset -->
        <div x-show="search || statusFilter || typeFilter || zoneFilter || notesFilter" x-cloak
            class="flex items-center gap-2 mt-3 pt-3 border-t border-gray-100 dark:border-[#21262d]">
            <span class="text-xs text-gray-500 dark:text-gray-400">
                <?= __('common.active_filters')?>
            </span>
            <template x-if="search">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-full">
                    <span x-text="__('voucher.search_label')"></span> "<span x-text="search"></span>"
                    <button @click="search = ''; loadVouchers()"
                        class="hover:text-primary-900 dark:hover:text-primary-100">&times;</button>
                </span>
            </template>
            <template x-if="statusFilter">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 rounded-full">
                    <span x-text="statusFilter"></span>
                    <button @click="statusFilter = ''; loadVouchers()"
                        class="hover:text-amber-900 dark:hover:text-amber-100">&times;</button>
                </span>
            </template>
            <template x-if="typeFilter">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-full">
                    <span x-text="typeFilter"></span>
                    <button @click="typeFilter = ''; loadVouchers()"
                        class="hover:text-blue-900 dark:hover:text-blue-100">&times;</button>
                </span>
            </template>
            <template x-if="zoneFilter">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 rounded-full">
                    Zone
                    <button @click="zoneFilter = ''; loadVouchers()"
                        class="hover:text-green-900 dark:hover:text-green-100">&times;</button>
                </span>
            </template>
            <template x-if="notesFilter">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 text-xs bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-full">
                    Note
                    <button @click="notesFilter = ''; loadVouchers()"
                        class="hover:text-purple-900 dark:hover:text-purple-100">&times;</button>
                </span>
            </template>
            <button
                @click="search = ''; statusFilter = ''; typeFilter = ''; zoneFilter = ''; notesFilter = ''; loadVouchers()"
                class="ml-auto text-xs text-red-500 hover:text-red-700 dark:hover:text-red-400 font-medium">
                <?= __('common.clear_all')?>
            </button>
        </div>
    </div>

    <!-- Table des vouchers -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-[#21262d]/50">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" @change="toggleSelectAll($event.target.checked)"
                                :checked="selectedIds.length === vouchers.length && vouchers.length > 0"
                                :indeterminate="selectedIds.length > 0 && selectedIds.length < vouchers.length"
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.type')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.username_code')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('common.password')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.profile')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.zone')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.status')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.time')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.data')?>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.price')?>
                        </th>
                        <th
                            class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <?= __('voucher.actions')?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="voucher in vouchers" :key="voucher.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/30 transition-colors"
                            :class="selectedIds.includes(voucher.id) ? 'bg-primary-50 dark:bg-primary-900/20' : ''">
                            <!-- Checkbox -->
                            <td class="px-4 py-4 whitespace-nowrap">
                                <input type="checkbox" :checked="selectedIds.includes(voucher.id)"
                                    @change="toggleSelect(voucher.id)"
                                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            </td>
                            <!-- Type -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                    :class="voucher.has_password ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'"
                                    x-text="voucher.has_password ? __('voucher.tickets').replace(/s$/, '') : __('voucher.vouchers').replace(/s$/, '')"></span>
                            </td>
                            <!-- Username -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="font-mono font-semibold text-gray-900 dark:text-white"
                                        x-text="voucher.username"></span>
                                    <button @click="copyToClipboard(voucher.username)"
                                        class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        title="<?= __('common.copy')?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <!-- Password -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="voucher.has_password">
                                    <div class="flex items-center">
                                        <span class="font-mono text-gray-600 dark:text-gray-400"
                                            x-text="voucher.show_password ? voucher.plain_password : '••••••'"></span>
                                        <button @click="voucher.show_password = !voucher.show_password"
                                            class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg x-show="!voucher.show_password" class="w-4 h-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="voucher.show_password" class="w-4 h-4" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                        <button @click="copyToClipboard(voucher.plain_password)"
                                            class="ml-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            title="<?= __('common.copy')?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!voucher.has_password">
                                    <span class="text-gray-400">-</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400"
                                x-text="voucher.profile_name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <template x-if="voucher.zone_name">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                        :style="'background-color: ' + (voucher.zone_color || '#3b82f6') + '20; color: ' + (voucher.zone_color || '#3b82f6')"
                                        x-text="voucher.zone_name"></span>
                                </template>
                                <template x-if="!voucher.zone_name">
                                    <span class="text-xs text-gray-400">Global</span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="getStatusClass(voucher.status)"
                                    x-text="getStatusLabel(voucher.status)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <template x-if="voucher.time_limit">
                                    <div>
                                        <span x-text="formatTime(voucher.time_limit - voucher.time_used)"></span>
                                        <div class="w-20 h-1.5 bg-gray-200 dark:bg-[#30363d] rounded-full mt-1">
                                            <div class="h-full rounded-full transition-all"
                                                :class="getProgressColor(getPercent(voucher.time_used, voucher.time_limit))"
                                                :style="'width:' + getPercent(voucher.time_used, voucher.time_limit) + '%'">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!voucher.time_limit">
                                    <span class="text-gray-400" x-text="__('common.unlimited')"></span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <template x-if="voucher.data_limit">
                                    <div>
                                        <span x-text="formatBytes(voucher.data_limit - voucher.data_used)"></span>
                                        <div class="w-20 h-1.5 bg-gray-200 dark:bg-[#30363d] rounded-full mt-1">
                                            <div class="h-full rounded-full transition-all"
                                                :class="getProgressColor(getPercent(voucher.data_used, voucher.data_limit))"
                                                :style="'width:' + getPercent(voucher.data_used, voucher.data_limit) + '%'">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="!voucher.data_limit">
                                    <span class="text-gray-400" x-text="__('common.unlimited')"></span>
                                </template>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="voucher.price > 0 ? voucher.price + ' XAF' : '-'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="printSingle(voucher, 'mini')" title="Mini Imprimante"
                                        class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M8 10h5M8 14h8M8 18h8" />
                                        </svg>
                                    </button>
                                    <button @click="printSingle(voucher, 'qr')" title="QR Code"
                                        class="text-gray-400 hover:text-purple-600 dark:hover:text-purple-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                                        </svg>
                                    </button>
                                    <button @click="viewVoucher(voucher)"
                                        class="text-gray-400 hover:text-primary-600 dark:hover:text-primary-400"
                                        title="<?= __('common.details')?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button @click="resetVoucher(voucher)"
                                        class="text-gray-400 hover:text-yellow-600 dark:hover:text-yellow-400"
                                        title="<?= __('session.reset')?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                    <button @click="toggleVoucher(voucher)"
                                        :class="voucher.status === 'disabled' ? 'text-green-500 hover:text-green-600' : 'text-yellow-500 hover:text-yellow-600'"
                                        :title="voucher.status === 'disabled' ? __('common.activate') : __('common.deactivate')">
                                        <svg x-show="voucher.status !== 'disabled'" class="w-5 h-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                        <svg x-show="voucher.status === 'disabled'" class="w-5 h-5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteVoucher(voucher)"
                                        class="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                        title="<?= __('common.delete')?>">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                <?= __('common.showing_x_to_y_of_z', ['from' => ''])?><span
                    x-text="((currentPage - 1) * perPage) + 1"></span> -
                <span x-text="Math.min(currentPage * perPage, totalItems)"></span> / <span x-text="totalItems"></span>
                <?= __('common.results')?>
            </p>
            <div class="flex gap-2">
                <button @click="currentPage--; loadVouchers()" :disabled="currentPage <= 1"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.previous')?>
                </button>
                <button @click="currentPage++; loadVouchers()" :disabled="currentPage >= totalPages"
                    class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 dark:hover:bg-[#30363d]">
                    <?= __('common.next')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Créer -->
    <div x-show="showCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showCreateModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6"
                x-data="{ createTab: 'account' }">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="__('voucher.create_title')"></h3>

                <!-- Onglets -->
                <div class="flex border-b border-gray-200 dark:border-[#30363d] mb-4">
                    <button type="button" @click="createTab = 'account'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="createTab === 'account' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                        <?= __('voucher.tab_account')?>
                    </button>
                    <button type="button" @click="createTab = 'client'"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="createTab === 'client' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'">
                        <svg class="w-4 h-4 inline mr-1.5 -mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <?= __('voucher.tab_client_note')?>
                        <span x-show="newVoucher.customer_name || newVoucher.customer_phone || newVoucher.notes"
                            class="ml-1.5 w-2 h-2 inline-block rounded-full bg-primary-500"></span>
                    </button>
                </div>

                <form @submit.prevent="createVoucher()">
                    <!-- Onglet Compte -->
                    <div x-show="createTab === 'account'" class="space-y-4">
                        <!-- Type Switch -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-xl border border-gray-200 dark:border-[#30363d]">
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="createType === 'voucher' ? __('voucher.type_voucher') : __('voucher.type_ticket')"></span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="createType === 'voucher' ? 'Code d\'accès unique (PIN)' : 'Identifiant et Mot de passe sécurisés'"></span>
                            </div>
                            <button type="button" 
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-[#161b22]"
                                :class="createType === 'ticket' ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                                @click="createType = createType === 'voucher' ? 'ticket' : 'voucher'">
                                <span class="sr-only">Changer de type</span>
                                <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="createType === 'ticket' ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                x-text="createType === 'ticket' ? 'Username' : __('voucher.code')"></label>
                            <input type="text" x-model="newVoucher.username" required
                                :placeholder="createType === 'ticket' ? 'user123' : 'ABCD1234'"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>

                        <!-- Password (only for ticket) -->
                        <div x-show="createType === 'ticket'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('common.password')?>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" x-model="newVoucher.password" :required="createType === 'ticket'"
                                    placeholder="<?= __('common.password')?>"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                <button type="button" @click="newVoucher.password = generateRandomPassword()"
                                    class="px-3 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]"
                                    title="<?= __('common.generate')?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Profil -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('common.profile')?>
                            </label>
                            <select x-model="newVoucher.profile_id" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="">
                                    <?= __('voucher.select_profile')?>
                                </option>
                                <template x-for="profile in profiles" :key="profile.id">
                                    <option :value="profile.id" x-text="profile.name + ' - ' + profile.price + ' XAF'">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <!-- Affichage du prix du profil sélectionné -->
                        <div x-show="newVoucher.profile_id" class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="__('voucher.profile_price')"></span>
                                <span class="font-bold text-blue-600 dark:text-blue-400"
                                    x-text="getSelectedProfilePrice(newVoucher.profile_id) + ' XAF'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Onglet Client & Note -->
                    <div x-show="createTab === 'client'" class="space-y-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            <?= __('common.optional')?>
                        </p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('voucher.client_name')?>
                            </label>
                            <input type="text" x-model="newVoucher.customer_name" placeholder="Ex: Jean Dupont"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('voucher.client_phone')?>
                            </label>
                            <input type="text" x-model="newVoucher.customer_phone" placeholder="Ex: +229 97 00 00 00"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('voucher.comment')?>
                            </label>
                            <input type="text" x-model="newVoucher.notes"
                                :placeholder="'Auto: ' + new Date().toLocaleDateString('fr-FR') + ' ' + new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'}) + ' - ' + (getSelectedProfileName(newVoucher.profile_id) || 'Profil')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <p class="mt-1 text-xs text-gray-400"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Vendeur associé (Optionnel)
                            </label>
                            <select x-model="newVoucher.vendeur_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="">Auto-defini par le Systeme (NAS)</option>
                                <template x-for="vendeur in vendeurs" :key="vendeur.id">
                                    <option :value="vendeur.id"
                                        x-text="vendeur.username + (vendeur.full_name ? ' (' + vendeur.full_name + ')' : '')">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showCreateModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <?= __('common.create')?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Générer -->
    <div x-show="showGenerateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showGenerateModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="__('voucher.generate_title')"></h3>
                <form @submit.prevent="generateVouchers()">
                    <div class="space-y-4">
                        <!-- Generate Header Options -->
                        <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-xl border border-gray-200 dark:border-[#30363d] p-4 mb-5">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Configuration</h4>
                            <!-- Type Switch -->
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="generateForm.type === 'voucher' ? __('voucher.type_voucher') : __('voucher.type_ticket')"></span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="generateForm.type === 'voucher' ? 'Code d\'accès unique (PIN)' : 'Identifiant et Mot de passe sécurisés'"></span>
                                </div>
                                <button type="button" 
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-[#161b22]"
                                    :class="generateForm.type === 'ticket' ? 'bg-primary-600' : 'bg-gray-300 dark:bg-gray-600'"
                                    @click="generateForm.type = generateForm.type === 'voucher' ? 'ticket' : 'voucher'">
                                    <span class="sr-only">Changer de type</span>
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="generateForm.type === 'ticket' ? 'translate-x-5' : 'translate-x-0'"></span>
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('voucher.quantity')?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="number" x-model="generateForm.count" min="1" max="1000" required
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('voucher.code_length')?>
                                    </label>
                                    <input type="number" x-model="generateForm.code_length" min="4" max="8"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('voucher.prefix')?>
                                    </label>
                                    <input type="text" x-model="generateForm.prefix" maxlength="10"
                                        placeholder="<?= __('voucher.prefix_placeholder')?>"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 placeholder-gray-400">
                                </div>
                            </div>
                        </div>



                        <!-- Password Specs -->
                        <div x-show="generateForm.type === 'ticket'" x-transition class="bg-indigo-50 dark:bg-indigo-900/10 rounded-xl border border-indigo-100 dark:border-indigo-900/30 p-4 mb-5">
                            <h4 class="text-xs font-semibold text-indigo-700 dark:text-indigo-400 uppercase tracking-wider mb-3">Sécurité Mot de Passe</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-indigo-900 dark:text-indigo-300 mb-1">
                                        <?= __('voucher.password_length')?>
                                    </label>
                                    <input type="number" x-model="generateForm.password_length" min="4" max="16"
                                        class="w-full px-3 py-2 text-sm border border-indigo-200 dark:border-indigo-800/50 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-indigo-900 dark:text-indigo-300 mb-1">
                                        <?= __('voucher.password_type')?>
                                    </label>
                                    <select x-model="generateForm.password_type"
                                        class="w-full px-3 py-2 text-sm border border-indigo-200 dark:border-indigo-800/50 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500">
                                        <option value="alphanumeric">
                                            <?= __('voucher.password_alphanumeric')?>
                                        </option>
                                        <option value="numeric">
                                            <?= __('voucher.password_numeric')?>
                                        </option>
                                        <option value="alpha">
                                            <?= __('voucher.password_alpha')?>
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Facturation & Service -->
                        <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-xl border border-gray-200 dark:border-[#30363d] p-4 mb-5">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">Service & Tarification</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('voucher.zone_required')?> <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="generateForm.zone_id" @change="loadZoneProfiles()" required
                                        class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                        <option value="">
                                            <?= __('common.all_zones')?>
                                        </option>
                                        <template x-for="zone in zones" :key="zone.id">
                                            <option :value="zone.id" x-text="zone.name"></option>
                                        </template>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('voucher.profile_required')?> <span class="text-red-500">*</span>
                                    </label>
                                    <select x-model="generateForm.profile_id" required
                                        :disabled="!generateForm.zone_id || loadingZoneProfiles"
                                        class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed focus:ring-2 focus:ring-primary-500">
                                        <option value=""
                                            x-text="!generateForm.zone_id ? __('voucher.select_profile') : (loadingZoneProfiles ? __('common.loading') : __('voucher.select_profile'))">
                                        </option>
                                        <template x-for="profile in zoneProfiles" :key="profile.id">
                                            <option :value="profile.id" x-text="profile.name"></option>
                                        </template>
                                    </select>
                                    <p x-show="generateForm.zone_id && zoneProfiles.length === 0 && !loadingZoneProfiles"
                                        class="mt-1 text-xs text-red-500" x-text="__('api.no_profile_available')"></p>
                                </div>
                                
                                <div x-show="generateForm.profile_id" x-transition class="mt-2 pt-3 border-t border-gray-200 dark:border-[#30363d] flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400"
                                        x-text="__('voucher.profile_price')"></span>
                                    <span class="text-sm font-bold text-green-600 dark:text-green-400 px-3 py-1 bg-green-50 dark:bg-green-900/20 rounded-full"
                                        x-text="getSelectedZoneProfilePrice(generateForm.profile_id) + ' XAF'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Commentaire (optionnel) -->
                        <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('voucher.comment')?> (
                                <?= __('common.optional')?>)
                            </label>
                            <input type="text" x-model="generateForm.notes"
                                :placeholder="'Auto: ' + new Date().toLocaleDateString('fr-FR') + ' ' + new Date().toLocaleTimeString('fr-FR', {hour:'2-digit', minute:'2-digit'}) + ' - ' + (getSelectedZoneProfileName(generateForm.profile_id) || 'Profil')"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            <p class="mt-1 text-xs text-gray-400"></p>
                        </div>

                        <!-- Vendeur (optionnel) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Vendeur associé (Optionnel)
                            </label>
                            <select x-model="generateForm.vendeur_id"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="">Auto-defini par le Systeme (NAS)</option>
                                <template x-for="vendeur in vendeurs" :key="vendeur.id">
                                    <option :value="vendeur.id"
                                        x-text="vendeur.username + (vendeur.full_name ? ' (' + vendeur.full_name + ')' : '')">
                                    </option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showGenerateModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            <?= __('common.generate')?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Détails -->
    <div x-show="showDetailsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDetailsModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <span x-text="__('voucher.details_title')"></span>: <span class="font-mono"
                        x-text="selectedVoucher?.username"></span>
                </h3>
                <div class="space-y-4" x-show="selectedVoucher">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.status')"></p>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mt-1"
                                :class="getStatusClass(selectedVoucher?.status)"
                                x-text="getStatusLabel(selectedVoucher?.status)"></span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.price')"></p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="(selectedVoucher?.price || 0) + ' XAF'"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.time')"></p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="formatTime(selectedVoucher?.time_used || 0) + (selectedVoucher?.time_limit ? ' / ' + formatTime(selectedVoucher?.time_limit) : '')">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.data')"></p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="formatBytes(selectedVoucher?.data_used || 0) + (selectedVoucher?.data_limit ? ' / ' + formatBytes(selectedVoucher?.data_limit) : '')">
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('session.download')"></p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.download_speed ? formatSpeed(selectedVoucher.download_speed) : __('common.unlimited')">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('session.upload')"></p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.upload_speed ? formatSpeed(selectedVoucher.upload_speed) : __('common.unlimited')">
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.created_at')"></p>
                            <p class="text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.created_at ? new Date(selectedVoucher.created_at).toLocaleString('fr-FR') : '-'">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.first_connection')">
                            </p>
                            <p class="text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.first_use ? new Date(selectedVoucher.first_use).toLocaleString('fr-FR') : '-'">
                            </p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Vendeur</p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.vendeur_name || '-'">
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Gérant</p>
                            <p class="font-semibold text-gray-900 dark:text-white"
                                x-text="selectedVoucher?.gerant_name || '-'">
                            </p>
                        </div>
                    </div>
                    <!-- Date d'expiration (affiché seulement si le voucher est actif) -->
                    <div x-show="selectedVoucher?.valid_until" class="p-3 rounded-lg"
                        :class="isExpired(selectedVoucher?.valid_until) ? 'bg-red-50 dark:bg-red-900/20' : 'bg-green-50 dark:bg-green-900/20'">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="__('voucher.expiration_date')"></p>
                                <p class="font-semibold"
                                    :class="isExpired(selectedVoucher?.valid_until) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                    x-text="selectedVoucher?.valid_until ? new Date(selectedVoucher.valid_until).toLocaleString('fr-FR') : '-'">
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400"
                                    x-text="__('voucher.time_remaining')"></p>
                                <p class="font-semibold"
                                    :class="isExpired(selectedVoucher?.valid_until) ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'"
                                    x-text="getRemainingTime(selectedVoucher?.valid_until)"></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.active_sessions')"></p>
                        <p class="font-semibold text-gray-900 dark:text-white"
                            x-text="selectedVoucher?.active_sessions || 0"></p>
                    </div>

                    <!-- Infos client & commentaire -->
                    <div x-show="selectedVoucher?.customer_name || selectedVoucher?.customer_phone || selectedVoucher?.notes"
                        class="p-3 bg-gray-50 dark:bg-[#0d1117] rounded-lg border border-gray-200 dark:border-[#30363d] space-y-2">
                        <div class="grid grid-cols-2 gap-4"
                            x-show="selectedVoucher?.customer_name || selectedVoucher?.customer_phone">
                            <div x-show="selectedVoucher?.customer_name">
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('common.client')"></p>
                                <p class="font-semibold text-gray-900 dark:text-white"
                                    x-text="selectedVoucher?.customer_name"></p>
                            </div>
                            <div x-show="selectedVoucher?.customer_phone">
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('common.phone')"></p>
                                <p class="font-semibold text-gray-900 dark:text-white"
                                    x-text="selectedVoucher?.customer_phone"></p>
                            </div>
                        </div>
                        <div x-show="selectedVoucher?.notes">
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="__('voucher.comment')"></p>
                            <p class="text-gray-900 dark:text-white text-sm mt-0.5" x-text="selectedVoucher?.notes"></p>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button @click="showDetailsModal = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('common.close')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Résultats génération -->
    <div x-show="showResultsModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showResultsModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-2xl w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    <span x-text="generatedVouchers.length"></span>
                    <span x-text="generatedVouchers[0]?.plain_password ? 'tickets' : 'vouchers'"></span> <span
                        x-text="__('voucher.results_generated')"></span>
                </h3>
                <div class="max-h-80 overflow-y-auto border border-gray-200/60 dark:border-[#30363d] rounded-lg">
                    <!-- Si tickets (avec password) -->
                    <template x-if="generatedVouchers[0]?.plain_password">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-[#21262d] sticky top-0">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                        Username</th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                        Password</th>
                                    <th
                                        class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">
                                        <?= __('common.actions')?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                                <template x-for="v in generatedVouchers" :key="v.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                        <td class="px-4 py-2 font-mono text-sm text-gray-900 dark:text-white"
                                            x-text="v.username"></td>
                                        <td class="px-4 py-2 font-mono text-sm text-gray-600 dark:text-gray-400"
                                            x-text="v.plain_password"></td>
                                        <td class="px-4 py-2 text-right">
                                            <button @click="copyToClipboard(v.username + ' / ' + v.plain_password)"
                                                class="text-gray-400 hover:text-primary-600"
                                                title="<?= __('common.copy')?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </template>
                    <!-- Si vouchers (sans password) -->
                    <template x-if="!generatedVouchers[0]?.plain_password">
                        <div class="grid grid-cols-3 gap-2 p-4">
                            <template x-for="v in generatedVouchers" :key="v.id">
                                <div class="font-mono text-sm bg-gray-50 dark:bg-[#21262d] px-3 py-2 rounded text-center"
                                    x-text="v.username"></div>
                            </template>
                        </div>
                    </template>
                </div>
                <div class="mt-6 flex justify-between">
                    <div class="flex gap-2">
                        <button @click="printVouchers('normal')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]"
                            title="A4">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            A4
                        </button>
                        <button @click="printVouchers('mini')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]"
                            title="Mini">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 10h5M8 14h8M8 18h8" />
                            </svg>
                            Mini
                        </button>
                        <button @click="printVouchers('qr')"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]"
                            title="QR Code">
                            <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zM18 14h2v2h-2zM14 18h2v2h-2zM18 18h2v2h-2z" />
                            </svg>
                            QR
                        </button>
                        <button @click="exportCSV()"
                            class="px-4 py-2 bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            CSV
                        </button>
                    </div>
                    <button @click="showResultsModal = false"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <?= __('common.close')?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function vouchersPage() {
        return {
            vouchers: [],
            profiles: [],
            zones: [],
            availableNotes: [],
            search: '',
            statusFilter: '',
            typeFilter: '',
            zoneFilter: '',
            notesFilter: '',
            currentPage: 1,
            perPage: 20,
            totalItems: 0,
            totalPages: 1,
            showCreateModal: false,
            showGenerateModal: false,
            showDetailsModal: false,
            showResultsModal: false,
            selectedVoucher: null,
            generatedVouchers: [],
            createType: 'voucher',
            selectedIds: [],
            zoneProfiles: [],
            loadingZoneProfiles: false,
            newVoucher: {
                username: '',
                password: '',
                profile_id: '',
                zone_id: '',
                customer_name: '',
                customer_phone: '',
                notes: '',
                vendeur_id: ''
            },
            generateForm: {
                type: 'voucher',
                count: 10,
                prefix: '',
                code_length: 8,
                password_length: 6,
                password_type: 'alphanumeric',
                profile_id: '',
                zone_id: '',
                notes: '',
                vendeur_id: ''
            },
            defaultTemplate: null,
            vendeurs: [],

            async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones(),
                    this.loadNotes(),
                    this.loadDefaultTemplate(),
                    this.loadVendeurs()
                ]);
                await this.loadVouchers();
            },

            async loadDefaultTemplate() {
                try {
                    const response = await API.get('/templates/vouchers/default');
                    if (response.success) {
                        this.defaultTemplate = response.data;
                    }
                } catch (e) {
                    // Pas de template par défaut, on utilisera les valeurs par défaut
                }
            },

            async loadVendeurs() {
                try {
                    const response = await API.get('/users?role=vendeur');
                    this.vendeurs = response.data || [];
                } catch (e) {
                    console.error('Error loading vendeurs', e);
                }
            },

            async loadProfiles() {
                try {
                    const response = await API.get('/profiles?active=1');
                    this.profiles = response.data;
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data.filter(z => z.is_active);
                } catch (error) {
                    console.error('Error loading zones:', error);
                }
            },

            async loadNotes() {
                try {
                    const response = await API.get('/vouchers/notes');
                    this.availableNotes = response.data || [];
                } catch (error) {
                    console.error('Error loading notes:', error);
                }
            },

            async loadVouchers() {
                try {
                    let url = `/vouchers?page=${this.currentPage}&per_page=${this.perPage}`;
                    if (this.search) url += `&search=${encodeURIComponent(this.search)}`;
                    if (this.statusFilter) url += `&status=${this.statusFilter}`;
                    if (this.typeFilter) url += `&type=${this.typeFilter}`;
                    if (this.zoneFilter) url += `&zone=${this.zoneFilter}`;
                    if (this.notesFilter) url += `&notes=${encodeURIComponent(this.notesFilter)}`;

                    const response = await API.get(url);
                    this.vouchers = response.data.data.map(v => ({ ...v, show_password: false }));
                    this.totalItems = response.data.total;
                    this.totalPages = response.data.total_pages;
                    // Réinitialiser la sélection lors du rechargement
                    this.selectedIds = [];
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            // Sélection
            toggleSelect(id) {
                const index = this.selectedIds.indexOf(id);
                if (index === -1) {
                    this.selectedIds.push(id);
                } else {
                    this.selectedIds.splice(index, 1);
                }
            },

            toggleSelectAll(checked) {
                if (checked) {
                    this.selectedIds = this.vouchers.map(v => v.id);
                } else {
                    this.selectedIds = [];
                }
            },

            printSelected(printType = 'normal') {
                const selected = this.vouchers.filter(v => this.selectedIds.includes(v.id));
                if (selected.length === 0) return;

                const items = selected.map(v => {
                    const profile = this.profiles.find(p => p.id == v.profile_id);
                    return {
                        code: v.username,
                        password: v.has_password ? v.plain_password : null,
                        profileName: profile?.name || '',
                        time: profile?.time_limit ? formatTime(profile.time_limit) : '',
                        speed: profile?.download_speed ? formatSpeed(profile.download_speed) : '',
                        price: profile?.price ? Number(profile.price).toLocaleString() + ' XAF' : ''
                    };
                });

                this.openPrintWindow(items, printType);
            },

            printSingle(voucher, printType = 'normal') {
                const profile = this.getProfileInfo(voucher.profile_id);
                const items = [{
                    code: voucher.username,
                    password: voucher.has_password ? voucher.plain_password : null,
                    profileName: profile.name || (voucher.profile_name || ''),
                    time: voucher.time_limit ? formatTime(voucher.time_limit) : '',
                    speed: voucher.download_speed ? formatSpeed(voucher.download_speed) : '',
                    price: voucher.price ? Number(voucher.price).toLocaleString() + ' XAF' : ''
                }];
                this.openPrintWindow(items, printType);
            },

            async deleteSelected() {
                if (this.selectedIds.length === 0) return;

                if (!confirmAction(__('confirm.delete_message'))) return;

                try {
                    // Supprimer un par un
                    let deleted = 0;
                    let errors = 0;

                    for (const id of this.selectedIds) {
                        try {
                            await API.delete(`/vouchers/${id}`);
                            deleted++;
                        } catch (e) {
                            errors++;
                        }
                    }

                    if (deleted > 0) {
                        showToast(__('voucher.msg_deleted'));
                    }
                    if (errors > 0) {
                        showToast(__('api.error_deleting'), 'error');
                    }

                    this.selectedIds = [];
                    this.loadVouchers();
                } catch (error) {
                    showToast(__('api.error_deleting'), 'error');
                }
            },

            async createVoucher() {
                if (!this.newVoucher.profile_id) {
                    showToast(__('voucher.select_profile'), 'error');
                    return;
                }

                try {
                    const data = {
                        username: this.createType === 'ticket' ? this.newVoucher.username : this.newVoucher.username.toUpperCase(),
                        password: this.createType === 'ticket' ? this.newVoucher.password : null,
                        profile_id: this.newVoucher.profile_id,
                        customer_name: this.newVoucher.customer_name || null,
                        customer_phone: this.newVoucher.customer_phone || null,
                        notes: this.newVoucher.notes || null,
                        vendeur_id: this.newVoucher.vendeur_id || null
                    };

                    await API.post('/vouchers', data);
                    showToast(__('voucher.msg_created'));
                    this.showCreateModal = false;
                    this.resetNewVoucher();
                    this.loadVouchers();
                    this.loadNotes();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async generateVouchers() {
                // Validation des champs obligatoires
                if (!this.generateForm.profile_id) {
                    showToast(__('voucher.select_profile'), 'error');
                    return;
                }
                if (!this.generateForm.zone_id) {
                    showToast(__('common.all_zones'), 'error');
                    return;
                }

                try {
                    const data = {
                        type: this.generateForm.type,
                        count: parseInt(this.generateForm.count),
                        length: parseInt(this.generateForm.code_length) || 8,
                        prefix: this.generateForm.type === 'ticket' ? this.generateForm.prefix : this.generateForm.prefix.toUpperCase(),
                        password_length: this.generateForm.type === 'ticket' ? parseInt(this.generateForm.password_length) : null,
                        password_type: this.generateForm.type === 'ticket' ? this.generateForm.password_type : null,
                        profile_id: this.generateForm.profile_id,
                        zone_id: this.generateForm.zone_id,
                        notes: this.generateForm.notes || null,
                        vendeur_id: this.generateForm.vendeur_id || null
                    };

                    const response = await API.post('/vouchers/generate', data);
                    this.generatedVouchers = response.data.vouchers;
                    const typeLabel = this.generateForm.type === 'ticket' ? 'tickets' : 'vouchers';
                    showToast(__('voucher.msg_generate_success'));
                    this.showGenerateModal = false;
                    this.showResultsModal = true;
                    this.loadVouchers();
                    this.loadNotes();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async viewVoucher(voucher) {
                try {
                    const response = await API.get(`/vouchers/${voucher.id}`);
                    this.selectedVoucher = response.data;
                    this.showDetailsModal = true;
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            async resetVoucher(voucher) {
                if (!confirmAction(__('confirm.delete_message'))) return;

                try {
                    await API.post(`/vouchers/${voucher.id}/reset`);
                    showToast(__('notify.success'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async toggleVoucher(voucher) {
                const action = voucher.status === 'disabled' ? 'enable' : 'disable';
                try {
                    await API.post(`/vouchers/${voucher.id}/${action}`);
                    showToast(action === 'enable' ? __('voucher.msg_enabled') : __('voucher.msg_disabled'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async deleteVoucher(voucher) {
                if (!confirmAction(__('voucher.msg_delete_confirm'))) return;

                try {
                    await API.delete(`/vouchers/${voucher.id}`);
                    showToast(__('voucher.msg_deleted'));
                    this.loadVouchers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            resetNewVoucher() {
                this.newVoucher = {
                    username: '',
                    password: '',
                    profile_id: '',
                    zone_id: '',
                    customer_name: '',
                    customer_phone: '',
                    notes: '',
                    vendeur_id: ''
                };
                this.createType = 'voucher';
            },

            getSelectedProfilePrice(profileId) {
                if (!profileId) return 0;
                const profile = this.profiles.find(p => p.id == profileId);
                return profile ? profile.price : 0;
            },

            getSelectedProfileName(profileId) {
                if (!profileId) return '';
                const profile = this.profiles.find(p => p.id == profileId);
                return profile ? profile.name : '';
            },

            getSelectedZoneProfilePrice(profileId) {
                if (!profileId) return 0;
                const profile = this.zoneProfiles.find(p => p.id == profileId);
                return profile ? profile.price : 0;
            },

            getSelectedZoneProfileName(profileId) {
                if (!profileId) return '';
                const profile = this.zoneProfiles.find(p => p.id == profileId);
                return profile ? profile.name : '';
            },

            async loadZoneProfiles() {
                // Réinitialiser le profil sélectionné quand la zone change
                this.generateForm.profile_id = '';
                this.zoneProfiles = [];

                if (!this.generateForm.zone_id) {
                    return;
                }

                this.loadingZoneProfiles = true;
                try {
                    const response = await API.get(`/zones/${this.generateForm.zone_id}/profiles`);
                    // Filtrer les profils actifs
                    this.zoneProfiles = (response.data || []).filter(p => p.is_active);
                } catch (error) {
                    console.error('Erreur chargement profils zone:', error);
                    showToast(__('voucher.msg_load_profiles_error'), 'error');
                } finally {
                    this.loadingZoneProfiles = false;
                }
            },

            generateRandomPassword(length = 8) {
                const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                let password = '';
                for (let i = 0; i < length; i++) {
                    password += chars.charAt(Math.floor(Math.random() * chars.length));
                }
                return password;
            },

            copyToClipboard(text) {
                navigator.clipboard.writeText(text);
                showToast(__('common.copied'));
            },

            getProfileInfo(profileId) {
                const p = this.profiles.find(pr => pr.id == profileId);
                if (!p) return {};
                return {
                    name: p.name || '',
                    price: p.price ? Number(p.price).toLocaleString() + ' XAF' : '',
                    time: p.time_limit ? formatTime(p.time_limit) : 'Illimité',
                    speed: p.download_speed ? formatSpeed(p.download_speed) : ''
                };
            },

            printVouchers(printType = 'normal') {
                const profile = this.getProfileInfo(this.generateForm.profile_id);
                const items = this.generatedVouchers.map(v => ({
                    code: v.username,
                    password: v.plain_password || null,
                    profileName: profile.name || '',
                    time: profile.time || '',
                    speed: profile.speed || '',
                    price: profile.price || ''
                }));

                this.openPrintWindow(items, printType);
            },

            // Génère la page d'impression en utilisant le template par défaut
            openPrintWindow(items, printType = 'normal') {
                if (!items.length) return;

                const t = this.defaultTemplate || {};
                const primaryColor = t.primary_color || '#1a1a2e';
                const borderColor = t.border_color || '#e2e8f0';
                const bgColor = t.background_color || '#ffffff';
                const textColor = t.text_color || '#0f172a';
                let cols = t.columns_count || 4;
                const headerText = t.header_text || '';
                const footerText = t.footer_text || '';
                const showPassword = t.show_password !== undefined ? !!parseInt(t.show_password) : true;
                const showValidity = t.show_validity !== undefined ? !!parseInt(t.show_validity) : true;
                const showSpeed = t.show_speed !== undefined ? !!parseInt(t.show_speed) : false;
                const showPrice = t.show_price !== undefined ? !!parseInt(t.show_price) : true;
                const showLogo = t.show_logo !== undefined ? !!parseInt(t.show_logo) : true;
                let showQr = t.show_qr_code !== undefined ? !!parseInt(t.show_qr_code) : false;
                const showHeader = showLogo || headerText;
                let paperSize = t.paper_size || 'A4';
                let orientation = t.orientation || 'portrait';
                if (printType === 'mini') {
                    cols = 1;
                    paperSize = '58mm auto';
                } else if (printType === 'qr') {
                    showQr = true;
                }

                const ticketsHtml = items.map((v, i) => {
                    let html = '<div class="ticket">';

                    // Header
                    if (showHeader) {
                        html += `<div class="t-header">${headerText || v.profileName || 'WiFi Hotspot'}</div>`;
                    }

                    // Body (with optional QR)
                    html += `<div class="t-body-container">`;
                    html += `<div class="${showQr ? 't-body-qr' : 't-info'}">`;
                    if (showQr) {
                        html += `<div class="t-qr" id="qr-${i}"></div><div class="t-info">`;
                    }
                    html += `<div class="t-row"><span class="t-label">PIN / CODE</span><span class="t-value">${v.code}</span></div>`;
                    if (showPassword && v.password) {
                        html += `<div class="t-row"><span class="t-label">PASSWORD</span><span class="t-value">${v.password}</span></div>`;
                    }
                    if (showQr) {
                        html += `</div>`;
                    }
                    html += '</div></div>';

                    // Footer info
                    const infos = [];
                    if (showValidity && v.time) infos.push(`<span>${v.time}</span>`);
                    if (showSpeed && v.speed) infos.push(`<span>${v.speed}</span>`);
                    if (showPrice && v.price) infos.push(`<span class="t-price">${v.price}</span>`);
                    if (infos.length) {
                        html += `<div class="t-footer">${infos.join('')}</div>`;
                    }

                    // Footer text
                    if (footerText) {
                        html += `<div class="t-note">${footerText}</div>`;
                    }

                    html += '</div>';
                    return html;
                }).join('');

                const qrData = showQr ? JSON.stringify(items.map(v => v.code)) : '[]';
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`<!DOCTYPE html><html><head><title>Impression</title>
            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                @page { size: ${paperSize} ${orientation}; margin: 8mm; }
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
                body { background: #f8fafc; }
                .grid { display: grid; grid-template-columns: repeat(${cols}, 1fr); gap: 12px; padding: 10px; }
                .ticket {
                    border: 2px solid ${borderColor};
                    border-radius: 12px;
                    overflow: hidden;
                    page-break-inside: avoid;
                    background: ${bgColor};
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }
                .t-header {
                    background: ${primaryColor};
                    color: #fff;
                    text-align: center;
                    font-weight: 700;
                    font-size: 10pt;
                    padding: 8px 10px;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                }
                .t-body-container {
                    display: flex;
                    flex: 1;
                    padding: 12px;
                    align-items: center;
                    justify-content: center;
                }
                .t-body-qr { display: flex; align-items: center; gap: 12px; width: 100%; }
                .t-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; width: 100%; }
                .t-qr { flex-shrink: 0; background: #fff; padding: 2px; border-radius: 6px; border: 1px solid #e2e8f0; }
                .t-qr img, .t-qr svg { width: 50px; height: 50px; display: block; }
                .t-row { display: flex; flex-direction: column; text-align: center; }
                .t-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; }
                .t-value {
                    font-family: 'Consolas', 'Courier New', monospace;
                    font-weight: 700;
                    font-size: 11pt;
                    color: ${textColor};
                    letter-spacing: 1.5px;
                    background: #f1f5f9;
                    padding: 4px 6px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                }
                .t-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 12px;
                    background: #f8fafc;
                    font-size: 8pt;
                    color: #475569;
                    border-top: 2px dashed #e2e8f0;
                    font-weight: 600;
                }
                .t-price { font-weight: 800; color: ${primaryColor}; font-size: 9pt; }
                .t-note { text-align: center; font-size: 6.5pt; color: #94a3b8; padding: 4px 12px 8px; background: #f8fafc; font-weight: 500; }
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: none; }
                }
            </style></head><body>
            <div class="grid">${ticketsHtml}</div>
            <script>
                var codes = ${qrData};
                function generateQRCodes() {
                    if (typeof qrcode === 'undefined' || !codes.length) { window.print(); return; }
                    codes.forEach(function(code, i) {
                        var el = document.getElementById('qr-' + i);
                        if (!el) return;
                        var qr = qrcode(0, 'M');
                        qr.addData(code);
                        qr.make();
                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ window.print(); }, 200);
                }
                if (${showQr}) {
                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ clearInterval(checkLib); window.print(); }, 3000);
                } else {
                    window.onload = function(){ setTimeout(function(){ window.print(); }, 200); };
                }
            <\/script>
            </body></html>`);
                printWindow.document.close();
            },

            exportCSV() {
                const hasPassword = this.generatedVouchers[0]?.plain_password;
                let csv = hasPassword ? 'Username,Password\n' : 'Code\n';

                this.generatedVouchers.forEach(v => {
                    if (hasPassword) {
                        csv += `${v.username},${v.plain_password}\n`;
                    } else {
                        csv += `${v.username}\n`;
                    }
                });

                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = hasPassword ? 'tickets.csv' : 'vouchers.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            },

            getStatusClass(status) {
                const classes = {
                    'unused': 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300',
                    'active': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                    'expired': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                    'disabled': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'
                };
                return classes[status] || classes['unused'];
            },

            getStatusLabel(status) {
                const labels = {
                    'unused': 'Non utilisé',
                    'active': 'Actif',
                    'expired': 'Expiré',
                    'disabled': 'Désactivé'
                };
                return labels[status] || status;
            },

            getPercent(used, limit) {
                if (!limit) return 0;
                return Math.min(100, Math.round((used / limit) * 100));
            },

            getProgressColor(percent) {
                if (percent >= 90) return 'bg-red-500';
                if (percent >= 70) return 'bg-yellow-500';
                return 'bg-green-500';
            },

            formatBytes(bytes) { return formatBytes(bytes); },
            formatTime(seconds) { return formatTime(seconds); },
            formatSpeed(bps) { return formatSpeed(bps); },

            isExpired(validUntil) {
                if (!validUntil) return false;
                return new Date(validUntil) < new Date();
            },

            getRemainingTime(validUntil) {
                if (!validUntil) return '-';
                const now = new Date();
                const expires = new Date(validUntil);
                const diffMs = expires - now;

                if (diffMs <= 0) return 'Expiré';

                const diffSeconds = Math.floor(diffMs / 1000);
                return formatTime(diffSeconds);
            }
        };
    }
</script>