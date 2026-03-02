<?php $pageTitle = __('nav.router_commands');
$currentPage = 'router-commands'; ?>

<div x-data="routerCommandsPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('router_commands.desc') ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <template x-if="activeTab === 'history'">
                <div class="flex items-center gap-2">
                    <button @click="exportCsv()" class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors" title="<?= __('router_commands.export_csv') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </button>
                    <button @click="clearHistory()" class="px-3 py-2 text-sm border border-rose-300 dark:border-rose-800 rounded-lg text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors" title="<?= __('router_commands.clear_history') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button @click="loadCommands()" class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.total') ?></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-amber-500" x-text="stats.pending || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.pending') ?></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-blue-500" x-text="stats.sent || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.sent') ?></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-emerald-500" x-text="stats.executed || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.executed') ?></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-rose-500" x-text="stats.failed || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.failed') ?></p>
        </div>
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
            <p class="text-2xl font-bold text-gray-400" x-text="stats.expired || 0"></p>
            <p class="text-xs text-gray-500 mt-1"><?= __('router_commands.expired') ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-[#30363d] mb-6">
        <button @click="activeTab = 'history'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors" :class="activeTab === 'history' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= __('router_commands.tab_history') ?>
        </button>
        <button @click="activeTab = 'send'" class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors" :class="activeTab === 'send' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'">
            <svg class="w-4 h-4 inline-block mr-1.5 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            <?= __('router_commands.tab_send') ?>
        </button>
    </div>

    <!-- ==================== TAB: HISTORY ==================== -->
    <div x-show="activeTab === 'history'" x-cloak>
        <!-- Filters + Bulk actions -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
            <select x-model="filterStatus" @change="resetSelection(); loadCommands()" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
                <option value=""><?= __('router_commands.all_statuses') ?></option>
                <option value="pending"><?= __('router_commands.pending') ?></option>
                <option value="sent"><?= __('router_commands.sent') ?></option>
                <option value="executed"><?= __('router_commands.executed') ?></option>
                <option value="failed"><?= __('router_commands.failed') ?></option>
                <option value="expired"><?= __('router_commands.expired') ?></option>
                <option value="cancelled"><?= __('router_commands.cancelled') ?></option>
            </select>
            <select x-model="filterRouter" @change="resetSelection(); loadCommands()" class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
                <option value=""><?= __('router_commands.all_routers') ?></option>
                <template x-for="r in routers" :key="r.router_id">
                    <option :value="r.router_id" x-text="r.shortname + ' (' + r.router_id + ')'"></option>
                </template>
            </select>

            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-400"><?= __('router_commands.date_from') ?></span>
                <input type="date" x-model="dateFrom" @change="resetSelection(); offset = 0; loadCommands()" class="px-2 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
            </div>
            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-400"><?= __('router_commands.date_to') ?></span>
                <input type="date" x-model="dateTo" @change="resetSelection(); offset = 0; loadCommands()" class="px-2 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
            </div>

            <!-- Bulk action bar -->
            <template x-if="selectedIds.length > 0">
                <div class="flex items-center gap-2 ml-auto">
                    <span class="text-xs text-gray-500 dark:text-gray-400" x-text="i18n.selected_count.replace(':count', selectedIds.length)"></span>
                    <button @click="deleteSelected()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/40 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        <?= __('router_commands.delete_selected') ?>
                    </button>
                </div>
            </template>
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-[#30363d] bg-gray-50 dark:bg-[#0d1117]/50">
                            <th class="w-10 px-3 py-3">
                                <input type="checkbox" :checked="allSelected" @change="toggleSelectAll()" class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-[#0d1117]" title="<?= __('router_commands.select_all') ?>">
                            </th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_id') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_router') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_type') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_description') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_status') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_created') ?></th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_executed') ?></th>
                            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider"><?= __('router_commands.col_actions') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="cmd in commands" :key="cmd.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#21262d]/30 transition-colors" :class="selectedIds.includes(cmd.id) && 'bg-blue-50/50 dark:bg-blue-900/10'">
                                <td class="w-10 px-3 py-3">
                                    <input type="checkbox" :value="cmd.id" :checked="selectedIds.includes(cmd.id)" @change="toggleSelect(cmd.id)" class="w-3.5 h-3.5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500 dark:bg-[#0d1117]">
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-500" x-text="'#' + cmd.id"></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium text-gray-900 dark:text-gray-100" x-text="cmd.router_name || cmd.router_id"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
                                        :class="{
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': cmd.command_type === 'raw',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': cmd.command_type.includes('disconnect'),
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': cmd.command_type.includes('create'),
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': cmd.command_type.includes('rate') || cmd.command_type.includes('fup'),
                                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': cmd.command_type === 'log',
                                        }"
                                        x-text="cmd.command_type.replace(/_/g, ' ')"></span>
                                </td>
                                <td class="px-4 py-3 max-w-[200px] truncate text-xs text-gray-600 dark:text-gray-400" x-text="cmd.command_description || '—'"></td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full"
                                        :class="{
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': cmd.status === 'pending',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': cmd.status === 'sent',
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': cmd.status === 'executed',
                                            'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': cmd.status === 'failed',
                                            'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': cmd.status === 'expired' || cmd.status === 'cancelled',
                                        }"
                                        x-text="statusLabel(cmd.status)"></span>
                                    <span x-show="cmd.retry_count > 0" class="ml-1 text-[10px] text-gray-400" x-text="'(x' + cmd.retry_count + ')'"></span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(cmd.created_at)"></td>
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="cmd.executed_at ? formatDate(cmd.executed_at) : '—'"></td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="viewCommand(cmd)" class="p-1 text-gray-400 hover:text-blue-500 rounded transition-colors" title="<?= __('router_commands.view') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </button>
                                        <button x-show="cmd.status === 'pending' || cmd.status === 'sent'" @click="cancelCmd(cmd)" class="p-1 text-gray-400 hover:text-amber-500 rounded transition-colors" title="<?= __('router_commands.cancel') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <button x-show="cmd.status === 'failed' || cmd.status === 'expired'" @click="retryCmd(cmd)" class="p-1 text-gray-400 hover:text-emerald-500 rounded transition-colors" title="<?= __('router_commands.retry') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        </button>
                                        <button @click="deleteCmd(cmd)" class="p-1 text-gray-400 hover:text-rose-500 rounded transition-colors" title="<?= __('router_commands.delete') ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="commands.length === 0" class="text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <p class="text-sm"><?= __('router_commands.no_commands') ?></p>
            </div>

            <!-- Pagination -->
            <div x-show="commands.length > 0" class="flex items-center justify-between px-4 py-3 border-t border-gray-200 dark:border-[#30363d]">
                <button @click="prevPage()" :disabled="offset === 0" class="px-3 py-1 text-xs border border-gray-300 dark:border-[#30363d] rounded-lg disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#30363d]"><?= __('router_commands.prev') ?></button>
                <span class="text-xs text-gray-500" x-text="'<?= __('router_commands.page') ?> ' + (Math.floor(offset / limit) + 1)"></span>
                <button @click="nextPage()" :disabled="commands.length < limit" class="px-3 py-1 text-xs border border-gray-300 dark:border-[#30363d] rounded-lg disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#30363d]"><?= __('router_commands.next') ?></button>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: SEND COMMAND ==================== -->
    <div x-show="activeTab === 'send'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Template selector -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-[#30363d]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('router_commands.select_template') ?></h3>
                        <p class="text-xs text-gray-500 mt-0.5"><?= __('router_commands.template_hint') ?></p>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="tpl in templates" :key="tpl.type">
                            <button @click="selectTemplate(tpl)" class="w-full flex items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-[#21262d]/30" :class="selectedTemplate === tpl.type && 'bg-blue-50 dark:bg-blue-900/10 border-l-2 border-blue-500'">
                                <div class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center" :class="tpl.iconBg">
                                    <svg class="w-4 h-4" :class="tpl.iconColor" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tpl.icon"/></svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="tpl.label"></p>
                                    <p class="text-[10px] text-gray-400 uppercase tracking-wider" x-text="tpl.type.replace(/_/g, ' ')"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right: Command form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-[#30363d]">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('router_commands.send_title') ?></h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Router selector -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.select_router') ?> *</label>
                            <select x-model="sendForm.router_id" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— <?= __('router_commands.select_router') ?> —</option>
                                <template x-for="r in routers" :key="r.router_id">
                                    <option :value="r.router_id" x-text="r.shortname + ' (' + r.router_id + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Dynamic fields based on template -->

                        <!-- Username field -->
                        <div x-show="templateNeedsField('username')">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_username') ?> *</label>
                            <input type="text" x-model="sendForm.params.username" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="user123">
                        </div>

                        <!-- Password field (create_pppoe) -->
                        <div x-show="selectedTemplate === 'create_pppoe'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_password') ?> *</label>
                            <input type="text" x-model="sendForm.params.password" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="pass123">
                        </div>

                        <!-- Profile field (create_pppoe) -->
                        <div x-show="selectedTemplate === 'create_pppoe'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_profile') ?></label>
                            <input type="text" x-model="sendForm.params.profile" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="default">
                        </div>

                        <!-- Rate limit field -->
                        <div x-show="selectedTemplate === 'set_rate_limit'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_rate_limit') ?> *</label>
                            <input type="text" x-model="sendForm.params.rate_limit" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="5M/10M">
                        </div>

                        <!-- Toggle user action -->
                        <div x-show="selectedTemplate === 'toggle_user'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_action') ?></label>
                            <select x-model="sendForm.params.disabled" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
                                <option value="true"><?= __('router_commands.action_disable') ?></option>
                                <option value="false"><?= __('router_commands.action_enable') ?></option>
                            </select>
                        </div>

                        <!-- Message field (log) -->
                        <div x-show="selectedTemplate === 'log'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_message') ?> *</label>
                            <input type="text" x-model="sendForm.params.message" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="Test message">
                        </div>

                        <!-- Interface field (interface_reset) -->
                        <div x-show="selectedTemplate === 'interface_reset'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_interface') ?> *</label>
                            <input type="text" x-model="sendForm.params.interface_name" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500" placeholder="ether1">
                        </div>

                        <!-- Custom command textarea -->
                        <div x-show="selectedTemplate === 'custom'">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_command') ?> *</label>
                            <textarea x-model="sendForm.command" rows="6" class="w-full px-3 py-2 text-sm font-mono border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500 resize-y" placeholder="<?= __('router_commands.custom_hint') ?>"></textarea>
                        </div>

                        <!-- Description field (always visible) -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1.5"><?= __('router_commands.field_description') ?></label>
                            <input type="text" x-model="sendForm.description" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-700 dark:text-gray-300 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Send button -->
                        <div class="flex items-center gap-3 pt-2">
                            <button @click="sendCommand()" :disabled="sending" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 disabled:opacity-50 transition-colors">
                                <svg x-show="!sending" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                <svg x-show="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                <?= __('router_commands.btn_send') ?>
                            </button>
                            <span x-show="lastSendResult" class="text-xs" :class="lastSendResult === 'success' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'" x-text="lastSendResult === 'success' ? i18n.send_success : i18n.send_error"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Command Detail Modal -->
    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="min-h-screen px-4 flex items-center justify-center">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDetail = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-2xl max-h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white" x-text="'<?= __('router_commands.detail_title') ?> #' + (detailCmd?.id || '')"></h3>
                    <button @click="showDetail = false" class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-5 space-y-4">
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div><span class="text-gray-500"><?= __('router_commands.detail_router') ?> :</span> <span class="font-medium text-gray-900 dark:text-white" x-text="detailCmd?.router_name || detailCmd?.router_id"></span></div>
                        <div><span class="text-gray-500"><?= __('router_commands.detail_type') ?> :</span> <span class="font-medium text-gray-900 dark:text-white" x-text="detailCmd?.command_type"></span></div>
                        <div><span class="text-gray-500"><?= __('router_commands.detail_priority') ?> :</span> <span class="font-medium text-gray-900 dark:text-white" x-text="detailCmd?.priority"></span></div>
                        <div><span class="text-gray-500"><?= __('router_commands.detail_status') ?> :</span> <span class="font-medium" x-text="statusLabel(detailCmd?.status)"></span></div>
                        <div><span class="text-gray-500"><?= __('router_commands.detail_created') ?> :</span> <span class="font-medium text-gray-900 dark:text-white" x-text="detailCmd?.created_at"></span></div>
                        <div><span class="text-gray-500"><?= __('router_commands.detail_executed') ?> :</span> <span class="font-medium text-gray-900 dark:text-white" x-text="detailCmd?.executed_at || '—'"></span></div>
                    </div>
                    <div x-show="detailCmd?.error_message" class="p-3 rounded-lg bg-rose-50 dark:bg-rose-900/10 border border-rose-200 dark:border-rose-800/30">
                        <p class="text-xs text-rose-700 dark:text-rose-300"><strong><?= __('router_commands.detail_error') ?> :</strong> <span x-text="detailCmd?.error_message"></span></p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 mb-2"><?= __('router_commands.detail_content') ?> :</p>
                        <pre class="p-3 rounded-lg bg-gray-900 text-gray-100 text-xs font-mono overflow-x-auto max-h-60 whitespace-pre-wrap border border-gray-700"><code x-text="detailCmd?.command_content"></code></pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function routerCommandsPage() {
    const statusLabels = <?= json_encode([
        'pending' => __('router_commands.pending'),
        'sent' => __('router_commands.sent'),
        'executed' => __('router_commands.executed'),
        'failed' => __('router_commands.failed'),
        'expired' => __('router_commands.expired'),
        'cancelled' => __('router_commands.cancelled'),
    ]) ?>;

    const i18n = <?= json_encode([
        'confirm_cancel' => __('router_commands.confirm_cancel'),
        'cancelled_success' => __('router_commands.cancelled_success'),
        'retried_success' => __('router_commands.retried_success'),
        'load_error' => __('router_commands.load_error'),
        'error' => __('router_commands.error'),
        'confirm_delete' => __('router_commands.confirm_delete'),
        'deleted_success' => __('router_commands.deleted_success'),
        'confirm_delete_selected' => __('router_commands.confirm_delete_selected'),
        'bulk_deleted_success' => __('router_commands.bulk_deleted_success'),
        'confirm_clear' => __('router_commands.confirm_clear'),
        'clear_success' => __('router_commands.clear_success'),
        'selected_count' => __('router_commands.selected_count'),
        'send_success' => __('router_commands.send_success'),
        'send_error' => __('router_commands.send_error'),
        'validation_router' => __('router_commands.validation_router'),
        'validation_command' => __('router_commands.validation_command'),
        'validation_username' => __('router_commands.validation_username'),
        'validation_password' => __('router_commands.validation_password'),
        'confirm_reboot' => __('router_commands.confirm_reboot'),
    ]) ?>;

    const templateDefs = [
        {
            type: 'custom',
            label: <?= json_encode(__('router_commands.template_custom')) ?>,
            icon: 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
            iconBg: 'bg-blue-100 dark:bg-blue-900/30',
            iconColor: 'text-blue-600 dark:text-blue-400',
            fields: ['command']
        },
        {
            type: 'disconnect_hotspot',
            label: <?= json_encode(__('router_commands.template_disconnect_hotspot')) ?>,
            icon: 'M18.364 5.636a9 9 0 010 12.728m-2.829-2.829a5 5 0 000-7.07m-4.243 2.122a1.5 1.5 0 112.121 2.121',
            iconBg: 'bg-rose-100 dark:bg-rose-900/30',
            iconColor: 'text-rose-600 dark:text-rose-400',
            fields: ['username']
        },
        {
            type: 'disconnect_pppoe',
            label: <?= json_encode(__('router_commands.template_disconnect_pppoe')) ?>,
            icon: 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M3 3l18 18',
            iconBg: 'bg-rose-100 dark:bg-rose-900/30',
            iconColor: 'text-rose-600 dark:text-rose-400',
            fields: ['username']
        },
        {
            type: 'create_pppoe',
            label: <?= json_encode(__('router_commands.template_create_pppoe')) ?>,
            icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
            iconBg: 'bg-emerald-100 dark:bg-emerald-900/30',
            iconColor: 'text-emerald-600 dark:text-emerald-400',
            fields: ['username', 'password', 'profile']
        },
        {
            type: 'delete_pppoe',
            label: <?= json_encode(__('router_commands.template_delete_pppoe')) ?>,
            icon: 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6',
            iconBg: 'bg-rose-100 dark:bg-rose-900/30',
            iconColor: 'text-rose-600 dark:text-rose-400',
            fields: ['username']
        },
        {
            type: 'set_rate_limit',
            label: <?= json_encode(__('router_commands.template_set_rate_limit')) ?>,
            icon: 'M13 10V3L4 14h7v7l9-11h-7z',
            iconBg: 'bg-amber-100 dark:bg-amber-900/30',
            iconColor: 'text-amber-600 dark:text-amber-400',
            fields: ['username', 'rate_limit']
        },
        {
            type: 'toggle_user',
            label: <?= json_encode(__('router_commands.template_toggle_user')) ?>,
            icon: 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z',
            iconBg: 'bg-purple-100 dark:bg-purple-900/30',
            iconColor: 'text-purple-600 dark:text-purple-400',
            fields: ['username', 'disabled']
        },
        {
            type: 'log',
            label: <?= json_encode(__('router_commands.template_log')) ?>,
            icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
            iconBg: 'bg-gray-100 dark:bg-gray-800',
            iconColor: 'text-gray-600 dark:text-gray-400',
            fields: ['message']
        },
        {
            type: 'reboot',
            label: <?= json_encode(__('router_commands.template_reboot')) ?>,
            icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            iconBg: 'bg-red-100 dark:bg-red-900/30',
            iconColor: 'text-red-600 dark:text-red-400',
            fields: []
        },
        {
            type: 'backup',
            label: <?= json_encode(__('router_commands.template_backup')) ?>,
            icon: 'M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4',
            iconBg: 'bg-teal-100 dark:bg-teal-900/30',
            iconColor: 'text-teal-600 dark:text-teal-400',
            fields: []
        },
        {
            type: 'dns_flush',
            label: <?= json_encode(__('router_commands.template_dns_flush')) ?>,
            icon: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9',
            iconBg: 'bg-cyan-100 dark:bg-cyan-900/30',
            iconColor: 'text-cyan-600 dark:text-cyan-400',
            fields: []
        },
        {
            type: 'interface_reset',
            label: <?= json_encode(__('router_commands.template_interface_reset')) ?>,
            icon: 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z',
            iconBg: 'bg-indigo-100 dark:bg-indigo-900/30',
            iconColor: 'text-indigo-600 dark:text-indigo-400',
            fields: ['interface_name']
        },
    ];

    return {
        // History tab state
        commands: [],
        routers: [],
        stats: {},
        filterStatus: '',
        filterRouter: '',
        dateFrom: '',
        dateTo: '',
        offset: 0,
        limit: 30,
        showDetail: false,
        detailCmd: null,
        selectedIds: [],

        // Tabs
        activeTab: 'history',

        // Send tab state
        templates: templateDefs,
        selectedTemplate: 'custom',
        sending: false,
        lastSendResult: '',
        sendForm: {
            router_id: '',
            command: '',
            description: '',
            command_type: 'raw',
            params: {
                username: '',
                password: '',
                profile: 'default',
                rate_limit: '',
                disabled: 'true',
                message: '',
                interface_name: '',
            }
        },

        get allSelected() {
            return this.commands.length > 0 && this.selectedIds.length === this.commands.length;
        },

        async init() {
            await Promise.all([this.loadCommands(), this.loadRouters()]);
        },

        buildQueryParams() {
            let params = `limit=${this.limit}&offset=${this.offset}`;
            if (this.filterStatus) params += `&status=${this.filterStatus}`;
            if (this.filterRouter) params += `&router_id=${this.filterRouter}`;
            if (this.dateFrom) params += `&date_from=${this.dateFrom}`;
            if (this.dateTo) params += `&date_to=${this.dateTo}`;
            return params;
        },

        async loadCommands() {
            try {
                const res = await API.get(`/router-commands?${this.buildQueryParams()}`);
                this.commands = res.data?.commands || [];
                this.stats = res.data?.stats || {};
            } catch (e) {
                showToast(i18n.load_error, 'error');
            }
        },

        async loadRouters() {
            try {
                const res = await API.get('/nas');
                this.routers = (res.data || []).filter(n => n.router_id);
            } catch (e) {}
        },

        viewCommand(cmd) {
            this.detailCmd = cmd;
            this.showDetail = true;
        },

        async cancelCmd(cmd) {
            if (!confirm(i18n.confirm_cancel)) return;
            try {
                await API.post(`/router-commands/${cmd.id}/cancel`);
                showToast(i18n.cancelled_success, 'success');
                this.loadCommands();
            } catch (e) {
                showToast(i18n.error, 'error');
            }
        },

        async retryCmd(cmd) {
            try {
                await API.post(`/router-commands/${cmd.id}/retry`);
                showToast(i18n.retried_success, 'success');
                this.loadCommands();
            } catch (e) {
                showToast(i18n.error, 'error');
            }
        },

        async deleteCmd(cmd) {
            if (!confirm(i18n.confirm_delete)) return;
            try {
                await API.delete(`/router-commands/${cmd.id}`);
                showToast(i18n.deleted_success, 'success');
                this.selectedIds = this.selectedIds.filter(id => id !== cmd.id);
                this.loadCommands();
            } catch (e) {
                showToast(i18n.error, 'error');
            }
        },

        async deleteSelected() {
            if (this.selectedIds.length === 0) return;
            const msg = i18n.confirm_delete_selected.replace(':count', this.selectedIds.length);
            if (!confirm(msg)) return;
            try {
                await API.post('/router-commands/delete-bulk', { ids: this.selectedIds });
                showToast(i18n.bulk_deleted_success, 'success');
                this.selectedIds = [];
                this.loadCommands();
            } catch (e) {
                showToast(i18n.error, 'error');
            }
        },

        async clearHistory() {
            if (!confirm(i18n.confirm_clear)) return;
            try {
                await API.post('/router-commands/clear-history');
                showToast(i18n.clear_success, 'success');
                this.selectedIds = [];
                this.offset = 0;
                this.loadCommands();
            } catch (e) {
                showToast(i18n.error, 'error');
            }
        },

        exportCsv() {
            let params = '';
            if (this.filterStatus) params += `&status=${this.filterStatus}`;
            if (this.filterRouter) params += `&router_id=${this.filterRouter}`;
            if (this.dateFrom) params += `&date_from=${this.dateFrom}`;
            if (this.dateTo) params += `&date_to=${this.dateTo}`;
            const url = 'api.php?route=/router-commands/export' + params;
            window.open(url, '_blank');
        },

        toggleSelect(id) {
            const idx = this.selectedIds.indexOf(id);
            if (idx === -1) {
                this.selectedIds.push(id);
            } else {
                this.selectedIds.splice(idx, 1);
            }
        },

        toggleSelectAll() {
            if (this.allSelected) {
                this.selectedIds = [];
            } else {
                this.selectedIds = this.commands.map(c => c.id);
            }
        },

        resetSelection() {
            this.selectedIds = [];
        },

        prevPage() {
            this.offset = Math.max(0, this.offset - this.limit);
            this.resetSelection();
            this.loadCommands();
        },

        nextPage() {
            this.offset += this.limit;
            this.resetSelection();
            this.loadCommands();
        },

        statusLabel(status) {
            return statusLabels[status] || status;
        },

        formatDate(dt) {
            if (!dt) return '—';
            const d = new Date(dt);
            const locale = document.documentElement.lang || 'fr-FR';
            return d.toLocaleDateString(locale) + ' ' + d.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' });
        },

        // ==================== Send tab methods ====================

        selectTemplate(tpl) {
            this.selectedTemplate = tpl.type;
            this.lastSendResult = '';
            // Reset params
            this.sendForm.params = {
                username: '',
                password: '',
                profile: 'default',
                rate_limit: '',
                disabled: 'true',
                message: '',
                interface_name: '',
            };
            this.sendForm.command = '';
            this.sendForm.description = '';
        },

        templateNeedsField(field) {
            const tpl = this.templates.find(t => t.type === this.selectedTemplate);
            return tpl && tpl.fields.includes(field);
        },

        buildCommandPayload() {
            const type = this.selectedTemplate;

            if (type === 'custom') {
                return {
                    router_id: this.sendForm.router_id,
                    command: this.sendForm.command,
                    description: this.sendForm.description || null,
                    command_type: 'raw',
                };
            }

            if (type === 'reboot') {
                return {
                    router_id: this.sendForm.router_id,
                    command: '/system reboot',
                    description: this.sendForm.description || 'Redémarrage routeur',
                    command_type: 'raw',
                };
            }

            if (type === 'backup') {
                return {
                    router_id: this.sendForm.router_id,
                    command: '/system backup save name=("nas-backup-" . [:pick [/system clock get date] 0 10])\n:log info "NAS: Backup created"',
                    description: this.sendForm.description || 'Backup système',
                    command_type: 'raw',
                };
            }

            if (type === 'dns_flush') {
                return {
                    router_id: this.sendForm.router_id,
                    command: '/ip dns cache flush\n:log info "NAS: DNS cache flushed"',
                    description: this.sendForm.description || 'Flush DNS cache',
                    command_type: 'raw',
                };
            }

            if (type === 'interface_reset') {
                const iface = this.sendForm.params.interface_name;
                return {
                    router_id: this.sendForm.router_id,
                    command: `:log info "NAS: Redemarrage interface ${iface}"\n/interface disable [find name="${iface}"]\n:delay 2s\n/interface enable [find name="${iface}"]\n:log info "NAS: Interface ${iface} redemarree"`,
                    description: this.sendForm.description || `Redémarrage interface ${iface}`,
                    command_type: 'raw',
                };
            }

            // Predefined commands that use the controller's handlePredefinedCommand
            return {
                router_id: this.sendForm.router_id,
                command: '—',
                description: this.sendForm.description || null,
                command_type: type,
                params: { ...this.sendForm.params },
            };
        },

        validateSendForm() {
            if (!this.sendForm.router_id) {
                showToast(i18n.validation_router, 'error');
                return false;
            }

            const type = this.selectedTemplate;

            if (type === 'custom' && !this.sendForm.command.trim()) {
                showToast(i18n.validation_command, 'error');
                return false;
            }

            const needsUsername = ['disconnect_hotspot', 'disconnect_pppoe', 'create_pppoe', 'delete_pppoe', 'set_rate_limit', 'toggle_user'];
            if (needsUsername.includes(type) && !this.sendForm.params.username.trim()) {
                showToast(i18n.validation_username, 'error');
                return false;
            }

            if (type === 'create_pppoe' && !this.sendForm.params.password.trim()) {
                showToast(i18n.validation_password, 'error');
                return false;
            }

            if (type === 'reboot' && !confirm(i18n.confirm_reboot)) {
                return false;
            }

            return true;
        },

        async sendCommand() {
            if (!this.validateSendForm()) return;

            this.sending = true;
            this.lastSendResult = '';

            try {
                const payload = this.buildCommandPayload();
                await API.post('/router-commands', payload);
                this.lastSendResult = 'success';
                showToast(i18n.send_success, 'success');
                // Refresh stats
                this.loadCommands();
                // Clear form but keep router selected
                const routerId = this.sendForm.router_id;
                this.selectTemplate(this.templates.find(t => t.type === this.selectedTemplate));
                this.sendForm.router_id = routerId;
            } catch (e) {
                this.lastSendResult = 'error';
                showToast(i18n.send_error, 'error');
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
