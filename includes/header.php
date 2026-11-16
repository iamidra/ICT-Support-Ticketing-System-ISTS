<?php
require_once __DIR__ . '/functions.php';

$isLoggedIn = isset($_SESSION['officer_id']);
$useSidebar = $isLoggedIn || (isset($forceSidebar) && $forceSidebar);
$currentScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= APP_NAME ?> - Fast, transparent, and efficient IT support desk">
  <meta name="theme-color" content="#090d16">
  <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . APP_SHORT_NAME : APP_NAME ?></title>
  <!-- Modern SVG Favicon -->
  <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%232563eb'/%3E%3Cpath fill='white' d='M9 12h14v2H9zm0 4h10v2H9zm0-8h14v2H9z'/%3E%3C/svg%3E">
  <link rel="stylesheet" href="<?= asset_url('css/style.css') ?>">
  <script src="<?= asset_url('js/main.js') ?>" defer></script>
</head>
<body>

<?php if ($useSidebar): ?>
  <!-- Authenticated Sidebar Layout -->
  <div class="app-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <main class="app-main-content">
      <?= render_flash() ?>

<?php else: ?>
  <!-- Public Top Navigation Header -->
  <?php if (!isset($hidePublicNav) || !$hidePublicNav): ?>
    <header class="app-header no-print">
      <div class="header-container">
        <a href="<?= url('index.php') ?>" class="brand-logo">
          <div class="brand-icon">
            <?= icon('headset', '', 22) ?>
          </div>
          <span><?= APP_NAME ?></span>
        </a>

        <!-- Mobile Menu Toggle Button -->
        <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation">
          <?= icon('filter', '', 22) ?>
        </button>

        <nav class="nav-container" id="navContainer">
          <ul class="nav-menu">
            <li>
              <a href="<?= url('index.php') ?>" class="nav-link <?= str_ends_with($currentScript, 'index.php') ? 'active' : '' ?>">
                <?= icon('dashboard', '', 16) ?> Home
              </a>
            </li>
            <li>
              <a href="<?= url('ticket_form.php') ?>" class="nav-link <?= str_ends_with($currentScript, 'ticket_form.php') ? 'active' : '' ?>">
                <?= icon('plus', '', 16) ?> Submit Ticket
              </a>
            </li>
            <li>
              <a href="<?= url('track_status.php') ?>" class="nav-link <?= str_ends_with($currentScript, 'track_status.php') ? 'active' : '' ?>">
                <?= icon('search', '', 16) ?> Track Ticket
              </a>
            </li>
            <li>
              <a href="<?= url('login.php') ?>" class="btn btn-primary btn-sm">
                <?= icon('key', '', 15) ?> Staff Login
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </header>
  <?php endif; ?>

  <div class="app-content">
    <?= render_flash() ?>
<?php endif; ?>
