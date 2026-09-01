-- ============================================================
-- Migration: Add Organism Structure Fields
-- HAZINA ASILI v2.1
-- ============================================================

ALTER TABLE `organisms`
    ADD COLUMN `order_name`      VARCHAR(100)  DEFAULT NULL AFTER `class`,
    ADD COLUMN `family`          VARCHAR(100)  DEFAULT NULL AFTER `order_name`,
    ADD COLUMN `genus`           VARCHAR(100)  DEFAULT NULL AFTER `family`,
    ADD COLUMN `species`         VARCHAR(100)  DEFAULT NULL AFTER `genus`,
    ADD COLUMN `cell_type`       ENUM('eukaryotic','prokaryotic') DEFAULT NULL AFTER `species`,
    ADD COLUMN `habitat`         VARCHAR(255)  DEFAULT NULL AFTER `cell_type`,
    ADD COLUMN `description`     TEXT          DEFAULT NULL AFTER `habitat`,
    ADD COLUMN `structure_image` VARCHAR(255)  DEFAULT NULL AFTER `description`,
    ADD COLUMN `created_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `structure_image`,
    ADD COLUMN `updated_at`      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Update existing seed data with structure info
UPDATE `organisms` SET
    order_name = 'Ericales', family = 'Theaceae', genus = 'Camellia', species = 'sinensis',
    cell_type = 'eukaryotic', habitat = 'Tropical and subtropical regions of Asia'
WHERE scientific_name = 'Camellia sinensis';

UPDATE `organisms` SET
    order_name = 'Zingiberales', family = 'Zingiberaceae', genus = 'Curcuma', species = 'longa',
    cell_type = 'eukaryotic', habitat = 'Tropical South Asia'
WHERE scientific_name = 'Curcuma longa';

UPDATE `organisms` SET
    order_name = 'Asparagales', family = 'Amaryllidaceae', genus = 'Allium', species = 'sativum',
    cell_type = 'eukaryotic', habitat = 'Central Asia, widely cultivated worldwide'
WHERE scientific_name = 'Allium sativum';

UPDATE `organisms` SET
    order_name = 'Zingiberales', family = 'Zingiberaceae', genus = 'Zingiber', species = 'officinale',
    cell_type = 'eukaryotic', habitat = 'Southeast Asia, tropical regions'
WHERE scientific_name = 'Zingiber officinale';

UPDATE `organisms` SET
    order_name = 'Eurotiales', family = 'Aspergillaceae', genus = 'Penicillium', species = 'chrysogenum',
    cell_type = 'eukaryotic', habitat = 'Soil, decaying organic matter, indoor environments'
WHERE scientific_name = 'Penicillium chrysogenum';

UPDATE `organisms` SET
    order_name = 'Streptomycetales', family = 'Streptomycetaceae', genus = 'Streptomyces', species = 'griseus',
    cell_type = 'prokaryotic', habitat = 'Soil worldwide'
WHERE scientific_name = 'Streptomyces griseus';
