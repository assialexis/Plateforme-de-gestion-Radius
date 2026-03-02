<?php $pageTitle = __('superadmin.paygate_withdrawals_title') ?? 'Retraits Paygate';
$currentPage = 'superadmin-withdrawals'; ?>

<div x-data="withdrawalsPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.paygate_withdrawals_subtitle') ?? 'Gérer les demandes de retrait des administrateurs' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading" class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.total_collected') ?? 'Total collecté' ?></p>
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="formatMoney(stats.total_collected || 0)"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.status_pending') ?? 'En attente' ?></p>
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1" x-text="formatMoney(stats.pending_withdrawals_amount || 0)"></p>
                <p class="text-xs text-gray-500" x-text="(stats.pending_withdrawals_count || 0) + ' demande(s)'"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.total_withdrawn') ?? 'Total retiré' ?></p>
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1" x-text="formatMoney(stats.total_withdrawn || 0)"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.commission') ?? 'Commission totale' ?></p>
                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1" x-text="formatMoney(stats.total_commission || 0)"></p>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <select x-model="filterStatus" @change="loadWithdrawals()"
                class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#161b22] text-gray-900 dark:text-gray-100">
                <option value=""><?= __('common.all') ?? 'Tous les statuts' ?></option>
                <option value="pending"><?= __('paygate.status_pending') ?? 'En attente' ?></option>
                <option value="approved"><?= __('paygate.status_approved') ?? 'Approuvé' ?></option>
                <option value="completed"><?= __('paygate.status_completed') ?? 'Complété' ?></option>
                <option value="rejected"><?= __('paygate.status_rejected') ?? 'Rejeté' ?></option>
                <option value="cancelled"><?= __('paygate.status_cancelled') ?? 'Annulé' ?></option>
            </select>
        </div>

        <!-- Withdrawals Table -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Admin</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.col_amount') ?? 'Montant' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.commission') ?? 'Commission' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.net_amount') ?? 'Net' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.withdrawal_method') ?? 'Méthode' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.col_status') ?? 'Statut' ?></th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('paygate.col_date') ?? 'Date' ?></th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]/50">
                        <template x-for="w in withdrawals" :key="w.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#161b22]/50">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900 dark:text-gray-100" x-text="w.admin_display_name || w.admin_username"></div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-gray-900 dark:text-gray-100" x-text="formatMoney(w.amount_requested) + ' ' + w.currency"></td>
                                <td class="px-4 py-3 text-gray-500" x-text="formatMoney(w.commission_amount) + ' (' + w.commission_rate + '%)'"></td>
                                <td class="px-4 py-3 font-semibold text-emerald-600 dark:text-emerald-400" x-text="formatMoney(w.amount_net) + ' ' + w.currency"></td>
                                <td class="px-4 py-3">
                                    <span class="text-xs" x-text="w.payment_method === 'mobile_money' ? 'Mobile Money' : 'Virement bancaire'"></span>
                                    <div class="text-xs text-gray-400" x-text="w.payment_details?.phone || w.payment_details?.account_number || ''"></div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400': w.status === 'pending',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400': w.status === 'approved',
                                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400': w.status === 'completed',
                                            'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400': w.status === 'rejected',
                                            'bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-400': w.status === 'cancelled'
                                        }" x-text="statusLabel(w.status)"></span>
                                    <div x-show="w.transfer_reference" class="text-xs text-gray-400 mt-1" x-text="'Réf: ' + w.transfer_reference"></div>
                                    <div x-show="w.superadmin_note" class="text-xs text-gray-400 mt-1" x-text="w.superadmin_note"></div>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="formatDate(w.requested_at)"></td>
                                <td class="px-4 py-3 text-right">
                                    <button @click="showDetailModal(w)" class="px-3 py-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 border border-indigo-300 dark:border-indigo-600 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-500/10">
                                        <i class="fas fa-eye mr-1"></i> <?= __('common.view') ?? 'Voir' ?>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="withdrawals.length === 0">
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                                <?= __('common.no_data') ?? 'Aucune donnée' ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="totalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <p class="text-sm text-gray-500" x-text="'Page ' + currentPage + ' / ' + totalPages + ' (' + total + ' résultats)'"></p>
                <div class="flex gap-2">
                    <button @click="currentPage--; loadWithdrawals()" :disabled="currentPage <= 1"
                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50">&laquo; <?= __('common.previous') ?? 'Précédent' ?></button>
                    <button @click="currentPage++; loadWithdrawals()" :disabled="currentPage >= totalPages"
                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 rounded-lg disabled:opacity-50"><?= __('common.next') ?? 'Suivant' ?> &raquo;</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail / Validation Modal -->
    <div x-show="showDetail" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showDetail = false">
        <div class="absolute inset-0 bg-black/40" @click="showDetail = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-2xl max-w-lg w-full border border-gray-200 dark:border-[#30363d] p-6 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"><?= __('superadmin.paygate_withdrawal_details') ?? 'Détails de la demande de retrait' ?></h3>
                <button @click="showDetail = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <template x-if="detailTarget">
                <div class="space-y-4">
                    <!-- Status badge -->
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                            :class="{
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-400': detailTarget.status === 'pending',
                                'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-400': detailTarget.status === 'approved',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-400': detailTarget.status === 'completed',
                                'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400': detailTarget.status === 'rejected',
                                'bg-gray-100 text-gray-800 dark:bg-gray-500/10 dark:text-gray-400': detailTarget.status === 'cancelled'
                            }" x-text="statusLabel(detailTarget.status)"></span>
                        <span class="text-xs text-gray-500" x-text="formatDate(detailTarget.requested_at)"></span>
                    </div>

                    <!-- Admin info -->
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-4 space-y-2">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.paygate_admin_info') ?? 'Administrateur' ?></h4>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-indigo-100 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm"
                                x-text="(detailTarget.admin_display_name || detailTarget.admin_username || '?').substring(0, 2).toUpperCase()"></div>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-gray-100" x-text="detailTarget.admin_display_name || detailTarget.admin_username"></p>
                                <p class="text-xs text-gray-500" x-text="detailTarget.admin_username"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Amount details -->
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"><?= __('superadmin.paygate_amount_details') ?? 'Montants' ?></h4>
                        <div class="grid grid-cols-3 gap-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500"><?= __('paygate.col_amount') ?? 'Demandé' ?></p>
                                <p class="text-lg font-bold text-gray-900 dark:text-gray-100" x-text="formatMoney(detailTarget.amount_requested)"></p>
                                <p class="text-[10px] text-gray-400" x-text="detailTarget.currency || 'XOF'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500"><?= __('paygate.commission') ?? 'Commission' ?></p>
                                <p class="text-lg font-bold text-red-500" x-text="'−' + formatMoney(detailTarget.commission_amount)"></p>
                                <p class="text-[10px] text-gray-400" x-text="detailTarget.commission_rate + '%'"></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500"><?= __('paygate.net_amount') ?? 'Net à verser' ?></p>
                                <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400" x-text="formatMoney(detailTarget.amount_net)"></p>
                                <p class="text-[10px] text-gray-400" x-text="detailTarget.currency || 'XOF'"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment details -->
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-4">
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase mb-2"><?= __('superadmin.paygate_payment_info') ?? 'Informations de paiement' ?></h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500"><?= __('paygate.withdrawal_method') ?? 'Méthode' ?></span>
                                <span class="font-medium text-gray-900 dark:text-gray-100" x-text="detailTarget.payment_method === 'mobile_money' ? 'Mobile Money' : 'Virement bancaire'"></span>
                            </div>
                            <template x-if="detailTarget.payment_method === 'mobile_money'">
                                <div class="flex justify-between">
                                    <span class="text-gray-500"><?= __('paygate.withdrawal_phone') ?? 'Téléphone' ?></span>
                                    <span class="font-medium font-mono text-gray-900 dark:text-gray-100" x-text="detailTarget.payment_details?.phone || '—'"></span>
                                </div>
                            </template>
                            <template x-if="detailTarget.payment_method === 'bank_transfer'">
                                <div class="space-y-2">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500"><?= __('paygate.withdrawal_bank_name') ?? 'Banque' ?></span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="detailTarget.payment_details?.bank_name || '—'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500"><?= __('paygate.withdrawal_account') ?? 'N° compte' ?></span>
                                        <span class="font-medium font-mono text-gray-900 dark:text-gray-100" x-text="detailTarget.payment_details?.account_number || '—'"></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500"><?= __('paygate.withdrawal_account_name') ?? 'Titulaire' ?></span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100" x-text="detailTarget.payment_details?.account_name || '—'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Admin note -->
                    <div x-show="detailTarget.admin_note" class="bg-amber-50 dark:bg-amber-500/5 border border-amber-200 dark:border-amber-500/20 rounded-lg p-3">
                        <p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-1"><i class="fas fa-sticky-note mr-1"></i> <?= __('superadmin.paygate_admin_note') ?? 'Note de l\'admin' ?></p>
                        <p class="text-sm text-amber-800 dark:text-amber-300" x-text="detailTarget.admin_note"></p>
                    </div>

                    <!-- Transfer reference (for completed) -->
                    <div x-show="detailTarget.transfer_reference" class="bg-emerald-50 dark:bg-emerald-500/5 border border-emerald-200 dark:border-emerald-500/20 rounded-lg p-3">
                        <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-400 mb-1"><i class="fas fa-check-circle mr-1"></i> <?= __('superadmin.paygate_transfer_reference') ?? 'Référence du virement' ?></p>
                        <p class="text-sm font-mono text-emerald-800 dark:text-emerald-300" x-text="detailTarget.transfer_reference"></p>
                    </div>

                    <!-- Superadmin note -->
                    <div x-show="detailTarget.superadmin_note" class="bg-blue-50 dark:bg-blue-500/5 border border-blue-200 dark:border-blue-500/20 rounded-lg p-3">
                        <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1"><i class="fas fa-comment mr-1"></i> <?= __('superadmin.paygate_your_note') ?? 'Votre note' ?></p>
                        <p class="text-sm text-blue-800 dark:text-blue-300" x-text="detailTarget.superadmin_note"></p>
                    </div>

                    <!-- Action: PENDING → Approve or Reject -->
                    <div x-show="detailTarget.status === 'pending'" class="border-t border-gray-200 dark:border-[#30363d] pt-4 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('superadmin.paygate_superadmin_note') ?? 'Note (optionnel)' ?></label>
                            <textarea x-model="actionNote" rows="2" placeholder="<?= __('superadmin.paygate_note_placeholder') ?? 'Ajouter une note...' ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button @click="approveWithdrawal()" :disabled="processing"
                                class="flex-1 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors">
                                <i class="fas fa-check mr-1"></i>
                                <span x-show="!processing"><?= __('common.approve') ?? 'Approuver' ?></span>
                                <span x-show="processing"><?= __('common.loading') ?? '...' ?></span>
                            </button>
                            <button @click="rejectWithdrawal()" :disabled="processing"
                                class="flex-1 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors">
                                <i class="fas fa-times mr-1"></i>
                                <span x-show="!processing"><?= __('common.reject') ?? 'Rejeter' ?></span>
                                <span x-show="processing"><?= __('common.loading') ?? '...' ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- Action: APPROVED → Complete -->
                    <div x-show="detailTarget.status === 'approved'" class="border-t border-gray-200 dark:border-[#30363d] pt-4 space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('superadmin.paygate_transfer_reference') ?? 'Référence du virement' ?> *
                            </label>
                            <input type="text" x-model="completeRef" placeholder="Ex: TRF-12345"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('superadmin.paygate_superadmin_note') ?? 'Note (optionnel)' ?></label>
                            <textarea x-model="actionNote" rows="2"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100"></textarea>
                        </div>
                        <button @click="completeWithdrawal()" :disabled="!completeRef || processing"
                            class="w-full py-2.5 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 transition-colors">
                            <i class="fas fa-check-double mr-1"></i>
                            <span x-show="!processing"><?= __('superadmin.paygate_confirm_transfer') ?? 'Confirmer le virement' ?></span>
                            <span x-show="processing"><?= __('common.loading') ?? '...' ?></span>
                        </button>
                    </div>

                    <!-- Close button for terminal statuses -->
                    <div x-show="['completed','rejected','cancelled'].includes(detailTarget.status)" class="border-t border-gray-200 dark:border-[#30363d] pt-4">
                        <button @click="showDetail = false" class="w-full py-2 text-sm text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d]">
                            <?= __('common.close') ?? 'Fermer' ?>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function withdrawalsPage() {
    return {
        loading: true,
        withdrawals: [],
        stats: {},
        filterStatus: '',
        currentPage: 1,
        totalPages: 1,
        total: 0,

        showDetail: false,
        detailTarget: null,
        actionNote: '',
        completeRef: '',
        processing: false,

        async init() {
            await Promise.all([this.loadWithdrawals(), this.loadStats()]);
            this.loading = false;
        },

        async loadWithdrawals() {
            try {
                let url = `api.php?route=/superadmin/paygate/withdrawals&page=${this.currentPage}`;
                if (this.filterStatus) url += `&status=${this.filterStatus}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.withdrawals = data.data.withdrawals;
                    this.totalPages = data.data.total_pages;
                    this.total = data.data.total;
                }
            } catch (e) { showToast('Erreur chargement', 'error'); }
        },

        async loadStats() {
            try {
                const res = await fetch('api.php?route=/superadmin/paygate/stats', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.stats = data.data;
            } catch (e) { console.error(e); }
        },

        showDetailModal(w) {
            this.detailTarget = w;
            this.actionNote = '';
            this.completeRef = '';
            this.processing = false;
            this.showDetail = true;
        },

        async approveWithdrawal() {
            this.processing = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/paygate/withdrawals/${this.detailTarget.id}/approve`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ note: this.actionNote })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.showDetail = false;
                    await this.loadWithdrawals();
                    await this.loadStats();
                }
            } catch (e) { showToast('Erreur', 'error'); }
            this.processing = false;
        },

        async completeWithdrawal() {
            if (!this.completeRef) return;
            this.processing = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/paygate/withdrawals/${this.detailTarget.id}/complete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ transfer_reference: this.completeRef, note: this.actionNote })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.showDetail = false;
                    await this.loadWithdrawals();
                    await this.loadStats();
                }
            } catch (e) { showToast('Erreur', 'error'); }
            this.processing = false;
        },

        async rejectWithdrawal() {
            this.processing = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/paygate/withdrawals/${this.detailTarget.id}/reject`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ reason: this.actionNote })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) {
                    this.showDetail = false;
                    await this.loadWithdrawals();
                    await this.loadStats();
                }
            } catch (e) { showToast('Erreur', 'error'); }
            this.processing = false;
        },

        statusLabel(status) {
            const labels = { pending: 'En attente', approved: 'Approuvé', completed: 'Complété', rejected: 'Rejeté', cancelled: 'Annulé' };
            return labels[status] || status;
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
