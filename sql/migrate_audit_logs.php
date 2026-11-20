<?php
require_once __DIR__ . '/../includes/db_connect.php';

echo "=== ISTS System Audit Logs Migration ===\n\n";

$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    log_id        INT AUTO_INCREMENT PRIMARY KEY,
    actor_id      INT DEFAULT NULL,
    actor_name    VARCHAR(100) NOT NULL,
    actor_role    ENUM('supervisor', 'officer', 'public', 'system') NOT NULL DEFAULT 'system',
    action        VARCHAR(60) NOT NULL,
    category      VARCHAR(30) NOT NULL DEFAULT 'system',
    target_type   VARCHAR(40) DEFAULT NULL,
    target_id     VARCHAR(60) DEFAULT NULL,
    details       TEXT DEFAULT NULL,
    ip_address    VARCHAR(45) NOT NULL,
    user_agent    VARCHAR(255) DEFAULT NULL,
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_category (category),
    INDEX idx_audit_action (action),
    INDEX idx_audit_actor (actor_id, actor_role),
    INDEX idx_audit_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

try {
    $pdo->exec($sql);
    echo " [CREATED] Table audit_logs created successfully.\n";

    // Seed an initial system log entry if table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO audit_logs (actor_name, actor_role, action, category, details, ip_address, user_agent, created_at)
             VALUES ('System', 'system', 'system.audit_initialized', 'system', 'ISTS System Audit Trail initialized successfully.', '127.0.0.1', 'System Bootstrap', NOW())"
        );
        $stmt->execute();
        echo " [SEEDED] Initial audit trail event recorded.\n";
    }

    // Refresh schema sync marker
    $cacheDir = __DIR__ . '/../sys_cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    @file_put_contents($cacheDir . '/.schema_synced', date('Y-m-d H:i:s'));
    echo " [OK] Refreshed schema sync marker.\n";

} catch (Throwable $e) {
    echo " [ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
echo "\nAudit logs migration completed.\n";
