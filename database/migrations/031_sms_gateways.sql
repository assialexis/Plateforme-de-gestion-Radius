-- Migration: SMS Gateway Module
-- Date: 2026-02-23

CREATE TABLE IF NOT EXISTS sms_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_code VARCHAR(50) NOT NULL COMMENT 'Provider identifier (e.g., nghcorp)',
    name VARCHAR(100) NOT NULL COMMENT 'Display name',
    description VARCHAR(255) DEFAULT NULL COMMENT 'Provider description',
    config JSON NOT NULL COMMENT 'Provider-specific credentials',
    is_active TINYINT(1) DEFAULT 0,
    is_default TINYINT(1) DEFAULT 0,
    balance DECIMAL(10,2) DEFAULT NULL,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_sms_provider_admin (provider_code, admin_id),
    INDEX idx_sms_admin_id (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sms_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_id INT DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    provider_response JSON DEFAULT NULL,
    admin_id INT NOT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sms_notif_status (status),
    INDEX idx_sms_notif_admin (admin_id),
    INDEX idx_sms_notif_gateway (gateway_id),
    FOREIGN KEY (gateway_id) REFERENCES sms_gateways(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
