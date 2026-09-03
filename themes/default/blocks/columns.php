<?php
/** @var array $block */
$colCount = (int)($block['col_count'] ?? 2);
if ($colCount < 1 || $colCount > 5) $colCount = 2;
$blockTitle = trim((string)($block['title'] ?? ''));
$isServiceBlock = preg_match('/^unser\s+service$/iu', $blockTitle) === 1;

$cols = [];
for ($i = 1; $i <= $colCount; $i++) {
    $title = trim((string)($block["col_{$i}_title"] ?? ''));
    $image = trim((string)($block["col_{$i}_image_url"] ?? ''));
    $text  = trim((string)($block["col_{$i}_text"]  ?? ''));
    $link  = trim((string)($block["col_{$i}_link_url"] ?? ''));
    $isInternalLink = str_starts_with($link, '/') && !str_starts_with($link, '//');
    $isAnchorLink = str_starts_with($link, '#');
    $isExternalLink = preg_match('#^https?://#i', $link) === 1;
    if ($link !== '' && !$isInternalLink && !$isAnchorLink && !$isExternalLink) {
        $link = '';
    }
    $focusStyle = '';
    $focusAttrs = '';
    if (isset($block["col_{$i}_image_url_focus_x"]) || isset($block["col_{$i}_image_url_focus_y"])) {
        $px = focus_to_percent($block["col_{$i}_image_url_focus_x"] ?? null, 50.0);
        $py = focus_to_percent($block["col_{$i}_image_url_focus_y"] ?? null, 50.0);
        $focusStyle = ' style="object-position:' . $px . '% ' . $py . '%"';
        $focusAttrs = focus_data_attributes($block["col_{$i}_image_url_focus_x"] ?? null, $block["col_{$i}_image_url_focus_y"] ?? null);
    }
    if ($title !== '' || $image !== '' || $text !== '') {
        $cols[] = ['title' => $title, 'image' => $image, 'focus_style' => $focusStyle, 'focus_attrs' => $focusAttrs, 'text' => $text, 'link' => $link];
    }
}

if ($blockTitle === '' && empty($cols)) return;
$serviceBlockId = 'service-columns-' . substr(hash('sha256', json_encode($block, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $blockTitle), 0, 10);
?>
<section
  class="block block-columns block-columns-<?= $colCount ?><?= $isServiceBlock ? ' block-columns--service' : '' ?>"
  <?= $isServiceBlock ? 'id="' . htmlspecialchars($serviceBlockId, ENT_QUOTES, 'UTF-8') . '" data-service-columns' : '' ?>
>
  <?php if ($blockTitle !== ''): ?>
    <h2><?= htmlspecialchars($blockTitle, ENT_QUOTES, 'UTF-8') ?></h2>
  <?php endif; ?>
  <div class="block-columns__grid">
  <?php foreach ($cols as $index => $col): ?>
    <?php if ($isServiceBlock): ?>
      <?php $serviceTag = $col['link'] !== '' ? 'a' : 'article'; ?>
      <<?= $serviceTag ?> class="block-columns__col<?= $col['link'] !== '' ? ' block-columns__col--linked' : '' ?>"<?= $col['link'] !== '' ? ' href="' . htmlspecialchars($col['link'], ENT_QUOTES, 'UTF-8') . '"' : '' ?> data-service-card="<?= (int)$index ?>">
        <span class="block-columns__screw is-top-left" aria-hidden="true"></span>
        <span class="block-columns__screw is-top-right" aria-hidden="true"></span>
        <span class="block-columns__screw is-bottom-left" aria-hidden="true"></span>
        <span class="block-columns__screw is-bottom-right" aria-hidden="true"></span>
        <div class="block-columns__service-head">
          <span class="block-columns__service-icon" aria-hidden="true">
            <?php if ($col['image'] !== ''): ?>
              <span class="block-columns__service-icon-image"><img src="<?= htmlspecialchars($col['image'], ENT_QUOTES, 'UTF-8') ?>" alt=""<?= $col['focus_style'] ?><?= $col['focus_attrs'] ?>></span>
            <?php else: ?>
              <span class="block-columns__service-icon-fallback"></span>
            <?php endif; ?>
          </span>
          <?php if ($col['title'] !== ''): ?>
            <h3><?= htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8') ?></h3>
          <?php endif; ?>
        </div>
        <?php if ($col['text'] !== ''): ?>
          <div class="block-columns__service-text"><?= nl2br(htmlspecialchars($col['text'], ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
      </<?= $serviceTag ?>>
    <?php else: ?>
      <?php $tileTag = $col['link'] !== '' ? 'a' : 'div'; ?>
      <<?= $tileTag ?> class="block-columns__col<?= $col['link'] !== '' ? ' block-columns__col--linked' : '' ?>"<?= $col['link'] !== '' ? ' href="' . htmlspecialchars($col['link'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
        <?php if ($col['image'] !== ''): ?>
          <img src="<?= htmlspecialchars($col['image'], ENT_QUOTES, 'UTF-8') ?>" alt=""<?= $col['focus_style'] ?><?= $col['focus_attrs'] ?>>
        <?php endif; ?>
        <?php if ($col['title'] !== ''): ?>
          <h3><?= htmlspecialchars($col['title'], ENT_QUOTES, 'UTF-8') ?></h3>
        <?php endif; ?>
        <?php if ($col['text'] !== ''): ?>
          <div><?= nl2br(htmlspecialchars($col['text'], ENT_QUOTES, 'UTF-8')) ?></div>
        <?php endif; ?>
      </<?= $tileTag ?>>
    <?php endif; ?>
  <?php endforeach; ?>
  </div>
</section>

<?php if ($isServiceBlock && count($cols) > 3): ?>
<script>
(() => {
  const root = document.getElementById(<?= json_encode($serviceBlockId, JSON_UNESCAPED_SLASHES) ?>);
  if (!root || root.dataset.serviceReady === '1') return;
  root.dataset.serviceReady = '1';

  const cards = Array.from(root.querySelectorAll('[data-service-card]'));
  const desktop = window.matchMedia('(min-width: 920px)');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  const visibleCount = 3;
  const maxStart = Math.max(cards.length - visibleCount, 0);
  let start = 0;
  let direction = 1;
  let timer = 0;

  const render = () => {
    cards.forEach((card, index) => {
      if (!desktop.matches) {
        card.dataset.pos = 'static';
        card.style.removeProperty('transform');
        return;
      }
      const offset = index - start;
      const visible = offset >= 0 && offset < visibleCount;
      card.dataset.pos = visible ? String(offset) : 'hidden';
      card.style.transform = `translateX(${offset * 100}%) translateX(${offset}rem)`;
    });
  };

  const stop = () => {
    if (timer) window.clearInterval(timer);
    timer = 0;
  };

  const advance = () => {
    let next = start + direction;
    if (next >= maxStart) {
      next = maxStart;
      direction = -1;
    } else if (next <= 0) {
      next = 0;
      direction = 1;
    }
    start = next;
    render();
  };

  const play = () => {
    stop();
    if (!desktop.matches || reducedMotion.matches || maxStart === 0) return;
    timer = window.setInterval(advance, 4500);
  };

  const reset = () => {
    if (!desktop.matches) start = 0;
    render();
    play();
  };

  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', play);
  root.addEventListener('focusin', stop);
  root.addEventListener('focusout', play);
  desktop.addEventListener('change', reset);
  reducedMotion.addEventListener('change', reset);
  render();
  play();
})();
</script>
<?php endif; ?>
