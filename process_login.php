<?php
// Central Authentication Processor with Role-Based Redirection & Security Controls
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('login.php'));
    exit;
}

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

// Hard-cap input lengths to prevent DoS via oversized strings
$email    = substr($email, 0, 254);
$password = substr($password, 0, 1024);

// 1. Rate Limiting Check (by IP and targeted Account)
$rateLimit = check_rate_limit('login', $email);
if (!$rateLimit['allowed']) {
    log_audit_event('auth.account_locked', 'security', 'Login attempt blocked by rate limit lockout for: ' . $email, 'officer', $email, null, 'Anonymous', 'public');
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
            log_audit_event('auth.deactivated_attempt', 'security', 'Deactivated account attempted sign-in: ' . $email, 'officer', (string)$user['officer_id'], (int)$user['officer_id'], $user['full_name'], $user['role']);
            set_flash('danger', 'Your account has been deactivated. Please contact your ICT Supervisor.');
            header('Location: ' . url('login.php'));
            exit;
        }

        // Authentication Successful: Reset rate limit for both IP and user
        clear_rate_limit('login', $email);

        // Regenerate session ID and CSRF token to prevent Session Fixation & pre-session token reuse
        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        generate_csrf_token();

        // Set Session Authentication Context
        $_SESSION['officer_id']    = (int)$user['officer_id'];
        $_SESSION['officer_name']  = $user['full_name'];
        $_SESSION['officer_email'] = $user['email'];
        $_SESSION['officer_role']  = $user['role']; // 'officer' or 'supervisor'
        $_SESSION['last_activity'] = time();

        // Record successful sign-in in system audit log
        log_audit_event('auth.login_success', 'auth', 'Signed in successfully as ' . ucfirst($user['role']), 'officer', (string)$user['officer_id'], (int)$user['officer_id'], $user['full_name'], $user['role']);

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

    // Failed login attempt: Record against IP and specific targeted email
    record_failed_attempt('login', $email);
    log_audit_event('auth.login_failed', 'auth', 'Failed sign-in attempt for email: ' . $email, 'officer', $email, null, 'Anonymous', 'public');
    set_flash('danger', 'Invalid email address or password. Please check your credentials.');
    header('Location: ' . url('login.php'));
    exit;

} catch (Exception $e) {
    error_log('Login Process Error: ' . $e->getMessage());
    set_flash('danger', 'An unexpected error occurred during authentication. Please try again.');
    header('Location: ' . url('login.php'));
    exit;
}
