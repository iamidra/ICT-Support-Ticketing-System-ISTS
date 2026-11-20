<?php
// Assigns/reassigns a ticket to an officer with CSRF protection
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

$ticketId  = (int)($_POST['ticket_id'] ?? 0);
$officerId = (int)($_POST['officer_id'] ?? 0);

if (!$ticketId || !$officerId) {
    set_flash('warning', 'Please select a valid ticket and officer for assignment.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify officer exists and is an active officer role
    $officerCheck = $pdo->prepare("SELECT officer_id, full_name, email FROM ict_officers WHERE officer_id = ? AND role = 'officer'");
    $officerCheck->execute([$officerId]);
    $officer = $officerCheck->fetch(PDO::FETCH_ASSOC);
    if (!$officer) {
        $pdo->rollBack();
        set_flash('danger', 'Selected officer account is invalid or not found.');
        header('Location: ' . url('supervisor/dashboard.php'));
        exit;
    }

    // Verify ticket exists
    $ticketCheck = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $ticketCheck->execute([$ticketId]);
    $ticket = $ticketCheck->fetch(PDO::FETCH_ASSOC);
    if (!$ticket) {
        $pdo->rollBack();
        set_flash('danger', 'Ticket not found.');
        header('Location: ' . url('supervisor/dashboard.php'));
        exit;
    }

    // Update assignment
    $stmt = $pdo->prepare("UPDATE tickets SET assigned_officer_id = ? WHERE ticket_id = ?");
    $stmt->execute([$officerId, $ticketId]);

    // Automatically transition Pending status to In Progress
    $statusStmt = $pdo->prepare("UPDATE tickets SET status = 'In Progress' WHERE ticket_id = ? AND status = 'Pending'");
    $statusStmt->execute([$ticketId]);

    // Log assignment to audit history
    $supervisorName = $_SESSION['officer_name'] ?? 'Supervisor';
    log_ticket_activity(
        $ticketId,
        $supervisorName,
        'supervisor',
        'assigned',
        'Assigned ticket to technician ' . $officer['full_name']
    );

    $pdo->commit();

    // Notify assigned officer
    require_once __DIR__ . '/../includes/mailer.php';
    notify_ticket_assigned($ticket, $officer);

    set_flash('success', 'Ticket successfully assigned to ' . $officer['full_name'] . '.');
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    set_flash('danger', 'Error processing ticket assignment: ' . $e->getMessage());
    header('Location: ' . url('supervisor/dashboard.php'));
    exit;
}
