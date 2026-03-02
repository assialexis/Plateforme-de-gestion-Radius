-- =====================================================
-- Migration 040: Système de rappels PPPoE
-- Permet aux admins de configurer des rappels automatiques
-- avant/après expiration des abonnements PPPoE via WhatsApp ou SMS
-- =====================================================

-- 1. Table des règles de rappel
CREATE TABLE IF NOT EXISTS pppoe_reminder_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'Nom lisible de la règle',
    days_before INT NOT NULL DEFAULT 0 COMMENT 'Jours avant expiration. Positif=avant, 0=jour même, négatif=après',
    channel ENUM('whatsapp', 'sms') NOT NULL DEFAULT 'whatsapp',
    message_template TEXT NOT NULL COMMENT 'Template du message avec {{variables}}',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_reminder_rule_unique (admin_id, days_before, channel),
    INDEX idx_reminder_rules_admin (admin_id),
    INDEX idx_reminder_rules_active (is_active),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table de log des rappels envoyés (historique + dédoublonnage)
CREATE TABLE IF NOT EXISTS pppoe_reminder_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_id INT DEFAULT NULL,
    pppoe_user_id INT NOT NULL,
    channel ENUM('whatsapp', 'sms') NOT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL COMMENT 'Message final après substitution des variables',
    status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
    error_message TEXT DEFAULT NULL,
    notification_date DATE NOT NULL COMMENT 'Date applicable de la notification',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_reminder_dedup (admin_id, rule_id, pppoe_user_id, notification_date),
    INDEX idx_reminder_log_admin (admin_id),
    INDEX idx_reminder_log_status (status),
    INDEX idx_reminder_log_date (sent_at),
    INDEX idx_reminder_log_user (pppoe_user_id),
    FOREIGN KEY (rule_id) REFERENCES pppoe_reminder_rules(id) ON DELETE SET NULL,
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
