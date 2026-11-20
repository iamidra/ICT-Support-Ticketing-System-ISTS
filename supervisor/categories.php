<?php
/**
 * Supervisor Category Management
 * CRUD for ticket categories with ticket count tracking and update cascading.
 */
require_once __DIR__ . '/../includes/auth_supervisor.php';
require_once __DIR__ . '/../includes/db_connect.php';

$pageTitle = 'Manage Categories';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        set_flash('danger', 'Security validation expired. Please try again.');
        header('Location: ' . url('supervisor/categories.php'));
        exit;
    }

    $action = trim($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = trim($_POST['category_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if (empty($name)) {
            set_flash('warning', 'Category name is required.');
            header('Location: ' . url('supervisor/categories.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->execute([$name, $desc ?: null]);
            $newCatId = (int)$pdo->lastInsertId();
            log_audit_event('category.created', 'category', 'Created category "' . $name . '"', 'category', $newCatId);
            set_flash('success', 'New category "' . $name . '" created successfully.');
        } catch (Throwable $e) {
            set_flash('danger', 'Error adding category (it may already exist): ' . $e->getMessage());
        }
        header('Location: ' . url('supervisor/categories.php'));
        exit;

    } elseif ($action === 'edit') {
        $catId = (int)($_POST['category_id'] ?? 0);
        $name  = trim($_POST['category_name'] ?? '');
        $desc  = trim($_POST['description'] ?? '');

        if (!$catId || empty($name)) {
            set_flash('warning', 'Invalid category parameters.');
            header('Location: ' . url('supervisor/categories.php'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Find current name
            $origStmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $origStmt->execute([$catId]);
            $origName = $origStmt->fetchColumn();

            if ($origName) {
                // Update category record
                $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->execute([$name, $desc ?: null, $catId]);

                // Cascade update tickets if name changed
                if ($origName !== $name) {
                    $updTickets = $pdo->prepare("UPDATE tickets SET category = ? WHERE category = ?");
                    $updTickets->execute([$name, $origName]);
                }
            }

            $pdo->commit();
            log_audit_event('category.updated', 'category', 'Updated category ID ' . $catId . ' ("' . $name . '")', 'category', $catId);
            set_flash('success', 'Category updated successfully.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            set_flash('danger', 'Error updating category: ' . $e->getMessage());
        }
        header('Location: ' . url('supervisor/categories.php'));
        exit;

    } elseif ($action === 'delete') {
        $catId = (int)($_POST['category_id'] ?? 0);

        if (!$catId) {
            set_flash('warning', 'Invalid category identifier.');
            header('Location: ' . url('supervisor/categories.php'));
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ?");
            $stmt->execute([$catId]);
            $catName = $stmt->fetchColumn();

            if ($catName) {
                // Check if any tickets use this category
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE category = ?");
                $countStmt->execute([$catName]);
                $usedCount = (int)$countStmt->fetchColumn();

                if ($usedCount > 0) {
                    set_flash('danger', 'Cannot delete "' . $catName . '" because ' . $usedCount . ' ticket(s) are currently categorized under it. Please reassign those tickets first.');
                } else {
                    $delStmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
                    $delStmt->execute([$catId]);
                    log_audit_event('category.deleted', 'category', 'Deleted category "' . $catName . '" (ID: ' . $catId . ')', 'category', $catId);
                    set_flash('success', 'Category "' . $catName . '" deleted successfully.');
                }
            }
        } catch (Throwable $e) {
            set_flash('danger', 'Error deleting category: ' . $e->getMessage());
        }
        header('Location: ' . url('supervisor/categories.php'));
        exit;
    }
}

// Fetch categories with ticket counts
try {
    $categories = $pdo->query(
        "SELECT c.*, COUNT(t.ticket_id) AS ticket_count
         FROM categories c
         LEFT JOIN tickets t ON c.category_name = t.category
         GROUP BY c.category_id, c.category_name, c.description
         ORDER BY c.category_name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $categories = [];
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/supervisor_nav.php';
?>

<div class="card">
  <div class="card-header">
    <div>
      <h1 class="card-title"><?= icon('tag', '', 22) ?> Issue Categories Directory</h1>
      <p class="card-subtitle">Manage technical support categories, service classifications, and track ticket distributions.</p>
    </div>
    <div>
      <button type="button" class="btn btn-primary" onclick="openAddModal()">
        <?= icon('plus', '', 16) ?> Add New Category
      </button>
    </div>
  </div>

  <div class="table-container">
    <table class="table">
      <thead>
        <tr>
          <th>Category Name</th>
          <th>Description & Scope</th>
          <th>Active Tickets</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($categories)): ?>
          <tr>
            <td colspan="4" style="text-align: center; color: var(--color-text-muted); padding: 2.5rem;">No categories defined.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <strong style="color: var(--color-dark);"><?= e($cat['category_name']) ?></strong>
              </td>
              <td style="color: var(--color-text-muted); font-size: 0.875rem;">
                <?= e($cat['description'] ?: 'No description provided') ?>
              </td>
              <td>
                <span class="badge <?= $cat['ticket_count'] > 0 ? 'badge-in-progress' : 'badge-default' ?>">
                  <?= (int)$cat['ticket_count'] ?> Ticket<?= $cat['ticket_count'] != 1 ? 's' : '' ?>
                </span>
              </td>
              <td>
                <div style="display: flex; gap: 0.4rem; align-items: center;">
                  <button type="button" class="btn btn-secondary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;"
                    onclick="openEditModal(<?= (int)$cat['category_id'] ?>, '<?= e(addslashes($cat['category_name'])) ?>', '<?= e(addslashes($cat['description'] ?? '')) ?>')">
                    <?= icon('edit', '', 13) ?> Edit
                  </button>

                  <form action="<?= url('supervisor/categories.php') ?>" method="POST" style="margin: 0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" value="<?= (int)$cat['category_id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.775rem;"
                      data-confirm="Are you sure you want to delete category '<?= e(addslashes($cat['category_name'])) ?>'?">
                      <?= icon('trash', '', 13) ?> Delete
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Category -->
<div id="addModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem;">
  <div class="card" style="max-width: 500px; width: 100%; margin: 0; box-shadow: var(--shadow-lg);">
    <div class="card-header">
      <h3 class="card-title">Add Support Category</h3>
      <button type="button" class="alert-close" onclick="closeAddModal()">&times;</button>
    </div>
    <form action="<?= url('supervisor/categories.php') ?>" method="POST" style="padding-top: 1rem;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">

      <div class="form-group">
        <label for="add_name">Category Name <span style="color:red">*</span></label>
        <input type="text" id="add_name" name="category_name" class="form-control" placeholder="e.g. Cybersecurity Incident" required>
      </div>

      <div class="form-group">
        <label for="add_desc">Description / Scope</label>
        <textarea id="add_desc" name="description" rows="3" class="form-textarea" placeholder="Describe the types of technical issues covered by this category..."></textarea>
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Category</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Edit Category -->
<div id="editModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1.5rem;">
  <div class="card" style="max-width: 500px; width: 100%; margin: 0; box-shadow: var(--shadow-lg);">
    <div class="card-header">
      <h3 class="card-title">Edit Support Category</h3>
      <button type="button" class="alert-close" onclick="closeEditModal()">&times;</button>
    </div>
    <form action="<?= url('supervisor/categories.php') ?>" method="POST" style="padding-top: 1rem;">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="edit">
      <input type="hidden" id="edit_id" name="category_id" value="">

      <div class="form-group">
        <label for="edit_name">Category Name <span style="color:red">*</span></label>
        <input type="text" id="edit_name" name="category_name" class="form-control" required>
      </div>

      <div class="form-group">
        <label for="edit_desc">Description / Scope</label>
        <textarea id="edit_desc" name="description" rows="3" class="form-textarea"></textarea>
      </div>

      <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem;">
        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openAddModal() {
  const m = document.getElementById('addModal');
  m.style.display = 'flex';
  document.getElementById('add_name').focus();
}
function closeAddModal() {
  document.getElementById('addModal').style.display = 'none';
}
function openEditModal(id, name, desc) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name').value = name;
  document.getElementById('edit_desc').value = desc;
  const m = document.getElementById('editModal');
  m.style.display = 'flex';
  document.getElementById('edit_name').focus();
}
function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}
// Close modals on clicking backdrop
window.addEventListener('click', (e) => {
  const addM = document.getElementById('addModal');
  const editM = document.getElementById('editModal');
  if (e.target === addM) closeAddModal();
  if (e.target === editM) closeEditModal();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
