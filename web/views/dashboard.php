<?php
$pageTitle = __('page.dashboard');
$currentPage = 'dashboard';

$hasHotspot = $isModuleActive('hotspot');
$hasPppoe = $isModuleActive('pppoe');

// Nombre de KPI cards visibles pour adapter la grille
$kpiCount = 4; // Sessions, Trafic, Revenu, NAS (toujours visibles)
if ($hasPppoe) $kpiCount++;
if ($hasHotspot) $kpiCount++;
$kpiGridClass = match($kpiCount) {
    6 => 'xl:grid-cols-6',
    5 => 'xl:grid-cols-5',
    default => 'xl:grid-cols-4',
};
?>

<div x-data="dashboardData()" x-init="init()">

    <!-- Welcome Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
                <?php
                    $hour = (int) date('H');
                    if ($hour < 12) $greeting = __('dashboard.greeting_morning');
                    elseif ($hour < 18) $greeting = __('dashboard.greeting_afternoon');
                    else $greeting = __('dashboard.greeting_evening');
                ?><?= $greeting ?>, <?= htmlspecialchars($currentUser->getFullName() ?? $currentUser->getUsername()) ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                <?php
                    $days_fr = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
                    $months_fr = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                    if (($_SESSION['lang'] ?? 'fr') === 'fr') {
                        echo $days_fr[(int)date('w')] . ' ' . date('d') . ' ' . $months_fr[(int)date('n')] . ' ' . date('Y');
                    } else {
                        echo date('l, F j, Y');
                    }
                ?>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($hasHotspot): ?>
            <a href="index.php?page=vouchers" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('dashboard.new_voucher') ?>
            </a>
            <?php endif; ?>
            <?php if ($hasPppoe): ?>
            <a href="index.php?page=pppoe" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg <?= $hasHotspot ? 'border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] hover:bg-gray-50 dark:hover:bg-[#30363d]' : 'bg-primary-600 text-white hover:bg-primary-700' ?> transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <?= __('dashboard.add_pppoe') ?>
            </a>
            <?php endif; ?>
            <?php if ($hasHotspot || $hasPppoe): ?>
            <a href="index.php?page=sessions" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <?= __('dashboard.view_sessions') ?>
            </a>
            <a href="index.php?page=logs" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-[#30363d] text-gray-700 dark:text-gray-300 bg-white dark:bg-[#21262d] hover:bg-gray-50 dark:hover:bg-[#30363d] transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <?= __('dashboard.view_logs') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 <?= $kpiGridClass ?> gap-3 mb-6">
        <!-- Sessions actives -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active_sessions') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="monitoring.active_sessions ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <?php if ($hasPppoe): ?>
                <span x-text="(monitoring.pppoe_sessions ?? 0) + ' PPPoE'"></span>
                <?php endif; ?>
                <?php if ($hasPppoe && $hasHotspot): ?>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <?php endif; ?>
                <?php if ($hasHotspot): ?>
                <span x-text="(monitoring.hotspot_sessions ?? 0) + ' Hotspot'"></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($hasPppoe): ?>
        <!-- Utilisateurs PPPoE -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-blue-50 dark:bg-blue-900/20">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.pppoe_users') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="pppoe.active_users ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="'/' + (pppoe.total_users ?? 0) + ' total'"></span>
                <template x-if="pppoe.suspended_users > 0">
                    <span class="text-amber-500" x-text="pppoe.suspended_users + ' ' + __('dashboard.susp')"></span>
                </template>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($hasHotspot): ?>
        <!-- Vouchers actifs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-violet-50 dark:bg-violet-900/20">
                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active_vouchers') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="stats.vouchers_by_status?.active ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="(stats.vouchers_by_status?.unused ?? 0) + ' ' + __('dashboard.avail')"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span class="text-red-400" x-text="(stats.vouchers_by_status?.expired ?? 0) + ' ' + __('dashboard.exp')"></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Trafic 24h -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-orange-50 dark:bg-orange-900/20">
                    <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.traffic_24h') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="formatBytes(monitoring.total_24h ?? 0)">0 B</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="'↓ ' + formatBytes(monitoring.download_24h ?? 0)"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span x-text="'↑ ' + formatBytes(monitoring.upload_24h ?? 0)"></span>
            </div>
        </div>

        <!-- Revenu aujourd'hui -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-emerald-50 dark:bg-emerald-900/20">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.revenue_today') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="formatMoney(billing.daily_revenue ?? 0)">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <?php if ($hasHotspot && $hasPppoe): ?>
                <span class="text-violet-500" x-text="'H: ' + formatMoney(billing.hotspot_daily_revenue ?? 0)"></span>
                <span class="text-gray-300 dark:text-gray-500">/</span>
                <span class="text-blue-500" x-text="'P: ' + formatMoney(billing.pppoe_daily_revenue ?? 0)"></span>
                <?php elseif ($hasHotspot): ?>
                <span x-text="__('dashboard.month') + ' ' + formatMoney(billing.monthly_revenue ?? 0)"></span>
                <?php elseif ($hasPppoe): ?>
                <span x-text="__('dashboard.month') + ' ' + formatMoney(billing.monthly_revenue ?? 0)"></span>
                <?php else: ?>
                <span x-text="__('dashboard.month') + ' ' + formatMoney(billing.monthly_revenue ?? 0)"></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- NAS actifs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-4 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="p-1.5 rounded-md bg-slate-50 dark:bg-slate-800/50">
                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400"><?= __('dashboard.nas_configured') ?></span>
            </div>
            <p class="text-2xl font-semibold text-gray-900 dark:text-white tracking-tight" x-text="stats.nas_count ?? 0">0</p>
            <div class="flex items-center gap-2 mt-1.5 text-2xs text-gray-400">
                <span x-text="stats.connections_today + ' ' + __('dashboard.auth_today')"></span>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 <?= $hasHotspot ? 'lg:grid-cols-3' : '' ?> gap-3 mb-6">
        <!-- Trafic réseau 7 jours -->
        <div class="<?= $hasHotspot ? 'lg:col-span-2' : '' ?> bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.network_traffic') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_7_days') ?></span>
            </div>
            <div class="relative" style="height: 240px;">
                <canvas id="trafficChart"></canvas>
            </div>
        </div>

        <?php if ($hasHotspot): ?>
        <!-- Doughnut vouchers -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.vouchers') ?></h3>
                <span class="text-2xs text-gray-400" x-text="stats.total_vouchers + ' total'"></span>
            </div>
            <div class="relative flex justify-center" style="height: 170px;">
                <canvas id="voucherChart"></canvas>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-4">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-[#30363d]"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.unused') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.unused ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.active') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.active ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.expired') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.expired ?? 0"></span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <span class="text-2xs text-gray-500 dark:text-gray-400"><?= __('dashboard.disabled') ?></span>
                    <span class="text-2xs font-medium text-gray-900 dark:text-white ml-auto" x-text="stats.vouchers_by_status?.disabled ?? 0"></span>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-6">
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.connections') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_7_days') ?></span>
            </div>
            <div class="relative" style="height: 210px;">
                <canvas id="connectionsChart"></canvas>
            </div>
        </div>

        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.hourly_traffic') ?></h3>
                <span class="text-2xs text-gray-400"><?= __('dashboard.last_24h') ?></span>
            </div>
            <div class="relative" style="height: 210px;">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-6">
        <!-- Top consommateurs -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.top_consumers') ?></h3>
                <a href="index.php?page=monitoring" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.type') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider text-right"><?= __('dashboard.total_label') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="user in topConsumers" :key="user.username">
                            <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                                <td class="py-2.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-md flex items-center justify-center text-2xs font-semibold"
                                             :class="user.type === 'pppoe' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400'"
                                             x-text="(user.customer_name || user.username).charAt(0).toUpperCase()"></div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-900 dark:text-white" x-text="user.customer_name || user.username"></p>
                                            <p class="text-2xs text-gray-400" x-text="user.username" x-show="user.customer_name"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-2.5">
                                    <span class="text-2xs font-medium px-1.5 py-0.5 rounded"
                                          :class="user.type === 'pppoe' ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/20 dark:text-emerald-400'"
                                          x-text="user.type === 'pppoe' ? 'PPPoE' : 'Hotspot'"></span>
                                </td>
                                <td class="py-2.5 text-right">
                                    <p class="text-xs font-medium text-gray-900 dark:text-white" x-text="formatBytes(user.total)"></p>
                                    <div class="flex items-center justify-end gap-1.5 text-2xs text-gray-400 mt-0.5">
                                        <span x-text="'↓' + formatBytes(user.download)"></span>
                                        <span x-text="'↑' + formatBytes(user.upload)"></span>
                                    </div>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="topConsumers.length === 0">
                            <td colspan="3" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_data') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sessions actives -->
        <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.active_sessions') ?></h3>
                <?php if ($hasHotspot || $hasPppoe): ?>
                <a href="index.php?page=sessions" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
                <?php endif; ?>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.ip') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.duration') ?></th>
                            <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider text-right"><?= __('dashboard.data') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="session in activeSessions.slice(0, 5)" :key="session.id">
                            <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                                <td class="py-2.5">
                                    <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="session.voucher_code || session.username"></span>
                                </td>
                                <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="session.client_ip || '-'"></td>
                                <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(session.session_time)"></td>
                                <td class="py-2.5 text-right text-xs text-gray-500 dark:text-gray-400" x-text="formatBytes((session.input_octets || 0) + (session.output_octets || 0))"></td>
                            </tr>
                        </template>
                        <tr x-show="activeSessions.length === 0">
                            <td colspan="4" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_active_session') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dernières connexions -->
    <div class="bg-white dark:bg-[#161b22] rounded-xl p-5 border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('dashboard.recent_connections') ?></h3>
            <?php if ($hasHotspot || $hasPppoe): ?>
            <a href="index.php?page=logs" class="text-2xs text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium"><?= __('dashboard.view_all') ?></a>
            <?php endif; ?>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-gray-100 dark:border-[#30363d]">
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.date') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.user') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.status') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.nas') ?></th>
                        <th class="pb-2.5 text-2xs font-medium text-gray-400 uppercase tracking-wider"><?= __('dashboard.mac') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="log in recentConnections" :key="log.id">
                        <tr class="border-b border-gray-50 dark:border-[#30363d]/50">
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="new Date(log.created_at).toLocaleString('fr-FR')"></td>
                            <td class="py-2.5">
                                <span class="text-xs font-medium text-gray-900 dark:text-white" x-text="log.username"></span>
                            </td>
                            <td class="py-2.5">
                                <span class="text-2xs font-medium px-1.5 py-0.5 rounded"
                                      :class="log.action === 'accept' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'"
                                      x-text="log.action === 'accept' ? __('dashboard.accepted') : __('dashboard.rejected')"></span>
                            </td>
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400" x-text="log.nas_name || log.nas_ip"></td>
                            <td class="py-2.5 text-xs text-gray-500 dark:text-gray-400 font-mono" x-text="log.client_mac || '-'"></td>
                        </tr>
                    </template>
                    <tr x-show="recentConnections.length === 0">
                        <td colspan="5" class="py-8 text-center text-xs text-gray-400"><?= __('dashboard.no_recent_connection') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Communauté -->
    <div>
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3"><?= __('dashboard.community') ?></h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- YouTube -->
            <a href="https://youtube.com/@radiusmanager" target="_blank" rel="noopener"
               class="group flex items-center gap-3 p-4 bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none hover:border-red-300 dark:hover:border-red-800 hover:shadow-md transition-all">
                <div class="p-2 rounded-lg bg-red-50 dark:bg-red-900/20 group-hover:bg-red-100 dark:group-hover:bg-red-900/30 transition-colors">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= __('dashboard.join_youtube') ?></p>
                    <p class="text-2xs text-gray-400"><?= __('dashboard.community_youtube_desc') ?></p>
                </div>
                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto flex-shrink-0 group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <!-- WhatsApp -->
            <a href="https://chat.whatsapp.com/radiusmanager" target="_blank" rel="noopener"
               class="group flex items-center gap-3 p-4 bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none hover:border-green-300 dark:hover:border-green-800 hover:shadow-md transition-all">
                <div class="p-2 rounded-lg bg-green-50 dark:bg-green-900/20 group-hover:bg-green-100 dark:group-hover:bg-green-900/30 transition-colors">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= __('dashboard.join_whatsapp') ?></p>
                    <p class="text-2xs text-gray-400"><?= __('dashboard.community_whatsapp_desc') ?></p>
                </div>
                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto flex-shrink-0 group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <!-- Telegram -->
            <a href="https://t.me/radiusmanager" target="_blank" rel="noopener"
               class="group flex items-center gap-3 p-4 bg-white dark:bg-[#161b22] rounded-xl border border-gray-200/60 dark:border-[#30363d] shadow-sm dark:shadow-none hover:border-blue-300 dark:hover:border-blue-800 hover:shadow-md transition-all">
                <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/30 transition-colors">
                    <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.479.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= __('dashboard.join_telegram') ?></p>
                    <p class="text-2xs text-gray-400"><?= __('dashboard.community_telegram_desc') ?></p>
                </div>
                <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 ml-auto flex-shrink-0 group-hover:text-gray-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>

<script>
const MODULES = {
    hotspot: <?= $hasHotspot ? 'true' : 'false' ?>,
    pppoe: <?= $hasPppoe ? 'true' : 'false' ?>
};

function dashboardData() {
    return {
        stats: {},
        pppoe: {},
        billing: {},
        monitoring: {},
        topConsumers: [],
        activeSessions: [],
        recentConnections: [],
        hourlyStats: [],
        trafficChart: null,
        voucherChart: null,
        connectionsChart: null,
        hourlyChart: null,

        async init() {
            const promises = [this.loadFullStats()];
            if (MODULES.hotspot || MODULES.pppoe) {
                promises.push(this.loadTopConsumers());
                promises.push(this.loadActiveSessions());
            }
            promises.push(this.loadRecentConnections());
            await Promise.all(promises);
            await this.loadCharts();

            setInterval(() => {
                this.loadFullStats();
                if (MODULES.hotspot || MODULES.pppoe) {
                    this.loadActiveSessions();
                    this.loadTopConsumers();
                }
            }, 30000);
        },

        async loadFullStats() {
            try {
                const response = await API.get('/dashboard/full');
                const d = response.data;
                this.stats = d.main || {};
                this.pppoe = d.pppoe || {};
                this.billing = d.billing || {};
                this.monitoring = d.monitoring || {};
            } catch (error) { console.error('Error loading stats:', error); }
        },

        async loadTopConsumers() {
            try {
                const response = await API.get('/monitoring/top-users?limit=5');
                this.topConsumers = response.data || [];
            } catch (error) { console.error('Error loading top consumers:', error); }
        },

        async loadActiveSessions() {
            try {
                const response = await API.get('/sessions/active');
                this.activeSessions = response.data || [];
            } catch (error) { console.error('Error loading sessions:', error); }
        },

        async loadRecentConnections() {
            try {
                const response = await API.get('/dashboard/recent?limit=10');
                this.recentConnections = response.data || [];
            } catch (error) { console.error('Error loading connections:', error); }
        },

        async loadCharts() {
            try {
                const promises = [
                    API.get('/dashboard/connections?days=7'),
                    API.get('/dashboard/data?days=7'),
                    API.get('/monitoring/hourly-stats'),
                ];
                const [connectionsData, dataUsage, hourlyData] = await Promise.all(promises);
                this.renderTrafficChart(dataUsage.data || []);
                if (MODULES.hotspot) this.renderVoucherChart();
                this.renderConnectionsChart(connectionsData.data || []);
                this.renderHourlyChart(hourlyData.data || []);
            } catch (error) { console.error('Error loading charts:', error); }
        },

        chartDefaults() {
            const isDark = document.documentElement.classList.contains('dark');
            return {
                gridColor: isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.04)',
                tickColor: isDark ? '#8b949e' : '#9ca3af',
                tooltipBg: isDark ? '#161b22' : '#fff',
                tooltipTitle: isDark ? '#f0f6fc' : '#111827',
                tooltipBody: isDark ? '#8b949e' : '#6b7280',
                tooltipBorder: isDark ? '#30363d' : '#f3f4f6',
                legendColor: isDark ? '#8b949e' : '#9ca3af',
            };
        },

        renderTrafficChart(data) {
            const ctx = document.getElementById('trafficChart');
            if (!ctx) return;
            if (this.trafficChart) this.trafficChart.destroy();
            const c = this.chartDefaults();

            this.trafficChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' })),
                    datasets: [
                        { label: 'Download', data: data.map(d => (d.download || 0) / (1024*1024)), borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,0.08)', fill: true, tension: 0.4, borderWidth: 1.5, pointRadius: 2, pointBackgroundColor: '#6366f1' },
                        { label: 'Upload', data: data.map(d => (d.upload || 0) / (1024*1024)), borderColor: '#a78bfa', backgroundColor: 'rgba(167,139,250,0.06)', fill: true, tension: 0.4, borderWidth: 1.5, pointRadius: 2, pointBackgroundColor: '#a78bfa' }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, padding: 10, cornerRadius: 6, callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 11 } } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 }, callback: v => v + ' MB' } }
                    }
                }
            });
        },

        renderVoucherChart() {
            const ctx = document.getElementById('voucherChart');
            if (!ctx) return;
            if (this.voucherChart) this.voucherChart.destroy();
            const c = this.chartDefaults();
            const vs = this.stats.vouchers_by_status || {};

            this.voucherChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: [__('dashboard.chart_unused'), __('dashboard.chart_active'), __('dashboard.chart_expired'), __('dashboard.chart_disabled')],
                    datasets: [{ data: [vs.unused || 0, vs.active || 0, vs.expired || 0, vs.disabled || 0], backgroundColor: ['#d1d5db', '#10b981', '#ef4444', '#f59e0b'], borderWidth: 0, hoverOffset: 3 }]
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { display: false }, tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6 } } }
            });
        },

        renderConnectionsChart(data) {
            const ctx = document.getElementById('connectionsChart');
            if (!ctx) return;
            if (this.connectionsChart) this.connectionsChart.destroy();
            const c = this.chartDefaults();

            this.connectionsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => new Date(d.date).toLocaleDateString('fr-FR', { weekday: 'short', day: 'numeric' })),
                    datasets: [
                        { label: __('dashboard.chart_accepted'), data: data.map(d => d.accepted || 0), backgroundColor: '#10b981', borderRadius: 3, barPercentage: 0.65 },
                        { label: __('dashboard.chart_rejected'), data: data.map(d => d.rejected || 0), backgroundColor: '#ef4444', borderRadius: 3, barPercentage: 0.65 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6 }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 11 } } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 } } }
                    }
                }
            });
        },

        renderHourlyChart(data) {
            const ctx = document.getElementById('hourlyChart');
            if (!ctx) return;
            if (this.hourlyChart) this.hourlyChart.destroy();
            const c = this.chartDefaults();

            this.hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.hour_num + 'h'),
                    datasets: [
                        { label: 'Download', data: data.map(d => (d.download || 0) / (1024*1024)), backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 2, barPercentage: 0.8 },
                        { label: 'Upload', data: data.map(d => (d.upload || 0) / (1024*1024)), backgroundColor: 'rgba(167,139,250,0.6)', borderRadius: 2, barPercentage: 0.8 }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { position: 'bottom', labels: { color: c.legendColor, usePointStyle: true, pointStyle: 'circle', padding: 16, font: { size: 11 } } },
                        tooltip: { backgroundColor: c.tooltipBg, titleColor: c.tooltipTitle, bodyColor: c.tooltipBody, borderColor: c.tooltipBorder, borderWidth: 1, cornerRadius: 6, callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' MB' } }
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: c.tickColor, font: { size: 10 }, maxRotation: 0 } },
                        y: { beginAtZero: true, grid: { color: c.gridColor }, ticks: { color: c.tickColor, font: { size: 11 }, callback: v => v + ' MB' } }
                    }
                }
            });
        },

        formatBytes(bytes) { return formatBytes(bytes); },
        formatTime(seconds) { return formatTime(seconds); },
        formatMoney(amount) { return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(amount || 0); }
    }
}
</script>
