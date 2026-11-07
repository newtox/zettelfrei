# Zettelfrei

Anonymous voting for family and friends, no pen and paper required. Originally built for my family's yearly Christmas tree rating on Boxing Day, now usable for any kind of vote: create a room, show the QR code, everyone votes on their phone, and the results are revealed together at the end.

The interface is in German.

## Features

- **Two voting modes per room**
  - *Scores:* 1–10 points per criterion for every entry
  - *Choice:* everyone picks one favourite or up to N
- **Templates when creating a room:** Christmas trees (one entry after another), contest (all entries at once), poll (choice). Each one presets labels, settings, theme and criteria
- **Custom labels per room**, e.g. family/families, entry/entries, option/options
- **Room settings:** exclude your own entry, allow photos, one entry at a time or all at once, number of choices
- **Criteria presets** (Klassisch, Schnell, Ausführlich) plus personal presets per account
- **No account needed to create a room:** the creator is recognised as room admin via cookie and gets a secret admin link for other devices. The link can be regenerated at any time, which invalidates the old one
- **No account needed to join** via link, QR code or 6-character code, just a name
- **Optional accounts:** login only, no public registration. Accounts get personal criteria presets and "Meine Räume" across devices
- **Super admin area** at `/admin`: all rooms with creator, last activity and deletion date; open, delete or keep any room; create, edit and delete accounts
- **Automatic cleanup:** rooms without activity are deleted together with their photos after `ROOM_RETENTION_DAYS` days (default 90), unless marked as kept
- **Anonymous ballots:** a ballot only stores an HMAC of the secret device token and the entry or room, never the participant ID. Votes cannot be traced back to a person, not even in the database
- **Change your vote** while voting is open
- **Photos** can be uploaded by anyone in the room. They are resized to 2000 px in the browser and only visible to room members
- **Reveal** place by place from last to first, or all at once
- **Live status** via polling
- **Themes** Christmas, dark, light: set per room for everyone, switchable per device

## Stack

Laravel 13, Blade components, Tailwind CSS 4, Alpine.js, SQLite (MySQL works too via `DB_*`).

## Local setup

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

Create a super admin:

```bash
php artisan user:create you@example.com --name=You --admin
```

Without `--password` a password is generated and printed. Then open `http://localhost:8000` and create a room, no account required. To test with several people, open the room link in a private window or on your phone.

Tests:

```bash
php artisan test
```

## Deployment (Docker)

Every push to `main` runs the tests first, then the GitHub Action builds and pushes `ghcr.io/newtox/zettelfrei:latest`.

1. Generate an app key once:
   ```bash
   docker run --rm --entrypoint php ghcr.io/newtox/zettelfrei artisan key:generate --show
   ```
2. Deploy `docker-compose.yml` (e.g. as a Portainer stack) and fill in `APP_KEY`, `APP_URL`, `ADMIN_EMAIL` and `ADMIN_PASSWORD`. The super admin is created on first start. You can remove the password afterwards, an existing account is never overwritten.
3. Put a reverse proxy in front, e.g. Caddy:
   ```
   vote.example.com {
       reverse_proxy 127.0.0.1:8095
   }
   ```

Database, photos, sessions and logs live in the volume mounted at `/var/www/html/storage`. Migrations run automatically on container start, and the scheduler for the cleanup (daily at 04:00) runs inside the same container.

More accounts can be created in the admin area or inside the container:

```bash
docker exec -it -u www-data zettelfrei php artisan user:create someone@example.com --name=Someone
```

### Environment variables

| Variable | Default | Description |
| --- | --- | --- |
| `APP_KEY` | – | Required. Also used for the anonymous ballot hashes and the encrypted admin links, so never change it once rooms exist |
| `APP_URL` | – | Public URL, used for links and QR codes |
| `DEFAULT_THEME` | `christmas` | Theme for the start page and login |
| `ROOM_RETENTION_DAYS` | `90` | Days without activity until a room is deleted, `0` disables the cleanup |
| `ADMIN_EMAIL` / `ADMIN_PASSWORD` | – | Creates a super admin on start if the account does not exist yet |
| `POLL_INTERVAL_MS` | `4000` | How often phones poll for status updates |
| `MAX_PHOTO_KB` | `12288` | Maximum upload size per photo |
| `SESSION_SECURE_COOKIE` | – | Set to `true` behind HTTPS |

## Contact

justin.lys26@gmail.com
