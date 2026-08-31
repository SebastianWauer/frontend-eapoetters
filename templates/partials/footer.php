<?php
$footerSettings = is_array($publicSettings ?? null) ? $publicSettings : [];
$footerCompanyName = trim((string)($footerSettings['contact_name'] ?? ''));
if ($footerCompanyName === '') {
    $footerCompanyName = (string)($siteName ?? 'Website');
}
$footerOwner = trim((string)($footerSettings['legal_owner'] ?? ''));
?>
<footer class="site-footer">
    <div class="site-footer__inner">
        <p class="site-footer__copyright">
            &copy; <?php echo date('Y'); ?> <?php echo e($footerCompanyName); ?><?php if ($footerOwner !== ''): ?><span class="site-footer__owner"> - Inhaber: <?= e($footerOwner) ?></span><?php endif; ?>
            <span class="site-footer__credit"><span class="site-footer__credit-separator" aria-hidden="true"> · </span><a href="https://digiwtal.de" target="_blank" rel="noopener">Designed by DigiWtal</a></span>
        </p>
        <?php if (!empty($footerNavItems ?? [])): ?>
        <nav class="site-footer__nav" aria-label="Rechtliche Navigation">
            <?php foreach (($footerNavItems ?? []) as $item): ?>
                <?php
                $url = (string)($item['url'] ?? '');
                if ($url === '') {
                    $itemSlug = (string)($item['slug'] ?? '');
                    $url = in_array($itemSlug, ['start', 'home'], true) ? '/' : '/' . ltrim($itemSlug, '/');
                }
                $label = (string)($item['title'] ?? '');
                ?>
                <a href="<?php echo e($url); ?>"><?php echo e($label !== '' ? $label : 'Link'); ?></a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>
    </div>
</footer>
