<?php
require_once __DIR__ . '/functions.php';

$isLoggedIn = isset($_SESSION['officer_id']);
$useSidebar = $isLoggedIn || (isset($forceSidebar) && $forceSidebar);
?>

<?php if ($useSidebar): ?>
    </main> <!-- /.app-main-content -->
  </div> <!-- /.app-layout -->
<?php else: ?>
  </div> <!-- /.app-content -->

  <footer class="app-footer no-print">
    <div class="footer-container">
      <p>&copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> &mdash; Enterprise IT Service Management &mdash; v<?= e(APP_VERSION) ?></p>
    </div>
  </footer>
<?php endif; ?>

</body>
</html>
