<?php $pageTitle = __('superadmin.permissions_title') ?? 'Rôles & Permissions';
$currentPage = 'superadmin-permissions'; ?>

<div x-data="superadminPermissions()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('superadmin.permissions_subtitle') ?? 'Définir les permissions par défaut pour chaque rôle' ?>
            </p>
        </div>
    </div>

    <!-- Tabs Rôles -->
    <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
        <template x-for="r in roles" :key="r.code">
            <button @click="selectRole(r.code)"
                class="px-4 py-2 text-sm font-medium rounded-lg whitespace-nowrap transition-colors"
                :class="activeRole === r.code
                    ? 'bg-red-600 text-white'
                    : 'bg-white dark:bg-[#161b22] text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-[#30363d] hover:bg-gray-50 dark:hover:bg-[#1c2128]'"
                x-text="r.label"></button>
        </template>
    </div>

    <!-- Permissions Grid -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
        <div x-show="loading" class="p-8 text-center text-gray-500">
            <?= __('common.loading') ?? 'Chargement...' ?>
        </div>
        <div x-show="!loading">
            <template x-for="(perms, category) in groupedPermissions" :key="category">
                <div class="border-b border-gray-100 dark:border-[#21262d] last:border-0">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider" x-text="categoryLabels[category] || category"></h3>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-[#21262d]/50">
                        <template x-for="perm in perms" :key="perm.id">
                            <div class="flex items-center justify-between px-5 py-3 hover:bg-gray-50/50 dark:hover:bg-[#1c2128]/50 transition-colors">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100" x-text="perm.name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="perm.description"></p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" class="sr-only peer"
                                        :checked="perm.granted == 1"
                                        @change="togglePermission(perm)">
                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-red-300 dark:peer-focus:ring-red-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Save Button -->
    <div class="mt-4 flex justify-end" x-show="!loading">
        <button @click="savePermissions()" :disabled="saving"
            class="px-6 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
            <span x-show="!saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
            <span x-show="saving"><?= __('common.loading') ?? 'Chargement...' ?></span>
        </button>
    </div>

    <!-- Section Surcharges Utilisateur -->
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
            <?= __('superadmin.user_overrides') ?? 'Surcharges par utilisateur' ?>
        </h2>
        <div class="flex gap-3 mb-4">
            <div class="flex-1 relative">
                <input type="text" x-model="userSearch"
                    placeholder="<?= __('superadmin.search_user') ?? 'Rechercher un utilisateur par nom ou username...' ?>"
                    class="w-full pl-4 pr-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-red-500"
                    @keydown.enter="searchUser()">
            </div>
            <button @click="searchUser()"
                class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-lg hover:bg-gray-700 transition-colors">
                <?= __('common.search') ?? 'Rechercher' ?>
            </button>
        </div>

        <div x-show="selectedUser" class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 flex items-center justify-between">
                <div>
                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100" x-text="selectedUser?.full_name || selectedUser?.username"></span>
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" x-text="selectedUser?.role"></span>
                </div>
                <button @click="selectedUser = null; userPermissions = []" class="text-sm text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="divide-y divide-gray-50 dark:divide-[#21262d]/50">
                <template x-for="perm in userPermissions" :key="perm.id">
                    <div class="flex items-center justify-between px-5 py-2.5">
                        <div>
                            <span class="text-sm text-gray-900 dark:text-gray-100" x-text="perm.name"></span>
                            <span class="ml-2 text-xs"
                                :class="perm.role_granted == 1 ? 'text-emerald-500' : 'text-gray-400'"
                                x-text="perm.role_granted == 1 ? '(rôle: oui)' : '(rôle: non)'"></span>
                        </div>
                        <select @change="setUserOverride(perm, $event.target.value)"
                            class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                            <option value="default" :selected="perm.user_override === null"><?= __('superadmin.default_role') ?? 'Défaut du rôle' ?></option>
                            <option value="1" :selected="perm.user_override == 1"><?= __('superadmin.granted') ?? 'Accordé' ?></option>
                            <option value="0" :selected="perm.user_override == 0"><?= __('superadmin.revoked') ?? 'Révoqué' ?></option>
                        </select>
                    </div>
                </template>
            </div>
            <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex justify-end" x-show="userPermissions.length > 0">
                <button @click="saveUserPermissions()" :disabled="savingUser"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingUser"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="savingUser"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function superadminPermissions() {
    return {
        roles: [
            { code: 'admin', label: 'Admin' },
            { code: 'gerant', label: '<?= __('role.gerant') ?? 'Gérant' ?>' },
            { code: 'vendeur', label: '<?= __('role.vendeur') ?? 'Vendeur' ?>' },
            { code: 'technicien', label: '<?= __('role.technicien') ?? 'Technicien' ?>' },
            { code: 'client', label: '<?= __('role.client') ?? 'Client' ?>' }
        ],
        activeRole: 'admin',
        permissions: [],
        groupedPermissions: {},
        loading: false,
        saving: false,
        categoryLabels: {
            users: '<?= __('superadmin.cat_users') ?? 'Utilisateurs' ?>',
            hotspot: 'Hotspot',
            network: '<?= __('superadmin.cat_network') ?? 'Réseau' ?>',
            pppoe: 'PPPoE',
            system: '<?= __('superadmin.cat_system') ?? 'Système' ?>',
            communication: '<?= __('superadmin.cat_communication') ?? 'Communication' ?>',
            sales: '<?= __('superadmin.cat_sales') ?? 'Ventes' ?>',
            client_portal: '<?= __('superadmin.cat_client_portal') ?? 'Portail Client' ?>'
        },
        // User overrides
        userSearch: '',
        selectedUser: null,
        userPermissions: [],
        savingUser: false,

        async init() {
            await this.loadRolePermissions(this.activeRole);
        },

        async selectRole(role) {
            this.activeRole = role;
            await this.loadRolePermissions(role);
        },

        async loadRolePermissions(role) {
            this.loading = true;
            try {
                const res = await fetch(`api.php?route=/superadmin/roles/${role}/permissions`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.permissions = data.permissions;
                    this.groupPermissions();
                }
            } catch (e) { showToast('Erreur chargement', 'error'); }
            this.loading = false;
        },

        groupPermissions() {
            this.groupedPermissions = {};
            for (const perm of this.permissions) {
                if (!this.groupedPermissions[perm.category]) {
                    this.groupedPermissions[perm.category] = [];
                }
                this.groupedPermissions[perm.category].push(perm);
            }
        },

        togglePermission(perm) {
            perm.granted = perm.granted == 1 ? 0 : 1;
        },

        async savePermissions() {
            this.saving = true;
            try {
                const permissionIds = this.permissions.filter(p => p.granted == 1).map(p => p.id);
                const res = await fetch(`api.php?route=/superadmin/roles/${this.activeRole}/permissions`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ permission_ids: permissionIds })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.saving = false;
        },

        async searchUser() {
            if (!this.userSearch.trim()) return;
            try {
                const res = await fetch(`api.php?route=/users&search=${encodeURIComponent(this.userSearch)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                const users = data.users || data;
                if (Array.isArray(users) && users.length > 0) {
                    const user = users[0];
                    await this.loadUserPermissions(user.id);
                } else {
                    showToast('<?= __('superadmin.user_not_found') ?? 'Utilisateur non trouvé' ?>', 'error');
                }
            } catch (e) { showToast('Erreur recherche', 'error'); }
        },

        async loadUserPermissions(userId) {
            try {
                const res = await fetch(`api.php?route=/superadmin/users/${userId}/permissions`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.selectedUser = data.user;
                    this.userPermissions = data.permissions;
                }
            } catch (e) { showToast('Erreur', 'error'); }
        },

        setUserOverride(perm, value) {
            if (value === 'default') perm.user_override = null;
            else perm.user_override = parseInt(value);
        },

        async saveUserPermissions() {
            this.savingUser = true;
            try {
                const overrides = this.userPermissions
                    .filter(p => p.user_override !== null && p.user_override !== undefined)
                    .map(p => ({ permission_id: p.id, granted: p.user_override }));

                const res = await fetch(`api.php?route=/superadmin/users/${this.selectedUser.id}/permissions`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ overrides })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) { showToast('Erreur', 'error'); }
            this.savingUser = false;
        }
    };
}
</script>
