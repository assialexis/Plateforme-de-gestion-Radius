-- Migration: OTP Verification for Hotspot Captive Portal
-- Date: 2026-02-23

-- Table de configuration OTP par admin
CREATE TABLE IF NOT EXISTS otp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    is_enabled TINYINT(1) DEFAULT 0 COMMENT 'Activer/désactiver OTP',
    hotspot_dns VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'DNS du hotspot MikroTik (ex: hotspot.local)',
    otp_length TINYINT DEFAULT 6 COMMENT 'Longueur du code OTP (4-8)',
    otp_expiry_seconds INT DEFAULT 300 COMMENT 'Durée de validité OTP en secondes',
    sms_gateway_id INT DEFAULT NULL COMMENT 'Gateway SMS à utiliser',
    sms_template TEXT DEFAULT NULL COMMENT 'Template SMS avec {{otp_code}}',
    country_code VARCHAR(5) DEFAULT '229' COMMENT 'Code pays par défaut',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_otp_config_admin (admin_id),
    INDEX idx_otp_config_gateway (sms_gateway_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des vérifications OTP
CREATE TABLE IF NOT EXISTS otp_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    phone VARCHAR(20) NOT NULL COMMENT 'Numéro de téléphone',
    otp_code VARCHAR(10) NOT NULL COMMENT 'Code OTP généré',
    voucher_username VARCHAR(100) NOT NULL COMMENT 'Username du voucher',
    voucher_password VARCHAR(100) NOT NULL COMMENT 'Password du voucher',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IP du client',
    mac_address VARCHAR(17) DEFAULT NULL COMMENT 'MAC du client',
    user_agent TEXT DEFAULT NULL COMMENT 'User agent du navigateur',
    status ENUM('pending', 'verified', 'expired', 'failed') DEFAULT 'pending',
    attempts TINYINT DEFAULT 0 COMMENT 'Nombre de tentatives (max 3)',
    expires_at TIMESTAMP NOT NULL COMMENT 'Date d''expiration du code',
    verified_at TIMESTAMP NULL COMMENT 'Date de vérification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_phone_code (phone, otp_code, status),
    INDEX idx_otp_admin_date (admin_id, created_at),
    INDEX idx_otp_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer config par défaut pour admin 1
INSERT IGNORE INTO otp_config (admin_id, is_enabled, hotspot_dns, otp_length, otp_expiry_seconds, sms_template, country_code)
VALUES (1, 0, '', 6, 300, 'Votre code de verification WiFi: {{otp_code}}. Ce code expire dans {{expiry_duration}}. {{company_name}}', '229');
