-- Migration: Support PPPoE clients
-- Date: 2025-12-05

-- 1. Table des profils PPPoE (abonnements)
CREATE TABLE IF NOT EXISTS pppoe_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NULL COMMENT 'Zone spécifique ou NULL pour global',
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,

    -- Limites de vitesse
    download_speed BIGINT NOT NULL DEFAULT 1048576 COMMENT 'Vitesse download en bits/s',
    upload_speed BIGINT NOT NULL DEFAULT 524288 COMMENT 'Vitesse upload en bits/s',

    -- Limites de données (0 = illimité)
    data_limit BIGINT DEFAULT 0 COMMENT 'Limite data en octets (0 = illimité)',

    -- Validité de l'abonnement
    validity_days INT NOT NULL DEFAULT 30 COMMENT 'Durée de validité en jours',

    -- Prix
    price DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Pool d'adresses IP
    ip_pool_name VARCHAR(100) DEFAULT NULL COMMENT 'Nom du pool IP sur MikroTik',
    local_address VARCHAR(45) DEFAULT NULL COMMENT 'Adresse locale PPPoE',

    -- Limites de sessions
    simultaneous_use INT DEFAULT 1 COMMENT 'Nombre de connexions simultanées',

    -- Options avancées
    burst_download BIGINT DEFAULT 0 COMMENT 'Burst download en bits/s',
    burst_upload BIGINT DEFAULT 0 COMMENT 'Burst upload en bits/s',
    burst_threshold BIGINT DEFAULT 0 COMMENT 'Seuil burst en octets',
    burst_time INT DEFAULT 0 COMMENT 'Durée burst en secondes',

    -- Statut
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pppoe_profiles_zone (zone_id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table des clients PPPoE
CREATE TABLE IF NOT EXISTS pppoe_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NULL COMMENT 'Zone assignée',
    profile_id INT NOT NULL COMMENT 'Profil PPPoE',

    -- Identifiants de connexion
    username VARCHAR(64) NOT NULL UNIQUE COMMENT 'Nom d''utilisateur PPPoE',
    password VARCHAR(255) NOT NULL COMMENT 'Mot de passe',

    -- Informations client
    customer_name VARCHAR(100) DEFAULT NULL COMMENT 'Nom complet du client',
    customer_phone VARCHAR(50) DEFAULT NULL,
    customer_email VARCHAR(100) DEFAULT NULL,
    customer_address TEXT DEFAULT NULL,

    -- Adresse IP (optionnel - assignation statique)
    static_ip VARCHAR(45) DEFAULT NULL COMMENT 'IP statique assignée',

    -- Statut et validité
    status ENUM('active', 'suspended', 'expired', 'disabled') DEFAULT 'active',
    valid_from TIMESTAMP NULL DEFAULT NULL,
    valid_until TIMESTAMP NULL DEFAULT NULL,

    -- Première connexion
    first_use TIMESTAMP NULL DEFAULT NULL,
    last_login TIMESTAMP NULL DEFAULT NULL,

    -- Compteurs d'utilisation
    data_used BIGINT DEFAULT 0 COMMENT 'Données utilisées en octets',
    time_used INT DEFAULT 0 COMMENT 'Temps de connexion total en secondes',

    -- Informations de vente/facturation
    sold_by INT NULL COMMENT 'Vendeur/gérant',
    sold_at TIMESTAMP NULL,
    sold_on_nas_id INT NULL COMMENT 'NAS principal assigné',
    payment_method ENUM('cash', 'mobile_money', 'online', 'free') DEFAULT NULL,
    sale_amount DECIMAL(10,2) DEFAULT NULL,

    -- Commissions
    commission_vendeur DECIMAL(10,2) DEFAULT 0,
    commission_gerant DECIMAL(10,2) DEFAULT 0,
    commission_admin DECIMAL(10,2) DEFAULT 0,
    commission_paid TINYINT(1) DEFAULT 0,

    -- Notes
    notes TEXT DEFAULT NULL,

    -- Métadonnées
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_pppoe_users_zone (zone_id),
    INDEX idx_pppoe_users_profile (profile_id),
    INDEX idx_pppoe_users_status (status),
    INDEX idx_pppoe_users_valid_until (valid_until),
    INDEX idx_pppoe_users_sold_by (sold_by),
    INDEX idx_pppoe_users_customer_phone (customer_phone),

    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL,
    FOREIGN KEY (profile_id) REFERENCES pppoe_profiles(id) ON DELETE RESTRICT,
    FOREIGN KEY (sold_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sold_on_nas_id) REFERENCES nas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table des sessions PPPoE (historique de connexion)
CREATE TABLE IF NOT EXISTS pppoe_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    acct_session_id VARCHAR(64) NOT NULL COMMENT 'ID de session RADIUS',

    -- NAS info
    nas_ip VARCHAR(45) NOT NULL,
    nas_port BIGINT NULL,
    nas_identifier VARCHAR(64) NULL,

    -- Client info
    client_ip VARCHAR(45) DEFAULT NULL COMMENT 'IP assignée au client',
    client_mac VARCHAR(17) DEFAULT NULL,
    calling_station_id VARCHAR(50) DEFAULT NULL,
    called_station_id VARCHAR(50) DEFAULT NULL,

    -- Compteurs
    session_time INT DEFAULT 0 COMMENT 'Durée session en secondes',
    input_octets BIGINT DEFAULT 0,
    output_octets BIGINT DEFAULT 0,
    input_packets BIGINT DEFAULT 0,
    output_packets BIGINT DEFAULT 0,

    -- Timestamps
    start_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_update TIMESTAMP NULL,
    stop_time TIMESTAMP NULL,
    terminate_cause VARCHAR(50) DEFAULT NULL,

    INDEX idx_pppoe_sessions_user (pppoe_user_id),
    INDEX idx_pppoe_sessions_acct (acct_session_id),
    INDEX idx_pppoe_sessions_nas_ip (nas_ip),
    INDEX idx_pppoe_sessions_active (stop_time),
    INDEX idx_pppoe_sessions_start (start_time),

    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table des logs d'authentification PPPoE
CREATE TABLE IF NOT EXISTS pppoe_auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_identifier VARCHAR(64) NULL,
    action ENUM('accept', 'reject') NOT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,
    calling_station_id VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_pppoe_auth_username (username),
    INDEX idx_pppoe_auth_nas (nas_ip),
    INDEX idx_pppoe_auth_action (action),
    INDEX idx_pppoe_auth_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Table de mapping NAS <-> PPPoE users (pour assignation multi-NAS)
CREATE TABLE IF NOT EXISTS pppoe_user_nas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    nas_id INT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'NAS principal pour ce client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_pppoe_user_nas (pppoe_user_id, nas_id),
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE,
    FOREIGN KEY (nas_id) REFERENCES nas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Vue pour les statistiques PPPoE
CREATE OR REPLACE VIEW v_pppoe_stats AS
SELECT
    pu.id as pppoe_user_id,
    pu.username,
    pu.customer_name,
    pu.customer_phone,
    pu.status,
    pu.valid_until,
    pp.name as profile_name,
    pp.download_speed,
    pp.upload_speed,
    pp.price as profile_price,
    pp.validity_days,
    z.id as zone_id,
    z.name as zone_name,
    pu.data_used,
    pp.data_limit,
    CASE
        WHEN pp.data_limit > 0 THEN ROUND((pu.data_used / pp.data_limit) * 100, 2)
        ELSE 0
    END as data_usage_percent,
    pu.sold_by,
    u_seller.username as seller_username,
    u_seller.full_name as seller_name,
    pu.sold_at,
    pu.sale_amount,
    pu.payment_method,
    (SELECT COUNT(*) FROM pppoe_sessions ps WHERE ps.pppoe_user_id = pu.id AND ps.stop_time IS NULL) as active_sessions,
    (SELECT MAX(start_time) FROM pppoe_sessions ps WHERE ps.pppoe_user_id = pu.id) as last_session
FROM pppoe_users pu
LEFT JOIN pppoe_profiles pp ON pu.profile_id = pp.id
LEFT JOIN zones z ON pu.zone_id = z.id
LEFT JOIN users u_seller ON pu.sold_by = u_seller.id;

-- 7. Insérer des profils PPPoE par défaut
INSERT INTO pppoe_profiles (name, description, download_speed, upload_speed, data_limit, validity_days, price) VALUES
    ('Bronze 2Mbps', 'Abonnement basique 2Mbps illimité', 2097152, 1048576, 0, 30, 5000),
    ('Silver 5Mbps', 'Abonnement standard 5Mbps illimité', 5242880, 2621440, 0, 30, 10000),
    ('Gold 10Mbps', 'Abonnement premium 10Mbps illimité', 10485760, 5242880, 0, 30, 20000),
    ('Platinum 20Mbps', 'Abonnement professionnel 20Mbps illimité', 20971520, 10485760, 0, 30, 35000)
ON DUPLICATE KEY UPDATE updated_at = NOW();

-- 8. Ajouter un type de service pour différencier hotspot et PPPoE dans la config
ALTER TABLE nas ADD COLUMN IF NOT EXISTS service_type ENUM('hotspot', 'pppoe', 'both') DEFAULT 'hotspot' COMMENT 'Type de service supporté';
