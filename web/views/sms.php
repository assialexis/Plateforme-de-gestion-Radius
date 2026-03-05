<?php $pageTitle = __('sms.title'); $currentPage = 'sms'; ?>

<div x-data="smsPage()">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <?= __('sms.title') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('sms.subtitle') ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="-mb-px flex gap-6 overflow-x-auto">
            <button @click="activeTab = 'gateways'"
                :class="activeTab === 'gateways' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('sms.tab_gateways') ?>
            </button>
            <button @click="activeTab = 'templates'; if(!templatesLoaded) loadTemplates()"
                :class="activeTab === 'templates' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('sms.tab_templates') ?>
            </button>
            <button @click="activeTab = 'history'; if(!historyLoaded) loadHistory()"
                :class="activeTab === 'history' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('sms.tab_history') ?>
            </button>
        </nav>
    </div>

    <!-- Tab: Gateways -->
    <div x-show="activeTab === 'gateways'" x-cloak>
        <!-- Loading -->
        <div x-show="loading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- Gateway Cards Grid -->
        <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <template x-for="gw in gateways" :key="gw.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                    <!-- Card Header -->
                    <div class="p-5 border-b border-gray-100 dark:border-[#30363d]">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center"
                                    :class="gw.is_active ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-100 dark:bg-[#21262d]'">
                                    <svg class="w-6 h-6" :class="gw.is_active ? 'text-blue-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white" x-text="gw.provider_name || gw.name"></h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="gw.description"></p>
                                </div>
                            </div>
                            <!-- Status Badge -->
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full"
                                :class="gw.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-600 dark:bg-[#21262d] dark:text-gray-400'"
                                x-text="gw.is_active ? '<?= __js('sms.active') ?>' : '<?= __js('sms.inactive') ?>'">
                            </span>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-5">
                        <!-- Balance display -->
                        <div class="flex items-center justify-between mb-4" x-show="gw.supports_balance">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider"><?= __('sms.balance') ?></p>
                                <p class="text-lg font-bold text-gray-900 dark:text-white">
                                    <span x-text="gw.balance !== null ? gw.balance : '--'"></span>
                                    <span class="text-sm font-normal text-gray-500" x-show="gw.balance !== null" x-text="gw.balance_unit || '<?= __js('sms.credits') ?>'"></span>
                                </p>
                            </div>
                            <button @click="checkBalance(gw)"
                                class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                title="<?= __('sms.check_balance') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                        </div>

                        <!-- Configuration status -->
                        <div class="flex items-center gap-2 mb-4 text-sm"
                            :class="isConfigured(gw) ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400'">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <template x-if="isConfigured(gw)">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </template>
                                <template x-if="!isConfigured(gw)">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </template>
                            </svg>
                            <span x-text="gw.is_platform ? '<?= __js('sms_credits.platform_desc') ?? 'SMS via crédits plateforme' ?>' : (isConfigured(gw) ? 'Configuré' : '<?= __js('sms.not_configured') ?>')"></span>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <button @click="openConfigModal(gw)"
                                class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span x-text="gw.is_platform ? '<?= __js('sms_credits.details') ?? 'Détails' ?>' : '<?= __js('sms.configure') ?>'"></span>
                            </button>
                            <button @click="toggleGateway(gw)"
                                class="px-4 py-2 border rounded-lg text-sm font-medium transition-colors"
                                :class="gw.is_active
                                    ? 'border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20'
                                    : 'border-green-200 dark:border-green-800 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20'"
                                x-text="gw.is_active ? 'Désactiver' : 'Activer'">
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty state -->
        <div x-show="!loading && gateways.length === 0" class="flex flex-col items-center justify-center py-20 px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mb-2 font-medium"><?= __('sms.no_gateways') ?></p>
            <p class="text-sm text-gray-400 dark:text-gray-500"><?= __('sms.no_gateways_desc') ?></p>
        </div>
    </div>

    <!-- Tab: Templates -->
    <div x-show="activeTab === 'templates'" x-cloak>
        <!-- Sub-tabs PPPoE / Hotspot -->
        <div class="flex items-center justify-between mb-5">
            <div class="flex gap-2">
                <button @click="templateCategory = 'pppoe'; loadTemplates()"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="templateCategory === 'pppoe'
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <?= __('sms.sub_pppoe') ?>
                    </span>
                </button>
                <button @click="templateCategory = 'hotspot'; loadTemplates()"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                    :class="templateCategory === 'hotspot'
                        ? 'bg-blue-600 text-white shadow-sm'
                        : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0" />
                        </svg>
                        <?= __('sms.sub_hotspot') ?>
                    </span>
                </button>
            </div>

            <button @click="openNewTemplateModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2 text-sm font-medium transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('sms.new_template') ?>
            </button>
        </div>

        <!-- Template Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="tpl in templates" :key="tpl.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                    <!-- Header: Name + Toggle -->
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm truncate" x-text="tpl.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate" x-text="tpl.description"></p>
                        </div>
                        <button @click="toggleTemplateActive(tpl)"
                            class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out ml-3"
                            :class="tpl.is_active == 1 ? 'bg-blue-500' : 'bg-gray-300 dark:bg-[#30363d]'">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                :class="tpl.is_active == 1 ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <!-- Event Badge + Days -->
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                            :class="getEventColor(tpl.event_type)"
                            x-text="getEventLabel(tpl.event_type)"></span>
                        <template x-if="tpl.days_before != 0">
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                x-text="tpl.days_before > 0 ? 'J-' + tpl.days_before : 'J+' + Math.abs(tpl.days_before)"></span>
                        </template>
                    </div>

                    <!-- Message Preview -->
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-3 mb-3 max-h-28 overflow-y-auto">
                        <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans" x-text="tpl.message_template?.substring(0, 160) + (tpl.message_template?.length > 160 ? '...' : '')"></pre>
                    </div>

                    <!-- SMS count indicator -->
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            <span x-text="(tpl.message_template || '').length"></span> <?= __('sms.chars_count') ?>
                            · <span x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))((tpl.message_template || '').length)"></span> SMS
                        </span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-1">
                        <button @click="openTestTemplateModal(tpl)" class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded transition-colors" title="<?= __('sms.test_template') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                        <button @click="openEditTemplateModal(tpl)" class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded transition-colors" title="<?= __('sms.edit_template') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="deleteTemplate(tpl.id)" class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded transition-colors" title="Supprimer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty state -->
        <div x-show="templatesLoaded && templates.length === 0" class="flex flex-col items-center justify-center py-20 px-4">
            <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <p class="text-gray-500 dark:text-gray-400 mb-2 font-medium"><?= __('sms.no_templates') ?></p>
            <p class="text-sm text-gray-400 dark:text-gray-500"><?= __('sms.no_templates_desc') ?></p>
        </div>
    </div>

    <!-- Tab: History -->
    <div x-show="activeTab === 'history'" x-cloak>
        <!-- Filter -->
        <div class="mb-4 flex items-center gap-3">
            <select x-model="historyFilter" @change="loadHistory()"
                class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                <option value=""><?= __('sms.all_statuses') ?></option>
                <option value="sent"><?= __('sms.sent') ?></option>
                <option value="failed"><?= __('sms.failed') ?></option>
                <option value="pending"><?= __('sms.pending') ?></option>
            </select>
        </div>

        <!-- History Table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#21262d]">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('sms.phone') ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('sms.message') ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('sms.gateway') ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('sms.status') ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('sms.date') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                        <template x-for="item in history" :key="item.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#21262d]">
                                <td class="px-4 py-3 text-gray-900 dark:text-white font-mono text-xs" x-text="item.phone"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-xs truncate" x-text="item.message"></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400" x-text="item.gateway_name || '-'"></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.status === 'sent',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.status === 'failed',
                                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': item.status === 'pending',
                                        }"
                                        x-text="item.status === 'sent' ? '<?= __js('sms.sent') ?>' : (item.status === 'failed' ? '<?= __js('sms.failed') ?>' : '<?= __js('sms.pending') ?>')">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="item.created_at"></td>
                            </tr>
                        </template>
                        <tr x-show="history.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-gray-400 dark:text-gray-500">
                                <?= __('sms.no_history') ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="historyPagination.pages > 1" class="px-4 py-3 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Page <span x-text="historyPagination.page"></span> / <span x-text="historyPagination.pages"></span>
                    (<span x-text="historyPagination.total"></span> total)
                </p>
                <div class="flex gap-2">
                    <button @click="historyPagination.page--; loadHistory()" :disabled="historyPagination.page <= 1"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50">
                        &laquo;
                    </button>
                    <button @click="historyPagination.page++; loadHistory()" :disabled="historyPagination.page >= historyPagination.pages"
                        class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50">
                        &raquo;
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Modal -->
    <div x-show="showConfigModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="showConfigModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col"
            @click.outside="showConfigModal = false">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedGateway?.provider_name || selectedGateway?.name"></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="selectedGateway?.description"></p>
                </div>
                <button @click="showConfigModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Tabs -->
            <div class="px-6 pt-3 border-b border-gray-200 dark:border-[#30363d] flex-shrink-0">
                <nav class="-mb-px flex gap-4">
                    <button @click="configModalTab = 'config'"
                        :class="configModalTab === 'config' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm">
                        <?= __('sms.config_tab') ?>
                    </button>
                    <button @click="configModalTab = 'test'"
                        :class="configModalTab === 'test' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm">
                        <?= __('sms.test_tab') ?>
                    </button>
                </nav>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 overflow-y-auto flex-1">
                <!-- Config Tab -->
                <div x-show="configModalTab === 'config'">
                    <!-- Platform provider: no config needed -->
                    <div x-show="selectedGateway?.is_platform" class="space-y-4">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <p class="text-sm text-blue-700 dark:text-blue-300">
                                <?= __('sms_credits.platform_no_config') ?? 'Cette passerelle utilise vos crédits SMS (CSMS) de la plateforme. Aucune configuration externe n\'est nécessaire.' ?>
                            </p>
                        </div>
                        <div class="text-center py-4">
                            <p class="text-4xl font-bold text-blue-600 dark:text-blue-400" x-text="selectedGateway?.balance || 0"></p>
                            <p class="text-sm text-gray-500 mt-1">CSMS <?= __('sms_credits.available') ?? 'disponibles' ?></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-3 text-center">
                            <p class="text-xs text-gray-500"><?= __('sms_credits.convert_hint') ?? 'Convertissez vos CRT en CSMS via le bouton dans la barre supérieure' ?></p>
                        </div>
                    </div>

                    <!-- Regular providers: show config fields -->
                    <div x-show="!selectedGateway?.is_platform" class="space-y-4">
                        <!-- Dynamic fields from provider definition -->
                        <template x-for="field in getProviderFields()" :key="field.key">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <span x-text="field.label"></span>
                                    <span x-show="field.required" class="text-red-500">*</span>
                                </label>
                                <input
                                    :type="field.type || 'text'"
                                    x-model="gatewayForm.config[field.key]"
                                    :placeholder="field.secret && gatewayForm.config[field.key + '_masked'] ? gatewayForm.config[field.key + '_masked'] : (field.placeholder || '')"
                                    :required="field.required"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Test SMS Tab -->
                <div x-show="configModalTab === 'test'">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('sms.test_phone') ?>
                            </label>
                            <input type="text" x-model="testPhone" placeholder="22899001122"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('sms.test_message') ?>
                            </label>
                            <textarea x-model="testMessage" rows="4"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            <div class="mt-1">
                                <span class="text-xs text-gray-400">
                                    <span x-text="testMessage.length"></span> <?= __('sms.chars_count') ?>
                                    · <span x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))(testMessage.length)"></span> SMS
                                </span>
                            </div>
                        </div>
                        <button @click="sendTestSms()" :disabled="testSending || !testPhone"
                            class="w-full px-4 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                            <svg x-show="!testSending" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                            <svg x-show="testSending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <span x-text="testSending ? '<?= __js('sms.sending') ?>' : '<?= __js('sms.send_test') ?>'"></span>
                        </button>

                        <!-- Test Result -->
                        <div x-show="testResult" class="mt-2 p-4 rounded-lg border"
                            :class="testResult?.success
                                ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800'
                                : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800'">
                            <template x-if="testResult?.success">
                                <div>
                                    <p class="text-green-700 dark:text-green-400 font-medium flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                        <?= __('sms.test_success') ?>
                                    </p>
                                    <div class="mt-2 space-y-1 text-sm text-green-600 dark:text-green-300">
                                        <p x-show="testResult.data?.message_id">
                                            <?= __('sms.message_id') ?>: <span class="font-mono" x-text="testResult.data?.message_id"></span>
                                        </p>
                                        <p x-show="testResult.data?.credits != null">
                                            <?= __('sms.credits_remaining') ?>: <span class="font-bold" x-text="testResult.data?.credits"></span>
                                        </p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="testResult && !testResult.success">
                                <div>
                                    <p class="text-red-700 dark:text-red-400 font-medium flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        <?= __('sms.test_failed') ?>
                                    </p>
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-300" x-text="testResult.error"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end gap-3 flex-shrink-0">
                <button @click="showConfigModal = false"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-[#21262d]">
                    <?= __('sms.close') ?>
                </button>
                <button @click="saveGateway()" x-show="configModalTab === 'config' && !selectedGateway?.is_platform" :disabled="saving"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                    <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <?= __('sms.save') ?>
                </button>
            </div>
        </div>
    </div>
    <!-- Template Create/Edit Modal -->
    <div x-show="showTemplateModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="showTemplateModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col"
            @click.outside="showTemplateModal = false">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between flex-shrink-0">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"
                    x-text="templateForm.id ? '<?= __js('sms.edit_template') ?>' : '<?= __js('sms.new_template') ?>'"></h2>
                <button @click="showTemplateModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.template_name') ?> <span class="text-red-500">*</span></label>
                    <input type="text" x-model="templateForm.name"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.template_description') ?></label>
                    <input type="text" x-model="templateForm.description"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Event Type + Days Before (row) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.template_event') ?> <span class="text-red-500">*</span></label>
                        <select x-model="templateForm.event_type"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <template x-for="evt in getEventTypes()" :key="evt.value">
                                <option :value="evt.value" x-text="evt.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.template_days_before') ?></label>
                        <input type="number" x-model.number="templateForm.days_before"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">0 = jour même, -1 = après</p>
                    </div>
                </div>

                <!-- Message Template -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.template_message') ?> <span class="text-red-500">*</span></label>
                    <textarea x-model="templateForm.message_template" rows="5" x-ref="templateMessageArea"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-gray-400">
                            <span x-text="(templateForm.message_template || '').length"></span> <?= __('sms.chars_count') ?>
                            · <span x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))((templateForm.message_template || '').length)"></span> SMS
                        </span>
                    </div>
                </div>

                <!-- Available Variables -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('sms.variables') ?></label>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2"><?= __('sms.variable_hint') ?></p>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="v in templateVariables" :key="v.variable">
                            <button type="button" @click="insertVariable(v.placeholder)"
                                class="px-2 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded text-xs font-mono hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-colors"
                                :title="v.description"
                                x-text="v.placeholder"></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end gap-3 flex-shrink-0">
                <button @click="showTemplateModal = false"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-[#21262d]">
                    <?= __('sms.close') ?>
                </button>
                <button @click="saveTemplate()" :disabled="templateSaving"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium flex items-center gap-2">
                    <svg x-show="templateSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <?= __('sms.save') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Test Template Modal -->
    <div x-show="showTestTemplateModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="showTestTemplateModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-hidden flex flex-col"
            @click.outside="showTestTemplateModal = false">

            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('sms.test_template') ?></h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="testTemplateData?.name"></p>
                </div>
                <button @click="showTestTemplateModal = false" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-5 overflow-y-auto flex-1 space-y-4">
                <!-- Gateway selector -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.gateway') ?> <span class="text-red-500">*</span></label>
                    <select x-model="testTemplateGatewayId"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">-- <?= __('sms.select_gateway') ?> --</option>
                        <template x-for="gw in gateways.filter(g => g.is_active)" :key="gw.id">
                            <option :value="gw.id" x-text="gw.provider_name || gw.name"></option>
                        </template>
                    </select>
                    <p x-show="gateways.filter(g => g.is_active).length === 0" class="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        <?= __('sms.no_active_gateway') ?>
                    </p>
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.test_phone') ?> <span class="text-red-500">*</span></label>
                    <input type="text" x-model="testTemplatePhone" placeholder="22899001122"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Message preview -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('sms.message_preview') ?></label>
                    <textarea x-model="testTemplateMessage" rows="5"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono"></textarea>
                    <div class="flex items-center justify-between mt-1">
                        <p class="text-xs text-gray-400"><?= __('sms.test_preview_hint') ?></p>
                        <span class="text-xs text-gray-400">
                            <span x-text="testTemplateMessage.length"></span> <?= __('sms.chars_count') ?>
                            · <span x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))(testTemplateMessage.length)"></span> SMS
                        </span>
                    </div>
                </div>

                <!-- Send button -->
                <button @click="sendTestTemplate()" :disabled="testTemplateSending || !testTemplatePhone || !testTemplateGatewayId"
                    class="w-full px-4 py-2.5 bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2">
                    <svg x-show="!testTemplateSending" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg x-show="testTemplateSending" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="testTemplateSending ? '<?= __js('sms.sending') ?>' : '<?= __js('sms.send_test') ?>'"></span>
                </button>

                <!-- Test Result -->
                <div x-show="testTemplateResult" class="mt-2 p-4 rounded-lg border"
                    :class="testTemplateResult?.success
                        ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800'">
                    <template x-if="testTemplateResult?.success">
                        <div>
                            <p class="text-green-700 dark:text-green-400 font-medium flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <?= __('sms.test_success') ?>
                            </p>
                            <div class="mt-2 space-y-1 text-sm text-green-600 dark:text-green-300">
                                <p x-show="testTemplateResult.data?.message_id">
                                    <?= __('sms.message_id') ?>: <span class="font-mono" x-text="testTemplateResult.data?.message_id"></span>
                                </p>
                                <p x-show="testTemplateResult.data?.credits != null">
                                    <?= __('sms.credits_remaining') ?>: <span class="font-bold" x-text="testTemplateResult.data?.credits"></span>
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="testTemplateResult && !testTemplateResult.success">
                        <p class="text-red-700 dark:text-red-400 font-medium flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span x-text="testTemplateResult.error || '<?= __js('sms.test_failed') ?>'"></span>
                        </p>
                    </template>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end flex-shrink-0">
                <button @click="showTestTemplateModal = false"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-50 dark:hover:bg-[#21262d]">
                    <?= __('sms.close') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function smsPage() {
    return {
        activeTab: 'gateways',
        loading: true,

        // Data
        providers: {},
        gateways: [],
        history: [],
        historyLoaded: false,
        historyFilter: '',
        historyPagination: { page: 1, pages: 1, total: 0 },

        // Modal state
        showConfigModal: false,
        configModalTab: 'config',
        selectedGateway: null,
        gatewayForm: { config: {} },
        saving: false,

        // Test SMS state
        testPhone: '',
        testMessage: 'Ceci est un message de test SMS envoyé depuis votre plateforme.',
        testSending: false,
        testResult: null,

        // Templates state
        templates: [],
        templatesLoaded: false,
        templateCategory: 'pppoe',
        templateVariables: [],
        showTemplateModal: false,
        templateSaving: false,
        templateForm: {
            id: null,
            name: '',
            description: '',
            category: 'pppoe',
            event_type: 'expiration_warning',
            message_template: '',
            days_before: 0,
            is_active: 1,
        },

        // Test template state
        showTestTemplateModal: false,
        testTemplateData: null,
        testTemplatePhone: '',
        testTemplateMessage: '',
        testTemplateGatewayId: '',
        testTemplateSending: false,
        testTemplateResult: null,

        async init() {
            await Promise.all([
                this.loadProviders(),
                this.loadGateways(),
            ]);
            this.loading = false;
        },

        async loadProviders() {
            try {
                const r = await fetch('api.php?route=/sms/providers');
                const data = await r.json();
                this.providers = data.providers || {};
            } catch (e) {
                console.error('Error loading providers:', e);
            }
        },

        async loadGateways() {
            try {
                const r = await fetch('api.php?route=/sms/gateways');
                const data = await r.json();
                this.gateways = data.gateways || [];
            } catch (e) {
                console.error('Error loading gateways:', e);
            }
        },

        async loadHistory() {
            try {
                const params = new URLSearchParams({
                    page: this.historyPagination.page,
                });
                if (this.historyFilter) params.set('status', this.historyFilter);

                const r = await fetch('api.php?route=/sms/history&' + params.toString());
                const data = await r.json();
                this.history = data.history || [];
                this.historyPagination = data.pagination || { page: 1, pages: 1, total: 0 };
                this.historyLoaded = true;
            } catch (e) {
                console.error('Error loading history:', e);
            }
        },

        getProviderFields() {
            if (!this.selectedGateway) return [];
            const providerCode = this.selectedGateway.provider_code;
            return this.providers[providerCode]?.fields || [];
        },

        isConfigured(gw) {
            if (gw.is_platform) return true;
            const config = gw.config || {};
            const providerDef = this.providers[gw.provider_code];
            if (!providerDef) return false;

            return providerDef.fields
                .filter(f => f.required)
                .every(f => {
                    // Check either the actual value or the masked version exists
                    return (config[f.key] && config[f.key] !== '') || (config[f.key + '_masked'] && config[f.key + '_masked'] !== '');
                });
        },

        openConfigModal(gw) {
            this.selectedGateway = gw;
            this.configModalTab = 'config';
            this.testResult = null;

            // Build form from gateway data
            const config = {};
            const providerDef = this.providers[gw.provider_code];
            if (providerDef) {
                providerDef.fields.forEach(field => {
                    if (field.secret) {
                        // For secret fields: keep masked info, set value to empty (user fills if changing)
                        config[field.key] = '';
                        if (gw.config[field.key + '_masked']) {
                            config[field.key + '_masked'] = gw.config[field.key + '_masked'];
                        }
                    } else {
                        config[field.key] = gw.config[field.key] || '';
                    }
                });
            }

            this.gatewayForm = {
                id: gw.id,
                provider_code: gw.provider_code,
                config: config,
            };

            this.showConfigModal = true;
        },

        async saveGateway() {
            this.saving = true;
            try {
                const r = await fetch('api.php?route=/sms/gateways/' + this.gatewayForm.id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.gatewayForm),
                });
                const data = await r.json();
                if (data.success) {
                    this.notify('<?= __js('sms.config_saved') ?>', 'success');
                    this.showConfigModal = false;
                    await this.loadGateways();
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur réseau', 'error');
            }
            this.saving = false;
        },

        async toggleGateway(gw) {
            try {
                const r = await fetch('api.php?route=/sms/gateways/' + gw.id + '/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await r.json();
                if (data.success || data.data) {
                    gw.is_active = data.data?.is_active ?? !gw.is_active;
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                    await this.loadGateways();
                }
            } catch (e) {
                this.notify('Erreur réseau', 'error');
                await this.loadGateways();
            }
        },

        async sendTestSms() {
            if (!this.testPhone) return;
            this.testSending = true;
            this.testResult = null;

            try {
                // Save config first, then test
                await fetch('api.php?route=/sms/gateways/' + this.gatewayForm.id, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.gatewayForm),
                });

                const r = await fetch('api.php?route=/sms/gateways/' + this.gatewayForm.id + '/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone: this.testPhone,
                        message: this.testMessage,
                    }),
                });
                const data = await r.json();

                if (data.success) {
                    this.testResult = { success: true, data: data.data };
                    this.notify('<?= __js('sms.test_success') ?>', 'success');
                    // Refresh gateways to update balance
                    await this.loadGateways();
                } else {
                    this.testResult = { success: false, error: data.message || '<?= __js('sms.test_failed') ?>' };
                    this.notify(data.message || '<?= __js('sms.test_failed') ?>', 'error');
                }
            } catch (e) {
                this.testResult = { success: false, error: 'Erreur réseau' };
                this.notify('Erreur réseau', 'error');
            }
            this.testSending = false;
        },

        // --- Templates ---

        async loadTemplates() {
            try {
                const r = await fetch('api.php?route=/sms/templates&category=' + this.templateCategory);
                const data = await r.json();
                this.templates = data.templates || [];
                this.templatesLoaded = true;
                await this.loadVariables();
            } catch (e) {
                console.error('Error loading templates:', e);
            }
        },

        async loadVariables() {
            try {
                const r = await fetch('api.php?route=/sms/variables&category=' + this.templateCategory);
                const data = await r.json();
                this.templateVariables = data.variables || [];
            } catch (e) {
                console.error('Error loading variables:', e);
            }
        },

        getEventTypes() {
            if (this.templateCategory === 'hotspot') {
                return [
                    { value: 'voucher_created', label: '<?= __js('sms.event_voucher_created') ?>' },
                    { value: 'welcome', label: '<?= __js('sms.event_welcome') ?>' },
                    { value: 'expiration_warning', label: '<?= __js('sms.event_expiration_warning') ?>' },
                    { value: 'expired', label: '<?= __js('sms.event_expired') ?>' },
                    { value: 'connection_info', label: '<?= __js('sms.event_connection_info') ?>' },
                    { value: 'custom', label: '<?= __js('sms.event_custom') ?>' },
                ];
            }
            return [
                { value: 'expiration_warning', label: '<?= __js('sms.event_expiration_warning') ?>' },
                { value: 'expired', label: '<?= __js('sms.event_expired') ?>' },
                { value: 'welcome', label: '<?= __js('sms.event_welcome') ?>' },
                { value: 'payment_received', label: '<?= __js('sms.event_payment_received') ?>' },
                { value: 'suspended', label: '<?= __js('sms.event_suspended') ?>' },
                { value: 'reactivated', label: '<?= __js('sms.event_reactivated') ?>' },
                { value: 'custom', label: '<?= __js('sms.event_custom') ?>' },
            ];
        },

        getEventLabel(eventType) {
            const labels = {
                'expiration_warning': '<?= __js('sms.event_expiration_warning') ?>',
                'expired': '<?= __js('sms.event_expired') ?>',
                'welcome': '<?= __js('sms.event_welcome') ?>',
                'payment_received': '<?= __js('sms.event_payment_received') ?>',
                'suspended': '<?= __js('sms.event_suspended') ?>',
                'reactivated': '<?= __js('sms.event_reactivated') ?>',
                'voucher_created': '<?= __js('sms.event_voucher_created') ?>',
                'connection_info': '<?= __js('sms.event_connection_info') ?>',
                'custom': '<?= __js('sms.event_custom') ?>',
            };
            return labels[eventType] || eventType;
        },

        getEventColor(eventType) {
            const colors = {
                'expiration_warning': 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                'expired': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'welcome': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'payment_received': 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
                'suspended': 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
                'reactivated': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'voucher_created': 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                'connection_info': 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
                'custom': 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
            };
            return colors[eventType] || colors['custom'];
        },

        openNewTemplateModal() {
            this.templateForm = {
                id: null,
                name: '',
                description: '',
                category: this.templateCategory,
                event_type: this.templateCategory === 'hotspot' ? 'voucher_created' : 'expiration_warning',
                message_template: '',
                days_before: 0,
                is_active: 1,
            };
            this.showTemplateModal = true;
        },

        openEditTemplateModal(tpl) {
            this.templateForm = {
                id: tpl.id,
                name: tpl.name,
                description: tpl.description || '',
                category: tpl.category,
                event_type: tpl.event_type,
                message_template: tpl.message_template,
                days_before: parseInt(tpl.days_before) || 0,
                is_active: tpl.is_active,
            };
            this.showTemplateModal = true;
        },

        insertVariable(placeholder) {
            const textarea = this.$refs.templateMessageArea;
            if (!textarea) {
                this.templateForm.message_template += placeholder;
                return;
            }
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = this.templateForm.message_template || '';
            this.templateForm.message_template = text.substring(0, start) + placeholder + text.substring(end);
            this.$nextTick(() => {
                textarea.focus();
                textarea.selectionStart = textarea.selectionEnd = start + placeholder.length;
            });
        },

        async saveTemplate() {
            if (!this.templateForm.name || !this.templateForm.event_type || !this.templateForm.message_template) {
                this.notify('Veuillez remplir tous les champs requis', 'error');
                return;
            }

            this.templateSaving = true;
            try {
                const isEdit = !!this.templateForm.id;
                const url = isEdit
                    ? 'api.php?route=/sms/templates/' + this.templateForm.id
                    : 'api.php?route=/sms/templates';
                const method = isEdit ? 'PUT' : 'POST';

                const r = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.templateForm),
                });
                const data = await r.json();

                if (data.success) {
                    this.notify(data.message || (isEdit ? '<?= __js('sms.template_updated') ?>' : '<?= __js('sms.template_created') ?>'), 'success');
                    this.showTemplateModal = false;
                    await this.loadTemplates();
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur réseau', 'error');
            }
            this.templateSaving = false;
        },

        async toggleTemplateActive(tpl) {
            try {
                tpl.is_active = tpl.is_active == 1 ? 0 : 1;
                const r = await fetch('api.php?route=/sms/templates/' + tpl.id + '/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await r.json();
                if (!data.success) {
                    tpl.is_active = tpl.is_active == 1 ? 0 : 1;
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                tpl.is_active = tpl.is_active == 1 ? 0 : 1;
                this.notify('Erreur réseau', 'error');
            }
        },

        async deleteTemplate(id) {
            if (!confirm('<?= __js('sms.confirm_delete_template') ?>')) return;
            try {
                const r = await fetch('api.php?route=/sms/templates/' + id, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                });
                const data = await r.json();
                if (data.success) {
                    this.notify('<?= __js('sms.template_deleted') ?>', 'success');
                    await this.loadTemplates();
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur réseau', 'error');
            }
        },

        openTestTemplateModal(tpl) {
            this.testTemplateData = tpl;
            this.testTemplateResult = null;
            this.testTemplateSending = false;

            // Auto-select first active gateway
            const activeGw = this.gateways.find(g => g.is_active);
            this.testTemplateGatewayId = activeGw ? activeGw.id : '';

            // Replace variables with sample values
            this.testTemplateMessage = this.replaceWithSampleValues(tpl.message_template || '', tpl.category);

            this.showTestTemplateModal = true;
        },

        replaceWithSampleValues(message, category) {
            const samples = category === 'hotspot' ? {
                'customer_name': 'Jean Dupont',
                'voucher_code': 'ABC-12345',
                'profile_name': 'WiFi 24h',
                'duration': '24 heures',
                'hotspot_name': 'MonHotspot',
                'download_speed': '10 Mbps',
                'upload_speed': '5 Mbps',
                'company_name': 'MonEntreprise',
                'support_phone': '+229 97 00 00 00',
                'current_date': new Date().toLocaleDateString('fr-FR'),
            } : {
                'customer_name': 'Jean Dupont',
                'username': 'jean.dupont',
                'password': '********',
                'profile_name': 'Forfait 10Mbps',
                'profile_price': '15 000',
                'expiration_date': '28/02/2026',
                'days_remaining': '5',
                'download_speed': '10 Mbps',
                'upload_speed': '5 Mbps',
                'company_name': 'MonEntreprise',
                'support_phone': '+229 97 00 00 00',
                'current_date': new Date().toLocaleDateString('fr-FR'),
            };

            let result = message;
            for (const [key, val] of Object.entries(samples)) {
                result = result.replace(new RegExp('\\{\\{' + key + '\\}\\}', 'g'), val);
            }
            return result;
        },

        async sendTestTemplate() {
            if (!this.testTemplatePhone || !this.testTemplateGatewayId) return;

            this.testTemplateSending = true;
            this.testTemplateResult = null;

            try {
                const r = await fetch('api.php?route=/sms/gateways/' + this.testTemplateGatewayId + '/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone: this.testTemplatePhone,
                        message: this.testTemplateMessage,
                    }),
                });
                const data = await r.json();

                if (data.success) {
                    this.testTemplateResult = { success: true, data: data.data };
                    this.notify('<?= __js('sms.test_success') ?>', 'success');
                } else {
                    this.testTemplateResult = { success: false, error: data.message || '<?= __js('sms.test_failed') ?>' };
                    this.notify(data.message || '<?= __js('sms.test_failed') ?>', 'error');
                }
            } catch (e) {
                this.testTemplateResult = { success: false, error: 'Erreur réseau' };
                this.notify('Erreur réseau', 'error');
            }
            this.testTemplateSending = false;
        },

        async checkBalance(gw) {
            try {
                const r = await fetch('api.php?route=/sms/gateways/' + gw.id + '/balance');
                const data = await r.json();
                if (data.success && data.data) {
                    gw.balance = data.data.balance;
                    this.notify('<?= __js('sms.balance') ?>: ' + data.data.balance + ' <?= __js('sms.credits') ?>', 'success');
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur réseau', 'error');
            }
        },

        notify(message, type) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        },
    };
}
</script>
