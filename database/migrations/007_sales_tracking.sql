-- Migration: Système de suivi des ventes et rémunération
-- Date: 2025-12-04

-- 1. Ajouter les colonnes de traçabilité à la table vouchers
ALTER TABLE vouchers
    ADD COLUMN IF NOT EXISTS sold_by INT NULL COMMENT 'ID du vendeur/gérant qui a vendu le ticket',
    ADD COLUMN IF NOT EXISTS sold_at TIMESTAMP NULL COMMENT 'Date de vente (première utilisation)',
    ADD COLUMN IF NOT EXISTS sold_on_nas_id INT NULL COMMENT 'ID du NAS où le ticket a été utilisé pour la première fois',
    ADD COLUMN IF NOT EXISTS payment_method ENUM('cash', 'mobile_money', 'online', 'free') DEFAULT NULL COMMENT 'Méthode de paiement',
    ADD COLUMN IF NOT EXISTS sale_amount DECIMAL(10,2) DEFAULT NULL COMMENT 'Montant de la vente',
    ADD COLUMN IF NOT EXISTS commission_vendeur DECIMAL(10,2) DEFAULT 0 COMMENT 'Commission du vendeur',
    ADD COLUMN IF NOT EXISTS commission_gerant DECIMAL(10,2) DEFAULT 0 COMMENT 'Commission du gérant',
    ADD COLUMN IF NOT EXISTS commission_admin DECIMAL(10,2) DEFAULT 0 COMMENT 'Commission de l\'admin',
    ADD COLUMN IF NOT EXISTS commission_paid TINYINT(1) DEFAULT 0 COMMENT 'Commissions payées';

-- 2. Index pour les recherches de ventes
CREATE INDEX IF NOT EXISTS idx_vouchers_sold_by ON vouchers(sold_by);
CREATE INDEX IF NOT EXISTS idx_vouchers_sold_at ON vouchers(sold_at);
CREATE INDEX IF NOT EXISTS idx_vouchers_sold_on_nas ON vouchers(sold_on_nas_id);
CREATE INDEX IF NOT EXISTS idx_vouchers_payment_method ON vouchers(payment_method);

-- 3. Table des taux de commission par rôle
CREATE TABLE IF NOT EXISTS commission_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('vendeur', 'gerant', 'admin') NOT NULL,
    zone_id INT NULL COMMENT 'Zone spécifique ou NULL pour global',
    profile_id INT NULL COMMENT 'Profil spécifique ou NULL pour global',
    rate_type ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    rate_value DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Pourcentage ou montant fixe',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_commission (role, zone_id, profile_id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table historique des paiements de commissions
CREATE TABLE IF NOT EXISTS commission_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Bénéficiaire de la commission',
    amount DECIMAL(10,2) NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    vouchers_count INT DEFAULT 0 COMMENT 'Nombre de tickets concernés',
    total_sales DECIMAL(10,2) DEFAULT 0 COMMENT 'Total des ventes',
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    paid_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insérer les taux de commission par défaut
INSERT INTO commission_rates (role, rate_type, rate_value) VALUES
    ('vendeur', 'percentage', 10.00),
    ('gerant', 'percentage', 5.00),
    ('admin', 'percentage', 2.00)
ON DUPLICATE KEY UPDATE rate_value = VALUES(rate_value);

-- 6. Vue pour les statistiques de ventes
CREATE OR REPLACE VIEW v_sales_stats AS
SELECT
    v.id as voucher_id,
    v.username as ticket_code,
    v.sold_by,
    u_seller.username as seller_username,
    u_seller.full_name as seller_name,
    u_seller.role as seller_role,
    u_seller.parent_id as seller_parent_id,
    u_parent.username as parent_username,
    u_parent.full_name as parent_name,
    v.sold_at,
    v.sold_on_nas_id,
    n.shortname as nas_name,
    n.router_id,
    z.id as zone_id,
    z.name as zone_name,
    p.id as profile_id,
    p.name as profile_name,
    p.price as profile_price,
    v.payment_method,
    v.sale_amount,
    v.commission_vendeur,
    v.commission_gerant,
    v.commission_admin,
    v.commission_paid
FROM vouchers v
LEFT JOIN users u_seller ON v.sold_by = u_seller.id
LEFT JOIN users u_parent ON u_seller.parent_id = u_parent.id
LEFT JOIN nas n ON v.sold_on_nas_id = n.id
LEFT JOIN zones z ON n.zone_id = z.id
LEFT JOIN profiles p ON v.profile_id = p.id
WHERE v.sold_at IS NOT NULL;
