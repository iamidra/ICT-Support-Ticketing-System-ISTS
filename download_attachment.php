<?php
/**
 * ISTS - Secure Attachment Download Handler
 * Prevents directory traversal, validates permissions, and streams file cleanly.
 */

require_once __DIR__ . '/includes/db_connect.php';

$attachmentId = (int)($_GET['id'] ?? 0);

if (!$attachmentId) {
    http_response_code(400);
    exit('Invalid attachment request.');
}

try {
    $stmt = $pdo->prepare(
        "SELECT a.*, t.ticket_code, r.is_internal
         FROM attachments a
         JOIN tickets t ON a.ticket_id = t.ticket_id
         LEFT JOIN resolutions r ON a.resolution_id = r.resolution_id
         WHERE a.attachment_id = ?"
    );
    $stmt->execute([$attachmentId]);
    $fileRecord = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fileRecord) {
        http_response_code(404);
        exit('Attachment not found.');
    }

    // Access control: If attached to an internal resolution, require staff login
    if (!empty($fileRecord['is_internal'])) {
        if (!is_logged_in()) {
            http_response_code(403);
            exit('Access Denied: You must be signed in as ICT staff to view this internal attachment.');
        }
    }

    $uploadDir = __DIR__ . '/uploads';
    $safeName = basename($fileRecord['stored_filename']);
    $filePath = $uploadDir . '/' . $safeName;

    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        exit('File not found on storage server.');
    }

    $mimeType = $fileRecord['mime_type'] ?: 'application/octet-stream';
    $origName = $fileRecord['original_filename'];

    // Clear any output buffers
    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');

    // Inline display for images/PDFs, attachment for others
    $disposition = (str_starts_with($mimeType, 'image/') || $mimeType === 'application/pdf') ? 'inline' : 'attachment';
    header('Content-Disposition: ' . $disposition . '; filename="' . rawurlencode($origName) . '"');
    header('Cache-Control: private, max-age=86400');

    // Audit log download activity
    log_audit_event(
        'attachment.downloaded',
        'ticket',
        'Downloaded file "' . $origName . '" for ticket #' . $fileRecord['ticket_code'],
        'attachment',
        $attachmentId
    );

    // Close session to release write lock for concurrent requests
    session_write_close();

    readfile($filePath);
    exit;

} catch (Throwable $e) {
    error_log("Download attachment error: " . $e->getMessage());
    http_response_code(500);
    exit('Error downloading file.');
}
