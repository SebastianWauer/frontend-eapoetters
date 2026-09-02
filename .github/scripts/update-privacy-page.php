<?php
declare(strict_types=1);

$cmsRoot = getcwd();
if (!is_string($cmsRoot) || !is_file($cmsRoot . '/app/bootstrap.php')) {
    fwrite(STDERR, "CMS root could not be resolved.\n");
    exit(1);
}

require $cmsRoot . '/app/bootstrap.php';

$pdo = db();
$repo = new \App\Repositories\PageRepositoryDb($pdo);
$page = $repo->findActiveBySlug('/datenschutz');
if (!is_array($page) || (int)($page['id'] ?? 0) <= 0) {
    fwrite(STDERR, "Privacy page was not found.\n");
    exit(1);
}

$content = json_decode((string)($page['content_json'] ?? ''), true);
if (!is_array($content) || !is_array($content['blocks'] ?? null)) {
    fwrite(STDERR, "Privacy page has invalid PageBuilder content.\n");
    exit(1);
}

$replaceSection = static function (string $html, string $heading, string $nextHeading, string $replacement): string {
    $pattern = '#<h2\b[^>]*>' . preg_quote($heading, '#') . '</h2>.*?(?=<h2\b[^>]*>' . preg_quote($nextHeading, '#') . '</h2>)#su';
    $updated = preg_replace($pattern, $replacement, $html, 1, $count);
    if (!is_string($updated) || $count !== 1) {
        throw new RuntimeException('Section could not be updated: ' . $heading);
    }
    return $updated;
};

$hosting = '<h2 class="break-after-avoid-column">3. Bereitstellung der Website und Hosting</h2>'
    . '<p>Diese Website wird bei der IONOS SE, Elgendorfer Straße 57, 56410 Montabaur („IONOS“), gehostet. Beim Aufruf der Website verarbeitet der Webserver technisch erforderliche Zugriffsdaten. Dazu können insbesondere die IP-Adresse des zugreifenden Geräts, Datum und Uhrzeit des Zugriffs, die aufgerufene Seite oder Datei, die übertragene Datenmenge, Browser und Betriebssystem sowie die Referrer-URL gehören.</p>'
    . '<p>Die Verarbeitung dient der sicheren, stabilen und technisch fehlerfreien Bereitstellung der Website. Rechtsgrundlage ist Art. 6 Abs. 1 lit. f DSGVO. Unser berechtigtes Interesse liegt im sicheren und zuverlässigen Betrieb dieses Internetauftritts. IONOS weist darauf hin, IP-Adressen in Webhosting-Logdateien zu anonymisieren. Soweit IONOS personenbezogene Daten in unserem Auftrag verarbeitet, erfolgt dies auf Grundlage eines Vertrags zur Auftragsverarbeitung gemäß Art. 28 DSGVO. Weitere Informationen finden Sie in den <a href="https://www.ionos.de/terms-gtc/datenschutzerklaerung/" target="_blank" rel="noopener">Datenschutzhinweisen von IONOS</a>.</p>';

$fonts = '<h2 class="break-after-avoid-column">6. Schriftarten</h2>'
    . '<p>Zur einheitlichen Darstellung verwendet diese Website die Schriftarten Inter und Barlow Condensed. Die Schriftdateien sind lokal in das Frontend eingebunden und werden zusammen mit den übrigen Website-Dateien vom eigenen Webserver ausgeliefert. Beim Laden der Schriftarten wird keine Verbindung zu Google Fonts oder einem anderen Schriftanbieter hergestellt.</p>';

$contactForm = '<h2 class="break-after-avoid-column">8. Kontaktformular</h2>'
    . '<p>Wenn Sie das Kontaktformular verwenden, werden die von Ihnen eingegebenen Daten – je nach eingeblendeten Feldern insbesondere Name, E-Mail-Adresse, Telefonnummer und Nachricht – verschlüsselt per HTTPS an unseren Server übertragen und im zugehörigen CMS gespeichert. Eine Übermittlung an einen gesonderten Formular- oder Analysedienst findet derzeit nicht statt.</p>'
    . '<p>Zur Absicherung des Formulars gegen Missbrauch werden außerdem die IP-Adresse und Angaben zum verwendeten Browser (User-Agent) verarbeitet. Die Verarbeitung Ihrer Anfrage erfolgt auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO, sofern sie der Anbahnung oder Erfüllung eines Vertrags dient, andernfalls auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO. Die Verarbeitung der Sicherheitsdaten beruht auf unserem berechtigten Interesse am Schutz des Formulars und unserer Systeme vor Spam und Missbrauch (Art. 6 Abs. 1 lit. f DSGVO).</p>'
    . '<p>Die Daten werden gelöscht, sobald sie für die Bearbeitung Ihrer Anfrage und etwaige Anschlussfragen nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten entgegenstehen.</p>';

$updated = false;
foreach ($content['blocks'] as $index => $block) {
    if (!is_array($block) || (string)($block['type'] ?? '') !== 'text') continue;
    $html = (string)($block['text'] ?? '');
    if (!str_contains($html, '3. Bereitstellung der Website und Hosting')) continue;

    if (!str_contains($html, 'Vercel Inc.')
        && str_contains($html, 'IONOS SE')
        && str_contains($html, 'Inter und Barlow Condensed')
        && !str_contains($html, 'Wir planen, auf dieser Website ein Kontaktformular')) {
        echo "Privacy page is already current.\n";
        exit(0);
    }

    $html = $replaceSection($html, '3. Bereitstellung der Website und Hosting', '4. Cookies', $hosting);
    $html = $replaceSection($html, '5. Web-Analyse mit Vercel Speed Insights', '6. Schriftarten', '');
    $html = $replaceSection($html, '6. Schriftarten', '7. Kontaktaufnahme per E-Mail oder Telefon', $fonts);
    $html = $replaceSection($html, '8. Kontaktformular', '9. Ihre Rechte als betroffene Person', $contactForm);
    $html = preg_replace_callback(
        '#>(6|7|8|9|10)\. #',
        static fn(array $match): string => '>' . ((int)$match[1] - 1) . '. ',
        $html
    ) ?? $html;
    $html = str_replace('Stand: August 2026', 'Stand: September 2026', $html);

    if (str_contains($html, 'Vercel')
        || str_contains($html, 'Wir planen, auf dieser Website ein Kontaktformular')
        || !str_contains($html, 'IONOS SE')
        || !str_contains($html, 'Inter und Barlow Condensed')) {
        throw new RuntimeException('Privacy page verification failed before saving.');
    }

    $content['blocks'][$index]['text'] = $html;
    $updated = true;
    break;
}

if (!$updated) {
    fwrite(STDERR, "Expected privacy text block was not found.\n");
    exit(1);
}

$contentJson = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if (!is_string($contentJson)) {
    throw new RuntimeException('Updated privacy content could not be encoded.');
}

$service = new \App\Services\PageService($repo);
$result = $service->save(
    (int)$page['id'],
    (string)$page['slug'],
    (string)$page['title'],
    (string)$page['frontend_title'],
    (string)$page['subtitle'],
    (string)$page['status'],
    $contentJson,
    (int)$page['is_home'] === 1,
    (int)$page['nav_visible'] === 1,
    (string)$page['nav_label'],
    (string)$page['nav_area'],
    (int)$page['nav_order'],
    null
);

if (empty($result['ok'])) {
    fwrite(STDERR, "Privacy page could not be saved.\n");
    exit(1);
}

echo "Privacy page updated and revision created.\n";
