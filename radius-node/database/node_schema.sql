-- =====================================================
-- Schéma DB locale pour un nœud RADIUS
-- Version simplifiée de la DB centrale
-- =====================================================

CREATE DATABASE IF NOT EXISTS radius_node CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE radius_node;

-- Zones (synced from central)
CREATE TABLE IF NOT EXISTS zones (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3b82f6',
    dns_name VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- NAS / Routeurs (synced from central)
CREATE TABLE IF NOT EXISTS nas (
    id INT PRIMARY KEY,
    router_id VARCHAR(64) DEFAULT NULL,
    zone_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    nasname VARCHAR(128) NOT NULL DEFAULT '0.0.0.0/0',
    shortname VARCHAR(32) NOT NULL,
    secret VARCHAR(60) NOT NULL,
    description VARCHAR(200) DEFAULT NULL,
    type VARCHAR(30) DEFAULT 'mikrotik',
    ports INT DEFAULT NULL,
    community VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (router_id),
    INDEX idx_zone (zone_id)
) ENGINE=InnoDB;

-- Profils (synced from central)
CREATE TABLE IF NOT EXISTS profiles (
    id INT PRIMARY KEY,
    zone_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    name VARCHAR(50) NOT NULL,
    description VARCHAR(200) DEFAULT NULL,
    time_limit INT DEFAULT NULL,
    data_limit BIGINT DEFAULT NULL,
    upload_speed INT DEFAULT NULL,
    download_speed INT DEFAULT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    validity INT DEFAULT NULL,
    validity_unit ENUM('minutes', 'hours', 'days') DEFAULT 'days',
    simultaneous_use INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zone (zone_id)
) ENGINE=InnoDB;

-- Vouchers (synced from central)
CREATE TABLE IF NOT EXISTS vouchers (
    id INT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    password VARCHAR(64) NOT NULL,
    profile_id INT DEFAULT NULL,
    zone_id INT DEFAULT NULL,
    time_limit INT DEFAULT NULL,
    data_limit BIGINT DEFAULT NULL,
    upload_limit BIGINT DEFAULT NULL,
    download_limit BIGINT DEFAULT NULL,
    upload_speed INT DEFAULT NULL,
    download_speed INT DEFAULT NULL,
    status ENUM('unused', 'active', 'expired', 'disabled') DEFAULT 'unused',
    simultaneous_use INT DEFAULT 1,
    price DECIMAL(10,2) DEFAULT 0.00,
    valid_from DATETIME DEFAULT NULL,
    valid_until DATETIME DEFAULT NULL,
    first_use DATETIME DEFAULT NULL,
    time_used INT DEFAULT 0,
    data_used BIGINT DEFAULT 0,
    upload_used BIGINT DEFAULT 0,
    download_used BIGINT DEFAULT 0,
    customer_name VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(50) DEFAULT NULL,
    batch_id VARCHAR(36) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    vendeur_id INT DEFAULT NULL,
    gerant_id INT DEFAULT NULL,
    sold_by INT DEFAULT NULL,
    sold_at DATETIME DEFAULT NULL,
    sold_on_nas_id INT DEFAULT NULL,
    payment_method VARCHAR(30) DEFAULT NULL,
    sale_amount DECIMAL(10,2) DEFAULT NULL,
    commission_vendeur DECIMAL(10,2) DEFAULT 0,
    commission_gerant DECIMAL(10,2) DEFAULT 0,
    commission_admin DECIMAL(10,2) DEFAULT 0,
    commission_paid TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (username),
    INDEX idx_status (status),
    INDEX idx_zone (zone_id)
) ENGINE=InnoDB;

-- Sessions (local, pushed to central)
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    acct_session_id VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_port BIGINT DEFAULT NULL,
    username VARCHAR(64) NOT NULL,
    client_ip VARCHAR(45) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,
    session_time INT DEFAULT 0,
    input_octets BIGINT DEFAULT 0,
    output_octets BIGINT DEFAULT 0,
    input_packets INT DEFAULT 0,
    output_packets INT DEFAULT 0,
    start_time DATETIME NOT NULL,
    last_update DATETIME DEFAULT NULL,
    stop_time DATETIME DEFAULT NULL,
    terminate_cause VARCHAR(32) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    synced TINYINT(1) DEFAULT 0 COMMENT 'Synced to central platform',
    UNIQUE KEY (acct_session_id, nas_ip),
    INDEX idx_voucher (voucher_id),
    INDEX idx_username (username),
    INDEX idx_synced (synced)
) ENGINE=InnoDB;

-- Auth logs (local, pushed to central)
CREATE TABLE IF NOT EXISTS auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_name VARCHAR(32) DEFAULT NULL,
    action ENUM('accept', 'reject') NOT NULL,
    reason VARCHAR(200) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,
    client_ip VARCHAR(45) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    synced TINYINT(1) DEFAULT 0 COMMENT 'Synced to central platform',
    INDEX idx_username (username),
    INDEX idx_synced (synced)
) ENGINE=InnoDB;

-- PPPoE Profiles (synced from central)
CREATE TABLE IF NOT EXISTS pppoe_profiles (
    id INT PRIMARY KEY,
    zone_id INT DEFAULT NULL,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    download_speed BIGINT NOT NULL DEFAULT 1048576,
    upload_speed BIGINT NOT NULL DEFAULT 524288,
    data_limit BIGINT DEFAULT 0,
    validity_days INT NOT NULL DEFAULT 30,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    ip_pool_name VARCHAR(100) DEFAULT NULL,
    local_address VARCHAR(45) DEFAULT NULL,
    simultaneous_use INT DEFAULT 1,
    burst_download BIGINT DEFAULT 0,
    burst_upload BIGINT DEFAULT 0,
    burst_threshold BIGINT DEFAULT 0,
    burst_time INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zone (zone_id)
) ENGINE=InnoDB;

-- PPPoE Users (synced from central)
CREATE TABLE IF NOT EXISTS pppoe_users (
    id INT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    password VARCHAR(64) NOT NULL,
    profile_id INT DEFAULT NULL,
    profile_name VARCHAR(50) DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    customer_phone VARCHAR(50) DEFAULT NULL,
    status ENUM('active', 'suspended', 'expired', 'disabled') DEFAULT 'active',
    upload_speed INT DEFAULT NULL,
    download_speed INT DEFAULT NULL,
    ip_mode ENUM('router', 'static', 'pool') DEFAULT 'router',
    static_ip VARCHAR(45) DEFAULT NULL,
    pool_ip VARCHAR(45) DEFAULT NULL,
    ip_pool_name VARCHAR(100) DEFAULT NULL,
    mikrotik_group VARCHAR(100) DEFAULT NULL,
    valid_until DATETIME DEFAULT NULL,
    burst_upload INT DEFAULT NULL,
    burst_download INT DEFAULT NULL,
    burst_threshold INT DEFAULT NULL,
    burst_time INT DEFAULT NULL,
    simultaneous_use INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (username),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- PPPoE Sessions (local, pushed to central)
CREATE TABLE IF NOT EXISTS pppoe_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    acct_session_id VARCHAR(64) NOT NULL,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_identifier VARCHAR(64) DEFAULT NULL,
    nas_port BIGINT DEFAULT NULL,
    client_ip VARCHAR(45) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,
    calling_station_id VARCHAR(50) DEFAULT NULL,
    called_station_id VARCHAR(50) DEFAULT NULL,
    session_time INT DEFAULT 0,
    input_octets BIGINT DEFAULT 0,
    output_octets BIGINT DEFAULT 0,
    input_packets INT DEFAULT 0,
    output_packets INT DEFAULT 0,
    start_time DATETIME NOT NULL,
    last_update DATETIME DEFAULT NULL,
    stop_time DATETIME DEFAULT NULL,
    terminate_cause VARCHAR(32) DEFAULT NULL,
    synced TINYINT(1) DEFAULT 0 COMMENT 'Synced to central platform',
    UNIQUE KEY (acct_session_id, nas_ip),
    INDEX idx_user (user_id),
    INDEX idx_synced (synced)
) ENGINE=InnoDB;

-- Sync metadata
CREATE TABLE IF NOT EXISTS sync_meta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    last_pull_at TIMESTAMP NULL DEFAULT NULL,
    last_push_at TIMESTAMP NULL DEFAULT NULL,
    config_hash VARCHAR(64) DEFAULT NULL,
    pull_count INT DEFAULT 0,
    push_count INT DEFAULT 0,
    last_error TEXT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO sync_meta (id, pull_count, push_count) VALUES (1, 0, 0)
ON DUPLICATE KEY UPDATE id = id;
