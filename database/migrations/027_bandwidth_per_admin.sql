-- Migration: Bandwidth Management per Admin
-- Date: 2026-02-18
-- Description: Ajouter admin_id aux tables bandwidth pour isolation multi-tenant

-- =============================================
-- 1. Ajouter admin_id à bandwidth_policies
-- =============================================
ALTER TABLE bandwidth_policies ADD COLUMN admin_id INT DEFAULT NULL AFTER color;

-- Changer l'index UNIQUE de name vers (name, admin_id)
ALTER TABLE bandwidth_policies DROP INDEX idx_name;
ALTER TABLE bandwidth_policies ADD UNIQUE INDEX idx_name_admin (name, admin_id);

-- Assigner les policies existantes à admin 1
UPDATE bandwidth_policies SET admin_id = 1 WHERE admin_id IS NULL;

-- =============================================
-- 2. Ajouter admin_id à bandwidth_schedules
-- =============================================
ALTER TABLE bandwidth_schedules ADD COLUMN admin_id INT DEFAULT NULL AFTER target_id;

-- =============================================
-- 3. Dupliquer les données pour les autres admins
-- =============================================

-- Copier les policies de admin 1 vers tous les autres admins
INSERT INTO bandwidth_policies (name, description, download_rate, upload_rate, burst_download_rate, burst_upload_rate, burst_threshold_download, burst_threshold_upload, burst_time, priority, session_timeout, idle_timeout, color, admin_id)
SELECT bp.name, bp.description, bp.download_rate, bp.upload_rate, bp.burst_download_rate, bp.burst_upload_rate, bp.burst_threshold_download, bp.burst_threshold_upload, bp.burst_time, bp.priority, bp.session_timeout, bp.idle_timeout, bp.color, u.id
FROM bandwidth_policies bp
CROSS JOIN users u
WHERE bp.admin_id = 1 AND u.role = 'admin' AND u.id != 1
AND u.id NOT IN (SELECT DISTINCT admin_id FROM bandwidth_policies WHERE admin_id IS NOT NULL AND admin_id != 1);

-- Copier les attributs RADIUS pour les nouvelles policies
INSERT INTO bandwidth_radius_attributes (policy_id, attribute_name, attribute_value, attribute_op, vendor)
SELECT new_bp.id, bra.attribute_name, bra.attribute_value, bra.attribute_op, bra.vendor
FROM bandwidth_radius_attributes bra
JOIN bandwidth_policies old_bp ON bra.policy_id = old_bp.id AND old_bp.admin_id = 1
JOIN bandwidth_policies new_bp ON new_bp.name = old_bp.name AND new_bp.admin_id != 1
WHERE new_bp.id NOT IN (SELECT DISTINCT policy_id FROM bandwidth_radius_attributes);

-- Copier les schedules de admin 1 vers les autres admins (en remappant les policy_id)
INSERT INTO bandwidth_schedules (name, description, default_policy_id, scheduled_policy_id, start_time, end_time, active_days, apply_to, target_id, admin_id, is_active)
SELECT bs.name, bs.description,
       (SELECT id FROM bandwidth_policies WHERE name = dp.name AND admin_id = u.id) as new_default_policy_id,
       (SELECT id FROM bandwidth_policies WHERE name = sp.name AND admin_id = u.id) as new_scheduled_policy_id,
       bs.start_time, bs.end_time, bs.active_days, bs.apply_to, bs.target_id, u.id, bs.is_active
FROM bandwidth_schedules bs
JOIN bandwidth_policies dp ON bs.default_policy_id = dp.id
JOIN bandwidth_policies sp ON bs.scheduled_policy_id = sp.id
CROSS JOIN users u
WHERE bs.admin_id = 1 AND u.role = 'admin' AND u.id != 1
AND u.id NOT IN (SELECT DISTINCT admin_id FROM bandwidth_schedules WHERE admin_id IS NOT NULL AND admin_id != 1);
