<?php
require_once __DIR__ . '/functions.php';
$currentOfficerName = $_SESSION['officer_name'] ?? 'Officer';
?>
<div class="portal-banner officer-banner">
  <div class="portal-banner-content">
    <div class="portal-badge-icon">
      <?= icon('user', '', 24) ?>
    </div>
    <div style="flex: 1;">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
        <div>
          <h2 class="portal-banner-title">ICT Officer Workspace</h2>
          <p class="portal-banner-subtitle">Signed in as <strong><?= e($currentOfficerName) ?></strong> &bull; Manage assigned queues and record technical resolutions</p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <span style="font-size: 0.8rem; background: rgba(16, 185, 129, 0.18); border: 1px solid rgba(16, 185, 129, 0.35); color: #34d399; padding: 0.3rem 0.75rem; border-radius: var(--radius-full); font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;">
            <span class="pulse-dot"></span> Online &amp; Active
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
