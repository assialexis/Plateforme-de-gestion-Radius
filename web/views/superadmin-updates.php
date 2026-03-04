<div class="space-y-6" x-data="updateManager()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('updates.title') ?>
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                <?= __('updates.description') ?>
            </p>
        </div>
        <div class="flex gap-2">
            <button @click="checkForUpdates()" class="btn-secondary flex items-center gap-2" :disabled="loading">
                <svg class="w-4 h-4" :class="checking && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <?= __('updates.check') ?>
            </button>
            <button @click="createBackup()" class="btn-secondary flex items-center gap-2" :disabled="loading">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <?= __('updates.backup') ?>
            </button>
        </div>
    </div>

    <!-- Version & Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Version actuelle -->
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('updates.current_version') ?></p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="'v' + status.version"></p>
                </div>
            </div>
        </div>

        <!-- Migrations -->
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center"
                    :class="status.migrations?.pending > 0 ? 'bg-yellow-100 dark:bg-yellow-500/20' : 'bg-green-100 dark:bg-green-500/20'">
                    <svg class="w-5 h-5" :class="status.migrations?.pending > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400'"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('updates.migrations') ?></p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">
                        <span x-text="status.migrations?.executed || 0"></span>
                        <span class="text-sm font-normal text-gray-400">/ <span x-text="status.migrations?.total || 0"></span></span>
                    </p>
                </div>
            </div>
            <template x-if="status.migrations?.pending > 0">
                <button @click="runMigrations()" class="w-full mt-2 btn-primary text-xs py-1.5" :disabled="loading">
                    <?= __('updates.run_migrations') ?> (<span x-text="status.migrations.pending"></span>)
                </button>
            </template>
        </div>

        <!-- Sauvegardes -->
        <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('updates.backups') ?></p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white" x-text="status.backups_count || 0"></p>
                </div>
            </div>
            <template x-if="status.latest_backup">
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="'<?= __('updates.latest') ?>: ' + status.latest_backup?.created_at"></p>
            </template>
        </div>
    </div>

    <!-- Mise à jour hors ligne (upload) -->
    <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            <?= __('updates.offline_update') ?>
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4"><?= __('updates.offline_desc') ?></p>

        <div class="flex items-center gap-4">
            <label class="flex-1">
                <div class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-6 text-center cursor-pointer hover:border-primary-400 dark:hover:border-primary-500 transition-colors"
                    :class="uploadFile && 'border-primary-500 dark:border-primary-500 bg-primary-50/50 dark:bg-primary-500/10'">
                    <input type="file" accept=".zip" class="absolute inset-0 opacity-0 cursor-pointer"
                        @change="uploadFile = $event.target.files[0]">
                    <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="uploadFile ? uploadFile.name : '<?= __js('updates.select_zip') ?>'"></p>
                </div>
            </label>
            <button @click="applyUpload()" class="btn-primary px-6 py-3" :disabled="!uploadFile || loading">
                <span x-show="!uploading"><?= __('updates.apply') ?></span>
                <span x-show="uploading" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <?= __('updates.applying') ?>
                </span>
            </button>
        </div>
    </div>

    <!-- Migrations détaillées -->
    <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200/60 dark:border-[#30363d] flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                </svg>
                <?= __('updates.migration_list') ?>
            </h3>
            <button @click="loadMigrations()" class="text-sm text-primary-600 hover:text-primary-700">
                <?= __('common.refresh') ?? 'Actualiser' ?>
            </button>
        </div>
        <div class="overflow-x-auto max-h-96 overflow-y-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 dark:bg-[#21262d]/50 text-gray-600 dark:text-gray-400 font-medium border-b border-gray-200/60 dark:border-[#30363d] sticky top-0">
                    <tr>
                        <th class="px-6 py-3"><?= __('updates.migration_name') ?></th>
                        <th class="px-6 py-3"><?= __('common.status') ?? 'Statut' ?></th>
                        <th class="px-6 py-3"><?= __('updates.executed_at') ?></th>
                        <th class="px-6 py-3"><?= __('updates.duration') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                    <template x-for="m in migrations" :key="m.name">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-[#21262d]/30">
                            <td class="px-6 py-3 font-mono text-xs" x-text="m.name"></td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="m.status === 'executed' ? 'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="m.status === 'executed' ? 'bg-green-500' : 'bg-yellow-500'"></span>
                                    <span x-text="m.status === 'executed' ? '<?= __js('updates.status_executed') ?>' : '<?= __js('updates.status_pending') ?>'"></span>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="m.executed_at || '-'"></td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="m.execution_time_ms ? m.execution_time_ms + ' ms' : '-'"></td>
                        </tr>
                    </template>
                    <template x-if="migrations.length === 0">
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400"><?= __('updates.no_migrations') ?></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sauvegardes -->
    <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200/60 dark:border-[#30363d]">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                <?= __('updates.backup_list') ?>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 dark:bg-[#21262d]/50 text-gray-600 dark:text-gray-400 font-medium border-b border-gray-200/60 dark:border-[#30363d]">
                    <tr>
                        <th class="px-6 py-3"><?= __('common.name') ?? 'Nom' ?></th>
                        <th class="px-6 py-3"><?= __('updates.version') ?></th>
                        <th class="px-6 py-3"><?= __('updates.size') ?></th>
                        <th class="px-6 py-3"><?= __('common.date') ?? 'Date' ?></th>
                        <th class="px-6 py-3 text-right"><?= __('common.actions') ?? 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                    <template x-for="b in backups" :key="b.name">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-[#21262d]/30">
                            <td class="px-6 py-3 font-mono text-xs" x-text="b.name"></td>
                            <td class="px-6 py-3">
                                <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-400 px-2 py-0.5 rounded-full" x-text="'v' + b.version"></span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="b.size_formatted"></td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="b.created_at"></td>
                            <td class="px-6 py-3 text-right">
                                <button @click="restoreBackup(b.name)"
                                    class="text-xs text-orange-600 hover:text-orange-700 dark:text-orange-400 dark:hover:text-orange-300 font-medium"
                                    :disabled="loading"
                                    x-show="b.has_database">
                                    <?= __('updates.restore') ?>
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-if="backups.length === 0">
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400"><?= __('updates.no_backups') ?></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Historique des mises à jour -->
    <div class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200/60 dark:border-[#30363d]">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <?= __('updates.history') ?>
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50/50 dark:bg-[#21262d]/50 text-gray-600 dark:text-gray-400 font-medium border-b border-gray-200/60 dark:border-[#30363d]">
                    <tr>
                        <th class="px-6 py-3"><?= __('updates.from') ?></th>
                        <th class="px-6 py-3"><?= __('updates.to') ?></th>
                        <th class="px-6 py-3"><?= __('common.status') ?? 'Statut' ?></th>
                        <th class="px-6 py-3"><?= __('updates.migrations') ?></th>
                        <th class="px-6 py-3"><?= __('common.date') ?? 'Date' ?></th>
                        <th class="px-6 py-3"><?= __('updates.started_by') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                    <template x-for="h in history" :key="h.id">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-[#21262d]/30">
                            <td class="px-6 py-3 text-xs" x-text="'v' + h.from_version"></td>
                            <td class="px-6 py-3 text-xs" x-text="'v' + h.to_version"></td>
                            <td class="px-6 py-3">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-700 dark:bg-green-500/20 dark:text-green-400': h.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-400': h.status === 'failed',
                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/20 dark:text-yellow-400': h.status === 'started',
                                        'bg-gray-100 text-gray-700 dark:bg-gray-500/20 dark:text-gray-400': h.status === 'rolled_back',
                                    }">
                                    <span x-text="h.status"></span>
                                </span>
                            </td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="h.migrations_run"></td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="h.started_at"></td>
                            <td class="px-6 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="h.started_by || '-'"></td>
                        </tr>
                    </template>
                    <template x-if="history.length === 0">
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400"><?= __('updates.no_history') ?></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function updateManager() {
    return {
        status: { version: '...', migrations: {}, backups_count: 0, latest_backup: null },
        migrations: [],
        backups: [],
        history: [],
        loading: false,
        checking: false,
        uploading: false,
        uploadFile: null,

        async init() {
            await this.loadStatus();
            await Promise.all([this.loadMigrations(), this.loadBackups(), this.loadHistory()]);
        },

        async loadStatus() {
            try {
                const r = await fetch('api.php?route=/superadmin/updates/status');
                const d = await r.json();
                if (d.success) this.status = d.data;
            } catch (e) { console.error(e); }
        },

        async loadMigrations() {
            try {
                const r = await fetch('api.php?route=/superadmin/updates/migrations');
                const d = await r.json();
                if (d.success) this.migrations = d.data.migrations || [];
            } catch (e) { console.error(e); }
        },

        async loadBackups() {
            try {
                const r = await fetch('api.php?route=/superadmin/updates/backups');
                const d = await r.json();
                if (d.success) this.backups = d.data || [];
            } catch (e) { console.error(e); }
        },

        async loadHistory() {
            try {
                const r = await fetch('api.php?route=/superadmin/updates/history');
                const d = await r.json();
                if (d.success) this.history = d.data || [];
            } catch (e) { console.error(e); }
        },

        async checkForUpdates() {
            this.checking = true;
            try {
                const r = await fetch('api.php?route=/superadmin/updates/check');
                const d = await r.json();
                if (d.success) {
                    if (d.data.update_available) {
                        this.notify('<?= __js('updates.update_available') ?>: v' + d.data.update.version, 'info');
                    } else {
                        this.notify('<?= __js('updates.up_to_date') ?>', 'success');
                    }
                }
            } catch (e) {
                this.notify('<?= __js('updates.check_error') ?>', 'error');
            }
            this.checking = false;
        },

        async runMigrations() {
            if (!confirm('<?= __js('updates.confirm_migrate') ?>')) return;
            this.loading = true;
            try {
                const r = await fetch('api.php?route=/superadmin/updates/migrate', { method: 'POST' });
                const d = await r.json();
                if (d.success) {
                    this.notify(d.message, 'success');
                    await this.loadStatus();
                    await this.loadMigrations();
                } else {
                    this.notify(d.message, 'error');
                }
            } catch (e) {
                this.notify('<?= __js('common.error') ?>', 'error');
            }
            this.loading = false;
        },

        async createBackup() {
            this.loading = true;
            try {
                const r = await fetch('api.php?route=/superadmin/updates/backup', { method: 'POST' });
                const d = await r.json();
                if (d.success) {
                    this.notify(d.message, 'success');
                    await this.loadBackups();
                    await this.loadStatus();
                } else {
                    this.notify(d.message, 'error');
                }
            } catch (e) {
                this.notify('<?= __js('common.error') ?>', 'error');
            }
            this.loading = false;
        },

        async restoreBackup(name) {
            if (!confirm('<?= __js('updates.confirm_restore') ?>')) return;
            this.loading = true;
            try {
                const r = await fetch('api.php?route=/superadmin/updates/restore', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ backup: name })
                });
                const d = await r.json();
                this.notify(d.message, d.success ? 'success' : 'error');
            } catch (e) {
                this.notify('<?= __js('common.error') ?>', 'error');
            }
            this.loading = false;
        },

        async applyUpload() {
            if (!this.uploadFile) return;
            if (!confirm('<?= __js('updates.confirm_update') ?>')) return;

            this.uploading = true;
            this.loading = true;
            try {
                const formData = new FormData();
                formData.append('update_file', this.uploadFile);

                const r = await fetch('api.php?route=/superadmin/updates/upload', {
                    method: 'POST',
                    body: formData
                });
                const d = await r.json();
                if (d.success) {
                    this.notify(d.message, 'success');
                    this.uploadFile = null;
                    await this.init();
                } else {
                    this.notify(d.message, 'error');
                }
            } catch (e) {
                this.notify('<?= __js('common.error') ?>', 'error');
            }
            this.uploading = false;
            this.loading = false;
        },

        notify(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('notify', { detail: { message, type } }));
        }
    };
}
</script>
