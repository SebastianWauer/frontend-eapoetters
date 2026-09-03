<?php
$catalogTitle = trim((string)($data['title'] ?? 'Katalog'));
$catalogSubtitle = trim((string)($data['subtitle'] ?? ''));
$pdfUrl = trim((string)($data['pdf_url'] ?? ''));
$pageUrlTemplate = trim((string)($data['page_url_template'] ?? ''));
$pageCount = max(0, (int)($data['page_count'] ?? 0));
$catalogReady = !empty($data['catalog_ready']) || ((string)($data['catalog_status'] ?? '') === 'ready');
$downloadLabel = trim((string)($data['download_label'] ?? 'PDF herunterladen'));
$backLabel = trim((string)($data['back_label'] ?? 'Startseite'));
$backUrl = trim((string)($data['back_url'] ?? '/'));
static $catalogInstance = 0;
$catalogInstance++;
$catalogSeed = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)($block['id'] ?? ($block['_render_index'] ?? 0)));
$catalogId = 'catalog-' . ($catalogSeed !== '' ? $catalogSeed . '-' : '') . $catalogInstance;
if ($catalogTitle === '') $catalogTitle = 'Katalog';
if ($downloadLabel === '') $downloadLabel = 'PDF herunterladen';
if ($backLabel === '') $backLabel = 'Startseite';
if ($backUrl === '') $backUrl = '/';
$usable = $catalogReady && $pageCount > 0 && $pageUrlTemplate !== '';
$requestedPage = max(1, min($pageCount > 0 ? $pageCount : 1, (int)($_GET['page'] ?? $_GET['seite'] ?? 1)));
?>
<section
  class="block-catalog<?= $usable ? ' is-ready' : ' is-unavailable' ?>"
  id="<?= e($catalogId) ?>"
  data-page-count="<?= $pageCount ?>"
  data-page-url-template="<?= e($pageUrlTemplate) ?>"
  data-initial-page="<?= $requestedPage ?>"
>
  <header class="block-catalog__toolbar">
    <a class="block-catalog__back" href="<?= e($backUrl) ?>">
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 19-7-7 7-7M19 12H5"/></svg>
      <span><?= e($backLabel) ?></span>
    </a>
    <div class="block-catalog__heading">
      <strong><?= e($catalogTitle) ?></strong>
      <?php if ($catalogSubtitle !== ''): ?><span><?= e($catalogSubtitle) ?></span><?php endif; ?>
    </div>
    <?php if ($pdfUrl !== ''): ?>
      <a class="block-catalog__download" href="<?= e($pdfUrl) ?>">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 5-5m-5 5-5-5M5 15v4h14v-4"/></svg>
        <span><?= e($downloadLabel) ?></span>
      </a>
    <?php endif; ?>
  </header>

  <?php if ($usable): ?>
    <div class="block-catalog__stage">
      <div class="block-catalog__loading" aria-live="polite">
        <span class="block-catalog__spinner" aria-hidden="true"></span>
        <span>Katalogseite wird geladen …</span>
      </div>
      <button class="block-catalog__arrow is-prev" type="button" aria-label="Vorherige Seite">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <div class="block-catalog__book is-cover" role="group" aria-label="Blätterkatalog">
        <figure class="block-catalog__page is-left" hidden>
          <img alt="" decoding="async">
          <figcaption></figcaption>
        </figure>
        <figure class="block-catalog__page is-right">
          <img alt="" decoding="async">
          <figcaption></figcaption>
        </figure>
      </div>
      <button class="block-catalog__arrow is-next" type="button" aria-label="Nächste Seite">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>
    <div class="block-catalog__controls">
      <button type="button" class="block-catalog__control is-prev" aria-label="Vorherige Seite">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <span class="block-catalog__indicator" aria-live="polite">Seite 1 / <?= $pageCount ?></span>
      <button type="button" class="block-catalog__control is-next" aria-label="Nächste Seite">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>
  <?php else: ?>
    <div class="block-catalog__empty">
      <strong>Der Blätterkatalog wird vorbereitet.</strong>
      <span>Die PDF kann bereits über den Download geöffnet werden, sobald sie hinterlegt ist.</span>
    </div>
  <?php endif; ?>
</section>
<?php if ($usable): ?>
<script>
(function () {
  var root = document.getElementById(<?= json_encode($catalogId, JSON_UNESCAPED_SLASHES) ?>);
  if (!root || root.dataset.initialized === '1') return;
  root.dataset.initialized = '1';

  var pageCount = Number(root.dataset.pageCount || 0);
  var template = String(root.dataset.pageUrlTemplate || '');
  var book = root.querySelector('.block-catalog__book');
  var left = root.querySelector('.block-catalog__page.is-left');
  var right = root.querySelector('.block-catalog__page.is-right');
  var indicator = root.querySelector('.block-catalog__indicator');
  var prevButtons = Array.from(root.querySelectorAll('.is-prev'));
  var nextButtons = Array.from(root.querySelectorAll('.is-next'));
  var mobileQuery = window.matchMedia('(max-width: 719px)');
  var initialPage = Math.max(1, Math.min(pageCount, Number(root.dataset.initialPage || 1)));
  var currentPage = normalizedStartPage(initialPage);
  var transitionLocked = false;
  var visible = true;
  var loaded = new Map();

  function pageUrl(page) {
    return template.replace('{page}', String(page));
  }

  function normalizedStartPage(page) {
    page = Math.max(1, Math.min(pageCount, Number(page) || 1));
    if (mobileQuery.matches || page === 1) return page;
    return page % 2 === 0 ? page : page - 1;
  }

  function updatePageUrl(page) {
    if (!window.history || typeof window.history.replaceState !== 'function') return;
    var url = new URL(window.location.href);
    if (page <= 1) url.searchParams.delete('page');
    else url.searchParams.set('page', String(page));
    url.searchParams.delete('seite');
    window.history.replaceState(window.history.state, '', url.pathname + url.search + url.hash);
  }

  function pagesFor(startPage) {
    if (mobileQuery.matches || startPage === 1) return [startPage];
    return [startPage, Math.min(pageCount, startPage + 1)].filter(function (page, index, pages) {
      return page > 0 && pages.indexOf(page) === index;
    });
  }

  function loadPage(page) {
    if (page < 1 || page > pageCount) return Promise.resolve('');
    if (loaded.has(page)) return loaded.get(page);
    var promise = new Promise(function (resolve, reject) {
      var image = new Image();
      image.decoding = 'async';
      image.onload = function () { resolve(image.src); };
      image.onerror = function () { reject(new Error('Seite ' + page + ' konnte nicht geladen werden.')); };
      image.src = pageUrl(page);
    });
    loaded.set(page, promise);
    promise.catch(function () { loaded.delete(page); });
    return promise;
  }

  function preloadAround(startPage) {
    var candidates = mobileQuery.matches
      ? [startPage - 1, startPage + 1, startPage + 2]
      : [startPage - 2, startPage + 2, startPage + 3];
    candidates.forEach(function (page) {
      if (page >= 1 && page <= pageCount) loadPage(page).catch(function () {});
    });
  }

  function setSlot(slot, page, src) {
    if (!page) {
      slot.hidden = true;
      return;
    }
    var image = slot.querySelector('img');
    var caption = slot.querySelector('figcaption');
    image.src = src;
    image.alt = 'Katalogseite ' + page;
    caption.textContent = 'Seite ' + page;
    slot.hidden = false;
  }

  async function renderPages(direction) {
    var pages = pagesFor(currentPage);
    root.classList.add('is-loading');
    try {
      var sources = await Promise.all(pages.map(loadPage));
      var isCover = !mobileQuery.matches && pages.length === 1;
      book.classList.toggle('is-cover', isCover);
      if (mobileQuery.matches || isCover) {
        setSlot(left, 0, '');
        setSlot(right, pages[0], sources[0]);
      } else {
        setSlot(left, pages[0], sources[0]);
        setSlot(right, pages[1] || 0, sources[1] || '');
      }

      if (direction) {
        book.classList.remove('is-turning-next', 'is-turning-prev');
        void book.offsetWidth;
        book.classList.add(direction === 'next' ? 'is-turning-next' : 'is-turning-prev');
        window.setTimeout(function () {
          book.classList.remove('is-turning-next', 'is-turning-prev');
        }, 520);
      }

      var lastVisible = pages[pages.length - 1];
      indicator.textContent = pages.length > 1
        ? 'Seiten ' + pages[0] + '–' + lastVisible + ' / ' + pageCount
        : 'Seite ' + pages[0] + ' / ' + pageCount;
      prevButtons.forEach(function (button) { button.disabled = currentPage <= 1; });
      nextButtons.forEach(function (button) { button.disabled = lastVisible >= pageCount; });
      preloadAround(currentPage);
    } catch (error) {
      indicator.textContent = 'Katalogseite konnte nicht geladen werden';
    } finally {
      root.classList.remove('is-loading');
      transitionLocked = false;
    }
  }

  function go(direction) {
    if (transitionLocked) return;
    var nextPage = currentPage;
    if (mobileQuery.matches) {
      nextPage += direction === 'next' ? 1 : -1;
    } else if (direction === 'next') {
      nextPage = currentPage === 1 ? 2 : currentPage + 2;
    } else {
      nextPage = currentPage <= 2 ? 1 : currentPage - 2;
    }
    if (nextPage < 1 || nextPage > pageCount) return;
    transitionLocked = true;
    currentPage = nextPage;
    updatePageUrl(currentPage);
    renderPages(direction);
  }

  prevButtons.forEach(function (button) { button.addEventListener('click', function () { go('prev'); }); });
  nextButtons.forEach(function (button) { button.addEventListener('click', function () { go('next'); }); });
  var handleViewportChange = function () {
    currentPage = normalizedStartPage(currentPage);
    renderPages('');
  };
  if (typeof mobileQuery.addEventListener === 'function') mobileQuery.addEventListener('change', handleViewportChange);
  else if (typeof mobileQuery.addListener === 'function') mobileQuery.addListener(handleViewportChange);

  var touchStartX = null;
  book.addEventListener('pointerdown', function (event) { touchStartX = event.clientX; });
  book.addEventListener('pointerup', function (event) {
    if (touchStartX === null) return;
    var distance = event.clientX - touchStartX;
    touchStartX = null;
    if (Math.abs(distance) < 45) return;
    go(distance < 0 ? 'next' : 'prev');
  });
  book.addEventListener('pointercancel', function () { touchStartX = null; });

  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entries) {
      visible = entries.some(function (entry) { return entry.isIntersecting; });
    }, {threshold: 0.2}).observe(root);
  }
  document.addEventListener('keydown', function (event) {
    if (!visible) return;
    if (event.key === 'ArrowLeft') go('prev');
    if (event.key === 'ArrowRight') go('next');
  });

  renderPages('');
}());
</script>
<?php endif; ?>
