-- Migration 044: Paygate per-gateway settings
-- Déplacer commission_rate, min_withdrawal, withdrawal_currency de global_settings vers platform_payment_gateways

-- Ajouter colonnes per-gateway sur platform_payment_gateways
ALTER TABLE platform_payment_gateways
    ADD COLUMN IF NOT EXISTS commission_rate DECIMAL(5,2) DEFAULT 5.00,
    ADD COLUMN IF NOT EXISTS min_withdrawal DECIMAL(12,2) DEFAULT 1000.00,
    ADD COLUMN IF NOT EXISTS withdrawal_currency VARCHAR(10) DEFAULT 'XOF';

-- Lier retrait à la passerelle source
ALTER TABLE paygate_withdrawals
    ADD COLUMN IF NOT EXISTS platform_gateway_id INT DEFAULT NULL;

-- Copier les valeurs globales existantes vers chaque gateway
UPDATE platform_payment_gateways SET
    commission_rate = COALESCE((SELECT CAST(setting_value AS DECIMAL(5,2)) FROM global_settings WHERE setting_key = 'paygate_commission_rate'), 5.00),
    min_withdrawal = COALESCE((SELECT CAST(setting_value AS DECIMAL(12,2)) FROM global_settings WHERE setting_key = 'paygate_min_withdrawal'), 1000.00),
    withdrawal_currency = COALESCE((SELECT setting_value FROM global_settings WHERE setting_key = 'paygate_withdrawal_currency'), 'XOF');
