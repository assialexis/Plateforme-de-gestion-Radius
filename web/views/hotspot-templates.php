<?php $pageTitle = __('page.hotspot_templates');
$currentPage = 'hotspot-templates'; ?>

<div x-data="hotspotTemplatesPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('template.hotspot_subtitle')?>
            </p>
        </div>
        <button @click="resetForm(); showCreateModal = true; fetchLivePreview()"
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <?= __('template.new_template')?>
        </button>
    </div>

    <!-- Loading -->
    <div x-show="loading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-10 w-10 border-4 border-primary-500 border-t-transparent"></div>
    </div>

    <!-- Templates Grid -->
    <div x-show="!loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <template x-for="template in templates" :key="template.id">
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <!-- Aperçu visuel -->
                <div class="aspect-video relative overflow-hidden" :style="getPreviewStyle(template)">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="text-center p-4 rounded-lg max-w-[80%]"
                            :style="'background-color: ' + template.card_bg_color + '; color: ' + template.card_text_color">
                            <div x-show="template.show_logo"
                                class="w-10 h-10 bg-gray-200 dark:bg-[#30363d] rounded mx-auto mb-2"></div>
                            <p class="text-sm font-bold truncate" x-text="template.title_text || 'WiFi Login'"></p>
                            <div
                                class="mt-2 w-full h-6 bg-gray-100 dark:bg-[#21262d] rounded text-xs flex items-center justify-center text-gray-400">
                                <span x-text="template.username_placeholder"></span>
                            </div>
                            <div x-show="template.show_password_field"
                                class="mt-1 w-full h-6 bg-gray-100 dark:bg-[#21262d] rounded text-xs flex items-center justify-center text-gray-400">
                                <span x-text="template.password_placeholder"></span>
                            </div>
                            <div class="mt-2 w-full h-7 rounded text-xs flex items-center justify-center text-white font-medium"
                                :style="'background-color: ' + template.primary_color">
                                <span x-text="template.login_button_text"></span>
                            </div>
                        </div>
                    </div>
                    <!-- Badge chat support -->
                    <template x-if="template.show_chat_support">
                        <div class="absolute bottom-2 right-2 w-8 h-8 rounded-full shadow flex items-center justify-center"
                            :style="'background-color: ' + (template.chat_support_type === 'whatsapp' ? '#25D366' : template.primary_color)"
                            :title="template.chat_support_type === 'whatsapp' ? 'WhatsApp' : 'Live Chat'">
                            <template x-if="template.chat_support_type === 'whatsapp'">
                                <svg class="w-4 h-4 text-white" fill="white" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                </svg>
                            </template>
                            <template x-if="template.chat_support_type === 'live_chat'">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="white" stroke-width="2"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                            </template>
                        </div>
                    </template>
                    <!-- Badge par défaut -->
                    <template x-if="template.is_default">
                        <div
                            class="absolute top-2 right-2 px-2 py-1 text-xs font-medium bg-green-500 text-white rounded-full shadow">
                            <?= __('template.default')?>
                        </div>
                    </template>
                </div>

                <!-- Infos -->
                <div class="p-4">
                    <h4 class="font-semibold text-gray-900 dark:text-white" x-text="template.name"></h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="template.template_code"></p>

                    <!-- Couleurs -->
                    <div class="flex gap-1 mt-3">
                        <div class="w-6 h-6 rounded-full border-2 border-white shadow"
                            :style="'background-color: ' + template.primary_color"
                            :title="__('template.primary') + ': ' + template.primary_color"></div>
                        <div class="w-6 h-6 rounded-full border-2 border-white shadow"
                            :style="'background-color: ' + template.secondary_color"
                            :title="__('template.secondary') + ': ' + template.secondary_color"></div>
                        <div class="w-6 h-6 rounded-full border-2 border-white shadow"
                            :style="'background-color: ' + template.card_bg_color"
                            :title="__('template.card') + ': ' + template.card_bg_color"></div>
                        <div class="w-6 h-6 rounded-full border-2 border-white shadow"
                            :style="'background-color: ' + template.background_gradient_start"
                            :title="__('template.background') + ': ' + template.background_gradient_start"></div>
                    </div>

                    <!-- Actions -->
                    <div class="mt-4 flex gap-2">
                        <button @click="editTemplate(template)"
                            class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <?= __('template.edit')?>
                        </button>
                        <button @click="previewTemplate(template)"
                            class="px-3 py-2 text-sm font-medium text-primary-700 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors"
                            :title="__('template.preview')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                        <button @click="downloadTemplate(template)"
                            class="px-3 py-2 text-sm font-medium text-blue-700 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors"
                            :title="__('template.download_html')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </button>
                        <button @click="duplicateTemplate(template)"
                            class="px-3 py-2 text-sm font-medium text-purple-700 dark:text-purple-400 bg-purple-100 dark:bg-purple-900/30 rounded-lg hover:bg-purple-200 dark:hover:bg-purple-900/50 transition-colors"
                            :title="__('template.duplicate')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </button>
                        <button x-show="!template.is_default" @click="setDefault(template)"
                            class="px-3 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-green-100 dark:bg-green-900/30 rounded-lg hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                            :title="__('template.set_default')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button x-show="!template.is_default" @click="deleteTemplate(template)"
                            class="px-3 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-100 dark:bg-red-900/30 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                            :title="__('template.delete')">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty state -->
    <div x-show="!loading && templates.length === 0" class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">
            <?= __('template.no_hotspot_template')?>
        </p>
        <button @click="resetForm(); showCreateModal = true; fetchLivePreview()"
            class="mt-4 text-primary-600 hover:text-primary-700 font-medium">
            <?= __('template.create_first_hotspot')?>
        </button>
    </div>

    <!-- Modal Créer/Modifier Template -->
    <div x-show="showCreateModal || showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-6xl w-full max-h-[95vh] overflow-hidden flex flex-col">
                <!-- Header -->
                <div
                    class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                        x-text="showEditModal ? __('template.edit_template') : __('template.new_hotspot_template')">
                    </h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content avec tabs -->
                <div class="flex-1 overflow-hidden flex flex-col">
                    <!-- Tabs -->
                    <div class="flex-shrink-0 border-b border-gray-200 dark:border-[#30363d]">
                        <nav class="flex px-6 -mb-px">
                            <button @click="activeTab = 'general'"
                                :class="activeTab === 'general' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm">
                                <?= __('template.tab_general')?>
                            </button>
                            <button @click="activeTab = 'design'"
                                :class="activeTab === 'design' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm">
                                <?= __('template.tab_design')?>
                            </button>
                            <button @click="activeTab = 'content'"
                                :class="activeTab === 'content' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm">
                                <?= __('template.tab_content')?>
                            </button>
                            <button @click="activeTab = 'options'"
                                :class="activeTab === 'options' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm">
                                <?= __('template.tab_options')?>
                            </button>
                            <button @click="activeTab = 'advanced'"
                                :class="activeTab === 'advanced' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm">
                                <?= __('template.tab_advanced')?>
                            </button>
                            <button @click="activeTab = 'config'"
                                :class="activeTab === 'config' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="py-3 px-4 border-b-2 font-medium text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Paramètres Dynamiques
                            </button>
                        </nav>
                    </div>

                    <!-- Form avec preview -->
                    <div class="flex-1 overflow-y-auto" @input="triggerLivePreview()" @change="triggerLivePreview()">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-6">
                            <!-- Colonne gauche - Formulaire -->
                            <div class="space-y-6">
                                <!-- Tab Général -->
                                <div x-show="activeTab === 'general'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.template_name')?>
                                        </label>
                                        <input type="text" x-model="form.name" required
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.unique_code')?>
                                        </label>
                                        <input type="text" x-model="form.template_code" required pattern="[a-z0-9_-]+"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                            placeholder="mon_template">
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?= __('template.unique_code_hint')?>
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.logo_url')?>
                                        </label>
                                        <input type="url" x-model="form.logo_url"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"
                                            placeholder="https://...">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.logo_position')?>
                                        </label>
                                        <select x-model="form.logo_position"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                            <option value="center">
                                                <?= __('template.pos_center')?>
                                            </option>
                                            <option value="top">
                                                <?= __('template.pos_top')?>
                                            </option>
                                            <option value="left">
                                                <?= __('template.pos_left')?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Tab Design -->
                                <div x-show="activeTab === 'design'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.background_type')?>
                                        </label>
                                        <select x-model="form.background_type"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                            <option value="gradient">
                                                <?= __('template.bg_gradient')?>
                                            </option>
                                            <option value="color">
                                                <?= __('template.bg_color')?>
                                            </option>
                                            <option value="image">
                                                <?= __('template.bg_image')?>
                                            </option>
                                        </select>
                                    </div>

                                    <div x-show="form.background_type === 'color'">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.bg_color_label')?>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="color" x-model="form.background_color"
                                                class="h-10 w-16 rounded cursor-pointer">
                                            <input type="text" x-model="form.background_color"
                                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        </div>
                                    </div>

                                    <div x-show="form.background_type === 'gradient'" class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.gradient_start')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.background_gradient_start"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.background_gradient_start"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.gradient_end')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.background_gradient_end"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.background_gradient_end"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.primary_color')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.primary_color"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.primary_color"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.secondary_color')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.secondary_color"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.secondary_color"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.card_bg')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.card_bg_color"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.card_bg_color"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <?= __('template.card_text')?>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="color" x-model="form.card_text_color"
                                                    class="h-10 w-16 rounded cursor-pointer">
                                                <input type="text" x-model="form.card_text_color"
                                                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.general_text')?>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="color" x-model="form.text_color"
                                                class="h-10 w-16 rounded cursor-pointer">
                                            <input type="text" x-model="form.text_color"
                                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Contenu -->
                                <div x-show="activeTab === 'content'" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.title_label')?>
                                        </label>
                                        <input type="text" x-model="form.title_text"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.subtitle_label')?>
                                        </label>
                                        <input type="text" x-model="form.subtitle_text"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.username_placeholder')?>
                                        </label>
                                        <input type="text" x-model="form.username_placeholder"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.password_placeholder')?>
                                        </label>
                                        <input type="text" x-model="form.password_placeholder"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.button_text')?>
                                        </label>
                                        <input type="text" x-model="form.login_button_text"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.footer_text')?>
                                        </label>
                                        <input type="text" x-model="form.footer_text"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                    </div>
                                </div>

                                <!-- Tab Options -->
                                <div x-show="activeTab === 'options'" class="space-y-4">
                                    <div class="space-y-3">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="form.show_logo"
                                                class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <?= __('template.show_logo')?>
                                            </span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="form.show_password_field"
                                                class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <?= __('template.show_password_field')?>
                                            </span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="form.show_remember_me"
                                                class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <?= __('template.show_remember_me')?>
                                            </span>
                                        </label>

                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" x-model="form.show_footer"
                                                class="w-5 h-5 text-primary-600 rounded focus:ring-primary-500">
                                            <span class="text-gray-700 dark:text-gray-300">
                                                <?= __('template.show_footer')?>
                                            </span>
                                        </label>
                                    </div>

                                    <!-- Section Chat Support -->
                                    <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-4">
                                        <h5
                                            class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center gap-2">
                                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                            </svg>
                                            <?= __('template.chat_support')?>
                                        </h5>

                                        <div class="space-y-3">
                                            <label class="flex items-center gap-3 cursor-pointer">
                                                <input type="checkbox" x-model="form.show_chat_support"
                                                    class="w-5 h-5 text-green-600 rounded focus:ring-green-500">
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    <?= __('template.enable_chat_support')?>
                                                </span>
                                            </label>

                                            <div x-show="form.show_chat_support" x-transition
                                                class="space-y-3 pl-2 border-l-2 border-green-300 dark:border-green-700 ml-2">
                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        <?= __('template.support_type')?>
                                                    </label>
                                                    <select x-model="form.chat_support_type"
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                        <option value="whatsapp">WhatsApp</option>
                                                        <option value="live_chat">
                                                            <?= __('template.live_chat_option')?>
                                                        </option>
                                                    </select>
                                                </div>

                                                <div x-show="form.chat_support_type === 'whatsapp'">
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        <?= __('template.whatsapp_number')?>
                                                    </label>
                                                    <input type="tel" x-model="form.chat_whatsapp_phone"
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500"
                                                        placeholder="22990000000">
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?= __('template.whatsapp_format_hint')?>
                                                    </p>
                                                </div>

                                                <div x-show="form.chat_support_type === 'live_chat'"
                                                    class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                                    <p class="text-sm text-blue-700 dark:text-blue-300">
                                                        <svg class="w-4 h-4 inline mr-1" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <?= __('template.live_chat_desc')?>
                                                    </p>
                                                </div>

                                                <div>
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                        <?= __('template.welcome_message')?>
                                                    </label>
                                                    <textarea x-model="form.chat_welcome_message" rows="2"
                                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 text-sm"
                                                        placeholder="Bonjour ! Comment puis-je vous aider ?"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab Avancé -->
                                <div x-show="activeTab === 'advanced'" class="space-y-4">
                                    <div
                                        class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <svg class="w-5 h-5 text-amber-500 mr-2 mt-0.5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <p class="text-sm text-amber-700 dark:text-amber-300">
                                                <?= __('template.advanced_warning')?>
                                            </p>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.custom_css')?>
                                        </label>
                                        <textarea x-model="form.css_content" rows="6"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 font-mono text-sm"
                                            placeholder="/* Votre CSS personnalisé */"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.custom_js')?>
                                        </label>
                                        <textarea x-model="form.js_content" rows="6"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 font-mono text-sm"
                                            placeholder="// Votre JavaScript personnalisé"></textarea>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            <?= __('template.custom_html')?>
                                        </label>
                                        <textarea x-model="form.html_content" rows="10"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 font-mono text-sm"
                                            placeholder="<!-- HTML MikroTik complet -->"></textarea>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <?= __('template.custom_html_hint')?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Tab Config Dynamique -->
                                <div x-show="activeTab === 'config'" class="space-y-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Numéro
                                                de Contact WhatsApp/Tel</label>
                                            <input type="text" x-model="form.config.contact_number"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white"
                                                placeholder="+225...">
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Méthode
                                                par Défaut</label>
                                            <select x-model="form.config.default_auth_method"
                                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <option value="voucher">Voucher</option>
                                                <option value="member">Membre</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Zone
                                            Applicable</label>
                                        <select x-model="form.config.zone_id"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                            <option value="">Sélectionner une zone</option>
                                            <template x-for="zone in zones" :key="zone.id">
                                                <option :value="zone.id" x-text="zone.name"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Profils
                                            de la zone (Tarifs)</label>
                                        <div
                                            class="space-y-2 border border-gray-300 dark:border-[#30363d] rounded-lg p-3 max-h-40 overflow-y-auto">
                                            <template x-for="profile in filteredProfiles" :key="profile.id">
                                                <label class="flex items-center gap-2">
                                                    <input type="checkbox" :value="profile.id"
                                                        x-model="form.config.selected_profiles"
                                                        class="rounded text-primary-600 focus:ring-primary-500 w-4 h-4">
                                                    <span class="text-sm text-gray-700 dark:text-gray-300"
                                                        x-text="profile.name + ' (' + profile.price + ' ' + APP_CURRENCY + ')' "></span>
                                                </label>
                                            </template>
                                            <p x-show="filteredProfiles.length === 0"
                                                class="text-sm text-gray-500 italic">Veuillez sélectionner une zone
                                                contenant des profils.</p>
                                        </div>
                                    </div>

                                    <!-- Media (Logo et Sliders) -->
                                    <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-4">
                                        <h4
                                            class="text-sm font-semibold mb-3 text-gray-800 dark:text-gray-200 flex items-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Médias (Logo & Sliders)
                                        </h4>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <!-- Logo Gallery -->
                                            <div>
                                                <label
                                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Logo
                                                    Personnalisé</label>
                                                <div
                                                    class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto p-2 border border-gray-200 dark:border-[#30363d] bg-gray-50/50 dark:bg-[#21262d]/50 rounded-lg">
                                                    <!-- Option par défaut -->
                                                    <div class="cursor-pointer relative rounded-md overflow-hidden border-2 transition-all"
                                                        :class="!form.config.logo_url ? 'border-primary-500 shadow-sm' : 'border-transparent border-dashed border-gray-300 dark:border-gray-600 hover:border-gray-400'"
                                                        @click="form.config.logo_url = ''; fetchLivePreview()">
                                                        <div
                                                            class="h-16 flex items-center justify-center bg-white dark:bg-[#161b22] text-xs text-gray-500 font-medium text-center p-1 leading-tight">
                                                            (Par défaut)
                                                        </div>
                                                        <div x-show="!form.config.logo_url"
                                                            class="absolute top-1 right-1 bg-white rounded-full shadow-sm">
                                                            <svg class="w-4 h-4 text-primary-500" fill="currentColor"
                                                                viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                    clip-rule="evenodd" />
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <!-- Logos issus de la librairie -->
                                                    <template
                                                        x-for="media in mediaItems.filter(m => m.url && (m.media_type === 'logo' || m.media_type === 'image'))"
                                                        :key="media.url">
                                                        <div class="cursor-pointer relative rounded-md overflow-hidden border-2 bg-white dark:bg-[#161b22] transition-all"
                                                            :class="form.config.logo_url === media.url ? 'border-primary-500 shadow-sm' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'"
                                                            @click="form.config.logo_url = media.url; fetchLivePreview()"
                                                            :title="media.description || media.original_name || media.media_key">
                                                            <img :src="media.url"
                                                                class="h-16 w-full object-contain p-1">
                                                            <div x-show="form.config.logo_url === media.url"
                                                                class="absolute top-1 right-1 bg-white rounded-full shadow-sm">
                                                                <svg class="w-4 h-4 text-primary-500"
                                                                    fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd"
                                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                        clip-rule="evenodd" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                                <p class="text-[10px] text-gray-500 mt-1">Cliquez sur une image pour
                                                    l'utiliser comme logo.</p>
                                            </div>

                                            <!-- Slider Gallery -->
                                            <div>
                                                <div class="flex justify-between items-end mb-2">
                                                    <label
                                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Images
                                                        Slider</label>
                                                    <span
                                                        class="text-[10px] text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">Multi-sélection</span>
                                                </div>
                                                <div
                                                    class="grid grid-cols-3 gap-2 max-h-48 overflow-y-auto p-2 border border-gray-200 dark:border-[#30363d] bg-gray-50/50 dark:bg-[#21262d]/50 rounded-lg">
                                                    <template
                                                        x-for="media in mediaItems.filter(m => m.url && m.media_type === 'image')"
                                                        :key="media.url">
                                                        <div class="cursor-pointer relative rounded-md overflow-hidden border-2 bg-white dark:bg-[#161b22] transition-all"
                                                            :class="form.config.slider_images.includes(media.url) ? 'border-primary-500 shadow-sm' : 'border-transparent hover:border-gray-300 dark:hover:border-gray-600'"
                                                            @click="if(form.config.slider_images.includes(media.url)) { form.config.slider_images = form.config.slider_images.filter(x => x !== media.url); } else { form.config.slider_images.push(media.url); } fetchLivePreview()"
                                                            :title="media.description || media.original_name || media.media_key">
                                                            <img :src="media.url" class="h-16 w-full object-cover">
                                                            <div x-show="form.config.slider_images.includes(media.url)"
                                                                class="absolute inset-0 bg-primary-500/20 flex items-center justify-center">
                                                                <div class="bg-white rounded-full shadow-sm">
                                                                    <svg class="w-5 h-5 text-primary-600"
                                                                        fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd"
                                                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                                            clip-rule="evenodd" />
                                                                    </svg>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </template>
                                                    <div x-show="mediaItems.filter(m => m.url && m.media_type === 'image').length === 0"
                                                        class="col-span-3 text-center py-4 text-sm text-gray-500">
                                                        Aucune image trouvée dans la bibliothèque.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Services Additionnels -->
                                    <div class="border-t border-gray-200 dark:border-[#30363d] pt-4 mt-4">
                                        <div class="flex justify-between items-center mb-2">
                                            <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Services
                                                Affichés</h4>
                                            <button
                                                @click.prevent="form.config.services.push({title: '', description: '', icon: '&#11088;'})"
                                                class="text-primary-600 text-xs font-semibold px-2 py-1 bg-primary-50 rounded hover:bg-primary-100 dark:bg-primary-900/30 dark:hover:bg-primary-900/50">+
                                                Ajouter</button>
                                        </div>
                                        <div class="space-y-3">
                                            <template x-for="(srv, index) in form.config.services" :key="index">
                                                <div
                                                    class="flex gap-2 items-start border border-gray-200 dark:border-[#30363d] p-3 rounded-lg relative bg-gray-50 dark:bg-[#0d1117]">
                                                    <button @click.prevent="form.config.services.splice(index, 1)"
                                                        class="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>

                                                    <div class="flex-1 space-y-2 mr-6">
                                                        <div class="flex gap-2">
                                                            <input type="text" x-model="srv.icon"
                                                                placeholder="Emoji/HTML Icon"
                                                                class="w-16 px-2 py-1.5 text-xs border border-gray-300 dark:border-[#30363d] rounded bg-white dark:bg-[#21262d] text-center"
                                                                title="Emoji ou Icône SVG">
                                                            <input type="text" x-model="srv.title"
                                                                placeholder="Titre (ex: Wifi Rapide)"
                                                                class="flex-1 px-2 py-1.5 text-xs border border-gray-300 dark:border-[#30363d] rounded bg-white dark:bg-[#21262d]">
                                                        </div>
                                                        <input type="text" x-model="srv.description"
                                                            placeholder="Description courte du service"
                                                            class="w-full px-2 py-1.5 text-xs border border-gray-300 dark:border-[#30363d] rounded bg-white dark:bg-[#21262d]">
                                                    </div>
                                                </div>
                                            </template>
                                            <p x-show="form.config.services.length === 0"
                                                class="text-xs text-gray-500 italic">Aucun service mis en avant.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Colonne droite - Preview -->
                            <div class="lg:sticky lg:top-0">
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <?= __('template.live_preview')?>
                                    </h4>
                                    <div class="flex items-center bg-gray-100 dark:bg-[#21262d] rounded-lg p-1">
                                        <button @click="previewMode = 'mobile'" type="button"
                                            :class="previewMode === 'mobile' ? 'bg-white dark:bg-[#30363d] shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                            class="px-3 py-1 text-xs font-medium rounded-md transition-all flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            Mobile
                                        </button>
                                        <button @click="previewMode = 'desktop'" type="button"
                                            :class="previewMode === 'desktop' ? 'bg-white dark:bg-[#30363d] shadow-sm text-primary-600' : 'text-gray-500 hover:text-gray-700'"
                                            class="px-3 py-1 text-xs font-medium rounded-md transition-all flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            Desktop
                                        </button>
                                    </div>
                                </div>
                                <div :class="previewMode === 'mobile' ? 'max-w-[360px] aspect-[9/16] max-h-[640px]' : 'w-full aspect-video'"
                                    class="relative border border-gray-200/60 dark:border-[#30363d] rounded-lg overflow-hidden mx-auto bg-white dark:bg-[#0d1117] flex flex-col shadow-inner transition-all duration-300">
                                    <template x-if="livePreviewHtml">
                                        <iframe :srcdoc="livePreviewHtml"
                                            :style="previewMode === 'desktop' ? 'width: 200%; height: 200%; transform: scale(0.5); transform-origin: top left;' : 'width: 100%; height: 100%;'"
                                            class="absolute top-0 left-0 border-0"
                                            sandbox="allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox"></iframe>
                                    </template>
                                    <template x-if="!livePreviewHtml">
                                        <div
                                            class="absolute inset-0 flex items-center justify-center bg-gray-50/50 backdrop-blur-sm">
                                            <div
                                                class="animate-spin rounded-full h-8 w-8 border-4 border-primary-500 border-t-transparent">
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Variables MikroTik -->
                                <div class="mt-4 p-3 bg-gray-50 dark:bg-[#0d1117] rounded-lg">
                                    <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?= __('template.mikrotik_variables')?>
                                    </h5>
                                    <div class="grid grid-cols-2 gap-1 text-xs text-gray-500">
                                        <span>$(link-login-only)</span>
                                        <span>$(link-orig)</span>
                                        <span>$(username)</span>
                                        <span>$(error)</span>
                                        <span>$(mac)</span>
                                        <span>$(ip)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex-shrink-0 px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end gap-3">
                    <button type="button" @click="closeModal()"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                        <?= __('common.cancel')?>
                    </button>
                    <button @click="saveTemplate()" :disabled="saving"
                        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors disabled:opacity-50">
                        <span x-show="!saving"
                            x-text="showEditModal ? __('common.update') : __('common.create')"></span>
                        <span x-show="saving">
                            <?= __('template.saving')?>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Preview -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="showPreviewModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('template.preview_title')?>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button @click="downloadPreviewHtml()"
                            class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            <?= __('template.download_html')?>
                        </button>
                        <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-4 bg-gray-100 dark:bg-[#0d1117]">
                    <iframe :srcdoc="previewHtml" class="w-full h-[70vh] rounded-lg border-0 bg-white"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function hotspotTemplatesPage() {
        return {
            templates: [],
            zones: [],
            profiles: [],
            mediaItems: [],
            loading: true,
            saving: false,
            showCreateModal: false,
            showEditModal: false,
            showPreviewModal: false,
            activeTab: 'general',
            previewMode: 'mobile',
            previewHtml: '',
            livePreviewHtml: '',
            previewDebounce: null,
            currentTemplate: null,
            form: {
                name: '',
                template_code: '',
                logo_url: '',
                logo_position: 'center',
                background_type: 'gradient',
                background_color: '#1e3a5f',
                background_gradient_start: '#1e3a5f',
                background_gradient_end: '#0d1b2a',
                primary_color: '#3b82f6',
                secondary_color: '#10b981',
                text_color: '#ffffff',
                card_bg_color: '#ffffff',
                card_text_color: '#1f2937',
                title_text: __('template.default_title'),
                subtitle_text: '',
                login_button_text: __('template.default_login_button'),
                username_placeholder: __('template.default_username'),
                password_placeholder: __('template.default_password'),
                footer_text: '',
                show_logo: true,
                show_password_field: true,
                show_remember_me: false,
                show_footer: true,
                show_chat_support: false,
                chat_support_type: 'whatsapp',
                chat_whatsapp_phone: '',
                chat_welcome_message: __('template.default_welcome'),
                html_content: '',
                css_content: '',
                js_content: '',
                config: {
                    contact_number: '',
                    default_auth_method: 'voucher',
                    zone_id: '',
                    selected_profiles: [],
                    logo_url: '',
                    slider_images: [],
                    services: []
                }
            },

            get filteredProfiles() {
                if (!this.form.config.zone_id) return [];
                return this.profiles.filter(p => parseInt(p.zone_id) === parseInt(this.form.config.zone_id));
            },

            async init() {
                await Promise.all([
                    this.loadTemplates(),
                    this.loadZones(),
                    this.loadProfiles(),
                    this.loadMedia()
                ]);
            },

            async loadZones() {
                try {
                    const res = await API.get('/zones');
                    this.zones = res.data || [];
                } catch (e) { }
            },

            async loadProfiles() {
                try {
                    const res = await API.get('/profiles');
                    this.profiles = res.data || [];
                } catch (e) { }
            },

            async loadMedia() {
                try {
                    const res = await API.get('/library');
                    this.mediaItems = res.data || [];
                } catch (e) { }
            },

            triggerLivePreview() {
                if (!this.showCreateModal && !this.showEditModal) return;
                clearTimeout(this.previewDebounce);
                this.previewDebounce = setTimeout(() => {
                    this.fetchLivePreview();
                }, 800);
            },

            async fetchLivePreview() {
                try {
                    this.livePreviewHtml = ''; // Show spinner
                    const response = await API.post('/templates/hotspot/preview-live', this.form);
                    this.livePreviewHtml = response.data.html;
                } catch (error) {
                    console.error("Erreur de preview live", error);
                }
            },

            async loadTemplates() {
                this.loading = true;
                try {
                    const response = await API.get('/templates/hotspot');
                    this.templates = response.data || [];
                } catch (error) {
                    Toast.error(__('template.msg_load_error'));
                    this.templates = [];
                } finally {
                    this.loading = false;
                }
            },

            getPreviewStyle(template) {
                if (template.background_type === 'color') {
                    return `background-color: ${template.background_color};`;
                }
                return `background: linear-gradient(135deg, ${template.background_gradient_start} 0%, ${template.background_gradient_end} 100%);`;
            },

            resetForm() {
                this.form = {
                    name: '',
                    template_code: '',
                    logo_url: '',
                    logo_position: 'center',
                    background_type: 'gradient',
                    background_color: '#1e3a5f',
                    background_gradient_start: '#1e3a5f',
                    background_gradient_end: '#0d1b2a',
                    primary_color: '#3b82f6',
                    secondary_color: '#10b981',
                    text_color: '#ffffff',
                    card_bg_color: '#ffffff',
                    card_text_color: '#1f2937',
                    title_text: __('template.default_title'),
                    subtitle_text: '',
                    login_button_text: __('template.default_login_button'),
                    username_placeholder: __('template.default_username'),
                    password_placeholder: __('template.default_password'),
                    footer_text: '',
                    show_logo: true,
                    show_password_field: true,
                    show_remember_me: false,
                    show_footer: true,
                    show_chat_support: false,
                    chat_support_type: 'whatsapp',
                    chat_whatsapp_phone: '',
                    chat_welcome_message: __('template.default_welcome'),
                    html_content: '',
                    css_content: '',
                    js_content: '',
                    config: {
                        contact_number: '',
                        default_auth_method: 'voucher',
                        zone_id: '',
                        selected_profiles: [],
                        logo_url: '',
                        slider_images: [],
                        services: []
                    }
                };
                this.activeTab = 'general';
            },

            editTemplate(template) {
                this.currentTemplate = template;
                this.form = {
                    name: template.name || '',
                    template_code: template.template_code || '',
                    logo_url: template.logo_url || '',
                    logo_position: template.logo_position || 'center',
                    background_type: template.background_type || 'gradient',
                    background_color: template.background_color || '#1e3a5f',
                    background_gradient_start: template.background_gradient_start || '#1e3a5f',
                    background_gradient_end: template.background_gradient_end || '#0d1b2a',
                    primary_color: template.primary_color || '#3b82f6',
                    secondary_color: template.secondary_color || '#10b981',
                    text_color: template.text_color || '#ffffff',
                    card_bg_color: template.card_bg_color || '#ffffff',
                    card_text_color: template.card_text_color || '#1f2937',
                    title_text: template.title_text || '',
                    subtitle_text: template.subtitle_text || '',
                    login_button_text: template.login_button_text || __('template.default_login_button'),
                    username_placeholder: template.username_placeholder || __('template.default_username'),
                    password_placeholder: template.password_placeholder || __('template.default_password'),
                    footer_text: template.footer_text || '',
                    show_logo: !!template.show_logo,
                    show_password_field: !!template.show_password_field,
                    show_remember_me: !!template.show_remember_me,
                    show_footer: !!template.show_footer,
                    show_chat_support: !!template.show_chat_support,
                    chat_support_type: template.chat_support_type || 'whatsapp',
                    chat_whatsapp_phone: template.chat_whatsapp_phone || '',
                    chat_welcome_message: template.chat_welcome_message || __('template.default_welcome'),
                    html_content: template.html_content || '',
                    css_content: template.css_content || '',
                    js_content: template.js_content || '',
                    config: template.config ? (typeof template.config === 'string' ? JSON.parse(template.config) : template.config) : {
                        contact_number: '',
                        default_auth_method: 'voucher',
                        zone_id: '',
                        selected_profiles: [],
                        logo_url: '',
                        slider_images: [],
                    }
                };

                // S'assurer que les tableaux existent bien
                if (!this.form.config.selected_profiles) this.form.config.selected_profiles = [];
                if (!this.form.config.slider_images) this.form.config.slider_images = [];
                if (!this.form.config.services) this.form.config.services = [];

                this.activeTab = 'general';
                this.showEditModal = true;
                this.fetchLivePreview();
            },

            async saveTemplate() {
                if (!this.form.name || !this.form.template_code) {
                    Toast.error(__('template.msg_name_code_required'));
                    return;
                }

                this.saving = true;
                try {
                    if (this.showEditModal && this.currentTemplate) {
                        await API.put(`/templates/hotspot/${this.currentTemplate.id}`, this.form);
                        Toast.success(__('template.msg_updated'));
                    } else {
                        await API.post('/templates/hotspot', this.form);
                        Toast.success(__('template.msg_created'));
                    }
                    await this.loadTemplates();
                    this.closeModal();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_save_error'));
                } finally {
                    this.saving = false;
                }
            },

            async deleteTemplate(template) {
                if (!confirm(__('template.msg_confirm_delete').replace(':name', template.name))) return;

                try {
                    await API.delete(`/templates/hotspot/${template.id}`);
                    Toast.success(__('template.msg_deleted'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_delete_error'));
                }
            },

            async setDefault(template) {
                try {
                    await API.post(`/templates/hotspot/${template.id}/default`);
                    Toast.success(__('template.msg_set_default'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('common.error'));
                }
            },

            async duplicateTemplate(template) {
                try {
                    await API.post(`/templates/hotspot/${template.id}/duplicate`);
                    Toast.success(__('template.msg_duplicated'));
                    await this.loadTemplates();
                } catch (error) {
                    Toast.error(error.message || __('template.msg_duplicate_error'));
                }
            },

            async previewTemplate(template) {
                try {
                    const response = await API.post(`/templates/hotspot/${template.id}/generate`);
                    this.previewHtml = response.data.html;
                    this.currentTemplate = template;
                    this.showPreviewModal = true;
                } catch (error) {
                    Toast.error(__('template.msg_preview_error'));
                }
            },

            async downloadTemplate(template) {
                try {
                    const response = await API.post(`/templates/hotspot/${template.id}/generate`);
                    const blob = new Blob([response.data.html], { type: 'text/html' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'login.html';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                    Toast.success(__('template.msg_downloaded'));
                } catch (error) {
                    Toast.error(__('template.msg_download_error'));
                }
            },

            downloadPreviewHtml() {
                if (!this.previewHtml) return;
                const blob = new Blob([this.previewHtml], { type: 'text/html' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'login.html';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            },

            closeModal() {
                this.showCreateModal = false;
                this.showEditModal = false;
                this.currentTemplate = null;
                this.livePreviewHtml = '';
                clearTimeout(this.previewDebounce);
                this.res
            }
        }
    }
</script>