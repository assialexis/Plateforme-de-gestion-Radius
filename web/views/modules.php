<?php $pageTitle = 'Gestion des Modules';
$currentPage = 'modules'; ?>

<div x-data="modulesPage()" x-init="init()">

    <!-- Credit Balance Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6" x-show="creditSystemEnabled">
        <!-- CRT Card -->
        <div class="relative overflow-hidden bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('credits.balance_label') ?? 'Solde crédits' ?></p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white" x-text="creditBalance.toFixed(2)">0.00</span>
                        <span class="text-sm font-medium text-gray-400">CRT</span>
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <a href="index.php?page=subscription"
                class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('module.recharge_credits') ?? 'Recharger' ?>
            </a>
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
                </div>
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-lg shadow-emerald-500/20">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                </div>
            </div>
            <a href="index.php?page=subscription"
                class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('sms_credits.convert_title') ?? 'Convertir CRT → CSMS' ?>
            </a>
            <div class="absolute -right-4 -bottom-4 w-24 h-24 rounded-full bg-emerald-500/5 dark:bg-emerald-500/10"></div>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="flex gap-1 -mb-px">
            <button @click="activeTab = 'all'"
                class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors border-b-2"
                :class="activeTab === 'all'
                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-500/10'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                    <?= __('module.tab_all') ?? 'Tous les modules' ?>
                </span>
            </button>
            <button @click="activeTab = 'subscriptions'"
                class="px-4 py-2.5 text-sm font-medium rounded-t-lg transition-colors border-b-2"
                :class="activeTab === 'subscriptions'
                    ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-500/10'
                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'">
                <span class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <?= __('module.tab_subscriptions') ?? 'Mes abonnements' ?>
                    <span x-show="activeModules.length > 0"
                        class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400"
                        x-text="activeModules.length"></span>
                </span>
            </button>
        </nav>
    </div>

    <!-- TAB: All Modules -->
    <div x-show="activeTab === 'all'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <template x-for="module in modules" :key="module.module_code">
                <div class="group bg-white dark:bg-[#161b22] rounded-2xl border transition-all duration-200 hover:shadow-md"
                    :class="module.is_active
                        ? 'border-green-200/60 dark:border-green-800/30'
                        : 'border-gray-200/60 dark:border-[#30363d]'">

                    <div class="p-5">
                        <!-- Top Row: Icon + Name + Info button -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors"
                                    :class="module.is_active
                                        ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400'
                                        : 'bg-gray-100 dark:bg-[#21262d] text-gray-400 dark:text-gray-500'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="module.icon === 'gift'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        <path x-show="module.icon === 'chat-bubble-left-right'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2h-3l-4 3v-3H6a2 2 0 01-2-2v-6m16 0H4" />
                                        <path x-show="module.icon === 'device-phone-mobile'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        <path x-show="module.icon === 'chart-bar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        <path x-show="!['gift','chat-bubble-left-right','device-phone-mobile','chart-bar'].includes(module.icon)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="module.name"></h3>
                                    <!-- Status badge -->
                                    <span x-show="module.is_active" class="inline-flex items-center gap-1 text-[11px] font-medium text-green-600 dark:text-green-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        <?= __('module.active') ?? 'Actif' ?>
                                    </span>
                                    <span x-show="!module.is_active && !isPaidModule(module)" class="text-[11px] text-gray-400"><?= __('module.inactive') ?? 'Inactif' ?></span>
                                    <span x-show="!module.is_active && isPaidModule(module)" class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600 dark:text-amber-400">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                        <?= __('module.requires_payment') ?? 'Abonnement requis' ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Info button -->
                            <button @click="showDetail(module)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors"
                                title="<?= __('module.details') ?? 'Détails' ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-gray-500 dark:text-gray-400 line-clamp-2 mb-4" x-text="module.description"></p>

                        <!-- Price badge -->
                        <div x-show="creditSystemEnabled" class="mb-4">
                            <span x-show="module.price_credits > 0"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-semibold"
                                :class="module.is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-600 dark:bg-[#21262d] dark:text-gray-400'">
                                <span x-text="module.price_credits + ' CRT'"></span>
                                <span x-show="module.billing_type === 'monthly'" class="opacity-60">/<?= __('credits.month_short') ?? 'mois' ?></span>
                                <span x-show="module.billing_type === 'one_time'" class="opacity-60">(<?= __('credits.one_time') ?? 'unique' ?>)</span>
                            </span>
                            <span x-show="module.price_credits === 0"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-gray-50 text-gray-400 dark:bg-[#21262d] dark:text-gray-500">
                                <?= __('credits.free') ?? 'Gratuit' ?>
                            </span>
                        </div>

                        <!-- Action area -->
                        <div class="flex items-center gap-2">
                            <!-- Toggle for free modules or active modules -->
                            <template x-if="!isPaidModule(module) || module.is_active">
                                <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                    <input type="checkbox" class="sr-only peer" :checked="module.is_active" @change="toggleModule(module)">
                                    <div class="w-9 h-5 rounded-full transition-colors peer"
                                        :class="module.is_active ? 'bg-green-500' : 'bg-gray-300 dark:bg-[#30363d]'">
                                        <span class="absolute top-0.5 left-0.5 w-4 h-4 rounded-full bg-white shadow transition-transform"
                                            :class="module.is_active ? 'translate-x-4' : 'translate-x-0'"></span>
                                    </div>
                                </label>
                            </template>

                            <!-- Subscribe button for paid inactive modules -->
                            <button x-show="isPaidModule(module) && !module.is_active"
                                @click="openSubscribeModal(module)"
                                :disabled="subscribing === module.module_code"
                                class="flex-1 py-2 px-3 text-xs font-semibold rounded-lg transition-all flex items-center justify-center gap-1.5"
                                :class="creditBalance >= module.price_credits
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                                    : 'bg-gray-100 dark:bg-[#21262d] text-gray-400 cursor-not-allowed'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span x-text="creditBalance >= module.price_credits ? '<?= __('module.subscribe') ?? 'Souscrire' ?>' : '<?= __('credits.insufficient_balance') ?? 'Solde insuffisant' ?>'"></span>
                            </button>

                            <!-- Quick link if active -->
                            <a x-show="module.is_active && getModuleLink(module.module_code)"
                                :href="getModuleLink(module.module_code)"
                                class="ml-auto inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                                <?= __('module.access') ?? 'Accéder' ?>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- TAB: My Subscriptions -->
    <div x-show="activeTab === 'subscriptions'" x-cloak>
        <!-- Empty state -->
        <div x-show="activeModules.length === 0 && !loading" class="text-center py-16">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center">
                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('module.no_active_subscriptions') ?? 'Aucun abonnement actif' ?></p>
            <button @click="activeTab = 'all'" class="mt-3 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                <?= __('module.browse_modules') ?? 'Parcourir les modules' ?>
            </button>
        </div>

        <!-- Active subscriptions list -->
        <div class="space-y-3" x-show="activeModules.length > 0">
            <template x-for="module in activeModules" :key="module.module_code">
                <div class="bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-start justify-between">
                            <!-- Module info -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="module.icon === 'gift'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                                        <path x-show="module.icon === 'chat-bubble-left-right'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12V6a2 2 0 00-2-2H6a2 2 0 00-2 2v6m16 0v6a2 2 0 01-2 2h-3l-4 3v-3H6a2 2 0 01-2-2v-6m16 0H4" />
                                        <path x-show="module.icon === 'device-phone-mobile'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        <path x-show="module.icon === 'chart-bar'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        <path x-show="!['gift','chat-bubble-left-right','device-phone-mobile','chart-bar'].includes(module.icon)" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="module.name"></h3>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-green-600 dark:text-green-400">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                                            <?= __('module.active') ?? 'Actif' ?>
                                        </span>
                                        <span x-show="isPaidModule(module)" class="text-[11px] text-gray-400" x-text="module.price_credits + ' CRT/' + (module.billing_type === 'monthly' ? '<?= __('credits.month_short') ?? 'mois' ?>' : '<?= __('credits.one_time') ?? 'unique' ?>')"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Info button -->
                            <button @click="showDetail(module)"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </button>
                        </div>

                        <!-- Expiration & Auto-renew Section (monthly paid only) -->
                        <div x-show="isPaidModule(module) && module.billing_type === 'monthly'" class="mt-4 pt-4 border-t border-gray-100 dark:border-[#21262d]">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <!-- Expiration info -->
                                <div x-show="module.next_renewal_at" class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                        :class="isExpiringSoon(module.next_renewal_at)
                                            ? 'bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                            : 'bg-gray-100 dark:bg-[#21262d] text-gray-400 dark:text-gray-500'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('module.expires_on') ?? 'Expire le' ?></p>
                                        <p class="text-sm font-medium"
                                            :class="isExpiringSoon(module.next_renewal_at) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'"
                                            x-text="formatDate(module.next_renewal_at)"></p>
                                        <p x-show="isExpiringSoon(module.next_renewal_at)" class="text-[11px] text-amber-500"
                                            x-text="daysUntilExpiry(module.next_renewal_at) <= 0
                                                ? '<?= __('module.expired') ?? 'Expiré' ?>'
                                                : '<?= __('module.expires_in') ?? 'Dans' ?> ' + daysUntilExpiry(module.next_renewal_at) + ' <?= __('module.days') ?? 'jours' ?>'"></p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <!-- Auto-renew toggle -->
                                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-gray-50 dark:bg-[#0d1117]">
                                        <span class="text-[11px] text-gray-500 dark:text-gray-400 whitespace-nowrap"><?= __('module.auto_renew') ?? 'Auto' ?></span>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer" :checked="module.auto_renew" @change="toggleAutoRenew(module)">
                                            <div class="w-7 h-4 rounded-full transition-colors peer"
                                                :class="module.auto_renew ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-[#30363d]'">
                                                <span class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow transition-transform"
                                                    :class="module.auto_renew ? 'translate-x-3' : 'translate-x-0'"></span>
                                            </div>
                                        </label>
                                    </div>

                                    <!-- Renew button -->
                                    <button @click="openRenewModal(module)"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors shadow-sm">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <?= __('module.renew') ?? 'Prolonger' ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- One-time payment badge -->
                        <div x-show="isPaidModule(module) && module.billing_type === 'one_time'" class="mt-4 pt-4 border-t border-gray-100 dark:border-[#21262d]">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('module.lifetime_access') ?? 'Accès permanent' ?>
                            </span>
                        </div>

                        <!-- Free module indicator -->
                        <div x-show="!isPaidModule(module)" class="mt-4 pt-4 border-t border-gray-100 dark:border-[#21262d]">
                            <span class="inline-flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('credits.free') ?? 'Gratuit' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Detail Modal (Drawer right) -->
    <template x-teleport="body">
        <div x-show="detailModal.open" x-cloak class="fixed inset-0 z-[60]" @keydown.escape.window="detailModal.open = false">
            <div class="fixed inset-0 bg-black/30 backdrop-blur-sm" @click="detailModal.open = false" x-show="detailModal.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div class="fixed inset-y-0 right-0 w-full max-w-sm bg-white dark:bg-[#161b22] shadow-2xl border-l border-gray-200 dark:border-[#30363d] flex flex-col"
                x-show="detailModal.open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                <!-- Header -->
                <div class="px-5 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center"
                            :class="detailModal.module?.is_active ? 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400' : 'bg-gray-100 dark:bg-[#21262d] text-gray-400'">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="detailModal.module?.name"></h3>
                            <span class="text-[11px]"
                                :class="detailModal.module?.is_active ? 'text-green-500' : 'text-gray-400'"
                                x-text="detailModal.module?.is_active ? '<?= __('module.active') ?>' : '<?= __('module.inactive') ?>'"></span>
                        </div>
                    </div>
                    <button @click="detailModal.open = false" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto p-5 space-y-5">
                    <!-- Description -->
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Description</h4>
                        <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed" x-text="detailModal.module?.description"></p>
                    </div>

                    <!-- Pricing -->
                    <div x-show="creditSystemEnabled">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('module.pricing') ?? 'Tarification' ?></h4>
                        <div class="bg-gray-50 dark:bg-[#0d1117] rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.unit_price') ?? 'Prix' ?></span>
                                <span class="text-sm font-bold text-gray-900 dark:text-white" x-text="(detailModal.module?.price_credits || 0) + ' CRT'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.billing') ?? 'Facturation' ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-md"
                                    :class="detailModal.module?.billing_type === 'monthly' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-gray-100 text-gray-600 dark:bg-[#21262d] dark:text-gray-400'"
                                    x-text="detailModal.module?.billing_type === 'monthly' ? '<?= __('module.monthly') ?? 'Mensuel' ?>' : '<?= __('module.one_time_payment') ?? 'Paiement unique' ?>'"></span>
                            </div>
                            <div x-show="detailModal.module?.price_credits == 0" class="flex items-center justify-between">
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium"><?= __('credits.free') ?? 'Gratuit' ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Subscription Info -->
                    <div x-show="detailModal.module?.is_active && detailModal.module?.activated_at">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('module.subscription_info') ?? 'Abonnement' ?></h4>
                        <div class="bg-gray-50 dark:bg-[#0d1117] rounded-xl p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.activated_on') ?? 'Activé le' ?></span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300" x-text="formatDate(detailModal.module?.activated_at)"></span>
                            </div>
                            <div x-show="detailModal.module?.next_renewal_at" class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.expires_on') ?? 'Expire le' ?></span>
                                <span class="text-xs font-medium"
                                    :class="isExpiringSoon(detailModal.module?.next_renewal_at) ? 'text-amber-600 dark:text-amber-400' : 'text-gray-700 dark:text-gray-300'"
                                    x-text="formatDate(detailModal.module?.next_renewal_at)"></span>
                            </div>
                            <div x-show="detailModal.module?.last_renewal_at" class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.last_renewed') ?? 'Dernier renouvellement' ?></span>
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300" x-text="formatDate(detailModal.module?.last_renewal_at)"></span>
                            </div>
                            <div x-show="detailModal.module?.auto_renew" class="flex items-center justify-between">
                                <span class="text-xs text-gray-500"><?= __('module.auto_renew') ?? 'Renouvellement auto' ?></span>
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <?= __('common.enabled') ?? 'Activé' ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick link -->
                    <div x-show="detailModal.module?.is_active && getModuleLink(detailModal.module?.module_code)">
                        <a :href="getModuleLink(detailModal.module?.module_code)"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl bg-gray-900 dark:bg-white text-white dark:text-gray-900 hover:bg-gray-800 dark:hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            <?= __('module.access') ?? 'Accéder au module' ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Subscribe / Renew Modal -->
    <template x-teleport="body">
        <div x-show="subModal.open" x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            @keydown.escape.window="subModal.open = false">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="subModal.open = false"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-sm overflow-hidden"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                @click.stop>

                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold" x-text="subModal.isRenew
                                ? '<?= __('module.renew_title') ?? 'Prolonger l\'abonnement' ?>'
                                : '<?= __('module.subscribe_title') ?? 'Souscrire au module' ?>'"></h3>
                            <p class="text-sm text-indigo-100 mt-0.5" x-text="subModal.module?.name"></p>
                        </div>
                        <button @click="subModal.open = false" class="text-white/80 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-4">
                    <!-- Price info -->
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-xl p-3 text-center">
                        <p class="text-xs text-indigo-600 dark:text-indigo-400 font-medium">
                            <span x-text="subModal.module?.price_credits"></span> CRT
                            <span x-show="subModal.billingType === 'monthly'"> / <?= __('credits.month_short') ?? 'mois' ?></span>
                            <span x-show="subModal.billingType === 'one_time'" class="opacity-60">(<?= __('credits.one_time') ?? 'unique' ?>)</span>
                        </p>
                    </div>

                    <!-- Month selector (monthly) -->
                    <div x-show="subModal.billingType === 'monthly'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <?= __('module.number_of_months') ?? 'Nombre de mois' ?>
                        </label>
                        <div class="flex gap-2 justify-center">
                            <template x-for="m in [1, 3, 6, 12]" :key="m">
                                <button @click="subModal.months = m" type="button"
                                    class="px-4 py-2 text-sm font-medium rounded-xl transition-all"
                                    :class="subModal.months == m
                                        ? 'bg-indigo-600 text-white shadow-sm'
                                        : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                    x-text="m + ' <?= __('credits.month_short') ?? 'mois' ?>'"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500"><?= __('module.unit_price') ?? 'Prix unitaire' ?></span>
                            <span class="font-medium text-gray-700 dark:text-gray-300" x-text="subModal.module?.price_credits + ' CRT'"></span>
                        </div>
                        <div x-show="subModal.billingType === 'monthly'" class="flex items-center justify-between text-sm">
                            <span class="text-gray-500"><?= __('module.duration') ?? 'Durée' ?></span>
                            <span class="font-medium text-gray-700 dark:text-gray-300" x-text="subModal.months + ' <?= __('credits.month_short') ?? 'mois' ?>'"></span>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-2 flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('module.total_cost') ?? 'Total' ?></span>
                            <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400" x-text="subModalTotal + ' CRT'"></span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-400"><?= __('credits.your_balance') ?? 'Votre solde' ?></span>
                            <span :class="creditBalance >= subModalTotal ? 'text-green-500' : 'text-red-500'" class="font-medium" x-text="creditBalance.toFixed(2) + ' CRT'"></span>
                        </div>
                    </div>

                    <!-- Insufficient balance warning -->
                    <div x-show="creditBalance < subModalTotal" class="bg-red-50 dark:bg-red-900/20 rounded-xl p-3 text-center">
                        <p class="text-xs text-red-600 dark:text-red-400 font-medium">
                            <?= __('credits.insufficient_balance') ?? 'Solde insuffisant' ?> —
                            <a href="index.php?page=subscription" class="underline"><?= __('module.recharge_credits') ?? 'Recharger' ?></a>
                        </p>
                    </div>

                    <!-- Error -->
                    <p x-show="subModal.error" class="text-xs text-red-500 text-center" x-text="subModal.error"></p>

                    <!-- Submit -->
                    <button @click="confirmSubscription()"
                        :disabled="creditBalance < subModalTotal || subModal.loading || subModal.months < 1"
                        class="w-full py-3 px-4 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-semibold rounded-xl transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-sm">
                        <svg x-show="subModal.loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <span x-text="subModal.loading
                            ? (subModal.isRenew ? '<?= __('module.renewing') ?? 'Prolongation...' ?>' : '<?= __('module.subscribing') ?? 'Souscription...' ?>')
                            : (subModal.isRenew ? '<?= __('module.confirm_and_renew') ?? 'Confirmer et prolonger' ?>' : '<?= __('module.confirm_and_subscribe') ?? 'Confirmer et souscrire' ?>')"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

    <!-- Loading -->
    <div x-show="loading" class="flex justify-center py-16">
        <svg class="animate-spin h-8 w-8 text-indigo-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Empty state -->
    <div x-show="modules.length === 0 && !loading" class="text-center py-16">
        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center">
            <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <h3 class="text-sm font-medium text-gray-900 dark:text-white"><?= __('module.empty') ?? 'Aucun module disponible' ?></h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400"><?= __('module.empty_hint') ?? 'Les modules seront disponibles bientôt.' ?></p>
    </div>
</div>

<script>
function modulesPage() {
    return {
        modules: [],
        loading: true,
        creditSystemEnabled: false,
        creditBalance: 0,
        smsBalance: 0,
        subscribing: null,
        activeTab: 'all',

        // Detail drawer
        detailModal: { open: false, module: null },

        // Subscribe/Renew modal
        subModal: {
            open: false,
            module: null,
            billingType: 'monthly',
            months: 1,
            loading: false,
            error: '',
            isRenew: false,
        },

        get subModalTotal() {
            if (!this.subModal.module) return 0;
            if (this.subModal.billingType === 'one_time') return this.subModal.module.price_credits;
            return this.subModal.module.price_credits * Math.max(1, this.subModal.months || 1);
        },

        get activeModules() {
            return this.modules.filter(m => m.is_active);
        },

        async init() {
            await Promise.all([this.loadModules(), this.loadSmsBalance()]);
        },

        async loadModules() {
            this.loading = true;
            try {
                const res = await fetch('api.php?route=/modules');
                const data = await res.json();
                if (data.success && data.data) {
                    this.modules = data.data.modules || [];
                    this.creditSystemEnabled = data.data.credit_system_enabled || false;
                    this.creditBalance = parseFloat(data.data.credit_balance || 0);
                }
            } catch (e) {
                console.error('Error loading modules:', e);
            } finally {
                this.loading = false;
            }
        },

        async loadSmsBalance() {
            try {
                const res = await fetch('api.php?route=/sms-credits/balance');
                const data = await res.json();
                if (data.success && data.data) {
                    this.smsBalance = parseFloat(data.data.balance || 0);
                }
            } catch (e) {
                // SMS credits not enabled or table doesn't exist
                this.smsBalance = 0;
            }
        },

        isPaidModule(module) {
            return this.creditSystemEnabled && module.price_credits > 0 && module.pricing_active;
        },

        showDetail(module) {
            this.detailModal.module = module;
            this.detailModal.open = true;
        },

        openSubscribeModal(module) {
            this.subModal.module = module;
            this.subModal.billingType = module.billing_type || 'monthly';
            this.subModal.months = 1;
            this.subModal.error = '';
            this.subModal.loading = false;
            this.subModal.isRenew = false;
            this.subModal.open = true;
        },

        openRenewModal(module) {
            this.subModal.module = module;
            this.subModal.billingType = 'monthly';
            this.subModal.months = 1;
            this.subModal.error = '';
            this.subModal.loading = false;
            this.subModal.isRenew = true;
            this.subModal.open = true;
        },

        async toggleModule(module) {
            if (module.is_active) {
                if (!confirm("<?= __('module.confirm_disable') ?? 'Voulez-vous vraiment désactiver ce module ?' ?>")) {
                    module.is_active = true;
                    return;
                }
            }
            try {
                const res = await fetch(`api.php?route=/modules/${module.module_code}/toggle`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (data.success && data.data) {
                    const idx = this.modules.findIndex(m => m.module_code === module.module_code);
                    if (idx !== -1) this.modules[idx].is_active = data.data.is_active;
                    if (data.data.new_balance !== undefined) this.creditBalance = parseFloat(data.data.new_balance);
                    this.notify(data.data.message || '<?= __('module.msg_modified') ?? 'Module modifié' ?>', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.notify(data.message || '<?= __('module.msg_modify_error') ?? 'Erreur' ?>', 'error');
                    await this.loadModules();
                }
            } catch (e) {
                this.notify('<?= __('module.msg_modify_error') ?? 'Erreur' ?>', 'error');
                await this.loadModules();
            }
        },

        async toggleAutoRenew(module) {
            try {
                const res = await fetch(`api.php?route=/modules/${module.module_code}/auto-renew`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    const idx = this.modules.findIndex(m => m.module_code === module.module_code);
                    if (idx !== -1) this.modules[idx].auto_renew = data.data.auto_renew;
                    this.notify(data.data.message, 'success');
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                    await this.loadModules();
                }
            } catch (e) {
                this.notify('Erreur', 'error');
                await this.loadModules();
            }
        },

        async confirmSubscription() {
            const module = this.subModal.module;
            if (!module) return;
            const total = this.subModalTotal;
            if (this.creditBalance < total) {
                this.subModal.error = '<?= __('credits.insufficient_balance') ?? 'Solde insuffisant' ?>';
                return;
            }
            this.subModal.loading = true;
            this.subModal.error = '';
            this.subscribing = module.module_code;
            try {
                const url = this.subModal.isRenew
                    ? `api.php?route=/modules/${module.module_code}/renew`
                    : `api.php?route=/modules/${module.module_code}/toggle`;
                const method = this.subModal.isRenew ? 'POST' : 'PUT';
                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ months: Math.max(1, this.subModal.months || 1) })
                });
                const data = await res.json();
                if (data.success && data.data) {
                    if (data.data.new_balance !== undefined) this.creditBalance = parseFloat(data.data.new_balance);
                    const idx = this.modules.findIndex(m => m.module_code === module.module_code);
                    if (idx !== -1) {
                        if (data.data.is_active !== undefined) this.modules[idx].is_active = data.data.is_active;
                        if (data.data.next_renewal_at) this.modules[idx].next_renewal_at = data.data.next_renewal_at;
                    }
                    this.subModal.open = false;
                    this.notify(data.data.message || '<?= __('module.msg_activated') ?? 'Succès' ?>', 'success');
                    setTimeout(() => window.location.reload(), 500);
                } else {
                    this.subModal.error = data.message || '<?= __('module.msg_modify_error') ?? 'Erreur' ?>';
                }
            } catch (e) {
                this.subModal.error = '<?= __('module.msg_modify_error') ?? 'Erreur' ?>';
            } finally {
                this.subModal.loading = false;
                this.subscribing = null;
            }
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            return new Date(dateStr).toLocaleDateString('<?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'fr-FR' : 'en-US' ?>', {
                day: 'numeric', month: 'long', year: 'numeric'
            });
        },

        daysUntilExpiry(dateStr) {
            if (!dateStr) return 999;
            return Math.ceil((new Date(dateStr) - new Date()) / (1000 * 60 * 60 * 24));
        },

        isExpiringSoon(dateStr) {
            return this.daysUntilExpiry(dateStr) <= 7;
        },

        getModuleLink(code) {
            const links = {
                'loyalty': 'index.php?page=loyalty',
                'chat': 'index.php?page=chat',
                'pppoe': 'index.php?page=pppoe',
                'whatsapp': 'index.php?page=whatsapp',
                'telegram': 'index.php?page=telegram',
                'captive-portal': 'index.php?page=captive-portal',
                'sms': 'index.php?page=sms'
            };
            return links[code] || null;
        },

        notify(message, type = 'info') {
            if (typeof window.showToast === 'function') window.showToast(message, type);
            else if (typeof window.showNotification === 'function') window.showNotification(message, type);
            else alert(message);
        }
    };
}
</script>
