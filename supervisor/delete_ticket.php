<?php
// Supervisor Ticket Deletion Handler with CSRF protection
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);

if (!$ticketId) {
    set_flash('danger', 'Invalid ticket identifier.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ticket_code FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        set_flash('danger', 'Ticket not found.');
        header('Location: ' . url('supervisor/dashboard.php'));
        exit;
    }

    $delete = $pdo->prepare("DELETE FROM tickets WHERE ticket_id = ?");
    $delete->execute([$ticketId]);

    log_audit_event('ticket.deleted', 'ticket', 'Permanently deleted ticket ' . $ticket['ticket_code'], 'ticket', (string)$ticketId);

    set_flash('success', 'Ticket ' . $ticket['ticket_code'] . ' has been permanently deleted.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;

} catch (Exception $e) {
    error_log('Delete Ticket Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while deleting the ticket.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}
