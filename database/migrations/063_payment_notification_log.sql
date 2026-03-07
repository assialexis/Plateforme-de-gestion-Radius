-- Payment notification log table
CREATE TABLE IF NOT EXISTS payment_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    invoice_id INT DEFAULT NULL,
    pppoe_user_id INT NOT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) NOT NULL,
    channel ENUM('whatsapp', 'sms') NOT NULL,
    message TEXT NOT NULL,
    status ENUM('sent', 'failed') NOT NULL DEFAULT 'sent',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_notif_admin (admin_id),
    INDEX idx_payment_notif_invoice (invoice_id),
    INDEX idx_payment_notif_user (pppoe_user_id),
    INDEX idx_payment_notif_status (status),
    INDEX idx_payment_notif_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
