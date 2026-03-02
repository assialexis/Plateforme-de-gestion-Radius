-- Migration: Create PPPoE payment transactions table
-- Date: 2025-12-07

-- Table pour les transactions de paiement PPPoE
CREATE TABLE IF NOT EXISTS pppoe_payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) NOT NULL UNIQUE,
    pppoe_user_id INT NOT NULL,
    gateway_code VARCHAR(50) NOT NULL,
    gateway_transaction_id VARCHAR(255) DEFAULT NULL,
    payment_type ENUM('invoice', 'extension', 'renewal') NOT NULL,
    invoice_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'XAF',
    customer_phone VARCHAR(50) DEFAULT NULL,
    customer_email VARCHAR(255) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT DEFAULT NULL,
    gateway_response JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL DEFAULT NULL,

    INDEX idx_transaction_id (transaction_id),
    INDEX idx_pppoe_user_id (pppoe_user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),

    CONSTRAINT fk_pppoe_payment_user FOREIGN KEY (pppoe_user_id)
        REFERENCES pppoe_users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pppoe_payment_invoice FOREIGN KEY (invoice_id)
        REFERENCES invoices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les paiements de facturation (billing_payments)
CREATE TABLE IF NOT EXISTS billing_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    payment_reference VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    received_by INT DEFAULT NULL,
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_invoice_id (invoice_id),
    INDEX idx_paid_at (paid_at),

    CONSTRAINT fk_billing_payment_invoice FOREIGN KEY (invoice_id)
        REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
