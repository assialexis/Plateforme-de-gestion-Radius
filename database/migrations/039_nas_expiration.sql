-- =====================================================
-- Migration 039: Expiration des NAS
-- Ajoute la date d'expiration et la durée de validité configurable
-- =====================================================

-- 1. Ajouter la colonne expires_at à la table nas
ALTER TABLE nas ADD COLUMN IF NOT EXISTS expires_at DATETIME DEFAULT NULL COMMENT 'Date d expiration du NAS';

-- 2. Index pour faciliter les requêtes sur l'expiration
CREATE INDEX IF NOT EXISTS idx_nas_expires ON nas (expires_at);

-- 3. Paramètre global pour la durée de validité d'un NAS (en jours, 0 = illimité)
INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES
    ('nas_validity_days', '30', 'Durée de validité d un NAS en jours (0 = illimité)');
