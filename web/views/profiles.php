<?php $pageTitle = __('page.profiles');
$currentPage = 'profiles'; ?>

<div x-data="profilesPage()" x-init="init()">
    <!-- Lien page de paiement -->
    <div class="mb-5 bg-gradient-to-r from-primary-50 to-blue-50 dark:from-primary-900/20 dark:to-blue-900/20 border border-primary-200 dark:border-primary-800/40 rounded-xl p-4"
        x-data="{ payUrl: '', copied: false }" x-init="
            payUrl = window.location.origin + '/pay.php?admin=<?= $auth->getAdminId() ?>';
         ">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-3 min-w-0">
                <div
                    class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                        <?= __('profile.payment_link')?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="payUrl"></p>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <button
                    @click="navigator.clipboard.writeText(payUrl).then(() => { copied = true; setTimeout(() => copied = false, 2000); })"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                    :class="copied ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-white dark:bg-[#21262d] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#30363d]'">
                    <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copied" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copied ? __('profile.copied') : __('profile.copy')"></span>
                </button>
                <a :href="payUrl" target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    <?= __('profile.open')?>
                </a>
            </div>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('profile.subtitle')?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Bouton suppression multiple -->
            <button x-show="selectedProfiles.length > 0" x-cloak @click="deleteSelected()"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <?= __('common.delete')?> (<span x-text="selectedProfiles.length"></span>)
            </button>

            <!-- Recherche -->
            <div class="relative">
                <input type="text" x-model="search" :placeholder="__('profile.search_placeholder')"
                    class="w-48 bg-white dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 py-2 pl-9 pr-3 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <!-- Filtre par zone -->
            <div class="relative">
                <select x-model="selectedZoneFilter"
                    class="appearance-none bg-white dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 py-2 pl-4 pr-10 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent cursor-pointer text-sm font-medium">
                    <option value="">
                        <?= __('profile.all_zones')?>
                    </option>
                    <template x-for="zone in zones" :key="zone.id">
                        <option :value="zone.id" x-text="zone.name"></option>
                    </template>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Toggle vue grille/liste -->
            <div class="flex items-center bg-gray-100 dark:bg-[#21262d] rounded-lg p-1">
                <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ? 'bg-white dark:bg-[#30363d] shadow-sm' : 'text-gray-500'"
                    class="p-2 rounded-md transition-all" :title="__('profile.grid_view')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                </button>
                <button @click="viewMode = 'list'"
                    :class="viewMode === 'list' ? 'bg-white dark:bg-[#30363d] shadow-sm' : 'text-gray-500'"
                    class="p-2 rounded-md transition-all" :title="__('profile.list_view')">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Bouton nouveau profil -->
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('profile.new_profile')?>
            </button>
        </div>
    </div>

    <!-- Sélection multiple info bar -->
    <div x-show="selectedProfiles.length > 0" x-cloak
        class="mb-4 p-3 bg-primary-50 dark:bg-primary-900/20 border border-primary-200 dark:border-primary-800 rounded-lg flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-primary-700 dark:text-primary-300 font-medium">
                <span x-text="selectedProfiles.length"></span>
                <?= __('profile.selected')?>
            </span>
            <button @click="selectAll()" class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400">
                <?= __('profile.select_all')?>
            </button>
            <button @click="selectedProfiles = []" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">
                <?= __('profile.deselect_all')?>
            </button>
        </div>
    </div>

    <!-- Vue Grille -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <template x-for="profile in filteredProfilesData" :key="profile.id">
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-100 dark:border-[#30363d] group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col"
                :class="[!profile.is_active && 'opacity-70', isSelected(profile.id) && 'ring-2 ring-primary-500 border-transparent dark:border-transparent']">

                <div
                    class="absolute inset-0 bg-gradient-to-br from-gray-50/50 to-white dark:from-[#21262d]/50 dark:to-[#161b22] opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-0">
                </div>
                <div class="absolute top-0 left-0 right-0 h-1.5 z-10"
                    :style="'background-color:' + (getZoneColor(profile.zone_id) || '#6366f1')"></div>

                <div class="relative z-10 flex-1 flex flex-col mt-1">
                    <div class="flex items-start gap-2 mb-2">
                        <input type="checkbox" :checked="isSelected(profile.id)" @change="toggleSelect(profile.id)"
                            class="w-4 h-4 mt-1 text-primary-600 border-gray-300 rounded focus:ring-primary-500 cursor-pointer flex-shrink-0">
                        <div class="min-w-0 flex-1">
                            <h3 class="text-lg font-black text-gray-900 dark:text-white leading-tight truncate"
                                x-text="profile.name"></h3>
                            <p class="text-[12px] text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-1"
                                x-text="profile.description || __('profile.no_description')"></p>
                        </div>
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 rounded border text-[10px] font-bold uppercase tracking-wide shrink-0"
                            :class="profile.is_active ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-900/30 dark:border-emerald-800/50 dark:text-emerald-400' : 'bg-gray-50 border-gray-200 text-gray-500 dark:bg-[#21262d] dark:border-[#30363d] dark:text-gray-400'">
                            <span x-text="profile.is_active ? __('common.active') : __('common.inactive')"></span>
                        </span>
                    </div>

                    <div class="flex items-baseline gap-1 mb-4 pl-6">
                        <span class="text-2xl font-black text-primary-600 dark:text-primary-400"
                            x-text="Number(profile.price || 0).toLocaleString('fr-FR')"></span>
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400" x-text="APP_CURRENCY"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-[11px] mb-4 flex-1">
                        <!-- Validité -->
                        <div
                            class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                            <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5 relative pl-4">
                                <svg class="w-3 h-3 absolute left-0 top-0.5 text-amber-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <?= __('profile.validity')?>
                            </p>
                            <p class="font-bold text-gray-900 dark:text-white truncate"
                                x-text="formatValidity(profile)"></p>
                        </div>
                        <!-- Temps -->
                        <div
                            class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                            <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5 relative pl-4">
                                <svg class="w-3 h-3 absolute left-0 top-0.5 text-blue-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= __('profile.time')?>
                            </p>
                            <p class="font-bold text-gray-900 dark:text-white truncate"
                                x-text="profile.time_limit ? formatTimeLimit(profile.time_limit) : __('common.unlimited')">
                            </p>
                        </div>
                        <!-- Data -->
                        <div
                            class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                            <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5 relative pl-4">
                                <svg class="w-3 h-3 absolute left-0 top-0.5 text-purple-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                                </svg>
                                <?= __('profile.data')?>
                            </p>
                            <p class="font-bold text-gray-900 dark:text-white truncate"
                                x-text="profile.data_limit ? formatBytes(profile.data_limit) : __('common.unlimited')">
                            </p>
                        </div>
                        <!-- Vitesse -->
                        <div
                            class="bg-gray-50 dark:bg-[#21262d] p-2.5 rounded-lg border border-gray-100 dark:border-[#30363d]">
                            <p class="text-gray-500 dark:text-gray-400 font-semibold mb-0.5 relative pl-4">
                                <svg class="w-3 h-3 absolute left-0 top-0.5 text-cyan-500" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                <?= __('profile.speed')?>
                            </p>
                            <p class="font-bold text-gray-900 dark:text-white truncate" x-text="getSpeedText(profile)">
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-auto pt-3 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                        <div class="flex items-center gap-1.5 min-w-0">
                            <!-- Zone badge -->
                            <span x-show="getZoneName(profile.zone_id)"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-50 border border-gray-100 dark:border-[#30363d] dark:bg-[#21262d] text-gray-600 dark:text-gray-400 truncate max-w-[100px]"
                                :title="getZoneName(profile.zone_id)">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0"
                                    :style="'background-color:' + (getZoneColor(profile.zone_id) || '#6b7280')"></span>
                                <span class="truncate" x-text="getZoneName(profile.zone_id)"></span>
                            </span>
                            <!-- Connexions -->
                            <span
                                class="inline-flex items-center gap-0.5 text-[10px] font-bold text-gray-500 dark:text-gray-400 bg-gray-50 border border-gray-100 dark:border-[#30363d] dark:bg-[#21262d] px-1.5 py-0.5 rounded"
                                x-show="profile.simultaneous_use > 1" :title="__('profile.simultaneous_use')">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span x-text="'x' + profile.simultaneous_use"></span>
                            </span>
                        </div>
                        <div class="flex gap-1 shrink-0">
                            <button @click="showPaymentLink(profile)"
                                class="p-1.5 text-gray-400 hover:text-green-600 rounded-md hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                :title="__('profile.payment_link_title')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </button>
                            <button @click="editProfile(profile)"
                                class="p-1.5 text-gray-400 hover:text-primary-600 rounded-md hover:bg-primary-50 dark:hover:bg-primary-900/20 transition-colors"
                                :title="__('common.edit')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="deleteProfile(profile)"
                                class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                :title="__('common.delete')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Vue Liste -->
    <div x-show="viewMode === 'list'" x-cloak
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#30363d]">
            <thead class="bg-gray-50 dark:bg-[#0d1117]">
                <tr>
                    <th scope="col" class="px-4 py-3 w-12">
                        <input type="checkbox"
                            :checked="selectedProfiles.length === filteredProfilesData.length && filteredProfilesData.length > 0"
                            @change="toggleSelectAll()"
                            class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 cursor-pointer">
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('common.profile')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider">
                        <?= __('profile.validity')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                        <?= __('profile.time')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('profile.data')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('profile.speed')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('common.price')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('common.status')?>
                    </th>
                    <th scope="col"
                        class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('common.actions')?>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                <template x-for="profile in filteredProfilesData" :key="profile.id">
                    <tr :class="[!profile.is_active && 'opacity-60', isSelected(profile.id) && 'bg-primary-50 dark:bg-primary-900/20']"
                        class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50 transition-colors">
                        <td class="px-4 py-3">
                            <input type="checkbox" :checked="isSelected(profile.id)" @change="toggleSelect(profile.id)"
                                class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500 cursor-pointer">
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="profile.name"></p>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="profile.description || '-'">
                                </p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-amber-600 dark:text-amber-400"
                            x-text="formatValidity(profile)"></td>
                        <td class="px-4 py-3 text-sm font-medium text-blue-600 dark:text-blue-400"
                            x-text="profile.time_limit ? formatTimeLimit(profile.time_limit) : __('common.unlimited')">
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white"
                            x-text="profile.data_limit ? formatBytes(profile.data_limit) : __('common.unlimited')"></td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" x-text="getSpeedText(profile)"></td>
                        <td class="px-4 py-3">
                            <span class="font-semibold text-primary-600 dark:text-primary-400"
                                x-text="profile.price + ' ' + APP_CURRENCY"></span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="profile.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-400'"
                                x-text="profile.is_active ? __('common.active') : __('common.inactive')"></span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="showPaymentLink(profile)"
                                    class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-gray-100 dark:hover:bg-[#30363d]"
                                    :title="__('profile.payment_link_title')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </button>
                                <button @click="editProfile(profile)"
                                    class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100 dark:hover:bg-[#30363d]"
                                    :title="__('common.edit')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteProfile(profile)"
                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100 dark:hover:bg-[#30363d]"
                                    :title="__('common.delete')">
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

        <!-- Empty state pour la liste -->
        <template x-if="filteredProfilesData.length === 0">
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    <?= __('profile.empty')?>
                </p>
            </div>
        </template>
    </div>

    <!-- Empty state pour la grille -->
    <template x-if="filteredProfilesData.length === 0 && viewMode === 'grid'">
        <div class="text-center py-12">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <p class="text-gray-500 dark:text-gray-400">
                <?= __('profile.empty')?>
            </p>
            <button @click="showModal = true; editMode = false; resetForm()"
                class="mt-4 text-primary-600 hover:text-primary-700 font-medium">
                <?= __('profile.create_first')?>
            </button>
        </div>
    </template>

    <!-- Modal Créer/Modifier -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="editMode ? __('profile.edit_profile') : __('profile.new_profile')"></h3>
                <form @submit.prevent="saveProfile()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('profile.form_name')?>
                            </label>
                            <input type="text" x-model="form.name" required placeholder="ex: 1 Heure"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('profile.form_description')?>
                            </label>
                            <input type="text" x-model="form.description"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <!-- Switch Type de profil -->
                        <div class="flex items-center bg-gray-100 dark:bg-[#21262d] rounded-lg p-1">
                            <button type="button" @click="form.profile_type = 'time'"
                                :class="form.profile_type === 'time' ? 'bg-white dark:bg-[#30363d] shadow-sm text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-sm font-medium transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= __('profile.type_time') ?? 'Temps' ?>
                            </button>
                            <button type="button" @click="form.profile_type = 'data'"
                                :class="form.profile_type === 'data' ? 'bg-white dark:bg-[#30363d] shadow-sm text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-md text-sm font-medium transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <?= __('profile.type_data') ?? 'Donn\u00e9es' ?>
                            </button>
                        </div>

                        <!-- Temps d'utilisation (mode TEMPS uniquement) -->
                        <div x-show="form.profile_type === 'time'" x-transition
                            class="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                            <label class="block text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= __('profile.form_usage_time')?> *
                            </label>
                            <div class="flex gap-2">
                                <input type="number" x-model="form.time_limit_value" min="1"
                                    placeholder="Ex: 1" :required="form.profile_type === 'time'"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <select x-model="form.time_limit_unit"
                                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                    <option value="minutes"><?= __('time.minutes')?></option>
                                    <option value="hours"><?= __('time.hours')?></option>
                                    <option value="days"><?= __('time.days')?></option>
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-blue-700 dark:text-blue-300">
                                <?= __('profile.usage_time_hint')?>
                            </p>
                        </div>

                        <!-- Limite de donn\u00e9es (mode DATA uniquement) -->
                        <div x-show="form.profile_type === 'data'" x-transition
                            class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                            <label class="block text-sm font-medium text-emerald-800 dark:text-emerald-200 mb-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <?= __('profile.form_data_limit') ?? 'Limite de donn\u00e9es' ?> *
                            </label>
                            <div class="flex gap-2">
                                <input type="number" x-model="form.data_limit_value" min="1"
                                    placeholder="Ex: 500" :required="form.profile_type === 'data'"
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <select x-model="form.data_limit_unit"
                                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                    <option value="MB">Mo</option>
                                    <option value="GB">Go</option>
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-emerald-700 dark:text-emerald-300">
                                <?= __('profile.data_limit_hint') ?? 'Volume de donn\u00e9es autoris\u00e9 pour ce profil' ?>
                            </p>
                        </div>

                        <!-- Validit\u00e9 du voucher (dur\u00e9e de vie calendaire) - toujours visible -->
                        <div
                            class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                            <label class="block text-sm font-medium text-amber-800 dark:text-amber-200 mb-2">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <?= __('profile.form_validity')?>
                            </label>
                            <div class="flex gap-2">
                                <input type="number" x-model="form.validity_value" min="1" placeholder="Ex: 24" required
                                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <select x-model="form.validity_unit"
                                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                    <option value="minutes"><?= __('time.minutes')?></option>
                                    <option value="hours"><?= __('time.hours')?></option>
                                    <option value="days"><?= __('time.days')?></option>
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-amber-700 dark:text-amber-300">
                                <?= __('profile.validity_hint')?>
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('profile.form_max_connections')?>
                                </label>
                                <input type="number" x-model="form.simultaneous_use" min="1" placeholder="1"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Verrouillage MAC -->
                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-[#21262d] border border-gray-200 dark:border-[#30363d] rounded-lg">
                            <input type="checkbox" x-model="form.lock_to_mac" id="lock_to_mac"
                                class="mt-0.5 w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <div>
                                <label for="lock_to_mac" class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">
                                    <svg class="w-4 h-4 inline mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <?= __('profile.lock_to_mac') ?? 'Verrouiller l\'appareil (MAC)' ?>
                                </label>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    <?= __('profile.lock_to_mac_hint') ?? 'Le voucher sera lié au premier appareil connecté' ?>
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('profile.form_download')?>
                                </label>
                                <input type="number" step="0.1" x-model="form.download_speed_mbps"
                                    :placeholder="__('common.unlimited')"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('profile.form_upload')?>
                                </label>
                                <input type="number" step="0.1" x-model="form.upload_speed_mbps"
                                    :placeholder="__('common.unlimited')"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('profile.form_price')?> (<span x-text="APP_CURRENCY"></span>)
                            </label>
                            <input type="number" x-model="form.price" placeholder="100"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('profile.form_zone')?>
                            </label>
                            <select x-model="form.zone_id" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                                :class="!form.zone_id && 'border-red-300 dark:border-red-600'">
                                <option value="">--
                                    <?= __('profile.select_zone')?> --
                                </option>
                                <template x-for="zone in zones" :key="zone.id">
                                    <option :value="zone.id" x-text="zone.name"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                <?= __('profile.zone_hint')?>
                            </p>
                            <p x-show="zones.length === 0" class="mt-1 text-xs text-red-500">
                                <?= __('profile.no_zone')?> <a href="index.php?page=zones" class="underline">
                                    <?= __('profile.create_zone_first')?>
                                </a>.
                            </p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" x-model="form.is_active" id="is_active"
                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                <?= __('profile.form_active')?>
                            </label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <span x-text="editMode ? __('common.save') : __('common.create')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Lien de paiement -->
    <div x-show="showLinkModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showLinkModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <svg class="w-5 h-5 inline-block mr-2 text-green-500" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                        <?= __('profile.payment_link_title')?>
                    </h3>
                    <button @click="showLinkModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                        <?= __('common.profile')?>: <span class="font-medium text-gray-900 dark:text-white"
                            x-text="selectedProfileForLink?.name"></span>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= __('common.price')?>: <span class="font-semibold text-primary-600 dark:text-primary-400"
                            x-text="selectedProfileForLink?.price + ' ' + APP_CURRENCY"></span>
                    </p>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <?= __('profile.payment_link_title')?>
                    </label>
                    <div class="flex gap-2">
                        <input type="text" :value="paymentLink" readonly
                            class="flex-1 px-4 py-2 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white text-sm"
                            id="payment-link-input">
                        <button @click="copyPaymentLink()"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mb-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium mb-1">
                                <?= __('profile.payment_link_howto')?>
                            </p>
                            <ul class="list-disc list-inside text-blue-600 dark:text-blue-400 space-y-1">
                                <li>
                                    <?= __('profile.howto_share')?>
                                </li>
                                <li>
                                    <?= __('profile.howto_pay')?>
                                </li>
                                <li>
                                    <?= __('profile.howto_voucher')?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <a :href="paymentLink" target="_blank"
                        class="text-primary-600 hover:text-primary-700 dark:text-primary-400 text-sm font-medium">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <?= __('profile.open_link')?>
                    </a>
                    <button @click="showLinkModal = false"
                        class="px-4 py-2 bg-gray-200 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-[#30363d] transition-colors">
                        <?= __('common.close')?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal confirmation suppression multiple -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <div class="text-center">
                    <div
                        class="w-16 h-16 mx-auto mb-4 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        <?= __('profile.delete_confirm_title')?>
                    </h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-6">
                        <?= __('profile.delete_confirm_text')?> <span
                            class="font-semibold text-gray-900 dark:text-white" x-text="selectedProfiles.length"></span>
                        <?= __('profile.delete_confirm_profiles')?>
                        <?= __('profile.delete_confirm_warning')?>
                    </p>
                    <div class="flex justify-center gap-3">
                        <button @click="showDeleteModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button @click="confirmDeleteSelected()" :disabled="deleting"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50">
                            <span x-show="!deleting">
                                <?= __('common.delete')?>
                            </span>
                            <span x-show="deleting">
                                <?= __('profile.deleting')?>
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function profilesPage() {
        return {
            profiles: [],
            filteredProfilesData: [],
            zones: [],
            showModal: false,
            showDeleteModal: false,
            showLinkModal: false,
            editMode: false,
            editId: null,
            viewMode: localStorage.getItem('profilesViewMode') || 'grid',
            search: '',
            selectedZoneFilter: '',
            selectedProfiles: [],
            selectedProfileForLink: null,
            paymentLink: '',
            deleting: false,
            form: {
                profile_type: 'time',
                name: '',
                description: '',
                validity_value: '',
                validity_unit: 'hours',
                time_limit_value: '',
                time_limit_unit: 'hours',
                data_limit_value: '',
                data_limit_unit: 'MB',
                download_speed_mbps: '',
                upload_speed_mbps: '',
                price: 0,
                simultaneous_use: 1,
                lock_to_mac: false,
                zone_id: '',
                is_active: true
            },

            applyFilters() {
                let filtered = this.profiles;

                if (this.search) {
                    filtered = filtered.filter(profile =>
                        profile.name?.toLowerCase().includes(this.search.toLowerCase()) ||
                        (profile.description && profile.description.toLowerCase().includes(this.search.toLowerCase()))
                    );
                }

                if (this.selectedZoneFilter) {
                    filtered = filtered.filter(profile => profile.zone_id == this.selectedZoneFilter);
                }

                this.filteredProfilesData = filtered;
            },

            async init() {
                await Promise.all([
                    this.loadProfiles(),
                    this.loadZones()
                ]);
                this.$watch('viewMode', value => localStorage.setItem('profilesViewMode', value));
                this.$watch('search', () => this.applyFilters());
                this.$watch('selectedZoneFilter', () => this.applyFilters());
            },

            async loadProfiles() {
                try {
                    const response = await API.get('/profiles');
                    this.profiles = response.data;
                    this.applyFilters();
                } catch (error) {
                    Toast.error(__('profile.msg_load_error'));
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data.filter(z => z.is_active);
                } catch (error) {
                    console.error('Erreur chargement zones:', error);
                }
            },

            resetForm() {
                this.form = {
                    profile_type: 'time',
                    name: '',
                    description: '',
                    validity_value: '',
                    validity_unit: 'hours',
                    time_limit_value: '',
                    time_limit_unit: 'hours',
                    data_limit_value: '',
                    data_limit_unit: 'MB',
                    download_speed_mbps: '',
                    upload_speed_mbps: '',
                    price: 0,
                    simultaneous_use: 1,
                    lock_to_mac: false,
                    zone_id: '',
                    is_active: true
                };
                this.editId = null;
            },

            secondsToValueUnit(seconds) {
                if (!seconds) return { value: '', unit: 'hours' };
                if (seconds % 86400 === 0) return { value: seconds / 86400, unit: 'days' };
                if (seconds % 3600 === 0) return { value: seconds / 3600, unit: 'hours' };
                return { value: seconds / 60, unit: 'minutes' };
            },

            valueUnitToSeconds(value, unit) {
                if (!value) return null;
                const v = parseFloat(value);
                switch (unit) {
                    case 'days': return v * 86400;
                    case 'hours': return v * 3600;
                    case 'minutes': return v * 60;
                    default: return v * 3600;
                }
            },

            editProfile(profile) {
                this.editMode = true;
                this.editId = profile.id;

                const validity = this.secondsToValueUnit(profile.validity);
                const timeLimit = this.secondsToValueUnit(profile.time_limit);
                const dataLimitMb = profile.data_limit ? profile.data_limit / (1024 * 1024) : '';
                let dataValue = dataLimitMb;
                let dataUnit = 'MB';
                if (dataLimitMb && dataLimitMb >= 1024 && dataLimitMb % 1024 === 0) {
                    dataValue = dataLimitMb / 1024;
                    dataUnit = 'GB';
                }

                this.form = {
                    profile_type: profile.data_limit ? 'data' : 'time',
                    name: profile.name,
                    description: profile.description || '',
                    validity_value: validity.value,
                    validity_unit: profile.validity_unit || validity.unit,
                    time_limit_value: timeLimit.value,
                    time_limit_unit: timeLimit.unit,
                    data_limit_value: dataValue,
                    data_limit_unit: dataUnit,
                    download_speed_mbps: profile.download_speed ? profile.download_speed / 1000000 : '',
                    upload_speed_mbps: profile.upload_speed ? profile.upload_speed / 1000000 : '',
                    price: profile.price || 0,
                    simultaneous_use: profile.simultaneous_use || 1,
                    lock_to_mac: profile.lock_to_mac == 1,
                    zone_id: profile.zone_id || '',
                    is_active: profile.is_active == 1
                };
                this.showModal = true;
            },

            async saveProfile() {
                try {
                    let dataLimitBytes = null;
                    if (this.form.profile_type === 'data' && this.form.data_limit_value) {
                        const mb = this.form.data_limit_unit === 'GB'
                            ? this.form.data_limit_value * 1024
                            : parseFloat(this.form.data_limit_value);
                        dataLimitBytes = mb * 1024 * 1024;
                    }

                    const data = {
                        name: this.form.name,
                        description: this.form.description || null,
                        validity: this.valueUnitToSeconds(this.form.validity_value, this.form.validity_unit),
                        validity_unit: this.form.validity_unit,
                        time_limit: this.form.profile_type === 'time'
                            ? this.valueUnitToSeconds(this.form.time_limit_value, this.form.time_limit_unit)
                            : null,
                        data_limit: dataLimitBytes,
                        download_speed: this.form.download_speed_mbps ? this.form.download_speed_mbps * 1000000 : null,
                        upload_speed: this.form.upload_speed_mbps ? this.form.upload_speed_mbps * 1000000 : null,
                        price: this.form.price || 0,
                        simultaneous_use: this.form.simultaneous_use || 1,
                        lock_to_mac: this.form.lock_to_mac ? 1 : 0,
                        zone_id: this.form.zone_id || null,
                        is_active: this.form.is_active ? 1 : 0
                    };

                    if (this.editMode) {
                        await API.put(`/profiles/${this.editId}`, data);
                        Toast.success(__('profile.msg_updated'));
                    } else {
                        await API.post('/profiles', data);
                        Toast.success(__('profile.msg_created'));
                    }
                    this.showModal = false;
                    await this.loadProfiles();
                } catch (error) {
                    Toast.error(error.message);
                }
            },

            async deleteProfile(profile) {
                if (!confirm(__('profile.msg_confirm_delete').replace(':name', profile.name))) return;

                try {
                    await API.delete(`/profiles/${profile.id}`);
                    Toast.success(__('profile.msg_deleted'));
                    await this.loadProfiles();
                } catch (error) {
                    Toast.error(error.message);
                }
            },

            isSelected(id) {
                return this.selectedProfiles.includes(id);
            },

            toggleSelect(id) {
                if (this.isSelected(id)) {
                    this.selectedProfiles = this.selectedProfiles.filter(p => p !== id);
                } else {
                    this.selectedProfiles.push(id);
                }
            },

            toggleSelectAll() {
                if (this.selectedProfiles.length === this.filteredProfilesData.length && this.filteredProfilesData.length > 0) {
                    this.selectedProfiles = [];
                } else {
                    this.selectedProfiles = this.filteredProfilesData.map(p => p.id);
                }
            },

            selectAll() {
                this.selectedProfiles = this.filteredProfilesData.map(p => p.id);
            },

            deleteSelected() {
                if (this.selectedProfiles.length === 0) return;
                this.showDeleteModal = true;
            },

            async confirmDeleteSelected() {
                this.deleting = true;
                let successCount = 0;
                let errorCount = 0;

                for (const id of this.selectedProfiles) {
                    try {
                        await API.delete(`/profiles/${id}`);
                        successCount++;
                    } catch (error) {
                        errorCount++;
                    }
                }

                this.deleting = false;
                this.showDeleteModal = false;
                this.selectedProfiles = [];

                if (successCount > 0) {
                    Toast.success(__('profile.msg_deleted_count').replace(':count', successCount));
                }
                if (errorCount > 0) {
                    Toast.error(__('profile.msg_delete_error').replace(':count', errorCount));
                }
                await this.loadProfiles();
            },

            getZoneName(zoneId) {
                if (!zoneId) return '';
                const zone = this.zones.find(z => z.id == zoneId);
                return zone ? zone.name : '';
            },

            getZoneColor(zoneId) {
                if (!zoneId) return null;
                const zone = this.zones.find(z => z.id == zoneId);
                return zone ? zone.color : null;
            },

            getSpeedText(profile) {
                if (!profile.download_speed && !profile.upload_speed) return __('common.unlimited');
                const down = profile.download_speed ? formatSpeed(profile.download_speed) : '∞';
                const up = profile.upload_speed ? formatSpeed(profile.upload_speed) : '∞';
                return `${down} / ${up}`;
            },

            formatValidity(profile) {
                if (!profile.validity) return __('profile.not_defined');
                const seconds = parseInt(profile.validity);
                const unit = profile.validity_unit || 'hours';

                switch (unit) {
                    case 'days':
                        const days = seconds / 86400;
                        return days === 1 ? '1 ' + __('time.day') : `${days} ` + __('time.days');
                    case 'hours':
                        const hours = seconds / 3600;
                        return hours === 1 ? '1 ' + __('time.hour') : `${hours} ` + __('time.hours');
                    case 'minutes':
                        const minutes = seconds / 60;
                        return minutes === 1 ? '1 ' + __('time.minute') : `${minutes} ` + __('time.minutes');
                    default:
                        return formatTime(seconds);
                }
            },

            async showPaymentLink(profile) {
                this.selectedProfileForLink = profile;
                try {
                    const response = await API.get(`/payments/link/${profile.id}`);
                    this.paymentLink = response.data.payment_link;
                    this.showLinkModal = true;
                } catch (error) {
                    Toast.error(__('profile.msg_link_error'));
                }
            },

            copyPaymentLink() {
                navigator.clipboard.writeText(this.paymentLink).then(() => {
                    Toast.success(__('profile.msg_link_copied'));
                }).catch(() => {
                    const input = document.getElementById('payment-link-input');
                    input.select();
                    document.execCommand('copy');
                    Toast.success(__('profile.msg_link_copied'));
                });
            },

            formatTimeLimit(seconds) {
                if (!seconds) return __('common.unlimited');
                seconds = parseInt(seconds);
                if (seconds % 86400 === 0) {
                    const d = seconds / 86400;
                    return d === 1 ? '1 ' + __('time.day') : `${d} ` + __('time.days');
                }
                if (seconds % 3600 === 0) {
                    const h = seconds / 3600;
                    return h === 1 ? '1 ' + __('time.hour') : `${h} ` + __('time.hours');
                }
                const m = Math.round(seconds / 60);
                return m === 1 ? '1 ' + __('time.minute') : `${m} ` + __('time.minutes');
            },

            formatTime(seconds) { return formatTime(seconds); },
            formatBytes(bytes) { return formatBytes(bytes); }
        }
    }
</script>