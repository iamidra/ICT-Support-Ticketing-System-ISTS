<?php
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Manage Officers & Staff';

$officers = $pdo->query(
    "SELECT * FROM ict_officers ORDER BY role DESC, full_name ASC"
)->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><?= icon('users', '', 22) ?> ICT Staff Directory & Accounts</h1>
      <p class="card-subtitle">Manage ICT Support Officers and Supervisor accounts, update profiles, reset passwords, or adjust permissions.</p>
    </div>
  </div>

  <div class="table-container" style="margin-bottom: 2.5rem;">
    <table class="table">
      <thead>
        <tr>
          <th>Full Name</th>
          <th>Email Address</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
          <th>Last Login</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($officers as $o): ?>
          <?php $isSelf = ((int)$o['officer_id'] === (int)$_SESSION['officer_id']); ?>
          <tr>
            <td>
              <strong><?= e($o['full_name']) ?></strong>
              <?php if ($isSelf): ?>
                <span style="font-size: 0.75rem; background: #e2e8f0; color: #475569; padding: 0.15rem 0.45rem; border-radius: 4px; margin-left: 0.35rem; font-weight: 600;">You</span>
              <?php endif; ?>
            </td>
            <td><?= e($o['email']) ?></td>
            <td>
              <?php if ($o['role'] === 'supervisor'): ?>
                <span class="badge" style="background: #0f172a; color: #ffffff;"><?= icon('shield', '', 12) ?> Supervisor</span>
              <?php else: ?>
                <span class="badge badge-in-progress"><?= icon('user', '', 12) ?> Officer</span>
              <?php endif; ?>
            </td>
            <td><?= e($o['department'] ?: 'N/A') ?></td>
            <td><?= render_account_status_badge($o['status'] ?? 'active') ?></td>
            <td style="font-size: 0.85rem; color: var(--color-text-muted);"><?= format_date($o['last_login'] ?? null) ?></td>
            <td>
              <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                <!-- Edit Profile Button -->
                <button type="button" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;" 
                  onclick="openEditModal(<?= (int)$o['officer_id'] ?>, '<?= e(addslashes($o['full_name'])) ?>', '<?= e(addslashes($o['email'])) ?>', '<?= e(addslashes($o['department'] ?? '')) ?>', '<?= e($o['role']) ?>', '<?= e($o['status'] ?? 'active') ?>', <?= $isSelf ? 'true' : 'false' ?>)">
                  <?= icon('edit', '', 13) ?> Edit
                </button>

                <?php if (!$isSelf): ?>
                  <!-- Toggle Status Form -->
                  <form action="<?= url('supervisor/toggle_officer.php') ?>" method="POST" style="margin: 0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="officer_id" value="<?= $o['officer_id'] ?>">
                    <?php if (($o['status'] ?? 'active') === 'active'): ?>
                      <button type="submit" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;" data-confirm="Deactivate account for <?= e($o['full_name']) ?>? They will not be able to log in.">
                        Deactivate
                      </button>
                    <?php else: ?>
                      <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;">
                        Activate
                      </button>
                    <?php endif; ?>
                  </form>

                  <!-- Reset Password Trigger -->
                  <button type="button" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;" onclick="openResetModal(<?= $o['officer_id'] ?>, '<?= e(addslashes($o['full_name'])) ?>')">
                    <?= icon('key', '', 13) ?> Reset Pass
                  </button>

                  <!-- Delete Account Trigger -->
                  <button type="button" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;" onclick="openDeleteModal(<?= (int)$o['officer_id'] ?>, '<?= e(addslashes($o['full_name'])) ?>')">
                    <?= icon('trash', '', 13) ?> Delete
                  </button>
                <?php else: ?>
                  <a href="<?= url('supervisor/change_password.php') ?>" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;"><?= icon('key', '', 13) ?> Password</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Add Officer Form Card -->
  <div style="background: #f8fafc; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.75rem;">
    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--color-dark); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
      <?= icon('plus', '', 18) ?> Add New ICT Staff Account
    </h2>
    <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">Create a new login account for an ICT Officer or Senior Supervisor.</p>

    <form action="<?= url('supervisor/add_officer.php') ?>" method="POST">
      <?= csrf_field() ?>

      <div class="form-row">
        <div class="form-group">
          <label for="full_name"><?= icon('user', '', 15) ?> Full Name <span style="color:red">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Jane Smith" required>
        </div>

        <div class="form-group">
          <label for="email"><?= icon('mail', '', 15) ?> Email Address <span style="color:red">*</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="j.smith@ists.com" required>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="password"><?= icon('key', '', 15) ?> Temporary Password <span style="color:red">*</span></label>
          <?= password_input('password', 'password', 'Minimum 8 characters', true, 8) ?>
        </div>

        <div class="form-group">
          <label for="role"><?= icon('shield', '', 15) ?> Role <span style="color:red">*</span></label>
          <select id="role" name="role" class="form-select" required>
            <option value="officer" selected>ICT Officer</option>
            <option value="supervisor">Supervisor / Admin</option>
          </select>
        </div>
      </div>

      <div class="form-group" style="max-width: 400px;">
        <label for="department"><?= icon('building', '', 15) ?> Department / Unit</label>
        <input type="text" id="department" name="department" class="form-control" placeholder="e.g. Network Operations / Helpdesk">
      </div>

      <div style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary"><?= icon('plus', '', 16) ?> Create Account</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Staff Profile Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
  <div style="background: #ffffff; border-radius: var(--radius-lg); max-width: 520px; width: 92%; padding: 2.25rem; box-shadow: var(--shadow-xl); max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.85rem;">
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--color-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <?= icon('edit', '', 18) ?> Edit Staff Profile
      </h3>
      <button type="button" onclick="closeEditModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; line-height: 1;">&times;</button>
    </div>

    <form action="<?= url('supervisor/edit_officer.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="officer_id" id="editOfficerId" value="">
      <!-- Hidden fallback inputs used when selects are disabled (self-editing) -->
      <input type="hidden" name="role"   id="editRoleFallback"   value="supervisor">
      <input type="hidden" name="status" id="editStatusFallback" value="active">

      <div class="form-group">
        <label for="editFullName"><?= icon('user', '', 15) ?> Full Name <span style="color:red">*</span></label>
        <input type="text" name="full_name" id="editFullName" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="editEmail"><?= icon('mail', '', 15) ?> Email Address <span style="color:red">*</span></label>
        <input type="email" name="email" id="editEmail" class="form-control" required>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="editRole"><?= icon('shield', '', 15) ?> Role <span style="color:red">*</span></label>
          <select name="role" id="editRole" class="form-select" required>
            <option value="officer">ICT Officer</option>
            <option value="supervisor">Supervisor / Admin</option>
          </select>
        </div>

        <div class="form-group">
          <label for="editStatus"><?= icon('check_circle', '', 15) ?> Status <span style="color:red">*</span></label>
          <select name="status" id="editStatus" class="form-select" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="editDepartment"><?= icon('building', '', 15) ?> Department / Unit</label>
        <input type="text" name="department" id="editDepartment" class="form-control">
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.75rem;">
        <button type="button" class="btn btn-secondary" id="editModalCancelBtn">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('check', '', 16) ?> Save Profile Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Password Reset Modal -->
<div id="resetModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
  <div style="background: #ffffff; border-radius: var(--radius-lg); max-width: 450px; width: 90%; padding: 2.25rem; box-shadow: var(--shadow-xl);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
      <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--color-dark); margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <?= icon('key', '', 18) ?> Reset Staff Password
      </h3>
      <button type="button" onclick="closeResetModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; line-height: 1;">&times;</button>
    </div>
    <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">Set a new temporary password for <strong id="resetStaffName"></strong>.</p>

    <form action="<?= url('supervisor/reset_officer_password.php') ?>" method="POST">
      <?= csrf_field() ?>
      <input type="hidden" name="officer_id" id="resetOfficerId" value="">

      <div class="form-group">
        <label for="modalNewPassword"><?= icon('key', '', 15) ?> New Temporary Password <span style="color:red">*</span></label>
        <?= password_input('new_password', 'modalNewPassword', 'Minimum 8 characters', true, 8) ?>
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeResetModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><?= icon('check', '', 16) ?> Set New Password</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEditModal(id, name, email, department, role, status, isSelf) {
  document.getElementById('editOfficerId').value = id;
  document.getElementById('editFullName').value  = name;
  document.getElementById('editEmail').value     = email;
  document.getElementById('editDepartment').value = department;

  const roleSelect   = document.getElementById('editRole');
  const statusSelect = document.getElementById('editStatus');
  const roleFallback   = document.getElementById('editRoleFallback');
  const statusFallback = document.getElementById('editStatusFallback');

  roleSelect.value   = role;
  statusSelect.value = status;

  if (isSelf) {
    // Disable visual interaction but keep hidden fallback inputs for form submission
    roleSelect.disabled   = true;
    statusSelect.disabled = true;
    roleFallback.value    = role;
    statusFallback.value  = status;
    // Remove name from selects so only hidden inputs submit
    roleSelect.removeAttribute('name');
    statusSelect.removeAttribute('name');
  } else {
    roleSelect.disabled   = false;
    statusSelect.disabled = false;
    // Restore name attributes so selects submit normally
    roleSelect.setAttribute('name', 'role');
    statusSelect.setAttribute('name', 'status');
    // Clear hidden fallback names so they don't double-submit
    roleFallback.removeAttribute('name');
    statusFallback.removeAttribute('name');
  }

  document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}

function openResetModal(id, name) {
  document.getElementById('resetOfficerId').value = id;
  document.getElementById('resetStaffName').textContent = name;
  document.getElementById('modalNewPassword').value = '';
  document.getElementById('resetModal').style.display = 'flex';
}

function closeResetModal() {
  document.getElementById('resetModal').style.display = 'none';
}

// Cancel button inside edit modal
const editCancelBtn = document.getElementById('editModalCancelBtn');
if (editCancelBtn) editCancelBtn.addEventListener('click', closeEditModal);

// Close modals when clicking backdrop
window.addEventListener('click', function(e) {
  const resetModal = document.getElementById('resetModal');
  const editModal  = document.getElementById('editModal');
  const deleteModal = document.getElementById('deleteModal');
  if (e.target === resetModal)  closeResetModal();
  if (e.target === editModal)   closeEditModal();
  if (e.target === deleteModal) closeDeleteModal();
});

// ESC key closes modals
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeEditModal();
    closeResetModal();
    closeDeleteModal();
  }
});

function openDeleteModal(id, name) {
  document.getElementById('deleteOfficerId').value = id;
  document.getElementById('deleteStaffName').textContent = name;
  document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
  document.getElementById('deleteModal').style.display = 'none';
}
</script>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.65); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
  <div style="background: #ffffff; border-radius: var(--radius-lg); max-width: 420px; width: 90%; padding: 2rem; box-shadow: var(--shadow-xl);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
      <h3 style="font-size: 1.1rem; font-weight: 700; color: #991b1b; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <?= icon('trash', '', 18) ?> Delete Staff Account
      </h3>
      <button type="button" onclick="closeDeleteModal()" style="background: none; border: none; font-size: 1.5rem; color: var(--color-text-muted); cursor: pointer; line-height: 1;">&times;</button>
    </div>
    <p style="font-size: 0.875rem; color: var(--color-text-muted); margin-bottom: 0.5rem;">
      You are about to permanently delete the account for <strong id="deleteStaffName"></strong>.
    </p>
    <p style="font-size: 0.82rem; color: #991b1b; background: #fef2f2; border: 1px solid #fecaca; border-radius: var(--radius-sm); padding: 0.65rem 0.85rem; margin-bottom: 1.25rem;">
      <?= icon('alert', '', 14) ?> This action is <strong>irreversible</strong>. All assignment history linked to this officer will be preserved in tickets.
    </p>
    <form action="<?= url('supervisor/delete_officer.php') ?>" method="POST" style="margin: 0;">
      <?= csrf_field() ?>
      <input type="hidden" name="officer_id" id="deleteOfficerId" value="">
      <div style="display: flex; gap: 0.75rem; justify-content: flex-end;">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button type="submit" class="btn btn-danger"><?= icon('trash', '', 15) ?> Permanently Delete</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
