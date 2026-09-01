-- ============================================================
-- HAZINA ASILI v2.0 — MySQL Database Schema
-- Natural Organic Compounds Management System
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `hazina-asili`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hazina_asili`;

-- ── Drop all tables (reverse dependency order) ────────────────
DROP TABLE IF EXISTS `error_log`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `approval_comments`;
DROP TABLE IF EXISTS `compound_versions`;
DROP TABLE IF EXISTS `activity_log`;
DROP TABLE IF EXISTS `researcher_recommendations`;
DROP TABLE IF EXISTS `researcher_insights`;
DROP TABLE IF EXISTS `compound_reference`;
DROP TABLE IF EXISTS `compounds`;
DROP TABLE IF EXISTS `organisms`;
DROP TABLE IF EXISTS `references`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- TABLE: users
-- ============================================================
CREATE TABLE `users` (
    `id`                  INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`                VARCHAR(120)    NOT NULL,
    `email`               VARCHAR(180)    NOT NULL,
    `password`            VARCHAR(255)    NOT NULL,
    `role`                ENUM('admin','researcher') NOT NULL DEFAULT 'researcher',
    `bio`                 TEXT            DEFAULT NULL,
    `institution`         VARCHAR(200)    DEFAULT NULL,
    `avatar`              VARCHAR(255)    DEFAULT NULL,
    `passport_document`   VARCHAR(255)    DEFAULT NULL,
    `reset_token`         VARCHAR(64)     DEFAULT NULL,
    `reset_expires`       DATETIME        DEFAULT NULL,
    `created_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: organisms
-- ============================================================
CREATE TABLE `organisms` (
    `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `kingdom`         VARCHAR(100)  NOT NULL,
    `phylum`          VARCHAR(100)  NOT NULL,
    `class`           VARCHAR(100)  NOT NULL,
    `scientific_name` VARCHAR(200)  NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_organisms_name` (`scientific_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: compounds
-- ============================================================
CREATE TABLE `compounds` (
    `id`               INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `name`             VARCHAR(200)    NOT NULL,
    `formula`          VARCHAR(100)    NOT NULL,
    `molecular_weight` DECIMAL(10,4)   NOT NULL,
    `description`      TEXT            DEFAULT NULL,
    `organism_id`      INT UNSIGNED    DEFAULT NULL,
    `created_by`       INT UNSIGNED    DEFAULT NULL,
    `version`          INT UNSIGNED    NOT NULL DEFAULT 1,
    `created_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_compounds_name`    (`name`),
    KEY `idx_compounds_formula` (`formula`),
    CONSTRAINT `fk_compounds_organism`
        FOREIGN KEY (`organism_id`) REFERENCES `organisms` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_compounds_creator`
        FOREIGN KEY (`created_by`)  REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: compound_versions  (version history + rollback)
-- ============================================================
CREATE TABLE `compound_versions` (
    `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `compound_id`      INT UNSIGNED  NOT NULL,
    `version`          INT UNSIGNED  NOT NULL,
    `name`             VARCHAR(200)  NOT NULL,
    `formula`          VARCHAR(100)  NOT NULL,
    `molecular_weight` DECIMAL(10,4) NOT NULL,
    `description`      TEXT          DEFAULT NULL,
    `organism_id`      INT UNSIGNED  DEFAULT NULL,
    `changed_by`       INT UNSIGNED  DEFAULT NULL,
    `change_summary`   VARCHAR(500)  DEFAULT NULL,
    `old_values`       JSON          DEFAULT NULL,
    `new_values`       JSON          DEFAULT NULL,
    `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_compound_version` (`compound_id`, `version`),
    KEY `idx_cv_compound` (`compound_id`),
    CONSTRAINT `fk_cv_compound`
        FOREIGN KEY (`compound_id`) REFERENCES `compounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cv_user`
        FOREIGN KEY (`changed_by`)  REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: references
-- ============================================================
CREATE TABLE `references` (
    `id`       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `title`    VARCHAR(400)  NOT NULL,
    `author`   VARCHAR(300)  NOT NULL,
    `year`     YEAR          NOT NULL,
    `citation` TEXT          NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: compound_reference  (junction)
-- ============================================================
CREATE TABLE `compound_reference` (
    `compound_id`   INT UNSIGNED NOT NULL,
    `reference_id`  INT UNSIGNED NOT NULL,
    PRIMARY KEY (`compound_id`, `reference_id`),
    CONSTRAINT `fk_cr_compound`
        FOREIGN KEY (`compound_id`)  REFERENCES `compounds`  (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cr_reference`
        FOREIGN KEY (`reference_id`) REFERENCES `references` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: researcher_insights
-- ============================================================
CREATE TABLE `researcher_insights` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`      INT UNSIGNED NOT NULL,
    `compound_id`  INT UNSIGNED NOT NULL,
    `insight_text` TEXT         NOT NULL,
    `status`       ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_comment`TEXT         DEFAULT NULL,
    `reviewed_by`  INT UNSIGNED DEFAULT NULL,
    `reviewed_at`  DATETIME     DEFAULT NULL,
    `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_insights_status` (`status`),
    KEY `idx_insights_user`   (`user_id`),
    CONSTRAINT `fk_insights_user`
        FOREIGN KEY (`user_id`)     REFERENCES `users`     (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_insights_compound`
        FOREIGN KEY (`compound_id`) REFERENCES `compounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_insights_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: researcher_recommendations
-- ============================================================
CREATE TABLE `researcher_recommendations` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `compound_id`     INT UNSIGNED NOT NULL,
    `field_to_change` ENUM('name','formula','molecular_weight','description') NOT NULL,
    `suggested_value` TEXT         NOT NULL,
    `status`          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `admin_comment`   TEXT         DEFAULT NULL,
    `reviewed_by`     INT UNSIGNED DEFAULT NULL,
    `reviewed_at`     DATETIME     DEFAULT NULL,
    `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_recs_status` (`status`),
    KEY `idx_recs_user`   (`user_id`),
    CONSTRAINT `fk_recs_user`
        FOREIGN KEY (`user_id`)     REFERENCES `users`     (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recs_compound`
        FOREIGN KEY (`compound_id`) REFERENCES `compounds` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recs_reviewer`
        FOREIGN KEY (`reviewed_by`) REFERENCES `users`     (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: approval_comments  (workflow history)
-- ============================================================
CREATE TABLE `approval_comments` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `entity_type` ENUM('insight','recommendation') NOT NULL,
    `entity_id`   INT UNSIGNED NOT NULL,
    `user_id`     INT UNSIGNED NOT NULL,
    `comment`     TEXT         NOT NULL,
    `action`      ENUM('comment','approve','reject') NOT NULL DEFAULT 'comment',
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_ac_entity` (`entity_type`, `entity_id`),
    CONSTRAINT `fk_ac_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: activity_log  (full audit trail)
-- ============================================================
CREATE TABLE `activity_log` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED  DEFAULT NULL,
    `action`      VARCHAR(100)  NOT NULL,
    `entity_type` VARCHAR(50)   DEFAULT NULL,
    `entity_id`   INT UNSIGNED  DEFAULT NULL,
    `old_values`  JSON          DEFAULT NULL,
    `new_values`  JSON          DEFAULT NULL,
    `details`     TEXT          DEFAULT NULL,
    `ip_address`  VARCHAR(45)   DEFAULT NULL,
    `user_agent`  VARCHAR(300)  DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_log_user`   (`user_id`),
    KEY `idx_log_action` (`action`),
    KEY `idx_log_entity` (`entity_type`, `entity_id`),
    KEY `idx_log_date`   (`created_at`),
    CONSTRAINT `fk_log_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: notifications
-- ============================================================
CREATE TABLE `notifications` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `user_id`    INT UNSIGNED  NOT NULL,
    `type`       VARCHAR(50)   NOT NULL DEFAULT 'info',
    `title`      VARCHAR(200)  NOT NULL,
    `message`    TEXT          NOT NULL,
    `link`       VARCHAR(400)  DEFAULT NULL,
    `is_read`    TINYINT(1)    NOT NULL DEFAULT 0,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user`    (`user_id`),
    KEY `idx_notif_is_read` (`is_read`),
    CONSTRAINT `fk_notif_user`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: error_log  (system errors)
-- ============================================================
CREATE TABLE `error_log` (
    `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `level`      ENUM('notice','warning','critical') NOT NULL DEFAULT 'notice',
    `message`    TEXT          NOT NULL,
    `file`       VARCHAR(400)  DEFAULT NULL,
    `line`       INT UNSIGNED  DEFAULT NULL,
    `trace`      TEXT          DEFAULT NULL,
    `user_id`    INT UNSIGNED  DEFAULT NULL,
    `url`        VARCHAR(500)  DEFAULT NULL,
    `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_err_level` (`level`),
    KEY `idx_err_date`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Organisms
INSERT INTO `organisms` (`kingdom`, `phylum`, `class`, `scientific_name`) VALUES
('Plantae',   'Tracheophyta',  'Magnoliopsida', 'Camellia sinensis'),
('Plantae',   'Tracheophyta',  'Magnoliopsida', 'Curcuma longa'),
('Plantae',   'Tracheophyta',  'Magnoliopsida', 'Allium sativum'),
('Plantae',   'Tracheophyta',  'Magnoliopsida', 'Zingiber officinale'),
('Fungi',     'Ascomycota',    'Eurotiomycetes','Penicillium chrysogenum'),
('Bacteria',  'Actinobacteria','Actinomycetia', 'Streptomyces griseus');

-- References
INSERT INTO `references` (`title`, `author`, `year`, `citation`) VALUES
('Quercetin: A Versatile Flavonoid', 'Boots, A.W., Haenen, G.R., Bast, A.', 2008,
 'Boots AW, Haenen GR, Bast A. Health effects of quercetin. Eur J Pharmacol. 2008;585(2-3):325-37.'),
('Curcumin: Biological and Medicinal Properties', 'Aggarwal, B.B., Harikumar, K.B.', 2009,
 'Aggarwal BB, Harikumar KB. Potential therapeutic effects of curcumin. Int J Biochem Cell Biol. 2009;41(1):40-59.'),
('Allicin: Chemistry and Biological Properties', 'Borlinghaus, J. et al.', 2014,
 'Borlinghaus J et al. Allicin: chemistry and biological properties. Molecules. 2014;19(8):12591-618.'),
('Penicillin: Discovery and Development', 'Fleming, A.', 1929,
 'Fleming A. On the antibacterial action of cultures of a Penicillium. Br J Exp Pathol. 1929;10(3):226-236.');

-- Compounds (created_by will be set by seed_users.php)
INSERT INTO `compounds` (`name`, `formula`, `molecular_weight`, `description`, `organism_id`, `version`) VALUES
('Quercetin',    'C15H10O7',    302.2357, 'A plant flavonoid found in many fruits, vegetables, and grains. Known for its antioxidant and anti-inflammatory properties.', 1, 1),
('Curcumin',     'C21H20O6',    368.3799, 'The principal curcuminoid of turmeric. Exhibits anti-inflammatory, antioxidant, and potential anticancer properties.', 2, 1),
('Allicin',      'C6H10OS2',    162.2700, 'An organosulfur compound obtained from garlic. Responsible for the characteristic odor and many health benefits of garlic.', 3, 1),
('Gingerol',     'C17H26O4',    294.3800, 'The active constituent of fresh ginger. Exhibits anti-nausea, anti-inflammatory, and antioxidant effects.', 4, 1),
('Penicillin G', 'C16H18N2O4S', 334.3900, 'A beta-lactam antibiotic produced by Penicillium fungi. The first widely used antibiotic in clinical medicine.', 5, 1),
('Streptomycin', 'C21H39N7O12', 581.5700, 'An aminoglycoside antibiotic produced by Streptomyces griseus. Used to treat tuberculosis and other bacterial infections.', 6, 1),
('Resveratrol',  'C14H12O3',    228.2440, 'A stilbenoid found in the skin of red grapes. Associated with cardiovascular protection and anti-aging properties.', NULL, 1),
('Caffeine',     'C8H10N4O2',   194.1900, 'A purine alkaloid found in coffee, tea, and cacao. Acts as a central nervous system stimulant.', 1, 1);

-- Attach references to compounds
INSERT INTO `compound_reference` (`compound_id`, `reference_id`) VALUES
(1, 1), (2, 2), (3, 3), (5, 4);

-- ============================================================
-- AFTER IMPORT: Run seed_users.php to create demo accounts
-- Admin:      admin@HAZINA ASILI.com      / Admin@1234
-- Researcher: researcher@HAZINA ASILI.com / Admin@1234
-- ============================================================
