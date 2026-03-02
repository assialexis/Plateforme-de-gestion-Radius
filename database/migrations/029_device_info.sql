-- Migration 029: Device Info Capture
-- Date: 2026-02-18
-- Description: Ajouter colonne device_info JSON à payment_transactions
-- pour capturer les informations appareil/navigateur du client lors du paiement

ALTER TABLE payment_transactions
ADD COLUMN IF NOT EXISTS device_info JSON DEFAULT NULL COMMENT 'Informations appareil client (navigateur, OS, écran, etc.)' AFTER operator_reference;
