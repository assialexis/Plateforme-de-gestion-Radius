-- Migration: Ajouter les champs de configuration API MikroTik à la table NAS
-- Date: 2026-02-17

ALTER TABLE nas
    ADD COLUMN mikrotik_host VARCHAR(255) DEFAULT NULL COMMENT 'IP ou DDNS du MikroTik pour connexion API' AFTER address,
    ADD COLUMN mikrotik_api_port INT DEFAULT 8728 COMMENT 'Port API MikroTik (8728=plain, 8729=SSL)' AFTER mikrotik_host,
    ADD COLUMN mikrotik_api_username VARCHAR(100) DEFAULT NULL COMMENT 'Nom utilisateur API MikroTik' AFTER mikrotik_api_port,
    ADD COLUMN mikrotik_api_password VARCHAR(255) DEFAULT NULL COMMENT 'Mot de passe API MikroTik' AFTER mikrotik_api_username,
    ADD COLUMN mikrotik_use_ssl TINYINT(1) DEFAULT 0 COMMENT 'Utiliser SSL pour API MikroTik' AFTER mikrotik_api_password;
