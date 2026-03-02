<?php $pageTitle = __('pppoe_reminders.title');
$currentPage = 'pppoe-reminders'; ?>

<div x-data="pppoeRemindersPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('pppoe_reminders.subtitle')?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Toggle enabled -->
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" :checked="enabled" @change="toggleEnabled()">
                <div
                    class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#30363d] peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:after:border-gray-500 peer-checked:bg-green-600">
                </div>
                <span class="ms-2 text-sm font-medium"
                    :class="enabled ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'"
                    x-text="enabled ? '<?= __('pppoe_reminders.enabled')?>' : '<?= __('pppoe_reminders.disabled')?>'"></span>
            </label>

            <!-- Process now -->
            <button @click="processNow()" :disabled="processing"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                <svg x-show="!processing" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                <svg x-show="processing" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span
                    x-text="processing ? '<?= __('pppoe_reminders.processing')?>' : '<?= __('pppoe_reminders.process_now')?>'"></span>
            </button>
        </div>
    </div>

    <!-- Stats cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Card 1 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-blue-500/10 dark:bg-blue-500/5 rounded-full blur-2xl group-hover:bg-blue-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_reminders.total_sent')?>
                    </p>
                    <p class="text-3xl font-black text-blue-600 dark:text-blue-400" x-text="stats.total_sent || 0"></p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-[0_0_15px_rgba(59,130,246,0.3)] group-hover:shadow-[0_0_20px_rgba(59,130,246,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div
            class="relative bg-white dark:bg-[#161b22] rounded-2xl p-6 border border-gray-100 dark:border-[#30363d] overflow-hidden group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
            <div
                class="absolute -right-6 -top-6 w-24 h-24 bg-rose-500/10 dark:bg-rose-500/5 rounded-full blur-2xl group-hover:bg-rose-500/20 transition-all duration-500">
            </div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <p class="text-[13px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        <?= __('pppoe_reminders.total_failed')?>
                    </p>
                    <p class="text-3xl font-black text-rose-600 dark:text-rose-400" x-text="stats.total_failed || 0">
                    </p>
                </div>
                <div
                    class="w-14 h-14 rounded-2xl bg-gradient-to-br from-rose-500 to-rose-600 shadow-[0_0_15px_rgba(244,63,94,0.3)] group-hover:shadow-[0_0_20px_rgba(244,63,94,0.5)] flex items-center justify-center text-white transition-all duration-300 group-hover:scale-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
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
                        <?= __('pppoe_reminders.success_rate')?>
                    </p>
                    <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400"
                        x-text="(stats.success_rate || 0) + '%'"></p>
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
                        <?= __('pppoe_reminders.pending_today')?>
                    </p>
                    <p class="text-3xl font-black text-amber-600 dark:text-amber-400" x-text="stats.pending_today || 0">
                    </p>
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

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 bg-gray-100 dark:bg-[#21262d] rounded-lg p-0.5 w-fit">
        <button @click="activeTab = 'rules'"
            :class="activeTab === 'rules' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
            class="py-2 px-4 rounded-md text-sm font-medium transition-all">
            <?= __('pppoe_reminders.tab_rules')?>
        </button>
        <button @click="activeTab = 'history'; loadHistory()"
            :class="activeTab === 'history' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
            class="py-2 px-4 rounded-md text-sm font-medium transition-all">
            <?= __('pppoe_reminders.tab_history')?>
        </button>
        <button @click="activeTab = 'variables'"
            :class="activeTab === 'variables' ? 'bg-white dark:bg-[#30363d] text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
            class="py-2 px-4 rounded-md text-sm font-medium transition-all">
            <?= __('pppoe_reminders.tab_variables')?>
        </button>
    </div>

    <!-- ============ TAB: RULES ============ -->
    <div x-show="activeTab === 'rules'">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                <?= __('pppoe_reminders.tab_rules')?>
            </h3>
            <button @click="openRuleModal()"
                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('pppoe_reminders.new_rule')?>
            </button>
        </div>

        <!-- Rules table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <!-- Header -->
            <div
                class="hidden sm:grid grid-cols-12 gap-3 px-4 py-2.5 bg-gray-50 dark:bg-[#0d1117] border-b border-gray-200 dark:border-[#30363d] text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                <div class="col-span-1">
                    <?= __('pppoe_reminders.active') ?? 'Actif'?>
                </div>
                <div class="col-span-3">
                    <?= __('pppoe_reminders.rule_name')?>
                </div>
                <div class="col-span-2">
                    <?= __('pppoe_reminders.timing') ?? 'Timing'?>
                </div>
                <div class="col-span-1">
                    <?= __('pppoe_reminders.channel')?>
                </div>
                <div class="col-span-3">
                    <?= __('pppoe_reminders.preview') ?? 'Aperçu'?>
                </div>
                <div class="col-span-2 text-right">
                    <?= __('common.actions')?>
                </div>
            </div>

            <!-- Rows -->
            <template x-for="rule in rules" :key="rule.id">
                <div
                    class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 px-4 py-3 border-b border-gray-100 dark:border-[#21262d] hover:bg-gray-50/50 dark:hover:bg-[#1c2128] transition-colors items-center">
                    <!-- Active toggle -->
                    <div class="hidden sm:flex col-span-1 items-center">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" :checked="rule.is_active == 1"
                                @change="toggleRule(rule)">
                            <div
                                class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#30363d] peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:start-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3.5 after:w-3.5 after:transition-all dark:after:border-gray-500 peer-checked:bg-green-600">
                            </div>
                        </label>
                    </div>

                    <!-- Name -->
                    <div class="sm:col-span-3">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="rule.name"></p>
                    </div>

                    <!-- Timing -->
                    <div class="hidden sm:block col-span-2">
                        <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full"
                            :class="rule.days_before > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : rule.days_before === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                            x-text="getTimingLabel(rule.days_before)"></span>
                    </div>

                    <!-- Channel -->
                    <div class="hidden sm:block col-span-1">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-0.5 text-[11px] font-semibold rounded-full uppercase"
                            :class="rule.channel === 'whatsapp' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'"
                            x-text="rule.channel === 'whatsapp' ? 'WhatsApp' : 'SMS'"></span>
                    </div>

                    <!-- Preview -->
                    <div class="hidden sm:block col-span-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate"
                            x-text="rule.message_template.substring(0, 80) + (rule.message_template.length > 80 ? '...' : '')">
                        </p>
                    </div>

                    <!-- Mobile info -->
                    <div class="flex items-center gap-2 sm:hidden">
                        <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-full"
                            :class="rule.days_before > 0 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : rule.days_before === 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                            x-text="getTimingLabel(rule.days_before)"></span>
                        <span
                            class="inline-flex items-center px-2 py-0.5 text-[10px] font-semibold rounded-full uppercase"
                            :class="rule.channel === 'whatsapp' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'"
                            x-text="rule.channel === 'whatsapp' ? 'WhatsApp' : 'SMS'"></span>
                        <label class="relative inline-flex items-center cursor-pointer sm:hidden ml-auto">
                            <input type="checkbox" class="sr-only peer" :checked="rule.is_active == 1"
                                @change="toggleRule(rule)">
                            <div
                                class="w-7 h-3.5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#30363d] peer-checked:after:translate-x-3 peer-checked:after:border-white after:content-[''] after:absolute after:top-[1px] after:start-[1px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-green-600">
                            </div>
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="sm:col-span-2 flex items-center justify-end gap-1">
                        <button @click="editRule(rule)"
                            class="p-1.5 text-gray-400 hover:text-blue-500 rounded-md transition-colors"
                            title="<?= __('common.edit')?>">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button @click="deleteRule(rule)"
                            class="p-1.5 text-gray-400 hover:text-rose-500 rounded-md transition-colors"
                            title="<?= __('common.delete')?>">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Empty -->
            <template x-if="rules.length === 0">
                <div class="text-center py-12">
                    <svg class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        <?= __('pppoe_reminders.no_rules')?>
                    </p>
                </div>
            </template>
        </div>
    </div>

    <!-- ============ TAB: HISTORY ============ -->
    <div x-show="activeTab === 'history'">
        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3 mb-4">
            <select x-model="historyChannel" @change="loadHistory(1)"
                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('pppoe_reminders.all_channels')?>
                </option>
                <option value="whatsapp">WhatsApp</option>
                <option value="sms">SMS</option>
            </select>
            <select x-model="historyStatus" @change="loadHistory(1)"
                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                <option value="">
                    <?= __('pppoe_reminders.all_statuses')?>
                </option>
                <option value="sent">
                    <?= __('pppoe_reminders.status_sent') ?? 'Envoyé'?>
                </option>
                <option value="failed">
                    <?= __('pppoe_reminders.status_failed') ?? 'Échoué'?>
                </option>
            </select>
            <input type="date" x-model="historyDateFrom" @change="loadHistory(1)"
                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                placeholder="<?= __('pppoe_reminders.date_from')?>">
            <input type="date" x-model="historyDateTo" @change="loadHistory(1)"
                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                placeholder="<?= __('pppoe_reminders.date_to')?>">
        </div>

        <!-- History table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div
                class="hidden sm:grid grid-cols-12 gap-3 px-4 py-2.5 bg-gray-50 dark:bg-[#0d1117] border-b border-gray-200 dark:border-[#30363d] text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                <div class="col-span-2">Date</div>
                <div class="col-span-2">Client</div>
                <div class="col-span-2">
                    <?= __('pppoe_reminders.phone') ?? 'Téléphone'?>
                </div>
                <div class="col-span-1">
                    <?= __('pppoe_reminders.channel')?>
                </div>
                <div class="col-span-2">
                    <?= __('pppoe_reminders.rule_name')?>
                </div>
                <div class="col-span-1">
                    <?= __('pppoe_reminders.status') ?? 'Statut'?>
                </div>
                <div class="col-span-2 text-right">
                    <?= __('common.actions')?>
                </div>
            </div>

            <template x-for="log in history" :key="log.id">
                <div
                    class="grid grid-cols-1 sm:grid-cols-12 gap-2 sm:gap-3 px-4 py-3 border-b border-gray-100 dark:border-[#21262d] hover:bg-gray-50/50 dark:hover:bg-[#1c2128] transition-colors items-center">
                    <div class="sm:col-span-2 text-xs text-gray-600 dark:text-gray-400"
                        x-text="new Date(log.sent_at).toLocaleString()"></div>
                    <div class="sm:col-span-2 text-sm font-medium text-gray-900 dark:text-white truncate"
                        x-text="log.customer_name || log.username || '—'"></div>
                    <div class="hidden sm:block col-span-2 text-xs font-mono text-gray-600 dark:text-gray-400"
                        x-text="log.phone"></div>
                    <div class="hidden sm:block col-span-1">
                        <span
                            class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full uppercase"
                            :class="log.channel === 'whatsapp' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'"
                            x-text="log.channel === 'whatsapp' ? 'WA' : 'SMS'"></span>
                    </div>
                    <div class="hidden sm:block col-span-2 text-xs text-gray-500 dark:text-gray-400 truncate"
                        x-text="log.rule_name || '—'"></div>
                    <div class="hidden sm:block col-span-1">
                        <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded-full"
                            :class="log.status === 'sent' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                            x-text="log.status === 'sent' ? '<?= __('pppoe_reminders.status_sent') ?? 'Envoyé'?>' : '<?= __('pppoe_reminders.status_failed') ?? 'Échoué'?>'"></span>
                    </div>
                    <!-- Mobile badges -->
                    <div class="flex items-center gap-2 sm:hidden">
                        <span class="text-[10px] font-mono text-gray-400" x-text="log.phone"></span>
                        <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full uppercase"
                            :class="log.channel === 'whatsapp' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-400'"
                            x-text="log.channel === 'whatsapp' ? 'WA' : 'SMS'"></span>
                        <span class="inline-flex px-1.5 py-0.5 text-[10px] font-semibold rounded-full"
                            :class="log.status === 'sent' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
                            x-text="log.status === 'sent' ? '<?= __('pppoe_reminders.status_sent') ?? 'OK'?>' : '<?= __('pppoe_reminders.status_failed') ?? 'Err'?>'"></span>
                    </div>
                    <div class="sm:col-span-2 flex items-center justify-end">
                        <button
                            @click="viewMessage = log.message; viewError = log.error_message; showMessageModal = true"
                            class="p-1.5 text-gray-400 hover:text-blue-500 rounded-md transition-colors"
                            title="<?= __('pppoe_reminders.view_message') ?? 'Voir message'?>">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>

            <template x-if="history.length === 0">
                <div class="text-center py-12">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= __('pppoe_reminders.no_history')?>
                    </p>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.pages > 1" class="flex items-center justify-between mt-4">
            <p class="text-sm text-gray-500 dark:text-gray-400"
                x-text="'Page ' + pagination.page + ' / ' + pagination.pages + ' (' + pagination.total + ' résultats)'">
            </p>
            <div class="flex gap-1">
                <button @click="loadHistory(pagination.page - 1)" :disabled="pagination.page <= 1"
                    class="px-3 py-1 text-sm border rounded-lg disabled:opacity-30 border-gray-300 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#21262d]">&larr;</button>
                <button @click="loadHistory(pagination.page + 1)" :disabled="pagination.page >= pagination.pages"
                    class="px-3 py-1 text-sm border rounded-lg disabled:opacity-30 border-gray-300 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#21262d]">&rarr;</button>
            </div>
        </div>
    </div>

    <!-- ============ TAB: VARIABLES ============ -->
    <div x-show="activeTab === 'variables'">
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-6">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">
                <?= __('pppoe_reminders.variables_title')?>
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-5">
                <?= __('pppoe_reminders.variables_description')?>
            </p>

            <template x-for="group in variables" :key="group.category">
                <div class="mb-5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2"
                        x-text="group.category"></h4>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="v in group.variables" :key="v.name">
                            <button @click="copyVariable(v.name)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-mono bg-gray-100 dark:bg-[#21262d] border border-gray-200 dark:border-[#30363d] rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 transition-colors cursor-pointer group"
                                :title="v.description">
                                <span class="text-blue-600 dark:text-blue-400" x-text="'{{' + v.name + '}}'"></span>
                                <svg class="w-3 h-3 text-gray-400 group-hover:text-blue-500 transition-colors"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ============ RULE MODAL ============ -->
    <div x-show="showRuleModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRuleModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white"
                        x-text="editingRule ? '<?= __('pppoe_reminders.edit_rule')?>' : '<?= __('pppoe_reminders.new_rule')?>'">
                    </h3>
                    <button @click="showRuleModal = false"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto flex-1 px-5 py-4 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            <?= __('pppoe_reminders.rule_name')?>
                        </label>
                        <input type="text" x-model="ruleForm.name"
                            placeholder="<?= __('pppoe_reminders.rule_name_placeholder') ?? 'Ex: Rappel 3 jours'?>"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                    </div>

                    <!-- Days + Channel -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                <?= __('pppoe_reminders.days_before_label') ?? 'Jours'?>
                            </label>
                            <input type="number" x-model.number="ruleForm.days_before"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                <?= __('pppoe_reminders.timing_help')?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                <?= __('pppoe_reminders.channel')?>
                            </label>
                            <select x-model="ruleForm.channel"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="whatsapp">WhatsApp</option>
                                <option value="sms">SMS</option>
                            </select>
                        </div>
                    </div>

                    <!-- Message template -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            <?= __('pppoe_reminders.message_template')?>
                        </label>
                        <textarea x-model="ruleForm.message_template" rows="6" x-ref="templateTextarea"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono"></textarea>
                        <!-- Variable chips -->
                        <div class="flex flex-wrap gap-1 mt-2">
                            <template x-for="v in quickVars" :key="v">
                                <button type="button" @click="insertVariable(v)"
                                    class="px-2 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-[#21262d] border border-gray-200 dark:border-[#30363d] rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 text-blue-600 dark:text-blue-400 transition-colors"
                                    x-text="'{{' + v + '}}'"></button>
                            </template>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            <?= __('pppoe_reminders.preview') ?? 'Aperçu'?>
                        </label>
                        <div class="px-3 py-2 text-sm bg-gray-50 dark:bg-[#0d1117] border border-gray-200 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 whitespace-pre-wrap min-h-[80px]"
                            x-text="getPreview()"></div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-200 dark:border-[#30363d]">
                    <button @click="showRuleModal = false"
                        class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                        <?= __('common.cancel')?>
                    </button>
                    <button @click="saveRule()" :disabled="savingRule"
                        class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span
                            x-text="savingRule ? '...' : (editingRule ? '<?= __('common.save')?>' : '<?= __('common.add')?>')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ MESSAGE VIEW MODAL ============ -->
    <div x-show="showMessageModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showMessageModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-md w-full">
                <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        <?= __('pppoe_reminders.view_message') ?? 'Message envoyé'?>
                    </h3>
                    <button @click="showMessageModal = false"
                        class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#30363d]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="p-5">
                    <div class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap bg-gray-50 dark:bg-[#0d1117] rounded-lg p-4 border border-gray-200 dark:border-[#30363d]"
                        x-text="viewMessage"></div>
                    <template x-if="viewError">
                        <div
                            class="mt-3 text-xs text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 rounded-lg p-3 border border-rose-200 dark:border-rose-800/30">
                            <strong>Erreur :</strong> <span x-text="viewError"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function pppoeRemindersPage() {
        return {
            activeTab: 'rules',
            processing: false,
            enabled: false,
            savingRule: false,

            rules: [],
            variables: [],
            history: [],
            pagination: { page: 1, pages: 1, total: 0 },
            stats: { total_sent: 0, total_failed: 0, success_rate: 0, pending_today: 0 },

            historyChannel: '',
            historyStatus: '',
            historyDateFrom: '',
            historyDateTo: '',

            showRuleModal: false,
            showMessageModal: false,
            editingRule: null,
            viewMessage: '',
            viewError: '',

            ruleForm: {
                name: '', days_before: 3, channel: 'whatsapp', message_template: '', is_active: true
            },

            quickVars: ['customer_name', 'profile_name', 'profile_price', 'expiration_date', 'days_remaining', 'days_expired', 'support_phone', 'company_name'],

            demoData: {
                customer_name: 'Jean Dupont',
                customer_phone: '+229 97 00 00 00',
                customer_email: 'jean@example.com',
                customer_address: 'Cotonou, Bénin',
                username: 'jean_pppoe',
                password: '****',
                profile_name: 'Gold 10 Mbps',
                profile_price: '20 000',
                expiration_date: '15/03/2026',
                days_remaining: '3',
                days_expired: '0',
                download_speed: '10 Mbps',
                upload_speed: '5 Mbps',
                data_limit: 'Illimité',
                zone_name: 'Zone Nord',
                nas_name: 'Routeur-01',
                company_name: 'MonISP',
                support_phone: '+229 97 00 00 00',
                current_date: new Date().toLocaleDateString('fr-FR'),
                current_time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
            },

            async init() {
                await Promise.all([
                    this.loadSettings(),
                    this.loadRules(),
                    this.loadVariables(),
                    this.loadStats(),
                ]);
            },

            async loadSettings() {
                try {
                    const res = await API.get('/pppoe-reminders/settings');
                    this.enabled = res.data?.enabled || false;
                } catch (e) { }
            },

            async toggleEnabled() {
                this.enabled = !this.enabled;
                try {
                    await API.put('/pppoe-reminders/settings', { enabled: this.enabled });
                    showToast(this.enabled ? __('pppoe_reminders.enabled') : __('pppoe_reminders.disabled'));
                } catch (e) {
                    this.enabled = !this.enabled;
                    showToast(e.message, 'error');
                }
            },

            async loadRules() {
                try {
                    const res = await API.get('/pppoe-reminders/rules');
                    this.rules = res.data || [];
                } catch (e) {
                    showToast(e.message, 'error');
                }
            },

            async loadVariables() {
                try {
                    const res = await API.get('/pppoe-reminders/variables');
                    this.variables = res.data || [];
                } catch (e) { }
            },

            async loadStats() {
                try {
                    const res = await API.get('/pppoe-reminders/stats');
                    this.stats = res.data || {};
                } catch (e) { }
            },

            async loadHistory(page) {
                page = page || 1;
                try {
                    let url = `/pppoe-reminders/history?page=${page}&limit=20`;
                    if (this.historyChannel) url += `&channel=${this.historyChannel}`;
                    if (this.historyStatus) url += `&status=${this.historyStatus}`;
                    if (this.historyDateFrom) url += `&date_from=${this.historyDateFrom}`;
                    if (this.historyDateTo) url += `&date_to=${this.historyDateTo}`;
                    const res = await API.get(url);
                    this.history = res.data?.data || [];
                    this.pagination = res.data?.pagination || { page: 1, pages: 1, total: 0 };
                } catch (e) {
                    showToast(e.message, 'error');
                }
            },

            openRuleModal() {
                this.editingRule = null;
                this.ruleForm = { name: '', days_before: 3, channel: 'whatsapp', message_template: '', is_active: true };
                this.showRuleModal = true;
            },

            editRule(rule) {
                this.editingRule = rule;
                this.ruleForm = {
                    name: rule.name,
                    days_before: parseInt(rule.days_before),
                    channel: rule.channel,
                    message_template: rule.message_template,
                    is_active: rule.is_active == 1
                };
                this.showRuleModal = true;
            },

            async saveRule() {
                if (!this.ruleForm.name || !this.ruleForm.message_template) {
                    showToast(__('pppoe_reminders.fill_required') || 'Remplissez les champs requis', 'error');
                    return;
                }
                this.savingRule = true;
                try {
                    if (this.editingRule) {
                        await API.put(`/pppoe-reminders/rules/${this.editingRule.id}`, this.ruleForm);
                        showToast(__('pppoe_reminders.rule_updated'));
                    } else {
                        await API.post('/pppoe-reminders/rules', this.ruleForm);
                        showToast(__('pppoe_reminders.rule_created'));
                    }
                    this.showRuleModal = false;
                    await this.loadRules();
                } catch (e) {
                    showToast(e.message, 'error');
                } finally {
                    this.savingRule = false;
                }
            },

            async deleteRule(rule) {
                if (!confirmAction(__('pppoe_reminders.confirm_delete'))) return;
                try {
                    await API.delete(`/pppoe-reminders/rules/${rule.id}`);
                    showToast(__('pppoe_reminders.rule_deleted'));
                    await this.loadRules();
                } catch (e) {
                    showToast(e.message, 'error');
                }
            },

            async toggleRule(rule) {
                try {
                    const res = await API.post(`/pppoe-reminders/rules/${rule.id}/toggle`);
                    rule.is_active = res.data.is_active ? 1 : 0;
                } catch (e) {
                    showToast(e.message, 'error');
                }
            },

            async processNow() {
                this.processing = true;
                try {
                    const res = await API.post('/pppoe-reminders/process');
                    const d = res.data || {};
                    showToast(`${d.sent || 0} envoyé(s), ${d.failed || 0} échoué(s), ${d.skipped || 0} ignoré(s)`);
                    await Promise.all([this.loadStats(), this.loadHistory()]);
                } catch (e) {
                    showToast(e.message, 'error');
                } finally {
                    this.processing = false;
                }
            },

            getTimingLabel(daysBefore) {
                daysBefore = parseInt(daysBefore);
                if (daysBefore > 0) return daysBefore + ' ' + __('pppoe_reminders.days_before');
                if (daysBefore === 0) return __('pppoe_reminders.on_expiration_day');
                return Math.abs(daysBefore) + ' ' + __('pppoe_reminders.days_after');
            },

            getPreview() {
                let msg = this.ruleForm.message_template || '';
                for (const [key, value] of Object.entries(this.demoData)) {
                    msg = msg.replaceAll('{{' + key + '}}', value);
                }
                return msg;
            },

            insertVariable(name) {
                const textarea = this.$refs.templateTextarea;
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = this.ruleForm.message_template;
                const variable = '{{' + name + '}}';
                this.ruleForm.message_template = text.substring(0, start) + variable + text.substring(end);
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + variable.length, start + variable.length);
                });
            },

            copyVariable(name) {
                navigator.clipboard.writeText('{{' + name + '}}').then(() => {
                    showToast(__('pppoe_reminders.variable_copied') || 'Copié !');
                });
            }
        }
    }
</script>