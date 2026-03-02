<?php $pageTitle = __('marketing.title'); $currentPage = 'marketing'; ?>

<div x-data="marketingPage()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-7 h-7 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
            <?= __('marketing.title') ?>
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('marketing.subtitle') ?></p>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="-mb-px flex gap-6">
            <button @click="activeTab = 'compose'"
                :class="activeTab === 'compose' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('marketing.tab_compose') ?>
            </button>
            <button @click="activeTab = 'history'; if(!historyLoaded) loadHistory()"
                :class="activeTab === 'history' ? 'border-purple-500 text-purple-600 dark:text-purple-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('marketing.tab_history') ?>
            </button>
        </nav>
    </div>

    <!-- Tab: Compose -->
    <div x-show="activeTab === 'compose'" x-cloak>

        <!-- Step 1: Client Source Selection -->
        <div class="mb-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 uppercase tracking-wider">1. Source des clients</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Hotspot -->
                <div @click="selectSource('hotspot')"
                    class="p-5 rounded-xl border-2 cursor-pointer transition-all hover:shadow-md"
                    :class="clientSource === 'hotspot' ? 'border-orange-500 bg-orange-50 dark:bg-orange-900/10 shadow-sm' : 'border-gray-200 dark:border-[#30363d] hover:border-gray-300'">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="clientSource === 'hotspot' ? 'bg-orange-100 dark:bg-orange-900/30' : 'bg-gray-100 dark:bg-[#21262d]'">
                            <svg class="w-5 h-5" :class="clientSource === 'hotspot' ? 'text-orange-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.858 15.355-5.858 21.213 0" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><?= __('marketing.source_hotspot') ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.source_hotspot_desc') ?></p>
                        </div>
                    </div>
                </div>

                <!-- OTP -->
                <div @click="selectSource('otp')"
                    class="p-5 rounded-xl border-2 cursor-pointer transition-all hover:shadow-md"
                    :class="clientSource === 'otp' ? 'border-cyan-500 bg-cyan-50 dark:bg-cyan-900/10 shadow-sm' : 'border-gray-200 dark:border-[#30363d] hover:border-gray-300'">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="clientSource === 'otp' ? 'bg-cyan-100 dark:bg-cyan-900/30' : 'bg-gray-100 dark:bg-[#21262d]'">
                            <svg class="w-5 h-5" :class="clientSource === 'otp' ? 'text-cyan-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><?= __('marketing.source_otp') ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.source_otp_desc') ?></p>
                        </div>
                    </div>
                </div>

                <!-- PPPoE -->
                <div @click="selectSource('pppoe')"
                    class="p-5 rounded-xl border-2 cursor-pointer transition-all hover:shadow-md"
                    :class="clientSource === 'pppoe' ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/10 shadow-sm' : 'border-gray-200 dark:border-[#30363d] hover:border-gray-300'">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                            :class="clientSource === 'pppoe' ? 'bg-violet-100 dark:bg-violet-900/30' : 'bg-gray-100 dark:bg-[#21262d]'">
                            <svg class="w-5 h-5" :class="clientSource === 'pppoe' ? 'text-violet-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><?= __('marketing.source_pppoe') ?></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.source_pppoe_desc') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Filters -->
        <div class="mb-6 bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wider">2. <?= __('marketing.filter_title') ?></h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Date from (always) -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.date_from') ?></label>
                    <input type="date" x-model="filters.date_from"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <!-- Date to (always) -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.date_to') ?></label>
                    <input type="date" x-model="filters.date_to"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <!-- Hotspot: Profile -->
                <div x-show="clientSource === 'hotspot'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.profile') ?></label>
                    <select x-model="filters.profile_id"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?= __('marketing.all_profiles') ?></option>
                        <template x-for="p in hotspotProfiles" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + p.price + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Hotspot: Payment method -->
                <div x-show="clientSource === 'hotspot'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.payment_method') ?></label>
                    <select x-model="filters.payment_method"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?= __('marketing.all_methods') ?></option>
                        <option value="kkiapay">KKiaPay</option>
                        <option value="fedapay">FedaPay</option>
                        <option value="moov_money">Moov Money</option>
                        <option value="mtn_momo">MTN MoMo</option>
                    </select>
                </div>

                <!-- Hotspot: Amount min -->
                <div x-show="clientSource === 'hotspot'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.amount_min') ?></label>
                    <input type="number" x-model="filters.amount_min" min="0" step="100"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <!-- Hotspot: Amount max -->
                <div x-show="clientSource === 'hotspot'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.amount_max') ?></label>
                    <input type="number" x-model="filters.amount_max" min="0" step="100"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <!-- PPPoE: Status -->
                <div x-show="clientSource === 'pppoe'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.pppoe_status') ?></label>
                    <select x-model="filters.status"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?= __('marketing.all_statuses') ?></option>
                        <option value="active">Active</option>
                        <option value="suspended">Suspended</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <!-- PPPoE: Profile -->
                <div x-show="clientSource === 'pppoe'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.profile') ?></label>
                    <select x-model="filters.profile_id"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value=""><?= __('marketing.all_profiles') ?></option>
                        <template x-for="p in pppoeProfiles" :key="p.id">
                            <option :value="p.id" x-text="p.name + ' (' + p.price + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- PPPoE: Expiry from -->
                <div x-show="clientSource === 'pppoe'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.expiry_from') ?></label>
                    <input type="date" x-model="filters.expiry_from"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <!-- PPPoE: Expiry to -->
                <div x-show="clientSource === 'pppoe'">
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.expiry_to') ?></label>
                    <input type="date" x-model="filters.expiry_to"
                        class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
            </div>

            <!-- Search button + count -->
            <div class="flex items-center gap-4 mt-4">
                <button @click="loadClients()"
                    :disabled="clientsLoading"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors">
                    <svg x-show="!clientsLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <svg x-show="clientsLoading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="clientsLoading ? '<?= __js('marketing.searching') ?>' : '<?= __js('marketing.search') ?>'"></span>
                </button>

                <span x-show="clientsLoaded" class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-bold text-purple-600" x-text="clientPagination.total"></span>
                    <?= __('marketing.clients_found') ?>
                </span>
            </div>
        </div>

        <!-- Step 3: Client List -->
        <div x-show="clientsLoaded" class="mb-6 bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden" x-cloak>
            <div class="px-5 py-3 border-b border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">3. Clients</h2>
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" @change="toggleSelectAll($event)" :checked="allSelected"
                            class="rounded border-gray-300 dark:border-[#30363d] text-purple-600 focus:ring-purple-500">
                        <?= __('marketing.select_all') ?>
                    </label>
                    <span x-show="selectedPhones.length > 0" class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded-full text-xs font-semibold">
                        <span x-text="selectedPhones.length"></span> <?= __('marketing.selected') ?>
                    </span>
                </div>
            </div>

            <!-- No results -->
            <div x-show="clients.length === 0 && !clientsLoading" class="py-12 text-center text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <p class="text-sm"><?= __('marketing.no_clients') ?></p>
            </div>

            <!-- Table -->
            <div x-show="clients.length > 0" class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#21262d] text-left">
                        <tr>
                            <th class="px-4 py-3 w-10"></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.phone') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.name') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.date') ?></th>
                            <th x-show="clientSource === 'hotspot'" class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.amount') ?></th>
                            <th x-show="clientSource === 'pppoe'" class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.status') ?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                        <template x-for="client in clients" :key="client.phone">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1c2129]">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="client.phone" x-model="selectedPhones"
                                        class="rounded border-gray-300 dark:border-[#30363d] text-purple-600 focus:ring-purple-500">
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-900 dark:text-white" x-text="client.phone"></td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="client.name || '-'"></td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="client.date ? new Date(client.date).toLocaleDateString() : '-'"></td>
                                <td x-show="clientSource === 'hotspot'" class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="client.amount ? Number(client.amount).toLocaleString() + ' FCFA' : '-'"></td>
                                <td x-show="clientSource === 'pppoe'" class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': client.status === 'active',
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': client.status === 'suspended',
                                            'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': client.status === 'expired'
                                        }" x-text="client.status"></span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="clientPagination.pages > 1" class="px-5 py-3 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('marketing.page') ?> <span x-text="clientPagination.page"></span> <?= __('marketing.of') ?> <span x-text="clientPagination.pages"></span>
                </div>
                <div class="flex gap-2">
                    <button @click="clientPagination.page--; loadClients()" :disabled="clientPagination.page <= 1"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#21262d] text-gray-700 dark:text-gray-300">
                        <?= __('marketing.prev') ?>
                    </button>
                    <button @click="clientPagination.page++; loadClients()" :disabled="clientPagination.page >= clientPagination.pages"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#21262d] text-gray-700 dark:text-gray-300">
                        <?= __('marketing.next') ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Step 4: Message Composition -->
        <div x-show="clientsLoaded && clients.length > 0" class="mb-6 bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5" x-cloak>
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4 uppercase tracking-wider">4. <?= __('marketing.compose_message') ?></h2>

            <!-- Channel Toggle -->
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2"><?= __('marketing.channel') ?></label>
                <div class="flex gap-2">
                    <button @click="channel = 'sms'; if(delaySeconds >= 3) delaySeconds = 0.5"
                        :class="channel === 'sms' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        SMS
                    </button>
                    <button @click="channel = 'whatsapp'; if(delaySeconds < 3) delaySeconds = 5"
                        :class="channel === 'whatsapp' ? 'bg-green-600 text-white shadow-sm' : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" />
                        </svg>
                        WhatsApp
                    </button>
                </div>
            </div>

            <!-- SMS Gateway selector -->
            <div x-show="channel === 'sms'" class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.select_gateway') ?></label>
                <select x-model="smsGatewayId"
                    class="w-full max-w-md px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <option value="">-- <?= __('marketing.select_gateway') ?> --</option>
                    <template x-for="gw in smsGateways" :key="gw.id">
                        <option :value="gw.id" x-text="gw.name + ' (' + gw.provider_code + ')'"></option>
                    </template>
                </select>
                <p x-show="smsGateways.length === 0" class="mt-1 text-xs text-amber-600"><?= __('marketing.no_gateway') ?></p>
            </div>

            <!-- Message textarea -->
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.message') ?></label>
                <textarea x-model="message" rows="5" x-ref="messageBox"
                    placeholder="<?= __('marketing.message_placeholder') ?>"
                    class="w-full px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent font-mono"></textarea>
                <div class="flex items-center justify-between mt-1">
                    <span class="text-xs text-gray-400">
                        <span x-text="message.length"></span> <?= __('marketing.chars') ?>
                        <span x-show="channel === 'sms'">
                            &middot; <span x-text="Math.ceil(Math.max(1, message.length) / 160)"></span> SMS
                        </span>
                    </span>
                </div>
            </div>

            <!-- Variables -->
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-2"><?= __('marketing.variables') ?></label>
                <div class="flex flex-wrap gap-1.5">
                    <button @click="insertVar('{{name}}')" type="button"
                        class="px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded text-xs font-mono hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                        {{name}}
                    </button>
                    <button @click="insertVar('{{phone}}')" type="button"
                        class="px-2.5 py-1 bg-purple-50 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 rounded text-xs font-mono hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                        {{phone}}
                    </button>
                </div>
            </div>

            <!-- Delay between messages -->
            <div class="mb-4">
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('marketing.delay_label') ?></label>
                <div class="flex items-center gap-3">
                    <input type="number" x-model.number="delaySeconds" min="0.2" max="30" step="0.5"
                        class="w-24 px-3 py-2 text-sm bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.delay_unit') ?></span>
                </div>
                <p x-show="channel === 'whatsapp'" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                    <?= __('marketing.delay_whatsapp_hint') ?>
                </p>
            </div>

            <!-- Send button -->
            <div x-show="channel === 'sms' && !smsGatewayId && smsGateways.length > 0" class="p-3 mb-3 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 text-amber-700 dark:text-amber-400 text-xs">
                Veuillez sélectionner une passerelle SMS pour envoyer.
            </div>
            <div x-show="channel === 'sms' && smsGateways.length === 0" class="p-3 mb-3 rounded-lg bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800/30 text-amber-700 dark:text-amber-400 text-xs">
                <?= __('marketing.no_gateway') ?>. <a href="index.php?page=sms" class="underline font-medium">Configurer</a>
            </div>
            <div class="flex items-center gap-4 pt-2">
                <button @click="showConfirmModal = true"
                    :disabled="sending || selectedPhones.length === 0 || !message.trim() || (channel === 'sms' && !smsGatewayId)"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                    <svg x-show="!sending" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg x-show="sending" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span x-text="sending ? '<?= __js('marketing.sending') ?>...' : '<?= __js('marketing.send_campaign') ?>'"></span>
                </button>
                <span x-show="selectedPhones.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                    <span class="font-bold" x-text="selectedPhones.length"></span> <?= __('marketing.recipients') ?>
                    <?= __('marketing.via') ?> <span class="font-medium" x-text="channel === 'sms' ? 'SMS' : 'WhatsApp'"></span>
                </span>
            </div>

            <!-- Progress bar -->
            <div x-show="sending" class="mt-4" x-cloak>
                <div class="bg-gray-100 dark:bg-[#21262d] rounded-full h-2 overflow-hidden">
                    <div class="bg-purple-600 h-2 rounded-full transition-all duration-300"
                        :style="'width:' + (sendProgress.total > 0 ? ((sendProgress.sent + sendProgress.failed) / sendProgress.total * 100) : 0) + '%'">
                    </div>
                </div>
                <p class="mt-1 text-xs text-gray-500">
                    <span x-text="sendProgress.sent + sendProgress.failed"></span> / <span x-text="sendProgress.total"></span>
                </p>
            </div>

            <!-- Results -->
            <div x-show="sendResult" class="mt-4 p-4 rounded-lg border border-green-200 dark:border-green-900/30 bg-green-50 dark:bg-green-900/10" x-cloak>
                <p class="font-medium text-green-800 dark:text-green-300 text-sm"><?= __('marketing.send_complete') ?></p>
                <div class="flex gap-4 mt-2 text-sm">
                    <span class="text-green-700 dark:text-green-400"><?= __('marketing.sent') ?>: <span class="font-bold" x-text="sendResult.sent"></span></span>
                    <span class="text-red-600 dark:text-red-400"><?= __('marketing.failed') ?>: <span class="font-bold" x-text="sendResult.failed"></span></span>
                    <span class="text-gray-600 dark:text-gray-400"><?= __('marketing.total') ?>: <span class="font-bold" x-text="sendResult.total"></span></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: History -->
    <div x-show="activeTab === 'history'" x-cloak>
        <!-- Loading -->
        <div x-show="historyLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <!-- No campaigns -->
        <div x-show="!historyLoading && campaigns.length === 0" class="text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
            </svg>
            <p class="text-gray-400 dark:text-gray-500 text-sm"><?= __('marketing.no_campaigns') ?></p>
        </div>

        <!-- Campaigns table -->
        <div x-show="!historyLoading && campaigns.length > 0"
            class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 dark:bg-[#21262d] text-left">
                        <tr>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_date') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_channel') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_source') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_message') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_results') ?></th>
                            <th class="px-4 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.campaign_status') ?></th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                        <template x-for="c in campaigns" :key="c.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1c2129]">
                                <td class="px-4 py-3 text-xs text-gray-500" x-text="new Date(c.created_at).toLocaleString()"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="c.channel === 'sms' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400'"
                                        x-text="c.channel === 'sms' ? 'SMS' : 'WhatsApp'"></span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                    <span x-text="c.client_source === 'hotspot' ? '<?= __js('marketing.source_hotspot') ?>' : c.client_source === 'otp' ? '<?= __js('marketing.source_otp') ?>' : '<?= __js('marketing.source_pppoe') ?>'"></span>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 max-w-xs truncate" x-text="c.message_template.substring(0, 80) + (c.message_template.length > 80 ? '...' : '')"></td>
                                <td class="px-4 py-3 text-xs">
                                    <span class="text-green-600" x-text="c.sent_count"></span>
                                    <span class="text-gray-400">/</span>
                                    <span class="text-red-600" x-text="c.failed_count"></span>
                                    <span class="text-gray-400">/</span>
                                    <span x-text="c.total_recipients"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                        :class="{
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400': c.status === 'sending',
                                            'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': c.status === 'completed',
                                            'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': c.status === 'failed'
                                        }"
                                        x-text="c.status === 'sending' ? '<?= __js('marketing.status_sending') ?>' : c.status === 'completed' ? '<?= __js('marketing.status_completed') ?>' : '<?= __js('marketing.status_failed') ?>'"></span>
                                </td>
                                <td class="px-4 py-3">
                                    <button @click="viewCampaignDetails(c.id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <?= __('marketing.details') ?>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="historyPagination.pages > 1" class="px-5 py-3 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    <?= __('marketing.page') ?> <span x-text="historyPagination.page"></span> <?= __('marketing.of') ?> <span x-text="historyPagination.pages"></span>
                </div>
                <div class="flex gap-2">
                    <button @click="historyPagination.page--; loadHistory()" :disabled="historyPagination.page <= 1"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#21262d] text-gray-700 dark:text-gray-300">
                        <?= __('marketing.prev') ?>
                    </button>
                    <button @click="historyPagination.page++; loadHistory()" :disabled="historyPagination.page >= historyPagination.pages"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] disabled:opacity-30 hover:bg-gray-50 dark:hover:bg-[#21262d] text-gray-700 dark:text-gray-300">
                        <?= __('marketing.next') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaign Details Modal -->
    <div x-show="showDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="showDetailsModal = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-xl border border-gray-200 dark:border-[#30363d] max-w-3xl w-full max-h-[85vh] flex flex-col">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between flex-shrink-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= __('marketing.details_title') ?></h3>
                <button @click="showDetailsModal = false" class="p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Loading -->
            <div x-show="detailsLoading" class="flex justify-center py-12">
                <svg class="animate-spin h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>

            <!-- Content -->
            <div x-show="!detailsLoading && campaignDetail" class="overflow-y-auto flex-1">
                <!-- Summary cards -->
                <div class="px-6 py-4 grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.campaign_channel') ?></p>
                        <p class="font-bold text-sm mt-1">
                            <span :class="campaignDetail?.channel === 'sms' ? 'text-blue-600' : 'text-green-600'"
                                x-text="campaignDetail?.channel === 'sms' ? 'SMS' : 'WhatsApp'"></span>
                        </p>
                    </div>
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.total') ?></p>
                        <p class="font-bold text-sm mt-1 text-gray-900 dark:text-white" x-text="campaignDetail?.total_recipients"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.sent') ?></p>
                        <p class="font-bold text-sm mt-1 text-green-600" x-text="campaignDetail?.sent_count"></p>
                    </div>
                    <div class="bg-gray-50 dark:bg-[#0d1117] rounded-lg p-3 text-center">
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('marketing.failed') ?></p>
                        <p class="font-bold text-sm mt-1 text-red-600" x-text="campaignDetail?.failed_count"></p>
                    </div>
                </div>

                <!-- Message template -->
                <div class="px-6 pb-3">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1"><?= __('marketing.campaign_message') ?></p>
                    <div class="p-3 bg-gray-50 dark:bg-[#0d1117] rounded-lg">
                        <p class="text-xs text-gray-700 dark:text-gray-300 font-mono whitespace-pre-wrap" x-text="campaignDetail?.message_template"></p>
                    </div>
                </div>

                <!-- Messages table -->
                <div class="px-6 pb-4">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2"><?= __('marketing.details_messages') ?></p>
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-[#21262d] text-left">
                                <tr>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.phone') ?></th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.name') ?></th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.status') ?></th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.details_error') ?></th>
                                    <th class="px-3 py-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('marketing.details_sent_at') ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                                <template x-for="msg in campaignDetail?.messages || []" :key="msg.id">
                                    <tr class="hover:bg-gray-50 dark:hover:bg-[#1c2129]">
                                        <td class="px-3 py-2 font-mono text-xs text-gray-900 dark:text-white" x-text="msg.phone"></td>
                                        <td class="px-3 py-2 text-xs text-gray-600 dark:text-gray-400" x-text="msg.client_name || '-'"></td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                                :class="{
                                                    'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400': msg.status === 'sent',
                                                    'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400': msg.status === 'failed',
                                                    'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400': msg.status === 'pending'
                                                }"
                                                x-text="msg.status === 'sent' ? '<?= __js('marketing.sent') ?>' : msg.status === 'failed' ? '<?= __js('marketing.failed') ?>' : 'Pending'"></span>
                                        </td>
                                        <td class="px-3 py-2 text-xs text-red-500 max-w-[200px] truncate" x-text="msg.error_message || '-'" :title="msg.error_message"></td>
                                        <td class="px-3 py-2 text-xs text-gray-500" x-text="msg.sent_at ? new Date(msg.sent_at).toLocaleString() : '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Modal -->
    <div x-show="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
        <div class="absolute inset-0 bg-black/50" @click="showConfirmModal = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-xl border border-gray-200 dark:border-[#30363d] max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-3"><?= __('marketing.confirm_title') ?></h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                <?= __('marketing.confirm_text') ?>
                <span class="font-bold text-purple-600" x-text="selectedPhones.length"></span>
                <?= __('marketing.recipients') ?>
                <?= __('marketing.via') ?>
                <span class="font-bold" x-text="channel === 'sms' ? 'SMS' : 'WhatsApp'"></span>.
            </p>
            <div class="p-3 bg-gray-50 dark:bg-[#0d1117] rounded-lg mb-3 max-h-24 overflow-y-auto">
                <p class="text-xs text-gray-600 dark:text-gray-400 font-mono whitespace-pre-wrap" x-text="message"></p>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                <?= __('marketing.delay_label') ?>: <span class="font-medium" x-text="delaySeconds + 's'"></span>
                &middot; <?= __('marketing.estimated_time') ?>:
                <span class="font-medium" x-text="(() => { let s = Math.round(selectedPhones.length * delaySeconds); if (s < 60) return s + 's'; let m = Math.floor(s/60); return m + 'min ' + (s%60) + 's'; })()"></span>
            </p>
            <div class="flex gap-3 justify-end">
                <button @click="showConfirmModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] hover:bg-gray-200 dark:hover:bg-[#30363d] rounded-lg transition-colors">
                    <?= __('marketing.cancel') ?>
                </button>
                <button @click="executeSend()"
                    class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors shadow-sm">
                    <?= __('marketing.confirm_send') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function marketingPage() {
    return {
        activeTab: 'compose',

        // Client selection
        clientSource: 'hotspot',
        filters: {
            date_from: '', date_to: '',
            profile_id: '', payment_method: '',
            amount_min: '', amount_max: '',
            status: '', expiry_from: '', expiry_to: ''
        },

        // Client list
        clients: [],
        clientsLoaded: false,
        clientsLoading: false,
        clientPagination: { page: 1, per_page: 50, total: 0, pages: 0 },
        selectedPhones: [],

        // Profiles
        hotspotProfiles: [],
        pppoeProfiles: [],

        // Message
        channel: 'sms',
        smsGatewayId: '',
        smsGateways: [],
        message: '',
        delaySeconds: 5,

        // Send
        sending: false,
        sendProgress: { sent: 0, failed: 0, total: 0 },
        sendResult: null,
        showConfirmModal: false,

        // History
        campaigns: [],
        historyLoaded: false,
        historyLoading: false,
        historyPagination: { page: 1, per_page: 20, total: 0, pages: 0 },

        // Campaign details
        showDetailsModal: false,
        detailsLoading: false,
        campaignDetail: null,

        get allSelected() {
            return this.clients.length > 0 && this.selectedPhones.length === this.clients.length;
        },

        async init() {
            await Promise.all([
                this.loadGateways(),
                this.loadProfiles(),
            ]);
        },

        async loadGateways() {
            try {
                const r = await fetch('api.php?route=/marketing/gateways', { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (data.success) {
                    this.smsGateways = data.data || [];
                }
            } catch (e) { console.error('loadGateways error:', e); }
        },

        async loadProfiles() {
            try {
                const r = await fetch('api.php?route=/marketing/profiles', { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (data.success) {
                    this.hotspotProfiles = data.data?.hotspot || [];
                    this.pppoeProfiles = data.data?.pppoe || [];
                }
            } catch (e) { console.error('loadProfiles error:', e); }
        },

        selectSource(source) {
            if (this.clientSource === source) return;
            this.clientSource = source;
            this.resetFilters();
        },

        resetFilters() {
            this.filters = {
                date_from: '', date_to: '',
                profile_id: '', payment_method: '',
                amount_min: '', amount_max: '',
                status: '', expiry_from: '', expiry_to: ''
            };
            this.clients = [];
            this.clientsLoaded = false;
            this.selectedPhones = [];
            this.sendResult = null;
        },

        async loadClients() {
            this.clientsLoading = true;
            this.sendResult = null;

            const params = new URLSearchParams({
                source: this.clientSource,
                page: this.clientPagination.page
            });

            Object.entries(this.filters).forEach(([k, v]) => {
                if (v !== '' && v !== null && v !== undefined) params.set(k, v);
            });

            try {
                const r = await fetch('api.php?route=/marketing/clients&' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (data.success) {
                    this.clients = data.data?.clients || [];
                    this.clientPagination = data.data?.pagination || { page: 1, per_page: 50, total: 0, pages: 0 };
                    this.clientsLoaded = true;
                    // Select all by default
                    this.selectedPhones = this.clients.map(c => c.phone);
                }
            } catch (e) {
                console.error(e);
                this.notify('Erreur réseau', 'error');
            }
            this.clientsLoading = false;
        },

        toggleSelectAll(event) {
            this.selectedPhones = event.target.checked ? this.clients.map(c => c.phone) : [];
        },

        insertVar(v) {
            const el = this.$refs.messageBox;
            if (el) {
                const start = el.selectionStart;
                const end = el.selectionEnd;
                this.message = this.message.substring(0, start) + v + this.message.substring(end);
                this.$nextTick(() => {
                    el.selectionStart = el.selectionEnd = start + v.length;
                    el.focus();
                });
            } else {
                this.message += v;
            }
        },

        async executeSend() {
            this.showConfirmModal = false;
            this.sending = true;
            this.sendResult = null;
            this.sendProgress = { sent: 0, failed: 0, total: this.selectedPhones.length };

            try {
                const r = await fetch('api.php?route=/marketing/send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        channel: this.channel,
                        gateway_id: this.channel === 'sms' ? this.smsGatewayId : null,
                        message: this.message,
                        source: this.clientSource,
                        filters: this.filters,
                        client_phones: this.selectedPhones,
                        delay_ms: Math.round(this.delaySeconds * 1000),
                    })
                });

                if (!r.ok && r.status === 302) {
                    this.notify('Session expirée, veuillez vous reconnecter', 'error');
                    this.sending = false;
                    return;
                }

                const text = await r.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseErr) {
                    console.error('Response not JSON:', text.substring(0, 500));
                    this.notify('Erreur serveur inattendue', 'error');
                    this.sending = false;
                    return;
                }

                if (data.success) {
                    this.sendResult = data.data;
                    this.sendProgress = {
                        sent: data.data.sent,
                        failed: data.data.failed,
                        total: data.data.total
                    };
                    this.notify(data.message || '<?= __js('marketing.send_complete') ?>', 'success');
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                console.error('Send error:', e);
                this.notify('Erreur réseau', 'error');
            }
            this.sending = false;
        },

        async loadHistory() {
            this.historyLoading = true;
            const params = new URLSearchParams({ page: this.historyPagination.page });

            try {
                const r = await fetch('api.php?route=/marketing/campaigns&' + params.toString(), { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (data.success) {
                    this.campaigns = data.data?.campaigns || [];
                    this.historyPagination = data.data?.pagination || { page: 1, per_page: 20, total: 0, pages: 0 };
                    this.historyLoaded = true;
                }
            } catch (e) { console.error(e); }
            this.historyLoading = false;
        },

        async viewCampaignDetails(campaignId) {
            this.showDetailsModal = true;
            this.detailsLoading = true;
            this.campaignDetail = null;

            try {
                const r = await fetch('api.php?route=/marketing/campaigns/' + campaignId, { headers: { 'Accept': 'application/json' } });
                const data = await r.json();
                if (data.success) {
                    this.campaignDetail = data.data;
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                    this.showDetailsModal = false;
                }
            } catch (e) {
                console.error('Campaign details error:', e);
                this.notify('Erreur réseau', 'error');
                this.showDetailsModal = false;
            }
            this.detailsLoading = false;
        },

        notify(message, type) {
            if (typeof showToast === 'function') {
                showToast(message, type);
            }
        },
    };
}
</script>
