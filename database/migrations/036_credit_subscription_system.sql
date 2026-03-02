-- =====================================================
-- Migration 036: Système de Crédits & Abonnements
-- Permet aux admins de recharger des crédits via Mobile Money
-- et de payer pour activer les modules
-- =====================================================

-- 1. Ajouter credit_balance à la table users
ALTER TABLE users ADD COLUMN IF NOT EXISTS credit_balance DECIMAL(12,2) DEFAULT 0.00;

-- 2. Table credit_transactions (historique des mouvements de crédits)
CREATE TABLE IF NOT EXISTS credit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    type ENUM('recharge', 'module_activation', 'module_renewal', 'adjustment', 'refund') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id VARCHAR(100) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_credit_tx_admin (admin_id),
    INDEX idx_credit_tx_type (type),
    INDEX idx_credit_tx_created (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table module_pricing (tarification globale des modules par le SuperAdmin)
CREATE TABLE IF NOT EXISTS module_pricing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(50) NOT NULL UNIQUE,
    price_credits DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    billing_type ENUM('one_time', 'monthly') NOT NULL DEFAULT 'one_time',
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Ajouter transaction_type à payment_transactions
ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS transaction_type
    ENUM('voucher_purchase', 'credit_recharge', 'pppoe_payment') NOT NULL DEFAULT 'voucher_purchase';

-- 5. Table admin_module_subscriptions (suivi des abonnements modules par admin)
CREATE TABLE IF NOT EXISTS admin_module_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    module_code VARCHAR(50) NOT NULL,
    billing_type ENUM('one_time', 'monthly') NOT NULL,
    activated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_renewal_at TIMESTAMP NULL DEFAULT NULL,
    next_renewal_at TIMESTAMP NULL DEFAULT NULL,
    is_paid TINYINT(1) DEFAULT 1,
    UNIQUE KEY idx_admin_module_sub (admin_id, module_code),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Seed module_pricing pour les 9 modules (prix = 0 par défaut = gratuit)
INSERT IGNORE INTO module_pricing (module_code, price_credits, billing_type, description) VALUES
    ('hotspot', 0, 'one_time', 'Gestion des vouchers WiFi hotspot'),
    ('captive-portal', 0, 'one_time', 'Portail captif personnalisable'),
    ('loyalty', 0, 'monthly', 'Programme de fidélité'),
    ('chat', 0, 'monthly', 'Chat support en temps réel'),
    ('sms', 0, 'monthly', 'Notifications SMS'),
    ('pppoe', 0, 'one_time', 'Gestion PPPoE'),
    ('whatsapp', 0, 'monthly', 'Notifications WhatsApp'),
    ('telegram', 0, 'monthly', 'Notifications Telegram'),
    ('analytics', 0, 'monthly', 'Analytiques avancées');

-- 7. Seed global_settings pour la configuration des crédits
INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES
    ('credit_exchange_rate', '100', 'Montant en devise pour 1 crédit (ex: 100 XOF = 1 crédit)'),
    ('credit_currency', 'XOF', 'Devise utilisée pour les recharges de crédits'),
    ('credit_system_enabled', '1', 'Activer/désactiver le système de crédits'),
    ('free_initial_credits', '0', 'Crédits offerts aux nouveaux admins'),
    ('nas_creation_cost', '10', 'Coût en crédits pour ajouter un routeur NAS');

-- 8. Passerelles de recharge globales (SuperAdmin) — admin_id = NULL
INSERT IGNORE INTO payment_gateways (gateway_code, name, description, is_active, is_sandbox, config, display_order, admin_id) VALUES
    ('fedapay', 'FedaPay', 'Paiement Mobile Money via FedaPay (MTN, Moov, Togocel)', 0, 1, '{"public_key":"","secret_key":"","account_name":""}', 1, NULL),
    ('cinetpay', 'CinetPay', 'Paiement Mobile Money via CinetPay (Orange, MTN, Moov, Wave)', 0, 1, '{"site_id":"","api_key":"","secret_key":""}', 2, NULL),
    ('ligdicash', 'LigdiCash', 'Paiement Mobile Money via LigdiCash (Coris, Orange, Moov)', 0, 1, '{"api_key":"","auth_token":"","platform":""}', 3, NULL),
    ('cryptomus', 'Cryptomus', 'Paiement Crypto via Cryptomus (Bitcoin, USDT, ETH, etc.)', 0, 1, '{"merchant_uuid":"","payment_key":""}', 4, NULL);
