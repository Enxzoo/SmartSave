<?php if (!empty($_SESSION['flash'])): ?>
    <?php $notice = $_SESSION['flash']; unset($_SESSION['flash']); ?>
    <div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'danger' ?>" role="status">
        <?= e($notice['message']) ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>
