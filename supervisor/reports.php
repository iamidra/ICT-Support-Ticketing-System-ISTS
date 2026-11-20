<?php
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Analytics & Reports';

$totalTickets = (int)($pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn() ?: 0);

$byStatus = $pdo->query("SELECT status, COUNT(*) AS total FROM tickets GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$byCategory = $pdo->query("SELECT category, COUNT(*) AS total FROM tickets GROUP BY category ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);

$officerPerformance = $pdo->query(
    "SELECT o.full_name, COUNT(t.ticket_id) AS total_assigned,
            SUM(CASE WHEN t.status IN ('Resolved', 'Closed') THEN 1 ELSE 0 END) AS total_resolved
     FROM ict_officers o
     LEFT JOIN tickets t ON o.officer_id = t.assigned_officer_id
     WHERE o.role = 'officer'
     GROUP BY o.officer_id, o.full_name
     ORDER BY total_assigned DESC"
)->fetchAll(PDO::FETCH_ASSOC);

// Data formatting for Chart.js
$officerNames = array_column($officerPerformance, 'full_name');
$officerAssigned = array_map(function($op) { return (int)$op['total_assigned']; }, $officerPerformance);
$officerResolved = array_map(function($op) { return (int)$op['total_resolved']; }, $officerPerformance);

$statusLabels = array_column($byStatus, 'status');
$statusTotals = array_map(function($s) { return (int)$s['total']; }, $byStatus);

$catLabels = array_column($byCategory, 'category');
$catTotals = array_map(function($c) { return (int)$c['total']; }, $byCategory);

// CSAT and SLA statistics
try {
    $csatStats = $pdo->query(
        "SELECT COUNT(feedback_rating) AS total_rated, AVG(feedback_rating) AS avg_rating
         FROM tickets WHERE feedback_rating IS NOT NULL"
    )->fetch(PDO::FETCH_ASSOC);
    $avgRating = $csatStats['avg_rating'] ? round((float)$csatStats['avg_rating'], 1) : 0;
    $totalRated = (int)($csatStats['total_rated'] ?? 0);

    $slaStats = $pdo->query(
        "SELECT 
            SUM(CASE WHEN due_date IS NOT NULL AND status IN ('Resolved', 'Closed') AND (resolved_at <= due_date OR (resolved_at IS NULL AND updated_at <= due_date)) THEN 1 ELSE 0 END) AS met_sla,
            SUM(CASE WHEN due_date IS NOT NULL AND ((status IN ('Resolved', 'Closed') AND resolved_at > due_date) OR (status NOT IN ('Resolved', 'Closed') AND NOW() > due_date)) THEN 1 ELSE 0 END) AS breached_sla
         FROM tickets WHERE due_date IS NOT NULL"
    )->fetch(PDO::FETCH_ASSOC);
    $metSla = (int)($slaStats['met_sla'] ?? 0);
    $breachedSla = (int)($slaStats['breached_sla'] ?? 0);
    $totalTrackedSla = $metSla + $breachedSla;
    $slaCompliancePct = $totalTrackedSla > 0 ? round(($metSla / $totalTrackedSla) * 100) : 100;
} catch (Throwable $e) {
    $avgRating = 0;
    $totalRated = 0;
    $metSla = 0;
    $breachedSla = 0;
    $slaCompliancePct = 100;
}

$jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<!-- Load Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><?= icon('chart', '', 22) ?> System Analytics &amp; Performance Reports</h1>
      <p class="card-subtitle">Visual performance graphs, officer resolution metrics, and ticket status breakdown.</p>
    </div>
    <div>
      <a href="<?= url('supervisor/reports_export.php') ?>" class="btn btn-primary">
        <?= icon('document', '', 16) ?>
        Export Full CSV Report
      </a>
    </div>
  </div>

  <!-- Key Metrics Summary Row: CSAT & SLA -->
  <div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
      <div>
        <div class="stat-value"><?= $avgRating > 0 ? $avgRating . ' / 5.0' : 'N/A' ?></div>
        <div class="stat-label">CSAT Satisfaction (<?= $totalRated ?> ratings)</div>
      </div>
      <div class="stat-icon-wrapper stat-icon-amber"><?= icon('star', '', 26) ?></div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-value"><?= $slaCompliancePct ?>%</div>
        <div class="stat-label">SLA Compliance Rate (<?= $metSla ?> met)</div>
      </div>
      <div class="stat-icon-wrapper stat-icon-green"><?= icon('check_circle', '', 26) ?></div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-value"><?= $breachedSla ?></div>
        <div class="stat-label">SLA Breached Tickets</div>
      </div>
      <div class="stat-icon-wrapper stat-icon-purple"><?= icon('clock', '', 26) ?></div>
    </div>

    <div class="stat-card">
      <div>
        <div class="stat-value"><?= $totalTickets ?></div>
        <div class="stat-label">Total System Requests</div>
      </div>
      <div class="stat-icon-wrapper stat-icon-blue"><?= icon('ticket', '', 26) ?></div>
    </div>
  </div>

  <!-- Interactive Charts Grid -->
  <div class="form-row" style="margin-bottom: 2rem;">
    <!-- Officer Performance Bar Chart -->
    <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm);">
      <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Officer Performance & Workload</h2>
      <div style="position: relative; min-height: 280px;">
        <canvas id="officerChart"></canvas>
      </div>
    </div>

    <!-- Status Doughnut Chart -->
    <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; box-shadow: var(--shadow-sm);">
      <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Ticket Status Distribution</h2>
      <div style="position: relative; min-height: 280px; display: flex; align-items: center; justify-content: center;">
        <canvas id="statusChart" style="max-height: 260px;"></canvas>
      </div>
    </div>
  </div>

  <!-- Category Breakdown Bar Chart -->
  <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2.5rem; box-shadow: var(--shadow-sm);">
    <h2 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Tickets by Support Category</h2>
    <div style="position: relative; min-height: 250px;">
      <canvas id="categoryChart"></canvas>
    </div>
  </div>

  <!-- Officer Productivity Detailed Table -->
  <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Detailed Officer Evaluation Data</h2>
  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Officer Name</th>
          <th>Total Assigned</th>
          <th>Resolved & Closed</th>
          <th>Pending / In Progress</th>
          <th>Resolution Success Rate</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($officerPerformance)): ?>
          <tr>
            <td colspan="5" style="text-align: center; color: var(--color-text-muted); padding: 2rem;">No officer performance records available yet.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($officerPerformance as $op): ?>
            <?php
              $assigned = (int)$op['total_assigned'];
              $resolved = (int)$op['total_resolved'];
              $active = $assigned - $resolved;
              $rate = $assigned > 0 ? round(($resolved / $assigned) * 100) : 0;
            ?>
            <tr>
              <td><strong><?= e($op['full_name']) ?></strong></td>
              <td><strong><?= $assigned ?></strong></td>
              <td><span style="color: #10b981; font-weight: 700;"><?= $resolved ?></span></td>
              <td><span style="color: #f59e0b; font-weight: 600;"><?= $active ?></span></td>
              <td>
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                  <strong style="min-width: 40px;"><?= $rate ?>%</strong>
                  <div style="background: #f1f5f9; flex: 1; height: 8px; border-radius: 4px; overflow: hidden; max-width: 120px;">
                    <div style="background: #10b981; width: <?= $rate ?>%; height: 100%;"></div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Officer Performance Bar Chart
  const ctxOfficer = document.getElementById('officerChart').getContext('2d');
  new Chart(ctxOfficer, {
    type: 'bar',
    data: {
      labels: <?= json_encode($officerNames, $jsonFlags) ?>,
      datasets: [
        {
          label: 'Total Assigned',
          data: <?= json_encode($officerAssigned, $jsonFlags) ?>,
          backgroundColor: '#3b82f6',
          borderRadius: 4
        },
        {
          label: 'Resolved & Closed',
          data: <?= json_encode($officerResolved, $jsonFlags) ?>,
          backgroundColor: '#10b981',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'top' } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });

  // 2. Status Distribution Doughnut Chart
  const ctxStatus = document.getElementById('statusChart').getContext('2d');
  new Chart(ctxStatus, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($statusLabels, $jsonFlags) ?>,
      datasets: [{
        data: <?= json_encode($statusTotals, $jsonFlags) ?>,
        backgroundColor: ['#f59e0b', '#3b82f6', '#10b981', '#64748b']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' } }
    }
  });

  // 3. Category Breakdown Bar Chart
  const ctxCategory = document.getElementById('categoryChart').getContext('2d');
  new Chart(ctxCategory, {
    type: 'bar',
    data: {
      labels: <?= json_encode($catLabels, $jsonFlags) ?>,
      datasets: [{
        label: 'Tickets Logged',
        data: <?= json_encode($catTotals, $jsonFlags) ?>,
        backgroundColor: '#8b5cf6',
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
  });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
