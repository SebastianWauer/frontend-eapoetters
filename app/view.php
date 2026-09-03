<?php
declare(strict_types=1);

/**
 * Escape HTML special characters
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Render a template file with given variables
 */
function render(string $file, array $vars = []): void {
    if (str_contains($file, '..')) {
        throw new RuntimeException('Invalid template path');
    }
    $path = __DIR__ . '/../' . $file;
    if (!is_file($path)) {
        throw new RuntimeException("Template not found: {$file}");
    }
    extract($vars, EXTR_SKIP);
    require $path;
}

/**
 * Convert the CMS focus convention (-1..1) to percentages.
 * Values outside that range are accepted as legacy percentages.
 */
function focus_to_percent(mixed $raw, float $default = 50.0): float
{
    if ($raw === null || $raw === '') {
        return $default;
    }
    $v = (float)$raw;

    if ($v >= -1.0 && $v <= 1.0) {
        $v = (($v + 1.0) / 2.0) * 100.0;
    }

    if ($v < 0.0) $v = 0.0;
    if ($v > 100.0) $v = 100.0;
    return $v;
}

/**
 * Data attributes used by the frontend focal-point positioning script.
 */
function focus_data_attributes(mixed $xRaw, mixed $yRaw, ?string $backgroundUrl = null): string
{
    if (($xRaw === null || $xRaw === '') && ($yRaw === null || $yRaw === '')) {
        return '';
    }

    $x = focus_to_percent($xRaw, 50.0);
    $y = focus_to_percent($yRaw, 50.0);
    $attrs = ' data-cms-focus-x="' . htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8') . '"'
        . ' data-cms-focus-y="' . htmlspecialchars((string)$y, ENT_QUOTES, 'UTF-8') . '"';

    if ($backgroundUrl !== null && $backgroundUrl !== '') {
        $attrs .= ' data-cms-focus-background="' . htmlspecialchars($backgroundUrl, ENT_QUOTES, 'UTF-8') . '"';
    }

    return $attrs;
}

/**
 * Restrict href="" values (e.g. in rich-text <a> tags) to http/https/mailto.
 * Any other scheme (javascript:, data:, vbscript:, ...) is replaced with "#"
 * to prevent XSS via pasted/hand-edited rich-text HTML.
 */
function sanitize_rich_text_hrefs(string $html): string
{
    $isDisallowed = static function (string $url): bool {
        $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strtolower(preg_replace('/[\x00-\x20]+/', '', $decoded) ?? '');
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/', $stripped, $sm) === 1) {
            return !in_array($sm[1], ['http', 'https', 'mailto'], true);
        }
        return false;
    };

    $html = preg_replace_callback('/\bhref\s*=\s*"([^"]*)"/i', static function (array $m) use ($isDisallowed): string {
        return $isDisallowed($m[1]) ? 'href="#"' : $m[0];
    }, $html) ?? $html;

    $html = preg_replace_callback("/\bhref\s*=\s*'([^']*)'/i", static function (array $m) use ($isDisallowed): string {
        return $isDisallowed($m[1]) ? "href='#'" : $m[0];
    }, $html) ?? $html;

    $html = preg_replace_callback('/\bhref\s*=\s*([^\s"\'>]+)/i', static function (array $m) use ($isDisallowed): string {
        return $isDisallowed($m[1]) ? 'href="#"' : $m[0];
    }, $html) ?? $html;

    return $html;
}
