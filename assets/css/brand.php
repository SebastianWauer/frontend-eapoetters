<?php
declare(strict_types=1);

// -------------------------------------------------------
// Minimal .env loader (no Composer required)
// -------------------------------------------------------
(static function (): void {
    $file = dirname(__DIR__, 2) . '/.env';
    if (!is_file($file)) {
        return;
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $eq = strpos($line, '=');
        if ($eq === false) {
            continue;
        }
        $k = trim(substr($line, 0, $eq));
        $v = trim(substr($line, $eq + 1));
        // Strip surrounding quotes
        if (strlen($v) >= 2 && $v[0] === $v[-1] && ($v[0] === '"' || $v[0] === "'")) {
            $v = substr($v, 1, -1);
        }
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
    }
})();

require_once dirname(__DIR__, 2) . '/app/CmsApiClient.php';

// -------------------------------------------------------
// Config from .env
// -------------------------------------------------------
$baseUrl  = (string)(getenv('CMS_API_URL')   ?: '');
$token    = (string)(getenv('CMS_API_TOKEN') ?: '');
$timeout  = (int)(getenv('CMS_TIMEOUT')      ?: 5);
$cacheTtl = 0; // Farben aus CMS immer live ziehen
$resolveIp = (string)(getenv('CMS_RESOLVE_IP') ?: '');

// -------------------------------------------------------
// Defaults
// -------------------------------------------------------
$colorPrimary   = '#2563eb';
$colorSecondary = $colorPrimary;
$colorTertiary  = '#f59e0b';

// -------------------------------------------------------
// Load settings if API available
// -------------------------------------------------------
if ($baseUrl !== '') {
    $client = new CmsApiClient(
        baseUrl:  $baseUrl,
        token:    $token !== '' ? $token : null,
        timeout:  $timeout,
        cacheTtl: $cacheTtl,
        cacheDir: dirname(__DIR__, 2) . '/storage/cache',
        resolveIp: $resolveIp !== '' ? $resolveIp : null
    );

    try {
        $settings = $client->getPublicSettings();
        
        // Validate and apply brand_color_primary
        $c = (string)($settings['brand_color_primary'] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
            $colorPrimary = strtolower($c);
        }
        
        // Validate and apply brand_color_secondary
        $c = (string)($settings['brand_color_secondary'] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
            $colorSecondary = strtolower($c);
        } else {
            $colorSecondary = $colorPrimary;
        }
        
        // Validate and apply brand_color_tertiary
        $c = (string)($settings['brand_color_tertiary'] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $c)) {
            $colorTertiary = strtolower($c);
        }
    } catch (CmsApiException) {
        // Use defaults
    }
}

// -------------------------------------------------------
// Output CSS
// -------------------------------------------------------
$contrastColor = static function (string $hex): string {
    $rgb = sscanf(ltrim($hex, '#'), '%02x%02x%02x');
    if (!is_array($rgb) || count($rgb) !== 3) {
        return '#ffffff';
    }
    $linear = static function (int $channel): float {
        $value = $channel / 255;
        return $value <= 0.03928
            ? $value / 12.92
            : (($value + 0.055) / 1.055) ** 2.4;
    };
    $luminance = 0.2126 * $linear((int)$rgb[0])
        + 0.7152 * $linear((int)$rgb[1])
        + 0.0722 * $linear((int)$rgb[2]);
    return $luminance > 0.42 ? '#17181a' : '#ffffff';
};
$colorPrimaryContrast = $contrastColor($colorPrimary);

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
?>:root {
  /* DB-fed variables consumed by theme.css */
  --db-color-primary:   <?php echo $colorPrimary; ?>;
  --db-color-primary-contrast: <?php echo $colorPrimaryContrast; ?>;
  --db-color-secondary: <?php echo $colorSecondary; ?>;
  --db-color-tertiary:  <?php echo $colorTertiary; ?>;

  /* Legacy compatibility variables */
  --color-primary:   var(--db-color-primary);
  --color-primary-contrast: var(--db-color-primary-contrast);
  --color-secondary: var(--db-color-secondary);
  --color-tertiary:  var(--db-color-tertiary);
  --color-accent:    var(--db-color-tertiary);
}
