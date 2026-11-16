<?php
require_once __DIR__ . '/functions.php';
$currentSupervisorName = $_SESSION['officer_name'] ?? 'Supervisor';
?>
<div class="portal-banner supervisor-banner">
  <div class="portal-banner-content">
    <div class="portal-badge-icon dark">
      <?= icon('shield', '', 24) ?>
    </div>
    <div style="flex: 1;">
      <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
        <div>
          <h2 class="portal-banner-title">Supervisor Administrative Console</h2>
          <p class="portal-banner-subtitle">Signed in as <strong><?= e($currentSupervisorName) ?></strong> &bull; System analytics, staff allocation &amp; performance management</p>
        </div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <span style="font-size: 0.8rem; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #93c5fd; padding: 0.3rem 0.75rem; border-radius: var(--radius-full); font-weight: 700; display: inline-flex; align-items: center; gap: 0.4rem;">
            <?= icon('shield', '', 14) ?> Admin Console
          </span>
        </div>
      </div>
    </div>
  </div>
</div>
