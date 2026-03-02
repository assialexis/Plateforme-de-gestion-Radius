-- Migration: Système de notifications Telegram
-- Date: 2025-12-12

-- Table de configuration Telegram
CREATE TABLE IF NOT EXISTS telegram_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_token VARCHAR(255) NOT NULL COMMENT 'Token du bot Telegram',
    default_chat_id VARCHAR(100) COMMENT 'Chat ID par défaut pour les notifications',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT 'Activer/désactiver les notifications',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des templates de messages
CREATE TABLE IF NOT EXISTS telegram_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du template',
    description VARCHAR(255) COMMENT 'Description du template',
    event_type ENUM('expiration_warning', 'expired', 'payment_reminder', 'welcome', 'suspended', 'reactivated', 'custom') NOT NULL DEFAULT 'custom',
    message_template TEXT NOT NULL COMMENT 'Template du message avec variables',
    days_before INT DEFAULT 0 COMMENT 'Jours avant expiration pour déclencher (0 = jour même, -1 = après expiration)',
    is_active TINYINT(1) DEFAULT 1,
    send_time TIME DEFAULT '09:00:00' COMMENT 'Heure d\'envoi préférée',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_days (event_type, days_before)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des destinataires (admins, gérants, etc.)
CREATE TABLE IF NOT EXISTS telegram_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT COMMENT 'ID utilisateur système (nullable pour destinataires externes)',
    chat_id VARCHAR(100) NOT NULL COMMENT 'Telegram Chat ID',
    name VARCHAR(100) NOT NULL COMMENT 'Nom du destinataire',
    role ENUM('admin', 'manager', 'accountant', 'technician', 'custom') DEFAULT 'custom',
    receive_expiration_alerts TINYINT(1) DEFAULT 1,
    receive_payment_alerts TINYINT(1) DEFAULT 1,
    receive_system_alerts TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat_id (chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications envoyées (historique)
CREATE TABLE IF NOT EXISTS telegram_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    recipient_id INT,
    pppoe_user_id INT COMMENT 'ID du client PPPoE concerné',
    chat_id VARCHAR(100) NOT NULL,
    message TEXT NOT NULL COMMENT 'Message envoyé (après substitution des variables)',
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT COMMENT 'Message d\'erreur si échec',
    telegram_message_id VARCHAR(50) COMMENT 'ID du message Telegram',
    scheduled_at TIMESTAMP NULL COMMENT 'Date/heure planifiée',
    sent_at TIMESTAMP NULL COMMENT 'Date/heure d\'envoi effectif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_pppoe_user (pppoe_user_id),
    INDEX idx_scheduled (scheduled_at),
    FOREIGN KEY (template_id) REFERENCES telegram_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (recipient_id) REFERENCES telegram_recipients(id) ON DELETE SET NULL,
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour éviter les doublons de notifications
CREATE TABLE IF NOT EXISTS telegram_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    template_id INT NOT NULL,
    notification_date DATE NOT NULL COMMENT 'Date de la notification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_notification (pppoe_user_id, template_id, notification_date),
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES telegram_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insérer les templates par défaut
INSERT INTO telegram_templates (name, description, event_type, days_before, message_template, is_active) VALUES
('Rappel 7 jours', 'Notification 7 jours avant expiration', 'expiration_warning', 7,
'🔔 *Rappel d\'expiration*

Bonjour {{customer_name}},

Votre abonnement Internet expire dans *{{days_remaining}} jours* (le {{expiration_date}}).

📋 *Détails:*
• Compte: `{{username}}`
• Forfait: {{profile_name}}
• Prix: {{profile_price}} FCFA

💳 Pensez à renouveler pour éviter toute interruption de service.

_Message automatique - NAS System_', 1),

('Rappel 3 jours', 'Notification 3 jours avant expiration', 'expiration_warning', 3,
'⚠️ *Expiration imminente!*

Bonjour {{customer_name}},

Votre abonnement expire dans *{{days_remaining}} jours* seulement!

📅 Date d\'expiration: {{expiration_date}}
👤 Compte: `{{username}}`
📦 Forfait: {{profile_name}}

💰 Montant à payer: *{{profile_price}} FCFA*

Contactez-nous rapidement pour renouveler.

_Message automatique - NAS System_', 1),

('Rappel 1 jour', 'Notification 1 jour avant expiration', 'expiration_warning', 1,
'🚨 *URGENT - Expiration demain!*

Bonjour {{customer_name}},

Votre abonnement expire *DEMAIN* ({{expiration_date}})!

👤 Compte: `{{username}}`
📦 Forfait: {{profile_name}}
💰 Renouvellement: *{{profile_price}} FCFA*

⚡ Renouvelez aujourd\'hui pour éviter la coupure!

📞 Contact: {{support_phone}}

_Message automatique - NAS System_', 1),

('Jour d\'expiration', 'Notification le jour de l\'expiration', 'expiration_warning', 0,
'❌ *Expiration aujourd\'hui!*

Bonjour {{customer_name}},

Votre abonnement Internet expire *AUJOURD\'HUI*!

👤 Compte: `{{username}}`
📦 Forfait: {{profile_name}}

🔴 Votre connexion sera coupée si vous ne renouvelez pas.

💳 Montant: *{{profile_price}} FCFA*
📞 Contact: {{support_phone}}

_Message automatique - NAS System_', 1),

('Compte expiré', 'Notification après expiration', 'expired', -1,
'🔴 *Compte expiré*

Bonjour {{customer_name}},

Votre abonnement Internet a expiré le {{expiration_date}}.

👤 Compte: `{{username}}`
📦 Forfait: {{profile_name}}

🔄 Pour réactiver votre connexion:
💰 Montant: *{{profile_price}} FCFA*
📞 Contact: {{support_phone}}

_Message automatique - NAS System_', 1),

('Bienvenue', 'Message de bienvenue pour nouveau client', 'welcome', 0,
'🎉 *Bienvenue chez nous!*

Bonjour {{customer_name}},

Votre compte Internet est maintenant actif!

📋 *Vos informations:*
• Identifiant: `{{username}}`
• Mot de passe: `{{password}}`
• Forfait: {{profile_name}}
• Vitesse: {{download_speed}} / {{upload_speed}}
• Valide jusqu\'au: {{expiration_date}}

📞 Support: {{support_phone}}

Merci de votre confiance!

_Message automatique - NAS System_', 1),

('Compte suspendu', 'Notification de suspension', 'suspended', 0,
'⛔ *Compte suspendu*

Bonjour {{customer_name}},

Votre compte Internet a été suspendu.

👤 Compte: `{{username}}`
📅 Date: {{current_date}}

Pour réactiver votre connexion, veuillez nous contacter.
📞 Contact: {{support_phone}}

_Message automatique - NAS System_', 1),

('Compte réactivé', 'Notification de réactivation', 'reactivated', 0,
'✅ *Compte réactivé*

Bonjour {{customer_name}},

Votre compte Internet est de nouveau actif!

👤 Compte: `{{username}}`
📦 Forfait: {{profile_name}}
📅 Valide jusqu\'au: {{expiration_date}}

Merci pour votre paiement!

_Message automatique - NAS System_', 1);

-- Ajouter une colonne pour le numéro de téléphone Telegram du client (optionnel)
ALTER TABLE pppoe_users
ADD COLUMN IF NOT EXISTS telegram_chat_id VARCHAR(100) COMMENT 'Chat ID Telegram du client' AFTER customer_email,
ADD COLUMN IF NOT EXISTS telegram_notifications TINYINT(1) DEFAULT 1 COMMENT 'Activer notifications Telegram' AFTER telegram_chat_id;
