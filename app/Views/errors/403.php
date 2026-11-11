<?php use function Core\{e, url}; ?>
<h1><?= e($code) ?> · <?= e($title) ?></h1>
<p>You don't have permission to see this page.</p>
<p><a href="<?= e(url('/')) ?>">Back to the home page</a></p>
