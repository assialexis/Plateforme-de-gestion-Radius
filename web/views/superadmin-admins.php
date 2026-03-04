<?php $pageTitle = __('superadmin.admins_title') ?? 'Gestion des Admins';
$currentPage = 'superadmin-admins'; ?>

<div x-data="superadminAdmins()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.admins_subtitle') ?? 'Gérer les comptes administrateurs de la plateforme' ?>
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="showModal = true; editMode = false; resetForm()"
                class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('superadmin.add_admin') ?? 'Nouvel Admin' ?>
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300" x-text="stats.total_admins || 0"></p>
                    <p class="text-xs text-blue-600 dark:text-blue-400"><?= __('superadmin.total_admins') ?? 'Total Admins' ?></p>
                </div>
            </div>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-emerald-700 dark:text-emerald-300" x-text="stats.active_admins || 0"></p>
                    <p class="text-xs text-emerald-600 dark:text-emerald-400"><?= __('superadmin.active_admins') ?? 'Actifs' ?></p>
                </div>
            </div>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-red-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-700 dark:text-red-300" x-text="stats.inactive_admins || 0"></p>
                    <p class="text-xs text-red-600 dark:text-red-400"><?= __('superadmin.inactive_admins') ?? 'Inactifs' ?></p>
                </div>
            </div>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-purple-600 rounded-lg">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-purple-700 dark:text-purple-300" x-text="stats.total_superadmins || 0"></p>
                    <p class="text-xs text-purple-600 dark:text-purple-400"><?= __('superadmin.total_superadmins') ?? 'Super Admins' ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search & Filter -->
    <div class="flex flex-col sm:flex-row gap-3 mb-4">
        <div class="flex-1 relative">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" x-model="search" @input="filterAdmins()"
                placeholder="<?= __('superadmin.search_admin') ?? 'Rechercher un admin...' ?>"
                class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 focus:border-transparent">
        </div>
        <select x-model="roleFilter" @change="filterAdmins()"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
            <option value=""><?= __('superadmin.all_roles') ?? 'Tous les rôles' ?></option>
            <option value="superadmin"><?= __('superadmin.role_superadmin') ?? 'Super Admin' ?></option>
            <option value="admin"><?= __('superadmin.role_admin') ?? 'Admin' ?></option>
        </select>
        <select x-model="statusFilter" @change="filterAdmins()"
            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
            <option value=""><?= __('superadmin.all_status') ?? 'Tous les statuts' ?></option>
            <option value="active"><?= __('superadmin.active') ?? 'Actif' ?></option>
            <option value="inactive"><?= __('superadmin.inactive') ?? 'Inactif' ?></option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-[#161b22] border-b border-gray-200 dark:border-[#30363d]">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_admin') ?? 'Admin' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_role') ?? 'Rôle' ?></th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_email') ?? 'Email' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_status') ?? 'Statut' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_users') ?? 'Utilisateurs' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_vouchers') ?? 'Vouchers' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_zones') ?? 'Zones' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_credits') ?? 'CRT' ?></th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CSMS</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('superadmin.col_last_login') ?? 'Dernière connexion' ?></th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.actions') ?? 'Actions' ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-[#21262d]">
                    <template x-for="admin in filteredAdmins" :key="admin.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1c2128] transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-semibold"
                                        :class="admin.role === 'superadmin' ? 'bg-red-600' : 'bg-blue-600'"
                                        x-text="admin.username.charAt(0).toUpperCase()"></div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-gray-100" x-text="admin.full_name || admin.username"></p>
                                        <p class="text-xs text-gray-500" x-text="'@' + admin.username"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                    :class="admin.role === 'superadmin' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'"
                                    x-text="admin.role === 'superadmin' ? 'Super Admin' : 'Admin'"></span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400" x-text="admin.email"></td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="admin.is_active == 1 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                        x-text="admin.is_active == 1 ? '<?= __js('superadmin.active') ?? 'Actif' ?>' : '<?= __js('superadmin.inactive') ?? 'Inactif' ?>'"></span>
                                    <span x-show="admin.totp_enabled == 1" class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" title="2FA activé">2FA</span>
                                    <span x-show="admin.email_verified == 0"
                                        class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400"
                                        title="<?= __js('email.not_verified_badge') ?? 'Email non vérifié' ?>">
                                        <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400" x-text="admin.sub_users || 0"></td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400" x-text="admin.total_vouchers || 0"></td>
                            <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400" x-text="admin.total_zones || 0"></td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openCreditModal(admin, 'crt')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors group/crt" title="Ajuster CRT">
                                    <span class="font-medium" :class="parseFloat(admin.credit_balance || 0) > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'" x-text="parseFloat(admin.credit_balance || 0).toFixed(2)"></span>
                                    <svg class="w-3 h-3 text-gray-300 group-hover/crt:text-emerald-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button @click="openCreditModal(admin, 'csms')" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors group/sms" title="Ajuster CSMS">
                                    <span class="font-medium" :class="parseFloat(admin.sms_credit_balance || 0) > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400 dark:text-gray-500'" x-text="parseFloat(admin.sms_credit_balance || 0).toFixed(0)"></span>
                                    <svg class="w-3 h-3 text-gray-300 group-hover/sms:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                </button>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs" x-text="admin.last_login ? new Date(admin.last_login).toLocaleString() : '-'"></td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="editAdmin(admin)"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        :title="'<?= __js('common.edit') ?? 'Modifier' ?>'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button @click="toggleAdmin(admin)"
                                        class="p-1.5 rounded-lg transition-colors"
                                        :class="admin.is_active == 1 ? 'text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20' : 'text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20'"
                                        :title="admin.is_active == 1 ? '<?= __js('superadmin.deactivate') ?? 'Désactiver' ?>' : '<?= __js('superadmin.activate') ?? 'Activer' ?>'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                        </svg>
                                    </button>
                                    <button x-show="admin.email_verified == 0" @click="verifyEmail(admin)"
                                        class="p-1.5 text-gray-400 hover:text-green-600 dark:hover:text-green-400 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                                        title="<?= __js('email.verify_manually') ?? 'Vérifier manuellement' ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
                                    <button x-show="admin.email_verified == 0 && admin.email" @click="resendVerification(admin)"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        title="<?= __js('email.resend_verification') ?? 'Renvoyer email de vérification' ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                    <button x-show="admin.totp_enabled == 1" @click="reset2fa(admin)"
                                        class="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                        title="<?= __js('superadmin.reset_2fa') ?? 'Réinitialiser 2FA' ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteAdmin(admin)"
                                        class="p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        :title="'<?= __js('common.delete') ?? 'Supprimer' ?>'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="filteredAdmins.length === 0">
                        <td colspan="11" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            <?= __('superadmin.no_admins') ?? 'Aucun admin trouvé' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Créer/Modifier -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-lg border border-gray-200 dark:border-[#30363d]"
            @click.away="showModal = false">
            <div class="flex items-center justify-between p-5 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100"
                    x-text="editMode ? '<?= __js('superadmin.edit_admin') ?? 'Modifier l\\\'admin' ?>' : '<?= __js('superadmin.add_admin') ?? 'Nouvel Admin' ?>'"></h3>
                <button @click="showModal = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('user.username') ?? 'Nom d\'utilisateur' ?></label>
                        <input type="text" x-model="form.username" :disabled="editMode"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500 disabled:opacity-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('user.full_name') ?? 'Nom complet' ?></label>
                        <input type="text" x-model="form.full_name"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('user.email') ?? 'Email' ?></label>
                        <input type="email" x-model="form.email"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('user.phone') ?? 'Téléphone' ?></label>
                        <input type="text" x-model="form.phone"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('superadmin.select_role') ?? 'Rôle' ?></label>
                    <select x-model="form.role"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                        <option value="admin"><?= __('superadmin.role_admin') ?? 'Admin' ?></option>
                        <option value="superadmin"><?= __('superadmin.role_superadmin') ?? 'Super Admin' ?></option>
                    </select>
                    <p x-show="form.role === 'superadmin'" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                        <?= __('superadmin.superadmin_warning') ?? 'Un Super Admin a un accès complet à toute la plateforme' ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('user.password') ?? 'Mot de passe' ?>
                        <span x-show="editMode" class="text-xs text-gray-400">(<?= __('superadmin.leave_empty') ?? 'laisser vide pour ne pas modifier' ?>)</span>
                    </label>
                    <input type="password" x-model="form.password"
                        class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500">
                </div>
                <div class="flex items-center gap-2">
                    <input type="checkbox" x-model="form.is_active" id="admin_active"
                        class="rounded border-gray-300 dark:border-gray-600 text-red-600 focus:ring-red-500">
                    <label for="admin_active" class="text-sm text-gray-700 dark:text-gray-300"><?= __('superadmin.account_active') ?? 'Compte actif' ?></label>
                </div>
            </div>
            <div class="flex justify-end gap-3 p-5 border-t border-gray-200 dark:border-[#30363d]">
                <button @click="showModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <?= __('common.cancel') ?? 'Annuler' ?>
                </button>
                <button @click="saveAdmin()" :disabled="saving"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!saving" x-text="editMode ? '<?= __js('common.save') ?? 'Enregistrer' ?>' : '<?= __js('superadmin.create') ?? 'Créer' ?>'"></span>
                    <span x-show="saving"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>
    </div>
    <!-- Modal Ajustement Crédits -->
    <div x-show="showCreditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCreditModal = false"></div>
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl w-full max-w-sm border border-gray-200 dark:border-[#30363d]">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200 dark:border-[#30363d]">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                        :class="creditType === 'crt' ? 'bg-emerald-600' : 'bg-blue-600'"
                        x-text="creditType === 'crt' ? 'C' : 'S'"></div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                            x-text="creditType === 'crt' ? '<?= __js('superadmin.adjust_crt') ?? 'Ajuster CRT' ?>' : '<?= __js('superadmin.adjust_csms') ?? 'Ajuster CSMS' ?>'"></h3>
                        <p class="text-[11px] text-gray-500" x-text="creditTarget?.full_name || creditTarget?.username"></p>
                    </div>
                </div>
                <button @click="showCreditModal = false" class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="px-5 py-4 space-y-4">
                <!-- Solde actuel -->
                <div class="flex items-center justify-between px-3 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1117]">
                    <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('superadmin.current_balance') ?? 'Solde actuel' ?></span>
                    <span class="text-sm font-bold" :class="creditType === 'crt' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'"
                        x-text="(creditType === 'crt' ? parseFloat(creditTarget?.credit_balance || 0).toFixed(2) + ' CRT' : parseFloat(creditTarget?.sms_credit_balance || 0).toFixed(0) + ' CSMS')"></span>
                </div>

                <!-- Type: ajouter / retirer -->
                <div class="flex gap-2">
                    <button @click="creditAction = 'add'"
                        :class="creditAction === 'add' ? 'bg-emerald-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400'"
                        class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('superadmin.credit_add') ?? 'Ajouter' ?>
                    </button>
                    <button @click="creditAction = 'remove'"
                        :class="creditAction === 'remove' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400'"
                        class="flex-1 py-2 text-xs font-medium rounded-lg transition-colors flex items-center justify-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
                        <?= __('superadmin.credit_remove') ?? 'Retirer' ?>
                    </button>
                </div>

                <!-- Crédits + Montant (liés) -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Nombre de crédits -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1" x-text="creditType === 'crt' ? '<?= __js('superadmin.nb_credits') ?? 'Nb crédits (CRT)' ?>' : '<?= __js('superadmin.nb_csms') ?? 'Nb crédits (CSMS)' ?>'"></label>
                        <input type="number" x-model.number="creditAmount" min="0" :step="creditType === 'crt' ? '0.01' : '1'" placeholder="0"
                            @input="creditMoneyAmount = creditType === 'crt' ? Math.round(creditAmount * crtRate) : Math.round(creditAmount * csmsRate)"
                            class="w-full px-3 py-2 text-base font-bold text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2"
                            :class="creditAction === 'add' ? 'focus:ring-emerald-500' : 'focus:ring-red-500'">
                    </div>
                    <!-- Montant en devise (éditable, indépendant des crédits) -->
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                            <?= __('superadmin.money_amount') ?? 'Montant' ?> (<span x-text="creditCurrency"></span>)
                        </label>
                        <input type="number" x-model.number="creditMoneyAmount" min="0" step="1" placeholder="0"
                            class="w-full px-3 py-2 text-base font-bold text-center border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gray-400">
                    </div>
                </div>

                <!-- Taux -->
                <p class="text-[10px] text-gray-400 dark:text-gray-500 text-center" x-text="creditType === 'crt' ? '1 CRT = ' + crtRate + ' ' + creditCurrency : '1 CSMS = ' + csmsRate + ' ' + creditCurrency"></p>

                <!-- Montants rapides (en crédits) -->
                <div class="flex gap-1.5">
                    <template x-for="q in (creditType === 'crt' ? [50, 100, 500, 1000] : [10, 50, 100, 500])" :key="q">
                        <button type="button" @click="creditAmount = q; creditMoneyAmount = creditType === 'crt' ? Math.round(q * crtRate) : Math.round(q * csmsRate)"
                            class="flex-1 py-1 text-[11px] font-medium rounded-md transition-colors"
                            :class="creditAmount === q ? (creditType === 'crt' ? 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400') : 'bg-gray-100 dark:bg-[#21262d] text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                            x-text="q"></button>
                    </template>
                </div>

                <!-- Raison -->
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1"><?= __('superadmin.credit_reason') ?? 'Raison (optionnel)' ?></label>
                    <input type="text" x-model="creditReason" placeholder="<?= __('superadmin.credit_reason_placeholder') ?? 'Ex: Bonus, correction...' ?>"
                        class="w-full px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-gray-400">
                </div>

                <!-- Résumé -->
                <div x-show="creditAmount > 0" class="flex items-center justify-between px-3 py-2 rounded-lg border"
                    :class="creditAction === 'add' ? 'bg-emerald-50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-800/40' : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800/40'">
                    <div>
                        <p class="text-[10px]" :class="creditAction === 'add' ? 'text-emerald-600 dark:text-emerald-500' : 'text-red-600 dark:text-red-500'"
                            x-text="(creditAction === 'add' ? '+' : '-') + creditAmount + (creditType === 'crt' ? ' CRT' : ' CSMS')"></p>
                        <p class="text-xs font-medium" :class="creditAction === 'add' ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-700 dark:text-red-400'"><?= __('superadmin.new_balance') ?? 'Nouveau solde' ?></p>
                    </div>
                    <span class="text-sm font-bold" :class="creditAction === 'add' ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300'"
                        x-text="(() => {
                            const current = creditType === 'crt' ? parseFloat(creditTarget?.credit_balance || 0) : parseFloat(creditTarget?.sms_credit_balance || 0);
                            const result = creditAction === 'add' ? current + creditAmount : current - creditAmount;
                            return (creditType === 'crt' ? result.toFixed(2) + ' CRT' : Math.round(result) + ' CSMS');
                        })()"></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-2 px-5 py-3 border-t border-gray-200 dark:border-[#30363d]">
                <button @click="showCreditModal = false"
                    class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    <?= __('common.cancel') ?? 'Annuler' ?>
                </button>
                <button @click="submitCreditAdjustment()" :disabled="!creditAmount || creditAmount <= 0 || savingCredit"
                    class="px-4 py-1.5 text-sm font-medium text-white rounded-lg transition-colors disabled:opacity-50"
                    :class="creditAction === 'add' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-red-600 hover:bg-red-700'">
                    <span x-show="!savingCredit" x-text="creditAction === 'add' ? '<?= __js('superadmin.credit_add') ?? 'Ajouter' ?>' : '<?= __js('superadmin.credit_remove') ?? 'Retirer' ?>'"></span>
                    <span x-show="savingCredit"><?= __('common.loading') ?? '...' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function superadminAdmins() {
    return {
        admins: [],
        filteredAdmins: [],
        stats: {},
        search: '',
        roleFilter: '',
        statusFilter: '',
        showModal: false,
        editMode: false,
        editId: null,
        saving: false,
        form: { username: '', full_name: '', email: '', phone: '', password: '', role: 'admin', is_active: true },

        // Credit adjustment
        showCreditModal: false,
        creditTarget: null,
        creditType: 'crt',
        creditAction: 'add',
        creditAmount: 0,
        creditMoneyAmount: 0,
        creditReason: '',
        savingCredit: false,
        crtRate: 100,
        csmsRate: 50,
        creditCurrency: 'XOF',

        async init() {
            await Promise.all([this.loadAdmins(), this.loadStats(), this.loadSettings()]);
        },

        async loadAdmins() {
            try {
                const res = await fetch('api.php?route=/superadmin/admins', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.admins = data.admins;
                    this.filterAdmins();
                }
            } catch (e) { showToast('Erreur chargement admins', 'error'); }
        },

        async loadStats() {
            try {
                const res = await fetch('api.php?route=/superadmin/stats', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) this.stats = data.stats;
            } catch (e) {}
        },

        filterAdmins() {
            let result = [...this.admins];
            if (this.search) {
                const s = this.search.toLowerCase();
                result = result.filter(a =>
                    (a.username || '').toLowerCase().includes(s) ||
                    (a.full_name || '').toLowerCase().includes(s) ||
                    (a.email || '').toLowerCase().includes(s)
                );
            }
            if (this.roleFilter) result = result.filter(a => a.role === this.roleFilter);
            if (this.statusFilter === 'active') result = result.filter(a => a.is_active == 1);
            if (this.statusFilter === 'inactive') result = result.filter(a => a.is_active == 0);
            this.filteredAdmins = result;
        },

        resetForm() {
            this.form = { username: '', full_name: '', email: '', phone: '', password: '', role: 'admin', is_active: true };
            this.editId = null;
        },

        editAdmin(admin) {
            this.editMode = true;
            this.editId = admin.id;
            this.form = {
                username: admin.username,
                full_name: admin.full_name || '',
                email: admin.email || '',
                phone: admin.phone || '',
                password: '',
                role: admin.role || 'admin',
                is_active: admin.is_active == 1
            };
            this.showModal = true;
        },

        async saveAdmin() {
            this.saving = true;
            try {
                const url = this.editMode
                    ? `api.php?route=/superadmin/admins/${this.editId}`
                    : 'api.php?route=/superadmin/admins';
                const method = this.editMode ? 'PUT' : 'POST';

                const body = { ...this.form, is_active: this.form.is_active ? 1 : 0, role: this.form.role };
                if (this.editMode && !body.password) delete body.password;

                const res = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    this.showModal = false;
                    await this.init();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) { showToast('Erreur', 'error'); }
            this.saving = false;
        },

        async toggleAdmin(admin) {
            if (!confirm(admin.is_active == 1
                ? '<?= __js('superadmin.confirm_deactivate') ?? 'Désactiver cet admin ?' ?>'
                : '<?= __js('superadmin.confirm_activate') ?? 'Activer cet admin ?' ?>')) return;
            try {
                const res = await fetch(`api.php?route=/superadmin/admins/${admin.id}/toggle`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.init();
                } else showToast(data.message, 'error');
            } catch (e) { showToast('Erreur', 'error'); }
        },

        async deleteAdmin(admin) {
            if (!confirm('<?= __js('superadmin.confirm_delete_admin') ?? 'Supprimer cet admin et tous ses sous-utilisateurs ?' ?>')) return;
            try {
                const res = await fetch(`api.php?route=/superadmin/admins/${admin.id}`, {
                    method: 'DELETE',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.init();
                } else showToast(data.message, 'error');
            } catch (e) { showToast('Erreur', 'error'); }
        },

        async reset2fa(admin) {
            if (!confirm('<?= __js('superadmin.confirm_reset_2fa') ?? 'Réinitialiser la 2FA pour cet utilisateur ? Il devra la reconfigurer.' ?>')) return;
            try {
                const res = await fetch(`api.php?route=/superadmin/admins/${admin.id}/reset-2fa`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.init();
                } else showToast(data.message, 'error');
            } catch (e) { showToast('Erreur', 'error'); }
        },

        async verifyEmail(admin) {
            if (!confirm('<?= __js('email.confirm_verify_manually') ?? "Vérifier manuellement l\'email de cet admin ?" ?>')) return;
            try {
                const res = await fetch(`api.php?route=/superadmin/admins/${admin.id}/verify-email`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadAdmins();
                } else showToast(data.message, 'error');
            } catch (e) { showToast('Erreur', 'error'); }
        },

        async resendVerification(admin) {
            try {
                const res = await fetch(`api.php?route=/superadmin/admins/${admin.id}/resend-verification`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                } else showToast(data.message, 'error');
            } catch (e) { showToast('Erreur', 'error'); }
        },

        async loadSettings() {
            try {
                const res = await fetch('api.php?route=/superadmin/settings', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success && data.settings) {
                    this.crtRate = parseFloat(data.settings.credit_exchange_rate?.value) || 100;
                    this.csmsRate = parseFloat(data.settings.sms_credit_cost_fcfa?.value) || 50;
                    this.creditCurrency = data.settings.credit_currency?.value || 'XOF';
                }
            } catch (e) {}
        },

        openCreditModal(admin, type) {
            this.creditTarget = admin;
            this.creditType = type;
            this.creditAction = 'add';
            this.creditAmount = 0;
            this.creditMoneyAmount = 0;
            this.creditReason = '';
            this.showCreditModal = true;
        },

        async submitCreditAdjustment() {
            if (!this.creditAmount || this.creditAmount <= 0) return;
            this.savingCredit = true;

            const amount = this.creditAction === 'add' ? this.creditAmount : -this.creditAmount;
            const endpoint = this.creditType === 'crt'
                ? `api.php?route=/superadmin/admins/${this.creditTarget.id}/adjust-credits`
                : `api.php?route=/superadmin/admins/${this.creditTarget.id}/adjust-sms-credits`;

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ amount, reason: this.creditReason })
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    this.showCreditModal = false;
                    await this.loadAdmins();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) {
                showToast('Erreur', 'error');
            }
            this.savingCredit = false;
        }
    };
}
</script>
