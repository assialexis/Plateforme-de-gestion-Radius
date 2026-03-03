-- =====================================================
-- Migration 048: Serveurs RADIUS distribués
-- Permet de gérer plusieurs serveurs RADIUS sur des VPS
-- =====================================================

-- Table des serveurs RADIUS
CREATE TABLE IF NOT EXISTS radius_servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du serveur (ex: VPS France)',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique (ex: vps-fr-01)',
    host VARCHAR(255) NOT NULL COMMENT 'IP ou hostname du VPS',
    webhook_port INT DEFAULT 443 COMMENT 'Port HTTPS pour les webhooks push',
    webhook_path VARCHAR(255) DEFAULT '/webhook.php' COMMENT 'Chemin endpoint webhook',
    sync_token VARCHAR(128) NOT NULL COMMENT 'Token auth pour sync API (noeud → plateforme)',
    platform_token VARCHAR(128) NOT NULL COMMENT 'Token auth pour webhooks (plateforme → noeud)',
    status ENUM('online', 'offline', 'setup') DEFAULT 'setup',
    last_sync_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Dernière sync pull réussie',
    last_heartbeat_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Dernier heartbeat reçu',
    sync_interval INT DEFAULT 60 COMMENT 'Intervalle sync pull en secondes',
    config JSON DEFAULT NULL COMMENT 'Config spécifique au noeud',
    admin_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_admin (admin_id),
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Ajouter radius_server_id à la table zones
ALTER TABLE zones ADD COLUMN radius_server_id INT DEFAULT NULL
    COMMENT 'Serveur RADIUS hébergeant cette zone' AFTER code;
ALTER TABLE zones ADD INDEX idx_radius_server (radius_server_id);
ALTER TABLE zones ADD CONSTRAINT fk_zones_radius_server
    FOREIGN KEY (radius_server_id) REFERENCES radius_servers(id) ON DELETE SET NULL;
