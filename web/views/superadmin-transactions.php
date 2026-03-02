<?php
/**
 * SuperAdmin - Transactions Crédits
 * Vue complète des achats de crédits et dépenses des admins
 */
?>

<div x-data="saTransactionsPage()" x-init="init()" class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?= __('superadmin.transactions_title') ?? 'Transactions Crédits' ?></h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1"><?= __('superadmin.transactions_subtitle') ?? 'Suivi des recharges et dépenses de crédits des administrateurs' ?></p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="exportCSV()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 bg-white dark:bg-[#161b22] hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <?= __('superadmin.export_csv') ?? 'Exporter CSV' ?>
            </button>
            <button @click="loadData()" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <?= __('common.refresh') ?? 'Actualiser' ?>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Total entrées -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.total_credits_in') ?? 'Crédits achetés' ?></p>
                    <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400" x-text="parseFloat(stats.total_credits_in || 0).toFixed(2)"></p>
                </div>
            </div>
        </div>

        <!-- Total sorties -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/30">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.total_credits_out') ?? 'Crédits dépensés' ?></p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400" x-text="parseFloat(stats.total_credits_out || 0).toFixed(2)"></p>
                </div>
            </div>
        </div>

        <!-- Recharges -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.recharges_count') ?? 'Recharges' ?></p>
                    <p class="text-xl font-bold text-blue-600 dark:text-blue-400" x-text="stats.recharge_count || 0"></p>
                </div>
            </div>
        </div>

        <!-- Activations -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/30">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.activations_count') ?? 'Activations' ?></p>
                    <p class="text-xl font-bold text-purple-600 dark:text-purple-400" x-text="stats.activation_count || 0"></p>
                </div>
            </div>
        </div>

        <!-- Ajustements -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
            <div class="flex items-center gap-3">
                <div class="p-2 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.adjustments_count') ?? 'Ajustements' ?></p>
                    <p class="text-xl font-bold text-amber-600 dark:text-amber-400" x-text="stats.adjustment_count || 0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Recherche -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('common.search') ?? 'Rechercher' ?></label>
                <input type="text" x-model="filters.search" @input.debounce.400ms="applyFilters()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                    placeholder="<?= __('superadmin.search_transactions') ?? 'Nom, username, description...' ?>">
            </div>

            <!-- Filtre type -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('credits.col_type') ?? 'Type' ?></label>
                <select x-model="filters.type" @change="applyFilters()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value=""><?= __('common.all') ?? 'Tous' ?></option>
                    <option value="recharge"><?= __('credits.type_recharge') ?? 'Recharge' ?></option>
                    <option value="module_activation"><?= __('credits.type_module_activation') ?? 'Activation module' ?></option>
                    <option value="module_renewal"><?= __('credits.type_module_renewal') ?? 'Renouvellement' ?></option>
                    <option value="adjustment"><?= __('credits.type_adjustment') ?? 'Ajustement' ?></option>
                    <option value="refund"><?= __('credits.type_refund') ?? 'Remboursement' ?></option>
                </select>
            </div>

            <!-- Filtre admin -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('superadmin.filter_admin') ?? 'Administrateur' ?></label>
                <select x-model="filters.admin_id" @change="applyFilters()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <option value=""><?= __('common.all') ?? 'Tous' ?></option>
                    <template x-for="admin in admins" :key="admin.id">
                        <option :value="admin.id" x-text="admin.full_name || admin.username"></option>
                    </template>
                </select>
            </div>

            <!-- Date début -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('superadmin.date_from') ?? 'Du' ?></label>
                <input type="date" x-model="filters.date_from" @change="applyFilters()"
                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Date fin -->
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('superadmin.date_to') ?? 'Au' ?></label>
                <div class="flex gap-2">
                    <input type="date" x-model="filters.date_to" @change="applyFilters()"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <button @click="clearFilters()" class="px-3 py-2 text-sm text-gray-500 hover:text-red-600 border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors" title="<?= __('common.clear_filters') ?? 'Effacer filtres' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des transactions -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
        <!-- Loading -->
        <div x-show="loading" class="p-8 text-center">
            <div class="inline-flex items-center gap-2 text-gray-500">
                <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                <?= __('common.loading') ?? 'Chargement...' ?>
            </div>
        </div>

        <div x-show="!loading">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]/30">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('credits.col_date') ?? 'Date' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_admin') ?? 'Admin' ?></th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('credits.col_type') ?? 'Type' ?></th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('credits.col_amount') ?? 'Montant' ?></th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('credits.col_balance') ?? 'Solde après' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('credits.col_description') ?? 'Description' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_created_by') ?? 'Par' ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="tx in transactions" :key="tx.id">
                            <tr class="hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/50 transition-colors">
                                <!-- Date -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-gray-900 dark:text-gray-100 text-xs" x-text="new Date(tx.created_at).toLocaleDateString()"></p>
                                    <p class="text-gray-400 text-[10px]" x-text="new Date(tx.created_at).toLocaleTimeString()"></p>
                                </td>

                                <!-- Admin -->
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center text-white text-[10px] font-semibold flex-shrink-0"
                                            x-text="(tx.username || '?').charAt(0).toUpperCase()"></div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-gray-100 text-xs truncate" x-text="tx.full_name || tx.username"></p>
                                            <p class="text-[10px] text-gray-400 truncate" x-text="'@' + tx.username"></p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wide"
                                        :class="{
                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': tx.type === 'recharge',
                                            'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400': tx.type === 'module_activation',
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': tx.type === 'module_renewal',
                                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400': tx.type === 'adjustment',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': tx.type === 'refund'
                                        }"
                                        x-text="typeLabels[tx.type] || tx.type"></span>
                                </td>

                                <!-- Montant -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <span class="font-semibold text-sm"
                                        :class="parseFloat(tx.amount) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'"
                                        x-text="(parseFloat(tx.amount) >= 0 ? '+' : '') + parseFloat(tx.amount).toFixed(2)"></span>
                                </td>

                                <!-- Solde après -->
                                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-600 dark:text-gray-400" x-text="parseFloat(tx.balance_after).toFixed(2)"></td>

                                <!-- Description -->
                                <td class="px-4 py-3">
                                    <p class="text-gray-600 dark:text-gray-400 text-xs max-w-[200px] truncate" x-text="tx.description || '-'" :title="tx.description"></p>
                                    <p x-show="tx.reference_id" class="text-[10px] text-gray-400 mt-0.5" x-text="'Ref: ' + (tx.reference_id || '')"></p>
                                </td>

                                <!-- Par -->
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span x-show="tx.created_by_username" x-text="tx.created_by_username"></span>
                                    <span x-show="!tx.created_by_username" class="text-gray-300 dark:text-gray-600">—</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div x-show="transactions.length === 0 && !loading" class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('credits.no_transactions') ?? 'Aucune transaction' ?></p>
            </div>

            <!-- Pagination -->
            <div x-show="totalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <span x-text="'<?= __('common.showing') ?? 'Affichage' ?> ' + ((page - 1) * perPage + 1) + '-' + Math.min(page * perPage, total) + ' <?= __('common.of') ?? 'sur' ?> ' + total"></span>
                </p>
                <div class="flex items-center gap-1">
                    <button @click="goToPage(1)" :disabled="page <= 1"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    </button>
                    <button @click="goToPage(page - 1)" :disabled="page <= 1"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                        <?= __('common.previous') ?? 'Préc.' ?>
                    </button>

                    <template x-for="p in paginationRange()" :key="p">
                        <button @click="p !== '...' && goToPage(p)"
                            class="px-3 py-1.5 text-xs rounded-md border transition-colors"
                            :class="p === page ? 'bg-blue-600 text-white border-blue-600' : (p === '...' ? 'border-transparent cursor-default' : 'border-gray-300 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#1c2128]')"
                            x-text="p"></button>
                    </template>

                    <button @click="goToPage(page + 1)" :disabled="page >= totalPages"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                        <?= __('common.next') ?? 'Suiv.' ?>
                    </button>
                    <button @click="goToPage(totalPages)" :disabled="page >= totalPages"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function saTransactionsPage() {
    return {
        transactions: [],
        admins: [],
        stats: {},
        loading: true,
        page: 1,
        perPage: 25,
        total: 0,
        totalPages: 0,
        filters: {
            search: '',
            type: '',
            admin_id: '',
            date_from: '',
            date_to: ''
        },
        typeLabels: {
            recharge: '<?= __('credits.type_recharge') ?? 'Recharge' ?>',
            module_activation: '<?= __('credits.type_module_activation') ?? 'Activation' ?>',
            module_renewal: '<?= __('credits.type_module_renewal') ?? 'Renouvellement' ?>',
            adjustment: '<?= __('credits.type_adjustment') ?? 'Ajustement' ?>',
            refund: '<?= __('credits.type_refund') ?? 'Remboursement' ?>'
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.page,
                    per_page: this.perPage
                });

                if (this.filters.search) params.set('search', this.filters.search);
                if (this.filters.type) params.set('type', this.filters.type);
                if (this.filters.admin_id) params.set('admin_id', this.filters.admin_id);
                if (this.filters.date_from) params.set('date_from', this.filters.date_from);
                if (this.filters.date_to) params.set('date_to', this.filters.date_to);

                const res = await fetch(`api.php?route=/superadmin/credit-transactions&${params}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();

                if (data.success) {
                    this.transactions = data.data.transactions;
                    this.total = data.data.total;
                    this.totalPages = data.data.total_pages;
                    this.stats = data.data.stats;
                    this.admins = data.data.admins;
                }
            } catch (e) {
                console.error('Erreur chargement transactions:', e);
            } finally {
                this.loading = false;
            }
        },

        applyFilters() {
            this.page = 1;
            this.loadData();
        },

        clearFilters() {
            this.filters = { search: '', type: '', admin_id: '', date_from: '', date_to: '' };
            this.applyFilters();
        },

        goToPage(p) {
            if (p < 1 || p > this.totalPages) return;
            this.page = p;
            this.loadData();
        },

        paginationRange() {
            const range = [];
            const delta = 2;
            const left = Math.max(2, this.page - delta);
            const right = Math.min(this.totalPages - 1, this.page + delta);

            range.push(1);
            if (left > 2) range.push('...');
            for (let i = left; i <= right; i++) range.push(i);
            if (right < this.totalPages - 1) range.push('...');
            if (this.totalPages > 1) range.push(this.totalPages);
            return range;
        },

        exportCSV() {
            const params = new URLSearchParams({ per_page: 10000 });
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.type) params.set('type', this.filters.type);
            if (this.filters.admin_id) params.set('admin_id', this.filters.admin_id);
            if (this.filters.date_from) params.set('date_from', this.filters.date_from);
            if (this.filters.date_to) params.set('date_to', this.filters.date_to);

            fetch(`api.php?route=/superadmin/credit-transactions&${params}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const rows = data.data.transactions;
                const headers = ['Date', 'Admin', 'Username', 'Type', 'Montant', 'Solde après', 'Description', 'Référence'];
                const csv = [
                    headers.join(';'),
                    ...rows.map(tx => [
                        tx.created_at,
                        (tx.full_name || '').replace(/;/g, ','),
                        tx.username,
                        tx.type,
                        tx.amount,
                        tx.balance_after,
                        (tx.description || '').replace(/;/g, ','),
                        tx.reference_id || ''
                    ].join(';'))
                ].join('\n');

                const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `transactions-credits-${new Date().toISOString().slice(0, 10)}.csv`;
                a.click();
                URL.revokeObjectURL(url);
            });
        }
    };
}
</script>
