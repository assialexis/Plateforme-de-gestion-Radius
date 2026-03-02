<?php
/**
 * Callback de paiement PPPoE
 * Traite les notifications des passerelles de paiement et met à jour le statut
 */

error_reporting(E_ALL & ~E_DEPRECATED);
ini_set('display_errors', 0);

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Utils/pppoe-payment-helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

// Initialiser la base de données
try {
    $db = new RadiusDatabase($config['database']);
    $pdo = $db->getPdo();
} catch (Exception $e) {
    logCallback('ERROR', 'Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit;
}

// Logger les callbacks (alias pour compatibilité)
function logCallback($type, $message, $data = []) {
    logPPPoEPayment($type, $message, $data);
}

// Récupérer les données de callback
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?? [];
$getData = $_GET;
$postData = $_POST;

logCallback('INFO', 'Callback received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'get' => $getData,
    'post' => $postData,
    'json' => $jsonData
]);

// Identifier la passerelle et extraire la transaction
$transactionId = null;
$gatewayTransactionId = null;
$status = null;
$gatewayCode = null;

// FedaPay - Webhook JSON
if (isset($jsonData['entity']) && $jsonData['entity'] === 'event') {
    $gatewayCode = 'fedapay';
    $eventData = $jsonData['object'] ?? [];
    $transactionId = $eventData['metadata']['transaction_id'] ?? null;
    $gatewayTransactionId = (string)($eventData['id'] ?? '');

    if ($jsonData['name'] === 'transaction.approved') {
        $status = 'completed';
    } elseif ($jsonData['name'] === 'transaction.declined' || $jsonData['name'] === 'transaction.canceled') {
        $status = 'failed';
    }
}

// FedaPay - Callback GET (redirection après paiement)
if (isset($getData['status']) && isset($getData['id']) && !$gatewayCode) {
    $fedaStatus = $getData['status'];
    $fedaId = $getData['id'];

    // Chercher la transaction par gateway_transaction_id
    $stmt = $pdo->prepare("SELECT * FROM pppoe_payment_transactions WHERE gateway_transaction_id = ?");
    $stmt->execute([$fedaId]);
    $foundTransaction = $stmt->fetch();

    if ($foundTransaction) {
        $gatewayCode = 'fedapay';
        $transactionId = $foundTransaction['transaction_id'];
        $gatewayTransactionId = $fedaId;

        if ($fedaStatus === 'approved') {
            $status = 'completed';
        } elseif (in_array($fedaStatus, ['declined', 'canceled', 'cancelled'])) {
            $status = 'failed';
        }

        logCallback('INFO', 'FedaPay callback via GET', [
            'feda_id' => $fedaId,
            'feda_status' => $fedaStatus,
            'found_transaction' => $transactionId
        ]);
    }
}

// CinetPay
if (isset($jsonData['cpm_trans_id']) || isset($postData['cpm_trans_id'])) {
    $gatewayCode = 'cinetpay';
    $transactionId = $jsonData['cpm_trans_id'] ?? $postData['cpm_trans_id'] ?? null;
    $gatewayTransactionId = $jsonData['cpm_payid'] ?? $postData['cpm_payid'] ?? '';

    $cpmResult = $jsonData['cpm_result'] ?? $postData['cpm_result'] ?? '';
    if ($cpmResult === '00') {
        $status = 'completed';
    } else {
        $status = 'failed';
    }
}

// FeexPay - webhook avec callback_info (notre transaction_id) et reference (ref FeexPay)
if (isset($jsonData['callback_info']) && isset($jsonData['status']) && !$gatewayCode) {
    $gatewayCode = 'feexpay';
    $transactionId = $jsonData['callback_info'];
    $gatewayTransactionId = $jsonData['reference'] ?? '';

    if ($jsonData['status'] === 'SUCCESSFUL') {
        $status = 'completed';
    } elseif ($jsonData['status'] === 'FAILED') {
        $status = 'failed';
    }
}

// PayGate
if (isset($jsonData['identifier']) || isset($postData['identifier'])) {
    $gatewayCode = 'paygate';
    $transactionId = $jsonData['identifier'] ?? $postData['identifier'] ?? null;
    $gatewayTransactionId = $jsonData['tx_reference'] ?? $postData['tx_reference'] ?? '';

    $payStatus = $jsonData['status'] ?? $postData['status'] ?? '';
    if ($payStatus === '0') {
        $status = 'completed';
    } else {
        $status = 'failed';
    }
}

// PayDunya
if (isset($jsonData['data']['invoice']['token'])) {
    $gatewayCode = 'paydunya';
    $customData = $jsonData['data']['invoice']['custom_data'] ?? [];
    $transactionId = $customData['transaction_id'] ?? null;
    $gatewayTransactionId = $jsonData['data']['invoice']['token'] ?? '';

    $payStatus = $jsonData['data']['invoice']['status'] ?? '';
    if ($payStatus === 'completed') {
        $status = 'completed';
    } elseif ($payStatus === 'cancelled' || $payStatus === 'failed') {
        $status = 'failed';
    }
}

// Moneroo
if (isset($jsonData['event']) && strpos($jsonData['event'], 'payment.') === 0) {
    $gatewayCode = 'moneroo';
    $metadata = $jsonData['data']['metadata'] ?? [];
    $transactionId = $metadata['transaction_id'] ?? null;
    $gatewayTransactionId = $jsonData['data']['id'] ?? '';

    if ($jsonData['event'] === 'payment.success') {
        $status = 'completed';
    } elseif ($jsonData['event'] === 'payment.failed') {
        $status = 'failed';
    }
}

// Stripe (webhook)
if (isset($jsonData['type']) && strpos($jsonData['type'], 'checkout.session.') === 0) {
    $gatewayCode = 'stripe';
    $sessionData = $jsonData['data']['object'] ?? [];
    $transactionId = $sessionData['metadata']['transaction_id'] ?? null;
    $gatewayTransactionId = $sessionData['id'] ?? '';

    if ($jsonData['type'] === 'checkout.session.completed') {
        $status = 'completed';
    }
}

// Kkiapay (appelé depuis la page de succès)
if (isset($getData['transaction_id']) && isset($getData['kkiapay_transaction_id'])) {
    $gatewayCode = 'kkiapay';
    $transactionId = $getData['transaction_id'];
    $gatewayTransactionId = $getData['kkiapay_transaction_id'];
    $status = 'completed'; // Kkiapay envoie seulement les succès via ce callback
}

// YengaPay - webhook POST avec reference, paymentStatus
if (isset($jsonData['reference']) && isset($jsonData['paymentStatus']) && !isset($jsonData['callback_info'])) {
    $gatewayCode = 'yengapay';
    $transactionId = $jsonData['reference'] ?? null;
    $gatewayTransactionId = (string)($jsonData['id'] ?? '');

    switch ($jsonData['paymentStatus']) {
        case 'DONE':
            $status = 'completed';
            break;
        case 'FAILED':
        case 'CANCELLED':
            $status = 'failed';
            break;
    }
}

logCallback('INFO', 'Parsed callback', [
    'gateway' => $gatewayCode,
    'transaction_id' => $transactionId,
    'gateway_transaction_id' => $gatewayTransactionId,
    'status' => $status
]);

// Traiter la transaction si trouvée
if ($transactionId && $status) {
    try {
        // Récupérer la transaction
        $stmt = $pdo->prepare("SELECT * FROM pppoe_payment_transactions WHERE transaction_id = ?");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            logCallback('ERROR', 'Transaction not found', ['transaction_id' => $transactionId]);
            http_response_code(200);
            echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
            exit;
        }

        if ($transaction['status'] === 'completed') {
            logCallback('INFO', 'Transaction already completed', ['transaction_id' => $transactionId]);
            http_response_code(200);
            echo json_encode(['status' => 'ok', 'message' => 'Already processed']);
            exit;
        }

        // Si paiement réussi, traiter selon le type AVANT de mettre à jour le statut
        if ($status === 'completed') {
            processSuccessfulPPPoEPayment($db, $pdo, $transaction);

            // Si paiement via passerelle plateforme → créditer le solde paygate de l'admin
            $txAdminId = $transaction['admin_id'] ?? null;
            if (($transaction['is_platform'] ?? 0) == 1 && $txAdminId) {
                try {
                    require_once __DIR__ . '/../src/Payment/PlatformPaymentService.php';
                    $platformService = new PlatformPaymentService($pdo, $config);
                    $platformService->creditAdminBalance(
                        (int)$txAdminId,
                        (float)$transaction['amount'],
                        'pppoe_payment_transaction',
                        $transactionId,
                        'Paiement PPPoE client via passerelle plateforme'
                    );
                    logCallback('INFO', 'Admin paygate balance credited for platform PPPoE payment', [
                        'admin_id' => $txAdminId,
                        'amount' => $transaction['amount']
                    ]);
                } catch (Exception $e) {
                    logCallback('ERROR', 'Paygate credit error: ' . $e->getMessage(), [
                        'admin_id' => $txAdminId,
                        'transaction_id' => $transactionId
                    ]);
                }
            }
        }

        // Mettre à jour la transaction APRÈS le traitement réussi
        $stmt = $pdo->prepare("
            UPDATE pppoe_payment_transactions
            SET status = ?,
                gateway_transaction_id = ?,
                gateway_response = ?,
                completed_at = ?
            WHERE transaction_id = ?
        ");
        $stmt->execute([
            $status,
            $gatewayTransactionId,
            json_encode(array_merge($jsonData, $postData, $getData)),
            $status === 'completed' ? date('Y-m-d H:i:s') : null,
            $transactionId
        ]);

        logCallback('INFO', 'Transaction processed successfully', [
            'transaction_id' => $transactionId,
            'status' => $status
        ]);

        http_response_code(200);
        echo json_encode(['status' => 'ok']);

    } catch (Exception $e) {
        logCallback('ERROR', 'Processing error: ' . $e->getMessage(), [
            'transaction_id' => $transactionId
        ]);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
} else {
    logCallback('WARNING', 'Could not parse callback');
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'message' => 'Callback received but not processed']);
}
