<?php
use function Core\{e, asset};
// Small transparent PNG logo. Set $logoVariant = 'white' before including it on dark backgrounds.
$logoFile = ($logoVariant ?? 'black') === 'white' ? 'images/logo-white-small.png' : 'images/logo-black-small.png';
?>
<img class="logo-mark" src="<?= e(asset($logoFile)) ?>" alt="" width="40" height="40">
