<?php
/** @var array $block */
/** @var array $data */

$e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$dualHero = $block;
if (isset($block['data']) && is_array($block['data'])) {
    $dualHero = array_merge($dualHero, $block['data']);
}
if (isset($block['payload']) && is_array($block['payload'])) {
    $dualHero = array_merge($dualHero, $block['payload']);
}

$panels = [];
foreach (['left', 'right'] as $side) {
    $backgroundUrl = trim((string)($dualHero[$side . '_background_image_url'] ?? ''));
    $foregroundUrl = trim((string)($dualHero[$side . '_foreground_image_url'] ?? ''));
    $styleParts = [];
    if ($backgroundUrl !== '') {
        $styleParts[] = 'background-image:url(' . $e($backgroundUrl) . ')';
        if (isset($dualHero[$side . '_background_image_url_focus_x']) || isset($dualHero[$side . '_background_image_url_focus_y'])) {
            $focusX = focus_to_percent($dualHero[$side . '_background_image_url_focus_x'] ?? null, 50.0);
            $focusY = focus_to_percent($dualHero[$side . '_background_image_url_focus_y'] ?? null, 50.0);
            $styleParts[] = 'background-position:' . $focusX . '% ' . $focusY . '%';
        }
    }

    $panels[] = [
        'side' => $side,
        'background_url' => $backgroundUrl,
        'foreground_url' => $foregroundUrl,
        'topline' => trim((string)($dualHero[$side . '_topline'] ?? '')),
        'headline' => trim((string)($dualHero[$side . '_headline'] ?? '')),
        'subtitle' => trim((string)($dualHero[$side . '_subtitle'] ?? '')),
        'button_text' => trim((string)($dualHero[$side . '_button_text'] ?? '')),
        'button_url' => trim((string)($dualHero[$side . '_button_url'] ?? '')),
        'button_secondary_text' => trim((string)($dualHero[$side . '_button_secondary_text'] ?? '')),
        'button_secondary_url' => trim((string)($dualHero[$side . '_button_secondary_url'] ?? '')),
        'style' => $styleParts !== [] ? ' style="' . implode(';', $styleParts) . '"' : '',
    ];
}
?>
<section class="block block-dual-hero">
  <div class="dual-hero__grid">
    <?php foreach ($panels as $panel): ?>
      <?php $hasActions = ($panel['button_text'] !== '' && $panel['button_url'] !== '') || ($panel['button_secondary_text'] !== '' && $panel['button_secondary_url'] !== ''); ?>
      <article class="dual-hero__panel dual-hero__panel--<?= $e($panel['side']) ?><?= $panel['background_url'] !== '' ? ' dual-hero__panel--has-background' : '' ?>"<?= $panel['style'] ?>>
        <div class="dual-hero__content">
          <?php if ($panel['foreground_url'] !== ''): ?>
            <img class="dual-hero__foreground" src="<?= $e($panel['foreground_url']) ?>" alt="">
          <?php endif; ?>
          <?php if ($panel['topline'] !== ''): ?>
            <div class="dual-hero__topline"><?= $e($panel['topline']) ?></div>
          <?php endif; ?>
          <?php if ($panel['headline'] !== ''): ?>
            <h2><?= $e($panel['headline']) ?></h2>
          <?php endif; ?>
          <?php if ($panel['subtitle'] !== ''): ?>
            <p><?= nl2br($e($panel['subtitle'])) ?></p>
          <?php endif; ?>
          <?php if ($hasActions): ?>
            <div class="dual-hero__actions">
              <?php if ($panel['button_text'] !== '' && $panel['button_url'] !== ''): ?>
                <a class="hero-btn hero-btn--primary" href="<?= $e($panel['button_url']) ?>"><?= $e($panel['button_text']) ?></a>
              <?php endif; ?>
              <?php if ($panel['button_secondary_text'] !== '' && $panel['button_secondary_url'] !== ''): ?>
                <a class="hero-btn hero-btn--secondary" href="<?= $e($panel['button_secondary_url']) ?>"><?= $e($panel['button_secondary_text']) ?></a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
