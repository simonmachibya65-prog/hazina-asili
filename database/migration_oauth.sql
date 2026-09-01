-- Migration: OAuth support
-- Adds oauth_provider and oauth_id columns to users table

ALTER TABLE users ADD COLUMN IF NOT EXISTS oauth_provider VARCHAR(20) DEFAULT NULL AFTER api_key;
ALTER TABLE users ADD COLUMN IF NOT EXISTS oauth_id VARCHAR(255) DEFAULT NULL AFTER oauth_provider;
ALTER TABLE users ADD INDEX idx_oauth (oauth_provider, oauth_id);
