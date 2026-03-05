<?php $pageTitle = __('nas.title');
$currentPage = 'nas'; ?>

<div x-data="nasPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('nas.subtitle')?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?page=zones"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d]">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
                <?= __('nas.manage_zones')?>
            </a>
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('nas.add_nas')?>
            </button>
        </div>
    </div>

    <!-- Filtre par zone + View toggle -->
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <!-- View mode toggle -->
        <div class="flex items-center bg-gray-100 dark:bg-[#21262d] rounded-lg p-0.5 mr-2">
            <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'" class="p-1.5 rounded-md transition-all" title="<?= __('nas.view_grid') ?? 'Grille' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            </button>
            <button @click="viewMode = 'list'" :class="viewMode === 'list' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'" class="p-1.5 rounded-md transition-all" title="<?= __('nas.view_list') ?? 'Liste' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        <span class="text-sm text-gray-500 dark:text-gray-400">
            <?= __('nas.filter_by_zone')?>
        </span>
        <button @click="filterZone = null"
            :class="filterZone === null ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('nas.all_zones')?>
        </button>
        <button @click="filterZone = 0"
            :class="filterZone === 0 ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('nas.no_zone')?>
        </button>
        <template x-for="zone in zones" :key="zone.id">
            <button @click="filterZone = zone.id"
                :class="filterZone === zone.id ? 'text-white' : 'text-gray-700 dark:text-gray-300'"
                :style="filterZone === zone.id ? `background-color: ${zone.color}` : ''"
                class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors"
                :class="filterZone !== zone.id && 'bg-gray-100 dark:bg-[#21262d]'">
                <span class="inline-flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :style="`background-color: ${zone.color}`"></span>
                    <span x-text="zone.name"></span>
                    <span class="text-xs opacity-75" x-text="'(' + (zone.nas_count || 0) + ')'"></span>
                </span>
            </button>
        </template>
    </div>

    <!-- NAS Grid View -->
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="nas in filteredNas" :key="nas.id">
            <div
                class="group relative bg-white dark:bg-[#161b22] rounded-2xl shadow-sm hover:shadow-xl dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden transition-all duration-300 transform hover:-translate-y-1">

                <!-- Bandeau supérieur avec dégradé subtil -->
                <div class="absolute inset-x-0 top-0 h-1.5 opacity-80"
                    :style="nas.zone_color ? `background: linear-gradient(90deg, ${nas.zone_color} 0%, transparent 100%)` : 'background: linear-gradient(90deg, #9ca3af 0%, transparent 100%)'">
                </div>

                <div class="p-6">
                    <!-- En-tête de la carte -->
                    <div class="flex items-start justify-between mb-5">
                        <div class="flex shrink-0">
                            <!-- Icône Routeur stylisée -->
                            <div
                                class="relative w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 dark:from-blue-900/30 dark:to-blue-800/10 border border-blue-100 dark:border-blue-800/30">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <!-- Point de statut polling (online/offline) -->
                                <div x-show="routerStatuses[nas.router_id]"
                                    class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-[#161b22]"
                                    :class="routerStatuses[nas.router_id]?.online ? 'bg-emerald-500' : 'bg-gray-400'"
                                    :title="routerStatuses[nas.router_id]?.online ? 'En ligne' : 'Hors ligne'"></div>
                            </div>

                            <!-- Titre et IP -->
                            <div class="ml-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight"
                                    x-text="nas.shortname"></h3>
                                <p
                                    class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-1 mt-0.5 flex items-center gap-1.5">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9">
                                        </path>
                                    </svg>
                                    <span x-text="nas.router_id"></span>
                                </p>
                            </div>
                        </div>

                        <!-- Zone Badge Dynamique -->
                        <div class="shrink-0 pl-2">
                            <template x-if="nas.zone_name">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded-full shadow-sm"
                                    :style="`background-color: ${nas.zone_color}1a; color: ${nas.zone_color}; border: 1px solid ${nas.zone_color}33`">
                                    <span class="w-1.5 h-1.5 rounded-full"
                                        :style="`background-color: ${nas.zone_color}`"></span>
                                    <span x-text="nas.zone_name"></span>
                                </span>
                            </template>
                            <template x-if="!nas.zone_name">
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold uppercase tracking-wider rounded-full shadow-sm bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    <?= __('nas.all_zones_label')?>
                                </span>
                            </template>
                        </div>
                    </div>

                    <!-- Infos Essentielles (Grille Moderne) -->
                    <div
                        class="grid grid-cols-2 gap-4 my-6 p-4 rounded-xl bg-gray-50/50 dark:bg-[#0d1117] border border-gray-100 dark:border-[#30363d]">
                        <!-- CPU (si API dispo) / Type sinon -->
                        <template x-if="nas.apiData && nas.apiData.cpu_load !== null && nas.apiStatus === 'ok'">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold mb-1">CPU
                                    Load</span>
                                <div class="flex items-end gap-2 text-gray-900 dark:text-white">
                                    <span class="text-xl font-bold leading-none"
                                        :class="nas.apiData.cpu_load > 80 ? 'text-rose-500' : nas.apiData.cpu_load > 50 ? 'text-amber-500' : 'text-emerald-500'"
                                        x-text="nas.apiData.cpu_load + '%'"></span>
                                    <svg class="w-4 h-4 mb-0.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                        <template x-if="!(nas.apiData && nas.apiData.cpu_load !== null && nas.apiStatus === 'ok')">
                            <div class="flex flex-col">
                                <span
                                    class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    <?= __('nas.type')?>
                                </span>
                                <span class="text-sm font-semibold capitalize text-gray-800 dark:text-gray-200"
                                    x-text="nas.type || 'mikrotik'"></span>
                            </div>
                        </template>

                        <!-- RAM (si API dispo) / Secret sinon -->
                        <template
                            x-if="nas.apiData && nas.apiData.memory_percent !== null && nas.apiStatus === 'ok'">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold mb-1">RAM</span>
                                <div class="flex items-end gap-2 text-gray-900 dark:text-white">
                                    <span class="text-xl font-bold leading-none"
                                        :class="nas.apiData.memory_percent > 80 ? 'text-rose-500' : nas.apiData.memory_percent > 50 ? 'text-amber-500' : 'text-emerald-500'"
                                        x-text="nas.apiData.memory_percent + '%'"></span>
                                    <svg class="w-4 h-4 mb-0.5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7C5 4 4 5 4 7z" />
                                    </svg>
                                </div>
                            </div>
                        </template>
                        <template
                            x-if="!(nas.apiData && nas.apiData.memory_percent !== null && nas.apiStatus === 'ok')">
                            <div class="flex flex-col">
                                <span
                                    class="text-[11px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-semibold mb-1">
                                    <?= __('nas.secret')?>
                                </span>
                                <span class="text-sm font-mono text-gray-800 dark:text-gray-200">••••••••</span>
                            </div>
                        </template>
                    </div>

                    <!-- Ligne Méta (Sync + API séparés) -->
                    <div class="flex flex-col gap-2.5 text-sm mb-4">
                        <!-- Statut Synchronisation -->
                        <template x-if="nas.pingStatus === 'ok'">
                            <div class="flex items-center text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/10 px-3 py-1.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                                <span class="relative flex h-2.5 w-2.5 mr-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                                <span class="font-medium"><?= __('nas.sync_ok') ?></span>
                            </div>
                        </template>
                        <template x-if="nas.pingStatus === 'fail'">
                            <div class="flex items-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/10 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700/30">
                                <span class="relative flex h-2.5 w-2.5 mr-2.5">
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-gray-400"></span>
                                </span>
                                <span class="font-medium"><?= __('nas.sync_fail') ?></span>
                            </div>
                        </template>

                        <!-- Statut API (séparé) -->
                        <template x-if="nas.apiStatus === 'ok' && nas.apiData">
                            <div class="flex items-center gap-3 bg-emerald-50 dark:bg-emerald-900/10 px-3 py-2 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                                <span class="relative flex h-2.5 w-2.5 flex-shrink-0">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                </span>
                                <span class="font-semibold text-emerald-700 dark:text-emerald-300 text-xs">API</span>
                                <div class="flex items-center gap-2 ml-auto text-[11px]">
                                    <template x-if="nas.apiData.board">
                                        <span class="px-1.5 py-0.5 rounded bg-white dark:bg-[#161b22] text-gray-700 dark:text-gray-300 font-mono border border-gray-200 dark:border-[#30363d]" x-text="nas.apiData.board"></span>
                                    </template>
                                    <template x-if="nas.apiData.version">
                                        <span class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 font-mono border border-blue-100 dark:border-blue-800/30" x-text="'v' + nas.apiData.version"></span>
                                    </template>
                                    <template x-if="nas.apiData.uptime">
                                        <span class="px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 font-mono border border-emerald-200 dark:border-emerald-800/30" x-text="'⏱ ' + nas.apiData.uptime"></span>
                                    </template>
                                    <template x-if="nas.apiLatency">
                                        <span class="text-gray-400 dark:text-gray-500 font-mono" x-text="nas.apiLatency + 'ms'"></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <template x-if="nas.apiStatus === 'fail'">
                            <div class="flex items-center text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/10 px-3 py-1.5 rounded-lg border border-rose-100 dark:border-rose-800/30">
                                <span class="relative flex h-2.5 w-2.5 mr-2.5">
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-rose-500"></span>
                                </span>
                                <span class="font-medium">API</span>
                                <span class="ml-2 text-xs truncate" x-text="nas.apiMessage || '<?= __('nas.api_unreachable') ?>'"></span>
                            </div>
                        </template>
                        <template x-if="nas.apiStatus === 'testing'">
                            <div class="flex items-center text-blue-500 bg-blue-50 dark:bg-blue-900/10 px-3 py-1.5 rounded-lg border border-blue-100 dark:border-blue-800/30">
                                <svg class="w-3.5 h-3.5 animate-spin mr-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="font-medium text-xs">API <?= __('nas.ping_testing') ?></span>
                            </div>
                        </template>

                        <!-- Connexion DNS/API Rapide -->
                        <div class="flex items-center justify-between mt-1">
                            <template x-if="nas.nasname && nas.nasname !== '0.0.0.0/0'">
                                <div
                                    class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-[13px] truncate">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    <span class="font-mono truncate" x-text="nas.nasname"></span>
                                </div>
                            </template>

                            <template x-if="nas.mikrotik_host">
                                <div
                                    class="flex items-center gap-1.5 text-[13px] text-orange-600 dark:text-orange-400/90 ml-auto bg-orange-50 dark:bg-orange-900/20 px-2 py-0.5 rounded border border-orange-100 dark:border-orange-800/40">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-mono font-medium truncate max-w-[120px]"
                                        x-text="nas.mikrotik_host"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Expiration -->
                    <template x-if="nas.expires_at">
                        <div class="flex items-center gap-2 mt-2 px-3 py-1.5 rounded-lg text-[13px]"
                            :class="isExpired(nas.expires_at) ? 'bg-rose-50 dark:bg-rose-900/10 text-rose-600 dark:text-rose-400 border border-rose-100 dark:border-rose-800/30' : isExpiringSoon(nas.expires_at) ? 'bg-amber-50 dark:bg-amber-900/10 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-800/30' : 'bg-gray-50 dark:bg-[#0d1117] text-gray-500 dark:text-gray-400 border border-gray-100 dark:border-[#30363d]'">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="isExpired(nas.expires_at) ? '<?= __('nas.expired') ?>' : '<?= __('nas.expires') ?> ' + formatExpirationDate(nas.expires_at)"></span>
                            <template x-if="!isExpired(nas.expires_at)">
                                <span class="ml-auto text-[11px] font-medium opacity-75" x-text="'(' + daysRemaining(nas.expires_at) + ')'"></span>
                            </template>
                        </div>
                    </template>

                    <!-- Actions (Barre Footer intégrée) -->
                    <div
                        class="mt-5 pt-4 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                        <!-- Temps depuis dernier check -->
                        <div class="flex items-center gap-2">
                            <div x-show="nas.pingStatus === 'testing'" class="flex items-center gap-1.5 text-blue-500">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <span class="text-[11px] font-medium uppercase tracking-wider">
                                    <?= __('nas.ping_testing')?>
                                </span>
                            </div>
                            <span x-show="nas.pingStatus !== 'testing' && nas.pingTime"
                                class="text-[11px] font-medium text-gray-400 dark:text-gray-500 uppercase tracking-wider"
                                x-text="timeAgo(nas.pingTime)"></span>
                            <!-- Statut polling -->
                            <template x-if="routerStatuses[nas.router_id]">
                                <span class="text-[11px] font-medium uppercase tracking-wider flex items-center gap-1"
                                    :class="routerStatuses[nas.router_id]?.online ? 'text-emerald-500' : 'text-gray-400'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="routerStatuses[nas.router_id]?.online ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                    <span x-text="routerStatuses[nas.router_id]?.online ? 'En ligne' : (routerStatuses[nas.router_id]?.last_seen ? 'Vu ' + formatSecondsAgo(routerStatuses[nas.router_id]?.last_seen_ago) : 'Jamais connecté')"></span>
                                </span>
                            </template>
                        </div>

                        <!-- Boutons d'action -->
                        <div
                            class="flex items-center gap-1 bg-gray-50 dark:bg-[#21262d] p-1 rounded-lg border border-gray-200 dark:border-[#30363d]">
                            <button @click="pingNas(nas)"
                                class="p-1.5 text-gray-400 hover:text-emerald-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                :title="'<?= __('nas.check_sync') ?>'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            <button @click="pingApiDirect(nas)" x-show="nas.mikrotik_host && nas.mikrotik_api_username"
                                class="p-1.5 text-gray-400 hover:text-orange-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                :title="'<?= __('nas.ping_api') ?>'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>
                            <a :href="'index.php?page=nas-map&nas_id=' + nas.id" x-show="nas.latitude && nas.longitude"
                                class="p-1.5 text-gray-400 hover:text-purple-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                title="Voir sur la carte">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                                </svg>
                            </a>
                            <button @click="openSetupModal(nas)"
                                class="p-1.5 text-gray-400 hover:text-indigo-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                title="Script Setup MikroTik">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </button>
                            <div class="w-px h-4 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                            <button @click="editNas(nas)"
                                class="p-1.5 text-gray-400 hover:text-blue-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                title="<?= __('common.edit')?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </button>
                            <button @click="deleteNas(nas)"
                                class="p-1.5 text-gray-400 hover:text-rose-500 hover:bg-white dark:hover:bg-[#30363d] rounded-md transition-all"
                                title="<?= __('common.delete')?>">
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

        <template x-if="filteredNas.length === 0">
            <div
                class="col-span-full text-center py-12 bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d]">
                <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                </svg>
                <p class="mt-4 text-gray-500 dark:text-gray-400"
                    x-text="filterZone !== null ? __('nas.empty_zone') : __('nas.empty')"></p>
                <button @click="showModal = true; resetForm()"
                    class="mt-2 text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    <?= __('nas.add_first')?>
                </button>
            </div>
        </template>
    </div>

    <!-- NAS List View -->
    <div x-show="viewMode === 'list'" class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <!-- Table header -->
        <div class="hidden sm:grid grid-cols-12 gap-3 px-4 py-2.5 bg-gray-50 dark:bg-[#0d1117] border-b border-gray-200 dark:border-[#30363d] text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            <div class="col-span-1"><?= __('nas.status') ?? 'Statut' ?></div>
            <div class="col-span-2"><?= __('nas.form_display_name') ?? 'Nom' ?></div>
            <div class="col-span-2"><?= __('nas.form_identifier') ?? 'Identifiant' ?></div>
            <div class="col-span-2"><?= __('nas.form_dns_name') ?? 'DNS / IP' ?></div>
            <div class="col-span-1"><?= __('nas.form_zone') ?? 'Zone' ?></div>
            <div class="col-span-2"><?= __('nas.expiration') ?? 'Expiration' ?></div>
            <div class="col-span-2 text-right"><?= __('common.actions') ?? 'Actions' ?></div>
        </div>

        <!-- Rows -->
        <template x-for="nas in filteredNas" :key="'list-'+nas.id">
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 px-4 py-3 border-b border-gray-100 dark:border-[#21262d] hover:bg-gray-50/50 dark:hover:bg-[#1c2128] transition-colors items-center group">
                <!-- Status -->
                <div class="hidden sm:flex col-span-1 items-center justify-center">
                    <template x-if="nas.pingStatus === 'testing'">
                        <svg class="w-4 h-4 animate-spin text-blue-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </template>
                    <template x-if="nas.pingStatus === 'ok'">
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                    </template>
                    <template x-if="nas.pingStatus === 'fail'">
                        <span class="flex h-3 w-3 rounded-full bg-rose-500"></span>
                    </template>
                    <template x-if="!nas.pingStatus || nas.pingStatus === ''">
                        <span class="flex h-3 w-3 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    </template>
                </div>

                <!-- Name + router_id (mobile stacked, desktop side by side) -->
                <div class="sm:col-span-2 flex items-center gap-3">
                    <!-- Mobile status dot -->
                    <div class="sm:hidden flex-shrink-0">
                        <span class="flex h-2.5 w-2.5 rounded-full"
                            :class="nas.pingStatus === 'ok' ? 'bg-emerald-500' : nas.pingStatus === 'fail' ? 'bg-rose-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="nas.shortname"></p>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 font-mono truncate sm:hidden" x-text="nas.router_id"></p>
                    </div>
                </div>

                <!-- Identifier -->
                <div class="hidden sm:block col-span-2">
                    <p class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate" x-text="nas.router_id"></p>
                </div>

                <!-- DNS / IP -->
                <div class="hidden sm:block col-span-2">
                    <p class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate" x-text="nas.nasname && nas.nasname !== '0.0.0.0/0' ? nas.nasname : (nas.mikrotik_host || '—')"></p>
                </div>

                <!-- Zone -->
                <div class="hidden sm:flex col-span-1 items-center">
                    <template x-if="nas.zone_name">
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 text-[10px] font-medium rounded-full"
                            :style="`background-color: ${nas.zone_color}15; color: ${nas.zone_color}; border: 1px solid ${nas.zone_color}30`">
                            <span class="w-1.5 h-1.5 rounded-full" :style="`background-color: ${nas.zone_color}`"></span>
                            <span x-text="nas.zone_name" class="truncate max-w-[60px]"></span>
                        </span>
                    </template>
                    <template x-if="!nas.zone_name">
                        <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                    </template>
                </div>

                <!-- Expiration -->
                <div class="hidden sm:flex col-span-2 items-center">
                    <template x-if="nas.expires_at">
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium"
                            :class="isExpired(nas.expires_at) ? 'text-rose-500' : isExpiringSoon(nas.expires_at) ? 'text-amber-500' : 'text-gray-500 dark:text-gray-400'">
                            <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span x-text="isExpired(nas.expires_at) ? '<?= __('nas.expired') ?>' : formatExpirationDate(nas.expires_at)"></span>
                        </span>
                    </template>
                    <template x-if="!nas.expires_at">
                        <span class="text-[11px] text-gray-400 dark:text-gray-500">∞</span>
                    </template>
                </div>

                <!-- Mobile: zone + expiration + extras row -->
                <div class="flex items-center gap-2 sm:hidden pl-5 flex-wrap">
                    <template x-if="nas.zone_name">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-medium rounded-full"
                            :style="`background-color: ${nas.zone_color}15; color: ${nas.zone_color}; border: 1px solid ${nas.zone_color}30`">
                            <span class="w-1.5 h-1.5 rounded-full" :style="`background-color: ${nas.zone_color}`"></span>
                            <span x-text="nas.zone_name"></span>
                        </span>
                    </template>
                    <template x-if="nas.expires_at">
                        <span class="text-[10px] font-medium px-1.5 py-0.5 rounded"
                            :class="isExpired(nas.expires_at) ? 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20' : isExpiringSoon(nas.expires_at) ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-[#21262d]'"
                            x-text="isExpired(nas.expires_at) ? '<?= __('nas.expired') ?>' : formatExpirationDate(nas.expires_at)"></span>
                    </template>
                    <template x-if="nas.mikrotik_host">
                        <span class="text-[10px] text-orange-600 dark:text-orange-400 font-mono bg-orange-50 dark:bg-orange-900/20 px-1.5 py-0.5 rounded">API</span>
                    </template>
                    <template x-if="nas.apiStatus === 'ok' && nas.apiData && nas.apiData.uptime">
                        <span class="text-[10px] text-gray-400" x-text="'Up: ' + nas.apiData.uptime"></span>
                    </template>
                </div>

                <!-- Actions -->
                <div class="sm:col-span-2 flex items-center justify-end gap-0.5">
                    <button @click="pingNas(nas)" class="p-1.5 text-gray-400 hover:text-emerald-500 rounded-md transition-colors" :title="'<?= __('nas.check_sync') ?>'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                    <button @click="pingApiDirect(nas)" x-show="nas.mikrotik_host && nas.mikrotik_api_username" class="p-1.5 text-gray-400 hover:text-orange-500 rounded-md transition-colors" :title="'<?= __('nas.ping_api') ?>'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </button>
                    <a :href="'index.php?page=nas-map&nas_id=' + nas.id" x-show="nas.latitude && nas.longitude"
                        class="p-1.5 text-gray-400 hover:text-purple-500 rounded-md transition-colors" title="Carte">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                    </a>
                    <button @click="editNas(nas)" class="p-1.5 text-gray-400 hover:text-blue-500 rounded-md transition-colors" title="<?= __('common.edit')?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button @click="deleteNas(nas)" class="p-1.5 text-gray-400 hover:text-rose-500 rounded-md transition-colors" title="<?= __('common.delete')?>">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- Empty state -->
        <template x-if="filteredNas.length === 0">
            <div class="text-center py-12">
                <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                </svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400" x-text="filterZone !== null ? __('nas.empty_zone') : __('nas.empty')"></p>
                <button @click="showModal = true; resetForm()" class="mt-1.5 text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                    <?= __('nas.add_first')?>
                </button>
            </div>
        </template>
    </div>

    <!-- Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="editMode ? __('nas.edit_title') : __('nas.add_nas')"></h3>
                    <button type="button" @click="showModal = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Scrollable body -->
                <div class="overflow-y-auto flex-1 px-5 py-3">
                    <!-- NAS creation cost banner -->
                    <div x-show="!editMode && nasCost > 0" class="mb-3 px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/15 border border-amber-200/60 dark:border-amber-800/30">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs font-medium text-amber-800 dark:text-amber-300"><strong x-text="nasCost + ' CRT'"></strong> → <strong x-text="nasValidityDays > 0 ? nasValidityDays + ' <?= __('nas.validity_days_label') ?? 'jours' ?>' : '<?= __('nas.unlimited') ?? 'Illimité' ?>'"></strong></span>
                            </div>
                            <span class="text-xs font-semibold" :class="creditBalance >= nasCost ? 'text-green-600 dark:text-green-400' : 'text-red-500'" x-text="'Solde: ' + creditBalance + ' CRT'"></span>
                        </div>
                    </div>

                    <!-- Tabs -->
                    <div class="flex gap-1 mb-3 bg-gray-100 dark:bg-[#21262d] rounded-lg p-0.5">
                        <button type="button" @click="activeTab = 'general'"
                            :class="activeTab === 'general' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                            class="flex-1 py-1.5 px-2 rounded-md text-xs font-medium transition-all">
                            <?= __('nas.tab_general')?>
                        </button>
                        <button type="button" @click="activeTab = 'mikrotik_api'"
                            :class="activeTab === 'mikrotik_api' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                            class="flex-1 py-1.5 px-2 rounded-md text-xs font-medium transition-all">
                            <?= __('nas.tab_api')?>
                        </button>
                        <button type="button" @click="activeTab = 'location'; $nextTick(() => initMap())"
                            :class="activeTab === 'location' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                            class="flex-1 py-1.5 px-2 rounded-md text-xs font-medium transition-all">
                            <?= __('nas.tab_location')?>
                        </button>
                    </div>

                    <form id="nasForm" @submit.prevent="saveNas()">
                        <!-- Tab: Général -->
                        <div x-show="activeTab === 'general'" class="space-y-3">
                            <!-- Compact info hint -->
                            <div class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-xs text-blue-700 dark:text-blue-300">
                                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('nas.check_identity')?> <code class="ml-1 px-1 py-0.5 bg-blue-100 dark:bg-blue-900/50 rounded text-[10px] font-mono">/system/identity/print</code>
                            </div>

                            <!-- NAS-Identifier -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_identifier')?></label>
                                <div class="flex gap-1.5">
                                    <input type="text" x-model="form.router_id" placeholder="<?= __('nas.placeholder_identifier')?>"
                                        class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                    <button type="button" x-show="!editMode" @click="generateNasId()" :disabled="generatingId"
                                        class="px-2.5 py-1.5 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors disabled:opacity-50"
                                        title="<?= __('nas.generate_id')?>">
                                        <svg x-show="!generatingId" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <svg x-show="generatingId" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Display name + DNS -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_display_name')?></label>
                                    <input type="text" x-model="form.shortname" required placeholder="<?= __('nas.placeholder_display_name')?>"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_dns_name')?></label>
                                    <input type="text" x-model="form.nasname" placeholder="wifizone.lan"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                </div>
                            </div>

                            <!-- Zone + Type -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_zone')?></label>
                                    <select x-model="form.zone_id"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <option value=""><?= __('nas.no_zone_option')?></option>
                                        <template x-for="zone in zones" :key="zone.id">
                                            <option :value="zone.id" x-text="zone.name"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_type')?></label>
                                    <select x-model="form.type"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <option value="mikrotik">MikroTik</option>
                                        <option value="cisco">Cisco</option>
                                        <option value="ubiquiti">Ubiquiti</option>
                                        <option value="other"><?= __('nas.type_other')?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Secret + CoA Port -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_secret')?></label>
                                    <input type="text" x-model="form.secret" required placeholder="secret123"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_coa_port')?></label>
                                    <input type="number" x-model="form.ports" placeholder="3799"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.form_description')?></label>
                                <input type="text" x-model="form.description" placeholder="<?= __('nas.placeholder_description')?>"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Tab: API MikroTik -->
                        <div x-show="activeTab === 'mikrotik_api'" class="space-y-3">
                            <!-- Compact info hint -->
                            <div class="flex items-start gap-2 px-2.5 py-1.5 rounded-lg bg-orange-50 dark:bg-orange-900/20 text-xs text-orange-700 dark:text-orange-300">
                                <svg class="w-3.5 h-3.5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span><?= __('nas.api_hint')?> <code class="px-1 py-0.5 bg-orange-100 dark:bg-orange-900/50 rounded text-[10px] font-mono">/ip/service/enable api</code></span>
                            </div>

                            <!-- Host -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.api_address')?></label>
                                <input type="text" x-model="form.mikrotik_host" placeholder="<?= __('nas.placeholder_api_address')?>"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                <p class="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500"><?= __('nas.api_address_hint')?></p>
                            </div>

                            <!-- Port + SSL -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.api_port')?></label>
                                    <input type="number" x-model="form.mikrotik_api_port" placeholder="8728"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.api_ssl')?></label>
                                    <div class="flex items-center h-[34px]">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" x-model="form.mikrotik_use_ssl" class="sr-only peer"
                                                @change="if(form.mikrotik_use_ssl && form.mikrotik_api_port == 8728) form.mikrotik_api_port = 8729; if(!form.mikrotik_use_ssl && form.mikrotik_api_port == 8729) form.mikrotik_api_port = 8728;">
                                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#30363d] peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-500 peer-checked:bg-blue-600"></div>
                                            <span class="ms-2 text-xs text-gray-600 dark:text-gray-400"
                                                x-text="form.mikrotik_use_ssl ? __('nas.ssl_active') : __('nas.ssl_inactive')"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Username + Password -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.api_username')?></label>
                                    <input type="text" x-model="form.mikrotik_api_username" placeholder="admin"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.api_password')?></label>
                                    <div class="relative">
                                        <input :type="showApiPassword ? 'text' : 'password'" x-model="form.mikrotik_api_password" placeholder="••••••••"
                                            class="w-full px-3 py-1.5 pr-8 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <button type="button" @click="showApiPassword = !showApiPassword"
                                            class="absolute inset-y-0 right-0 flex items-center pr-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg x-show="!showApiPassword" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="showApiPassword" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Test connection -->
                            <div>
                                <button type="button" @click="testMikrotikApi()"
                                    :disabled="!form.mikrotik_host || !form.mikrotik_api_username || testingApi"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg x-show="!testingApi" class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <svg x-show="testingApi" class="w-3.5 h-3.5 mr-1.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    <span x-text="testingApi ? __('nas.ping_testing') : __('nas.api_test')"></span>
                                </button>
                                <div x-show="apiTestResult" class="mt-2 px-2.5 py-1.5 rounded-lg text-xs"
                                    :class="apiTestResult === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 border border-red-200 dark:border-red-800'">
                                    <div class="flex items-center gap-1.5">
                                        <svg x-show="apiTestResult === 'success'" class="w-3.5 h-3.5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <svg x-show="apiTestResult === 'error'" class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span x-text="apiTestMessage"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tab: Localisation -->
                        <div x-show="activeTab === 'location'" class="space-y-3">
                            <!-- Map Container -->
                            <div id="nasLocationMap"
                                class="w-full h-44 rounded-lg border border-gray-300 dark:border-[#30363d] bg-gray-100 dark:bg-[#21262d]">
                            </div>

                            <!-- Buttons -->
                            <div class="flex gap-2">
                                <button type="button" @click="getCurrentLocation()"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                    <?= __('nas.location_my_position')?>
                                </button>
                                <button type="button" @click="openGoogleMaps()" x-show="form.latitude && form.longitude"
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    Google Maps
                                </button>
                                <button type="button" @click="clearLocation()" x-show="form.latitude || form.longitude"
                                    class="ml-auto inline-flex items-center px-2 py-1.5 text-xs text-red-600 hover:text-red-700 dark:text-red-400 transition-colors">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    <?= __('nas.location_clear')?>
                                </button>
                            </div>

                            <!-- Coordinates + Address -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.location_latitude')?></label>
                                    <input type="number" step="any" x-model="form.latitude" placeholder="6.3654" @change="updateMapFromCoords()"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.location_longitude')?></label>
                                    <input type="number" step="any" x-model="form.longitude" placeholder="2.4183" @change="updateMapFromCoords()"
                                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('nas.location_address')?></label>
                                <input type="text" x-model="form.address" placeholder="<?= __('nas.placeholder_address')?>"
                                    class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Fixed footer -->
                <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-200 dark:border-[#30363d]">
                    <button type="button" @click="showModal = false"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                        <?= __('common.cancel')?>
                    </button>
                    <button type="submit" form="nasForm" class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <span x-text="editMode ? __('common.save') : __('common.add')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Script Modal -->
    <div x-show="showSetupModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showSetupModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-3xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#30363d]">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            Script Setup MikroTik
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5" x-text="'Routeur: ' + (setupTarget?.shortname || '') + ' (' + (setupTarget?.router_id || '') + ')'"></p>
                    </div>
                    <button type="button" @click="showSetupModal = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <!-- Body -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <!-- Status -->
                    <div class="flex items-center gap-4 p-3 rounded-lg bg-gray-50 dark:bg-[#0d1117] border border-gray-200 dark:border-[#30363d]">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" :class="setupStatus?.online ? 'bg-emerald-500 animate-pulse' : 'bg-gray-400'"></span>
                            <span class="text-sm font-medium" :class="setupStatus?.online ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500'"
                                x-text="setupStatus?.online ? 'En ligne' : 'Hors ligne'"></span>
                        </div>
                        <span x-show="setupStatus?.last_seen" class="text-xs text-gray-400"
                            x-text="'Dernière connexion: ' + (setupStatus?.last_seen || 'jamais')"></span>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="setupStatus?.has_token ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'"
                            x-text="setupStatus?.has_token ? 'Token actif' : 'Pas de token'"></span>
                    </div>

                    <!-- Tabs: Script / Lien -->
                    <div class="flex border-b border-gray-200 dark:border-[#30363d]">
                        <button @click="setupTab = 'script'"
                            :class="setupTab === 'script' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                            Script complet
                        </button>
                        <button @click="setupTab = 'link'"
                            :class="setupTab === 'link' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                            class="px-4 py-2 text-sm font-medium border-b-2 transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            Installer par lien
                        </button>
                    </div>

                    <!-- Tab: Script complet -->
                    <div x-show="setupTab === 'script'">
                        <!-- Instructions -->
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30">
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                <strong>Instructions :</strong> Copiez le script ci-dessous et collez-le dans le Terminal MikroTik (Winbox ou SSH).
                                Le routeur commencera à communiquer automatiquement avec le serveur.
                            </p>
                        </div>

                        <!-- Script -->
                        <div x-show="loadingSetup" class="text-center py-8 text-gray-400">
                            <svg class="w-6 h-6 mx-auto animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <p class="mt-2 text-sm">Génération du script...</p>
                        </div>
                        <div x-show="!loadingSetup">
                            <div class="relative">
                                <pre class="p-4 rounded-lg bg-gray-900 text-gray-100 text-xs font-mono overflow-x-auto max-h-80 whitespace-pre-wrap border border-gray-700"><code x-text="setupScript"></code></pre>
                                <button @click="copySetupScript()"
                                    class="absolute top-2 right-2 px-3 py-1.5 text-xs font-medium bg-white/10 hover:bg-white/20 text-white rounded-lg border border-white/20 transition-colors">
                                    <span x-text="setupCopied ? 'Copié !' : 'Copier'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Installer par lien -->
                    <div x-show="setupTab === 'link'">
                        <div class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/10 border border-blue-200 dark:border-blue-800/30">
                            <p class="text-xs text-blue-700 dark:text-blue-300">
                                <strong>Instructions :</strong> Collez la commande ci-dessous dans le Terminal MikroTik.
                                Le routeur va télécharger et exécuter le script automatiquement.
                            </p>
                        </div>

                        <div x-show="loadingSetup" class="text-center py-8 text-gray-400">
                            <svg class="w-6 h-6 mx-auto animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            <p class="mt-2 text-sm">Génération du lien...</p>
                        </div>
                        <div x-show="!loadingSetup" class="space-y-4">
                            <!-- One-liner command -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">Commande MikroTik (Terminal)</label>
                                <div class="relative">
                                    <pre class="p-4 rounded-lg bg-gray-900 text-gray-100 text-xs font-mono overflow-x-auto whitespace-pre-wrap border border-gray-700"><code x-text="getSetupFetchCommand()"></code></pre>
                                    <button @click="copySetupLink()"
                                        class="absolute top-2 right-2 px-3 py-1.5 text-xs font-medium bg-white/10 hover:bg-white/20 text-white rounded-lg border border-white/20 transition-colors">
                                        <span x-text="setupLinkCopied ? 'Copié !' : 'Copier'"></span>
                                    </button>
                                </div>
                            </div>

                            <!-- URL directe -->
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5">URL du script (pour téléchargement manuel)</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly :value="getSetupScriptUrl()"
                                        class="flex-1 px-3 py-2 text-xs font-mono bg-gray-50 dark:bg-[#0d1117] border border-gray-200 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300"
                                        @click="$event.target.select()">
                                    <button @click="copySetupLink(getSetupScriptUrl())"
                                        class="px-3 py-2 text-xs font-medium border border-gray-300 dark:border-[#30363d] text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors whitespace-nowrap">
                                        Copier l'URL
                                    </button>
                                </div>
                            </div>

                            <!-- Note -->
                            <div class="p-3 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30">
                                <p class="text-xs text-amber-700 dark:text-amber-300">
                                    <strong>Note :</strong> Le lien contient le token d'authentification. Ne le partagez pas publiquement.
                                    Si le token est régénéré, le lien changera.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex items-center justify-between px-5 py-3 border-t border-gray-200 dark:border-[#30363d]">
                    <button @click="regenerateToken()" :disabled="regeneratingToken"
                        class="px-3 py-1.5 text-xs font-medium border border-amber-300 dark:border-amber-700 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/10 transition-colors disabled:opacity-50">
                        <span x-text="regeneratingToken ? 'Génération...' : 'Régénérer le token'"></span>
                    </button>
                    <div class="flex gap-2">
                        <button @click="downloadSetupScript()"
                            class="px-3 py-1.5 text-xs font-medium border border-gray-300 dark:border-[#30363d] text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                            Télécharger .rsc
                        </button>
                        <button @click="showSetupModal = false"
                            class="px-4 py-1.5 text-xs font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS & JS for Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    function nasPage() {
        return {
            nasList: [],
            zones: [],
            filterZone: null,
            viewMode: localStorage.getItem('nas_view_mode') || 'grid',
            showModal: false,
            editMode: false,
            editId: null,
            generatingId: false,
            testingApi: false,
            apiTestResult: null,
            apiTestMessage: '',
            showApiPassword: false,
            activeTab: 'general',
            map: null,
            marker: null,
            nasCost: 0,
            nasValidityDays: 0,
            creditBalance: 0,
            // Setup script modal
            showSetupModal: false,
            setupTarget: null,
            setupScript: '',
            setupStatus: null,
            loadingSetup: false,
            setupCopied: false,
            setupLinkCopied: false,
            setupTab: 'script', // 'script' or 'link'
            setupPollingToken: '',
            regeneratingToken: false,
            routerStatuses: {},

            form: {
                router_id: '',
                zone_id: '',
                shortname: '',
                nasname: '',
                secret: '',
                type: 'mikrotik',
                ports: '',
                description: '',
                mikrotik_host: '',
                mikrotik_api_port: 8728,
                mikrotik_api_username: '',
                mikrotik_api_password: '',
                mikrotik_use_ssl: false,
                latitude: '',
                longitude: '',
                address: ''
            },

            get filteredNas() {
                if (this.filterZone === null) {
                    return this.nasList;
                }
                if (this.filterZone === 0) {
                    return this.nasList.filter(nas => !nas.zone_id);
                }
                return this.nasList.filter(nas => nas.zone_id == this.filterZone);
            },

            async init() {
                this.$watch('viewMode', v => localStorage.setItem('nas_view_mode', v));

                const urlParams = new URLSearchParams(window.location.search);
                const zoneParam = urlParams.get('zone');
                if (zoneParam) {
                    this.filterZone = parseInt(zoneParam);
                }

                await Promise.all([this.loadNas(), this.loadZones(), this.loadCreditInfo()]);
                this.loadRouterStatuses();
                // Rafraîchir les statuts toutes les 15 secondes
                setInterval(() => this.loadRouterStatuses(), 15000);
            },

            async loadNas() {
                try {
                    const response = await API.get('/nas');
                    this.nasList = response.data;
                    this.restorePingResults();
                } catch (error) {
                    showToast(__('nas.msg_load_error'), 'error');
                }
            },

            restorePingResults() {
                try {
                    const saved = JSON.parse(localStorage.getItem('nas_ping_results') || '{}');
                    this.nasList.forEach(nas => {
                        const cached = saved[nas.id];
                        if (cached) {
                            nas.pingStatus = cached.pingStatus;
                            nas.pingLatency = cached.pingLatency;
                            nas.pingMethod = cached.pingMethod;
                            nas.pingMessage = cached.pingMessage;
                            nas.pingHost = cached.pingHost;
                            nas.pingTime = cached.pingTime;
                            // API data (séparé)
                            nas.apiStatus = cached.apiStatus;
                            nas.apiLatency = cached.apiLatency;
                            nas.apiMessage = cached.apiMessage;
                            nas.apiData = cached.apiData;
                            nas.apiTime = cached.apiTime;
                        }
                    });
                } catch (e) { }
            },

            savePingResult(nas) {
                try {
                    const saved = JSON.parse(localStorage.getItem('nas_ping_results') || '{}');
                    saved[nas.id] = {
                        pingStatus: nas.pingStatus,
                        pingLatency: nas.pingLatency,
                        pingMethod: nas.pingMethod,
                        pingMessage: nas.pingMessage,
                        pingHost: nas.pingHost,
                        pingTime: Date.now(),
                        // API data (séparé)
                        apiStatus: nas.apiStatus,
                        apiLatency: nas.apiLatency,
                        apiMessage: nas.apiMessage,
                        apiData: nas.apiData,
                        apiTime: nas.apiTime
                    };
                    localStorage.setItem('nas_ping_results', JSON.stringify(saved));
                } catch (e) { }
            },

            timeAgo(timestamp) {
                if (!timestamp) return '';
                const seconds = Math.floor((Date.now() - timestamp) / 1000);
                if (seconds < 60) return __('time.few_seconds_ago');
                const minutes = Math.floor(seconds / 60);
                if (minutes < 60) return __('time.x_min_ago').replace(':count', minutes);
                const hours = Math.floor(minutes / 60);
                if (hours < 24) return __('time.x_hours_ago').replace(':count', hours);
                const days = Math.floor(hours / 24);
                return __('time.x_days_ago').replace(':count', days);
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data || [];
                } catch (error) {
                    console.error('Error loading zones:', error);
                }
            },

            async loadCreditInfo() {
                try {
                    const res = await fetch('api.php?route=/credits/balance', { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (data.success && data.data) {
                        this.creditBalance = parseFloat(data.data.balance || 0);
                        this.nasCost = parseFloat(data.data.nas_creation_cost || 0);
                        this.nasValidityDays = parseInt(data.data.nas_validity_days || 0);
                    }
                } catch (e) {
                    // Credit system not enabled
                }
            },

            generateSecret(length = 16) {
                const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let result = '';
                const array = new Uint8Array(length);
                crypto.getRandomValues(array);
                for (let i = 0; i < length; i++) {
                    result += chars[array[i] % chars.length];
                }
                return result;
            },

            resetForm() {
                this.form = {
                    router_id: '',
                    zone_id: this.filterZone && this.filterZone !== 0 ? this.filterZone : '',
                    shortname: '',
                    nasname: '',
                    secret: this.generateSecret(),
                    type: 'mikrotik',
                    ports: '',
                    description: '',
                    mikrotik_host: '',
                    mikrotik_api_port: 8728,
                    mikrotik_api_username: '',
                    mikrotik_api_password: '',
                    mikrotik_use_ssl: false,
                    latitude: '',
                    longitude: '',
                    address: ''
                };
                this.editId = null;
                this.activeTab = 'general';
                this.apiTestResult = null;
                this.apiTestMessage = '';
                this.showApiPassword = false;
                if (this.marker) {
                    this.map.removeLayer(this.marker);
                    this.marker = null;
                }
                // Auto-generate NAS identifier
                this.generateNasId();
            },

            async generateNasId() {
                this.generatingId = true;
                try {
                    const response = await API.get('/nas/generate-id');
                    if (response.data && response.data.router_id) {
                        this.form.router_id = response.data.router_id;
                    }
                } catch (error) {
                    showToast(__('nas.msg_generate_id_error'), 'error');
                } finally {
                    this.generatingId = false;
                }
            },

            editNas(nas) {
                this.editMode = true;
                this.editId = nas.id;
                this.activeTab = 'general';
                this.form = {
                    ...nas,
                    zone_id: nas.zone_id || '',
                    mikrotik_host: nas.mikrotik_host || '',
                    mikrotik_api_port: nas.mikrotik_api_port || 8728,
                    mikrotik_api_username: nas.mikrotik_api_username || '',
                    mikrotik_api_password: nas.mikrotik_api_password || '',
                    mikrotik_use_ssl: !!nas.mikrotik_use_ssl,
                    latitude: nas.latitude || '',
                    longitude: nas.longitude || '',
                    address: nas.address || ''
                };
                this.apiTestResult = null;
                this.apiTestMessage = '';
                this.showApiPassword = false;
                this.showModal = true;
                if (this.marker && this.map) {
                    this.map.removeLayer(this.marker);
                    this.marker = null;
                }
            },

            async saveNas() {
                try {
                    const data = {
                        ...this.form,
                        zone_id: this.form.zone_id || null,
                        mikrotik_host: this.form.mikrotik_host || null,
                        mikrotik_api_port: this.form.mikrotik_api_port || 8728,
                        mikrotik_api_username: this.form.mikrotik_api_username || null,
                        mikrotik_api_password: this.form.mikrotik_api_password || null,
                        mikrotik_use_ssl: this.form.mikrotik_use_ssl ? 1 : 0,
                        latitude: this.form.latitude || null,
                        longitude: this.form.longitude || null,
                        address: this.form.address || null
                    };

                    if (this.editMode) {
                        await API.put(`/nas/${this.editId}`, data);
                        showToast(__('nas.msg_updated'));
                    } else {
                        await API.post('/nas', data);
                        showToast(__('nas.msg_created'));
                    }
                    this.showModal = false;
                    await Promise.all([this.loadNas(), this.loadZones()]);
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async deleteNas(nas) {
                if (!confirmAction(__('nas.confirm_delete').replace(':name', nas.shortname))) return;

                try {
                    await API.delete(`/nas/${nas.id}`);
                    showToast(__('nas.msg_deleted'));
                    await Promise.all([this.loadNas(), this.loadZones()]);
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async pingNas(nas) {
                nas.pingStatus = 'testing';
                nas.pingMessage = null;
                nas.pingTime = null;
                try {
                    await this.loadRouterStatuses();
                    const status = this.routerStatuses[nas.router_id];
                    nas.pingStatus = status?.online ? 'ok' : 'fail';
                    nas.pingLatency = null;
                    nas.pingMethod = 'sync';
                    nas.pingMessage = status?.online ? '<?= __js('nas.sync_ok') ?>' : '<?= __js('nas.sync_fail') ?>';
                    nas.pingHost = '';
                    nas.pingTime = Date.now();
                } catch (error) {
                    nas.pingStatus = 'fail';
                    nas.pingMessage = '<?= __js('nas.sync_fail') ?>';
                    nas.pingTime = Date.now();
                }
                this.savePingResult(nas);
            },

            async pingApiDirect(nas) {
                nas.apiStatus = 'testing';
                nas.apiData = null;
                nas.apiMessage = null;
                try {
                    const response = await API.post(`/nas/${nas.id}/ping-api`);
                    nas.apiStatus = response.data.reachable ? 'ok' : 'fail';
                    nas.apiLatency = response.data.latency;
                    nas.apiMessage = response.data.message;
                    nas.apiTime = Date.now();
                    if (response.data.reachable) {
                        nas.apiData = {
                            identity: response.data.identity,
                            uptime: response.data.uptime,
                            version: response.data.version,
                            board: response.data.board,
                            cpu_load: response.data.cpu_load,
                            memory_percent: response.data.memory_percent
                        };
                    }
                } catch (error) {
                    nas.apiStatus = 'fail';
                    nas.apiMessage = error.message || __('common.error');
                    nas.apiTime = Date.now();
                }
                this.savePingResult(nas);
            },

            // Map functions for location tab
            initMap() {
                const defaultLat = 6.3654;
                const defaultLng = 2.4183;
                const defaultZoom = 13;

                const lat = this.form.latitude ? parseFloat(this.form.latitude) : defaultLat;
                const lng = this.form.longitude ? parseFloat(this.form.longitude) : defaultLng;
                const zoom = this.form.latitude ? 15 : defaultZoom;

                if (!this.map) {
                    this.map = L.map('nasLocationMap').setView([lat, lng], zoom);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(this.map);

                    this.map.on('click', (e) => {
                        this.setMarker(e.latlng.lat, e.latlng.lng);
                        this.form.latitude = e.latlng.lat.toFixed(8);
                        this.form.longitude = e.latlng.lng.toFixed(8);
                    });
                } else {
                    this.map.setView([lat, lng], zoom);
                    this.map.invalidateSize();
                }

                if (this.form.latitude && this.form.longitude) {
                    this.setMarker(parseFloat(this.form.latitude), parseFloat(this.form.longitude));
                }
            },

            setMarker(lat, lng) {
                if (this.marker) {
                    this.map.removeLayer(this.marker);
                }

                const routerIcon = L.divIcon({
                    className: 'custom-div-icon',
                    html: `<div style="background-color: #3B82F6; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2"/>
                    </svg>
                </div>`,
                    iconSize: [32, 32],
                    iconAnchor: [16, 16]
                });

                this.marker = L.marker([lat, lng], { icon: routerIcon, draggable: true }).addTo(this.map);

                this.marker.on('dragend', (e) => {
                    const pos = e.target.getLatLng();
                    this.form.latitude = pos.lat.toFixed(8);
                    this.form.longitude = pos.lng.toFixed(8);
                });
            },

            updateMapFromCoords() {
                if (this.form.latitude && this.form.longitude && this.map) {
                    const lat = parseFloat(this.form.latitude);
                    const lng = parseFloat(this.form.longitude);
                    if (!isNaN(lat) && !isNaN(lng)) {
                        this.map.setView([lat, lng], 15);
                        this.setMarker(lat, lng);
                    }
                }
            },

            getCurrentLocation() {
                if (!navigator.geolocation) {
                    showToast(__('nas.geolocation_not_supported'), 'error');
                    return;
                }

                showToast(__('nas.getting_position'), 'info');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        this.form.latitude = lat.toFixed(8);
                        this.form.longitude = lng.toFixed(8);

                        if (this.map) {
                            this.map.setView([lat, lng], 15);
                            this.setMarker(lat, lng);
                        }
                        showToast(__('nas.position_obtained'), 'success');
                    },
                    (error) => {
                        let message = __('nas.geolocation_error');
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                message = __('nas.geolocation_denied');
                                break;
                            case error.POSITION_UNAVAILABLE:
                                message = __('nas.geolocation_unavailable');
                                break;
                            case error.TIMEOUT:
                                message = __('nas.geolocation_timeout');
                                break;
                        }
                        showToast(message, 'error');
                    },
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            },

            clearLocation() {
                this.form.latitude = '';
                this.form.longitude = '';
                this.form.address = '';
                if (this.marker && this.map) {
                    this.map.removeLayer(this.marker);
                    this.marker = null;
                }
            },

            openGoogleMaps() {
                if (this.form.latitude && this.form.longitude) {
                    const url = `https://www.google.com/maps?q=${this.form.latitude},${this.form.longitude}`;
                    window.open(url, '_blank');
                }
            },

            isExpired(expiresAt) {
                if (!expiresAt) return false;
                return new Date(expiresAt) < new Date();
            },

            isExpiringSoon(expiresAt) {
                if (!expiresAt) return false;
                const expires = new Date(expiresAt);
                const now = new Date();
                const diffDays = (expires - now) / (1000 * 60 * 60 * 24);
                return diffDays >= 0 && diffDays <= 7;
            },

            formatExpirationDate(expiresAt) {
                if (!expiresAt) return '';
                const d = new Date(expiresAt);
                return d.toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' });
            },

            daysRemaining(expiresAt) {
                if (!expiresAt) return '';
                const expires = new Date(expiresAt);
                const now = new Date();
                const diffDays = Math.ceil((expires - now) / (1000 * 60 * 60 * 24));
                if (diffDays < 0) return __('nas.expired');
                if (diffDays === 0) return __('nas.expires_today');
                if (diffDays === 1) return '1 ' + __('nas.day_remaining');
                return diffDays + ' ' + __('nas.days_remaining');
            },

            async testMikrotikApi() {
                if (!this.form.mikrotik_host || !this.form.mikrotik_api_username) {
                    showToast(__('nas.api_fill_required'), 'error');
                    return;
                }
                this.testingApi = true;
                this.apiTestResult = null;
                try {
                    const response = await API.post('/nas/test-api', {
                        host: this.form.mikrotik_host,
                        port: this.form.mikrotik_api_port || 8728,
                        username: this.form.mikrotik_api_username,
                        password: this.form.mikrotik_api_password || '',
                        use_ssl: this.form.mikrotik_use_ssl ? 1 : 0
                    });
                    if (response.success) {
                        this.apiTestResult = 'success';
                        this.apiTestMessage = response.data.identity
                            ? __('nas.api_success_identity').replace(':identity', response.data.identity)
                            : __('nas.api_success');
                    } else {
                        this.apiTestResult = 'error';
                        this.apiTestMessage = response.error || __('nas.api_failed');
                    }
                } catch (error) {
                    this.apiTestResult = 'error';
                    this.apiTestMessage = error.message || __('nas.api_connection_error');
                } finally {
                    this.testingApi = false;
                }
            },

            // ===== Setup Script & Router Status =====

            async loadRouterStatuses() {
                try {
                    const res = await API.get('/router-setup/statuses');
                    this.routerStatuses = res.data || {};
                } catch (e) { /* silencieux */ }
            },

            async openSetupModal(nas) {
                this.setupTarget = nas;
                this.showSetupModal = true;
                this.setupScript = '';
                this.setupStatus = null;
                this.setupCopied = false;
                this.setupLinkCopied = false;
                this.setupTab = 'script';
                this.loadingSetup = true;

                try {
                    const [scriptRes, statusRes] = await Promise.all([
                        API.get(`/router-setup/${nas.router_id}`),
                        API.get(`/router-setup/${nas.router_id}/status`),
                    ]);
                    this.setupScript = scriptRes.data?.script || '# Erreur de génération';
                    this.setupPollingToken = scriptRes.data?.polling_token || '';
                    this.setupStatus = statusRes.data || {};
                } catch (e) {
                    this.setupScript = '# Erreur: ' + (e.message || 'Impossible de générer le script');
                }
                this.loadingSetup = false;
            },

            async copySetupScript() {
                try {
                    await navigator.clipboard.writeText(this.setupScript);
                    this.setupCopied = true;
                    showToast('Script copié dans le presse-papiers', 'success');
                    setTimeout(() => this.setupCopied = false, 3000);
                } catch (e) {
                    // Fallback
                    const ta = document.createElement('textarea');
                    ta.value = this.setupScript;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    this.setupCopied = true;
                    showToast('Script copié', 'success');
                    setTimeout(() => this.setupCopied = false, 3000);
                }
            },

            downloadSetupScript() {
                const blob = new Blob([this.setupScript], { type: 'text/plain' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `setup-${this.setupTarget?.router_id || 'nas'}.rsc`;
                a.click();
                URL.revokeObjectURL(url);
            },

            getSetupScriptUrl() {
                if (!this.setupTarget || !this.setupPollingToken) return '';
                const base = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
                return `${base}/setup_script.php?router=${this.setupTarget.router_id}&token=${this.setupPollingToken}`;
            },

            getSetupFetchCommand() {
                const url = this.getSetupScriptUrl();
                if (!url) return '# Erreur: token manquant';
                const isHttps = url.startsWith('https');
                const mode = isHttps ? 'https' : 'http';
                const certOpt = isHttps ? ' check-certificate=no' : '';
                return `/tool fetch url="${url}" dst-path="nas-setup.rsc" mode=${mode}${certOpt}\n:delay 2s\n/import file-name="nas-setup.rsc"\n:delay 1s\n/file remove nas-setup.rsc`;
            },

            async copySetupLink(text) {
                const content = text || this.getSetupFetchCommand();
                try {
                    await navigator.clipboard.writeText(content);
                } catch (e) {
                    const ta = document.createElement('textarea');
                    ta.value = content;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                }
                this.setupLinkCopied = true;
                showToast('Copié dans le presse-papiers', 'success');
                setTimeout(() => this.setupLinkCopied = false, 3000);
            },

            async regenerateToken() {
                if (!confirm('Régénérer le token ? Le script setup devra être réinstallé sur le routeur.')) return;
                this.regeneratingToken = true;
                try {
                    const res = await API.post(`/router-setup/${this.setupTarget.router_id}/generate-token`);
                    showToast(res.data?.message || 'Token régénéré', 'success');
                    // Recharger le script
                    await this.openSetupModal(this.setupTarget);
                } catch (e) {
                    showToast('Erreur: ' + (e.message || 'Échec'), 'error');
                }
                this.regeneratingToken = false;
            },

            formatSecondsAgo(seconds) {
                if (!seconds) return 'jamais';
                if (seconds < 60) return seconds + 's';
                if (seconds < 3600) return Math.floor(seconds / 60) + 'min';
                if (seconds < 86400) return Math.floor(seconds / 3600) + 'h';
                return Math.floor(seconds / 86400) + 'j';
            },

        }
    }
</script>