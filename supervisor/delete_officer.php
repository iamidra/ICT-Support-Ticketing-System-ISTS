<?php
// Permanently deletes a staff/officer account — supervisor only, CSRF protected
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token. Please try again.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

$officerId = (int)($_POST['officer_id'] ?? 0);

if (!$officerId) {
    set_flash('danger', 'Invalid officer identifier.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

// Prevent supervisor from deleting their own account
if ($officerId === (int)$_SESSION['officer_id']) {
    set_flash('danger', 'You cannot delete your own active supervisor account.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

try {
    // Fetch officer to confirm existence
    $stmt = $pdo->prepare("SELECT full_name, role FROM ict_officers WHERE officer_id = ?");
    $stmt->execute([$officerId]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        set_flash('danger', 'Staff account not found.');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    // Nullify any ticket assignments so ticket records are preserved
    $nullify = $pdo->prepare("UPDATE tickets SET assigned_officer_id = NULL WHERE assigned_officer_id = ?");
    $nullify->execute([$officerId]);

    // Delete the officer account
    $delete = $pdo->prepare("DELETE FROM ict_officers WHERE officer_id = ?");
    $delete->execute([$officerId]);

    set_flash('success', 'Account for "' . e($officer['full_name']) . '" has been permanently deleted. Associated ticket assignments were cleared.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;

} catch (Exception $e) {
    error_log('Delete Officer Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while deleting the account. Please try again.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}
