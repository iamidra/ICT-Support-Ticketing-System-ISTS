<?php
/**
 * Allows a requester to reopen a resolved ticket if the problem persists.
 */
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('track_status.php'));
    exit;
}

$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Security validation expired. Please try again.');
    header('Location: ' . url('track_status.php'));
    exit;
}

$ticketCode = trim($_POST['ticket_code'] ?? '');
$reason     = trim($_POST['reopen_reason'] ?? '');

$redirectUrl = url('track_status.php?ticket_id=' . urlencode($ticketCode));

if (empty($ticketCode) || empty($reason)) {
    set_flash('warning', 'Please provide a reason for reopening the ticket.');
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT t.*, o.full_name AS officer_name, o.email AS officer_email
         FROM tickets t
         LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
         WHERE UPPER(t.ticket_code) = UPPER(?)"
    );
    $stmt->execute([$ticketCode]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        set_flash('danger', 'Ticket not found.');
        header('Location: ' . url('track_status.php'));
        exit;
    }

    if ($ticket['status'] !== 'Resolved') {
        set_flash('warning', 'Only tickets in "Resolved" status can be reopened.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    $pdo->beginTransaction();

    // Reopen ticket back to In Progress
    $update = $pdo->prepare("UPDATE tickets SET status = 'In Progress', resolved_at = NULL WHERE ticket_id = ?");
    $update->execute([$ticket['ticket_id']]);

    // Log in ticket history
    log_ticket_activity(
        $ticket['ticket_id'],
        $ticket['full_name'],
        'public',
        'reopened',
        'Ticket reopened by requester: ' . substr($reason, 0, 500)
    );

    $pdo->commit();

    // Notify assigned officer if officer exists
    if (!empty($ticket['assigned_officer_id']) && !empty($ticket['officer_email'])) {
        require_once __DIR__ . '/includes/mailer.php';
        $subject = "Ticket Reopened by Requester: [" . $ticket['ticket_code'] . "] " . $ticket['subject'];
        $body = '
        <p>Hello <strong>' . e($ticket['officer_name']) . '</strong>,</p>
        <p>The requester <strong>' . e($ticket['full_name']) . '</strong> has reopened ticket <strong>' . e($ticket['ticket_code']) . '</strong> because the issue persists.</p>
        <div style="background: #fff1f2; border-left: 4px solid #f43f5e; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
            <strong>Requester Comment:</strong>
            <p style="margin: 6px 0 0 0; color: #1e293b;">' . e($reason) . '</p>
        </div>
        <p>Please log in to your officer workspace to follow up.</p>';
        $html = email_template("Ticket Reopened", $body, url('officer/ticket_detail.php?id=' . $ticket['ticket_id']), "View Reopened Ticket");
        send_email($ticket['officer_email'], $ticket['officer_name'], $subject, $html);
    }

    set_flash('success', 'Your ticket has been reopened and set back to "In Progress". An ICT technician will follow up with you.');
    header('Location: ' . $redirectUrl);
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Reopen ticket error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while reopening your ticket. Please try again.');
    header('Location: ' . $redirectUrl);
    exit;
}
