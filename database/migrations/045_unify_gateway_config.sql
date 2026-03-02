-- Migration 045: Unifier la configuration API des passerelles
-- Les clés API ne sont stockées que dans payment_gateways (recharge gateways).
-- platform_payment_gateways ne garde que: is_active, commission_rate, min_withdrawal, withdrawal_currency.

-- Étape 1: S'assurer que chaque passerelle plateforme a un équivalent recharge
INSERT IGNORE INTO payment_gateways (gateway_code, name, description, is_active, is_sandbox, config, admin_id)
SELECT pg.gateway_code, pg.name, pg.description, 0, pg.is_sandbox, pg.config, NULL
FROM platform_payment_gateways pg
WHERE NOT EXISTS (
    SELECT 1 FROM payment_gateways rg
    WHERE rg.gateway_code COLLATE utf8mb4_unicode_ci = pg.gateway_code COLLATE utf8mb4_unicode_ci AND rg.admin_id IS NULL
);

-- Étape 2: Copier config plateforme → recharge SI recharge est vide (éviter perte de données)
UPDATE payment_gateways rg
INNER JOIN platform_payment_gateways pg ON pg.gateway_code COLLATE utf8mb4_unicode_ci = rg.gateway_code COLLATE utf8mb4_unicode_ci
SET rg.config = pg.config, rg.is_sandbox = pg.is_sandbox
WHERE rg.admin_id IS NULL
  AND (rg.config IS NULL OR rg.config = '{}' OR rg.config = '' OR rg.config = 'null')
  AND pg.config IS NOT NULL AND pg.config != '{}' AND pg.config != '';

-- Étape 3: Vider config de platform_payment_gateways (plus utilisé)
UPDATE platform_payment_gateways SET config = '{}';
