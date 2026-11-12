<?php
function renderBrandMark($size = 'brand-mark')
{
    ?>
<span class="<?= htmlspecialchars($size) ?>" aria-hidden="true"><svg viewBox="0 0 100 100" focusable="false">
<circle cx="50" cy="50" r="43" class="brand-orbit"/><path d="M50 20v30M50 50 25 68M50 50l25 18M35 35l15 15m15-15L50 50M50 50v32m-10-10 10 10 10-10" class="brand-lines"/>
<circle cx="50" cy="50" r="8" class="brand-core"/><circle cx="25" cy="68" r="3.5" class="brand-node"/><circle cx="75" cy="68" r="3.5" class="brand-node"/><circle cx="50" cy="20" r="3.5" class="brand-node"/><circle cx="50" cy="82" r="4" class="brand-node"/>
</svg></span>
<?php } ?>