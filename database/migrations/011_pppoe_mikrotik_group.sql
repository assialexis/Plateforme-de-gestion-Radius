-- Migration: Ajout du champ mikrotik_group aux profils PPPoE
-- Date: 2025-12-07
-- Description: Permet d'assigner un profil PPP MikroTik via RADIUS (Mikrotik-Group attribute)

-- Ajouter la colonne mikrotik_group à la table pppoe_profiles
ALTER TABLE pppoe_profiles
ADD COLUMN mikrotik_group VARCHAR(100) DEFAULT NULL COMMENT 'Nom du profil PPP sur MikroTik (Mikrotik-Group)' AFTER ip_pool_name;
