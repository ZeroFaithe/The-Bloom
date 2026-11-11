<?php use function Core\{e, url, csrf_field}; ?>
<div class="page-head">
    <h1>Brewing methods</h1>
    <a class="btn" href="<?= e(url('/admin/methods/create')) ?>">New method</a>
</div>

<?php if ($methods === []): ?>
    <p class="muted">No methods yet. Add one so guides can use it (for example: V60, French Press, AeroPress).</p>
<?php else: ?>
<table class="table">
    <thead><tr><th>Name</th><th>Slug</th><th>Guides</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($methods as $m): ?>
        <tr>
            <td><a href="<?= e(url("/admin/methods/{$m['id']}/edit")) ?>"><?= e($m['name']) ?></a></td>
            <td><?= e($m['slug']) ?></td>
            <td><?= e($m['guide_count']) ?></td>
            <td class="row-actions">
                <a href="<?= e(url("/admin/methods/{$m['id']}/edit")) ?>">Edit</a>
                <form method="post" action="<?= e(url("/admin/methods/{$m['id']}/delete")) ?>" class="inline"
                      onsubmit="return confirm('Delete this method?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-link danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
