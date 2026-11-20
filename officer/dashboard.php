<?php
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'ICT Officer Dashboard';
$officerId = $_SESSION['officer_id'];

// Scalable KPI metrics: calculated in one instant database query for this officer
try {
    $metricsStmt = $pdo->prepare(
        "SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_count
         FROM tickets
         WHERE assigned_officer_id = ?"
    );
    $metricsStmt->execute([$officerId]);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $assignedCount   = (int)($metrics['total_count'] ?? 0);
    $pendingCount    = (int)($metrics['pending_count'] ?? 0);
    $inProgressCount = (int)($metrics['in_progress_count'] ?? 0);
    $resolvedCount   = (int)($metrics['resolved_count'] ?? 0);
    $closedCount     = (int)($metrics['closed_count'] ?? 0);
} catch (Throwable $e) {
    error_log('Officer metrics query error: ' . $e->getMessage());
    $assignedCount = $pendingCount = $inProgressCount = $resolvedCount = $closedCount = 0;
}

// Server-side filtering & pagination parameters
$statusFilter = trim($_GET['status'] ?? 'all');
$searchQuery  = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$where = ["assigned_officer_id = ?"];
$params = [$officerId];

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if ($searchQuery !== '') {
    $where[] = "(ticket_code LIKE ? OR full_name LIKE ? OR subject LIKE ? OR office LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets $whereSql");
    $countStmt->execute($params);
    $totalMatching = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalMatching / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $dataStmt = $pdo->prepare(
        "SELECT * FROM tickets
         $whereSql
         ORDER BY created_at DESC
         LIMIT " . (int)$perPage . " OFFSET " . (int)$offset
    );
    $dataStmt->execute($params);
    $tickets = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Officer tickets query error: ' . $e->getMessage());
    $totalMatching = 0;
    $totalPages = 1;
    $tickets = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/officer_nav.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $assignedCount ?></div>
      <div class="stat-label">Assigned To Me</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-blue"><?= icon('document', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $inProgressCount ?></div>
      <div class="stat-label">In Progress</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-amber"><?= icon('cog', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $resolvedCount ?></div>
      <div class="stat-label">Resolved</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-green"><?= icon('check_circle', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $closedCount ?></div>
      <div class="stat-label">Closed &amp; Finalized</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-purple"><?= icon('lock', '', 26) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title"><?= icon('ticket', '', 22) ?> My Assigned Ticket Queue</h2>
      <p class="card-subtitle">Showing tickets specifically assigned to your account by the supervisor.</p>
    </div>
  </div>

  <div class="filter-bar" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
      <?php
        $buildFilterUrl = function($st) use ($searchQuery) {
          $p = ['status' => $st];
          if ($searchQuery !== '') $p['q'] = $searchQuery;
          return '?' . http_build_query($p);
        };
      ?>
      <a href="<?= e($buildFilterUrl('all')) ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All Assigned (<?= $assignedCount ?>)</a>
      <a href="<?= e($buildFilterUrl('Pending')) ?>" class="btn <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Pending (<?= $pendingCount ?>)</a>
      <a href="<?= e($buildFilterUrl('In Progress')) ?>" class="btn <?= $statusFilter === 'In Progress' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">In Progress (<?= $inProgressCount ?>)</a>
      <a href="<?= e($buildFilterUrl('Resolved')) ?>" class="btn <?= $statusFilter === 'Resolved' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Resolved (<?= $resolvedCount ?>)</a>
      <a href="<?= e($buildFilterUrl('Closed')) ?>" class="btn <?= $statusFilter === 'Closed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Closed (<?= $closedCount ?>)</a>
    </div>

    <form action="<?= url('officer/dashboard.php') ?>" method="GET" style="display: flex; gap: 0.4rem; align-items: center; margin: 0;">
      <?php if ($statusFilter !== 'all'): ?>
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
      <?php endif; ?>
      <input type="text" name="q" class="form-control search-input" style="min-width: 220px;" placeholder="Search your queue..." value="<?= e($searchQuery) ?>" data-table-search="officerTicketsTable">
      <button type="submit" class="btn btn-secondary btn-sm">Search</button>
      <?php if ($searchQuery !== ''): ?>
        <a href="?status=<?= urlencode($statusFilter) ?>" class="btn btn-secondary btn-sm" title="Clear Search">&times;</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($tickets)): ?>
    <div style="text-align: center; color: var(--color-text-muted); padding: 3rem 1rem;">
      <div style="margin-bottom: 0.75rem; color: #94a3b8;"><?= icon('folder_empty', '', 48) ?></div>
      <p style="font-weight: 600; color: var(--color-dark);">No assigned tickets in this view.</p>
      <p style="font-size: 0.875rem;">
        <?= $searchQuery !== '' ? 'No tickets matched your search query "' . e($searchQuery) . '".' : 'When a supervisor assigns support tickets to you, they will appear here automatically.' ?>
      </p>
    </div>
  <?php else: ?>
    <div class="table-container">
      <table class="table" id="officerTicketsTable">
        <thead>
          <tr>
            <th>Ticket Code</th>
            <th>Office / Requester</th>
            <th>Subject &amp; Category</th>
            <th>Priority</th>
            <th>SLA Status</th>
            <th>Status</th>
            <th>Date Assigned</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $t): ?>
            <tr data-status="<?= e($t['status']) ?>">
              <td><strong style="font-family: var(--font-mono); font-size: 0.875rem;"><?= e($t['ticket_code']) ?></strong></td>
              <td>
                <div><strong><?= e($t['full_name']) ?></strong></div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= e($t['office']) ?></div>
              </td>
              <td>
                <div style="font-weight: 600;"><?= e($t['subject']) ?></div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= e($t['category']) ?></div>
              </td>
              <td><?= render_priority_badge($t['priority']) ?></td>
              <td><?= render_sla_badge($t['due_date'] ?? null, $t['status']) ?></td>
              <td><?= render_status_badge($t['status']) ?></td>
              <td><?= format_date($t['created_at']) ?></td>
              <td>
                <a href="<?= url('officer/ticket_detail.php?id=' . $t['ticket_id']) ?>" class="btn btn-secondary btn-sm">
                  Manage &amp; Update &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Scalable Server-Side Pagination -->
    <?= render_pagination($page, $totalPages, $totalMatching, $perPage, ['status' => $statusFilter, 'q' => $searchQuery]) ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
