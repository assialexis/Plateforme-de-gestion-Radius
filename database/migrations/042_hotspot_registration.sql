-- Migration 042: Hotspot Self-Registration with Daily Code + OTP
-- Extends otp_config with registration settings and creates registration_log table

-- Extend otp_config with registration columns
ALTER TABLE otp_config
    ADD COLUMN registration_enabled TINYINT(1) DEFAULT 0 AFTER country_code,
    ADD COLUMN daily_code VARCHAR(50) DEFAULT NULL AFTER registration_enabled,
    ADD COLUMN daily_code_auto_rotate TINYINT(1) DEFAULT 0 AFTER daily_code,
    ADD COLUMN registration_profile_id INT DEFAULT NULL AFTER daily_code_auto_rotate,
    ADD COLUMN registration_validity_days INT DEFAULT 1 AFTER registration_profile_id,
    ADD COLUMN registration_max_per_phone INT DEFAULT 1 AFTER registration_validity_days,
    ADD COLUMN registration_sms_template TEXT DEFAULT NULL AFTER registration_max_per_phone;

-- Registration log table
CREATE TABLE IF NOT EXISTS registration_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    voucher_username VARCHAR(64) DEFAULT NULL,
    profile_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    mac_address VARCHAR(17) DEFAULT NULL,
    status ENUM('pending','completed','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin (admin_id),
    INDEX idx_phone (admin_id, phone),
    INDEX idx_created (created_at)
);
