-- Migration: Ajouter validité flexible aux profils
-- Date: 2025-12-04

-- Ajouter la colonne validity (en secondes) si elle n'existe pas
ALTER TABLE profiles
ADD COLUMN IF NOT EXISTS validity INT DEFAULT NULL COMMENT 'Validité du voucher en secondes (durée de vie après première connexion)';

-- Ajouter la colonne validity_unit pour l'affichage
ALTER TABLE profiles
ADD COLUMN IF NOT EXISTS validity_unit ENUM('minutes', 'hours', 'days') DEFAULT 'days' COMMENT 'Unité pour affichage';

-- Migrer les données existantes de validity_days vers validity (convertir en secondes)
UPDATE profiles
SET validity = validity_days * 86400,
    validity_unit = 'days'
WHERE validity_days IS NOT NULL AND validity IS NULL;

-- Optionnel: Supprimer l'ancienne colonne validity_days après migration
-- ALTER TABLE profiles DROP COLUMN IF EXISTS validity_days;
