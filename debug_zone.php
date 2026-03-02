<?php
/**
 * Script de diagnostic pour les zones NAS
 * Usage: php debug_zone.php
 */

require_once __DIR__ . '/src/Radius/RadiusDatabase.php';
$config = require __DIR__ . '/config/config.php';
$db = new RadiusDatabase($config['database']);

echo "=== Diagnostic des Zones NAS ===\n\n";

// 1. Lister tous les NAS avec leurs zones
echo "1. Liste des NAS enregistrés:\n";
echo str_repeat("-", 80) . "\n";
$nasList = $db->getAllNas();
foreach ($nasList as $nas) {
    echo "   ID: {$nas['id']}\n";
    echo "   Router ID (NAS-Identifier): '{$nas['router_id']}'\n";
    echo "   Shortname: '{$nas['shortname']}'\n";
    echo "   Zone ID: " . ($nas['zone_id'] ?? 'NULL (global)') . "\n";
    echo "   Zone Name: " . ($nas['zone_name'] ?? 'Aucune') . "\n";
    echo str_repeat("-", 80) . "\n";
}

// 2. Lister toutes les zones
echo "\n2. Liste des Zones:\n";
echo str_repeat("-", 80) . "\n";
$zones = $db->getAllZones();
foreach ($zones as $zone) {
    echo "   ID: {$zone['id']} - Nom: '{$zone['name']}' - Code: '{$zone['code']}'\n";
}

// 3. Tester la recherche par NAS-Identifier
echo "\n3. Test de recherche par NAS-Identifier:\n";
echo str_repeat("-", 80) . "\n";

foreach ($nasList as $nas) {
    if ($nas['router_id']) {
        $foundZone = $db->getNasZoneByIdentifier($nas['router_id']);
        echo "   Recherche '{$nas['router_id']}' -> Zone ID: " . ($foundZone ?? 'NULL') . "\n";
    }
    if ($nas['shortname']) {
        $foundZone = $db->getNasZoneByIdentifier($nas['shortname']);
        echo "   Recherche '{$nas['shortname']}' -> Zone ID: " . ($foundZone ?? 'NULL') . "\n";
    }
}

// 4. Lister les vouchers avec zones
echo "\n4. Vouchers avec zones assignées:\n";
echo str_repeat("-", 80) . "\n";

$stmt = $db->getPdo()->query("
    SELECT v.username, v.zone_id, z.name as zone_name
    FROM vouchers v
    LEFT JOIN zones z ON v.zone_id = z.id
    WHERE v.zone_id IS NOT NULL
    LIMIT 10
");
$vouchers = $stmt->fetchAll();

if (empty($vouchers)) {
    echo "   Aucun voucher avec zone assignée.\n";
} else {
    foreach ($vouchers as $v) {
        echo "   Voucher: '{$v['username']}' - Zone ID: {$v['zone_id']} ({$v['zone_name']})\n";
    }
}

echo "\n=== Fin du diagnostic ===\n";
echo "\nConseil: Le 'Router ID' ci-dessus doit correspondre EXACTEMENT à l'identité système\n";
echo "de votre MikroTik (sensible à la casse!).\n\n";
echo "Pour voir l'identité système sur MikroTik:\n";
echo "  /system/identity/print\n\n";
echo "Le serveur RADIUS utilise l'attribut NAS-Identifier (attr 32) qui contient\n";
echo "l'identité système pour identifier la zone.\n";
