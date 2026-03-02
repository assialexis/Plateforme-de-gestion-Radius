-- Migration 038: Auto-renewal flag for module subscriptions
ALTER TABLE admin_module_subscriptions ADD COLUMN IF NOT EXISTS auto_renew TINYINT(1) DEFAULT 0;
