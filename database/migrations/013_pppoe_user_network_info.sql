-- Migration: Ajout des informations réseau aux clients PPPoE
-- Date: 2025-12-07
-- Description: Stocke la dernière adresse MAC et IP connues du client

-- Ajouter les colonnes pour les informations réseau
ALTER TABLE pppoe_users
ADD COLUMN last_mac VARCHAR(50) DEFAULT NULL COMMENT 'Dernière adresse MAC connue' AFTER equipment_serial,
ADD COLUMN last_ip VARCHAR(45) DEFAULT NULL COMMENT 'Dernière adresse IP utilisée' AFTER last_mac;

-- Index pour recherche par MAC
CREATE INDEX idx_pppoe_users_last_mac ON pppoe_users(last_mac);
