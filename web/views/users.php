<?php $pageTitle = __('page.users');
$currentPage = 'users'; ?>

<div x-data="usersPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('user.subtitle')?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('user.add_user')?>
            </button>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-orange-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-orange-700 dark:text-orange-300" x-text="stats.vendeurs || 0"></p>
                    <p class="text-xs text-orange-600 dark:text-orange-400">
                        <?= __('user.sellers')?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 border border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-700 dark:text-green-300" x-text="stats.gerants || 0"></p>
                    <p class="text-xs text-green-600 dark:text-green-400">
                        <?= __('user.managers')?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 dark:bg-[#0d1117]/20 rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d]">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gray-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-700 dark:text-gray-300" x-text="stats.clients || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        <?= __('user.clients')?>
                    </p>
                </div>
            </div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-300" x-text="stats.techniciens || 0">
                    </p>
                    <p class="text-xs text-purple-600 dark:text-purple-400">
                        <?= __('role.technicien') ?? 'Techniciens'?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="mb-6 flex flex-wrap items-center gap-2">
        <span class="text-sm text-gray-500 dark:text-gray-400">
            <?= __('user.filter_role')?>
        </span>
        <button @click="filterRole = null"
            :class="filterRole === null ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('user.all')?>
        </button>
        <button @click="filterRole = 'vendeur'"
            :class="filterRole === 'vendeur' ? 'bg-orange-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('user.sellers')?>
        </button>
        <button @click="filterRole = 'gerant'"
            :class="filterRole === 'gerant' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('user.managers')?>
        </button>
        <button @click="filterRole = 'technicien'"
            :class="filterRole === 'technicien' ? 'bg-purple-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('role.technicien') ?? 'Techniciens'?>
        </button>
        <button @click="filterRole = 'client'"
            :class="filterRole === 'client' ? 'bg-gray-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300'"
            class="px-3 py-1.5 text-sm font-medium rounded-lg transition-colors">
            <?= __('user.clients')?>
        </button>
    </div>

    <!-- Liste des utilisateurs -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#30363d]">
            <thead class="bg-gray-50 dark:bg-[#0d1117]">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_user')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_role')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_contact')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_vouchers_created')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_last_login')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_status')?>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <?= __('user.table_actions')?>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                <template x-for="user in filteredUsers" :key="user.id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center text-white font-bold"
                                    :class="{
                                         'bg-blue-600': user.role === 'admin',
                                         'bg-orange-600': user.role === 'vendeur',
                                         'bg-green-600': user.role === 'gerant',
                                         'bg-purple-600': user.role === 'technicien',
                                         'bg-gray-600': user.role === 'client'
                                     }" x-text="(user.full_name || user.username).charAt(0).toUpperCase()">
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white"
                                        x-text="user.full_name || user.username"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="'@' + user.username">
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="{
                                      'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': user.role === 'admin',
                                      'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400': user.role === 'vendeur',
                                      'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': user.role === 'gerant',
                                      'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400': user.role === 'technicien',
                                      'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-400': user.role === 'client'
                                  }" x-text="getRoleLabel(user.role)">
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white" x-text="user.email || '-'"></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400" x-text="user.phone || ''"></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="user.vouchers_count || 0"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            <span x-text="user.last_login ? formatDate(user.last_login) : __('user.never')"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="user.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'"
                                x-text="user.is_active ? __('user.active') : __('user.inactive')">
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button @click="toggleUser(user)" class="text-gray-400 hover:text-yellow-600"
                                    :title="user.is_active ? __('common.deactivate') : __('common.activate')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path x-show="user.is_active" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        <path x-show="!user.is_active" stroke-linecap="round" stroke-linejoin="round"
                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                                <button @click="editUser(user)" class="text-gray-400 hover:text-blue-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteUser(user)" class="text-gray-400 hover:text-red-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </template>
                <template x-if="filteredUsers.length === 0">
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <?= __('user.empty')?>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    <!-- Modal Ajouter/Modifier -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"
                    x-text="editMode ? __('user.edit_user') : __('user.add_user')"></h3>

                <form @submit.prevent="saveUser()">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('user.form_username')?>
                                </label>
                                <input type="text" x-model="form.username" required :disabled="editMode"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white disabled:opacity-50">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('user.form_full_name')?>
                                </label>
                                <input type="text" x-model="form.full_name"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('user.form_email')?>
                                </label>
                                <input type="email" x-model="form.email"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('user.form_phone')?>
                                </label>
                                <input type="tel" x-model="form.phone"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <span
                                    x-text="editMode ? __('user.form_password_edit') : __('user.form_password')"></span>
                            </label>
                            <input type="password" x-model="form.password" :required="!editMode" minlength="6"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('user.form_role')?>
                            </label>
                            <select x-model="form.role" required
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="vendeur">
                                    <?= __('user.role_vendeur')?>
                                </option>
                                <option value="gerant">
                                    <?= __('user.role_gerant')?>
                                </option>
                                <option value="technicien">
                                    <?= __('role.technicien') ?? 'Technicien'?>
                                </option>
                                <option value="client">
                                    <?= __('user.role_client')?>
                                </option>
                            </select>
                        </div>

                        <!-- Zones assignées (pour gérants, vendeurs et techniciens) - Afficher en premier -->
                        <div x-show="form.role === 'gerant' || form.role === 'vendeur' || form.role === 'technicien'"
                            class="p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <label class="block text-sm font-medium text-green-800 dark:text-green-200 mb-2">
                                <?= __('user.assigned_zones')?>
                                <span x-show="form.role === 'vendeur'" class="text-xs font-normal">(sélectionner pour
                                    voir les routeurs)</span>
                            </label>
                            <div class="space-y-2 max-h-32 overflow-y-auto">
                                <template x-for="zone in zones" :key="zone.id">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" :value="parseInt(zone.id)" x-model.number="form.zones"
                                            @change="onZoneChange()"
                                            class="w-4 h-4 text-green-600 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            <span x-text="zone.name"></span>
                                            <span class="text-xs text-gray-500"
                                                x-text="'(' + getNasCountForZone(zone.id) + ' routeurs)'"></span>
                                        </span>
                                    </label>
                                </template>
                                <template x-if="zones.length === 0">
                                    <p class="text-sm text-gray-500 italic">
                                        <?= __('user.no_zones_available')?>
                                    </p>
                                </template>
                            </div>
                        </div>

                        <!-- NAS assignés (pour vendeurs et techniciens) - Filtré par zones sélectionnées -->
                        <div x-show="form.role === 'vendeur' || form.role === 'technicien'"
                            class="p-3 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                            <label class="block text-sm font-medium text-orange-800 dark:text-orange-200 mb-2">
                                <?= __('user.assigned_nas')?>
                                <span class="text-xs font-normal" x-show="form.zones.length === 0">(
                                    <?= __('user.select_zone_first')?>)
                                </span>
                            </label>
                            <div class="space-y-2 max-h-40 overflow-y-auto">
                                <template x-if="form.zones.length === 0">
                                    <p class="text-sm text-gray-500 italic">
                                        <?= __('user.select_zones_first')?>
                                    </p>
                                </template>
                                <template x-if="form.zones.length > 0 && filteredNasList.length === 0">
                                    <p class="text-sm text-gray-500 italic">
                                        <?= __('user.no_routers_in_zones')?>
                                    </p>
                                </template>
                                <template x-for="nas in filteredNasList" :key="nas.id">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" :value="parseInt(nas.id)" x-model.number="form.nas"
                                            class="w-4 h-4 text-orange-600 border-gray-300 rounded">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            <span x-text="nas.shortname || nas.nasname"></span>
                                            <span class="text-xs text-gray-500"
                                                x-text="nas.router_id ? ' (' + nas.router_id + ')' : ''"></span>
                                            <span class="text-xs text-green-600 dark:text-green-400"
                                                x-text="' - ' + getZoneName(nas.zone_id)"></span>
                                        </span>
                                    </label>
                                </template>
                            </div>
                            <!-- Bouton pour sélectionner tous les routeurs des zones -->
                            <div x-show="filteredNasList.length > 0"
                                class="mt-2 pt-2 border-t border-orange-200 dark:border-orange-700">
                                <button type="button" @click="selectAllNasInZones()"
                                    class="text-xs text-orange-600 dark:text-orange-400 hover:underline">
                                    <?= __('user.select_all_routers')?>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" x-model="form.is_active" id="is_active"
                                class="w-4 h-4 text-blue-600 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                <?= __('user.active_account')?>
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <span x-text="editMode ? __('common.save') : __('common.add')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function usersPage() {
        return {
            users: [],
            zones: [],
            nasList: [],
            filterRole: null,
            showModal: false,
            editMode: false,
            editId: null,
            currentUser: JSON.parse(localStorage.getItem('user') || '{}'),
            stats: {},
            form: {
                username: '',
                full_name: '',
                email: '',
                phone: '',
                password: '',
                role: 'gerant',
                zones: [],
                nas: [],
                is_active: true
            },

            get filteredUsers() {
                if (!this.filterRole) {
                    return this.users;
                }
                return this.users.filter(u => u.role === this.filterRole);
            },

            // Liste des NAS filtrée par zones sélectionnées
            get filteredNasList() {
                if (this.form.zones.length === 0) {
                    return [];
                }
                // Convertir les IDs en nombres pour la comparaison
                const selectedZoneIds = this.form.zones.map(id => parseInt(id));
                return this.nasList.filter(nas => {
                    const nasZoneId = parseInt(nas.zone_id);
                    return selectedZoneIds.includes(nasZoneId);
                });
            },

            async init() {
                await Promise.all([this.loadUsers(), this.loadZones(), this.loadNas(), this.loadStats()]);
            },

            async loadUsers() {
                try {
                    const response = await API.get('/users');
                    this.users = response.data || [];
                } catch (error) {
                    showToast(__('api.error_loading'), 'error');
                }
            },

            async loadZones() {
                try {
                    const response = await API.get('/zones');
                    this.zones = response.data || [];
                } catch (error) {
                    console.error('Erreur chargement zones:', error);
                }
            },

            async loadNas() {
                try {
                    const response = await API.get('/nas');
                    this.nasList = response.data || [];
                } catch (error) {
                    console.error('Erreur chargement NAS:', error);
                }
            },

            async loadStats() {
                try {
                    const response = await API.get('/users/stats');
                    const data = response.data || {};
                    this.stats = {};
                    (data.by_role || []).forEach(r => {
                        if (r.role === 'vendeur') this.stats.vendeurs = r.count;
                        else if (r.role === 'gerant') this.stats.gerants = r.count;
                        else if (r.role === 'technicien') this.stats.techniciens = r.count;
                        else if (r.role === 'client') this.stats.clients = r.count;
                    });
                } catch (error) {
                    console.error('Erreur chargement stats:', error);
                }
            },

            resetForm() {
                this.form = {
                    username: '',
                    full_name: '',
                    email: '',
                    phone: '',
                    password: '',
                    role: 'gerant',
                    zones: [],
                    nas: [],
                    is_active: true
                };
                this.editId = null;
            },

            editUser(user) {
                this.editMode = true;
                this.editId = user.id;
                this.form = {
                    username: user.username,
                    full_name: user.full_name || '',
                    email: user.email || '',
                    phone: user.phone || '',
                    password: '',
                    role: user.role,
                    // Convertir les IDs en entiers pour la comparaison avec les checkboxes
                    zones: (user.zones || []).map(z => parseInt(z.zone_id)),
                    nas: (user.nas || []).map(n => parseInt(n.nas_id)),
                    is_active: user.is_active == 1
                };
                this.showModal = true;
            },

            async saveUser() {
                try {
                    const data = { ...this.form };
                    if (!data.password) delete data.password;

                    if (this.editMode) {
                        await API.put(`/users/${this.editId}`, data);
                        showToast(__('user.msg_updated'));
                    } else {
                        await API.post('/users', data);
                        showToast(__('user.msg_created'));
                    }
                    this.showModal = false;
                    await Promise.all([this.loadUsers(), this.loadStats()]);
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async toggleUser(user) {
                try {
                    await API.post(`/users/${user.id}/toggle`);
                    showToast(user.is_active ? __('user.msg_deactivated') : __('user.msg_activated'));
                    await this.loadUsers();
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            async deleteUser(user) {
                if (!confirm(__('confirm.delete_message'))) return;

                try {
                    await API.delete(`/users/${user.id}`);
                    showToast(__('user.msg_deleted'));
                    await Promise.all([this.loadUsers(), this.loadStats()]);
                } catch (error) {
                    showToast(error.message, 'error');
                }
            },

            getRoleLabel(role) {
                const labels = {
                    'admin': __('user.role_admin'),
                    'vendeur': __('user.role_vendeur'),
                    'gerant': __('user.role_gerant'),
                    'technicien': __('role.technicien') || 'Technicien',
                    'client': __('user.role_client')
                };
                return labels[role] || role;
            },

            formatDate(date) {
                if (!date) return '';
                return new Date(date).toLocaleDateString('fr-FR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            },

            // Obtenir le nombre de NAS dans une zone
            getNasCountForZone(zoneId) {
                const zid = parseInt(zoneId);
                return this.nasList.filter(nas => parseInt(nas.zone_id) === zid).length;
            },

            // Obtenir le nom d'une zone par son ID
            getZoneName(zoneId) {
                const zid = parseInt(zoneId);
                const zone = this.zones.find(z => parseInt(z.id) === zid);
                return zone ? zone.name : __('common.no_zone');
            },

            // Appelé quand les zones changent - nettoyer les NAS qui ne sont plus dans les zones sélectionnées
            onZoneChange() {
                // Convertir les IDs de zones en nombres
                const selectedZoneIds = this.form.zones.map(id => parseInt(id));
                // Filtrer les NAS sélectionnés pour ne garder que ceux dans les zones sélectionnées
                this.form.nas = this.form.nas.filter(nasId => {
                    const nas = this.nasList.find(n => parseInt(n.id) === parseInt(nasId));
                    return nas && selectedZoneIds.includes(parseInt(nas.zone_id));
                });
            },

            // Sélectionner tous les NAS des zones sélectionnées
            selectAllNasInZones() {
                this.form.nas = this.filteredNasList.map(nas => parseInt(nas.id));
            }
        };
    }
</script>