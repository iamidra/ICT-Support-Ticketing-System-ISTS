<?php
// Supervisor Session Logout handler
require_once __DIR__ . '/../includes/db_connect.php';

if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
    session_regenerate_id(true);
}

// Log audit event before clearing session
if (isset($_SESSION['officer_id'])) {
    log_audit_event('auth.logout', 'auth', 'Supervisor signed out of portal.', 'officer', (string)$_SESSION['officer_id'], (int)$_SESSION['officer_id'], $_SESSION['officer_name'] ?? 'Supervisor', 'supervisor');
}

unset($_SESSION['csrf_token']);
generate_csrf_token();

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();

// Restart session for flash message
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    session_start();
    session_regenerate_id(true);
}

unset($_SESSION['csrf_token']);
generate_csrf_token();

// Prevent browser from serving cached authenticated pages after logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

set_flash('info', 'You have been safely signed out of the supervisor portal.');
header('Location: ' . url('login.php'));
exit;
