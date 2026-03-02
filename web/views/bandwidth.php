<?php
$pageTitle = __('bandwidth.title');
$currentPage = 'bandwidth';
?>

<div x-data="bandwidthPage()" x-init="init()">
    <!-- Header avec onglets principaux -->
    <div class="mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <?= __('bandwidth.title')?>
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1"><?= __('bandwidth.subtitle')?></p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="border-b border-gray-200 dark:border-[#30363d]">
            <nav class="flex gap-6 overflow-x-auto">
                <button type="button" @click="activeSection = 'learn'"
                        :class="activeSection === 'learn' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <?= __('bandwidth.tab_learn')?>
                </button>
                <button type="button" @click="activeSection = 'policies'"
                        :class="activeSection === 'policies' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <?= __('bandwidth.tab_policies')?>
                </button>
                <button type="button" @click="activeSection = 'schedules'"
                        :class="activeSection === 'schedules' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <?= __('bandwidth.tab_schedules')?>
                </button>
                <button type="button" @click="activeSection = 'apply'"
                        :class="activeSection === 'apply' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <?= __('bandwidth.tab_apply')?>
                </button>
                <button type="button" @click="activeSection = 'monitor'"
                        :class="activeSection === 'monitor' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400'"
                        class="pb-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2 whitespace-nowrap">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <?= __('bandwidth.tab_monitor')?>
                </button>
            </nav>
        </div>
    </div>

    <!-- Section: Comprendre (Éducative) -->
    <div x-show="activeSection === 'learn'" x-cloak class="space-y-6">
        <!-- Introduction -->
        <div class="bg-gradient-to-r from-primary-500 to-purple-600 rounded-2xl p-6 text-white">
            <h2 class="text-2xl font-bold mb-3"><?= __('bandwidth.what_is')?></h2>
            <p class="text-primary-100 text-lg">
                <?= __('bandwidth.what_is_desc')?>
            </p>
        </div>

        <!-- Concepts de base -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Download/Upload -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.download_upload')?></h3>
                </div>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <p><?= __('bandwidth.download_desc')?></p>
                    <p><?= __('bandwidth.upload_desc')?></p>
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-3 mt-4">
                        <p class="font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.practical_example')?></p>
                        <p><?= __('bandwidth.speed_example')?></p>
                    </div>
                </div>
            </div>

            <!-- Unités -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.understand_units')?></h3>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-[#30363d]">
                        <span class="text-gray-600 dark:text-gray-400">1 Kbps</span>
                        <span class="font-mono text-gray-900 dark:text-white">1,024 bps</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-[#30363d]">
                        <span class="text-gray-600 dark:text-gray-400">1 Mbps</span>
                        <span class="font-mono text-gray-900 dark:text-white">1,048,576 bps</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-[#30363d]">
                        <span class="text-gray-600 dark:text-gray-400">10 Mbps</span>
                        <span class="font-mono text-gray-900 dark:text-white">10,485,760 bps</span>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 mt-3">
                        <p class="text-yellow-800 dark:text-yellow-200 text-xs">
                            <?= __('bandwidth.note_bits')?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Burst -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.burst')?></h3>
                </div>
                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                    <p><?= __('bandwidth.burst_desc')?></p>
                    <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-lg p-3">
                        <p class="font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('bandwidth.how_it_works')?></p>
                        <ol class="list-decimal list-inside space-y-1 text-xs">
                            <li><?= __('bandwidth.burst_step1')?></li>
                            <li><?= __('bandwidth.burst_step2')?></li>
                            <li><?= __('bandwidth.burst_step3')?></li>
                        </ol>
                    </div>
                    <p class="text-xs text-gray-500"><?= __('bandwidth.burst_note')?></p>
                </div>
            </div>
        </div>

        <!-- Schéma visuel -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('bandwidth.how_radius')?></h3>
            <div class="flex flex-col lg:flex-row items-center justify-center gap-8 py-6">
                <!-- User -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white"><?= __('bandwidth.user')?></p>
                    <p class="text-xs text-gray-500"><?= __('bandwidth.connects')?></p>
                </div>

                <!-- Arrow -->
                <div class="hidden lg:block">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
                <div class="lg:hidden">
                    <svg class="w-8 h-8 text-gray-400 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>

                <!-- NAS/Router -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white"><?= __('bandwidth.router_nas')?></p>
                    <p class="text-xs text-gray-500"><?= __('bandwidth.asks_authorization')?></p>
                </div>

                <!-- Arrow -->
                <div class="hidden lg:block">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>
                <div class="lg:hidden">
                    <svg class="w-8 h-8 text-gray-400 rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>

                <!-- RADIUS -->
                <div class="text-center">
                    <div class="w-20 h-20 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white"><?= __('bandwidth.radius_server')?></p>
                    <p class="text-xs text-gray-500"><?= __('bandwidth.sends_limits')?></p>
                </div>

                <!-- Arrow back -->
                <div class="hidden lg:block">
                    <svg class="w-12 h-12 text-gray-400 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </div>

                <!-- Result -->
                <div class="text-center hidden lg:block">
                    <div class="w-20 h-20 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mx-auto mb-2">
                        <svg class="w-10 h-10 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <p class="font-medium text-gray-900 dark:text-white"><?= __('bandwidth.speed_applied')?></p>
                    <p class="text-xs text-gray-500">Ex: 5M/2M</p>
                </div>
            </div>

            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 mt-4">
                <p class="text-blue-800 dark:text-blue-200 text-sm">
                    <?= __('bandwidth.summary_text')?>
                </p>
            </div>
        </div>

        <!-- Guide rapide -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('bandwidth.quick_guide')?></h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-[#21262d]/50">
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.usage')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.download_min')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.upload_min')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.recommendation')?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= __('bandwidth.usage_basic_web')?></td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">1 Mbps</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">512 Kbps</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-gray-100 dark:bg-[#21262d] rounded text-xs">Basique</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= __('bandwidth.usage_streaming_sd')?></td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">3 Mbps</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">1 Mbps</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs">Standard</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= __('bandwidth.usage_streaming_hd')?></td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">5 Mbps</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">2 Mbps</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300 rounded text-xs">Premium</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= __('bandwidth.usage_4k_gaming')?></td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">25 Mbps</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">5 Mbps</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded text-xs">Business</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 text-gray-900 dark:text-white"><?= __('bandwidth.usage_remote_work')?></td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">10 Mbps</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">5 Mbps</td>
                            <td class="px-4 py-3"><span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs">Pro</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Bouton Commencer -->
        <div class="text-center py-6">
            <button @click="activeSection = 'policies'"
                    class="inline-flex items-center gap-2 px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white text-lg font-medium rounded-xl transition-colors">
                <?= __('bandwidth.start_creating_policies')?>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Section: Politiques de Bande Passante -->
    <div x-show="activeSection === 'policies'" x-cloak class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.policies_title')?></h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm"><?= __('bandwidth.policies_subtitle')?></p>
            </div>
            <button @click="showPolicyModal = true; resetPolicyForm()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('bandwidth.new_policy')?>
            </button>
        </div>

        <!-- Liste des politiques -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="policy in policies" :key="policy.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-5 relative overflow-hidden">
                    <!-- Barre de couleur -->
                    <div class="absolute top-0 left-0 right-0 h-1" :style="'background-color: ' + policy.color"></div>

                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white" x-text="policy.name"></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="policy.description || __('bandwidth.no_description')"></p>
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="editPolicy(policy)" class="p-2 text-gray-400 hover:text-primary-600" title="<?= __('common.edit')?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            <button @click="deletePolicy(policy)" class="p-2 text-gray-400 hover:text-red-600" title="<?= __('common.delete')?>">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Débits -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                Download
                            </span>
                            <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="formatSpeed(policy.download_rate)"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                Upload
                            </span>
                            <span class="font-mono font-medium text-gray-900 dark:text-white" x-text="formatSpeed(policy.upload_rate)"></span>
                        </div>
                        <div class="flex items-center justify-between" x-show="policy.burst_download_rate">
                            <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                Burst
                            </span>
                            <span class="font-mono text-sm text-purple-600 dark:text-purple-400" x-text="formatSpeed(policy.burst_download_rate) + '/' + formatSpeed(policy.burst_upload_rate)"></span>
                        </div>
                    </div>

                    <!-- Priorité -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-[#30363d] flex items-center justify-between">
                        <span class="text-xs text-gray-500"><?= __('bandwidth.priority')?></span>
                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium"
                              :class="getPriorityClass(policy.priority)"
                              x-text="getPriorityText(policy.priority)"></span>
                    </div>
                </div>
            </template>

            <!-- Empty state -->
            <div x-show="policies.length === 0" class="col-span-full py-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"><?= __('bandwidth.no_policy_created')?></h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4"><?= __('bandwidth.no_policy_desc')?></p>
                <button @click="showPolicyModal = true; resetPolicyForm()"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <?= __('bandwidth.create_policy')?>
                </button>
            </div>
        </div>
    </div>

    <!-- Section: Planification -->
    <div x-show="activeSection === 'schedules'" x-cloak class="space-y-6">
        <!-- Info box -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="font-medium text-blue-800 dark:text-blue-200"><?= __('bandwidth.schedule_info_title')?></h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        <?= __('bandwidth.schedule_info_desc')?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.schedules_title')?></h2>
                <p class="text-gray-600 dark:text-gray-400 text-sm"><?= __('bandwidth.schedules_subtitle')?></p>
            </div>
            <button @click="showScheduleModal = true; resetScheduleForm()"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <?= __('bandwidth.new_schedule')?>
            </button>
        </div>

        <!-- Liste des planifications -->
        <div class="space-y-4">
            <template x-for="schedule in schedules" :key="schedule.id">
                <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-5">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex items-start gap-4">
                            <!-- Icône horloge -->
                            <div class="p-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white" x-text="schedule.name"></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400" x-text="schedule.description || ''"></p>
                                <div class="flex items-center gap-4 mt-2 text-sm">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span x-text="schedule.start_time + ' - ' + schedule.end_time"></span>
                                    </span>
                                    <span class="text-gray-400">|</span>
                                    <span class="text-gray-600 dark:text-gray-400" x-text="formatActiveDays(schedule.active_days)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Politiques -->
                        <div class="flex items-center gap-4">
                            <div class="text-center px-4">
                                <p class="text-xs text-gray-500 mb-1"><?= __('bandwidth.normal')?></p>
                                <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full text-sm font-medium"
                                      x-text="getPolicyName(schedule.default_policy_id)"></span>
                            </div>
                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                            <div class="text-center px-4">
                                <p class="text-xs text-gray-500 mb-1"><?= __('bandwidth.scheduled')?></p>
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-full text-sm font-medium"
                                      x-text="getPolicyName(schedule.scheduled_policy_id)"></span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center gap-1 ml-4">
                                <button @click="toggleSchedule(schedule)"
                                        class="p-2 rounded-lg transition-colors"
                                        :class="schedule.is_active ? 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' : 'text-gray-400 hover:bg-gray-50 dark:hover:bg-[#30363d]'"
                                        :title="schedule.is_active ? __('common.deactivate') : __('common.activate')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                                    </svg>
                                </button>
                                <button @click="editSchedule(schedule)" class="p-2 text-gray-400 hover:text-primary-600" title="<?= __('common.edit')?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button @click="deleteSchedule(schedule)" class="p-2 text-gray-400 hover:text-red-600" title="<?= __('common.delete')?>">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Empty state -->
            <div x-show="schedules.length === 0" class="py-12 text-center bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d]">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2"><?= __('bandwidth.no_schedule')?></h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4"><?= __('bandwidth.no_schedule_desc')?></p>
            </div>
        </div>
    </div>

    <!-- Section: Appliquer -->
    <div x-show="activeSection === 'apply'" x-cloak class="space-y-6">
        <!-- Méthodes d'application -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Appliquer à un profil -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.by_profile')?></h3>
                        <p class="text-sm text-gray-500"><?= __('bandwidth.recommended_method')?></p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <?= __('bandwidth.by_profile_desc')?>
                </p>
                <a href="index.php?page=profiles" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 text-sm font-medium">
                    <?= __('bandwidth.manage_profiles')?>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>

            <!-- Appliquer à un utilisateur -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.by_user')?></h3>
                        <p class="text-sm text-gray-500"><?= __('bandwidth.individual_customization')?></p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <?= __('bandwidth.by_user_desc')?>
                </p>
                <div class="space-y-2">
                    <a href="index.php?page=vouchers" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 text-sm font-medium">
                        <?= __('bandwidth.vouchers_hotspot')?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                    <br>
                    <a href="index.php?page=pppoe" class="inline-flex items-center gap-2 text-primary-600 hover:text-primary-700 text-sm font-medium">
                        <?= __('bandwidth.pppoe_clients')?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Appliquer globalement -->
            <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= __('bandwidth.global_zone')?></h3>
                        <p class="text-sm text-gray-500"><?= __('bandwidth.bulk_application')?></p>
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <?= __('bandwidth.global_zone_desc')?>
                </p>
                <button @click="showBulkApplyModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    <?= __('bandwidth.bulk_apply')?>
                </button>
            </div>
        </div>

        <!-- Attributs RADIUS -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl shadow-sm dark:shadow-none border border-gray-200/60 dark:border-[#30363d] p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><?= __('bandwidth.radius_attributes')?></h3>
            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                <?= __('bandwidth.radius_attributes_desc')?>
            </p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-[#21262d]/50">
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.vendor')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.attribute')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.format')?></th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-300"><?= __('bandwidth.example')?></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#30363d]">
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">MikroTik</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">Mikrotik-Rate-Limit</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">rx/tx</td>
                            <td class="px-4 py-3 font-mono text-primary-600">5M/2M</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">MikroTik (Burst)</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">Mikrotik-Rate-Limit</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">rx/tx burst-rx/burst-tx threshold time</td>
                            <td class="px-4 py-3 font-mono text-primary-600">2M/1M 4M/2M 1M/512k 10</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Cisco</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">Cisco-AVPair (qos-policy)</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">policy-name</td>
                            <td class="px-4 py-3 font-mono text-primary-600">subscriber:qos-policy-out=5M</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Standard</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">WISPr-Bandwidth-Max-Down</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">bits/seconde</td>
                            <td class="px-4 py-3 font-mono text-primary-600">5242880</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">Standard</td>
                            <td class="px-4 py-3 font-mono text-gray-600 dark:text-gray-400">WISPr-Bandwidth-Max-Up</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">bits/seconde</td>
                            <td class="px-4 py-3 font-mono text-primary-600">2097152</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section: Monitoring -->
    <div x-show="activeSection === 'monitor'" x-cloak class="space-y-6">
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <div>
                    <h4 class="font-medium text-green-800 dark:text-green-200"><?= __('bandwidth.module_ready')?></h4>
                    <p class="text-sm text-green-700 dark:text-green-300 mt-1">
                        Le système de monitoring de la bande passante est prêt. Vous pouvez visualiser le trafic global, les sessions en temps réel et les consommateurs les plus importants sur le tableau de bord dédié.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center py-12">
            <a href="index.php?page=monitoring" class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                Ouvrir le tableau de bord de Monitoring
            </a>
        </div>
    </div>

    <!-- Modal: Nouvelle/Modifier Politique -->
    <div x-show="showPolicyModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showPolicyModal = false"></div>

            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-xl max-w-3xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="sticky top-0 bg-white dark:bg-[#161b22] px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editPolicyId ? __('bandwidth.edit_policy') : __('bandwidth.new_policy')"></h3>
                    <button @click="showPolicyModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Onglets du formulaire -->
                <div class="border-b border-gray-200 dark:border-[#30363d] px-6">
                    <nav class="flex gap-4">
                        <button type="button" @click="policyTab = 'general'"
                                :class="policyTab === 'general' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?= __('bandwidth.general')?>
                        </button>
                        <button type="button" @click="policyTab = 'burst'"
                                :class="policyTab === 'burst' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-3 px-1 border-b-2 font-medium text-sm transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            Burst
                            <span x-show="policyForm.enable_burst" class="w-2 h-2 bg-purple-500 rounded-full"></span>
                        </button>
                    </nav>
                </div>

                <form @submit.prevent="savePolicy()" class="flex-1 overflow-y-auto">
                    <div class="p-6 space-y-6">
                        <!-- Tab: Général -->
                        <div x-show="policyTab === 'general'" class="space-y-6">
                            <!-- Nom et couleur -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('bandwidth.policy_name')?> *</label>
                                    <input type="text" x-model="policyForm.name" required
                                           placeholder="Ex: Premium 10M"
                                           class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.color')?></label>
                                    <input type="color" x-model="policyForm.color"
                                           class="w-full h-[42px] border border-gray-300 dark:border-[#30363d] rounded-lg cursor-pointer">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.description')?></label>
                                <textarea x-model="policyForm.description" rows="2"
                                          placeholder="<?= __('bandwidth.policy_description_placeholder')?>"
                                          class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500"></textarea>
                            </div>

                            <!-- Débits principaux -->
                            <div class="bg-gray-50 dark:bg-[#21262d]/50 rounded-xl p-5">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                                    </svg>
                                    <?= __('bandwidth.base_speeds')?>
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                </svg>
                                                <?= __('bandwidth.download_reception')?>
                                            </span>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="number" x-model="policyForm.download_value" min="0"
                                                   class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                            <select x-model="policyForm.download_unit"
                                                    class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                                <option value="k">Kbps</option>
                                                <option value="M">Mbps</option>
                                                <option value="G">Gbps</option>
                                            </select>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.download_speed_hint')?></p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <span class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                </svg>
                                                <?= __('bandwidth.upload_send')?>
                                            </span>
                                        </label>
                                        <div class="flex gap-2">
                                            <input type="number" x-model="policyForm.upload_value" min="0"
                                                   class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                            <select x-model="policyForm.upload_unit"
                                                    class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                                <option value="k">Kbps</option>
                                                <option value="M">Mbps</option>
                                                <option value="G">Gbps</option>
                                            </select>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.upload_speed_hint')?></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Priorité -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('bandwidth.priority_qos')?></label>
                                <div class="flex items-center gap-4">
                                    <input type="range" x-model="policyForm.priority" min="1" max="8" step="1"
                                           class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-[#21262d]">
                                    <span class="w-24 text-center px-3 py-1 rounded-lg text-sm font-medium"
                                          :class="getPriorityClass(policyForm.priority)"
                                          x-text="getPriorityText(policyForm.priority)"></span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.priority_hint')?></p>
                            </div>
                        </div>

                        <!-- Tab: Burst -->
                        <div x-show="policyTab === 'burst'" class="space-y-6">
                            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        <?= __('bandwidth.burst')?>
                                    </h4>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="policyForm.enable_burst" class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer dark:bg-[#21262d] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400"><?= __('common.enable')?></span>
                                    </label>
                                </div>

                                <div class="bg-white dark:bg-[#161b22] rounded-lg p-4 mb-4">
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        <?= __('bandwidth.burst_how_title')?><br>
                                        <?= __('bandwidth.burst_how_desc')?>
                                    </p>
                                </div>

                                <div x-show="policyForm.enable_burst" x-transition class="space-y-5">
                                    <!-- Burst Rates -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                                    </svg>
                                                    Burst Download
                                                </span>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="number" x-model="policyForm.burst_download_value" min="0"
                                                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <select x-model="policyForm.burst_download_unit"
                                                        class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                    <option value="G">Gbps</option>
                                                </select>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.burst_max_speed')?></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                <span class="flex items-center gap-2">
                                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                                    </svg>
                                                    Burst Upload
                                                </span>
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="number" x-model="policyForm.burst_upload_value" min="0"
                                                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <select x-model="policyForm.burst_upload_unit"
                                                        class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                    <option value="G">Gbps</option>
                                                </select>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.burst_max_speed')?></p>
                                        </div>
                                    </div>

                                    <!-- Burst Threshold -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Threshold Download
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="number" x-model="policyForm.burst_threshold_download_value" min="0"
                                                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <select x-model="policyForm.burst_threshold_download_unit"
                                                        class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                    <option value="G">Gbps</option>
                                                </select>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.burst_threshold_hint')?></p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                                Threshold Upload
                                            </label>
                                            <div class="flex gap-2">
                                                <input type="number" x-model="policyForm.burst_threshold_upload_value" min="0"
                                                       class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <select x-model="policyForm.burst_threshold_upload_unit"
                                                        class="px-3 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                    <option value="k">Kbps</option>
                                                    <option value="M">Mbps</option>
                                                    <option value="G">Gbps</option>
                                                </select>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.burst_threshold_hint')?></p>
                                        </div>
                                    </div>

                                    <!-- Burst Time -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <?= __('bandwidth.burst_duration')?>
                                        </label>
                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                            <div>
                                                <input type="number" x-model="policyForm.burst_time" min="0" max="86400"
                                                       placeholder="10"
                                                       class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white">
                                                <p class="text-xs text-gray-500 mt-1"><?= __('bandwidth.seconds')?></p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2"><?= __('bandwidth.burst_time_hint')?></p>
                                    </div>

                                    <!-- Résumé visuel -->
                                    <div class="bg-purple-100 dark:bg-purple-900/30 rounded-lg p-4">
                                        <h5 class="text-sm font-medium text-purple-800 dark:text-purple-300 mb-2"><?= __('bandwidth.burst_summary')?></h5>
                                        <div class="text-xs text-purple-700 dark:text-purple-400 space-y-1">
                                            <p>• Vitesse burst: <span x-text="policyForm.burst_download_value + policyForm.burst_download_unit"></span> / <span x-text="policyForm.burst_upload_value + policyForm.burst_upload_unit"></span></p>
                                            <p>• Threshold: <span x-text="policyForm.burst_threshold_download_value + policyForm.burst_threshold_download_unit"></span> / <span x-text="policyForm.burst_threshold_upload_value + policyForm.burst_threshold_upload_unit"></span></p>
                                            <p>• Période: <span x-text="policyForm.burst_time"></span> secondes</p>
                                        </div>
                                    </div>
                                </div>

                                <div x-show="!policyForm.enable_burst" class="text-center py-8 text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <p><?= __('bandwidth.enable_burst_hint')?></p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Actions (toujours visible) -->
                    <div class="sticky bottom-0 bg-white dark:bg-[#161b22] px-6 py-4 border-t border-gray-200 dark:border-[#30363d] flex justify-end gap-3">
                        <button type="button" @click="showPolicyModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <span x-text="editPolicyId ? __('common.update') : __('common.create')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Nouvelle/Modifier Planification -->
    <div x-show="showScheduleModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="showScheduleModal = false"></div>

            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-xl max-w-xl w-full">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-[#30363d] flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="editScheduleId ? __('bandwidth.edit_schedule') : __('bandwidth.new_schedule')"></h3>
                    <button @click="showScheduleModal = false" class="text-gray-400 hover:text-gray-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="saveSchedule()" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('common.name')?> *</label>
                        <input type="text" x-model="scheduleForm.name" required
                               placeholder="Ex: Heures de pointe soir"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                    </div>

                    <!-- Politiques -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('bandwidth.normal_policy')?> *</label>
                            <select x-model="scheduleForm.default_policy_id" required
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value=""><?= __('bandwidth.select')?></option>
                                <template x-for="policy in policies" :key="policy.id">
                                    <option :value="policy.id" x-text="policy.name"></option>
                                </template>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('bandwidth.scheduled_policy')?> *</label>
                            <select x-model="scheduleForm.scheduled_policy_id" required
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                                <option value=""><?= __('bandwidth.select')?></option>
                                <template x-for="policy in policies" :key="policy.id">
                                    <option :value="policy.id" x-text="policy.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <!-- Horaires -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('bandwidth.start_time')?> *</label>
                            <input type="time" x-model="scheduleForm.start_time" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= __('bandwidth.end_time')?> *</label>
                            <input type="time" x-model="scheduleForm.end_time" required
                                   class="w-full px-4 py-2.5 border border-gray-300 dark:border-[#30363d] rounded-lg bg-white dark:bg-[#21262d] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500">
                        </div>
                    </div>

                    <!-- Jours -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"><?= __('bandwidth.active_days')?></label>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="(day, index) in [__('bandwidth.day_mon'), __('bandwidth.day_tue'), __('bandwidth.day_wed'), __('bandwidth.day_thu'), __('bandwidth.day_fri'), __('bandwidth.day_sat'), __('bandwidth.day_sun')]" :key="index">
                                <label class="flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition-colors"
                                       :class="isDayActive(index) ? 'bg-primary-50 border-primary-300 dark:bg-primary-900/20 dark:border-primary-700' : 'border-gray-200 dark:border-[#30363d] hover:border-gray-300'">
                                    <input type="checkbox"
                                           :checked="isDayActive(index)"
                                           @change="toggleDay(index)"
                                           class="sr-only">
                                    <span class="text-sm" :class="isDayActive(index) ? 'text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-600 dark:text-gray-400'" x-text="day"></span>
                                </label>
                            </template>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-[#30363d]">
                        <button type="button" @click="showScheduleModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#21262d] rounded-lg hover:bg-gray-200 dark:hover:bg-[#30363d] transition-colors">
                            <?= __('common.cancel')?>
                        </button>
                        <button type="submit"
                                class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                            <span x-text="editScheduleId ? __('common.update') : __('common.create')"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function bandwidthPage() {
    return {
        activeSection: 'learn',
        policies: [],
        schedules: [],
        loading: false,

        // Modals
        showPolicyModal: false,
        showScheduleModal: false,
        showBulkApplyModal: false,

        // Edit IDs
        editPolicyId: null,
        editScheduleId: null,

        // Policy form tab
        policyTab: 'general',

        // Policy form
        policyForm: {
            name: '',
            description: '',
            color: '#3B82F6',
            download_value: 5,
            download_unit: 'M',
            upload_value: 2,
            upload_unit: 'M',
            enable_burst: false,
            burst_download_value: 10,
            burst_download_unit: 'M',
            burst_upload_value: 5,
            burst_upload_unit: 'M',
            burst_threshold_download_value: 4,
            burst_threshold_download_unit: 'M',
            burst_threshold_upload_value: 2,
            burst_threshold_upload_unit: 'M',
            burst_time: 10,
            priority: 6
        },

        // Schedule form
        scheduleForm: {
            name: '',
            description: '',
            default_policy_id: '',
            scheduled_policy_id: '',
            start_time: '18:00',
            end_time: '22:00',
            active_days: 127
        },

        async init() {
            await this.loadData();
        },

        async loadData() {
            try {
                const [policiesRes, schedulesRes] = await Promise.all([
                    API.get('/bandwidth/policies'),
                    API.get('/bandwidth/schedules')
                ]);
                this.policies = policiesRes.data || [];
                this.schedules = schedulesRes.data || [];
            } catch (error) {
                console.error('Error loading data:', error);
                // Use default data if API not ready
                this.policies = [];
                this.schedules = [];
            }
        },

        // Policy methods
        resetPolicyForm() {
            this.editPolicyId = null;
            this.policyTab = 'general';
            this.policyForm = {
                name: '',
                description: '',
                color: '#3B82F6',
                download_value: 5,
                download_unit: 'M',
                upload_value: 2,
                upload_unit: 'M',
                enable_burst: false,
                burst_download_value: 10,
                burst_download_unit: 'M',
                burst_upload_value: 5,
                burst_upload_unit: 'M',
                burst_threshold_download_value: 4,
                burst_threshold_download_unit: 'M',
                burst_threshold_upload_value: 2,
                burst_threshold_upload_unit: 'M',
                burst_time: 10,
                priority: 6
            };
        },

        editPolicy(policy) {
            this.editPolicyId = policy.id;
            this.policyTab = 'general';
            const downloadParsed = this.parseSpeed(policy.download_rate);
            const uploadParsed = this.parseSpeed(policy.upload_rate);

            this.policyForm = {
                name: policy.name,
                description: policy.description || '',
                color: policy.color || '#3B82F6',
                download_value: downloadParsed.value,
                download_unit: downloadParsed.unit,
                upload_value: uploadParsed.value,
                upload_unit: uploadParsed.unit,
                enable_burst: !!policy.burst_download_rate,
                burst_download_value: policy.burst_download_rate ? this.parseSpeed(policy.burst_download_rate).value : 10,
                burst_download_unit: policy.burst_download_rate ? this.parseSpeed(policy.burst_download_rate).unit : 'M',
                burst_upload_value: policy.burst_upload_rate ? this.parseSpeed(policy.burst_upload_rate).value : 5,
                burst_upload_unit: policy.burst_upload_rate ? this.parseSpeed(policy.burst_upload_rate).unit : 'M',
                burst_threshold_download_value: policy.burst_threshold_download ? this.parseSpeed(policy.burst_threshold_download).value : 4,
                burst_threshold_download_unit: policy.burst_threshold_download ? this.parseSpeed(policy.burst_threshold_download).unit : 'M',
                burst_threshold_upload_value: policy.burst_threshold_upload ? this.parseSpeed(policy.burst_threshold_upload).value : 2,
                burst_threshold_upload_unit: policy.burst_threshold_upload ? this.parseSpeed(policy.burst_threshold_upload).unit : 'M',
                burst_time: policy.burst_time || 10,
                priority: policy.priority || 6
            };
            this.showPolicyModal = true;
        },

        async savePolicy() {
            try {
                const data = {
                    name: this.policyForm.name,
                    description: this.policyForm.description,
                    color: this.policyForm.color,
                    download_rate: this.convertToBytes(this.policyForm.download_value, this.policyForm.download_unit),
                    upload_rate: this.convertToBytes(this.policyForm.upload_value, this.policyForm.upload_unit),
                    priority: parseInt(this.policyForm.priority)
                };

                if (this.policyForm.enable_burst) {
                    data.burst_download_rate = this.convertToBytes(this.policyForm.burst_download_value, this.policyForm.burst_download_unit);
                    data.burst_upload_rate = this.convertToBytes(this.policyForm.burst_upload_value, this.policyForm.burst_upload_unit);
                    data.burst_threshold_download = this.convertToBytes(this.policyForm.burst_threshold_download_value, this.policyForm.burst_threshold_download_unit);
                    data.burst_threshold_upload = this.convertToBytes(this.policyForm.burst_threshold_upload_value, this.policyForm.burst_threshold_upload_unit);
                    data.burst_time = parseInt(this.policyForm.burst_time);
                } else {
                    data.burst_download_rate = null;
                    data.burst_upload_rate = null;
                    data.burst_threshold_download = null;
                    data.burst_threshold_upload = null;
                    data.burst_time = null;
                }

                if (this.editPolicyId) {
                    await API.put(`/bandwidth/policies/${this.editPolicyId}`, data);
                    showToast(__('bandwidth.msg_policy_updated'));
                } else {
                    await API.post('/bandwidth/policies', data);
                    showToast(__('bandwidth.msg_policy_created'));
                }

                this.showPolicyModal = false;
                await this.loadData();
            } catch (error) {
                showToast(__('bandwidth.msg_save_error') + ': ' + (error.message || ''), 'error');
            }
        },

        async deletePolicy(policy) {
            if (!confirm(__('bandwidth.confirm_delete_policy').replace(':name', policy.name))) return;
            try {
                await API.delete(`/bandwidth/policies/${policy.id}`);
                showToast(__('bandwidth.msg_policy_deleted'));
                await this.loadData();
            } catch (error) {
                showToast(__('bandwidth.msg_delete_error') + ': ' + (error.message || ''), 'error');
            }
        },

        // Schedule methods
        resetScheduleForm() {
            this.editScheduleId = null;
            this.scheduleForm = {
                name: '',
                description: '',
                default_policy_id: '',
                scheduled_policy_id: '',
                start_time: '18:00',
                end_time: '22:00',
                active_days: 127
            };
        },

        editSchedule(schedule) {
            this.editScheduleId = schedule.id;
            this.scheduleForm = {
                name: schedule.name,
                description: schedule.description || '',
                default_policy_id: schedule.default_policy_id,
                scheduled_policy_id: schedule.scheduled_policy_id,
                start_time: schedule.start_time,
                end_time: schedule.end_time,
                active_days: schedule.active_days
            };
            this.showScheduleModal = true;
        },

        async saveSchedule() {
            try {
                const data = { ...this.scheduleForm };

                if (this.editScheduleId) {
                    await API.put(`/bandwidth/schedules/${this.editScheduleId}`, data);
                    showToast(__('bandwidth.msg_schedule_updated'));
                } else {
                    await API.post('/bandwidth/schedules', data);
                    showToast(__('bandwidth.msg_schedule_created'));
                }

                this.showScheduleModal = false;
                await this.loadData();
            } catch (error) {
                showToast(__('bandwidth.msg_save_error') + ': ' + (error.message || ''), 'error');
            }
        },

        async deleteSchedule(schedule) {
            if (!confirm(__('bandwidth.confirm_delete_schedule').replace(':name', schedule.name))) return;
            try {
                await API.delete(`/bandwidth/schedules/${schedule.id}`);
                showToast(__('bandwidth.msg_schedule_deleted'));
                await this.loadData();
            } catch (error) {
                showToast(__('bandwidth.msg_delete_error') + ': ' + (error.message || ''), 'error');
            }
        },

        async toggleSchedule(schedule) {
            try {
                await API.post(`/bandwidth/schedules/${schedule.id}/toggle`);
                schedule.is_active = !schedule.is_active;
                showToast(schedule.is_active ? __('bandwidth.msg_schedule_activated') : __('bandwidth.msg_schedule_deactivated'));
            } catch (error) {
                showToast(__('bandwidth.msg_error'), 'error');
            }
        },

        // Day helpers
        isDayActive(dayIndex) {
            return (this.scheduleForm.active_days & (1 << dayIndex)) !== 0;
        },

        toggleDay(dayIndex) {
            this.scheduleForm.active_days ^= (1 << dayIndex);
        },

        formatActiveDays(bitmask) {
            const days = [__('bandwidth.day_mon'), __('bandwidth.day_tue'), __('bandwidth.day_wed'), __('bandwidth.day_thu'), __('bandwidth.day_fri'), __('bandwidth.day_sat'), __('bandwidth.day_sun')];
            const activeDays = days.filter((_, i) => (bitmask & (1 << i)) !== 0);
            if (activeDays.length === 7) return __('bandwidth.every_day');
            if (activeDays.length === 5 && !this.isDayActiveInMask(bitmask, 5) && !this.isDayActiveInMask(bitmask, 6)) return __('bandwidth.weekdays');
            return activeDays.join(', ');
        },

        isDayActiveInMask(bitmask, dayIndex) {
            return (bitmask & (1 << dayIndex)) !== 0;
        },

        // Speed helpers
        formatSpeed(bps) {
            if (!bps || bps === 0) return __('common.unlimited');
            if (bps >= 1073741824) return (bps / 1073741824).toFixed(1) + ' Gbps';
            if (bps >= 1048576) return (bps / 1048576).toFixed(1) + ' Mbps';
            if (bps >= 1024) return (bps / 1024).toFixed(0) + ' Kbps';
            return bps + ' bps';
        },

        parseSpeed(bps) {
            if (!bps || bps === 0) return { value: 0, unit: 'M' };
            if (bps >= 1073741824) return { value: Math.round(bps / 1073741824), unit: 'G' };
            if (bps >= 1048576) return { value: Math.round(bps / 1048576), unit: 'M' };
            return { value: Math.round(bps / 1024), unit: 'k' };
        },

        convertToBytes(value, unit) {
            value = parseFloat(value) || 0;
            switch (unit) {
                case 'G': return Math.round(value * 1073741824);
                case 'M': return Math.round(value * 1048576);
                case 'k': return Math.round(value * 1024);
                default: return value;
            }
        },

        // Priority helpers
        getPriorityClass(priority) {
            if (priority <= 2) return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            if (priority <= 4) return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
            if (priority <= 6) return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300';
            return 'bg-gray-100 text-gray-800 dark:bg-[#21262d] dark:text-gray-300';
        },

        getPriorityText(priority) {
            if (priority <= 2) return __('bandwidth.priority_vip') + ' (' + priority + ')';
            if (priority <= 4) return __('bandwidth.priority_high') + ' (' + priority + ')';
            if (priority <= 6) return __('bandwidth.priority_normal') + ' (' + priority + ')';
            return __('bandwidth.priority_low') + ' (' + priority + ')';
        },

        getPolicyName(policyId) {
            const policy = this.policies.find(p => p.id === policyId);
            return policy ? policy.name : 'N/A';
        }
    };
}
</script>
