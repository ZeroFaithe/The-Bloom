<?php use function Core\{e, url}; ?>
<h1><?= e($code) ?> · <?= e($title) ?></h1>
<p>Your form expired. Go back, refresh the page, and try again.</p>
<p><a href="<?= e(url('/')) ?>">Back to the home page</a></p>
