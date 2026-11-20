<?php
// Handles new ticket submission with CSRF protection, input validation, and transaction safety
require_once __DIR__ . '/includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('ticket_form.php'));
    exit;
}

// Preserve submitted form values in case of validation error
$preserveData = [
    'full_name'   => trim($_POST['full_name'] ?? ''),
    'email'       => trim($_POST['email'] ?? ''),
    'office'      => trim($_POST['office'] ?? ''),
    'phone'       => trim($_POST['phone'] ?? ''),
    'category'    => trim($_POST['category'] ?? ''),
    'subject'     => trim($_POST['subject'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'priority'    => trim($_POST['priority'] ?? 'medium')
];

// CSRF check
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrfToken)) {
    $_SESSION['form_data'] = $preserveData;
    set_flash('danger', 'Security validation expired. Please submit the form again.');
    header('Location: ' . url('ticket_form.php'));
    exit;
}

$fullName    = substr($preserveData['full_name'], 0, 100);
$email       = substr($preserveData['email'], 0, 150);
$office      = substr($preserveData['office'], 0, 100);
$phone       = substr($preserveData['phone'], 0, 30);
$category    = substr($preserveData['category'], 0, 100);
$subject     = substr($preserveData['subject'], 0, 150);
$description = substr($preserveData['description'], 0, 5000);
$priority    = strtolower($preserveData['priority']);

// Check required fields
if (empty($fullName) || empty($office) || empty($phone) || empty($category) || empty($subject) || empty($description)) {
    $_SESSION['form_data'] = $preserveData;
    set_flash('warning', 'Please complete all required fields marked with an asterisk (*).');
    header('Location: ' . url('ticket_form.php'));
    exit;
}

// Optional email validation
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['form_data'] = $preserveData;
    set_flash('warning', 'The email address provided is formatted incorrectly.');
    header('Location: ' . url('ticket_form.php'));
    exit;
}

// Phone: only digits, spaces, +, -, ()
if (!preg_match('/^[\d\s\+\-\(\)]{6,30}$/', $phone)) {
    $_SESSION['form_data'] = $preserveData;
    set_flash('warning', 'Please enter a valid phone number.');
    header('Location: ' . url('ticket_form.php'));
    exit;
}

// Validate priority value
$allowedPriorities = ['low', 'medium', 'high'];
if (!in_array($priority, $allowedPriorities, true)) {
    $priority = 'medium';
}

$dueDate = calculate_due_date($priority);

try {
    $pdo->beginTransaction();

    $ticketCode = generate_ticket_code($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO tickets (ticket_code, full_name, email, office, phone, category, subject, description, priority, due_date, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())"
    );
    $stmt->execute([$ticketCode, $fullName, $email ?: null, $office, $phone, $category, $subject, $description, $priority, $dueDate]);

    $newId = (int)$pdo->lastInsertId();

    // Handle optional attachment upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadRes = save_ticket_attachment($_FILES['attachment'], $newId, 'public');
        if (!$uploadRes['success']) {
            $pdo->rollBack();
            $_SESSION['form_data'] = $preserveData;
            set_flash('danger', 'Attachment error: ' . $uploadRes['error']);
            header('Location: ' . url('ticket_form.php'));
            exit;
        }
    }

    // Log creation in ticket history audit log
    log_ticket_activity($newId, $fullName, 'public', 'created', 'Ticket created with priority: ' . ucfirst($priority) . ' (SLA due ' . format_date($dueDate) . ')');

    $pdo->commit();

    // Clear saved form data upon success
    unset($_SESSION['form_data']);

    // Send confirmation email
    require_once __DIR__ . '/includes/mailer.php';
    notify_ticket_created([
        'ticket_id'   => $newId,
        'ticket_code' => $ticketCode,
        'full_name'   => $fullName,
        'email'       => $email,
        'subject'     => $subject,
        'category'    => $category,
        'priority'    => $priority
    ]);

    set_flash('success', 'Your support ticket has been created! Please keep your Ticket ID safe for tracking.');
    $contactParam = !empty($email) ? $email : $phone;
    header('Location: ' . url('ticket_confirmation.php?ticket_id=' . urlencode($ticketCode) . '&c=' . urlencode($contactParam)));
    exit;
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Ticket Submission Error: ' . $e->getMessage());
    $_SESSION['form_data'] = $preserveData;
    set_flash('danger', 'A system error occurred while submitting your ticket. Please try again.');
    header('Location: ' . url('ticket_form.php'));
    exit;
}
