-- Migration 056: Changer nas_port de INT à BIGINT dans sessions
-- MikroTik envoie des valeurs NAS-Port qui dépassent la limite INT (2^31)
-- Le nœud RADIUS stocke déjà en BIGINT, la centrale doit être cohérente

ALTER TABLE sessions MODIFY COLUMN nas_port BIGINT DEFAULT NULL;
