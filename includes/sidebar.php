<?php
require_once __DIR__ . '/functions.php';

$role = $_SESSION['officer_role'] ?? '';
$userName = $_SESSION['officer_name'] ?? 'User Account';
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
?>

<aside class="app-sidebar">
  <div class="sidebar-brand">
    <a href="<?= url('index.php') ?>" class="brand-logo" style="gap: 0.75rem;">
      <div class="brand-icon" style="width: 36px; height: 36px;">
        <?= icon('headset', '', 20) ?>
      </div>
      <div>
        <div style="font-weight: 800; font-size: 1.1rem; line-height: 1.1; color: #ffffff; letter-spacing: -0.01em;"><?= APP_SHORT_NAME ?></div>
        <div style="font-size: 0.7rem; color: #94a3b8; font-weight: 500;">Support Desk</div>
      </div>
    </a>
  </div>

  <div class="sidebar-user-card">
    <div class="sidebar-avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
    <div style="overflow: hidden;">
      <div class="sidebar-user-name" title="<?= e($userName) ?>"><?= e($userName) ?></div>
      <span class="user-role-tag" style="background: <?= $role === 'supervisor' ? '#3b82f6' : '#10b981' ?>;">
        <?= ucfirst(e($role)) ?>
      </span>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Navigation Menu</div>

    <?php if ($role === 'supervisor'): ?>
      <a href="<?= url('supervisor/dashboard.php') ?>" class="sidebar-link <?= strpos($currentScript, 'supervisor/dashboard.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
        All Tickets Dashboard
      </a>

      <a href="<?= url('supervisor/reports.php') ?>" class="sidebar-link <?= strpos($currentScript, 'supervisor/reports.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
        Reports & Analytics
      </a>

      <a href="<?= url('supervisor/manage_officers.php') ?>" class="sidebar-link <?= strpos($currentScript, 'supervisor/manage_officers.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        Manage Officers
      </a>

      <a href="<?= url('supervisor/change_password.php') ?>" class="sidebar-link <?= strpos($currentScript, 'supervisor/change_password.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
        Change Password
      </a>

    <?php else: // Officer ?>
      <a href="<?= url('officer/dashboard.php') ?>" class="sidebar-link <?= strpos($currentScript, 'officer/dashboard.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
        My Ticket Queue
      </a>

      <a href="<?= url('officer/change_password.php') ?>" class="sidebar-link <?= strpos($currentScript, 'officer/change_password.php') !== false ? 'active' : '' ?>">
        <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
        Change Password
      </a>
    <?php endif; ?>

    <div class="sidebar-section-label" style="margin-top: 1.5rem;">Public Portal</div>

    <a href="<?= url('index.php') ?>" class="sidebar-link" target="_blank" rel="noopener">
      <svg class="sidebar-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
      Public Helpdesk Site &rarr;
    </a>
  </nav>

  <div class="sidebar-footer">
    <?php if ($role === 'supervisor'): ?>
      <a href="<?= url('supervisor/logout.php') ?>" class="btn btn-secondary btn-block btn-sm" data-confirm="Are you sure you want to sign out?">
        <?= icon('logout', '', 15) ?> Sign Out
      </a>
    <?php else: ?>
      <a href="<?= url('officer/logout.php') ?>" class="btn btn-secondary btn-block btn-sm" data-confirm="Are you sure you want to sign out?">
        <?= icon('logout', '', 15) ?> Sign Out
      </a>
    <?php endif; ?>
  </div>
</aside>
