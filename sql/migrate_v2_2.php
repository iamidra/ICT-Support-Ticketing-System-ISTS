<?php
/**
 * Migration Script for ISTS v2.2.0 Schema Enhancements
 */
require_once __DIR__ . '/../includes/db_connect.php';

try {
    echo "Starting migration...\n";

    // 1. Create attachments table
    $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
        attachment_id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        resolution_id INT DEFAULT NULL,
        uploaded_by_type ENUM('public', 'officer', 'supervisor') NOT NULL DEFAULT 'public',
        uploader_id INT DEFAULT NULL,
        original_filename VARCHAR(255) NOT NULL,
        stored_filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        mime_type VARCHAR(100) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_attach_ticket (ticket_id),
        INDEX idx_attach_resolution (resolution_id),
        FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Attachments table ensured.\n";

    // 2. Add is_internal to resolutions table if missing
    $resCols = $pdo->query("SHOW COLUMNS FROM resolutions")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('is_internal', $resCols, true)) {
        $pdo->exec("ALTER TABLE resolutions ADD COLUMN is_internal TINYINT(1) NOT NULL DEFAULT 0 AFTER resolution_text");
        echo "Added is_internal column to resolutions.\n";
    }

    // 3. Add SLA, feedback, and resolution timestamps to tickets table if missing
    $tCols = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('due_date', $tCols, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN due_date DATETIME DEFAULT NULL AFTER priority");
        echo "Added due_date column to tickets.\n";
    }
    if (!in_array('feedback_rating', $tCols, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN feedback_rating TINYINT DEFAULT NULL AFTER updated_at");
        echo "Added feedback_rating column to tickets.\n";
    }
    if (!in_array('feedback_comments', $tCols, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN feedback_comments TEXT DEFAULT NULL AFTER feedback_rating");
        echo "Added feedback_comments column to tickets.\n";
    }
    if (!in_array('resolved_at', $tCols, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER feedback_comments");
        echo "Added resolved_at column to tickets.\n";
    }
    if (!in_array('closed_at', $tCols, true)) {
        $pdo->exec("ALTER TABLE tickets ADD COLUMN closed_at DATETIME DEFAULT NULL AFTER resolved_at");
        echo "Added closed_at column to tickets.\n";
    }

    // 4. Create ticket_history table
    $pdo->exec("CREATE TABLE IF NOT EXISTS ticket_history (
        history_id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        actor_name VARCHAR(100) NOT NULL,
        actor_role ENUM('public', 'officer', 'supervisor', 'system') NOT NULL,
        action VARCHAR(50) NOT NULL,
        details TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_hist_ticket (ticket_id),
        FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Ticket history table ensured.\n";

    // 5. Create persistent rate_limits table
    $pdo->exec("CREATE TABLE IF NOT EXISTS rate_limits (
        rate_key VARCHAR(64) PRIMARY KEY,
        action VARCHAR(32) NOT NULL,
        identifier VARCHAR(150) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        attempts INT NOT NULL DEFAULT 1,
        lockout_until DATETIME DEFAULT NULL,
        last_attempt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_rate_ip (ip_address),
        INDEX idx_rate_ident (identifier),
        INDEX idx_rate_lockout (lockout_until)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "Rate limits table ensured.\n";

    echo "Migration completed successfully!\n";

} catch (Throwable $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
