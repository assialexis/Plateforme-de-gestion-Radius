<?php
$userId = $_GET['id'] ?? null;
$isEdit = !empty($userId);
$pageTitle = $isEdit ? __('pppoe_user.title_edit') : __('pppoe_user.title_new');
$currentPage = 'pppoe';
?>

<div x-data="pppoeUserPage()" x-init="init()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-4 mb-4">
            <a href="index.php?page=pppoe" class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <?= __('pppoe_user.back_to_clients') ?>
            </a>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="isEdit ? __('pppoe_user.title_edit') : __('pppoe_user.title_new')"></h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1" x-text="isEdit ? __('pppoe_user.subtitle_edit') : __('pppoe_user.subtitle_new')"></p>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="flex justify-center py-12">
        <svg class="animate-spin h-10 w-10 text-primary-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    <!-- Main Form -->
    <div x-show="!loading" class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d]">
        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-[#30363d] px-6 pt-4">
            <nav class="flex gap-6">
                <button type="button" @click="activeTab = 'general'"
                        :class="activeTab === 'general' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <?= __('pppoe_user.tab_general') ?>
                </button>
                <button type="button" @click="activeTab = 'client'"
                        :class="activeTab === 'client' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    <?= __('pppoe_user.tab_client_info') ?>
                </button>
                <button type="button" @click="activeTab = 'location'"
                        :class="activeTab === 'location' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <?= __('pppoe_user.tab_location') ?>
                </button>
                <button type="button" @click="activeTab = 'network'"
                        :class="activeTab === 'network' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <?= __('pppoe_user.tab_network') ?>
                </button>
                <button type="button" @click="activeTab = 'fup'; loadFupStatus()" x-show="isEdit && hasFupProfile"
                        :class="activeTab === 'fup' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    FUP
                    <span x-show="fupStatus?.fup_triggered" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">!</span>
                </button>
                <button type="button" @click="activeTab = 'invoices'" x-show="isEdit"
                        :class="activeTab === 'invoices' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <?= __('pppoe_user.tab_invoices') ?>
                    <span x-show="unpaidInvoicesCount > 0" class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 rounded-full" x-text="unpaidInvoicesCount"></span>
                </button>
                <button type="button" @click="activeTab = 'stats'; loadTrafficStats()" x-show="isEdit"
                        :class="activeTab === 'stats' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <?= __('pppoe_user.tab_stats') ?>
                </button>
            </nav>
        </div>

        <form @submit.prevent="saveUser()" class="p-6">
            <!-- Tab: Général -->
            <div x-show="activeTab === 'general'" class="space-y-6">
                <!-- Credentials Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.username') ?></label>
                        <div class="flex gap-2">
                            <input type="text" x-model="form.username" required :disabled="isEdit"
                                   class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white disabled:opacity-50 focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <button type="button" @click="generateUsername()" :disabled="isEdit"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#3d4450] disabled:opacity-50 transition-colors"
                                    title="<?= __('pppoe_user.generate_username') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.password') ?></label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <input :type="showPassword ? 'text' : 'password'" x-model="form.password" :required="!isEdit"
                                       class="w-full px-4 py-2.5 pr-12 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <button type="button" @click="generatePassword()"
                                    class="px-4 py-2.5 bg-gray-100 dark:bg-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-[#3d4450] transition-colors"
                                    title="<?= __('pppoe_user.generate_password') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            </button>
                        </div>
                        <p x-show="isEdit && form.password && originalPassword && form.password !== originalPassword" class="mt-2 text-xs text-amber-600 dark:text-amber-400">
                            <?= __('pppoe_user.password_will_change') ?>
                        </p>
                    </div>
                </div>

                <!-- Profile & NAS Selection -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.profile') ?></label>
                        <select x-model="form.profile_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value=""><?= __('pppoe_user.select_profile') ?></option>
                            <template x-for="profile in profiles" :key="profile.id">
                                <option :value="profile.id" x-text="profile.name + ' - ' + formatPrice(profile.price)"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.router_nas') ?></label>
                        <select x-model="form.nas_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value=""><?= __('pppoe_user.all_routers') ?></option>
                            <template x-for="nas in nasList" :key="nas.id">
                                <option :value="nas.id" x-text="nas.shortname + (nas.zone_name ? ' (' + nas.zone_name + ')' : '')"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= __('pppoe_user.router_hint') ?></p>
                    </div>
                </div>

                <!-- Basic Info -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.customer_name') ?></label>
                        <input type="text" x-model="form.customer_name"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.customer_phone') ?></label>
                        <input type="text" x-model="form.customer_phone"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.notes') ?></label>
                    <textarea x-model="form.notes" rows="3"
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>
            </div>

            <!-- Tab: Infos Client -->
            <div x-show="activeTab === 'client'" x-cloak class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.customer_email') ?></label>
                        <input type="email" x-model="form.customer_email"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.secondary_phone') ?></label>
                        <input type="text" x-model="form.customer_secondary_phone"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.id_type') ?></label>
                        <select x-model="form.customer_id_type"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value=""><?= __('pppoe_user.id_select') ?></option>
                            <option value="CNI"><?= __('pppoe_user.id_cni') ?></option>
                            <option value="Passeport"><?= __('pppoe_user.id_passport') ?></option>
                            <option value="Permis"><?= __('pppoe_user.id_license') ?></option>
                            <option value="Autre"><?= __('pppoe_user.id_other') ?></option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.id_number') ?></label>
                        <input type="text" x-model="form.customer_id_number"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.full_address') ?></label>
                    <textarea x-model="form.customer_address" rows="3"
                              placeholder="Quartier, rue, numéro..."
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>

                <!-- Installation Section -->
                <div class="border-t border-gray-200 dark:border-[#30363d] pt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <?= __('pppoe_user.installation') ?>
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.installation_date') ?></label>
                            <input type="date" x-model="form.installation_date"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.technician') ?></label>
                            <input type="text" x-model="form.installation_tech"
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.equipment_serial') ?></label>
                        <input type="text" x-model="form.equipment_serial"
                               placeholder="ex: SN-123456789"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>
            </div>

            <!-- Tab: Localisation -->
            <div x-show="activeTab === 'location'" x-cloak class="space-y-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <div class="text-sm text-blue-700 dark:text-blue-300">
                            <p class="font-medium"><?= __('pppoe_user.gps_location') ?></p>
                            <p class="text-blue-600 dark:text-blue-400"><?= __('pppoe_user.gps_description') ?></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.latitude') ?></label>
                        <input type="text" x-model="form.latitude"
                               placeholder="ex: 6.3702928"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.longitude') ?></label>
                        <input type="text" x-model="form.longitude"
                               placeholder="ex: 2.3912362"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="getCurrentLocation()"
                            class="inline-flex items-center px-4 py-2.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <?= __('pppoe_user.my_position') ?>
                    </button>
                    <button type="button" @click="openGoogleMaps()"
                            x-show="form.latitude && form.longitude"
                            class="inline-flex items-center px-4 py-2.5 text-sm bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                        </svg>
                        <?= __('pppoe_user.view_google_maps') ?>
                    </button>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.location_description') ?></label>
                    <textarea x-model="form.location_description" rows="3"
                              placeholder="ex: Maison bleue à côté de l'école primaire..."
                              class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                </div>

                <!-- Mini Map Preview -->
                <div x-show="form.latitude && form.longitude" class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.position_preview') ?></label>
                    <div class="rounded-lg overflow-hidden border border-gray-200/60 dark:border-[#30363d]">
                        <iframe
                            :src="'https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=' + form.latitude + ',' + form.longitude + '&zoom=17'"
                            width="100%"
                            height="300"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>

            <!-- Tab: Réseau -->
            <div x-show="activeTab === 'network'" x-cloak class="space-y-6">
                <!-- Network Info (Read-only when editing) -->
                <div x-show="isEdit && editingUser" class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-5 space-y-4">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                        </svg>
                        <?= __('pppoe_user.current_network_info') ?>
                    </h4>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe_user.mac_address') ?></span>
                            <p class="font-mono text-gray-900 dark:text-white mt-1" x-text="editingUser?.last_mac || __('pppoe_user.not_detected')"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe_user.last_ip') ?></span>
                            <p class="font-mono text-gray-900 dark:text-white mt-1" x-text="editingUser?.last_ip || __('pppoe_user.not_known')"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe_user.data_consumed') ?></span>
                            <p class="font-semibold text-gray-900 dark:text-white mt-1" x-text="formatBytes(editingUser?.data_used || 0)"></p>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400"><?= __('pppoe_user.connection_time') ?></span>
                            <p class="font-semibold text-gray-900 dark:text-white mt-1" x-text="formatDuration(editingUser?.time_used || 0)"></p>
                        </div>
                    </div>

                    <!-- Data Usage Progress -->
                    <div x-show="editingUser?.profile_data_limit > 0" class="pt-3 border-t border-gray-200 dark:border-[#30363d]">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span x-text="__('pppoe_user.data_usage')"></span>
                            <span x-text="formatBytes(editingUser?.data_used || 0) + ' / ' + formatBytes(editingUser?.profile_data_limit || 0)"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-[#30363d] rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all"
                                 :class="(editingUser?.data_used / editingUser?.profile_data_limit * 100) > 90 ? 'bg-red-500' : (editingUser?.data_used / editingUser?.profile_data_limit * 100) > 70 ? 'bg-amber-500' : 'bg-green-500'"
                                 :style="'width: ' + Math.min(100, (editingUser?.data_used / editingUser?.profile_data_limit * 100)) + '%'"></div>
                        </div>
                    </div>
                </div>

                <!-- IP Mode Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.ip_assignment') ?></label>
                    <select x-model="form.ip_mode" @change="onIpModeChange()"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        <option value="router"><?= __('pppoe_user.ip_by_router') ?></option>
                        <option value="static"><?= __('pppoe_user.ip_static') ?></option>
                        <option value="pool"><?= __('pppoe_user.ip_dynamic_pool') ?></option>
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400" x-show="form.ip_mode === 'router'" x-text="__('pppoe_user.ip_router_hint')"></p>
                </div>

                <!-- Static IP Options -->
                <div x-show="form.ip_mode === 'static'" x-cloak class="space-y-4 p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Pool IP
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(optionnel - pour auto-complétion)</span>
                        </label>
                        <select x-model="form.static_pool_id" @change="onStaticPoolChange()"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value="">-- Saisie libre --</option>
                            <template x-for="pool in ipPools" :key="pool.id">
                                <option :value="pool.id" x-text="pool.name + ' (' + pool.available_ips + ' dispo)'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <?= __('pppoe_user.ip_address') ?> *
                        </label>
                        <div class="relative">
                            <input type="text" x-model="form.static_ip"
                                   @input="debounceCheckStaticIP()"
                                   placeholder="ex: 10.0.0.100 ou sélectionnez un pool"
                                   :class="staticIpStatus === 'invalid' ? 'border-red-500 focus:ring-red-500' : staticIpStatus === 'valid' ? 'border-green-500 focus:ring-green-500' : 'border-gray-300 dark:border-[#30363d]'"
                                   class="w-full px-4 py-2.5 border rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono pr-12 focus:ring-2 focus:border-transparent">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg x-show="checkingStaticIP" class="w-5 h-5 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <svg x-show="!checkingStaticIP && staticIpStatus === 'valid'" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg x-show="!checkingStaticIP && staticIpStatus === 'invalid'" class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <p x-show="staticIpStatus === 'valid' && staticIpMessage" class="mt-1 text-xs text-green-600 dark:text-green-400" x-text="staticIpMessage"></p>
                        <p x-show="staticIpStatus === 'invalid' && staticIpMessage" class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="staticIpMessage"></p>
                        <p x-show="!staticIpStatus && form.static_pool_id" class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="__('pppoe_user.ip_auto_assign_hint')">
                        </p>
                    </div>

                    <div x-show="form.static_pool_id" class="flex items-center gap-3">
                        <button type="button" @click="loadNextAvailableStaticIP()"
                                :disabled="loadingNextStaticIP"
                                class="inline-flex items-center px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                            <svg x-show="!loadingNextStaticIP" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="loadingNextStaticIP" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <?= __('pppoe_user.next_available_ip') ?>
                        </button>
                        <a href="index.php?page=network" class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                            <?= __('pppoe_user.manage_pools') ?>
                        </a>
                    </div>
                </div>

                <!-- Pool IP Options -->
                <div x-show="form.ip_mode === 'pool'" x-cloak class="space-y-4 p-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-lg">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.select_pool') ?></label>
                        <select x-model="form.pool_id" @change="loadPoolNextAvailableIP()"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                            <option value=""><?= __('pppoe_user.choose_pool') ?></option>
                            <template x-for="pool in ipPools" :key="pool.id">
                                <option :value="pool.id" x-text="pool.name + ' (' + (pool.network ? pool.network + '/' + pool.cidr : pool.start_ip + '-' + pool.end_ip) + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div x-show="form.pool_id">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <?= __('pppoe_user.assigned_ip') ?>
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(depuis le pool)</span>
                        </label>
                        <div class="relative">
                            <input type="text" x-model="form.pool_ip" readonly
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-gray-50 dark:bg-[#30363d] text-gray-900 dark:text-white font-mono"
                                   placeholder="IP sera assignée automatiquement">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="form.pool_ip">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg class="w-5 h-5 text-gray-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!form.pool_ip && form.pool_id && loadingPoolIP">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                        </div>
                        <p x-show="form.pool_ip" class="mt-1 text-xs text-green-600 dark:text-green-400" x-text="__('pppoe_user.ip_reserved')">
                        </p>
                        <p x-show="!form.pool_ip && !loadingPoolIP" class="mt-1 text-xs text-amber-600 dark:text-amber-400" x-text="__('pppoe_user.no_ip_available')">
                        </p>
                    </div>
                    <a href="index.php?page=network" class="inline-flex items-center text-xs text-primary-600 dark:text-primary-400 hover:underline">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <?= __('pppoe_user.manage_ip_pools') ?>
                    </a>
                </div>
            </div>

            <!-- Tab: FUP (Fair Usage Policy) -->
            <div x-show="activeTab === 'fup'" x-cloak class="space-y-6">
                <!-- FUP Info Banner -->
                <div class="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <div class="text-sm text-amber-700 dark:text-amber-300">
                            <p class="font-medium"><?= __('pppoe_user.fup_title') ?></p>
                            <p class="text-amber-600 dark:text-amber-400"><?= __('pppoe_user.fup_description') ?></p>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div x-show="loadingFup" class="flex justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- FUP Status -->
                <div x-show="!loadingFup && fupStatus" class="space-y-6">
                    <!-- Current Status Card -->
                    <div class="p-6 rounded-xl border-2" :class="fupStatus?.fup_triggered ? 'border-amber-400 bg-amber-50 dark:bg-amber-900/20' : 'border-green-400 bg-green-50 dark:bg-green-900/20'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-full" :class="fupStatus?.fup_triggered ? 'bg-amber-100 dark:bg-amber-900/40' : 'bg-green-100 dark:bg-green-900/40'">
                                    <svg class="w-8 h-8" :class="fupStatus?.fup_triggered ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="fupStatus?.fup_triggered" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        <path x-show="!fupStatus?.fup_triggered" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold" :class="fupStatus?.fup_triggered ? 'text-amber-800 dark:text-amber-200' : 'text-green-800 dark:text-green-200'" x-text="fupStatus?.fup_triggered ? __('pppoe_user.fup_triggered') : __('pppoe_user.fup_normal_speed')"></h3>
                                    <p class="text-sm" :class="fupStatus?.fup_triggered ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400'">
                                        <span x-show="fupStatus?.fup_triggered" x-text="__('pppoe_user.fup_reduced_since') + ' ' + formatDate(fupStatus?.fup_triggered_at)"></span>
                                        <span x-show="!fupStatus?.fup_triggered" x-text="__('pppoe_user.fup_client_normal_speed')"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('pppoe_user.fup_current_speed') ?></p>
                                <p class="text-lg font-bold" :class="fupStatus?.fup_triggered ? 'text-amber-700 dark:text-amber-300' : 'text-green-700 dark:text-green-300'" x-text="fupStatus?.effective_speed || '-'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Data Usage -->
                    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] p-6">
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <?= __('pppoe_user.fup_monthly_usage') ?>
                        </h4>

                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span class="text-gray-600 dark:text-gray-400" x-text="__('pppoe_user.fup_data_used')"></span>
                                    <span class="font-medium text-gray-900 dark:text-white" x-text="formatBytes(fupStatus?.fup_data_used || 0) + ' / ' + formatBytes(fupStatus?.fup_quota || 0)"></span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-[#21262d] rounded-full h-4">
                                    <div class="h-4 rounded-full transition-all flex items-center justify-end pr-2"
                                         :class="fupUsagePercent > 100 ? 'bg-red-500' : fupUsagePercent > 80 ? 'bg-amber-500' : 'bg-green-500'"
                                         :style="'width: ' + Math.min(100, fupUsagePercent) + '%'">
                                        <span class="text-xs font-medium text-white" x-show="fupUsagePercent > 15" x-text="fupUsagePercent.toFixed(1) + '%'"></span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="__('pppoe_user.fup_next_reset') + ': ' + formatDate(fupStatus?.fup_next_reset)"></p>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('pppoe_user.fup_monthly_quota') ?></p>
                                    <p class="font-semibold text-gray-900 dark:text-white" x-text="formatBytes(fupStatus?.fup_quota || 0)"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('pppoe_user.fup_normal_speed_label') ?></p>
                                    <p class="font-semibold text-gray-900 dark:text-white" x-text="fupStatus?.normal_speed || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('pppoe_user.fup_speed_label') ?></p>
                                    <p class="font-semibold text-amber-600 dark:text-amber-400" x-text="fupStatus?.fup_speed || '-'"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('pppoe_user.fup_last_reset') ?></p>
                                    <p class="font-semibold text-gray-900 dark:text-white" x-text="formatDate(fupStatus?.fup_last_reset) || '-'"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FUP Override -->
                    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <?= __('pppoe_user.fup_disable_override') ?>
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= __('pppoe_user.fup_override_hint') ?></p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" :checked="fupStatus?.fup_override" @change="toggleFupOverride()" class="sr-only peer" :disabled="togglingOverride">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-500/25 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-[#21262d] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-[#30363d] peer-checked:bg-primary-600"></div>
                            </label>
                        </div>
                        <div x-show="fupStatus?.fup_override" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= __('pppoe_user.fup_disabled_info') ?>
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap gap-3">
                        <button type="button" @click="resetFup()" :disabled="resettingFup || !fupStatus?.fup_triggered"
                                class="inline-flex items-center px-4 py-2.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg x-show="!resettingFup" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <svg x-show="resettingFup" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <?= __('pppoe_user.fup_reset') ?>
                        </button>
                        <button type="button" @click="loadFupStatus()"
                                class="inline-flex items-center px-4 py-2.5 text-sm bg-gray-200 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-[#30363d] transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <?= __('common.refresh') ?>
                        </button>
                    </div>

                    <!-- FUP Logs -->
                    <div x-show="fupLogs.length > 0" class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d]">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?= __('pppoe_user.fup_history') ?>
                            </h4>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-[#30363d] max-h-64 overflow-y-auto">
                            <template x-for="log in fupLogs" :key="log.id">
                                <div class="px-6 py-3 flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                              :class="log.action === 'triggered' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'"
                                              x-text="log.action === 'triggered' ? __('pppoe_user.fup_log_triggered') : log.action === 'reset' ? __('pppoe_user.fup_log_reset') : log.action"></span>
                                        <span class="text-gray-600 dark:text-gray-400" x-text="log.details || '-'"></span>
                                    </div>
                                    <span class="text-gray-500 dark:text-gray-400" x-text="formatDateTime(log.created_at)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab: Factures -->
            <div x-show="activeTab === 'invoices'" x-cloak class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-500/10 rounded-lg">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-blue-600 dark:text-blue-400"><?= __('pppoe_user.total_invoices') ?></p>
                                <p class="text-xl font-bold text-blue-700 dark:text-blue-300" x-text="invoices.length"></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-red-500/10 rounded-lg">
                                <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-red-600 dark:text-red-400"><?= __('pppoe_user.unpaid') ?></p>
                                <p class="text-xl font-bold text-red-700 dark:text-red-300" x-text="formatPrice(totalUnpaid)"></p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-green-500/10 rounded-lg">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-green-600 dark:text-green-400"><?= __('pppoe_user.paid') ?></p>
                                <p class="text-xl font-bold text-green-700 dark:text-green-300" x-text="formatPrice(totalPaid)"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div x-show="loadingInvoices" class="flex justify-center py-8">
                    <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <!-- Empty State -->
                <div x-show="!loadingInvoices && invoices.length === 0" class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"><?= __('pppoe_user.no_invoices') ?></h3>
                    <p class="text-gray-500 dark:text-gray-400"><?= __('pppoe_user.no_invoices_desc') ?></p>
                </div>

                <!-- Invoices List -->
                <div x-show="!loadingInvoices && invoices.length > 0" class="space-y-3">
                    <template x-for="invoice in invoices" :key="invoice.id">
                        <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4 border border-gray-200 dark:border-[#30363d]">
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <div class="flex items-center gap-4">
                                    <div class="p-2 rounded-lg" :class="invoice.status === 'paid' ? 'bg-green-100 dark:bg-green-900/30' : invoice.status === 'overdue' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-amber-100 dark:bg-amber-900/30'">
                                        <svg class="w-5 h-5" :class="invoice.status === 'paid' ? 'text-green-600 dark:text-green-400' : invoice.status === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path x-show="invoice.status === 'paid'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            <path x-show="invoice.status !== 'paid'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-white" x-text="'#' + invoice.invoice_number"></span>
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                                  :class="invoice.status === 'paid' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : invoice.status === 'overdue' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400'"
                                                  x-text="invoice.status === 'paid' ? __('pppoe_user.invoice_paid') : invoice.status === 'overdue' ? __('pppoe_user.invoice_overdue') : __('pppoe_user.invoice_pending')"></span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            <span x-text="invoice.description || 'Abonnement mensuel'"></span>
                                            <span class="mx-1">•</span>
                                            <span x-text="formatDate(invoice.invoice_date)"></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <div class="text-right">
                                        <p class="font-semibold text-gray-900 dark:text-white" x-text="formatPrice(invoice.total_amount)"></p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400" x-show="invoice.status !== 'paid' && invoice.paid_amount > 0">
                                            <span x-text="__('pppoe_user.remaining') + ': ' + formatPrice(invoice.total_amount - invoice.paid_amount)"></span>
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-show="invoice.status !== 'paid'">
                                            <span x-text="__('pppoe_user.due_date') + ': ' + formatDate(invoice.due_date)"></span>
                                        </p>
                                        <p class="text-xs text-green-600 dark:text-green-400" x-show="invoice.status === 'paid' && invoice.paid_date">
                                            <span x-text="__('pppoe_user.paid_on') + ' ' + formatDate(invoice.paid_date)"></span>
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="openPaymentModal(invoice)" x-show="invoice.status !== 'paid'"
                                                class="inline-flex items-center px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                            <?= __('pppoe_user.settle') ?>
                                        </button>
                                        <button type="button" @click="viewInvoice(invoice)"
                                                class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-200 dark:bg-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-[#3d4450] transition-colors"
                                                title="Voir la facture">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Tab: Statistiques -->
            <div x-show="activeTab === 'stats'" x-cloak class="space-y-6">
                <!-- Period Filter -->
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        <?= __('pppoe_user.stats_traffic_title') ?>
                    </h3>
                    <div class="flex bg-gray-100 dark:bg-[#21262d] rounded-lg p-0.5">
                        <template x-for="d in [7, 30, 90]" :key="d">
                            <button type="button" @click="trafficDays = d; loadTrafficStats()"
                                :class="trafficDays === d ? 'bg-white dark:bg-[#30363d] shadow-sm text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                                class="px-3 py-1.5 text-xs font-medium rounded-md transition-all"
                                x-text="d + 'j'"></button>
                        </template>
                    </div>
                </div>

                <!-- Loading -->
                <div x-show="loadingTraffic" class="flex justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-primary-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>

                <div x-show="!loadingTraffic && trafficStats">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                                </svg>
                                <span class="text-xs font-medium text-green-700 dark:text-green-400"><?= __('pppoe_user.stats_upload') ?></span>
                            </div>
                            <p class="text-lg font-bold text-green-800 dark:text-green-300" x-text="formatBytes(trafficStats?.summary?.total_upload || 0)"></p>
                        </div>
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                                </svg>
                                <span class="text-xs font-medium text-blue-700 dark:text-blue-400"><?= __('pppoe_user.stats_download') ?></span>
                            </div>
                            <p class="text-lg font-bold text-blue-800 dark:text-blue-300" x-text="formatBytes(trafficStats?.summary?.total_download || 0)"></p>
                        </div>
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                <span class="text-xs font-medium text-purple-700 dark:text-purple-400"><?= __('pppoe_user.stats_total') ?></span>
                            </div>
                            <p class="text-lg font-bold text-purple-800 dark:text-purple-300" x-text="formatBytes(trafficStats?.summary?.total || 0)"></p>
                        </div>
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium text-amber-700 dark:text-amber-400"><?= __('pppoe_user.stats_time') ?></span>
                            </div>
                            <p class="text-lg font-bold text-amber-800 dark:text-amber-300" x-text="formatDuration(trafficStats?.summary?.total_time || 0)"></p>
                        </div>
                    </div>

                    <!-- Chart -->
                    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                <?= __('pppoe_user.stats_daily_chart') ?>
                            </h4>
                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-green-500 inline-block"></span> <?= __('pppoe_user.stats_upload') ?></span>
                                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-blue-500 inline-block"></span> <?= __('pppoe_user.stats_download') ?></span>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas x-ref="trafficChartCanvas"></canvas>
                        </div>
                    </div>

                    <!-- All-time stats + Reset button -->
                    <div class="mt-4 flex items-center justify-between bg-gray-50 dark:bg-[#21262d] rounded-lg px-4 py-3">
                        <div class="flex items-center gap-6 text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-medium text-gray-700 dark:text-gray-300"><?= __('pppoe_user.stats_all_time') ?> :</span>
                            <span><?= __('pppoe_user.stats_total_data') ?> <strong class="text-gray-900 dark:text-white" x-text="formatBytes(trafficStats?.summary?.all_time_data || 0)"></strong></span>
                            <span><?= __('pppoe_user.stats_total_time') ?> <strong class="text-gray-900 dark:text-white" x-text="formatDuration(trafficStats?.summary?.all_time_time || 0)"></strong></span>
                            <span><?= __('pppoe_user.stats_sessions') ?> <strong class="text-gray-900 dark:text-white" x-text="trafficStats?.summary?.sessions_count || 0"></strong></span>
                        </div>
                        <button type="button" @click="resetTrafficData()"
                            :disabled="resettingTraffic"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors disabled:opacity-50">
                            <svg x-show="!resettingTraffic" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            <svg x-show="resettingTraffic" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <?= __('pppoe_user.stats_reset') ?>
                        </button>
                    </div>
                </div>

                <!-- No data -->
                <div x-show="!loadingTraffic && (!trafficStats || !trafficStats.daily || trafficStats.daily.length === 0)" class="text-center py-12 text-gray-500 dark:text-gray-400">
                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <p class="text-sm"><?= __('pppoe_user.stats_no_data') ?></p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200 dark:border-[#30363d]">
                <a href="index.php?page=pppoe"
                   class="px-6 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors">
                    <?= __('common.cancel') ?>
                </a>
                <button type="submit" :disabled="saving"
                        class="inline-flex items-center px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 transition-colors">
                    <svg x-show="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="isEdit ? __('pppoe_user.save_changes') : __('pppoe_user.create_client')"></span>
                </button>
            </div>
        </form>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @keydown.escape.window="showPaymentModal = false">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500/75 dark:bg-[#0d1117]/80 transition-opacity" @click="showPaymentModal = false"></div>

            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-xl transform transition-all sm:max-w-lg sm:w-full">
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <?= __('pppoe_user.record_payment') ?>
                        </h3>
                        <button @click="showPaymentModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Invoice Info -->
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4 mb-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('pppoe_user.invoice') ?></p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="'#' + (selectedInvoice?.invoice_number || '')"></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('pppoe_user.amount_due') ?></p>
                                <p class="font-bold text-lg text-gray-900 dark:text-white" x-text="formatPrice((selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0))"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.amount_paid') ?> *</label>
                            <div class="relative">
                                <input type="number" x-model="paymentForm.amount" required min="0" :max="(selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0)"
                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 text-sm">FCFA</span>
                            </div>
                            <button type="button" @click="paymentForm.amount = (selectedInvoice?.total_amount || 0) - (selectedInvoice?.paid_amount || 0)" class="mt-1 text-xs text-primary-600 hover:underline">
                                <?= __('pppoe_user.pay_full_amount') ?>
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.payment_method') ?> *</label>
                            <select x-model="paymentForm.payment_method" required
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                                <option value="cash"><?= __('pppoe_user.pay_cash') ?></option>
                                <option value="mobile_money"><?= __('pppoe_user.pay_mobile_money') ?></option>
                                <option value="bank_transfer"><?= __('pppoe_user.pay_bank_transfer') ?></option>
                                <option value="card"><?= __('pppoe_user.pay_card') ?></option>
                                <option value="other"><?= __('pppoe_user.pay_other') ?></option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.reference') ?></label>
                            <input type="text" x-model="paymentForm.reference"
                                   placeholder="Numéro de transaction, reçu..."
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('pppoe_user.notes_optional') ?></label>
                            <textarea x-model="paymentForm.notes" rows="2"
                                      class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent"></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-[#21262d]/50 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" @click="showPaymentModal = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d] rounded-lg transition-colors">
                        <?= __('common.cancel') ?>
                    </button>
                    <button type="button" @click="processPayment()" :disabled="processingPayment || !paymentForm.amount"
                            class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 transition-colors">
                        <svg x-show="processingPayment" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <?= __('pppoe_user.confirm_payment') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pppoeUserPage() {
    return {
        isEdit: <?= $isEdit ? 'true' : 'false' ?>,
        userId: <?= $userId ? json_encode($userId) : 'null' ?>,
        loading: true,
        saving: false,
        activeTab: 'general',
        showPassword: false,
        profiles: [],
        ipPools: [],
        editingUser: null,
        originalPassword: '',

        // Invoices data
        invoices: [],
        loadingInvoices: false,
        unpaidInvoicesCount: 0,
        totalUnpaid: 0,
        totalPaid: 0,

        // Payment modal
        showPaymentModal: false,
        selectedInvoice: null,
        processingPayment: false,
        paymentForm: {
            amount: 0,
            payment_method: 'cash',
            reference: '',
            notes: ''
        },

        // NAS list
        nasList: [],

        // Traffic stats
        trafficStats: null,
        trafficChart: null,
        trafficDays: 30,
        loadingTraffic: false,
        resettingTraffic: false,

        // FUP data
        hasFupProfile: false,
        fupStatus: null,
        fupLogs: [],
        loadingFup: false,
        resettingFup: false,
        togglingOverride: false,

        // Form data
        form: {
            username: '',
            password: '',
            profile_id: '',
            nas_id: '',
            customer_name: '',
            customer_phone: '',
            customer_secondary_phone: '',
            customer_email: '',
            customer_id_type: '',
            customer_id_number: '',
            customer_address: '',
            latitude: '',
            longitude: '',
            location_description: '',
            installation_date: '',
            installation_tech: '',
            equipment_serial: '',
            ip_mode: 'router',
            static_ip: '',
            static_pool_id: '',
            pool_id: '',
            pool_ip: '',
            notes: ''
        },

        // IP validation
        loadingPoolIP: false,
        staticIpStatus: '',
        staticIpMessage: '',
        checkingStaticIP: false,
        loadingNextStaticIP: false,
        staticIpCheckTimeout: null,

        async init() {
            await Promise.all([
                this.loadProfiles(),
                this.loadIPPools(),
                this.loadNasList()
            ]);

            if (this.isEdit && this.userId) {
                await this.loadUser();
                this.loadInvoices();
                this.loadFupStatus();
            }

            this.loading = false;
        },

        async loadProfiles() {
            try {
                const response = await API.get('/pppoe/profiles');
                this.profiles = response.data || [];
            } catch (error) {
                console.error('Error loading profiles:', error);
            }
        },

        async loadIPPools() {
            try {
                const response = await API.get('/network/pools');
                this.ipPools = (response.data || []).filter(p => p.is_active);
            } catch (error) {
                console.error('Error loading IP pools:', error);
            }
        },

        async loadNasList() {
            try {
                const response = await API.get('/nas');
                this.nasList = response.data || [];
            } catch (error) {
                console.error('Error loading NAS list:', error);
            }
        },

        async loadUser() {
            try {
                const response = await API.get(`/pppoe/users/${this.userId}`);
                if (response.success && response.data) {
                    const user = response.data;
                    this.editingUser = user;
                    this.originalPassword = user.password || '';

                    // Determine IP mode
                    let ipMode = user.ip_mode || 'router';
                    if (!user.ip_mode) {
                        if (user.pool_id && user.pool_ip) {
                            ipMode = 'pool';
                        } else if (user.static_ip) {
                            ipMode = 'static';
                        }
                    }

                    this.form = {
                        username: user.username,
                        password: user.password || '',
                        profile_id: user.profile_id,
                        nas_id: user.nas_id || '',
                        customer_name: user.customer_name || '',
                        customer_phone: user.customer_phone || '',
                        customer_secondary_phone: user.customer_secondary_phone || '',
                        customer_email: user.customer_email || '',
                        customer_id_type: user.customer_id_type || '',
                        customer_id_number: user.customer_id_number || '',
                        customer_address: user.customer_address || '',
                        latitude: user.latitude || '',
                        longitude: user.longitude || '',
                        location_description: user.location_description || '',
                        installation_date: user.installation_date || '',
                        installation_tech: user.installation_tech || '',
                        equipment_serial: user.equipment_serial || '',
                        ip_mode: ipMode,
                        static_ip: user.static_ip || '',
                        static_pool_id: (ipMode === 'static' && user.pool_id) ? String(user.pool_id) : '',
                        pool_id: (ipMode === 'pool' && user.pool_id) ? String(user.pool_id) : '',
                        pool_ip: user.pool_ip || '',
                        notes: user.notes || ''
                    };

                    // Validate existing IP
                    if (ipMode === 'pool' && user.pool_id) {
                        this.loadPoolNextAvailableIP();
                    }
                    if (ipMode === 'static' && user.static_ip) {
                        this.staticIpStatus = 'valid';
                        this.staticIpMessage = __('pppoe_user.ip_current_client');
                    }
                } else {
                    showToast(__('pppoe_user.msg_not_found'), 'error');
                    window.location.href = 'index.php?page=pppoe';
                }
            } catch (error) {
                console.error('Error loading user:', error);
                showToast(__('pppoe_user.msg_load_error'), 'error');
            }
        },

        onIpModeChange() {
            this.form.static_ip = '';
            this.form.static_pool_id = '';
            this.form.pool_id = '';
            this.form.pool_ip = '';
            this.loadingPoolIP = false;
            this.staticIpStatus = '';
            this.staticIpMessage = '';
        },

        onStaticPoolChange() {
            this.staticIpStatus = '';
            this.staticIpMessage = '';
            this.form.static_ip = '';
        },

        async loadNextAvailableStaticIP() {
            if (!this.form.static_pool_id) {
                showToast(__('pppoe_user.msg_select_pool_first'), 'warning');
                return;
            }

            this.loadingNextStaticIP = true;
            try {
                const response = await API.get(`/network/pools/${this.form.static_pool_id}/next-available`);
                if (response.success && response.data) {
                    this.form.static_ip = response.data.ip;
                    this.staticIpStatus = 'valid';
                    this.staticIpMessage = __('pppoe_user.ip_available');
                } else {
                    showToast(__('pppoe_user.msg_no_ip_in_pool'), 'warning');
                }
            } catch (error) {
                console.error('Error loading next available static IP:', error);
                showToast(__('pppoe_user.msg_ip_fetch_error'), 'error');
            } finally {
                this.loadingNextStaticIP = false;
            }
        },

        async loadPoolNextAvailableIP() {
            if (!this.form.pool_id) {
                this.form.pool_ip = '';
                this.loadingPoolIP = false;
                return;
            }

            if (this.isEdit && this.editingUser && this.editingUser.pool_id == this.form.pool_id && this.editingUser.pool_ip) {
                this.form.pool_ip = this.editingUser.pool_ip;
                this.loadingPoolIP = false;
                return;
            }

            this.loadingPoolIP = true;
            try {
                if (this.isEdit && this.editingUser && this.editingUser.id) {
                    const response = await API.get(`/network/pools/${this.form.pool_id}/reserved-for/${this.editingUser.id}`);
                    if (response.success && response.data) {
                        this.form.pool_ip = response.data.ip;
                        return;
                    }
                }

                const response = await API.get(`/network/pools/${this.form.pool_id}/next-available`);
                if (response.success && response.data) {
                    this.form.pool_ip = response.data.ip;
                } else {
                    this.form.pool_ip = '';
                    showToast(__('pppoe_user.msg_no_ip_in_pool'), 'warning');
                }
            } catch (error) {
                console.error('Error loading pool next available IP:', error);
                this.form.pool_ip = '';
            } finally {
                this.loadingPoolIP = false;
            }
        },

        debounceCheckStaticIP() {
            if (this.staticIpCheckTimeout) {
                clearTimeout(this.staticIpCheckTimeout);
            }

            if (!this.form.static_ip) {
                this.staticIpStatus = '';
                this.staticIpMessage = '';
                return;
            }

            const ipPattern = /^(\d{1,3}\.){3}\d{1,3}$/;
            if (!ipPattern.test(this.form.static_ip)) {
                this.staticIpStatus = '';
                this.staticIpMessage = '';
                return;
            }

            this.staticIpStatus = 'checking';
            this.staticIpMessage = __('pppoe_user.ip_checking');

            this.staticIpCheckTimeout = setTimeout(() => {
                this.checkStaticIPAvailability();
            }, 500);
        },

        async checkStaticIPAvailability() {
            if (!this.form.static_ip) return;

            if (this.isEdit && this.editingUser && this.editingUser.static_ip === this.form.static_ip) {
                this.staticIpStatus = 'valid';
                this.staticIpMessage = __('pppoe_user.ip_current_client');
                return;
            }

            this.checkingStaticIP = true;
            try {
                const response = await API.get(`/network/ips/check/${encodeURIComponent(this.form.static_ip)}`);
                if (response.success) {
                    if (response.data.available) {
                        this.staticIpStatus = 'valid';
                        this.staticIpMessage = response.data.message || __('pppoe_user.ip_available');
                    } else {
                        this.staticIpStatus = 'invalid';
                        this.staticIpMessage = response.data.message || __('pppoe_user.ip_already_used');
                    }
                }
            } catch (error) {
                console.error('Error checking IP availability:', error);
                this.staticIpStatus = 'invalid';
                this.staticIpMessage = __('pppoe_user.ip_check_error');
            } finally {
                this.checkingStaticIP = false;
            }
        },

        async saveUser() {
            try {
                // Validate static IP
                if (this.form.ip_mode === 'static' && this.form.static_ip) {
                    if (this.staticIpStatus === 'invalid') {
                        showToast(__('pppoe_user.msg_ip_already_used'), 'error');
                        return;
                    }
                    if (this.staticIpStatus !== 'valid' && !(this.isEdit && this.editingUser && this.editingUser.static_ip === this.form.static_ip)) {
                        showToast(__('pppoe_user.msg_check_ip_first'), 'warning');
                        return;
                    }
                }

                this.saving = true;

                const data = {
                    username: this.form.username,
                    password: this.form.password,
                    profile_id: this.form.profile_id,
                    nas_id: this.form.nas_id || null,
                    customer_name: this.form.customer_name,
                    customer_phone: this.form.customer_phone,
                    customer_secondary_phone: this.form.customer_secondary_phone,
                    customer_email: this.form.customer_email,
                    customer_id_type: this.form.customer_id_type,
                    customer_id_number: this.form.customer_id_number,
                    customer_address: this.form.customer_address,
                    latitude: this.form.latitude || null,
                    longitude: this.form.longitude || null,
                    location_description: this.form.location_description,
                    installation_date: this.form.installation_date || null,
                    installation_tech: this.form.installation_tech,
                    equipment_serial: this.form.equipment_serial,
                    notes: this.form.notes,
                    ip_mode: this.form.ip_mode
                };

                // Add IP data based on mode
                if (this.form.ip_mode === 'static') {
                    data.static_ip = this.form.static_ip;
                    data.pool_id = this.form.static_pool_id || null;
                    data.pool_ip = null;
                } else if (this.form.ip_mode === 'pool') {
                    data.pool_id = this.form.pool_id;
                    data.pool_ip = this.form.pool_ip;
                    data.static_ip = null;
                } else {
                    data.static_ip = null;
                    data.pool_id = null;
                    data.pool_ip = null;
                }

                let response;
                if (this.isEdit) {
                    response = await API.put(`/pppoe/users/${this.userId}`, data);
                } else {
                    response = await API.post('/pppoe/users', data);
                }

                if (response.success) {
                    showToast(this.isEdit ? __('pppoe_user.msg_updated') : __('pppoe_user.msg_created'), 'success');
                    window.location.href = 'index.php?page=pppoe';
                } else {
                    showToast(response.message || __('pppoe_user.msg_save_error'), 'error');
                }
            } catch (error) {
                console.error('Error saving user:', error);
                showToast(__('pppoe_user.msg_save_error'), 'error');
            } finally {
                this.saving = false;
            }
        },

        generateUsername() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = 'PPP';
            for (let i = 0; i < 6; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.form.username = result;
        },

        generatePassword() {
            const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let result = '';
            for (let i = 0; i < 10; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            this.form.password = result;
            this.showPassword = true;
        },

        getCurrentLocation() {
            if (!navigator.geolocation) {
                showToast(__('pppoe_user.msg_geo_not_supported'), 'error');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.form.latitude = position.coords.latitude.toFixed(8);
                    this.form.longitude = position.coords.longitude.toFixed(8);
                    showToast(__('pppoe_user.msg_position_retrieved'), 'success');
                },
                (error) => {
                    let message = __('pppoe_user.msg_geo_error');
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            message = __('pppoe_user.msg_geo_denied');
                            break;
                        case error.POSITION_UNAVAILABLE:
                            message = __('pppoe_user.msg_geo_unavailable');
                            break;
                        case error.TIMEOUT:
                            message = __('pppoe_user.msg_geo_timeout');
                            break;
                    }
                    showToast(message, 'error');
                }
            );
        },

        openGoogleMaps() {
            if (this.form.latitude && this.form.longitude) {
                window.open(`https://www.google.com/maps?q=${this.form.latitude},${this.form.longitude}`, '_blank');
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('fr-FR').format(price) + ' FCFA';
        },

        formatBytes(bytes) {
            bytes = Number(bytes) || 0;
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        formatDuration(seconds) {
            seconds = Number(seconds) || 0;
            if (seconds === 0) return '0s';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            if (days > 0) return days + 'j ' + hours + 'h';
            if (hours > 0) return hours + 'h ' + minutes + 'm';
            return minutes + 'm';
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        },

        // Invoice functions
        async loadTrafficStats() {
            if (!this.userId) return;
            this.loadingTraffic = true;
            try {
                const response = await API.get(`/pppoe/users/${this.userId}/traffic-stats?days=${this.trafficDays}`);
                if (response.success) {
                    this.trafficStats = response.data;
                    this.$nextTick(() => this.renderTrafficChart());
                }
            } catch (error) {
                console.error('Error loading traffic stats:', error);
            } finally {
                this.loadingTraffic = false;
            }
        },

        async resetTrafficData() {
            if (!this.userId) return;
            if (!confirm(__('pppoe_user.stats_reset_confirm'))) return;
            this.resettingTraffic = true;
            try {
                const response = await API.post(`/pppoe/users/${this.userId}/reset-traffic`);
                if (response.success) {
                    showToast(response.data?.message || __('pppoe_user.stats_reset_success'), 'success');
                    await this.loadTrafficStats();
                } else {
                    showToast(response.error || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('common.error'), 'error');
            } finally {
                this.resettingTraffic = false;
            }
        },

        renderTrafficChart() {
            const canvas = this.$refs.trafficChartCanvas;
            if (!canvas || !this.trafficStats?.daily?.length) return;

            if (this.trafficChart) {
                this.trafficChart.destroy();
            }

            const isDark = document.documentElement.classList.contains('dark');
            const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)';
            const tickColor = isDark ? '#8b949e' : '#9ca3af';

            const labels = this.trafficStats.daily.map(d => d.label);
            const uploadData = this.trafficStats.daily.map(d => Number(d.upload));
            const downloadData = this.trafficStats.daily.map(d => Number(d.download));

            this.trafficChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [
                        {
                            label: __('pppoe_user.stats_upload'),
                            data: uploadData,
                            backgroundColor: isDark ? 'rgba(34,197,94,0.7)' : 'rgba(34,197,94,0.8)',
                            borderRadius: 3,
                            borderSkipped: false,
                        },
                        {
                            label: __('pppoe_user.stats_download'),
                            data: downloadData,
                            backgroundColor: isDark ? 'rgba(59,130,246,0.7)' : 'rgba(59,130,246,0.8)',
                            borderRadius: 3,
                            borderSkipped: false,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: isDark ? '#161b22' : '#fff',
                            titleColor: isDark ? '#f0f6fc' : '#111827',
                            bodyColor: isDark ? '#8b949e' : '#6b7280',
                            borderColor: isDark ? '#30363d' : '#f3f4f6',
                            borderWidth: 1,
                            padding: 10,
                            callbacks: {
                                label: (ctx) => {
                                    return ctx.dataset.label + ': ' + this.formatBytes(ctx.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            stacked: true,
                            grid: { display: false },
                            ticks: { color: tickColor, font: { size: 11 } }
                        },
                        y: {
                            stacked: true,
                            grid: { color: gridColor },
                            ticks: {
                                color: tickColor,
                                font: { size: 11 },
                                callback: (v) => this.formatBytes(v)
                            }
                        }
                    }
                }
            });
        },

        async loadInvoices() {
            if (!this.userId) return;

            this.loadingInvoices = true;
            try {
                const response = await API.get(`/billing/users/${this.userId}/summary`);
                if (response.success && response.data) {
                    this.invoices = response.data.invoices || [];
                    this.totalUnpaid = response.data.total_unpaid || 0;
                    this.totalPaid = response.data.total_paid || 0;
                    this.unpaidInvoicesCount = this.invoices.filter(inv => inv.status !== 'paid').length;
                }
            } catch (error) {
                console.error('Error loading invoices:', error);
                this.invoices = [];
            } finally {
                this.loadingInvoices = false;
            }
        },

        openPaymentModal(invoice) {
            this.selectedInvoice = invoice;
            const remainingAmount = parseFloat(invoice.total_amount) - parseFloat(invoice.paid_amount || 0);
            this.paymentForm = {
                amount: remainingAmount,
                payment_method: 'cash',
                reference: '',
                notes: ''
            };
            this.showPaymentModal = true;
        },

        async processPayment() {
            if (!this.selectedInvoice || !this.paymentForm.amount) return;

            this.processingPayment = true;
            try {
                const response = await API.post(`/billing/invoices/${this.selectedInvoice.id}/pay`, {
                    amount: this.paymentForm.amount,
                    payment_method: this.paymentForm.payment_method,
                    reference: this.paymentForm.reference,
                    notes: this.paymentForm.notes
                });

                if (response.success) {
                    showToast(__('pppoe_user.msg_payment_success'), 'success');
                    this.showPaymentModal = false;
                    await this.loadInvoices();
                } else {
                    showToast(response.message || __('pppoe_user.msg_payment_error'), 'error');
                }
            } catch (error) {
                console.error('Error processing payment:', error);
                showToast(__('pppoe_user.msg_payment_error'), 'error');
            } finally {
                this.processingPayment = false;
            }
        },

        viewInvoice(invoice) {
            // Open invoice in new tab or modal
            window.open(`index.php?page=billing&invoice=${invoice.id}`, '_blank');
        },

        // FUP computed property
        get fupUsagePercent() {
            if (!this.fupStatus?.fup_quota || this.fupStatus.fup_quota === 0) return 0;
            return (this.fupStatus.fup_data_used / this.fupStatus.fup_quota) * 100;
        },

        // FUP Methods
        async loadFupStatus() {
            if (!this.userId) return;

            this.loadingFup = true;
            try {
                const response = await API.get(`/pppoe/users/${this.userId}/fup`);
                if (response.success && response.data) {
                    this.fupStatus = response.data;
                    this.hasFupProfile = response.data.fup_enabled;
                }

                // Also load FUP logs
                const logsResponse = await API.get(`/pppoe/users/${this.userId}/fup/logs`);
                if (logsResponse.success) {
                    this.fupLogs = logsResponse.data || [];
                }
            } catch (error) {
                console.error('Error loading FUP status:', error);
            } finally {
                this.loadingFup = false;
            }
        },

        async resetFup() {
            if (!this.userId || !this.fupStatus?.fup_triggered) return;

            if (!confirm(__('pppoe_user.msg_confirm_fup_reset'))) return;

            this.resettingFup = true;
            try {
                const response = await API.post(`/pppoe/users/${this.userId}/fup/reset`);
                if (response.success) {
                    showToast(__('pppoe_user.msg_fup_reset_success'), 'success');
                    await this.loadFupStatus();
                } else {
                    showToast(response.message || __('pppoe_user.msg_fup_reset_error'), 'error');
                }
            } catch (error) {
                console.error('Error resetting FUP:', error);
                showToast(__('pppoe_user.msg_fup_reset_error'), 'error');
            } finally {
                this.resettingFup = false;
            }
        },

        async toggleFupOverride() {
            if (!this.userId) return;

            this.togglingOverride = true;
            try {
                const response = await API.post(`/pppoe/users/${this.userId}/fup/toggle-override`);
                if (response.success) {
                    showToast(response.data?.fup_override ? __('pppoe_user.msg_fup_disabled') : __('pppoe_user.msg_fup_enabled'), 'success');
                    await this.loadFupStatus();
                } else {
                    showToast(response.message || __('notify.error'), 'error');
                }
            } catch (error) {
                console.error('Error toggling FUP override:', error);
                showToast(__('notify.error'), 'error');
            } finally {
                this.togglingOverride = false;
            }
        },

        formatDateTime(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleString('fr-FR', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        // Check if selected profile has FUP
        checkProfileFup() {
            if (this.form.profile_id) {
                const profile = this.profiles.find(p => p.id == this.form.profile_id);
                this.hasFupProfile = profile?.fup_enabled == 1;
            } else {
                this.hasFupProfile = false;
            }
        }
    };
}
</script>
