-- Migration: Module WhatsApp via Green API
-- Date: 2026-02-17

-- Table de configuration WhatsApp (Green API)
CREATE TABLE IF NOT EXISTS whatsapp_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_instance VARCHAR(100) NOT NULL COMMENT 'ID Instance Green API',
    api_token_instance VARCHAR(255) NOT NULL COMMENT 'Token API Green API',
    api_url VARCHAR(255) DEFAULT 'https://api.green-api.com' COMMENT 'URL de base API',
    default_phone VARCHAR(20) COMMENT 'Numéro par défaut pour les notifications',
    country_code VARCHAR(5) DEFAULT '229' COMMENT 'Code pays par défaut (229 = Bénin)',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT 'Activer/désactiver les notifications',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des templates de messages WhatsApp
CREATE TABLE IF NOT EXISTS whatsapp_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nom du template',
    description VARCHAR(255) COMMENT 'Description du template',
    event_type ENUM('expiration_warning', 'expired', 'payment_reminder', 'invoice_created', 'payment_received', 'welcome', 'suspended', 'reactivated', 'custom') NOT NULL DEFAULT 'custom',
    message_template TEXT NOT NULL COMMENT 'Template du message avec variables {{...}}',
    days_before INT DEFAULT 0 COMMENT 'Jours avant expiration (0 = jour même, -1 = après)',
    is_active TINYINT(1) DEFAULT 1,
    send_time TIME DEFAULT '09:00:00' COMMENT 'Heure d''envoi préférée',
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_event_days (event_type, days_before)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des notifications envoyées (historique)
CREATE TABLE IF NOT EXISTS whatsapp_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT,
    pppoe_user_id INT COMMENT 'ID du client PPPoE concerné',
    phone VARCHAR(20) NOT NULL COMMENT 'Numéro WhatsApp du destinataire',
    message TEXT NOT NULL COMMENT 'Message envoyé (après substitution)',
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT COMMENT 'Message d''erreur si échec',
    wa_message_id VARCHAR(100) COMMENT 'ID du message WhatsApp',
    scheduled_at TIMESTAMP NULL COMMENT 'Date/heure planifiée',
    sent_at TIMESTAMP NULL COMMENT 'Date/heure d''envoi effectif',
    admin_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_pppoe_user (pppoe_user_id),
    INDEX idx_scheduled (scheduled_at),
    FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour éviter les doublons de notifications
CREATE TABLE IF NOT EXISTS whatsapp_notification_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    template_id INT NOT NULL,
    notification_date DATE NOT NULL COMMENT 'Date de la notification',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_notification (pppoe_user_id, template_id, notification_date),
    FOREIGN KEY (pppoe_user_id) REFERENCES pppoe_users(id) ON DELETE CASCADE,
    FOREIGN KEY (template_id) REFERENCES whatsapp_templates(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter colonnes WhatsApp aux utilisateurs PPPoE
ALTER TABLE pppoe_users
ADD COLUMN IF NOT EXISTS whatsapp_phone VARCHAR(20) COMMENT 'Numéro WhatsApp du client' AFTER customer_phone,
ADD COLUMN IF NOT EXISTS whatsapp_notifications TINYINT(1) DEFAULT 1 COMMENT 'Activer notifications WhatsApp' AFTER whatsapp_phone;

-- Templates par défaut
INSERT INTO whatsapp_templates (name, description, event_type, days_before, message_template, is_active) VALUES
('Rappel 7 jours', 'Notification 7 jours avant expiration', 'expiration_warning', 7,
'🔔 *Rappel d''expiration*

Bonjour {{customer_name}},

Votre abonnement Internet expire dans *{{days_remaining}} jours* (le {{expiration_date}}).

📋 *Détails:*
• Compte: {{username}}
• Forfait: {{profile_name}}
• Prix: {{profile_price}} FCFA

💳 Pensez à renouveler pour éviter toute interruption de service.

_{{company_name}}_', 1),

('Rappel 3 jours', 'Notification 3 jours avant expiration', 'expiration_warning', 3,
'⚠️ *Expiration imminente!*

Bonjour {{customer_name}},

Votre abonnement expire dans *{{days_remaining}} jours* seulement!

📅 Date d''expiration: {{expiration_date}}
👤 Compte: {{username}}
📦 Forfait: {{profile_name}}

💰 Montant à payer: *{{profile_price}} FCFA*

Contactez-nous rapidement pour renouveler.

_{{company_name}}_', 1),

('Rappel 1 jour', 'Notification 1 jour avant expiration', 'expiration_warning', 1,
'🚨 *URGENT - Expiration demain!*

Bonjour {{customer_name}},

Votre abonnement expire *DEMAIN* ({{expiration_date}})!

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}
💰 Renouvellement: *{{profile_price}} FCFA*

⚡ Renouvelez aujourd''hui pour éviter la coupure!

📞 Contact: {{support_phone}}

_{{company_name}}_', 1),

('Jour d''expiration', 'Notification le jour de l''expiration', 'expiration_warning', 0,
'❌ *Expiration aujourd''hui!*

Bonjour {{customer_name}},

Votre abonnement Internet expire *AUJOURD''HUI*!

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}

🔴 Votre connexion sera coupée si vous ne renouvelez pas.

💳 Montant: *{{profile_price}} FCFA*
📞 Contact: {{support_phone}}

_{{company_name}}_', 1),

('Compte expiré', 'Notification après expiration', 'expired', -1,
'🔴 *Compte expiré*

Bonjour {{customer_name}},

Votre abonnement Internet a expiré le {{expiration_date}}.

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}

🔄 Pour réactiver votre connexion:
💰 Montant: *{{profile_price}} FCFA*
📞 Contact: {{support_phone}}

_{{company_name}}_', 1),

('Bienvenue', 'Message de bienvenue pour nouveau client', 'welcome', 0,
'🎉 *Bienvenue chez {{company_name}}!*

Bonjour {{customer_name}},

Votre compte Internet est maintenant actif!

📋 *Vos informations:*
• Identifiant: {{username}}
• Mot de passe: {{password}}
• Forfait: {{profile_name}}
• Vitesse: {{download_speed}} / {{upload_speed}}
• Valide jusqu''au: {{expiration_date}}

📞 Support: {{support_phone}}

Merci de votre confiance!', 1),

('Compte suspendu', 'Notification de suspension', 'suspended', 0,
'⛔ *Compte suspendu*

Bonjour {{customer_name}},

Votre compte Internet a été suspendu.

👤 Compte: {{username}}
📅 Date: {{current_date}}

Pour réactiver votre connexion, veuillez nous contacter.
📞 Contact: {{support_phone}}

_{{company_name}}_', 1),

('Compte réactivé', 'Notification de réactivation', 'reactivated', 0,
'✅ *Compte réactivé*

Bonjour {{customer_name}},

Votre compte Internet est de nouveau actif!

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}
📅 Valide jusqu''au: {{expiration_date}}

Merci pour votre paiement!

_{{company_name}}_', 1),

('Facture créée', 'Notification de nouvelle facture', 'invoice_created', 0,
'📄 *Nouvelle facture*

Bonjour {{customer_name}},

Une nouvelle facture a été générée pour votre compte.

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}
💰 Montant: *{{profile_price}} FCFA*
📅 Date: {{current_date}}

Merci de procéder au paiement dans les meilleurs délais.
📞 Contact: {{support_phone}}

_{{company_name}}_', 1),

('Paiement reçu', 'Confirmation de paiement', 'payment_received', 0,
'✅ *Paiement reçu*

Bonjour {{customer_name}},

Nous confirmons la réception de votre paiement.

👤 Compte: {{username}}
📦 Forfait: {{profile_name}}
💰 Montant: *{{profile_price}} FCFA*
📅 Valide jusqu''au: {{expiration_date}}

Merci pour votre paiement!

_{{company_name}}_', 1);
