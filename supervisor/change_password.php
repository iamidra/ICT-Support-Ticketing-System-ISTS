<?php
require_once __DIR__ . '/../includes/auth_supervisor.php';
$pageTitle = 'Change Password';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title">Change Supervisor Password</h1>
      <p class="card-subtitle">Update your administrator account login credentials</p>
    </div>
  </div>

  <form action="<?= url('supervisor/process_change_password.php') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="current_password"><?= icon('key', '', 16) ?> Current Password <span style="color:red">*</span></label>
      <?= password_input('current_password', 'current_password', '••••••••', true) ?>
    </div>

    <div class="form-group">
      <label for="new_password"><?= icon('key', '', 16) ?> New Password <span style="color:red">*</span></label>
      <?= password_input('new_password', 'new_password', 'Minimum 8 characters', true, 8) ?>
      <div class="form-text">Choose a strong password with a mix of letters, numbers, and symbols.</div>
    </div>

    <div class="form-group">
      <label for="confirm_password"><?= icon('key', '', 16) ?> Confirm New Password <span style="color:red">*</span></label>
      <?= password_input('confirm_password', 'confirm_password', '••••••••', true, 8) ?>
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
      <button type="submit" class="btn btn-primary">Update Password</button>
      <a href="<?= url('supervisor/dashboard.php') ?>" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
