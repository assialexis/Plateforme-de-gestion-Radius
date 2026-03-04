-- Migration: Ajouter colonne is_default aux serveurs RADIUS
-- Permet de définir un serveur par défaut pour les nouvelles zones

ALTER TABLE radius_servers ADD COLUMN is_default TINYINT(1) DEFAULT 0 AFTER is_active;
