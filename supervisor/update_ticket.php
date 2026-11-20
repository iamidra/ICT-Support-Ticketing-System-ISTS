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
    // Fetch ticket details
    $tStmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $tStmt->execute([$ticketId]);
    $ticket = $tStmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        set_flash('danger', 'Ticket not found.');
        header('Location: ' . url('supervisor/dashboard.php'));
        exit;
    }

    $supervisorName = $_SESSION['officer_name'] ?? 'Supervisor';

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

        $pdo->beginTransaction();

        // Calculate new SLA due date if priority changed
        $newDueDate = $ticket['due_date'];
        if ($priority !== strtolower($ticket['priority'] ?? '')) {
            $newDueDate = calculate_due_date($priority, $ticket['created_at']);
        }

        // Set resolved_at / closed_at timestamps if applicable
        $resolvedAt = $ticket['resolved_at'];
        $closedAt   = $ticket['closed_at'];
        if ($status === 'Resolved' && empty($resolvedAt)) {
            $resolvedAt = date('Y-m-d H:i:s');
        }
        if ($status === 'Closed' && empty($closedAt)) {
            $closedAt = date('Y-m-d H:i:s');
        }

        $stmt = $pdo->prepare(
            "UPDATE tickets 
             SET status = ?, priority = ?, due_date = ?, resolved_at = ?, closed_at = ?
             WHERE ticket_id = ?"
        );
        $stmt->execute([$status, $priority, $newDueDate, $resolvedAt, $closedAt, $ticketId]);

        // Log changes
        $changeDetails = "Status: {$ticket['status']} -> {$status}; Priority: " . ucfirst($ticket['priority']) . " -> " . ucfirst($priority);
        log_ticket_activity($ticketId, $supervisorName, 'supervisor', 'status_changed', $changeDetails);

        $pdo->commit();

        // If newly resolved, notify requester
        if ($status === 'Resolved' && $ticket['status'] !== 'Resolved') {
            require_once __DIR__ . '/../includes/mailer.php';
            notify_ticket_resolved($ticket, 'Status updated to Resolved by Supervisor.');
        }

        set_flash('success', 'Ticket status updated to "' . $status . '" and priority set to "' . ucfirst($priority) . '".');
        header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
        exit;

    } elseif ($action === 'add_note') {
        $note = trim($_POST['admin_note'] ?? '');
        $newStatus = trim($_POST['ticket_status'] ?? '');
        $isInternal = !empty($_POST['is_internal']) ? 1 : 0;

        if (empty($note)) {
            set_flash('warning', 'Administrative note cannot be empty.');
            header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
            exit;
        }

        $pdo->beginTransaction();

        // Insert into resolutions table under the supervisor's officer_id
        $notePrefix = $isInternal ? '[Supervisor Directive] ' : '[Supervisor Note] ';
        $stmt = $pdo->prepare(
            "INSERT INTO resolutions (ticket_id, officer_id, resolution_text, is_internal, created_at) VALUES (?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$ticketId, $_SESSION['officer_id'], $notePrefix . $note, $isInternal]);
        $resId = (int)$pdo->lastInsertId();

        // Handle optional note attachment
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadRes = save_ticket_attachment($_FILES['attachment'], $ticketId, 'supervisor', $_SESSION['officer_id'], $resId);
            if (!$uploadRes['success']) {
                $pdo->rollBack();
                set_flash('danger', 'Attachment error: ' . $uploadRes['error']);
                header('Location: ' . url('supervisor/ticket_detail.php?id=' . $ticketId));
                exit;
            }
        }

        if (!empty($newStatus) && in_array($newStatus, ['Pending', 'In Progress', 'Resolved', 'Closed'], true)) {
            $resolvedAt = ($newStatus === 'Resolved') ? date('Y-m-d H:i:s') : $ticket['resolved_at'];
            $closedAt   = ($newStatus === 'Closed') ? date('Y-m-d H:i:s') : $ticket['closed_at'];

            $update = $pdo->prepare("UPDATE tickets SET status = ?, resolved_at = ?, closed_at = ? WHERE ticket_id = ?");
            $update->execute([$newStatus, $resolvedAt, $closedAt, $ticketId]);
        }

        $logAction = $isInternal ? 'internal_note' : 'note_added';
        log_ticket_activity($ticketId, $supervisorName, 'supervisor', $logAction, ($isInternal ? '[Internal Directive] ' : '') . $note);

        $pdo->commit();

        if (!$isInternal && $newStatus === 'Resolved') {
            require_once __DIR__ . '/../includes/mailer.php';
            notify_ticket_resolved($ticket, $note);
        }

        $noteTypeStr = $isInternal ? 'Internal supervisor directive' : 'Supervisor note';
        set_flash('success', $noteTypeStr . ' recorded successfully.');
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
