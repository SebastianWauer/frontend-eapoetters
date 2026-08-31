<?php
$footerSettings = is_array($publicSettings ?? null) ? $publicSettings : [];
$footerCompanyName = trim((string)($footerSettings['contact_name'] ?? ''));
if ($footerCompanyName === '') {
    $footerCompanyName = (string)($siteName ?? 'Website');
}
?>
<footer class="site-footer">
    <div class="site-footer__inner">
        <p class="site-footer__copyright">
            &copy; <?php echo date('Y'); ?> <?php echo e($footerCompanyName); ?><span class="site-footer__owner"> - Inhaber: Rolf Seitz</span>
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
