-- Ajouter PayGate Global comme passerelle de paiement
INSERT INTO payment_gateways (gateway_code, name, description, logo_url, is_active, is_sandbox, config, display_order)
VALUES ('paygate', 'PayGate Global', 'Paiement mobile money Togo (Flooz, TMoney)', 'https://paygateglobal.com/assets/logo.png', 0, 0, '{"auth_token": ""}', 4)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    description = VALUES(description),
    logo_url = VALUES(logo_url);
