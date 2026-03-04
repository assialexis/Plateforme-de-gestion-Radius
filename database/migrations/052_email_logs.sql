-- Migration 052: Email sending logs
-- Date: 2026-03-04

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    email_type ENUM('verification', 'reset', 'test', 'other') NOT NULL DEFAULT 'other',
    status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
    error_message TEXT DEFAULT NULL,
    smtp_host VARCHAR(255) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_logs_type (email_type),
    INDEX idx_email_logs_status (status),
    INDEX idx_email_logs_created (created_at),
    INDEX idx_email_logs_to (to_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
