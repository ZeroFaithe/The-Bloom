<?php use function Core\{e, url}; ?>
<h1><?= e($code) ?> · <?= e($title) ?></h1>
<p>Something went wrong on our side. Please try again later.</p>
<p><a href="<?= e(url('/')) ?>">Back to the home page</a></p>
