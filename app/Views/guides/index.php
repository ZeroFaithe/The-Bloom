<?php
use App\Models\Guide;
use App\Services\RecipeScaler;
use function Core\{e, url};
?>
<section class="hero">
    <h1>Brew better coffee at home.</h1>
    <p class="lede">Step-by-step recipes with exact doses, water temperatures and timed pours. Pick a brewing method and follow along.</p>
</section>

<form class="filters" method="get" action="<?= e(url('/guides')) ?>" role="search">
    <label>
        <span class="sr-only">Search guides</span>
        <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Search by name or method" maxlength="100">
    </label>
    <label>
        <span class="sr-only">Brewing method</span>
        <select name="method">
            <option value="">All methods</option>
            <?php foreach ($methods as $m): ?>
                <option value="<?= e($m['slug']) ?>" <?= $filters['method'] === $m['slug'] ? 'selected' : '' ?>><?= e($m['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        <span class="sr-only">Difficulty</span>
        <select name="difficulty">
            <option value="">Any difficulty</option>
            <?php foreach (Guide::DIFFICULTIES as $d): ?>
                <option value="<?= e($d) ?>" <?= $filters['difficulty'] === $d ? 'selected' : '' ?>><?= e(ucfirst($d)) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit">Find guides</button>
</form>

<p class="result-count">
    <?= count($guides) === 1 ? '1 guide' : e(count($guides)) . ' guides' ?>
    <?php if ($filtered): ?><a href="<?= e(url('/guides')) ?>">Clear filters</a><?php endif; ?>
</p>

<?php if ($guides === []): ?>
    <div class="empty">
        <?php if ($filtered): ?>
            <h2>No guides match those filters.</h2>
            <p>Try another method or a shorter search, or <a href="<?= e(url('/guides')) ?>">see every guide</a>.</p>
        <?php else: ?>
            <h2>No guides published yet.</h2>
            <p>Staff can publish a guide from the dashboard, and it will appear here.</p>
        <?php endif; ?>
    </div>
<?php else: ?>
    <ul class="guide-list">
        <?php foreach ($guides as $g): ?>
            <li class="guide-row">
                <div>
                    <p class="guide-meta">
                        <a href="<?= e(url('/guides?method=' . rawurlencode($g['method_slug']))) ?>"><?= e($g['method_name']) ?></a>
                        <span class="difficulty"><?= e(ucfirst($g['difficulty'])) ?></span>
                    </p>
                    <h2><a href="<?= e(url('/guides/' . rawurlencode($g['slug']))) ?>"><?= e($g['title']) ?></a></h2>
                    <?php if (!empty($g['summary'])): ?>
                        <p class="guide-summary"><?= e($g['summary']) ?></p>
                    <?php endif; ?>
                </div>
                <dl class="guide-specs">
                    <div class="spec-ratio"><dt>Ratio</dt><dd><?= e(RecipeScaler::formatRatio((float) $g['coffee_g'], (float) $g['water_g'])) ?></dd></div>
                    <div><dt>Coffee</dt><dd><?= e(RecipeScaler::number($g['coffee_g'])) ?> g</dd></div>
                    <div><dt>Water</dt><dd><?= e(RecipeScaler::number($g['water_g'])) ?> g</dd></div>
                    <div><dt>Temp</dt><dd><?= e($g['temperature_c']) ?> °C</dd></div>
                </dl>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
