<?php
$pageTitle = 'Account Login';
require_once __DIR__ . '/includes/db_connect.php';

// Redirect if already logged in
if (isset($_SESSION['officer_id'])) {
    session_write_close();
    if ($_SESSION['officer_role'] === 'supervisor') {
        header('Location: ' . url('supervisor/dashboard.php'));
        exit;
    } else {
        header('Location: ' . url('officer/dashboard.php'));
        exit;
    }
}

// Check if initial admin has been created
$hasAdmin = false;
try {
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM ict_officers WHERE role = 'supervisor'")->fetchColumn();
    $hasAdmin = ($adminCount > 0);
} catch (Exception $e) {
    $hasAdmin = false;
}

// Check login rate limit
$rateLimit = check_rate_limit('login');
$isLocked = !$rateLimit['allowed'];

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper" style="margin-top: 2rem;">
  <div class="card" style="box-shadow: var(--shadow-lg); border-radius: var(--radius-lg);">
    <div class="card-header" style="text-align: center; justify-content: center; flex-direction: column; border-bottom: none; padding-bottom: 0;">
      <div class="brand-icon" style="margin: 0 auto 0.75rem auto; width: 52px; height: 52px; font-size: 1.5rem; background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);">I</div>
      <h1 class="card-title" style="font-size: 1.5rem; font-weight: 800;">Portal Sign In</h1>
      <p class="card-subtitle">Enter your registered ICT staff or supervisor email to access your dashboard</p>
    </div>

    <?php if (!$hasAdmin): ?>
      <div class="alert alert-warning" style="margin-top: 1.25rem;">
        <strong>Initial Setup Required:</strong> No supervisor account exists yet. Please complete the <a href="<?= url('supervisor/setup_admin.php') ?>" style="font-weight: 700; text-decoration: underline;">Initial Setup Utility</a> to create the administrator account.
      </div>
    <?php endif; ?>

    <?php if ($isLocked): ?>
      <div class="alert alert-danger" style="margin-top: 1.25rem;">
        Too many failed login attempts. For security, sign-in is temporarily locked for <strong><?= (int)$rateLimit['remaining_minutes'] ?> minute(s)</strong>. Please try again later.
      </div>
    <?php endif; ?>

    <form action="<?= url('process_login.php') ?>" method="POST" style="margin-top: 1.5rem;">
      <?= csrf_field() ?>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" class="form-control" placeholder="name@ists.com" required autofocus <?= $isLocked ? 'disabled' : '' ?>>
      </div>

      <div class="form-group">
        <label for="password"><?= icon('key', '', 16) ?> Password</label>
        <?= password_input('password', 'password', '••••••••', true, null, $isLocked ? 'disabled' : '') ?>
      </div>

      <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.75rem; padding: 0.75rem; font-size: 1rem;" <?= $isLocked ? 'disabled' : '' ?>>
        Sign In to My Dashboard &rarr;
      </button>
    </form>

    <div style="margin-top: 2rem; border-top: 1px solid var(--color-border); padding-top: 1.25rem; font-size: 0.85rem; color: var(--color-text-muted); text-align: center;">
      <p style="margin-bottom: 0.5rem;">Public user? <a href="<?= url('ticket_form.php') ?>">Submit a support ticket</a> without login.</p>
      <p style="margin: 0;"><a href="<?= url('track_status.php') ?>">Track your existing support ticket</a></p>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
