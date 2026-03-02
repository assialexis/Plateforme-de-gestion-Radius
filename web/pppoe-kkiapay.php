<?php
/**
 * Page de paiement Kkiapay pour PPPoE
 * Kkiapay utilise une intégration JavaScript côté client
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
$gateway = null;
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
        SELECT t.*, u.username, u.customer_name
        FROM pppoe_payment_transactions t
        JOIN pppoe_users u ON t.pppoe_user_id = u.id
        WHERE t.transaction_id = ?
    ");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch();
}

// Récupérer la configuration Kkiapay (scoped par admin_id de la transaction)
$kkiapayAdminId = $transaction['admin_id'] ?? null;
$gateway = $db->getPaymentGatewayByCode('kkiapay', $kkiapayAdminId);
$gatewayConfig = [];
if ($gateway) {
    $gatewayConfig = json_decode($gateway['config'], true) ?? [];
}

if (!$transaction || !$gateway) {
    header('Location: pppoe-pay.php');
    exit;
}

$publicKey = $gatewayConfig['public_key'] ?? '';
$sandbox = ($gatewayConfig['environment'] ?? 'sandbox') !== 'live';
$baseUrl = $config['app']['base_url'] ?? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement Kkiapay - <?= htmlspecialchars($appName) ?></title>
    <script src="https://cdn.kkiapay.me/k.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-indigo-50 via-white to-purple-50 min-h-screen flex items-center justify-center p-4">

<div class="max-w-md w-full">
    <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
        <div class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-credit-card text-indigo-600 text-2xl"></i>
        </div>

        <h1 class="text-xl font-bold text-gray-800 mb-2">Paiement Kkiapay</h1>
        <p class="text-gray-600 mb-6">
            Cliquez sur le bouton ci-dessous pour effectuer votre paiement.
        </p>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">Compte:</span>
                    <div class="font-medium text-gray-800"><?= htmlspecialchars($transaction['username']) ?></div>
                </div>
                <div>
                    <span class="text-gray-500">Montant:</span>
                    <div class="font-bold text-indigo-600"><?= number_format($transaction['amount'], 0, ',', ' ') ?> <?= $currency ?></div>
                </div>
            </div>
            <?php if ($transaction['description']): ?>
            <div class="mt-3 pt-3 border-t text-sm">
                <span class="text-gray-500">Description:</span>
                <div class="text-gray-800"><?= htmlspecialchars($transaction['description']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <button id="payButton"
                class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50">
            <i class="fas fa-lock mr-2"></i> Payer maintenant
        </button>

        <a href="pppoe-pay.php?username=<?= urlencode($transaction['username']) ?>"
           class="block mt-4 text-sm text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-1"></i> Annuler et retourner
        </a>

        <div id="loading" class="hidden mt-4">
            <i class="fas fa-spinner fa-spin text-2xl text-indigo-600"></i>
            <p class="text-gray-600 mt-2">Traitement en cours...</p>
        </div>

        <div id="error" class="hidden mt-4 p-3 bg-red-50 text-red-700 rounded-lg text-sm">
            <i class="fas fa-exclamation-circle mr-1"></i>
            <span id="errorMessage"></span>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-6 text-sm text-gray-500">
        <p><?= htmlspecialchars($appName) ?></p>
        <p class="mt-1">Paiement sécurisé par Kkiapay</p>
    </div>
</div>

<script>
document.getElementById('payButton').addEventListener('click', function() {
    // Ouvrir le widget Kkiapay
    openKkiapayWidget({
        amount: <?= (int)$transaction['amount'] ?>,
        position: 'center',
        callback: '<?= $baseUrl ?>/web/pppoe-payment-callback.php',
        data: '<?= $transactionId ?>',
        theme: '#4F46E5',
        key: '<?= htmlspecialchars($publicKey) ?>',
        sandbox: <?= $sandbox ? 'true' : 'false' ?>
    });
});

// Écouter les événements Kkiapay
if (typeof addKkiapayListener === 'function') {
    addKkiapayListener('success', function(response) {
        console.log('Kkiapay success:', response);

        document.getElementById('payButton').classList.add('hidden');
        document.getElementById('loading').classList.remove('hidden');
        document.getElementById('error').classList.add('hidden');

        // Notifier le callback avec la transaction Kkiapay
        const callbackUrl = '<?= $baseUrl ?>/web/pppoe-payment-callback.php?' +
            'transaction_id=<?= $transactionId ?>&' +
            'kkiapay_transaction_id=' + response.transactionId;

        fetch(callbackUrl)
            .then(response => response.json())
            .then(data => {
                // Rediriger vers la page de succès
                window.location.href = '<?= $baseUrl ?>/web/pppoe-payment-success.php?transaction=<?= $transactionId ?>';
            })
            .catch(error => {
                console.error('Callback error:', error);
                // Rediriger quand même vers la page de succès
                window.location.href = '<?= $baseUrl ?>/web/pppoe-payment-success.php?transaction=<?= $transactionId ?>';
            });
    });

    addKkiapayListener('failed', function(response) {
        console.log('Kkiapay failed:', response);

        document.getElementById('error').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = 'Le paiement a échoué. Veuillez réessayer.';
    });

    addKkiapayListener('close', function() {
        console.log('Widget closed');
    });
}
</script>

</body>
</html>
