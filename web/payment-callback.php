<?php
/**
 * Webhook/Callback handler pour les paiements
 * Ce fichier reçoit les notifications de paiement des passerelles
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Log function
function logPayment($message, $data = []) {
    $logFile = __DIR__ . '/../logs/payments.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if (!empty($data)) {
        $logMessage .= " - " . json_encode($data);
    }
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
}

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
    logPayment('Database connection failed', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    exit;
}

// Récupérer les données du webhook
$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true) ?? [];

// Récupérer aussi les paramètres GET/POST
$getData = $_GET;
$postData = $_POST;

// Admin context from callback URL (multi-tenant isolation)
$callbackAdminId = isset($_GET['admin']) ? (int)$_GET['admin'] : null;

// Platform payment context
$isPlatformPayment = isset($_GET['platform']) && $_GET['platform'] == '1';

logPayment('Webhook received', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'payload' => $payload,
    'get' => $getData,
    'post' => $postData,
    'headers' => getallheaders()
]);

// Identifier la passerelle de paiement
$gatewayCode = identifyGateway($payload, $getData, $postData);

if (!$gatewayCode) {
    logPayment('Unknown payment gateway');
    http_response_code(400);
    echo json_encode(['error' => 'Unknown payment gateway']);
    exit;
}

logPayment("Processing callback for gateway: $gatewayCode");

// Headers JSON seulement pour les webhooks POST (pas pour les redirections GET)
$isGetRedirect = !empty($getData['status']) && !empty($getData['id']);
if (!$isGetRedirect) {
    header('Content-Type: application/json');
}

try {
    switch ($gatewayCode) {
        case 'fedapay':
            handleFedaPayCallback($db, $paymentService, $payload, $getData, $callbackAdminId, $isPlatformPayment);
            break;

        case 'cinetpay':
            handleCinetPayCallback($db, $paymentService, $payload, $postData, $callbackAdminId, $isPlatformPayment);
            break;

        case 'stripe':
            handleStripeCallback($db, $paymentService, $rawPayload, $config, $callbackAdminId);
            break;

        case 'paypal':
            handlePayPalCallback($db, $paymentService, $payload, $getData, $callbackAdminId);
            break;

        case 'feexpay':
            handleFeexPayCallback($db, $paymentService, $payload, $postData, $callbackAdminId);
            break;

        case 'paygate':
        case 'paygate_global':
            handlePayGateCallback($db, $paymentService, $payload, $postData, $callbackAdminId);
            break;

        case 'paydunya':
            handlePayDunyaCallback($db, $paymentService, $payload, $postData, $callbackAdminId);
            break;

        case 'moneroo':
            handleMonerooCallback($db, $paymentService, $payload, $getData, $callbackAdminId);
            break;

        case 'cryptomus':
            handleCryptomusCallback($db, $paymentService, $payload, $callbackAdminId);
            break;

        case 'yengapay':
            handleYengaPayCallback($db, $paymentService, $payload, $callbackAdminId, $isPlatformPayment);
            break;

        default:
            logPayment("Unhandled gateway: $gatewayCode");
            http_response_code(400);
            echo json_encode(['error' => 'Gateway not supported']);
            exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    logPayment('Callback error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Identifier la passerelle à partir des données reçues
 */
function identifyGateway($payload, $getData, $postData): ?string
{
    // Essayer de détecter à partir de l'URL ou des paramètres (priorité)
    if (isset($getData['gateway'])) {
        return $getData['gateway'];
    }

    // FedaPay - redirection GET avec status et id (format: ?status=approved&id=123456)
    if (isset($getData['status']) && isset($getData['id']) && is_numeric($getData['id'])) {
        return 'fedapay';
    }

    // FedaPay - webhook POST plusieurs formats possibles
    if (isset($payload['entity']) && $payload['entity'] === 'Event') {
        return 'fedapay';
    }
    if (isset($payload['name']) && strpos($payload['name'], 'transaction.') === 0) {
        return 'fedapay';
    }
    if (isset($payload['object']['transaction']['klass']) && $payload['object']['transaction']['klass'] === 'v1/transaction') {
        return 'fedapay';
    }

    // CinetPay
    if (isset($postData['cpm_trans_id']) || isset($payload['cpm_trans_id'])) {
        return 'cinetpay';
    }

    // Stripe (vérifie le header)
    if (isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
        return 'stripe';
    }

    // PayPal
    if (isset($getData['token']) && isset($getData['PayerID'])) {
        return 'paypal';
    }

    // FeexPay - webhook POST avec reference et status
    if (isset($payload['reference']) && isset($payload['status']) && isset($payload['callback_info'])) {
        return 'feexpay';
    }

    // PayGate Global - webhook POST avec tx_reference, identifier, payment_reference
    if (isset($payload['tx_reference']) && isset($payload['identifier']) && isset($payload['payment_reference'])) {
        return 'paygate';
    }

    // PayDunya - IPN POST avec data.invoice.token et data.status
    if (isset($payload['data']['invoice']['token']) || isset($payload['data']['status'])) {
        return 'paydunya';
    }
    // PayDunya - format alternatif avec custom_data.transaction_id
    if (isset($payload['data']['custom_data']['transaction_id'])) {
        return 'paydunya';
    }

    // Moneroo - redirection GET avec paymentId et paymentStatus
    if (isset($getData['paymentId']) && isset($getData['paymentStatus'])) {
        return 'moneroo';
    }
    // Moneroo - webhook POST
    if (isset($payload['event']) && strpos($payload['event'], 'payment.') === 0) {
        return 'moneroo';
    }

    // Cryptomus - webhook POST avec order_id, uuid, status, sign
    if (isset($payload['order_id']) && isset($payload['uuid']) && isset($payload['sign']) && isset($payload['status'])) {
        return 'cryptomus';
    }

    // YengaPay - webhook POST avec reference, paymentStatus
    if (isset($payload['reference']) && isset($payload['paymentStatus']) && !isset($payload['callback_info'])) {
        return 'yengapay';
    }

    return null;
}

/**
 * Handler FedaPay
 * Documentation: https://docs.fedapay.com/webhooks
 *
 * Gère deux formats:
 * 1. Redirection GET: ?status=approved&id=123456 (après paiement réussi)
 * 2. Webhook POST: JSON avec entity=Event et name=transaction.approved
 */
function handleFedaPayCallback($db, $paymentService, $payload, $getData = [], ?int $adminId = null, bool $isPlatform = false)
{
    logPayment('FedaPay callback', ['payload' => $payload, 'getData' => $getData]);

    $transaction = null;
    $status = null;
    $fedaPayTransactionId = null;

    // Mode 1: Redirection GET avec status et id
    if (isset($getData['status']) && isset($getData['id'])) {
        $status = $getData['status'];
        $fedaPayTransactionId = $getData['id'];

        logPayment("FedaPay GET redirect detected", ['status' => $status, 'fedapay_id' => $fedaPayTransactionId]);

        // Récupérer les détails de la transaction via l'API FedaPay
        $transaction = fetchFedaPayTransaction($db, $fedaPayTransactionId, $adminId, $isPlatform);

        if ($transaction) {
            // Prendre le statut de la transaction API (plus fiable)
            $apiStatus = $transaction['status'] ?? $status;
            if ($apiStatus) {
                $status = $apiStatus;
            }
        }
    }
    // Mode 2: Webhook POST
    else {
        // Extraire la transaction du payload - plusieurs formats possibles
        if (isset($payload['object']['transaction'])) {
            $transaction = $payload['object']['transaction'];
        } elseif (isset($payload['transaction'])) {
            $transaction = $payload['transaction'];
        } elseif (isset($payload['data']['transaction'])) {
            $transaction = $payload['data']['transaction'];
        }

        if (!$transaction) {
            logPayment('Invalid FedaPay payload - no transaction found', $payload);
            throw new Exception('Invalid FedaPay payload');
        }

        $status = $transaction['status'] ?? '';
        $eventName = $payload['name'] ?? '';
        $fedaPayTransactionId = $transaction['id'] ?? null;

        // Déduire le statut depuis le nom de l'événement si nécessaire
        if (empty($status) && !empty($eventName)) {
            if (strpos($eventName, '.approved') !== false) {
                $status = 'approved';
            } elseif (strpos($eventName, '.declined') !== false) {
                $status = 'declined';
            } elseif (strpos($eventName, '.canceled') !== false) {
                $status = 'canceled';
            }
        }
    }

    // Récupérer notre transaction_id depuis les métadonnées ou la base de données
    $transactionId = null;

    if ($transaction) {
        $transactionId = $transaction['metadata']['transaction_id'] ?? null;

        // Si pas dans metadata, chercher dans custom_metadata
        if (!$transactionId && isset($transaction['custom_metadata']['transaction_id'])) {
            $transactionId = $transaction['custom_metadata']['transaction_id'];
        }
    }

    // Essayer de trouver par gateway_transaction_id
    if (!$transactionId && $fedaPayTransactionId) {
        $pdo = $db->getPdo();
        $stmt = $pdo->prepare("SELECT transaction_id FROM payment_transactions WHERE gateway_transaction_id = ?");
        $stmt->execute([(string)$fedaPayTransactionId]);
        $row = $stmt->fetch();
        if ($row) {
            $transactionId = $row['transaction_id'];
            logPayment('Found transaction by gateway_transaction_id', ['transaction_id' => $transactionId]);
        }
    }

    if (!$transactionId) {
        logPayment('Could not identify transaction', ['fedapay_id' => $fedaPayTransactionId]);

        // En mode GET redirect, rediriger vers une page d'erreur
        if (isset($getData['status'])) {
            header('Location: payment-success.php?error=transaction_not_found');
            exit;
        }
        return;
    }

    // Déterminer si c'est une recharge de crédits admin (pour la redirection)
    $pdo = $db->getPdo();
    $stmtType = $pdo->prepare("SELECT transaction_type FROM payment_transactions WHERE transaction_id = ?");
    $stmtType->execute([$transactionId]);
    $isCreditRecharge = ($stmtType->fetchColumn() === 'credit_recharge');

    logPayment("FedaPay transaction status: $status", ['transaction_id' => $transactionId, 'fedapay_id' => $fedaPayTransactionId, 'is_credit_recharge' => $isCreditRecharge]);

    // Extraire la référence opérateur FedaPay
    $operatorReference = extractFedaPayOperatorReference($transaction);
    logPayment("FedaPay operator reference: " . ($operatorReference ?? 'none'), ['transaction_id' => $transactionId]);

    if ($status === 'approved') {
        try {
            $result = $paymentService->completeTransaction(
                $transactionId,
                (string)$fedaPayTransactionId,
                $transaction ?? $payload,
                $operatorReference
            );
            logPayment('FedaPay payment completed', $result);

            // En mode GET redirect, rediriger
            if (isset($getData['status'])) {
                if ($isCreditRecharge) {
                    header('Location: index.php?page=dashboard&recharge=success&txn=' . urlencode($transactionId));
                } else {
                    header('Location: payment-success.php?txn=' . urlencode($transactionId));
                }
                exit;
            }
        } catch (Exception $e) {
            logPayment('FedaPay complete transaction error', ['error' => $e->getMessage()]);

            // En mode GET redirect, rediriger quand même (pour voir le statut)
            if (isset($getData['status'])) {
                if ($isCreditRecharge) {
                    header('Location: index.php?page=dashboard&recharge=error');
                } else {
                    header('Location: payment-success.php?txn=' . urlencode($transactionId));
                }
                exit;
            }
            throw $e;
        }
    } elseif (in_array($status, ['declined', 'canceled', 'cancelled', 'refunded'])) {
        // Mettre à jour le statut comme échoué
        $newStatus = $status === 'refunded' ? 'refunded' : 'failed';
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = ?, gateway_response = ?, operator_reference = ? WHERE transaction_id = ?");
        $stmt->execute([$newStatus, json_encode($transaction ?? $payload), $operatorReference, $transactionId]);
        logPayment("FedaPay payment $status", ['transaction_id' => $transactionId]);

        // En mode GET redirect, rediriger
        if (isset($getData['status'])) {
            if ($isCreditRecharge) {
                header('Location: index.php?page=dashboard&recharge=failed');
            } else {
                header('Location: payment-success.php?txn=' . urlencode($transactionId) . '&failed=1');
            }
            exit;
        }
    } else {
        // Statut inconnu en mode GET redirect
        if (isset($getData['status'])) {
            if ($isCreditRecharge) {
                header('Location: index.php?page=dashboard&recharge=pending&txn=' . urlencode($transactionId));
            } else {
                header('Location: payment-success.php?txn=' . urlencode($transactionId));
            }
            exit;
        }
    }
}

/**
 * Récupérer les détails d'une transaction FedaPay via l'API
 */
function fetchFedaPayTransaction($db, $fedaPayTransactionId, ?int $adminId = null, bool $isPlatform = false): ?array
{
    $gateway = null;

    // Si paiement plateforme, résoudre config depuis la passerelle recharge (source unique)
    if ($isPlatform) {
        $gateway = $db->getGlobalGatewayByCode('fedapay');
    }

    if (!$gateway) {
        $gateway = $db->getPaymentGatewayByCode('fedapay', $adminId);
    }

    if (!$gateway) {
        logPayment('FedaPay gateway not configured');
        return null;
    }

    $config = $gateway['config'];
    $apiKey = $config['secret_key'] ?? $config['api_key'] ?? '';

    if (empty($apiKey)) {
        logPayment('FedaPay API key not configured');
        return null;
    }

    $apiUrl = $gateway['is_sandbox']
        ? 'https://sandbox-api.fedapay.com'
        : 'https://api.fedapay.com';

    $ch = curl_init($apiUrl . '/v1/transactions/' . $fedaPayTransactionId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logPayment('FedaPay API curl error', ['error' => $error]);
        return null;
    }

    if ($httpCode !== 200) {
        logPayment('FedaPay API error', ['http_code' => $httpCode, 'response' => $response]);
        return null;
    }

    $data = json_decode($response, true);
    logPayment('FedaPay API response', $data);

    // FedaPay retourne la transaction dans 'v1/transaction' ou directement
    if (isset($data['v1/transaction'])) {
        return $data['v1/transaction'];
    } elseif (isset($data['transaction'])) {
        return $data['transaction'];
    }

    return $data;
}

/**
 * Extraire la référence opérateur depuis une transaction FedaPay
 * La transaction_key est la vraie référence opérateur (ex: 14496562590)
 * La reference est l'ID interne FedaPay (ex: trx_iLc_xxx)
 */
function extractFedaPayOperatorReference(?array $transaction): ?string
{
    if (!$transaction) {
        return null;
    }

    // Priorité 1: transaction_key - C'est la vraie référence opérateur mobile money
    if (!empty($transaction['transaction_key'])) {
        return $transaction['transaction_key'];
    }
    // Priorité 2: operator_reference explicite
    if (!empty($transaction['operator_reference'])) {
        return $transaction['operator_reference'];
    }
    // Priorité 3: payment_method reference
    if (!empty($transaction['payment_method']['reference'])) {
        return $transaction['payment_method']['reference'];
    }
    if (!empty($transaction['payment_method']['number'])) {
        return $transaction['payment_method']['number'];
    }
    // Priorité 4: merchant_reference
    if (!empty($transaction['merchant_reference'])) {
        return $transaction['merchant_reference'];
    }
    // Fallback: reference FedaPay (moins utile pour le client)
    if (!empty($transaction['reference'])) {
        return $transaction['reference'];
    }

    return null;
}

/**
 * Handler CinetPay
 * Documentation: https://docs.cinetpay.com/api/1.0-fr/checkout/notification
 *
 * IMPORTANT: CinetPay recommande de NE PAS faire confiance aux données du callback
 * et de TOUJOURS vérifier le statut via l'API /v2/payment/check
 */
function handleCinetPayCallback($db, $paymentService, $payload, $postData, ?int $adminId = null, bool $isPlatform = false)
{
    logPayment('CinetPay callback received', ['payload' => $payload, 'post' => $postData]);

    // CinetPay envoie cpm_trans_id dans le callback (notre transaction_id nettoyé)
    $data = !empty($payload) ? $payload : $postData;
    $cinetPayTransId = $data['cpm_trans_id'] ?? $data['transaction_id'] ?? null;

    if (!$cinetPayTransId) {
        logPayment('CinetPay callback: No transaction ID');
        throw new Exception('No transaction ID in CinetPay callback');
    }

    logPayment("CinetPay callback for transaction: $cinetPayTransId");

    // Retrouver notre transaction_id original (avec le préfixe TXN_)
    // CinetPay a reçu le transaction_id sans underscore, on doit le reconstruire
    $pdo = $db->getPdo();

    // Chercher la transaction par transaction_id qui contient cinetPayTransId (sans le underscore)
    $cleanedId = preg_replace('/[^A-Za-z0-9]/', '', $cinetPayTransId);
    $stmt = $pdo->prepare("SELECT transaction_id FROM payment_transactions WHERE REPLACE(transaction_id, '_', '') = ?");
    $stmt->execute([$cleanedId]);
    $row = $stmt->fetch();

    if (!$row) {
        // Essayer avec le transaction_id tel quel
        $stmt = $pdo->prepare("SELECT transaction_id FROM payment_transactions WHERE transaction_id = ?");
        $stmt->execute([$cinetPayTransId]);
        $row = $stmt->fetch();
    }

    if (!$row) {
        logPayment("CinetPay callback: Transaction not found", ['cpm_trans_id' => $cinetPayTransId]);
        throw new Exception('Transaction not found: ' . $cinetPayTransId);
    }

    $transactionId = $row['transaction_id'];
    logPayment("CinetPay: Found our transaction", ['our_id' => $transactionId, 'cinetpay_id' => $cinetPayTransId]);

    // Vérifier que la transaction n'est pas déjà complétée
    $existingTx = $paymentService->getTransaction($transactionId);
    if ($existingTx && $existingTx['status'] === 'completed') {
        logPayment("CinetPay: Transaction already completed", ['transaction_id' => $transactionId]);
        return;
    }

    // IMPORTANT: Vérifier le VRAI statut via l'API CinetPay (sécurité)
    // Ne JAMAIS faire confiance aux données du callback directement
    $txAdminId = $existingTx['admin_id'] ?? $adminId;
    $txIsPlatform = $isPlatform || (($existingTx['is_platform'] ?? 0) == 1);

    if ($txIsPlatform) {
        // Utiliser les credentials plateforme pour la vérification
        $verifiedData = verifyCinetPayPlatform($db, $transactionId);
    } else {
        $verifiedData = $paymentService->verifyCinetPayTransaction($transactionId, $txAdminId);
    }

    if (!$verifiedData) {
        logPayment("CinetPay: Could not verify transaction via API", ['transaction_id' => $transactionId]);
        // Ne pas échouer, peut-être que la transaction est encore en cours
        return;
    }

    logPayment("CinetPay verified status", $verifiedData);

    $status = $verifiedData['status'] ?? '';
    $operatorId = $verifiedData['operator_id'] ?? null;
    $paymentMethod = $verifiedData['payment_method'] ?? null;

    if ($status === 'ACCEPTED') {
        try {
            $result = $paymentService->completeTransaction(
                $transactionId,
                $verifiedData['payment_token'] ?? $cinetPayTransId,
                $verifiedData,
                $operatorId // Référence opérateur mobile money
            );
            logPayment('CinetPay payment completed', $result);
        } catch (Exception $e) {
            logPayment('CinetPay complete transaction error', ['error' => $e->getMessage()]);
            throw $e;
        }
    } elseif (in_array($status, ['REFUSED', 'CANCELLED'])) {
        $pdo = $db->getPdo();
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'failed', gateway_response = ? WHERE transaction_id = ?");
        $stmt->execute([json_encode($verifiedData), $transactionId]);
        logPayment('CinetPay payment failed/refused', ['transaction_id' => $transactionId, 'status' => $status]);
    } else {
        // Statut en attente (PENDING, WAITING_FOR_CUSTOMER)
        logPayment('CinetPay payment pending', ['transaction_id' => $transactionId, 'status' => $status]);
    }
}

/**
 * Handler Stripe
 */
function handleStripeCallback($db, $paymentService, $rawPayload, $config, ?int $adminId = null)
{
    logPayment('Stripe callback');

    // Vérifier la signature Stripe
    $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
    $gateway = $db->getPaymentGatewayByCode('stripe', $adminId);

    if (!$gateway) {
        throw new Exception('Stripe gateway not configured');
    }

    $webhookSecret = $gateway['config']['webhook_secret'] ?? '';

    // Si un secret est configuré, vérifier la signature
    if ($webhookSecret) {
        // Implémentation simplifiée - en production, utiliser la librairie Stripe
        // Pour une vérification complète, installez stripe/stripe-php
    }

    $event = json_decode($rawPayload, true);

    if ($event['type'] === 'checkout.session.completed') {
        $session = $event['data']['object'];
        $transactionId = $session['metadata']['transaction_id'] ?? null;

        if ($transactionId) {
            $result = $paymentService->completeTransaction(
                $transactionId,
                $session['payment_intent'] ?? $session['id'],
                $event
            );
            logPayment('Stripe payment completed', $result);
        }
    }
}

/**
 * Handler PayPal
 */
function handlePayPalCallback($db, $paymentService, $payload, $getData, ?int $adminId = null)
{
    logPayment('PayPal callback', ['payload' => $payload, 'get' => $getData]);

    // PayPal renvoie le token et PayerID dans l'URL
    $token = $getData['token'] ?? null;

    if (!$token) {
        throw new Exception('No PayPal token');
    }

    // Récupérer la transaction par le gateway_transaction_id
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE gateway_transaction_id = ?");
    $stmt->execute([$token]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        logPayment('PayPal transaction not found', ['token' => $token]);
        return;
    }

    // Capturer le paiement avec l'API PayPal (use transaction's admin_id if available, fallback to URL admin)
    $paypalAdminId = $transaction['admin_id'] ?? $adminId;
    $gateway = $db->getPaymentGatewayByCode('paypal', $paypalAdminId);
    if (!$gateway) {
        throw new Exception('PayPal gateway not configured');
    }

    $apiUrl = $gateway['is_sandbox'] ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';
    $config = $gateway['config'];

    // Obtenir le token d'accès
    $ch = curl_init($apiUrl . '/v1/oauth2/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_USERPWD, $config['client_id'] . ':' . $config['client_secret']);
    $tokenResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (!isset($tokenResponse['access_token'])) {
        throw new Exception('PayPal auth error');
    }

    // Capturer le paiement
    $ch = curl_init($apiUrl . '/v2/checkout/orders/' . $token . '/capture');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $tokenResponse['access_token'],
        'Content-Type: application/json'
    ]);
    $captureResponse = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if (($captureResponse['status'] ?? '') === 'COMPLETED') {
        $result = $paymentService->completeTransaction(
            $transaction['transaction_id'],
            $captureResponse['id'],
            $captureResponse
        );
        logPayment('PayPal payment completed', $result);
    } else {
        logPayment('PayPal capture failed', $captureResponse);
    }
}

/**
 * Handler FeexPay
 * Documentation: https://docs.feexpay.me/api_rest.html
 *
 * Webhook POST avec:
 * - reference: ID de transaction FeexPay
 * - status: SUCCESSFUL, FAILED, PENDING
 * - callback_info: Notre transaction_id
 * - amount, phoneNumber, etc.
 */
function handleFeexPayCallback($db, $paymentService, $payload, $postData = [], ?int $adminId = null)
{
    logPayment('FeexPay callback', ['payload' => $payload, 'postData' => $postData]);

    // FeexPay envoie callback_info qui contient notre transaction_id
    $transactionId = $payload['callback_info'] ?? null;
    $feexPayReference = $payload['reference'] ?? null;
    $status = $payload['status'] ?? null;

    if (!$transactionId) {
        logPayment('FeexPay callback: No transaction ID in callback_info');
        http_response_code(400);
        echo json_encode(['error' => 'Missing callback_info']);
        exit;
    }

    // Récupérer notre transaction
    $transaction = $paymentService->getTransaction($transactionId);

    if (!$transaction) {
        logPayment('FeexPay callback: Transaction not found', ['transaction_id' => $transactionId]);
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    // Vérifier si déjà traitée
    if ($transaction['status'] === 'completed') {
        logPayment('FeexPay callback: Transaction already completed', ['transaction_id' => $transactionId]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Already processed']);
        exit;
    }

    logPayment('FeexPay transaction status', [
        'transaction_id' => $transactionId,
        'feexpay_reference' => $feexPayReference,
        'status' => $status
    ]);

    // Traiter selon le statut
    if ($status === 'SUCCESSFUL') {
        // Extraire la référence opérateur si disponible
        $operatorReference = $feexPayReference;

        // Compléter la transaction
        $result = $paymentService->completeTransaction(
            $transactionId,
            $feexPayReference,
            $payload,
            $operatorReference
        );

        logPayment('FeexPay payment completed', $result);

    } elseif ($status === 'FAILED') {
        // Marquer comme échouée
        $paymentService->updateTransaction($transactionId, [
            'status' => 'failed',
            'gateway_response' => $payload
        ]);

        logPayment('FeexPay payment failed', ['reason' => $payload['reason'] ?? 'Unknown']);

    } else {
        // PENDING ou autre - ne rien faire, attendre le prochain callback
        logPayment('FeexPay payment pending', ['status' => $status]);
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
}

/**
 * Handler PayGate Global
 * Documentation: https://paygateglobal.com
 *
 * Webhook POST avec:
 * - tx_reference: ID de transaction PayGate
 * - identifier: Notre transaction_id
 * - payment_reference: Code de référence Flooz/TMoney
 * - amount, datetime, payment_method, phone_number
 */
function handlePayGateCallback($db, $paymentService, $payload, $postData = [], ?int $adminId = null)
{
    logPayment('PayGate callback', ['payload' => $payload, 'postData' => $postData]);

    // PayGate envoie identifier qui contient notre transaction_id
    $transactionId = $payload['identifier'] ?? null;
    $txReference = $payload['tx_reference'] ?? null;
    $paymentReference = $payload['payment_reference'] ?? null;
    $paymentMethod = $payload['payment_method'] ?? null;

    if (!$transactionId) {
        logPayment('PayGate callback: No transaction ID in identifier');
        http_response_code(400);
        echo json_encode(['error' => 'Missing identifier']);
        exit;
    }

    // Récupérer notre transaction
    $transaction = $paymentService->getTransaction($transactionId);

    if (!$transaction) {
        logPayment('PayGate callback: Transaction not found', ['transaction_id' => $transactionId]);
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    // Vérifier si déjà traitée
    if ($transaction['status'] === 'completed') {
        logPayment('PayGate callback: Transaction already completed', ['transaction_id' => $transactionId]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Already processed']);
        exit;
    }

    logPayment('PayGate transaction received', [
        'transaction_id' => $transactionId,
        'tx_reference' => $txReference,
        'payment_reference' => $paymentReference,
        'payment_method' => $paymentMethod
    ]);

    // PayGate envoie le callback uniquement en cas de succès
    // Donc si on reçoit un callback avec payment_reference, c'est un succès
    if ($paymentReference) {
        // Compléter la transaction
        $result = $paymentService->completeTransaction(
            $transactionId,
            $txReference,
            $payload,
            $paymentReference  // Référence opérateur (Flooz/TMoney)
        );

        logPayment('PayGate payment completed', $result);
    } else {
        // Pas de payment_reference - vérifier le statut via API
        $statusResponse = $paymentService->verifyPayGateTransaction($transactionId, $transaction['admin_id'] ?? $adminId);

        if ($statusResponse && isset($statusResponse['status'])) {
            $status = $statusResponse['status'];

            if ($status === 0) {
                // Succès
                $result = $paymentService->completeTransaction(
                    $transactionId,
                    $statusResponse['tx_reference'] ?? $txReference,
                    $statusResponse,
                    $statusResponse['payment_reference'] ?? null
                );
                logPayment('PayGate payment verified and completed', $result);
            } elseif (in_array($status, [4, 6])) {
                // Expiré ou annulé
                $paymentService->updateTransaction($transactionId, [
                    'status' => 'failed',
                    'gateway_response' => $statusResponse
                ]);
                logPayment('PayGate payment failed', ['status' => $status]);
            } else {
                // En cours
                logPayment('PayGate payment still pending', ['status' => $status]);
            }
        }
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
}

/**
 * Handler PayDunya
 * Documentation: https://developers.paydunya.com/doc/FR/http_json
 *
 * IPN POST avec:
 * - data.invoice.token: Token de la facture
 * - data.status: completed, pending, cancelled, failed
 * - data.custom_data.transaction_id: Notre transaction_id
 * - data.customer: Infos client
 */
function handlePayDunyaCallback($db, $paymentService, $payload, $postData = [], ?int $adminId = null)
{
    logPayment('PayDunya callback', ['payload' => $payload, 'postData' => $postData]);

    // PayDunya envoie les données dans un objet 'data'
    $data = $payload['data'] ?? $payload;

    // Récupérer le transaction_id depuis custom_data
    $transactionId = $data['custom_data']['transaction_id'] ?? null;
    $invoiceToken = $data['invoice']['token'] ?? null;
    $status = $data['status'] ?? null;

    if (!$transactionId && !$invoiceToken) {
        logPayment('PayDunya callback: No transaction ID or invoice token');
        http_response_code(400);
        echo json_encode(['error' => 'Missing transaction ID or invoice token']);
        exit;
    }

    // Si on n'a pas le transaction_id, essayer de le trouver via le token
    if (!$transactionId && $invoiceToken) {
        // Chercher la transaction par gateway_transaction_id
        $transaction = $db->getTransactionByGatewayId($invoiceToken);
        if ($transaction) {
            $transactionId = $transaction['transaction_id'];
        }
    }

    if (!$transactionId) {
        logPayment('PayDunya callback: Could not find transaction');
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    // Récupérer notre transaction
    $transaction = $paymentService->getTransaction($transactionId);

    if (!$transaction) {
        logPayment('PayDunya callback: Transaction not found', ['transaction_id' => $transactionId]);
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    // Vérifier si déjà traitée
    if ($transaction['status'] === 'completed') {
        logPayment('PayDunya callback: Transaction already completed', ['transaction_id' => $transactionId]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Already processed']);
        exit;
    }

    logPayment('PayDunya transaction status', [
        'transaction_id' => $transactionId,
        'invoice_token' => $invoiceToken,
        'status' => $status
    ]);

    // Traiter selon le statut
    if ($status === 'completed') {
        // Extraire les informations de paiement
        $receiptIdentifier = $data['receipt_identifier'] ?? null;
        $receiptUrl = $data['receipt_url'] ?? null;

        // Compléter la transaction
        $result = $paymentService->completeTransaction(
            $transactionId,
            $invoiceToken,
            $data,
            $receiptIdentifier
        );

        logPayment('PayDunya payment completed', $result);

    } elseif (in_array($status, ['cancelled', 'failed'])) {
        // Marquer comme échouée
        $paymentService->updateTransaction($transactionId, [
            'status' => 'failed',
            'gateway_response' => $data
        ]);

        logPayment('PayDunya payment failed', ['status' => $status, 'reason' => $data['fail_reason'] ?? 'Unknown']);

    } else {
        // PENDING ou autre - vérifier via API pour être sûr
        $statusResponse = $paymentService->verifyPayDunyaTransaction($invoiceToken, $transaction['admin_id'] ?? $adminId);

        if ($statusResponse && isset($statusResponse['status'])) {
            $verifiedStatus = $statusResponse['status'];

            if ($verifiedStatus === 'completed') {
                $result = $paymentService->completeTransaction(
                    $transactionId,
                    $invoiceToken,
                    $statusResponse,
                    $statusResponse['receipt_identifier'] ?? null
                );
                logPayment('PayDunya payment verified and completed', $result);
            } elseif (in_array($verifiedStatus, ['cancelled', 'failed'])) {
                $paymentService->updateTransaction($transactionId, [
                    'status' => 'failed',
                    'gateway_response' => $statusResponse
                ]);
                logPayment('PayDunya payment verified as failed', ['status' => $verifiedStatus]);
            } else {
                logPayment('PayDunya payment still pending', ['status' => $verifiedStatus]);
            }
        } else {
            logPayment('PayDunya payment pending', ['status' => $status]);
        }
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
}

/**
 * Gère le callback Moneroo (redirection GET et webhook POST)
 */
function handleMonerooCallback($db, $paymentService, $payload, $getData = [], ?int $adminId = null)
{
    logPayment('Moneroo callback received', [
        'getData' => $getData,
        'payload' => $payload
    ]);

    // Cas 1: Redirection GET après paiement (utilisateur revient)
    if (isset($getData['paymentId']) && isset($getData['paymentStatus'])) {
        $monerooPaymentId = $getData['paymentId'];
        $paymentStatus = $getData['paymentStatus'];

        logPayment('Moneroo redirect callback', [
            'paymentId' => $monerooPaymentId,
            'paymentStatus' => $paymentStatus
        ]);

        // Trouver l'admin_id à partir de la transaction stockée ou du callback URL
        $pdo = $db->getPdo();
        $txStmt = $pdo->prepare("SELECT admin_id FROM payment_transactions WHERE gateway_transaction_id = ? AND gateway_code = 'moneroo'");
        $txStmt->execute([$monerooPaymentId]);
        $txRow = $txStmt->fetch();
        $monerooAdminId = $txRow['admin_id'] ?? $adminId;

        // Vérifier le paiement via l'API Moneroo
        $paymentData = $paymentService->verifyMonerooPayment($monerooPaymentId, $monerooAdminId);

        if (!$paymentData) {
            logPayment('Moneroo: Failed to verify payment', ['paymentId' => $monerooPaymentId]);
            header('Location: /web/pay.php?error=verification_failed');
            exit;
        }

        $metadata = $paymentData['metadata'] ?? [];
        $transactionId = $metadata['transaction_id'] ?? null;
        $profileId = $metadata['profile_id'] ?? null;
        $status = $paymentData['status'] ?? 'unknown';

        logPayment('Moneroo payment verified', [
            'status' => $status,
            'transactionId' => $transactionId,
            'profileId' => $profileId
        ]);

        // Traiter selon le statut
        if ($status === 'success') {
            $result = $paymentService->completeMonerooPayment($monerooPaymentId);

            if ($result['success']) {
                logPayment('Moneroo payment completed successfully', $result);
                header('Location: /web/pay.php?profile=' . $profileId . '&success=1&gateway=moneroo');
            } else {
                logPayment('Moneroo payment completion failed', $result);
                header('Location: /web/pay.php?profile=' . $profileId . '&error=' . urlencode($result['error'] ?? 'completion_failed'));
            }
        } elseif (in_array($status, ['failed', 'cancelled', 'expired'])) {
            // Mettre à jour la transaction comme échouée
            if ($transactionId) {
                $paymentService->updateTransaction($transactionId, [
                    'status' => 'failed',
                    'gateway_response' => $paymentData
                ]);
            }
            logPayment('Moneroo payment failed/cancelled', ['status' => $status]);
            header('Location: /web/pay.php?profile=' . $profileId . '&error=' . $status);
        } else {
            // Paiement en attente
            logPayment('Moneroo payment pending', ['status' => $status]);
            header('Location: /web/pay.php?profile=' . $profileId . '&pending=1');
        }
        exit;
    }

    // Cas 2: Webhook POST de Moneroo
    if (isset($payload['event']) && strpos($payload['event'], 'payment.') === 0) {
        $event = $payload['event'];
        $paymentData = $payload['data'] ?? [];

        logPayment('Moneroo webhook received', [
            'event' => $event,
            'payment_id' => $paymentData['id'] ?? null
        ]);

        $monerooPaymentId = $paymentData['id'] ?? null;

        if (!$monerooPaymentId) {
            logPayment('Moneroo webhook: Missing payment ID');
            http_response_code(400);
            echo json_encode(['error' => 'Missing payment ID']);
            return;
        }

        $metadata = $paymentData['metadata'] ?? [];
        $transactionId = $metadata['transaction_id'] ?? null;
        $status = $paymentData['status'] ?? 'unknown';

        switch ($event) {
            case 'payment.success':
                $result = $paymentService->completeMonerooPayment($monerooPaymentId);
                logPayment('Moneroo webhook: Payment completed', $result);
                break;

            case 'payment.failed':
            case 'payment.cancelled':
            case 'payment.expired':
                if ($transactionId) {
                    $paymentService->updateTransaction($transactionId, [
                        'status' => 'failed',
                        'gateway_response' => $paymentData
                    ]);
                }
                logPayment('Moneroo webhook: Payment ' . $event, ['transactionId' => $transactionId]);
                break;

            default:
                logPayment('Moneroo webhook: Unhandled event', ['event' => $event]);
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
        return;
    }

    // Cas inconnu
    logPayment('Moneroo callback: Unknown format', [
        'getData' => $getData,
        'payload' => $payload
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Unknown callback format']);
}

/**
 * Handler Cryptomus
 * Documentation: https://doc.cryptomus.com/merchant-api/payments/webhook
 *
 * Webhook POST avec:
 * - uuid: ID unique de la facture Cryptomus
 * - order_id: Notre transaction_id
 * - status: paid, paid_over, fail, wrong_amount, cancel, system_fail, etc.
 * - sign: Signature MD5 pour vérification
 * - amount, currency, network, txid, etc.
 */
function handleCryptomusCallback($db, $paymentService, $payload, ?int $adminId = null)
{
    logPayment('Cryptomus callback', ['payload' => $payload]);

    $transactionId = $payload['order_id'] ?? null;
    $cryptomusUuid = $payload['uuid'] ?? null;
    $status = $payload['status'] ?? null;
    $receivedSign = $payload['sign'] ?? null;

    if (!$transactionId || !$cryptomusUuid) {
        logPayment('Cryptomus callback: Missing order_id or uuid');
        http_response_code(400);
        echo json_encode(['error' => 'Missing order_id or uuid']);
        exit;
    }

    // Récupérer notre transaction
    $transaction = $paymentService->getTransaction($transactionId);

    if (!$transaction) {
        logPayment('Cryptomus callback: Transaction not found', ['transaction_id' => $transactionId]);
        http_response_code(404);
        echo json_encode(['error' => 'Transaction not found']);
        exit;
    }

    // Vérifier si déjà traitée
    if ($transaction['status'] === 'completed') {
        logPayment('Cryptomus callback: Transaction already completed', ['transaction_id' => $transactionId]);
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Already processed']);
        exit;
    }

    // Vérifier la signature Cryptomus
    // Récupérer la passerelle pour obtenir la clé API
    $gateway = $db->getGlobalGatewayByCode('cryptomus');
    if (!$gateway && $adminId) {
        $gateway = $db->getPaymentGatewayByCode('cryptomus', $adminId);
    }

    if ($gateway && $receivedSign) {
        $config = $gateway['config'];
        $apiKey = $config['payment_key'] ?? $config['api_key'] ?? '';

        if ($apiKey) {
            // Retirer le sign du payload pour vérification
            $dataToVerify = $payload;
            unset($dataToVerify['sign']);
            $expectedSign = md5(base64_encode(json_encode($dataToVerify, JSON_UNESCAPED_UNICODE)) . $apiKey);

            if ($receivedSign !== $expectedSign) {
                logPayment('Cryptomus callback: Invalid signature', [
                    'received' => $receivedSign,
                    'expected' => $expectedSign
                ]);
                http_response_code(403);
                echo json_encode(['error' => 'Invalid signature']);
                exit;
            }
            logPayment('Cryptomus callback: Signature verified');
        }
    }

    logPayment('Cryptomus transaction status', [
        'transaction_id' => $transactionId,
        'uuid' => $cryptomusUuid,
        'status' => $status
    ]);

    // Traiter selon le statut
    if (in_array($status, ['paid', 'paid_over'])) {
        $result = $paymentService->completeTransaction(
            $transactionId,
            $cryptomusUuid,
            $payload,
            $payload['txid'] ?? null
        );
        logPayment('Cryptomus payment completed', $result);

    } elseif (in_array($status, ['fail', 'wrong_amount', 'cancel', 'system_fail'])) {
        $paymentService->updateTransaction($transactionId, [
            'status' => 'failed',
            'gateway_response' => $payload
        ]);
        logPayment('Cryptomus payment failed', ['status' => $status]);

    } else {
        // confirm_check ou autre statut en attente
        logPayment('Cryptomus payment pending', ['status' => $status]);
    }

    http_response_code(200);
    echo json_encode(['success' => true]);
}

/**
 * Vérifier une transaction CinetPay avec les credentials plateforme
 */
function verifyCinetPayPlatform($db, string $transactionId): ?array
{
    // Résoudre config depuis la passerelle recharge (source unique)
    $gateway = $db->getGlobalGatewayByCode('cinetpay');

    if (!$gateway) {
        logPayment('CinetPay recharge gateway not configured');
        return null;
    }

    $config = $gateway['config'];
    $apiUrl = 'https://api-checkout.cinetpay.com/v2/payment/check';
    $cinetPayTransId = preg_replace('/[^A-Za-z0-9]/', '', $transactionId);

    $payload = json_encode([
        'apikey' => $config['api_key'],
        'site_id' => $config['site_id'],
        'transaction_id' => $cinetPayTransId
    ]);

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        logPayment('CinetPay platform verify curl error', ['error' => $error]);
        return null;
    }

    $result = json_decode($response, true);
    logPayment('CinetPay platform verify response', $result);

    if (isset($result['code']) && $result['code'] === '00' && isset($result['data'])) {
        return $result['data'];
    }

    return null;
}

/**
 * Handler YengaPay
 * Webhook POST avec reference, paymentStatus, id
 * Header X-Webhook-Hash = HMAC-SHA256 du body
 */
function handleYengaPayCallback($db, $paymentService, $payload, ?int $adminId = null, bool $isPlatform = false)
{
    $reference = $payload['reference'] ?? null;
    $paymentStatus = $payload['paymentStatus'] ?? null;
    $yengaTransactionId = $payload['id'] ?? '';

    logPayment('YengaPay callback', [
        'reference' => $reference,
        'paymentStatus' => $paymentStatus,
        'yengaId' => $yengaTransactionId
    ]);

    if (!$reference) {
        logPayment('YengaPay: no reference');
        http_response_code(400);
        echo json_encode(['error' => 'Missing reference']);
        return;
    }

    // Trouver la transaction
    $pdo = $db->getPdo();
    $stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE transaction_id = ?");
    $stmt->execute([$reference]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        logPayment('YengaPay: transaction not found: ' . $reference);
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => 'Transaction not found']);
        return;
    }

    if ($transaction['status'] === 'completed') {
        logPayment('YengaPay: already completed: ' . $reference);
        http_response_code(200);
        echo json_encode(['status' => 'ok', 'message' => 'Already processed']);
        return;
    }

    // Vérifier le webhook secret (HMAC-SHA256)
    $txIsPlatform = $isPlatform || (($transaction['is_platform'] ?? 0) == 1);
    $webhookSecret = null;

    if ($txIsPlatform) {
        // Résoudre config depuis la passerelle recharge (source unique)
        $rechargeGw = $db->getGlobalGatewayByCode('yengapay');
        if ($rechargeGw) {
            $webhookSecret = $rechargeGw['config']['webhook_secret'] ?? null;
        }
    } else {
        $txAdminId = $transaction['admin_id'] ?? $adminId;
        $gateway = $db->getPaymentGatewayByCode('yengapay', $txAdminId);
        if ($gateway) {
            $cfg = is_array($gateway['config']) ? $gateway['config'] : (json_decode($gateway['config'], true) ?? []);
            $webhookSecret = $cfg['webhook_secret'] ?? null;
        }
    }

    // Vérifier le hash si le secret est configuré
    if ($webhookSecret) {
        $receivedHash = null;
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-webhook-hash') {
                $receivedHash = $value;
                break;
            }
        }
        if (!$receivedHash) {
            $receivedHash = $_SERVER['HTTP_X_WEBHOOK_HASH'] ?? null;
        }

        if ($receivedHash) {
            $rawBody = file_get_contents('php://input');
            $calculatedHash = hash_hmac('sha256', $rawBody, $webhookSecret);
            if (!hash_equals($calculatedHash, $receivedHash)) {
                logPayment('YengaPay: invalid webhook signature');
                http_response_code(403);
                echo json_encode(['error' => 'Invalid signature']);
                return;
            }
        }
    }

    // Mapper le statut
    $status = null;
    switch ($paymentStatus) {
        case 'DONE':
            $status = 'completed';
            break;
        case 'FAILED':
        case 'CANCELLED':
            $status = 'failed';
            break;
        case 'PENDING':
            $status = 'pending';
            break;
    }

    if (!$status || $status === 'pending') {
        logPayment('YengaPay: status pending or unknown: ' . $paymentStatus);
        http_response_code(200);
        echo json_encode(['status' => 'ok']);
        return;
    }

    // Mettre à jour gateway_transaction_id
    if ($yengaTransactionId) {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET gateway_transaction_id = ? WHERE transaction_id = ?");
        $stmt->execute([(string)$yengaTransactionId, $reference]);
    }

    if ($status === 'completed') {
        $txAdminId = $transaction['admin_id'] ?? $adminId;
        $paymentService->completeTransaction($reference, $txAdminId);
        logPayment('YengaPay: payment completed for ' . $reference);
    } else {
        $stmt = $pdo->prepare("UPDATE payment_transactions SET status = 'failed' WHERE transaction_id = ?");
        $stmt->execute([$reference]);
        logPayment('YengaPay: payment failed for ' . $reference);
    }

    http_response_code(200);
    echo json_encode(['status' => 'ok']);
}
