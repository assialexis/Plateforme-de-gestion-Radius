-- Migration: Update System Tables
-- Date: 2026-03-04
-- Description: Create migration tracking and update history tables

CREATE TABLE IF NOT EXISTS schema_migrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    migration VARCHAR(255) NOT NULL UNIQUE,
    batch INT NOT NULL DEFAULT 1,
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    execution_time_ms INT DEFAULT NULL,
    checksum VARCHAR(64) DEFAULT NULL,
    INDEX idx_batch (batch)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS system_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    from_version VARCHAR(20) NOT NULL,
    to_version VARCHAR(20) NOT NULL,
    update_type ENUM('full', 'migration_only', 'code_only') DEFAULT 'full',
    status ENUM('started', 'completed', 'failed', 'rolled_back') NOT NULL,
    migrations_run INT DEFAULT 0,
    backup_path VARCHAR(500) DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    started_by VARCHAR(100) DEFAULT NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
