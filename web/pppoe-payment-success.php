<?php
/**
 * Page de succès de paiement PPPoE
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();
} catch (Exception $e) {
    die('Database connection failed');
}

$transactionId = $_GET['transaction'] ?? '';
$transaction = null;
$user = null;
$profile = null;
$invoice = null;
$currency = $config['currency'] ?? 'XAF';
$appName = 'RADIUS Manager';

// Charger le nom de l'app
try {
    $stmt = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'app_name'");
    $row = $stmt->fetch();
    if ($row) {
        $appName = $row['setting_value'];
    }
} catch (Exception $e) {}

// Récupérer la transaction
if ($transactionId) {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username, u.customer_name, u.valid_until, u.status as user_status,
               p.name as profile_name, p.validity_days
        FROM pppoe_payment_transactions t
        JOIN pppoe_users u ON t.pppoe_user_id = u.id
        LEFT JOIN pppoe_profiles p ON u.profile_id = p.id
        WHERE t.transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch();

    if ($transaction && $transaction['invoice_id']) {
        $stmt = $pdo->prepare("SELECT * FROM pppoe_invoices WHERE id = ?");
        $stmt->execute([$transaction['invoice_id']]);
        $invoice = $stmt->fetch();
    }
}

// Le statut est vérifié côté client via AJAX (/pppoe-pay/check-status)

function formatPrice($amount, $currency) {
    return number_format($amount, 0, ',', ' ') . ' ' . $currency;
}

function formatDate($date) {
    if (!$date) return '-';
    return date('d/m/Y', strtotime($date));
}

$paymentTypeLabels = [
    'invoice' => 'Paiement de facture',
    'extension' => 'Prolongation d\'abonnement',
    'renewal' => 'Renouvellement d\'abonnement'
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full">
    <?php if (!$transaction): ?>
    <!-- Transaction non trouvée -->
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-amber-500 text-2xl"></i>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Transaction non trouvée</h1>
        <p class="text-gray-600 mb-6">
            La transaction demandée n'existe pas ou a expiré.
        </p>
        <a href="pppoe-pay.php" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
            <i class="fas fa-arrow-left mr-2"></i> Retour
        </a>
    </div>

    <?php elseif ($transaction['status'] === 'completed'): ?>
    <!-- Paiement réussi -->
    <div class="bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-6">
            <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-green-500 text-4xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement réussi!</h1>
            <p class="text-gray-600">
                Votre paiement a été traité avec succès.
            </p>
        </div>

        <div class="bg-gray-50 rounded-xl p-4 mb-6">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Compte:</span>
                    <div class="font-medium text-gray-800"><?= htmlspecialchars($transaction['username']) ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Montant:</span>
                    <div class="font-bold text-green-600"><?= formatPrice($transaction['amount'], $currency) ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Type:</span>
                    <div class="font-medium text-gray-800"><?= $paymentTypeLabels[$transaction['payment_type']] ?? $transaction['payment_type'] ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Référence:</span>
                    <div class="font-mono text-xs text-gray-600"><?= htmlspecialchars($transactionId) ?></div>
                </div>
            </div>
        </div>

        <?php if ($transaction['payment_type'] !== 'invoice'): ?>
        <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-6">
            <h3 class="font-semibold text-indigo-800 mb-2">
                <i class="fas fa-calendar-check mr-2"></i>
                Nouvel abonnement
            </h3>
            <div class="text-sm text-indigo-700">
                <p>
                    <strong>Profil:</strong> <?= htmlspecialchars($transaction['profile_name']) ?>
                </p>
                <p>
                    <strong>Valide jusqu'au:</strong> <?= formatDate($transaction['valid_until']) ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($invoice): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-2">
                <i class="fas fa-file-invoice mr-2"></i>
                Facture
            </h3>
            <div class="text-sm text-blue-700">
                <p>
                    <strong>N°:</strong> <?= htmlspecialchars($invoice['invoice_number']) ?>
                </p>
                <p>
                    <strong>Statut:</strong>
                    <?php if ($invoice['status'] === 'paid'): ?>
                    <span class="text-green-600 font-medium">Soldée</span>
                    <?php else: ?>
                    <span class="text-amber-600 font-medium">Partiellement payée</span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="text-center space-y-3">
            <a href="pppoe-pay.php?username=<?= urlencode($transaction['username']) ?>"
               class="block w-full py-3 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Retour à mon compte
            </a>
            <button onclick="window.print()" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-print mr-1"></i> Imprimer le reçu
            </button>
        </div>
    </div>

    <?php elseif ($transaction['status'] === 'pending'): ?>
    <!-- Paiement en attente -->
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-clock text-amber-500 text-4xl animate-pulse"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement en cours...</h1>
        <p class="text-gray-600 mb-6">
            Votre paiement est en cours de traitement. Cette page se rafraîchira automatiquement.
        </p>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Montant:</span>
                    <div class="font-bold text-gray-800"><?= formatPrice($transaction['amount'], $currency) ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Référence:</span>
                    <div class="font-mono text-xs text-gray-600"><?= htmlspecialchars($transactionId) ?></div>
                </div>
            </div>
        </div>

        <p class="text-sm text-gray-500">
            <i class="fas fa-spinner fa-spin mr-1"></i>
            Rafraîchissement automatique dans <span id="countdown">10</span>s
        </p>
    </div>

    <script>
    const transactionId = '<?= htmlspecialchars($transactionId) ?>';
    let countdown = 5;
    let checking = false;
    const countdownEl = document.getElementById('countdown');

    async function checkStatus() {
        if (checking) return;
        checking = true;
        try {
            const res = await fetch('api.php?route=/pppoe-pay/check-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ transaction_id: transactionId })
            });
            const data = await res.json();
            if (data.success && data.data.status !== 'pending') {
                location.reload();
                return;
            }
        } catch (e) {}
        checking = false;
        countdown = 5;
    }

    setInterval(() => {
        countdown--;
        if (countdownEl) countdownEl.textContent = countdown;
        if (countdown <= 0) {
            checkStatus();
        }
    }, 1000);

    // Vérifier immédiatement au chargement
    checkStatus();
    </script>

    <?php else: ?>
    <!-- Paiement échoué -->
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-times text-red-500 text-4xl"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Paiement échoué</h1>
        <p class="text-gray-600 mb-6">
            Le paiement n'a pas pu être traité. Veuillez réessayer.
        </p>

        <?php if ($transaction['error_message']): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-6 text-sm text-red-700">
            <?= htmlspecialchars($transaction['error_message']) ?>
        </div>
        <?php endif; ?>

        <div class="space-y-3">
            <a href="pppoe-pay.php?username=<?= urlencode($transaction['username']) ?>"
               class="block w-full py-3 px-4 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition-colors">
                <i class="fas fa-redo mr-2"></i> Réessayer
            </a>
            <a href="pppoe-pay.php" class="block text-sm text-gray-500 hover:text-gray-700">
                Retour à l'accueil
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="text-center mt-6 text-sm text-gray-500">
        <p><?= htmlspecialchars($appName) ?></p>
    </div>
</div>

</body>
</html>
