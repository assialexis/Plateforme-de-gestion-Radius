-- =====================================================
-- Migration 026: Templates WhatsApp/Telegram par admin
-- Chaque admin a ses propres templates isolés
-- =====================================================

-- 1. Corriger les index UNIQUE globaux → composites avec admin_id

-- WhatsApp templates
ALTER TABLE whatsapp_templates DROP INDEX unique_event_days;
ALTER TABLE whatsapp_templates ADD UNIQUE INDEX unique_event_days_admin (event_type, days_before, admin_id);

-- Telegram templates
ALTER TABLE telegram_templates DROP INDEX unique_event_days;
ALTER TABLE telegram_templates ADD UNIQUE INDEX unique_event_days_admin (event_type, days_before, admin_id);

-- 2. Dupliquer les templates de l'admin 1 vers tous les autres admins

-- WhatsApp: copier les 10 templates pour chaque admin qui n'en a pas
INSERT INTO whatsapp_templates (name, description, event_type, message_template, days_before, is_active, send_time, admin_id)
SELECT wt.name, wt.description, wt.event_type, wt.message_template, wt.days_before, wt.is_active, wt.send_time, u.id
FROM whatsapp_templates wt
CROSS JOIN users u
WHERE wt.admin_id = (SELECT MIN(admin_id) FROM whatsapp_templates WHERE admin_id IS NOT NULL)
  AND u.role = 'admin'
  AND u.id != (SELECT MIN(admin_id) FROM whatsapp_templates WHERE admin_id IS NOT NULL)
  AND u.id NOT IN (SELECT DISTINCT admin_id FROM whatsapp_templates WHERE admin_id IS NOT NULL AND admin_id != (SELECT MIN(admin_id) FROM whatsapp_templates WHERE admin_id IS NOT NULL));

-- Telegram: copier les 8 templates pour chaque admin qui n'en a pas
INSERT INTO telegram_templates (name, description, event_type, message_template, days_before, is_active, send_time, admin_id)
SELECT tt.name, tt.description, tt.event_type, tt.message_template, tt.days_before, tt.is_active, tt.send_time, u.id
FROM telegram_templates tt
CROSS JOIN users u
WHERE tt.admin_id = (SELECT MIN(admin_id) FROM telegram_templates WHERE admin_id IS NOT NULL)
  AND u.role = 'admin'
  AND u.id != (SELECT MIN(admin_id) FROM telegram_templates WHERE admin_id IS NOT NULL)
  AND u.id NOT IN (SELECT DISTINCT admin_id FROM telegram_templates WHERE admin_id IS NOT NULL AND admin_id != (SELECT MIN(admin_id) FROM telegram_templates WHERE admin_id IS NOT NULL));
