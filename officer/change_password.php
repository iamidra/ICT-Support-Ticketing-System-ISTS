<?php
require_once __DIR__ . '/../includes/auth_officer.php';
$pageTitle = 'Change Password';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/officer_nav.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
  <div class="card-header">
    <div>
      <h1 class="card-title">Change Password</h1>
      <p class="card-subtitle">Update your officer account security password</p>
    </div>
  </div>

  <form action="<?= url('officer/process_change_password.php') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="current_password"><?= icon('key', '', 16) ?> Current Password <span style="color:red">*</span></label>
      <?= password_input('current_password', 'current_password', '••••••••', true) ?>
    </div>

    <div class="form-group">
      <label for="new_password"><?= icon('key', '', 16) ?> New Password <span style="color:red">*</span></label>
      <?= password_input('new_password', 'new_password', 'Minimum 8 characters', true, 8) ?>
      <div class="form-text">Must be at least 8 characters long.</div>
    </div>

    <div class="form-group">
      <label for="confirm_password"><?= icon('key', '', 16) ?> Confirm New Password <span style="color:red">*</span></label>
      <?= password_input('confirm_password', 'confirm_password', '••••••••', true, 8) ?>
    </div>

    <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
      <button type="submit" class="btn btn-primary">Update Password</button>
      <a href="<?= url('officer/dashboard.php') ?>" class="btn btn-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
