<?php use function Core\{e, url}; ?>
<h1><?= e($code) ?> · <?= e($title) ?></h1>
<p>We couldn't find that page.</p>
<p><a href="<?= e(url('/')) ?>">Back to the home page</a></p>
