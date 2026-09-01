<?php
/** @var array $block */
/** @var array $data */

$e = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$carousel = $block;
if (isset($block['data']) && is_array($block['data'])) {
    $carousel = array_merge($carousel, $block['data']);
}
if (isset($block['payload']) && is_array($block['payload'])) {
    $carousel = array_merge($carousel, $block['payload']);
}

$headline = trim((string)($carousel['headline'] ?? 'Leistung im Fokus'));
$rawItems = $carousel['items'] ?? [];
if (!is_array($rawItems) && isset($carousel['items_json'])) {
    $decodedItems = json_decode((string)$carousel['items_json'], true);
    $rawItems = is_array($decodedItems) ? $decodedItems : [];
}
if (!is_array($rawItems)) {
    $rawItems = [];
}

$items = [];
foreach (array_slice($rawItems, 0, 12) as $rawItem) {
    if (!is_array($rawItem)) continue;
    $slug = trim((string)($rawItem['page_slug'] ?? ''));
    $title = trim((string)($rawItem['page_title'] ?? ''));
    $imageUrl = trim((string)($rawItem['image_url'] ?? ''));
    $text = trim((string)($rawItem['text'] ?? ''));
    if ($slug === '' || $slug[0] !== '/' || str_starts_with($slug, '//') || $title === '') continue;
    if ($imageUrl !== '' && preg_match('#^(https?://|/)#i', $imageUrl) !== 1) {
        $imageUrl = '';
    }
    $items[] = [
        'slug' => $slug,
        'title' => $title,
        'image_url' => $imageUrl,
        'focus_x' => focus_to_percent($rawItem['image_url_focus_x'] ?? null, 50.0),
        'focus_y' => focus_to_percent($rawItem['image_url_focus_y'] ?? null, 50.0),
        'text' => $text,
    ];
}

if ($items === []) return;

$count = count($items);
$renderIndex = max(0, (int)($block['_render_index'] ?? 0));
$carouselId = 'page-carousel-' . $renderIndex . '-' . substr(sha1((string)json_encode($items)), 0, 8);
?>
<section class="block block-page-carousel" id="<?= $e($carouselId) ?>" data-page-carousel>
  <div class="page-carousel__inner">
    <?php if ($headline !== ''): ?>
    <div class="page-carousel__header">
      <h2><?= $e($headline) ?></h2>
    </div>
    <?php endif; ?>

    <div class="page-carousel__stage" tabindex="0" role="region" aria-roledescription="Karussell" aria-label="<?= $e($headline !== '' ? $headline : 'Ausgewählte Seiten') ?>">
      <?php foreach ($items as $index => $item): ?>
        <?php
        $position = $index === 0 ? 'current' : ($index === 1 ? 'next' : ($index === $count - 1 && $count > 2 ? 'prev' : 'hidden'));
        ?>
        <a class="page-carousel__slide" href="<?= $e($item['slug']) ?>" data-carousel-slide="<?= $index ?>" data-pos="<?= $position ?>" aria-label="<?= $e($item['title']) ?>">
          <?php if ($item['image_url'] !== ''): ?>
            <img class="page-carousel__image" src="<?= $e($item['image_url']) ?>" alt="" loading="lazy" draggable="false" style="object-position:<?= $e((string)$item['focus_x']) ?>% <?= $e((string)$item['focus_y']) ?>%">
          <?php else: ?>
            <span class="page-carousel__image-placeholder" aria-hidden="true"></span>
          <?php endif; ?>
          <span class="page-carousel__scrim" aria-hidden="true"></span>
          <span class="page-carousel__content">
            <span class="page-carousel__card-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M7 17 17 7M8 7h9v9"/></svg>
            </span>
            <span class="page-carousel__copy">
              <strong><?= $e($item['title']) ?></strong>
              <?php if ($item['text'] !== ''): ?><span><?= nl2br($e($item['text'])) ?></span><?php endif; ?>
            </span>
          </span>
        </a>
      <?php endforeach; ?>

      <?php if ($count > 1): ?>
        <button class="page-carousel__nav page-carousel__nav--prev" type="button" data-carousel-prev aria-label="Vorherige Seite">
          <svg viewBox="0 0 24 24"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button class="page-carousel__nav page-carousel__nav--next" type="button" data-carousel-next aria-label="Nächste Seite">
          <svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
        </button>
      <?php endif; ?>
    </div>

    <?php if ($count > 1): ?>
      <div class="page-carousel__dots" role="group" aria-label="Karussell-Navigation">
        <?php foreach ($items as $index => $item): ?>
          <button type="button" data-carousel-dot="<?= $index ?>" aria-label="<?= $e($item['title']) ?> anzeigen"<?= $index === 0 ? ' aria-current="true"' : '' ?>></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <span class="page-carousel__status" data-carousel-status aria-live="polite"><?= $count > 0 ? '1 von ' . $count : '' ?></span>
  </div>
</section>
<script>
(() => {
  const root = document.getElementById(<?= json_encode($carouselId, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
  if (!root || root.dataset.carouselReady === '1') return;
  root.dataset.carouselReady = '1';
  const slides = Array.from(root.querySelectorAll('[data-carousel-slide]'));
  const dots = Array.from(root.querySelectorAll('[data-carousel-dot]'));
  const previous = root.querySelector('[data-carousel-prev]');
  const next = root.querySelector('[data-carousel-next]');
  const stage = root.querySelector('.page-carousel__stage');
  const status = root.querySelector('[data-carousel-status]');
  const sidebarLinks = Array.from(document.querySelectorAll('.site-sidebar .site-nav a[href]'));
  const total = slides.length;
  let current = 0;
  let timer = null;
  let pointerStart = null;

  const normalizePath = (value) => {
    try {
      const path = new URL(value, window.location.origin).pathname.replace(/\/+$/, '');
      return path || '/';
    } catch (_) {
      return '';
    }
  };

  const syncSidebar = () => {
    const focusedPath = normalizePath(slides[current]?.getAttribute('href') || '');
    let hasMatch = false;
    sidebarLinks.forEach((link) => {
      const matches = focusedPath !== '' && normalizePath(link.getAttribute('href') || '') === focusedPath;
      link.classList.toggle('is-carousel-focus', matches);
      hasMatch = hasMatch || matches;
    });
    document.body.classList.toggle('has-carousel-nav-focus', hasMatch);
  };

  const show = (requested) => {
    if (total === 0) return;
    current = (requested % total + total) % total;
    slides.forEach((slide, index) => {
      const offset = (index - current + total) % total;
      const position = offset === 0 ? 'current' : (offset === 1 ? 'next' : (offset === total - 1 && total > 2 ? 'prev' : 'hidden'));
      slide.dataset.pos = position;
      slide.tabIndex = position === 'current' ? 0 : -1;
      slide.setAttribute('aria-hidden', position === 'hidden' ? 'true' : 'false');
    });
    dots.forEach((dot, index) => {
      if (index === current) dot.setAttribute('aria-current', 'true');
      else dot.removeAttribute('aria-current');
    });
    if (status) status.textContent = `${current + 1} von ${total}`;
    syncSidebar();
  };

  const stop = () => {
    if (timer !== null) window.clearInterval(timer);
    timer = null;
  };
  const start = () => {
    stop();
    if (total < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    timer = window.setInterval(() => show(current + 1), 4500);
  };
  const restart = () => { show(current); start(); };

  slides.forEach((slide, index) => {
    slide.addEventListener('click', (event) => {
      if (slide.dataset.pos === 'current') return;
      event.preventDefault();
      current = index;
      restart();
    });
  });
  dots.forEach((dot, index) => dot.addEventListener('click', () => {
    current = index;
    restart();
  }));
  previous?.addEventListener('click', () => {
    current -= 1;
    restart();
  });
  next?.addEventListener('click', () => {
    current += 1;
    restart();
  });
  stage?.addEventListener('keydown', (event) => {
    if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
    event.preventDefault();
    current += event.key === 'ArrowRight' ? 1 : -1;
    restart();
  });
  stage?.addEventListener('pointerdown', (event) => {
    if (event.button !== 0 || event.target.closest('button')) return;
    pointerStart = event.clientX;
    stop();
  });
  stage?.addEventListener('pointerup', (event) => {
    if (pointerStart === null) return;
    const distance = event.clientX - pointerStart;
    pointerStart = null;
    if (Math.abs(distance) > 45) current += distance < 0 ? 1 : -1;
    restart();
  });
  stage?.addEventListener('pointercancel', () => {
    pointerStart = null;
    start();
  });
  root.addEventListener('mouseenter', stop);
  root.addEventListener('mouseleave', start);
  root.addEventListener('focusin', stop);
  root.addEventListener('focusout', (event) => {
    if (!root.contains(event.relatedTarget)) start();
  });

  show(0);
  start();
})();
</script>
