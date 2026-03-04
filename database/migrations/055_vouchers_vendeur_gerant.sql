-- Migration: Ajouter colonnes vendeur_id et gerant_id aux vouchers
-- Permet de tracer quel vendeur/gérant a créé le voucher

ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS vendeur_id INT DEFAULT NULL COMMENT 'Vendeur qui a créé le voucher' AFTER admin_id;
ALTER TABLE vouchers ADD COLUMN IF NOT EXISTS gerant_id INT DEFAULT NULL COMMENT 'Gérant qui a créé le voucher' AFTER vendeur_id;
ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_vouchers_vendeur (vendeur_id);
ALTER TABLE vouchers ADD INDEX IF NOT EXISTS idx_vouchers_gerant (gerant_id);
