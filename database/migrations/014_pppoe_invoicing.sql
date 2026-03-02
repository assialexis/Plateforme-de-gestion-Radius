-- Migration: Système de facturation PPPoE
-- Date: 2025-12-07
-- Description: Tables pour la gestion des factures et paiements clients PPPoE

-- 1. Table des factures
CREATE TABLE IF NOT EXISTS pppoe_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE COMMENT 'Numéro de facture unique',
    pppoe_user_id INT NOT NULL,

    -- Période de facturation
    period_start DATE NOT NULL COMMENT 'Début de la période',
    period_end DATE NOT NULL COMMENT 'Fin de la période',

    -- Montants
    amount DECIMAL(10,2) NOT NULL COMMENT 'Montant HT',
    tax_rate DECIMAL(5,2) DEFAULT 0 COMMENT 'Taux de taxe en %',
    tax_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Montant de la taxe',
    total_amount DECIMAL(10,2) NOT NULL COMMENT 'Montant TTC',

    -- Statut
    status ENUM('draft', 'pending', 'paid', 'partial', 'overdue', 'cancelled') DEFAULT 'pending',
    due_date DATE NOT NULL COMMENT 'Date d\'échéance',

    -- Paiement
    paid_amount DECIMAL(10,2) DEFAULT 0 COMMENT 'Montant payé',
    paid_date TIMESTAMP NULL COMMENT 'Date du dernier paiement',
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'online', 'other') DEFAULT NULL,
    payment_reference VARCHAR(100) DEFAULT NULL COMMENT 'Référence de paiement',

    -- Détails
    description TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,

    -- Métadonnées
    created_by INT NULL COMMENT 'Utilisateur ayant créé la facture',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_invoices_user (pppoe_user_id),
    INDEX idx_invoices_status (status),
    INDEX idx_invoices_due_date (due_date),
    INDEX idx_invoices_number (invoice_number),

    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des lignes de facture
CREATE TABLE IF NOT EXISTS pppoe_invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,

    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(10,2) DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,

    -- Type d'item
    item_type ENUM('subscription', 'installation', 'equipment', 'service', 'other') DEFAULT 'subscription',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_invoice_items_invoice (invoice_id),
    FOREIGN KEY (invoice_id) REFERENCES pppoe_invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des paiements
CREATE TABLE IF NOT EXISTS pppoe_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    pppoe_user_id INT NOT NULL,

    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'mobile_money', 'bank_transfer', 'online', 'other') NOT NULL,
    payment_reference VARCHAR(100) DEFAULT NULL,

    -- Détails Mobile Money
    mobile_money_provider VARCHAR(50) DEFAULT NULL COMMENT 'MTN, Orange, Moov, etc.',
    mobile_money_number VARCHAR(20) DEFAULT NULL,
    transaction_id VARCHAR(100) DEFAULT NULL,

    notes TEXT DEFAULT NULL,

    -- Métadonnées
    received_by INT NULL COMMENT 'Utilisateur ayant reçu le paiement',
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_payments_invoice (invoice_id),
    INDEX idx_payments_user (pppoe_user_id),
    INDEX idx_payments_date (payment_date),

    FOREIGN KEY (invoice_id) REFERENCES pppoe_invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table de configuration facturation
CREATE TABLE IF NOT EXISTS billing_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les paramètres par défaut
INSERT INTO billing_settings (setting_key, setting_value, description) VALUES
('invoice_prefix', 'FAC', 'Préfixe des numéros de facture'),
('invoice_next_number', '1', 'Prochain numéro de facture'),
('default_tax_rate', '0', 'Taux de taxe par défaut (%)'),
('payment_due_days', '7', 'Délai de paiement par défaut (jours)'),
('company_name', 'Mon Entreprise ISP', 'Nom de l\'entreprise'),
('company_address', '', 'Adresse de l\'entreprise'),
('company_phone', '', 'Téléphone de l\'entreprise'),
('company_email', '', 'Email de l\'entreprise'),
('invoice_footer', 'Merci pour votre confiance.', 'Texte en bas de facture'),
('auto_generate_invoice', '1', 'Générer automatiquement les factures à l\'activation')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- 5. Ajouter colonne balance au client PPPoE
ALTER TABLE pppoe_users
ADD COLUMN balance DECIMAL(10,2) DEFAULT 0 COMMENT 'Solde du compte client' AFTER sale_amount;
