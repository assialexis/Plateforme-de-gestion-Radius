-- Migration: Add location fields to NAS table
-- Date: 2025-12-07

-- Add location columns to nas table
ALTER TABLE nas
ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL COMMENT 'Latitude GPS du NAS/Routeur' AFTER description,
ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL COMMENT 'Longitude GPS du NAS/Routeur' AFTER latitude,
ADD COLUMN address VARCHAR(255) DEFAULT NULL COMMENT 'Adresse du NAS/Routeur' AFTER longitude;

-- Add index for geospatial queries (optional, for future use)
ALTER TABLE nas ADD INDEX idx_location (latitude, longitude);
