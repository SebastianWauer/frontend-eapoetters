# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Übergeordnete Arbeitsregeln stehen in `../CLAUDE.MD` (Designtokens, Interaktionszustände,
Guardrails). Diese Datei ergänzt sie um das Repo-spezifische Bild.

## Was das hier ist

Kundenspezifisches Frontend für EA Poetters. Reines PHP 8.1+, kein Composer, kein npm,
kein Buildschritt, keine Datenbank. Alle Inhalte (Seiten, Blöcke, Navigation, Medien,
Markenfarben, Events, News) kommen zur Laufzeit per HTTP aus der DigiWTAL-CMS-API.
Das Repo enthält keine Testsuite und keinen Linter.

## Befehle

```bash
cp .env.example .env          # CMS_API_URL + CMS_API_TOKEN eintragen
php -S localhost:8002 index.php   # lokaler Server, Router ist index.php selbst
php -l index.php              # Syntaxcheck (einziges vorhandenes Prüfwerkzeug)
```

Kein Testlauf vorhanden — Änderungen werden gegen einen laufenden CMS im Browser geprüft.

Deployment läuft automatisch bei Push auf `main` über
[.github/workflows/deploy.yml](.github/workflows/deploy.yml): Die Verwaltung liefert das
Ziel, der Workflow bricht ab, wenn `frontend.json.id`, `GITHUB_REPOSITORY` und Branch
nicht exakt zur Kundenzuordnung passen. Danach rsync auf IONOS, `.env` wird aus der
Verwaltung gerendert (verwaltete Keys überschrieben, eigene Zusatzwerte bleiben erhalten),
zum Schluss Health-Check von CMS und Frontend.

## Request-Fluss

Alles landet über [.htaccess](.htaccess) in [index.php](index.php). Reihenfolge dort:

1. `.env`-Loader (inline, ohne Abhängigkeiten) → `CmsApiClient` bauen
2. HTTPS erzwingen (301) + HSTS
3. Sonderrouten `/sitemap.xml` (Proxy auf CMS) und `/robots.txt` (generiert)
4. Slug aus dem Pfad normalisieren; leerer Pfad → `resolveHomeSlug()` fragt `/pages` nach
   der Seite mit `url === '/'` oder `is_home`
5. `getPublicSettings()`, `getNavigation()`, `getPage($slug)`
6. Block-Anreicherung (siehe unten)
7. Bei POST: `processContactFormSubmission()`
8. `render('templates/layout.php', …)`

`.htaccess` sperrt `.env`, `app/`, `templates/`, `themes/`, `storage/` explizit, weil das
Document Root direkt auf dieses Verzeichnis zeigt. Diese Sperren nicht entfernen.

## CMS-Anbindung

[app/CmsApiClient.php](app/CmsApiClient.php) ist ein abhängigkeitsfreier HTTP-Client
(cURL, Fallback auf Streams). Zwei Eigenheiten prägen das Verhalten der Seite:

- **Stale-Cache als Ausfallschutz**: Dateicache unter `storage/cache`, TTL aus
  `CMS_CACHE_TTL`. Schlägt ein Request transient fehl (Netz, 5xx, 408, 429, kaputtes
  JSON), wird der letzte bekannte Stand ausgeliefert statt eines Fehlers.
- **`CMS_RESOLVE_IP`**: DNS-Umgehung per `CURLOPT_RESOLVE`, greift nur für den Host aus
  `CMS_API_URL`. Wird beim Rollout automatisch gesetzt.

Fehlertoleranz ist absichtlich gestaffelt: Navigation darf ausfallen (leeres Menü),
Settings- oder Page-Fehler führen zu `render500()` bzw. `render404()`.

## Blöcke und Rendering

Der Dispatch sitzt in [templates/page.php:41-60](templates/page.php#L41-L60): ein `match`
auf `$block['type']` → Pfad nach `themes/default/blocks/`. Ein neuer Blocktyp braucht
einen Arm in diesem `match` **und** eine Datei dort; ohne Arm greift
`templates/blocks/unknown.php`.

Wichtig zur Ordnerstruktur: **produktiv sind nur `themes/default/blocks/`**.

> **TODO: ungeklärt** — [templates/blocks/hero.php](templates/blocks/hero.php) und
> [templates/blocks/text.php](templates/blocks/text.php) werden von keiner Stelle im Repo
> referenziert; aus `templates/blocks/` ist nur `unknown.php` über den `match`-Default
> erreichbar. Ob die beiden Altbestand sind oder noch gebraucht werden, ist nicht geklärt.
> Bewusst nicht gelöscht — vor dem Entfernen nachfragen.

Blockdaten werden vor dem Rendern verflacht — Felder aus `$block` selbst und aus
`$block['data']` landen gemeinsam in `$data` (`data` gewinnt). Einige Blöcke lesen
zusätzlich `$block['payload']`. Der Renderindex steht als `$block['_render_index']` bereit.

Die H1 der Seite hängt am Blocktyp: Bei `dual_hero` liefert der Block sie selbst, bei
`hero` wird sie danach eingefügt, bei `catalog` entfällt der Kopfbereich ganz, sonst steht
sie als `.page-headline-wrap` vor allen Blöcken.

### Anreicherung vor dem Rendern

Vier rekursive Walker in `index.php` laufen über den kompletten Blockbaum:

- `absolutizePageCarouselIcons()` / `absolutizeCatalogUrls()` — machen `page_icon_url`,
  `pdf_url`, `page_url_template` absolut
- `enrichBlockFocusWithMedia()` — erkennt Medien-URLs (`/media/file?id=…`), holt per
  `getMedia()` den Fokuspunkt und schreibt ihn als `<feld>_focus_x` / `<feld>_focus_y`
  neben das Originalfeld; pro Request gecached
- `enrichEventBlocksWithItems()` / `enrichNewsBlocksWithItems()` — hängen `items[]` an
  `events`- bzw. `news`-Blöcke; API-Fehler ergeben eine leere Liste, keinen Abbruch

Fokuswerte kommen vom CMS als −1..1 und werden über `focus_to_percent()` in Prozent
übersetzt. `focus_data_attributes()` schreibt `data-cms-focus-*`; [assets/js/focal-point.js](assets/js/focal-point.js)
rechnet daraus zur Laufzeit die `object-position` / `background-position` bei `cover`.

## Styling

- [assets/css/theme.css](assets/css/theme.css) ist das einzige statische Stylesheet
  (~4300 Zeilen, Vanilla, mobile-first, keine Frameworks).
- [assets/css/brand.php](assets/css/brand.php) wird als `<link rel="stylesheet">`
  eingebunden und liefert die Markenfarben live aus dem CMS als `--db-color-*`.
  Immer ungecached (`cacheTtl: 0`, `no-store`), validiert `#rrggbb` streng und leitet
  `--db-color-primary-contrast` per Luminanz ab. Farben also nicht in `theme.css`
  hardcoden — dort stehen sie nur als Fallback in `var(--db-color-…, #…)`.
- `brand.php` hat einen **eigenen, kürzeren** `.env`-Loader als `index.php`: er entfernt
  Anführungszeichen, löst aber keine Maskierungen (`\"`, `\`) auf. Bei Änderungen am
  `.env`-Format beide Stellen anfassen.
- Assets werden per `?v=<filemtime>` gebustet.

## Kontaktformular

`processContactFormSubmission()` ([index.php:520](index.php#L520)) ist die längste
Einzelfunktion und läuft als feste Kette; die Reihenfolge ist Absicht:
Honeypot (`website`) → HMAC-Signaturen (`_cf_sig`, `_cf_robot_sig`, `_cf_cap_sig`, Secret
aus `FRONTEND_FORM_SECRET` mit Fallback auf `CMS_API_TOKEN`) → Rechen-Captcha →
Einwilligung → optional Cloudflare Turnstile → Formularalter 3s–2h → IP-Ratelimit
(dateibasiert in `storage/contact_rate_limit.json`, 6/10min, 40/Tag) → Feldvalidierung.

Erst danach `mail()` und ein Best-Effort-`POST /form/submit` ins CMS. Die Nutzlast wird
per `openssl_seal` gegen einen Public Key verschlüsselt; fehlt der Key, bricht der Versand
ab, solange `CONTACT_FORM_ENCRYPTION_REQUIRED` nicht ausdrücklich abgeschaltet ist.

Welche Felder Pflicht sind, entscheidet das versteckte `_cf_fields` aus dem Block.

## Umgebungsvariablen

`.env.example` deckt nur den Rollout-Kern ab. Zusätzlich im Code gelesen und dort **nicht**
dokumentiert: `FRONTEND_FORM_SECRET`, `CONTACT_FORM_TO`, `CONTACT_FORM_FROM`,
`CONTACT_FORM_ENCRYPTION_PUBLIC_KEY`, `GLOBAL_CONTACT_ENCRYPTION_PUBLIC_KEY`,
`CONTACT_FORM_ENCRYPTION_REQUIRED`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`.
Der Rollout schreibt diese Keys nicht — sie bleiben serverseitig in der `.env` stehen
(der Merge in `deploy.yml` erhält unverwaltete Zeilen).

## Ausgabe und Escaping

`e()` aus [app/view.php](app/view.php) für alles, was aus dem CMS kommt.

Einzige Ausnahme ist der Rich-Text im `text`-Block: Enthält das Feld Tags, wird es nicht
escaped, sondern durch `strip_tags()` mit fester Tag-Allowlist und anschließend
`sanitize_rich_text_hrefs()` geschickt (begrenzt `href` auf `http`, `https`, `mailto`).
Ohne Tags greift `htmlspecialchars()` + `nl2br()`. Weitere Blöcke, die HTML aus dem CMS
durchreichen sollen, müssen dieses Paar übernehmen — aktuell tut das nur
[themes/default/blocks/text.php](themes/default/blocks/text.php).

`render()` verbietet `..` im Pfad und löst relativ zum Repo-Root auf.
