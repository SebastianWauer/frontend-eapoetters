<?php
/** @var array $block */
$e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$hero = $block;
if (isset($block['data']) && is_array($block['data'])) {
    $hero = array_merge($hero, $block['data']);
}
if (isset($block['payload']) && is_array($block['payload'])) {
    $hero = array_merge($hero, $block['payload']);
}
$bgStyle = '';
$focusAttrs = '';
$styleParts = [];
if (!empty($hero['image_url'])) {
    $styleParts[] = 'background-image:url(' . $e((string)$hero['image_url']) . ')';
    $styleParts[] = 'background-size:cover !important';
    $styleParts[] = 'background-repeat:no-repeat !important';
    $styleParts[] = 'background-position:50% 50% !important';

    if (isset($hero['image_url_focus_x']) || isset($hero['image_url_focus_y'])) {
        $px = focus_to_percent($hero['image_url_focus_x'] ?? null, 50.0);
        $py = focus_to_percent($hero['image_url_focus_y'] ?? null, 50.0);
        $styleParts[] = 'background-position:' . $px . '% ' . $py . '% !important';
        $focusAttrs = focus_data_attributes(
            $hero['image_url_focus_x'] ?? null,
            $hero['image_url_focus_y'] ?? null,
            (string)$hero['image_url']
        );
    }
}
$bgStyle = ' style="' . implode(';', $styleParts) . '"';
?>
<section class="block block-hero"<?= $bgStyle ?><?= $focusAttrs ?>>
  <?php if (!empty($hero['topline'])): ?>
    <div class="hero-topline"><?= $e((string)$hero['topline']) ?></div>
  <?php endif; ?>
  <?php if (!empty($hero['headline'])): ?>
    <h1><?= $e((string)$hero['headline']) ?></h1>
  <?php endif; ?>
  <?php if (!empty($hero['subtitle'])): ?>
    <p><?= $e((string)$hero['subtitle']) ?></p>
  <?php endif; ?>
  <?php if ((!empty($hero['button_url']) && !empty($hero['button_text'])) || (!empty($hero['button_secondary_url']) && !empty($hero['button_secondary_text']))): ?>
    <div class="hero-actions">
      <?php if (!empty($hero['button_url']) && !empty($hero['button_text'])): ?>
        <a href="<?= $e((string)$hero['button_url']) ?>" class="hero-btn hero-btn--primary">
          <?= $e((string)$hero['button_text']) ?>
        </a>
      <?php endif; ?>
      <?php if (!empty($hero['button_secondary_url']) && !empty($hero['button_secondary_text'])): ?>
        <a href="<?= $e((string)$hero['button_secondary_url']) ?>" class="hero-btn hero-btn--secondary">
          <?= $e((string)$hero['button_secondary_text']) ?>
        </a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
