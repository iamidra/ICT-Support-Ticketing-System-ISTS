<?php
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'ICT Officer Dashboard';
$officerId = $_SESSION['officer_id'];

// ACL Enforced: Fetch ONLY tickets explicitly assigned to this ICT Officer
$stmt = $pdo->prepare(
    "SELECT * FROM tickets
     WHERE assigned_officer_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$officerId]);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Metrics calculation
$assignedCount = count($tickets);
$pendingCount = 0;
$inProgressCount = 0;
$resolvedCount = 0;
$closedCount = 0;

foreach ($tickets as $t) {
    if ($t['status'] === 'Pending') $pendingCount++;
    if ($t['status'] === 'In Progress') $inProgressCount++;
    if ($t['status'] === 'Resolved') $resolvedCount++;
    if ($t['status'] === 'Closed') $closedCount++;
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
      <div class="stat-label">Closed & Finalized</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-purple"><?= icon('lock', '', 26) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <h2 class="card-title"><?= icon('ticket', '', 22) ?> My Assigned Ticket Queue</h2>
      <p class="card-subtitle">Showing only tickets specifically assigned to your account by the supervisor.</p>
    </div>
  </div>

  <div class="filter-bar">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <button class="btn btn-primary btn-sm" data-status-filter="all" data-target-table="officerTicketsTable">All Assigned</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Pending" data-target-table="officerTicketsTable">Pending</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="In Progress" data-target-table="officerTicketsTable">In Progress</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Resolved" data-target-table="officerTicketsTable">Resolved</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Closed" data-target-table="officerTicketsTable">Closed</button>
    </div>

    <input type="text" class="form-control search-input" placeholder="Search assigned tickets..." data-table-search="officerTicketsTable">
  </div>

  <?php if (empty($tickets)): ?>
    <div style="text-align: center; color: var(--color-text-muted); padding: 3rem 1rem;">
      <div style="margin-bottom: 0.75rem; color: #94a3b8;"><?= icon('folder_empty', '', 48) ?></div>
      <p style="font-weight: 600; color: var(--color-dark);">No assigned tickets in your queue.</p>
      <p style="font-size: 0.875rem;">When a supervisor assigns support tickets to you, they will appear here automatically.</p>
    </div>
  <?php else: ?>
    <div class="table-container">
      <table class="table" id="officerTicketsTable">
        <thead>
          <tr>
            <th>Ticket Code</th>
            <th>Office / Requester</th>
            <th>Subject & Category</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Date Assigned</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($tickets as $t): ?>
            <tr data-status="<?= e($t['status']) ?>">
              <td><strong><?= e($t['ticket_code']) ?></strong></td>
              <td>
                <div><strong><?= e($t['full_name']) ?></strong></div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= e($t['office']) ?></div>
              </td>
              <td>
                <div><?= e($t['subject']) ?></div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted);"><?= e($t['category']) ?></div>
              </td>
              <td><?= render_priority_badge($t['priority']) ?></td>
              <td><?= render_status_badge($t['status']) ?></td>
              <td><?= format_date($t['created_at']) ?></td>
              <td>
                <a href="<?= url('officer/ticket_detail.php?id=' . $t['ticket_id']) ?>" class="btn btn-secondary btn-sm">
                  Manage & Update &rarr;
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
