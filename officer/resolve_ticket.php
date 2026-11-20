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

$ticketId     = (int)($_POST['ticket_id'] ?? 0);
$text         = trim($_POST['resolution_text'] ?? '');
$targetStatus = trim($_POST['target_status'] ?? 'Resolved');
$isInternal   = !empty($_POST['is_internal']) ? 1 : 0;

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
    $check = $pdo->prepare("SELECT * FROM tickets WHERE ticket_id = ? AND assigned_officer_id = ?");
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
        "INSERT INTO resolutions (ticket_id, officer_id, resolution_text, is_internal, created_at) VALUES (?, ?, ?, ?, NOW())"
    );
    $stmt->execute([$ticketId, $_SESSION['officer_id'], $text, $isInternal]);
    $resId = (int)$pdo->lastInsertId();

    // Handle optional resolution attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadRes = save_ticket_attachment($_FILES['attachment'], $ticketId, 'officer', $_SESSION['officer_id'], $resId);
        if (!$uploadRes['success']) {
            $pdo->rollBack();
            set_flash('danger', 'Attachment error: ' . $uploadRes['error']);
            header('Location: ' . url('officer/ticket_detail.php?id=' . $ticketId));
            exit;
        }
    }

    // Update ticket status if not already closed
    if ($existing['status'] !== 'Closed') {
        if ($targetStatus === 'Resolved') {
            $statusUpdate = $pdo->prepare("UPDATE tickets SET status = ?, resolved_at = NOW() WHERE ticket_id = ?");
            $statusUpdate->execute([$targetStatus, $ticketId]);
        } else {
            $statusUpdate = $pdo->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ?");
            $statusUpdate->execute([$targetStatus, $ticketId]);
        }
    }

    // Log action to ticket history
    $officerName = $_SESSION['officer_name'] ?? 'Officer';
    $actionType = $isInternal ? 'internal_note' : ($targetStatus === 'Resolved' ? 'resolved' : 'note_added');
    $details = ($isInternal ? '[Internal Note] ' : '') . 'Logged note; set status to ' . $targetStatus;
    log_ticket_activity($ticketId, $officerName, 'officer', $actionType, $details);

    $pdo->commit();

    // Notify requester if this is a public resolution
    if (!$isInternal && $targetStatus === 'Resolved') {
        require_once __DIR__ . '/../includes/mailer.php';
        notify_ticket_resolved($existing, $text);
    }

    $noteLabel = $isInternal ? 'Internal staff note' : 'Resolution note';
    set_flash('success', $noteLabel . ' saved successfully and ticket status set to "' . $targetStatus . '".');
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
