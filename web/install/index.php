<?php
/**
 * RADIUS Manager - Installation Wizard
 * Interface d'installation étape par étape
 */

session_start();

// Vérifier si déjà installé
$configFile = __DIR__ . '/../../config/config.php';
$lockFile = __DIR__ . '/../../.installed';

if (file_exists($lockFile)) {
    header('Location: ../index.php');
    exit;
}

// Étape actuelle
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$totalSteps = 5;

// Données de session pour l'installation
if (!isset($_SESSION['install'])) {
    $_SESSION['install'] = [
        'db_host' => '127.0.0.1',
        'db_port' => '3306',
        'db_name' => 'radius_db',
        'db_user' => 'root',
        'db_pass' => '',
        'app_name' => 'RADIUS Manager',
        'admin_user' => 'admin',
        'admin_pass' => '',
        'admin_email' => '',
        'timezone' => 'Africa/Douala',
        'language' => 'fr'
    ];
}

$error = '';
$success = '';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2: // Configuration base de données
            $_SESSION['install']['db_host'] = trim($_POST['db_host'] ?? '127.0.0.1');
            $_SESSION['install']['db_port'] = trim($_POST['db_port'] ?? '3306');
            $_SESSION['install']['db_name'] = trim($_POST['db_name'] ?? 'radius_db');
            $_SESSION['install']['db_user'] = trim($_POST['db_user'] ?? 'root');
            $_SESSION['install']['db_pass'] = $_POST['db_pass'] ?? '';

            // Tester la connexion
            try {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;charset=utf8mb4',
                    $_SESSION['install']['db_host'],
                    $_SESSION['install']['db_port']
                );
                $pdo = new PDO($dsn, $_SESSION['install']['db_user'], $_SESSION['install']['db_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $_SESSION['install']['db_connected'] = true;
                header('Location: ?step=3');
                exit;
            } catch (PDOException $e) {
                $error = 'Connexion échouée: ' . $e->getMessage();
            }
            break;

        case 3: // Configuration application
            $_SESSION['install']['app_name'] = trim($_POST['app_name'] ?? 'RADIUS Manager');
            $_SESSION['install']['timezone'] = $_POST['timezone'] ?? 'Africa/Douala';
            $_SESSION['install']['language'] = $_POST['language'] ?? 'fr';
            header('Location: ?step=4');
            exit;
            break;

        case 4: // Compte administrateur
            $adminUser = trim($_POST['admin_user'] ?? '');
            $adminPass = $_POST['admin_pass'] ?? '';
            $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';
            $adminEmail = trim($_POST['admin_email'] ?? '');

            if (strlen($adminUser) < 3) {
                $error = 'Le nom d\'utilisateur doit contenir au moins 3 caractères.';
            } elseif (strlen($adminPass) < 6) {
                $error = 'Le mot de passe doit contenir au moins 6 caractères.';
            } elseif ($adminPass !== $adminPassConfirm) {
                $error = 'Les mots de passe ne correspondent pas.';
            } else {
                $_SESSION['install']['admin_user'] = $adminUser;
                $_SESSION['install']['admin_pass'] = $adminPass;
                $_SESSION['install']['admin_email'] = $adminEmail;
                header('Location: ?step=5');
                exit;
            }
            break;

        case 5: // Installation finale
            $result = performInstallation();
            if ($result['success']) {
                // Créer le fichier lock
                file_put_contents($lockFile, date('Y-m-d H:i:s'));
                // Nettoyer la session
                unset($_SESSION['install']);
                header('Location: ?step=6');
                exit;
            } else {
                $error = $result['error'];
            }
            break;
    }
}

/**
 * Effectuer l'installation complète
 */
function performInstallation(): array {
    $install = $_SESSION['install'];

    try {
        // Connexion à MySQL
        $dsn = sprintf(
            'mysql:host=%s;port=%s;charset=utf8mb4',
            $install['db_host'],
            $install['db_port']
        );
        $pdo = new PDO($dsn, $install['db_user'], $install['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        // Créer la base de données
        $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $install['db_name']);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `{$dbName}`");

        // Exécuter le schéma SQL
        $schemaFile = __DIR__ . '/../../database/schema.sql';
        if (!file_exists($schemaFile)) {
            return ['success' => false, 'error' => 'Fichier schema.sql introuvable'];
        }

        $schema = file_get_contents($schemaFile);

        // Nettoyer le SQL
        // Retirer les commentaires sur une ligne (-- ...)
        $schema = preg_replace('/--.*$/m', '', $schema);
        // Retirer les commentaires multi-lignes /* ... */
        $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);
        // Retirer les commandes CREATE DATABASE et USE
        $schema = preg_replace('/CREATE DATABASE.*?;/si', '', $schema);
        $schema = preg_replace('/USE\s+[`]?\w+[`]?\s*;/si', '', $schema);

        // Exécuter chaque requête
        $queries = array_filter(array_map('trim', explode(';', $schema)));
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query) && strlen($query) > 10) {
                try {
                    $pdo->exec($query);
                } catch (PDOException $e) {
                    // Ignorer les erreurs de tables/clés existantes
                    $msg = $e->getMessage();
                    if (strpos($msg, 'already exists') === false &&
                        strpos($msg, 'Duplicate') === false &&
                        strpos($msg, '1062') === false) {
                        // Log l'erreur pour debug si nécessaire
                        error_log("SQL Install: " . $msg . " - Query: " . substr($query, 0, 100));
                    }
                }
            }
        }

        // Mettre à jour l'admin
        $hashedPassword = password_hash($install['admin_pass'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            UPDATE admins SET
                username = ?,
                password = ?,
                email = ?,
                full_name = 'Administrateur'
            WHERE id = 1
        ");
        $stmt->execute([$install['admin_user'], $hashedPassword, $install['admin_email']]);

        // Si aucune ligne modifiée, insérer
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("
                INSERT INTO admins (username, password, email, full_name, role)
                VALUES (?, ?, ?, 'Administrateur', 'admin')
            ");
            $stmt->execute([$install['admin_user'], $hashedPassword, $install['admin_email']]);
        }

        // Créer le fichier de configuration
        $configContent = generateConfigFile($install);
        $configPath = __DIR__ . '/../../config/config.php';

        if (!is_writable(dirname($configPath))) {
            return ['success' => false, 'error' => 'Le dossier config/ n\'est pas accessible en écriture'];
        }

        file_put_contents($configPath, $configContent);

        // Créer le dossier logs
        $logsDir = __DIR__ . '/../../logs';
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        return ['success' => true];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Générer le contenu du fichier de configuration
 */
function generateConfigFile(array $install): string {
    $dbPass = addslashes($install['db_pass']);

    return "<?php
/**
 * Configuration RADIUS Manager
 * Généré le " . date('Y-m-d H:i:s') . "
 */

return [
    // Base de données
    'database' => [
        'host' => '{$install['db_host']}',
        'port' => {$install['db_port']},
        'dbname' => '{$install['db_name']}',
        'username' => '{$install['db_user']}',
        'password' => '{$dbPass}',
        'charset' => 'utf8mb4'
    ],

    // Serveur RADIUS
    'radius' => [
        'auth_port' => 1812,
        'acct_port' => 1813,
        'listen_ip' => '0.0.0.0',
    ],

    // Application
    'app' => [
        'name' => '{$install['app_name']}',
        'version' => '1.0.0',
        'timezone' => '{$install['timezone']}',
        'language' => '{$install['language']}',
        'debug' => false,
        'session_lifetime' => 3600,
    ],

    // Sécurité
    'security' => [
        'csrf_token_name' => 'csrf_token',
        'rate_limit' => [
            'enabled' => true,
            'requests_per_minute' => 60,
        ],
    ],

    // Options RADIUS
    'options' => [
        'debug' => true,
        'log_file' => __DIR__ . '/../logs/radius.log',
        'default_session_timeout' => 86400,
        'default_idle_timeout' => 300,
    ]
];
";
}

/**
 * Vérifier les prérequis
 */
function checkRequirements(): array {
    $requirements = [];

    // Version PHP
    $requirements['php_version'] = [
        'name' => 'PHP 8.0+',
        'status' => version_compare(PHP_VERSION, '8.0.0', '>='),
        'current' => PHP_VERSION
    ];

    // Extensions
    $extensions = ['pdo', 'pdo_mysql', 'sockets', 'json', 'mbstring'];
    foreach ($extensions as $ext) {
        $requirements["ext_{$ext}"] = [
            'name' => "Extension {$ext}",
            'status' => extension_loaded($ext),
            'current' => extension_loaded($ext) ? 'Installée' : 'Manquante'
        ];
    }

    // Permissions d'écriture
    $writableDirs = [
        __DIR__ . '/../../config' => 'Dossier config/',
        __DIR__ . '/../../logs' => 'Dossier logs/',
    ];

    foreach ($writableDirs as $dir => $name) {
        $isWritable = is_dir($dir) ? is_writable($dir) : is_writable(dirname($dir));
        $requirements["write_" . basename($dir)] = [
            'name' => $name,
            'status' => $isWritable,
            'current' => $isWritable ? 'Accessible' : 'Non accessible'
        ];
    }

    return $requirements;
}

$requirements = checkRequirements();
$allRequirementsMet = !in_array(false, array_column($requirements, 'status'));

// Fuseaux horaires courants
$timezones = [
    'Africa/Douala' => 'Douala (UTC+1)',
    'Africa/Lagos' => 'Lagos (UTC+1)',
    'Africa/Casablanca' => 'Casablanca (UTC+0/+1)',
    'Africa/Johannesburg' => 'Johannesburg (UTC+2)',
    'Europe/Paris' => 'Paris (UTC+1/+2)',
    'Europe/London' => 'London (UTC+0/+1)',
    'America/New_York' => 'New York (UTC-5/-4)',
    'Asia/Dubai' => 'Dubai (UTC+4)',
    'UTC' => 'UTC'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation - RADIUS Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .step-active { @apply bg-blue-600 text-white; }
        .step-completed { @apply bg-green-500 text-white; }
        .step-pending { @apply bg-gray-200 text-gray-500; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">RADIUS Manager</h1>
            <p class="text-gray-600 mt-2">Assistant d'installation</p>
        </div>

        <!-- Progress Steps -->
        <?php if ($step <= 5): ?>
        <div class="max-w-3xl mx-auto mb-8">
            <div class="flex items-center justify-between">
                <?php
                $steps = [
                    1 => 'Prérequis',
                    2 => 'Base de données',
                    3 => 'Application',
                    4 => 'Administrateur',
                    5 => 'Installation'
                ];
                foreach ($steps as $num => $label):
                    $class = 'step-pending';
                    if ($num < $step) $class = 'step-completed';
                    if ($num === $step) $class = 'step-active';
                ?>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-semibold <?= $class ?>">
                        <?php if ($num < $step): ?>
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        <?php else: ?>
                            <?= $num ?>
                        <?php endif; ?>
                    </div>
                    <span class="text-xs mt-2 text-gray-600 hidden sm:block"><?= $label ?></span>
                </div>
                <?php if ($num < 5): ?>
                <div class="flex-1 h-1 mx-2 <?= $num < $step ? 'bg-green-500' : 'bg-gray-200' ?>"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Card -->
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

                <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-red-700"><?= htmlspecialchars($error) ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <div class="p-8">
                    <?php if ($step === 1): ?>
                    <!-- Étape 1: Vérification des prérequis -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Vérification des prérequis</h2>
                        <p class="text-gray-600 mb-6">Vérifions que votre serveur est prêt pour l'installation.</p>

                        <div class="space-y-3">
                            <?php foreach ($requirements as $req): ?>
                            <div class="flex items-center justify-between p-4 rounded-lg <?= $req['status'] ? 'bg-green-50' : 'bg-red-50' ?>">
                                <div class="flex items-center">
                                    <?php if ($req['status']): ?>
                                    <svg class="w-6 h-6 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?php else: ?>
                                    <svg class="w-6 h-6 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <?php endif; ?>
                                    <span class="font-medium text-gray-700"><?= $req['name'] ?></span>
                                </div>
                                <span class="text-sm <?= $req['status'] ? 'text-green-600' : 'text-red-600' ?>">
                                    <?= $req['current'] ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="mt-8 flex justify-end">
                            <?php if ($allRequirementsMet): ?>
                            <a href="?step=2" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                Continuer
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                            <?php else: ?>
                            <button disabled class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-500 font-semibold rounded-lg cursor-not-allowed">
                                Corrigez les erreurs pour continuer
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php elseif ($step === 2): ?>
                    <!-- Étape 2: Base de données -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Configuration de la base de données</h2>
                        <p class="text-gray-600 mb-6">Entrez les informations de connexion à votre serveur MySQL.</p>

                        <form method="POST" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hôte MySQL</label>
                                    <input type="text" name="db_host" value="<?= htmlspecialchars($_SESSION['install']['db_host']) ?>" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Port</label>
                                    <input type="text" name="db_port" value="<?= htmlspecialchars($_SESSION['install']['db_port']) ?>" required
                                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de la base de données</label>
                                <input type="text" name="db_name" value="<?= htmlspecialchars($_SESSION['install']['db_name']) ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Sera créée automatiquement si elle n'existe pas.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Utilisateur MySQL</label>
                                <input type="text" name="db_user" value="<?= htmlspecialchars($_SESSION['install']['db_user']) ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe MySQL</label>
                                <input type="password" name="db_pass" value="<?= htmlspecialchars($_SESSION['install']['db_pass']) ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div class="mt-8 flex justify-between">
                                <a href="?step=1" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    Retour
                                </a>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Tester et continuer
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php elseif ($step === 3): ?>
                    <!-- Étape 3: Configuration application -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Configuration de l'application</h2>
                        <p class="text-gray-600 mb-6">Personnalisez votre installation RADIUS Manager.</p>

                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom de l'application</label>
                                <input type="text" name="app_name" value="<?= htmlspecialchars($_SESSION['install']['app_name']) ?>" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Affiché dans l'interface et les rapports.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fuseau horaire</label>
                                <select name="timezone" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <?php foreach ($timezones as $tz => $label): ?>
                                    <option value="<?= $tz ?>" <?= $_SESSION['install']['timezone'] === $tz ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Langue</label>
                                <select name="language" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="fr" <?= $_SESSION['install']['language'] === 'fr' ? 'selected' : '' ?>>Français</option>
                                    <option value="en" <?= $_SESSION['install']['language'] === 'en' ? 'selected' : '' ?>>English</option>
                                </select>
                            </div>

                            <div class="mt-8 flex justify-between">
                                <a href="?step=2" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    Retour
                                </a>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Continuer
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php elseif ($step === 4): ?>
                    <!-- Étape 4: Compte administrateur -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Compte administrateur</h2>
                        <p class="text-gray-600 mb-6">Créez votre compte administrateur pour accéder au système.</p>

                        <form method="POST" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur</label>
                                <input type="text" name="admin_user" value="<?= htmlspecialchars($_SESSION['install']['admin_user']) ?>" required minlength="3"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" name="admin_email" value="<?= htmlspecialchars($_SESSION['install']['admin_email']) ?>"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                                <input type="password" name="admin_pass" required minlength="6"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <p class="text-xs text-gray-500 mt-1">Minimum 6 caractères.</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                                <input type="password" name="admin_pass_confirm" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <div class="mt-8 flex justify-between">
                                <a href="?step=3" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    Retour
                                </a>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Continuer
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                    </svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php elseif ($step === 5): ?>
                    <!-- Étape 5: Résumé et installation -->
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Prêt pour l'installation</h2>
                        <p class="text-gray-600 mb-6">Vérifiez les informations avant de lancer l'installation.</p>

                        <div class="space-y-4">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Base de données</h3>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <span class="text-gray-500">Serveur:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['db_host']) ?>:<?= htmlspecialchars($_SESSION['install']['db_port']) ?></span>
                                    <span class="text-gray-500">Base:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['db_name']) ?></span>
                                    <span class="text-gray-500">Utilisateur:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['db_user']) ?></span>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Application</h3>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <span class="text-gray-500">Nom:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['app_name']) ?></span>
                                    <span class="text-gray-500">Fuseau horaire:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['timezone']) ?></span>
                                    <span class="text-gray-500">Langue:</span>
                                    <span class="text-gray-900"><?= $_SESSION['install']['language'] === 'fr' ? 'Français' : 'English' ?></span>
                                </div>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <h3 class="font-semibold text-gray-700 mb-2">Administrateur</h3>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <span class="text-gray-500">Utilisateur:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['admin_user']) ?></span>
                                    <span class="text-gray-500">Email:</span>
                                    <span class="text-gray-900"><?= htmlspecialchars($_SESSION['install']['admin_email']) ?: '-' ?></span>
                                </div>
                            </div>
                        </div>

                        <form method="POST" class="mt-8">
                            <div class="flex justify-between">
                                <a href="?step=4" class="inline-flex items-center px-4 py-2 text-gray-600 hover:text-gray-800">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
                                    </svg>
                                    Retour
                                </a>
                                <button type="submit" class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                    </svg>
                                    Installer maintenant
                                </button>
                            </div>
                        </form>
                    </div>

                    <?php elseif ($step === 6): ?>
                    <!-- Étape 6: Succès -->
                    <div class="text-center py-8">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Installation terminée !</h2>
                        <p class="text-gray-600 mb-8">RADIUS Manager est maintenant installé et prêt à être utilisé.</p>

                        <div class="bg-blue-50 rounded-lg p-6 text-left mb-8">
                            <h3 class="font-semibold text-blue-900 mb-4">Prochaines étapes</h3>
                            <ol class="space-y-3 text-sm text-blue-800">
                                <li class="flex items-start">
                                    <span class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 font-semibold mr-3 flex-shrink-0">1</span>
                                    <span>Démarrez le serveur RADIUS avec: <code class="bg-blue-100 px-2 py-1 rounded">sudo php radius_server.php</code></span>
                                </li>
                                <li class="flex items-start">
                                    <span class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 font-semibold mr-3 flex-shrink-0">2</span>
                                    <span>Configurez votre MikroTik avec le serveur RADIUS (IP: votre serveur, Secret: testing123)</span>
                                </li>
                                <li class="flex items-start">
                                    <span class="w-6 h-6 bg-blue-200 rounded-full flex items-center justify-center text-blue-700 font-semibold mr-3 flex-shrink-0">3</span>
                                    <span>Créez des profils et des vouchers depuis l'interface d'administration</span>
                                </li>
                            </ol>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <p class="text-sm text-yellow-700">
                                    <strong>Important :</strong> Supprimez le dossier <code class="bg-yellow-100 px-1 rounded">install/</code> après l'installation pour des raisons de sécurité.
                                </p>
                            </div>
                        </div>

                        <a href="../login.php" class="inline-flex items-center px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                            Accéder à l'administration
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            RADIUS Manager v1.0 &copy; <?= date('Y') ?>
        </div>
    </div>
</body>
</html>
