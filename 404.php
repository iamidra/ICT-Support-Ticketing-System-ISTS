<?php
$pageTitle = 'Page Not Found';
require_once __DIR__ . '/includes/functions.php';
http_response_code(404);
include __DIR__ . '/includes/header.php';
?>

<div class="card" style="max-width: 620px; margin: 3rem auto; text-align: center; padding: 3.5rem 2.25rem;">
  <div style="width: 72px; height: 72px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto;">
    <?= icon('alert', '', 36) ?>
  </div>
  <h1 style="font-size: 2rem; font-weight: 800; color: var(--color-dark); margin-bottom: 0.5rem;">404 - Page Not Found</h1>
  <p style="color: var(--color-text-muted); font-size: 1rem; margin-bottom: 2rem; line-height: 1.6;">
    The page or resource you requested could not be found. It may have been moved, renamed, or is temporarily unavailable.
  </p>
  <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
    <a href="<?= url('index.php') ?>" class="btn btn-primary">
      <?= icon('dashboard', '', 16) ?> Return to Home
    </a>
    <a href="<?= url('track_status.php') ?>" class="btn btn-secondary">
      <?= icon('search', '', 16) ?> Track a Ticket
    </a>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
