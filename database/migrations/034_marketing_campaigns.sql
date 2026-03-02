-- Marketing campaigns
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    channel ENUM('sms', 'whatsapp') NOT NULL,
    sms_gateway_id INT DEFAULT NULL,
    client_source ENUM('hotspot', 'otp', 'pppoe') NOT NULL,
    filters JSON DEFAULT NULL,
    message_template TEXT NOT NULL,
    total_recipients INT DEFAULT 0,
    sent_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    status ENUM('draft', 'sending', 'completed', 'failed') DEFAULT 'draft',
    started_at TIMESTAMP NULL DEFAULT NULL,
    completed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_marketing_admin (admin_id),
    INDEX idx_marketing_status (status),
    INDEX idx_marketing_created (created_at),
    FOREIGN KEY (sms_gateway_id) REFERENCES sms_gateways(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS marketing_campaign_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    phone VARCHAR(50) NOT NULL,
    client_name VARCHAR(100) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    sent_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mcm_campaign (campaign_id),
    INDEX idx_mcm_status (status),
    FOREIGN KEY (campaign_id) REFERENCES marketing_campaigns(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
