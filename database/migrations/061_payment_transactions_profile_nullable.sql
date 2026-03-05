-- Migration: 061_payment_transactions_profile_nullable
-- Description: Rendre profile_id nullable pour les recharges de crédits (pas de profil associé)

ALTER TABLE payment_transactions MODIFY COLUMN profile_id INT DEFAULT NULL COMMENT 'Profil acheté (NULL pour credit_recharge)';
