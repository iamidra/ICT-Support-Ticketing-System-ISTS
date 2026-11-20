<?php
// Toggles officer account active/inactive status with CSRF protection
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

if (!$officerId) {
    set_flash('danger', 'Invalid officer identifier.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

// Prevent supervisor from deactivating their own account
if ($officerId === (int)$_SESSION['officer_id']) {
    set_flash('danger', 'You cannot deactivate your own active supervisor account.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT full_name, status, role FROM ict_officers WHERE officer_id = ?");
    $stmt->execute([$officerId]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        set_flash('danger', 'Staff member not found.');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    $currentStatus = $officer['status'] ?? 'active';
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';

    $update = $pdo->prepare("UPDATE ict_officers SET status = ? WHERE officer_id = ?");
    $update->execute([$newStatus, $officerId]);

    $label = ($newStatus === 'active') ? 'activated' : 'deactivated';
    log_audit_event('officer.status_toggled', 'officer', 'Changed account status for ' . $officer['full_name'] . ' from ' . $currentStatus . ' to ' . $newStatus, 'officer', $officerId);
    set_flash('success', 'Account for "' . $officer['full_name'] . '" has been ' . $label . '.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;

} catch (Exception $e) {
    error_log('Toggle Officer Status Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating officer status.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}
