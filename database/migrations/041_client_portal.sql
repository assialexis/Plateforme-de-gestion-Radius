-- =============================================
-- Migration 041: Portail Client PPPoE
-- =============================================

-- 1. Table des sessions client (auth séparée de l'admin)
CREATE TABLE IF NOT EXISTS client_sessions (
    id VARCHAR(64) PRIMARY KEY,
    pppoe_user_id INT NOT NULL,
    admin_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_client_pppoe_user (pppoe_user_id),
    INDEX idx_client_expires (expires_at),
    INDEX idx_client_admin (admin_id)
);

-- 2. Colonne metadata JSON sur pppoe_invoices (pour changement de plan)
ALTER TABLE pppoe_invoices ADD COLUMN IF NOT EXISTS metadata JSON DEFAULT NULL;

-- 3. Permissions du portail client
INSERT INTO permissions (permission_code, name, description, category, display_order) VALUES
('client_view_account', 'Voir le compte', 'Le client peut voir les informations de son compte', 'client_portal', 70),
('client_view_invoices', 'Voir les factures', 'Le client peut consulter ses factures', 'client_portal', 71),
('client_view_transactions', 'Voir les transactions', 'Le client peut voir l''historique des paiements', 'client_portal', 72),
('client_change_plan', 'Changer d''offre', 'Le client peut changer son offre/profil', 'client_portal', 73),
('client_renew', 'Renouveler l''abonnement', 'Le client peut prolonger ou renouveler son abonnement', 'client_portal', 74),
('client_view_traffic', 'Voir le trafic', 'Le client peut consulter sa consommation de données', 'client_portal', 75)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- 4. Permissions par défaut pour le rôle client (toutes activées)
INSERT IGNORE INTO role_permissions (role, permission_id, admin_id)
SELECT 'client', id, NULL FROM permissions WHERE category = 'client_portal';
