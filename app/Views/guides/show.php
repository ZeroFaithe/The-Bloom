<?php
use App\Services\RecipeScaler;
use function Core\{e, url, asset};
$coffee = (float) $guide['coffee_g'];
?>
<?php if ($isPreview): ?>
    <p class="alert alert-preview">Draft preview. Only staff can see this page until the guide is published.</p>
<?php endif; ?>

<p class="crumb"><a href="<?= e(url('/guides')) ?>">All guides</a></p>

<header class="guide-head">
    <p class="guide-meta">
        <a href="<?= e(url('/guides?method=' . rawurlencode($guide['method_slug']))) ?>"><?= e($guide['method_name']) ?></a>
        <span class="difficulty"><?= e(ucfirst($guide['difficulty'])) ?></span>
    </p>
    <h1><?= e($guide['title']) ?></h1>
    <?php if (!empty($guide['summary'])): ?>
        <p class="lede"><?= nl2br(e($guide['summary'])) ?></p>
    <?php endif; ?>
</header>

<div class="guide-layout">
    <aside class="brew-card" aria-label="Recipe" data-base-coffee="<?= e($coffee) ?>">
        <p class="ratio">
            <span class="ratio-value"><?= e(RecipeScaler::formatRatio($coffee, (float) $guide['water_g'])) ?></span>
            <span class="ratio-label">Coffee to water ratio</span>
        </p>
        <dl class="brew-specs">
            <div><dt>Coffee</dt><dd><span data-scale data-base="<?= e($coffee) ?>"><?= e(RecipeScaler::number($coffee)) ?></span> g</dd></div>
            <div><dt>Water</dt><dd><span data-scale data-base="<?= e($guide['water_g']) ?>"><?= e(RecipeScaler::number($guide['water_g'])) ?></span> g</dd></div>
            <div><dt>Water temperature</dt><dd><?= e($guide['temperature_c']) ?> °C</dd></div>
            <?php if ($totalSeconds > 0): ?>
                <div><dt>Brew time</dt><dd><?= e(RecipeScaler::clock($totalSeconds)) ?></dd></div>
            <?php endif; ?>
        </dl>
        <form class="scaler" hidden>
            <label for="scale-coffee">Brewing a different amount? Enter your coffee dose.</label>
            <div class="scaler-row">
                <input id="scale-coffee" type="number" min="1" max="1000" step="0.5" inputmode="decimal" value="<?= e(RecipeScaler::number($coffee)) ?>">
                <span>g</span>
                <button type="button" data-scale-reset>Reset</button>
            </div>
            <p class="scaler-note" aria-live="polite">Water and pours change to keep the same ratio.</p>
        </form>
    </aside>

    <div class="guide-main">
    <section class="steps" aria-labelledby="steps-title">
        <h2 id="steps-title">Steps</h2>
        <?php if ($steps === []): ?>
            <p class="muted">This guide has no steps yet.</p>
        <?php else: ?>
            <ol class="step-list">
                <?php foreach ($steps as $step): ?>
                    <li class="step">
                        <?php if ($step['ends_at'] !== null): ?>
                            <p class="step-time"><?= e(RecipeScaler::clock($step['starts_at'])) ?> to <?= e(RecipeScaler::clock($step['ends_at'])) ?></p>
                        <?php endif; ?>
                        <p class="step-text"><?= nl2br(e($step['instruction'])) ?></p>
                        <?php if ($step['pour_g'] !== null): ?>
                            <p class="step-pour">Pour <span data-scale data-base="<?= e($step['pour_g']) ?>"><?= e(RecipeScaler::number($step['pour_g'])) ?></span> g</p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>

    <?php if ($video): ?>
        <section class="video-section" aria-labelledby="video-heading">
            <h2 id="video-heading">Watch it brewed</h2>
            <div class="video-frame">
                <iframe src="https://www.youtube-nocookie.com/embed/<?= e($video['video_id']) ?>"
                        title="<?= e($video['title'] ?? 'Brewing video for ' . $guide['title']) ?>"
                        loading="lazy"
                        allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen></iframe>
            </div>
            <?php if (!empty($video['title'])): ?>
                <p class="video-caption">“<?= e($video['title']) ?>” by <?= e($video['channel_title']) ?>, on YouTube</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </div>
</div>

<script src="<?= e(asset('js/guide.js')) ?>" defer></script>
