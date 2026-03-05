-- Migration 057: Corriger les passerelles de paiement
-- Bug: migration 025 déplaçait les gateways globaux vers le premier admin
-- Bug: UNIQUE(gateway_code) empêchait les copies per-admin

-- 1. Changer la contrainte UNIQUE pour permettre un gateway_code par admin
ALTER TABLE payment_gateways DROP INDEX IF EXISTS gateway_code;
ALTER TABLE payment_gateways ADD UNIQUE INDEX IF NOT EXISTS idx_gateway_code_admin (gateway_code, admin_id);

-- 2. Remettre les gateways globaux (admin_id IS NULL) avec toutes les passerelles
-- Les credentials restent vides, le SuperAdmin les configure ensuite
INSERT IGNORE INTO payment_gateways (gateway_code, name, description, logo_url, is_active, is_sandbox, config, display_order, admin_id) VALUES
    ('fedapay', 'FedaPay', 'Paiement Mobile Money via FedaPay (MTN, Moov, Togocel)', 'https://fedapay.com/assets/images/logo.svg', 0, 1, '{"account_name":"","public_key":"","secret_key":""}', 1, NULL),
    ('cinetpay', 'CinetPay', 'Paiement Mobile Money via CinetPay (Orange, MTN, Moov, Wave)', 'https://cinetpay.com/images/logo.png', 0, 1, '{"site_id":"","api_key":"","secret_key":""}', 2, NULL),
    ('ligdicash', 'LigdiCash', 'Paiement Mobile Money via LigdiCash (Coris, Orange, Moov)', NULL, 0, 1, '{"api_key":"","auth_token":"","platform":""}', 3, NULL),
    ('cryptomus', 'Cryptomus', 'Paiement Crypto via Cryptomus (Bitcoin, USDT, ETH, etc.)', NULL, 0, 1, '{"merchant_uuid":"","payment_key":""}', 4, NULL),
    ('feexpay', 'FeexPay', 'Paiement Mobile Money Afrique de l''Ouest', 'https://feexpay.me/assets/logo.png', 0, 1, '{"shop_id":"","api_key":"","operator":"mtn"}', 5, NULL),
    ('paygate_global', 'PayGate Global', 'Paiement mobile money Togo (Flooz, TMoney)', 'https://paygateglobal.com/assets/logo.png', 0, 0, '{"auth_token":""}', 6, NULL),
    ('kkiapay', 'KkiaPay', 'Paiement Mobile Money et Carte (Bénin, Côte d''Ivoire)', NULL, 0, 1, '{"public_key":"","private_key":"","secret":""}', 7, NULL),
    ('paydunya', 'PayDunya', 'Paiement Mobile Money Afrique de l''Ouest (Orange, Wave, MTN, Moov)', 'https://paydunya.com/assets/images/logo.png', 0, 1, '{"master_key":"","private_key":"","token":"","store_name":""}', 8, NULL),
    ('yengapay', 'YengaPay', 'Paiement Mobile Money (Afrique Centrale)', NULL, 0, 1, '{"groupe_id":"","api_key":"","project_id":""}', 9, NULL),
    ('moneroo', 'Moneroo', 'Paiement unifié Mobile Money et Carte', NULL, 0, 1, '{"secret_key":"","public_key":""}', 10, NULL);

-- 3. Seed platform_payment_gateways (passerelles Paygate pré-configurées)
INSERT IGNORE INTO platform_payment_gateways (gateway_code, name, description, is_active, is_sandbox, display_order) VALUES
    ('fedapay', 'FedaPay', 'Mobile Money (Bénin, Togo, Côte d''Ivoire)', 0, 1, 1),
    ('cinetpay', 'CinetPay', 'Mobile Money (Afrique de l''Ouest)', 0, 1, 2),
    ('ligdicash', 'LigdiCash', 'Mobile Money (Coris, Orange, Moov)', 0, 1, 3),
    ('cryptomus', 'Cryptomus', 'Crypto (Bitcoin, USDT, ETH)', 0, 1, 4),
    ('feexpay', 'FeexPay', 'Mobile Money Afrique de l''Ouest', 0, 1, 5),
    ('paygate_global', 'PayGate Global', 'Mobile Money Togo (Flooz, TMoney)', 0, 0, 6),
    ('kkiapay', 'KkiaPay', 'Mobile Money et Carte (Bénin, Côte d''Ivoire)', 0, 1, 7),
    ('paydunya', 'PayDunya', 'Mobile Money (Orange, Wave, MTN, Moov)', 0, 1, 8),
    ('yengapay', 'YengaPay', 'Mobile Money (Afrique Centrale)', 0, 1, 9),
    ('moneroo', 'Moneroo', 'Paiement unifié Mobile Money et Carte', 0, 1, 10);
