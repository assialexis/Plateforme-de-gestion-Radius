<?php
/**
 * Vue Gestion de la Fidélité
 * Système de récompense basé sur le numéro de téléphone
 */
?>

<div x-data="loyaltyManager()" x-init="init()">
    <!-- En-tête -->
    <div class="mb-6 flex justify-between items-start">
        <div>
            <h1 class="text-2xl font-bold text-gray-800"><?= __('loyalty.title') ?></h1>
            <p class="text-gray-600"><?= __('loyalty.subtitle') ?></p>
        </div>
        <div class="flex gap-2 items-center">
            <!-- Auto-record toggle -->
            <button @click="toggleAutoRecord()"
                    :class="autoRecordEnabled ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-500 hover:bg-gray-600'"
                    class="text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                <svg x-show="autoRecordEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <svg x-show="!autoRecordEnabled" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span x-text="autoRecordEnabled ? '<?= addslashes(__('loyalty.auto_record_on')) ?>' : '<?= addslashes(__('loyalty.auto_record_off')) ?>'"></span>
            </button>

            <button @click="resetLoyalty()" :disabled="resetting"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 disabled:opacity-50">
                <svg class="w-5 h-5" :class="resetting ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span x-text="resetting ? '<?= addslashes(__('loyalty.resetting')) ?>' : '<?= addslashes(__('loyalty.reset')) ?>'"></span>
            </button>
            <button @click="importTransactions()" :disabled="importing"
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 disabled:opacity-50">
                <svg class="w-5 h-5" :class="importing ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span x-text="importing ? '<?= addslashes(__('loyalty.importing')) ?>' : '<?= addslashes(__('loyalty.import_transactions')) ?>'"></span>
            </button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?= __('loyalty.loyal_customers') ?></p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.total_customers || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?= __('loyalty.total_spent') ?></p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="formatCurrency(stats.total_spent || 0)"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?= __('loyalty.rewards_given') ?></p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.total_rewards_given || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500"><?= __('loyalty.pending') ?></p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.pending_rewards || 0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'customers'"
                        :class="activeTab === 'customers' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <?= __('loyalty.tab_customers') ?>
                </button>
                <button @click="activeTab = 'rules'"
                        :class="activeTab === 'rules' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <?= __('loyalty.tab_rules') ?>
                </button>
                <button @click="activeTab = 'rewards'"
                        :class="activeTab === 'rewards' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    <?= __('loyalty.tab_rewards') ?>
                </button>
            </nav>
        </div>
    </div>

    <!-- Contenu Clients -->
    <div x-show="activeTab === 'customers'" x-cloak>
        <!-- Recherche -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" x-model="customerSearch" @input.debounce.300ms="loadCustomers()"
                           placeholder="<?= __('loyalty.search_placeholder') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select x-model="customerSort" @change="loadCustomers()"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        <option value="total_purchases"><?= __('loyalty.sort_purchases') ?></option>
                        <option value="total_spent"><?= __('loyalty.sort_spent') ?></option>
                        <option value="rewards_earned"><?= __('loyalty.sort_rewards') ?></option>
                        <option value="last_purchase_at"><?= __('loyalty.sort_last_purchase') ?></option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Liste des clients -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_client') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_purchases') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_total_spent') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_rewards') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_progress') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_last_purchase') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="customer in customers" :key="customer.id">
                        <tr class="hover:bg-gray-50 cursor-pointer" @click="showCustomerDetails(customer)">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900" x-text="customer.phone"></div>
                                        <div class="text-sm text-gray-500" x-text="customer.customer_name || 'Client'"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-blue-100 text-blue-800"
                                      x-text="customer.total_purchases"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(customer.total_spent)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800"
                                      x-text="customer.rewards_earned"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full" :style="`width: ${calculateProgress(customer)}%`"></div>
                                </div>
                                <span class="text-xs text-gray-500" x-text="`${calculateProgress(customer)}%`"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(customer.last_purchase_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right" @click.stop>
                                <button @click="deleteCustomer(customer)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="<?= __('common.delete') ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="customers.length === 0">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            <?= __('loyalty.no_customer_found') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex items-center justify-between" x-show="customersPagination.total_pages > 1">
            <div class="text-sm text-gray-700">
                Page <span x-text="customersPagination.page"></span> sur <span x-text="customersPagination.total_pages"></span>
            </div>
            <div class="flex gap-2">
                <button @click="loadCustomers(customersPagination.page - 1)"
                        :disabled="customersPagination.page <= 1"
                        class="px-3 py-1 border rounded disabled:opacity-50">
                    <?= __('common.previous') ?>
                </button>
                <button @click="loadCustomers(customersPagination.page + 1)"
                        :disabled="customersPagination.page >= customersPagination.total_pages"
                        class="px-3 py-1 border rounded disabled:opacity-50">
                    <?= __('common.next') ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Contenu Règles -->
    <div x-show="activeTab === 'rules'" x-cloak>
        <div class="mb-4 flex justify-end">
            <button @click="openRuleModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?= __('loyalty.new_rule') ?>
            </button>
        </div>

        <div class="grid gap-4">
            <template x-for="rule in rules" :key="rule.id">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3">
                                <h3 class="text-lg font-semibold text-gray-900" x-text="rule.name"></h3>
                                <span :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                      class="px-2 py-1 text-xs rounded-full"
                                      x-text="rule.is_active ? '<?= addslashes(__('module.active')) ?>' : '<?= addslashes(__('module.inactive')) ?>'"></span>
                            </div>
                            <p class="text-gray-600 mt-1" x-text="rule.description || '<?= addslashes(__('misc.no_description')) ?>'"></p>

                            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div>
                                    <span class="text-xs text-gray-500"><?= __('common.type') ?></span>
                                    <p class="font-medium" x-text="getRuleTypeLabel(rule.rule_type)"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500"><?= __('loyalty.threshold') ?></span>
                                    <p class="font-medium" x-text="rule.threshold_value + ' ' + getRuleThresholdUnit(rule.rule_type)"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500"><?= __('loyalty.reward') ?></span>
                                    <p class="font-medium" x-text="getRewardTypeLabel(rule.reward_type)"></p>
                                </div>
                                <div>
                                    <span class="text-xs text-gray-500"><?= __('common.profile') ?></span>
                                    <p class="font-medium" x-text="rule.profile_name || '<?= addslashes(__('loyalty.last_purchase')) ?>'"></p>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <button @click="openRuleModal(rule)" class="p-2 text-gray-400 hover:text-blue-600" title="<?= __('common.edit') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                            <button @click="toggleRule(rule)" :class="rule.is_active ? 'text-orange-600' : 'text-green-600'" class="p-2 hover:opacity-80" :title="rule.is_active ? '<?= addslashes(__('common.deactivate')) ?>' : '<?= addslashes(__('common.activate')) ?>'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                </svg>
                            </button>
                            <button @click="deleteRule(rule)" class="p-2 text-gray-400 hover:text-red-600" title="<?= __('common.delete') ?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="rules.length === 0" class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <?= __('loyalty.no_rule_found') ?>
            </div>
        </div>
    </div>

    <!-- Contenu Récompenses -->
    <div x-show="activeTab === 'rewards'" x-cloak>
        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex gap-4">
                <select x-model="rewardStatusFilter" @change="loadRewards()"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    <option value=""><?= __('common.all_statuses') ?></option>
                    <option value="pending"><?= __('loyalty.status_pending') ?></option>
                    <option value="claimed"><?= __('loyalty.status_claimed') ?></option>
                    <option value="expired"><?= __('loyalty.status_expired') ?></option>
                </select>
            </div>
        </div>

        <!-- Liste des récompenses -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.table_client') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('loyalty.rule') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('common.type') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voucher</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('common.status') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('common.date') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider"><?= __('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="reward in rewards" :key="reward.id">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="reward.phone"></div>
                                <div class="text-sm text-gray-500" x-text="reward.customer_name || ''"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="reward.rule_name || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="getRewardTypeLabel(reward.reward_type)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span x-show="reward.voucher_code" class="font-mono text-sm bg-green-100 text-green-800 px-2 py-1 rounded" x-text="reward.voucher_code"></span>
                                <span x-show="!reward.voucher_code" class="text-orange-500 text-sm">
                                    <i class="fas fa-exclamation-triangle mr-1"></i><?= __('loyalty.profile_not_configured') ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="{
                                    'bg-yellow-100 text-yellow-800': reward.status === 'pending',
                                    'bg-green-100 text-green-800': reward.status === 'claimed',
                                    'bg-gray-100 text-gray-800': reward.status === 'expired',
                                    'bg-red-100 text-red-800': reward.status === 'cancelled'
                                }" class="px-2 py-1 text-xs rounded-full" x-text="getStatusLabel(reward.status)"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(reward.created_at)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <button @click="deleteReward(reward)" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors" title="<?= __('common.delete') ?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="rewards.length === 0">
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            <?= __('loyalty.no_reward_found') ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Règle -->
    <div x-show="showRuleModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showRuleModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-4" x-text="editingRule ? '<?= addslashes(__('loyalty.edit_rule')) ?>' : '<?= addslashes(__('loyalty.new_rule')) ?>'"></h2>

                <form @submit.prevent="saveRule()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('loyalty.rule_name') ?></label>
                            <input type="text" x-model="ruleForm.name" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="<?= __('loyalty.rule_name_placeholder') ?>">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('common.description') ?></label>
                            <textarea x-model="ruleForm.description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="<?= __('loyalty.rule_description_placeholder') ?>"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('loyalty.rule_type') ?></label>
                                <select x-model="ruleForm.rule_type"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="purchase_count"><?= __('loyalty.rule_type_purchase_count') ?></option>
                                    <option value="amount_spent"><?= __('loyalty.rule_type_amount_spent') ?></option>
                                    <option value="points"><?= __('loyalty.rule_type_points') ?></option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('loyalty.threshold') ?></label>
                                <input type="number" x-model.number="ruleForm.threshold_value" min="1" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="5">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('loyalty.reward_type') ?></label>
                                <select x-model="ruleForm.reward_type"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value="free_voucher"><?= __('loyalty.reward_free_voucher') ?></option>
                                    <option value="discount_percent"><?= __('loyalty.reward_discount_percent') ?></option>
                                    <option value="bonus_time"><?= __('loyalty.reward_bonus_time') ?></option>
                                    <option value="bonus_data"><?= __('loyalty.reward_bonus_data') ?></option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1"><?= __('loyalty.voucher_profile') ?></label>
                                <select x-model="ruleForm.reward_profile_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                    <option value=""><?= __('loyalty.same_as_last_purchase') ?></option>
                                    <template x-for="profile in profiles" :key="profile.id">
                                        <option :value="profile.id" x-text="profile.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" x-model="ruleForm.is_active"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <span class="text-sm text-gray-700"><?= __('loyalty.rule_active') ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showRuleModal = false"
                                class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                            <?= __('common.cancel') ?>
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                            <span x-text="editingRule ? '<?= addslashes(__('common.edit')) ?>' : '<?= addslashes(__('common.create')) ?>'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Détails Client -->
    <div x-show="showCustomerModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="showCustomerModal = false">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6" x-show="selectedCustomer">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold"><?= __('loyalty.customer_details') ?></h2>
                    <button @click="showCustomerModal = false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Info client -->
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <span class="text-xs text-gray-500"><?= __('common.phone') ?></span>
                            <p class="font-medium" x-text="selectedCustomer?.phone"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500"><?= __('common.name') ?></span>
                            <p class="font-medium" x-text="selectedCustomer?.customer_name || '<?= addslashes(__('loyalty.not_provided')) ?>'"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500"><?= __('loyalty.total_purchases') ?></span>
                            <p class="font-medium" x-text="selectedCustomer?.total_purchases"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500"><?= __('loyalty.total_spent') ?></span>
                            <p class="font-medium" x-text="formatCurrency(selectedCustomer?.total_spent || 0)"></p>
                        </div>
                    </div>
                </div>

                <!-- Historique des achats -->
                <div class="mb-6">
                    <h3 class="font-semibold mb-3"><?= __('loyalty.recent_purchases') ?></h3>
                    <div class="bg-white border rounded-lg divide-y max-h-48 overflow-y-auto">
                        <template x-for="purchase in selectedCustomer?.purchases || []" :key="purchase.id">
                            <div class="p-3 flex justify-between items-center">
                                <div>
                                    <span class="font-medium" x-text="purchase.profile_name || 'Achat'"></span>
                                    <span class="text-sm text-gray-500 ml-2" x-text="formatDate(purchase.created_at)"></span>
                                </div>
                                <span class="font-medium" x-text="formatCurrency(purchase.amount)"></span>
                            </div>
                        </template>
                        <div x-show="!selectedCustomer?.purchases?.length" class="p-3 text-center text-gray-500">
                            <?= __('loyalty.no_purchase_found') ?>
                        </div>
                    </div>
                </div>

                <!-- Récompenses -->
                <div>
                    <h3 class="font-semibold mb-3"><?= __('loyalty.tab_rewards') ?></h3>
                    <div class="bg-white border rounded-lg divide-y max-h-48 overflow-y-auto">
                        <template x-for="reward in selectedCustomer?.rewards || []" :key="reward.id">
                            <div class="p-3 flex justify-between items-center">
                                <div>
                                    <span class="font-medium" x-text="getRewardTypeLabel(reward.reward_type)"></span>
                                    <span x-show="reward.voucher_code" class="ml-2 font-mono text-sm bg-green-100 text-green-800 px-2 py-0.5 rounded" x-text="reward.voucher_code"></span>
                                </div>
                                <span :class="{
                                    'text-yellow-600': reward.status === 'pending',
                                    'text-green-600': reward.status === 'claimed',
                                    'text-gray-400': reward.status === 'expired'
                                }" x-text="getStatusLabel(reward.status)"></span>
                            </div>
                        </template>
                        <div x-show="!selectedCustomer?.rewards?.length" class="p-3 text-center text-gray-500">
                            <?= __('loyalty.no_reward_found') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loyaltyManager() {
    return {
        activeTab: 'customers',
        stats: {},
        customers: [],
        customersPagination: {},
        customerSearch: '',
        customerSort: 'total_purchases',
        rules: [],
        rewards: [],
        rewardStatusFilter: '',
        profiles: [],
        showRuleModal: false,
        showCustomerModal: false,
        editingRule: null,
        selectedCustomer: null,
        importing: false,
        resetting: false,
        autoRecordEnabled: false,
        ruleForm: {
            name: '',
            description: '',
            rule_type: 'purchase_count',
            threshold_value: 5,
            reward_type: 'free_voucher',
            reward_profile_id: '',
            is_active: true
        },

        async init() {
            await this.loadAutoRecordStatus();
            await this.loadStats();
            await this.loadCustomers();
            await this.loadRules();
            // Générer automatiquement les vouchers manquants avant de charger les récompenses
            await this.generateMissingVouchers();
            await this.loadRewards();
            await this.loadProfiles();
        },

        async generateMissingVouchers() {
            try {
                await fetch('api.php?route=/loyalty/generate-pending-vouchers', { method: 'POST', headers: { 'Accept': 'application/json' } });
            } catch (e) {
                console.error('Erreur génération vouchers:', e);
            }
        },

        async loadAutoRecordStatus() {
            try {
                const response = await fetch('api.php?route=/loyalty/auto-record', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.autoRecordEnabled = data.data.enabled;
                }
            } catch (e) {
                console.error('Erreur chargement statut auto-record:', e);
            }
        },

        async toggleAutoRecord() {
            const newState = !this.autoRecordEnabled;
            const msg = newState
                ? '<?= addslashes(__('loyalty.confirm_auto_record_on')) ?>'
                : '<?= addslashes(__('loyalty.confirm_auto_record_off')) ?>';
            if (!confirm(msg)) return;

            try {
                const response = await fetch('api.php?route=/loyalty/auto-record', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ enabled: newState })
                });
                const data = await response.json();
                if (data.success) {
                    this.autoRecordEnabled = data.data.enabled;
                    showToast(data.message, 'success');
                } else {
                    showToast(data.error || '<?= addslashes(__('common.error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur toggle auto-record:', e);
                showToast('<?= addslashes(__('common.error')) ?>', 'error');
            }
        },

        async resetLoyalty() {
            if (!confirm('<?= addslashes(__('loyalty.confirm_reset')) ?>')) return;

            this.resetting = true;
            try {
                const response = await fetch('api.php?route=/loyalty/reset', { method: 'POST', headers: { 'Accept': 'application/json' } });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadStats();
                    await this.loadCustomers();
                    await this.loadRewards();
                } else {
                    showToast(data.error || '<?= addslashes(__('loyalty.msg_reset_error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur reset:', e);
                showToast('<?= addslashes(__('loyalty.msg_reset_error')) ?>', 'error');
            } finally {
                this.resetting = false;
            }
        },

        async importTransactions() {
            if (!confirm('<?= addslashes(__('loyalty.confirm_import')) ?>')) return;

            this.importing = true;
            try {
                const response = await fetch('api.php?route=/loyalty/import', { method: 'POST', headers: { 'Accept': 'application/json' } });
                const data = await response.json();

                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadStats();
                    await this.loadCustomers();
                    await this.loadRewards();
                } else {
                    showToast(data.error || '<?= addslashes(__('loyalty.msg_import_error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur import:', e);
                showToast('<?= addslashes(__('loyalty.msg_import_error')) ?>', 'error');
            } finally {
                this.importing = false;
            }
        },

        async loadStats() {
            try {
                const response = await fetch('api.php?route=/loyalty/stats', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.stats = data.data;
                }
            } catch (e) {
                console.error('Erreur chargement stats:', e);
            }
        },

        async loadCustomers(page = 1) {
            try {
                const params = new URLSearchParams({
                    route: '/loyalty/customers',
                    page,
                    search: this.customerSearch,
                    sort_by: this.customerSort,
                    sort_order: 'DESC'
                });
                const response = await fetch(`api.php?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.customers = data.data.customers;
                    this.customersPagination = data.data.pagination;
                }
            } catch (e) {
                console.error('Erreur chargement clients:', e);
            }
        },

        async loadRules() {
            try {
                const response = await fetch('api.php?route=/loyalty/rules', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.rules = data.data;
                }
            } catch (e) {
                console.error('Erreur chargement règles:', e);
            }
        },

        async loadRewards(page = 1) {
            try {
                const params = new URLSearchParams({ route: '/loyalty/rewards', page });
                if (this.rewardStatusFilter) {
                    params.append('status', this.rewardStatusFilter);
                }
                const response = await fetch(`api.php?${params}`, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.rewards = data.data.rewards;
                }
            } catch (e) {
                console.error('Erreur chargement récompenses:', e);
            }
        },

        async loadProfiles() {
            try {
                const response = await fetch('api.php?route=/profiles&active=1', { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.profiles = data.data;
                }
            } catch (e) {
                console.error('Erreur chargement profils:', e);
            }
        },

        openRuleModal(rule = null) {
            this.editingRule = rule;
            if (rule) {
                this.ruleForm = {
                    name: rule.name,
                    description: rule.description || '',
                    rule_type: rule.rule_type,
                    threshold_value: rule.threshold_value,
                    reward_type: rule.reward_type,
                    reward_profile_id: rule.reward_profile_id || '',
                    is_active: !!rule.is_active
                };
            } else {
                this.ruleForm = {
                    name: '',
                    description: '',
                    rule_type: 'purchase_count',
                    threshold_value: 5,
                    reward_type: 'free_voucher',
                    reward_profile_id: '',
                    is_active: true
                };
            }
            this.showRuleModal = true;
        },

        async saveRule() {
            try {
                const url = this.editingRule
                    ? `api.php?route=/loyalty/rules/${this.editingRule.id}`
                    : 'api.php?route=/loyalty/rules';
                const method = this.editingRule ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.ruleForm)
                });

                const data = await response.json();
                if (data.success) {
                    this.showRuleModal = false;
                    await this.loadRules();
                    showToast(data.message || '<?= addslashes(__('loyalty.msg_rule_saved')) ?>', 'success');
                } else {
                    showToast(data.error || '<?= addslashes(__('common.error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur sauvegarde règle:', e);
                showToast('<?= addslashes(__('loyalty.msg_rule_save_error')) ?>', 'error');
            }
        },

        async toggleRule(rule) {
            try {
                const response = await fetch(`api.php?route=/loyalty/rules/${rule.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ is_active: rule.is_active ? 0 : 1 })
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadRules();
                }
            } catch (e) {
                console.error('Erreur toggle règle:', e);
            }
        },

        async deleteRule(rule) {
            if (!confirm('<?= addslashes(__('loyalty.confirm_delete_rule')) ?>')) return;

            try {
                const response = await fetch(`api.php?route=/loyalty/rules/${rule.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadRules();
                    showToast('<?= addslashes(__('loyalty.msg_rule_deleted')) ?>', 'success');
                } else {
                    showToast(data.error || '<?= addslashes(__('common.error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur suppression règle:', e);
            }
        },

        async deleteCustomer(customer) {
            if (!confirm('<?= addslashes(__('loyalty.confirm_delete_customer')) ?>')) return;

            try {
                const response = await fetch(`api.php?route=/loyalty/customers/${customer.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadCustomers();
                    await this.loadStats();
                    showToast(data.message || '<?= addslashes(__('loyalty.msg_customer_deleted')) ?>', 'success');
                } else {
                    showToast(data.error || '<?= addslashes(__('common.error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur suppression client:', e);
            }
        },

        async deleteReward(reward) {
            if (!confirm('<?= addslashes(__('loyalty.confirm_delete_reward')) ?>')) return;

            try {
                const response = await fetch(`api.php?route=/loyalty/rewards/${reward.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });

                const data = await response.json();
                if (data.success) {
                    await this.loadRewards();
                    await this.loadStats();
                    showToast(data.message || '<?= addslashes(__('loyalty.msg_reward_deleted')) ?>', 'success');
                } else {
                    showToast(data.error || '<?= addslashes(__('common.error')) ?>', 'error');
                }
            } catch (e) {
                console.error('Erreur suppression récompense:', e);
            }
        },

        async showCustomerDetails(customer) {
            try {
                const response = await fetch(`api.php?route=/loyalty/customers/${customer.id}`, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (data.success) {
                    this.selectedCustomer = data.data;
                    this.showCustomerModal = true;
                }
            } catch (e) {
                console.error('Erreur chargement détails client:', e);
            }
        },

        calculateProgress(customer) {
            // Calcule la progression vers la prochaine récompense basée sur la règle active
            const activeRule = this.rules.find(r => r.is_active && r.rule_type === 'purchase_count');
            if (!activeRule) return 0;

            const purchasesSinceLastReward = customer.total_purchases % activeRule.threshold_value;
            return Math.round((purchasesSinceLastReward / activeRule.threshold_value) * 100);
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('fr-FR', {
                style: 'currency',
                currency: 'XOF',
                minimumFractionDigits: 0
            }).format(amount || 0);
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            return new Date(dateStr).toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        getRuleTypeLabel(type) {
            const labels = {
                'purchase_count': '<?= addslashes(__('loyalty.rule_type_purchase_count')) ?>',
                'amount_spent': '<?= addslashes(__('loyalty.rule_type_amount_spent')) ?>',
                'points': '<?= addslashes(__('loyalty.rule_type_points')) ?>'
            };
            return labels[type] || type;
        },

        getRuleThresholdUnit(type) {
            const units = {
                'purchase_count': '<?= addslashes(__('loyalty.unit_purchases')) ?>',
                'amount_spent': 'XOF',
                'points': 'points'
            };
            return units[type] || '';
        },

        getRewardTypeLabel(type) {
            const labels = {
                'free_voucher': '<?= addslashes(__('loyalty.reward_free_voucher')) ?>',
                'discount_percent': '<?= addslashes(__('loyalty.reward_discount_percent')) ?>',
                'bonus_time': '<?= addslashes(__('loyalty.reward_bonus_time')) ?>',
                'bonus_data': '<?= addslashes(__('loyalty.reward_bonus_data')) ?>'
            };
            return labels[type] || type;
        },

        getStatusLabel(status) {
            const labels = {
                'pending': '<?= addslashes(__('loyalty.status_pending')) ?>',
                'claimed': '<?= addslashes(__('loyalty.status_claimed')) ?>',
                'expired': '<?= addslashes(__('loyalty.status_expired')) ?>',
                'cancelled': '<?= addslashes(__('loyalty.status_cancelled')) ?>'
            };
            return labels[status] || status;
        }
    };
}
</script>
