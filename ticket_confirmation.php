<?php
$pageTitle = 'Ticket Submitted Confirmation';
require_once __DIR__ . '/includes/db_connect.php';

$ticketCode = trim($_GET['ticket_id'] ?? '');

$trackUrl = url('track_status.php?ticket_id=' . urlencode($ticketCode));

include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 650px; margin: 2rem auto;">
  <div class="receipt-card">
    <div class="receipt-icon-wrapper">
      <?= icon('check_circle', '', 36) ?>
    </div>
    
    <h1 style="font-size: 1.75rem; font-weight: 800; color: var(--color-dark); margin-bottom: 0.5rem;">Ticket Logged Successfully!</h1>
    <p style="color: var(--color-text-muted); line-height: 1.6; max-width: 500px; margin: 0 auto;">Your ICT support request has been registered in our queue. Please save your unique Ticket Code below to track resolution progress.</p>

    <div style="margin: 1.75rem 0;">
      <span style="font-size: 0.8rem; font-weight: 700; color: var(--color-text-muted); text-transform: uppercase; letter-spacing: 0.08em; display: block; margin-bottom: 0.35rem;">Your Secure Ticket Code</span>
      <div id="ticketCode" class="ticket-code-display"><?= e($ticketCode ?: 'N/A') ?></div>
    </div>

    <div style="display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem;">
      <button type="button" class="btn btn-secondary" data-copy-target="#ticketCode">
        <?= icon('copy', '', 16) ?>
        Copy Code
      </button>

      <a href="<?= $trackUrl ?>" class="btn btn-primary">
        <?= icon('search', '', 16) ?>
        Track Ticket Status &rarr;
      </a>

      <button type="button" class="btn btn-secondary" onclick="window.print()">
        <?= icon('print', '', 16) ?>
        Print Receipt
      </button>
    </div>

    <div style="margin-top: 2rem; border-top: 1px solid var(--color-border); padding-top: 1.25rem; display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; flex-wrap: wrap; gap: 0.5rem;">
      <a href="<?= url('ticket_form.php') ?>" style="color: var(--color-text-muted); font-weight: 600;">
        <?= icon('arrow_left', '', 14) ?> Submit Another Ticket
      </a>
      <a href="<?= url('index.php') ?>" style="color: var(--color-text-muted); font-weight: 600;">
        Back to Home
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
