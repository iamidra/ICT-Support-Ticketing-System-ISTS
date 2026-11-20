<?php
/**
 * ISTS - Comprehensive System Audit Log Dashboard
 * Accessible exclusively by Supervisors to inspect authentication, ticket lifecycle,
 * staff management, category configuration, and security events across the entire application.
 */

require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'System Audit Logs';

// 1. Instant Aggregate KPI Metrics
try {
    $metrics = $pdo->query(
        "SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN category = 'auth' THEN 1 ELSE 0 END) AS auth_count,
            SUM(CASE WHEN category = 'ticket' THEN 1 ELSE 0 END) AS ticket_count,
            SUM(CASE WHEN category IN ('officer', 'category', 'security') THEN 1 ELSE 0 END) AS admin_security_count
         FROM audit_logs"
    )->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalLogs       = (int)($metrics['total_count'] ?? 0);
    $authEventsCount = (int)($metrics['auth_count'] ?? 0);
    $ticketEventsCount = (int)($metrics['ticket_count'] ?? 0);
    $adminSecCount   = (int)($metrics['admin_security_count'] ?? 0);
} catch (Throwable $e) {
    error_log('Audit metrics query error: ' . $e->getMessage());
    $totalLogs = $authEventsCount = $ticketEventsCount = $adminSecCount = 0;
}

// 2. Query Parameters & Filters
$categoryFilter  = trim($_GET['category'] ?? 'all');
$timeRangeFilter = trim($_GET['range'] ?? 'all');
$searchQuery     = trim($_GET['q'] ?? '');
$page            = max(1, (int)($_GET['page'] ?? 1));
$perPage         = 25;

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

// Filter: Search keyword
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

// 3. Paginated Fetching
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs $whereSql");
    $countStmt->execute($params);
    $totalMatching = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalMatching / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $dataStmt = $pdo->prepare(
        "SELECT * FROM audit_logs
         $whereSql
         ORDER BY created_at DESC, log_id DESC
         LIMIT " . (int)$perPage . " OFFSET " . (int)$offset
    );
    $dataStmt->execute($params);
    $logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Audit logs fetch error: ' . $e->getMessage());
    $totalMatching = 0;
    $totalPages = 1;
    $logs = [];
}

// Helper to preserve active query parameters in links
$buildAuditUrl = function(array $overrides = []) use ($categoryFilter, $timeRangeFilter, $searchQuery) {
    $p = [
        'category' => $categoryFilter,
        'range'    => $timeRangeFilter,
        'q'        => $searchQuery,
    ];
    $merged = array_merge($p, $overrides);
    // Prune defaults
    if (($merged['category'] ?? '') === 'all') unset($merged['category']);
    if (($merged['range'] ?? '') === 'all') unset($merged['range']);
    if (($merged['q'] ?? '') === '') unset($merged['q']);
    if (($merged['page'] ?? 1) <= 1) unset($merged['page']);

    return !empty($merged) ? '?' . http_build_query($merged) : 'audit_logs.php';
};

$exportUrl = 'audit_logs_export.php?' . http_build_query(array_filter([
    'category' => $categoryFilter !== 'all' ? $categoryFilter : null,
    'range'    => $timeRangeFilter !== 'all' ? $timeRangeFilter : null,
    'q'        => $searchQuery !== '' ? $searchQuery : null,
]));

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<!-- KPI Highlights -->
<div class="stats-grid">
  <div class="stat-card">
    <div>
      <div class="stat-value"><?= number_format($totalLogs) ?></div>
      <div class="stat-label">Total System Log Events</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-blue"><?= icon('history', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= number_format($authEventsCount) ?></div>
      <div class="stat-label">Authentication Events</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-purple"><?= icon('lock', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= number_format($ticketEventsCount) ?></div>
      <div class="stat-label">Ticket Lifecycle Events</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-green"><?= icon('ticket', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= number_format($adminSecCount) ?></div>
      <div class="stat-label">Admin &amp; Security Audits</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-amber"><?= icon('shield', '', 26) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><?= icon('shield', '', 22) ?> Comprehensive System Audit Trail</h1>
      <p class="card-subtitle">Real-time immutable audit records of all user authentication, ticket mutations, officer management, and security changes.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="<?= e($exportUrl) ?>" class="btn btn-primary btn-sm">
        <?= icon('document', '', 14) ?>
        Export Filtered CSV
      </a>
    </div>
  </div>

  <!-- Filter & Search Controls -->
  <div class="filter-bar" style="display: flex; flex-direction: column; gap: 1rem;">
    <!-- Category Pills -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
      <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); margin-right: 0.35rem;">Category:</span>
        <a href="<?= e($buildAuditUrl(['category' => 'all', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">All</a>
        <a href="<?= e($buildAuditUrl(['category' => 'auth', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'auth' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Authentication</a>
        <a href="<?= e($buildAuditUrl(['category' => 'ticket', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'ticket' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Tickets</a>
        <a href="<?= e($buildAuditUrl(['category' => 'officer', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'officer' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Staff/Officers</a>
        <a href="<?= e($buildAuditUrl(['category' => 'category', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'category' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Categories</a>
        <a href="<?= e($buildAuditUrl(['category' => 'security', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'security' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Security</a>
        <a href="<?= e($buildAuditUrl(['category' => 'system', 'page' => 1])) ?>" class="btn <?= $categoryFilter === 'system' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">System</a>
      </div>

      <!-- Date Range Pills -->
      <div style="display: flex; gap: 0.35rem; flex-wrap: wrap; align-items: center;">
        <span style="font-size: 0.8rem; font-weight: 600; color: var(--color-text-muted); margin-right: 0.35rem;">Time Range:</span>
        <a href="<?= e($buildAuditUrl(['range' => 'all', 'page' => 1])) ?>" class="btn <?= $timeRangeFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">All Time</a>
        <a href="<?= e($buildAuditUrl(['range' => 'today', 'page' => 1])) ?>" class="btn <?= $timeRangeFilter === 'today' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Today</a>
        <a href="<?= e($buildAuditUrl(['range' => '7d', 'page' => 1])) ?>" class="btn <?= $timeRangeFilter === '7d' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Past 7 Days</a>
        <a href="<?= e($buildAuditUrl(['range' => '30d', 'page' => 1])) ?>" class="btn <?= $timeRangeFilter === '30d' ? 'btn-primary' : 'btn-secondary' ?> btn-sm" style="padding: 0.25rem 0.65rem; font-size: 0.8rem;">Past 30 Days</a>
      </div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="audit_logs.php" style="display: flex; gap: 0.5rem; margin: 0; align-items: center;">
      <?php if ($categoryFilter !== 'all'): ?>
        <input type="hidden" name="category" value="<?= e($categoryFilter) ?>">
      <?php endif; ?>
      <?php if ($timeRangeFilter !== 'all'): ?>
        <input type="hidden" name="range" value="<?= e($timeRangeFilter) ?>">
      <?php endif; ?>

      <div style="flex: 1; position: relative;">
        <input 
          type="text" 
          name="q" 
          value="<?= e($searchQuery) ?>" 
          class="form-control" 
          placeholder="Search audit trail by actor, action, details, IP address or target ID..."
          style="padding-left: 2.25rem; font-size: 0.875rem;"
        >
        <span style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-muted); pointer-events: none;">
          <?= icon('search', '', 15) ?>
        </span>
      </div>

      <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.5rem 0.85rem;">
        Filter
      </button>

      <?php if ($searchQuery !== '' || $categoryFilter !== 'all' || $timeRangeFilter !== 'all'): ?>
        <a href="audit_logs.php" class="btn btn-secondary btn-sm" style="padding: 0.5rem 0.75rem;" title="Reset filters">
          <?= icon('close', '', 14) ?> Reset
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Audit Logs Table -->
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th style="width: 140px;">Timestamp</th>
          <th style="width: 170px;">Actor</th>
          <th style="width: 100px;">Category</th>
          <th style="width: 160px;">Action</th>
          <th style="width: 140px;">Target</th>
          <th>Activity Details</th>
          <th style="width: 140px;">IP / Source</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr>
            <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--color-text-muted);">
              <div style="margin-bottom: 0.5rem;"><?= icon('history', '', 36) ?></div>
              <div style="font-weight: 600; font-size: 1rem; color: var(--color-text);">No audit records found</div>
              <p style="margin: 0.25rem 0 0 0; font-size: 0.85rem;">No activities matched your current search and filter criteria.</p>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($logs as $log): ?>
            <tr>
              <!-- Timestamp -->
              <td style="font-size: 0.8rem; white-space: nowrap;">
                <div style="font-weight: 600; color: var(--color-text);"><?= date('Y-m-d', strtotime($log['created_at'])) ?></div>
                <div style="color: var(--color-text-muted);"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
              </td>

              <!-- Actor -->
              <td>
                <div style="font-weight: 600; font-size: 0.875rem; color: var(--color-text); line-height: 1.2;">
                  <?= e($log['actor_name']) ?>
                </div>
                <div style="margin-top: 0.2rem;">
                  <?= render_audit_actor_badge($log['actor_role']) ?>
                </div>
              </td>

              <!-- Category -->
              <td>
                <?= render_audit_category_badge($log['category']) ?>
              </td>

              <!-- Action -->
              <td>
                <code style="font-size: 0.775rem; background: #f1f5f9; color: #0f172a; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid #e2e8f0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;">
                  <?= e($log['action']) ?>
                </code>
              </td>

              <!-- Target -->
              <td style="font-size: 0.825rem;">
                <?php if (!empty($log['target_type'])): ?>
                  <span style="color: var(--color-text-muted); text-transform: uppercase; font-size: 0.7rem; font-weight: 600;"><?= e($log['target_type']) ?></span>
                  <?php if (!empty($log['target_id'])): ?>
                    <div style="font-weight: 600; font-family: monospace;">#<?= e($log['target_id']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: var(--color-text-muted);">&mdash;</span>
                <?php endif; ?>
              </td>

              <!-- Details -->
              <td style="font-size: 0.85rem; line-height: 1.4;">
                <div style="word-break: break-word; color: #334155;">
                  <?= e($log['details'] ?? 'No additional details provided.') ?>
                </div>
              </td>

              <!-- IP & Client -->
              <td style="font-size: 0.8rem; color: var(--color-text-muted);">
                <div style="font-family: monospace; font-weight: 600; color: #475569;" title="<?= e($log['ip_address']) ?>">
                  <?= e($log['ip_address']) ?>
                </div>
                <?php 
                  $ua = $log['user_agent'] ?? '';
                  $shortUa = 'Browser';
                  if (strpos($ua, 'Firefox') !== false) $shortUa = 'Firefox';
                  elseif (strpos($ua, 'Chrome') !== false || strpos($ua, 'CriOS') !== false) $shortUa = 'Chrome';
                  elseif (strpos($ua, 'Safari') !== false) $shortUa = 'Safari';
                  elseif (strpos($ua, 'Edge') !== false || strpos($ua, 'Edg') !== false) $shortUa = 'Edge';
                  elseif (strpos($ua, 'CLI') !== false) $shortUa = 'CLI';
                ?>
                <div style="font-size: 0.725rem; color: #94a3b8; margin-top: 0.15rem;" title="<?= e($ua) ?>">
                  <?= e($shortUa) ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Controls -->
  <?php
    $queryParams = [];
    if ($categoryFilter !== 'all') $queryParams['category'] = $categoryFilter;
    if ($timeRangeFilter !== 'all') $queryParams['range'] = $timeRangeFilter;
    if ($searchQuery !== '') $queryParams['q'] = $searchQuery;
    echo render_pagination($page, $totalPages, $totalMatching, $perPage, $queryParams, 'audit events');
  ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
