<?php
// Exports all system tickets as a clean, secure CSV download
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

try {
    // Release session lock immediately so concurrent user requests aren't blocked
    session_write_close();

    $stmt = $pdo->query(
        "SELECT t.ticket_code, t.full_name, t.email, t.office, t.phone, t.category, t.subject, t.priority, t.due_date, t.status, o.full_name AS officer_name, t.created_at, t.resolved_at, t.feedback_rating, t.feedback_comments, t.updated_at
         FROM tickets t
         LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
         ORDER BY t.created_at DESC"
    );

    $filename = 'ists_tickets_export_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // Add UTF-8 BOM for accurate character display in Microsoft Excel
    fputs($out, "\xEF\xBB\xBF");

    // CSV Headers
    fputcsv($out, [
        'Ticket Code',
        'Requester Name',
        'Requester Email',
        'Office / Location',
        'Contact Phone',
        'Category',
        'Subject Summary',
        'Priority',
        'SLA Target Due Date',
        'Status',
        'Assigned Officer',
        'Date Created',
        'Date Resolved',
        'CSAT Rating (1-5)',
        'CSAT Comments',
        'Last Updated'
    ]);

    // Memory-capped cursor streaming: process row by row without buffering full dataset in memory
    while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Sanitize every field to prevent CSV formula injection
        fputcsv($out, [
            escape_csv_field($t['ticket_code']),
            escape_csv_field($t['full_name']),
            escape_csv_field($t['email'] ?? ''),
            escape_csv_field($t['office']),
            escape_csv_field($t['phone']),
            escape_csv_field($t['category']),
            escape_csv_field($t['subject']),
            escape_csv_field(ucfirst($t['priority'] ?? 'medium')),
            escape_csv_field($t['due_date'] ?? ''),
            escape_csv_field($t['status']),
            escape_csv_field($t['officer_name'] ?: 'Unassigned'),
            escape_csv_field($t['created_at']),
            escape_csv_field($t['resolved_at'] ?? ''),
            escape_csv_field($t['feedback_rating'] ?? ''),
            escape_csv_field($t['feedback_comments'] ?? ''),
            escape_csv_field($t['updated_at'])
        ]);
    }

    fclose($out);
    exit;

} catch (Exception $e) {
    error_log('CSV Export Error: ' . $e->getMessage());
    set_flash('danger', 'Failed to generate export file. Please try again.');
    header('Location: ' . url('supervisor/reports.php'));
    exit;
}
