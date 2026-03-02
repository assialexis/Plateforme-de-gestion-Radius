-- Migration: Bandwidth Management Tables
-- Date: 2025-12-08
-- Description: Tables pour la gestion avancée de la bande passante RADIUS

-- Table des politiques de bande passante (templates réutilisables)
CREATE TABLE IF NOT EXISTS bandwidth_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Limites de base (en bits par seconde)
    download_rate BIGINT NOT NULL DEFAULT 1048576 COMMENT 'Débit download en bps (1M = 1048576)',
    upload_rate BIGINT NOT NULL DEFAULT 524288 COMMENT 'Débit upload en bps (512K = 524288)',

    -- Burst (accélération temporaire) - MikroTik style
    burst_download_rate BIGINT DEFAULT NULL COMMENT 'Débit burst download en bps',
    burst_upload_rate BIGINT DEFAULT NULL COMMENT 'Débit burst upload en bps',
    burst_threshold_download BIGINT DEFAULT NULL COMMENT 'Seuil de déclenchement burst download',
    burst_threshold_upload BIGINT DEFAULT NULL COMMENT 'Seuil de déclenchement burst upload',
    burst_time INT DEFAULT NULL COMMENT 'Durée du burst en secondes',

    -- Priority (QoS)
    priority TINYINT DEFAULT 8 COMMENT 'Priorité 1-8 (1=haute, 8=basse)',

    -- Limites additionnelles
    session_timeout INT DEFAULT NULL COMMENT 'Timeout de session en secondes',
    idle_timeout INT DEFAULT NULL COMMENT 'Timeout d\'inactivité en secondes',

    -- Métadonnées
    color VARCHAR(7) DEFAULT '#3B82F6' COMMENT 'Couleur pour l\'affichage',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des planifications horaires de bande passante
CREATE TABLE IF NOT EXISTS bandwidth_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,

    -- Politique de base (hors planification)
    default_policy_id INT NOT NULL,

    -- Politique alternative (pendant la planification)
    scheduled_policy_id INT NOT NULL,

    -- Planification temporelle
    start_time TIME NOT NULL COMMENT 'Heure de début (ex: 18:00)',
    end_time TIME NOT NULL COMMENT 'Heure de fin (ex: 08:00)',

    -- Jours actifs (bitmask: 1=Lundi, 2=Mardi, 4=Mercredi, 8=Jeudi, 16=Vendredi, 32=Samedi, 64=Dimanche)
    active_days TINYINT DEFAULT 127 COMMENT 'Tous les jours par défaut',

    -- Application
    apply_to ENUM('all', 'zone', 'profile', 'user') DEFAULT 'all',
    target_id INT DEFAULT NULL COMMENT 'ID de la zone/profil/user selon apply_to',

    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (default_policy_id) REFERENCES bandwidth_policies(id) ON DELETE RESTRICT,
    FOREIGN KEY (scheduled_policy_id) REFERENCES bandwidth_policies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des attributs RADIUS pour la bande passante
CREATE TABLE IF NOT EXISTS bandwidth_radius_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    policy_id INT NOT NULL,

    -- Attribut RADIUS
    attribute_name VARCHAR(100) NOT NULL COMMENT 'Nom de l\'attribut RADIUS',
    attribute_value VARCHAR(255) NOT NULL COMMENT 'Valeur de l\'attribut',
    attribute_op CHAR(2) DEFAULT ':=' COMMENT 'Opérateur (:=, =, +=, etc.)',

    -- Vendor spécifique
    vendor VARCHAR(50) DEFAULT NULL COMMENT 'Nom du vendor (Mikrotik, Cisco, etc.)',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (policy_id) REFERENCES bandwidth_policies(id) ON DELETE CASCADE,
    INDEX idx_policy (policy_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insérer des politiques par défaut
INSERT INTO bandwidth_policies (name, description, download_rate, upload_rate, priority, color) VALUES
('Basique 1M', 'Connexion basique 1 Mbps symétrique', 1048576, 1048576, 8, '#6B7280'),
('Standard 2M', 'Connexion standard 2 Mbps / 1 Mbps', 2097152, 1048576, 6, '#3B82F6'),
('Premium 5M', 'Connexion premium 5 Mbps / 2 Mbps', 5242880, 2097152, 4, '#8B5CF6'),
('Business 10M', 'Connexion business 10 Mbps / 5 Mbps', 10485760, 5242880, 2, '#F59E0B'),
('Illimité', 'Sans limite de bande passante', 0, 0, 1, '#10B981'),
('Heures creuses', 'Vitesse réduite pour heures de pointe', 524288, 262144, 8, '#EF4444');

-- Insérer les attributs RADIUS pour MikroTik
INSERT INTO bandwidth_radius_attributes (policy_id, attribute_name, attribute_value, attribute_op, vendor) VALUES
-- Basique 1M
(1, 'Mikrotik-Rate-Limit', '1M/1M', ':=', 'Mikrotik'),
-- Standard 2M
(2, 'Mikrotik-Rate-Limit', '2M/1M', ':=', 'Mikrotik'),
-- Premium 5M
(3, 'Mikrotik-Rate-Limit', '5M/2M', ':=', 'Mikrotik'),
-- Business 10M
(4, 'Mikrotik-Rate-Limit', '10M/5M', ':=', 'Mikrotik'),
-- Illimité (pas de limite)
(5, 'Mikrotik-Rate-Limit', '', ':=', 'Mikrotik'),
-- Heures creuses
(6, 'Mikrotik-Rate-Limit', '512k/256k', ':=', 'Mikrotik');
