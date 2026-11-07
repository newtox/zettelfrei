# Zettelfrei

Anonyme Abstimmungen für Familie und Freunde, ganz ohne Zettel und Stift. Ursprünglich für die jährliche Weihnachtsbaum-Bewertung der Verwandtschaft gebaut, inzwischen für jede Art von Abstimmung: Raum anlegen, QR-Code zeigen, auf dem Handy abstimmen und am Ende gemeinsam auflösen.

## Funktionen

- **Zwei Abstimmungsarten pro Raum**
  - *Punkte vergeben:* 1–10 Punkte pro Kriterium für jeden Eintrag
  - *Auswählen:* jeder wählt einen Favoriten oder bis zu N
- **Vorlagen beim Erstellen:** Weihnachtsbäume (Rundgang), Wettbewerb (alle gleichzeitig), Umfrage (Auswahl). Jede füllt Begriffe, Schalter, Theme und Kriterien vor
- **Eigene Begriffe pro Raum**, z. B. Familie/Familien, Beitrag/Beiträge, Option/Optionen
- **Schalter pro Raum:** eigenen Eintrag ausschließen, Fotos erlauben, einer nach dem anderen (Rundgang) oder alle gleichzeitig, Anzahl der Auswahlen
- **Kriterien-Vorlagen** (Klassisch, Schnell, Ausführlich) plus eigene Vorlagen pro Konto
- **Räume erstellen ohne Konto:** Der Ersteller wird per Cookie als Admin erkannt und bekommt einen geheimen Admin-Link für andere Geräte. Der Link kann jederzeit neu erzeugt werden, dann wird der alte ungültig
- **Beitritt ohne Konto** per Link, QR-Code oder 6-stelligem Code, nur mit Namen
- **Konten (optional)** wie bei graf-cedric.de: nur Login, keine öffentliche Registrierung. Mit Konto gibt es eigene Kriterien-Vorlagen und „Meine Räume“ auf allen Geräten
- **Super-Admin-Bereich** unter `/admin`: alle Räume mit Ersteller, letzter Aktivität und Löschdatum, jeden Raum öffnen, löschen oder auf „Behalten“ setzen, Konten anlegen, bearbeiten und löschen
- **Automatisches Aufräumen:** Räume ohne Aktivität werden nach `ROOM_RETENTION_DAYS` Tagen (Standard 90) samt Fotos gelöscht, außer sie stehen auf „Behalten“
- **Anonyme Abstimmung:** Stimmzettel speichern nur einen HMAC aus dem geheimen Geräte-Token und dem Eintrag bzw. Raum, nie die Teilnehmer-ID. Auch in der Datenbank lässt sich keine Stimme einer Person zuordnen
- **Stimme ändern**, solange die Abstimmung offen ist
- **Fotos** kann jeder im Raum hochladen. Sie werden im Browser auf 2000 px verkleinert und sind nur für Raummitglieder abrufbar
- **Auflösung** Platz für Platz vom letzten bis zum Sieger oder alles auf einmal
- **Live-Status** per Polling
- **Themes** Weihnachtlich, Dunkel, Hell: pro Raum für alle gesetzt, auf jedem Gerät zusätzlich frei umschaltbar

## Stack

Laravel 13, Blade-Komponenten, Tailwind CSS 4, Alpine.js, SQLite (MySQL geht auch über `DB_*`).

## Lokal starten

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
php artisan serve
```

Super-Admin anlegen:

```bash
php artisan user:create justin.lys26@gmail.com --name=Justin --admin
```

Ohne `--password` wird ein Passwort erzeugt und ausgegeben. Danach unter `http://localhost:8000` einen Raum erstellen, dafür braucht es kein Konto. Zum Testen mit mehreren Personen einfach den Raum-Link in einem privaten Fenster oder auf dem Handy öffnen.

Tests:

```bash
php artisan test
```

## Deployment (Docker / Portainer)

Bei jedem Push auf `main` laufen erst die Tests, danach baut die GitHub Action das Image `ghcr.io/newtox/zettelfrei:latest`.

1. Einmalig einen App-Key erzeugen:
   ```bash
   docker run --rm --entrypoint php ghcr.io/newtox/zettelfrei artisan key:generate --show
   ```
2. `docker-compose.yml` als Stack in Portainer anlegen, `APP_KEY`, `APP_URL`, `ADMIN_EMAIL` und `ADMIN_PASSWORD` eintragen. Beim ersten Start wird damit dein Super-Admin angelegt. Danach kannst du das Passwort aus dem Stack wieder entfernen, ein bestehendes Konto wird nie überschrieben.
3. In Caddy:
   ```
   abstimmen.newtox.de {
       reverse_proxy 127.0.0.1:8090
   }
   ```

Datenbank, Fotos, Sessions und Logs liegen im Volume `/docker_volumes/zettelfrei`. Migrationen laufen beim Containerstart automatisch, der Scheduler für das Aufräumen (täglich 04:00) läuft im selben Container mit.

Weitere Konten per Admin-Bereich oder im Container:

```bash
docker exec -it zettelfrei php artisan user:create mama@example.com --name=Mama
```

### Umgebungsvariablen

| Variable | Standard | Bedeutung |
| --- | --- | --- |
| `APP_KEY` | – | Pflicht. Wird auch für die anonymen Stimmzettel-Hashes und die verschlüsselten Admin-Links benutzt, also nicht mehr ändern |
| `APP_URL` | – | Öffentliche URL, wird für Links und QR-Codes verwendet |
| `DEFAULT_THEME` | `christmas` | Theme für Startseite und Login |
| `ROOM_RETENTION_DAYS` | `90` | Tage ohne Aktivität bis zur automatischen Löschung, `0` schaltet das Aufräumen ab |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | – | Legt beim Start einen Super-Admin an, falls es das Konto noch nicht gibt |
| `POLL_INTERVAL_MS` | `4000` | Wie oft Handys den Status abfragen |
| `MAX_PHOTO_KB` | `12288` | Maximale Upload-Größe pro Foto |
| `SESSION_SECURE_COOKIE` | – | Hinter HTTPS auf `true` setzen |

## Kontakt

justin.lys26@gmail.com
