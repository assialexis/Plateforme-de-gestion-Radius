-- Migration: Programme de Fidélité per Admin
-- Date: 2026-02-18
-- Description: Ajouter admin_id aux tables loyalty pour isolation multi-tenant

-- =============================================
-- 1. Ajouter admin_id à loyalty_customers
-- =============================================
ALTER TABLE loyalty_customers ADD COLUMN admin_id INT DEFAULT NULL;

-- Changer l'index UNIQUE de phone vers (phone, admin_id)
ALTER TABLE loyalty_customers DROP INDEX phone;
ALTER TABLE loyalty_customers ADD UNIQUE INDEX idx_phone_admin (phone, admin_id);

-- Assigner les données existantes à admin 1
UPDATE loyalty_customers SET admin_id = 1 WHERE admin_id IS NULL;

-- =============================================
-- 2. Ajouter admin_id à loyalty_rules
-- =============================================
ALTER TABLE loyalty_rules ADD COLUMN admin_id INT DEFAULT NULL;
UPDATE loyalty_rules SET admin_id = 1 WHERE admin_id IS NULL;

-- =============================================
-- 3. Ajouter admin_id à loyalty_purchases
-- =============================================
ALTER TABLE loyalty_purchases ADD COLUMN admin_id INT DEFAULT NULL;
UPDATE loyalty_purchases SET admin_id = 1 WHERE admin_id IS NULL;

-- =============================================
-- 4. Ajouter admin_id à loyalty_rewards
-- =============================================
ALTER TABLE loyalty_rewards ADD COLUMN admin_id INT DEFAULT NULL;
UPDATE loyalty_rewards SET admin_id = 1 WHERE admin_id IS NULL;

-- =============================================
-- 5. Dupliquer les règles pour les autres admins
-- =============================================
INSERT INTO loyalty_rules (name, description, rule_type, threshold_value, reward_type, reward_profile_id, reward_value, points_per_purchase, points_per_amount, points_amount_unit, is_active, is_cumulative, max_rewards_per_customer, valid_from, valid_until, admin_id)
SELECT lr.name, lr.description, lr.rule_type, lr.threshold_value, lr.reward_type, lr.reward_profile_id, lr.reward_value, lr.points_per_purchase, lr.points_per_amount, lr.points_amount_unit, lr.is_active, lr.is_cumulative, lr.max_rewards_per_customer, lr.valid_from, lr.valid_until, u.id
FROM loyalty_rules lr
CROSS JOIN users u
WHERE lr.admin_id = 1 AND u.role = 'admin' AND u.id != 1
AND u.id NOT IN (SELECT DISTINCT admin_id FROM loyalty_rules WHERE admin_id IS NOT NULL AND admin_id != 1);
