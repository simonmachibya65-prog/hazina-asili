-- ============================================================
-- Migration: Add Compound Structure Image
-- HAZINA ASILI v2.1
-- ============================================================

ALTER TABLE `compounds`
    ADD COLUMN `structure_image` VARCHAR(255) DEFAULT NULL AFTER `description`;
