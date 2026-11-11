<?php
use Core\Session;
use function Core\{e, url, asset, flash, csrf_field};
$user = Session::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <title><?= e($title ?? 'Dashboard') ?> | The Bloom staff</title>
    <script src="<?= e(asset('js/admin.js')) ?>" defer></script>
</head>
<body>
    <header class="site-header admin">
        <div class="header-inner">
            <a class="brand" href="<?= e(url('/admin')) ?>">
                <?php $logoVariant = 'white'; require __DIR__ . '/../partials/logo.php'; ?>
                <span>
                    <span class="brand-name">The Bloom</span>
                    <span class="brand-tag">Staff dashboard</span>
                </span>
            </a>
            <nav class="site-nav" aria-label="Staff">
                <a href="<?= e(url('/admin')) ?>">Dashboard</a>
                <a href="<?= e(url('/admin/guides')) ?>">Guides</a>
                <?php if (Session::hasRole('admin')): ?>
                    <a href="<?= e(url('/admin/methods')) ?>">Methods</a>
                <?php endif; ?>
                <a href="<?= e(url('/')) ?>">View site</a>
                <span class="who"><?= e($user['name'] ?? '') ?> (<?= e($user['role'] ?? '') ?>)</span>
                <form method="post" action="<?= e(url('/logout')) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit">Log out</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php if ($msg = flash('success')): ?><p class="alert alert-success"><?= e($msg) ?></p><?php endif; ?>
        <?php if ($msg = flash('error')): ?><p class="alert alert-error"><?= e($msg) ?></p><?php endif; ?>
        <?= $content ?>
    </main>
</body>
</html>
