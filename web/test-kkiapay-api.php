<?php
/**
 * Script de test pour vérifier la configuration Kkiapay
 */

header('Content-Type: text/plain; charset=utf-8');

// Charger les dépendances
require_once __DIR__ . '/../src/Utils/helpers.php';
require_once __DIR__ . '/../src/Radius/RadiusDatabase.php';

// Charger la configuration
$config = require __DIR__ . '/../config/config.php';

echo "=== Test Configuration Kkiapay ===\n\n";

try {
    $db = new RadiusDatabase($config['database']);
    echo "Connexion BD: OK\n\n";

    // Récupérer la passerelle Kkiapay
    $gateway = $db->getPaymentGatewayByCode('kkiapay');

    if (!$gateway) {
        echo "ERREUR: Passerelle Kkiapay non trouvée dans la base de données!\n";
        echo "\nVérifiez que Kkiapay est configuré dans les passerelles de paiement.\n";
        exit(1);
    }

    echo "Passerelle trouvée:\n";
    echo "  - ID: " . $gateway['id'] . "\n";
    echo "  - Nom: " . $gateway['name'] . "\n";
    echo "  - Code: " . $gateway['gateway_code'] . "\n";
    echo "  - is_active (raw): " . var_export($gateway['is_active'], true) . "\n";
    echo "  - is_sandbox (raw): " . var_export($gateway['is_sandbox'], true) . "\n";
    echo "  - is_sandbox (bool): " . ((bool)$gateway['is_sandbox'] ? 'true' : 'false') . "\n";
    echo "\n";

    echo "Configuration:\n";
    $configData = $gateway['config'];
    foreach ($configData as $key => $value) {
        if (in_array($key, ['secret_key', 'private_key', 'secret'])) {
            echo "  - $key: " . (empty($value) ? '(vide)' : '****' . substr($value, -4)) . "\n";
        } else {
            echo "  - $key: " . (empty($value) ? '(vide)' : $value) . "\n";
        }
    }

    // Vérifier la clé publique
    echo "\n=== Vérification de la clé publique ===\n";
    $publicKey = $configData['public_key'] ?? null;
    if (empty($publicKey)) {
        echo "ERREUR: Clé publique non configurée!\n";
    } else {
        echo "Clé publique: $publicKey\n";
        echo "Longueur: " . strlen($publicKey) . " caractères\n";
    }

    // Simuler ce que le PaymentService retournerait
    echo "\n=== Simulation widget_config ===\n";
    $isSandbox = (bool)($gateway['is_sandbox'] ?? true);
    $widgetConfig = [
        'amount' => 100,
        'key' => $publicKey,
        'sandbox' => $isSandbox,
        'phone' => '97000000',
        'name' => 'Test Client',
    ];
    echo json_encode($widgetConfig, JSON_PRETTY_PRINT) . "\n";

    echo "\n=== Recommandations ===\n";
    if (!$gateway['is_active']) {
        echo "- ATTENTION: La passerelle Kkiapay n'est pas active!\n";
    }
    if ($isSandbox) {
        echo "- Mode SANDBOX activé - utilisez les numéros de test Kkiapay\n";
        echo "  Numéros de test: 97000000, 97000001, 97000002\n";
    } else {
        echo "- Mode PRODUCTION - les vrais paiements seront effectués\n";
    }

    echo "\n=== Test terminé ===\n";

} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
