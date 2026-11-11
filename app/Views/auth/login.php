<?php use function Core\{e, url, csrf_field, flash}; ?>
<div class="auth-card">
    <h1>Staff login</h1>
    <p class="muted">For admins and editors who publish guides.</p>
    <form method="post" action="<?= e(url('/login')) ?>" class="form">
        <?= csrf_field() ?>
        <label>Email
            <input type="email" name="email" value="<?= e(flash('old_email')) ?>" required autofocus autocomplete="username">
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit">Log in</button>
    </form>
</div>
