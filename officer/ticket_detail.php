<?php
require_once __DIR__ . '/../includes/auth_officer.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Ticket Detail & Resolution';
$ticketId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT t.*, o.full_name AS officer_name
     FROM tickets t
     LEFT JOIN ict_officers o ON t.assigned_officer_id = o.officer_id
     WHERE t.ticket_id = ?"
);
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    set_flash('danger', 'Ticket not found.');
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

// ACL Check: Ensure ticket is assigned to THIS officer
if ((int)$ticket['assigned_officer_id'] !== (int)$_SESSION['officer_id']) {
    set_flash('danger', 'Access Denied: You are not authorized to view or manage tickets assigned to another officer.');
    header('Location: ' . url('officer/dashboard.php'));
    exit;
}

// Fetch resolutions
$resStmt = $pdo->prepare(
    "SELECT r.*, o.full_name AS officer_name, o.role AS officer_role
     FROM resolutions r
     JOIN ict_officers o ON r.officer_id = o.officer_id
     WHERE r.ticket_id = ?
     ORDER BY r.created_at DESC"
);
$resStmt->execute([$ticketId]);
$resolutions = $resStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch ticket attachments
$allAttachments = get_ticket_attachments($ticketId);
$ticketAttachments = array_filter($allAttachments, function($a) { return empty($a['resolution_id']); });

// Group resolution attachments by resolution_id
$resAttachments = [];
foreach ($allAttachments as $a) {
    if (!empty($a['resolution_id'])) {
        $resAttachments[$a['resolution_id']][] = $a;
    }
}

// Fetch audit history
$history = get_ticket_history($ticketId);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/officer_nav.php';
?>

<div style="margin-bottom: 1rem;">
  <a href="<?= url('officer/dashboard.php') ?>" class="btn btn-secondary btn-sm">
    <?= icon('arrow_left', '', 14) ?> Back to My Queue
  </a>
</div>

<div class="card">
  <div class="card-header">
    <div>
      <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
        <h1 class="card-title"><?= e($ticket['ticket_code']) ?> — <?= e($ticket['subject']) ?></h1>
        <?= render_status_badge($ticket['status']) ?>
        <?= render_priority_badge($ticket['priority']) ?>
        <?= render_sla_badge($ticket['due_date'], $ticket['status']) ?>
      </div>
      <p class="card-subtitle">Logged by <strong><?= e($ticket['full_name']) ?></strong> on <?= format_date($ticket['created_at']) ?></p>
    </div>
  </div>

  <div class="detail-grid">
    <div>
      <div style="background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem;">
        <h3 style="font-size: 0.85rem; text-transform: uppercase; color: var(--color-text-muted); margin-bottom: 0.5rem;">Problem Description</h3>
        <p style="white-space: pre-line; color: var(--color-text-main); margin: 0; font-size: 0.95rem;"><?= e($ticket['description']) ?></p>
        
        <?php if (!empty($ticketAttachments)): ?>
          <div style="margin-top: 1rem; border-top: 1px dashed var(--color-border); padding-top: 0.75rem;">
            <strong style="font-size: 0.825rem; color: var(--color-dark);"><?= icon('paperclip', '', 14) ?> Attached Files:</strong>
            <?= render_attachments_list($ticketAttachments) ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Update Status Card -->
      <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Update Ticket Status</h3>
        <form action="<?= url('officer/update_status.php') ?>" method="POST" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
          <?= csrf_field() ?>
          <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
          <select name="status" class="form-select" style="max-width: 240px;">
            <option value="Pending" <?= $ticket['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="In Progress" <?= $ticket['status'] === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="Resolved" <?= $ticket['status'] === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
            <option value="Closed" <?= $ticket['status'] === 'Closed' ? 'selected' : '' ?>>Closed</option>
          </select>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
      </div>

      <!-- Record Resolution Card -->
      <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Record Technical Resolution / Action Note</h3>
        <form action="<?= url('officer/resolve_ticket.php') ?>" method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
          <div class="form-group">
            <textarea name="resolution_text" rows="4" class="form-textarea" placeholder="Describe diagnosis, troubleshooting steps taken, or final resolution notes..." required></textarea>
          </div>

          <div class="form-group" style="background: #f8fafc; border: 1px dashed var(--color-border); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 600; font-size: 0.875rem; margin: 0; cursor: pointer;">
              <input type="checkbox" name="is_internal" value="1" style="width: 16px; height: 16px;">
              <span><?= icon('lock', '', 15) ?> Internal Staff Note <span style="font-weight: normal; color: var(--color-text-muted); font-size: 0.8rem;">(Visible only to ICT staff, hidden from requester)</span></span>
            </label>
          </div>

          <div class="form-group">
            <label for="res_attachment" style="font-size: 0.85rem; font-weight: 600; color: var(--color-text-main);"><?= icon('paperclip', '', 14) ?> Attach Supporting File / Screenshot <span style="font-weight: normal; color: var(--color-text-muted);">(Optional)</span></label>
            <input type="file" id="res_attachment" name="attachment" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.txt,.csv,.zip,.log">
          </div>

          <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; margin-top: 1rem;">
            <label style="font-size: 0.85rem; font-weight: 600; color: var(--color-text-main); margin-bottom: 0;">Set Status to:</label>
            <select name="target_status" class="form-select" style="max-width: 200px;">
              <option value="Resolved" selected>Resolved (Work Finished)</option>
              <option value="In Progress">In Progress (Work Ongoing)</option>
            </select>
            <button type="submit" class="btn btn-primary">Save Note</button>
          </div>
        </form>
      </div>

      <!-- Resolution History -->
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem;">Resolution Logs & Work History</h3>
      <?php if (empty($resolutions)): ?>
        <p style="color: var(--color-text-muted); font-style: italic;">No resolution notes logged yet.</p>
      <?php else: ?>
        <?php foreach ($resolutions as $r): ?>
          <?php $isInternalNote = !empty($r['is_internal']); ?>
          <div style="background: <?= $isInternalNote ? '#fffbeb' : (($r['officer_role'] ?? '') === 'supervisor' ? '#f8fafc' : '#f0fdf4') ?>; border-left: 4px solid <?= $isInternalNote ? '#f59e0b' : (($r['officer_role'] ?? '') === 'supervisor' ? '#3b82f6' : '#10b981') ?>; padding: 1rem; border-radius: 0 var(--radius-md) var(--radius-md) 0; margin-bottom: 1rem;">
            <div style="display: flex; justify-content: space-between; font-size: 0.825rem; color: var(--color-text-muted); margin-bottom: 0.35rem; flex-wrap: wrap; gap: 0.5rem;">
              <div>
                <strong>Logged by <?= e($r['officer_name']) ?> <?= ($r['officer_role'] ?? '') === 'supervisor' ? '(Supervisor)' : '(Officer)' ?></strong>
                <?php if ($isInternalNote): ?>
                  <span class="badge" style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; font-size: 0.75rem; margin-left: 0.4rem;"><?= icon('lock', '', 12) ?> Internal Only</span>
                <?php endif; ?>
              </div>
              <span><?= format_date($r['created_at']) ?></span>
            </div>
            <p style="margin: 0; color: var(--color-text-main); font-size: 0.925rem; white-space: pre-line;"><?= e($r['resolution_text']) ?></p>

            <?php if (!empty($resAttachments[$r['resolution_id']])): ?>
              <div style="margin-top: 0.65rem; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 0.5rem;">
                <strong style="font-size: 0.775rem; color: var(--color-text-muted);"><?= icon('paperclip', '', 13) ?> Attachment:</strong>
                <?= render_attachments_list($resAttachments[$r['resolution_id']]) ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div>
      <div style="background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
        <h3 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted); margin-bottom: 1rem;">Requester Contact Details</h3>

        <div style="margin-bottom: 0.85rem;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Full Name</span>
          <strong><?= e($ticket['full_name']) ?></strong>
        </div>

        <?php if (!empty($ticket['email'])): ?>
          <div style="margin-bottom: 0.85rem;">
            <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Email</span>
            <a href="mailto:<?= e($ticket['email']) ?>" style="font-weight: 500;"><?= e($ticket['email']) ?></a>
          </div>
        <?php endif; ?>

        <div style="margin-bottom: 0.85rem;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Office / Dept</span>
          <span><?= e($ticket['office']) ?></span>
        </div>

        <div style="margin-bottom: 0.85rem;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Phone / Contact</span>
          <a href="tel:<?= e($ticket['phone']) ?>" style="font-weight: 600;"><?= e($ticket['phone']) ?></a>
        </div>

        <div style="margin-bottom: 0.85rem;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">Category</span>
          <span><?= e($ticket['category']) ?></span>
        </div>

        <div style="margin-bottom: 0.85rem;">
          <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block;">SLA Due Target</span>
          <span><?= $ticket['due_date'] ? format_date($ticket['due_date']) : 'N/A' ?></span>
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

      <!-- Audit / Activity History Card -->
      <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem;">
        <h3 style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.4rem;">
          <?= icon('history', '', 16) ?> Activity &amp; Audit Trail
        </h3>
        <?php if (empty($history)): ?>
          <p style="color: var(--color-text-muted); font-size: 0.825rem; font-style: italic; margin: 0;">No activity logged yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <?php foreach ($history as $h): ?>
              <div style="border-left: 2px solid var(--color-border); padding-left: 0.75rem; font-size: 0.825rem;">
                <div style="display: flex; justify-content: space-between; color: var(--color-text-muted); font-size: 0.75rem; margin-bottom: 0.2rem;">
                  <strong><?= e($h['actor_name']) ?> (<?= e($h['actor_role']) ?>)</strong>
                  <span><?= format_date($h['created_at']) ?></span>
                </div>
                <div style="color: var(--color-text-main);"><?= e($h['details']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
