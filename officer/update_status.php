<?php
// Updates ticket status with CSRF protection and officer ACL check
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

// CSRF validation
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token.');
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$status   = trim($_POST['status'] ?? '');

$allowedStatuses = ['Pending', 'In Progress', 'Resolved', 'Closed'];
if (!$ticketId || !in_array($status, $allowedStatuses, true)) {
    set_flash('danger', 'Invalid status update parameters.');
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ? AND assigned_officer_id = ?");
    $stmt->execute([$status, $ticketId, $_SESSION['officer_id']]);

    if ($stmt->rowCount() === 0) {
        set_flash('danger', 'Access Denied: Ticket is not assigned to your account.');
        header('Location: ' . url('officer/dashboard.php'));
        exit;
    }

    set_flash('success', 'Ticket status updated to "' . $status . '".');
    header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
    exit;

} catch (Exception $e) {
    error_log('Officer Update Status Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating ticket status.');
    header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
    exit;
}
