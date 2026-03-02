-- =====================================================
-- Migration 037: Système de Crédits SMS (CSMS)
-- Permet aux admins de convertir des CRT en CSMS
-- et d'envoyer des SMS via la passerelle plateforme
-- =====================================================

-- 1. Ajouter sms_credit_balance à la table users
ALTER TABLE users ADD COLUMN IF NOT EXISTS sms_credit_balance DECIMAL(12,2) DEFAULT 0.00;

-- 2. Table sms_credit_transactions (historique des mouvements CSMS)
CREATE TABLE IF NOT EXISTS sms_credit_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    type ENUM('conversion', 'sms_sent', 'adjustment', 'refund') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id VARCHAR(100) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_credit_tx_admin (admin_id),
    INDEX idx_sms_credit_tx_type (type),
    INDEX idx_sms_credit_tx_created (created_at),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Seed global_settings pour la configuration CSMS
INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES
    ('sms_credit_cost_fcfa', '25', 'Coût en FCFA par 1 SMS (CSMS)'),
    ('sms_credit_enabled', '1', 'Activer/désactiver le système de crédits SMS (CSMS)'),
    ('platform_sms_provider', '', 'Code du provider SMS backend (ex: nghcorp)'),
    ('platform_sms_config', '{}', 'Configuration JSON du provider SMS backend (api_key, api_secret, sender_id)');
