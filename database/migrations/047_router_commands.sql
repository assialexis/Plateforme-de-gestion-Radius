-- =====================================================
-- Migration 047: Système de commandes routeur (Pull-Based Remote Control)
-- Date: 2026-03-02
-- Description: Queue de commandes BDD, polling token, walled garden auto
-- =====================================================

-- 1. Ajouter les colonnes de polling à la table nas
ALTER TABLE nas
    ADD COLUMN IF NOT EXISTS last_seen DATETIME DEFAULT NULL COMMENT 'Dernière fois que le routeur a contacté le serveur',
    ADD COLUMN IF NOT EXISTS polling_token VARCHAR(64) DEFAULT NULL COMMENT 'Token secret pour authentification polling',
    ADD COLUMN IF NOT EXISTS polling_interval INT DEFAULT 10 COMMENT 'Intervalle de polling en secondes',
    ADD COLUMN IF NOT EXISTS setup_installed_at DATETIME DEFAULT NULL COMMENT 'Date installation du script setup';

ALTER TABLE nas ADD INDEX IF NOT EXISTS idx_nas_last_seen (last_seen);
ALTER TABLE nas ADD INDEX IF NOT EXISTS idx_nas_polling_token (polling_token);

-- 2. Queue de commandes routeur (remplace le filesystem .rsc)
CREATE TABLE IF NOT EXISTS router_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    router_id VARCHAR(64) NOT NULL COMMENT 'ID routeur (matches nas.router_id)',
    nas_id INT DEFAULT NULL COMMENT 'FK vers nas',
    admin_id INT DEFAULT NULL COMMENT 'Isolation multi-tenant',

    -- Contenu de la commande
    command_type VARCHAR(50) DEFAULT 'raw' COMMENT 'Type: disconnect_hotspot, disconnect_pppoe, create_pppoe, set_rate_limit, raw, walled_garden, sync',
    command_content TEXT NOT NULL COMMENT 'Contenu du script RouterOS',
    command_description VARCHAR(255) DEFAULT NULL COMMENT 'Description lisible',

    -- Priorité et ordonnancement
    priority INT DEFAULT 50 COMMENT 'Plus bas = plus prioritaire (1-99)',

    -- Suivi de statut
    status ENUM('pending', 'sent', 'executed', 'failed', 'expired', 'cancelled') DEFAULT 'pending',

    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL COMMENT 'Quand la commande a été envoyée au routeur',
    executed_at DATETIME DEFAULT NULL COMMENT 'Quand le routeur a confirmé l\'exécution',
    expires_at DATETIME DEFAULT NULL COMMENT 'Auto-expiration si pas exécutée avant',

    -- Gestion des erreurs
    error_message VARCHAR(500) DEFAULT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,

    -- Audit
    created_by INT DEFAULT NULL COMMENT 'Admin/utilisateur qui a créé la commande',

    INDEX idx_rc_router_status (router_id, status),
    INDEX idx_rc_router_priority (router_id, status, priority, created_at),
    INDEX idx_rc_admin (admin_id),
    INDEX idx_rc_status (status),
    INDEX idx_rc_created (created_at),
    INDEX idx_rc_expires (expires_at),

    FOREIGN KEY (nas_id) REFERENCES nas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Domaines walled garden par passerelle de paiement
CREATE TABLE IF NOT EXISTS gateway_walled_garden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_code VARCHAR(50) NOT NULL COMMENT 'Code de la passerelle (fedapay, cinetpay, etc.)',
    domain VARCHAR(255) NOT NULL COMMENT 'Pattern de domaine pour le walled garden',
    port VARCHAR(20) DEFAULT '80,443',
    description VARCHAR(255) DEFAULT NULL,

    UNIQUE KEY uk_gwg_code_domain (gateway_code, domain),
    INDEX idx_gwg_gateway (gateway_code)
) ENGINE=InnoDB;

-- 4. Seed des domaines walled garden pour les passerelles connues
INSERT INTO gateway_walled_garden (gateway_code, domain, description) VALUES
    -- FedaPay
    ('fedapay', '*.fedapay.com', 'FedaPay wildcard'),
    ('fedapay', 'cdn.fedapay.com', 'FedaPay CDN'),
    ('fedapay', 'api.fedapay.com', 'FedaPay API'),
    -- CinetPay
    ('cinetpay', '*.cinetpay.com', 'CinetPay wildcard'),
    ('cinetpay', 'cdn.cinetpay.com', 'CinetPay CDN'),
    ('cinetpay', 'api-checkout.cinetpay.com', 'CinetPay checkout'),
    ('cinetpay', 'aws-mm-webpayment.bizao.com*', 'CinetPay Bizao'),
    -- FeexPay
    ('feexpay', '*.feexpay.me', 'FeexPay wildcard'),
    ('feexpay', 'api.feexpay.me', 'FeexPay API'),
    ('feexpay', 'feexpay.me', 'FeexPay main'),
    -- KkiaPay
    ('kkiapay', '*.kkiapay.me', 'KkiaPay wildcard'),
    ('kkiapay', 'api.kkiapay.me', 'KkiaPay API'),
    ('kkiapay', 'cdn.kkiapay.me', 'KkiaPay CDN'),
    -- FlexPay (RDC)
    ('flexpay', '*.flexpay.cd', 'FlexPay wildcard'),
    ('flexpay', 'backend.flexpay.cd', 'FlexPay backend'),
    -- MoneyFusion
    ('moneyfusion', '*.moneyfusion.net', 'MoneyFusion wildcard'),
    ('moneyfusion', 'pay.moneyfusion.net', 'MoneyFusion pay'),
    -- PayDunya
    ('paydunya', '*.paydunya.com', 'PayDunya wildcard'),
    ('paydunya', 'app.paydunya.com', 'PayDunya app'),
    -- PayGate Global
    ('paygate_global', '*.paygateglobal.com', 'PayGate wildcard'),
    -- Cryptomus
    ('cryptomus', '*.cryptomus.com', 'Cryptomus wildcard'),
    ('cryptomus', 'api.cryptomus.com', 'Cryptomus API'),
    -- YengaPay
    ('yengapay', '*.yengapay.com', 'YengaPay wildcard'),
    -- LigdiCash
    ('ligdicash', '*.ligdicash.com', 'LigdiCash wildcard'),
    ('ligdicash', 'app.ligdicash.com', 'LigdiCash app'),
    -- Moneroo
    ('moneroo', '*.moneroo.io', 'Moneroo wildcard'),
    ('moneroo', 'api.moneroo.io', 'Moneroo API'),
    -- Stripe
    ('stripe', '*.stripe.com', 'Stripe wildcard'),
    ('stripe', 'js.stripe.com', 'Stripe JS'),
    -- PayPal
    ('paypal', '*.paypal.com', 'PayPal wildcard'),
    ('paypal', '*.paypalobjects.com', 'PayPal assets')
ON DUPLICATE KEY UPDATE description = VALUES(description);
