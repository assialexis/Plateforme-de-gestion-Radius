-- Migration 059: Ajouter UNIQUE KEY sur pppoe_sessions pour sync noeud->central
-- Nécessaire pour ON DUPLICATE KEY UPDATE dans importNodeSyncData()

ALTER TABLE pppoe_sessions
  ADD UNIQUE KEY uk_pppoe_session (acct_session_id, nas_ip);
