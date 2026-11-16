<?php
// Central Authentication Processor with Role-Based Redirection & Security Controls
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('login.php'));
    exit;
}

// 1. Rate Limiting Check
$rateLimit = check_rate_limit('login');
if (!$rateLimit['allowed']) {
    set_flash('danger', 'Account sign-in is temporarily locked due to excessive failed attempts. Please retry in ' . (int)$rateLimit['remaining_minutes'] . ' minute(s).');
    header('Location: ' . url('login.php'));
    exit;
}

// 2. Verify CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Security validation failed (invalid CSRF token). Please try signing in again.');
    header('Location: ' . url('login.php'));
    exit;
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

// Hard-cap input lengths to prevent DoS via oversized strings
$email    = substr($email, 0, 254);
$password = substr($password, 0, 1024);

if (empty($email) || empty($password)) {
    set_flash('danger', 'Please provide both your registered email address and password.');
    header('Location: ' . url('login.php'));
    exit;
}

// 3. Query account by email
try {
    $stmt = $pdo->prepare("SELECT * FROM ict_officers WHERE LOWER(email) = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Check if account has been deactivated
        if (isset($user['status']) && $user['status'] === 'inactive') {
            set_flash('danger', 'Your account has been deactivated. Please contact your ICT Supervisor.');
            header('Location: ' . url('login.php'));
            exit;
        }

        // Authentication Successful: Reset rate limit
        clear_rate_limit('login');

        // Regenerate session ID to prevent Session Fixation
        session_regenerate_id(true);

        // Set Session Authentication Context
        $_SESSION['officer_id']    = (int)$user['officer_id'];
        $_SESSION['officer_name']  = $user['full_name'];
        $_SESSION['officer_email'] = $user['email'];
        $_SESSION['officer_role']  = $user['role']; // 'officer' or 'supervisor'
        $_SESSION['last_activity'] = time();

        // Update last login timestamp in background
        try {
            $updateLogin = $pdo->prepare("UPDATE ict_officers SET last_login = NOW() WHERE officer_id = ?");
            $updateLogin->execute([$user['officer_id']]);
        } catch (Exception $e) {
            // Ignore if column not yet added
        }

        set_flash('success', 'Welcome back, ' . $user['full_name'] . '!');

        // Role-based Access Control Redirection
        if ($user['role'] === 'supervisor') {
            session_write_close();
            header('Location: ' . url('supervisor/dashboard.php'));
            exit;
        } else {
            session_write_close();
            header('Location: ' . url('officer/dashboard.php'));
            exit;
        }
    }

    // Failed login attempt
    record_failed_attempt('login');
    set_flash('danger', 'Invalid email address or password. Please check your credentials.');
    header('Location: ' . url('login.php'));
    exit;

} catch (Exception $e) {
    error_log('Login Process Error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred during authentication. Please try again.');
    header('Location: ' . url('login.php'));
    exit;
}
