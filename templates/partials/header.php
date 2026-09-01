<?php
/**
 * Build navigation tree from flat array
 */
if (!function_exists('buildNavTree')) {
    function buildNavTree(array $items, $parentId = null, array $visitedIds = []): array {
        $branch = [];
        
        foreach ($items as $item) {
            // Normalize parent_id key and convert to int|null
            $rawParentId = $item['parent_id'] ?? $item['parentId'] ?? null;
            $itemParentId = null;
            if ($rawParentId !== null && $rawParentId !== '') {
                $itemParentId = (int)$rawParentId;
            }
            
            // Match logic based on parentId type
            $matches = false;
            if ($parentId === null) {
                // Looking for null parents
                $matches = ($itemParentId === null);
            } elseif ($parentId === 0) {
                // Looking for 0 parents
                $matches = ($itemParentId === 0);
            } else {
                // Looking for specific non-root parent
                $matches = ($itemParentId === (int)$parentId);
            }
            
            if (!$matches) {
                continue;
            }
            
            // Normalize URL
            $url = $item['url'] ?? null;
            if ($url === null) {
                $itemSlug = (string)($item['slug'] ?? '');
                $url = in_array($itemSlug, ['start', 'home'], true) ? '/' : '/' . $itemSlug;
            }
            
            // Skip absolute URLs (security)
            if (preg_match('#^https?://#i', $url)) {
                continue;
            }
            
            // Skip protocol-relative URLs (security)
            if (substr($url, 0, 2) === '//') {
                continue;
            }
            
            // Ensure URL starts with /
            if ($url === '' || $url[0] !== '/') {
                $url = '/' . $url;
            }
            
            $nodeId = (int)($item['id'] ?? 0);
            
            // Recursion protection: check if this node was already visited
            if (in_array($nodeId, $visitedIds, true)) {
                continue;
            }
            
            $node = [
                'id' => $nodeId,
                'title' => (string)($item['title'] ?? 'Untitled'),
                'url' => $url,
                'slug' => (string)($item['slug'] ?? ''),
                'icon_url' => trim((string)($item['icon_url'] ?? '')),
                'is_home' => !empty($item['is_home']),
                'nav_order' => (int)($item['nav_order'] ?? $item['sort_order'] ?? 9999),
                'children' => buildNavTree($items, $nodeId, array_merge($visitedIds, [$nodeId]))
            ];
            
            $branch[] = $node;
        }
        
        // Sort by nav_order ASC, then by title
        usort($branch, function($a, $b) {
            if ($a['nav_order'] !== $b['nav_order']) {
                return $a['nav_order'] <=> $b['nav_order'];
            }
            return $a['title'] <=> $b['title'];
        });
        
        return $branch;
    }
}

/**
 * Mark active states in navigation tree
 * Returns updated tree with active_self and active_any flags
 */
if (!function_exists('markNavActive')) {
    function markNavActive(array $tree, string $currentSlug): array {
        foreach ($tree as &$node) {
            // Check if this node is the current page
            $selfActive = false;
            if (in_array($currentSlug, ['start', 'home'], true) && $node['url'] === '/') {
                $selfActive = true;
            } elseif (!in_array($currentSlug, ['start', 'home'], true) && $node['slug'] === $currentSlug) {
                $selfActive = true;
            }
            
            // Mark children recursively
            $childActive = false;
            if (!empty($node['children'])) {
                $node['children'] = markNavActive($node['children'], $currentSlug);
                // Check if any child is active
                foreach ($node['children'] as $child) {
                    if ($child['active_any'] ?? false) {
                        $childActive = true;
                        break;
                    }
                }
            }
            
            $node['active_self'] = $selfActive;
            $node['active_any'] = $selfActive || $childActive;
        }
        unset($node);
        
        return $tree;
    }
}

/**
 * Render navigation tree recursively
 */
if (!function_exists('sidebarIconSvg')) {
    function sidebarIconSvg(string $name): string {
        $name = strtolower($name);
        $content = match (true) {
            str_contains($name, 'gravur') => '<path d="M20 12l-8 8-9-9V4h7z"/><circle cx="7.5" cy="8" r="1"/>',
            str_contains($name, 'druck') => '<path d="M7 8V3h10v5"/><rect x="5" y="14" width="14" height="7" rx="1"/><path d="M5 17H3v-7h18v7h-2M8 11h1"/>',
            str_contains($name, 'beschrift') => '<path d="M5 5h14M12 5v14M8 19h8"/>',
            str_contains($name, 'stempel') => '<path d="M8 14h8l-1-4a3 3 0 0 0-6 0zM6 14h12v4H6zM4 21h16"/>',
            str_contains($name, 'sicherheit') => '<path d="M12 3l7 3v5c0 5-3 8-7 10-4-2-7-5-7-10V6z"/><path d="M9 12l2 2 4-4"/>',
            str_contains($name, 'pruef'), str_contains($name, 'prüf') => '<circle cx="12" cy="12" r="8"/><path d="M8.5 12l2.2 2.2 4.8-4.8"/>',
            str_contains($name, 'betrieb') => '<path d="M4 20V7h5v4l5-3v3l6-3v12z"/><path d="M8 16h1M13 16h1M18 16h1"/>',
            str_contains($name, 'sonder') => '<path d="M12 3l1.4 4.1L17 9l-3.6 1.9L12 15l-1.4-4.1L7 9l3.6-1.9zM18.5 14l.8 2.2 2.2.8-2.2.8-.8 2.2-.8-2.2-2.2-.8 2.2-.8zM5 14l.8 2.2L8 17l-2.2.8L5 20l-.8-2.2L2 17l2.2-.8z"/>',
            str_contains($name, 'adresse') => '<path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11z"/><circle cx="12" cy="10" r="2"/>',
            str_contains($name, 'telefon') => '<path d="M5 4l4 3-2 3c1.5 3 3.5 5 6.5 6.5l3-2 3 4c-1 2-3 2.5-5 2-5.5-1.5-10.5-6.5-12-12-.5-2 0-4 2.5-4.5z"/>',
            str_contains($name, 'mail') => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M4 7l8 6 8-6"/>',
            default => '<circle cx="12" cy="12" r="8"/><path d="M9 12h6M13 10l2 2-2 2"/>',
        };

        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $content . '</svg>';
    }
}

if (!function_exists('sidebarIconImageMarkup')) {
    function sidebarIconImageMarkup(string $url): string {
        return '<span class="site-nav__icon-image" aria-hidden="true"><img src="' . e($url) . '" alt=""></span>';
    }
}

if (!function_exists('renderNavTree')) {
    function renderNavTree(array $tree, string $activeFaviconUrl = '', string $currentPath = '/', string $currentSlug = '', bool $highlightFirst = false): void {
        if (empty($tree)) {
            return;
        }
        $normalize = static function (string $path): string {
            $path = trim($path);
            if ($path === '') return '/';
            if ($path[0] !== '/') $path = '/' . $path;
            $path = preg_replace('#/+#', '/', $path) ?: $path;
            if ($path !== '/') $path = rtrim($path, '/');
            return $path === '' ? '/' : $path;
        };
        
        echo '<ul>';
        foreach ($tree as $nodeIndex => $node) {
            $nodePath = $normalize((string)($node['url'] ?? '/'));
            $slugNode = trim((string)($node['slug'] ?? ''), '/');
            $slugCurrent = trim($currentSlug, '/');
            $selfByPath = ($nodePath === $currentPath)
                || ($currentPath === '/' && !empty($node['is_home']))
                || ($currentPath === '/' && $slugCurrent !== '' && $nodePath === $normalize('/' . $slugCurrent))
                || ($slugNode !== '' && $slugCurrent !== '' && $slugNode === $slugCurrent);
            $selfActive = ($node['active_self'] ?? false) || $selfByPath;
            $anyActive = ($node['active_any'] ?? false) || $selfByPath;
            $visualActive = $anyActive || ($highlightFirst && $nodeIndex === 0);

            echo '<li>';
            echo '<a href="' . e($node['url']) . '"';
            if ($visualActive) {
                echo ' class="active"';
            }
            if ($selfActive) {
                echo ' aria-current="page"';
            }
            echo '>';
            $iconUrl = trim((string)($node['icon_url'] ?? ''));
            if ($iconUrl !== '' && preg_match('#^(https?://|/)#i', $iconUrl) !== 1) $iconUrl = '';
            if ($iconUrl === '') $iconUrl = $activeFaviconUrl;
            $iconMarkup = $iconUrl !== ''
                ? sidebarIconImageMarkup($iconUrl)
                : sidebarIconSvg((string)$node['title']);
            echo '<span class="site-nav__icon">' . $iconMarkup . '</span>';
            echo '<span class="site-nav__label">' . e($node['title']) . '</span>';
            echo '<span class="site-nav__chevron" aria-hidden="true">›</span></a>';
            
            if (!empty($node['children'])) {
                renderNavTree($node['children'], $activeFaviconUrl, $currentPath, $currentSlug, false);
            }
            
            echo '</li>';
        }
        echo '</ul>';
    }
}

// The contact page gets a dedicated CTA at the bottom of the sidebar.
$navItems = array_values(array_filter($headerNavItems ?? $navItems ?? [], static function (array $item): bool {
    $itemSlug = trim(strtolower((string)($item['slug'] ?? '')), '/');
    $itemUrl = trim(strtolower((string)($item['url'] ?? '')), '/');

    return $itemSlug !== 'kontakt' && $itemUrl !== 'kontakt';
}));

// Auto-detect root parent value
$rootParent = null;
foreach ($navItems as $item) {
    $rawParentId = $item['parent_id'] ?? $item['parentId'] ?? null;
    if ($rawParentId !== null && trim((string)$rawParentId) === '0') {
        $rootParent = 0;
        break;
    }
}

// Build tree, mark active states, and render navigation
$tree = buildNavTree($navItems, $rootParent);
$serviceOrder = [
    'gravuren' => 10,
    'druckverfahren' => 20,
    'beschriftungen' => 30,
    'stempel' => 40,
    'sicherheitskennzeichnungen' => 50,
    'pruefplaketten' => 60,
    'betriebsausstattung' => 70,
    'sonderanfertigung' => 80,
    'sonderanfertigungen' => 80,
];
usort($tree, static function (array $left, array $right) use ($serviceOrder): int {
    $leftKey = trim((string)parse_url((string)($left['url'] ?? ''), PHP_URL_PATH), '/');
    $rightKey = trim((string)parse_url((string)($right['url'] ?? ''), PHP_URL_PATH), '/');
    $leftOrder = $serviceOrder[$leftKey] ?? (1000 + (int)($left['nav_order'] ?? 0));
    $rightOrder = $serviceOrder[$rightKey] ?? (1000 + (int)($right['nav_order'] ?? 0));

    return $leftOrder <=> $rightOrder;
});
$tree = markNavActive($tree, $slug ?? 'home');
$activeFaviconUrl = isset($faviconUrl) && is_string($faviconUrl) ? trim($faviconUrl) : '';
$reqPathRaw = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$currentPath = is_string($reqPathRaw) ? trim($reqPathRaw) : '/';
if ($currentPath === '') $currentPath = '/';
if ($currentPath[0] !== '/') $currentPath = '/' . $currentPath;
$currentPath = preg_replace('#/+#', '/', $currentPath) ?: $currentPath;
if (substr($currentPath, 0, 10) === '/index.php') {
    $currentPath = (string)substr($currentPath, strlen('/index.php'));
    if ($currentPath === '') $currentPath = '/';
}
if ($currentPath !== '/') $currentPath = rtrim($currentPath, '/');
if ($activeFaviconUrl === '') {
    $assetBaseUrl = isset($assetBaseUrl) && is_string($assetBaseUrl) ? rtrim($assetBaseUrl, '/') : '';
    $activeFaviconUrl = ($assetBaseUrl !== '' ? $assetBaseUrl : '') . '/favicon.ico';
}

$sidebarSettings = is_array($publicSettings ?? null) ? $publicSettings : [];
$contactAddress = trim((string)($sidebarSettings['contact_address'] ?? ''));
$contactPostalCity = trim((string)($sidebarSettings['contact_postal_city'] ?? ''));
$contactCountry = trim((string)($sidebarSettings['contact_country'] ?? ''));
$contactPhone = trim((string)($sidebarSettings['contact_phone'] ?? ''));
$contactEmail = trim((string)($sidebarSettings['contact_email'] ?? ''));
$contactPhoneHref = preg_replace('/[^0-9+]/', '', $contactPhone) ?: '';
$hasContactDetails = $contactAddress !== '' || $contactPostalCity !== '' || $contactCountry !== '' || $contactPhone !== '' || $contactEmail !== '';
$openingStatus = trim((string)($sidebarSettings['opening_status'] ?? 'hidden'));
$openingLabel = match ($openingStatus) {
    'open' => 'Aktuell geöffnet',
    'closed' => 'Aktuell geschlossen',
    default => '',
};
$isContactPage = $currentPath === '/kontakt' || trim(strtolower((string)($slug ?? '')), '/') === 'kontakt';
$highlightFirstService = $currentPath === '/' || in_array(trim(strtolower((string)($slug ?? '')), '/'), ['home', 'start'], true);
?>
<aside class="site-sidebar" aria-label="Seitennavigation">
    <div class="site-sidebar__top">
        <a class="site-brand" href="/" aria-label="<?php echo e($siteName ?? 'Website'); ?> – Startseite">
            <?php if (!empty($headerLogoUrl)): ?>
                <img src="<?php echo e((string)$headerLogoUrl); ?>" alt="<?php echo e($siteName ?? 'Website'); ?>">
            <?php else: ?>
                <span><?php echo e($siteName ?? 'Website'); ?></span>
            <?php endif; ?>
        </a>
        <button class="site-sidebar__toggle" type="button" aria-expanded="false" aria-controls="sidebar-panel">
            <span class="site-sidebar__toggle-icon" aria-hidden="true"><i></i><i></i><i></i></span>
            <span class="site-sidebar__toggle-label">Menü</span>
        </button>
    </div>

    <div class="site-sidebar__panel" id="sidebar-panel">
        <?php if (!empty($tree)): ?>
        <nav class="site-nav" aria-label="Hauptnavigation">
            <span class="site-nav__eyebrow">Leistungen</span>
            <?php renderNavTree($tree, $activeFaviconUrl, $currentPath, (string)($slug ?? ''), $highlightFirstService); ?>
        </nav>
        <?php endif; ?>

        <div class="site-sidebar__bottom">
            <?php if ($hasContactDetails): ?>
            <address class="site-sidebar__contact">
                <?php if ($contactAddress !== '' || $contactPostalCity !== '' || $contactCountry !== ''): ?>
                <div class="site-sidebar__contact-item site-sidebar__contact-item--address">
                    <span class="site-sidebar__contact-icon"><?= sidebarIconSvg('Adresse') ?></span>
                    <span class="site-sidebar__contact-copy">
                        <?php if ($contactAddress !== ''): ?><span><?= nl2br(e($contactAddress)) ?></span><?php endif; ?>
                        <?php if ($contactPostalCity !== ''): ?><span><?= e($contactPostalCity) ?></span><?php endif; ?>
                        <?php if ($contactCountry !== ''): ?><span><?= e($contactCountry) ?></span><?php endif; ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($contactPhone !== ''): ?>
                <a class="site-sidebar__contact-item" href="tel:<?= e($contactPhoneHref) ?>">
                    <span class="site-sidebar__contact-icon"><?= sidebarIconSvg('Telefon') ?></span>
                    <span class="site-sidebar__contact-copy"><?= e($contactPhone) ?></span>
                </a>
                <?php endif; ?>
                <?php if ($contactEmail !== ''): ?>
                <a class="site-sidebar__contact-item" href="mailto:<?= e($contactEmail) ?>">
                    <span class="site-sidebar__contact-icon"><?= sidebarIconSvg('E-Mail') ?></span>
                    <span class="site-sidebar__contact-copy"><?= e($contactEmail) ?></span>
                </a>
                <?php endif; ?>
            </address>
            <?php endif; ?>

            <?php if ($openingLabel !== ''): ?>
            <p class="site-sidebar__opening">
                <span class="site-sidebar__opening-dot<?= $openingStatus === 'open' ? ' is-open' : '' ?>" aria-hidden="true"></span>
                <span><?= e($openingLabel) ?></span>
            </p>
            <?php endif; ?>

            <a class="site-sidebar__contact-badge<?= $isContactPage ? ' is-active' : '' ?>" href="/kontakt"<?= $isContactPage ? ' aria-current="page"' : '' ?>>
                Angebot anfragen
            </a>
        </div>
    </div>
</aside>
<script>
(() => {
    const sidebar = document.querySelector('.site-sidebar');
    const toggle = sidebar?.querySelector('.site-sidebar__toggle');
    if (!sidebar || !toggle) return;

    sidebar.classList.add('is-collapsible');
    toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
            sidebar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
            toggle.focus();
        }
    });

})();
</script>
