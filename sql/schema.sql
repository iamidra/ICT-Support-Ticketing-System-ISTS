-- ICT Support Ticketing Solution (ISTS) — Enhanced Production Database Schema
-- Version: 2.2.0
-- Import via phpMyAdmin or MySQL CLI: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS ists_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ists_db;

-- 1. ICT Officers & Supervisors Table
CREATE TABLE IF NOT EXISTS ict_officers (
    officer_id   INT AUTO_INCREMENT PRIMARY KEY,
    full_name    VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    password     VARCHAR(255) NOT NULL,      -- Stores password_hash() output
    role         ENUM('officer', 'supervisor') NOT NULL DEFAULT 'officer',
    department   VARCHAR(100) DEFAULT NULL,
    status       ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login   DATETIME DEFAULT NULL,
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_officer_role (role),
    INDEX idx_officer_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default categories if empty
INSERT IGNORE INTO categories (category_id, category_name, description) VALUES
(1, 'Hardware Support', 'Computers, monitors, peripherals, mouse, keyboard, and physical equipment'),
(2, 'Software & Operating System', 'Application installation, OS errors, license issues, and software crashes'),
(3, 'Network & Internet', 'Wi-Fi, Ethernet, LAN access, VPN, and internet connection issues'),
(4, 'Printer & Scanner', 'Printer queues, paper jams, toner replacement, and network scanner setup'),
(5, 'User Account & Access', 'Password resets, email account configuration, and portal access permissions'),
(6, 'Other Technical Issue', 'General technical inquiries and unlisted troubleshooting requests');

-- 3. Support Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    ticket_id           INT AUTO_INCREMENT PRIMARY KEY,
    ticket_code         VARCHAR(20) UNIQUE,     -- Format: ISTS-8X7D-2041 (CSPRNG unguessable code)
    full_name           VARCHAR(100) NOT NULL,
    email               VARCHAR(150) DEFAULT NULL,
    office              VARCHAR(100) NOT NULL,
    phone               VARCHAR(30) NOT NULL,
    category            VARCHAR(100) NOT NULL,
    subject             VARCHAR(150) NOT NULL,
    description         TEXT NOT NULL,
    priority            ENUM('low', 'medium', 'high') DEFAULT 'medium',
    due_date            DATETIME DEFAULT NULL,
    status              ENUM('Pending', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Pending',
    assigned_officer_id INT DEFAULT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at         DATETIME DEFAULT NULL,
    closed_at           DATETIME DEFAULT NULL,
    feedback_rating     TINYINT DEFAULT NULL,
    feedback_comments   TEXT DEFAULT NULL,
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_officer_id),
    INDEX idx_ticket_code (ticket_code),
    INDEX idx_created_at (created_at),
    INDEX idx_category (category),
    INDEX idx_due_date (due_date),
    INDEX idx_officer_status (assigned_officer_id, status),
    INDEX idx_status_created (status, created_at),
    INDEX idx_priority_status (priority, status),
    FOREIGN KEY (assigned_officer_id) REFERENCES ict_officers(officer_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Resolution Notes Table
CREATE TABLE IF NOT EXISTS resolutions (
    resolution_id   INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id       INT NOT NULL,
    officer_id      INT NOT NULL,
    resolution_text TEXT NOT NULL,
    is_internal     TINYINT(1) NOT NULL DEFAULT 0,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resolution_ticket (ticket_id),
    INDEX idx_ticket_internal (ticket_id, is_internal),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES ict_officers(officer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Attachments Table
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

-- 6. Ticket Activity & Audit History Table
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

-- 7. Persistent Rate Limiting Table (Brute-Force & Credential Stuffing Defense)
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

-- 8. Comprehensive System Audit Logs Table
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
