-- ==========================================================
-- visit-tracker: Database Schema
-- Database: visit_tracker_db
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `visit_tracker_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `visit_tracker_db`;

-- ----------------------------------------------------------
-- 1. Table: sites
-- Stores websites registered for tracking
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `domain` VARCHAR(255) NOT NULL UNIQUE,
  `site_token` VARCHAR(64) NOT NULL UNIQUE,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_sites_token` (`site_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. Table: pages
-- Stores unique URLs tracked per site
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `site_id` INT NOT NULL,
  `url` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_site_page` (`site_id`, `url`),
  INDEX `idx_pages_url` (`url`),
  INDEX `idx_pages_site_id` (`site_id`),
  CONSTRAINT `fk_pages_site` 
    FOREIGN KEY (`site_id`) 
    REFERENCES `sites` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. Table: visits
-- Logs each individual visit with an anonymous visitor hash (GDPR compliant)
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `visits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `page_id` INT NOT NULL,
  `visitor_hash` VARCHAR(64) NOT NULL,
  `device` VARCHAR(20) NOT NULL DEFAULT 'Desktop',
  `visit_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_visits_page_time` (`page_id`, `visit_time`),
  INDEX `idx_visits_time` (`visit_time`),
  INDEX `idx_visits_hash` (`visitor_hash`),
  INDEX `idx_visits_device` (`device`),
  CONSTRAINT `fk_visits_page` 
    FOREIGN KEY (`page_id`) 
    REFERENCES `pages` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Initial sample data (default site for testing)
-- ----------------------------------------------------------
INSERT INTO `sites` (`domain`, `site_token`, `created_at`)
VALUES ('localhost', 'demo_token_visit_tracker', NOW())
ON DUPLICATE KEY UPDATE `domain` = VALUES(`domain`);
