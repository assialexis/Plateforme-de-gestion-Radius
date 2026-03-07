-- Soft delete for vouchers: preserve sales reports, session history, and logs
ALTER TABLE vouchers ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;
CREATE INDEX idx_voucher_deleted_at ON vouchers(deleted_at);

-- Change sessions FK to SET NULL instead of CASCADE
-- so deleting a voucher doesn't wipe session history
ALTER TABLE sessions DROP FOREIGN KEY sessions_ibfk_1;
ALTER TABLE sessions MODIFY COLUMN voucher_id INT NULL;
ALTER TABLE sessions ADD CONSTRAINT sessions_ibfk_1
    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE SET NULL;
