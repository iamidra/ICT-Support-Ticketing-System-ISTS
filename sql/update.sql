-- ICT Support Ticketing Solution (ISTS) — Database Migration Script (v2.0 -> v2.1.0)
-- Run this if you already have ists_db installed and want to upgrade safely.

USE ists_db;

-- 1. Add status and last_login columns to ict_officers if not exists
SET @exist_status := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'ict_officers' AND column_name = 'status');
SET @sql_status := IF(@exist_status = 0, 'ALTER TABLE ict_officers ADD COLUMN status ENUM(\'active\', \'inactive\') NOT NULL DEFAULT \'active\' AFTER department', 'SELECT 1');
PREPARE stmt FROM @sql_status;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_last_login := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'ict_officers' AND column_name = 'last_login');
SET @sql_last_login := IF(@exist_last_login = 0, 'ALTER TABLE ict_officers ADD COLUMN last_login DATETIME DEFAULT NULL AFTER status', 'SELECT 1');
PREPARE stmt FROM @sql_last_login;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Add email column to tickets if not exists
SET @exist_email := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'email');
SET @sql_email := IF(@exist_email = 0, 'ALTER TABLE tickets ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER full_name', 'SELECT 1');
PREPARE stmt FROM @sql_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add performance indexes if missing
SET @exist_idx_created := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tickets' AND index_name = 'idx_created_at');
SET @sql_idx_created := IF(@exist_idx_created = 0, 'ALTER TABLE tickets ADD INDEX idx_created_at (created_at)', 'SELECT 1');
PREPARE stmt FROM @sql_idx_created;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_idx_cat := (SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'tickets' AND index_name = 'idx_category');
SET @sql_idx_cat := IF(@exist_idx_cat = 0, 'ALTER TABLE tickets ADD INDEX idx_category (category)', 'SELECT 1');
PREPARE stmt FROM @sql_idx_cat;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
