# EA Poetters Frontend

Eigenständiges, CMS-angebundenes Frontend für EA Poetters. Navigation, Logo,
Kontaktdaten, Medien, Seiten und Inhalte werden aus der über `CMS_API_URL`
zugeordneten CMS-Instanz geladen.

## Lokal starten

1. `.env.example` nach `.env` kopieren und `CMS_API_URL` setzen.
2. `php -S localhost:8002 index.php` starten.

`.env`, Cache, Logs, Schlüssel und sonstige Laufzeitdaten bleiben lokal.

## Zuordnung in der Verwaltung

Vor dem ersten Rollout müssen beim Kundendatensatz von EA Poetters diese Werte
unter **Frontend-Zuordnung** hinterlegt sein:

- Frontend-Key: `ea-poetters`
- Frontend-Repository: `SebastianWauer/frontend-eapoetters`
- Branch oder Tag: `main`

Der Frontend-Key muss mit der `id` in `frontend.json` übereinstimmen.
