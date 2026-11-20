<?php
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Supervisor Dashboard';

// Scalable KPI metrics: calculated in one instant database aggregate query
try {
    $metrics = $pdo->query(
        "SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN assigned_officer_id IS NULL THEN 1 ELSE 0 END) AS unassigned_count,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) AS in_progress_count,
            SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) AS closed_count
         FROM tickets"
    )->fetch(PDO::FETCH_ASSOC) ?: [];

    $totalTickets    = (int)($metrics['total_count'] ?? 0);
    $unassignedCount = (int)($metrics['unassigned_count'] ?? 0);
    $pendingCount    = (int)($metrics['pending_count'] ?? 0);
    $inProgressCount = (int)($metrics['in_progress_count'] ?? 0);
    $resolvedCount   = (int)($metrics['resolved_count'] ?? 0);
    $closedCount     = (int)($metrics['closed_count'] ?? 0);
} catch (Throwable $e) {
    error_log('Supervisor metrics query error: ' . $e->getMessage());
    $totalTickets = $unassignedCount = $pendingCount = $inProgressCount = $resolvedCount = $closedCount = 0;
}

// Fetch active officers for assignment dropdown
try {
    $officers = $pdo->query(
        "SELECT officer_id, full_name, department FROM ict_officers WHERE role = 'officer' AND (status = 'active' OR status IS NULL) ORDER BY full_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $officers = [];
}

// Server-side filtering & pagination parameters
$statusFilter = trim($_GET['status'] ?? 'all');
$searchQuery  = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

$where = [];
$params = [];

if ($statusFilter !== '' && $statusFilter !== 'all') {
    $where[] = "t.status = ?";
    $params[] = $statusFilter;
}
if ($searchQuery !== '') {
    $where[] = "(t.ticket_code LIKE ? OR t.full_name LIKE ? OR t.subject LIKE ? OR t.office LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets t $whereSql");
    $countStmt->execute($params);
    $totalMatching = (int)$countStmt->fetchColumn();

    $totalPages = max(1, (int)ceil($totalMatching / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $dataStmt = $pdo->prepare(
        "SELECT t.*, o.full_name AS officer_name
         FROM tickets t
         LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
         $whereSql
         ORDER BY t.created_at DESC
         LIMIT " . (int)$perPage . " OFFSET " . (int)$offset
    );
    $dataStmt->execute($params);
    $tickets = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Supervisor tickets query error: ' . $e->getMessage());
    $totalMatching = 0;
    $totalPages = 1;
    $tickets = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $totalTickets ?></div>
      <div class="stat-label">Total System Tickets</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-blue"><?= icon('chart', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $unassignedCount ?></div>
      <div class="stat-label">Unassigned Tickets</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-amber"><?= icon('alert', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $pendingCount ?></div>
      <div class="stat-label">Pending Action</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-purple"><?= icon('clock', '', 26) ?></div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= count($officers) ?></div>
      <div class="stat-label">Active ICT Officers</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-green"><?= icon('users', '', 26) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title"><?= icon('dashboard', '', 22) ?> All System Support Tickets</h2>
      <p class="card-subtitle">Assign tickets to officers, monitor SLA deadlines, and review resolution progress.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="<?= url('supervisor/audit_logs.php') ?>" class="btn btn-secondary btn-sm"><?= icon('history', '', 14) ?> Audit Logs</a>
      <a href="<?= url('supervisor/reports.php') ?>" class="btn btn-secondary btn-sm"><?= icon('chart', '', 14) ?> Analytics Reports &rarr;</a>
      <a href="<?= url('supervisor/reports_export.php') ?>" class="btn btn-primary btn-sm">
        <?= icon('document', '', 14) ?>
        Export CSV
      </a>
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
      <a href="<?= e($buildFilterUrl('all')) ?>" class="btn <?= $statusFilter === 'all' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">All Tickets (<?= $totalTickets ?>)</a>
      <a href="<?= e($buildFilterUrl('Pending')) ?>" class="btn <?= $statusFilter === 'Pending' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Pending (<?= $pendingCount ?>)</a>
      <a href="<?= e($buildFilterUrl('In Progress')) ?>" class="btn <?= $statusFilter === 'In Progress' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">In Progress (<?= $inProgressCount ?>)</a>
      <a href="<?= e($buildFilterUrl('Resolved')) ?>" class="btn <?= $statusFilter === 'Resolved' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Resolved (<?= $resolvedCount ?>)</a>
      <a href="<?= e($buildFilterUrl('Closed')) ?>" class="btn <?= $statusFilter === 'Closed' ? 'btn-primary' : 'btn-secondary' ?> btn-sm">Closed (<?= $closedCount ?>)</a>
    </div>

    <form action="<?= url('supervisor/dashboard.php') ?>" method="GET" style="display: flex; gap: 0.4rem; align-items: center; margin: 0;">
      <?php if ($statusFilter !== 'all'): ?>
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
      <?php endif; ?>
      <input type="text" name="q" class="form-control search-input" style="min-width: 220px;" placeholder="Search all tickets..." value="<?= e($searchQuery) ?>" data-table-search="supervisorTicketsTable">
      <button type="submit" class="btn btn-secondary btn-sm">Search</button>
      <?php if ($searchQuery !== ''): ?>
        <a href="?status=<?= urlencode($statusFilter) ?>" class="btn btn-secondary btn-sm" title="Clear Search">&times;</a>
      <?php endif; ?>
    </form>
  </div>

  <?php if (empty($tickets)): ?>
    <div style="text-align: center; color: var(--color-text-muted); padding: 3rem 1rem;">
      <div style="margin-bottom: 0.75rem; color: #94a3b8;"><?= icon('folder_empty', '', 48) ?></div>
      <p style="font-weight: 600; color: var(--color-dark);">No support tickets found.</p>
      <p style="font-size: 0.875rem;">
        <?= $searchQuery !== '' ? 'No tickets matched your search query "' . e($searchQuery) . '".' : 'Tickets submitted by staff will appear here.' ?>
      </p>
    </div>
  <?php else: ?>
    <div class="table-container">
      <table class="table" id="supervisorTicketsTable">
        <thead>
          <tr>
            <th>Ticket Code</th>
            <th>Requester / Office</th>
            <th>Subject &amp; Category</th>
            <th>Priority</th>
            <th>SLA Status</th>
            <th>Status</th>
            <th>Assigned Officer</th>
            <th>Assign / Reassign</th>
            <th>Actions</th>
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
              <td>
                <?php if ($t['officer_name']): ?>
                  <span style="font-weight: 600; color: var(--color-dark);"><?= e($t['officer_name']) ?></span>
                <?php else: ?>
                  <span class="badge badge-pending">Unassigned</span>
                <?php endif; ?>
              </td>
              <td>
                <form action="<?= url('supervisor/assign_ticket.php') ?>" method="POST" style="display: flex; gap: 0.35rem; align-items: center; margin: 0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="ticket_id" value="<?= $t['ticket_id'] ?>">
                  <select name="officer_id" class="form-select" style="padding: 0.3rem 0.5rem; font-size: 0.8rem; max-width: 145px;" required>
                    <option value="">-- Assign --</option>
                    <?php foreach ($officers as $o): ?>
                      <option value="<?= $o['officer_id'] ?>" <?= $t['assigned_officer_id'] == $o['officer_id'] ? 'selected' : '' ?>>
                        <?= e($o['full_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.6rem; font-size: 0.775rem;">Set</button>
                </form>
              </td>
              <td>
                <a href="<?= url('supervisor/ticket_detail.php?id=' . $t['ticket_id']) ?>" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem;">
                  Inspect &rarr;
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
