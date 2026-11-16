<?php
// Supervisor Ticket Management Action Processor (Status, Priority, and Admin Notes)
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
$action   = trim($_POST['action'] ?? 'update_status_priority');

if (!$ticketId) {
    set_flash('danger', 'Invalid ticket identifier.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}

try {
    if ($action === 'update_status_priority') {
        $status   = trim($_POST['status'] ?? '');
        $priority = strtolower(trim($_POST['priority'] ?? ''));

        $allowedStatuses   = ['Pending', 'In Progress', 'Resolved', 'Closed'];
        $allowedPriorities = ['low', 'medium', 'high'];

        if (!in_array($status, $allowedStatuses, true) || !in_array($priority, $allowedPriorities, true)) {
            set_flash('danger', 'Invalid status or priority value provided.');
            header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
            exit;
        }

        $stmt = $pdo->prepare("UPDATE tickets SET status = ?, priority = ? WHERE ticket_id = ?");
        $stmt->execute([$status, $priority, $ticketId]);

        set_flash('success', 'Ticket status updated to "' . $status . '" and priority set to "' . ucfirst($priority) . '".');
        header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
        exit;

    } elseif ($action === 'add_note') {
        $note = trim($_POST['admin_note'] ?? '');
        $newStatus = trim($_POST['ticket_status'] ?? '');

        if (empty($note)) {
            set_flash('warning', 'Administrative note cannot be empty.');
            header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
            exit;
        }

        $pdo->beginTransaction();

        // Insert into resolutions table under the supervisor's officer_id
        $stmt = $pdo->prepare(
            "INSERT INTO resolutions (ticket_id, officer_id, resolution_text, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$ticketId, $_SESSION['officer_id'], '[Supervisor Note] ' . $note]);

        if (!empty($newStatus) && in_array($newStatus, ['Pending', 'In Progress', 'Resolved', 'Closed'], true)) {
            $update = $pdo->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ?");
            $update->execute([$newStatus, $ticketId]);
        }

        $pdo->commit();

        set_flash('success', 'Supervisor note recorded successfully.');
        header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
        exit;
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Supervisor Update Ticket Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while updating the ticket.');
    header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
    exit;
}
