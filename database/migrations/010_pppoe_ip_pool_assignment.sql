-- Migration: Support assignation IP depuis pool pour clients PPPoE
-- Ajoute le support pour le mode d'assignation IP dynamique depuis un pool

-- 1. Ajouter les colonnes ip_mode, pool_id, pool_ip à pppoe_users
ALTER TABLE pppoe_users
    ADD COLUMN IF NOT EXISTS ip_mode ENUM('router', 'static', 'pool') DEFAULT 'router'
        COMMENT 'Mode assignation IP: router=auto, static=manuel, pool=depuis pool' AFTER static_ip,
    ADD COLUMN IF NOT EXISTS pool_id INT NULL
        COMMENT 'Pool IP assigné pour mode pool' AFTER ip_mode,
    ADD COLUMN IF NOT EXISTS pool_ip VARCHAR(45) NULL
        COMMENT 'Adresse IP assignée depuis le pool' AFTER pool_id;

-- 2. Ajouter un index sur pool_id
ALTER TABLE pppoe_users
    ADD INDEX IF NOT EXISTS idx_pppoe_users_pool (pool_id);

-- 3. Mettre à jour ip_mode pour les utilisateurs existants avec static_ip
UPDATE pppoe_users SET ip_mode = 'static' WHERE static_ip IS NOT NULL AND static_ip != '';
