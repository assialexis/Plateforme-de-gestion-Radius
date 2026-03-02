-- Migration: Système SuperAdmin
-- Date: 2026-02-24
-- Description: Ajouter le rôle superadmin, tables permissions, paramètres globaux

-- =============================================
-- 1. Ajouter 'superadmin' au ENUM des rôles
-- =============================================
ALTER TABLE users MODIFY COLUMN role
    ENUM('superadmin', 'admin', 'vendeur', 'gerant', 'client', 'technicien') NOT NULL DEFAULT 'client';

-- =============================================
-- 2. Table des permissions granulaires
-- =============================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique: manage_users, manage_vouchers, etc.',
    name VARCHAR(100) NOT NULL COMMENT 'Nom affiché',
    description VARCHAR(255) DEFAULT NULL,
    category VARCHAR(50) DEFAULT 'general' COMMENT 'Catégorie: users, hotspot, pppoe, system, etc.',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 3. Table permissions par rôle (défauts globaux)
-- =============================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('superadmin', 'admin', 'vendeur', 'gerant', 'client', 'technicien') NOT NULL,
    permission_id INT NOT NULL,
    admin_id INT DEFAULT NULL COMMENT 'NULL = global default, non-NULL = personnalisé par admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_role_perm_admin (role, permission_id, admin_id),
    INDEX idx_rp_admin (admin_id),
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 4. Table surcharges permissions par utilisateur
-- =============================================
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted TINYINT(1) DEFAULT 1 COMMENT '1 = accordé, 0 = révoqué (override)',
    granted_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY idx_user_perm (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 5. Table paramètres globaux (superadmin only)
-- =============================================
CREATE TABLE IF NOT EXISTS global_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description VARCHAR(200) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 6. Seed des permissions
-- =============================================
INSERT INTO permissions (permission_code, name, description, category, display_order) VALUES
-- Users
('manage_users', 'Gérer les utilisateurs', 'Créer, modifier, supprimer des utilisateurs', 'users', 1),
('create_gerants', 'Créer des gérants', 'Créer des comptes gérant', 'users', 2),
('create_vendeurs', 'Créer des vendeurs', 'Créer des comptes vendeur', 'users', 3),
('create_techniciens', 'Créer des techniciens', 'Créer des comptes technicien', 'users', 4),
-- Hotspot
('manage_vouchers', 'Gérer les vouchers', 'Créer, modifier, supprimer des vouchers', 'hotspot', 10),
('create_vouchers', 'Créer des vouchers', 'Générer de nouveaux vouchers', 'hotspot', 11),
('manage_profiles', 'Gérer les profils', 'Créer, modifier les profils tarifaires', 'hotspot', 12),
('manage_sessions', 'Gérer les sessions', 'Voir et déconnecter les sessions actives', 'hotspot', 13),
('manage_templates', 'Gérer les templates', 'Modifier les templates voucher et hotspot', 'hotspot', 14),
-- Network
('manage_zones', 'Gérer les zones', 'Créer et modifier les zones', 'network', 20),
('manage_nas', 'Gérer les NAS', 'Ajouter et configurer les routeurs NAS', 'network', 21),
('manage_bandwidth', 'Gérer la bande passante', 'Configurer les politiques de bande passante', 'network', 22),
-- PPPoE
('manage_pppoe', 'Gérer PPPoE', 'Gérer les clients et profils PPPoE', 'pppoe', 30),
('manage_billing', 'Gérer la facturation', 'Créer et gérer les factures', 'pppoe', 31),
-- System
('view_stats', 'Voir les statistiques', 'Accéder au dashboard et aux stats', 'system', 40),
('view_logs', 'Voir les journaux', 'Consulter les logs d''authentification', 'system', 41),
('access_settings', 'Accéder aux paramètres', 'Modifier la configuration du système', 'system', 42),
('manage_modules', 'Gérer les modules', 'Activer/désactiver les modules', 'system', 43),
('manage_payments', 'Gérer les paiements', 'Configurer les passerelles de paiement', 'system', 44),
('manage_library', 'Gérer la médiathèque', 'Upload et gérer les médias', 'system', 45),
-- Communication
('manage_chat', 'Gérer le chat', 'Répondre aux conversations de support', 'communication', 50),
('manage_telegram', 'Gérer Telegram', 'Configurer les notifications Telegram', 'communication', 51),
('manage_whatsapp', 'Gérer WhatsApp', 'Configurer les notifications WhatsApp', 'communication', 52),
('manage_sms', 'Gérer les SMS', 'Configurer les passerelles SMS', 'communication', 53),
('manage_marketing', 'Gérer le marketing', 'Envoyer des campagnes marketing', 'communication', 54),
-- Sales
('view_sales', 'Voir les ventes', 'Consulter les rapports de vente', 'sales', 60),
('manage_loyalty', 'Gérer la fidélité', 'Configurer le programme de fidélité', 'sales', 61),
-- SuperAdmin only
('manage_admins', 'Gérer les admins', 'Créer, modifier et supprimer les comptes admin', 'superadmin', 90),
('view_all_tenants', 'Voir tous les tenants', 'Voir les données de tous les tenants', 'superadmin', 91),
('manage_global_settings', 'Paramètres globaux', 'Modifier les paramètres globaux du système', 'superadmin', 92)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =============================================
-- 7. Seed permissions par rôle (défauts globaux)
-- =============================================

-- SuperAdmin: toutes les permissions
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'superadmin', id, NULL FROM permissions;

-- Admin: tout sauf catégorie superadmin
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'admin', id, NULL FROM permissions WHERE category != 'superadmin';

-- Gérant: subset
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'gerant', id, NULL FROM permissions
WHERE permission_code IN ('create_vendeurs', 'create_techniciens', 'manage_vouchers', 'create_vouchers', 'manage_sessions', 'view_stats', 'view_sales', 'manage_chat', 'manage_pppoe');

-- Vendeur: minimal
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'vendeur', id, NULL FROM permissions
WHERE permission_code IN ('manage_vouchers', 'create_vouchers', 'manage_nas', 'manage_sessions', 'manage_chat');

-- Technicien: réseau
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'technicien', id, NULL FROM permissions
WHERE permission_code IN ('create_vendeurs', 'manage_zones', 'manage_nas', 'manage_bandwidth', 'manage_profiles', 'access_settings');

-- =============================================
-- 8. Promouvoir user id=1 en superadmin
-- =============================================
UPDATE users SET role = 'superadmin', parent_id = NULL WHERE id = 1;

-- =============================================
-- 9. Seed paramètres globaux
-- =============================================
INSERT INTO global_settings (setting_key, setting_value, description) VALUES
('allow_registration', '1', 'Permettre l''inscription de nouveaux admins'),
('max_admins', '0', 'Nombre maximum d''admins (0 = illimité)'),
('platform_name', 'RADIUS Manager', 'Nom de la plateforme'),
('maintenance_mode', '0', 'Mode maintenance'),
('default_modules', '["hotspot","captive-portal"]', 'Modules activés par défaut pour les nouveaux admins')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);
