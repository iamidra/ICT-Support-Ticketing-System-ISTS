<?php
/**
 * ISTS - System Audit Logs CSV Export Handler
 * Streams filtered system audit logs directly to CSV with memory-capped cursor,
 * UTF-8 BOM, and formula-injection sanitization.
 */

require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

try {
    $categoryFilter  = trim($_GET['category'] ?? 'all');
    $timeRangeFilter = trim($_GET['range'] ?? 'all');
    $searchQuery     = trim($_GET['q'] ?? '');

    $where = [];
    $params = [];

    // Filter: Category
    $allowedCategories = ['auth', 'ticket', 'officer', 'category', 'security', 'system'];
    if (in_array($categoryFilter, $allowedCategories, true)) {
        $where[] = "category = ?";
        $params[] = $categoryFilter;
    }

    // Filter: Time Range
    if ($timeRangeFilter === 'today') {
        $where[] = "created_at >= CURDATE()";
    } elseif ($timeRangeFilter === '7d') {
        $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
    } elseif ($timeRangeFilter === '30d') {
        $where[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    }

    // Filter: Search query
    if ($searchQuery !== '') {
        $where[] = "(actor_name LIKE ? OR action LIKE ? OR details LIKE ? OR target_id LIKE ? OR ip_address LIKE ?)";
        $like = '%' . $searchQuery . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Log the export activity itself to the audit trail
    log_audit_event(
        'audit.exported',
        'security',
        'Supervisor exported system audit logs (Filter: ' . ($categoryFilter !== 'all' ? $categoryFilter : 'All') . ', Range: ' . ($timeRangeFilter !== 'all' ? $timeRangeFilter : 'All') . ')',
        'audit_logs',
        null
    );

    // Release session write lock immediately
    session_write_close();

    $stmt = $pdo->prepare(
        "SELECT log_id, created_at, actor_name, actor_role, category, action, target_type, target_id, details, ip_address, user_agent
         FROM audit_logs
         $whereSql
         ORDER BY created_at DESC, log_id DESC"
    );
    $stmt->execute($params);

    $filename = 'ists_audit_logs_' . date('Y-m-d_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // UTF-8 BOM for Excel compatibility
    fputs($out, "\xEF\xBB\xBF");

    // CSV Column Headers
    fputcsv($out, [
        'Log ID',
        'Timestamp',
        'Actor Name',
        'Actor Role',
        'Category',
        'Action',
        'Target Type',
        'Target ID',
        'Details',
        'IP Address',
        'User Agent'
    ]);

    // Stream rows one by one to keep memory capped
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            escape_csv_field((string)$row['log_id']),
            escape_csv_field($row['created_at']),
            escape_csv_field($row['actor_name']),
            escape_csv_field(ucfirst($row['actor_role'])),
            escape_csv_field(strtoupper($row['category'])),
            escape_csv_field($row['action']),
            escape_csv_field($row['target_type'] ?? ''),
            escape_csv_field($row['target_id'] ?? ''),
            escape_csv_field($row['details'] ?? ''),
            escape_csv_field($row['ip_address']),
            escape_csv_field($row['user_agent'] ?? '')
        ]);
    }

    fclose($out);
    exit;

} catch (Throwable $e) {
    error_log('Audit logs CSV export error: ' . $e->getMessage());
    set_flash('danger', 'Failed to generate audit export file. Please try again.');
    header('Location: ' . url('supervisor/audit_logs.php'));
    exit;
}
