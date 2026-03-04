-- Migration: Two-Factor Authentication (Google Authenticator)
-- Date: 2026-03-03

-- Add 2FA columns to users table
ALTER TABLE users
    ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL COMMENT 'TOTP secret key (Base32 encoded)' AFTER preferences,
    ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0 COMMENT '2FA enabled flag' AFTER totp_secret;

-- Temporary tokens for 2FA verification during login
CREATE TABLE IF NOT EXISTS two_factor_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,

    UNIQUE KEY unique_token (token),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
