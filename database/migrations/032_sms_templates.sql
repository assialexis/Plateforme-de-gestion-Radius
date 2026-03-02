-- Migration: SMS Templates for PPPoE and Hotspot
-- Date: 2026-02-23

CREATE TABLE IF NOT EXISTS sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Template name',
    description VARCHAR(255) DEFAULT NULL COMMENT 'Template description',
    category ENUM('pppoe', 'hotspot') NOT NULL DEFAULT 'pppoe' COMMENT 'Template category',
    event_type VARCHAR(50) NOT NULL COMMENT 'Event trigger type',
    message_template TEXT NOT NULL COMMENT 'Message template with {{variables}}',
    days_before INT DEFAULT 0 COMMENT 'Days before event (0 = same day, negative = after)',
    is_active TINYINT(1) DEFAULT 1,
    admin_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_sms_tpl_event (category, event_type, days_before, admin_id),
    INDEX idx_sms_tpl_admin (admin_id),
    INDEX idx_sms_tpl_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default PPPoE templates
INSERT IGNORE INTO sms_templates (name, description, category, event_type, days_before, message_template, is_active, admin_id) VALUES
('Rappel 3 jours', 'Notification 3 jours avant expiration', 'pppoe', 'expiration_warning', 3,
'Bonjour {{customer_name}}, votre abonnement Internet ({{profile_name}}) expire dans {{days_remaining}} jours le {{expiration_date}}. Montant: {{profile_price}} FCFA. Renouvelez pour eviter la coupure. {{company_name}}', 1, 1),

('Rappel 1 jour', 'Notification 1 jour avant expiration', 'pppoe', 'expiration_warning', 1,
'URGENT: {{customer_name}}, votre abonnement {{profile_name}} expire DEMAIN ({{expiration_date}}). Renouvelez maintenant: {{profile_price}} FCFA. Contact: {{support_phone}}. {{company_name}}', 1, 1),

('Jour expiration', 'Notification le jour de l''expiration', 'pppoe', 'expiration_warning', 0,
'{{customer_name}}, votre abonnement {{profile_name}} expire AUJOURD''HUI. Votre connexion sera coupee sans renouvellement. Montant: {{profile_price}} FCFA. {{company_name}}', 1, 1),

('Compte expire', 'Notification apres expiration', 'pppoe', 'expired', -1,
'{{customer_name}}, votre abonnement Internet a expire le {{expiration_date}}. Compte: {{username}}. Pour reactiver: {{profile_price}} FCFA. Contact: {{support_phone}}. {{company_name}}', 1, 1),

('Bienvenue', 'Message de bienvenue nouveau client PPPoE', 'pppoe', 'welcome', 0,
'Bienvenue chez {{company_name}}! Votre compte Internet est actif. ID: {{username}} / MDP: {{password}}. Forfait: {{profile_name}}. Valide jusqu''au {{expiration_date}}. Support: {{support_phone}}', 1, 1),

('Paiement recu', 'Confirmation de paiement', 'pppoe', 'payment_received', 0,
'{{customer_name}}, paiement recu! Forfait: {{profile_name}} - {{profile_price}} FCFA. Valide jusqu''au {{expiration_date}}. Merci! {{company_name}}', 1, 1),

('Compte suspendu', 'Notification de suspension', 'pppoe', 'suspended', 0,
'{{customer_name}}, votre compte Internet ({{username}}) a ete suspendu. Pour reactiver, contactez-nous: {{support_phone}}. {{company_name}}', 1, 1),

('Compte reactive', 'Notification de reactivation', 'pppoe', 'reactivated', 0,
'{{customer_name}}, votre compte Internet ({{username}}) est de nouveau actif! Forfait: {{profile_name}}, valide jusqu''au {{expiration_date}}. {{company_name}}', 1, 1);

-- Default Hotspot templates
INSERT IGNORE INTO sms_templates (name, description, category, event_type, days_before, message_template, is_active, admin_id) VALUES
('Voucher cree', 'SMS avec code voucher', 'hotspot', 'voucher_created', 0,
'Votre code WiFi: {{voucher_code}}. Forfait: {{profile_name}} ({{duration}}). Connectez-vous au reseau {{hotspot_name}} et entrez ce code. {{company_name}}', 1, 1),

('Bienvenue Hotspot', 'Message de bienvenue hotspot', 'hotspot', 'welcome', 0,
'Bienvenue sur {{hotspot_name}}! Vous etes connecte avec le forfait {{profile_name}} ({{duration}}). Bon surf! {{company_name}}', 1, 1),

('Expiration voucher', 'Notification expiration voucher', 'hotspot', 'expiration_warning', 1,
'{{customer_name}}, votre acces WiFi ({{profile_name}}) expire demain. Pensez a renouveler votre voucher. {{company_name}}', 1, 1),

('Voucher expire', 'Notification voucher expire', 'hotspot', 'expired', 0,
'Votre acces WiFi {{profile_name}} a expire. Pour continuer a surfer, procurez-vous un nouveau voucher. {{company_name}}', 1, 1),

('Ticket de connexion', 'SMS avec details de connexion', 'hotspot', 'connection_info', 0,
'Info connexion WiFi - Reseau: {{hotspot_name}}, Code: {{voucher_code}}, Forfait: {{profile_name}}, Duree: {{duration}}, Debit: {{download_speed}}/{{upload_speed}}. {{company_name}}', 1, 1);
