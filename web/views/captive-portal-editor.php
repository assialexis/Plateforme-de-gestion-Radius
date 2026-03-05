<?php
$pageTitle = __('page.captive_portal');
$currentPage = 'captive-portal';

// Basic check for template parameter
$templateId = $_GET['template'] ?? null;
if (!$templateId) {
    header('Location: index.php?page=captive-portal');
    exit;
}
?>

<div x-data="captivePortalEditor('<?= htmlspecialchars($templateId)?>')"
    class="h-[calc(100vh-8rem)] flex flex-col -m-6">

    <!-- Topbar Editor -->
    <div
        class="bg-white dark:bg-[#161b22] border-b border-gray-200 dark:border-[#30363d] px-6 py-4 flex items-center justify-between z-10">
        <div class="flex items-center gap-4">
            <a href="index.php?page=captive-portal"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    Éditeur : <span x-text="templateName" class="text-primary-600 dark:text-primary-400"></span>
                </h1>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <!-- View Toggle -->
            <div class="flex bg-gray-100 dark:bg-[#21262d] p-1 rounded-lg">
                <button @click="viewMode = 'desktop'"
                    :class="viewMode === 'desktop' ? 'bg-white dark:bg-[#30363d] shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    Desktop
                </button>
                <button @click="viewMode = 'mobile'"
                    :class="viewMode === 'mobile' ? 'bg-white dark:bg-[#30363d] shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                    Mobile
                </button>
            </div>

            <!-- Save Action -->
            <button @click="saveChanges()" :disabled="saving"
                class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                <svg x-show="saving" class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                    </path>
                </svg>
                <span x-text="saving ? 'Sauvegarde...' : 'Sauvegarder'"></span>
            </button>
        </div>
    </div>

    <div class="flex-1 flex overflow-hidden">
        <!-- Sidebar Settings -->
        <div class="w-[420px] min-w-[420px] bg-white dark:bg-[#161b22] border-r border-gray-200 dark:border-[#30363d] flex flex-col">
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-[#30363d] overflow-x-auto hide-scrollbar">
                <button @click="activeTab = 'general'; previewPage = 'login.html'; updatePreview()"
                    :class="activeTab === 'general' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Général
                </button>
                <button @click="activeTab = 'options'; previewPage = 'login.html'; updatePreview()"
                    :class="activeTab === 'options' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Options
                </button>
                <button @click="activeTab = 'tarifs'; previewPage = 'tarifs.html'; updatePreview()"
                    :class="activeTab === 'tarifs' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Tarifs
                </button>
                <button @click="activeTab = 'services'; previewPage = 'services.html'; updatePreview()"
                    :class="activeTab === 'services' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Services
                </button>
                <button @click="activeTab = 'advanced'; previewPage = 'login.html'; updatePreview()"
                    :class="activeTab === 'advanced' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="px-4 py-3 border-b-2 text-sm font-medium whitespace-nowrap transition-colors">
                    Avancé
                </button>
            </div>

            <!-- Tab Content -->
            <div class="flex-1 overflow-y-auto p-4 space-y-6">

                <!-- General Tab -->
                <div x-show="activeTab === 'general'" x-cloak class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom de la
                            page</label>
                        <input type="text" x-model="config.page_name" @input="updatePreview"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                        <p class="text-xs text-gray-500 mt-1">Sera remplacé à la place de "mnaspot"</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numéro de
                            téléphone</label>
                        <input type="text" x-model="config.phone" @input="updatePreview"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input type="email" x-model="config.email" @input="updatePreview"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                    </div>
                </div>

                <!-- Options Tab -->
                <div x-show="activeTab === 'options'" x-cloak class="space-y-3">

                    <!-- Section: Mode de connexion -->
                    <div>
                        <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Mode de connexion</h3>
                        <select x-model="config.connection_mode" @change="updatePreview"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                            <option value="voucher">Voucher / Ticket</option>
                            <option value="member">Membre (User / Pass)</option>
                        </select>
                        <p class="text-[10px] text-gray-400 mt-1">Mode affiché par défaut sur la page de connexion</p>
                    </div>

                    <div class="border-t border-gray-200 dark:border-[#30363d]"></div>

                    <!-- Section: Toggles -->
                    <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fonctionnalités</h3>

                    <!-- Audio -->
                    <label class="flex items-center justify-between p-3 border border-gray-200 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Audio / Musique</span>
                                <span class="block text-[10px] text-gray-500">Musique de fond automatique</span>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" x-model="config.audio_enabled" @change="updatePreview" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                        </div>
                    </label>

                    <!-- Slide Images -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Carrousel d'images</span>
                                    <span class="block text-[10px] text-gray-500">Slider avec images de la médiathèque</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.slide_enabled" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <!-- Galerie d'images slider (affiché si activé) -->
                        <div x-show="config.slide_enabled" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Images du slider</span>
                                <span class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded" x-text="(config.slider_images || []).length + ' sélectionnée(s)'"></span>
                            </div>
                            <div class="grid grid-cols-3 gap-1.5 max-h-40 overflow-y-auto">
                                <template x-for="media in mediaItems.filter(m => m.url && m.media_type === 'image')" :key="media.id">
                                    <div class="cursor-pointer relative rounded-md overflow-hidden border-2 transition-all aspect-square"
                                        :class="(config.slider_images || []).includes(media.url) ? 'border-primary-500 shadow-sm' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'"
                                        @click="if(!config.slider_images) config.slider_images = []; if(config.slider_images.includes(media.url)) { config.slider_images = config.slider_images.filter(x => x !== media.url); } else { config.slider_images.push(media.url); } updatePreview()">
                                        <img :src="media.url" class="w-full h-full object-cover">
                                        <div x-show="(config.slider_images || []).includes(media.url)" class="absolute inset-0 bg-primary-500/20 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white drop-shadow" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                                <div x-show="mediaItems.filter(m => m.url && m.media_type === 'image').length === 0" class="col-span-3 text-center py-3 text-xs text-gray-400">
                                    Aucune image dans la médiathèque
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logo personnalisé -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <div class="p-3">
                            <div class="flex items-center gap-2.5 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Logo personnalisé</span>
                                    <span class="block text-[10px] text-gray-500">Remplacer l'icône WiFi par défaut</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-1.5 max-h-32 overflow-y-auto">
                                <!-- Option par défaut -->
                                <div class="cursor-pointer relative rounded-md overflow-hidden border-2 transition-all aspect-square flex items-center justify-center bg-white dark:bg-[#161b22]"
                                    :class="!config.logo_url ? 'border-primary-500 shadow-sm' : 'border-transparent border-dashed border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                                    @click="config.logo_url = ''; updatePreview()">
                                    <span class="text-[9px] text-gray-400 text-center leading-tight">Défaut</span>
                                    <div x-show="!config.logo_url" class="absolute top-0.5 right-0.5">
                                        <svg class="w-3.5 h-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    </div>
                                </div>
                                <!-- Logos de la médiathèque -->
                                <template x-for="media in mediaItems.filter(m => m.url && (m.media_type === 'logo' || m.media_type === 'image'))" :key="media.id">
                                    <div class="cursor-pointer relative rounded-md overflow-hidden border-2 bg-white dark:bg-[#161b22] transition-all aspect-square"
                                        :class="config.logo_url === media.url ? 'border-primary-500 shadow-sm' : 'border-transparent hover:border-gray-300'"
                                        @click="config.logo_url = media.url; updatePreview()">
                                        <img :src="media.url" class="w-full h-full object-contain p-0.5">
                                        <div x-show="config.logo_url === media.url" class="absolute top-0.5 right-0.5">
                                            <svg class="w-3.5 h-3.5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-[#30363d]"></div>

                    <!-- Récupérer un ticket -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Récupérer un ticket</span>
                                    <span class="block text-[10px] text-gray-500">Bouton de récupération ticket hotspot</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.recover_ticket" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <div x-show="config.recover_ticket" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Lien de récupération</label>
                            <input type="url" x-model="config.recover_ticket_url" @input="updatePreview" :placeholder="config.recover_ticket_url || 'https://...'"
                                class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-xs focus:ring-2 focus:ring-primary-500 outline-none" readonly>
                            <p class="text-[10px] text-gray-400 mt-1">Lien auto-généré vers la page de récupération des tickets</p>
                        </div>
                    </div>

                    <!-- Bouton Acheter (sur tarifs) -->
                    <label class="flex items-center justify-between p-3 border border-gray-200 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                                </svg>
                            </div>
                            <div>
                                <span class="block text-sm font-medium text-gray-900 dark:text-white">Bouton Acheter</span>
                                <span class="block text-[10px] text-gray-500">Afficher les boutons "Acheter" sur les tarifs</span>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="checkbox" x-model="config.buy_button" @change="updatePreview" class="sr-only peer">
                            <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                        </div>
                    </label>

                    <!-- Bouton Acheter un ticket (page paiement) -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Acheter un ticket</span>
                                    <span class="block text-[10px] text-gray-500">Bouton lien vers la page de paiement hotspot</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.buy_ticket" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <div x-show="config.buy_ticket" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50">
                            <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Lien page de paiement</label>
                            <input type="url" x-model="config.buy_ticket_url" @input="updatePreview"
                                class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-xs focus:ring-2 focus:ring-primary-500 outline-none font-mono">
                            <p class="text-[10px] text-gray-400 mt-1">URL de la page pay.php avec l'admin ID</p>
                        </div>
                    </div>

                    <!-- Vérification OTP -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-cyan-100 dark:bg-cyan-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Vérification OTP</span>
                                    <span class="block text-[10px] text-gray-500">Identification client par SMS avant connexion</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.otp_enabled" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <div x-show="config.otp_enabled" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50 space-y-2">
                            <div class="bg-cyan-50 dark:bg-cyan-900/20 border border-cyan-200 dark:border-cyan-800 rounded-lg p-2.5">
                                <p class="text-[11px] text-cyan-700 dark:text-cyan-300 flex items-start gap-1.5">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Le script OTP sera injecté dans login.html. Le client devra entrer son numéro de téléphone et valider un code SMS avant de se connecter. Configurez l'OTP depuis le menu Vérification OTP.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Inscription Hotspot (Self-Registration) -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Inscription Hotspot</span>
                                    <span class="block text-[10px] text-gray-500">Auto-inscription avec code du jour + OTP</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.registration_enabled" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <div x-show="config.registration_enabled" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50 space-y-2">
                            <div class="bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded-lg p-2.5">
                                <p class="text-[11px] text-violet-700 dark:text-violet-300 flex items-start gap-1.5">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Un bouton "S'inscrire" sera ajouté sur login.html. L'utilisateur devra entrer son numéro + le code du jour, puis valider par OTP SMS. Un compte voucher sera créé automatiquement. Configurez le code du jour depuis Vérification OTP > Inscription.
                                </p>
                            </div>
                            <a :href="'public/registration.html?admin_id=1'" target="_blank"
                                class="mt-2 inline-flex items-center gap-1.5 text-[11px] text-violet-600 dark:text-violet-400 hover:underline font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Tester la page d'inscription
                            </a>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 dark:border-[#30363d]"></div>

                    <!-- Section Chat Support -->
                    <div class="border border-gray-200 dark:border-[#30363d] rounded-lg overflow-hidden">
                        <label class="flex items-center justify-between p-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/30 flex items-center justify-center">
                                    <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">Chat Support</span>
                                    <span class="block text-[10px] text-gray-500">Widget de chat en direct</span>
                                </div>
                            </div>
                            <div class="relative">
                                <input type="checkbox" x-model="config.live_chat" @change="updatePreview" class="sr-only peer">
                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-primary-600"></div>
                            </div>
                        </label>
                        <div x-show="config.live_chat" x-transition x-cloak class="border-t border-gray-200 dark:border-[#30363d] p-3 bg-gray-50/50 dark:bg-[#0d1117]/50 space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Type de support</label>
                                <select x-model="config.chat_support_type" @change="updatePreview"
                                    class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-xs focus:ring-2 focus:ring-primary-500 outline-none">
                                    <option value="live_chat">Live Chat (Module Chat)</option>
                                    <option value="whatsapp">WhatsApp</option>
                                </select>
                            </div>
                            <!-- WhatsApp config -->
                            <div x-show="config.chat_support_type === 'whatsapp'" x-transition>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Numéro WhatsApp</label>
                                <input type="tel" x-model="config.chat_whatsapp_phone" @input="updatePreview" placeholder="22990000000"
                                    class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-xs focus:ring-2 focus:ring-primary-500 outline-none">
                                <p class="text-[10px] text-gray-400 mt-1">Format international sans +</p>
                            </div>
                            <!-- Live Chat info -->
                            <div x-show="config.chat_support_type === 'live_chat'" x-transition
                                class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-2.5">
                                <p class="text-[11px] text-blue-700 dark:text-blue-300 flex items-start gap-1.5">
                                    <svg class="w-3.5 h-3.5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Le widget du module Chat sera automatiquement injecté dans la page du portail captif.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Tarifs Tab -->
                <div x-show="activeTab === 'tarifs'" x-cloak class="space-y-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sélectionner une
                            Zone (Optionnel)</label>
                        <select x-model="config.selected_zone" @change="loadProfiles(); updatePreview()"
                            class="w-full px-3 py-2 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                            <option value="">-- Toutes les zones --</option>
                            <template x-for="zone in zones" :key="zone.id">
                                <option :value="zone.id" x-text="zone.name"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profils à
                            afficher comme Tarifs</label>
                        <div
                            class="space-y-2 max-h-64 overflow-y-auto p-2 border border-gray-200 dark:border-[#30363d] rounded-lg">
                            <template x-if="availableProfiles.length === 0">
                                <p class="text-xs text-gray-500 text-center py-2">Aucun profil disponible</p>
                            </template>
                            <template x-for="profile in availableProfiles" :key="profile.id">
                                <label
                                    class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-[#21262d] rounded cursor-pointer transition-colors">
                                    <input type="checkbox" :value="profile.id" x-model="config.selected_profiles"
                                        @change="updatePreview" class="w-4 h-4 text-primary-600 rounded mr-3">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white"
                                            x-text="profile.name"></span>
                                        <span class="block text-xs text-gray-500"
                                            x-text="profile.price + ' Fcfa'"></span>
                                    </div>
                                </label>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Services Tab -->
                <div x-show="activeTab === 'services'" x-cloak class="space-y-4">
                    <div
                        class="flex items-center justify-between mb-4 border-b border-gray-200 dark:border-[#30363d] pb-4">
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white">Services à afficher</h3>
                        <button @click="addService()"
                            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-[#21262d] dark:hover:bg-[#30363d] text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Ajouter
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(service, index) in config.services" :key="index">
                            <div
                                class="p-4 bg-gray-50 dark:bg-[#161b22] border border-gray-200 dark:border-[#30363d] rounded-xl relative group">
                                <button @click="removeService(index)"
                                    class="absolute top-3 right-3 text-gray-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>

                                <div class="grid gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Titre du service</label>
                                        <input type="text" x-model="service.title" @input="updatePreview"
                                            class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Description</label>
                                        <textarea x-model="service.description" @input="updatePreview" rows="2"
                                            class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm focus:ring-2 focus:ring-primary-500 outline-none"
                                            placeholder="Description courte du service"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Fonctionnalités</label>
                                        <textarea :value="(service.features || []).join('\n')"
                                            @input="service.features = $event.target.value.split('\n').filter(l => l.trim()); updatePreview()"
                                            rows="3"
                                            class="w-full px-3 py-1.5 bg-white dark:bg-[#0d1117] border border-gray-300 dark:border-[#30363d] rounded-lg text-xs focus:ring-2 focus:ring-primary-500 outline-none font-mono"
                                            placeholder="Une fonctionnalité par ligne&#10;Ex: Débit jusqu'à 100 Mbps&#10;Connexion stable et fiable"></textarea>
                                        <p class="text-[10px] text-gray-400 mt-0.5">Une fonctionnalité par ligne</p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="!config.services || config.services.length === 0"
                            class="text-sm text-gray-500 italic text-center py-4">
                            Aucun service configuré. Cliquez sur Ajouter.
                        </div>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div x-show="activeTab === 'advanced'" x-cloak class="space-y-4">
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-xs text-amber-700 dark:text-amber-300">
                                Ces options sont réservées aux utilisateurs avancés. Un code incorrect peut casser le template.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">CSS personnalisé</label>
                        <textarea x-model="config.custom_css" @input="updatePreview()" rows="8"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none font-mono text-xs leading-relaxed"
                            placeholder="/* Votre CSS personnalisé */&#10;.brand-name { color: gold; }&#10;.tariff-card { border: 2px solid #fff; }"></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5">Injecté dans une balise &lt;style&gt; sur toutes les pages</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">JavaScript personnalisé</label>
                        <textarea x-model="config.custom_js" @input="updatePreview()" rows="8"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none font-mono text-xs leading-relaxed"
                            placeholder="// Votre JavaScript personnalisé&#10;document.addEventListener('DOMContentLoaded', function() {&#10;    console.log('Custom script loaded');&#10;});"></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5">Injecté dans une balise &lt;script&gt; avant &lt;/body&gt;</p>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">HTML complet personnalisé</label>
                        <textarea x-model="config.custom_html" rows="12"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none font-mono text-xs leading-relaxed"
                            placeholder="<!-- Laissez vide pour utiliser le template par défaut -->&#10;<!doctype html>&#10;<html>&#10;<head>..."></textarea>
                        <p class="text-[10px] text-gray-400 mt-0.5">Si rempli, remplace entièrement le fichier login.html. Laissez vide pour garder le template par défaut.</p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Preview Area -->
        <div class="flex-1 bg-gray-50 dark:bg-[#0d1117] flex flex-col overflow-hidden">

            <!-- Page selector -->
            <div class="flex items-center justify-center gap-1 py-2 px-4 bg-gray-100 dark:bg-[#161b22] border-b border-gray-200 dark:border-[#30363d]">
                <template x-for="page in [{id:'login.html',label:'Accueil'},{id:'services.html',label:'Services'},{id:'tarifs.html',label:'Tarifs'}]" :key="page.id">
                    <button @click="previewPage = page.id; updatePreview()"
                        :class="previewPage === page.id ? 'bg-white dark:bg-[#30363d] shadow-sm text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="px-3 py-1 rounded-md text-xs font-medium transition-all" x-text="page.label">
                    </button>
                </template>
            </div>

            <div class="flex-1 flex items-center justify-center p-8 overflow-y-auto">

            <div class="relative transition-all duration-300 ease-in-out shadow-2xl rounded-xl overflow-hidden bg-white"
                :class="viewMode === 'mobile' ? 'w-[375px] h-[812px] border-[12px] border-black rounded-[2.5rem]' : 'w-full max-w-5xl h-full border border-gray-200'"

                <!-- Loading indicator -->
                <div x-show="loadingPreview"
                    class="absolute inset-0 bg-white/80 dark:bg-black/80 flex items-center justify-center z-10">
                    <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>

                <iframe id="previewIframe" class="w-full h-full bg-white border-none" title="Live Preview">
                </iframe>
            </div>

            </div>
        </div>
    </div>
</div>

<script>
    function captivePortalEditor(templateIdParam) {
        return {
            templateId: templateIdParam || '',
            templateName: '',
            activeTab: 'general',
            viewMode: 'desktop',
            previewPage: 'login.html',
            loading: true,
            loadingPreview: false,
            saving: false,
            zones: [],
            availableProfiles: [],
            mediaItems: [],

            config: {
                page_name: '',
                phone: '',
                email: '',
                audio_enabled: false,
                slide_enabled: false,
                slider_images: [],
                logo_url: '',
                recover_ticket: false,
                recover_ticket_url: '',
                live_chat: false,
                chat_support_type: 'live_chat',
                chat_whatsapp_phone: '',
                buy_button: true,
                buy_ticket: false,
                buy_ticket_url: '',
                otp_enabled: false,
                registration_enabled: false,
                connection_mode: 'voucher',
                selected_zone: null,
                selected_profiles: [],
                services: [],
                custom_css: '',
                custom_js: '',
                custom_html: ''
            },

            async init() {
                await Promise.all([
                    this.loadZones(),
                    this.loadConfig(),
                    this.loadMedia()
                ]);
                await this.loadProfiles();
                this.updatePreview();
            },

            async loadZones() {
                try {
                    const response = await fetch('api.php?route=/zones');
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            this.zones = data.data;
                        }
                    }
                } catch (e) {
                    console.error('Erreur chargement zones', e);
                }
            },

            async loadMedia() {
                try {
                    const response = await fetch('api.php?route=/library');
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            this.mediaItems = data.data || [];
                        }
                    }
                } catch (e) {
                    console.error('Erreur chargement médias', e);
                }
            },

            async loadProfiles() {
                try {
                    let url = 'api.php?route=/profiles';
                    if (this.config.selected_zone) {
                        url = `api.php?route=/zones/${this.config.selected_zone}/profiles`;
                    }
                    const response = await fetch(url);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.success) {
                            this.availableProfiles = data.data || [];

                            // Normaliser selected_profiles en strings (les checkboxes Alpine retournent des strings)
                            this.config.selected_profiles = (this.config.selected_profiles || []).map(id => id.toString());

                            // Cleanup: ne garder que les profils disponibles
                            if (this.config.selected_profiles.length > 0) {
                                const availableIds = this.availableProfiles.map(p => p.id.toString());
                                this.config.selected_profiles = this.config.selected_profiles.filter(id => availableIds.includes(id));
                            }
                        }
                    }
                } catch (e) {
                    console.error('Erreur chargement profils', e);
                }
            },

            async loadConfig() {
                try {
                    const response = await fetch(`api.php?route=/captive-portal/templates/${this.templateId}`);
                    if (!response.ok) throw new Error('Network response was not ok');
                    const data = await response.json();

                    if (data.name) {
                        this.templateName = data.name;
                        this.config = { ...this.config, ...data.config };
                        if (!this.config.services) this.config.services = [];
                        if (!this.config.slider_images) this.config.slider_images = [];
                        if (!this.config.recover_ticket_url) this.config.recover_ticket_url = '';
                        if (!this.config.chat_support_type) this.config.chat_support_type = 'live_chat';
                    }
                } catch (error) {
                    console.error('Failed to load template info:', error);
                    this.showNotification('Erreur de chargement du template', 'error');
                }
            },

            addService() {
                if (!this.config.services) this.config.services = [];
                this.config.services.push({
                    title: 'Nouveau Service',
                    description: '',
                    features: []
                });
                this.updatePreview();
            },

            removeService(index) {
                this.config.services.splice(index, 1);
                this.updatePreview();
            },

            updatePreview() {
                this.loadingPreview = true;
                const iframe = document.getElementById('previewIframe');

                const templateName = this.templateId.replace(/_/g, ' ');
                const url = `portal-preview.php/${encodeURIComponent(templateName)}/${encodeURIComponent(this.previewPage)}`;

                // Add a timestamp to bypass cache
                iframe.src = url + '?t=' + new Date().getTime();

                iframe.onload = () => {
                    this.loadingPreview = false;
                    this.injectLiveChanges();
                };
            },

            injectLiveChanges() {
                const iframe = document.getElementById('previewIframe');
                let doc, iframeWindow;
                try {
                    doc = iframe.contentDocument || iframe.contentWindow.document;
                    iframeWindow = iframe.contentWindow;
                } catch(e) { return; }

                if (!doc || !doc.body) return;

                // --- Commun à toutes les pages: nom et logo ---
                this.injectCommonChanges(doc);

                // --- Injection spécifique selon la page ---
                if (this.previewPage === 'login.html') {
                    this.injectLoginChanges(doc, iframeWindow);
                } else if (this.previewPage === 'services.html') {
                    this.injectServicesChanges(doc);
                } else if (this.previewPage === 'tarifs.html') {
                    this.injectTarifsChanges(doc);
                }
            },

            injectCommonChanges(doc) {
                // --- Nom de la page ---
                if (this.config.page_name) {
                    const brandName = doc.querySelector('.brand-name');
                    if (brandName) brandName.textContent = this.config.page_name;
                    const title = doc.querySelector('title');
                    if (title) title.textContent = this.config.page_name;
                }

                // --- Logo personnalisé ---
                const wifiLogo = doc.querySelector('.wifi-logo');
                if (wifiLogo) {
                    if (this.config.logo_url) {
                        wifiLogo.innerHTML = `<img src="${this.config.logo_url}" style="width:60px;height:60px;object-fit:contain;border-radius:12px;" alt="Logo">`;
                    } else if (!wifiLogo.querySelector('svg')) {
                        wifiLogo.innerHTML = `<svg viewBox="0 0 24 24" class="wifi-icon"><path d="M12,3L2,12H5V20H11V14H13V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,13.6 14.1,15.3 12,15.3C9.9,15.3 8.2,13.6 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z" fill="#fff"/></svg>`;
                    }
                }

                // --- CSS personnalisé (preview live) ---
                let customStyle = doc.getElementById('custom-css-preview');
                if (this.config.custom_css) {
                    if (!customStyle) {
                        customStyle = doc.createElement('style');
                        customStyle.id = 'custom-css-preview';
                        doc.head.appendChild(customStyle);
                    }
                    customStyle.textContent = this.config.custom_css;
                } else if (customStyle) {
                    customStyle.remove();
                }
            },

            injectLoginChanges(doc, iframeWindow) {
                // --- Style overrides pour visibilité ---
                let overrideStyle = doc.getElementById('editor-override-style');
                if (!overrideStyle) {
                    overrideStyle = doc.createElement('style');
                    overrideStyle.id = 'editor-override-style';
                    doc.head.appendChild(overrideStyle);
                }
                let cssRules = [];
                if (!this.config.audio_enabled) {
                    cssRules.push('.audio-btn, #audioBtn, #bgMusic { display: none !important; }');
                }
                if (!this.config.slide_enabled) {
                    cssRules.push('.slider-container { display: none !important; }');
                }
                if (!this.config.recover_ticket) {
                    cssRules.push('.ticket-section { display: none !important; }');
                }
                if (!this.config.buy_button) {
                    cssRules.push('.buy-btn { display: none !important; }');
                }
                if (!this.config.buy_ticket) {
                    cssRules.push('.buy-ticket-section { display: none !important; }');
                }
                overrideStyle.textContent = cssRules.join('\n');

                // --- Bouton Acheter un ticket ---
                let buyTicketSection = doc.querySelector('.buy-ticket-section');
                if (this.config.buy_ticket) {
                    if (!buyTicketSection) {
                        buyTicketSection = doc.createElement('div');
                        buyTicketSection.className = 'buy-ticket-section';
                        const buyTicketUrl = this.config.buy_ticket_url || '#';
                        buyTicketSection.innerHTML = `
                            <a href="${buyTicketUrl}" target="_blank" class="ticket-btn buy-ticket-btn">
                                <svg class="ticket-icon" viewBox="0 0 24 24">
                                    <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z" fill="currentColor"/>
                                </svg>
                                Acheter un ticket
                            </a>`;
                        const tariffsContainer = doc.querySelector('.tariffs-container');
                        if (tariffsContainer) {
                            tariffsContainer.parentNode.insertBefore(buyTicketSection, tariffsContainer);
                        }
                    } else {
                        const link = buyTicketSection.querySelector('a');
                        if (link) link.href = this.config.buy_ticket_url || '#';
                    }
                } else if (buyTicketSection) {
                    buyTicketSection.remove();
                }

                // --- Stopper l'audio si désactivé ---
                if (!this.config.audio_enabled) {
                    try {
                        const bgMusic = doc.getElementById('bgMusic');
                        if (bgMusic) { bgMusic.pause(); bgMusic.src = ''; }
                    } catch(e) {}
                }

                // --- Slider images ---
                if (this.config.slide_enabled && this.config.slider_images && this.config.slider_images.length > 0) {
                    const slider = doc.querySelector('.slider');
                    const dotsContainer = doc.querySelector('.slider-dots');
                    if (slider) {
                        slider.innerHTML = '';
                        this.config.slider_images.forEach((url, i) => {
                            const slide = doc.createElement('div');
                            slide.className = `slide slide-${i+1}`;
                            slide.style.backgroundImage = `url('${url}')`;
                            slider.appendChild(slide);
                        });
                    }
                    if (dotsContainer) {
                        dotsContainer.innerHTML = '';
                        this.config.slider_images.forEach((_, i) => {
                            const dot = doc.createElement('div');
                            dot.className = 'dot' + (i === 0 ? ' active' : '');
                            dotsContainer.appendChild(dot);
                        });
                    }
                }

                // --- Tarifs ---
                const tariffsContainer = doc.querySelector('.tariffs-container');
                if (tariffsContainer && this.config.selected_profiles && this.config.selected_profiles.length > 0) {
                    tariffsContainer.querySelectorAll('.tariff-card').forEach(c => c.remove());
                    const selectedIds = this.config.selected_profiles.map(id => id.toString());
                    const selectedProfiles = this.availableProfiles.filter(p => selectedIds.includes(p.id.toString()));
                    selectedProfiles.forEach(profile => {
                        const card = doc.createElement('div');
                        card.className = 'tariff-card';
                        const price = new Intl.NumberFormat('fr-FR').format(profile.price || 0) + ' Fcfa';
                        let featuresHtml = '';
                        if (profile.description) {
                            profile.description.split('\n').filter(l => l.trim()).forEach(line => {
                                featuresHtml += `<li>${line.replace(/^-\s*/, '').trim()}</li>`;
                            });
                        } else {
                            featuresHtml = '<li>Connexion haute vitesse</li><li>Achat via mobile money</li><li>Accès illimité</li>';
                        }
                        card.innerHTML = `
                            <div class="tariff-header">
                                <div class="tariff-name">${profile.name}</div>
                                <div class="tariff-price">${price}</div>
                            </div>
                            <ul class="tariff-features">${featuresHtml}</ul>
                            <a href="#" target="_blank" class="buy-btn">Acheter</a>
                        `;
                        tariffsContainer.appendChild(card);
                    });
                } else if (tariffsContainer) {
                    tariffsContainer.querySelectorAll('.tariff-card').forEach(c => c.remove());
                }

                // --- Mode de connexion ---
                if (iframeWindow && this.config.connection_mode) {
                    try {
                        if (typeof iframeWindow.switchMode === 'function') {
                            iframeWindow.switchMode(this.config.connection_mode);
                        }
                    } catch(e) {}
                }

                // --- OTP badge preview ---
                let otpBadge = doc.querySelector('#otp-badge-preview');
                if (this.config.otp_enabled) {
                    if (!otpBadge) {
                        otpBadge = doc.createElement('div');
                        otpBadge.id = 'otp-badge-preview';
                        otpBadge.style.cssText = 'position:fixed;top:12px;right:12px;background:linear-gradient(135deg,#06b6d4,#0891b2);color:#fff;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;box-shadow:0 2px 8px rgba(6,182,212,0.3);z-index:9999;font-family:system-ui,sans-serif;';
                        otpBadge.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg> OTP actif';
                        doc.body.appendChild(otpBadge);
                    }
                } else if (otpBadge) {
                    otpBadge.remove();
                }

                // --- Registration button preview ---
                // Remove any button created by the saved snippet (it has no ID)
                doc.querySelectorAll('a[href*="registration.html"]').forEach(el => {
                    let parent = el.parentElement;
                    if (parent && parent.style.cssText && parent.style.cssText.includes('text-align')) parent.remove();
                    else el.remove();
                });
                let regBadge = doc.querySelector('#reg-badge-preview');
                if (this.config.registration_enabled) {
                    if (!regBadge) {
                        regBadge = doc.createElement('div');
                        regBadge.id = 'reg-badge-preview';
                        regBadge.style.cssText = 'text-align:center;margin-top:16px;';
                        let regBtn = doc.createElement('a');
                        let regUrl = window.location.origin + '/nas/web/public/registration.html?admin_id=1';
                        regBtn.href = regUrl;
                        regBtn.target = '_blank';
                        regBtn.style.cssText = 'display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:12px;text-decoration:none;font-weight:600;font-size:15px;box-shadow:0 4px 14px rgba(79,70,229,0.4);font-family:system-ui,sans-serif;cursor:pointer;';
                        regBtn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4-4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg> S\'inscrire';
                        regBadge.appendChild(regBtn);
                        let form = doc.querySelector('form') || doc.querySelector('.login-form');
                        if (form) {
                            form.parentNode.insertBefore(regBadge, form.nextSibling);
                        } else {
                            doc.body.appendChild(regBadge);
                        }
                    }
                } else if (regBadge) {
                    regBadge.remove();
                }

                // --- Chat widget badge ---
                let chatBadge = doc.querySelector('#chat-widget-preview');
                if (this.config.live_chat) {
                    if (!chatBadge) {
                        chatBadge = doc.createElement('div');
                        chatBadge.id = 'chat-widget-preview';
                        chatBadge.style.cssText = 'position:fixed;bottom:20px;right:20px;width:56px;height:56px;border-radius:50%;background:#25d366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.2);cursor:pointer;z-index:9999;';
                        chatBadge.innerHTML = this.config.chat_support_type === 'whatsapp'
                            ? '<svg width="28" height="28" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>'
                            : '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>';
                        if (this.config.chat_support_type !== 'whatsapp') {
                            chatBadge.style.background = '#10b981';
                        }
                        doc.body.appendChild(chatBadge);
                    }
                } else if (chatBadge) {
                    chatBadge.remove();
                }
            },

            // --- Preview live des services (services.html) ---
            injectServicesChanges(doc) {
                const container = doc.querySelector('.services-container');
                if (!container) return;

                // Supprimer les anciennes cartes
                container.querySelectorAll('.service-card').forEach(c => c.remove());

                // Injecter les services configurés
                const services = this.config.services || [];
                const iconPath = 'M12,3L2,12H5V20H11V14H13V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,13.6 14.1,15.3 12,15.3C9.9,15.3 8.2,13.6 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z';

                services.forEach((service, i) => {
                    const card = doc.createElement('div');
                    card.className = 'service-card';

                    let featuresHtml = '';
                    if (service.features && service.features.length > 0) {
                        const items = service.features.filter(f => f.trim()).map(f => `<li>${f}</li>`).join('');
                        if (items) featuresHtml = `<ul class="service-features">${items}</ul>`;
                    }

                    card.innerHTML = `
                        <div class="service-icon">
                            <svg viewBox="0 0 24 24"><path d="${iconPath}"/></svg>
                        </div>
                        <h3 class="service-name">${service.title || 'Service'}</h3>
                        <p class="service-description">${service.description || ''}</p>
                        ${featuresHtml}
                        <button class="service-btn">Choisir ce service</button>
                    `;
                    container.appendChild(card);
                });
            },

            // --- Preview live des tarifs (tarifs.html) ---
            injectTarifsChanges(doc) {
                // Injecter les tarifs
                const tariffsContainer = doc.querySelector('.tariffs-container');
                if (tariffsContainer && this.config.selected_profiles && this.config.selected_profiles.length > 0) {
                    tariffsContainer.querySelectorAll('.tariff-card').forEach(c => c.remove());
                    const selectedIds = this.config.selected_profiles.map(id => id.toString());
                    const selectedProfiles = this.availableProfiles.filter(p => selectedIds.includes(p.id.toString()));
                    selectedProfiles.forEach(profile => {
                        const card = doc.createElement('div');
                        card.className = 'tariff-card';
                        const price = new Intl.NumberFormat('fr-FR').format(profile.price || 0) + ' Fcfa';
                        let featuresHtml = '';
                        if (profile.description) {
                            profile.description.split('\n').filter(l => l.trim()).forEach(line => {
                                featuresHtml += `<li>${line.replace(/^-\s*/, '').trim()}</li>`;
                            });
                        } else {
                            featuresHtml = '<li>Connexion haute vitesse</li><li>Achat via mobile money</li><li>Accès illimité</li>';
                        }
                        card.innerHTML = `
                            <div class="tariff-header">
                                <div class="tariff-name">${profile.name}</div>
                                <div class="tariff-price">${price}</div>
                            </div>
                            <ul class="tariff-features">${featuresHtml}</ul>
                            <a href="#" target="_blank" class="buy-btn">Acheter</a>
                        `;
                        tariffsContainer.appendChild(card);
                    });
                }

                // Injecter les services dans la liste info
                const infoList = doc.querySelector('.info-list');
                if (infoList) {
                    infoList.innerHTML = '';
                    const services = this.config.services || [];
                    services.forEach(service => {
                        const li = doc.createElement('li');
                        li.textContent = '\u2705 ' + (service.title || 'Service');
                        infoList.appendChild(li);
                    });
                }
            },

            walkDOM(node, func) {
                func(node);
                node = node.firstChild;
                while (node) {
                    this.walkDOM(node, func);
                    node = node.nextSibling;
                }
            },

            async saveChanges() {
                this.saving = true;
                try {
                    const payload = {
                        ...this.config,
                        selected_profiles: (this.config.selected_profiles || []).map(id => parseInt(id, 10))
                    };

                    const response = await fetch(`api.php?route=/captive-portal/templates/${this.templateId}/save`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    if (!response.ok) {
                        const text = await response.text();
                        throw new Error('HTTP ' + response.status + ': ' + (text.substring(0, 100) || 'Erreur serveur'));
                    }

                    const data = await response.json();

                    if (!data.success) {
                        throw new Error(data.message || 'Erreur inconnue');
                    }

                    this.showNotification('Modifications enregistrées', 'success');
                    this.updatePreview();

                } catch (error) {
                    console.error('Save failed:', error);
                    this.showNotification('Erreur de sauvegarde: ' + error.message, 'error');
                } finally {
                    this.saving = false;
                }
            },

            showNotification(message, type = 'info') {
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { message, type }
                }));
            }
        }
    }
</script>