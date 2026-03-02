-- Migration: Fair Usage Policy (FUP) Support for PPPoE
-- Date: 2025-12-08
-- Description: Ajoute le support FUP pour réduire la bande passante après un quota

-- 1. Ajouter les colonnes FUP aux profils PPPoE
ALTER TABLE pppoe_profiles
    ADD COLUMN fup_enabled TINYINT(1) DEFAULT 0 COMMENT 'FUP activé pour ce profil' AFTER burst_time,
    ADD COLUMN fup_quota BIGINT DEFAULT 0 COMMENT 'Quota FUP en octets (0 = désactivé)' AFTER fup_enabled,
    ADD COLUMN fup_download_speed BIGINT DEFAULT 0 COMMENT 'Vitesse download après FUP en bits/s' AFTER fup_quota,
    ADD COLUMN fup_upload_speed BIGINT DEFAULT 0 COMMENT 'Vitesse upload après FUP en bits/s' AFTER fup_download_speed,
    ADD COLUMN fup_reset_day INT DEFAULT 1 COMMENT 'Jour du mois pour reset FUP (1-28)' AFTER fup_upload_speed,
    ADD COLUMN fup_reset_type ENUM('monthly', 'billing_cycle', 'manual') DEFAULT 'monthly' COMMENT 'Type de réinitialisation FUP' AFTER fup_reset_day;

-- 2. Ajouter les colonnes FUP aux utilisateurs PPPoE
ALTER TABLE pppoe_users
    ADD COLUMN fup_data_used BIGINT DEFAULT 0 COMMENT 'Données FUP utilisées ce cycle' AFTER data_used,
    ADD COLUMN fup_data_offset BIGINT DEFAULT 0 COMMENT 'Offset de consommation pour calcul FUP' AFTER fup_data_used,
    ADD COLUMN fup_triggered TINYINT(1) DEFAULT 0 COMMENT 'FUP actuellement déclenché' AFTER fup_data_offset,
    ADD COLUMN fup_triggered_at TIMESTAMP NULL COMMENT 'Date de déclenchement FUP' AFTER fup_triggered,
    ADD COLUMN fup_last_reset TIMESTAMP NULL COMMENT 'Dernière réinitialisation FUP' AFTER fup_triggered_at,
    ADD COLUMN fup_override TINYINT(1) DEFAULT 0 COMMENT 'Ignorer FUP pour cet utilisateur' AFTER fup_last_reset;

-- 3. Table des logs FUP
CREATE TABLE IF NOT EXISTS pppoe_fup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    action ENUM('triggered', 'reset', 'override_enabled', 'override_disabled', 'manual_reset') NOT NULL,
    data_used BIGINT DEFAULT 0 COMMENT 'Données utilisées au moment de l''action',
    quota BIGINT DEFAULT 0 COMMENT 'Quota FUP du profil',
    old_speed_down BIGINT DEFAULT NULL COMMENT 'Ancienne vitesse download',
    old_speed_up BIGINT DEFAULT NULL COMMENT 'Ancienne vitesse upload',
    new_speed_down BIGINT DEFAULT NULL COMMENT 'Nouvelle vitesse download',
    new_speed_up BIGINT DEFAULT NULL COMMENT 'Nouvelle vitesse upload',
    triggered_by VARCHAR(50) DEFAULT 'system' COMMENT 'system, admin, cron',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_fup_logs_user (pppoe_user_id),
    INDEX idx_fup_logs_action (action),
    INDEX idx_fup_logs_date (created_at),
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des notifications FUP (optionnel)
CREATE TABLE IF NOT EXISTS pppoe_fup_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    notification_type ENUM('warning_80', 'warning_90', 'triggered', 'reset') NOT NULL,
    sent_via ENUM('sms', 'email', 'push', 'none') DEFAULT 'none',
    sent_at TIMESTAMP NULL,
    message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_fup_notif_user (pppoe_user_id),
    INDEX idx_fup_notif_type (notification_type),
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Vue pour le suivi FUP
CREATE OR REPLACE VIEW v_pppoe_fup_status AS
SELECT
    pu.id as pppoe_user_id,
    pu.username,
    pu.customer_name,
    pu.customer_phone,
    pu.status,
    pp.name as profile_name,
    pp.fup_enabled,
    pp.fup_quota,
    pp.fup_download_speed,
    pp.fup_upload_speed,
    pp.download_speed as normal_download_speed,
    pp.upload_speed as normal_upload_speed,
    pu.fup_data_used,
    pu.fup_triggered,
    pu.fup_triggered_at,
    pu.fup_last_reset,
    pu.fup_override,
    CASE
        WHEN pp.fup_quota > 0 THEN ROUND((pu.fup_data_used / pp.fup_quota) * 100, 2)
        ELSE 0
    END as fup_usage_percent,
    CASE
        WHEN pp.fup_quota > 0 THEN pp.fup_quota - pu.fup_data_used
        ELSE 0
    END as fup_remaining,
    CASE
        WHEN pu.fup_override = 1 THEN 'override'
        WHEN pu.fup_triggered = 1 THEN 'throttled'
        WHEN pp.fup_enabled = 1 AND pp.fup_quota > 0 AND pu.fup_data_used >= pp.fup_quota THEN 'should_trigger'
        WHEN pp.fup_enabled = 1 AND pp.fup_quota > 0 AND pu.fup_data_used >= (pp.fup_quota * 0.9) THEN 'warning_90'
        WHEN pp.fup_enabled = 1 AND pp.fup_quota > 0 AND pu.fup_data_used >= (pp.fup_quota * 0.8) THEN 'warning_80'
        WHEN pp.fup_enabled = 1 THEN 'normal'
        ELSE 'disabled'
    END as fup_status
FROM pppoe_users pu
LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
WHERE pp.fup_enabled = 1;

-- 6. Mettre à jour les profils existants avec des valeurs FUP par défaut (exemple)
UPDATE pppoe_profiles SET
    fup_enabled = 0,
    fup_quota = 0,
    fup_download_speed = 0,
    fup_upload_speed = 0,
    fup_reset_day = 1,
    fup_reset_type = 'monthly'
WHERE fup_enabled IS NULL;
