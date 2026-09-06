# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Übergeordnete Arbeitsregeln: `../CLAUDE.MD`. Marke, Farben, Typografie und
Komponentenzustände dieses Kunden: @DESIGN.md — die geht bei allem Visuellen vor.

## Was das hier ist

Kundenfrontend für EA Poetters. Reines PHP 8.1+, kein Composer, kein npm, kein
Buildschritt, keine Datenbank, keine Testsuite. Alle Inhalte — Seiten, Blöcke, Navigation,
Medien, Markenfarben, Events, News — kommen zur Laufzeit per HTTP aus der DigiWTAL-CMS-API.

## Befehle

```bash
cp .env.example .env              # CMS_API_URL + CMS_API_TOKEN eintragen
php -S localhost:8002 index.php   # Router ist index.php selbst
php -l index.php                  # Syntaxcheck (einziges Prüfwerkzeug im Repo)
```

Es gibt keinen Testlauf. Änderungen werden gegen ein laufendes CMS im Browser geprüft.

## Festgelegte Entscheidungen

- **Kein Build, kein Composer, kein npm** — alles muss fertig im Repo liegen und direkt
  nach dem Kopieren lauffähig sein; daher der handgeschriebene `.env`-Loader und der
  eigene HTTP-Client.
- **Schriften selbst gehostet** — DSGVO: das Google-Fonts-CDN überträgt die IP jedes
  Besuchers an Google. Deshalb Subsets im Repo, mit den OFL-Lizenztexten daneben, wie
  die Lizenz es bei Weitergabe verlangt.
- **Alter Cache schlägt Fehlerseite** — die Kundenseite darf nicht ausfallen, weil das
  CMS gerade nicht erreichbar ist. Leicht veralteter Inhalt ist für eine Inhaltsseite
  besser als eine Fehlermeldung, deshalb liefert `CmsApiClient` bei Netz- und
  5xx-Fehlern den letzten bekannten Stand.
- **Farben live aus dem CMS, ungecacht** — ändert ein Kunde seine Markenfarbe, soll er
  sie sofort sehen; eine gecachte Farbe wirkt wie ein Fehler. **Das ist der einzige
  ungecachte Zugriff und fällt bei jedem Seitenaufruf an** — bewusst in Kauf genommen,
  nicht übersehen. Wer Ladezeiten untersucht, muss hier nicht raten.

> **Korrektur zur Begründung "kein Build":** Die Annahme, der IONOS-Webspace habe keine
> Shell, ist durch den eigenen Code widerlegt — [deploy.yml](.github/workflows/deploy.yml)
> öffnet SSH und führt auf dem Ziel `true`, `mkdir`, `chmod`, `command -v rsync` und
> `cat >` aus. Eine Shell ist da. Belegt ist nur: auf dem Ziel läuft **kein
> Installationsschritt**, Composer und npm sind dort nicht vorausgesetzt.
> Die Ursache im CMS ist außerdem **eine andere**: `CMS/docs/testing.md` begründet den
> Verzicht damit, das Repo schlank zu halten, und beschreibt ausdrücklich eine lokale
> Composer-Installation. Die Repos teilen die Entscheidung, nicht den Grund.

## Verworfenes

- **Blöcke flach unter `templates/blocks/`** wurde versucht und zugunsten der
  Theme-Struktur `themes/default/blocks/` verworfen. Übrig sind
  [templates/blocks/hero.php](templates/blocks/hero.php) und
  [templates/blocks/text.php](templates/blocks/text.php) — von nichts referenziert,
  bewusst nicht gelöscht. Aus dem Verzeichnis ist nur `unknown.php` noch aktiv.
  **TODO: ungeklärt** — warum gewechselt wurde, ist nicht erfasst.

## Ein Blocktyp berührt fünf Repositories

Das ist die teuerste Falle im Aufbau. Ein Blocktyp existiert nicht an einer Stelle,
sondern verteilt über mehrere Repos — und keins merkt, wenn in einem anderen etwas fehlt.

Im **CMS** liegen Klasse, Registrierung, Validierung und Editor-UI:

```text
CMS/app/PageBuilder/Blocks/<Typ>Block.php   Klasse
CMS/app/PageBuilder/BlockRegistry.php       Registrierung
CMS/app/PageBuilder/BlockValidator.php      Validierung
CMS/app/Frontend/BlockRenderer.php          CMS-seitiges Rendern
```

**Hier** liegt nur das Template — plus, leicht zu vergessen, ein `match`-Arm:

```text
themes/default/blocks/<typ>.php             Template
templates/page.php:41-60                    match-Arm; ohne ihn -> unknown.php
```

Und dasselbe Template noch einmal in **jedem weiteren Kundenfrontend**:
`../DigiWtal/`, `../Sport Voice/`, `../Ihmann Medien/`. Ein neuer Blocktyp heißt also:
Klasse im CMS, Registry-Eintrag, Editor-UI — und danach dieselbe Template-Arbeit in
jedem Frontend einzeln.

Der Stand belegt das: Das CMS kennt 17 Typen, EA Poetters hat 17 Templates — DigiWtal
und Sport Voice haben **14**, es fehlen `catalog`, `dual_hero`, `page_carousel`.
Redaktionell sind diese Blöcke dort einsetzbar und fallen im Frontend kommentarlos auf
`unknown.php` zurück. Genau diese Lücke entsteht, wenn ein Typ im CMS angelegt und der
Rundgang durch die Frontends vergessen wird.

**Vor dem Anlegen oder Ändern eines Blocktyps** deshalb erst prüfen, welche der fünf
Stellen betroffen sind — und dass hier Template *und* `match`-Arm zusammen wandern.

## Auslieferung: Positivliste

Der erste rsync in [deploy.yml](.github/workflows/deploy.yml) überträgt **nur, was
namentlich eingetragen ist**: `index.php`, `.htaccess`, `favicon.ico`, `app/`,
`templates/`, `themes/`, `assets/`. Alles andere bleibt im Repo.

**Eine neue Datei im Repo-Root muss dort eingetragen werden, sonst fehlt sie
kommentarlos auf dem Server.** Das ist der Preis dafür, dass Doku, `frontend.json` und
`.env.example` nicht mehr auf einer Kundendomain landen — vorher war es eine
Negativliste, durch die genau das zweimal durchgerutscht ist.

Ein Pflichtdateien-Check hinter dem rsync bricht ab, wenn die Liste unvollständig ist:
der zweite rsync läuft mit `--delete`, ein leeres Stage würde das Frontend löschen.
`.env` und `storage/` sind dort von `--delete` ausgenommen. Deployment startet bei Push
auf `main`; die Verwaltung liefert das Ziel, der Workflow bricht ab, wenn
`frontend.json.id`, Repository und Branch nicht zur Zuordnung passen.

## Request-Fluss

Alles landet über [.htaccess](.htaccess) in [index.php](index.php):

1. `.env`-Loader (inline) → `CmsApiClient` bauen
2. HTTPS erzwingen (301) + HSTS
3. Sonderrouten `/sitemap.xml` (Proxy aufs CMS) und `/robots.txt` (generiert)
4. Slug normalisieren; leerer Pfad → `resolveHomeSlug()` sucht `url === '/'` bzw. `is_home`
5. `getPublicSettings()`, `getNavigation()`, `getPage($slug)`
6. Blöcke anreichern, bei POST `processContactFormSubmission()`
7. `render('templates/layout.php', …)`

Die `.htaccess` sperrt `.env`, `app/`, `templates/`, `themes/`, `storage/` — das Document
Root zeigt direkt hierher, diese Sperren nicht entfernen. Weil sie in
`<IfModule mod_rewrite.c>` stehen, liegt in `app/`, `templates/` und `themes/` zusätzlich
je eine eigene `.htaccess` mit `Require all denied`; `storage/` bekommt seine beim Rollout.
Fehlertoleranz ist gestaffelt: Navigation darf ausfallen (leeres Menü), Settings- und
Page-Fehler führen zu `render500()` bzw. `render404()`.

## Blöcke und Rendering

Dispatch: `match` auf `$block['type']` in
[templates/page.php:41-60](templates/page.php#L41-L60) → Pfad nach
`themes/default/blocks/`. **Produktiv ist nur dieses Verzeichnis.**

Blockdaten werden verflacht: Felder aus `$block` und aus `$block['data']` landen
gemeinsam in `$data` (`data` gewinnt); manche Blöcke lesen zusätzlich `$block['payload']`.
Der Renderindex steht als `$block['_render_index']`.

Die H1 hängt am Blocktyp: `dual_hero` liefert sie selbst, bei `hero` folgt sie danach,
bei `catalog` entfällt der Kopf, sonst steht sie als `.page-headline-wrap` vor allem.

Vier rekursive Walker in `index.php` laufen vorher über den Blockbaum:
`absolutizePageCarouselIcons()` und `absolutizeCatalogUrls()` machen URLs absolut;
`enrichBlockFocusWithMedia()` holt zu Medien-URLs den Fokuspunkt und schreibt ihn als
`<feld>_focus_x/_y` daneben; `enrichEventBlocksWithItems()` und
`enrichNewsBlocksWithItems()` hängen `items[]` an — API-Fehler ergeben eine leere Liste,
keinen Abbruch. Fokuswerte kommen als −1..1, `focus_to_percent()` rechnet sie in Prozent,
[assets/js/focal-point.js](assets/js/focal-point.js) daraus die `object-position`.

## Styling

[assets/css/theme.css](assets/css/theme.css) ist das einzige statische Stylesheet
(~4300 Zeilen, Vanilla, mobile-first). [assets/css/brand.php](assets/css/brand.php)
liefert die Markenfarben live aus dem CMS als `--db-color-*` und leitet
`--db-color-primary-contrast` per Luminanz ab. Farben nie in `theme.css` hardcoden.

`brand.php` hat einen **eigenen, kürzeren** `.env`-Loader als `index.php`: er entfernt
Anführungszeichen, löst aber keine Maskierungen auf. Bei Änderungen am `.env`-Format
beide Stellen anfassen. Alles Weitere zu Farbe, Schrift und Zuständen: @DESIGN.md.

## Kontaktformular

`processContactFormSubmission()` ([index.php:520](index.php#L520)) ist eine feste Kette,
deren Reihenfolge Absicht ist: Honeypot → HMAC-Signaturen (Secret aus
`FRONTEND_FORM_SECRET`, Fallback `CMS_API_TOKEN`) → Rechen-Captcha → Einwilligung →
optional Turnstile → Formularalter 3s–2h → IP-Ratelimit → Feldvalidierung. Erst danach
`mail()` und ein Best-Effort-`POST /form/submit`. Die Nutzlast wird per `openssl_seal`
verschlüsselt; fehlt der Key, bricht der Versand ab, solange
`CONTACT_FORM_ENCRYPTION_REQUIRED` nicht abgeschaltet ist. Pflichtfelder bestimmt das
versteckte `_cf_fields` aus dem Block.

## Offene Punkte

**Personenbezogene Daten im Document Root.** `storage/contact_rate_limit.json` speichert
die IP-Adressen der Formularabsender, und `storage/` liegt im Webroot; Schutz sind nur
Zugriffsregeln auf einem öffentlich erreichbaren Pfad. **TODO: ungeklärt** — richtig wäre
`storage/` außerhalb des Document Roots; ob der Tarif das hergibt, ist nicht geprüft.

**favicon.ico ist ein Platzhalter** — neutrales Quadrat, kein Monogramm.
**TODO: ungeklärt**, die Bildmarke ist nirgends belegt. Vorrang hat ohnehin das CMS.

Die Mängelliste zu Fokus, Formularzuständen und Kontrasten steht in @DESIGN.md.

## Umgebungsvariablen

`.env.example` deckt nur den Rollout-Kern ab. Zusätzlich gelesen, dort **nicht**
dokumentiert: `FRONTEND_FORM_SECRET`, `CONTACT_FORM_TO`, `CONTACT_FORM_FROM`,
`CONTACT_FORM_ENCRYPTION_PUBLIC_KEY`, `GLOBAL_CONTACT_ENCRYPTION_PUBLIC_KEY`,
`CONTACT_FORM_ENCRYPTION_REQUIRED`, `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY`. Der
Rollout schreibt diese Keys nicht; sie bleiben serverseitig stehen.

## Ausgabe und Escaping

`e()` aus [app/view.php](app/view.php) für alles aus dem CMS. Ausnahme ist der Rich-Text
im `text`-Block: mit Tags läuft er durch `strip_tags()` mit fester Allowlist und
`sanitize_rich_text_hrefs()` (begrenzt `href` auf http/https/mailto), ohne Tags durch
`htmlspecialchars()` + `nl2br()`. Weitere Blöcke, die HTML durchreichen, müssen dieses
Paar übernehmen — aktuell tut es nur [text.php](themes/default/blocks/text.php).
