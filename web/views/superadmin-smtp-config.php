<?php $pageTitle = __('email.smtp_config_title') ?? 'Configuration SMTP';
$currentPage = 'superadmin-smtp-config'; ?>

<div x-data="smtpConfigPage()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <p class="text-gray-600 dark:text-gray-400">
                <?= __('email.smtp_config_desc') ?? 'Configuration du serveur SMTP pour l\'envoi d\'emails (vérification des comptes, notifications)' ?>
            </p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex border-b border-gray-200 dark:border-[#30363d] mb-6">
        <button @click="activeTab = 'smtp'"
            :class="activeTab === 'smtp' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <?= __('email.smtp_tab') ?>
        </button>
        <button @click="activeTab = 'templates'; if (!templatesLoaded) loadTemplates()"
            :class="activeTab === 'templates' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            <?= __('email.templates_tab') ?>
        </button>
        <button @click="activeTab = 'logs'; if (!logsLoaded) loadLogs()"
            :class="activeTab === 'logs' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'"
            class="flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <?= __('email.logs_tab') ?>
        </button>
    </div>

    <div x-show="loading" class="text-center py-12 text-gray-500">
        <?= __('common.loading') ?? 'Chargement...' ?>
    </div>

    <!-- ==================== TAB: SMTP Configuration ==================== -->
    <div x-show="!loading && activeTab === 'smtp'" class="space-y-6">
        <!-- Email Verification Toggle Card -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl p-5 text-white relative overflow-hidden">
            <div class="absolute right-0 top-0 w-40 h-40 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/4"></div>
            <div class="absolute right-16 bottom-0 w-24 h-24 bg-white/5 rounded-full translate-y-1/2"></div>
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="text-sm text-white/70 uppercase tracking-wider mb-1">
                        <?= __('email.verification_status') ?? 'Vérification Email' ?>
                    </p>
                    <p class="text-xl font-bold" x-text="config.email_verification_enabled === '1' ? '<?= __js('email.verification_active') ?>' : '<?= __js('email.verification_inactive') ?>'"></p>
                    <p class="text-xs text-white/50 mt-1">
                        <?= __('email.verification_desc') ?? 'Les nouveaux admins devront vérifier leur email avant de pouvoir se connecter' ?>
                    </p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer self-start sm:self-center">
                    <input type="checkbox" class="sr-only peer"
                        :checked="config.email_verification_enabled === '1'"
                        @change="config.email_verification_enabled = $event.target.checked ? '1' : '0'">
                    <div class="w-11 h-6 bg-white/20 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-white/30 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                </label>
            </div>
        </div>

        <!-- SMTP Settings Card -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('email.smtp_settings') ?? 'Paramètres SMTP' ?>
                </h3>
            </div>
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Host -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('email.smtp_host') ?? 'Serveur SMTP' ?>
                        </label>
                        <input type="text" x-model="config.smtp_host" placeholder="smtp.gmail.com"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <!-- Port -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('email.smtp_port') ?? 'Port' ?>
                        </label>
                        <input type="number" x-model="config.smtp_port" placeholder="587"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <!-- Encryption -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('email.smtp_encryption') ?? 'Chiffrement' ?>
                        </label>
                        <select x-model="config.smtp_encryption"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value="tls">TLS (<?= __('email.recommended') ?? 'Recommandé' ?>)</option>
                            <option value="ssl">SSL</option>
                            <option value="none"><?= __('email.no_encryption') ?? 'Aucun' ?></option>
                        </select>
                    </div>
                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('email.smtp_username') ?? 'Utilisateur SMTP' ?>
                        </label>
                        <input type="text" x-model="config.smtp_username" placeholder="user@example.com"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            <?= __('email.smtp_password') ?? 'Mot de passe SMTP' ?>
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" x-model="config.smtp_password"
                                :placeholder="config.smtp_password_set ? '<?= __js('email.password_unchanged') ?>' : ''"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 pr-10">
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 px-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Separator -->
                <div class="border-t border-gray-200 dark:border-[#30363d] pt-4">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        <?= __('email.sender_info') ?? 'Informations expéditeur' ?>
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- From Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.smtp_from_email') ?? 'Email expéditeur' ?>
                            </label>
                            <input type="email" x-model="config.smtp_from_email" placeholder="noreply@example.com"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- From Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.smtp_from_name') ?? 'Nom expéditeur' ?>
                            </label>
                            <input type="text" x-model="config.smtp_from_name" placeholder="RADIUS Manager"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Info box -->
                <div class="bg-blue-50 dark:bg-blue-900/10 rounded-lg p-3">
                    <p class="text-xs text-blue-700 dark:text-blue-300">
                        <strong><?= __('email.smtp_info_title') ?? 'Ports courants' ?> :</strong>
                        587 (TLS/STARTTLS), 465 (SSL), 25 (non chiffré).
                        <?= __('email.smtp_info_gmail') ?? 'Pour Gmail, utilisez un "App Password" avec 2FA activé.' ?>
                    </p>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex flex-col sm:flex-row gap-2 sm:justify-between">
                <div class="flex gap-2">
                    <!-- Test Connection -->
                    <button @click="testConnection()" :disabled="testing"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors disabled:opacity-50">
                        <span x-show="!testing">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <?= __('email.test_connection') ?? 'Tester la connexion' ?>
                        </span>
                        <span x-show="testing"><?= __('common.loading') ?? 'Chargement...' ?></span>
                    </button>
                    <!-- Send Test Email -->
                    <button @click="showTestEmail = !showTestEmail"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        <?= __('email.send_test') ?? 'Envoyer un test' ?>
                    </button>
                </div>
                <!-- Save -->
                <button @click="saveConfig()" :disabled="saving"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <span x-show="!saving"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="saving"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>

        <!-- Test Email Panel -->
        <div x-show="showTestEmail" x-collapse
            class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
            <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    <?= __('email.send_test_email') ?? 'Envoyer un email de test' ?>
                </h3>
            </div>
            <div class="p-5">
                <div class="flex gap-3">
                    <input type="email" x-model="testEmailAddress" placeholder="test@example.com"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                    <button @click="sendTestEmail()" :disabled="sendingTest"
                        class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 whitespace-nowrap">
                        <span x-show="!sendingTest"><?= __('email.send') ?? 'Envoyer' ?></span>
                        <span x-show="sendingTest"><?= __('common.loading') ?? 'Chargement...' ?></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Test Result -->
        <div x-show="testResult" x-collapse>
            <div :class="testSuccess ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800'"
                class="rounded-xl border p-4">
                <div class="flex items-start gap-3">
                    <template x-if="testSuccess">
                        <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="!testSuccess">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <div>
                        <p :class="testSuccess ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'" class="text-sm font-medium" x-text="testResult"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: Email Templates ==================== -->
    <div x-show="!loading && activeTab === 'templates'" class="space-y-6">
        <div x-show="loadingTemplates" class="text-center py-12 text-gray-500">
            <?= __('common.loading') ?? 'Chargement...' ?>
        </div>

        <div x-show="!loadingTemplates" class="space-y-6">
            <!-- Password Reset Expiry Setting -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d]">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        <?= __('email.template_reset_expiry') ?>
                    </h3>
                </div>
                <div class="p-5">
                    <div class="flex items-center gap-3">
                        <input type="number" x-model="templateSettings.password_reset_expiry_hours" min="1" max="72"
                            class="w-24 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        <span class="text-sm text-gray-600 dark:text-gray-400"><?= __('email.template_hours') ?></span>
                    </div>
                </div>
            </div>

            <!-- Template: Verification Email -->
            <template x-for="type in ['verification', 'reset']" :key="type">
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                    <div class="px-5 py-3 bg-gray-50 dark:bg-[#0d1117]/50 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider"
                            x-text="type === 'verification' ? '<?= __js('email.template_verification') ?>' : '<?= __js('email.template_reset') ?>'"></h3>
                        <div class="flex gap-2">
                            <button @click="previewTemplate(type)"
                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                                <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <?= __('email.template_preview') ?>
                            </button>
                            <button @click="resetToDefault(type)"
                                class="px-3 py-1.5 text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/10 border border-orange-200 dark:border-orange-800 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/20 transition-colors">
                                <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                <?= __('email.template_reset_default') ?>
                            </button>
                        </div>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Placeholders info -->
                        <div class="flex flex-wrap gap-2">
                            <span class="text-xs text-gray-500 dark:text-gray-400 self-center"><?= __('email.template_placeholders') ?> :</span>
                            <template x-for="ph in placeholders" :key="ph">
                                <button type="button" @click="navigator.clipboard.writeText(ph); showToast(ph + ' copié', 'success')"
                                    class="px-2 py-0.5 text-xs font-mono bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded border border-blue-200 dark:border-blue-800 hover:bg-blue-100 dark:hover:bg-blue-900/30 cursor-pointer transition-colors"
                                    x-text="ph"></button>
                            </template>
                        </div>

                        <!-- Subject -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.template_subject') ?>
                            </label>
                            <input type="text" x-model="templates[type].subject"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>

                        <!-- Body -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                <?= __('email.template_body') ?>
                            </label>
                            <textarea x-model="templates[type].body" rows="12"
                                class="w-full px-3 py-2 text-sm font-mono border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 resize-y"></textarea>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Save Templates -->
            <div class="flex justify-end">
                <button @click="saveTemplates()" :disabled="savingTemplates"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <span x-show="!savingTemplates"><?= __('common.save') ?? 'Enregistrer' ?></span>
                    <span x-show="savingTemplates"><?= __('common.loading') ?? 'Chargement...' ?></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== TAB: Email Logs ==================== -->
    <div x-show="!loading && activeTab === 'logs'" class="space-y-6">
        <div x-show="loadingLogs" class="text-center py-12 text-gray-500">
            <?= __('common.loading') ?? 'Chargement...' ?>
        </div>

        <div x-show="!loadingLogs" class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100" x-text="logStats.total || 0"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('email.log_total') ?></p>
                </div>
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
                    <p class="text-2xl font-bold text-green-600" x-text="logStats.sent || 0"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('email.log_sent') ?></p>
                </div>
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
                    <p class="text-2xl font-bold text-red-600" x-text="logStats.failed || 0"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('email.log_failed') ?></p>
                </div>
                <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] p-4 text-center">
                    <p class="text-2xl font-bold text-blue-600" x-text="logStats.last_24h || 0"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= __('email.log_last_24h') ?></p>
                </div>
            </div>

            <!-- Filters + Actions -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl border border-gray-200 dark:border-[#30363d] overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-200 dark:border-[#30363d]">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <!-- Search -->
                        <div class="flex-1">
                            <input type="text" x-model="logFilters.search" @input.debounce.400ms="loadLogs()"
                                placeholder="<?= __('email.log_search') ?>"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                        </div>
                        <!-- Type filter -->
                        <select x-model="logFilters.type" @change="loadLogs()"
                            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value=""><?= __('email.log_all_types') ?></option>
                            <option value="verification"><?= __('email.log_type_verification') ?></option>
                            <option value="reset"><?= __('email.log_type_reset') ?></option>
                            <option value="test"><?= __('email.log_type_test') ?></option>
                            <option value="other"><?= __('email.log_type_other') ?></option>
                        </select>
                        <!-- Status filter -->
                        <select x-model="logFilters.status" @change="loadLogs()"
                            class="px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500">
                            <option value=""><?= __('email.log_all_statuses') ?></option>
                            <option value="sent"><?= __('email.log_sent') ?></option>
                            <option value="failed"><?= __('email.log_failed') ?></option>
                        </select>
                        <!-- Refresh -->
                        <button @click="loadLogs()" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]/50">
                            <tr>
                                <th class="px-4 py-3 text-left">
                                    <input type="checkbox" @change="toggleAllLogs($event.target.checked)"
                                        class="rounded border-gray-300 dark:border-gray-600 text-blue-600">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_date') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_to') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_subject') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_type') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_status') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase"><?= __('email.log_error') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#30363d]">
                            <template x-for="log in emailLogs" :key="log.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]/50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" :value="log.id" x-model="selectedLogIds"
                                            class="rounded border-gray-300 dark:border-gray-600 text-blue-600">
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap" x-text="formatDate(log.created_at)"></td>
                                    <td class="px-4 py-3 text-gray-900 dark:text-gray-100 font-mono text-xs" x-text="log.to_email"></td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate" x-text="log.subject"></td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="{
                                                'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300': log.email_type === 'verification',
                                                'bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300': log.email_type === 'reset',
                                                'bg-purple-100 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300': log.email_type === 'test',
                                                'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': log.email_type === 'other'
                                            }"
                                            x-text="getTypeLabel(log.email_type)">
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium"
                                            :class="log.status === 'sent' ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300'">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <template x-if="log.status === 'sent'">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </template>
                                                <template x-if="log.status === 'failed'">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </template>
                                            </svg>
                                            <span x-text="log.status === 'sent' ? '<?= __js('email.log_sent') ?>' : '<?= __js('email.log_failed') ?>'"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-red-500 dark:text-red-400 text-xs max-w-xs truncate" x-text="log.error_message || '—'"></td>
                                </tr>
                            </template>
                            <tr x-show="emailLogs.length === 0">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <?= __('email.log_no_logs') ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination + Actions -->
                <div class="px-5 py-3 border-t border-gray-200 dark:border-[#30363d] flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between">
                    <!-- Bulk actions -->
                    <div class="flex gap-2 flex-wrap">
                        <button x-show="selectedLogIds.length > 0" @click="deleteSelectedLogs()"
                            class="px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/10 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/20 transition-colors">
                            <?= __('email.log_delete_selected') ?> (<span x-text="selectedLogIds.length"></span>)
                        </button>
                        <button @click="clearAllLogs()"
                            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <?= __('email.log_clear_all') ?>
                        </button>
                        <div class="flex items-center gap-1">
                            <button @click="clearOlderLogs()"
                                class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                                <?= __('email.log_clear_older') ?>
                            </button>
                            <input type="number" x-model="clearOlderDays" min="1" max="365"
                                class="w-16 px-2 py-1.5 text-xs border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100">
                            <span class="text-xs text-gray-500"><?= __('email.log_days') ?></span>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center gap-2" x-show="logPagination.total_pages > 1">
                        <button @click="logFilters.page = Math.max(1, logFilters.page - 1); loadLogs()"
                            :disabled="logFilters.page <= 1"
                            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 disabled:opacity-50 transition-colors">
                            &laquo;
                        </button>
                        <span class="text-xs text-gray-600 dark:text-gray-400">
                            <span x-text="logFilters.page"></span> / <span x-text="logPagination.total_pages"></span>
                        </span>
                        <button @click="logFilters.page = Math.min(logPagination.total_pages, logFilters.page + 1); loadLogs()"
                            :disabled="logFilters.page >= logPagination.total_pages"
                            class="px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-[#21262d] border border-gray-300 dark:border-[#30363d] rounded-lg hover:bg-gray-200 disabled:opacity-50 transition-colors">
                            &raquo;
                        </button>
                        <span class="text-xs text-gray-500" x-text="'(' + logPagination.total + ' total)'"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div x-show="showPreview" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
        @keydown.escape.window="showPreview = false">
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col"
            @click.away="showPreview = false">
            <div class="px-5 py-3 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300"><?= __('email.template_preview') ?></h3>
                <button @click="showPreview = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-auto p-1">
                <iframe :srcdoc="previewHtml" class="w-full h-[70vh] border-0 rounded-lg"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function smtpConfigPage() {
    return {
        activeTab: 'smtp',
        config: {
            smtp_host: '',
            smtp_port: '587',
            smtp_username: '',
            smtp_password: '',
            smtp_password_set: false,
            smtp_encryption: 'tls',
            smtp_from_email: '',
            smtp_from_name: 'RADIUS Manager',
            email_verification_enabled: '0'
        },
        loading: true,
        saving: false,
        testing: false,
        sendingTest: false,
        showPassword: false,
        showTestEmail: false,
        testEmailAddress: '',
        testResult: '',
        testSuccess: false,

        // Templates
        templatesLoaded: false,
        loadingTemplates: false,
        savingTemplates: false,
        templates: {
            verification: { subject: '', body: '' },
            reset: { subject: '', body: '' }
        },
        defaults: {
            verification: { subject: '', body: '' },
            reset: { subject: '', body: '' }
        },
        templateSettings: {
            password_reset_expiry_hours: '1'
        },
        placeholders: [],
        previewHtml: '',
        showPreview: false,

        // Logs
        logsLoaded: false,
        loadingLogs: false,
        emailLogs: [],
        logStats: {},
        logPagination: { total: 0, page: 1, per_page: 25, total_pages: 1 },
        logFilters: { search: '', type: '', status: '', page: 1 },
        selectedLogIds: [],
        clearOlderDays: 30,

        async init() {
            await this.loadConfig();
            this.loading = false;
        },

        async loadConfig() {
            try {
                const res = await fetch('api.php?route=/superadmin/smtp-config', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.config = { ...this.config, ...data.config, smtp_password: '' };
                }
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
        },

        async saveConfig() {
            this.saving = true;
            try {
                const res = await fetch('api.php?route=/superadmin/smtp-config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.config)
                });
                const data = await res.json();
                if (data.success) {
                    showToast(data.message, 'success');
                    await this.loadConfig();
                } else {
                    showToast(data.message, 'error');
                }
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
            this.saving = false;
        },

        async testConnection() {
            this.testing = true;
            this.testResult = '';
            try {
                await this.saveConfig();
                const res = await fetch('api.php?route=/superadmin/smtp-config/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                this.testResult = data.message;
                this.testSuccess = data.success;
            } catch (e) {
                this.testResult = '<?= __js('email.smtp_connect_failed') ?>';
                this.testSuccess = false;
            }
            this.testing = false;
        },

        async sendTestEmail() {
            if (!this.testEmailAddress) {
                showToast('<?= __js('email.enter_test_email') ?>', 'error');
                return;
            }
            this.sendingTest = true;
            this.testResult = '';
            try {
                await this.saveConfig();
                const res = await fetch('api.php?route=/superadmin/smtp-config/test', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email: this.testEmailAddress })
                });
                const data = await res.json();
                this.testResult = data.message;
                this.testSuccess = data.success;
            } catch (e) {
                this.testResult = '<?= __js('common.error') ?>';
                this.testSuccess = false;
            }
            this.sendingTest = false;
        },

        // Templates methods
        async loadTemplates() {
            this.loadingTemplates = true;
            try {
                const res = await fetch('api.php?route=/superadmin/email-templates', { headers: { 'Accept': 'application/json' } });
                const data = await res.json();
                if (data.success) {
                    this.templates = data.templates;
                    this.defaults = data.defaults;
                    this.templateSettings = data.settings;
                    this.placeholders = data.placeholders || [];
                    this.templatesLoaded = true;
                }
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
            this.loadingTemplates = false;
        },

        async saveTemplates() {
            this.savingTemplates = true;
            try {
                const payload = {
                    email_template_verification_subject: this.templates.verification.subject,
                    email_template_verification_body: this.templates.verification.body,
                    email_template_reset_subject: this.templates.reset.subject,
                    email_template_reset_body: this.templates.reset.body,
                    password_reset_expiry_hours: this.templateSettings.password_reset_expiry_hours,
                };
                const res = await fetch('api.php?route=/superadmin/email-templates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
            this.savingTemplates = false;
        },

        resetToDefault(type) {
            if (this.defaults[type]) {
                this.templates[type].subject = this.defaults[type].subject;
                this.templates[type].body = this.defaults[type].body;
            }
        },

        previewTemplate(type) {
            let html = this.templates[type].body || (this.defaults[type] ? this.defaults[type].body : '');
            if (!html) return;
            html = html.replace(/\{\{username\}\}/g, 'JohnDoe');
            html = html.replace(/\{\{link\}\}/g, '#');
            html = html.replace(/\{\{app_name\}\}/g, this.config.smtp_from_name || 'RADIUS Manager');
            html = html.replace(/\{\{expiry_hours\}\}/g, type === 'reset' ? this.templateSettings.password_reset_expiry_hours : '24');
            this.previewHtml = html;
            this.showPreview = true;
        },

        // Logs methods
        async loadLogs() {
            this.loadingLogs = true;
            try {
                const params = new URLSearchParams();
                params.set('page', this.logFilters.page);
                if (this.logFilters.search) params.set('search', this.logFilters.search);
                if (this.logFilters.type) params.set('type', this.logFilters.type);
                if (this.logFilters.status) params.set('status', this.logFilters.status);

                const [logsRes, statsRes] = await Promise.all([
                    fetch('api.php?route=/superadmin/email-logs&' + params.toString(), { headers: { 'Accept': 'application/json' } }),
                    fetch('api.php?route=/superadmin/email-logs/stats', { headers: { 'Accept': 'application/json' } })
                ]);
                const logsData = await logsRes.json();
                const statsData = await statsRes.json();

                if (logsData.success) {
                    this.emailLogs = logsData.logs;
                    this.logPagination = logsData.pagination;
                }
                if (statsData.success) {
                    this.logStats = statsData.stats;
                }
                this.logsLoaded = true;
                this.selectedLogIds = [];
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
            this.loadingLogs = false;
        },

        toggleAllLogs(checked) {
            this.selectedLogIds = checked ? this.emailLogs.map(l => String(l.id)) : [];
        },

        async deleteSelectedLogs() {
            if (!this.selectedLogIds.length) return;
            if (!confirm('<?= __js('email.log_confirm_delete') ?>')) return;
            try {
                const res = await fetch('api.php?route=/superadmin/email-logs', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ ids: this.selectedLogIds.map(Number) })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) await this.loadLogs();
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
        },

        async clearAllLogs() {
            if (!confirm('<?= __js('email.log_confirm_delete') ?>')) return;
            try {
                const res = await fetch('api.php?route=/superadmin/email-logs', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ clear_all: true })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) await this.loadLogs();
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
        },

        async clearOlderLogs() {
            if (!confirm('<?= __js('email.log_confirm_delete') ?>')) return;
            try {
                const res = await fetch('api.php?route=/superadmin/email-logs', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ older_than_days: parseInt(this.clearOlderDays) })
                });
                const data = await res.json();
                showToast(data.message, data.success ? 'success' : 'error');
                if (data.success) await this.loadLogs();
            } catch (e) {
                showToast('<?= __js('common.error') ?>', 'error');
            }
        },

        getTypeLabel(type) {
            const labels = {
                verification: '<?= __js('email.log_type_verification') ?>',
                reset: '<?= __js('email.log_type_reset') ?>',
                test: '<?= __js('email.log_type_test') ?>',
                other: '<?= __js('email.log_type_other') ?>'
            };
            return labels[type] || type;
        },

        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
                + ' ' + d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
    };
}
</script>
