<?php if (isset($_SESSION['msg'])): ?>
    <?= msg($_SESSION['msg']['texto'], $_SESSION['msg']['color'] ?? 'info'); ?>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>
