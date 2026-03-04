-- Add description/comment field to radius_servers
ALTER TABLE radius_servers ADD COLUMN description TEXT NULL AFTER name;
