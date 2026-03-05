<?php $pageTitle = __('otp.title'); $currentPage = 'otp'; ?>

<div x-data="otpPage()">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <?= __('otp.title') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('otp.subtitle') ?></p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="-mb-px flex gap-6 overflow-x-auto">
            <button @click="activeTab = 'config'"
                :class="activeTab === 'config' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('otp.tab_config') ?>
            </button>
            <button @click="activeTab = 'snippet'"
                :class="activeTab === 'snippet' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('otp.tab_snippet') ?>
            </button>
            <button @click="activeTab = 'history'; if(!historyLoaded) loadHistory()"
                :class="activeTab === 'history' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('otp.tab_history') ?>
            </button>
            <button @click="activeTab = 'stats'; if(!statsLoaded) loadStats()"
                :class="activeTab === 'stats' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('otp.tab_stats') ?>
            </button>
            <button @click="activeTab = 'inscription'; if(!regConfigLoaded) loadRegConfig()"
                :class="activeTab === 'inscription' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('registration.tab') ?>
            </button>
        </nav>
    </div>

    <!-- Tab: Configuration -->
    <div x-show="activeTab === 'config'" x-cloak>
        <div x-show="loading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div x-show="!loading" class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Config Card (3 cols) -->
            <div class="lg:col-span-3 bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6"><?= __('otp.config_title') ?></h3>

                <!-- Activer/Désactiver -->
                <div class="flex items-center justify-between mb-6 p-4 rounded-lg bg-gray-50 dark:bg-[#21262d]">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white"><?= __('otp.enable_otp') ?></p>
                        <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('otp.enable_otp_desc') ?></p>
                    </div>
                    <button @click="config.is_enabled = config.is_enabled ? 0 : 1"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                        :class="config.is_enabled ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                            :class="config.is_enabled ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>

                <!-- Hotspot DNS -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('otp.hotspot_dns') ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" x-model="config.hotspot_dns"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="hotspot.local ou 10.0.0.1">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"><?= __('otp.hotspot_dns_hint') ?></p>
                </div>

                <!-- Gateway SMS -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('otp.sms_gateway') ?> <span class="text-red-500">*</span>
                    </label>
                    <select x-model="config.sms_gateway_id" x-ref="gatewaySelect"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value=""><?= __('otp.select_gateway') ?></option>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- Code pays -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('otp.country_code') ?>
                        </label>
                        <div class="flex items-center">
                            <span class="px-3 py-2 bg-gray-100 dark:bg-[#21262d] border border-r-0 border-gray-300 dark:border-[#30363d] rounded-l-lg text-gray-500 text-sm">+</span>
                            <input type="text" x-model="config.country_code"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-r-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="229" maxlength="4">
                        </div>
                    </div>

                    <!-- Longueur OTP -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('otp.otp_length') ?>
                        </label>
                        <select x-model="config.otp_length"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="4">4 <?= __('otp.digits') ?></option>
                            <option value="5">5 <?= __('otp.digits') ?></option>
                            <option value="6">6 <?= __('otp.digits') ?></option>
                            <option value="7">7 <?= __('otp.digits') ?></option>
                            <option value="8">8 <?= __('otp.digits') ?></option>
                        </select>
                    </div>

                    <!-- Durée expiration -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('otp.expiry_time') ?>
                        </label>
                        <select x-model="config.otp_expiry_seconds"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="60">1 min</option>
                            <option value="120">2 min</option>
                            <option value="180">3 min</option>
                            <option value="300">5 min</option>
                            <option value="600">10 min</option>
                        </select>
                    </div>
                </div>

                <!-- Template SMS -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        <?= __('otp.sms_template') ?>
                    </label>
                    <textarea x-model="config.sms_template" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono text-sm"
                        placeholder="Votre code OTP: {{otp_code}}"></textarea>
                    <div class="mt-1 flex items-center justify-between">
                        <div class="flex flex-wrap gap-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('otp.available_vars') ?>:</span>
                            <template x-for="v in ['{{otp_code}}', '{{company_name}}', '{{expiry_duration}}']">
                                <button @click="config.sms_template += ' ' + v" type="button"
                                    class="px-1.5 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50 font-mono"
                                    x-text="v"></button>
                            </template>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                            <span x-text="(config.sms_template || '').length"></span> car.
                            · <span x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))((config.sms_template || '').length)"></span> SMS
                            · <span class="text-amber-500 dark:text-amber-400" x-text="((l) => l <= 160 ? 1 : Math.ceil(l / 153))((config.sms_template || '').length)"></span> CSMS
                        </span>
                    </div>
                </div>

                <!-- Bouton sauvegarder -->
                <div class="flex justify-end">
                    <button @click="saveConfig()"
                        :disabled="saving"
                        class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <svg x-show="saving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg x-show="!saving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <?= __('otp.save') ?>
                    </button>
                </div>
            </div>

            <!-- Phone Preview (2 cols) -->
            <div class="lg:col-span-2">
                <div class="sticky top-6">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3 text-center"><?= __('otp.preview') ?></p>
                    <!-- Phone Frame -->
                    <div class="mx-auto" style="width: 280px;">
                        <div class="bg-gray-900 rounded-[2.5rem] p-3 shadow-2xl">
                            <!-- Notch -->
                            <div class="flex justify-center mb-2">
                                <div class="w-24 h-5 bg-black rounded-full"></div>
                            </div>
                            <!-- Screen -->
                            <div class="bg-white dark:bg-gray-100 rounded-[1.8rem] overflow-hidden" style="min-height: 420px;">
                                <!-- Status Bar -->
                                <div class="flex items-center justify-between px-5 py-2 bg-gray-50">
                                    <span class="text-[10px] font-semibold text-gray-800">09:41</span>
                                    <div class="flex items-center gap-1">
                                        <svg class="w-3 h-3 text-gray-800" fill="currentColor" viewBox="0 0 24 24"><path d="M1 9l2 2c4.97-4.97 13.03-4.97 18 0l2-2C16.93 2.93 7.08 2.93 1 9z"/></svg>
                                        <svg class="w-3 h-3 text-gray-800" fill="currentColor" viewBox="0 0 24 24"><rect x="17" y="4" width="4" height="16" rx="1"/><rect x="11" y="8" width="4" height="12" rx="1"/><rect x="5" y="12" width="4" height="8" rx="1"/></svg>
                                        <div class="w-5 h-2.5 border border-gray-800 rounded-sm relative">
                                            <div class="absolute inset-0.5 bg-gray-800 rounded-[1px]" style="width:70%"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- SMS Notification -->
                                <div class="px-3 pt-3 pb-2">
                                    <!-- SMS Header -->
                                    <div class="bg-gray-50 rounded-xl p-3 mb-2 shadow-sm border border-gray-100">
                                        <div class="flex items-center gap-2 mb-2">
                                            <div class="w-8 h-8 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-xs font-semibold text-gray-900">SMS</p>
                                                <p class="text-[10px] text-gray-500"><?= __('otp.preview_now') ?></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Message Bubble -->
                                    <div class="bg-green-50 rounded-2xl rounded-tl-md p-3.5 shadow-sm border border-green-100">
                                        <p class="text-xs text-gray-800 leading-relaxed break-words" x-text="getPreviewMessage()" style="word-break: break-word;"></p>
                                        <p class="text-[9px] text-gray-400 text-right mt-2">09:41</p>
                                    </div>

                                    <!-- OTP Code Display -->
                                    <div class="mt-4 text-center">
                                        <p class="text-[10px] text-gray-400 uppercase tracking-wider mb-2"><?= __('otp.preview_code') ?></p>
                                        <div class="flex justify-center gap-1.5">
                                            <template x-for="(digit, i) in getPreviewOtpDigits()" :key="i">
                                                <div class="w-8 h-10 bg-white border-2 border-blue-400 rounded-lg flex items-center justify-center shadow-sm">
                                                    <span class="text-sm font-bold text-blue-600" x-text="digit"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Phone Number Preview -->
                                    <div class="mt-4 bg-gray-50 rounded-xl p-3 border border-gray-100">
                                        <p class="text-[10px] text-gray-400 mb-1"><?= __('otp.preview_sent_to') ?></p>
                                        <p class="text-xs font-mono font-semibold text-gray-700">+<span x-text="config.country_code || '229'"></span> 96 XX XX XX</p>
                                    </div>

                                    <!-- Expiry Info -->
                                    <div class="mt-3 flex items-center justify-center gap-1.5">
                                        <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <p class="text-[10px] text-gray-400">
                                            <?= __('otp.preview_expires') ?>
                                            <span class="font-semibold text-amber-600" x-text="getExpiryLabel()"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Home Indicator -->
                            <div class="flex justify-center mt-2">
                                <div class="w-28 h-1 bg-gray-600 rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Code Snippet -->
    <div x-show="activeTab === 'snippet'" x-cloak>
        <div class="max-w-3xl">
            <!-- Instructions -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5 mb-6">
                <div class="flex gap-3">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h4 class="font-medium text-blue-900 dark:text-blue-200 mb-1"><?= __('otp.snippet_instructions_title') ?></h4>
                        <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                            <li><?= __('otp.snippet_step1') ?></li>
                            <li><?= __('otp.snippet_step2') ?></li>
                            <li><?= __('otp.snippet_step3') ?></li>
                            <li><?= __('otp.snippet_step4') ?></li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Generate Button -->
            <div class="mb-4">
                <button @click="generateSnippet()"
                    :disabled="snippetLoading"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                    <svg x-show="snippetLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg x-show="!snippetLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                    <?= __('otp.generate_snippet') ?>
                </button>
            </div>

            <!-- Code Block -->
            <div x-show="snippet" class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-[#30363d] bg-gray-50 dark:bg-[#21262d]">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= __('otp.snippet_label') ?></span>
                    <button @click="copySnippet()"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                        :class="snippetCopied ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'">
                        <template x-if="!snippetCopied">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </template>
                        <template x-if="snippetCopied">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <span x-text="snippetCopied ? '<?= __js('otp.copied') ?>' : '<?= __js('otp.copy') ?>'"></span>
                    </button>
                </div>
                <pre class="p-4 text-sm text-gray-800 dark:text-gray-200 overflow-x-auto font-mono leading-relaxed"><code x-text="snippet"></code></pre>
            </div>
        </div>
    </div>

    <!-- Tab: History -->
    <div x-show="activeTab === 'history'" x-cloak>
        <div x-show="historyLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div x-show="!historyLoading">
            <!-- Search & Filters -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-4 mb-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" x-model="historySearch"
                                @keydown.enter="historyPage = 1; loadHistory()"
                                placeholder="<?= __('otp.search_placeholder') ?? 'Rechercher par téléphone ou voucher...' ?>"
                                class="w-full pl-10 pr-4 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <select x-model="historyStatus" @change="historyPage = 1; loadHistory()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        <option value=""><?= __('otp.all_statuses') ?? 'Tous les statuts' ?></option>
                        <option value="verified"><?= __('otp.status_verified') ?? 'Vérifié' ?></option>
                        <option value="pending"><?= __('otp.status_pending') ?? 'En attente' ?></option>
                        <option value="failed"><?= __('otp.status_failed') ?? 'Échoué' ?></option>
                        <option value="expired"><?= __('otp.status_expired') ?? 'Expiré' ?></option>
                    </select>
                    <input type="date" x-model="historyDateFrom" @change="historyPage = 1; loadHistory()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                        title="<?= __('otp.date_from') ?? 'Date début' ?>">
                    <input type="date" x-model="historyDateTo" @change="historyPage = 1; loadHistory()"
                        class="px-3 py-2 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500"
                        title="<?= __('otp.date_to') ?? 'Date fin' ?>">
                    <button @click="historyPage = 1; loadHistory()"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                    <button x-show="historySearch || historyStatus || historyDateFrom || historyDateTo"
                        @click="historySearch = ''; historyStatus = ''; historyDateFrom = ''; historyDateTo = ''; historyPage = 1; loadHistory()"
                        class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                        title="<?= __('otp.clear_filters') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <!-- Export -->
                    <button @click="exportHistory()" :disabled="historyExporting"
                        class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors disabled:opacity-50 flex items-center gap-1.5"
                        title="<?= __('otp.export_csv') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span class="hidden sm:inline"><?= __('otp.export_csv') ?></span>
                    </button>
                </div>
                <!-- Bulk delete bar -->
                <div x-show="historySelected.length > 0" x-cloak class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-200 dark:border-[#30363d]">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        <span x-text="historySelected.length"></span> <?= __('otp.selected') ?>
                    </span>
                    <button @click="deleteSelected()" :disabled="historyDeleting"
                        class="px-3 py-1.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50 flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <span x-show="!historyDeleting"><?= __('otp.delete_selected') ?></span>
                        <span x-show="historyDeleting"><?= __('common.loading') ?? '...' ?></span>
                    </button>
                    <button @click="historySelected = []; historySelectAll = false"
                        class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                        <?= __('otp.deselect_all') ?>
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-[#21262d] border-b border-gray-200 dark:border-[#30363d]">
                                <th class="px-3 py-3 w-10">
                                    <input type="checkbox" x-model="historySelectAll"
                                        @change="historySelected = historySelectAll ? history.map(i => i.id) : []"
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.phone') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.voucher') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.status') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.device') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.date') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                            <template x-for="item in history" :key="item.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                                    :class="historySelected.includes(item.id) ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''">
                                    <td class="px-3 py-3 w-10">
                                        <input type="checkbox" :value="item.id" x-model="historySelected"
                                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono" x-text="item.phone"></td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono" x-text="item.voucher_username"></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.status === 'verified',
                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': item.status === 'pending',
                                                'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.status === 'failed',
                                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': item.status === 'expired',
                                            }"
                                            x-text="getStatusLabel(item.status)"></span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <span x-text="parseUserAgent(item.user_agent).deviceIcon" class="text-base"></span>
                                            <div class="text-xs">
                                                <p class="text-gray-700 dark:text-gray-300 font-medium" x-text="parseUserAgent(item.user_agent).browser"></p>
                                                <p class="text-gray-400" x-text="parseUserAgent(item.user_agent).os"></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(item.created_at)"></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="showDetail(item)"
                                                class="p-1.5 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                                                title="<?= __('otp.details') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteSingle(item.id)"
                                                class="p-1.5 text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                title="<?= __('otp.delete') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div x-show="history.length === 0" class="flex flex-col items-center justify-center py-12">
                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400"><?= __('otp.no_history') ?></p>
                </div>

                <!-- Pagination -->
                <div x-show="historyTotalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <?= __('otp.showing') ?> <span x-text="((historyPage - 1) * 20) + 1"></span> - <span x-text="Math.min(historyPage * 20, historyTotal)"></span> <?= __('otp.of') ?> <span x-text="historyTotal"></span>
                    </p>
                    <div class="flex gap-2">
                        <button @click="historyPage--; loadHistory()" :disabled="historyPage <= 1"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
                            <?= __('otp.prev') ?>
                        </button>
                        <button @click="historyPage++; loadHistory()" :disabled="historyPage >= historyTotalPages"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
                            <?= __('otp.next') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Stats -->
    <div x-show="activeTab === 'stats'" x-cloak>
        <div x-show="statsLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div x-show="!statsLoading">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Total -->
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_total') ?></p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
                        </div>
                    </div>
                </div>

                <!-- Vérifiés -->
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_verified') ?></p>
                            <p class="text-2xl font-bold text-green-600" x-text="stats.verified || 0"></p>
                        </div>
                    </div>
                </div>

                <!-- Échoués -->
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-red-50 dark:bg-red-900/20 flex items-center justify-center">
                            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_failed') ?></p>
                            <p class="text-2xl font-bold text-red-600" x-text="stats.failed || 0"></p>
                        </div>
                    </div>
                </div>

                <!-- Expirés -->
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_expired') ?></p>
                            <p class="text-2xl font-bold text-gray-600" x-text="stats.expired || 0"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Today stats -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3"><?= __('otp.today') ?></h4>
                <div class="grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.today_total || 0"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('otp.stat_total') ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-green-600" x-text="stats.today_verified || 0"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('otp.stat_verified') ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-red-600" x-text="stats.today_failed || 0"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('otp.stat_failed') ?></p>
                    </div>
                </div>
            </div>

            <!-- Top 10 clients récurrents -->
            <div x-show="stats.top_clients && stats.top_clients.length > 0" class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden mt-6">
                <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <?= __('otp.top_clients') ?? 'Top 10 clients récurrents' ?>
                    </h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-[#21262d] border-b border-gray-200 dark:border-[#30363d]">
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.phone') ?></th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.total_requests') ?? 'Requêtes' ?></th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_verified') ?></th>
                                <th class="px-4 py-2.5 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.stat_failed') ?></th>
                                <th class="px-4 py-2.5 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.last_seen') ?? 'Dernière activité' ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                            <template x-for="(client, idx) in stats.top_clients" :key="client.phone">
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                                    <td class="px-4 py-2.5">
                                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold"
                                            :class="{
                                                'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': idx === 0,
                                                'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300': idx === 1,
                                                'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': idx === 2,
                                                'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400': idx > 2,
                                            }"
                                            x-text="idx + 1"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white" x-text="client.phone"></td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="client.total_requests"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-sm text-green-600 font-medium" x-text="client.verified || 0"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-center">
                                        <span class="text-sm text-red-600 font-medium" x-text="client.failed || 0"></span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(client.last_seen)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Inscription (Registration) -->
    <div x-show="activeTab === 'inscription'" x-cloak>
        <div x-show="regConfigLoading" class="flex justify-center py-12">
            <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>

        <div x-show="!regConfigLoading">
            <!-- Sub-tabs for Inscription -->
            <div class="flex gap-2 mb-6">
                <button @click="regSubTab = 'config'"
                    :class="regSubTab === 'config' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <?= __('otp.tab_config') ?>
                </button>
                <button @click="regSubTab = 'snippet'; if(!regSnippet) generateRegSnippet()"
                    :class="regSubTab === 'snippet' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <?= __('otp.tab_snippet') ?>
                </button>
                <button @click="regSubTab = 'history'; if(!regHistoryLoaded) loadRegHistory()"
                    :class="regSubTab === 'history' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <?= __('otp.tab_history') ?>
                </button>
                <button @click="regSubTab = 'stats'; if(!regStatsLoaded) loadRegStats()"
                    :class="regSubTab === 'stats' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-[#21262d] text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d]'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    <?= __('otp.tab_stats') ?>
                </button>
            </div>

            <!-- Sub-tab: Config -->
            <div x-show="regSubTab === 'config'">
                <div class="max-w-2xl bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6"><?= __('registration.enable') ?></h3>

                    <!-- Activer/Désactiver -->
                    <div class="flex items-center justify-between mb-6 p-4 rounded-lg bg-gray-50 dark:bg-[#21262d]">
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white"><?= __('registration.enable') ?></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Permettre aux utilisateurs de s'inscrire via leur telephone</p>
                        </div>
                        <button @click="regConfig.registration_enabled = regConfig.registration_enabled == 1 ? 0 : 1"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="regConfig.registration_enabled == 1 ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="regConfig.registration_enabled == 1 ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    <!-- Code du jour -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('registration.daily_code_label') ?> <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <input type="text" x-model="regConfig.daily_code"
                                :disabled="regConfig.daily_code_auto_rotate == 1"
                                class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50"
                                placeholder="Ex: WIFI2024">
                            <button @click="regConfig.daily_code = generateRandomCode()" type="button"
                                :disabled="regConfig.daily_code_auto_rotate == 1"
                                class="px-3 py-2 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-[#30363d] disabled:opacity-50">
                                <?= __('registration.generate_code') ?>
                            </button>
                        </div>
                    </div>

                    <!-- Rotation automatique -->
                    <div class="flex items-center justify-between mb-4 p-3 rounded-lg bg-gray-50 dark:bg-[#21262d]">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white"><?= __('registration.auto_rotate') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Le code change automatiquement chaque jour</p>
                        </div>
                        <button @click="regConfig.daily_code_auto_rotate = regConfig.daily_code_auto_rotate == 1 ? 0 : 1"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                            :class="regConfig.daily_code_auto_rotate == 1 ? 'bg-blue-600' : 'bg-gray-300 dark:bg-gray-600'">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                                :class="regConfig.daily_code_auto_rotate == 1 ? 'translate-x-6' : 'translate-x-1'"></span>
                        </button>
                    </div>

                    <!-- Code actuel (si auto-rotate) -->
                    <div x-show="regConfig.daily_code_auto_rotate == 1" class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-300">
                            <span class="font-medium"><?= __('registration.current_code') ?>:</span>
                            <span class="font-mono font-bold text-lg ml-2" x-text="regCurrentCode"></span>
                        </p>
                    </div>

                    <!-- Profil par défaut -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('registration.default_profile') ?> <span class="text-red-500">*</span>
                        </label>
                        <select x-model="regConfig.registration_profile_id" x-ref="profileSelect"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">-- Choisir un profil --</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <!-- Durée du voucher -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('registration.validity_days') ?>
                            </label>
                            <input type="number" x-model="regConfig.registration_validity_days" min="1" max="365"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Max par numéro -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('registration.max_per_phone') ?>
                            </label>
                            <input type="number" x-model="regConfig.registration_max_per_phone" min="1" max="100"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Bouton sauvegarder -->
                    <div class="flex justify-end">
                        <button @click="saveRegConfig()"
                            :disabled="regSaving"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <svg x-show="regSaving" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="!regSaving" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <?= __('otp.save') ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Sub-tab: Snippet -->
            <div x-show="regSubTab === 'snippet'">
                <div class="max-w-3xl">
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-5 mb-6">
                        <div class="flex gap-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                            <div>
                                <h4 class="font-medium text-blue-900 dark:text-blue-200 mb-1"><?= __('registration.snippet') ?></h4>
                                <ol class="text-sm text-blue-800 dark:text-blue-300 space-y-1 list-decimal list-inside">
                                    <li>Copiez le code ci-dessous</li>
                                    <li>Collez-le dans votre page de login hotspot (login.html) avant &lt;/body&gt;</li>
                                    <li>Un bouton "S'inscrire" apparaitra sur la page de connexion</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <button @click="generateRegSnippet()"
                            :disabled="regSnippetLoading"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                            <svg x-show="regSnippetLoading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg x-show="!regSnippetLoading" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <?= __('otp.generate_snippet') ?>
                        </button>
                    </div>

                    <div x-show="regSnippet" class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-[#30363d] bg-gray-50 dark:bg-[#21262d]">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300"><?= __('registration.snippet') ?></span>
                            <button @click="copyRegSnippet()"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors flex items-center gap-1.5"
                                :class="regSnippetCopied ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/50'">
                                <template x-if="!regSnippetCopied">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </template>
                                <template x-if="regSnippetCopied">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </template>
                                <span x-text="regSnippetCopied ? '<?= __js('otp.copied') ?>' : '<?= __js('otp.copy') ?>'"></span>
                            </button>
                        </div>
                        <pre class="p-4 text-sm text-gray-800 dark:text-gray-200 overflow-x-auto font-mono leading-relaxed"><code x-text="regSnippet"></code></pre>
                    </div>
                </div>
            </div>

            <!-- Sub-tab: History -->
            <div x-show="regSubTab === 'history'">
                <div x-show="regHistoryLoading" class="flex justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div x-show="!regHistoryLoading">
                    <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 dark:bg-[#21262d] border-b border-gray-200 dark:border-[#30363d]">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.phone') ?></th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.voucher') ?></th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Profil</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.status') ?></th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">IP</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('otp.date') ?></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                                    <template x-for="item in regHistory" :key="item.id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white font-mono" x-text="item.phone"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300 font-mono" x-text="item.voucher_username || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300" x-text="item.profile_name || '-'"></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                    :class="{
                                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.status === 'completed',
                                                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': item.status === 'pending',
                                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.status === 'failed',
                                                    }"
                                                    x-text="item.status === 'completed' ? 'Termine' : item.status === 'pending' ? 'En attente' : 'Echoue'"></span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 font-mono" x-text="item.ip_address || '-'"></td>
                                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(item.created_at)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div x-show="regHistory.length === 0" class="flex flex-col items-center justify-center py-12">
                            <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Aucune inscription</p>
                        </div>

                        <!-- Pagination -->
                        <div x-show="regHistoryTotalPages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <span x-text="((regHistoryPage - 1) * 20) + 1"></span> - <span x-text="Math.min(regHistoryPage * 20, regHistoryTotal)"></span> / <span x-text="regHistoryTotal"></span>
                            </p>
                            <div class="flex gap-2">
                                <button @click="regHistoryPage--; loadRegHistory()" :disabled="regHistoryPage <= 1"
                                    class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
                                    <?= __('otp.prev') ?>
                                </button>
                                <button @click="regHistoryPage++; loadRegHistory()" :disabled="regHistoryPage >= regHistoryTotalPages"
                                    class="px-3 py-1.5 text-sm border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-50 dark:hover:bg-[#21262d] disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 dark:text-gray-300">
                                    <?= __('otp.next') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sub-tab: Stats -->
            <div x-show="regSubTab === 'stats'">
                <div x-show="regStatsLoading" class="flex justify-center py-12">
                    <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                <div x-show="!regStatsLoading">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('registration.total') ?></p>
                                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="regStats.total || 0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('registration.today') ?></p>
                                    <p class="text-2xl font-bold text-green-600" x-text="regStats.today_completed || 0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-indigo-50 dark:bg-indigo-900/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase"><?= __('registration.this_week') ?></p>
                                    <p class="text-2xl font-bold text-indigo-600" x-text="regStats.week_completed || 0"></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm border border-gray-200/60 dark:border-[#30363d] p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 uppercase">En attente</p>
                                    <p class="text-2xl font-bold text-yellow-600" x-text="regStats.pending || 0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Modal -->
    <div x-show="detailItem" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        @keydown.escape.window="detailItem = null">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="detailItem = null"></div>
        <!-- Modal -->
        <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200/60 dark:border-[#30363d] w-full max-w-lg max-h-[90vh] overflow-y-auto"
            @click.stop>
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?= __('otp.client_details') ?>
                </h3>
                <button @click="detailItem = null" class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-[#21262d] rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4" x-show="detailItem">
                <!-- Status Badge -->
                <div class="flex items-center justify-between">
                    <span class="px-3 py-1.5 text-sm font-medium rounded-full"
                        :class="{
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': detailItem?.status === 'verified',
                            'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400': detailItem?.status === 'pending',
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': detailItem?.status === 'failed',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': detailItem?.status === 'expired',
                        }"
                        x-text="getStatusLabel(detailItem?.status)"></span>
                    <span class="text-sm text-gray-400" x-text="detailItem?.attempts + '/3 <?= __js('otp.attempts') ?>'"></span>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Telephone -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1"><?= __('otp.phone') ?></p>
                        <p class="text-sm font-mono font-semibold text-gray-900 dark:text-white" x-text="detailItem?.phone"></p>
                    </div>
                    <!-- Voucher -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1"><?= __('otp.voucher') ?></p>
                        <p class="text-sm font-mono font-semibold text-gray-900 dark:text-white" x-text="detailItem?.voucher_username"></p>
                    </div>
                    <!-- IP -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">IP</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white" x-text="detailItem?.ip_address || '-'"></p>
                    </div>
                    <!-- MAC -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">MAC</p>
                        <p class="text-sm font-mono text-gray-900 dark:text-white" x-text="detailItem?.mac_address || '-'"></p>
                    </div>
                    <!-- Date creation -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1"><?= __('otp.date') ?></p>
                        <p class="text-sm text-gray-900 dark:text-white" x-text="formatDate(detailItem?.created_at)"></p>
                    </div>
                    <!-- Date verification -->
                    <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-3">
                        <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1"><?= __('otp.verified_at') ?></p>
                        <p class="text-sm text-gray-900 dark:text-white" x-text="detailItem?.verified_at ? formatDate(detailItem.verified_at) : '-'"></p>
                    </div>
                </div>

                <!-- Device Info -->
                <div class="bg-gray-50 dark:bg-[#21262d] rounded-xl p-4" x-show="detailItem?.user_agent">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-3"><?= __('otp.device_info') ?></p>
                    <div class="space-y-3" x-data="{ ua: {} }" x-init="ua = parseUserAgent(detailItem?.user_agent)">
                        <!-- Device type icon + name -->
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center text-xl" x-text="ua.deviceIcon"></div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white" x-text="ua.deviceType"></p>
                                <p class="text-xs text-gray-400" x-text="ua.device"></p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Browser -->
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-purple-50 dark:bg-purple-900/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400"><?= __('otp.browser') ?></p>
                                    <p class="text-xs font-medium text-gray-800 dark:text-gray-200" x-text="ua.browser"></p>
                                </div>
                            </div>
                            <!-- OS -->
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-lg bg-green-50 dark:bg-green-900/20 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-400"><?= __('otp.os') ?></p>
                                    <p class="text-xs font-medium text-gray-800 dark:text-gray-200" x-text="ua.os"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Raw User Agent -->
                <div x-show="detailItem?.user_agent">
                    <p class="text-[10px] uppercase tracking-wider text-gray-400 mb-1">User Agent</p>
                    <p class="text-[11px] font-mono text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-[#0d1117] rounded-lg p-2.5 break-all leading-relaxed" x-text="detailItem?.user_agent"></p>
                </div>

                <!-- No UA -->
                <div x-show="!detailItem?.user_agent" class="text-center py-4">
                    <p class="text-sm text-gray-400"><?= __('otp.no_device_info') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function otpPage() {
    return {
        activeTab: 'config',
        loading: true,
        saving: false,

        // Config
        config: {
            is_enabled: 0,
            hotspot_dns: '',
            otp_length: 6,
            otp_expiry_seconds: 300,
            sms_gateway_id: '',
            sms_template: '',
            country_code: '229',
        },
        gateways: [],

        // Snippet
        snippet: '',
        snippetLoading: false,
        snippetCopied: false,

        // History
        history: [],
        historyLoaded: false,
        historyLoading: false,
        historyPage: 1,
        historyTotal: 0,
        historyTotalPages: 0,
        historySearch: '',
        historyStatus: '',
        historyDateFrom: '',
        historyDateTo: '',
        historySelected: [],
        historySelectAll: false,
        historyDeleting: false,
        historyExporting: false,

        // Stats
        stats: {},
        statsLoaded: false,
        statsLoading: false,

        // Detail modal
        detailItem: null,

        // Registration
        regSubTab: 'config',
        regConfigLoaded: false,
        regConfigLoading: false,
        regSaving: false,
        regConfig: {
            registration_enabled: 0,
            daily_code: '',
            daily_code_auto_rotate: 0,
            registration_profile_id: '',
            registration_validity_days: 1,
            registration_max_per_phone: 1,
        },
        regProfiles: [],
        regCurrentCode: '',
        regSnippet: '',
        regSnippetLoading: false,
        regSnippetCopied: false,
        regHistory: [],
        regHistoryLoaded: false,
        regHistoryLoading: false,
        regHistoryPage: 1,
        regHistoryTotal: 0,
        regHistoryTotalPages: 0,
        regStats: {},
        regStatsLoaded: false,
        regStatsLoading: false,

        init() {
            this.loadConfig();
        },

        async loadConfig() {
            this.loading = true;
            try {
                const res = await fetch('api.php?route=/otp/config');
                const data = await res.json();
                if (data.success) {
                    this.config = {
                        is_enabled: data.data.config.is_enabled || 0,
                        hotspot_dns: data.data.config.hotspot_dns || '',
                        otp_length: data.data.config.otp_length || 6,
                        otp_expiry_seconds: data.data.config.otp_expiry_seconds || 300,
                        sms_gateway_id: data.data.config.sms_gateway_id || '',
                        sms_template: data.data.config.sms_template || '',
                        country_code: data.data.config.country_code || '229',
                    };
                    this.gateways = data.data.gateways || [];
                }
            } catch (e) {
                console.error('Error loading OTP config:', e);
            }
            this.loading = false;
            this.$nextTick(() => this.populateGatewaySelect());
        },

        populateGatewaySelect() {
            const select = this.$refs.gatewaySelect;
            if (!select || !this.gateways.length) return;
            // Garder seulement la première option (placeholder)
            while (select.options.length > 1) select.remove(1);
            this.gateways.forEach(gw => {
                const opt = document.createElement('option');
                opt.value = gw.id;
                opt.textContent = gw.name + (parseInt(gw.is_active) ? '' : ' (<?= __js('otp.inactive') ?>)');
                select.appendChild(opt);
            });
            // Restaurer la valeur sélectionnée
            if (this.config.sms_gateway_id) {
                select.value = this.config.sms_gateway_id;
            }
        },

        async saveConfig() {
            this.saving = true;
            try {
                const res = await fetch('api.php?route=/otp/config', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.config),
                });
                const data = await res.json();
                if (data.success) {
                    this.notify('<?= __js('otp.config_saved') ?>', 'success');
                } else {
                    this.notify(data.message || '<?= __js('otp.config_save_error') ?>', 'error');
                }
            } catch (e) {
                this.notify('<?= __js('otp.config_save_error') ?>', 'error');
            }
            this.saving = false;
        },

        async generateSnippet() {
            this.snippetLoading = true;
            try {
                const res = await fetch('api.php?route=/otp/snippet');
                const data = await res.json();
                if (data.success) {
                    this.snippet = data.data.snippet;
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur de chargement', 'error');
            }
            this.snippetLoading = false;
        },

        copySnippet() {
            navigator.clipboard.writeText(this.snippet).then(() => {
                this.snippetCopied = true;
                this.notify('<?= __js('otp.snippet_copied') ?>', 'success');
                setTimeout(() => this.snippetCopied = false, 2000);
            });
        },

        async loadHistory() {
            this.historyLoading = true;
            try {
                let url = 'api.php?route=/otp/history&page=' + this.historyPage;
                if (this.historySearch) url += '&search=' + encodeURIComponent(this.historySearch);
                if (this.historyStatus) url += '&status=' + encodeURIComponent(this.historyStatus);
                if (this.historyDateFrom) url += '&date_from=' + encodeURIComponent(this.historyDateFrom);
                if (this.historyDateTo) url += '&date_to=' + encodeURIComponent(this.historyDateTo);
                const res = await fetch(url);
                const data = await res.json();
                if (data.success) {
                    this.history = data.data.items || [];
                    this.historyTotal = data.data.total || 0;
                    this.historyTotalPages = data.data.total_pages || 0;
                    this.historyLoaded = true;
                }
            } catch (e) {
                console.error('Error loading OTP history:', e);
            }
            this.historyLoading = false;
        },

        async loadStats() {
            this.statsLoading = true;
            try {
                const res = await fetch('api.php?route=/otp/stats');
                const data = await res.json();
                if (data.success) {
                    this.stats = data.data;
                    this.statsLoaded = true;
                }
            } catch (e) {
                console.error('Error loading OTP stats:', e);
            }
            this.statsLoading = false;
        },

        exportHistory() {
            this.historyExporting = true;
            let url = 'api.php?route=/otp/export';
            if (this.historySearch) url += '&search=' + encodeURIComponent(this.historySearch);
            if (this.historyStatus) url += '&status=' + encodeURIComponent(this.historyStatus);
            if (this.historyDateFrom) url += '&date_from=' + encodeURIComponent(this.historyDateFrom);
            if (this.historyDateTo) url += '&date_to=' + encodeURIComponent(this.historyDateTo);
            const a = document.createElement('a');
            a.href = url;
            a.download = '';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => this.historyExporting = false, 1000);
        },

        async deleteSingle(id) {
            if (!confirm('<?= __js('otp.confirm_delete_one') ?>')) return;
            await this.doDelete([id]);
        },

        async deleteSelected() {
            if (!confirm('<?= __js('otp.confirm_delete_many') ?>')) return;
            await this.doDelete(this.historySelected);
        },

        async doDelete(ids) {
            this.historyDeleting = true;
            try {
                const res = await fetch('api.php?route=/otp/history', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ ids }),
                });
                const data = await res.json();
                if (data.success) {
                    this.notify(data.data.message, 'success');
                    this.historySelected = [];
                    this.historySelectAll = false;
                    await this.loadHistory();
                } else {
                    this.notify(data.message || '<?= __js('common.error') ?>', 'error');
                }
            } catch (e) {
                this.notify('<?= __js('common.error') ?>', 'error');
            }
            this.historyDeleting = false;
        },

        getPreviewMessage() {
            let msg = this.config.sms_template || 'Votre code OTP: {{otp_code}}';
            const len = parseInt(this.config.otp_length) || 6;
            const sampleCode = '5'.repeat(len).split('').map((d, i) => '483927'[i % 6]).join('');
            msg = msg.replace(/\{\{otp_code\}\}/g, sampleCode);
            msg = msg.replace(/\{\{company_name\}\}/g, 'WiFi Zone');
            msg = msg.replace(/\{\{expiry_duration\}\}/g, this.getExpiryLabel());
            return msg;
        },

        getPreviewOtpDigits() {
            const len = parseInt(this.config.otp_length) || 6;
            return '483927'.substring(0, len).split('');
        },

        getExpiryLabel() {
            const sec = parseInt(this.config.otp_expiry_seconds) || 300;
            if (sec < 60) return sec + 's';
            return Math.floor(sec / 60) + ' min';
        },

        getStatusLabel(status) {
            const labels = {
                'pending': '<?= __js('otp.status_pending') ?>',
                'verified': '<?= __js('otp.status_verified') ?>',
                'failed': '<?= __js('otp.status_failed') ?>',
                'expired': '<?= __js('otp.status_expired') ?>',
            };
            return labels[status] || status;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const d = new Date(dateStr);
            return d.toLocaleDateString('fr-FR') + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        },

        showDetail(item) {
            this.detailItem = item;
        },

        parseUserAgent(ua) {
            if (!ua) return { browser: '-', os: '-', device: '-', deviceType: '<?= __js('otp.unknown') ?>', deviceIcon: '?' };

            let browser = '<?= __js('otp.unknown') ?>';
            let os = '<?= __js('otp.unknown') ?>';
            let device = '';
            let deviceType = '';
            let deviceIcon = '';

            // Detect browser
            if (ua.includes('Edg/')) {
                const m = ua.match(/Edg\/([\d.]+)/);
                browser = 'Edge' + (m ? ' ' + m[1] : '');
            } else if (ua.includes('OPR/') || ua.includes('Opera')) {
                const m = ua.match(/OPR\/([\d.]+)/);
                browser = 'Opera' + (m ? ' ' + m[1] : '');
            } else if (ua.includes('Chrome/') && !ua.includes('Chromium')) {
                const m = ua.match(/Chrome\/([\d.]+)/);
                browser = 'Chrome' + (m ? ' ' + m[1].split('.')[0] : '');
            } else if (ua.includes('Firefox/')) {
                const m = ua.match(/Firefox\/([\d.]+)/);
                browser = 'Firefox' + (m ? ' ' + m[1].split('.')[0] : '');
            } else if (ua.includes('Safari/') && !ua.includes('Chrome')) {
                const m = ua.match(/Version\/([\d.]+)/);
                browser = 'Safari' + (m ? ' ' + m[1] : '');
            }

            // Detect OS
            if (ua.includes('iPhone')) {
                const m = ua.match(/iPhone OS ([\d_]+)/);
                os = 'iOS' + (m ? ' ' + m[1].replace(/_/g, '.') : '');
                device = 'iPhone';
            } else if (ua.includes('iPad')) {
                const m = ua.match(/CPU OS ([\d_]+)/);
                os = 'iPadOS' + (m ? ' ' + m[1].replace(/_/g, '.') : '');
                device = 'iPad';
            } else if (ua.includes('Android')) {
                const m = ua.match(/Android ([\d.]+)/);
                os = 'Android' + (m ? ' ' + m[1] : '');
                // Try to get device model
                const dm = ua.match(/;\s*([^;)]+)\s*Build/);
                device = dm ? dm[1].trim() : 'Android';
            } else if (ua.includes('Windows')) {
                const m = ua.match(/Windows NT ([\d.]+)/);
                const versions = { '10.0': '10/11', '6.3': '8.1', '6.2': '8', '6.1': '7' };
                os = 'Windows' + (m ? ' ' + (versions[m[1]] || m[1]) : '');
                device = 'PC';
            } else if (ua.includes('Mac OS X')) {
                const m = ua.match(/Mac OS X ([\d_.]+)/);
                os = 'macOS' + (m ? ' ' + m[1].replace(/_/g, '.') : '');
                device = 'Mac';
            } else if (ua.includes('Linux')) {
                os = 'Linux';
                device = 'PC';
            } else if (ua.includes('CrOS')) {
                os = 'Chrome OS';
                device = 'Chromebook';
            }

            // Detect device type
            if (ua.includes('Mobile') || ua.includes('iPhone') || (ua.includes('Android') && !ua.includes('Tablet'))) {
                deviceType = '<?= __js('otp.mobile') ?>';
                deviceIcon = '\uD83D\uDCF1';
            } else if (ua.includes('iPad') || ua.includes('Tablet')) {
                deviceType = '<?= __js('otp.tablet') ?>';
                deviceIcon = '\uD83D\uDCF1';
            } else {
                deviceType = '<?= __js('otp.desktop') ?>';
                deviceIcon = '\uD83D\uDCBB';
            }

            return { browser, os, device: device || deviceType, deviceType, deviceIcon };
        },

        // Registration methods
        async loadRegConfig() {
            this.regConfigLoading = true;
            try {
                const res = await fetch('api.php?route=/registration/config');
                const data = await res.json();
                if (data.success) {
                    this.regConfig = {
                        registration_enabled: parseInt(data.data.config.registration_enabled) || 0,
                        daily_code: data.data.config.daily_code || '',
                        daily_code_auto_rotate: parseInt(data.data.config.daily_code_auto_rotate) || 0,
                        registration_profile_id: String(data.data.config.registration_profile_id || ''),
                        registration_validity_days: parseInt(data.data.config.registration_validity_days) || 1,
                        registration_max_per_phone: parseInt(data.data.config.registration_max_per_phone) || 1,
                    };
                    this.regProfiles = data.data.profiles || [];
                    this.regCurrentCode = data.data.current_daily_code || '';
                    this.regConfigLoaded = true;
                }
            } catch (e) {
                console.error('Error loading registration config:', e);
            }
            this.regConfigLoading = false;
            this.$nextTick(() => this.populateProfileSelect());
        },

        populateProfileSelect() {
            const select = this.$refs.profileSelect;
            if (!select || !this.regProfiles.length) return;
            while (select.options.length > 1) select.remove(1);
            this.regProfiles.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.id;
                opt.textContent = p.name + (p.price ? ' (' + p.price + ' ' + (typeof APP_CURRENCY !== 'undefined' ? APP_CURRENCY : 'FCFA') + ')' : '');
                select.appendChild(opt);
            });
            if (this.regConfig.registration_profile_id) {
                select.value = String(this.regConfig.registration_profile_id);
                select.dispatchEvent(new Event('input'));
            }
        },

        async saveRegConfig() {
            this.regSaving = true;
            try {
                const res = await fetch('api.php?route=/registration/config', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.regConfig),
                });
                const data = await res.json();
                if (data.success) {
                    this.notify('Configuration sauvegardee', 'success');
                    // Reload to get updated current code
                    this.loadRegConfig();
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur de sauvegarde', 'error');
            }
            this.regSaving = false;
        },

        generateRandomCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 6; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return code;
        },

        async generateRegSnippet() {
            this.regSnippetLoading = true;
            try {
                const res = await fetch('api.php?route=/registration/snippet');
                const data = await res.json();
                if (data.success) {
                    this.regSnippet = data.data.snippet;
                } else {
                    this.notify(data.message || 'Erreur', 'error');
                }
            } catch (e) {
                this.notify('Erreur de chargement', 'error');
            }
            this.regSnippetLoading = false;
        },

        copyRegSnippet() {
            navigator.clipboard.writeText(this.regSnippet).then(() => {
                this.regSnippetCopied = true;
                this.notify('<?= __js('otp.snippet_copied') ?>', 'success');
                setTimeout(() => this.regSnippetCopied = false, 2000);
            });
        },

        async loadRegHistory() {
            this.regHistoryLoading = true;
            try {
                const res = await fetch('api.php?route=/registration/history&page=' + this.regHistoryPage);
                const data = await res.json();
                if (data.success) {
                    this.regHistory = data.data.items || [];
                    this.regHistoryTotal = data.data.total || 0;
                    this.regHistoryTotalPages = data.data.total_pages || 0;
                    this.regHistoryLoaded = true;
                }
            } catch (e) {
                console.error('Error loading registration history:', e);
            }
            this.regHistoryLoading = false;
        },

        async loadRegStats() {
            this.regStatsLoading = true;
            try {
                const res = await fetch('api.php?route=/registration/stats');
                const data = await res.json();
                if (data.success) {
                    this.regStats = data.data;
                    this.regStatsLoaded = true;
                }
            } catch (e) {
                console.error('Error loading registration stats:', e);
            }
            this.regStatsLoading = false;
        },

        notify(message, type) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { message, type }
            }));
        },
    };
}
</script>
