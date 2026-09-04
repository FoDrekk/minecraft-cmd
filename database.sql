-- ================================================
-- Minecraft Command Generator — Database Setup
-- Import file ni dalam phpMyAdmin
-- Database: minecraft_cmd
-- ================================================

CREATE DATABASE IF NOT EXISTS `minecraft_cmd`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `minecraft_cmd`;

-- ------------------------------------------------
-- Table: command_history
-- Simpan semua command yang pernah generate
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `command_history` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `command`    TEXT          NOT NULL,
  `tab`        VARCHAR(30)   NOT NULL DEFAULT 'give',
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (`username`),
  INDEX idx_created (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Table: favourites
-- Simpan favourite commands (permanent)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `favourites` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `command`    TEXT          NOT NULL,
  `tab`        VARCHAR(30)   NOT NULL DEFAULT 'give',
  `note`       VARCHAR(255)  DEFAULT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Table: user_kits
-- Simpan kit presets yang user buat sendiri
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
-- Table: user_presets
-- Simpan command sequence / preset custom
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_presets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`   VARCHAR(50)   NOT NULL DEFAULT 'nizkbiits',
  `preset_name`VARCHAR(100)  NOT NULL,
  `preset_type`VARCHAR(30)   NOT NULL DEFAULT 'sequence',
  `preset_data`JSON          NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_preset (`username`, `preset_name`),
  INDEX idx_username (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------
-- Sample data (optional)
-- ------------------------------------------------
INSERT IGNORE INTO `favourites` (`username`, `command`, `tab`, `note`) VALUES
  ('nizkbiits', '/give nizkbiits netherite_sword 1', 'give', 'God sword'),
  ('nizkbiits', '/gamemode creative nizkbiits', 'gm', 'Switch to creative'),
  ('nizkbiits', '/effect give nizkbiits strength 300 1', 'effect', 'Strength II');

SELECT 'Database setup complete!' AS status;
