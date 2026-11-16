<?php
// Records a resolution or technical action note with CSRF protection and ACL validation
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    set_flash('danger', 'Invalid security token.');
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

$ticketId = (int)($_POST['ticket_id'] ?? 0);
$text     = trim($_POST['resolution_text'] ?? '');
$targetStatus = trim($_POST['target_status'] ?? 'Resolved');

if (!$ticketId || empty($text)) {
    set_flash('warning', 'Resolution description cannot be empty.');
    header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
    exit;
}

if (!in_array($targetStatus, ['Resolved', 'In Progress'], true)) {
    $targetStatus = 'Resolved';
}

try {
    $pdo->beginTransaction();

    // Verify assignment ACL
    $check = $pdo->prepare("SELECT ticket_id, status FROM tickets WHERE ticket_id = ? AND assigned_officer_id = ?");
    $check->execute([$ticketId, $_SESSION['officer_id']]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        $pdo->rollBack();
        set_flash('danger', 'Access Denied: Ticket is not assigned to your account.');
        header('Location: ' . url('officer/dashboard.php'));
        exit;
    }

    // Insert resolution log
    $stmt = $pdo->prepare(
        "INSERT INTO resolutions (ticket_id, officer_id, resolution_text, created_at) VALUES (?, ?, ?, NOW())"
    );
    $stmt->execute([$ticketId, $_SESSION['officer_id'], $text]);

    // Update ticket status if not already closed
    if ($existing['status'] !== 'Closed') {
        $statusUpdate = $pdo->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ?");
        $statusUpdate->execute([$targetStatus, $ticketId]);
    }

    $pdo->commit();

    set_flash('success', 'Resolution note saved successfully and ticket status set to "' . $targetStatus . '".');
    header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Officer Resolution Error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while saving your note. Please try again.');
    header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
    exit;
}
