-- Migration: Système multi-utilisateur avec 4 rôles
-- Date: 2025-12-04
-- Rôles: superuser, admin, gerant, client

-- =====================================================
-- Modifier la table admins -> users
-- =====================================================

-- Renommer la table admins en users
RENAME TABLE admins TO users;

-- Modifier la structure pour supporter les nouveaux rôles
ALTER TABLE users
    MODIFY COLUMN role ENUM('superuser', 'admin', 'gerant', 'client') NOT NULL DEFAULT 'client',
    ADD COLUMN parent_id INT DEFAULT NULL COMMENT 'ID du créateur (admin qui a créé le gérant)' AFTER role,
    ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER email,
    ADD COLUMN avatar VARCHAR(500) DEFAULT NULL COMMENT 'URL avatar' AFTER full_name,
    ADD COLUMN preferences JSON DEFAULT NULL COMMENT 'Préférences utilisateur' AFTER avatar,
    ADD INDEX idx_parent (parent_id),
    ADD FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- Table de liaison utilisateurs - zones (pour admins et gérants)
-- =====================================================
CREATE TABLE IF NOT EXISTS user_zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    zone_id INT NOT NULL,
    can_manage TINYINT(1) DEFAULT 0 COMMENT 'Peut gérer la zone (modifier routeurs, profils)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_zone (user_id, zone_id),
    INDEX idx_user (user_id),
    INDEX idx_zone (zone_id),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Mettre à jour la table admin_sessions -> user_sessions
-- =====================================================
RENAME TABLE admin_sessions TO user_sessions;

ALTER TABLE user_sessions
    CHANGE COLUMN admin_id user_id INT NOT NULL;

-- =====================================================
-- Mettre à jour les références dans la table zones
-- =====================================================
ALTER TABLE zones
    ADD COLUMN owner_id INT DEFAULT NULL COMMENT 'Propriétaire de la zone (admin)' AFTER is_active,
    ADD INDEX idx_owner (owner_id),
    ADD FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- Mettre à jour les références dans la table vouchers
-- =====================================================
ALTER TABLE vouchers
    MODIFY COLUMN created_by INT DEFAULT NULL COMMENT 'Utilisateur qui a créé le voucher',
    ADD CONSTRAINT fk_voucher_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- =====================================================
-- Table des logs d'activité utilisateur
-- =====================================================
CREATE TABLE IF NOT EXISTS user_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action effectuée',
    entity_type VARCHAR(50) DEFAULT NULL COMMENT 'Type d''entité (voucher, profile, zone, etc.)',
    entity_id INT DEFAULT NULL COMMENT 'ID de l''entité',
    details JSON DEFAULT NULL COMMENT 'Détails supplémentaires',
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created (created_at),

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Créer le super utilisateur par défaut
-- =====================================================
-- Mot de passe: superadmin123
INSERT INTO users (username, password, email, full_name, role, is_active) VALUES
('superuser', '$2y$10$WDzwwSNltUXliSA7ybZvYO9PyZuBREEG251EAGenjPQ.N59/Ltjsm', 'superuser@localhost', 'Super Administrateur', 'superuser', 1)
ON DUPLICATE KEY UPDATE role = 'superuser';

-- Mettre à jour l'admin existant
UPDATE users SET role = 'admin' WHERE username = 'admin' AND role NOT IN ('superuser');
