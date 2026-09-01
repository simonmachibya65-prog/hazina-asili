-- Migration v3.0: Security enhancements & API support
-- Run this after the base natural_compounds_db.sql

-- Rate limiting table
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempted_at),
    INDEX idx_email_time (email, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API key column for users
ALTER TABLE users ADD COLUMN IF NOT EXISTS api_key VARCHAR(64) DEFAULT NULL AFTER passport_document;
ALTER TABLE users ADD UNIQUE INDEX idx_api_key (api_key);

-- Session tracking for concurrent session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(128) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(512) DEFAULT NULL,
    last_activity DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Compound version history improvements
ALTER TABLE compound_versions ADD COLUMN IF NOT EXISTS change_type ENUM('update','rollback','create') DEFAULT 'update' AFTER version_number;

-- Full text search index for compounds
ALTER TABLE compounds ADD FULLTEXT INDEX ft_compound_search (name, formula, description);

-- Full text search index for organisms
ALTER TABLE organisms ADD FULLTEXT INDEX ft_organism_search (scientific_name, kingdom, phylum, family, genus, habitat, description);
