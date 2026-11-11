<?php use Core\Session; use function Core\{e, url}; ?>
<h1>Dashboard</h1>
<p>Hello, <?= e($user['name']) ?>.</p>

<div class="stats">
    <div class="stat"><span class="num"><?= e($stats['total']) ?></span> Guides</div>
    <div class="stat"><span class="num"><?= e($stats['published']) ?></span> Published</div>
    <div class="stat"><span class="num"><?= e($stats['drafts']) ?></span> Drafts</div>
    <div class="stat"><span class="num"><?= e($stats['methods']) ?></span> Methods</div>
</div>

<p class="actions">
    <a class="btn" href="<?= e(url('/admin/guides/create')) ?>">New guide</a>
    <a class="btn btn-secondary" href="<?= e(url('/admin/guides')) ?>">All guides</a>
    <?php if (Session::hasRole('admin')): ?>
        <a class="btn btn-secondary" href="<?= e(url('/admin/methods')) ?>">Manage methods</a>
    <?php endif; ?>
</p>

<h2>Recently updated</h2>
<?php if ($recent === []): ?>
    <p class="muted">No guides yet. Create the first one!</p>
<?php else: ?>
    <table class="table">
        <thead><tr><th>Title</th><th>Method</th><th>Status</th><th>Updated</th></tr></thead>
        <tbody>
        <?php foreach ($recent as $g): ?>
            <tr>
                <td><a href="<?= e(url("/admin/guides/{$g['id']}/edit")) ?>"><?= e($g['title']) ?></a></td>
                <td><?= e($g['method_name']) ?></td>
                <td><span class="badge badge-<?= e($g['status']) ?>"><?= e($g['status']) ?></span></td>
                <td><?= e(date('M j, Y', strtotime($g['updated_at']))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
