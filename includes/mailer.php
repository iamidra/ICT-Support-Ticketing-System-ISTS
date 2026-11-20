<?php
/**
 * ISTS - Email Notification Service
 * Dispatches transactional notifications for ticket lifecycle events.
 * Supports standard PHP mail() and logging driver for local development.
 */

require_once __DIR__ . '/functions.php';

/**
 * Send an email notification.
 * Does not throw exceptions; logs errors if sending fails.
 */
function send_email($toEmail, $toName, $subject, $htmlContent, $textContent = '') {
    if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $mailEnabled = filter_var(env('MAIL_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    $mailDriver  = env('MAIL_DRIVER', 'mail'); // 'mail' or 'log'
    $fromEmail   = env('MAIL_FROM', 'no-reply@ists.local');
    $fromName    = env('MAIL_FROM_NAME', APP_NAME);

    // If text version is not provided, convert HTML to basic plain text
    if (empty($textContent)) {
        $textContent = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlContent));
    }

    // In local development or if MAIL_ENABLED is false, write to a log file instead of failing
    if (!$mailEnabled || $mailDriver === 'log') {
        $logDir = dirname(__DIR__) . '/uploads';
        $logFile = $logDir . '/mail.log';
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] TO: $toName <$toEmail>\nSUBJECT: $subject\nBODY:\n$textContent\n" . str_repeat('-', 60) . "\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND);
        return true;
    }

    // Use native PHP mail()
    try {
        $boundary = '==Multipart_Boundary_x' . md5(time()) . 'x';
        $headers  = "From: " . sprintf('=?UTF-8?B?%s?=', base64_encode($fromName)) . " <$fromEmail>\r\n";
        $headers .= "Reply-To: $fromEmail\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
        $headers .= "X-Mailer: ISTS-Mailer/2.2\r\n";

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $textContent . "\r\n\r\n";

        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $body .= $htmlContent . "\r\n\r\n";

        $body .= "--$boundary--";

        $subjectEncoded = sprintf('=?UTF-8?B?%s?=', base64_encode($subject));

        return @mail($toEmail, $subjectEncoded, $body, $headers);
    } catch (Throwable $t) {
        error_log("ISTS Mail Error to $toEmail: " . $t->getMessage());
        return false;
    }
}

/**
 * Generate standard HTML email template wrapper.
 */
function email_template($title, $contentHtml, $actionUrl = null, $actionText = null) {
    $appName = e(APP_NAME);
    $actionBtnHtml = '';
    if ($actionUrl && $actionText) {
        $actionBtnHtml = '
        <div style="margin: 28px 0; text-align: center;">
            <a href="' . e($actionUrl) . '" style="background: #2563eb; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;">' . e($actionText) . '</a>
        </div>';
    }

    return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . e($title) . '</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <div style="background: #0f172a; padding: 20px 24px; color: #ffffff;">
            <h1 style="margin: 0; font-size: 18px; font-weight: 700;">' . $appName . '</h1>
        </div>
        <div style="padding: 24px;">
            <h2 style="font-size: 18px; color: #0f172a; margin-top: 0; margin-bottom: 16px;">' . e($title) . '</h2>
            ' . $contentHtml . '
            ' . $actionBtnHtml . '
        </div>
        <div style="background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px 24px; font-size: 12px; color: #64748b; text-align: center;">
            This is an automated notification from ' . $appName . '. Please do not reply directly to this email.
        </div>
    </div>
</body>
</html>';
}

/**
 * Notify requester that their ticket was created.
 */
function notify_ticket_created($ticket) {
    if (empty($ticket['email'])) return;

    $trackingUrl = url('track_status.php?ticket_id=' . urlencode($ticket['ticket_code']));
    $subject = "Support Ticket Received: [" . $ticket['ticket_code'] . "] " . $ticket['subject'];

    $content = '
    <p>Hello <strong>' . e($ticket['full_name']) . '</strong>,</p>
    <p>Your technical support ticket has been registered with our ICT service desk.</p>
    <div style="background: #f1f5f9; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
        <p style="margin: 0 0 6px 0;"><strong>Ticket Code:</strong> <span style="font-family: monospace; font-size: 15px; color: #2563eb;">' . e($ticket['ticket_code']) . '</span></p>
        <p style="margin: 0 0 6px 0;"><strong>Category:</strong> ' . e($ticket['category']) . '</p>
        <p style="margin: 0 0 6px 0;"><strong>Priority:</strong> ' . ucfirst(e($ticket['priority'])) . '</p>
        <p style="margin: 0;"><strong>Subject:</strong> ' . e($ticket['subject']) . '</p>
    </div>
    <p>Our ICT supervisor is reviewing your request and will assign a technician promptly. You can track progress anytime using your Ticket Code.</p>';

    $html = email_template("Ticket Submitted Successfully", $content, $trackingUrl, "Track Ticket Online");
    send_email($ticket['email'], $ticket['full_name'], $subject, $html);
}

/**
 * Notify officer that a ticket has been assigned to them.
 */
function notify_ticket_assigned($ticket, $officer) {
    if (empty($officer['email'])) return;

    $ticketUrl = url('officer/ticket_detail.php?id=' . $ticket['ticket_id']);
    $subject = "New Assigned Ticket: [" . $ticket['ticket_code'] . "] " . $ticket['subject'];

    $content = '
    <p>Hello <strong>' . e($officer['full_name']) . '</strong>,</p>
    <p>A support ticket has been assigned to you by the ICT supervisor.</p>
    <div style="background: #f1f5f9; border-left: 4px solid #3b82f6; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
        <p style="margin: 0 0 6px 0;"><strong>Ticket Code:</strong> ' . e($ticket['ticket_code']) . '</p>
        <p style="margin: 0 0 6px 0;"><strong>Requester:</strong> ' . e($ticket['full_name']) . ' (' . e($ticket['office']) . ')</p>
        <p style="margin: 0 0 6px 0;"><strong>Phone:</strong> ' . e($ticket['phone']) . '</p>
        <p style="margin: 0 0 6px 0;"><strong>Priority:</strong> ' . ucfirst(e($ticket['priority'])) . '</p>
        <p style="margin: 0;"><strong>Summary:</strong> ' . e($ticket['subject']) . '</p>
    </div>
    <p>Please log in to your officer workspace to review details and begin troubleshooting.</p>';

    $html = email_template("New Support Ticket Assigned", $content, $ticketUrl, "View Ticket in Workspace");
    send_email($officer['email'], $officer['full_name'], $subject, $html);
}

/**
 * Notify requester that their ticket has been resolved.
 */
function notify_ticket_resolved($ticket, $resolutionNote = '') {
    if (empty($ticket['email'])) return;

    $trackingUrl = url('track_status.php?ticket_id=' . urlencode($ticket['ticket_code']));
    $subject = "Support Ticket Resolved: [" . $ticket['ticket_code'] . "] " . $ticket['subject'];

    $resSection = '';
    if (!empty($resolutionNote)) {
        $resSection = '
        <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 12px 16px; margin: 16px 0; border-radius: 4px;">
            <strong style="color: #166534; font-size: 13px;">Technician Resolution Note:</strong>
            <p style="margin: 6px 0 0 0; color: #1e293b; white-space: pre-line;">' . e($resolutionNote) . '</p>
        </div>';
    }

    $content = '
    <p>Hello <strong>' . e($ticket['full_name']) . '</strong>,</p>
    <p>Your support ticket <strong>' . e($ticket['ticket_code']) . '</strong> has been marked as <strong>Resolved</strong> by our ICT team.</p>
    ' . $resSection . '
    <p>Please inspect your equipment or service. If you are satisfied, you can leave a quick rating. If you are still experiencing issues, you can reopen the ticket via the tracking portal.</p>';

    $html = email_template("Ticket Resolved", $content, $trackingUrl, "View Resolution & Provide Feedback");
    send_email($ticket['email'], $ticket['full_name'], $subject, $html);
}
