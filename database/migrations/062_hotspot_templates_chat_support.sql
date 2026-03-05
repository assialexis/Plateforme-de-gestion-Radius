-- Migration: 062_hotspot_templates_chat_support
-- Description: Ajouter les colonnes de support chat aux templates hotspot

ALTER TABLE hotspot_templates
  ADD COLUMN show_chat_support TINYINT(1) DEFAULT 0,
  ADD COLUMN chat_support_type VARCHAR(50) DEFAULT 'whatsapp',
  ADD COLUMN chat_whatsapp_phone VARCHAR(50) DEFAULT NULL,
  ADD COLUMN chat_welcome_message TEXT DEFAULT NULL;
