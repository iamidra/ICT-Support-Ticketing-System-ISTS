<?php
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Supervisor Dashboard';

// Fetch all tickets with assigned officer name
try {
    $stmt = $pdo->query(
        "SELECT t.*, o.full_name AS officer_name
         FROM tickets t
         LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
         ORDER BY t.created_at DESC"
    );
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('Dashboard tickets query error: ' . $e->getMessage());
    $tickets = [];
}

// Fetch all active officers for assignment selection
try {
    $officers = $pdo->query(
        "SELECT officer_id, full_name, department FROM ict_officers WHERE role = 'officer' AND (status = 'active' OR status IS NULL) ORDER BY full_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    // Fallback: status column may not exist on old DBs
    try {
        $officers = $pdo->query(
            "SELECT officer_id, full_name, department FROM ict_officers WHERE role = 'officer' ORDER BY full_name ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e2) {
        error_log('Dashboard officers query error: ' . $e2->getMessage());
        $officers = [];
    }
}

// Calculate metrics
$totalTickets = count($tickets);
$unassignedCount = 0;
$pendingCount = 0;
$inProgressCount = 0;
$resolvedCount = 0;

foreach ($tickets as $t) {
    if (!$t['assigned_officer_id']) $unassignedCount++;
    if ($t['status'] === 'Pending') $pendingCount++;
    if ($t['status'] === 'In Progress') $inProgressCount++;
    if ($t['status'] === 'Resolved' || $t['status'] === 'Closed') $resolvedCount++;
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
      <p class="card-subtitle">Assign tickets to officers, monitor status, and inspect resolution history.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <a href="<?= url('supervisor/reports.php') ?>" class="btn btn-secondary btn-sm"><?= icon('chart', '', 14) ?> Analytics Reports &rarr;</a>
      <a href="<?= url('supervisor/reports_export.php') ?>" class="btn btn-primary btn-sm">
        <?= icon('document', '', 14) ?>
        Export CSV
      </a>
    </div>
  </div>

  <div class="filter-bar">
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
      <button class="btn btn-primary btn-sm" data-status-filter="all" data-target-table="supervisorTicketsTable">All Tickets (<?= $totalTickets ?>)</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Pending" data-target-table="supervisorTicketsTable">Pending (<?= $pendingCount ?>)</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="In Progress" data-target-table="supervisorTicketsTable">In Progress (<?= $inProgressCount ?>)</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Resolved" data-target-table="supervisorTicketsTable">Resolved (<?= $resolvedCount ?>)</button>
      <button class="btn btn-secondary btn-sm" data-status-filter="Closed" data-target-table="supervisorTicketsTable">Closed</button>
    </div>

    <input type="text" class="form-control search-input" placeholder="Search by ticket code, requester, subject..." data-table-search="supervisorTicketsTable">
  </div>

  <?php if (empty($tickets)): ?>
    <div style="text-align: center; color: var(--color-text-muted); padding: 3rem 1rem;">
      <div style="margin-bottom: 0.75rem; color: #94a3b8;"><?= icon('folder_empty', '', 48) ?></div>
      <p style="font-weight: 600; color: var(--color-dark);">No support tickets logged in the system.</p>
      <p style="font-size: 0.875rem;">When staff submit maintenance issues, they will appear here in real time.</p>
    </div>
  <?php else: ?>
    <div class="table-container">
      <table class="table" id="supervisorTicketsTable">
        <thead>
          <tr>
            <th>Ticket Code</th>
            <th>Requester / Office</th>
            <th>Subject & Category</th>
            <th>Priority</th>
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
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
