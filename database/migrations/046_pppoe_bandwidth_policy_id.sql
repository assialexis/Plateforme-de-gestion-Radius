-- Migration 046: Ajouter bandwidth_policy_id aux profils PPPoE
-- Permet de sauvegarder la politique de bande passante sélectionnée

ALTER TABLE pppoe_profiles ADD COLUMN IF NOT EXISTS bandwidth_policy_id INT DEFAULT NULL AFTER mikrotik_group;
