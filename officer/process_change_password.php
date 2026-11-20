<?php
// Handles officer password change with CSRF protection and validation
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('officer/change_password.php'));
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token.');
    header('Location: ' . url('officer/change_password.php'));
    exit;
}

$current = substr((string)($_POST['current_password'] ?? ''), 0, 1024);
$new     = substr((string)($_POST['new_password'] ?? ''), 0, 1024);
$confirm = substr((string)($_POST['confirm_password'] ?? ''), 0, 1024);

if (empty($current) || empty($new) || empty($confirm)) {
    set_flash('warning', 'Please complete all password fields.');
    header('Location: ' . url('officer/change_password.php'));
    exit;
}

if ($new !== $confirm) {
    set_flash('danger', 'New password and confirmation password do not match.');
    header('Location: ' . url('officer/change_password.php'));
    exit;
}

if (strlen($new) < 8) {
    set_flash('danger', 'New password must be at least 8 characters long.');
    header('Location: ' . url('officer/change_password.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT password FROM ict_officers WHERE officer_id = ? AND role = 'officer'");
    $stmt->execute([$_SESSION['officer_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($current, $user['password'])) {
        set_flash('danger', 'Your current password was incorrect.');
        header('Location: ' . url('officer/change_password.php'));
        exit;
    }

    $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare("UPDATE ict_officers SET password = ? WHERE officer_id = ?");
    $update->execute([$hash, $_SESSION['officer_id']]);

    log_audit_event('officer.password_changed', 'security', 'Officer self-changed account password', 'officer', $_SESSION['officer_id']);

    // Regenerate session ID & refresh CSRF token on sensitive credential change
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    generate_csrf_token();

    set_flash('success', 'Your password has been updated successfully!');
    header('Location: ' . url('officer/change_password.php'));
    exit;

} catch (Exception $e) {
    error_log('Officer Change Password Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating your password.');
    header('Location: ' . url('officer/change_password.php'));
    exit;
}
