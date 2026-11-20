-- ICT Support Ticketing Solution (ISTS) — Database Migration Script (v2.0 / v2.1 -> v2.2.0)
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

-- 2. Add email, due_date, feedback, and timestamp columns to tickets
SET @exist_email := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'email');
SET @sql_email := IF(@exist_email = 0, 'ALTER TABLE tickets ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER full_name', 'SELECT 1');
PREPARE stmt FROM @sql_email;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_due_date := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'due_date');
SET @sql_due_date := IF(@exist_due_date = 0, 'ALTER TABLE tickets ADD COLUMN due_date DATETIME DEFAULT NULL AFTER priority', 'SELECT 1');
PREPARE stmt FROM @sql_due_date;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_rating := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'feedback_rating');
SET @sql_rating := IF(@exist_rating = 0, 'ALTER TABLE tickets ADD COLUMN feedback_rating TINYINT DEFAULT NULL AFTER updated_at', 'SELECT 1');
PREPARE stmt FROM @sql_rating;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_comments := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'feedback_comments');
SET @sql_comments := IF(@exist_comments = 0, 'ALTER TABLE tickets ADD COLUMN feedback_comments TEXT DEFAULT NULL AFTER feedback_rating', 'SELECT 1');
PREPARE stmt FROM @sql_comments;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_resolved_at := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'resolved_at');
SET @sql_resolved_at := IF(@exist_resolved_at = 0, 'ALTER TABLE tickets ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER feedback_comments', 'SELECT 1');
PREPARE stmt FROM @sql_resolved_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist_closed_at := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'tickets' AND column_name = 'closed_at');
SET @sql_closed_at := IF(@exist_closed_at = 0, 'ALTER TABLE tickets ADD COLUMN closed_at DATETIME DEFAULT NULL AFTER resolved_at', 'SELECT 1');
PREPARE stmt FROM @sql_closed_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Add is_internal to resolutions table
SET @exist_internal := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'resolutions' AND column_name = 'is_internal');
SET @sql_internal := IF(@exist_internal = 0, 'ALTER TABLE resolutions ADD COLUMN is_internal TINYINT(1) NOT NULL DEFAULT 0 AFTER resolution_text', 'SELECT 1');
PREPARE stmt FROM @sql_internal;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Create attachments table
CREATE TABLE IF NOT EXISTS attachments (
    attachment_id     INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id         INT NOT NULL,
    resolution_id     INT DEFAULT NULL,
    uploaded_by_type  ENUM('public', 'officer', 'supervisor') NOT NULL DEFAULT 'public',
    uploader_id       INT DEFAULT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename   VARCHAR(255) NOT NULL,
    file_size         INT NOT NULL,
    mime_type         VARCHAR(100) NOT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_attach_ticket (ticket_id),
    INDEX idx_attach_resolution (resolution_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Create ticket_history table
CREATE TABLE IF NOT EXISTS ticket_history (
    history_id  INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id   INT NOT NULL,
    actor_name  VARCHAR(100) NOT NULL,
    actor_role  ENUM('public', 'officer', 'supervisor', 'system') NOT NULL,
    action      VARCHAR(50) NOT NULL,
    details     TEXT DEFAULT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_hist_ticket (ticket_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Create rate_limits table
CREATE TABLE IF NOT EXISTS rate_limits (
    rate_key      VARCHAR(64) PRIMARY KEY,
    action        VARCHAR(32) NOT NULL,
    identifier    VARCHAR(150) NOT NULL,
    ip_address    VARCHAR(45) NOT NULL,
    attempts      INT NOT NULL DEFAULT 1,
    lockout_until DATETIME DEFAULT NULL,
    last_attempt  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rate_ip (ip_address),
    INDEX idx_rate_ident (identifier),
    INDEX idx_rate_lockout (lockout_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. High-Performance Composite Indexes
CREATE INDEX IF NOT EXISTS idx_officer_status ON tickets (assigned_officer_id, status);
CREATE INDEX IF NOT EXISTS idx_status_created ON tickets (status, created_at);
CREATE INDEX IF NOT EXISTS idx_priority_status ON tickets (priority, status);
CREATE INDEX IF NOT EXISTS idx_ticket_internal ON resolutions (ticket_id, is_internal);
CREATE INDEX IF NOT EXISTS idx_att_ticket_res ON attachments (ticket_id, resolution_id);

-- 8. Create System Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id       BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_id     INT DEFAULT NULL,
    actor_name   VARCHAR(100) NOT NULL,
    actor_role   ENUM('supervisor', 'officer', 'public', 'system') NOT NULL DEFAULT 'public',
    action       VARCHAR(60) NOT NULL,
    category     VARCHAR(30) NOT NULL DEFAULT 'system',
    target_type  VARCHAR(40) DEFAULT NULL,
    target_id    VARCHAR(60) DEFAULT NULL,
    details      TEXT DEFAULT NULL,
    ip_address   VARCHAR(45) NOT NULL,
    user_agent   VARCHAR(255) DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_category (category),
    INDEX idx_audit_action (action),
    INDEX idx_audit_actor (actor_id, actor_role),
    INDEX idx_audit_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
