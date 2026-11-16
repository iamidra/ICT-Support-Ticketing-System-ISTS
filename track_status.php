<?php
$pageTitle = 'Track Ticket Status';
require_once __DIR__ . '/includes/db_connect.php';

$ticket = null;
$resolutions = [];
$searched = false;
$ticketCode = trim($_GET['ticket_id'] ?? '');

if ($ticketCode !== '') {
    $searched = true;
    try {
        $stmt = $pdo->prepare(
            "SELECT t.*, o.full_name AS officer_name, o.department AS officer_dept
             FROM tickets t
             LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
             WHERE UPPER(t.ticket_code) = UPPER(?)"
        );
        $stmt->execute([$ticketCode]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            $resStmt = $pdo->prepare(
                "SELECT r.*, o.full_name AS officer_name, o.role AS officer_role
                 FROM resolutions r
                 JOIN ict_officers o ON r.officer_id = o.officer_id
                 WHERE r.ticket_id = ?
                 ORDER BY r.created_at DESC"
            );
            $resStmt->execute([$ticket['ticket_id']]);
            $resolutions = $resStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log('Track Status Query Error: ' . $e->getMessage());
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 850px; margin: 0 auto;">
  <div class="card-header no-print">
    <div>
      <h1 class="card-title">Track Ticket Status</h1>
      <p class="card-subtitle">Enter your Ticket Code (e.g. ISTS-000123) to view current progress and resolution history.</p>
    </div>
  </div>

  <form action="<?= url('track_status.php') ?>" method="GET" style="margin-bottom: 2rem;" class="no-print">
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <input type="text" id="ticket_id" name="ticket_id" class="form-control" style="flex: 1; min-width: 250px;" placeholder="Enter Ticket Code (e.g. ISTS-000123)" required value="<?= e($ticketCode) ?>">
      <button type="submit" class="btn btn-primary">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        Check Status
      </button>
    </div>
  </form>

  <?php if ($searched): ?>
    <?php if ($ticket): ?>
      <?php
        // Calculate progress step
        $status = $ticket['status'];
        $stepVal = 1; // Submitted
        if ($ticket['assigned_officer_id']) $stepVal = 2; // Assigned
        if ($status === 'In Progress') $stepVal = 3;
        if ($status === 'Resolved' || $status === 'Closed') $stepVal = 4;
        $progressPct = (($stepVal - 1) / 3) * 100;
      ?>

      <!-- Progress Step Timeline -->
      <div style="background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
          <h3 style="font-size: 0.9rem; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin: 0;">Resolution Progress Timeline</h3>
          <button type="button" class="btn btn-secondary btn-sm no-print" onclick="window.print()">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align: middle;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
            Print Ticket
          </button>
        </div>

        <div class="timeline">
          <div class="timeline-progress" style="width: <?= $progressPct ?>%;"></div>

          <div class="timeline-step <?= $stepVal >= 1 ? 'completed active' : '' ?>">
            <div class="timeline-node">1</div>
            <div class="timeline-label">Submitted</div>
          </div>

          <div class="timeline-step <?= $stepVal >= 2 ? 'completed' : '' ?> <?= $stepVal == 2 ? 'active' : '' ?>">
            <div class="timeline-node">2</div>
            <div class="timeline-label">Assigned</div>
          </div>

          <div class="timeline-step <?= $stepVal >= 3 ? 'completed' : '' ?> <?= $stepVal == 3 ? 'active' : '' ?>">
            <div class="timeline-node">3</div>
            <div class="timeline-label">In Progress</div>
          </div>

          <div class="timeline-step <?= $stepVal >= 4 ? 'completed active' : '' ?>">
            <div class="timeline-node"><?= icon('check', '', 18) ?></div>
            <div class="timeline-label"><?= $status === 'Closed' ? 'Closed' : 'Resolved' ?></div>
          </div>
        </div>
      </div>

      <!-- Ticket Details Card -->
      <div class="detail-grid">
        <div>
          <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem; flex-wrap: wrap;">
            <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--color-dark); margin: 0; font-family: var(--font-mono);"><?= e($ticket['ticket_code']) ?></h2>
            <?= render_status_badge($ticket['status']) ?>
            <?= render_priority_badge($ticket['priority']) ?>
          </div>

          <h3 style="font-size: 1.15rem; color: var(--color-dark); margin-bottom: 1rem;"><?= e($ticket['subject']) ?></h3>

          <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
            <h4 style="font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted); margin-bottom: 0.5rem;">Problem Description</h4>
            <p style="white-space: pre-line; color: var(--color-text-main); margin: 0;"><?= e($ticket['description']) ?></p>
          </div>

          <h3 style="font-size: 1.1rem; color: var(--color-dark); margin-bottom: 1rem;">Resolution Updates & Action Notes</h3>
          <?php if (empty($resolutions)): ?>
            <div style="background: #f8fafc; border: 1px dashed var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; text-align: center; color: var(--color-text-muted);">
              <p style="margin: 0; font-style: italic;">No resolution notes posted yet. Technicians are reviewing your request.</p>
            </div>
          <?php else: ?>
            <?php foreach ($resolutions as $r): ?>
              <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 1rem; border-radius: 0 var(--radius-md) var(--radius-md) 0; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; font-size: 0.825rem; color: var(--color-text-muted); margin-bottom: 0.35rem; flex-wrap: wrap;">
                  <strong><?= e($r['officer_name']) ?> (ICT Support)</strong>
                  <span><?= format_date($r['created_at']) ?></span>
                </div>
                <p style="margin: 0; color: var(--color-text-main); white-space: pre-line; font-size: 0.95rem;"><?= e($r['resolution_text']) ?></p>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div>
          <div style="background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem;">
            <h3 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-bottom: 1rem;">Ticket Overview</h3>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Submitted By</span>
              <strong><?= e($ticket['full_name']) ?></strong>
            </div>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Office / Dept</span>
              <span><?= e($ticket['office']) ?></span>
            </div>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Category</span>
              <span><?= e($ticket['category']) ?></span>
            </div>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Assigned Officer</span>
              <strong><?= $ticket['officer_name'] ? e($ticket['officer_name']) : '<span style="color:var(--color-text-muted);">Unassigned</span>' ?></strong>
            </div>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Date Submitted</span>
              <span><?= format_date($ticket['created_at']) ?></span>
            </div>

            <div style="margin-bottom: 0.85rem;">
              <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Last Updated</span>
              <span><?= format_date($ticket['updated_at']) ?></span>
            </div>
          </div>
        </div>
      </div>

    <?php else: ?>
      <div class="alert alert-warning" role="alert">
        No support ticket found matching ID <strong>"<?= e($ticketCode) ?>"</strong>. Please verify your Ticket Code and try again.
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
