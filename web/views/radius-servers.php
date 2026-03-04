<?php $pageTitle = __('radius_servers.title');
$currentPage = 'radius-servers'; ?>

<div x-data="radiusServersPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('radius_servers.subtitle') ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="refreshStatuses()" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d]">
                <svg class="w-5 h-5 mr-2" :class="refreshing && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('radius_servers.refresh') ?>
            </button>
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('radius_servers.add_server') ?>
            </button>
        </div>
    </div>

    <!-- Loading -->
    <template x-if="loading">
        <div class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </template>

    <!-- Empty state -->
    <template x-if="!loading && servers.length === 0">
        <div class="text-center py-16 bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200 dark:border-[#30363d]">
            <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2"><?= __('radius_servers.no_servers') ?></h3>
            <p class="text-gray-500 dark:text-gray-400 mb-6"><?= __('radius_servers.no_servers_desc') ?></p>
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">
                <?= __('radius_servers.add_first_server') ?>
            </button>
        </div>
    </template>

    <!-- Server Grid -->
    <div x-show="!loading && servers.length > 0" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="server in servers" :key="server.id">
            <div class="group relative bg-white dark:bg-[#161b22] rounded-2xl shadow-sm hover:shadow-xl dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden transition-all duration-300 transform hover:-translate-y-1">
                <!-- Status bar -->
                <div class="absolute inset-x-0 top-0 h-1.5"
                    :class="{
                        'bg-emerald-500': server.status === 'online',
                        'bg-red-500': server.status === 'offline',
                        'bg-yellow-500': server.status === 'setup'
                    }">
                </div>

                <div class="p-6">
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="relative w-12 h-12 flex items-center justify-center rounded-xl bg-gradient-to-br from-purple-50 to-purple-100/50 dark:from-purple-900/30 dark:to-purple-800/10 border border-purple-100 dark:border-purple-800/30">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                </svg>
                                <div class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full border-2 border-white dark:border-[#161b22]"
                                    :class="{
                                        'bg-emerald-500': server.status === 'online',
                                        'bg-red-500': server.status === 'offline',
                                        'bg-yellow-500': server.status === 'setup'
                                    }">
                                </div>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white leading-tight" x-text="server.name"></h3>
                                <p class="text-xs font-mono text-gray-500 dark:text-gray-400 mt-0.5" x-text="server.host"></p>
                            </div>
                        </div>
                        <!-- Status badge -->
                        <span class="px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider rounded-full"
                            :class="{
                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': server.status === 'online',
                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': server.status === 'offline',
                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': server.status === 'setup'
                            }"
                            x-text="server.status">
                        </span>
                    </div>

                    <!-- Description -->
                    <template x-if="server.description">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3 line-clamp-2" x-text="server.description"></p>
                    </template>

                    <!-- Info grid -->
                    <div class="grid grid-cols-2 gap-3 my-4 p-3 rounded-xl bg-gray-50/50 dark:bg-[#0d1117] border border-gray-100 dark:border-[#30363d]">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"><?= __('radius_servers.zones') ?></span>
                            <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="server.zones_count || 0"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"><?= __('radius_servers.routers') ?></span>
                            <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="server.nas_count || 0"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"><?= __('radius_servers.last_sync') ?></span>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="server.last_sync_at ? formatDate(server.last_sync_at) : '-'"></p>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider font-semibold"><?= __('radius_servers.heartbeat') ?></span>
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="server.last_heartbeat_at ? formatDate(server.last_heartbeat_at) : '-'"></p>
                        </div>
                    </div>

                    <!-- Code -->
                    <div class="flex items-center gap-2 mb-4 px-3 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1117] border border-gray-100 dark:border-[#30363d]">
                        <span class="text-xs text-gray-500 dark:text-gray-400 font-semibold">Code:</span>
                        <span class="text-xs font-mono text-gray-700 dark:text-gray-300" x-text="server.code"></span>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-[#30363d]">
                        <button @click="editServer(server)" class="flex-1 px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                            <?= __('radius_servers.edit') ?>
                        </button>
                        <button @click="showTokens(server)" class="px-3 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors" title="<?= __('radius_servers.tokens') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>
                        </button>
                        <button @click="showInstallCommand(server)" class="px-3 py-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/30 transition-colors" title="<?= __('radius_servers.install_script') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                        </button>
                        <button @click="toggleServer(server)" class="px-3 py-2 text-sm font-medium rounded-lg transition-colors"
                            :class="server.is_active ? 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 hover:bg-yellow-100 dark:hover:bg-yellow-900/30' : 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/20 hover:bg-emerald-100 dark:hover:bg-emerald-900/30'"
                            :title="server.is_active ? '<?= __js('radius_servers.deactivate') ?>' : '<?= __js('radius_servers.activate') ?>'">
                            <svg x-show="server.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            <svg x-show="!server.is_active" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </button>
                        <button @click="confirmDelete(server)" class="px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition-colors" title="<?= __('radius_servers.delete') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Modal Ajout/Édition -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-[#30363d]" @click.stop>
            <div class="p-6 border-b border-gray-200 dark:border-[#30363d]">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white" x-text="editMode ? '<?= __js('radius_servers.edit_server') ?>' : '<?= __js('radius_servers.add_server') ?>'"></h2>
            </div>
            <form @submit.prevent="saveServer()" class="p-6 space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.name') ?> *</label>
                    <input type="text" x-model="form.name" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="<?= __('radius_servers.name_placeholder') ?>">
                </div>

                <!-- Host -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.host') ?> *</label>
                    <input type="text" x-model="form.host" required
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="<?= __('radius_servers.host_placeholder') ?>">
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.description') ?></label>
                    <textarea x-model="form.description" rows="2"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                        placeholder="<?= __('radius_servers.description_placeholder') ?>"></textarea>
                </div>

                <!-- Webhook Port & Path -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.webhook_port') ?></label>
                        <input type="number" x-model="form.webhook_port"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="443">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.sync_interval') ?></label>
                        <input type="number" x-model="form.sync_interval"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-[#30363d] bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="60" min="10" max="300">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="showModal = false"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('radius_servers.cancel') ?>
                    </button>
                    <button type="submit" :disabled="saving"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50">
                        <span x-show="!saving" x-text="editMode ? '<?= __js('radius_servers.save') ?>' : '<?= __js('radius_servers.create') ?>'"></span>
                        <span x-show="saving"><?= __('radius_servers.saving') ?>...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tokens -->
    <div x-show="showTokenModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showTokenModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-[#30363d]" @click.stop>
            <div class="p-6 border-b border-gray-200 dark:border-[#30363d]">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?= __('radius_servers.tokens_title') ?></h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="tokenServer?.name"></p>
            </div>
            <div class="p-6 space-y-4">
                <!-- Sync Token -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.sync_token') ?></label>
                    <div class="flex gap-2">
                        <input type="text" :value="tokenServer?.sync_token" readonly
                            class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-gray-300 dark:border-[#30363d] bg-gray-50 dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
                        <button @click="copyToClipboard(tokenServer?.sync_token)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="Copier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </button>
                        <button @click="regenerateToken('sync')" class="px-3 py-2 text-sm text-orange-600 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100" title="<?= __('radius_servers.regenerate') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><?= __('radius_servers.sync_token_desc') ?></p>
                </div>

                <!-- Platform Token -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('radius_servers.platform_token') ?></label>
                    <div class="flex gap-2">
                        <input type="text" :value="tokenServer?.platform_token" readonly
                            class="flex-1 px-3 py-2 text-xs font-mono rounded-lg border border-gray-300 dark:border-[#30363d] bg-gray-50 dark:bg-[#0d1117] text-gray-700 dark:text-gray-300">
                        <button @click="copyToClipboard(tokenServer?.platform_token)" class="px-3 py-2 text-sm bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]" title="Copier">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        </button>
                        <button @click="regenerateToken('platform')" class="px-3 py-2 text-sm text-orange-600 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100" title="<?= __('radius_servers.regenerate') ?>">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><?= __('radius_servers.platform_token_desc') ?></p>
                </div>

                <div class="flex justify-end pt-4">
                    <button @click="showTokenModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('radius_servers.close') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete confirmation -->
    <div x-show="showDeleteModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showDeleteModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-md border border-gray-200 dark:border-[#30363d] p-6" @click.stop>
            <div class="text-center">
                <svg class="w-12 h-12 mx-auto text-red-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" /></svg>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2"><?= __('radius_servers.delete_confirm_title') ?></h3>
                <p class="text-gray-500 dark:text-gray-400 mb-6"><?= __('radius_servers.delete_confirm_desc') ?></p>
                <div class="flex gap-3 justify-center">
                    <button @click="showDeleteModal = false" class="px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200">
                        <?= __('radius_servers.cancel') ?>
                    </button>
                    <button @click="deleteServer()" class="px-4 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                        <?= __('radius_servers.delete') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Installation (commande curl) -->
    <div x-show="showInstallModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @click.self="showInstallModal = false">
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-2xl border border-gray-200 dark:border-[#30363d]" @click.stop>
            <div class="p-6 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                    <?= __('radius_servers.install_title') ?>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="installServer?.name"></p>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-gray-600 dark:text-gray-400"><?= __('radius_servers.install_desc') ?></p>

                <!-- Commande curl -->
                <div class="relative">
                    <div class="bg-gray-900 rounded-lg p-4 pr-12 overflow-x-auto">
                        <code class="text-emerald-400 text-sm whitespace-nowrap" x-text="getInstallCommand()"></code>
                    </div>
                    <button @click="copyToClipboard(getInstallCommand())"
                        class="absolute top-3 right-3 p-1.5 text-gray-400 hover:text-white bg-gray-700 hover:bg-gray-600 rounded transition-colors"
                        title="<?= __('radius_servers.copy') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                    </button>
                </div>

                <!-- Instructions -->
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-sm space-y-2">
                    <p class="font-medium text-blue-800 dark:text-blue-300"><?= __('radius_servers.install_steps') ?></p>
                    <ol class="list-decimal list-inside text-blue-700 dark:text-blue-400 space-y-1">
                        <li><?= __('radius_servers.install_step1') ?></li>
                        <li><?= __('radius_servers.install_step2') ?></li>
                        <li><?= __('radius_servers.install_step3') ?></li>
                    </ol>
                </div>

                <!-- Bouton download alternatif -->
                <div class="flex items-center justify-between pt-2">
                    <button @click="downloadInstallScript(installServer)" class="text-sm text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 underline">
                        <?= __('radius_servers.download_script') ?>
                    </button>
                    <button @click="showInstallModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d]">
                        <?= __('radius_servers.close') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function radiusServersPage() {
    return {
        servers: [],
        loading: true,
        saving: false,
        refreshing: false,
        showModal: false,
        showTokenModal: false,
        showDeleteModal: false,
        showInstallModal: false,
        installServer: null,
        editMode: false,
        editId: null,
        deleteId: null,
        tokenServer: null,
        form: {
            name: '',
            host: '',
            description: '',
            webhook_port: 443,
            sync_interval: 60,
        },

        async init() {
            await this.loadServers();
        },

        async loadServers() {
            this.loading = true;
            try {
                const res = await fetch('api.php?route=/radius-servers');
                const json = await res.json();
                if (json.success) {
                    this.servers = json.data;
                }
            } catch (e) {
                console.error('Load error:', e);
            }
            this.loading = false;
        },

        async refreshStatuses() {
            this.refreshing = true;
            try {
                const res = await fetch('api.php?route=/radius-servers/statuses');
                const json = await res.json();
                if (json.success) {
                    json.data.forEach(status => {
                        const server = this.servers.find(s => s.id === status.id);
                        if (server) {
                            server.status = status.status;
                            server.last_sync_at = status.last_sync_at;
                            server.last_heartbeat_at = status.last_heartbeat_at;
                        }
                    });
                }
            } catch (e) {
                console.error('Refresh error:', e);
            }
            this.refreshing = false;
        },

        resetForm() {
            this.form = { name: '', host: '', description: '', webhook_port: 443, sync_interval: 60 };
            this.editId = null;
        },

        editServer(server) {
            this.editMode = true;
            this.editId = server.id;
            this.form = {
                name: server.name,
                host: server.host,
                description: server.description || '',
                webhook_port: server.webhook_port || 443,
                sync_interval: server.sync_interval || 60,
            };
            this.showModal = true;
        },

        async saveServer() {
            this.saving = true;
            try {
                const url = this.editMode
                    ? `api.php?route=/radius-servers/${this.editId}`
                    : 'api.php?route=/radius-servers';
                const method = this.editMode ? 'PUT' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.form)
                });
                const json = await res.json();
                if (json.success) {
                    this.notify(json.message, 'success');
                    this.showModal = false;
                    await this.loadServers();
                } else {
                    this.notify(json.message || json.error, 'error');
                }
            } catch (e) {
                this.notify('Erreur de connexion', 'error');
            }
            this.saving = false;
        },

        async showTokens(server) {
            try {
                const res = await fetch(`api.php?route=/radius-servers/${server.id}`);
                const json = await res.json();
                if (json.success) {
                    this.tokenServer = json.data;
                    this.showTokenModal = true;
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
        },

        async regenerateToken(type) {
            if (!confirm('<?= __js('radius_servers.regenerate_confirm') ?>')) return;
            try {
                const res = await fetch(`api.php?route=/radius-servers/${this.tokenServer.id}/regenerate-token&type=${type}`, {
                    method: 'POST'
                });
                const json = await res.json();
                if (json.success) {
                    if (type === 'sync') {
                        this.tokenServer.sync_token = json.data.token;
                    } else {
                        this.tokenServer.platform_token = json.data.token;
                    }
                    this.notify(json.message, 'success');
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
        },

        async toggleServer(server) {
            try {
                const res = await fetch(`api.php?route=/radius-servers/${server.id}/toggle`, { method: 'POST' });
                const json = await res.json();
                if (json.success) {
                    this.notify(json.message, 'success');
                    await this.loadServers();
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
        },

        confirmDelete(server) {
            this.deleteId = server.id;
            this.showDeleteModal = true;
        },

        async deleteServer() {
            try {
                const res = await fetch(`api.php?route=/radius-servers/${this.deleteId}`, { method: 'DELETE' });
                const json = await res.json();
                if (json.success) {
                    this.notify(json.message, 'success');
                    this.showDeleteModal = false;
                    await this.loadServers();
                } else {
                    this.notify(json.message || json.error, 'error');
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
        },

        async showInstallCommand(server) {
            try {
                const res = await fetch(`api.php?route=/radius-servers/${server.id}`);
                const json = await res.json();
                if (json.success) {
                    this.installServer = json.data;
                    this.showInstallModal = true;
                }
            } catch (e) {
                this.notify('Erreur', 'error');
            }
        },

        getInstallCommand() {
            if (!this.installServer) return '';
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
            return `curl -sSL '${baseUrl}/node_install.php?code=${this.installServer.code}&token=${this.installServer.sync_token}' | sudo bash`;
        },

        downloadInstallScript(server) {
            const s = server || this.installServer;
            if (s) window.open(`api.php?route=/radius-servers/${s.id}/install-script`, '_blank');
        },

        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.notify('<?= __js('radius_servers.copied') ?>', 'success');
            });
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            if (diff < 60) return diff + 's';
            if (diff < 3600) return Math.floor(diff / 60) + 'min';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h';
            return date.toLocaleDateString();
        },

        notify(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        }
    };
}
</script>
