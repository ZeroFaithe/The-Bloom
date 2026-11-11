<?php
use App\Models\Guide;
use function Core\{e, url, csrf_field, old, field_error};

// Show what the user typed after an error; otherwise the saved value or a default.
$val = fn (string $field, mixed $default = '') => old($field, $guide[$field] ?? $default);
$err = fn (string $field): string => field_error($field);
?>
<h1><?= e($title) ?></h1>

<form method="post" action="<?= e($action) ?>" class="form wide">
    <?= csrf_field() ?>

    <label>Title
        <input type="text" name="title" maxlength="150" value="<?= e($val('title')) ?>" required>
        <?php if ($err('title')): ?><span class="field-error"><?= e($err('title')) ?></span><?php endif; ?>
    </label>

    <label>Slug <small>(leave blank to build it from the title)</small>
        <input type="text" name="slug" maxlength="170" value="<?= e($val('slug')) ?>">
        <?php if ($err('slug')): ?><span class="field-error"><?= e($err('slug')) ?></span><?php endif; ?>
    </label>

    <div class="grid-2">
        <label>Brewing method
            <select name="method_id" required>
                <option value="">Choose…</option>
                <?php foreach ($methods as $m): ?>
                    <option value="<?= e($m['id']) ?>" <?= (string) $val('method_id') === (string) $m['id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($err('method_id')): ?><span class="field-error"><?= e($err('method_id')) ?></span><?php endif; ?>
            <?php if ($methods === []): ?><span class="field-error">No methods yet. An admin must add one first.</span><?php endif; ?>
        </label>

        <label>Difficulty
            <select name="difficulty">
                <?php foreach (Guide::DIFFICULTIES as $d): ?>
                    <option value="<?= e($d) ?>" <?= $val('difficulty', 'beginner') === $d ? 'selected' : '' ?>><?= e(ucfirst($d)) ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($err('difficulty')): ?><span class="field-error"><?= e($err('difficulty')) ?></span><?php endif; ?>
        </label>
    </div>

    <label>Summary
        <textarea name="summary" rows="3" maxlength="2000"><?= e($val('summary')) ?></textarea>
        <?php if ($err('summary')): ?><span class="field-error"><?= e($err('summary')) ?></span><?php endif; ?>
    </label>

    <div class="grid-3">
        <label>Coffee (g)
            <input type="number" name="coffee_g" step="0.1" min="1" max="1000" value="<?= e($val('coffee_g')) ?>" required>
            <?php if ($err('coffee_g')): ?><span class="field-error"><?= e($err('coffee_g')) ?></span><?php endif; ?>
        </label>
        <label>Water (g)
            <input type="number" name="water_g" step="0.1" min="1" max="20000" value="<?= e($val('water_g')) ?>" required>
            <?php if ($err('water_g')): ?><span class="field-error"><?= e($err('water_g')) ?></span><?php endif; ?>
        </label>
        <label>Temperature (°C)
            <input type="number" name="temperature_c" min="60" max="100" value="<?= e($val('temperature_c', 93)) ?>" required>
            <?php if ($err('temperature_c')): ?><span class="field-error"><?= e($err('temperature_c')) ?></span><?php endif; ?>
        </label>
    </div>

    <label>Status
        <select name="status">
            <?php foreach (Guide::STATUSES as $s): ?>
                <option value="<?= e($s) ?>" <?= $val('status', 'draft') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($err('status')): ?><span class="field-error"><?= e($err('status')) ?></span><?php endif; ?>
    </label>

    <fieldset>
        <legend>Timed steps</legend>
        <?php if ($err('steps')): ?><p class="field-error"><?= e($err('steps')) ?></p><?php endif; ?>

        <div id="steps">
            <?php foreach ($steps as $i => $step): ?>
                <div class="step-row">
                    <label class="step-main">Instruction
                        <textarea name="steps[<?= e($i) ?>][instruction]" rows="2" maxlength="2000"><?= e($step['instruction'] ?? '') ?></textarea>
                    </label>
                    <label>Seconds
                        <input type="number" name="steps[<?= e($i) ?>][duration_seconds]" min="0" max="3600" value="<?= e($step['duration_seconds'] ?? '') ?>">
                    </label>
                    <label>Pour (g)
                        <input type="number" name="steps[<?= e($i) ?>][pour_g]" step="0.1" min="0" max="10000" value="<?= e($step['pour_g'] ?? '') ?>">
                    </label>
                    <button type="button" class="btn-link danger" data-remove-step>Remove</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn btn-secondary" data-add-step>+ Add step</button>
    </fieldset>

    <template id="step-template">
        <div class="step-row">
            <label class="step-main">Instruction
                <textarea name="steps[__INDEX__][instruction]" rows="2" maxlength="2000"></textarea>
            </label>
            <label>Seconds
                <input type="number" name="steps[__INDEX__][duration_seconds]" min="0" max="3600">
            </label>
            <label>Pour (g)
                <input type="number" name="steps[__INDEX__][pour_g]" step="0.1" min="0" max="10000">
            </label>
            <button type="button" class="btn-link danger" data-remove-step>Remove</button>
        </div>
    </template>

    <fieldset class="video-field">
        <legend>Brewing video</legend>

        <?php if ($currentVideo): ?>
            <div class="video-current">
                <?php if (!empty($currentVideo['thumbnail_url'])): ?>
                    <img src="<?= e($currentVideo['thumbnail_url']) ?>" alt="">
                <?php else: ?>
                    <span class="video-thumb-empty"></span>
                <?php endif; ?>
                <div>
                    <p class="video-title"><?= e($currentVideo['title'] ?? 'Video ' . $currentVideo['video_id']) ?></p>
                    <p class="video-meta"><?= e($currentVideo['channel_title'] ?? 'Details load when the guide is saved') ?></p>
                </div>
            </div>
        <?php endif; ?>

        <label>YouTube link or video ID <small>(optional)</small>
            <input type="text" id="youtube_video_id" name="youtube_video_id" maxlength="200"
                   value="<?= e($val('youtube_video_id')) ?>" placeholder="https://www.youtube.com/watch?v=…">
            <?php if ($err('youtube_video_id')): ?><span class="field-error"><?= e($err('youtube_video_id')) ?></span><?php endif; ?>
        </label>

        <?php if ($videoSearchEnabled): ?>
            <div class="video-picker" data-video-picker data-endpoint="<?= e(url('/admin/videos/search')) ?>" hidden>
                <label for="video-query">Or search YouTube</label>
                <div class="video-search-row">
                    <input type="search" id="video-query" data-video-query maxlength="100" placeholder="For example: V60 pour over recipe">
                    <button type="button" class="btn btn-secondary" data-video-search>Search</button>
                </div>
                <p class="video-status" data-video-status aria-live="polite"></p>
                <ul class="video-results" data-video-results></ul>
            </div>
        <?php else: ?>
            <p class="muted">YouTube search is off until an API key is added as <code>YOUTUBE_API_KEY</code> in <code>.env</code>. You can still paste a video link.</p>
        <?php endif; ?>

        <p><button type="button" class="btn-link danger" data-video-clear>Remove video</button></p>
    </fieldset>

    <div class="actions">
        <button type="submit">Save guide</button>
        <a class="btn btn-secondary" href="<?= e(url('/admin/guides')) ?>">Cancel</a>
    </div>
</form>
