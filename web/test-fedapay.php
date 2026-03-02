<?php
/**
 * Test direct de l'API FedaPay
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

$config = require __DIR__ . '/../config/config.php';

try {
    $db = new RadiusDatabase($config['database']);
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}

// Récupérer les infos FedaPay
$gateway = $db->getPaymentGatewayByCode('fedapay');

if (!$gateway) {
    die('FedaPay gateway not found');
}

echo "<pre>";
echo "=== Configuration FedaPay ===\n";
echo "is_sandbox: " . ($gateway['is_sandbox'] ? 'true' : 'false') . "\n";
echo "is_active: " . ($gateway['is_active'] ? 'true' : 'false') . "\n";

$gatewayConfig = $gateway['config'];
echo "secret_key starts with: " . substr($gatewayConfig['secret_key'] ?? '', 0, 10) . "...\n";
echo "public_key starts with: " . substr($gatewayConfig['public_key'] ?? '', 0, 10) . "...\n\n";

// Déterminer l'URL API
$isSandbox = $gateway['is_sandbox'] ?? true;
$apiUrl = $isSandbox ? 'https://sandbox-api.fedapay.com/v1' : 'https://api.fedapay.com/v1';

echo "API URL: $apiUrl\n\n";

// Créer une transaction test
$payload = [
    'description' => 'Test transaction',
    'amount' => 100,
    'currency' => [
        'iso' => 'XOF'
    ],
    'callback_url' => 'http://nas.test/web/payment-callback.php',
    'customer' => [
        'firstname' => 'Test',
        'lastname' => 'User',
        'email' => 'test@example.com',
        'phone_number' => [
            'number' => '+22990000000',
            'country' => 'bj'
        ]
    ]
];

echo "=== Payload ===\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Faire la requête
$ch = curl_init($apiUrl . '/transactions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $gatewayConfig['secret_key'],
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "=== Response ===\n";
echo "HTTP Code: $httpCode\n";
if ($error) {
    echo "cURL Error: $error\n";
}
echo "Raw Response:\n";
echo $response . "\n\n";

$decoded = json_decode($response, true);
echo "=== Decoded Response ===\n";
print_r($decoded);

// Vérifier les clés disponibles
if ($decoded) {
    echo "\n=== Available Keys ===\n";
    print_r(array_keys($decoded));

    // Chercher la transaction
    if (isset($decoded['v1/transaction'])) {
        echo "\n=== Transaction found at 'v1/transaction' ===\n";
        print_r($decoded['v1/transaction']);
    }
    if (isset($decoded['transaction'])) {
        echo "\n=== Transaction found at 'transaction' ===\n";
        print_r($decoded['transaction']);
    }
}

echo "</pre>";
