<?php
use function Core\{e, url, csrf_field, old, field_error};
$val = fn (string $field) => old($field, $method[$field] ?? '');
?>
<h1><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="form wide">
    <?= csrf_field() ?>

    <label>Name
        <input type="text" name="name" maxlength="80" value="<?= e($val('name')) ?>" required>
        <?php if (field_error('name')): ?><span class="field-error"><?= e(field_error('name')) ?></span><?php endif; ?>
    </label>

    <label>Slug <small>(leave blank to build it from the name)</small>
        <input type="text" name="slug" maxlength="100" value="<?= e($val('slug')) ?>">
        <?php if (field_error('slug')): ?><span class="field-error"><?= e(field_error('slug')) ?></span><?php endif; ?>
    </label>

    <label>Description
        <textarea name="description" rows="3" maxlength="1000"><?= e($val('description')) ?></textarea>
        <?php if (field_error('description')): ?><span class="field-error"><?= e(field_error('description')) ?></span><?php endif; ?>
    </label>

    <div class="actions">
        <button type="submit">Save method</button>
        <a class="btn btn-secondary" href="<?= e(url('/admin/methods')) ?>">Cancel</a>
    </div>
</form>
