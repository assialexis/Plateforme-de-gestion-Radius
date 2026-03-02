<div class="space-y-6" x-data="superAdminNotifications()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <?= __('superadmin.system_notifications') ?? 'Notifications Système'?>
            </h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">
                <?= __('superadmin.system_notifications_desc') ?? 'Publiez des annonces globales destinées aux administrateurs.'?>
            </p>
        </div>
        <button @click="openCreateModal()" class="btn-primary flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <?= __('common.create') ?? 'Créer'?>
        </button>
    </div>

    <!-- Table des notifications -->
    <div
        class="bg-white dark:bg-[#161b22] rounded-2xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead
                    class="bg-gray-50/50 dark:bg-[#21262d]/50 text-gray-600 dark:text-gray-400 font-medium border-b border-gray-200/60 dark:border-[#30363d]">
                    <tr>
                        <th class="px-6 py-4 rounded-tl-xl">
                            <?= __('superadmin.notification_title') ?? 'Titre'?>
                        </th>
                        <th class="px-6 py-4">
                            <?= __('superadmin.notification_type') ?? 'Type'?>
                        </th>
                        <th class="px-6 py-4">
                            <?= __('common.status') ?? 'Statut'?>
                        </th>
                        <th class="px-6 py-4">
                            <?= __('superadmin.notification_reads') ?? 'Lectures'?>
                        </th>
                        <th class="px-6 py-4">
                            <?= __('common.date') ?? 'Date'?>
                        </th>
                        <th class="px-6 py-4 text-right rounded-tr-xl">
                            <?= __('common.actions') ?? 'Actions'?>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/60 dark:divide-[#30363d]">
                    <template x-for="notification in notifications" :key="notification.id">
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-[#21262d]/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="font-medium text-gray-900 dark:text-white"
                                    x-text="notification.title"></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full" :class="{
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': notification.type === 'info',
                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': notification.type === 'success',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': notification.type === 'warning',
                                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': notification.type === 'error'
                                    }" x-text="notification.type"></span>
                            </td>
                            <td class="px-6 py-4">
                                <button @click="toggleStatus(notification)"
                                    class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer items-center justify-center rounded-full focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition-colors duration-200 ease-in-out"
                                    :class="notification.is_active == 1 ? 'bg-primary-500' : 'bg-gray-200 dark:bg-gray-700'">
                                    <span class="sr-only">Toggle</span>
                                    <span aria-hidden="true"
                                        class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="notification.is_active == 1 ? 'translate-x-2' : '-translate-x-2'"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="flex-1 w-24 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                                        <div class="h-full bg-primary-500 rounded-full"
                                            :style="'width: ' + (notification.total_admins > 0 ? (notification.read_count / notification.total_admins * 100) : 0) + '%'">
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500 font-medium"
                                        x-text="notification.read_count + '/' + notification.total_admins"></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400"
                                x-text="formatDate(notification.created_at)"></td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <button @click="editNotification(notification)"
                                        class="p-1.5 text-gray-400 hover:text-blue-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </button>
                                    <button @click="deleteNotification(notification.id)"
                                        class="p-1.5 text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                    <tr x-show="notifications.length === 0" x-cloak>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <?= __('superadmin.no_notifications') ?? 'Aucune notification trouvée'?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Form -->
    <div x-show="isModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal()"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-lg shadow-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200/60 dark:border-[#30363d]">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white"
                        x-text="editMode ? '<?= __('superadmin.edit_notification') ?? 'Modifier la notification'?>' : '<?= __('superadmin.new_notification') ?? 'Nouvelle notification'?>'">
                    </h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Body -->
                <form @submit.prevent="submitForm">
                    <div class="p-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('superadmin.notification_title') ?? 'Titre'?> *
                            </label>
                            <input type="text" x-model="form.title"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                                placeholder="Ex: Maintenance programmée" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('superadmin.notification_type') ?? 'Type'?>
                            </label>
                            <select x-model="form.type"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                                required>
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="success">Success</option>
                                <option value="error">Error</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('superadmin.notification_message') ?? 'Message'?> *
                            </label>
                            <textarea x-model="form.message" rows="4"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                                placeholder="Détail de l'annonce..." required></textarea>
                        </div>
                        <div class="flex items-center mt-4">
                            <input type="checkbox" id="is_active_check" x-model="form.is_active"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                            <label for="is_active_check"
                                class="ml-2 text-sm font-medium text-gray-900 dark:text-gray-300">Actif</label>
                        </div>
                    </div>
                    <!-- Footer -->
                    <div
                        class="p-6 border-t border-gray-200/60 dark:border-[#30363d] flex justify-end gap-3 bg-gray-50/50 dark:bg-[#21262d]/50 rounded-b-2xl">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d] font-medium">
                            <?= __('common.cancel') ?? 'Annuler'?>
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium relative"
                            :disabled="isSubmitting" :class="{'opacity-50 cursor-not-allowed': isSubmitting}">
                            <span x-show="!isSubmitting">
                                <?= __('common.save') ?? 'Sauvegarder'?>
                            </span>
                            <span x-show="isSubmitting" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                <?= __('common.loading') ?? 'Chargement...'?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('superAdminNotifications', () => ({
            notifications: [],
            isModalOpen: false,
            editMode: false,
            isSubmitting: false,
            form: {
                id: null,
                title: '',
                message: '',
                type: 'info',
                is_active: 1
            },

            async init() {
                await this.fetchNotifications();
            },

            async fetchNotifications() {
                try {
                    const data = await API.get('/superadmin/notifications');
                    if (data.success) {
                        this.notifications = data.data; // API Wrapper usually returns data inside `data` key for jsonSuccess, let's verify format
                    } else if (Array.isArray(data)) {
                        this.notifications = data;
                    }
                } catch (error) {
                    showToast('Erreur de chargement', 'error');
                }
            },

            openCreateModal() {
                this.editMode = false;
                this.form = { id: null, title: '', message: '', type: 'info', is_active: 1 };
                this.isModalOpen = true;
            },

            editNotification(notification) {
                this.editMode = true;
                this.form = { ...notification };
                this.isModalOpen = true;
            },

            closeModal() {
                this.isModalOpen = false;
            },

            async submitForm() {
                if (!this.form.title || !this.form.message) {
                    showToast('Veuillez remplir les champs obligatoires.', 'error');
                    return;
                }

                this.isSubmitting = true;
                try {
                    let response;
                    if (this.editMode) {
                        response = await API.put(`/superadmin/notifications/${this.form.id}`, this.form);
                    } else {
                        response = await API.post('/superadmin/notifications', this.form);
                    }

                    if (response.success) {
                        showToast(response.message, 'success');
                        this.closeModal();
                        await this.fetchNotifications();
                    } else {
                        showToast(response.message, 'error');
                    }
                } catch (error) {
                    showToast('Erreur lors de l\'enregistrement', 'error');
                } finally {
                    this.isSubmitting = false;
                }
            },

            async toggleStatus(notification) {
                try {
                    const newStatus = notification.is_active == 1 ? 0 : 1;
                    const response = await API.put(`/superadmin/notifications/${notification.id}`, {
                        is_active: newStatus
                    });

                    if (response.success) {
                        notification.is_active = newStatus;
                        showToast('Statut mis à jour', 'success');
                    }
                } catch (error) {
                    showToast('Erreur de mise à jour', 'error');
                }
            },

            async deleteNotification(id) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) return;

                try {
                    const response = await API.delete(`/superadmin/notifications/${id}`);
                    if (response.success) {
                        showToast(response.message, 'success');
                        await this.fetchNotifications();
                    } else {
                        showToast(response.message, 'error');
                    }
                } catch (error) {
                    showToast('Erreur lors de la suppression', 'error');
                }
            },

            formatDate(dateString) {
                const date = new Date(dateString);
                return new Intl.DateTimeFormat('fr-FR', {
                    year: 'numeric', month: 'short', day: 'numeric',
                    hour: '2-digit', minute: '2-digit'
                }).format(date);
            }
        }));
    });
</script>