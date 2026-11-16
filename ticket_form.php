<?php
$pageTitle = 'Submit a Ticket';
require_once __DIR__ . '/includes/db_connect.php';

// Fetch dynamic categories from database
$categories = [];
try {
    $stmt = $pdo->query("SELECT category_name, description FROM categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categories = [
        ['category_name' => 'Hardware Support', 'description' => 'PC, laptop, monitor, keyboard'],
        ['category_name' => 'Software & Operating System', 'description' => 'OS, software installation, errors'],
        ['category_name' => 'Network & Internet', 'description' => 'Wi-Fi, LAN, internet access'],
        ['category_name' => 'Printer & Scanner', 'description' => 'Printer jams, toner, scanning'],
        ['category_name' => 'User Account & Access', 'description' => 'Password reset, credentials, access'],
        ['category_name' => 'Other Technical Issue', 'description' => 'General technical support']
    ];
}

// Retrieve any preserved form data from session
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

include __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 1.25rem;">
  <a href="<?= url('index.php') ?>" class="btn btn-secondary btn-sm">
    <?= icon('arrow_left', '', 14) ?> Back to Home
  </a>
</div>

<div class="detail-grid">
  <!-- Left Column: Submission Form -->
  <div class="card" style="margin-bottom: 0;">
    <div class="card-header">
      <div>
        <h1 class="card-title"><?= icon('ticket', '', 22) ?> Report a Technical Issue</h1>
        <p class="card-subtitle">Fill out the form below to log a support ticket with the ICT service desk.</p>
      </div>
    </div>

    <form action="<?= url('submit_ticket.php') ?>" method="POST" id="ticketSubmitForm">
      <?= csrf_field() ?>

      <div class="form-row">
        <div class="form-group">
          <label for="full_name"><?= icon('user', '', 15) ?> Full Name <span style="color:red">*</span></label>
          <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Yusuph Jumbe" value="<?= e($formData['full_name'] ?? '') ?>" required autofocus>
        </div>

        <div class="form-group">
          <label for="email"><?= icon('mail', '', 15) ?> Email Address <span style="font-size: 0.8rem; font-weight: normal; color: var(--color-text-muted);">(For updates)</span></label>
          <input type="email" id="email" name="email" class="form-control" placeholder="e.g. y.jumbe@company.com" value="<?= e($formData['email'] ?? '') ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label for="office"><?= icon('building', '', 15) ?> Office / Department <span style="color:red">*</span></label>
          <input type="text" id="office" name="office" class="form-control" placeholder="e.g. Finance Dept / Room 204" value="<?= e($formData['office'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label for="phone"><?= icon('phone', '', 15) ?> Phone / Extension <span style="color:red">*</span></label>
          <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. +255 700 000000" value="<?= e($formData['phone'] ?? '') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label for="category"><?= icon('filter', '', 15) ?> Issue Category <span style="color:red">*</span></label>
        <select id="category" name="category" class="form-select" required>
          <option value="">-- Select Category --</option>
          <?php foreach ($categories as $cat): ?>
            <?php $selected = (($formData['category'] ?? '') === $cat['category_name']) ? 'selected' : ''; ?>
            <option value="<?= e($cat['category_name']) ?>" <?= $selected ?>><?= e($cat['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label><?= icon('alert', '', 15) ?> Urgency / Priority Level <span style="color:red">*</span></label>
        <?php $pVal = $formData['priority'] ?? 'medium'; ?>
        <div class="priority-options">
          <label class="priority-option">
            <input type="radio" name="priority" value="low" <?= $pVal === 'low' ? 'checked' : '' ?>>
            <div class="priority-card">
              <span class="priority-card-title">Low</span>
              <span class="priority-card-desc">Non-urgent inquiry</span>
            </div>
          </label>

          <label class="priority-option">
            <input type="radio" name="priority" value="medium" <?= $pVal === 'medium' ? 'checked' : '' ?>>
            <div class="priority-card">
              <span class="priority-card-title">Medium</span>
              <span class="priority-card-desc">Normal operations</span>
            </div>
          </label>

          <label class="priority-option">
            <input type="radio" name="priority" value="high" <?= $pVal === 'high' ? 'checked' : '' ?>>
            <div class="priority-card">
              <span class="priority-card-title">High</span>
              <span class="priority-card-desc">Critical work stoppage</span>
            </div>
          </label>
        </div>
      </div>

      <div class="form-group">
        <label for="subject"><?= icon('document', '', 15) ?> Issue Summary <span style="color:red">*</span></label>
        <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Network offline in Accounts office" value="<?= e($formData['subject'] ?? '') ?>" required maxlength="150">
      </div>

      <div class="form-group">
        <label for="description"><?= icon('document', '', 15) ?> Detailed Problem Description <span style="color:red">*</span></label>
        <textarea id="description" name="description" rows="5" class="form-textarea" placeholder="Please describe what happened, any error messages displayed, and steps to reproduce..." required><?= e($formData['description'] ?? '') ?></textarea>
        <div class="form-text">Include specific details (equipment tags, error numbers, symptoms) so our ICT technicians can assist promptly.</div>
      </div>

      <div style="margin-top: 2rem; display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
          <?= icon('check_circle', '', 18) ?> Submit Support Ticket
        </button>
        <a href="<?= url('index.php') ?>" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- Right Column: Support Guidelines & Tips Card -->
  <div>
    <div style="background: #ffffff; border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 1.75rem; box-shadow: var(--shadow-sm); margin-bottom: 1.5rem;">
      <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--color-dark); margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
        <?= icon('shield', '', 18) ?> Helpdesk Guidelines
      </h3>
      <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.875rem; color: var(--color-text-muted); line-height: 1.7;">
        <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start; gap: 0.5rem;">
          <span style="color: var(--color-primary);"><?= icon('check', '', 16) ?></span>
          <span><strong>One issue per ticket:</strong> Submit separate tickets for distinct equipment or problems.</span>
        </li>
        <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start; gap: 0.5rem;">
          <span style="color: var(--color-primary);"><?= icon('check', '', 16) ?></span>
          <span><strong>Equipment Tag:</strong> Note your computer or printer asset number if visible.</span>
        </li>
        <li style="margin-bottom: 0.75rem; display: flex; align-items: flex-start; gap: 0.5rem;">
          <span style="color: var(--color-primary);"><?= icon('check', '', 16) ?></span>
          <span><strong>Keep your Ticket Code:</strong> You will receive a code (e.g. ISTS-000123) to track status anytime.</span>
        </li>
      </ul>
    </div>

    <div style="background: linear-gradient(135deg, #090d16 0%, #1e293b 100%); color: #ffffff; border-radius: var(--radius-lg); padding: 1.75rem;">
      <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
        <?= icon('headset', '', 18) ?> Support Hours
      </h3>
      <p style="font-size: 0.85rem; color: #94a3b8; line-height: 1.6; margin-bottom: 1rem;">
        Monday – Friday: 8:00 AM – 5:00 PM<br>
        Emergency on-call coverage for critical server &amp; network stoppages.
      </p>
      <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 0.85rem; font-size: 0.8rem; color: #cbd5e1;">
        Already submitted a ticket? <a href="<?= url('track_status.php') ?>" style="color: #60a5fa; font-weight: 700;">Track your status &rarr;</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
