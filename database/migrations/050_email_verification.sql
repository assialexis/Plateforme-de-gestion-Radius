-- Migration: Email verification system + SMTP configuration
-- Date: 2026-03-04

-- 1. Add email verification columns to users table
ALTER TABLE users
    ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email,
    ADD COLUMN email_verification_token VARCHAR(64) DEFAULT NULL AFTER email_verified,
    ADD COLUMN email_token_expires_at TIMESTAMP NULL DEFAULT NULL AFTER email_verification_token;

-- 2. Mark all existing admin/superadmin users as verified
UPDATE users SET email_verified = 1 WHERE role IN ('superadmin', 'admin');

-- 3. SMTP configuration in global_settings
INSERT IGNORE INTO global_settings (setting_key, setting_value, description) VALUES
('smtp_host', '', 'Serveur SMTP'),
('smtp_port', '587', 'Port SMTP'),
('smtp_username', '', 'Utilisateur SMTP'),
('smtp_password', '', 'Mot de passe SMTP'),
('smtp_encryption', 'tls', 'Chiffrement: tls, ssl, ou none'),
('smtp_from_email', '', 'Email expéditeur'),
('smtp_from_name', 'RADIUS Manager', 'Nom expéditeur'),
('email_verification_enabled', '0', 'Activer la vérification email des admins');
