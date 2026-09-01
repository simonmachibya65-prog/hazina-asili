-- Migration: Add passport_document column to users table
-- Run this if you already have the database created

ALTER TABLE `users` 
ADD COLUMN `passport_document` VARCHAR(255) DEFAULT NULL 
AFTER `avatar`;
