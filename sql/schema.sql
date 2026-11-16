-- ICT Support Ticketing Solution (ISTS) — Enhanced Production Database Schema
-- Version: 2.1.0
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
    ticket_code         VARCHAR(20) UNIQUE,     -- Format: ISTS-000123
    full_name           VARCHAR(100) NOT NULL,
    email               VARCHAR(150) DEFAULT NULL,
    office              VARCHAR(100) NOT NULL,
    phone               VARCHAR(30) NOT NULL,
    category            VARCHAR(100) NOT NULL,
    subject             VARCHAR(150) NOT NULL,
    description         TEXT NOT NULL,
    priority            ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status              ENUM('Pending', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Pending',
    assigned_officer_id INT DEFAULT NULL,
    created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_assigned (assigned_officer_id),
    INDEX idx_ticket_code (ticket_code),
    INDEX idx_created_at (created_at),
    INDEX idx_category (category),
    FOREIGN KEY (assigned_officer_id) REFERENCES ict_officers(officer_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Resolution Notes Table
CREATE TABLE IF NOT EXISTS resolutions (
    resolution_id   INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id       INT NOT NULL,
    officer_id      INT NOT NULL,
    resolution_text TEXT NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_resolution_ticket (ticket_id),
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (officer_id) REFERENCES ict_officers(officer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
