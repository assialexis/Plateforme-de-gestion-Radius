<?php $pageTitle = __('page.voucher_templates');
$currentPage = 'voucher-templates'; ?>

<div x-data="voucherTemplatesPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('template.voucher_subtitle')?>
            </p>
        </div>
        <button @click="showCreateModal = true"
            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <?= __('template.new_template')?>
        </button>
    </div>



    <!-- Mes Templates -->
    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
        <?= __('template.my_templates')?>
    </h3>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <template x-for="template in templates" :key="template.id">
            <div
                class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <!-- Header du template -->
                <div class="p-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3"
                            :style="'background-color: ' + template.primary_color + '20'">
                            <svg class="w-5 h-5" :style="'color: ' + template.primary_color" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white" x-text="template.name"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="template.description"></p>
                        </div>
                    </div>
                    <template x-if="template.is_default">
                        <span
                            class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded-full">
                            <?= __('template.default')?>
                        </span>
                    </template>
                </div>

                <!-- Aperçu du template -->
                <div class="p-4 bg-gray-50 dark:bg-[#0d1117]">
                    <div class="border-2 border-dashed border-gray-300 dark:border-[#30363d] rounded-lg p-4">
                        <!-- Mini aperçu d'un ticket -->
                        <div class="bg-white dark:bg-[#161b22] rounded border p-3 max-w-xs mx-auto"
                            :style="'border-color: ' + template.border_color + '; background-color: ' + template.background_color">
                            <!-- Header ticket -->
                            <div x-show="template.show_logo || template.header_text"
                                class="text-center mb-2 px-2 py-1 rounded-t"
                                :style="'background: ' + template.primary_color">
                                <p class="text-xs font-bold text-white uppercase tracking-wide"
                                    x-text="template.header_text || 'WiFi Hotspot'"></p>
                            </div>
                            <!-- Body with optional QR -->
                            <div class="py-2 px-3"
                                :class="parseInt(template.show_qr_code) ? 'flex items-center gap-3' : ''">
                                <!-- QR placeholder -->
                                <div x-show="parseInt(template.show_qr_code)" class="flex-shrink-0">
                                    <svg width="48" height="48" viewBox="0 0 21 21" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" :style="'color: ' + template.text_color">
                                        <rect x="1" y="1" width="7" height="7" rx="1" fill="currentColor" opacity="0.15"
                                            stroke="currentColor" stroke-width="0.5" />
                                        <rect x="2.5" y="2.5" width="4" height="4" rx="0.5" fill="currentColor" />
                                        <rect x="13" y="1" width="7" height="7" rx="1" fill="currentColor"
                                            opacity="0.15" stroke="currentColor" stroke-width="0.5" />
                                        <rect x="14.5" y="2.5" width="4" height="4" rx="0.5" fill="currentColor" />
                                        <rect x="1" y="13" width="7" height="7" rx="1" fill="currentColor"
                                            opacity="0.15" stroke="currentColor" stroke-width="0.5" />
                                        <rect x="2.5" y="14.5" width="4" height="4" rx="0.5" fill="currentColor" />
                                        <rect x="10" y="10" width="2" height="2" fill="currentColor" />
                                        <rect x="13" y="13" width="2" height="2" fill="currentColor" />
                                        <rect x="16" y="13" width="2" height="2" fill="currentColor" />
                                        <rect x="13" y="16" width="2" height="2" fill="currentColor" />
                                        <rect x="10" y="13" width="2" height="2" fill="currentColor" opacity="0.5" />
                                        <rect x="10" y="16" width="2" height="2" fill="currentColor" opacity="0.5" />
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <!-- Code -->
                                    <div class="py-1"
                                        :class="parseInt(template.show_qr_code) ? '' : 'text-center border-t border-b border-gray-100 dark:border-[#30363d]'">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">Code</p>
                                        <p class="font-mono font-bold" :style="'color: ' + template.text_color">TEST0001
                                        </p>
                                    </div>
                                    <!-- Password -->
                                    <div x-show="template.show_password" class="py-0.5">
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <?= __('common.password')?>
                                        </p>
                                        <p class="font-mono text-sm" :style="'color: ' + template.text_color">PASS0001
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Infos -->
                            <div
                                class="flex justify-between text-xs mt-2 pt-2 border-t border-gray-100 dark:border-[#30363d]">
                                <span x-show="template.show_validity" class="text-gray-500">1h</span>
                                <span x-show="template.show_price" :style="'color: ' + template.primary_color">100
                                    XAF</span>
                            </div>
                            <!-- Footer -->
                            <p x-show="template.footer_text" class="text-center text-xs text-gray-400 mt-2"
                                x-text="template.footer_text"></p>
                        </div>
                    </div>
                </div>

                <!-- Infos et Actions -->
                <div class="p-4 border-t border-gray-200 dark:border-[#30363d]">
                    <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                <?= __('template.format_label')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="template.paper_size + ' ' + template.orientation"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                <?= __('template.grid_label')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white"
                                x-text="template.columns_count + ' x ' + template.rows_count"></p>
                        </div>
                        <div>
                            <p class="text-gray-500 dark:text-gray-400">
                                <?= __('template.type_label')?>
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white capitalize"
                                x-text="template.template_type"></p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button @click="editTemplate(template)"
                            class="flex-1 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <?= __('template.edit')?>
                        </button>
                        <button @click="previewTemplate(template)"
                            class="flex-1 px-3 py-2 text-sm font-medium text-primary-700 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-900/50 transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            <?= __('template.preview')?>
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
    <div x-show="templates.length === 0 && !loading" class="text-center py-12">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
        </svg>
        <p class="text-gray-500 dark:text-gray-400">
            <?= __('template.no_voucher_template')?>
        </p>
    </div>

    <!-- Modal Créer/Modifier Template -->
    <div x-show="showCreateModal || showEditModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="closeModal()"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div
                    class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                        x-text="showEditModal ? __('template.edit_template') : __('template.new_template')"></h3>
                    <button @click="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="saveTemplate()">
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Colonne gauche - Infos de base -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                <?= __('template.general_info')?>
                            </h4>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('template.template_name')?>
                                </label>
                                <input type="text" x-model="form.name" required
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('common.description')?>
                                </label>
                                <input type="text" x-model="form.description"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.type_label')?>
                                    </label>
                                    <select x-model="form.template_type"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <option value="simple">
                                            <?= __('template.simple')?>
                                        </option>
                                        <option value="detailed">
                                            <?= __('template.detailed')?>
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.paper_size')?>
                                    </label>
                                    <select x-model="form.paper_size"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <option value="A4">A4</option>
                                        <option value="Letter">Letter</option>
                                        <option value="A5">A5</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.orientation')?>
                                    </label>
                                    <select x-model="form.orientation"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                        <option value="portrait">
                                            <?= __('template.portrait')?>
                                        </option>
                                        <option value="landscape">
                                            <?= __('template.landscape')?>
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.columns')?>
                                    </label>
                                    <input type="number" x-model="form.columns_count" min="1" max="6"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.rows')?>
                                    </label>
                                    <input type="number" x-model="form.rows_count" min="1" max="15"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('template.header_text')?>
                                </label>
                                <input type="text" x-model="form.header_text" placeholder="WiFi Hotspot"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('template.footer_text_voucher')?>
                                </label>
                                <input type="text" x-model="form.footer_text" placeholder="Merci de votre visite!"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            </div>
                        </div>

                        <!-- Colonne droite - Options et couleurs -->
                        <div class="space-y-4">
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                <?= __('template.display_options')?>
                            </h4>

                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_logo"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Logo</span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_qr_code"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">QR Code</span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_password"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        <?= __('common.password')?>
                                    </span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_validity"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        <?= __('common.validity')?>
                                    </span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_speed"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        <?= __('common.speed')?>
                                    </span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-gray-200/60 dark:border-[#30363d] rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                                    <input type="checkbox" x-model="form.show_price"
                                        class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        <?= __('common.price')?>
                                    </span>
                                </label>
                            </div>

                            <h4 class="font-medium text-gray-900 dark:text-white pt-4">
                                <?= __('template.colors')?>
                            </h4>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.main_color')?>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="form.primary_color"
                                            class="w-10 h-10 rounded border-0 cursor-pointer">
                                        <input type="text" x-model="form.primary_color"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.text_color')?>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="form.text_color"
                                            class="w-10 h-10 rounded border-0 cursor-pointer">
                                        <input type="text" x-model="form.text_color"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.bg_color_voucher')?>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="form.background_color"
                                            class="w-10 h-10 rounded border-0 cursor-pointer">
                                        <input type="text" x-model="form.background_color"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <?= __('template.border_color')?>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="form.border_color"
                                            class="w-10 h-10 rounded border-0 cursor-pointer">
                                        <input type="text" x-model="form.border_color"
                                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        class="sticky bottom-0 bg-gray-50 dark:bg-[#21262d]/50 px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end gap-3">
                        <button type="button" @click="closeModal()"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-white dark:bg-[#161b22] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#30363d]">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50">
                            <span x-show="!saving"
                                x-text="showEditModal ? __('common.save') : __('common.create')"></span>
                            <span x-show="saving">
                                <?= __('template.saving')?>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Aperçu -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" @click="showPreviewModal = false"></div>
            <div
                class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <div
                    class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?= __('template.preview')?> - <span x-text="previewTemplate?.name"></span>
                    </h3>
                    <div class="flex items-center gap-2">
                        <button @click="printPreview()"
                            class="px-3 py-1.5 text-sm font-medium text-primary-700 dark:text-primary-400 bg-primary-100 dark:bg-primary-900/30 rounded-lg hover:bg-primary-200">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                            <?= __('common.print')?>
                        </button>
                        <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-500">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6" id="print-area">
                    <template x-if="previewTemplate">
                        <div class="grid gap-1"
                            :style="'grid-template-columns: repeat(' + previewTemplate.columns_count + ', 1fr)'">
                            <template x-for="(voucher, index) in previewVouchers" :key="index">
                                <div class="rounded overflow-hidden text-center"
                                    :style="'border: 1.5px solid ' + previewTemplate.border_color + '; background-color: ' + previewTemplate.background_color + '; font-size: 8pt;'">
                                    <!-- Header -->
                                    <div x-show="previewTemplate.show_logo || previewTemplate.header_text"
                                        class="px-2 py-1"
                                        :style="'background-color: ' + previewTemplate.primary_color + '; color: #fff; font-size: 7pt; font-weight: 700; letter-spacing: 0.5px; text-transform: uppercase;'">
                                        <span x-text="previewTemplate.header_text || 'WiFi'"></span>
                                    </div>

                                    <!-- Body -->
                                    <div class="px-2 py-1"
                                        :class="parseInt(previewTemplate.show_qr_code) ? 'flex items-center gap-2' : ''">
                                        <!-- QR placeholder -->
                                        <div x-show="parseInt(previewTemplate.show_qr_code)" class="flex-shrink-0">
                                            <svg width="36" height="36" viewBox="0 0 21 21" fill="none"
                                                xmlns="http://www.w3.org/2000/svg"
                                                :style="'color: ' + previewTemplate.text_color">
                                                <rect x="1" y="1" width="7" height="7" rx="1" fill="currentColor"
                                                    opacity="0.15" stroke="currentColor" stroke-width="0.5" />
                                                <rect x="2.5" y="2.5" width="4" height="4" rx="0.5"
                                                    fill="currentColor" />
                                                <rect x="13" y="1" width="7" height="7" rx="1" fill="currentColor"
                                                    opacity="0.15" stroke="currentColor" stroke-width="0.5" />
                                                <rect x="14.5" y="2.5" width="4" height="4" rx="0.5"
                                                    fill="currentColor" />
                                                <rect x="1" y="13" width="7" height="7" rx="1" fill="currentColor"
                                                    opacity="0.15" stroke="currentColor" stroke-width="0.5" />
                                                <rect x="2.5" y="14.5" width="4" height="4" rx="0.5"
                                                    fill="currentColor" />
                                                <rect x="10" y="10" width="2" height="2" fill="currentColor" />
                                                <rect x="13" y="13" width="2" height="2" fill="currentColor" />
                                                <rect x="16" y="13" width="2" height="2" fill="currentColor" />
                                                <rect x="13" y="16" width="2" height="2" fill="currentColor" />
                                                <rect x="10" y="13" width="2" height="2" fill="currentColor"
                                                    opacity="0.5" />
                                                <rect x="10" y="16" width="2" height="2" fill="currentColor"
                                                    opacity="0.5" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <!-- Code -->
                                            <div class="flex items-center gap-1 py-0.5"
                                                :class="parseInt(previewTemplate.show_qr_code) ? '' : 'justify-center'">
                                                <span class="text-gray-400"
                                                    style="font-size: 6pt; font-weight: 600;">ID</span>
                                                <span class="font-mono font-bold"
                                                    style="font-size: 9pt; letter-spacing: 0.3px;"
                                                    :style="'color: ' + previewTemplate.text_color"
                                                    x-text="voucher.code"></span>
                                            </div>

                                            <!-- Password -->
                                            <div x-show="previewTemplate.show_password"
                                                class="flex items-center gap-1 py-0.5"
                                                :class="parseInt(previewTemplate.show_qr_code) ? '' : 'justify-center'">
                                                <span class="text-gray-400"
                                                    style="font-size: 6pt; font-weight: 600;">Pass</span>
                                                <span class="font-mono font-bold" style="font-size: 9pt;"
                                                    :style="'color: ' + previewTemplate.text_color"
                                                    x-text="voucher.password"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Footer info -->
                                    <div class="flex justify-around items-center px-2 py-0.5"
                                        :style="'border-top: 1px solid ' + previewTemplate.border_color + '; font-size: 6.5pt; color: #666; background: #f8f8f8;'">
                                        <span x-show="previewTemplate.show_validity" x-text="voucher.validity"></span>
                                        <span x-show="previewTemplate.show_speed" x-text="voucher.speed"></span>
                                        <span x-show="previewTemplate.show_price" class="font-bold"
                                            :style="'color: ' + previewTemplate.primary_color"
                                            x-text="voucher.price"></span>
                                    </div>

                                    <!-- Footer text -->
                                    <div x-show="previewTemplate.footer_text" class="px-2 py-0.5"
                                        style="font-size: 5.5pt; color: #999;">
                                        <span x-text="previewTemplate.footer_text"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    function voucherTemplatesPage() {
        return {
            templates: [],
            loading: false,
            saving: false,
            showCreateModal: false,
            showEditModal: false,
            showPreviewModal: false,
            editingId: null,
            previewTemplate: null,
            previewVouchers: [],

            form: {
                name: '',
                description: '',
                template_type: 'simple',
                paper_size: 'A4',
                orientation: 'portrait',
                columns_count: 4,
                rows_count: 8,
                show_logo: true,
                show_qr_code: false,
                show_password: true,
                show_validity: true,
                show_speed: false,
                show_price: true,
                header_text: 'WiFi Hotspot',
                footer_text: 'Merci de votre visite!',
                background_color: '#ffffff',
                border_color: '#e2e8f0',
                primary_color: '#1a1a2e',
                text_color: '#0f172a',
                is_default: false
            },

            async init() {
                await this.loadTemplates();
            },

            async loadTemplates() {
                this.loading = true;
                try {
                    const response = await API.get('/templates/vouchers');
                    this.templates = response.data;
                } catch (error) {
                    showToast(__('template.msg_load_error'), 'error');
                } finally {
                    this.loading = false;
                }
            },

            resetForm() {
                this.form = {
                    name: '',
                    description: '',
                    template_type: 'simple',
                    paper_size: 'A4',
                    orientation: 'portrait',
                    columns_count: 4,
                    rows_count: 8,
                    show_logo: true,
                    show_qr_code: false,
                    show_password: true,
                    show_validity: true,
                    show_speed: false,
                    show_price: true,
                    header_text: 'WiFi Hotspot',
                    footer_text: 'Merci de votre visite!',
                    background_color: '#ffffff',
                    border_color: '#e2e8f0',
                    primary_color: '#1a1a2e',
                    text_color: '#0f172a',
                    is_default: false
                };
            },

            editTemplate(template) {
                this.editingId = template.id;
                this.form = {
                    name: template.name,
                    description: template.description || '',
                    template_type: template.template_type || 'simple',
                    paper_size: template.paper_size || 'A4',
                    orientation: template.orientation || 'portrait',
                    columns_count: parseInt(template.columns_count) || 4,
                    rows_count: parseInt(template.rows_count) || 8,
                    show_logo: !!parseInt(template.show_logo),
                    show_qr_code: !!parseInt(template.show_qr_code),
                    show_password: !!parseInt(template.show_password),
                    show_validity: !!parseInt(template.show_validity),
                    show_speed: !!parseInt(template.show_speed),
                    show_price: !!parseInt(template.show_price),
                    header_text: template.header_text || '',
                    footer_text: template.footer_text || '',
                    background_color: template.background_color || '#ffffff',
                    border_color: template.border_color || '#e2e8f0',
                    primary_color: template.primary_color || '#1a1a2e',
                    text_color: template.text_color || '#0f172a',
                    is_default: !!parseInt(template.is_default)
                };
                this.showEditModal = true;
            },

            async saveTemplate() {
                this.saving = true;
                try {
                    const data = {
                        ...this.form,
                        show_logo: this.form.show_logo ? 1 : 0,
                        show_qr_code: this.form.show_qr_code ? 1 : 0,
                        show_password: this.form.show_password ? 1 : 0,
                        show_validity: this.form.show_validity ? 1 : 0,
                        show_speed: this.form.show_speed ? 1 : 0,
                        show_price: this.form.show_price ? 1 : 0,
                        is_default: this.form.is_default ? 1 : 0
                    };

                    if (this.showEditModal) {
                        await API.put(`/templates/vouchers/${this.editingId}`, data);
                        showToast(__('template.msg_updated'));
                    } else {
                        await API.post('/templates/vouchers', data);
                        showToast(__('template.msg_created'));
                    }
                    this.closeModal();
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('template.msg_save_error'), 'error');
                } finally {
                    this.saving = false;
                }
            },

            closeModal() {
                this.showCreateModal = false;
                this.showEditModal = false;
                this.editingId = null;
                this.resetForm();
            },

            async setDefault(template) {
                try {
                    await API.post(`/templates/vouchers/${template.id}/default`);
                    showToast(__('template.msg_set_default'));
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('common.error'), 'error');
                }
            },

            async deleteTemplate(template) {
                if (!confirmAction(__('template.msg_confirm_delete').replace(':name', template.name))) return;

                try {
                    await API.delete(`/templates/vouchers/${template.id}`);
                    showToast(__('template.msg_deleted'));
                    await this.loadTemplates();
                } catch (error) {
                    showToast(error.message || __('template.msg_delete_error'), 'error');
                }
            },

            async previewTemplate(template) {
                try {
                    const response = await API.post(`/templates/vouchers/${template.id}/preview`);
                    this.previewTemplate = response.data.template;
                    this.previewVouchers = response.data.vouchers;
                    this.showPreviewModal = true;
                } catch (error) {
                    showToast(__('template.msg_preview_error'), 'error');
                }
            },

            printPreview() {
                const t = this.previewTemplate;
                if (!t) return;

                const showQr = !!parseInt(t.show_qr_code);

                const ticketsHtml = this.previewVouchers.map((v, i) => {
                    let html = `<div class="ticket">`;
                    if (t.show_logo || t.header_text) {
                        html += `<div class="t-header">${t.header_text || 'WiFi Hotspot'}</div>`;
                    }
                    html += `<div class="t-body-container">`;
                    html += `<div class="${showQr ? 't-body-qr' : 't-info'}">`;
                    if (showQr) {
                        html += `<div class="t-qr" id="qr-${i}"></div><div class="t-info">`;
                    }
                    html += `<div class="t-row"><span class="t-label">PIN / CODE</span><span class="t-value">${v.code}</span></div>`;
                    if (t.show_password) {
                        html += `<div class="t-row"><span class="t-label">PASSWORD</span><span class="t-value">${v.password}</span></div>`;
                    }
                    if (showQr) {
                        html += `</div>`;
                    }
                    html += `</div></div>`;

                    const infos = [];
                    if (t.show_validity && v.validity) infos.push(`<span>${v.validity}</span>`);
                    if (t.show_speed && v.speed) infos.push(`<span>${v.speed}</span>`);
                    if (t.show_price && v.price) infos.push(`<span class="t-price">${v.price}</span>`);
                    if (infos.length) {
                        html += `<div class="t-footer">${infos.join('')}</div>`;
                    }
                    if (t.footer_text) {
                        html += `<div class="t-note">${t.footer_text}</div>`;
                    }
                    html += `</div>`;
                    return html;
                }).join('');

                const cols = t.columns_count || 4;
                const qrData = showQr ? JSON.stringify(this.previewVouchers.map(v => v.code)) : '[]';
                const paperSize = t.paper_size || 'A4';
                const orientation = t.orientation || 'portrait';
                
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`<!DOCTYPE html><html><head><title>Impression - Tickets</title>
            ${showQr ? '<script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"><\/script>' : ''}
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');
                @page { size: ${paperSize} ${orientation}; margin: 8mm; }
                * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
                body { background: #f8fafc; }
                .grid { display: grid; grid-template-columns: repeat(${cols}, 1fr); gap: 12px; padding: 10px; }
                .ticket {
                    border: 2px solid ${t.border_color || '#e2e8f0'};
                    border-radius: 12px;
                    overflow: hidden;
                    page-break-inside: avoid;
                    background: ${t.background_color || '#ffffff'};
                    display: flex;
                    flex-direction: column;
                    height: 100%;
                }
                .t-header {
                    background: ${t.primary_color || '#1a1a2e'};
                    color: #fff;
                    text-align: center;
                    font-weight: 700;
                    font-size: 10pt;
                    padding: 8px 10px;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                }
                .t-body-container {
                    display: flex;
                    flex: 1;
                    padding: 12px;
                    align-items: center;
                    justify-content: center;
                }
                .t-body-qr { display: flex; align-items: center; gap: 12px; width: 100%; }
                .t-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; width: 100%; }
                .t-qr { flex-shrink: 0; background: #fff; padding: 2px; border-radius: 6px; border: 1px solid #e2e8f0; }
                .t-qr img, .t-qr svg { width: 50px; height: 50px; display: block; }
                .t-row { display: flex; flex-direction: column; text-align: center; }
                .t-label { font-size: 6.5pt; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; }
                .t-value {
                    font-family: 'Consolas', 'Courier New', monospace;
                    font-weight: 700;
                    font-size: 11pt;
                    color: ${t.text_color || '#0f172a'};
                    letter-spacing: 1.5px;
                    background: #f1f5f9;
                    padding: 4px 6px;
                    border-radius: 6px;
                    border: 1px solid #e2e8f0;
                }
                .t-footer {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 12px;
                    background: #f8fafc;
                    font-size: 8pt;
                    color: #475569;
                    border-top: 2px dashed #e2e8f0;
                    font-weight: 600;
                }
                .t-price { font-weight: 800; color: ${t.primary_color || '#1a1a2e'}; font-size: 9pt; }
                .t-note { text-align: center; font-size: 6.5pt; color: #94a3b8; padding: 4px 12px 8px; background: #f8fafc; font-weight: 500; }
                @media print {
                    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background: none; }
                }
            </style></head><body>
            <div class="grid">${ticketsHtml}</div>
            <script>
                var codes = ${qrData};
                function generateQRCodes() {
                    if (typeof qrcode === 'undefined' || !codes.length) { window.print(); return; }
                    codes.forEach(function(code, i) {
                        var el = document.getElementById('qr-' + i);
                        if (!el) return;
                        var qr = qrcode(0, 'M');
                        qr.addData(code);
                        qr.make();
                        el.innerHTML = qr.createSvgTag(3, 0);
                    });
                    setTimeout(function(){ window.print(); }, 200);
                }
                if (${showQr}) {
                    var checkLib = setInterval(function(){
                        if (typeof qrcode !== 'undefined') { clearInterval(checkLib); generateQRCodes(); }
                    }, 50);
                    setTimeout(function(){ clearInterval(checkLib); window.print(); }, 3000);
                } else {
                    window.onload = function(){ setTimeout(function(){ window.print(); }, 200); };
                }
            <\/script>
            </body></html>`);
                printWindow.document.close();
            }
        }
    }

</script>