<?php
$pageTitle = __('page.captive_portal');
$currentPage = 'captive-portal';
?>

<div x-data="captivePortalPage()">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <?= $pageTitle?>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    <?= __('captive.subtitle')?>
                </p>
            </div>
        </div>
    </div>

    <!-- Liste des templates -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <template x-for="template in templates" :key="template.id">
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden hover:shadow-md transition-all">
                <!-- Mockup Preview -->
                <div class="aspect-[3/4] relative group border-b border-gray-200/60 dark:border-[#30363d] overflow-hidden"
                    style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">

                    <!-- Mockup Content -->
                    <div class="absolute inset-0 flex flex-col items-center px-5 pt-5 pb-3">

                        <!-- WiFi Logo + Brand Name -->
                        <div class="flex flex-col items-center mb-3">
                            <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center mb-1.5">
                                <svg class="w-5 h-5 text-white/90" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12,3L2,12H5V20H11V14H13V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,13.6 14.1,15.3 12,15.3C9.9,15.3 8.2,13.6 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z"/>
                                </svg>
                            </div>
                            <span class="text-white/90 text-[10px] font-semibold truncate max-w-full"
                                x-text="template.config?.page_name || 'Hotspot WiFi'"></span>
                        </div>

                        <!-- Slider Mockup (3 dots) -->
                        <div class="w-full h-12 rounded-md bg-white/5 mb-2.5 flex items-end justify-center pb-1.5"
                            x-show="template.config?.slide_enabled !== false">
                            <div class="flex gap-1">
                                <div class="w-1.5 h-1.5 rounded-full bg-white/80"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-white/30"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-white/30"></div>
                            </div>
                        </div>

                        <!-- Mode Selector -->
                        <div class="flex gap-1 mb-2.5 bg-white/10 rounded-full p-0.5">
                            <div class="px-3 py-0.5 rounded-full text-[8px] font-medium"
                                :class="(template.config?.connection_mode || 'voucher') === 'voucher' ? 'bg-white/20 text-white' : 'text-white/50'"
                                >Voucher</div>
                            <div class="px-3 py-0.5 rounded-full text-[8px] font-medium"
                                :class="(template.config?.connection_mode || 'voucher') === 'member' ? 'bg-white/20 text-white' : 'text-white/50'"
                                >Membre</div>
                        </div>

                        <!-- Login Form Mockup -->
                        <div class="w-full space-y-1.5">
                            <!-- Username field -->
                            <div class="w-full h-5 bg-white/10 rounded flex items-center px-2">
                                <div class="w-2.5 h-2.5 rounded-sm bg-white/20 mr-1.5"></div>
                                <span class="text-white/30 text-[7px]"
                                    x-text="(template.config?.connection_mode || 'voucher') === 'voucher' ? 'Code Voucher' : 'Nom d\'utilisateur'"></span>
                            </div>
                            <!-- Password field (member mode only) -->
                            <div class="w-full h-5 bg-white/10 rounded flex items-center px-2"
                                x-show="(template.config?.connection_mode || 'voucher') === 'member'">
                                <div class="w-2.5 h-2.5 rounded-sm bg-white/20 mr-1.5"></div>
                                <span class="text-white/30 text-[7px]">Mot de passe</span>
                            </div>
                            <!-- Login button -->
                            <div class="w-full h-5 bg-blue-500 rounded flex items-center justify-center">
                                <span class="text-white text-[7px] font-medium">Se Connecter</span>
                            </div>
                        </div>

                        <!-- Feature Badges -->
                        <div class="mt-auto flex flex-wrap gap-1 justify-center">
                            <template x-if="template.config?.audio_enabled">
                                <div class="px-1.5 py-0.5 bg-white/10 rounded text-[6px] text-white/60 flex items-center gap-0.5">
                                    <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 24 24"><path d="M14,3.23V5.29C16.89,6.15 19,8.83 19,12C19,15.17 16.89,17.84 14,18.7V20.77C18,19.86 21,16.28 21,12C21,7.72 18,4.14 14,3.23M16.5,12C16.5,10.23 15.5,8.71 14,7.97V16C15.5,15.29 16.5,13.76 16.5,12M3,9V15H7L12,20V4L7,9H3Z"/></svg>
                                    Audio
                                </div>
                            </template>
                            <template x-if="template.config?.live_chat">
                                <div class="px-1.5 py-0.5 bg-white/10 rounded text-[6px] text-white/60 flex items-center gap-0.5">
                                    <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12,3C6.5,3 2,6.58 2,11C2.05,13.15 3.06,15.17 4.75,16.5C4.75,17.1 4.33,18.67 2,21C4.97,20.3 7.31,18.79 8.5,17.5C9.62,17.83 10.79,18 12,18C17.5,18 22,14.42 22,10C22,6.58 17.5,3 12,3Z"/></svg>
                                    Chat
                                </div>
                            </template>
                            <template x-if="template.config?.recover_ticket">
                                <div class="px-1.5 py-0.5 bg-white/10 rounded text-[6px] text-white/60 flex items-center gap-0.5">
                                    <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 24 24"><path d="M22,10V6A2,2 0 0,0 20,4H4A2,2 0 0,0 2,6V10C3.11,10 4,10.9 4,12A2,2 0 0,1 2,14V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V14A2,2 0 0,1 20,12C20,10.9 20.9,10 22,10Z"/></svg>
                                    Ticket
                                </div>
                            </template>
                            <template x-if="(template.config?.services || []).length > 0">
                                <div class="px-1.5 py-0.5 bg-white/10 rounded text-[6px] text-white/60 flex items-center gap-0.5">
                                    <svg class="w-2 h-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12,2l3.09,6.26L22,9.27l-5,4.87L18.18,22L12,18.27L5.82,22L7,14.14L2,9.27L8.91,8.26L12,2z"/></svg>
                                    <span x-text="(template.config?.services || []).length + ' services'"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Bottom Navbar Mockup -->
                        <div class="w-full flex justify-around mt-2 pt-1.5 border-t border-white/10">
                            <div class="flex flex-col items-center">
                                <svg class="w-2.5 h-2.5 text-white/40" fill="currentColor" viewBox="0 0 24 24"><path d="M12,2l3.09,6.26L22,9.27l-5,4.87L18.18,22L12,18.27L5.82,22L7,14.14L2,9.27L8.91,8.26L12,2z"/></svg>
                                <span class="text-[5px] text-white/40 mt-0.5">Services</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <svg class="w-2.5 h-2.5 text-white/70" fill="currentColor" viewBox="0 0 24 24"><path d="M3,3h6v6h-6z M15,3h6v6h-6z M3,15h6v6h-6z M16,16h2v2h-2z M19,19h2v2h-2z"/></svg>
                                <span class="text-[5px] text-white/70 mt-0.5">QR Code</span>
                            </div>
                            <div class="flex flex-col items-center">
                                <svg class="w-2.5 h-2.5 text-white/40" fill="currentColor" viewBox="0 0 24 24"><path d="M12,2C6.48,2 2,6.48 2,12s4.48,10 10,10 10,-4.48 10,-10S17.52,2 12,2zM13,17h-2v-6h2v6zM13,9h-2L11,7h2v2z"/></svg>
                                <span class="text-[5px] text-white/40 mt-0.5">Tarifs</span>
                            </div>
                        </div>
                    </div>

                    <!-- Overlay Actions -->
                    <div
                        class="absolute inset-0 bg-gray-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                        <a :href="'index.php?page=captive-portal-editor&template=' + template.id"
                            class="px-4 py-2 bg-white text-gray-900 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2 shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Modifier
                        </a>
                    </div>
                </div>

                <!-- Template Info -->
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-white truncate" x-text="template.name">
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"
                        x-text="template.config?.page_name || 'Portail Captif'"></p>

                    <div class="mt-3 flex items-center justify-between">
                        <a :href="'index.php?page=captive-portal-editor&template=' + template.id"
                            class="w-full text-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Éditer le template
                        </a>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- État vide -->
    <div x-show="templates.length === 0 && !loading" x-cloak
        class="flex flex-col items-center justify-center py-20 px-4">
        <div class="w-16 h-16 rounded-2xl bg-gray-100 dark:bg-[#21262d] flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <p class="text-gray-500 dark:text-gray-400 mb-2 font-medium">
            Aucun template trouvé
        </p>
        <p class="text-sm text-gray-400 dark:text-gray-500">
            Vérifiez le dossier Portail Captif.
        </p>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none"
            viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
            </path>
        </svg>
    </div>

</div>

<script>
    function captivePortalPage() {
        return {
            templates: [],
            loading: true,

            async init() {
                this.loading = true;
                await this.loadTemplates();
                this.loading = false;
            },

            async loadTemplates() {
                try {
                    const response = await fetch('api.php?route=/captive-portal/templates');
                    const data = await response.json();

                    if (data.templates) {
                        this.templates = data.templates;
                    }
                } catch (error) {
                    console.error('Erreur chargement templates:', error);
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: { message: 'Erreur lors du chargement des templates', type: 'error' }
                    }));
                }
            }
        }
    }
</script>