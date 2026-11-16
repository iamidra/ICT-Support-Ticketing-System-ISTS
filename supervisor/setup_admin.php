<?php
// One-time setup utility to seed initial supervisor account
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Initial Setup';
$alreadyExists = false;

try {
    $check = (int)$pdo->query("SELECT COUNT(*) FROM ict_officers WHERE role = 'supervisor'")->fetchColumn();
    if ($check > 0) {
        $alreadyExists = true;
    }
} catch (Exception $e) {
    $alreadyExists = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($alreadyExists) {
        set_flash('danger', 'Initial setup is locked because a supervisor administrator already exists.');
        header('Location: ' . url('login.php'));
        exit;
    }

    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Invalid security token. Please try submitting the setup form again.');
        header('Location: ' . url('supervisor/setup_admin.php'));
        exit;
    }

    $fullName   = trim($_POST['full_name'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = (string)($_POST['password'] ?? '');
    $role       = 'supervisor';
    $department = trim($_POST['department'] ?? 'ICT Management');

    if (empty($fullName) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please provide a valid full name and email address.');
        header('Location: ' . url('supervisor/setup_admin.php'));
        exit;
    }

    if (strlen($password) < 8) {
        set_flash('danger', 'Password must be at least 8 characters long for production security.');
        header('Location: ' . url('supervisor/setup_admin.php'));
        exit;
    }

    try {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            "INSERT INTO ict_officers (full_name, email, password, role, department, created_at) VALUES (?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$fullName, $email, $hash, $role, $department]);

        set_flash('success', 'Initial Supervisor account created successfully! You may now sign in.');
        header('Location: ' . url('login.php'));
        exit;
    } catch (Exception $e) {
        error_log('Setup Admin Error: ' . $e->getMessage());
        set_flash('danger', 'An error occurred while creating administrator account. Check your database setup.');
        header('Location: ' . url('supervisor/setup_admin.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-wrapper" style="margin-top: 2rem;">
  <div class="card" style="box-shadow: var(--shadow-lg); border-radius: var(--radius-lg);">
    <div class="card-header" style="text-align: center; flex-direction: column;">
      <div class="brand-icon" style="margin: 0 auto 0.75rem auto; width: 52px; height: 52px; font-size: 1.5rem; background: linear-gradient(135deg, #0f172a 0%, #2563eb 100%);">I</div>
      <h1 class="card-title">Initial Supervisor Setup</h1>
      <p class="card-subtitle">Create the root supervisor administrator account</p>
    </div>

    <?php if ($alreadyExists): ?>
      <div class="alert alert-warning" style="margin-top: 1.25rem;">
        <strong>Setup Completed:</strong> A supervisor administrator account already exists. For production security, this utility cannot be run again.
      </div>
      <div style="text-align: center; margin-top: 1.5rem;">
        <a href="<?= url('login.php') ?>" class="btn btn-primary">Go to Login &rarr;</a>
      </div>
    <?php else: ?>
      <form action="<?= url('supervisor/setup_admin.php') ?>" method="POST" style="margin-top: 1.25rem;">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="full_name">Administrator Full Name <span style="color:red">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Lead Administrator" required>
        </div>

        <div class="form-group">
          <label for="email">Admin Email Address <span style="color:red">*</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="admin@ists.com" required>
        </div>

        <div class="form-group">
          <label for="department">Department</label>
          <input type="text" id="department" name="department" class="form-control" value="ICT Management">
        </div>

        <div class="form-group">
          <label for="password"><?= icon('key', '', 16) ?> Admin Password <span style="color:red">*</span></label>
          <?= password_input('password', 'password', 'Minimum 8 characters', true, 8) ?>
          <div class="form-text">Choose a strong, secure password for this root supervisor account.</div>
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem; padding: 0.75rem;">
          Create Supervisor Account &rarr;
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
