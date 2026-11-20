<?php
/**
 * Core System Configuration
 * ICT Support Ticketing Solution (ISTS)
 */

// Load .env file if present in project root
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!isset($_SERVER[$key]) && !isset($_ENV[$key])) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

/**
 * Helper to get environment setting with fallback
 */
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// Application Environment: 'production' or 'development'
$appEnv = env('APP_ENV', null);
if ($appEnv === null) {
    $hostWithPort = $_SERVER['HTTP_HOST'] ?? '';
    $host = explode(':', $hostWithPort)[0];
    $appEnv = ($host === 'localhost' || $host === '127.0.0.1' || $host === '::1' || empty($host)) ? 'development' : 'production';
}
define('APP_ENV', $appEnv);

// Configure PHP Error Reporting based on Environment
if (APP_ENV === 'production') {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// HTTP Security Headers
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 0'); // Modern browsers ignore this; CSP is the proper control
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()');
    // Content Security Policy — allows inline scripts/styles needed by the application
    // 'unsafe-inline' is required because PHP templates embed inline <script> blocks and onclick handlers
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; frame-ancestors 'none';");

    $isHttps = (
        (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Session Configuration Settings (Default: 15 minutes idle timeout)
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 150));

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', (string)SESSION_LIFETIME);     // Shortened server-side GC timeout
    ini_set('session.cookie_lifetime', '0');        // Cookie expires when browser closes
    ini_set('session.cache_limiter', 'nocache');    // Prevent caching of authenticated pages

    $isHttps = (
        (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
        (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    );
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

// Database Credentials
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'ists_db'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));

// Application Details
define('APP_NAME', env('APP_NAME', 'ICT Support Ticketing Solution'));
define('APP_SHORT_NAME', env('APP_SHORT_NAME', 'ISTS'));
define('APP_VERSION', env('APP_VERSION', '2.2.0'));
define('MAX_LOGIN_ATTEMPTS', (int)env('MAX_LOGIN_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_MINUTES', (int)env('LOGIN_LOCKOUT_MINUTES', 15));

// Dynamic Base URL calculation (compatible with Apache, Nginx, subfolders & virtual hosts)
if (!defined('BASE_URL')) {
    $configuredBase = env('BASE_URL', null);
    if ($configuredBase !== null && $configuredBase !== '') {
        define('BASE_URL', rtrim($configuredBase, '/'));
    } else {
        $calculatedBase = '';
        $projectRoot    = str_replace('\\', '/', rtrim(realpath(__DIR__ . '/..') ?: dirname(__DIR__), '/\\'));
        $scriptFilename = str_replace('\\', '/', realpath($_SERVER['SCRIPT_FILENAME'] ?? '') ?: ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $scriptName     = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

        // 1. Primary method: compare executed script filename with web URL script name
        if (!empty($scriptFilename) && !empty($scriptName) && stripos($scriptFilename, $projectRoot) === 0) {
            $subPath = substr($scriptFilename, strlen($projectRoot));
            if (!empty($subPath) && str_ends_with($scriptName, $subPath)) {
                $calculatedBase = substr($scriptName, 0, strlen($scriptName) - strlen($subPath));
            }
        }

        // 2. Secondary fallback: DOCUMENT_ROOT comparison
        if ($calculatedBase === '' && !empty($_SERVER['DOCUMENT_ROOT'])) {
            $docRootRaw = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']);
            $docRoot = rtrim($docRootRaw, '/');
            if (stripos($projectRoot, $docRoot) === 0) {
                $calculatedBase = substr($projectRoot, strlen($docRoot));
            }
        }

        // 3. Subfolder auto-recovery for standard XAMPP ists installation
        if ($calculatedBase === '' && !empty($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/ists/')) {
            $calculatedBase = '/ists';
        }

        $cleanBase = '/' . trim($calculatedBase, '/');
        if ($cleanBase === '/') {
            $cleanBase = '';
        }

        define('BASE_URL', $cleanBase);
    }
}

// Default Timezone
$appTz = env('APP_TIMEZONE', 'UTC');
date_default_timezone_set($appTz);
