-- Migration: Ajout d'informations étendues pour les clients PPPoE
-- Date: 2025-12-07
-- Description: Ajoute la localisation GPS et informations supplémentaires

-- Ajouter les colonnes pour la localisation
ALTER TABLE pppoe_users
ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitude GPS du client' AFTER customer_address,
ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitude GPS du client' AFTER latitude,
ADD COLUMN location_description VARCHAR(255) DEFAULT NULL COMMENT 'Description de la localisation' AFTER longitude;

-- Ajouter des informations supplémentaires sur le client
ALTER TABLE pppoe_users
ADD COLUMN customer_id_type VARCHAR(50) DEFAULT NULL COMMENT 'Type de pièce d''identité (CNI, Passeport, etc.)' AFTER customer_email,
ADD COLUMN customer_id_number VARCHAR(100) DEFAULT NULL COMMENT 'Numéro de pièce d''identité' AFTER customer_id_type,
ADD COLUMN customer_secondary_phone VARCHAR(50) DEFAULT NULL COMMENT 'Téléphone secondaire' AFTER customer_phone;

-- Ajouter informations sur l'installation
ALTER TABLE pppoe_users
ADD COLUMN installation_date DATE DEFAULT NULL COMMENT 'Date d''installation' AFTER location_description,
ADD COLUMN installation_tech VARCHAR(100) DEFAULT NULL COMMENT 'Technicien ayant fait l''installation' AFTER installation_date,
ADD COLUMN equipment_serial VARCHAR(100) DEFAULT NULL COMMENT 'Numéro de série de l''équipement' AFTER installation_tech;
