-- =====================================================
-- Migration 043: Platform Payment Gateway (Paygate)
-- Passerelles de paiement plateforme pré-configurées.
-- L'argent va au compte plateforme, l'admin demande un retrait.
-- =====================================================

-- 1. Solde collecté par admin via passerelles plateforme
ALTER TABLE users ADD COLUMN IF NOT EXISTS paygate_balance DECIMAL(12,2) DEFAULT 0.00;

-- 2. Transactions du solde paygate (chaque crédit/débit)
CREATE TABLE IF NOT EXISTS paygate_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    type ENUM('payment_received','withdrawal','commission','adjustment','refund') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL COMMENT 'payment_transaction, withdrawal_request, manual',
    reference_id VARCHAR(100) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_paygate_tx_admin (admin_id),
    INDEX idx_paygate_tx_type (type),
    INDEX idx_paygate_tx_created (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Demandes de retrait
CREATE TABLE IF NOT EXISTS paygate_withdrawals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    amount_requested DECIMAL(12,2) NOT NULL,
    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Taux commission au moment de la demande',
    commission_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_net DECIMAL(12,2) NOT NULL COMMENT 'amount_requested - commission_amount',
    currency VARCHAR(10) DEFAULT 'XOF',
    status ENUM('pending','approved','completed','rejected','cancelled') NOT NULL DEFAULT 'pending',
    payment_method VARCHAR(50) DEFAULT NULL COMMENT 'mobile_money, bank_transfer',
    payment_details TEXT DEFAULT NULL COMMENT 'JSON: phone, bank_name, account_number, etc.',
    admin_note TEXT DEFAULT NULL,
    superadmin_note TEXT DEFAULT NULL,
    transfer_reference VARCHAR(255) DEFAULT NULL COMMENT 'Référence virement après completion',
    processed_by INT DEFAULT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_wd_admin (admin_id),
    INDEX idx_wd_status (status),
    INDEX idx_wd_requested (requested_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Passerelles plateforme (configurées par superadmin)
CREATE TABLE IF NOT EXISTS platform_payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    config TEXT DEFAULT '{}' COMMENT 'JSON: clés API configurées par superadmin',
    is_active TINYINT(1) DEFAULT 0,
    is_sandbox TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Activation par admin des passerelles plateforme
CREATE TABLE IF NOT EXISTS admin_platform_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    platform_gateway_id INT NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_admin_plat_gw (admin_id, platform_gateway_id),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (platform_gateway_id) REFERENCES platform_payment_gateways(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Flag sur payment_transactions pour distinguer paiements plateforme
ALTER TABLE payment_transactions
    ADD COLUMN IF NOT EXISTS is_platform TINYINT(1) DEFAULT 0,
    ADD COLUMN IF NOT EXISTS platform_gateway_id INT DEFAULT NULL;

-- 7. Seed passerelles plateforme
INSERT IGNORE INTO platform_payment_gateways (gateway_code, name, description, config, is_active, is_sandbox, display_order) VALUES
    ('fedapay', 'FedaPay', 'Mobile Money (Bénin, Togo, Côte d''Ivoire)', '{"public_key":"","secret_key":"","account_name":""}', 0, 1, 1),
    ('cinetpay', 'CinetPay', 'Mobile Money (Afrique de l''Ouest)', '{"site_id":"","api_key":"","secret_key":""}', 0, 1, 2);

-- 8. Settings globales Paygate
INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES
    ('paygate_enabled', '0', 'Activer le système de passerelle plateforme (Paygate)'),
    ('paygate_commission_rate', '5', 'Taux de commission (%) sur les retraits'),
    ('paygate_min_withdrawal', '1000', 'Montant minimum de retrait (en devise)'),
    ('paygate_withdrawal_currency', 'XOF', 'Devise des retraits');
