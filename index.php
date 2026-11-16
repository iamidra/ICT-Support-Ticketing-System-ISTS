<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/db_connect.php';

// Fetch public stats
try {
    $totalTickets    = (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn() ?: 0;
    $resolvedTickets = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status IN ('Resolved', 'Closed')")->fetchColumn() ?: 0;
    $pendingTickets  = (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status = 'Pending'")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $totalTickets = $resolvedTickets = $pendingTickets = 0;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<div class="hero-banner">
  <div class="hero-status-pill">
    <span class="pulse-dot"></span>
    Helpdesk Operational &bull; ISTS Enterprise Support
  </div>
  
  <h1 class="hero-title">ICT Support Desk &amp; Technical Portal</h1>
  <p class="hero-lead">Fast, transparent, and responsive IT technical assistance. Submit equipment faults, report network issues, request software assistance, and track ticket resolutions in real time.</p>
  
  <div class="hero-actions">
    <a href="<?= url('ticket_form.php') ?>" class="btn btn-primary">
      <?= icon('plus', '', 18) ?>
      Submit Support Ticket
    </a>
    <a href="<?= url('track_status.php') ?>" class="btn btn-secondary">
      <?= icon('search', '', 18) ?>
      Track Existing Ticket
    </a>
  </div>
</div>

<!-- Key Performance Metrics Grid (Clean SVG Icons, Zero Emojis) -->
<div class="stats-grid">
  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $totalTickets ?></div>
      <div class="stat-label">Total Tickets Submitted</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-blue">
      <?= icon('document', '', 26) ?>
    </div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $resolvedTickets ?></div>
      <div class="stat-label">Tickets Resolved</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-green">
      <?= icon('check_circle', '', 26) ?>
    </div>
  </div>

  <div class="stat-card">
    <div>
      <div class="stat-value"><?= $pendingTickets ?></div>
      <div class="stat-label">Pending Action</div>
    </div>
    <div class="stat-icon-wrapper stat-icon-amber">
      <?= icon('clock', '', 26) ?>
    </div>
  </div>
</div>

<!-- How It Works 3-Step Process -->
<div style="margin: 2.75rem 0 2rem 0;">
  <div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--color-dark); margin-bottom: 0.35rem;">How Technical Support Works</h2>
    <p style="color: var(--color-text-muted); font-size: 0.95rem;">Three simple steps from issue reporting to verified technician resolution</p>
  </div>

  <div class="how-it-works-grid">
    <div class="step-card">
      <div class="step-badge">
        <?= icon('document', '', 20) ?>
      </div>
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.5rem;">1. Submit Request</h3>
      <p style="font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.6;">Fill out the public ticket form with your department, contact details, and issue summary. No login needed.</p>
    </div>

    <div class="step-card">
      <div class="step-badge">
        <?= icon('users', '', 20) ?>
      </div>
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.5rem;">2. Technician Assigned</h3>
      <p style="font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.6;">Our ICT supervisor reviews the ticket and assigns an active specialist technician based on issue category.</p>
    </div>

    <div class="step-card">
      <div class="step-badge">
        <?= icon('check_circle', '', 20) ?>
      </div>
      <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.5rem;">3. Resolution &amp; Tracking</h3>
      <p style="font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.6;">Track the progress timeline using your unique Ticket Code and review technician notes and completion logs.</p>
    </div>
  </div>
</div>

<!-- Portal Access Row -->
<div class="form-row">
  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title"><?= icon('headset', '', 20) ?> Staff Self-Service Desk</h2>
        <p class="card-subtitle">Immediate technical assistance for all staff &amp; offices</p>
      </div>
    </div>
    <p style="margin-bottom: 1.5rem; color: var(--color-text-muted); line-height: 1.6;">Experiencing equipment issues with workstations, printers, email access, or Wi-Fi connectivity? Log a support ticket and track its progress anytime using your unique Ticket Code.</p>
    <div style="display: flex; gap: 0.85rem; flex-wrap: wrap;">
      <a href="<?= url('ticket_form.php') ?>" class="btn btn-primary">
        <?= icon('plus', '', 16) ?> Submit Support Ticket
      </a>
      <a href="<?= url('track_status.php') ?>" class="btn btn-secondary">
        <?= icon('search', '', 16) ?> Check Progress
      </a>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h2 class="card-title"><?= icon('shield', '', 20) ?> Authorized Staff Login</h2>
        <p class="card-subtitle">Restricted to ICT Officers and Department Administrators</p>
      </div>
    </div>
    <p style="margin-bottom: 1.5rem; color: var(--color-text-muted); line-height: 1.6;">Assigned ICT Support Officers and Supervisors can sign in to view assigned queues, update ticket progress, post resolution notes, and export comprehensive analytics reports.</p>
    <div style="display: flex; gap: 0.85rem; flex-wrap: wrap;">
      <a href="<?= url('login.php') ?>" class="btn btn-dark">
        <?= icon('key', '', 16) ?> Sign In to Dashboard &rarr;
      </a>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
