-- =====================================================
-- Base de données RADIUS Manager
-- =====================================================

CREATE DATABASE IF NOT EXISTS radius_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE radius_db;

-- =====================================================
-- Table des Zones (regroupement logique de NAS)
-- =====================================================
CREATE TABLE IF NOT EXISTS zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom de la zone',
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique de la zone',
    description VARCHAR(255) DEFAULT NULL,
    color VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Couleur pour identification',
    dns_name VARCHAR(255) DEFAULT NULL COMMENT 'DNS du hotspot',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table des NAS (MikroTik/Routeurs autorisés)
-- =====================================================
CREATE TABLE IF NOT EXISTS nas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    router_id VARCHAR(64) DEFAULT NULL COMMENT 'ID unique du routeur',
    zone_id INT DEFAULT NULL COMMENT 'Zone à laquelle appartient ce NAS',
    nasname VARCHAR(128) NOT NULL DEFAULT '0.0.0.0/0' COMMENT 'IP ou hostname du NAS (wildcard par défaut)',
    shortname VARCHAR(32) NOT NULL COMMENT 'Nom court du NAS',
    secret VARCHAR(60) NOT NULL COMMENT 'Secret partagé RADIUS',
    description VARCHAR(200) DEFAULT NULL,
    type VARCHAR(30) DEFAULT 'mikrotik',
    ports INT DEFAULT NULL,
    community VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (router_id),
    INDEX idx_zone (zone_id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des profils de vouchers (templates)
-- =====================================================
CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT DEFAULT NULL COMMENT 'Zone associée (NULL = toutes les zones)',
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(200) DEFAULT NULL,
    time_limit INT DEFAULT NULL COMMENT 'Durée en secondes',
    data_limit BIGINT DEFAULT NULL COMMENT 'Data en octets',
    upload_speed INT DEFAULT NULL COMMENT 'Upload en bps',
    download_speed INT DEFAULT NULL COMMENT 'Download en bps',
    price DECIMAL(10,2) DEFAULT 0.00,
    validity INT DEFAULT NULL COMMENT 'Validité du voucher en secondes (durée de vie après première connexion)',
    validity_unit ENUM('minutes', 'hours', 'days') DEFAULT 'days' COMMENT 'Unité pour affichage',
    simultaneous_use INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_zone (zone_id),
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des vouchers/tickets
-- =====================================================
CREATE TABLE IF NOT EXISTS vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL COMMENT 'Code du voucher',
    password VARCHAR(64) NOT NULL COMMENT 'Mot de passe',
    profile_id INT DEFAULT NULL,
    zone_id INT DEFAULT NULL COMMENT 'Zone associée (NULL = toutes les zones)',

    -- Limites du voucher
    time_limit INT DEFAULT NULL COMMENT 'Durée max en secondes',
    data_limit BIGINT DEFAULT NULL COMMENT 'Data max en octets',
    upload_limit BIGINT DEFAULT NULL COMMENT 'Upload max en octets',
    download_limit BIGINT DEFAULT NULL COMMENT 'Download max en octets',

    -- Vitesse (en bits par seconde)
    upload_speed INT DEFAULT NULL COMMENT 'Vitesse upload en bps',
    download_speed INT DEFAULT NULL COMMENT 'Vitesse download en bps',

    -- Statut
    status ENUM('unused', 'active', 'expired', 'disabled') DEFAULT 'unused',
    simultaneous_use INT DEFAULT 1 COMMENT 'Connexions simultanées',

    -- Prix
    price DECIMAL(10,2) DEFAULT 0.00,

    -- Dates
    valid_from DATETIME DEFAULT NULL COMMENT 'Date début validité',
    valid_until DATETIME DEFAULT NULL COMMENT 'Date expiration',
    first_use DATETIME DEFAULT NULL COMMENT 'Première utilisation',

    -- Compteurs utilisés
    time_used INT DEFAULT 0 COMMENT 'Temps utilisé en secondes',
    data_used BIGINT DEFAULT 0 COMMENT 'Data totale utilisée',
    upload_used BIGINT DEFAULT 0 COMMENT 'Upload utilisé',
    download_used BIGINT DEFAULT 0 COMMENT 'Download utilisé',

    -- Infos client (optionnel)
    customer_name VARCHAR(100) DEFAULT NULL COMMENT 'Nom du client',
    customer_phone VARCHAR(50) DEFAULT NULL COMMENT 'Numéro de téléphone du client',

    -- Métadonnées
    batch_id VARCHAR(36) DEFAULT NULL COMMENT 'ID du lot de génération',
    created_by INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY (username),
    INDEX idx_status (status),
    INDEX idx_profile (profile_id),
    INDEX idx_batch (batch_id),
    INDEX idx_created (created_at),
    INDEX idx_zone (zone_id),

    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des sessions
-- =====================================================
CREATE TABLE IF NOT EXISTS sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_id INT NOT NULL,
    acct_session_id VARCHAR(64) NOT NULL COMMENT 'ID session RADIUS',
    nas_ip VARCHAR(45) NOT NULL,
    nas_port INT DEFAULT NULL,

    -- Info client
    username VARCHAR(64) NOT NULL,
    client_ip VARCHAR(45) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,

    -- Compteurs de session
    session_time INT DEFAULT 0,
    input_octets BIGINT DEFAULT 0,
    output_octets BIGINT DEFAULT 0,
    input_packets INT DEFAULT 0,
    output_packets INT DEFAULT 0,

    -- Dates
    start_time DATETIME NOT NULL,
    last_update DATETIME DEFAULT NULL,
    stop_time DATETIME DEFAULT NULL,

    -- Cause de déconnexion
    terminate_cause VARCHAR(32) DEFAULT NULL,

    UNIQUE KEY (acct_session_id, nas_ip),
    INDEX idx_voucher (voucher_id),
    INDEX idx_username (username),
    INDEX idx_active (stop_time),
    INDEX idx_start (start_time),

    FOREIGN KEY (voucher_id) REFERENCES vouchers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table de logs d'authentification
-- =====================================================
CREATE TABLE IF NOT EXISTS auth_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL,
    nas_ip VARCHAR(45) NOT NULL,
    nas_name VARCHAR(32) DEFAULT NULL,
    action ENUM('accept', 'reject') NOT NULL,
    reason VARCHAR(200) DEFAULT NULL,
    client_mac VARCHAR(17) DEFAULT NULL,
    client_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_username (username),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_nas (nas_ip)
) ENGINE=InnoDB;

-- =====================================================
-- Table des administrateurs
-- =====================================================
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    full_name VARCHAR(100) DEFAULT NULL,
    role ENUM('admin', 'operator', 'viewer') DEFAULT 'operator',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME DEFAULT NULL,
    login_attempts INT DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =====================================================
-- Table des sessions admin (web)
-- =====================================================
CREATE TABLE IF NOT EXISTS admin_sessions (
    id VARCHAR(64) PRIMARY KEY,
    admin_id INT NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    data TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,

    INDEX idx_admin (admin_id),
    INDEX idx_expires (expires_at),

    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table de rate limiting API
-- =====================================================
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(100) NOT NULL COMMENT 'IP ou clé API',
    endpoint VARCHAR(100) NOT NULL,
    requests INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY (identifier, endpoint),
    INDEX idx_window (window_start)
) ENGINE=InnoDB;

-- =====================================================
-- Table de configuration système
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    description VARCHAR(200) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table de la bibliothèque média
-- =====================================================
CREATE TABLE IF NOT EXISTS media_library (
    id INT AUTO_INCREMENT PRIMARY KEY,
    media_type VARCHAR(50) NOT NULL COMMENT 'Type: logo, image, audio',
    media_key VARCHAR(50) NOT NULL UNIQUE COMMENT 'Clé unique: company_logo, image_1, audio_welcome, etc.',
    original_name VARCHAR(255) DEFAULT NULL COMMENT 'Nom original du fichier',
    file_path VARCHAR(500) DEFAULT NULL COMMENT 'Chemin du fichier',
    file_size INT DEFAULT NULL COMMENT 'Taille en octets',
    mime_type VARCHAR(100) DEFAULT NULL COMMENT 'Type MIME',
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Table des passerelles de paiement (Payment Gateways)
-- =====================================================
CREATE TABLE IF NOT EXISTS payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique (fedapay, cinetpay, orange_money, etc.)',
    name VARCHAR(100) NOT NULL COMMENT 'Nom affiché',
    description VARCHAR(255) DEFAULT NULL,
    logo_url VARCHAR(500) DEFAULT NULL COMMENT 'URL du logo',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Activé/Désactivé',
    is_sandbox TINYINT(1) DEFAULT 1 COMMENT 'Mode test/production',
    config JSON DEFAULT NULL COMMENT 'Configuration JSON (clés API, etc.)',
    display_order INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Données initiales
-- =====================================================

-- Admin par défaut (mot de passe: admin123)
INSERT INTO admins (username, password, email, full_name, role) VALUES
('admin', '$2y$10$WDzwwSNltUXliSA7ybZvYO9PyZuBREEG251EAGenjPQ.N59/Ltjsm', 'admin@localhost', 'Administrateur', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Zone par défaut
INSERT INTO zones (name, code, description, color, is_active) VALUES
('Zone Principale', 'main', 'Zone principale par défaut', '#3b82f6', 1)
ON DUPLICATE KEY UPDATE code = VALUES(code);

-- NAS de test
INSERT INTO nas (nasname, shortname, secret, description, type) VALUES
('0.0.0.0/0', 'all', 'testing123', 'Accepter tous les NAS (dev seulement)', 'other'),
('192.168.1.1', 'mikrotik-main', 'secret123', 'MikroTik Principal', 'mikrotik')
ON DUPLICATE KEY UPDATE shortname = VALUES(shortname);

-- Profils de base
INSERT INTO profiles (name, description, time_limit, data_limit, download_speed, upload_speed, price, validity, validity_unit, simultaneous_use) VALUES
('1 Heure', 'Accès 1 heure', 3600, NULL, 2000000, 1000000, 100.00, NULL, 'days', 1),
('3 Heures', 'Accès 3 heures', 10800, NULL, 2000000, 1000000, 200.00, NULL, 'days', 1),
('1 Jour', 'Accès 24 heures', 86400, NULL, 5000000, 2000000, 500.00, 86400, 'days', 1),
('1 Semaine', 'Accès 7 jours', 604800, NULL, 5000000, 2000000, 2000.00, 604800, 'days', 2),
('1 Mois', 'Accès 30 jours', 2592000, NULL, 10000000, 5000000, 5000.00, 2592000, 'days', 3),
('100 MB', 'Forfait 100 MB', NULL, 104857600, 2000000, 1000000, 150.00, NULL, 'days', 1),
('500 MB', 'Forfait 500 MB', NULL, 524288000, 5000000, 2000000, 500.00, NULL, 'days', 1),
('1 GB', 'Forfait 1 GB', NULL, 1073741824, 10000000, 5000000, 1000.00, NULL, 'days', 2)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Vouchers de test
INSERT INTO vouchers (username, password, time_limit, data_limit, download_speed, upload_speed, status, price) VALUES
('TEST001', 'TEST001', 3600, NULL, 2000000, 1000000, 'unused', 100.00),
('TEST002', 'TEST002', 7200, 104857600, 2000000, 1000000, 'unused', 200.00),
('TEST003', 'TEST003', NULL, NULL, 5000000, 2000000, 'unused', 500.00),
('DEMO', 'DEMO', 300, NULL, 1000000, 500000, 'unused', 0.00)
ON DUPLICATE KEY UPDATE username = VALUES(username);

-- Configuration système
INSERT INTO settings (setting_key, setting_value, description) VALUES
('app_name', 'RADIUS Manager', 'Nom de l''application'),
('app_logo', NULL, 'Logo personnalisé (URL ou base64)'),
('currency', 'XAF', 'Devise pour les prix'),
('timezone', 'Africa/Douala', 'Fuseau horaire'),
('language', 'fr', 'Langue par défaut (fr/en)'),
('hotspot_title', 'WiFi Hotspot', 'Titre affiché sur le portail captif'),
('support_email', 'support@example.com', 'Email de support'),
('support_phone', '', 'Téléphone de support')
ON DUPLICATE KEY UPDATE setting_key = VALUES(setting_key);

-- Passerelles de paiement par défaut
INSERT INTO payment_gateways (gateway_code, name, description, logo_url, is_active, is_sandbox, config, display_order) VALUES
('fedapay', 'FedaPay', 'Paiement mobile money et cartes en Afrique', 'https://fedapay.com/assets/images/logo.svg', 0, 1, '{"account_name": "", "public_key": "", "secret_key": ""}', 1),
('cinetpay', 'CinetPay', 'Solution de paiement mobile money', 'https://cinetpay.com/images/logo.png', 0, 1, '{"site_id": "", "api_key": "", "secret_key": ""}', 2),
('feexpay', 'FeexPay', 'Paiement mobile money Afrique de l''Ouest', 'https://feexpay.me/assets/logo.png', 0, 1, '{"account_name": "", "api_key": "", "shop_id": ""}', 3),
('paygate', 'PayGate Global', 'Paiement mobile money Togo (Flooz, TMoney)', 'https://paygateglobal.com/assets/logo.png', 0, 0, '{"auth_token": ""}', 4),
('paydunya', 'PayDunya', 'Paiement mobile money Afrique de l''Ouest (Orange, Wave, MTN, Moov)', 'https://paydunya.com/assets/images/logo.png', 0, 1, '{"master_key": "", "private_key": "", "token": "", "store_name": ""}', 5),
('orange_money', 'Orange Money', 'Paiement Orange Money', 'https://www.orange.com/sites/default/files/2020-06/logo_orange_money.png', 0, 1, '{"merchant_key": "", "username": "", "password": "", "auth_header": ""}', 6),
('mtn_momo', 'MTN Mobile Money', 'Paiement MTN MoMo', 'https://mtn.cm/wp-content/uploads/2020/05/momo-logo.png', 0, 1, '{"subscription_key": "", "api_user": "", "api_key": "", "environment": "sandbox"}', 7),
('paypal', 'PayPal', 'Paiement international PayPal', 'https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-200px.png', 0, 1, '{"client_id": "", "client_secret": ""}', 8),
('stripe', 'Stripe', 'Paiement par carte bancaire', 'https://stripe.com/img/v3/home/twitter.png', 0, 1, '{"publishable_key": "", "secret_key": "", "webhook_secret": ""}', 9)
ON DUPLICATE KEY UPDATE gateway_code = VALUES(gateway_code);

-- Bibliothèque média par défaut
INSERT INTO media_library (media_type, media_key, description) VALUES
('logo', 'company_logo', 'Logo de l''entreprise'),
('image', 'hotspot_image_1', 'Image hotspot 1'),
('image', 'hotspot_image_2', 'Image hotspot 2'),
('image', 'hotspot_image_3', 'Image hotspot 3'),
('image', 'hotspot_image_4', 'Image hotspot 4'),
('audio', 'welcome_audio', 'Audio de bienvenue (max 800KB)')
ON DUPLICATE KEY UPDATE media_key = VALUES(media_key);

-- =====================================================
-- Table des templates de vouchers
-- =====================================================
CREATE TABLE IF NOT EXISTS voucher_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du template',
    description VARCHAR(255) DEFAULT NULL,
    template_type ENUM('simple', 'detailed') DEFAULT 'simple' COMMENT 'Type: simple ou détaillé',
    paper_size VARCHAR(20) DEFAULT 'A4' COMMENT 'Taille papier: A4, Letter, etc.',
    orientation ENUM('portrait', 'landscape') DEFAULT 'portrait',
    columns_count INT DEFAULT 2 COMMENT 'Nombre de colonnes',
    rows_count INT DEFAULT 5 COMMENT 'Nombre de lignes',
    show_logo TINYINT(1) DEFAULT 1 COMMENT 'Afficher le logo',
    show_qr_code TINYINT(1) DEFAULT 0 COMMENT 'Afficher QR code',
    show_password TINYINT(1) DEFAULT 1 COMMENT 'Afficher mot de passe',
    show_validity TINYINT(1) DEFAULT 1 COMMENT 'Afficher validité',
    show_speed TINYINT(1) DEFAULT 0 COMMENT 'Afficher vitesse',
    show_price TINYINT(1) DEFAULT 1 COMMENT 'Afficher prix',
    header_text VARCHAR(255) DEFAULT NULL COMMENT 'Texte en-tête',
    footer_text VARCHAR(255) DEFAULT NULL COMMENT 'Texte pied de page',
    background_color VARCHAR(7) DEFAULT '#ffffff' COMMENT 'Couleur fond',
    border_color VARCHAR(7) DEFAULT '#e5e7eb' COMMENT 'Couleur bordure',
    primary_color VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Couleur principale',
    text_color VARCHAR(7) DEFAULT '#1f2937' COMMENT 'Couleur texte',
    custom_css TEXT DEFAULT NULL COMMENT 'CSS personnalisé',
    is_default TINYINT(1) DEFAULT 0 COMMENT 'Template par défaut',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Templates par défaut
INSERT INTO voucher_templates (name, description, template_type, columns_count, rows_count, show_logo, show_qr_code, show_password, show_validity, show_speed, show_price, header_text, footer_text, is_default) VALUES
('Ticket Simple', 'Template simple avec code et mot de passe', 'simple', 2, 5, 1, 0, 1, 1, 0, 1, 'WiFi Hotspot', 'Merci de votre visite!', 1),
('Ticket Détaillé', 'Template complet avec QR code et toutes les infos', 'detailed', 2, 4, 1, 1, 1, 1, 1, 1, 'WiFi Hotspot Premium', 'Support: support@example.com', 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- Table des templates hotspot MikroTik
-- =====================================================
CREATE TABLE IF NOT EXISTS hotspot_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du template',
    description VARCHAR(255) DEFAULT NULL,
    template_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique du template',

    -- Personnalisation visuelle
    logo_position ENUM('top', 'left', 'center') DEFAULT 'center',
    background_type ENUM('color', 'gradient', 'image') DEFAULT 'gradient',
    background_color VARCHAR(7) DEFAULT '#1e3a5f',
    background_gradient_start VARCHAR(7) DEFAULT '#1e3a5f',
    background_gradient_end VARCHAR(7) DEFAULT '#0d1b2a',
    background_image VARCHAR(500) DEFAULT NULL,

    -- Couleurs
    primary_color VARCHAR(7) DEFAULT '#3b82f6' COMMENT 'Couleur bouton principal',
    secondary_color VARCHAR(7) DEFAULT '#10b981' COMMENT 'Couleur secondaire',
    text_color VARCHAR(7) DEFAULT '#ffffff' COMMENT 'Couleur texte',
    card_bg_color VARCHAR(7) DEFAULT '#ffffff' COMMENT 'Fond carte login',
    card_text_color VARCHAR(7) DEFAULT '#1f2937' COMMENT 'Texte carte',

    -- Textes personnalisables
    title_text VARCHAR(255) DEFAULT 'Bienvenue sur notre WiFi',
    subtitle_text VARCHAR(255) DEFAULT 'Connectez-vous pour accéder à Internet',
    login_button_text VARCHAR(50) DEFAULT 'Se connecter',
    username_placeholder VARCHAR(50) DEFAULT 'Code voucher',
    password_placeholder VARCHAR(50) DEFAULT 'Mot de passe',
    footer_text VARCHAR(255) DEFAULT 'Powered by RADIUS Manager',

    -- Options d'affichage
    show_logo TINYINT(1) DEFAULT 1,
    show_password_field TINYINT(1) DEFAULT 1,
    show_remember_me TINYINT(1) DEFAULT 0,
    show_footer TINYINT(1) DEFAULT 1,
    show_social_login TINYINT(1) DEFAULT 0,
    show_terms_link TINYINT(1) DEFAULT 0,
    terms_url VARCHAR(500) DEFAULT NULL,

    -- Contenu personnalisé
    html_content LONGTEXT DEFAULT NULL COMMENT 'HTML personnalisé complet',
    css_content LONGTEXT DEFAULT NULL COMMENT 'CSS additionnel',
    js_content LONGTEXT DEFAULT NULL COMMENT 'JavaScript additionnel',
    config JSON DEFAULT NULL COMMENT 'Configuration dynamique (sliders, services, profils, etc)',

    -- Métadonnées
    preview_image VARCHAR(500) DEFAULT NULL,
    is_default TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Template hotspot MikroTik par défaut
INSERT INTO hotspot_templates (name, description, template_code, background_type, background_gradient_start, background_gradient_end, primary_color, title_text, subtitle_text, is_default) VALUES
('MikroTik Modern', 'Template moderne et responsive pour portail captif MikroTik', 'mikrotik_modern', 'gradient', '#1e3a5f', '#0d1b2a', '#3b82f6', 'Bienvenue sur notre WiFi', 'Entrez votre code pour vous connecter', 1),
('MikroTik Minimal', 'Template minimaliste et épuré', 'mikrotik_minimal', 'color', '#ffffff', '#ffffff', '#000000', 'WiFi Hotspot', 'Connexion requise', 0)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- Table des transactions de paiement
-- =====================================================
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) NOT NULL UNIQUE COMMENT 'ID unique de la transaction',
    gateway_code VARCHAR(50) NOT NULL COMMENT 'Code de la passerelle utilisée',
    profile_id INT NOT NULL COMMENT 'Profil acheté',
    amount DECIMAL(10,2) NOT NULL COMMENT 'Montant payé',
    currency VARCHAR(10) DEFAULT 'XAF',
    status ENUM('pending', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    customer_phone VARCHAR(50) DEFAULT NULL,
    customer_email VARCHAR(100) DEFAULT NULL,
    customer_name VARCHAR(100) DEFAULT NULL,
    gateway_transaction_id VARCHAR(500) DEFAULT NULL COMMENT 'ID de transaction de la passerelle (token CinetPay, etc.)',
    gateway_response JSON DEFAULT NULL COMMENT 'Réponse complète de la passerelle',
    operator_reference VARCHAR(100) DEFAULT NULL COMMENT 'Référence opérateur Mobile Money',
    device_info JSON DEFAULT NULL COMMENT 'Informations appareil client (navigateur, OS, écran, etc.)',
    voucher_id INT DEFAULT NULL COMMENT 'ID du voucher généré',
    voucher_code VARCHAR(64) DEFAULT NULL COMMENT 'Code du voucher généré',
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_gateway (gateway_code),
    INDEX idx_profile (profile_id),
    INDEX idx_voucher (voucher_id),
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- Table des modules (activation/désactivation)
-- =====================================================
CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Code unique du module',
    name VARCHAR(100) NOT NULL COMMENT 'Nom affiché',
    description VARCHAR(255) DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'cube' COMMENT 'Icône Heroicons',
    is_active TINYINT(1) DEFAULT 0 COMMENT 'Module activé/désactivé',
    config JSON DEFAULT NULL COMMENT 'Configuration spécifique du module',
    display_order INT DEFAULT 0 COMMENT 'Ordre d''affichage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Modules par défaut
INSERT INTO modules (module_code, name, description, icon, is_active, display_order) VALUES
('loyalty', 'Programme de Fidélité', 'Récompenses automatiques pour les clients fidèles', 'gift', 1, 1),
('chat', 'Chat Client', 'Chat en temps réel avec les clients sur la page d''achat', 'chat-bubble-left-right', 0, 2),
('sms', 'Notifications SMS', 'Envoi de SMS aux clients (codes voucher, promotions)', 'device-phone-mobile', 0, 3),
('analytics', 'Statistiques Avancées', 'Tableaux de bord et rapports détaillés', 'chart-bar', 0, 4)
ON DUPLICATE KEY UPDATE module_code = VALUES(module_code);

-- =====================================================
-- Table des conversations de chat
-- =====================================================
CREATE TABLE IF NOT EXISTS chat_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(50) NOT NULL COMMENT 'Numéro de téléphone du client',
    customer_name VARCHAR(100) DEFAULT NULL COMMENT 'Nom du client (optionnel)',
    status ENUM('active', 'closed', 'archived') DEFAULT 'active',
    unread_count INT DEFAULT 0 COMMENT 'Messages non lus côté admin',
    last_message_at TIMESTAMP NULL DEFAULT NULL,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    closed_by INT DEFAULT NULL COMMENT 'Admin qui a fermé',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_last_message (last_message_at),
    FOREIGN KEY (closed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- Table des messages de chat
-- =====================================================
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_type ENUM('customer', 'admin') NOT NULL COMMENT 'Expéditeur',
    admin_id INT DEFAULT NULL COMMENT 'ID admin si envoyé par admin',
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_conversation (conversation_id),
    INDEX idx_created (created_at),
    INDEX idx_unread (is_read, sender_type),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;
