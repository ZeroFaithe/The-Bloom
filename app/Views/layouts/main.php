<?php
use Core\Session;
use function Core\{e, url, flash, csrf_field};
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
    <title><?= e($title ?? 'The Bloom') ?> | The Bloom</title>
</head>
<body>
    <header class="site-header">
        <div class="header-inner">
            <a class="brand" href="<?= e(url('/')) ?>">
                <?php $logoVariant = 'black'; require __DIR__ . '/../partials/logo.php'; ?>
                <span>
                    <span class="brand-name">The Bloom</span>
                    <span class="brand-tag">Coffee brewing guides</span>
                </span>
            </a>
            <nav class="site-nav" aria-label="Main">
                <a href="<?= e(url('/guides')) ?>">Guides</a>
                <?php if (Session::hasRole('admin', 'editor')): ?>
                    <a href="<?= e(url('/admin')) ?>">Dashboard</a>
                <?php elseif (Session::check()): ?>
                    <form method="post" action="<?= e(url('/logout')) ?>" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn-link">Log out</button>
                    </form>
                <?php else: ?>
                    <a href="<?= e(url('/login')) ?>">Staff login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="container">
        <?php if ($msg = flash('success')): ?><p class="alert alert-success"><?= e($msg) ?></p><?php endif; ?>
        <?php if ($msg = flash('error')): ?><p class="alert alert-error"><?= e($msg) ?></p><?php endif; ?>
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <p>The Bloom: step-by-step coffee brewing guides for home brewers.</p>
            <p><a href="<?= e(url('/guides')) ?>">Browse all guides</a></p>
        </div>
    </footer>
</body>
</html>
