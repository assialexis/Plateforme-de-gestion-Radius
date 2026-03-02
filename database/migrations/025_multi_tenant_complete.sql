-- =====================================================
-- Migration 025: Compléter l'isolation multi-tenant
-- Ajouter admin_id aux tables manquantes
-- =====================================================

-- PAYMENT GATEWAYS
ALTER TABLE payment_gateways ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE payment_gateways ADD INDEX IF NOT EXISTS idx_payment_gateways_admin_id (admin_id);

-- MEDIA LIBRARY
ALTER TABLE media_library ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE media_library ADD INDEX IF NOT EXISTS idx_media_library_admin_id (admin_id);
-- Rendre media_key unique par admin (au lieu de globalement unique)
ALTER TABLE media_library DROP INDEX IF EXISTS media_key;
ALTER TABLE media_library ADD UNIQUE INDEX IF NOT EXISTS idx_media_library_key_admin (media_key, admin_id);

-- MODULES
ALTER TABLE modules ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE modules ADD INDEX IF NOT EXISTS idx_modules_admin_id (admin_id);
-- Rendre module_code unique par admin
ALTER TABLE modules DROP INDEX IF EXISTS module_code;
ALTER TABLE modules ADD UNIQUE INDEX IF NOT EXISTS idx_modules_code_admin (module_code, admin_id);

-- SETTINGS
ALTER TABLE settings ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE settings ADD INDEX IF NOT EXISTS idx_settings_admin_id (admin_id);
-- Rendre setting_key unique par admin
ALTER TABLE settings DROP INDEX IF EXISTS setting_key;
ALTER TABLE settings ADD UNIQUE INDEX IF NOT EXISTS idx_settings_key_admin (setting_key, admin_id);

-- VOUCHER TEMPLATES
ALTER TABLE voucher_templates ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE voucher_templates ADD INDEX IF NOT EXISTS idx_voucher_templates_admin_id (admin_id);

-- HOTSPOT TEMPLATES
ALTER TABLE hotspot_templates ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE hotspot_templates ADD INDEX IF NOT EXISTS idx_hotspot_templates_admin_id (admin_id);

-- WHATSAPP CONFIG
ALTER TABLE whatsapp_config ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE whatsapp_config ADD INDEX IF NOT EXISTS idx_whatsapp_config_admin_id (admin_id);

-- WHATSAPP TEMPLATES (si pas déjà fait)
ALTER TABLE whatsapp_templates ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE whatsapp_templates ADD INDEX IF NOT EXISTS idx_whatsapp_templates_admin_id (admin_id);

-- WHATSAPP NOTIFICATIONS (si pas déjà fait)
ALTER TABLE whatsapp_notifications ADD COLUMN IF NOT EXISTS admin_id INT DEFAULT NULL;
ALTER TABLE whatsapp_notifications ADD INDEX IF NOT EXISTS idx_whatsapp_notifications_admin_id (admin_id);

-- =====================================================
-- Backfill: attribuer les données existantes au premier admin
-- =====================================================

SET @existing_admin_id = (SELECT id FROM users WHERE role = 'admin' ORDER BY id LIMIT 1);

UPDATE payment_gateways SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE media_library SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE modules SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE settings SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE voucher_templates SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE hotspot_templates SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE whatsapp_config SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE whatsapp_templates SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
UPDATE whatsapp_notifications SET admin_id = @existing_admin_id WHERE admin_id IS NULL AND @existing_admin_id IS NOT NULL;
