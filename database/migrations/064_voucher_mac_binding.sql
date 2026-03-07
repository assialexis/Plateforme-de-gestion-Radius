-- Voucher MAC address binding (lock user to device)
ALTER TABLE profiles ADD COLUMN lock_to_mac TINYINT(1) NOT NULL DEFAULT 0 AFTER simultaneous_use;
ALTER TABLE vouchers ADD COLUMN locked_mac VARCHAR(17) DEFAULT NULL AFTER simultaneous_use;
CREATE INDEX idx_voucher_locked_mac ON vouchers(locked_mac);
