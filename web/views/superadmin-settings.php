<?php $pageTitle = __('superadmin.settings_title') ?? 'Paramètres Globaux';
$currentPage = 'superadmin-settings'; ?>

<div x-data="superadminSettings()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.settings_subtitle') ?? 'Configuration globale de la plateforme' ?>
            </p>
        </div>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <div x-show="!loading" class="space-y-6">
        <!-- Plateforme -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.section_platform') ?? 'Plateforme' ?>
                </h3>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.platform_name') ?? 'Nom de la plateforme' ?>
                    </label>
                    <input type="text" x-model="settings.platform_name"
                        class="w-full max-w-md px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                </div>

                <div class="flex items-center justify-between max-w-md">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?= __('superadmin.maintenance_mode') ?? 'Mode maintenance' ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('superadmin.maintenance_desc') ?? 'Empêcher l\'accès aux comptes admin (sauf superadmin)' ?>
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" x-model="maintenanceMode"
                            @change="settings.maintenance_mode = maintenanceMode ? '1' : '0'">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Inscription -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.section_registration') ?? 'Inscription' ?>
                </h3>
            </div>
            <div class="p-5 space-y-5">
                <div class="flex items-center justify-between max-w-md">
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?= __('superadmin.allow_registration') ?? 'Autoriser l\'inscription' ?>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('superadmin.allow_registration_desc') ?? 'Permettre la création de nouveaux comptes admin via la page d\'inscription' ?>
                        </p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" x-model="allowRegistration"
                            @change="settings.allow_registration = allowRegistration ? '1' : '0'">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('superadmin.max_admins') ?? 'Nombre maximum d\'admins' ?>
                    </label>
                    <div class="flex items-center gap-2 max-w-md">
                        <input type="number" x-model="settings.max_admins" min="0"
                            class="w-32 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('superadmin.zero_unlimited') ?? '0 = illimité' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modules par défaut -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('superadmin.section_default_modules') ?? 'Modules par défaut pour les nouveaux admins' ?>
                </h3>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <template x-for="mod in availableModules" :key="mod">
                        <label class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#1c2128] cursor-pointer transition-colors">
                            <input type="checkbox" :value="mod" x-model="defaultModules"
                                class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize" x-text="mod"></span>
                        </label>
                    </template>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
            <button @click="saveSettings()" :disabled="saving"
                class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                <span x-show="!saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
                <span x-show="saving"><?= __('common.loading') ?? 'Chargement...' ?></span>
            </button>
        </div>
    </div>
</div>

<script>
function superadminSettings() {
    return {
        settings: {},
        loading: true,
        saving: false,
        maintenanceMode: false,
        allowRegistration: true,
        defaultModules: [],
        availableModules: ['hotspot', 'captive-portal', 'loyalty', 'chat', 'sms', 'pppoe', 'whatsapp', 'telegram', 'analytics'],

        async init() {
            await this.loadSettings();
        },

        async loadSettings() {
            this.loading = true;
            try {
                const res = await fetch('api.php?route=/superadmin/settings', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    const s = data.settings;
                    this.settings = {
                        platform_name: s.platform_name?.value || 'RADIUS Manager',
                        maintenance_mode: s.maintenance_mode?.value || '0',
                        allow_registration: s.allow_registration?.value || '1',
                        max_admins: s.max_admins?.value || '0',
                        default_modules: s.default_modules?.value || '["hotspot","captive-portal"]'
                    };
                    this.maintenanceMode = this.settings.maintenance_mode === '1';
                    this.allowRegistration = this.settings.allow_registration === '1';
                    try {
                        this.defaultModules = JSON.parse(this.settings.default_modules);
                    } catch(e) {
                        this.defaultModules = ['hotspot', 'captive-portal'];
                    }
                }
            } catch (e) { showToast('Erreur chargement', 'error'); }
            this.loading = false;
        },

        async saveSettings() {
            this.saving = true;
            try {
                const payload = {
                    platform_name: this.settings.platform_name,
                    maintenance_mode: this.settings.maintenance_mode,
                    allow_registration: this.settings.allow_registration,
                    max_admins: this.settings.max_admins,
                    default_modules: JSON.stringify(this.defaultModules)
                };

                const res = await fetch('api.php?route=/superadmin/settings', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ settings: payload })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.saving = false;
        }
    };
}
</script>
