-- Migration: Ajouter le rôle vendeur et la table user_nas
-- Date: 2025-12-04

-- 1. Modifier l'ENUM role pour ajouter 'vendeur'
ALTER TABLE users MODIFY COLUMN role ENUM('superuser', 'admin', 'vendeur', 'gerant', 'client') NOT NULL DEFAULT 'client';

-- 2. Créer la table user_nas pour assigner des NAS aux vendeurs
CREATE TABLE IF NOT EXISTS user_nas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    nas_id INT NOT NULL,
    can_manage TINYINT(1) DEFAULT 1 COMMENT 'Peut gérer le NAS (modifier, voir sessions)',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL COMMENT 'ID de l\'utilisateur qui a fait l\'assignation',
    UNIQUE KEY unique_user_nas (user_id, nas_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (nas_id) REFERENCES nas(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Ajouter un index pour les recherches par vendeur
CREATE INDEX idx_user_nas_user ON user_nas(user_id);
CREATE INDEX idx_user_nas_nas ON user_nas(nas_id);
