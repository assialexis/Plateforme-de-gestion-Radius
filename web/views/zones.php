<?php $currentPage = 'zones'; ?>
<div x-data="zonesPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                <?= __('zone.title')?>
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                <?= __('zone.subtitle')?>
            </p>
        </div>
        <button @click="openModal()"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <?= __('zone.new_zone')?>
        </button>
    </div>

    <!-- Info Box -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
        <div class="flex">
            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-700 dark:text-blue-300">
                <p class="font-medium mb-1">
                    <?= __('zone.how_it_works')?>
                </p>
                <ul class="list-disc list-inside space-y-1 text-blue-600 dark:text-blue-400">
                    <li>
                        <?= __('zone.info_1')?>
                    </li>
                    <li>
                        <?= __('zone.info_2')?>
                    </li>
                    <li>
                        <?= __('zone.info_3')?>
                    </li>
                    <li>
                        <?= __('zone.info_4')?>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex justify-center py-12">
        <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
    </div>

    <!-- Zones Grid -->
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="zone in zones" :key="zone.id">
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden hover:shadow-lg transition-shadow">
                <!-- Zone Header -->
                <div class="p-4 border-b border-gray-200 dark:border-[#30363d]"
                    :style="`background: linear-gradient(135deg, ${zone.color}15, ${zone.color}05)`">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                :style="`background-color: ${zone.color}20; color: ${zone.color}`">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white" x-text="zone.name"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="zone.code"></p>
                            </div>
                        </div>
                        <span x-show="zone.is_active"
                            class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <?= __('common.active')?>
                        </span>
                        <span x-show="!zone.is_active"
                            class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-[#21262d] dark:text-gray-400">
                            <?= __('common.inactive')?>
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                        x-text="zone.description || __('misc.no_description')"></p>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 divide-x divide-gray-200 dark:divide-[#30363d]">
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="zone.nas_count || 0">
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('zone.routers')?>
                        </div>
                    </div>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="zone.profiles_count || 0">
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('zone.profiles')?>
                        </div>
                    </div>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="zone.vouchers_count || 0">
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            <?= __('zone.vouchers')?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="p-4 bg-gray-50 dark:bg-[#0d1117] flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <a :href="'index.php?page=nas&zone=' + zone.id"
                            class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 font-medium">
                            <?= __('zone.view_routers')?>
                        </a>
                        <a :href="'index.php?page=topology&zone=' + zone.id"
                            class="inline-flex items-center gap-1 text-sm text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium"
                            :title="__('zone.topology')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <?= __('zone.topology')?>
                        </a>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="toggleZone(zone)"
                            class="p-2 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition-colors"
                            :title="zone.is_active ? __('common.deactivate') : __('common.activate')">
                            <svg x-show="zone.is_active" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            <svg x-show="!zone.is_active" class="w-4 h-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button @click="openModal(zone)"
                            class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                            :title="__('common.edit')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                        <button @click="deleteZone(zone)"
                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            :title="__('common.delete')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Empty State -->
        <div x-show="zones.length === 0" class="col-span-full">
            <div
                class="text-center py-12 bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d]">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                    <?= __('zone.empty')?>
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    <?= __('zone.empty_hint')?>
                </p>
                <button @click="openModal()"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?= __('common.create')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Create/Edit Zone -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title"
        role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" class="fixed inset-0 bg-gray-500/75 transition-opacity"
                @click="closeModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                class="inline-block align-bottom bg-white dark:bg-[#161b22] rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form @submit.prevent="saveZone()">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                                x-text="editingZone ? __('zone.edit_zone') : __('zone.new_zone')"></h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_name')?>
                                </label>
                                <input type="text" x-model="form.name" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-[#21262d] dark:text-white"
                                    placeholder="Ex: Zone Centre-Ville">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_code')?>
                                </label>
                                <div class="flex gap-2">
                                    <input type="text" x-model="form.code" :readonly="editingZone"
                                        :class="editingZone ? 'bg-gray-100 dark:bg-[#30363d] cursor-not-allowed' : ''"
                                        class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-[#21262d] dark:text-white font-mono"
                                        placeholder="<?= __('zone.code_placeholder')?>">
                                    <button type="button" x-show="!editingZone" @click="generateZoneCode()"
                                        :disabled="generatingCode"
                                        class="px-3 py-2 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors disabled:opacity-50"
                                        title="Générer un code unique">
                                        <svg x-show="!generatingCode" class="w-5 h-5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        <svg x-show="generatingCode" class="w-5 h-5 animate-spin" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                    </button>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">
                                    <span x-show="!editingZone">Laissez vide pour auto-générer un code sécurisé.</span>
                                    <span x-show="editingZone">Le code ne peut pas être modifié après création.</span>
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_description')?>
                                </label>
                                <textarea x-model="form.description" rows="2"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-[#21262d] dark:text-white"
                                    placeholder="Description de la zone..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_dns_name')?>
                                </label>
                                <input type="text" x-model="form.dns_name"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-[#21262d] dark:text-white"
                                    placeholder="<?= __('zone.form_dns_name_placeholder')?>">
                            </div>

                            <!-- Serveur RADIUS -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_radius_server') ?? 'Serveur RADIUS' ?>
                                </label>
                                <select x-model="form.radius_server_id"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-[#21262d] dark:text-white">
                                    <template x-for="rs in radiusServers" :key="rs.id">
                                        <option :value="rs.id" x-text="rs.name"></option>
                                    </template>
                                </select>
                                <p class="text-xs text-gray-400 mt-1"><?= __('zone.form_radius_server_help') ?? 'Serveur RADIUS qui gèrera les authentifications de cette zone' ?></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('zone.form_color')?>
                                </label>
                                <div class="flex items-center gap-3">
                                    <input type="color" x-model="form.color"
                                        class="w-12 h-10 p-1 border border-gray-300 dark:border-[#30363d] rounded-lg cursor-pointer">
                                    <div class="flex gap-2">
                                        <button type="button" @click="form.color = '#3b82f6'"
                                            class="w-8 h-8 rounded-lg bg-blue-500 hover:ring-2 ring-blue-300"></button>
                                        <button type="button" @click="form.color = '#10b981'"
                                            class="w-8 h-8 rounded-lg bg-green-500 hover:ring-2 ring-green-300"></button>
                                        <button type="button" @click="form.color = '#f59e0b'"
                                            class="w-8 h-8 rounded-lg bg-yellow-500 hover:ring-2 ring-yellow-300"></button>
                                        <button type="button" @click="form.color = '#ef4444'"
                                            class="w-8 h-8 rounded-lg bg-red-500 hover:ring-2 ring-red-300"></button>
                                        <button type="button" @click="form.color = '#8b5cf6'"
                                            class="w-8 h-8 rounded-lg bg-purple-500 hover:ring-2 ring-purple-300"></button>
                                        <button type="button" @click="form.color = '#ec4899'"
                                            class="w-8 h-8 rounded-lg bg-pink-500 hover:ring-2 ring-pink-300"></button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" x-model="form.is_active" id="is_active"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <?= __('zone.form_active')?>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 dark:bg-[#0d1117] flex justify-end gap-3">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            Annuler
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 flex items-center gap-2">
                            <svg x-show="saving" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="editingZone ? 'Mettre à jour' : 'Créer'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function zonesPage() {
        return {
            zones: [],
            radiusServers: [],
            loading: true,
            showModal: false,
            saving: false,
            generatingCode: false,
            editingZone: null,
            form: {
                name: '',
                code: '',
                description: '',
                dns_name: '',
                color: '#3b82f6',
                is_active: true,
                radius_server_id: ''
            },

            async init() {
                await Promise.all([this.loadZones(), this.loadRadiusServers()]);
            },

            async loadRadiusServers() {
                try {
                    const response = await API.get('/radius-servers/active');
                    this.radiusServers = response.data || [];
                } catch (error) {
                    // Ignorer si pas disponible
                }
            },

            async loadZones() {
                this.loading = true;
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data || [];
                } catch (error) {
                    showToast(error.message || 'Erreur lors du chargement des zones', 'error');
                } finally {
                    this.loading = false;
                }
            },

            openModal(zone = null) {
                this.editingZone = zone;
                if (zone) {
                    this.form = {
                        name: zone.name,
                        code: zone.code,
                        description: zone.description || '',
                        dns_name: zone.dns_name || '',
                        color: zone.color || '#3b82f6',
                        is_active: zone.is_active == 1,
                        radius_server_id: zone.radius_server_id || ''
                    };
                } else {
                    // Pré-sélectionner le serveur RADIUS par défaut
                    const defaultServer = this.radiusServers.find(s => s.is_default == 1);
                    this.form = {
                        name: '',
                        code: '',
                        description: '',
                        dns_name: '',
                        color: '#3b82f6',
                        is_active: true,
                        radius_server_id: defaultServer ? defaultServer.id : ''
                    };
                }
                this.showModal = true;
            },

            closeModal() {
                this.showModal = false;
                this.editingZone = null;
            },

            async generateZoneCode() {
                this.generatingCode = true;
                try {
                    const response = await API.get('/zones/generate-code');
                    if (response.data && response.data.code) {
                        this.form.code = response.data.code;
                    }
                } catch (error) {
                    showToast(__('zone.msg_code_error'), 'error');
                } finally {
                    this.generatingCode = false;
                }
            },

            async saveZone() {
                this.saving = true;
                try {
                    if (this.editingZone) {
                        await API.put('/zones/' + this.editingZone.id, this.form);
                        showToast(__('zone.msg_updated'));
                    } else {
                        await API.post('/zones', this.form);
                        showToast(__('zone.msg_created'));
                    }
                    this.closeModal();
                    await this.loadZones();
                } catch (error) {
                    showToast(error.message || __('zone.msg_save_error'), 'error');
                } finally {
                    this.saving = false;
                }
            },

            async toggleZone(zone) {
                try {
                    await API.post('/zones/' + zone.id + '/toggle');
                    await this.loadZones();
                    showToast(zone.is_active ? __('zone.msg_deactivated') : __('zone.msg_activated'));
                } catch (error) {
                    showToast(error.message || __('zone.msg_toggle_error'), 'error');
                }
            },

            async deleteZone(zone) {
                const hasItems = zone.nas_count > 0 || zone.profiles_count > 0 || zone.vouchers_count > 0;
                let message = __('zone.confirm_delete').replace(':name', zone.name);

                if (hasItems) {
                    message += '\n\n' + __('zone.confirm_delete_warning');
                }

                if (!confirm(message)) return;

                try {
                    await API.delete('/zones/' + zone.id);
                    showToast(__('zone.msg_deleted'));
                    await this.loadZones();
                } catch (error) {
                    showToast(error.message || __('zone.msg_delete_error'), 'error');
                }
            }
        };
    }
</script>