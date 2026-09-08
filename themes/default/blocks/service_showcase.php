<?php
/** @var array $block */
/** @var array|null $data */

$e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$payload = is_array($block ?? null) ? $block : [];
if (isset($block['data']) && is_array($block['data'])) {
    $payload = array_merge($payload, $block['data']);
}
if (isset($block['payload']) && is_array($block['payload'])) {
    $payload = array_merge($payload, $block['payload']);
}
if (isset($data) && is_array($data)) {
    $payload = array_merge($payload, $data);
}

$safeUrl = static function (string $url, bool $allowContact = false): string {
    $url = trim($url);
    if ($url === '' || str_starts_with($url, '//')) {
        return '';
    }
    if (str_starts_with($url, '/') || str_starts_with($url, '#')) {
        return $url;
    }
    if (preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }
    if ($allowContact && preg_match('#^(?:tel:|mailto:)#i', $url) === 1) {
        return $url;
    }
    return '';
};

$headline = trim((string)($payload['headline'] ?? ($pageTitle ?? '')));
$headlineMuted = trim((string)($payload['headline_muted'] ?? ''));
$kicker = trim((string)($payload['kicker'] ?? ''));
$lead = trim((string)($payload['lead'] ?? ''));
$stockLabel = trim((string)($payload['stock_label'] ?? ''));
$stockItems = array_values(array_filter(array_map(
    static fn(string $item): string => trim($item),
    explode(',', (string)($payload['stock_items'] ?? ''))
), static fn(string $item): bool => $item !== ''));
$itemCount = max(1, min(6, (int)($payload['item_count'] ?? 3)));
$renderIndex = max(0, (int)($payload['_render_index'] ?? 0));
$titleId = 'service-showcase-title-' . $renderIndex;

$items = [];
for ($i = 1; $i <= $itemCount; $i++) {
    $title = trim((string)($payload["item_{$i}_title"] ?? ''));
    $itemLead = trim((string)($payload["item_{$i}_lead"] ?? ''));
    $text = trim((string)($payload["item_{$i}_text"] ?? ''));
    $imageUrl = $safeUrl((string)($payload["item_{$i}_image_url"] ?? ''));
    $imageAlt = trim((string)($payload["item_{$i}_image_alt"] ?? ''));
    $caption = trim((string)($payload["item_{$i}_image_caption"] ?? ''));
    $linkUrl = $safeUrl((string)($payload["item_{$i}_link_url"] ?? ''));
    $linkText = trim((string)($payload["item_{$i}_link_text"] ?? ''));

    if ($title === '' && $itemLead === '' && $text === '' && $imageUrl === '') {
        continue;
    }

    $focusStyle = '';
    $focusAttrs = '';
    $focusXKey = "item_{$i}_image_url_focus_x";
    $focusYKey = "item_{$i}_image_url_focus_y";
    if ($imageUrl !== '' && (isset($payload[$focusXKey]) || isset($payload[$focusYKey]))) {
        if (function_exists('focus_to_percent')) {
            $focusX = focus_to_percent($payload[$focusXKey] ?? null, 50.0);
            $focusY = focus_to_percent($payload[$focusYKey] ?? null, 50.0);
            $focusStyle = ' style="object-position:' . $focusX . '% ' . $focusY . '%"';
        }
        if (function_exists('focus_data_attributes')) {
            $focusAttrs = focus_data_attributes(
                $payload[$focusXKey] ?? null,
                $payload[$focusYKey] ?? null,
                $imageUrl
            );
        }
    }

    $items[] = [
        'number' => $i,
        'title' => $title,
        'lead' => $itemLead,
        'text' => $text,
        'image_url' => $imageUrl,
        'image_alt' => $imageAlt !== '' ? $imageAlt : $title,
        'caption' => $caption,
        'link_url' => $linkUrl,
        'link_text' => $linkText,
        'focus_style' => $focusStyle,
        'focus_attrs' => $focusAttrs,
    ];
}

$closingKicker = trim((string)($payload['closing_kicker'] ?? ''));
$closingHeadline = trim((string)($payload['closing_headline'] ?? ''));
$closingText = trim((string)($payload['closing_text'] ?? ''));
$closingButtonText = trim((string)($payload['closing_button_text'] ?? ''));
$closingButtonUrl = $safeUrl((string)($payload['closing_button_url'] ?? ''));
$closingSecondaryText = trim((string)($payload['closing_secondary_text'] ?? ''));
$closingSecondaryUrl = $safeUrl((string)($payload['closing_secondary_url'] ?? ''), true);
?>
<section class="block block-service-showcase"<?= $headline !== '' ? ' aria-labelledby="' . $e($titleId) . '"' : '' ?>>
  <header class="service-showcase__opening">
    <?php if ($kicker !== ''): ?>
      <div class="service-showcase__kicker">
        <span class="service-showcase__mark" aria-hidden="true"></span>
        <span class="service-showcase__eyebrow"><?= $e($kicker) ?></span>
      </div>
    <?php endif; ?>
    <?php if ($headline !== ''): ?>
      <h1 id="<?= $e($titleId) ?>">
        <?= $e($headline) ?><?php if ($headlineMuted !== ''): ?> <span><?= $e($headlineMuted) ?></span><?php endif; ?>
      </h1>
    <?php endif; ?>
    <?php if ($lead !== ''): ?>
      <p class="service-showcase__opening-lead"><?= nl2br($e($lead)) ?></p>
    <?php endif; ?>
  </header>

  <?php if ($stockItems !== []): ?>
    <div class="service-showcase__stock">
      <?php if ($stockLabel !== ''): ?>
        <span class="service-showcase__stock-label service-showcase__eyebrow"><?= $e($stockLabel) ?></span>
      <?php endif; ?>
      <ul>
        <?php foreach ($stockItems as $stockItem): ?>
          <li><?= $e($stockItem) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($items !== []): ?>
    <div class="service-showcase__items">
      <?php foreach ($items as $position => $item): ?>
        <?php $side = $position % 2 === 0 ? 'left' : 'right'; ?>
        <article class="service-showcase__item service-showcase__item--<?= $side ?><?= $item['image_url'] === '' ? ' service-showcase__item--no-media' : '' ?>">
          <?php if ($item['image_url'] !== ''): ?>
            <figure class="service-showcase__media">
              <img src="<?= $e($item['image_url']) ?>" alt="<?= $e($item['image_alt']) ?>" loading="lazy" decoding="async"<?= $item['focus_style'] ?><?= $item['focus_attrs'] ?>>
              <?php if ($item['caption'] !== ''): ?>
                <figcaption><?= $e($item['caption']) ?></figcaption>
              <?php endif; ?>
            </figure>
          <?php endif; ?>
          <div class="service-showcase__body">
            <span class="service-showcase__number" aria-hidden="true"><?= str_pad((string)$item['number'], 2, '0', STR_PAD_LEFT) ?></span>
            <?php if ($item['title'] !== ''): ?>
              <div class="service-showcase__head">
                <span class="service-showcase__mark" aria-hidden="true"></span>
                <h2><?= $e($item['title']) ?></h2>
              </div>
            <?php endif; ?>
            <?php if ($item['lead'] !== ''): ?>
              <p class="service-showcase__lead"><?= nl2br($e($item['lead'])) ?></p>
            <?php endif; ?>
            <?php if ($item['text'] !== ''): ?>
              <p class="service-showcase__text"><?= nl2br($e($item['text'])) ?></p>
            <?php endif; ?>
            <?php if ($item['link_url'] !== '' && $item['link_text'] !== ''): ?>
              <a class="service-showcase__link" href="<?= $e($item['link_url']) ?>">
                <span><?= $e($item['link_text']) ?></span>
                <span class="service-showcase__arrow" aria-hidden="true"></span>
              </a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if ($closingHeadline !== ''): ?>
    <footer class="service-showcase__closing">
      <div>
        <?php if ($closingKicker !== ''): ?>
          <span class="service-showcase__eyebrow"><?= $e($closingKicker) ?></span>
        <?php endif; ?>
        <h2><?= $e($closingHeadline) ?></h2>
        <?php if ($closingText !== ''): ?>
          <p><?= nl2br($e($closingText)) ?></p>
        <?php endif; ?>
      </div>
      <?php if (($closingButtonText !== '' && $closingButtonUrl !== '') || ($closingSecondaryText !== '' && $closingSecondaryUrl !== '')): ?>
        <div class="service-showcase__closing-actions">
          <?php if ($closingButtonText !== '' && $closingButtonUrl !== ''): ?>
            <a class="service-showcase__button" href="<?= $e($closingButtonUrl) ?>">
              <span><?= $e($closingButtonText) ?></span>
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
            </a>
          <?php endif; ?>
          <?php if ($closingSecondaryText !== '' && $closingSecondaryUrl !== ''): ?>
            <a class="service-showcase__secondary-link" href="<?= $e($closingSecondaryUrl) ?>"><?= $e($closingSecondaryText) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </footer>
  <?php endif; ?>
</section>
