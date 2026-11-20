<?php
// Centralized Database Connection File
require_once __DIR__ . '/functions.php';

try {
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=%s", DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // High-performance schema sync: gated by persistent cache marker so zero DDL queries run on normal page loads
    $cacheDir = __DIR__ . '/../sys_cache';
    $cacheFile = $cacheDir . '/.schema_synced';
    if (!file_exists($cacheFile)) {
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        try {
            $officerCols = $pdo->query("SHOW COLUMNS FROM ict_officers")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($officerCols)) {
                if (!in_array('status', $officerCols, true)) {
                    $pdo->exec("ALTER TABLE ict_officers ADD COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' AFTER department");
                }
                if (!in_array('last_login', $officerCols, true)) {
                    $pdo->exec("ALTER TABLE ict_officers ADD COLUMN last_login DATETIME DEFAULT NULL AFTER status");
                }
            }

            $ticketCols = $pdo->query("SHOW COLUMNS FROM tickets")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($ticketCols)) {
                if (!in_array('email', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER full_name");
                }
                if (!in_array('due_date', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN due_date DATETIME DEFAULT NULL AFTER priority");
                }
                if (!in_array('feedback_rating', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN feedback_rating TINYINT DEFAULT NULL AFTER updated_at");
                }
                if (!in_array('feedback_comments', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN feedback_comments TEXT DEFAULT NULL AFTER feedback_rating");
                }
                if (!in_array('resolved_at', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN resolved_at DATETIME DEFAULT NULL AFTER feedback_comments");
                }
                if (!in_array('closed_at', $ticketCols, true)) {
                    $pdo->exec("ALTER TABLE tickets ADD COLUMN closed_at DATETIME DEFAULT NULL AFTER resolved_at");
                }
            }

            $resCols = $pdo->query("SHOW COLUMNS FROM resolutions")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($resCols) && !in_array('is_internal', $resCols, true)) {
                $pdo->exec("ALTER TABLE resolutions ADD COLUMN is_internal TINYINT(1) NOT NULL DEFAULT 0 AFTER resolution_text");
            }

            @file_put_contents($cacheFile, date('Y-m-d H:i:s'));
        } catch (Throwable $se) {
            // Silently continue if DDL is restricted; queries have code-level fallbacks
            error_log("Schema auto-sync notice: " . $se->getMessage());
        }
    }
} catch (PDOException $e) {
    error_log("ISTS Database Connection Failure: " . $e->getMessage());

    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database connection failed. Please check your DB credentials in config/config.php or .env\n");
        exit(1);
    }

    http_response_code(500);

    // If development environment, show message for quick debugging; otherwise show friendly production error
    if (defined('APP_ENV') && APP_ENV === 'development') {
        $devMsg = e($e->getMessage());
    } else {
        $devMsg = "The support desk is temporarily unable to connect to the database service. Please contact your system administrator.";
    }

    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Unavailable | ' . e(APP_SHORT_NAME) . '</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #334155; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1.5rem; }
        .box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; max-width: 520px; width: 100%; padding: 2.5rem; text-align: center; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
        .icon { font-size: 3rem; margin-bottom: 1rem; color: #ef4444; }
        h1 { font-size: 1.4rem; color: #0f172a; margin: 0 0 0.75rem 0; font-weight: 700; }
        p { font-size: 0.95rem; line-height: 1.6; color: #64748b; margin: 0 0 1.5rem 0; }
        .btn { display: inline-block; background: #2563eb; color: #fff; padding: 0.65rem 1.4rem; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">' . icon('alert', '', 48) . '</div>
        <h1>Service Temporarily Unavailable</h1>
        <p>' . $devMsg . '</p>
        <a href="javascript:location.reload();" class="btn">Retry Connection</a>
    </div>
</body>
</html>';
    exit;
}
