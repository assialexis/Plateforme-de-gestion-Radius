<?php $pageTitle = __('whatsapp.title'); $currentPage = 'whatsapp'; ?>

<div x-data="whatsappPage()" x-init="init()">
    <!-- En-tête -->
    <div class="mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-7 h-7 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <?= __('whatsapp.title') ?>
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('whatsapp.subtitle') ?></p>
        </div>
        <div class="flex gap-3">
            <button @click="showConfigModal = true"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d] flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?= __('whatsapp.configuration') ?>
            </button>
            <button @click="processExpirations()"
                    :disabled="processing"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                <svg x-show="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <svg x-show="processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="processing ? __('whatsapp.sending') : __('whatsapp.process_expirations')"></span>
            </button>
        </div>
    </div>

    <!-- Onglets -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="-mb-px flex gap-6 overflow-x-auto">
            <button @click="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('whatsapp.tab_templates') ?>
            </button>
            <button @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('whatsapp.tab_history') ?>
            </button>
            <button @click="activeTab = 'stats'"
                    :class="activeTab === 'stats' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm whitespace-nowrap">
                <?= __('whatsapp.tab_stats') ?>
            </button>
        </nav>
    </div>

    <!-- Tab: Templates -->
    <div x-show="activeTab === 'templates'" x-cloak>
        <div class="flex justify-end mb-4">
            <button @click="openTemplateModal()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?= __('whatsapp.new_template') ?>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="tpl in templates" :key="tpl.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white text-sm" x-text="tpl.name"></h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5" x-text="tpl.description"></p>
                        </div>
                        <button @click="tpl.is_active = !tpl.is_active; toggleTemplate(tpl.id)"
                                class="relative inline-flex h-5 w-9 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out"
                                :class="tpl.is_active == 1 ? 'bg-green-500' : 'bg-gray-300 dark:bg-[#30363d]'">
                            <span class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                  :class="tpl.is_active == 1 ? 'translate-x-4' : 'translate-x-0'"></span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                              :class="getEventColor(tpl.event_type)"
                              x-text="getEventLabel(tpl.event_type)"></span>
                        <template x-if="tpl.days_before != 0">
                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                  x-text="tpl.days_before > 0 ? tpl.days_before + __('whatsapp.days_before_suffix') : Math.abs(tpl.days_before) + __('whatsapp.days_after_suffix')"></span>
                        </template>
                    </div>

                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-3 mb-3 max-h-32 overflow-y-auto">
                        <pre class="text-xs text-gray-700 dark:text-gray-300 whitespace-pre-wrap font-sans" x-text="tpl.message_template?.substring(0, 200) + (tpl.message_template?.length > 200 ? '...' : '')"></pre>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-400" x-text="(tpl.usage_count || 0) + ' ' + __('whatsapp.sends')"></span>
                        <div class="flex gap-1">
                            <button @click="previewTemplate(tpl.id)" class="p-1.5 text-gray-400 hover:text-green-600 rounded" :title="__('common.preview')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <button @click="editTemplate(tpl)" class="p-1.5 text-gray-400 hover:text-blue-600 rounded" :title="__('common.edit')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            <button @click="deleteTemplate(tpl.id)" class="p-1.5 text-gray-400 hover:text-red-600 rounded" :title="__('common.delete')">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="templates.length === 0" class="text-center py-12 text-gray-500 dark:text-gray-400">
            <?= __('whatsapp.no_template') ?>
        </div>
    </div>

    <!-- Tab: History -->
    <div x-show="activeTab === 'history'" x-cloak>
        <div class="flex items-center gap-3 mb-4">
            <select x-model="historyFilter" @change="loadHistory(1)"
                    class="px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#161b22] text-sm text-gray-900 dark:text-white">
                <option value=""><?= __('whatsapp.all_statuses') ?></option>
                <option value="sent"><?= __('whatsapp.sent') ?></option>
                <option value="failed"><?= __('whatsapp.failed') ?></option>
                <option value="pending"><?= __('whatsapp.pending') ?></option>
            </select>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-[#30363d] bg-gray-50 dark:bg-[#21262d]/50">
                            <th class="px-4 py-3 font-medium"><?= __('common.date') ?></th>
                            <th class="px-4 py-3 font-medium"><?= __('common.client') ?></th>
                            <th class="px-4 py-3 font-medium"><?= __('common.phone') ?></th>
                            <th class="px-4 py-3 font-medium">Template</th>
                            <th class="px-4 py-3 font-medium"><?= __('common.status') ?></th>
                            <th class="px-4 py-3 font-medium"><?= __('common.actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="notif in history" :key="notif.id">
                            <tr class="border-b border-gray-100 dark:border-[#30363d]/50 hover:bg-gray-50 dark:hover:bg-[#30363d]/30">
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400" x-text="new Date(notif.created_at).toLocaleString('fr-FR')"></td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900 dark:text-white text-xs" x-text="notif.customer_name || '-'"></p>
                                    <p class="text-xs text-gray-400" x-text="notif.username || ''"></p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400 font-mono" x-text="notif.phone"></td>
                                <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400" x-text="notif.template_name || __('whatsapp.manual')"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                          :class="{
                                              'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': notif.status === 'sent',
                                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': notif.status === 'failed',
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': notif.status === 'pending',
                                              'bg-gray-100 text-gray-800 dark:bg-[#0d1117]/30 dark:text-gray-400': notif.status === 'cancelled'
                                          }"
                                          x-text="notif.status === 'sent' ? __('whatsapp.status_sent') : notif.status === 'failed' ? __('whatsapp.status_failed') : notif.status === 'pending' ? __('whatsapp.status_pending') : __('whatsapp.status_cancelled')">
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <button @click="showMessage = notif.message; showMessageModal = true"
                                            class="text-xs text-green-600 dark:text-green-400 hover:underline" x-text="__('common.view')"></button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="history.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400" x-text="__('whatsapp.no_notification')"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div x-show="historyPagination.pages > 1" class="px-4 py-3 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-gray-400" x-text="historyPagination.total + ' ' + __('whatsapp.results')"></span>
                <div class="flex gap-1">
                    <button @click="loadHistory(historyPagination.page - 1)" :disabled="historyPagination.page <= 1"
                            class="px-3 py-1 text-xs border border-gray-300 dark:border-[#30363d] rounded disabled:opacity-50" x-text="__('whatsapp.prev')"></button>
                    <span class="px-3 py-1 text-xs text-gray-600 dark:text-gray-400" x-text="`${historyPagination.page} / ${historyPagination.pages}`"></span>
                    <button @click="loadHistory(historyPagination.page + 1)" :disabled="historyPagination.page >= historyPagination.pages"
                            class="px-3 py-1 text-xs border border-gray-300 dark:border-[#30363d] rounded disabled:opacity-50" x-text="__('whatsapp.next')"></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Stats -->
    <div x-show="activeTab === 'stats'" x-cloak>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.sent') ?></p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="stats.by_status?.sent || 0"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.failed') ?></p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="stats.by_status?.failed || 0"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.pending') ?></p>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400" x-text="stats.by_status?.pending || 0"></p>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <p class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.success_rate') ?></p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="stats.success_rate + '%'"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <!-- Top templates -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4"><?= __('whatsapp.top_templates') ?></h3>
                <div class="space-y-3">
                    <template x-for="t in (stats.top_templates || [])" :key="t.name">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-700 dark:text-gray-300" x-text="t.name"></span>
                            <span class="text-sm font-semibold text-gray-900 dark:text-white" x-text="t.count + ' ' + __('whatsapp.sends')"></span>
                        </div>
                    </template>
                    <p x-show="!stats.top_templates?.length" class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.no_data') ?></p>
                </div>
            </div>

            <!-- Derniers 7 jours -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none p-5 border border-gray-200/60 dark:border-[#30363d]">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4"><?= __('whatsapp.last_7_days') ?></h3>
                <div class="space-y-2">
                    <template x-for="d in (stats.last_7_days || [])" :key="d.date + d.status">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400" x-text="new Date(d.date).toLocaleDateString('fr-FR', {weekday: 'short', day: 'numeric', month: 'short'})"></span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs px-2 py-0.5 rounded"
                                      :class="d.status === 'sent' ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                      x-text="d.count"></span>
                            </div>
                        </div>
                    </template>
                    <p x-show="!stats.last_7_days?.length" class="text-sm text-gray-500 dark:text-gray-400"><?= __('whatsapp.no_data') ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Configuration -->
    <div x-show="showConfigModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConfigModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl w-full max-w-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('whatsapp.config_title') ?></h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.id_instance') ?></label>
                        <input type="text" x-model="config.id_instance" placeholder="1234567890"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.api_token') ?></label>
                        <input type="text" x-model="config.api_token_instance" placeholder="abcdef1234567890..."
                               class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        <p x-show="config.api_token_masked" class="text-xs text-gray-500 mt-1" x-text="__('whatsapp.current_token') + ': ' + config.api_token_masked"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.api_url') ?></label>
                        <input type="text" x-model="config.api_url" placeholder="https://api.green-api.com"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.country_code') ?></label>
                            <input type="text" x-model="config.country_code" placeholder="229"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.default_phone') ?></label>
                            <input type="text" x-model="config.default_phone" placeholder="99001122"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" x-model="config.is_enabled" id="wa_enabled"
                               class="rounded border-gray-300 dark:border-[#30363d] text-green-600 focus:ring-green-500">
                        <label for="wa_enabled" class="text-sm text-gray-700 dark:text-gray-300"><?= __('whatsapp.enable_notifications') ?></label>
                    </div>
                </div>

                <div class="flex justify-between mt-6">
                    <button @click="testConfig()" :disabled="testing"
                            class="px-4 py-2 border border-green-300 text-green-700 dark:text-green-400 dark:border-green-600 rounded-lg hover:bg-green-50 dark:hover:bg-green-900/20 text-sm flex items-center gap-2">
                        <svg x-show="!testing" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <svg x-show="testing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        <?= __('common.test') ?>
                    </button>
                    <div class="flex gap-2">
                        <button @click="showConfigModal = false" class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm"><?= __('common.cancel') ?></button>
                        <button @click="saveConfig()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm"><?= __('whatsapp.save') ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Template Editor -->
    <div x-show="showTemplateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showTemplateModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4" x-text="editingTemplate ? __('whatsapp.edit_template') : __('whatsapp.new_template')"></h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Formulaire -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.name') ?></label>
                            <input type="text" x-model="templateForm.name" :placeholder="__('whatsapp.template_name_placeholder')"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.description') ?></label>
                            <input type="text" x-model="templateForm.description" :placeholder="__('common.description') + '...'"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.event_type') ?></label>
                                <select x-model="templateForm.event_type"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                    <option value="expiration_warning"><?= __('whatsapp.event_expiration_warning') ?></option>
                                    <option value="expired"><?= __('whatsapp.event_expired') ?></option>
                                    <option value="payment_reminder"><?= __('whatsapp.event_payment_reminder') ?></option>
                                    <option value="invoice_created"><?= __('whatsapp.event_invoice') ?></option>
                                    <option value="payment_received"><?= __('whatsapp.event_payment') ?></option>
                                    <option value="welcome"><?= __('whatsapp.event_welcome') ?></option>
                                    <option value="suspended"><?= __('whatsapp.event_suspended') ?></option>
                                    <option value="reactivated"><?= __('whatsapp.event_reactivated') ?></option>
                                    <option value="custom"><?= __('whatsapp.event_custom') ?></option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.days_before_label') ?></label>
                                <input type="number" x-model="templateForm.days_before"
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.message') ?></label>
                            <textarea x-model="templateForm.message_template" rows="10" x-ref="messageArea"
                                      class="w-full px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm font-mono"
                                      placeholder="Bonjour {{customer_name}}..."></textarea>
                        </div>

                        <!-- Variables disponibles -->
                        <div>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2"><?= __('whatsapp.variables_click_to_insert') ?></p>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="v in variables" :key="v.variable">
                                    <button @click="insertVariable(v.placeholder)"
                                            class="px-2 py-0.5 text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded border border-green-200 dark:border-green-700 hover:bg-green-100 dark:hover:bg-green-900/40"
                                            :title="v.description"
                                            x-text="v.placeholder"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Aperçu live -->
                    <div>
                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('whatsapp.preview_label') ?></p>
                        <div class="bg-[#e5ddd5] dark:bg-[#0d1117] rounded-lg p-4 min-h-[300px]">
                            <div class="bg-white dark:bg-[#21262d] rounded-lg p-3 shadow-sm max-w-[90%]">
                                <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans" x-text="getPreview()"></pre>
                                <p class="text-xs text-gray-400 text-right mt-1" x-text="new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button @click="showTemplateModal = false" class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm"><?= __('common.cancel') ?></button>
                    <button @click="saveTemplate()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                        <span x-text="editingTemplate ? __('whatsapp.update') : __('common.create')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Preview -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPreviewModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('whatsapp.message_preview') ?></h2>
                <div class="bg-[#e5ddd5] dark:bg-[#0d1117] rounded-lg p-4">
                    <div class="bg-white dark:bg-[#21262d] rounded-lg p-3 shadow-sm">
                        <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans" x-text="previewText"></pre>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('whatsapp.send_test_to') ?></label>
                    <div class="flex gap-2">
                        <input type="text" x-model="testPhone" placeholder="99001122"
                               class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                        <button @click="sendTestTemplate()" :disabled="!testPhone || sendingTest"
                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 text-sm">
                            <span x-text="sendingTest ? __('common.sending') : __('whatsapp.send')"></span>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button @click="showPreviewModal = false" class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm"><?= __('common.close') ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Message detail -->
    <div x-show="showMessageModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" x-transition>
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showMessageModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl w-full max-w-md p-6">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('whatsapp.message_sent') ?></h2>
                <div class="bg-[#e5ddd5] dark:bg-[#0d1117] rounded-lg p-4 max-h-96 overflow-y-auto">
                    <div class="bg-white dark:bg-[#21262d] rounded-lg p-3 shadow-sm">
                        <pre class="text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans" x-text="showMessage"></pre>
                    </div>
                </div>
                <div class="flex justify-end mt-4">
                    <button @click="showMessageModal = false" class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 text-sm"><?= __('common.close') ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function whatsappPage() {
    return {
        activeTab: 'templates',
        processing: false,
        testing: false,
        sendingTest: false,

        // Data
        config: {},
        templates: [],
        variables: [],
        history: [],
        historyPagination: { page: 1, pages: 1, total: 0 },
        historyFilter: '',
        stats: {},

        // Modals
        showConfigModal: false,
        showTemplateModal: false,
        showPreviewModal: false,
        showMessageModal: false,
        editingTemplate: null,
        previewText: '',
        previewTemplateId: null,
        testPhone: '',
        showMessage: '',

        // Template form
        templateForm: {
            name: '', description: '', event_type: 'custom',
            message_template: '', days_before: 0, is_active: true, send_time: '09:00:00'
        },

        // Demo data for preview
        demoData: {
            customer_name: 'Jean Dupont',
            customer_phone: '99 00 00 00',
            customer_email: 'jean@example.com',
            customer_address: '123 Rue Exemple',
            username: 'DEMO12345',
            password: 'demo123',
            profile_name: 'Gold 10Mbps',
            profile_price: '15 000',
            download_speed: '10 Mbps',
            upload_speed: '5 Mbps',
            expiration_date: new Date(Date.now() + 7*86400000).toLocaleDateString('fr-FR'),
            days_remaining: '7',
            days_expired: '0',
            current_date: new Date().toLocaleDateString('fr-FR'),
            current_time: new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}),
            zone_name: 'Zone Centre',
            nas_name: 'NAS-01',
            data_used: '5.2 Go',
            data_limit: 'Illimité',
            balance: '0',
            support_phone: '99 00 00 00',
            company_name: 'Mon ISP',
        },

        async init() {
            await Promise.all([
                this.loadConfig(),
                this.loadTemplates(),
                this.loadVariables(),
                this.loadHistory(),
                this.loadStats(),
            ]);
        },

        async loadConfig() {
            try {
                const r = await API.get('/whatsapp/config');
                this.config = r.data || {};
            } catch (e) { console.error('Config error:', e); }
        },

        async loadTemplates() {
            try {
                const r = await API.get('/whatsapp/templates');
                this.templates = r.data || [];
            } catch (e) { console.error('Templates error:', e); }
        },

        async loadVariables() {
            try {
                const r = await API.get('/whatsapp/variables');
                this.variables = r.data || [];
            } catch (e) { console.error('Variables error:', e); }
        },

        async loadHistory(page = 1) {
            try {
                let url = `/whatsapp/history?page=${page}&limit=20`;
                if (this.historyFilter) url += `&status=${this.historyFilter}`;
                const r = await API.get(url);
                this.history = r.data?.data || [];
                this.historyPagination = r.data?.pagination || { page: 1, pages: 1, total: 0 };
            } catch (e) { console.error('History error:', e); }
        },

        async loadStats() {
            try {
                const r = await API.get('/whatsapp/stats');
                this.stats = r.data || {};
            } catch (e) { console.error('Stats error:', e); }
        },

        async saveConfig() {
            try {
                await API.post('/whatsapp/config', this.config);
                this.showConfigModal = false;
                showToast(__('whatsapp.config_saved'), 'success');
                this.loadConfig();
            } catch (e) {
                showToast(__('common.error') + ': ' + (e.message || __('status.failed')), 'error');
            }
        },

        async testConfig() {
            this.testing = true;
            try {
                // Sauvegarder d'abord
                await API.post('/whatsapp/config', this.config);
                const r = await API.post('/whatsapp/test', { phone: this.config.default_phone || null });
                showToast(__('whatsapp.connection_success') + ' ' + (r.data?.message_sent ? __('whatsapp.test_message_sent') : ''), 'success');
            } catch (e) {
                showToast(__('status.failed') + ': ' + (e.message || __('common.error')), 'error');
            }
            this.testing = false;
        },

        openTemplateModal() {
            this.editingTemplate = null;
            this.templateForm = {
                name: '', description: '', event_type: 'custom',
                message_template: '', days_before: 0, is_active: true, send_time: '09:00:00'
            };
            this.showTemplateModal = true;
        },

        editTemplate(tpl) {
            this.editingTemplate = tpl.id;
            this.templateForm = { ...tpl };
            this.showTemplateModal = true;
        },

        async saveTemplate() {
            try {
                if (this.editingTemplate) {
                    await API.put(`/whatsapp/templates/${this.editingTemplate}`, this.templateForm);
                    showToast(__('whatsapp.template_updated'), 'success');
                } else {
                    await API.post('/whatsapp/templates', this.templateForm);
                    showToast(__('whatsapp.template_created'), 'success');
                }
                this.showTemplateModal = false;
                this.loadTemplates();
            } catch (e) {
                showToast(__('common.error') + ': ' + (e.message || __('status.failed')), 'error');
            }
        },

        async deleteTemplate(id) {
            if (!confirm(__('whatsapp.confirm_delete_template'))) return;
            try {
                await API.delete(`/whatsapp/templates/${id}`);
                showToast(__('whatsapp.template_deleted'), 'success');
                this.loadTemplates();
            } catch (e) {
                showToast(__('common.error') + ': ' + (e.message || __('status.failed')), 'error');
            }
        },

        async toggleTemplate(id) {
            try {
                await API.post(`/whatsapp/templates/${id}/toggle`);
            } catch (e) {
                showToast(__('common.error') + ': ' + (e.message || __('status.failed')), 'error');
                this.loadTemplates();
            }
        },

        async previewTemplate(id) {
            try {
                const r = await API.post(`/whatsapp/templates/${id}/preview`, {});
                this.previewText = r.data?.preview || '';
                this.previewTemplateId = id;
                this.showPreviewModal = true;
            } catch (e) {
                showToast(__('whatsapp.preview_error'), 'error');
            }
        },

        async sendTestTemplate() {
            if (!this.testPhone || !this.previewTemplateId) return;
            this.sendingTest = true;
            try {
                await API.post('/whatsapp/test-template', {
                    template_id: this.previewTemplateId,
                    phone: this.testPhone,
                    use_demo_data: true
                });
                showToast(__('whatsapp.test_message_sent'), 'success');
            } catch (e) {
                showToast(__('status.failed') + ': ' + (e.message || __('common.error')), 'error');
            }
            this.sendingTest = false;
        },

        async processExpirations() {
            this.processing = true;
            try {
                const r = await API.post('/whatsapp/process-expirations');
                const d = r.data || {};
                showToast(__('whatsapp.process_result', {sent: d.sent || 0, failed: d.failed || 0, skipped: d.skipped || 0}), 'success');
                this.loadHistory();
                this.loadStats();
            } catch (e) {
                showToast(__('common.error') + ': ' + (e.message || __('status.failed')), 'error');
            }
            this.processing = false;
        },

        insertVariable(placeholder) {
            const area = this.$refs.messageArea;
            if (!area) return;
            const start = area.selectionStart;
            const end = area.selectionEnd;
            const text = this.templateForm.message_template;
            this.templateForm.message_template = text.substring(0, start) + placeholder + text.substring(end);
            this.$nextTick(() => {
                area.focus();
                area.setSelectionRange(start + placeholder.length, start + placeholder.length);
            });
        },

        getPreview() {
            let msg = this.templateForm.message_template || '';
            for (const [key, value] of Object.entries(this.demoData)) {
                msg = msg.replaceAll('{{' + key + '}}', value);
            }
            return msg;
        },

        getEventLabel(type) {
            const labels = {
                expiration_warning: __('whatsapp.event_expiration_warning'),
                expired: __('whatsapp.event_expired'),
                payment_reminder: __('whatsapp.event_payment_reminder'),
                invoice_created: __('whatsapp.event_invoice'),
                payment_received: __('whatsapp.event_payment'),
                welcome: __('whatsapp.event_welcome'),
                suspended: __('whatsapp.event_suspended'),
                reactivated: __('whatsapp.event_reactivated'),
                custom: __('whatsapp.event_custom')
            };
            return labels[type] || type;
        },

        getEventColor(type) {
            const colors = {
                expiration_warning: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                expired: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                payment_reminder: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
                invoice_created: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                payment_received: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                welcome: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
                suspended: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                reactivated: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                custom: 'bg-gray-100 text-gray-800 dark:bg-[#0d1117]/30 dark:text-gray-400',
            };
            return colors[type] || colors.custom;
        }
    }
}
</script>
