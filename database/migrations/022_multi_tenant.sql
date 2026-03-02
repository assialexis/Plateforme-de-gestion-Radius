-- =====================================================
-- Migration 022: Conversion Multi-Tenant
-- Chaque admin est un tenant isolé
-- =====================================================

-- =====================================================
-- ETAPE 1: Supprimer le rôle superuser
-- =====================================================

-- Promouvoir les superusers existants en admin
UPDATE users SET role = 'admin' WHERE role = 'superuser';

-- Modifier l'enum pour retirer superuser
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'vendeur', 'gerant', 'client') NOT NULL DEFAULT 'client';

-- =====================================================
-- ETAPE 2: Ajouter admin_id aux tables tenant-scoped
-- =====================================================

-- CORE: zones
ALTER TABLE zones ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL AFTER owner_id;
ALTER TABLE zones ADD INDEX IF NOT EXISTS idx_zones_admin_id (admin_id);

-- CORE: nas
ALTER TABLE nas ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL AFTER zone_id;
ALTER TABLE nas ADD INDEX IF NOT EXISTS idx_nas_admin_id (admin_id);

-- CORE: profiles
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL AFTER zone_id;
ALTER TABLE profiles ADD INDEX IF NOT EXISTS idx_profiles_admin_id (admin_id);

-- CORE: vouchers
ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_vouchers_admin_id (admin_id);

-- CORE: sessions
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE sessions ADD INDEX IF NOT EXISTS idx_sessions_admin_id (admin_id);

-- CORE: auth_logs
ALTER TABLE auth_logs ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE auth_logs ADD INDEX IF NOT EXISTS idx_auth_logs_admin_id (admin_id);

-- UTILISATEURS: user_zones
ALTER TABLE user_zones ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE user_zones ADD INDEX IF NOT EXISTS idx_user_zones_admin_id (admin_id);

-- UTILISATEURS: user_activity_logs
ALTER TABLE user_activity_logs ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE user_activity_logs ADD INDEX IF NOT EXISTS idx_user_activity_logs_admin_id (admin_id);

-- PPPOE: pppoe_profiles
ALTER TABLE pppoe_profiles ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_profiles ADD INDEX IF NOT EXISTS idx_pppoe_profiles_admin_id (admin_id);

-- PPPOE: pppoe_users
ALTER TABLE pppoe_users ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_users ADD INDEX IF NOT EXISTS idx_pppoe_users_admin_id (admin_id);

-- PPPOE: pppoe_sessions
ALTER TABLE pppoe_sessions ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_sessions ADD INDEX IF NOT EXISTS idx_pppoe_sessions_admin_id (admin_id);

-- PPPOE: pppoe_auth_logs
ALTER TABLE pppoe_auth_logs ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_auth_logs ADD INDEX IF NOT EXISTS idx_pppoe_auth_logs_admin_id (admin_id);

-- PPPOE: pppoe_user_nas
ALTER TABLE pppoe_user_nas ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_user_nas ADD INDEX IF NOT EXISTS idx_pppoe_user_nas_admin_id (admin_id);

-- FACTURATION: pppoe_invoices
ALTER TABLE pppoe_invoices ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_invoices ADD INDEX IF NOT EXISTS idx_pppoe_invoices_admin_id (admin_id);

-- FACTURATION: pppoe_invoice_items
ALTER TABLE pppoe_invoice_items ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_invoice_items ADD INDEX IF NOT EXISTS idx_pppoe_invoice_items_admin_id (admin_id);

-- FACTURATION: pppoe_payments
ALTER TABLE pppoe_payments ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_payments ADD INDEX IF NOT EXISTS idx_pppoe_payments_admin_id (admin_id);

-- FACTURATION: pppoe_payment_transactions
ALTER TABLE pppoe_payment_transactions ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_payment_transactions ADD INDEX IF NOT EXISTS idx_pppoe_payment_transactions_admin_id (admin_id);

-- FACTURATION: billing_payments
ALTER TABLE billing_payments ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE billing_payments ADD INDEX IF NOT EXISTS idx_billing_payments_admin_id (admin_id);

-- VENTES: commission_rates
ALTER TABLE commission_rates ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE commission_rates ADD INDEX IF NOT EXISTS idx_commission_rates_admin_id (admin_id);

-- VENTES: commission_payments
ALTER TABLE commission_payments ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE commission_payments ADD INDEX IF NOT EXISTS idx_commission_payments_admin_id (admin_id);

-- FIDELITE: loyalty_customers
ALTER TABLE loyalty_customers ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE loyalty_customers ADD INDEX IF NOT EXISTS idx_loyalty_customers_admin_id (admin_id);

-- FIDELITE: loyalty_purchases
ALTER TABLE loyalty_purchases ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE loyalty_purchases ADD INDEX IF NOT EXISTS idx_loyalty_purchases_admin_id (admin_id);

-- FIDELITE: loyalty_rewards
ALTER TABLE loyalty_rewards ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE loyalty_rewards ADD INDEX IF NOT EXISTS idx_loyalty_rewards_admin_id (admin_id);

-- FIDELITE: loyalty_rules
ALTER TABLE loyalty_rules ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE loyalty_rules ADD INDEX IF NOT EXISTS idx_loyalty_rules_admin_id (admin_id);

-- CHAT: chat_conversations
ALTER TABLE chat_conversations ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE chat_conversations ADD INDEX IF NOT EXISTS idx_chat_conversations_admin_id (admin_id);

-- TELEGRAM: telegram_config
ALTER TABLE telegram_config ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE telegram_config ADD INDEX IF NOT EXISTS idx_telegram_config_admin_id (admin_id);

-- TELEGRAM: telegram_recipients
ALTER TABLE telegram_recipients ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE telegram_recipients ADD INDEX IF NOT EXISTS idx_telegram_recipients_admin_id (admin_id);

-- TELEGRAM: telegram_templates
ALTER TABLE telegram_templates ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE telegram_templates ADD INDEX IF NOT EXISTS idx_telegram_templates_admin_id (admin_id);

-- TELEGRAM: telegram_notifications
ALTER TABLE telegram_notifications ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE telegram_notifications ADD INDEX IF NOT EXISTS idx_telegram_notifications_admin_id (admin_id);

-- TELEGRAM: telegram_notification_log
ALTER TABLE telegram_notification_log ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE telegram_notification_log ADD INDEX IF NOT EXISTS idx_telegram_notification_log_admin_id (admin_id);

-- FUP: pppoe_fup_logs
ALTER TABLE pppoe_fup_logs ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_fup_logs ADD INDEX IF NOT EXISTS idx_pppoe_fup_logs_admin_id (admin_id);

-- FUP: pppoe_fup_notifications
ALTER TABLE pppoe_fup_notifications ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE pppoe_fup_notifications ADD INDEX IF NOT EXISTS idx_pppoe_fup_notifications_admin_id (admin_id);

-- TRANSACTIONS: payment_transactions
ALTER TABLE payment_transactions ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE payment_transactions ADD INDEX IF NOT EXISTS idx_payment_transactions_admin_id (admin_id);

-- IP POOLS
ALTER TABLE ip_pools ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE ip_pools ADD INDEX IF NOT EXISTS idx_ip_pools_admin_id (admin_id);

-- =====================================================
-- ETAPE 3: Backfill admin_id pour les données existantes
-- =====================================================

SET @existing_admin_id = (SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1);

-- Seulement si un admin existe
UPDATE zones SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE nas SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE profiles SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE vouchers SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE sessions SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE auth_logs SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE user_zones SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE user_activity_logs SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_profiles SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_users SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_sessions SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_auth_logs SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_user_nas SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_invoices SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_invoice_items SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_payments SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_payment_transactions SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE billing_payments SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE commission_rates SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE commission_payments SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE loyalty_customers SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE loyalty_purchases SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE loyalty_rewards SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE loyalty_rules SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE chat_conversations SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE telegram_config SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE telegram_recipients SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE telegram_templates SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE telegram_notifications SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE telegram_notification_log SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;

UPDATE pppoe_fup_logs SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE pppoe_fup_notifications SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE payment_transactions SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE ip_pools SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
