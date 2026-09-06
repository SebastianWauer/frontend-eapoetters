# DESIGN.md — EA Poetters

Verbindlich für dieses Kundenfrontend. Erbt nichts von anderen Projekten und gleicht
sich an kein anderes Kundenfrontend an. Bei Widerspruch zu `../CLAUDE.MD` gilt für
Marke, Farbe und Typografie diese Datei; die Guardrails dort gelten zusätzlich.

Ein Corporate-Design-Dokument liegt nicht im Repo (geprüft). Grundlage sind daher
`assets/css/theme.css`, `assets/css/brand.php`, die Schriftdateien und `assets/img/`.

---

## 1. Marke

EA Poetters steht für Technik und Handwerk, und die Seite soll genau so auftreten:
präzise, robust, unaufgeregt. Die Gestaltung arbeitet mit dunklen, ruhigen Flächen und
einer schmalen, technischen Display-Schrift — nicht mit Wärme oder Verspieltheit.

Das Zahnrad-Dekor (`assets/img/gears.png`) ist deshalb programmatisch gemeint und nicht
Zierrat. Es liegt bewusst zurückgenommen: fest positioniert, `opacity: 0.28`,
`pointer-events: none`, und es ist **vollständig farblos** — gemessen reines `#dadada`
bei Sättigung 0.0. Farbe im Layout kommt ausschließlich aus dem CMS, das Dekor tritt
dahinter zurück.

---

## 2. Farben

### Markenfarben — vom Kunden vorgegeben

Kommen zur Laufzeit aus dem CMS über `assets/css/brand.php` und werden dort streng gegen
`^#[0-9a-f]{6}$` validiert. **Die konkreten Töne sind hier bewusst nicht notiert** — sie
stehen im CMS, nicht im Repo. Kein Hex-Wert in dieser Tabelle ist erfunden.

| Variable | Rolle | Herkunft |
|---|---|---|
| `--db-color-primary` | Primärfarbe: Buttons, aktive Zustände, Links | **Kunde**, CMS `brand_color_primary` |
| `--db-color-primary-contrast` | Textfarbe auf Primärfläche | **abgeleitet**, Luminanzschwelle 0.42 in brand.php |
| `--db-color-secondary` | Sekundärfläche, Hover auf Primäraktionen | **Kunde**, fällt auf Primary zurück, wenn nicht gesetzt |
| `--db-color-tertiary` | Akzent, gespiegelt auf `--color-accent` | **Kunde**, CMS `brand_color_tertiary` |

`--color-primary`, `--color-secondary`, `--color-accent` sind Aliasse auf die
`--db-*`-Ebene und die Namen, die im CSS benutzt werden.

### Neutrale — empirisch bestimmt, nicht gewählt

In `theme.css` liegen zwei vollständige Neutralreihen. Ausgezählt über alle 4273 Zeilen,
klassifiziert nach umschließendem Selektor:

| Reihe | Töne | Verteilung |
|---|---|---|
| **Graphit** (gesetzt) | `#17181a` `#202225` `#383a3e` `#737b86` `#7b8089` `#b3b6ba` `#c0c3c6` `#c9cccf` `#dcdedf` `#e6e8e9` `#eceff4` `#eef0f1` `#faf9f7` | **30 Verwendungen über 11 Komponenten** — Sidebar, Footer, Dual-Hero, Karussell, Service-Spalten, Katalog |
| **Slate** (Gerüstrest) | `#f8fafc` `#f1f5f9` `#e2e8f0` `#cbd5e1` `#94a3b8` `#64748b` `#1e293b` `#0f172a` | **28 Verwendungen an nur 2 Stellen** — `:root` (7) und `.block-events` (20), plus 1 Streuer im Footer |

**Graphit ist die Reihe der Marke.** Alle markenprägenden Flächen gehören ihr; Slate hat
in Chrome und Bühne zusammen genau **einen** Treffer. Slate lebt praktisch nur noch im
Tokenblock und im Events-Widget, einer offensichtlich separat gebauten Komponente.

Slate lässt sich trotzdem nicht einfach löschen: es *definiert* aktuell `--color-text`,
`--color-text-muted`, `--color-bg-alt`, `--color-border`, `--color-dark` und
`--color-dark-text`. Siehe Offene Mängel.

### Semantische Farben

| Zweck | Wert | Herkunft |
|---|---|---|
| Erfolg / geöffnet | `#10b981`, Rahmen `#86efac` | Bestand |
| Fehler | Fläche `#fef2f2`, Rahmen `#fecaca`, Text `#7f1d1d` | Bestand |
| Kontakt-Badge aktiv | `#a91630` | Bestand |

Wo Farbe Semantik trägt, tragen primäre Aktionen keinen Farbton — Guardrail aus
`../CLAUDE.MD`, hier eingehalten.

---

## 3. Typografie

Beide Familien liegen als latin-Subset im Repo und werden selbst ausgeliefert; kein
Google-CDN, kein externer Request. **Das ist kein Platzhalter, sondern der Bestand.**

| Rolle | Familie | Schnitte | Datei | Lizenz |
|---|---|---|---|---|
| `--font-body` | **Inter** | 400–600 (variabel) | `inter-latin-400-600.woff2`, 48 KB | SIL OFL 1.1, © 2020 The Inter Project Authors — Text in `assets/fonts/OFL-Inter.txt` |
| `--font-heading` | **Barlow Condensed** | 600, 700 | `barlow-condensed-latin-{600,700}.woff2`, je 22 KB | SIL OFL 1.1, © 2017 The Barlow Project Authors — Text in `assets/fonts/OFL-BarlowCondensed.txt` |

Fallbacks: `system-ui, sans-serif` für Body, `'Arial Narrow', sans-serif` für Headings.
Alle `@font-face` mit `font-display: swap`. Fließtext 1rem / `line-height: 1.6`.

Die OFL verlangt die Weitergabe des Lizenztexts — die beiden `OFL-*.txt` gehören
deshalb in die Auslieferung und dürfen nicht aus der Positivliste fallen.

---

## 4. Abstände

Zielskala für neuen Code laut `../CLAUDE.MD`: **4 / 8 / 12 / 16 / 24 / 32 px**
(0.25 / 0.5 / 0.75 / 1 / 1.5 / 2 rem). Bestehende Werte werden nicht konvertiert.

Der Bestand folgt ihr nicht: gezählt sind rund 22 verschiedene rem-Werte, darunter
`1.05rem`, `1.3rem`, `1.375rem`, `2.2rem` — Zwischengrößen ohne erkennbares Raster.
Verlässlich sind nur die Tokens:

| Token | Wert | Rolle |
|---|---|---|
| `--space-block` | `1rem` | vertikaler Blockabstand |
| `--space-block-sm` | `1.25rem` | engere Variante |
| `--container-pad` | `1rem` | horizontale Innenkante |
| `--container-max` | `1080px` | Textmaß |
| `--sidebar-width` / `--site-footer-height` | s. theme.css | Chrome-Maße |

**Für neuen Code**: Zielskala verwenden, nicht am Bestand orientieren.

---

## 5. Radien, Rahmen, Schatten

| Token / Wert | Verwendung |
|---|---|
| `999px` | Pillen: Buttons, Badges, Chips — **19×, die häufigste Form** |
| `--radius` `0.5rem` | Standardradius Flächen (10×) |
| `--radius-lg` `1rem` | große Karten (3×) |
| `10px`, `12px`, `8px` | Karten und Felder, **außerhalb der Tokens** |

Rahmen durchgängig `1px solid var(--color-border)`. Schatten sind sparsam und flach —
größter Wert `0 8px 28px rgba(0,0,0,0.16)`; mehrere Komponenten setzen bewusst
`box-shadow: none`.

Die Pillenform ist die eigentliche Formsprache der Seite. Neue Bedienelemente greifen
`999px` auf, neue Flächen `--radius`.

---

## 6. Komponenten und Zustände

Pflicht laut `../CLAUDE.MD`: Fokus über `:focus-visible` mit eigenem Token, sichtbar
anders als Hover; `:disabled` schaltet Hover-Transform und Farbwechsel ab; Eingabefelder
brauchen einen eigenen Fokusstil.

| Komponente | Ruhe | Hover | Fokus | Disabled | Aktiv |
|---|---|---|---|---|---|
| Primärbutton `.block-contact-form__submit` | Primary, `999px`, `--color-primary-contrast` | Fläche → `--color-secondary`, `translateY(-1px)` | **fehlt** | **fehlt** | — |
| Eingabefeld / Textarea | `1px` Border, `10px`, weiß | — | **fehlt**, nur Browser-Default | **fehlt** | — |
| Feld ungültig | — | — | — | — | **`:invalid` existiert nirgends** |
| Karussell-Bühne | — | — | ✔ `2px solid var(--color-primary)`, Offset 3 | — | — |
| Verlinkte Spalte | Karte | `translateY(-2px)` | ✔ `3px`, `color-mix` mit Primary | — | — |
| Katalog-Pfeile | Pille | ✔ | **fehlt** | ✔ einzige `:disabled`-Regel im Projekt | — |
| Sidebar-Navigation | Graphit | ✔ | **fehlt** | — | `--sidebar-contact` |
| Kontakt-Badge | Rahmen | Fläche `#a91630`, `translateY(-2px)` | **fehlt** | — | wie Hover |
| Öffnungs-Punkt | `#737b86` | — | — | — | `#10b981` |

Der einzige `outline: none` (`.page-carousel__stage`, Zeile 619) hat unmittelbar
darunter einen `:focus-visible`-Ersatz — regelkonform.

---

## 7. Bewegung

| Größe | Wert |
|---|---|
| `--transition` | `0.2s ease` — Standard, 6× |
| weitere Dauern | `0.18s`, `0.3s`, `0.35s`, `0.4s`, `0.55s` |
| Easing | `ease` (18×), `cubic-bezier(0.4, 0, 0.2, 1)` (2×, Karten und Katalog) |
| Bewegungsmuster | fast ausschließlich `translateY(-1px)` bis `-2px` beim Hover |

**`prefers-reduced-motion: reduce` ist in drei Blöcken berücksichtigt.** Neue Animation
gehört dort mit hinein.

---

## 8. Breakpoints

Mobile-first. Der Wechsel von der gestapelten Ansicht auf die Sidebar-Komposition liegt
bei **920px** — das ist der tragende Bruch, nicht 768.

| Query | Zweck |
|---|---|
| `min-width: 768px` | erste Spaltenaufteilung |
| `min-width: 920px` / `max-width: 919px` | **Hauptbruch**: Sidebar-Layout an/aus |
| `min-width: 1024px` | weite Desktopstufe |
| `min-width: 920px and max-height: 780px` | flache Desktopfenster |
| `600–919px` | Tabletzwischenstufe |
| `max-width: 767 / 719 / 620 / 560px` | Feinkorrekturen |
| `print` | Druckansicht |

Die vier engen `max-width`-Stufen sind Nachbesserungen ohne System. Neue Regeln an 768 /
920 / 1024 aufhängen.

---

## 9. Guardrails

Zusätzlich zu `../CLAUDE.MD` gilt hier:

- **Kein Hex im CSS für Markenfarben.** Immer die `--color-*`-Aliasse; Tönungen per
  `color-mix(in srgb, var(--token) <prozent>, transparent)`.
- **Graphit ist die Neutralreihe**, nicht Slate. Neue Flächen greifen Graphit auf.
- **Pillenform `999px` für Bedienelemente**, `--radius` für Flächen.
- **Jedes neue Bedienelement bringt `:focus-visible` mit** — die Seite ist öffentlich,
  und der Bestand ist hier bereits im Rückstand.
- **Keine neue Zwischengröße im Abstandsraster.** Zielskala oder vorhandenes Token.
- **Neue Animation respektiert `prefers-reduced-motion`.**
- Das Zahnrad-Dekor bleibt farblos und hinter dem Inhalt.

---

## Offene Mängel

Aufgenommen, nichts davon repariert. Diese Seite ist öffentlich — die Fokus- und
Formularpunkte sind Barrierefreiheitsmängel, keine Kosmetik.

### Fokus: 41 Hover-Regeln, 2 Fokus-Regeln

In den Templates stehen rund **64 Bedienelemente** (28 `<a>`, 18 `<button>`, 15
`<input>`, 2 `<select>`, 1 `<textarea>`). Dem stehen in 4273 Zeilen CSS **zwei**
`:focus-visible`-Regeln gegenüber — Karussell-Bühne und verlinkte Spalte. Alles andere,
einschließlich Absendebutton, Sidebar-Navigation, Footer-Links und Katalogsteuerung,
hat nur den Browser-Default. Ein `--focus`-Token existiert nicht.

### Formularzustände

- **Eingabefelder haben keinen eigenen Fokusstil** — verstößt direkt gegen `../CLAUDE.MD`.
- **`:invalid` kommt im gesamten Stylesheet nicht vor**, obwohl das Kontaktformular mit
  Pflichtfeldern und `type="email"` arbeitet.
- **`:disabled` nur an den Katalogpfeilen** (`theme.css:4140`). Der Absendebutton hat
  keinen Disabled-Zustand, behält also Hover-Transform und Farbwechsel.
- **Hover am Absendebutton kann unsichtbar sein**: er wechselt auf `--color-secondary`,
  und `brand.php` setzt Secondary auf Primary zurück, wenn im CMS kein eigener Wert
  hinterlegt ist. Dann ändert sich beim Hover nur die Position, nicht die Farbe.

### Kontraste unter 4.5:1

Gerechnet nach WCAG 2.1 gegen die tatsächlich im CSS stehenden Paare:

| Paar | Verwendung | Ratio | AA-Text | 3:1 |
|---|---|---|---|---|
| `#94a3b8` auf `#f8fafc` | Kalendertag außerhalb des Monats | **2.45:1** | ✗ | ✗ |
| `#eceff4` auf `#7b8089` | Datums-Badge vergangener Termine | **3.44:1** | ✗ | ✔ |
| Primary-Default `#2563eb` auf `--sidebar-bg #17181a` | Sidebar-Kontaktzeile | **3.44:1** | ✗ | ✔ |

Der Sidebar-Wert hängt an der CMS-Farbe: `--sidebar-contact` ist `--color-primary`. Mit
dem Default `#2563eb` sind es 3.44:1; **ein dunklerer Kundenton verschlechtert das
weiter.** Hier gehört eine Aufhellung gegen den Graphit-Grund hin, keine feste Farbe.

Unauffällig geprüft und bestanden: Fließtext 14.63:1, `--color-text-muted` 4.76:1 auf
Weiß und 4.55:1 auf `--color-bg-alt`, Primary auf Weiß 5.17:1, Footer-Text 7.03:1,
Event-Labels 12.02:1, Service-Spalte 11.22:1, Kontakt-Badge 7.38:1.

**Zusätzlich, kein Kontrastproblem:** Der Öffnungs-Punkt unterscheidet offen von
geschlossen **allein über die Farbe** (`#10b981` gegen `#737b86`, beide ≥3:1 gegen den
Grund). WCAG 1.4.1 verlangt ein zweites Merkmal — Form, Text oder Symbol.

### Gerüstreste

- **`#2563eb` steht an vier Stellen**: `brand.php:47` als Default und in `theme.css`
  Zeilen 31/33/35 als `var()`-Fallback, zusammen mit `#1d4ed8` und `#f59e0b`. Das sind
  unveränderte Tailwind-Standardwerte (blue-600 / blue-700 / amber-500) aus dem
  Ausgangsgerüst, keine Entscheidung für diesen Kunden.
- **Die Slate-Ebene** (siehe Abschnitt 2) ist die zweite, ungenutzte Tokenebene. Sie ist
  nicht folgenlos löschbar, weil sie die `--color-*`-Tokens definiert. Ablösung heißt:
  Tokenwerte auf Graphit umstellen, dann die 20 Slate-Literale im Events-Widget
  nachziehen.
- **131 Hex-Literale, 48 verschiedene** in `theme.css` — gegen die Regel „ein Farbton
  steht genau einmal" aus `../CLAUDE.MD`.
- **11 Verläufe**, darunter `linear-gradient(115deg, …)` als Fläche auf
  `.block-columns--service` (`theme.css:2682`) — der Guardrail „keine Farbverläufe als
  Flächen" ist dort verletzt. Die Karussell-Kantenverläufe sind Abblendungen und
  unkritisch.
- **14× `text-align: center`** — nicht geprüft, ob darunter Textblöcke über zwei Zeilen
  sind. **TODO: ungeklärt.**

### theme.css: keine Referenz aufs CMS

Geprüft, weil die Datei im CMS gelöscht wurde. **Das Frontend ist nicht betroffen.**
`templates/layout.php:32` baut den Pfad als `$assetBaseUrl . '/assets/css/theme.css'`,
und `$assetBaseUrl` wird von `index.php` **nie gesetzt** — im Live-Pfad ist es immer
leer, die Datei kommt also von der eigenen Domain aus diesem Repo und wird über die
Positivliste mit ausgeliefert.

Die einzige Stelle, die `assetBaseUrl` überhaupt durchreicht, ist
`themes/default/layout.php:111` — der Einstiegspunkt, über den **das CMS** dieses Theme
rendert. Ein CMS-seitiger Vorschaupfad könnte dort auf eine CMS-eigene Kopie zeigen.
**TODO: ungeklärt** — das liegt im CMS-Repo und ist von hier aus nicht prüfbar.
