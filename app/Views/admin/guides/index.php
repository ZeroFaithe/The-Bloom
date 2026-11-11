<?php use function Core\{e, url, csrf_field}; ?>
<div class="page-head">
    <h1>Guides</h1>
    <a class="btn" href="<?= e(url('/admin/guides/create')) ?>">New guide</a>
</div>

<?php if ($guides === []): ?>
    <p class="muted">No guides yet. Create the first one!</p>
<?php else: ?>
<table class="table">
    <thead>
        <tr><th>Title</th><th>Method</th><th>Difficulty</th><th>Status</th><th>Author</th><th>Updated</th><th>Actions</th></tr>
    </thead>
    <tbody>
    <?php foreach ($guides as $g): ?>
        <tr>
            <td><a href="<?= e(url("/admin/guides/{$g['id']}/edit")) ?>"><?= e($g['title']) ?></a></td>
            <td><?= e($g['method_name']) ?></td>
            <td><?= e($g['difficulty']) ?></td>
            <td><span class="badge badge-<?= e($g['status']) ?>"><?= e($g['status']) ?></span></td>
            <td><?= e($g['author_name']) ?></td>
            <td><?= e(date('M j, Y', strtotime($g['updated_at']))) ?></td>
            <td class="row-actions">
                <a href="<?= e(url("/admin/guides/{$g['id']}/edit")) ?>">Edit</a>
                <a href="<?= e(url('/guides/' . rawurlencode($g['slug']))) ?>"><?= $g['status'] === 'published' ? 'View' : 'Preview' ?></a>
                <form method="post" action="<?= e(url("/admin/guides/{$g['id']}/status")) ?>" class="inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-link"><?= $g['status'] === 'published' ? 'Unpublish' : 'Publish' ?></button>
                </form>
                <form method="post" action="<?= e(url("/admin/guides/{$g['id']}/delete")) ?>" class="inline"
                      onsubmit="return confirm('Delete this guide and its steps?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn-link danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
