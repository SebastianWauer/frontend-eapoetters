(() => {
  'use strict';

  const selector = '[data-cms-focus-x], [data-cms-focus-y]';
  const clamp = (value, min, max) => Math.min(max, Math.max(min, value));
  const backgroundImages = new WeakMap();

  function focusValue(element, axis) {
    const raw = Number(element.dataset[axis === 'x' ? 'cmsFocusX' : 'cmsFocusY']);
    return Number.isFinite(raw) ? clamp(raw, 0, 100) / 100 : 0.5;
  }

  // CSS percentages align the same percentage of image and container. This
  // converts a CMS focal point into the percentage that places it at the
  // container centre, clamped where an image edge makes that impossible.
  function centeredPosition(containerSize, renderedSize, focus) {
    const overflow = renderedSize - containerSize;
    if (overflow <= 0.5) return 50;

    const desiredOffset = (containerSize / 2) - (focus * renderedSize);
    const offset = clamp(desiredOffset, -overflow, 0);
    return clamp((-offset / overflow) * 100, 0, 100);
  }

  function coverPosition(element, naturalWidth, naturalHeight) {
    const width = element.clientWidth;
    const height = element.clientHeight;
    if (width <= 0 || height <= 0 || naturalWidth <= 0 || naturalHeight <= 0) {
      return null;
    }

    const scale = Math.max(width / naturalWidth, height / naturalHeight);
    const renderedWidth = naturalWidth * scale;
    const renderedHeight = naturalHeight * scale;
    const x = centeredPosition(width, renderedWidth, focusValue(element, 'x'));
    const y = centeredPosition(height, renderedHeight, focusValue(element, 'y'));
    return `${x.toFixed(3)}% ${y.toFixed(3)}%`;
  }

  function applyImageFocus(image) {
    if (!(image instanceof HTMLImageElement)) return;
    if (getComputedStyle(image).objectFit !== 'cover') return;

    const position = coverPosition(image, image.naturalWidth, image.naturalHeight);
    if (position !== null) image.style.objectPosition = position;
  }

  function applyBackgroundFocus(element) {
    const url = element.dataset.cmsFocusBackground || '';
    if (url === '') return;

    let image = backgroundImages.get(element);
    if (!image) {
      image = new Image();
      image.addEventListener('load', () => applyBackgroundFocus(element));
      image.src = url;
      backgroundImages.set(element, image);
    }
    if (!image.complete || image.naturalWidth <= 0 || image.naturalHeight <= 0) return;

    const position = coverPosition(element, image.naturalWidth, image.naturalHeight);
    if (position !== null) element.style.setProperty('background-position', position, 'important');
  }

  function applyFocus(element) {
    if (element instanceof HTMLImageElement) {
      applyImageFocus(element);
      return;
    }
    applyBackgroundFocus(element);
  }

  function register(element) {
    if (!(element instanceof HTMLElement) || element.dataset.cmsFocusReady === '1') return;
    element.dataset.cmsFocusReady = '1';

    if (element instanceof HTMLImageElement && !element.complete) {
      element.addEventListener('load', () => applyFocus(element));
    }
    if ('ResizeObserver' in window) {
      const observer = new ResizeObserver(() => applyFocus(element));
      observer.observe(element);
    }
    applyFocus(element);
  }

  function scan(root = document) {
    if (root instanceof HTMLElement && root.matches(selector)) register(root);
    root.querySelectorAll(selector).forEach(register);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => scan(), { once: true });
  } else {
    scan();
  }

  new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) scan(node);
      });
    });
  }).observe(document.documentElement, { childList: true, subtree: true });
})();
