<?php $pageTitle = __('telegram.title'); $currentPage = 'telegram'; ?>

<div x-data="telegramPage()" x-init="init()">
    <!-- En-tête -->
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('telegram.title') ?></h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('telegram.subtitle') ?></p>
        </div>
        <div class="flex gap-3">
            <button @click="showConfigModal = true"
                    class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#30363d] flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <?= __('telegram.configuration') ?>
            </button>
            <button @click="processExpirations()"
                    :disabled="processing"
                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 flex items-center gap-2">
                <svg x-show="!processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <svg x-show="processing" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="processing ? '<?= __('telegram.sending') ?>' : '<?= __('telegram.process_expirations') ?>'"></span>
            </button>
        </div>
    </div>

    <!-- Onglets -->
    <div class="mb-6 border-b border-gray-200 dark:border-[#30363d]">
        <nav class="-mb-px flex gap-6">
            <button @click="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm">
                <?= __('telegram.tab_templates') ?>
            </button>
            <button @click="activeTab = 'recipients'"
                    :class="activeTab === 'recipients' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm">
                <?= __('telegram.tab_recipients') ?>
            </button>
            <button @click="activeTab = 'history'"
                    :class="activeTab === 'history' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm">
                <?= __('telegram.tab_history') ?>
            </button>
            <button @click="activeTab = 'stats'"
                    :class="activeTab === 'stats' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="py-3 px-1 border-b-2 font-medium text-sm">
                <?= __('telegram.tab_stats') ?>
            </button>
        </nav>
    </div>

    <!-- Tab: Templates -->
    <div x-show="activeTab === 'templates'" x-cloak>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('telegram.notification_templates_label') ?></h2>
            <button @click="openTemplateModal()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?= __('telegram.new_template') ?>
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="template in templates" :key="template.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
                    <div class="p-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div :class="getEventTypeColor(template.event_type)" class="w-10 h-10 rounded-lg flex items-center justify-center">
                                <span x-text="getEventTypeIcon(template.event_type)" class="text-lg"></span>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white" x-text="template.name"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    <span x-text="getEventTypeLabel(template.event_type)"></span>
                                    <span x-show="template.days_before > 0"> - J-<span x-text="template.days_before"></span></span>
                                    <span x-show="template.days_before === 0 || template.days_before === '0'"> - <?= __('telegram.day_j') ?></span>
                                    <span x-show="template.days_before < 0"> - J+<span x-text="Math.abs(template.days_before)"></span></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Badge WhatsApp -->
                            <span x-show="template.whatsapp_button == 1"
                                  class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 flex items-center gap-1">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                </svg>
                                WA
                            </span>
                            <button @click="toggleTemplate(template)"
                                    :class="template.is_active == 1 ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-500 dark:bg-[#21262d] dark:text-gray-400'"
                                    class="px-2 py-1 rounded text-xs font-medium">
                                <span x-text="template.is_active == 1 ? '<?= __('common.active') ?>' : '<?= __('common.inactive') ?>'"></span>
                            </button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3 whitespace-pre-line" x-text="template.message_template.substring(0, 150) + '...'"></div>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                <?= __('telegram.hour') ?>: <span x-text="template.send_time?.substring(0,5) || '09:00'"></span>
                            </span>
                            <div class="flex gap-2">
                                <button @click="openTestModal(template)"
                                        class="text-green-600 dark:text-green-400 hover:underline text-sm flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                    </svg>
                                    <?= __('common.test') ?>
                                </button>
                                <button @click="previewTemplate(template.id)"
                                        class="text-primary-600 dark:text-primary-400 hover:underline text-sm"><?= __('common.preview') ?></button>
                                <button @click="openTemplateModal(template)"
                                        class="text-primary-600 dark:text-primary-400 hover:underline text-sm"><?= __('common.edit') ?></button>
                                <button @click="deleteTemplate(template.id)"
                                        class="text-red-600 dark:text-red-400 hover:underline text-sm"><?= __('common.delete') ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div x-show="templates.length === 0 && !loading" class="text-center py-12 bg-white dark:bg-[#161b22] rounded-xl">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white"><?= __('telegram.no_template') ?></h3>
            <p class="mt-1 text-sm text-gray-500"><?= __('telegram.create_first_template') ?></p>
        </div>
    </div>

    <!-- Tab: Recipients -->
    <div x-show="activeTab === 'recipients'" x-cloak>
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('telegram.alert_recipients') ?></h2>
            <button @click="openRecipientModal()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?= __('common.add') ?>
            </button>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-[#30363d]">
                <thead class="bg-gray-50 dark:bg-[#0d1117]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.name') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Chat ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.role') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('telegram.alert_types') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.status') ?></th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.actions') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="recipient in recipients" :key="recipient.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white" x-text="recipient.name"></div>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm text-gray-600 dark:text-gray-400" x-text="recipient.chat_id"></td>
                            <td class="px-6 py-4">
                                <span :class="getRoleClass(recipient.role)" class="px-2 py-1 rounded-full text-xs font-medium" x-text="getRoleLabel(recipient.role)"></span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-1">
                                    <span x-show="recipient.receive_expiration_alerts == 1" class="px-1.5 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded text-xs"><?= __('telegram.expiration_alert') ?></span>
                                    <span x-show="recipient.receive_payment_alerts == 1" class="px-1.5 py-0.5 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 rounded text-xs"><?= __('telegram.payment_alert') ?></span>
                                    <span x-show="recipient.receive_system_alerts == 1" class="px-1.5 py-0.5 bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400 rounded text-xs"><?= __('telegram.system_alert') ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="recipient.is_active == 1 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500 dark:bg-[#21262d] dark:text-gray-400'"
                                      class="px-2 py-1 rounded-full text-xs font-medium"
                                      x-text="recipient.is_active == 1 ? '<?= __('common.active') ?>' : '<?= __('common.inactive') ?>'"></span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button @click="openRecipientModal(recipient)" class="text-primary-600 dark:text-primary-400 hover:underline text-sm mr-2"><?= __('common.edit') ?></button>
                                <button @click="deleteRecipient(recipient.id)" class="text-red-600 dark:text-red-400 hover:underline text-sm"><?= __('common.delete') ?></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div x-show="recipients.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <?= __('telegram.no_recipient') ?>
            </div>
        </div>
    </div>

    <!-- Tab: History -->
    <div x-show="activeTab === 'history'" x-cloak>
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] overflow-hidden">
            <div class="p-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('telegram.notification_history') ?></h2>
                <select x-model="historyFilter" @change="loadHistory()"
                        class="px-3 py-1.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-sm">
                    <option value=""><?= __('common.all_statuses') ?></option>
                    <option value="sent"><?= __('telegram.sent') ?></option>
                    <option value="failed"><?= __('telegram.failures') ?></option>
                    <option value="pending"><?= __('status.pending') ?></option>
                </select>
            </div>
            <table class="min-w-full divide-y divide-gray-200 dark:divide-[#30363d]">
                <thead class="bg-gray-50 dark:bg-[#0d1117]">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.date') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.client') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Template</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('common.status') ?></th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('telegram.message') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                    <template x-for="notif in history" :key="notif.id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#30363d]/50">
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400" x-text="formatDate(notif.created_at)"></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 dark:text-white" x-text="notif.customer_name || '-'"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="notif.username || notif.chat_id"></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400" x-text="notif.template_name || __('telegram.custom_label')"></td>
                            <td class="px-6 py-4">
                                <span :class="getStatusClass(notif.status)" class="px-2 py-1 rounded-full text-xs font-medium" x-text="getStatusLabel(notif.status)"></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate" x-text="notif.message?.substring(0, 50) + '...'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div x-show="history.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                <?= __('telegram.no_notification_sent') ?>
            </div>
            <!-- Pagination -->
            <div x-show="historyPagination.pages > 1" class="px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <?= __('common.page') ?> <span x-text="historyPagination.page"></span> / <span x-text="historyPagination.pages"></span>
                </span>
                <div class="flex gap-2">
                    <button @click="loadHistory(historyPagination.page - 1)" :disabled="historyPagination.page <= 1"
                            class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50"><?= __('common.previous') ?></button>
                    <button @click="loadHistory(historyPagination.page + 1)" :disabled="historyPagination.page >= historyPagination.pages"
                            class="px-3 py-1 border border-gray-300 dark:border-[#30363d] rounded text-sm disabled:opacity-50"><?= __('common.next') ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Stats -->
    <div x-show="activeTab === 'stats'" x-cloak>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.by_status?.sent || 0"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400"><?= __('telegram.sent') ?></div>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="text-3xl font-bold text-red-600" x-text="stats.by_status?.failed || 0"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400"><?= __('telegram.failures') ?></div>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="text-3xl font-bold text-yellow-600" x-text="stats.by_status?.pending || 0"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400"><?= __('status.pending') ?></div>
            </div>
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="text-3xl font-bold text-green-600" x-text="stats.success_rate + '%'"></div>
                <div class="text-sm text-gray-600 dark:text-gray-400"><?= __('telegram.success_rate') ?></div>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4"><?= __('telegram.most_used_templates') ?></h3>
            <div class="space-y-3">
                <template x-for="t in stats.top_templates || []" :key="t.name">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 dark:text-gray-400" x-text="t.name"></span>
                        <span class="font-medium text-gray-900 dark:text-white" x-text="t.count + ' <?= __('telegram.sends') ?>'"></span>
                    </div>
                </template>
                <div x-show="!stats.top_templates?.length" class="text-gray-500 dark:text-gray-400"><?= __('telegram.no_data_available') ?></div>
            </div>
        </div>
    </div>

    <!-- Modal Configuration -->
    <div x-show="showConfigModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showConfigModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('telegram.config_title') ?></h3>
                <form @submit.prevent="saveConfig()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.bot_token') ?></label>
                            <input type="text" x-model="config.bot_token"
                                   :placeholder="config.bot_token_masked || '<?= __('telegram.bot_token_placeholder') ?>'"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono text-sm">
                            <p class="mt-1 text-xs text-gray-500"><?= __('telegram.bot_token_hint') ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.default_chat_id') ?></label>
                            <input type="text" x-model="config.default_chat_id"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                            <p class="mt-1 text-xs text-gray-500"><?= __('telegram.default_chat_id_hint') ?></p>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" x-model="config.is_enabled" id="tg-enabled"
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <label for="tg-enabled" class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.enable_notifications') ?></label>
                        </div>
                        <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2"><?= __('telegram.test_connection') ?></h4>
                            <div class="flex gap-2">
                                <input type="text" x-model="testChatId" placeholder="<?= __('telegram.chat_id_test_placeholder') ?>"
                                       class="flex-1 px-3 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-sm">
                                <button type="button" @click="testConnection()" :disabled="testingConnection"
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 text-sm">
                                    <span x-text="testingConnection ? __('telegram.testing') : __('common.test')"></span>
                                </button>
                            </div>
                            <div x-show="testResult" class="mt-2 text-sm" :class="testResult.success ? 'text-green-600' : 'text-red-600'" x-text="testResult.message"></div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showConfigModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300">
                            <?= __('common.cancel') ?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <?= __('common.save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Template -->
    <div x-show="showTemplateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showTemplateModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="templateForm.id ? __('telegram.edit_template') : __('telegram.new_template')"></h3>
                </div>
                <form @submit.prevent="saveTemplate()" class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.name') ?></label>
                            <input type="text" x-model="templateForm.name" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.event_type') ?></label>
                            <select x-model="templateForm.event_type" required
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="expiration_warning"><?= __('telegram.event_expiration_warning') ?></option>
                                <option value="expired"><?= __('telegram.event_expired') ?></option>
                                <option value="payment_reminder"><?= __('telegram.event_payment_reminder') ?></option>
                                <option value="welcome"><?= __('telegram.event_welcome') ?></option>
                                <option value="suspended"><?= __('telegram.event_suspended') ?></option>
                                <option value="reactivated"><?= __('telegram.event_reactivated') ?></option>
                                <option value="custom"><?= __('telegram.event_custom') ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.days_before_expiration') ?></label>
                            <input type="number" x-model="templateForm.days_before"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <p class="mt-1 text-xs text-gray-500"><?= __('telegram.days_before_hint') ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.send_time') ?></label>
                            <input type="time" x-model="templateForm.send_time"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.description') ?></label>
                            <input type="text" x-model="templateForm.description"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <!-- Zone de message et variables avec drag & drop -->
                        <div class="col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4">
                            <!-- Colonne gauche: Message -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.message') ?></label>
                                <textarea x-model="templateForm.message_template" rows="12" required
                                          x-ref="messageTextarea"
                                          @dragover.prevent="$event.target.classList.add('ring-2', 'ring-primary-500')"
                                          @dragleave.prevent="$event.target.classList.remove('ring-2', 'ring-primary-500')"
                                          @drop.prevent="handleVariableDrop($event)"
                                          class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono text-sm transition-all"></textarea>
                                <p class="mt-1 text-xs text-gray-500"><?= __('telegram.drag_variables_hint') ?></p>
                            </div>

                            <!-- Colonne droite: Apercu en temps reel -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    <?= __('telegram.live_preview') ?>
                                    <span class="text-xs font-normal text-gray-500 ml-2"><?= __('telegram.with_demo_data') ?></span>
                                </label>
                                <div class="h-[288px] overflow-y-auto px-4 py-3 border border-gray-300 dark:border-[#30363d] rounded-lg bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800">
                                    <div class="bg-white dark:bg-[#0d1117] rounded-lg p-3 shadow-sm">
                                        <div class="flex items-center gap-2 mb-2 pb-2 border-b border-gray-200 dark:border-[#30363d]">
                                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                                </svg>
                                            </div>
                                            <span class="font-medium text-gray-900 dark:text-white text-sm">Bot Notification</span>
                                        </div>
                                        <div class="whitespace-pre-line text-gray-800 dark:text-gray-200 text-sm leading-relaxed" x-html="getLivePreview()"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Variables disponibles avec drag & drop -->
                        <div class="col-span-2 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-gray-700/50 dark:to-gray-800/50 rounded-lg p-4 border border-blue-200 dark:border-[#30363d]">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                    </svg>
                                    <?= __('telegram.available_variables') ?>
                                </h4>
                                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('telegram.drag_or_click') ?></span>
                            </div>

                            <!-- Groupes de variables -->
                            <div class="space-y-3">
                                <!-- Client -->
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('telegram.var_client') ?></div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="v in variables.filter(v => ['customer_name', 'customer_phone', 'customer_email', 'customer_address'].includes(v.variable))" :key="v.variable">
                                            <div draggable="true"
                                                 @dragstart="handleVariableDragStart($event, v.variable)"
                                                 @click="insertVariable(v.variable)"
                                                 class="group cursor-grab active:cursor-grabbing px-3 py-1.5 bg-white dark:bg-[#161b22] border border-blue-300 dark:border-blue-700 text-blue-700 dark:text-blue-400 rounded-lg text-xs font-mono shadow-sm hover:shadow-md hover:border-blue-500 transition-all flex items-center gap-2">
                                                <svg class="w-3 h-3 text-gray-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                </svg>
                                                <span x-text="v.placeholder"></span>
                                                <span class="hidden group-hover:inline text-gray-400 text-xs" x-text="'- ' + v.description"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Compte -->
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('telegram.var_pppoe_account') ?></div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="v in variables.filter(v => ['username', 'password', 'profile_name', 'profile_price', 'download_speed', 'upload_speed'].includes(v.variable))" :key="v.variable">
                                            <div draggable="true"
                                                 @dragstart="handleVariableDragStart($event, v.variable)"
                                                 @click="insertVariable(v.variable)"
                                                 class="group cursor-grab active:cursor-grabbing px-3 py-1.5 bg-white dark:bg-[#161b22] border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 rounded-lg text-xs font-mono shadow-sm hover:shadow-md hover:border-green-500 transition-all flex items-center gap-2">
                                                <svg class="w-3 h-3 text-gray-400 group-hover:text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                </svg>
                                                <span x-text="v.placeholder"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Dates -->
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('telegram.var_dates') ?></div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="v in variables.filter(v => ['expiration_date', 'days_remaining', 'days_expired', 'current_date', 'current_time'].includes(v.variable))" :key="v.variable">
                                            <div draggable="true"
                                                 @dragstart="handleVariableDragStart($event, v.variable)"
                                                 @click="insertVariable(v.variable)"
                                                 class="group cursor-grab active:cursor-grabbing px-3 py-1.5 bg-white dark:bg-[#161b22] border border-yellow-300 dark:border-yellow-700 text-yellow-700 dark:text-yellow-400 rounded-lg text-xs font-mono shadow-sm hover:shadow-md hover:border-yellow-500 transition-all flex items-center gap-2">
                                                <svg class="w-3 h-3 text-gray-400 group-hover:text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                </svg>
                                                <span x-text="v.placeholder"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Reseau & Autres -->
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"><?= __('telegram.var_network') ?></div>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="v in variables.filter(v => ['zone_name', 'nas_name', 'data_used', 'data_limit', 'balance', 'support_phone', 'company_name'].includes(v.variable))" :key="v.variable">
                                            <div draggable="true"
                                                 @dragstart="handleVariableDragStart($event, v.variable)"
                                                 @click="insertVariable(v.variable)"
                                                 class="group cursor-grab active:cursor-grabbing px-3 py-1.5 bg-white dark:bg-[#161b22] border border-purple-300 dark:border-purple-700 text-purple-700 dark:text-purple-400 rounded-lg text-xs font-mono shadow-sm hover:shadow-md hover:border-purple-500 transition-all flex items-center gap-2">
                                                <svg class="w-3 h-3 text-gray-400 group-hover:text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                                                </svg>
                                                <span x-text="v.placeholder"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Options du template -->
                        <div class="col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-[#21262d]/30 rounded-lg border border-gray-200 dark:border-[#30363d]">
                            <!-- Template actif -->
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="templateForm.is_active" id="template-active"
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <label for="template-active" class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.template_active') ?></label>
                            </div>

                            <!-- Bouton WhatsApp -->
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="templateForm.whatsapp_button" id="whatsapp-button"
                                       class="w-4 h-4 text-green-600 border-gray-300 rounded">
                                <label for="whatsapp-button" class="text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <?= __('telegram.add_whatsapp_button') ?>
                                </label>
                            </div>

                            <!-- Texte du bouton WhatsApp (conditionnel) -->
                            <div x-show="templateForm.whatsapp_button" x-collapse class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.whatsapp_button_text') ?></label>
                                <input type="text" x-model="templateForm.whatsapp_button_text"
                                       placeholder="<?= __('telegram.whatsapp_button_default') ?>"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white text-sm">
                                <p class="mt-1 text-xs text-gray-500"><?= __('telegram.whatsapp_button_hint') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showTemplateModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300">
                            <?= __('common.cancel') ?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <?= __('common.save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Recipient -->
    <div x-show="showRecipientModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showRecipientModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="recipientForm.id ? __('telegram.edit_recipient') : __('telegram.add_recipient')"></h3>
                <form @submit.prevent="saveRecipient()">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.name') ?></label>
                            <input type="text" x-model="recipientForm.name" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.chat_id_telegram') ?></label>
                            <input type="text" x-model="recipientForm.chat_id" required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.role') ?></label>
                            <select x-model="recipientForm.role"
                                    class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                <option value="admin"><?= __('telegram.role_admin') ?></option>
                                <option value="manager"><?= __('telegram.role_manager') ?></option>
                                <option value="accountant"><?= __('telegram.role_accountant') ?></option>
                                <option value="technician"><?= __('telegram.role_technician') ?></option>
                                <option value="custom"><?= __('telegram.role_custom') ?></option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"><?= __('telegram.alert_types') ?></label>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="recipientForm.receive_expiration_alerts" id="r-expiration"
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <label for="r-expiration" class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.expirations') ?></label>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="recipientForm.receive_payment_alerts" id="r-payment"
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <label for="r-payment" class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.payments') ?></label>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" x-model="recipientForm.receive_system_alerts" id="r-system"
                                       class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <label for="r-system" class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.system_alerts') ?></label>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="checkbox" x-model="recipientForm.is_active" id="r-active"
                                   class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <label for="r-active" class="text-sm text-gray-700 dark:text-gray-300"><?= __('common.active') ?></label>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="showRecipientModal = false"
                                class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300">
                            <?= __('common.cancel') ?>
                        </button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <?= __('common.save') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Preview -->
    <div x-show="showPreviewModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPreviewModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('telegram.message_preview') ?></h3>
                <div class="bg-gray-100 dark:bg-[#21262d] rounded-lg p-4">
                    <div class="whitespace-pre-line text-gray-900 dark:text-white text-sm" x-html="previewContent"></div>
                </div>
                <div class="mt-4 flex justify-end">
                    <button @click="showPreviewModal = false"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        <?= __('common.close') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Test Template -->
    <div x-show="showTestModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showTestModal = false"></div>
            <div class="relative bg-white dark:bg-[#161b22] rounded-xl shadow-xl max-w-lg w-full p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('telegram.test_template') ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-text="testTemplate?.name"></p>
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Mode de test -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('telegram.test_mode') ?></label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="testMode" value="demo" class="text-primary-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.demo_data') ?></span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="testMode" value="user" class="text-primary-600">
                                <span class="text-sm text-gray-700 dark:text-gray-300"><?= __('telegram.real_client') ?></span>
                            </label>
                        </div>
                    </div>

                    <!-- Selection client (si mode user) -->
                    <div x-show="testMode === 'user'" x-collapse>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.select_pppoe_client') ?></label>
                        <select x-model="testUserId" @change="loadTestUserPreview()"
                                class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                            <option value=""><?= __('telegram.choose_client') ?></option>
                            <template x-for="user in pppoeUsers" :key="user.id">
                                <option :value="user.id" x-text="user.customer_name + ' (' + user.username + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Chat ID destination -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('telegram.destination_chat_id') ?>
                            <span class="text-xs font-normal text-gray-500"><?= __('telegram.destination_chat_id_hint') ?></span>
                        </label>
                        <input type="text" x-model="testChatIdForTemplate"
                               :placeholder="config.default_chat_id || __('telegram.chat_id_test_placeholder')"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white font-mono">
                        <p class="mt-1 text-xs text-gray-500"><?= __('telegram.leave_empty_default') ?></p>
                    </div>

                    <!-- Apercu du message -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('telegram.message_preview') ?></label>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-gray-700 dark:to-gray-800 rounded-lg p-3 border border-gray-200 dark:border-[#30363d]">
                            <div class="bg-white dark:bg-[#0d1117] rounded-lg p-3 shadow-sm">
                                <div class="whitespace-pre-line text-gray-800 dark:text-gray-200 text-sm" x-html="testPreviewContent"></div>
                            </div>
                            <!-- Apercu bouton WhatsApp -->
                            <div x-show="testTemplate?.whatsapp_button == 1" class="mt-2">
                                <div class="bg-green-500 text-white text-center py-2 px-4 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    <span x-text="testTemplate?.whatsapp_button_text || '<?= __('telegram.whatsapp_button_default') ?>'"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resultat du test -->
                    <div x-show="testResult" class="p-3 rounded-lg" :class="testResult?.success ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800'">
                        <div class="flex items-center gap-2">
                            <svg x-show="testResult?.success" class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg x-show="!testResult?.success" class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            <span class="text-sm font-medium" :class="testResult?.success ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'" x-text="testResult?.message"></span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" @click="showTestModal = false"
                            class="px-4 py-2 border border-gray-300 dark:border-[#30363d] rounded-lg text-gray-700 dark:text-gray-300">
                        <?= __('common.close') ?>
                    </button>
                    <button @click="sendTestMessage()" :disabled="sendingTest"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 flex items-center gap-2">
                        <svg x-show="!sendingTest" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <svg x-show="sendingTest" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="sendingTest ? __('common.sending') : __('telegram.send_test')"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading overlay -->
    <div x-show="loading" class="fixed inset-0 z-40 bg-black/20 flex items-center justify-center">
        <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 shadow-xl">
            <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>
    </div>
</div>

<script>
function telegramPage() {
    return {
        activeTab: 'templates',
        loading: false,
        processing: false,

        // Config
        config: {},
        showConfigModal: false,
        testChatId: '',
        testingConnection: false,
        testResult: null,

        // Templates
        templates: [],
        showTemplateModal: false,
        templateForm: {},
        variables: [],

        // Recipients
        recipients: [],
        showRecipientModal: false,
        recipientForm: {},

        // History
        history: [],
        historyFilter: '',
        historyPagination: { page: 1, pages: 1, total: 0 },

        // Stats
        stats: {},

        // Preview
        showPreviewModal: false,
        previewContent: '',

        // Test Template
        showTestModal: false,
        testTemplate: null,
        testMode: 'demo',
        testUserId: '',
        testChatIdForTemplate: '',
        testPreviewContent: '',
        testResult: null,
        sendingTest: false,
        pppoeUsers: [],

        async init() {
            await Promise.all([
                this.loadConfig(),
                this.loadTemplates(),
                this.loadVariables(),
                this.loadRecipients()
            ]);
        },

        async loadConfig() {
            try {
                const response = await fetch('api.php?route=/telegram/config');
                const data = await response.json();
                if (data.success) {
                    this.config = data.data || {};
                }
            } catch (error) {
                console.error('Error loading config:', error);
            }
        },

        async saveConfig() {
            try {
                const response = await fetch('api.php?route=/telegram/config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.config)
                });
                const data = await response.json();
                if (data.success) {
                    showToast(__('telegram.config_saved'));
                    this.showConfigModal = false;
                    await this.loadConfig();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.save_error'), 'error');
            }
        },

        async testConnection() {
            this.testingConnection = true;
            this.testResult = null;
            try {
                const response = await fetch('api.php?route=/telegram/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ chat_id: this.testChatId || null })
                });
                const data = await response.json();
                if (data.success) {
                    this.testResult = { success: true, message: __('telegram.connection_success') + ' Bot: @' + data.data.bot_info.username };
                } else {
                    this.testResult = { success: false, message: data.message || __('telegram.connection_failed') };
                }
            } catch (error) {
                this.testResult = { success: false, message: __('telegram.network_error') };
            } finally {
                this.testingConnection = false;
            }
        },

        async loadTemplates() {
            try {
                const response = await fetch('api.php?route=/telegram/templates');
                const data = await response.json();
                if (data.success) {
                    this.templates = data.data || [];
                }
            } catch (error) {
                console.error('Error loading templates:', error);
            }
        },

        async loadVariables() {
            try {
                const response = await fetch('api.php?route=/telegram/variables');
                const data = await response.json();
                if (data.success) {
                    this.variables = data.data || [];
                }
            } catch (error) {
                console.error('Error loading variables:', error);
            }
        },

        openTemplateModal(template = null) {
            if (template) {
                this.templateForm = {
                    ...template,
                    whatsapp_button: template.whatsapp_button == 1,
                    is_active: template.is_active == 1
                };
            } else {
                this.templateForm = {
                    name: '',
                    description: '',
                    event_type: 'expiration_warning',
                    message_template: '',
                    days_before: 7,
                    is_active: true,
                    send_time: '09:00',
                    whatsapp_button: false,
                    whatsapp_button_text: '📱 Envoyer sur WhatsApp'
                };
            }
            this.showTemplateModal = true;
        },

        // Donnees de demo pour l'apercu
        demoData: {
            customer_name: 'Jean Dupont',
            customer_phone: '99 00 11 22',
            customer_email: 'jean.dupont@email.com',
            customer_address: '123 Rue du Commerce, Cotonou',
            username: 'PPPOE-12345',
            password: 'secret123',
            profile_name: 'Fibre Gold 20Mbps',
            profile_price: '25 000',
            download_speed: '20 Mbps',
            upload_speed: '10 Mbps',
            expiration_date: new Date(Date.now() + 7*24*60*60*1000).toLocaleDateString('fr-FR'),
            days_remaining: '7',
            days_expired: '0',
            current_date: new Date().toLocaleDateString('fr-FR'),
            current_time: new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}),
            zone_name: 'Zone Centre',
            nas_name: 'NAS-Principal',
            data_used: '45.2 Go',
            data_limit: '100 Go',
            balance: '5 000',
            support_phone: '97 00 00 00',
            company_name: 'MonISP Telecom'
        },

        // Inserer une variable dans le textarea
        insertVariable(variable) {
            const textarea = this.$refs.messageTextarea;
            if (textarea) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = this.templateForm.message_template || '';
                const before = text.substring(0, start);
                const after = text.substring(end);
                const insertion = '{{' + variable + '}}';
                this.templateForm.message_template = before + insertion + after;

                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + insertion.length;
                });
            } else {
                this.templateForm.message_template = (this.templateForm.message_template || '') + '{{' + variable + '}}';
            }
        },

        // Gestion du drag & drop
        handleVariableDragStart(event, variable) {
            event.dataTransfer.setData('text/plain', '{{' + variable + '}}');
            event.dataTransfer.effectAllowed = 'copy';
        },

        handleVariableDrop(event) {
            event.target.classList.remove('ring-2', 'ring-primary-500');
            const variable = event.dataTransfer.getData('text/plain');
            if (variable) {
                const textarea = event.target;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = this.templateForm.message_template || '';
                const before = text.substring(0, start);
                const after = text.substring(end);
                this.templateForm.message_template = before + variable + after;

                this.$nextTick(() => {
                    textarea.focus();
                    textarea.selectionStart = textarea.selectionEnd = start + variable.length;
                });
            }
        },

        // Apercu en temps reel avec les donnees de demo
        getLivePreview() {
            let message = this.templateForm.message_template || '';
            if (!message) return '<span class="text-gray-400 italic">' + __('telegram.start_typing') + '</span>';

            for (const [key, value] of Object.entries(this.demoData)) {
                const regex = new RegExp('\\{\\{' + key + '\\}\\}', 'g');
                message = message.replace(regex, '<span class="bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 px-1 rounded">' + value + '</span>');
            }

            message = message.replace(/\{\{(\w+)\}\}/g, '<span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 px-1 rounded">{{$1}}</span>');

            message = message.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
            message = message.replace(/_([^_]+)_/g, '<em class="text-gray-500">$1</em>');
            message = message.replace(/`([^`]+)`/g, '<code class="bg-gray-200 dark:bg-[#21262d] px-1 rounded text-sm">$1</code>');

            return message;
        },

        async saveTemplate() {
            try {
                const url = this.templateForm.id
                    ? `api.php?route=/telegram/templates/${this.templateForm.id}`
                    : 'api.php?route=/telegram/templates';
                const method = this.templateForm.id ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.templateForm)
                });
                const data = await response.json();
                if (data.success) {
                    showToast(__('telegram.template_saved'));
                    this.showTemplateModal = false;
                    await this.loadTemplates();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.save_error'), 'error');
            }
        },

        async toggleTemplate(template) {
            try {
                const response = await fetch(`api.php?route=/telegram/templates/${template.id}/toggle`, {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.success) {
                    template.is_active = data.data.is_active ? 1 : 0;
                }
            } catch (error) {
                showToast(__('common.error'), 'error');
            }
        },

        async deleteTemplate(id) {
            if (!confirm(__('telegram.confirm_delete_template'))) return;
            try {
                const response = await fetch(`api.php?route=/telegram/templates/${id}`, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    showToast(__('telegram.template_deleted'));
                    await this.loadTemplates();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.delete_error'), 'error');
            }
        },

        async previewTemplate(id) {
            try {
                const response = await fetch(`api.php?route=/telegram/templates/${id}/preview`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({})
                });
                const data = await response.json();
                if (data.success) {
                    this.previewContent = data.data.preview.replace(/\n/g, '<br>');
                    this.showPreviewModal = true;
                }
            } catch (error) {
                showToast(__('common.error'), 'error');
            }
        },

        // Test Template Functions
        async openTestModal(template) {
            this.testTemplate = template;
            this.testMode = 'demo';
            this.testUserId = '';
            this.testChatIdForTemplate = this.config.default_chat_id || '';
            this.testResult = null;
            this.updateTestPreview();
            this.showTestModal = true;

            if (this.pppoeUsers.length === 0) {
                await this.loadPPPoEUsers();
            }
        },

        async loadPPPoEUsers() {
            try {
                const response = await fetch('api.php?route=/pppoe/users?limit=100');
                const data = await response.json();
                if (data.success) {
                    this.pppoeUsers = data.data.data || data.data || [];
                }
            } catch (error) {
                console.error('Error loading PPPoE clients:', error);
            }
        },

        updateTestPreview() {
            if (!this.testTemplate) {
                this.testPreviewContent = '';
                return;
            }

            let message = this.testTemplate.message_template || '';

            const data = this.demoData;

            for (const [key, value] of Object.entries(data)) {
                const regex = new RegExp('\\{\\{' + key + '\\}\\}', 'g');
                message = message.replace(regex, '<span class="bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 px-1 rounded">' + value + '</span>');
            }

            message = message.replace(/\{\{(\w+)\}\}/g, '<span class="bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 px-1 rounded">{{$1}}</span>');

            message = message.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
            message = message.replace(/_([^_]+)_/g, '<em>$1</em>');
            message = message.replace(/`([^`]+)`/g, '<code class="bg-gray-200 dark:bg-[#21262d] px-1 rounded text-sm">$1</code>');

            this.testPreviewContent = message;
        },

        async loadTestUserPreview() {
            if (!this.testUserId || !this.testTemplate) {
                this.updateTestPreview();
                return;
            }

            try {
                const response = await fetch(`api.php?route=/telegram/templates/${this.testTemplate.id}/preview`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: this.testUserId })
                });
                const data = await response.json();
                if (data.success) {
                    let message = data.data.preview;
                    message = message.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
                    message = message.replace(/_([^_]+)_/g, '<em>$1</em>');
                    message = message.replace(/`([^`]+)`/g, '<code class="bg-gray-200 dark:bg-[#21262d] px-1 rounded text-sm">$1</code>');
                    this.testPreviewContent = message;
                }
            } catch (error) {
                console.error('Preview error:', error);
            }
        },

        async sendTestMessage() {
            if (!this.testTemplate) return;

            const chatId = this.testChatIdForTemplate || this.config.default_chat_id;
            if (!chatId) {
                this.testResult = { success: false, message: __('telegram.enter_chat_id') };
                return;
            }

            this.sendingTest = true;
            this.testResult = null;

            try {
                const response = await fetch('api.php?route=/telegram/test-template', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        template_id: this.testTemplate.id,
                        chat_id: chatId,
                        user_id: this.testMode === 'user' ? this.testUserId : null,
                        use_demo_data: this.testMode === 'demo'
                    })
                });
                const data = await response.json();

                if (data.success) {
                    this.testResult = { success: true, message: __('telegram.test_sent_success') };
                    showToast(__('telegram.test_sent_success'));
                } else {
                    this.testResult = { success: false, message: data.message || __('telegram.send_failed') };
                }
            } catch (error) {
                this.testResult = { success: false, message: __('telegram.network_error') };
            } finally {
                this.sendingTest = false;
            }
        },

        async loadRecipients() {
            try {
                const response = await fetch('api.php?route=/telegram/recipients');
                const data = await response.json();
                if (data.success) {
                    this.recipients = data.data || [];
                }
            } catch (error) {
                console.error('Error loading recipients:', error);
            }
        },

        openRecipientModal(recipient = null) {
            if (recipient) {
                this.recipientForm = { ...recipient };
            } else {
                this.recipientForm = {
                    name: '',
                    chat_id: '',
                    role: 'custom',
                    receive_expiration_alerts: true,
                    receive_payment_alerts: true,
                    receive_system_alerts: false,
                    is_active: true
                };
            }
            this.showRecipientModal = true;
        },

        async saveRecipient() {
            try {
                const url = this.recipientForm.id
                    ? `api.php?route=/telegram/recipients/${this.recipientForm.id}`
                    : 'api.php?route=/telegram/recipients';
                const method = this.recipientForm.id ? 'PUT' : 'POST';

                const response = await fetch(url, {
                    method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.recipientForm)
                });
                const data = await response.json();
                if (data.success) {
                    showToast(__('telegram.recipient_saved'));
                    this.showRecipientModal = false;
                    await this.loadRecipients();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.save_error'), 'error');
            }
        },

        async deleteRecipient(id) {
            if (!confirm(__('telegram.confirm_delete_recipient'))) return;
            try {
                const response = await fetch(`api.php?route=/telegram/recipients/${id}`, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    showToast(__('telegram.recipient_deleted'));
                    await this.loadRecipients();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.delete_error'), 'error');
            }
        },

        async loadHistory(page = 1) {
            try {
                let url = `api.php?route=/telegram/history&page=${page}&limit=20`;
                if (this.historyFilter) url += `&status=${this.historyFilter}`;

                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    this.history = data.data.data || [];
                    this.historyPagination = data.data.pagination || { page: 1, pages: 1 };
                }
            } catch (error) {
                console.error('Error loading history:', error);
            }
        },

        async loadStats() {
            try {
                const response = await fetch('api.php?route=/telegram/stats');
                const data = await response.json();
                if (data.success) {
                    this.stats = data.data || {};
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },

        async processExpirations() {
            if (!confirm(__('telegram.confirm_process_expirations'))) return;

            this.processing = true;
            try {
                const response = await fetch('api.php?route=/telegram/process-expirations', {
                    method: 'POST'
                });
                const data = await response.json();
                if (data.success) {
                    const results = data.data;
                    showToast(__('telegram.process_result', {sent: results.sent || 0, failed: results.failed || 0}));
                    await this.loadHistory();
                    await this.loadStats();
                } else {
                    showToast(data.message || __('common.error'), 'error');
                }
            } catch (error) {
                showToast(__('telegram.process_error'), 'error');
            } finally {
                this.processing = false;
            }
        },

        // Helpers
        getEventTypeColor(type) {
            const colors = {
                'expiration_warning': 'bg-yellow-100 dark:bg-yellow-900/30',
                'expired': 'bg-red-100 dark:bg-red-900/30',
                'payment_reminder': 'bg-blue-100 dark:bg-blue-900/30',
                'welcome': 'bg-green-100 dark:bg-green-900/30',
                'suspended': 'bg-gray-100 dark:bg-[#21262d]',
                'reactivated': 'bg-emerald-100 dark:bg-emerald-900/30',
                'custom': 'bg-purple-100 dark:bg-purple-900/30'
            };
            return colors[type] || 'bg-gray-100';
        },

        getEventTypeIcon(type) {
            const icons = {
                'expiration_warning': '⚠️',
                'expired': '❌',
                'payment_reminder': '💳',
                'welcome': '🎉',
                'suspended': '⛔',
                'reactivated': '✅',
                'custom': '📝'
            };
            return icons[type] || '📨';
        },

        getEventTypeLabel(type) {
            const labels = {
                'expiration_warning': __('telegram.event_expiration_warning'),
                'expired': __('telegram.event_expired'),
                'payment_reminder': __('telegram.event_payment_reminder'),
                'welcome': __('telegram.event_welcome'),
                'suspended': __('telegram.event_suspended'),
                'reactivated': __('telegram.event_reactivated'),
                'custom': __('telegram.event_custom')
            };
            return labels[type] || type;
        },

        getRoleClass(role) {
            const classes = {
                'admin': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'manager': 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                'accountant': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'technician': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                'custom': 'bg-gray-100 text-gray-700 dark:bg-[#21262d] dark:text-gray-300'
            };
            return classes[role] || classes.custom;
        },

        getRoleLabel(role) {
            const labels = {
                'admin': __('telegram.role_admin'),
                'manager': __('telegram.role_manager'),
                'accountant': __('telegram.role_accountant'),
                'technician': __('telegram.role_technician'),
                'custom': __('telegram.role_custom')
            };
            return labels[role] || role;
        },

        getStatusClass(status) {
            const classes = {
                'sent': 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                'failed': 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                'pending': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                'cancelled': 'bg-gray-100 text-gray-700 dark:bg-[#21262d] dark:text-gray-300'
            };
            return classes[status] || classes.pending;
        },

        getStatusLabel(status) {
            const labels = {
                'sent': __('telegram.status_sent'),
                'failed': __('telegram.status_failed'),
                'pending': __('status.pending'),
                'cancelled': __('telegram.status_cancelled')
            };
            return labels[status] || status;
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        },

        // Watch pour charger les donnees selon l'onglet
        $watch: {
            activeTab(tab) {
                if (tab === 'history' && this.history.length === 0) {
                    this.loadHistory();
                } else if (tab === 'stats') {
                    this.loadStats();
                }
            }
        }
    };
}
</script>
