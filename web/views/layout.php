<?php
// Charger l'état des modules pour le menu (scopé par admin)
$activeModules = [];
try {
    if (isset($db)) {
        $pdo = $db->getPdo();
        $layoutAdminId = isset($auth) ? $auth->getAdminId() : null;
        if ($layoutAdminId !== null) {
            $stmt = $pdo->prepare("SELECT module_code, is_active FROM modules WHERE admin_id = ?");
            $stmt->execute([$layoutAdminId]);
        } else {
            $stmt = $pdo->query("SELECT module_code, is_active FROM modules");
        }
        while ($row = $stmt->fetch()) {
            $activeModules[$row['module_code']] = (bool)$row['is_active'];
        }
    }
} catch (Exception $e) {}

$isModuleActive = function($code) use ($activeModules) {
    return $activeModules[$code] ?? false;
};

// Helper pour vérifier si l'utilisateur a accès à une page
$canAccess = function(string $page) use ($allPages, $currentUser): bool {
    if (!isset($currentUser)) return false;
    if ($currentUser->isSuperAdmin()) return true;
    $allowed = $allPages[$page] ?? [];
    return in_array($currentUser->getRole(), $allowed);
};
?>
<!DOCTYPE html>
<html lang="<?= $_SESSION['lang'] ?? 'fr' ?>" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= e($pageTitle ?? 'Dashboard') ?> -
        <?= e($appName ?? 'RADIUS Manager') ?>
    </title>

    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                            950: '#1e1b4b',
                        }
                    },
                    fontSize: {
                        '2xs': ['0.65rem', { lineHeight: '1rem' }],
                    }
                }
            }
        }
    </script>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Sidebar — Light: white/minimal, Dark: dark */
        .sidebar-bg {
            background: #ffffff;
            border-right: 1px solid #e5e7eb;
        }

        .dark .sidebar-bg {
            background: #010409;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.4375rem 0.75rem;
            margin: 1px 0.5rem;
            color: #4b5563;
            border-radius: 0.5rem;
            transition: all 0.15s ease;
            font-size: 0.8125rem;
            font-weight: 400;
            letter-spacing: 0.01em;
        }

        .dark .sidebar-link {
            color: #9ca3af;
        }

        .sidebar-link:hover {
            background: #f3f4f6;
            color: #111827;
        }

        .dark .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.07);
            color: #f3f4f6;
        }

        .sidebar-link.active {
            background: #eef2ff;
            color: #4f46e5;
            font-weight: 500;
        }

        .dark .sidebar-link.active {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
        }

        .sidebar-link svg {
            opacity: 0.45;
        }

        .sidebar-link:hover svg,
        .sidebar-link.active svg {
            opacity: 1;
        }

        .sidebar-section-title {
            padding: 1rem 0.75rem 0.375rem;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
        }

        .dark .sidebar-section-title {
            color: #4b5563;
        }

        .sidebar-divider {
            height: 1px;
            background: #f3f4f6;
            margin: 0.5rem 0.75rem;
        }

        .dark .sidebar-divider {
            background: rgba(255, 255, 255, 0.05);
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 9999px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #374151;
        }

        .dark ::-webkit-scrollbar-thumb:hover {
            background: #4b5563;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.08);
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(0, 0, 0, 0.15);
        }

        .dark .sidebar-scroll::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.08);
        }

        .dark .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        aside {
            overflow: hidden;
        }

        aside .sidebar-link {
            white-space: nowrap;
            overflow: hidden;
        }

        /* Smooth page transitions */
        main {
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toast animations */
        .toast-enter {
            animation: toastIn 0.3s cubic-bezier(0.21, 1.02, 0.73, 1) forwards;
        }

        .toast-exit {
            animation: toastOut 0.2s ease forwards;
        }

        @keyframes toastIn {
            from {
                opacity: 0;
                transform: translateY(8px) scale(0.96);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @keyframes toastOut {
            to {
                opacity: 0;
                transform: translateY(-4px) scale(0.98);
            }
        }
    </style>
</head>

<body class="h-full bg-gray-100 dark:bg-[#0d1117] font-sans antialiased"
    x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', darkMode: localStorage.getItem('darkMode') === 'true' }"
    :class="{ 'dark': darkMode }" x-init="$watch('sidebarCollapsed', v => localStorage.setItem('sidebarCollapsed', v))">

    <div class="flex h-full">
        <!-- Sidebar Mobile Overlay -->
        <div x-show="sidebarOpen" x-cloak x-transition:enter="transition-opacity ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"></div>

        <!-- Sidebar -->
        <aside :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full', sidebarCollapsed ? 'lg:w-16' : 'lg:w-60']"
            class="fixed inset-y-0 left-0 z-50 w-60 sidebar-bg transform transition-all duration-200 ease-out lg:translate-x-0 lg:static lg:inset-auto flex flex-col">

            <!-- Logo -->
            <div class="flex items-center h-14 px-4" :class="sidebarCollapsed && 'lg:justify-center lg:px-0'">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-600 flex-shrink-0">
                    <svg class="w-4.5 h-4.5 text-white" fill="none" stroke="currentColor" stroke-width="2.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                    </svg>
                </div>
                <div class="ml-2.5" :class="sidebarCollapsed && 'lg:hidden'">
                    <span class="text-sm font-semibold text-gray-900 dark:text-white tracking-tight">
                        <?= e($appName ?? 'RADIUS') ?>
                    </span>
                    <span class="block text-2xs text-gray-400 dark:text-slate-500 font-medium">Manager</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 py-2 overflow-y-auto sidebar-scroll">
                <?php if (isset($currentUser) && $currentUser->isSuperAdmin()): ?>
                <div class="sidebar-section-title text-red-600/70 dark:text-red-400/80" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.superadmin') ?? 'Super Admin' ?>
                </div>

                <a href="index.php?page=superadmin-admins"
                    class="sidebar-link <?= ($currentPage ?? '') === 'superadmin-admins' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.superadmin_admins') ?? 'Gestion Admins' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.superadmin_admins') ?? 'Gestion Admins' ?>
                    </span>
                </a>

                <a href="index.php?page=superadmin-permissions"
                    class="sidebar-link <?= ($currentPage ?? '') === 'superadmin-permissions' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.superadmin_permissions') ?? 'Rôles & Permissions' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.superadmin_permissions') ?? 'Rôles & Permissions' ?>
                    </span>
                </a>

                <!-- Sub-menu: Configuration -->
                <?php $configPages = ['superadmin-settings', 'superadmin-module-pricing', 'superadmin-sms-config']; ?>
                <div x-data="{ open: <?= in_array($currentPage ?? '', $configPages) ? 'true' : 'false' ?> }">
                    <button @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; }"
                        class="sidebar-link w-full justify-between <?= in_array($currentPage ?? '', $configPages) ? 'active' : '' ?>"
                        :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                        :title="sidebarCollapsed ? '<?= __('nav.submenu_config') ?? 'Configuration' ?>' : ''">
                        <div class="flex items-center">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span :class="sidebarCollapsed && 'lg:hidden'"><?= __('nav.submenu_config') ?? 'Configuration' ?></span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[open ? 'rotate-180' : '', sidebarCollapsed && 'lg:hidden']" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-3.5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-white/[0.06] pl-2.5">
                        <a href="index.php?page=superadmin-settings"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-settings' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                            </svg>
                            <?= __('nav.superadmin_settings') ?? 'Paramètres Globaux' ?>
                        </a>
                        <a href="index.php?page=superadmin-module-pricing"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-module-pricing' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?= __('nav.superadmin_module_pricing') ?? 'Tarification Modules' ?>
                        </a>
                        <a href="index.php?page=superadmin-sms-config"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-sms-config' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />
                            </svg>
                            <?= __('nav.superadmin_sms_config') ?? 'Config SMS (CSMS)' ?>
                        </a>
                    </div>
                </div>

                <!-- Sub-menu: Passerelles -->
                <?php $gatewayPages = ['superadmin-paygate-config', 'superadmin-recharge-gateways', 'superadmin-withdrawals']; ?>
                <div x-data="{ open: <?= in_array($currentPage ?? '', $gatewayPages) ? 'true' : 'false' ?> }">
                    <button @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; }"
                        class="sidebar-link w-full justify-between <?= in_array($currentPage ?? '', $gatewayPages) ? 'active' : '' ?>"
                        :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                        :title="sidebarCollapsed ? '<?= __('nav.submenu_gateways') ?? 'Passerelles' ?>' : ''">
                        <div class="flex items-center">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                            <span :class="sidebarCollapsed && 'lg:hidden'"><?= __('nav.submenu_gateways') ?? 'Passerelles' ?></span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[open ? 'rotate-180' : '', sidebarCollapsed && 'lg:hidden']" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-3.5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-white/[0.06] pl-2.5">
                        <a href="index.php?page=superadmin-paygate-config"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-paygate-config' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
                            </svg>
                            <?= __('nav.superadmin_paygate_config') ?? 'Config Paygate' ?>
                        </a>
                        <a href="index.php?page=superadmin-recharge-gateways"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-recharge-gateways' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                            </svg>
                            <?= __('nav.superadmin_recharge_gateways') ?? 'Recharge Crédits' ?>
                        </a>
                        <a href="index.php?page=superadmin-withdrawals"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'superadmin-withdrawals' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            <?= __('nav.superadmin_withdrawals') ?? 'Retraits Paygate' ?>
                        </a>
                    </div>
                </div>

                <a href="index.php?page=superadmin-transactions"
                    class="sidebar-link <?= ($currentPage ?? '') === 'superadmin-transactions' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.superadmin_transactions') ?? 'Transactions Crédits' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M3 4.5h14.25M3 9h9.75M3 13.5h9.75m4.5-4.5v12m0 0l-3.75-3.75M17.25 21l3.75-3.75" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.superadmin_transactions') ?? 'Transactions Crédits' ?>
                    </span>
                </a>

                <a href="index.php?page=superadmin-notifications"
                    class="sidebar-link <?= ($currentPage ?? '') === 'superadmin-notifications' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.sys_notifications') ?? 'Notifications Système' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.sys_notifications') ?? 'Notifications Système' ?>
                    </span>
                </a>

                <a href="index.php?page=radius-servers"
                    class="sidebar-link <?= ($currentPage ?? '') === 'radius-servers' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.radius_servers') ?? 'Serveurs RADIUS' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.radius_servers') ?? 'Serveurs RADIUS' ?>
                    </span>
                </a>

                <div class="sidebar-divider"></div>
                <?php endif; ?>

                <div class="sidebar-section-title" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.main') ?>
                </div>

                <a href="index.php" class="sidebar-link <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'Dashboard' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">Dashboard</span>
                </a>

                <!-- Menu Hotspot -->
                <?php if ($isModuleActive('hotspot')): ?>
                <div
                    x-data="{ open: <?= in_array($currentPage ?? '', ['vouchers', 'profiles', 'sessions', 'voucher-templates', 'hotspot-templates', 'captive-portal']) ? 'true' : 'false' ?> }">
                    <button
                        @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; }"
                        class="sidebar-link w-full justify-between <?= in_array($currentPage ?? '', ['vouchers', 'profiles', 'sessions', 'voucher-templates', 'hotspot-templates', 'captive-portal']) ? 'active' : '' ?>"
                        :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                        :title="sidebarCollapsed ? 'Hotspot' : ''">
                        <div class="flex items-center">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                            <span :class="sidebarCollapsed && 'lg:hidden'">Hotspot</span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[open ? 'rotate-180' : '', sidebarCollapsed && 'lg:hidden']" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-3.5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-white/[0.06] pl-2.5">
                        <a href="index.php?page=vouchers"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'vouchers' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" />
                            </svg>
                            <?= __('nav.vouchers') ?>
                        </a>
                        <?php if ($canAccess('profiles')): ?>
                        <a href="index.php?page=profiles"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'profiles' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <?= __('nav.profiles') ?>
                        </a>
                        <?php endif; ?>
                        <a href="index.php?page=sessions"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'sessions' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <?= __('nav.sessions') ?>
                        </a>
                        <?php if ($canAccess('voucher-templates')): ?>
                        <a href="index.php?page=voucher-templates"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'voucher-templates' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                            <?= __('nav.voucher_templates') ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($canAccess('hotspot-templates')): ?>
                        <a href="index.php?page=hotspot-templates"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'hotspot-templates' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                            </svg>
                            <?= __('nav.hotspot_templates') ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($isModuleActive('captive-portal') && $canAccess('captive-portal')): ?>
                        <a href="index.php?page=captive-portal"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'captive-portal' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <?= __('nav.captive_portal') ?? 'Portail captif' ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu PPPoE -->
                <?php if ($isModuleActive('pppoe')): ?>
                <div
                    x-data="{ open: <?= in_array($currentPage ?? '', ['pppoe', 'network', 'billing', 'pppoe-transactions', 'pppoe-reminders']) ? 'true' : 'false' ?> }">
                    <button
                        @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; }"
                        class="sidebar-link w-full justify-between <?= in_array($currentPage ?? '', ['pppoe', 'network', 'billing', 'pppoe-transactions', 'pppoe-reminders']) ? 'active' : '' ?>"
                        :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                        :title="sidebarCollapsed ? 'PPPoE' : ''">
                        <div class="flex items-center">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <span :class="sidebarCollapsed && 'lg:hidden'">PPPoE</span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[open ? 'rotate-180' : '', sidebarCollapsed && 'lg:hidden']" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-3.5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-white/[0.06] pl-2.5">
                        <a href="index.php?page=pppoe"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'pppoe' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <?= __('nav.pppoe_clients') ?>
                        </a>
                        <a href="index.php?page=network"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'network' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            <?= __('nav.ip_pools') ?>
                        </a>
                        <?php if ($canAccess('billing')): ?>
                        <a href="index.php?page=billing"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'billing' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <?= __('nav.billing') ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($canAccess('pppoe-transactions')): ?>
                        <a href="index.php?page=pppoe-transactions"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'pppoe-transactions' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?= __('nav.transactions') ?>
                        </a>
                        <?php endif; ?>
                        <?php if ($canAccess('pppoe-reminders')): ?>
                        <a href="index.php?page=pppoe-reminders"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'pppoe-reminders' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <?= __('nav.pppoe_reminders') ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Menu Réseau -->
                <div
                    x-data="{ open: <?= in_array($currentPage ?? '', ['zones', 'nas', 'bandwidth', 'monitoring']) ? 'true' : 'false' ?> }">
                    <button
                        @click="if(sidebarCollapsed) { sidebarCollapsed = false; open = true; } else { open = !open; }"
                        class="sidebar-link w-full justify-between <?= in_array($currentPage ?? '', ['zones', 'nas', 'bandwidth', 'monitoring']) ? 'active' : '' ?>"
                        :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                        :title="sidebarCollapsed ? '<?= __('nav.network') ?>' : ''">
                        <div class="flex items-center">
                            <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                            <span :class="sidebarCollapsed && 'lg:hidden'">
                                <?= __('nav.network') ?>
                            </span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200"
                            :class="[open ? 'rotate-180' : '', sidebarCollapsed && 'lg:hidden']" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open && !sidebarCollapsed" x-collapse
                        class="ml-3.5 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-white/[0.06] pl-2.5">
                        <?php if ($canAccess('zones')): ?>
                        <a href="index.php?page=zones"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'zones' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <?= __('nav.zones') ?>
                        </a>
                        <?php endif; ?>
                        <a href="index.php?page=nas"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'nas' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                            </svg>
                            <?= __('nav.nas') ?>
                        </a>
                        <a href="index.php?page=router-commands"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'router-commands' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                            </svg>
                            <?= __('nav.router_commands') ?? 'Commandes Routeur' ?>
                        </a>
                        <?php if ($canAccess('bandwidth')): ?>
                        <a href="index.php?page=bandwidth"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'bandwidth' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <?= __('nav.bandwidth') ?>
                        </a>
                        <?php endif; ?>
                        <a href="index.php?page=monitoring"
                            class="sidebar-link text-xs <?= ($currentPage ?? '') === 'monitoring' ? 'active' : '' ?>">
                            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <?= __('nav.monitoring') ?>
                        </a>
                    </div>
                </div>

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.payments') ?>
                </div>

                <?php if ($isModuleActive('hotspot') && $canAccess('transactions')): ?>
                <a href="index.php?page=transactions"
                    class="sidebar-link <?= ($currentPage ?? '') === 'transactions' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'Transactions' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.transactions') ?>
                    </span>
                </a>

                <?php if ($canAccess('sales')): ?>
                <a href="index.php?page=sales"
                    class="sidebar-link <?= ($currentPage ?? '') === 'sales' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.sales_report') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.sales_report') ?>
                    </span>
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <?php if ($canAccess('payments')): ?>
                <a href="index.php?page=payments"
                    class="sidebar-link <?= ($currentPage ?? '') === 'payments' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.gateways') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.gateways') ?>
                    </span>
                </a>
                <?php endif; ?>

                <?php if ($isModuleActive('loyalty') && $canAccess('loyalty')): ?>
                <a href="index.php?page=loyalty"
                    class="sidebar-link <?= ($currentPage ?? '') === 'loyalty' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.loyalty') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.loyalty') ?>
                    </span>
                </a>
                <?php endif; ?>

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.content') ?>
                </div>

                <?php if ($canAccess('library')): ?>
                <a href="index.php?page=library"
                    class="sidebar-link <?= ($currentPage ?? '') === 'library' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.library') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.library') ?>
                    </span>
                </a>
                <?php endif; ?>

                <?php if ($isModuleActive('hotspot') && $canAccess('logs')): ?>
                <a href="index.php?page=logs"
                    class="sidebar-link <?= ($currentPage ?? '') === 'logs' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.logs') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.logs') ?>
                    </span>
                </a>
                <?php endif; ?>

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.communication') ?>
                </div>

                <?php if ($isModuleActive('chat') && $canAccess('chat')): ?>
                <a href="index.php?page=chat"
                    class="sidebar-link <?= ($currentPage ?? '') === 'chat' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'Chat' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.chat') ?>
                    </span>
                    <span x-data="{ unread: 0 }" x-init="
                        setInterval(async () => {
                            try {
                                const res = await fetch('api.php?route=/chat/conversations/unread-count');
                                const data = await res.json();
                                if(data.success) unread = data.data?.unread_count || 0;
                            } catch(e) {}
                        }, 30000);
                        (async () => {
                            try {
                                const res = await fetch('api.php?route=/chat/conversations/unread-count');
                                const data = await res.json();
                                if(data.success) unread = data.data?.unread_count || 0;
                            } catch(e) {}
                        })();
                    " x-show="unread > 0 && !sidebarCollapsed" x-cloak
                        class="ml-auto bg-red-500 text-white text-2xs font-bold px-1.5 py-0.5 rounded-full min-w-[18px] text-center"
                        x-text="unread"></span>
                </a>
                <?php endif; ?>

                <?php if ($isModuleActive('telegram') && $canAccess('telegram')): ?>
                <a href="index.php?page=telegram"
                    class="sidebar-link <?= ($currentPage ?? '') === 'telegram' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'Telegram' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">Telegram</span>
                </a>
                <?php endif; ?>

                <?php if ($isModuleActive('whatsapp') && $canAccess('whatsapp')): ?>
                <a href="index.php?page=whatsapp"
                    class="sidebar-link <?= ($currentPage ?? '') === 'whatsapp' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'WhatsApp' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">WhatsApp</span>
                </a>
                <?php endif; ?>

                <?php if ($isModuleActive('sms') && $canAccess('sms')): ?>
                <a href="index.php?page=sms" class="sidebar-link <?= ($currentPage ?? '') === 'sms' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'SMS' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">SMS</span>
                </a>

                <a href="index.php?page=otp" class="sidebar-link <?= ($currentPage ?? '') === 'otp' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'OTP' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">OTP</span>
                </a>

                <a href="index.php?page=marketing"
                    class="sidebar-link <?= ($currentPage ?? '') === 'marketing' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? 'Marketing' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">Marketing</span>
                </a>
                <?php endif; ?>

                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title" :class="sidebarCollapsed && 'lg:hidden'">
                    <?= __('nav.section.system') ?>
                </div>

                <?php if ($canAccess('modules')): ?>
                <a href="index.php?page=modules"
                    class="sidebar-link <?= ($currentPage ?? '') === 'modules' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.modules') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">Modules</span>
                </a>
                <?php endif; ?>

                <?php if ($canAccess('subscription')): ?>
                <a href="index.php?page=subscription"
                    class="sidebar-link <?= ($currentPage ?? '') === 'subscription' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.subscription') ?? 'Abonnement' ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.subscription') ?? 'Abonnement' ?>
                    </span>
                </a>
                <?php endif; ?>

                <?php if ($canAccess('users')): ?>
                <a href="index.php?page=users"
                    class="sidebar-link <?= ($currentPage ?? '') === 'users' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.users') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.users') ?>
                    </span>
                </a>
                <?php endif; ?>

                <?php if ($canAccess('settings')): ?>
                <a href="index.php?page=settings"
                    class="sidebar-link <?= ($currentPage ?? '') === 'settings' ? 'active' : '' ?>"
                    :class="sidebarCollapsed && 'lg:justify-center lg:mx-1 lg:px-0'"
                    :title="sidebarCollapsed ? '<?= __('nav.settings') ?>' : ''">
                    <svg class="w-[18px] h-[18px] flex-shrink-0" :class="!sidebarCollapsed && 'mr-2.5'" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span :class="sidebarCollapsed && 'lg:hidden'">
                        <?= __('nav.settings') ?>
                    </span>
                </a>
                <?php endif; ?>
            </nav>



        </aside>


        <!-- Help Documentation Drawer -->
        <div x-data="helpPanel(<?= htmlspecialchars(json_encode($currentPageDoc ?? null, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)"
            @toggle-help.window="open = !open" @keydown.escape.window="open = false">

            <!-- Overlay -->
            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="open = false" class="fixed inset-0 bg-black/30 z-[60]"
                style="display:none;"></div>

            <!-- Panel -->
            <div x-show="open" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="fixed inset-y-0 right-0 w-full max-w-sm z-[61] flex flex-col bg-white dark:bg-[#161b22] border-l border-gray-200 dark:border-[#30363d] shadow-2xl"
                style="display:none;">

                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-[#30363d]">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600 dark:text-primary-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                            <?= __('help.title') ?>
                        </h2>
                    </div>
                    <button @click="open = false"
                        class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <template x-if="doc">
                        <div class="space-y-5">
                            <!-- Page title -->
                            <div>
                                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100" x-text="doc.title">
                                </h3>
                            </div>

                            <!-- Description -->
                            <div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed"
                                    x-text="doc.description"></p>
                            </div>

                            <!-- Features -->
                            <template x-if="doc.features && doc.features.length > 0">
                                <div>
                                    <h4
                                        class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-2.5">
                                        <?= __('help.features') ?>
                                    </h4>
                                    <ul class="space-y-2">
                                        <template x-for="(feature, i) in doc.features" :key="i">
                                            <li class="flex items-start gap-2">
                                                <svg class="w-4 h-4 text-emerald-500 flex-shrink-0 mt-0.5" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span class="text-sm text-gray-600 dark:text-gray-400"
                                                    x-text="feature"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </template>

                            <!-- Tips -->
                            <template x-if="doc.tips">
                                <div
                                    class="rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/40 p-3.5">
                                    <div class="flex items-start gap-2.5">
                                        <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        <div>
                                            <p class="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-0.5">
                                                <?= __('help.tips') ?>
                                            </p>
                                            <p class="text-sm text-blue-600 dark:text-blue-300" x-text="doc.tips"></p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- YouTube button -->
                            <template x-if="doc.youtube_url && doc.youtube_url.length > 0">
                                <div class="pt-2">
                                    <a :href="doc.youtube_url" target="_blank" rel="noopener noreferrer"
                                        class="flex items-center justify-center gap-2.5 w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm transition-colors shadow-sm">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                                        </svg>
                                        <?= __('help.watch_video') ?>
                                    </a>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- No documentation -->
                    <template x-if="!doc">
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div
                                class="w-12 h-12 rounded-full bg-gray-100 dark:bg-[#21262d] flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                <?= __('help.no_doc') ?>
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Top bar -->
            <header
                class="h-14 bg-white/80 dark:bg-[#161b22] backdrop-blur-md border-b border-gray-200/60 dark:border-[#30363d] flex items-center px-4 lg:px-6 sticky top-0 z-30">
                <!-- Mobile menu -->
                <button @click="sidebarOpen = true"
                    class="lg:hidden p-1.5 -ml-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Desktop sidebar toggle -->
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                    class="hidden lg:flex p-1.5 -ml-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-md hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page title -->
                <h1 class="text-sm font-semibold text-gray-900 dark:text-gray-100 ml-3 tracking-tight truncate max-w-[120px] sm:max-w-none">
                    <?= e($pageTitle ?? 'Dashboard') ?>
                </h1>

                <div class="flex-1"></div>

                <?php if (isset($currentUser) && $currentUser->isAdmin() && !$currentUser->isSuperAdmin()): ?>
                <!-- Credit Balance Badge -->
                <div x-data="creditTopBar()" class="flex items-center mr-1 sm:mr-2">
                    <div
                        class="flex items-center gap-1 sm:gap-1.5 bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border border-amber-200/60 dark:border-amber-700/40 rounded-lg px-1.5 sm:px-2.5 py-1 sm:py-1.5">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-[10px] sm:text-xs font-bold text-amber-700 dark:text-amber-300 tabular-nums"
                            x-text="creditBalance"></span>
                        <span class="text-[9px] sm:text-[10px] text-amber-500 dark:text-amber-400 font-medium hidden sm:inline">CRT</span>
                        <button @click="openRechargeModal()"
                            class="ml-0.5 sm:ml-1 w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center bg-amber-500 hover:bg-amber-600 text-white rounded-md transition-colors text-[10px] sm:text-xs font-bold"
                            title="<?= __('credits.recharge') ?? 'Recharger' ?>">+</button>
                    </div>

                    <!-- Recharge Modal -->
                    <template x-teleport="body">
                        <div x-show="showModal" x-cloak
                            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                            @keydown.escape.window="showModal = false">
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
                            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-md overflow-hidden"
                                @click.stop>
                                <!-- Header -->
                                <div class="px-6 py-4 bg-gradient-to-r from-amber-500 to-yellow-500 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-bold">
                                                <?= __('credits.recharge_title') ?? 'Recharger mes crédits' ?>
                                            </h3>
                                            <p class="text-sm text-amber-100 mt-0.5">
                                                <?= __('credits.current_balance') ?? 'Solde actuel' ?>: <span
                                                    class="font-bold" x-text="creditBalance + ' CRT'"></span>
                                            </p>
                                        </div>
                                        <button @click="showModal = false"
                                            class="p-1 hover:bg-white/20 rounded-lg transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 1: Amount -->
                                <div x-show="step === 1" class="p-6">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?= __('credits.amount_label') ?? 'Montant à payer' ?> (<span
                                            x-text="currency"></span>)
                                    </label>
                                    <input type="number" x-model="amount" min="100" step="100"
                                        class="w-full px-4 py-3 text-lg font-bold text-center border-2 border-gray-200 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                                        placeholder="1000" @keydown.enter="goToStep2()">

                                    <!-- Quick amounts -->
                                    <div class="flex gap-2 mt-3">
                                        <template x-for="q in quickAmounts" :key="q">
                                            <button @click="amount = q" type="button"
                                                class="flex-1 py-2 text-xs font-medium rounded-lg border transition-colors"
                                                :class="amount == q ? 'bg-amber-500 text-white border-amber-500' : 'bg-gray-50 dark:bg-[#0d1117] text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-600 hover:border-amber-300'">
                                                <span x-text="q.toLocaleString()"></span>
                                            </button>
                                        </template>
                                    </div>

                                    <!-- Credits preview -->
                                    <div x-show="amount > 0"
                                        class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200/50 dark:border-amber-700/30">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm text-amber-700 dark:text-amber-300">
                                                <?= __('credits.you_will_receive') ?? 'Vous recevrez' ?>
                                            </span>
                                            <span class="text-lg font-bold text-amber-600 dark:text-amber-400"
                                                x-text="creditsPreview + ' CRT'"></span>
                                        </div>
                                        <p class="text-[10px] text-amber-500 mt-1">
                                            <?= __('credits.exchange_info') ?? 'Taux' ?>: <span
                                                x-text="exchangeRate"></span> <span x-text="currency"></span> = 1 CRT
                                        </p>
                                    </div>

                                    <button @click="goToStep2()" :disabled="!amount || amount <= 0 || loadingGateways"
                                        class="w-full mt-4 py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                        <span x-show="!loadingGateways">
                                            <?= __('credits.next_step') ?? 'Suivant' ?>
                                        </span>
                                        <span x-show="loadingGateways">
                                            <?= __('common.loading') ?? 'Chargement...' ?>
                                        </span>
                                        <svg x-show="!loadingGateways" class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Step 2: Gateway selection -->
                                <div x-show="step === 2" class="p-6">
                                    <button @click="step = 1"
                                        class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 mb-3 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 19l-7-7 7-7" />
                                        </svg>
                                        <?= __('common.back') ?? 'Retour' ?>
                                    </button>

                                    <div
                                        class="mb-4 p-3 bg-gray-50 dark:bg-[#0d1117] rounded-xl border border-gray-200 dark:border-gray-600 flex items-center justify-between">
                                        <span class="text-sm text-gray-500">
                                            <?= __('credits.amount_to_pay') ?? 'Montant' ?>
                                        </span>
                                        <span class="font-bold text-gray-900 dark:text-gray-100"><span
                                                x-text="Number(amount).toLocaleString()"></span> <span
                                                x-text="currency"></span> → <span x-text="creditsPreview"
                                                class="text-amber-600 dark:text-amber-400"></span> CRT</span>
                                    </div>

                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        <?= __('credits.select_gateway') ?? 'Choisir le mode de paiement' ?>
                                    </label>

                                    <div class="space-y-2 max-h-60 overflow-y-auto">
                                        <template x-for="gw in gateways" :key="gw.gateway_code">
                                            <button @click="selectedGateway = gw.gateway_code" type="button"
                                                class="w-full flex items-center gap-3 p-3 rounded-xl border-2 transition-all text-left"
                                                :class="selectedGateway === gw.gateway_code
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                    : 'border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500'">
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                                    :class="{
                                                        'bg-blue-600': gw.gateway_code === 'fedapay',
                                                        'bg-orange-500': gw.gateway_code === 'cinetpay',
                                                        'bg-green-600': gw.gateway_code === 'ligdicash',
                                                        'bg-purple-600': gw.gateway_code === 'cryptomus'
                                                    }" x-text="gw.name.substring(0, 2).toUpperCase()"></div>
                                                <div class="min-w-0">
                                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                                                        x-text="gw.name"></div>
                                                    <div class="text-xs text-gray-500 truncate" x-text="gw.description">
                                                    </div>
                                                </div>
                                                <svg x-show="selectedGateway === gw.gateway_code"
                                                    class="w-5 h-5 text-amber-500 ml-auto flex-shrink-0"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="gateways.length === 0 && !loadingGateways"
                                        class="text-center py-6 text-sm text-gray-500">
                                        <?= __('credits.no_gateways') ?? 'Aucun moyen de paiement disponible' ?>
                                    </div>

                                    <button @click="submitRecharge()" :disabled="!selectedGateway || processing"
                                        class="w-full mt-4 py-3 px-4 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                        <svg x-show="processing" class="w-4 h-4 animate-spin" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        <span
                                            x-text="processing ? '<?= __('credits.processing') ?? 'Traitement en cours...' ?>' : '<?= __('credits.pay_now') ?? 'Payer maintenant' ?>'"></span>
                                    </button>

                                    <p x-show="errorMsg" class="mt-2 text-xs text-red-500 text-center"
                                        x-text="errorMsg"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <?php endif; ?>

                <?php if (isset($currentUser) && $currentUser->isAdmin() && !$currentUser->isSuperAdmin() && ($isModuleActive('sms') ?? false)): ?>
                <!-- SMS Credit Balance Badge -->
                <div x-data="smsCreditTopBar()" class="flex items-center mr-1 sm:mr-2">
                    <div
                        class="flex items-center gap-1 sm:gap-1.5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200/60 dark:border-blue-700/40 rounded-lg px-1.5 sm:px-2.5 py-1 sm:py-1.5">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <span class="text-[10px] sm:text-xs font-bold text-blue-700 dark:text-blue-300 tabular-nums"
                            x-text="smsBalance"></span>
                        <span class="text-[9px] sm:text-[10px] text-blue-500 dark:text-blue-400 font-medium hidden sm:inline">CSMS</span>
                        <button @click="openConvertModal()"
                            class="ml-0.5 sm:ml-1 w-4 h-4 sm:w-5 sm:h-5 flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white rounded-md transition-colors text-[10px] sm:text-xs font-bold"
                            title="<?= __('sms_credits.convert_title') ?? 'Convertir CRT en CSMS' ?>">
                            <svg class="w-2.5 h-2.5 sm:w-3 sm:h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                        </button>
                    </div>

                    <!-- Conversion Modal CRT → CSMS -->
                    <template x-teleport="body">
                        <div x-show="showConvertModal" x-cloak
                            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
                            @keydown.escape.window="showConvertModal = false">
                            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showConvertModal = false">
                            </div>
                            <div class="relative bg-white dark:bg-[#161b22] rounded-2xl shadow-2xl border border-gray-200 dark:border-[#30363d] w-full max-w-sm overflow-hidden"
                                @click.stop>
                                <!-- Header -->
                                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-500 text-white">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h3 class="text-lg font-bold">
                                                <?= __('sms_credits.convert_title') ?? 'Convertir CRT en CSMS' ?>
                                            </h3>
                                            <p class="text-sm text-blue-100 mt-0.5">
                                                <?= __('sms_credits.balance') ?? 'Solde CSMS' ?>: <span
                                                    class="font-bold" x-text="smsBalance + ' CSMS'"></span>
                                            </p>
                                        </div>
                                        <button @click="showConvertModal = false"
                                            class="text-white/80 hover:text-white transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <!-- Body -->
                                <div class="p-6 space-y-4">
                                    <!-- Info taux -->
                                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 text-center">
                                        <p class="text-xs text-blue-600 dark:text-blue-400">
                                            1 CRT = <span class="font-bold" x-text="csmsPerCrt"></span> CSMS
                                            <span class="text-blue-400 dark:text-blue-500">(1 SMS = <span
                                                    x-text="costPerSmsFcfa"></span> FCFA)</span>
                                        </p>
                                    </div>

                                    <!-- Input CRT -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            <?= __('sms_credits.crt_amount') ?? 'Montant en CRT' ?>
                                        </label>
                                        <input type="number" x-model="crtAmount" min="0.01" step="0.01"
                                            class="w-full px-4 py-3 text-lg font-bold text-center border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-[#0d1117] text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            placeholder="0">
                                    </div>

                                    <!-- Quick amounts -->
                                    <div class="flex gap-2 justify-center">
                                        <template x-for="q in [1, 5, 10, 25]" :key="q">
                                            <button @click="crtAmount = q" type="button"
                                                class="px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                                                :class="crtAmount == q
                                                    ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-600'
                                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                                                x-text="q + ' CRT'"></button>
                                        </template>
                                    </div>

                                    <!-- Preview -->
                                    <div x-show="crtAmount > 0"
                                        class="bg-gray-50 dark:bg-[#0d1117] rounded-xl p-4 text-center">
                                        <p class="text-xs text-gray-500 mb-1">
                                            <?= __('sms_credits.preview') ?? 'Vous recevrez' ?>
                                        </p>
                                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"
                                            x-text="csmsPreview"></p>
                                        <p class="text-sm text-gray-500">CSMS</p>
                                        <p class="text-xs text-gray-400 mt-1">
                                            <span x-text="crtAmount"></span> CRT →
                                            <span x-text="csmsPreview"></span> SMS
                                        </p>
                                    </div>

                                    <!-- Error -->
                                    <p x-show="convertError" class="text-xs text-red-500 text-center"
                                        x-text="convertError"></p>

                                    <!-- Submit -->
                                    <button @click="submitConversion()"
                                        :disabled="!crtAmount || crtAmount <= 0 || csmsPreview < 1 || converting"
                                        class="w-full py-3 px-4 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                                        <svg x-show="converting" class="w-4 h-4 animate-spin" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4" />
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                        </svg>
                                        <span
                                            x-text="converting ? '<?= __('sms_credits.converting') ?? 'Conversion en cours...' ?>' : '<?= __('sms_credits.convert_btn') ?? 'Convertir' ?>'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                <?php endif; ?>

                <!-- Header actions -->
                <div class="flex items-center gap-0.5 sm:gap-1">
                    <!-- Language Switcher -->
                    <div class="relative mr-1 sm:mr-2 hidden sm:block"
                        x-data="{ open: false, currentLang: '<?= $_SESSION['lang'] ?? 'fr' ?>' }">
                        <button @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium bg-gray-100 hover:bg-gray-200 dark:bg-[#21262d] dark:hover:bg-[#30363d] text-gray-700 dark:text-gray-200 rounded-lg transition-colors">
                            <span x-show="currentLang === 'fr'" class="flex items-center gap-2">🇫🇷 FR</span>
                            <span x-show="currentLang === 'en'" class="flex items-center gap-2" x-cloak>🇬🇧 EN</span>
                            <svg class="w-4 h-4 text-gray-500 transition-transform duration-200"
                                :class="{'rotate-180': open}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95" x-cloak
                            class="absolute right-0 mt-2 w-32 bg-white dark:bg-[#161b22] border border-gray-200 dark:border-[#30363d] rounded-xl shadow-lg z-50 overflow-hidden">
                            <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'fr'])) ?>"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                                :class="{'bg-gray-50 dark:bg-[#21262d] font-semibold': currentLang === 'fr'}">
                                🇫🇷 FR
                            </a>
                            <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'en'])) ?>"
                                class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                                :class="{'bg-gray-50 dark:bg-[#21262d] font-semibold': currentLang === 'en'}">
                                🇬🇧 EN
                            </a>
                        </div>
                    </div>

                    <!-- Documentation & YouTube Links -->
                    <div class="hidden sm:flex items-center gap-1 border-l border-gray-200 dark:border-gray-700 pl-2 ml-1">
                        <!-- Documentation -->
                        <button @click="$dispatch('toggle-help')"
                            class="p-2 text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors"
                            title="<?= __('nav.documentation') ?? 'Documentation' ?>">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </button>
                        <!-- YouTube -->
                        <?php
                        $docData = require __DIR__ . '/../../config/documentation.php';
                        $currentPageDoc = $docData[$currentPage ?? 'dashboard'] ?? null;
                        ?>
                        <a href="<?= !empty($currentPageDoc['youtube_url']) ? htmlspecialchars($currentPageDoc['youtube_url']) : 'https://www.youtube.com/@csabordsuite' ?>" target="_blank" rel="noopener noreferrer"
                            class="p-2 text-gray-400 hover:text-red-500 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors"
                            title="<?= __('nav.tutorials') ?? 'Tutoriels YouTube' ?>">
                            <svg class="w-[18px] h-[18px]" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
                            </svg>
                        </a>
                    </div>

                    <!-- Notifications -->
                    <div class="relative" x-data="topMenuNotifications()" @click.outside="open = false">
                        <button @click="open = !open"
                            class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors relative"
                            title="<?= __('nav.notifications') ?? 'Notifications' ?>">
                            <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span x-show="unread > 0" x-cloak class="absolute top-2 right-2 flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span
                                    class="relative inline-flex rounded-full h-2 w-2 bg-red-500 border border-white dark:border-[#161b22]"></span>
                            </span>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95" x-cloak
                            class="absolute right-0 mt-2 w-80 bg-white dark:bg-[#161b22] border border-gray-200 dark:border-[#30363d] rounded-xl shadow-lg z-50 overflow-hidden">
                            <div
                                class="px-4 py-3 border-b border-gray-100 dark:border-[#30363d] flex items-center justify-between bg-gray-50/50 dark:bg-[#21262d]/50">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    <?= __('nav.notifications') ?? 'Notifications' ?>
                                </h3>
                                <button x-show="unread > 0" @click="markAllAsRead()"
                                    class="text-xs text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 font-medium transition-colors">
                                    <?= __('nav.mark_all_read') ?? 'Tout marquer comme lu' ?>
                                </button>
                            </div>
                            <div class="max-h-[300px] overflow-y-auto">
                                <template x-for="notification in notifications" :key="notification.id">
                                    <div class="px-4 py-3 border-b border-gray-100 dark:border-[#30363d] cursor-pointer hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors"
                                        :class="{'bg-blue-50/30 dark:bg-blue-900/10': !notification.is_read}"
                                        @click="markAsRead(notification)">
                                        <div class="flex gap-3">
                                            <div class="shrink-0 mt-0.5">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                                                    :class="{
                                                         'bg-blue-100 text-blue-600': notification.type === 'info',
                                                         'bg-yellow-100 text-yellow-600': notification.type === 'warning',
                                                         'bg-green-100 text-green-600': notification.type === 'success',
                                                         'bg-red-100 text-red-600': notification.type === 'error'
                                                     }">
                                                    <svg x-show="notification.type === 'info'" class="w-4 h-4"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    <svg x-show="notification.type === 'success'" class="w-4 h-4"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    <svg x-show="notification.type === 'warning'" class="w-4 h-4"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                    </svg>
                                                    <svg x-show="notification.type === 'error'" class="w-4 h-4"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100"
                                                    x-text="notification.title"></p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2"
                                                    x-text="notification.message"></p>
                                                <p class="text-[10px] text-gray-400 mt-1"
                                                    x-text="formatDate(notification.created_at)"></p>
                                            </div>
                                            <div class="shrink-0" x-show="!notification.is_read">
                                                <span
                                                    class="inline-block w-2 h-2 bg-blue-500 rounded-full mt-1.5"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div class="px-4 py-8 text-center" x-show="notifications.length === 0" x-cloak>
                                    <div
                                        class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-[#21262d] mb-3">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?= __('nav.no_notifications') ?? 'Aucune notification' ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        <?= __('nav.no_notifications_desc') ?? 'Vous n\'avez pas de nouvelles notifications pour le moment.' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="border-t border-gray-100 dark:border-[#30363d] p-3 text-center"
                                x-show="selectedNotification" x-cloak>
                                <div class="text-left bg-gray-50 dark:bg-[#21262d] p-3 rounded-xl">
                                    <div class="font-bold text-sm text-gray-900 dark:text-white"
                                        x-text="selectedNotification?.title"></div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1"
                                        x-text="selectedNotification?.message"></div>
                                </div>
                                <button @click="selectedNotification = null"
                                    class="mt-3 text-xs text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    <?= __('common.close_details') ?? 'Fermer les détails' ?>
                                </button>
                            </div>
                            <a href="#"
                                class="block px-4 py-2 text-center text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-[#21262d] hover:text-gray-800 dark:hover:text-gray-200 transition-colors border-t border-gray-100 dark:border-[#30363d]">
                                <?= __('nav.view_all_notifications') ?? 'Voir toutes les notifications' ?>
                            </a>
                        </div>
                    </div>
                    <!-- Dark mode -->
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)"
                        class="p-1.5 sm:p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors">
                        <svg x-show="!darkMode" class="w-[18px] h-[18px]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-[18px] h-[18px]" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    <!-- Profile Menu -->
                    <div class="relative border-l border-gray-200 dark:border-gray-700 pl-1 sm:pl-2 ml-0.5 sm:ml-1" x-data="{ openProfile: false }" @click.outside="openProfile = false">
                        <button @click="openProfile = !openProfile"
                            class="flex items-center gap-2 p-1 sm:p-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-[#21262d] transition-colors"
                            title="<?= __('nav.profile') ?>">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center text-white text-xs font-bold shadow-sm">
                                <?= strtoupper(substr($currentUser->getFullName() ?? $currentUser->getUsername(), 0, 1)) ?>
                            </div>
                        </button>

                        <div x-show="openProfile" x-cloak
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="transform opacity-0 scale-95"
                             x-transition:enter-end="transform opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="transform opacity-100 scale-100"
                             x-transition:leave-end="transform opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-56 bg-white dark:bg-[#161b22] border border-gray-200 dark:border-[#30363d] rounded-xl shadow-lg z-50 overflow-hidden">
                            <!-- User info -->
                            <div class="px-4 py-3 border-b border-gray-100 dark:border-[#30363d] bg-gray-50/50 dark:bg-[#21262d]/50">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">
                                    <?= htmlspecialchars($currentUser->getFullName() ?? $currentUser->getUsername()) ?>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                    <?= htmlspecialchars($currentUser->getEmail()) ?>
                                </p>
                            </div>
                            <!-- Menu items -->
                            <div class="py-1">
                                <a href="index.php?page=settings"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <?= __('nav.my_profile') ?>
                                </a>
                                <!-- Language switcher (mobile only) -->
                                <div class="sm:hidden border-t border-gray-100 dark:border-[#30363d] py-1">
                                    <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'fr'])) ?>"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors <?= ($_SESSION['lang'] ?? 'fr') === 'fr' ? 'font-semibold bg-gray-50 dark:bg-[#21262d]' : '' ?>">
                                        <span class="w-4 text-center">🇫🇷</span> Fran&ccedil;ais
                                    </a>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['lang' => 'en'])) ?>"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#21262d] transition-colors <?= ($_SESSION['lang'] ?? 'fr') === 'en' ? 'font-semibold bg-gray-50 dark:bg-[#21262d]' : '' ?>">
                                        <span class="w-4 text-center">🇬🇧</span> English
                                    </a>
                                </div>
                            </div>
                            <!-- Logout -->
                            <div class="border-t border-gray-100 dark:border-[#30363d] py-1">
                                <a href="logout.php"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <?= __('nav.logout') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <?php
                if (!empty($flashMessages)):
                    foreach ($flashMessages as $flash):
                ?>
                <div
                    class="mb-4 px-4 py-3 rounded-lg text-sm <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 border border-emerald-200/50 dark:border-emerald-800/30' : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400 border border-red-200/50 dark:border-red-800/30' ?>">
                    <?= e($flash['message']) ?>
                </div>
                <?php
                    endforeach;
                endif;
                ?>

                <?= $content ?? '' ?>
            </main>
        </div>
    </div>

    <!-- Toast container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-2"></div>

    <!-- Translation Bridge for JavaScript -->
    <script>
        const __translations = <?= json_encode(
            file_exists(__DIR__. '/../../lang/'. ($_SESSION['lang'] ?? 'fr'). '.php')
                ? require __DIR__. '/../../lang/'. ($_SESSION['lang'] ?? 'fr'). '.php'
                : [],
            JSON_UNESCAPED_UNICODE
        ) ?>;
        function __(key, replace = {}) {
            let text = __translations[key] ?? key;
            for (const [k, v] of Object.entries(replace)) {
                text = text.replaceAll(':' + k, v);
            }
            return text;
        }
    </script>

    <!-- Common Scripts -->
    <script>
        const API = {
            baseUrl: 'api.php',
            async request(endpoint, options = {}) {
                const [path, queryString] = endpoint.split('?');
                let url = `${this.baseUrl}?route=${encodeURIComponent(path)}`;
                if (queryString) url += '&' + queryString;

                const fetchOptions = {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...options.headers
                    }
                };

                const response = await fetch(url, fetchOptions);
                let data;
                const text = await response.text();
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('API response not JSON for ' + endpoint + ':', text.substring(0, 500));
                    throw new Error('Server error: ' + text.substring(0, 200));
                }
                if (!response.ok) throw new Error(data.message || 'An error occurred');
                return data;
            },
            get(endpoint) { return this.request(endpoint); },
            post(endpoint, body) { return this.request(endpoint, { method: 'POST', body: JSON.stringify(body) }); },
            put(endpoint, body) { return this.request(endpoint, { method: 'PUT', body: JSON.stringify(body) }); },
            delete(endpoint, body = null) { return this.request(endpoint, { method: 'DELETE', ...(body ? { body: JSON.stringify(body) } : {}) }); }
        };

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');

            const colors = {
                success: 'bg-emerald-600',
                error: 'bg-red-600',
                info: 'bg-primary-600',
                warning: 'bg-amber-600'
            };

            const icons = {
                success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />',
                error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />',
                info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
                warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />'
            };

            toast.className = `${colors[type] || colors.success} text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 text-sm font-medium toast-enter max-w-sm`;
            toast.innerHTML = `<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icons[type] || icons.success}</svg><span>${message}</span>`;
            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.remove('toast-enter');
                toast.classList.add('toast-exit');
                setTimeout(() => toast.remove(), 200);
            }, 3500);
        }

        const Toast = {
            success(msg) { showToast(msg, 'success'); },
            error(msg) { showToast(msg, 'error'); },
            info(msg) { showToast(msg, 'info'); },
            warning(msg) { showToast(msg, 'warning'); }
        };

        function formatBytes(bytes, decimals = 2) {
            if (!bytes || bytes <= 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            if (i < 0 || i >= sizes.length) return '0 B';
            return parseFloat((bytes / Math.pow(k, i)).toFixed(decimals)) + ' ' + sizes[i];
        }

        function formatTime(seconds) {
            if (seconds <= 0) return '0s';
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            if (days > 0) return `${days}${__('time.d')} ${hours}${__('time.h')}`;
            if (hours > 0) return `${hours}${__('time.h')} ${minutes}${__('time.m')}`;
            return `${minutes}${__('time.m')}`;
        }

        function formatSpeed(bps) {
            if (bps >= 1000000) return (bps / 1000000).toFixed(1) + ' Mbps';
            if (bps >= 1000) return (bps / 1000).toFixed(0) + ' Kbps';
            return bps + ' bps';
        }

        function confirmAction(message) {
            return confirm(message);
        }

        function creditTopBar() {
            return {
                creditBalance: <?= isset($currentUser) && $currentUser -> isAdmin() ? $currentUser -> getCreditBalance() : 0 ?>,
                showModal: false,
                step: 1,
                amount: '',
                phone: '',
                currency: 'XOF',
                exchangeRate: 100,
                gateways: [],
                selectedGateway: '',
                processing: false,
                loadingGateways: false,
                errorMsg: '',
                quickAmounts: [500, 1000, 2000, 5000],

                init() {
                    window.addEventListener('crt-updated', (e) => {
                        this.creditBalance = parseFloat(e.detail.balance);
                    });
                },

                get creditsPreview() {
                    if (!this.amount || this.amount <= 0 || !this.exchangeRate) return '0';
                    return (this.amount / this.exchangeRate).toFixed(2);
                },

                init() {
                    window.addEventListener('open-recharge-modal', () => this.openRechargeModal());
                },

                openRechargeModal() {
                    this.showModal = true;
                    this.step = 1;
                    this.amount = '';
                    this.phone = '';
                    this.selectedGateway = '';
                    this.errorMsg = '';
                    this.processing = false;
                    this.loadSettings();
                },

                async loadSettings() {
                    try {
                        const res = await API.get('/credits/balance');
                        if (res.success && res.data) {
                            this.creditBalance = parseFloat(res.data.balance || 0);
                            this.exchangeRate = parseFloat(res.data.exchange_rate || 100);
                            this.currency = res.data.currency || 'XOF';
                        }
                    } catch (e) { }
                },

                async goToStep2() {
                    if (!this.amount || this.amount <= 0) return;
                    this.loadingGateways = true;
                    this.errorMsg = '';
                    try {
                        const res = await API.get('/credits/recharge-gateways');
                        if (res.success && res.data) {
                            this.gateways = res.data.gateways || [];
                        }
                    } catch (e) {
                        this.errorMsg = e.message;
                    }
                    this.loadingGateways = false;
                    this.step = 2;
                },

                async submitRecharge() {
                    if (!this.selectedGateway || this.processing) return;
                    this.processing = true;
                    this.errorMsg = '';
                    try {
                        const res = await API.post('/credits/recharge', {
                            amount: parseFloat(this.amount),
                            gateway_code: this.selectedGateway,
                            phone: this.phone
                        });
                        if (res.success && res.data && res.data.payment_url) {
                            window.location.href = res.data.payment_url;
                        } else {
                            this.errorMsg = res.message || 'Erreur inattendue';
                            this.processing = false;
                        }
                    } catch (e) {
                        this.errorMsg = e.message;
                        this.processing = false;
                    }
                }
            };
        }

        function topMenuNotifications() {
            return {
                open: false,
                notifications: [],
                unread: 0,
                selectedNotification: null,
                init() {
                    this.loadNotifications();
                    setInterval(() => this.loadNotifications(), 60000);
                },
                async loadNotifications() {
                    try {
                        const res = await fetch('api.php?route=/notifications');
                        const data = await res.json();
                        if (data.success && data.data) {
                            this.notifications = Array.isArray(data.data) ? data.data : (data.data.notifications || []);
                            this.unread = this.notifications.filter(n => !n.is_read).length;
                        }
                    } catch (e) {}
                },
                async markAsRead(notification) {
                    if (this.selectedNotification?.id === notification.id) {
                        this.selectedNotification = null;
                        return;
                    }
                    this.selectedNotification = notification;
                    if (!notification.is_read) {
                        try {
                            await fetch(`api.php?route=/notifications/${notification.id}/read`, { method: 'POST' });
                            notification.is_read = true;
                            this.unread = this.notifications.filter(n => !n.is_read).length;
                        } catch (e) {}
                    }
                },
                async markAllAsRead() {
                    try {
                        await fetch('api.php?route=/notifications/read-all', { method: 'POST' });
                        this.notifications.forEach(n => n.is_read = true);
                        this.unread = 0;
                    } catch (e) {}
                },
                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    const now = new Date();
                    const diff = Math.floor((now - d) / 1000);
                    if (diff < 60) return 'À l\'instant';
                    if (diff < 3600) return Math.floor(diff / 60) + ' min';
                    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
                    if (diff < 604800) return Math.floor(diff / 86400) + 'j';
                    return d.toLocaleDateString();
                }
            };
        }

        function smsCreditTopBar() {
              return {
                  smsBalance: <?= isset($currentUser) && $currentUser -> isAdmin() ? $currentUser -> getSmsCreditBalance() : 0 ?>,
                showConvertModal: false,
                crtAmount: '',
                csmsPerCrt: 4,
                costPerSmsFcfa: 25,
                converting: false,
                convertError: '',

                get csmsPreview() {
                    if (!this.crtAmount || this.crtAmount <= 0) return 0;
                    return Math.floor(this.crtAmount * this.csmsPerCrt);
                },

                async openConvertModal() {
                    this.showConvertModal = true;
                    this.crtAmount = '';
                    this.convertError = '';
                    this.converting = false;
                    try {
                        const res = await API.get('/sms-credits/balance');
                        if (res.success && res.data) {
                            this.smsBalance = parseFloat(res.data.balance || 0);
                            this.csmsPerCrt = parseFloat(res.data.csms_per_crt || 4);
                            this.costPerSmsFcfa = parseFloat(res.data.cost_per_sms_fcfa || 25);
                        }
                    } catch (e) { }
                },

                async submitConversion() {
                    if (!this.crtAmount || this.crtAmount <= 0 || this.csmsPreview < 1 || this.converting) return;
                    this.converting = true;
                    this.convertError = '';
                    try {
                        const res = await API.post('/sms-credits/convert', {
                            crt_amount: parseFloat(this.crtAmount)
                        });
                        if (res.success && res.data) {
                            this.smsBalance = parseFloat(res.data.new_csms_balance);
                            // Update CRT badge
                            window.dispatchEvent(new CustomEvent('crt-updated', {
                                detail: { balance: res.data.new_crt_balance }
                            }));
                            this.showConvertModal = false;
                            if (typeof showToast === 'function') {
                                showToast(res.message, 'success');
                            }
                        } else {
                            this.convertError = res.message || 'Erreur';
                        }
                    } catch (e) {
                        this.convertError = e.message;
                    }
                    this.converting = false;
                }
            };
        }

        function helpPanel(doc) {
            return {
                open: false,
                doc: doc || null,
            };
        }
    </script>
    <script>
        // Affiche r un toast si recharge=success dans l'URL (retour après paiement)
        (function () {
            const params = new URLSearchParams(window.location.search);
            const recharge = params.get('recharge');
            if (recharge === 'success') {
                setTimeout(()=> showToast('<?= __('credits.recharge_success') ?? 'Recharge effectuée avec succès!' ?>', 'success'), 500);
                // Nettoyer l'URL
                params.delete('recharge');
                params.delete('txn');
                const clean = params.toString();
                history.replaceState(null, '', window.location.pathname + (clean ? '?' + clean : ''));
            } else if (recharge === 'failed' || recharge === 'error') {
                setTimeout(() => showToast('<?= __('credits.recharge_failed') ?? 'La recharge a échoué. Veuillez réessayer.' ?>', 'error'), 500);
                params.delete('recharge');
                const clean = params.toString();
                history.replaceState(null, '', window.location.pathname + (clean ? '?' + clean : ''));
            } else if (recharge === 'pending') {
                setTimeout(() => showToast('<?= __('credits.recharge_processing') ?? 'Votre paiement est en cours de traitement...' ?>', 'info'), 500);
                params.delete('recharge');
                params.delete('txn');
                const clean = params.toString();
                history.replaceState(null, '', window.location.pathname + (clean ? '?' + clean : ''));
            }
        })();
    </script>
</body>

</html>