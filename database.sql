-- ================================================
-- Minecraft CMD — MySQL setup
-- Import this in phpMyAdmin. Database: minecraft_cmd
--
-- This file is optional. If MySQL is not reachable, the app
-- creates the same tables automatically in a local SQLite file
-- at data/minecraft_cmd.sqlite, so nothing breaks either way.
-- db.php also adds any missing columns on connect, so an older
-- database is upgraded in place rather than needing a re-import.
-- ================================================

CREATE DATABASE IF NOT EXISTS `minecraft_cmd`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `minecraft_cmd`;

-- ------------------------------------------------
-- Every command that was generated and saved
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `command_history` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `command`    TEXT          NOT NULL,
  `tab`        VARCHAR(30)   NOT NULL DEFAULT 'give',
  `mc_version` VARCHAR(20)   DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (`username`),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- The command library — saved commands with metadata
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `favourites` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `command`    TEXT          NOT NULL,
  `tab`        VARCHAR(30)   NOT NULL DEFAULT 'give',
  `note`       VARCHAR(255)  DEFAULT NULL,
  `name`       VARCHAR(120)  DEFAULT NULL,
  `category`   VARCHAR(40)   DEFAULT NULL,
  `mc_version` VARCHAR(20)   DEFAULT NULL,
  `tags`       VARCHAR(255)  DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (`username`),
  INDEX idx_category (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Kits saved from the Kit Builder
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_kits` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `kit_name`   VARCHAR(100)  NOT NULL,
  `kit_data`   JSON          NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_kit (`username`, `kit_name`),
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Command sequences and other saved presets
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_presets` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`    VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `preset_name` VARCHAR(100)  NOT NULL,
  `preset_type` VARCHAR(30)   NOT NULL DEFAULT 'sequence',
  `preset_data` JSON          NOT NULL,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_preset (`username`, `preset_name`),
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Block palettes saved from the Palette Builder
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_palettes` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`     VARCHAR(50)  NOT NULL DEFAULT 'nizkbiits',
  `palette_name` VARCHAR(100) NOT NULL,
  `style`        VARCHAR(40)  DEFAULT NULL,
  `palette_data` JSON         NOT NULL,
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_palette (`username`, `palette_name`),
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'Database setup complete!' AS status;
