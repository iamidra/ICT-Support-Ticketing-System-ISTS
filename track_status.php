<?php
$pageTitle = 'Track Ticket Status';
require_once __DIR__ . '/includes/db_connect.php';

$ticket = null;
$resolutions = [];
$searched = false;
$ticketCode = strtoupper(trim($_GET['ticket_id'] ?? ''));

if ($ticketCode !== '') {
    // Brute-force & scraping protection: rate limit lookups (30 attempts / 10 minutes)
    $rateLimit = check_rate_limit('ticket_track', null, 30, 10);
    if (!$rateLimit['allowed']) {
        set_flash('danger', 'Too many ticket lookups. For security, tracking is temporarily throttled for ' . (int)$rateLimit['remaining_minutes'] . ' minute(s). Please try again later.');
    } else {
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
                     WHERE r.ticket_id = ? AND (r.is_internal = 0 OR r.is_internal IS NULL)
                     ORDER BY r.created_at DESC"
                );
                $resStmt->execute([$ticket['ticket_id']]);
                $resolutions = $resStmt->fetchAll(PDO::FETCH_ASSOC);

                // Fetch public attachments
                $allAttachments = get_ticket_attachments($ticket['ticket_id']);
                $ticketAttachments = array_filter($allAttachments, function($a) { return empty($a['resolution_id']); });

                $resAttachments = [];
                foreach ($allAttachments as $a) {
                    if (!empty($a['resolution_id'])) {
                        $resAttachments[$a['resolution_id']][] = $a;
                    }
                }
            } else {
                record_failed_attempt('ticket_track', null, 30, 10);
            }
        } catch (Exception $e) {
            error_log('Track Status Query Error: ' . $e->getMessage());
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 850px; margin: 0 auto;">
  <div class="card-header no-print">
    <div>
      <h1 class="card-title"><?= icon('search', '', 24) ?> Track Ticket Status</h1>
      <p class="card-subtitle">Enter your Ticket Code (e.g. ISTS-8X7D-2041) to check live progress, updates, and resolution notes.</p>
    </div>
  </div>

  <form action="<?= url('track_status.php') ?>" method="GET" style="margin-bottom: 2rem;" class="no-print">
    <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
      <input type="text" id="ticket_id" name="ticket_id" class="form-control" style="flex: 1; min-width: 250px; font-family: var(--font-mono);" placeholder="Enter Ticket Code (e.g. ISTS-8X7D-2041)" required value="<?= e($ticketCode) ?>">
      <button type="submit" class="btn btn-primary">
        <?= icon('search', '', 18) ?>
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
            <?= icon('print', '', 14) ?> Print Ticket
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

            <?php if (!empty($ticketAttachments)): ?>
              <div style="margin-top: 1rem; border-top: 1px dashed var(--color-border); padding-top: 0.75rem;">
                <strong style="font-size: 0.825rem; color: var(--color-dark);"><?= icon('paperclip', '', 14) ?> Attached Files:</strong>
                <?= render_attachments_list($ticketAttachments) ?>
              </div>
            <?php endif; ?>
          </div>

          <h3 style="font-size: 1.1rem; color: var(--color-dark); margin-bottom: 1rem;">Resolution Updates &amp; Action Notes</h3>
          <?php if (empty($resolutions)): ?>
            <div style="background: #f8fafc; border: 1px dashed var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; text-align: center; color: var(--color-text-muted); margin-bottom: 1.5rem;">
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

                <?php if (!empty($resAttachments[$r['resolution_id']])): ?>
                  <div style="margin-top: 0.65rem; border-top: 1px dashed rgba(0,0,0,0.1); padding-top: 0.5rem;">
                    <strong style="font-size: 0.775rem; color: var(--color-text-muted);"><?= icon('paperclip', '', 13) ?> Attachment:</strong>
                    <?= render_attachments_list($resAttachments[$r['resolution_id']]) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <?php if (in_array($ticket['status'], ['Resolved', 'Closed'], true)): ?>
            <!-- Customer Satisfaction (CSAT) Feedback Section -->
            <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
              <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <?= icon('star', '', 18) ?> Service Feedback &amp; Rating
              </h3>

              <?php if (empty($ticket['feedback_rating'])): ?>
                <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1rem;">How satisfied are you with the resolution of this issue?</p>
                <form action="<?= url('submit_feedback.php') ?>" method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="ticket_code" value="<?= e($ticket['ticket_code']) ?>">

                  <div style="display: flex; gap: 0.75rem; margin-bottom: 1rem; align-items: center; flex-wrap: wrap;">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                      <label style="display: inline-flex; align-items: center; gap: 0.35rem; cursor: pointer; padding: 0.4rem 0.8rem; border: 1px solid var(--color-border); border-radius: var(--radius-sm); font-size: 0.875rem; background: #f8fafc;">
                        <input type="radio" name="rating" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                        <span style="color: #eab308; font-size: 1rem;">★</span> <?= $i ?> Star<?= $i > 1 ? 's' : '' ?>
                      </label>
                    <?php endfor; ?>
                  </div>

                  <div class="form-group">
                    <label for="feedback_comments" style="font-size: 0.85rem;">Comments / Feedback (Optional):</label>
                    <textarea id="feedback_comments" name="feedback_comments" rows="2" class="form-textarea" placeholder="Share your experience with our ICT technician..."></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary btn-sm">Submit Feedback</button>
                </form>
              <?php else: ?>
                <div style="background: #fefce8; border: 1px solid #fef08a; padding: 0.85rem 1rem; border-radius: var(--radius-sm);">
                  <div style="color: #eab308; font-size: 1.25rem; margin-bottom: 0.25rem;">
                    <?= str_repeat('★', (int)$ticket['feedback_rating']) . str_repeat('☆', 5 - (int)$ticket['feedback_rating']) ?>
                    <span style="font-size: 0.9rem; color: #713f12; font-weight: 700; margin-left: 0.5rem;"><?= (int)$ticket['feedback_rating'] ?> / 5 Stars</span>
                  </div>
                  <?php if (!empty($ticket['feedback_comments'])): ?>
                    <p style="margin: 0; font-size: 0.875rem; color: #713f12; font-style: italic;">"<?= e($ticket['feedback_comments']) ?>"</p>
                  <?php endif; ?>
                  <span style="font-size: 0.775rem; color: #a16207; display: block; margin-top: 0.4rem;">✓ Thank you for rating our service!</span>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($ticket['status'] === 'Resolved'): ?>
              <!-- Reopen Ticket Section -->
              <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.4rem;">Still experiencing the issue?</h4>
                <p style="font-size: 0.85rem; color: var(--color-text-muted); margin-bottom: 0.75rem;">If the problem was not fully resolved, you can reopen this ticket to notify the ICT technician.</p>
                <form action="<?= url('reopen_ticket.php') ?>" method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="ticket_code" value="<?= e($ticket['ticket_code']) ?>">
                  <div class="form-group" style="margin-bottom: 0.75rem;">
                    <input type="text" name="reopen_reason" class="form-control" placeholder="Briefly describe why the issue persists..." required>
                  </div>
                  <button type="submit" class="btn btn-secondary btn-sm" data-confirm="Are you sure you want to reopen this ticket?">
                    Reopen Ticket &rarr;
                  </button>
                </form>
              </div>
            <?php endif; ?>
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
      <div class="alert alert-warning" role="alert" style="margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; gap: 0.65rem;">
          <?= icon('alert', 'alert-icon', 20) ?>
          <span>No support ticket found matching code <strong>"<?= e($ticketCode) ?>"</strong>. Please verify your Ticket Code and try again.</span>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
