<?php
// Creates a new officer/supervisor account with CSRF validation
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

$fullName   = substr(trim($_POST['full_name'] ?? ''), 0, 100);
$email      = strtolower(substr(trim($_POST['email'] ?? ''), 0, 150));
$password   = (string)($_POST['password'] ?? '');
$role       = trim($_POST['role'] ?? 'officer');
$department = substr(trim($_POST['department'] ?? ''), 0, 100);

if (empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    set_flash('danger', 'Please provide a valid full name and email address.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

if (strlen($password) < 8) {
    set_flash('danger', 'Password must be at least 8 characters long.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}

if (!in_array($role, ['officer', 'supervisor'], true)) {
    $role = 'officer';
}

// Check if email already registered
try {
    $check = $pdo->prepare("SELECT COUNT(*) FROM ict_officers WHERE LOWER(email) = ?");
    $check->execute([$email]);
    if ($check->fetchColumn() > 0) {
        set_flash('danger', 'An account with email "' . e($email) . '" already exists.');
        header('Location: ' . url('supervisor/manage_officers.php'));
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO ict_officers (full_name, email, password, role, department, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())"
    );
    $stmt->execute([$fullName, $email, $hash, $role, $department]);

    set_flash('success', 'New account created successfully for ' . $fullName . '.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;

} catch (Exception $e) {
    error_log('Add Officer Error: ' . $e->getMessage());
    set_flash('danger', 'Failed to create staff account. Please verify input data and try again.');
    header('Location: ' . url('supervisor/manage_officers.php'));
    exit;
}
