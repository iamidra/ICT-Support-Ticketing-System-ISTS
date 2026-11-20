<?php
/**
 * Submits CSAT feedback and rating for a resolved or closed ticket.
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
$rating     = (int)($_POST['rating'] ?? 0);
$comments   = trim($_POST['feedback_comments'] ?? '');

$redirectUrl = url('track_status.php?ticket_id=' . urlencode($ticketCode));

if (empty($ticketCode) || $rating < 1 || $rating > 5) {
    set_flash('warning', 'Please provide a valid rating between 1 and 5 stars.');
    header('Location: ' . $redirectUrl);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT ticket_id, full_name, status, feedback_rating FROM tickets WHERE UPPER(ticket_code) = UPPER(?)");
    $stmt->execute([$ticketCode]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        set_flash('danger', 'Ticket not found.');
        header('Location: ' . url('track_status.php'));
        exit;
    }

    if (!empty($ticket['feedback_rating'])) {
        set_flash('info', 'You have already submitted feedback for this ticket.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    $update = $pdo->prepare("UPDATE tickets SET feedback_rating = ?, feedback_comments = ? WHERE ticket_id = ?");
    $update->execute([$rating, substr($comments, 0, 1000) ?: null, $ticket['ticket_id']]);

    log_ticket_activity(
        $ticket['ticket_id'],
        $ticket['full_name'],
        'public',
        'feedback_submitted',
        "Submitted {$rating}-star rating" . (!empty($comments) ? ": \"{$comments}\"" : '')
    );

    set_flash('success', 'Thank you for your feedback! Your rating helps us improve our ICT support service.');
    header('Location: ' . $redirectUrl);
    exit;

} catch (Throwable $e) {
    error_log('Feedback submission error: ' . $e->getMessage());
    set_flash('danger', 'An error occurred while submitting your feedback. Please try again.');
    header('Location: ' . $redirectUrl);
    exit;
}
