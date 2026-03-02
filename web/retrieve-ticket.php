<?php
/**
 * Page de récupération de ticket par téléphone ou référence
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';
require_once __DIR__ . '/../src/Payment/PaymentService.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $db = new RadiusDatabase($config['database']);
    $paymentService = new PaymentService($db, $config);
} catch (Exception $e) {
    die('Database connection failed');
}

$appName = 'WiFi Hotspot';
$currency = $config['currency'] ?? 'XAF';

// Charger le nom de l'app
try {
    $stmt = $db->getPdo()->query("SELECT setting_value FROM settings WHERE setting_key = 'app_name'");
    $row = $stmt->fetch();
    if ($row) {
        $appName = $row['setting_value'];
    }
} catch (Exception $e) {}

$searchType = $_POST['search_type'] ?? $_GET['type'] ?? '';
$searchValue = trim($_POST['search_value'] ?? $_GET['q'] ?? '');
$transactions = [];
$error = null;
$searched = false;

if (!empty($searchValue)) {
    $searched = true;
    $pdo = $db->getPdo();

    try {
        // D'abord, chercher les transactions pending pour tenter de les récupérer
        $pendingRecovered = 0;
        if ($searchType === 'phone') {
            $phone = preg_replace('/[^0-9]/', '', $searchValue);
            $stmtPending = $pdo->prepare("
                SELECT transaction_id FROM payment_transactions
                WHERE customer_phone LIKE ? AND status = 'pending' AND gateway_transaction_id IS NOT NULL
                ORDER BY created_at DESC LIMIT 5
            ");
            $stmtPending->execute(['%' . $phone . '%']);
        } elseif ($searchType === 'reference') {
            $stmtPending = $pdo->prepare("
                SELECT transaction_id FROM payment_transactions
                WHERE (transaction_id LIKE ? OR gateway_transaction_id LIKE ? OR operator_reference LIKE ?)
                AND status = 'pending' AND gateway_transaction_id IS NOT NULL
                ORDER BY created_at DESC LIMIT 5
            ");
            $stmtPending->execute(['%' . $searchValue . '%', '%' . $searchValue . '%', '%' . $searchValue . '%']);
        } else {
            $phone = preg_replace('/[^0-9]/', '', $searchValue);
            $stmtPending = $pdo->prepare("
                SELECT transaction_id FROM payment_transactions
                WHERE (customer_phone LIKE ? OR transaction_id LIKE ? OR gateway_transaction_id LIKE ? OR operator_reference LIKE ?)
                AND status = 'pending' AND gateway_transaction_id IS NOT NULL
                ORDER BY created_at DESC LIMIT 5
            ");
            $stmtPending->execute(['%' . $phone . '%', '%' . $searchValue . '%', '%' . $searchValue . '%', '%' . $searchValue . '%']);
        }

        $pendingRows = $stmtPending->fetchAll();
        foreach ($pendingRows as $pendingRow) {
            try {
                $result = $paymentService->checkPaymentStatus($pendingRow['transaction_id']);
                if (($result['status'] ?? '') === 'completed') {
                    $pendingRecovered++;
                }
            } catch (Exception $e) {
                error_log('Retrieve ticket: recovery error for ' . $pendingRow['transaction_id'] . ': ' . $e->getMessage());
            }
        }

        // Maintenant chercher les transactions complétées (y compris celles récupérées)
        if ($searchType === 'phone') {
            $phone = preg_replace('/[^0-9]/', '', $searchValue);
            $stmt = $pdo->prepare("
                SELECT pt.*, p.name as profile_name, v.username as voucher_username, v.password as voucher_password
                FROM payment_transactions pt
                LEFT JOIN profiles p ON pt.profile_id = p.id
                LEFT JOIN vouchers v ON pt.voucher_id = v.id
                WHERE pt.customer_phone LIKE ? AND pt.status = 'completed'
                ORDER BY pt.created_at DESC
                LIMIT 10
            ");
            $stmt->execute(['%' . $phone . '%']);
            $transactions = $stmt->fetchAll();
        } elseif ($searchType === 'reference') {
            $stmt = $pdo->prepare("
                SELECT pt.*, p.name as profile_name, v.username as voucher_username, v.password as voucher_password
                FROM payment_transactions pt
                LEFT JOIN profiles p ON pt.profile_id = p.id
                LEFT JOIN vouchers v ON pt.voucher_id = v.id
                WHERE (pt.transaction_id LIKE ? OR pt.gateway_transaction_id LIKE ? OR pt.operator_reference LIKE ?)
                AND pt.status = 'completed'
                ORDER BY pt.created_at DESC
                LIMIT 10
            ");
            $stmt->execute(['%' . $searchValue . '%', '%' . $searchValue . '%', '%' . $searchValue . '%']);
            $transactions = $stmt->fetchAll();
        } else {
            $phone = preg_replace('/[^0-9]/', '', $searchValue);
            $stmt = $pdo->prepare("
                SELECT pt.*, p.name as profile_name, v.username as voucher_username, v.password as voucher_password
                FROM payment_transactions pt
                LEFT JOIN profiles p ON pt.profile_id = p.id
                LEFT JOIN vouchers v ON pt.voucher_id = v.id
                WHERE (pt.customer_phone LIKE ? OR pt.transaction_id LIKE ? OR pt.gateway_transaction_id LIKE ? OR pt.operator_reference LIKE ?)
                AND pt.status = 'completed'
                ORDER BY pt.created_at DESC
                LIMIT 10
            ");
            $stmt->execute(['%' . $phone . '%', '%' . $searchValue . '%', '%' . $searchValue . '%', '%' . $searchValue . '%']);
            $transactions = $stmt->fetchAll();
        }

        if (empty($transactions)) {
            $error = 'Aucun ticket trouvé. Vérifiez vos informations.';
        }
    } catch (Exception $e) {
        error_log('Retrieve ticket search error: ' . $e->getMessage());
        $error = 'Erreur lors de la recherche. Veuillez réessayer.';
    }
}

// Fonctions de formatage
function formatDuration($seconds) {
    if (!$seconds) return 'Illimité';
    if ($seconds < 3600) return floor($seconds / 60) . ' min';
    if ($seconds < 86400) return floor($seconds / 3600) . 'h';
    return floor($seconds / 86400) . ' jour(s)';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Récupérer mon ticket - <?= htmlspecialchars($appName) ?></title>
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
            letter-spacing: 2px;
        }
    </style>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-lg mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-white/20 mb-4">
                    <i class="fas fa-ticket text-3xl text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2"><?= htmlspecialchars($appName) ?></h1>
                <p class="text-white/70">Récupérer mon ticket WiFi</p>
            </div>

            <!-- Formulaire de recherche -->
            <div class="glass-card rounded-xl p-6 mb-6">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher par</label>
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <label class="flex items-center justify-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                                   :class="searchType === 'phone' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'">
                                <input type="radio" name="search_type" value="phone" class="sr-only"
                                       <?= $searchType === 'phone' || $searchType === '' ? 'checked' : '' ?>
                                       onchange="this.form.querySelector('[name=search_value]').placeholder = 'Ex: 90000000'">
                                <i class="fas fa-phone mr-2 text-blue-500"></i>
                                <span class="text-sm font-medium">Téléphone</span>
                            </label>
                            <label class="flex items-center justify-center p-3 border-2 rounded-lg cursor-pointer transition-colors"
                                   :class="searchType === 'reference' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-blue-300'">
                                <input type="radio" name="search_type" value="reference" class="sr-only"
                                       <?= $searchType === 'reference' ? 'checked' : '' ?>
                                       onchange="this.form.querySelector('[name=search_value]').placeholder = 'Ex: TXN_XXXXX ou ID opérateur'">
                                <i class="fas fa-hashtag mr-2 text-purple-500"></i>
                                <span class="text-sm font-medium">Référence</span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <input type="text" name="search_value" value="<?= htmlspecialchars($searchValue) ?>"
                               placeholder="<?= $searchType === 'reference' ? 'Ex: TXN_XXXXX ou ID opérateur' : 'Ex: 90000000' ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-lg"
                               required>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Entrez le numéro de téléphone utilisé lors du paiement ou la référence de transaction
                        </p>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Rechercher
                    </button>
                </form>
            </div>

            <?php if (!empty($pendingRecovered) && $pendingRecovered > 0): ?>
            <!-- Message récupération réussie -->
            <div class="glass-card rounded-xl p-4 mb-6 border-l-4 border-green-500">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <p class="text-gray-700"><?= $pendingRecovered ?> paiement(s) en attente récupéré(s) avec succès !</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <!-- Message d'erreur -->
            <div class="glass-card rounded-xl p-4 mb-6 border-l-4 border-red-500">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-red-500"></i>
                    <p class="text-gray-700"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($transactions)): ?>
            <!-- Résultats -->
            <div class="glass-card rounded-xl overflow-hidden">
                <div class="bg-green-500 p-4 text-white">
                    <h2 class="font-semibold">
                        <i class="fas fa-check-circle mr-2"></i>
                        <?= count($transactions) ?> ticket(s) trouvé(s)
                    </h2>
                </div>

                <div class="divide-y divide-gray-200">
                    <?php foreach ($transactions as $tx): ?>
                    <div class="p-4" x-data="{ expanded: false }">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-500">Forfait</p>
                                <p class="font-semibold text-gray-800"><?= htmlspecialchars($tx['profile_name'] ?? 'N/A') ?></p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?= date('d/m/Y à H:i', strtotime($tx['paid_at'] ?? $tx['created_at'])) ?>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-green-600"><?= number_format($tx['amount'], 0, ',', ' ') ?> <?= $currency ?></p>
                            </div>
                        </div>

                        <?php if ($tx['voucher_code']): ?>
                        <!-- Affichage du voucher -->
                        <div class="mt-4 bg-gradient-to-r from-blue-500 to-purple-600 rounded-xl p-4 text-white">
                            <div class="text-center">
                                <p class="text-white/70 text-sm mb-1">Code d'accès</p>
                                <div class="flex items-center justify-center gap-2">
                                    <span class="voucher-code text-2xl font-bold"><?= htmlspecialchars($tx['voucher_code']) ?></span>
                                    <button onclick="copyCode('<?= htmlspecialchars($tx['voucher_code']) ?>')"
                                            class="p-2 hover:bg-white/20 rounded-lg transition-colors" title="Copier">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                <?php if ($tx['voucher_password'] && $tx['voucher_password'] !== $tx['voucher_code']): ?>
                                <p class="text-white/70 text-sm mt-2 mb-1">Mot de passe</p>
                                <div class="flex items-center justify-center gap-2">
                                    <span class="voucher-code text-lg"><?= htmlspecialchars($tx['voucher_password']) ?></span>
                                    <button onclick="copyCode('<?= htmlspecialchars($tx['voucher_password']) ?>')"
                                            class="p-1 hover:bg-white/20 rounded-lg transition-colors" title="Copier">
                                        <i class="fas fa-copy text-sm"></i>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3 text-xs text-gray-500 space-y-1">
                            <div class="flex justify-between items-center">
                                <span>Réf: <?= htmlspecialchars($tx['transaction_id']) ?></span>
                                <a href="payment-success.php?txn=<?= urlencode($tx['transaction_id']) ?>"
                                   class="text-blue-600 hover:underline">
                                    <i class="fas fa-external-link-alt mr-1"></i>Voir détails
                                </a>
                            </div>
                            <?php if (!empty($tx['operator_reference'])): ?>
                            <div class="flex items-center gap-2">
                                <span class="text-purple-600">Réf. Opérateur: <?= htmlspecialchars($tx['operator_reference']) ?></span>
                                <button onclick="copyCode('<?= htmlspecialchars($tx['operator_reference']) ?>')"
                                        class="text-purple-400 hover:text-purple-600" title="Copier">
                                    <i class="fas fa-copy text-xs"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="mt-3 text-sm text-yellow-600">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Ticket non généré. Contactez le support.
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif ($searched): ?>
            <!-- Aucun résultat mais recherche effectuée -->
            <div class="glass-card rounded-xl p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Aucun ticket trouvé</h3>
                <p class="text-gray-600 mb-4">
                    Vérifiez que vous avez entré le bon numéro de téléphone ou la bonne référence.
                </p>
                <p class="text-sm text-gray-500">
                    Si vous avez effectué un paiement récent, il peut prendre quelques minutes à être traité.
                </p>
            </div>
            <?php endif; ?>

            <!-- Lien vers achat -->
            <div class="text-center mt-6">
                <a href="pay.php" class="inline-flex items-center gap-2 text-white/70 hover:text-white transition-colors">
                    <i class="fas fa-shopping-cart"></i>
                    Acheter un nouveau forfait
                </a>
            </div>

            <!-- Footer -->
            <div class="text-center mt-8 text-white/50 text-sm">
                <p>Powered by <?= htmlspecialchars($appName) ?></p>
            </div>
        </div>
    </div>

    <script>
        // Gérer la sélection des types de recherche
        document.querySelectorAll('input[name="search_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[name="search_type"]').forEach(r => {
                    r.closest('label').classList.remove('border-blue-500', 'bg-blue-50');
                    r.closest('label').classList.add('border-gray-200');
                });
                this.closest('label').classList.remove('border-gray-200');
                this.closest('label').classList.add('border-blue-500', 'bg-blue-50');
            });
        });

        // Initialiser l'état
        document.querySelectorAll('input[name="search_type"]:checked').forEach(radio => {
            radio.closest('label').classList.remove('border-gray-200');
            radio.closest('label').classList.add('border-blue-500', 'bg-blue-50');
        });

        function copyCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                notification.textContent = 'Code copié !';
                document.body.appendChild(notification);
                setTimeout(() => notification.remove(), 2000);
            });
        }
    </script>
</body>
</html>
