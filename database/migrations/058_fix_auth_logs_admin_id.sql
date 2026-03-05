-- Migration 058: Corriger admin_id NULL dans auth_logs
-- La migration 022 utilisait la table 'users' (inexistante) au lieu de 'admins'
-- Les auth_logs importés du nœud n'avaient pas d'admin_id

UPDATE auth_logs a
JOIN nas n ON (a.nas_ip = n.nasname)
   OR (a.nas_name = n.router_id AND n.router_id IS NOT NULL)
   OR (a.nas_name = n.shortname)
SET a.admin_id = n.admin_id
WHERE a.admin_id IS NULL AND n.admin_id IS NOT NULL;

-- Idem pour sessions
UPDATE sessions s
JOIN nas n ON (s.nas_ip = n.nasname)
   OR (s.nas_identifier = n.router_id AND n.router_id IS NOT NULL)
   OR (s.nas_identifier = n.shortname)
SET s.admin_id = n.admin_id
WHERE s.admin_id IS NULL AND n.admin_id IS NOT NULL;
