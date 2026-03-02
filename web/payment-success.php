<?php
/**
 * Page de succès après paiement - Affiche le voucher
 */

// Configuration
error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Payment/PaymentService.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $db = new RadiusDatabase($config['database']);
    $paymentService = new PaymentService($db, $config);
} catch (Exception $e) {
    die('Database connection failed');
}

// Récupérer l'ID de transaction
$transactionId = $_GET['txn'] ?? '';
$cancelled = isset($_GET['cancelled']);

$transaction = null;
$voucher = null;
$profile = null;
$status = 'unknown';
$currency = $config['currency'] ?? 'XAF';
$appName = 'WiFi Hotspot';

// Charger le nom de l'app depuis les settings
try {
    $stmt = $db->getPdo()->query("SELECT setting_value FROM settings WHERE setting_key = 'app_name'");
    $row = $stmt->fetch();
    if ($row) {
        $appName = $row['setting_value'];
    }
} catch (Exception $e) {}

if ($transactionId) {
    $transaction = $paymentService->getTransaction($transactionId);

    if ($transaction) {
        $status = $transaction['status'];
        $profile = $db->getProfileById($transaction['profile_id']);

        // Si le voucher a été généré, le récupérer
        if ($transaction['voucher_id']) {
            $voucher = $db->getVoucherById($transaction['voucher_id']);
        }

        // Si le paiement est en attente, vérifier le statut auprès de la passerelle
        if ($status === 'pending') {
            try {
                // Vérifier le statut auprès de la passerelle (FedaPay, etc.)
                $statusResult = $paymentService->checkPaymentStatus($transactionId);

                // Recharger la transaction après vérification
                $transaction = $paymentService->getTransaction($transactionId);
                if ($transaction) {
                    $status = $transaction['status'];
                    if ($transaction['voucher_id']) {
                        $voucher = $db->getVoucherById($transaction['voucher_id']);
                    }
                }
            } catch (Exception $e) {
                // Ignorer l'erreur, on affichera le statut actuel
                error_log('Check payment status error: ' . $e->getMessage());
            }
        }
    }
}

// Fonctions de formatage locales (pour éviter conflit avec helpers.php)
function successFormatPrice($amount, $currency) {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function successFormatDuration($seconds) {
    if (!$seconds) return 'Illimité';
    if ($seconds < 3600) return floor($seconds / 60) . ' minutes';
    if ($seconds < 86400) return floor($seconds / 3600) . ' heure(s)';
    return floor($seconds / 86400) . ' jour(s)';
}

function successFormatData($bytes) {
    if (!$bytes) return 'Illimité';
    if ($bytes < 1073741824) return round($bytes / 1048576) . ' MB';
    return round($bytes / 1073741824, 1) . ' GB';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre Voucher WiFi - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3a5f 0%, #0d1b2a 100%);
            min-height: 100vh;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .voucher-code {
            font-family: 'Courier New', monospace;
            letter-spacing: 4px;
        }
        @keyframes pulse-success {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .pulse-success {
            animation: pulse-success 2s infinite;
        }
        @media print {
            body {
                background: white !important;
            }
            .no-print {
                display: none !important;
            }
            .glass-card {
                box-shadow: none !important;
                border: 2px solid #e5e7eb !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-md mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                    <i class="fas fa-wifi text-3xl text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($appName) ?></h1>
            </div>

            <?php if ($cancelled): ?>
            <!-- Paiement annulé -->
            <div class="glass-card rounded-2xl p-8 text-center">
                <div class="w-20 h-20 rounded-full bg-yellow-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-exclamation-triangle text-4xl text-yellow-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Paiement annulé</h2>
                <p class="text-gray-600 mb-6">Vous avez annulé le paiement. Aucun montant n'a été prélevé.</p>
                <a href="pay.php" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo"></i>
                    Réessayer
                </a>
            </div>

            <?php elseif (!$transaction): ?>
            <!-- Transaction introuvable -->
            <div class="glass-card rounded-2xl p-8 text-center">
                <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-question text-4xl text-red-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Transaction introuvable</h2>
                <p class="text-gray-600 mb-6">Nous n'avons pas pu trouver votre transaction. Si vous avez effectué un paiement, veuillez contacter le support.</p>
                <a href="pay.php" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
            </div>

            <?php elseif ($status === 'completed' && $voucher): ?>
            <!-- Paiement réussi - Affichage du voucher -->
            <div class="glass-card rounded-2xl overflow-hidden">
                <!-- Header succès -->
                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-6 text-center text-white">
                    <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-4 pulse-success">
                        <i class="fas fa-check text-3xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold mb-1">Paiement réussi !</h2>
                    <p class="text-white/80">Votre voucher WiFi est prêt</p>
                </div>

                <!-- Voucher -->
                <div class="p-6">
                    <div class="bg-gray-50 rounded-xl p-6 text-center mb-6 border-2 border-dashed border-gray-300">
                        <p class="text-sm text-gray-500 mb-2">Votre code d'accès</p>
                        <div class="voucher-code text-3xl font-bold text-blue-600 mb-4">
                            <?= htmlspecialchars($voucher['username']) ?>
                        </div>
                        <?php if ($voucher['password'] && $voucher['password'] !== $voucher['username']): ?>
                        <p class="text-sm text-gray-500 mb-1">Mot de passe</p>
                        <div class="voucher-code text-xl font-semibold text-gray-700">
                            <?= htmlspecialchars($voucher['password']) ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Détails du forfait -->
                    <div class="space-y-3 mb-6">
                        <h3 class="font-semibold text-gray-800">Détails du forfait</h3>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Forfait</span>
                            <span class="font-medium text-gray-800"><?= htmlspecialchars($profile['name'] ?? '-') ?></span>
                        </div>
                        <?php if ($voucher['time_limit']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Durée</span>
                            <span class="font-medium text-gray-800"><?= successFormatDuration($voucher['time_limit']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($voucher['data_limit']): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Data</span>
                            <span class="font-medium text-gray-800"><?= successFormatData($voucher['data_limit']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Montant payé</span>
                            <span class="font-medium text-green-600"><?= successFormatPrice($transaction['amount'], $currency) ?></span>
                        </div>
                    </div>

                    <!-- Instructions -->
                    <div class="bg-blue-50 rounded-xl p-4 mb-6">
                        <h4 class="font-semibold text-blue-800 mb-2">
                            <i class="fas fa-info-circle mr-2"></i>
                            Comment utiliser votre voucher
                        </h4>
                        <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                            <li>Connectez-vous au réseau WiFi</li>
                            <li>Ouvrez votre navigateur</li>
                            <li>Entrez votre code d'accès</li>
                            <li>Profitez d'Internet !</li>
                        </ol>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-3 no-print">
                        <button onclick="window.print()" class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                            <i class="fas fa-print mr-2"></i>
                            Imprimer
                        </button>
                        <button onclick="copyCode()" class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                            <i class="fas fa-copy mr-2"></i>
                            Copier le code
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4">
                    <div class="text-center text-sm text-gray-500 mb-2">
                        Transaction: <?= htmlspecialchars($transactionId) ?>
                    </div>
                    <div class="text-center">
                        <a href="retrieve-ticket.php" class="text-blue-600 hover:underline text-sm">
                            <i class="fas fa-bookmark mr-1"></i>Récupérer ce ticket plus tard
                        </a>
                    </div>
                </div>
            </div>

            <!-- Acheter un autre forfait -->
            <div class="text-center mt-4 no-print">
                <a href="pay.php" class="text-white/70 hover:text-white text-sm">
                    <i class="fas fa-plus-circle mr-1"></i>Acheter un autre forfait
                </a>
            </div>

            <?php elseif ($status === 'pending'): ?>
            <!-- Paiement en attente -->
            <div class="glass-card rounded-2xl p-8 text-center" id="pending-card">
                <div class="w-20 h-20 rounded-full bg-yellow-100 flex items-center justify-center mx-auto mb-6">
                    <div class="w-12 h-12 border-4 border-yellow-500 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Paiement en cours...</h2>
                <p class="text-gray-600 mb-4">Nous vérifions votre paiement. Cette page se rafraîchira automatiquement.</p>
                <p class="text-sm text-gray-400 mb-4">Transaction: <?= htmlspecialchars($transactionId) ?></p>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg text-left">
                    <h4 class="font-semibold text-blue-800 mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Récupérer votre ticket plus tard
                    </h4>
                    <p class="text-sm text-blue-700 mb-3">
                        Si vous avez effectué le paiement, vous pouvez récupérer votre ticket à tout moment avec votre numéro de téléphone ou la référence de transaction.
                    </p>
                    <a href="retrieve-ticket.php" class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm font-medium">
                        <i class="fas fa-ticket"></i>
                        Page de récupération de ticket
                    </a>
                </div>
            </div>

            <script>
                // Vérifier le statut toutes les 5 secondes
                let checkCount = 0;
                const maxChecks = 60; // Arrêter après 5 minutes

                const checkStatus = setInterval(() => {
                    checkCount++;
                    if (checkCount > maxChecks) {
                        clearInterval(checkStatus);
                        return;
                    }

                    fetch('api.php?route=/payments/check-status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ transaction_id: '<?= $transactionId ?>' })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.data?.status === 'completed') {
                            window.location.reload();
                        } else if (data.data?.status === 'failed') {
                            window.location.href = 'pay.php?cancelled=1&profile=<?= $profile ? $profile['id'] : '' ?>';
                        }
                    })
                    .catch(() => {});
                }, 5000);
            </script>

            <?php elseif ($status === 'failed'): ?>
            <!-- Paiement échoué -->
            <div class="glass-card rounded-2xl p-8 text-center">
                <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-times text-4xl text-red-500"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Paiement échoué</h2>
                <p class="text-gray-600 mb-6">Le paiement n'a pas pu être traité. Veuillez réessayer ou utiliser une autre méthode de paiement.</p>
                <a href="pay.php<?= $profile ? '?profile=' . $profile['id'] : '' ?>" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo"></i>
                    Réessayer
                </a>
            </div>

            <?php else: ?>
            <!-- État inconnu -->
            <div class="glass-card rounded-2xl p-8 text-center">
                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-question text-4xl text-gray-400"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Statut inconnu</h2>
                <p class="text-gray-600 mb-6">Nous ne pouvons pas déterminer le statut de votre paiement. Veuillez contacter le support si vous avez effectué un paiement.</p>
                <a href="pay.php" class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                    <i class="fas fa-arrow-left"></i>
                    Retour
                </a>
            </div>
            <?php endif; ?>

            <!-- Footer -->
            <div class="text-center mt-8 text-white/50 text-sm no-print">
                <p>Powered by RADIUS Manager</p>
            </div>
        </div>
    </div>

    <script>
        function copyCode() {
            const code = '<?= $voucher ? addslashes($voucher['username']) : '' ?>';
            navigator.clipboard.writeText(code).then(() => {
                alert('Code copié !');
            }).catch(() => {
                prompt('Copiez ce code:', code);
            });
        }
    </script>
</body>
</html>
