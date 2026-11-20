<?php
// Supervisor Edits Staff/Officer Account Profile with CSRF protection
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

$officerId  = (int)($_POST['officer_id'] ?? 0);
$fullName   = substr(trim($_POST['full_name'] ?? ''), 0, 100);
$email      = strtolower(substr(trim($_POST['email'] ?? ''), 0, 150));
$role       = trim($_POST['role'] ?? 'officer');
$department = substr(trim($_POST['department'] ?? ''), 0, 100);
$status     = trim($_POST['status'] ?? 'active');

if (!$officerId || empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please provide a valid full name and email address.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

if (!in_array($role, ['officer', 'supervisor'], true)) {
    $role = 'officer';
}

if (!in_array($status, ['active', 'inactive'], true)) {
    $status = 'active';
}

// Check if officer exists
try {
    $stmt = $pdo->prepare("SELECT * FROM ict_officers WHERE officer_id = ?");
    $stmt->execute([$officerId]);
    $officer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$officer) {
        set_flash('danger', 'Staff member not found.');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    // Prevent self-demotion or self-deactivation
    $isSelf = ($officerId === (int)$_SESSION['officer_id']);
    if ($isSelf) {
        if ($role !== 'supervisor') {
            set_flash('danger', 'You cannot change your own role away from Supervisor.');
            header('Location: ' . url('supervisor/manage_officers.php'));
            exit;
        }
        if ($status !== 'active') {
            set_flash('danger', 'You cannot deactivate your own active account.');
            header('Location: ' . url('supervisor/manage_officers.php'));
            exit;
        }
    }

    // Check email uniqueness (excluding current officer)
    $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM ict_officers WHERE LOWER(email) = ? AND officer_id != ?");
    $emailCheck->execute([$email, $officerId]);
    if ($emailCheck->fetchColumn() > 0) {
        set_flash('danger', 'Another staff account is already using the email address "' . e($email) . '".');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    // Update officer profile
    $update = $pdo->prepare(
        "UPDATE ict_officers SET full_name = ?, email = ?, role = ?, department = ?, status = ? WHERE officer_id = ?"
    );
    $update->execute([$fullName, $email, $role, $department, $status, $officerId]);

    log_audit_event('officer.updated', 'officer', 'Updated profile for ' . $fullName . ' (' . $email . ', role: ' . $role . ', status: ' . $status . ')', 'officer', $officerId);

    // If updating own profile, update session name
    if ($isSelf) {
        $_SESSION['officer_name'] = $fullName;
        $_SESSION['officer_email'] = $email;
    }

    set_flash('success', 'Profile for "' . $fullName . '" was updated successfully.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;

} catch (Exception $e) {
    error_log('Edit Officer Profile Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating the profile.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}
