-- =====================================================
-- Migration: Système de fidélité/récompense
-- Basé sur le numéro de téléphone client (Mobile Money)
-- =====================================================

-- =====================================================
-- Table des clients fidèles (identifiés par téléphone)
-- =====================================================
CREATE TABLE IF NOT EXISTS loyalty_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) NOT NULL UNIQUE COMMENT 'Numéro de téléphone (identifiant unique)',
    customer_name VARCHAR(100) DEFAULT NULL COMMENT 'Nom du client',

    -- Statistiques d'achats
    total_purchases INT DEFAULT 0 COMMENT 'Nombre total d''achats',
    total_spent DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Montant total dépensé',

    -- Points de fidélité
    points_earned INT DEFAULT 0 COMMENT 'Points totaux gagnés',
    points_used INT DEFAULT 0 COMMENT 'Points utilisés',
    points_balance INT DEFAULT 0 COMMENT 'Solde de points actuel',

    -- Récompenses
    rewards_earned INT DEFAULT 0 COMMENT 'Nombre de récompenses gagnées',
    last_reward_at DATETIME DEFAULT NULL COMMENT 'Date dernière récompense',

    -- Dates
    first_purchase_at DATETIME DEFAULT NULL COMMENT 'Date premier achat',
    last_purchase_at DATETIME DEFAULT NULL COMMENT 'Date dernier achat',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_phone (phone),
    INDEX idx_points (points_balance),
    INDEX idx_purchases (total_purchases)
) ENGINE=InnoDB;

-- =====================================================
-- Table des règles de fidélité
-- =====================================================
CREATE TABLE IF NOT EXISTS loyalty_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom de la règle',
    description VARCHAR(255) DEFAULT NULL,
    rule_type ENUM('purchase_count', 'amount_spent', 'points') DEFAULT 'purchase_count' COMMENT 'Type de règle',

    -- Conditions pour gagner la récompense
    threshold_value INT NOT NULL COMMENT 'Seuil pour déclencher (nb achats, montant, ou points)',

    -- Récompense
    reward_type ENUM('free_voucher', 'discount_percent', 'bonus_time', 'bonus_data') DEFAULT 'free_voucher',
    reward_profile_id INT DEFAULT NULL COMMENT 'Profil du voucher gratuit à offrir',
    reward_value INT DEFAULT NULL COMMENT 'Valeur: % réduction, secondes bonus, octets bonus',

    -- Points générés par cette règle (pour système de points)
    points_per_purchase INT DEFAULT 1 COMMENT 'Points gagnés par achat',
    points_per_amount INT DEFAULT 0 COMMENT 'Points gagnés par tranche de montant (ex: 1 point par 100 XOF)',
    points_amount_unit INT DEFAULT 100 COMMENT 'Unité de montant pour les points (ex: 100 XOF)',

    -- Statut
    is_active TINYINT(1) DEFAULT 1,
    is_cumulative TINYINT(1) DEFAULT 0 COMMENT 'Peut être cumulé avec autres règles',

    -- Limites
    max_rewards_per_customer INT DEFAULT NULL COMMENT 'Limite de récompenses par client (NULL = illimité)',
    valid_from DATETIME DEFAULT NULL,
    valid_until DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_type (rule_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (reward_profile_id) REFERENCES profiles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table historique des achats pour fidélité
-- =====================================================
CREATE TABLE IF NOT EXISTS loyalty_purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Référence client fidélité',
    transaction_id VARCHAR(100) NOT NULL COMMENT 'ID transaction paiement',

    -- Détails achat
    amount DECIMAL(10,2) NOT NULL,
    profile_id INT DEFAULT NULL,
    profile_name VARCHAR(100) DEFAULT NULL,

    -- Points gagnés sur cet achat
    points_earned INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_customer (customer_id),
    INDEX idx_transaction (transaction_id),
    FOREIGN KEY (customer_id) REFERENCES loyalty_customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table des récompenses attribuées
-- =====================================================
CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL COMMENT 'Client bénéficiaire',
    rule_id INT DEFAULT NULL COMMENT 'Règle qui a déclenché la récompense',

    -- Type et valeur
    reward_type ENUM('free_voucher', 'discount_percent', 'bonus_time', 'bonus_data') NOT NULL,
    reward_value INT DEFAULT NULL,

    -- Voucher offert (si free_voucher)
    voucher_id INT DEFAULT NULL,
    voucher_code VARCHAR(64) DEFAULT NULL,
    profile_name VARCHAR(100) DEFAULT NULL,

    -- Points utilisés (si récompense par points)
    points_used INT DEFAULT 0,

    -- Statut
    status ENUM('pending', 'claimed', 'expired', 'cancelled') DEFAULT 'pending',
    claimed_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,

    -- Notification
    sms_sent TINYINT(1) DEFAULT 0,
    sms_sent_at DATETIME DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_customer (customer_id),
    INDEX idx_status (status),
    INDEX idx_rule (rule_id),
    FOREIGN KEY (customer_id) REFERENCES loyalty_customers(id) ON DELETE CASCADE,
    FOREIGN KEY (rule_id) REFERENCES loyalty_rules(id) ON DELETE SET NULL,
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Règles de fidélité par défaut
-- =====================================================
INSERT INTO loyalty_rules (name, description, rule_type, threshold_value, reward_type, reward_profile_id, points_per_purchase, is_active) VALUES
('5 achats = 1 gratuit', 'Après 5 achats, recevez un voucher gratuit du même profil que votre dernier achat', 'purchase_count', 5, 'free_voucher', NULL, 1, 1),
('Programme points', 'Gagnez 1 point par achat, échangez 10 points contre un voucher', 'points', 10, 'free_voucher', NULL, 1, 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- Ajouter colonne operator_reference à payment_transactions
-- Pour stocker la référence opérateur Mobile Money
-- =====================================================
ALTER TABLE payment_transactions
ADD COLUMN IF NOT EXISTS operator_reference VARCHAR(100) DEFAULT NULL COMMENT 'Référence opérateur Mobile Money' AFTER gateway_response;
