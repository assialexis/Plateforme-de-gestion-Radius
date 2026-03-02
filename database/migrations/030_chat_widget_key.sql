-- Migration 030: Chat Widget Key
-- Date: 2026-02-19
-- Description: Support pour le widget chat embeddable sur les portails captifs.
-- La cle widget est stockee dans la table `settings` existante avec:
--   setting_key = 'chat_widget_key'
--   setting_value = <cle hex 32 caracteres>
--   admin_id = <id de l'admin>
-- Aucune modification de schema requise.

-- Mettre a jour la contrainte unique de chat_conversations pour supporter le multi-tenant
-- (un meme numero de telephone peut avoir des conversations chez differents admins)
ALTER TABLE chat_conversations DROP INDEX IF EXISTS idx_phone;
ALTER TABLE chat_conversations ADD UNIQUE INDEX idx_phone_admin (phone, admin_id);
