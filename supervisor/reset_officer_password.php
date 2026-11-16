<?php
// Supervisor resets an officer's account password with CSRF protection
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

$officerId = (int)($_POST['officer_id'] ?? 0);
$newPass   = substr((string)($_POST['new_password'] ?? ''), 0, 1024);

if (!$officerId || strlen($newPass) < 8) {
    set_flash('danger', 'Password must be at least 8 characters long.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT full_name FROM ict_officers WHERE officer_id = ?");
    $stmt->execute([$officerId]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        set_flash('danger', 'Staff member not found.');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    $update = $pdo->prepare("UPDATE ict_officers SET password = ? WHERE officer_id = ?");
    $update->execute([$hash, $officerId]);

    set_flash('success', 'Password reset successfully for ' . $officer['full_name'] . '.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;

} catch (Exception $e) {
    error_log('Reset Officer Password Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while resetting the password.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}
