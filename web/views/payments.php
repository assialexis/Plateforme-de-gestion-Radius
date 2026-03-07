<?php $pageTitle = __('payment.title');
$currentPage = 'payments'; ?>

<div x-data="paymentsPage()" x-init="init()">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('payment.subtitle')?>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex items-center gap-1 mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <button @click="activeTab = 'own'"
            :class="activeTab === 'own' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
            <?= __('paygate.tab_own') ?? 'Mes passerelles' ?>
        </button>
        <button @click="switchTab('platform')"
            :class="activeTab === 'platform' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors">
            <?= __('paygate.tab_platform') ?? 'Passerelle plateforme' ?>
            <span x-show="platformBalance.balance > 0"
                class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400"
                x-text="formatMoney(platformBalance.balance)"></span>
        </button>
    </div>

    <!-- TAB 1: Mes passerelles -->
    <div x-show="activeTab === 'own'">

    <!-- Toggle Vue Grid/Liste -->
    <div class="flex justify-end mb-4">
        <div
            class="flex items-center bg-white dark:bg-[#161b22] rounded-lg p-1 border border-gray-200 dark:border-[#30363d]">
            <button @click="toggleViewMode()"
                :class="viewMode === 'grid' ? 'bg-gray-100 dark:bg-[#21262d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="p-2 rounded-md transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                    </path>
                </svg>
            </button>
            <button @click="toggleViewMode()"
                :class="viewMode === 'list' ? 'bg-gray-100 dark:bg-[#21262d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="p-2 rounded-md transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Gateways Container -->
    <div
        :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6' : 'flex flex-col space-y-4'">
        <template x-for="gateway in gateways" :key="gateway.id">
            <div>
                <!-- VUE GRILLE -->
                <div x-show="viewMode === 'grid'"
                    class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col h-full">

                    <!-- Effet de glow -->
                    <div
                        class="absolute -right-6 -top-6 w-32 h-32 bg-primary-500/10 dark:bg-primary-500/5 rounded-full blur-2xl group-hover:bg-primary-500/20 transition-all duration-500">
                    </div>

                    <div class="relative z-10 flex justify-between items-start mb-6">
                        <div
                            class="w-14 h-14 rounded-2xl bg-gradient-to-br from-gray-50 to-gray-100 dark:from-[#21262d] dark:to-[#161b22] shadow-[0_0_15px_rgba(0,0,0,0.05)] dark:shadow-[0_0_15px_rgba(0,0,0,0.2)] border border-gray-200/50 dark:border-[#30363d] flex items-center justify-center p-2.5 transition-transform duration-300 group-hover:scale-110">
                            <template x-if="getGatewayLogo(gateway.gateway_code)">
                                <img :src="getGatewayLogo(gateway.gateway_code)" :alt="gateway.name"
                                    class="w-full h-full object-contain filter dark:brightness-200 dark:contrast-200 drop-shadow-sm">
                            </template>
                            <template x-if="!getGatewayLogo(gateway.gateway_code)">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </template>
                        </div>

                        <button @click="toggleGateway(gateway)"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-300 focus:outline-none"
                            :class="gateway.is_active ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-[#30363d]'">
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-300 ease-in-out"
                                :class="gateway.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    <div class="relative z-10 mb-6 flex-grow">
                        <h3 class="font-bold text-xl text-gray-900 dark:text-white mb-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                            x-text="gateway.name"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2 leading-relaxed"
                            x-text="gateway.description"></p>
                    </div>

                    <div class="relative z-10 flex items-center gap-2 mb-6">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider"
                            :class="gateway.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20' : 'bg-gray-50 text-gray-600 dark:bg-[#21262d] dark:text-gray-400 border border-gray-200/50 dark:border-[#30363d]'">
                            <span class="w-1.5 h-1.5 rounded-full"
                                :class="gateway.is_active ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-500'"></span>
                            <span x-text="gateway.is_active ? __('payment.active') : __('payment.inactive')"></span>
                        </span>
                        <span
                            class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider"
                            :class="gateway.is_sandbox ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200/50 dark:border-amber-500/20' : 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 border border-primary-200/50 dark:border-primary-500/20'"
                            x-text="gateway.is_sandbox ? __('payment.test_mode') : __('payment.production')"></span>
                    </div>

                    <div class="relative z-10 mt-auto pt-4 border-t border-gray-100 dark:border-[#30363d]">
                        <button @click="editGateway(gateway)"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-50 hover:bg-primary-50 dark:bg-[#21262d] dark:hover:bg-primary-500/10 text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 text-sm font-semibold rounded-xl transition-all duration-300 group/btn">
                            <svg class="w-4 h-4 text-gray-400 group-hover/btn:text-primary-500 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?= __('payment.configure')?>
                        </button>
                    </div>
                </div>

                <!-- VUE LISTE -->
                <div x-show="viewMode === 'list'"
                    class="relative bg-white dark:bg-[#161b22] rounded-xl border border-gray-100 dark:border-[#30363d] p-4 group hover:shadow-lg transition-all duration-300 flex items-center gap-4">

                    <div
                        class="w-12 h-12 shrink-0 rounded-xl bg-gradient-to-br from-gray-50 to-gray-100 dark:from-[#21262d] dark:to-[#161b22] shadow-[0_0_10px_rgba(0,0,0,0.05)] border border-gray-200/50 dark:border-[#30363d] flex items-center justify-center p-2">
                        <template x-if="getGatewayLogo(gateway.gateway_code)">
                            <img :src="getGatewayLogo(gateway.gateway_code)" :alt="gateway.name"
                                class="w-full h-full object-contain filter dark:brightness-200 dark:contrast-200">
                        </template>
                        <template x-if="!getGatewayLogo(gateway.gateway_code)">
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </template>
                    </div>

                    <div class="flex-grow min-w-0">
                        <div class="flex items-center gap-3 mb-1">
                            <h3 class="font-bold text-lg text-gray-900 dark:text-white truncate group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"
                                x-text="gateway.name"></h3>
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider"
                                :class="gateway.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-500/20' : 'bg-gray-50 text-gray-600 dark:bg-[#21262d] dark:text-gray-400 border border-gray-200/50 dark:border-[#30363d]'">
                                <span class="w-1 h-1 rounded-full"
                                    :class="gateway.is_active ? 'bg-emerald-500' : 'bg-gray-400 dark:bg-gray-500'"></span>
                                <span x-text="gateway.is_active ? __('payment.active') : __('payment.inactive')"></span>
                            </span>
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider"
                                :class="gateway.is_sandbox ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border border-amber-200/50 dark:border-amber-500/20' : 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-400 border border-primary-200/50 dark:border-primary-500/20'"
                                x-text="gateway.is_sandbox ? __('payment.test_mode') : __('payment.production')"></span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate" x-text="gateway.description"></p>
                    </div>

                    <div class="flex items-center gap-4 shrink-0">
                        <button @click="editGateway(gateway)"
                            class="flex items-center justify-center gap-2 px-3 py-2 bg-gray-50 hover:bg-primary-50 dark:bg-[#21262d] dark:hover:bg-primary-500/10 text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 text-sm font-semibold rounded-lg transition-all duration-300 group/btn">
                            <svg class="w-4 h-4 text-gray-400 group-hover/btn:text-primary-500 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="hidden sm:inline">
                                <?= __('payment.configure')?>
                            </span>
                        </button>

                        <div class="h-8 w-px bg-gray-200 dark:bg-[#30363d]"></div>

                        <button @click="toggleGateway(gateway)"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-300 focus:outline-none"
                            :class="gateway.is_active ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-[#30363d]'">
                            <span
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-300 ease-in-out"
                                :class="gateway.is_active ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>


        <!-- Placeholder si aucune passerelle -->
        <template x-if="gateways.length === 0 && !loading">
            <div class="col-span-full text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    <?= __('payment.no_gateway')?>
                </h3>
                <p class="text-gray-500 dark:text-gray-400">
                    <?= __('payment.no_gateway_desc')?>
                </p>
            </div>
        </template>

        <!-- Loading -->
        <template x-if="loading">
            <div class="col-span-full flex justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
            </div>
        </template>
    </div>

    </div><!-- /TAB 1 -->

    <!-- TAB 2: Passerelle plateforme -->
    <div x-show="activeTab === 'platform'" x-cloak>
        <div x-show="loadingPlatform" class="text-center py-12 text-gray-500"><?= __('common.loading') ?? 'Chargement...' ?></div>

        <div x-show="!loadingPlatform" class="space-y-6">
            <!-- Balance Card -->
            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-white">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <p class="text-indigo-100 text-sm"><?= __('paygate.balance') ?? 'Solde collecté' ?></p>
                        <p class="text-3xl font-bold mt-1" x-text="formatMoney(platformBalance.balance)"></p>
                        <div class="flex items-center gap-4 mt-2 text-sm text-indigo-200">
                            <span><?= __('paygate.total_collected') ?? 'Collecté' ?>: <span x-text="formatMoney(platformBalance.total_collected)"></span></span>
                            <span><?= __('paygate.total_withdrawn') ?? 'Retiré' ?>: <span x-text="formatMoney(platformBalance.total_withdrawn)"></span></span>
                        </div>
                    </div>
                    <button @click="showWithdrawalModal = true" :disabled="platformBalance.balance <= 0"
                        class="px-5 py-2.5 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors disabled:opacity-50 backdrop-blur-sm">
                        <svg class="w-4 h-4 inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <?= __('paygate.request_withdrawal') ?? 'Demander un retrait' ?>
                    </button>
                </div>
                <div x-show="platformBalance.pending_count > 0" class="mt-3 p-2 bg-white/10 rounded-lg text-sm">
                    <span x-text="platformBalance.pending_count + ' retrait(s) en cours: ' + formatMoney(platformBalance.pending_amount)"></span>
                </div>
            </div>

            <!-- Per-gateway info cards -->
            <div x-show="platformBalance.gateways && platformBalance.gateways.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="gw in platformBalance.gateways" :key="gw.id">
                    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                :class="{
                                    'bg-blue-600': gw.gateway_code === 'fedapay',
                                    'bg-orange-500': gw.gateway_code === 'cinetpay',
                                    'bg-green-600': gw.gateway_code === 'ligdicash',
                                    'bg-emerald-600': gw.gateway_code === 'paygate_global',
                                    'bg-cyan-600': gw.gateway_code === 'feexpay',
                                    'bg-indigo-600': gw.gateway_code === 'kkiapay',
                                    'bg-teal-600': gw.gateway_code === 'paydunya',
                                    'bg-rose-600': gw.gateway_code === 'yengapay',
                                    'bg-purple-600': !['fedapay','cinetpay','ligdicash','paygate_global','feexpay','kkiapay','paydunya','yengapay'].includes(gw.gateway_code)
                                }"
                                x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="gw.name"></h4>
                        </div>
                        <div class="space-y-1 text-xs text-gray-500">
                            <p><?= __('paygate.commission') ?? 'Commission' ?>: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="gw.commission_rate + '%'"></span></p>
                            <p><?= __('paygate.min_withdrawal') ?? 'Retrait min' ?>: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="formatMoney(gw.min_withdrawal) + ' ' + gw.currency"></span></p>
                            <p><?= __('superadmin.paygate_currency') ?? 'Devise' ?>: <span class="font-medium text-gray-700 dark:text-gray-300" x-text="gw.currency"></span></p>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Platform Gateways Grid -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase"><?= __('paygate.tab_platform') ?? 'Passerelles plateforme' ?></h3>
                    <p class="text-xs text-gray-500"><?= __('paygate.no_config_needed') ?? 'Aucune configuration requise — activez simplement la passerelle' ?></p>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                    <template x-for="gw in platformGateways" :key="gw.id">
                        <div class="p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                                    :class="{
                                        'bg-blue-600': gw.gateway_code === 'fedapay',
                                        'bg-orange-500': gw.gateway_code === 'cinetpay',
                                        'bg-green-600': gw.gateway_code === 'ligdicash',
                                    'bg-emerald-600': gw.gateway_code === 'paygate_global',
                                    'bg-cyan-600': gw.gateway_code === 'feexpay',
                                    'bg-indigo-600': gw.gateway_code === 'kkiapay',
                                    'bg-teal-600': gw.gateway_code === 'paydunya',
                                    'bg-rose-600': gw.gateway_code === 'yengapay',
                                    'bg-purple-600': !['fedapay','cinetpay','ligdicash','paygate_global','feexpay','kkiapay','paydunya','yengapay'].includes(gw.gateway_code)
                                    }"
                                    x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 text-sm" x-text="gw.name"></h4>
                                    <p class="text-xs text-gray-500" x-text="gw.description"></p>
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 mt-1">
                                        <?= __('paygate.platform_preconfigured') ?? 'Pré-configurée par la plateforme' ?>
                                    </span>
                                </div>
                            </div>
                            <button @click="togglePlatformGateway(gw)"
                                class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors duration-300 focus:outline-none"
                                :class="gw.admin_active == 1 ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-[#30363d]'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition duration-300 ease-in-out"
                                    :class="gw.admin_active == 1 ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>
                    </template>
                    <div x-show="platformGateways.length === 0" class="p-8 text-center text-gray-500 text-sm">
                        <?= __('paygate.no_platform_gateways') ?? 'Aucune passerelle plateforme disponible' ?>
                    </div>
                </div>
            </div>

            <!-- Withdrawal History -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase"><?= __('paygate.withdrawal_history') ?? 'Historique des retraits' ?></h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-[#30363d]">
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase"><?= __('paygate.col_date') ?? 'Date' ?></th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase"><?= __('paygate.col_amount') ?? 'Montant' ?></th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase"><?= __('paygate.net_amount') ?? 'Net' ?></th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase"><?= __('paygate.col_status') ?? 'Statut' ?></th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                            <template x-for="w in platformWithdrawals" :key="w.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#161b22]/50">
                                    <td class="px-4 py-2.5 text-xs text-gray-500" x-text="formatDate(w.requested_at)"></td>
                                    <td class="px-4 py-2.5 font-semibold text-gray-900 dark:text-gray-100" x-text="formatMoney(w.amount_requested) + ' ' + w.currency"></td>
                                    <td class="px-4 py-2.5 font-semibold text-emerald-600 dark:text-emerald-400" x-text="formatMoney(w.amount_net) + ' ' + w.currency"></td>
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400': w.status === 'pending',
                                                'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400': w.status === 'approved',
                                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400': w.status === 'completed',
                                                'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400': w.status === 'rejected',
                                                'bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-400': w.status === 'cancelled'
                                            }" x-text="wdStatusLabel(w.status)"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right flex items-center justify-end gap-2">
                                        <button @click="showWdDetail(w)" class="px-2.5 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-500/10">
                                            <i class="fas fa-eye mr-1"></i> <?= __('common.view') ?? 'Voir' ?>
                                        </button>
                                        <button x-show="w.status === 'pending'" @click="cancelWithdrawal(w)"
                                            class="px-2.5 py-1 text-xs text-red-600 dark:text-red-400 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-500/10 font-medium">
                                            <?= __('common.cancel') ?? 'Annuler' ?>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="platformWithdrawals.length === 0">
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500 text-sm"><?= __('common.no_data') ?? 'Aucune donnée' ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Withdrawal Detail Modal -->
            <div x-show="wdDetailShow" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="wdDetailShow = false">
                <div class="absolute inset-0 bg-black/40" @click="wdDetailShow = false"></div>
                <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-[#30363d] p-6 max-h-[90vh] overflow-y-auto" id="wdReceiptArea">
                    <div class="flex items-center justify-between mb-5 print:hidden">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"><?= __('paygate.withdrawal_details') ?? 'Détails du retrait' ?></h3>
                        <button @click="wdDetailShow = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"><i class="fas fa-times"></i></button>
                    </div>

                    <template x-if="wdDetailTarget">
                        <div class="space-y-5">
                            <!-- Amount card -->
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-4 text-white text-center">
                                <p class="text-xs opacity-80 uppercase"><?= __('paygate.net_amount') ?? 'Montant net' ?></p>
                                <p class="text-2xl font-bold mt-1" x-text="formatMoney(wdDetailTarget.amount_net) + ' ' + wdDetailTarget.currency"></p>
                                <div class="flex justify-center gap-4 mt-2 text-xs opacity-75">
                                    <span x-text="'<?= __('paygate.col_amount') ?? 'Demandé' ?>: ' + formatMoney(wdDetailTarget.amount_requested)"></span>
                                    <span x-text="'<?= __('paygate.commission') ?? 'Commission' ?>: −' + formatMoney(wdDetailTarget.commission_amount) + ' (' + wdDetailTarget.commission_rate + '%)'"></span>
                                </div>
                            </div>

                            <!-- Timeline / Steps -->
                            <div>
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-3"><?= __('paygate.withdrawal_progress') ?? 'Suivi du retrait' ?></h4>
                                <div class="relative pl-6 space-y-4">
                                    <!-- Step 1: Demande -->
                                    <div class="relative">
                                        <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-indigo-500 border-2 border-white dark:border-[#161b22] flex items-center justify-center">
                                            <i class="fas fa-check text-white text-[7px]"></i>
                                        </div>
                                        <div class="absolute -left-[13px] top-5 w-0.5 h-full bg-gray-200 dark:bg-gray-700" x-show="wdDetailTarget.status !== 'cancelled'"></div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100"><?= __('paygate.step_requested') ?? 'Demande envoyée' ?></p>
                                            <p class="text-xs text-gray-500" x-text="formatDate(wdDetailTarget.requested_at)"></p>
                                        </div>
                                    </div>

                                    <!-- Step 2: Approuvé / Rejeté / Annulé -->
                                    <div class="relative" x-show="wdDetailTarget.status !== 'cancelled'">
                                        <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full border-2 border-white dark:border-[#161b22] flex items-center justify-center"
                                            :class="wdDetailTarget.status === 'rejected' ? 'bg-red-500' : ['approved','completed'].includes(wdDetailTarget.status) ? 'bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'">
                                            <i class="text-white text-[7px]" :class="wdDetailTarget.status === 'rejected' ? 'fas fa-times' : ['approved','completed'].includes(wdDetailTarget.status) ? 'fas fa-check' : 'fas fa-clock'"></i>
                                        </div>
                                        <div class="absolute -left-[13px] top-5 w-0.5 h-full bg-gray-200 dark:bg-gray-700" x-show="['approved','completed'].includes(wdDetailTarget.status)"></div>
                                        <div>
                                            <p class="text-sm font-medium" :class="wdDetailTarget.status === 'rejected' ? 'text-red-600 dark:text-red-400' : ['approved','completed'].includes(wdDetailTarget.status) ? 'text-gray-900 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'"
                                                x-text="wdDetailTarget.status === 'rejected' ? '<?= __('paygate.step_rejected') ?? 'Demande rejetée' ?>' : '<?= __('paygate.step_approved') ?? 'Demande approuvée' ?>'"></p>
                                            <p class="text-xs text-gray-500" x-show="wdDetailTarget.processed_at" x-text="formatDate(wdDetailTarget.processed_at)"></p>
                                            <p class="text-xs text-gray-400 italic" x-show="wdDetailTarget.status === 'pending'"><?= __('paygate.step_waiting_approval') ?? 'En attente de validation...' ?></p>
                                        </div>
                                    </div>

                                    <!-- Step cancelled -->
                                    <div class="relative" x-show="wdDetailTarget.status === 'cancelled'">
                                        <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full bg-gray-400 border-2 border-white dark:border-[#161b22] flex items-center justify-center">
                                            <i class="fas fa-ban text-white text-[7px]"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-gray-500"><?= __('paygate.step_cancelled') ?? 'Annulé par vous' ?></p>
                                        </div>
                                    </div>

                                    <!-- Step 3: Virement effectué -->
                                    <div class="relative" x-show="['approved','completed'].includes(wdDetailTarget.status)">
                                        <div class="absolute -left-6 top-0.5 w-4 h-4 rounded-full border-2 border-white dark:border-[#161b22] flex items-center justify-center"
                                            :class="wdDetailTarget.status === 'completed' ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-gray-600'">
                                            <i class="text-white text-[7px]" :class="wdDetailTarget.status === 'completed' ? 'fas fa-check' : 'fas fa-clock'"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium" :class="wdDetailTarget.status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'"
                                                x-text="wdDetailTarget.status === 'completed' ? '<?= __('paygate.step_completed') ?? 'Virement effectué' ?>' : '<?= __('paygate.step_waiting_transfer') ?? 'En attente du virement...' ?>'"></p>
                                            <p class="text-xs text-gray-500" x-show="wdDetailTarget.completed_at" x-text="formatDate(wdDetailTarget.completed_at)"></p>
                                            <p class="text-xs font-mono text-emerald-600 dark:text-emerald-400" x-show="wdDetailTarget.transfer_reference" x-text="'Réf: ' + wdDetailTarget.transfer_reference"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment info -->
                            <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-4">
                                <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"><?= __('paygate.withdrawal_method') ?? 'Méthode de paiement' ?></h4>
                                <div class="space-y-1.5 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500"><?= __('paygate.withdrawal_method') ?? 'Méthode' ?></span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="wdDetailTarget.payment_method === 'mobile_money' ? 'Mobile Money' : 'Virement bancaire'"></span>
                                    </div>
                                    <template x-if="wdDetailTarget.payment_method === 'mobile_money'">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500"><?= __('paygate.withdrawal_phone') ?? 'Téléphone' ?></span>
                                            <span class="font-medium font-mono text-gray-900 dark:text-gray-100" x-text="wdDetailTarget.payment_details?.phone || '—'"></span>
                                        </div>
                                    </template>
                                    <template x-if="wdDetailTarget.payment_method === 'bank_transfer'">
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500"><?= __('paygate.withdrawal_bank_name') ?? 'Banque' ?></span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="wdDetailTarget.payment_details?.bank_name || '—'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500"><?= __('paygate.withdrawal_account') ?? 'N° compte' ?></span>
                                                <span class="font-medium font-mono text-gray-900 dark:text-gray-100" x-text="wdDetailTarget.payment_details?.account_number || '—'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500"><?= __('paygate.withdrawal_account_name') ?? 'Titulaire' ?></span>
                                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="wdDetailTarget.payment_details?.account_name || '—'"></span>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Superadmin note -->
                            <div x-show="wdDetailTarget.superadmin_note" class="bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/20 rounded-lg p-3">
                                <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1"><i class="fas fa-comment mr-1"></i> <?= __('paygate.note_from_platform') ?? 'Note de la plateforme' ?></p>
                                <p class="text-sm text-blue-800 dark:text-blue-300" x-text="wdDetailTarget.superadmin_note"></p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3 border-t border-gray-200 dark:border-[#30363d] pt-4 print:hidden">
                                <button x-show="wdDetailTarget.status === 'completed'" @click="printWdReceipt()"
                                    class="flex-1 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-500/10">
                                    <i class="fas fa-print mr-1"></i> <?= __('paygate.print_receipt') ?? 'Imprimer le reçu' ?>
                                </button>
                                <button @click="wdDetailShow = false"
                                    class="flex-1 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d]">
                                    <?= __('common.close') ?? 'Fermer' ?>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div><!-- /TAB 2 -->

    <!-- Withdrawal Request Modal -->
    <div x-show="showWithdrawalModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showWithdrawalModal = false">
        <div class="absolute inset-0 bg-black/40" @click="showWithdrawalModal = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-md w-full border border-gray-200 dark:border-[#30363d] p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4"><?= __('paygate.request_withdrawal') ?? 'Demander un retrait' ?></h3>
            <div class="space-y-4">
                <!-- Gateway selector -->
                <div x-show="platformBalance.gateways && platformBalance.gateways.length > 0">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.select_gateway') ?? 'Passerelle' ?> *</label>
                    <select x-model="withdrawalForm.platform_gateway_id" @change="updateWithdrawalGatewayInfo()"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                        <template x-for="gw in platformBalance.gateways" :key="gw.id">
                            <option :value="gw.id"
                                :disabled="platformBalance.balance < gw.min_withdrawal"
                                x-text="gw.name + ' — ' + formatMoney(platformBalance.balance) + ' ' + gw.currency + (platformBalance.balance < gw.min_withdrawal ? ' (Solde insuffisant)' : '') + ' | ' + gw.commission_rate + '% — min ' + formatMoney(gw.min_withdrawal) + ' ' + gw.currency"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_amount') ?? 'Montant' ?> *</label>
                    <input type="number" x-model="withdrawalForm.amount" :max="platformBalance.balance" min="1" step="100"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                    <p class="text-xs text-gray-500 mt-1">
                        <?= __('paygate.available_balance') ?? 'Solde disponible' ?>: <span class="font-semibold text-emerald-600" x-text="formatMoney(platformBalance.balance) + ' ' + selectedGwInfo.currency"></span>
                        | <?= __('paygate.min_withdrawal') ?? 'Minimum' ?>: <span x-text="formatMoney(selectedGwInfo.min_withdrawal) + ' ' + selectedGwInfo.currency"></span>
                        | <?= __('paygate.commission') ?? 'Commission' ?>: <span x-text="selectedGwInfo.commission_rate + '%'"></span>
                    </p>
                    <p x-show="withdrawalForm.amount > 0" class="text-xs text-emerald-600 mt-1">
                        <?= __('paygate.net_amount') ?? 'Montant net' ?>:
                        <span x-text="formatMoney(Math.round(withdrawalForm.amount * (1 - selectedGwInfo.commission_rate / 100))) + ' ' + selectedGwInfo.currency"></span>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_method') ?? 'Méthode' ?> *</label>
                    <select x-model="withdrawalForm.payment_method"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                        <option value="mobile_money"><?= __('paygate.withdrawal_mobile') ?? 'Mobile Money' ?></option>
                        <option value="bank_transfer"><?= __('paygate.withdrawal_bank') ?? 'Virement bancaire' ?></option>
                    </select>
                </div>
                <div x-show="withdrawalForm.payment_method === 'mobile_money'">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_phone') ?? 'Numéro de téléphone' ?> *</label>
                    <input type="tel" x-model="withdrawalForm.payment_details.phone"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                </div>
                <div x-show="withdrawalForm.payment_method === 'bank_transfer'" class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_bank_name') ?? 'Nom de la banque' ?></label>
                        <input type="text" x-model="withdrawalForm.payment_details.bank_name"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_account') ?? 'Numéro de compte' ?></label>
                        <input type="text" x-model="withdrawalForm.payment_details.account_number"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_account_name') ?? 'Titulaire du compte' ?></label>
                        <input type="text" x-model="withdrawalForm.payment_details.account_name"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('paygate.withdrawal_note') ?? 'Note (optionnel)' ?></label>
                    <textarea x-model="withdrawalForm.note" rows="2"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-5">
                <button @click="showWithdrawalModal = false" class="px-4 py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d]">
                    <?= __('common.cancel') ?? 'Annuler' ?>
                </button>
                <button @click="submitWithdrawal()" :disabled="submittingWithdrawal || !withdrawalForm.amount"
                    class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                    <span x-show="!submittingWithdrawal"><?= __('paygate.request_withdrawal') ?? 'Demander' ?></span>
                    <span x-show="submittingWithdrawal"><?= __('common.loading') ?? '...' ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de configuration -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full">
                <!-- Header Modal -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#30363d]">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-lg bg-gray-100 dark:bg-[#21262d] flex items-center justify-center overflow-hidden">
                            <template x-if="selectedGateway && getGatewayLogo(selectedGateway.gateway_code)">
                                <img :src="getGatewayLogo(selectedGateway.gateway_code)" :alt="selectedGateway?.name"
                                    class="w-10 h-10 object-contain">
                            </template>
                            <template x-if="!selectedGateway || !getGatewayLogo(selectedGateway.gateway_code)">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                </svg>
                            </template>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                                x-text="__('payment.configure_title') + ' ' + (selectedGateway?.name || '')"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedGateway?.description">
                            </p>
                        </div>
                    </div>
                    <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Corps Modal -->
                <form @submit.prevent="saveGateway()">
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto">
                        <!-- Mode Sandbox -->
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    <?= __('payment.sandbox_title')?>
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?= __('payment.sandbox_desc')?>
                                </p>
                            </div>
                            <button type="button" @click="form.is_sandbox = !form.is_sandbox"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                :class="form.is_sandbox ? 'bg-yellow-500' : 'bg-green-500'">
                                <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                    :class="form.is_sandbox ? 'translate-x-6' : 'translate-x-1'"></span>
                            </button>
                        </div>

                        <!-- Champs dynamiques selon le type de passerelle -->
                        <template x-for="field in getConfigFields(selectedGateway?.gateway_code)" :key="field.key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <span x-text="field.label"></span>
                                    <span x-show="field.required" class="text-red-500">*</span>
                                </label>

                                <!-- Input text/password -->
                                <template x-if="field.type === 'text' || field.type === 'password'">
                                    <div class="relative">
                                        <input
                                            :type="field.type === 'password' && !showSecrets[field.key] ? 'password' : 'text'"
                                            x-model="form.config[field.key]" :required="field.required"
                                            :placeholder="field.label"
                                            class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white pr-10">
                                        <button x-show="field.type === 'password'" type="button"
                                            @click="showSecrets[field.key] = !showSecrets[field.key]"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                            <svg x-show="!showSecrets[field.key]" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            <svg x-show="showSecrets[field.key]" class="w-5 h-5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>

                                <!-- Select -->
                                <template x-if="field.type === 'select'">
                                    <select x-model="form.config[field.key]" :required="field.required"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <template x-for="option in field.options" :key="option">
                                            <option :value="option" x-text="option"></option>
                                        </template>
                                    </select>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Footer Modal -->
                    <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            <span x-show="!saving">
                                <?= __('common.save')?>
                            </span>
                            <span x-show="saving" class="flex items-center">
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
    </div>
</div>

<script>
    function paymentsPage() {
        return {
            activeTab: 'own',
            viewMode: localStorage.getItem('gatewaysViewMode') || 'grid',
            gateways: [],
            loading: true,
            showModal: false,
            saving: false,
            selectedGateway: null,
            showSecrets: {},
            form: {
                is_sandbox: true,
                config: {}
            },

            // Platform tab
            platformGateways: [],
            platformBalance: { balance: 0, total_collected: 0, total_withdrawn: 0, pending_amount: 0, pending_count: 0, gateways: [] },
            platformWithdrawals: [],
            loadingPlatform: false,
            showWithdrawalModal: false,
            submittingWithdrawal: false,
            withdrawalForm: { amount: '', payment_method: 'mobile_money', payment_details: { phone: '', bank_name: '', account_number: '', account_name: '' }, note: '', platform_gateway_id: null },
            selectedGwInfo: { commission_rate: 5, min_withdrawal: 1000, currency: 'XOF' },
            wdDetailShow: false,
            wdDetailTarget: null,

            toggleViewMode() {
                this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
                localStorage.setItem('gatewaysViewMode', this.viewMode);
            },

            // Configuration des champs par passerelle
            configFields: {
                fedapay: [
                    { key: 'account_name', label: __('payment.field_account_name'), type: 'text', required: true },
                    { key: 'public_key', label: 'Public Key', type: 'text', required: true },
                    { key: 'secret_key', label: 'Secret Key', type: 'password', required: true }
                ],
                cinetpay: [
                    { key: 'site_id', label: 'Site ID', type: 'text', required: true },
                    { key: 'api_key', label: 'API Key', type: 'text', required: true },
                    { key: 'secret_key', label: 'Secret Key', type: 'password', required: true }
                ],
                feexpay: [
                    { key: 'account_name', label: __('payment.field_account_name'), type: 'text', required: true },
                    { key: 'api_key', label: 'API Key', type: 'password', required: true },
                    { key: 'shop_id', label: 'Shop ID', type: 'text', required: true },
                    {
                        key: 'operator', label: __('payment.field_operator'), type: 'select', options: [
                            'mtn', 'moov', 'celtiis_bj', 'coris',
                            'togocom_tg', 'moov_tg',
                            'moov_bf', 'orange_bf',
                            'orange_sn', 'free_sn',
                            'mtn_ci', 'moov_ci', 'wave_ci', 'orange_ci',
                            'mtn_cm', 'orange_cm',
                            'mtn_cg'
                        ], required: true
                    }
                ],
                paygate_global: [
                    { key: 'auth_token', label: __('payment.field_api_key') + ' (Auth Token)', type: 'password', required: true }
                ],
                paydunya: [
                    { key: 'master_key', label: 'Master Key', type: 'text', required: true },
                    { key: 'private_key', label: 'Private Key', type: 'password', required: true },
                    { key: 'token', label: 'Token', type: 'password', required: true },
                    { key: 'store_name', label: __('payment.field_store_name'), type: 'text', required: true }
                ],
                orange_money: [
                    { key: 'merchant_key', label: 'Merchant Key', type: 'text', required: true },
                    { key: 'username', label: 'Username', type: 'text', required: true },
                    { key: 'password', label: 'Password', type: 'password', required: true },
                    { key: 'auth_header', label: 'Auth Header', type: 'text', required: false }
                ],
                mtn_momo: [
                    { key: 'subscription_key', label: 'Subscription Key', type: 'text', required: true },
                    { key: 'api_user', label: 'API User', type: 'text', required: true },
                    { key: 'api_key', label: 'API Key', type: 'password', required: true },
                    { key: 'environment', label: 'Environment', type: 'select', options: ['sandbox', 'production'], required: true }
                ],
                paypal: [
                    { key: 'client_id', label: 'Client ID', type: 'text', required: true },
                    { key: 'client_secret', label: 'Client Secret', type: 'password', required: true }
                ],
                stripe: [
                    { key: 'publishable_key', label: 'Publishable Key', type: 'text', required: true },
                    { key: 'secret_key', label: 'Secret Key', type: 'password', required: true },
                    { key: 'webhook_secret', label: 'Webhook Secret', type: 'password', required: false }
                ],
                kkiapay: [
                    { key: 'public_key', label: 'Public API Key', type: 'text', required: true },
                    { key: 'private_key', label: 'Private API Key', type: 'password', required: true },
                    { key: 'secret', label: 'Secret', type: 'password', required: false }
                ],
                moneroo: [
                    { key: 'secret_key', label: 'Secret Key', type: 'password', required: true },
                    { key: 'public_key', label: 'Public Key', type: 'text', required: false }
                ],
                ligdicash: [
                    { key: 'api_key', label: 'API Key', type: 'password', required: true },
                    { key: 'auth_token', label: 'Auth Token', type: 'password', required: true },
                    { key: 'platform', label: 'Nom plateforme', type: 'text', required: false }
                ],
                cryptomus: [
                    { key: 'merchant_uuid', label: 'Merchant UUID', type: 'text', required: true },
                    { key: 'payment_key', label: 'Payment API Key (pas Payout !)', type: 'password', required: true }
                ],
                yengapay: [
                    { key: 'groupe_id', label: 'Groupe ID', type: 'text', required: true },
                    { key: 'api_key', label: 'API Key', type: 'password', required: true },
                    { key: 'project_id', label: 'Project ID', type: 'text', required: true },
                    { key: 'webhook_secret', label: 'Webhook Secret', type: 'password', required: true }
                ]
            },

            // Logos des passerelles (SVG inline ou URLs)
            gatewayLogos: {
                fedapay: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzAwQjM3MCIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjYwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+RmVkYTwvdGV4dD48L3N2Zz4=",
                cinetpay: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI0U0MDAyQiIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjYwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjAiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+Q2luZXQ8L3RleHQ+PC9zdmc+",
                feexpay: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzFEQTFGMiIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjU1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+RmVleDwvdGV4dD48dGV4dCB4PSI1MCIgeT0iNzUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPnBheTwvdGV4dD48L3N2Zz4=",
                paygate_global: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzJEQkU2MCIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjQ1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+UGF5R2F0ZTwvdGV4dD48dGV4dCB4PSI1MCIgeT0iNjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPkdsb2JhbDwvdGV4dD48L3N2Zz4=",
                paydunya: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzE3QTJCOCIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjQ1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+UGF5PC90ZXh0Pjx0ZXh0IHg9IjUwIiB5PSI2NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+RHVueWE8L3RleHQ+PC9zdmc+",
                orange_money: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI0ZGNjYwMCIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjU1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+T3JhbmdlPC90ZXh0Pjx0ZXh0IHg9IjUwIiB5PSI3NSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE0IiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+TW9uZXk8L3RleHQ+PC9zdmc+",
                mtn_momo: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iI0ZGQ0MwMCIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjU1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjIiIGZpbGw9IiMwMDMzNjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtd2VpZ2h0PSJib2xkIj5NVE48L3RleHQ+PHRleHQgeD0iNTAiIHk9Ijc1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IiMwMDMzNjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk1vTW88L3RleHQ+PC9zdmc+",
                paypal: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzAwMzA4NyIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjYwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+UGF5UGFsPC90ZXh0Pjwvc3ZnPg==",
                stripe: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzYzNUJGRiIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjYwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjAiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+U3RyaXBlPC90ZXh0Pjwvc3ZnPg==",
                kkiapay: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzRGNDZFNSIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjQ1IiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTQiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+S2tpYTwvdGV4dD48dGV4dCB4PSI1MCIgeT0iNjUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxNCIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiPnBheTwvdGV4dD48L3N2Zz4=",
                moneroo: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48cmVjdCB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgZmlsbD0iIzEwQjk4MSIgcng9IjEwIi8+PHRleHQgeD0iNTAiIHk9IjQyIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTMyIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LXdlaWdodD0iYm9sZCI+TW9uZTwvdGV4dD48dGV4dCB4PSI1MCIgeT0iNjIiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMyIgZmlsbD0id2hpdGUiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtd2VpZ2h0PSJib2xkIj5yb288L3RleHQ+PC9zdmc+",
                yengapay: "data:image/svg+xml;base64," + btoa('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#E11D48" rx="10"/><text x="50" y="45" font-family="Arial" font-size="12" fill="white" text-anchor="middle" font-weight="bold">Yenga</text><text x="50" y="65" font-family="Arial" font-size="12" fill="white" text-anchor="middle">Pay</text></svg>')
            },

            async init() {
                await this.loadGateways();
            },

            async loadGateways() {
                this.loading = true;
                try {
                    const response = await API.get('/payments/gateways');
                    this.gateways = response.data;
                } catch (error) {
                    showToast(__('payment.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            getGatewayLogo(code) {
                return this.gatewayLogos[code] || null;
            },

            getConfigFields(code) {
                return this.configFields[code] || [];
            },

            editGateway(gateway) {
                this.selectedGateway = gateway;
                this.form = {
                    is_sandbox: gateway.is_sandbox == 1,
                    config: { ...gateway.config }
                };
                this.showSecrets = {};
                this.showModal = true;
            },

            async saveGateway() {
                if (!this.selectedGateway) return;

                this.saving = true;
                try {
                    await API.put('/payments/gateways/' + this.selectedGateway.id, this.form);
                    showToast(__('payment.msg_saved'));
                    this.showModal = false;
                    await this.loadGateways();
                } catch (error) {
                    showToast(error.message || __('payment.msg_save_error'), 'error');
                } finally {
                    this.saving = false;
                }
            },

            async toggleGateway(gateway) {
                try {
                    await API.post('/payments/gateways/' + gateway.id + '/toggle', {
                        is_active: !gateway.is_active
                    });
                    gateway.is_active = !gateway.is_active;
                    showToast(gateway.is_active ? __('payment.msg_activated') : __('payment.msg_deactivated'));
                } catch (error) {
                    showToast(error.message || __('payment.msg_toggle_error'), 'error');
                }
            },

            // ---- Platform Tab Methods ----
            async switchTab(tab) {
                this.activeTab = tab;
                if (tab === 'platform' && this.platformGateways.length === 0) {
                    await this.loadPlatformData();
                }
            },

            async loadPlatformData() {
                this.loadingPlatform = true;
                await Promise.all([
                    this.loadPlatformGateways(),
                    this.loadPlatformBalance(),
                    this.loadPlatformWithdrawals()
                ]);
                this.loadingPlatform = false;
            },

            async loadPlatformGateways() {
                try {
                    const res = await API.get('/platform-payments/gateways');
                    if (res.success) this.platformGateways = res.data.gateways;
                } catch (e) { console.error(e); }
            },

            async loadPlatformBalance() {
                try {
                    const res = await API.get('/platform-payments/balance');
                    if (res.success) {
                        this.platformBalance = res.data;
                        // Set default gateway for withdrawal (prefer one with sufficient balance)
                        if (res.data.gateways && res.data.gateways.length > 0) {
                            const eligible = res.data.gateways.find(g => res.data.balance >= g.min_withdrawal);
                            this.withdrawalForm.platform_gateway_id = eligible ? eligible.id : res.data.gateways[0].id;
                            this.updateWithdrawalGatewayInfo();
                        }
                    }
                } catch (e) { console.error(e); }
            },

            updateWithdrawalGatewayInfo() {
                const gw = (this.platformBalance.gateways || []).find(g => g.id == this.withdrawalForm.platform_gateway_id);
                if (gw) {
                    this.selectedGwInfo = { commission_rate: gw.commission_rate, min_withdrawal: gw.min_withdrawal, currency: gw.currency };
                }
            },

            async loadPlatformWithdrawals() {
                try {
                    const res = await API.get('/platform-payments/withdrawals');
                    if (res.success) this.platformWithdrawals = res.data.withdrawals;
                } catch (e) { console.error(e); }
            },

            async togglePlatformGateway(gw) {
                try {
                    const res = await API.post(`/platform-payments/gateways/${gw.id}/toggle`);
                    if (res.success) {
                        gw.admin_active = res.data.is_active ? 1 : 0;
                        showToast(res.message);
                    }
                } catch (e) { showToast(e.message || 'Erreur', 'error'); }
            },

            async submitWithdrawal() {
                this.submittingWithdrawal = true;
                try {
                    const res = await API.post('/platform-payments/withdrawals', this.withdrawalForm);
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        this.showWithdrawalModal = false;
                        const gwId = this.withdrawalForm.platform_gateway_id;
                        this.withdrawalForm = { amount: '', payment_method: 'mobile_money', payment_details: { phone: '', bank_name: '', account_number: '', account_name: '' }, note: '', platform_gateway_id: gwId };
                        await this.loadPlatformBalance();
                        await this.loadPlatformWithdrawals();
                    }
                } catch (e) { showToast(e.message || 'Erreur', 'error'); }
                this.submittingWithdrawal = false;
            },

            async cancelWithdrawal(w) {
                if (!confirm('Annuler cette demande de retrait ?')) return;
                try {
                    const res = await API.post(`/platform-payments/withdrawals/${w.id}/cancel`);
                    showToast(res.message, res.success ? 'success' : 'error');
                    if (res.success) {
                        await this.loadPlatformBalance();
                        await this.loadPlatformWithdrawals();
                    }
                } catch (e) { showToast(e.message || 'Erreur', 'error'); }
            },

            wdStatusLabel(status) {
                const labels = { pending: 'En attente', approved: 'Approuvé', completed: 'Complété', rejected: 'Rejeté', cancelled: 'Annulé' };
                return labels[status] || status;
            },

            showWdDetail(w) {
                this.wdDetailTarget = w;
                this.wdDetailShow = true;
            },

            printWdReceipt() {
                const el = document.getElementById('wdReceiptArea');
                if (!el) return;
                const printWin = window.open('', '_blank', 'width=600,height=700');
                printWin.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title><?= __('paygate.print_receipt') ?? 'Reçu de retrait' ?></title>
                <style>body{font-family:Arial,sans-serif;padding:30px;color:#333;max-width:500px;margin:0 auto}
                .header{text-align:center;border-bottom:2px solid #4f46e5;padding-bottom:15px;margin-bottom:20px}
                .header h1{color:#4f46e5;font-size:18px;margin:0 0 5px}
                .header p{color:#666;font-size:12px;margin:0}
                .amount{text-align:center;background:#f0f0ff;border-radius:10px;padding:15px;margin:15px 0}
                .amount .net{font-size:24px;font-weight:bold;color:#4f46e5}
                .amount .details{font-size:11px;color:#888;margin-top:5px}
                .section{margin:15px 0} .section h3{font-size:12px;text-transform:uppercase;color:#999;border-bottom:1px solid #eee;padding-bottom:5px;margin-bottom:8px}
                .row{display:flex;justify-content:space-between;padding:4px 0;font-size:13px}
                .row .label{color:#888} .row .value{font-weight:600}
                .status{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
                .status.completed{background:#d1fae5;color:#065f46} .status.approved{background:#dbeafe;color:#1e40af}
                .status.pending{background:#fef3c7;color:#92400e} .status.rejected{background:#fee2e2;color:#991b1b}
                .status.cancelled{background:#f3f4f6;color:#4b5563}
                .footer{text-align:center;margin-top:25px;padding-top:15px;border-top:1px solid #eee;font-size:11px;color:#aaa}
                @media print{body{padding:15px}}</style></head><body>`);
                const t = this.wdDetailTarget;
                const statusLabels = { pending:'En attente', approved:'Approuvé', completed:'Complété', rejected:'Rejeté', cancelled:'Annulé' };
                const methodLabel = t.payment_method === 'mobile_money' ? 'Mobile Money' : 'Virement bancaire';
                printWin.document.write(`<div class="header"><h1><?= __('paygate.print_receipt') ?? 'Reçu de retrait' ?></h1><p>${this.formatDate(t.requested_at)}</p></div>`);
                printWin.document.write(`<div class="amount"><div class="net">${this.formatMoney(t.amount_net)} ${t.currency}</div><div class="details"><?= __('paygate.col_amount') ?? 'Demandé' ?>: ${this.formatMoney(t.amount_requested)} ${t.currency} — <?= __('paygate.commission') ?? 'Commission' ?>: ${t.commission_rate}%</div></div>`);
                printWin.document.write(`<div class="section"><h3><?= __('paygate.col_status') ?? 'Statut' ?></h3><div class="row"><span class="label"><?= __('paygate.col_status') ?? 'Statut' ?></span><span class="status ${t.status}">${statusLabels[t.status] || t.status}</span></div></div>`);
                printWin.document.write(`<div class="section"><h3><?= __('paygate.withdrawal_method') ?? 'Méthode de paiement' ?></h3><div class="row"><span class="label"><?= __('paygate.withdrawal_method') ?? 'Méthode' ?></span><span class="value">${methodLabel}</span></div>`);
                if (t.payment_method === 'mobile_money') {
                    printWin.document.write(`<div class="row"><span class="label"><?= __('paygate.withdrawal_phone') ?? 'Téléphone' ?></span><span class="value">${t.payment_details?.phone || '—'}</span></div>`);
                } else {
                    printWin.document.write(`<div class="row"><span class="label"><?= __('paygate.withdrawal_bank_name') ?? 'Banque' ?></span><span class="value">${t.payment_details?.bank_name || '—'}</span></div>`);
                    printWin.document.write(`<div class="row"><span class="label"><?= __('paygate.withdrawal_account') ?? 'N° compte' ?></span><span class="value">${t.payment_details?.account_number || '—'}</span></div>`);
                    printWin.document.write(`<div class="row"><span class="label"><?= __('paygate.withdrawal_account_name') ?? 'Titulaire' ?></span><span class="value">${t.payment_details?.account_name || '—'}</span></div>`);
                }
                printWin.document.write('</div>');
                if (t.transfer_reference) {
                    printWin.document.write(`<div class="section"><h3>Référence</h3><div class="row"><span class="label">Réf. virement</span><span class="value">${t.transfer_reference}</span></div></div>`);
                }
                printWin.document.write(`<div class="footer"><?= htmlspecialchars($config['app_name'] ?? 'RADIUS Manager') ?></div></body></html>`);
                printWin.document.close();
                printWin.focus();
                setTimeout(() => printWin.print(), 300);
            },

            formatMoney(amount) {
                return parseFloat(amount || 0).toLocaleString('fr-FR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            },

            formatDate(dateStr) {
                if (!dateStr) return '-';
                return new Date(dateStr).toLocaleString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            }
        };
    }
</script>