# Identity

[English](README.md) | [Deutsch](README.de.md)

Laravel-Anwendung als Login- und Consent-Oberfläche für Ory Hydra. Lokal stellt Yerd Laravel unter `identity.test` bereit; Hydra und seine eigene PostgreSQL-Datenbank laufen über Docker Compose.

## Voraussetzungen

- Yerd mit PHP 8.5 und dem lokalen PostgreSQL-Service
- Composer und Node.js/npm
- Docker mit Docker Compose
- Für lokale HTTPS-Zertifikate: Yerds lokale CA muss installiert und vertraut sein

## 1. Yerd und PostgreSQL vorbereiten

Im Projektverzeichnis:

```sh
yerd link identity
yerd secure identity
yerd service start postgres
yerd db create postgres identity
```

`yerd link identity` und `yerd db create postgres identity` sind einmalige Schritte. Wenn Site oder Datenbank bereits existieren, diese Befehle überspringen. Mit `yerd sites` und `yerd services` lässt sich der Status prüfen.

Yerds PostgreSQL lauscht standardmäßig auf `127.0.0.1:5432`. Der Datenbankname muss mit `DB_DATABASE` in `.env` übereinstimmen.

## 2. Laravel einrichten

```sh
test -f .env || cp .env.example .env
composer install
```

`php artisan key:generate` nur beim ersten Setup ausführen, wenn `APP_KEY` noch leer ist. Danach `.env` für Yerds PostgreSQL konfigurieren. Beispiel für einen lokalen Dienst ohne Datenbankpasswort:

```dotenv
APP_URL=https://identity.test
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=identity
DB_USERNAME=postgres
DB_PASSWORD=
HYDRA_PUBLIC_URL=https://hydra.identity.test
HYDRA_ADMIN_URL=http://localhost:4445
HYDRA_SYSTEM_SECRET=<generierter Hex-Wert>
```

`DB_USERNAME` und `DB_PASSWORD` müssen zu deiner Yerd-PostgreSQL-Konfiguration passen. `HYDRA_PUBLIC_URL` ist Hydras Browser-Adresse; die Admin-URL bleibt lokal. `.env` enthält lokale Schlüssel und Passwörter und darf nicht eingecheckt werden.

Datenbanktabellen anlegen:

```sh
php artisan migrate
```

## 3. Hydra über Yerd und Docker starten

Yerd benötigt einen Host-Proxy von der Hydra-Subdomain auf Hydras Public-Port:

```sh
yerd proxy add hydra http://127.0.0.1:4444
yerd domain add hydra hydra.identity.test
```

Diese Proxy- und Domain-Einrichtung ist einmalig. Falls sie schon existiert, nicht erneut hinzufügen.

Hydra samt eigener PostgreSQL-Datenbank starten:

```sh
docker compose -f compose.dev.yml up -d hydra
```

Compose wartet auf die Hydra-Datenbank, führt automatisch `hydra migrate sql up` aus und startet danach Hydra. Der Hydra-Admin-Port `4445` bleibt an localhost gebunden und wird nicht über die Subdomain veröffentlicht. `HYDRA_PUBLIC_URL` muss auf `https://hydra.identity.test` zeigen. Falls noch kein `HYDRA_SYSTEM_SECRET` gesetzt ist, erzeuge eins mit `openssl rand -hex 32` und trage den ausgegebenen Wert in `.env` ein.

Status und Logs:

```sh
docker compose -f compose.dev.yml ps
docker compose -f compose.dev.yml logs -f hydra
```

Die Discovery-Metadaten sollten unter `https://hydra.identity.test/.well-known/openid-configuration` erreichbar sein.

## 4. Frontend im Entwicklungsmodus starten

In einem eigenen Terminal im Projektverzeichnis:

```sh
npm install
npm run dev
```

Der korrekte Befehl ist `npm install` (nicht `npm run install`). `npm run dev` muss während der lokalen Entwicklung laufen, damit Vite die Frontend-Assets ausliefert. Yerd bedient parallel dazu Laravel unter `https://identity.test`.

## 5. Prüfen

```sh
php artisan test
```

Der Express-OIDC-Testclient ist optional und separat dokumentiert: [oauth-client-demo/README.md](oauth-client-demo/README.md). Er läuft unter `http://localhost:3000`.

## Dienste stoppen

Hydra und die Hydra-Datenbank stoppen, ohne die persistente Hydra-Datenbank zu löschen:

```sh
docker compose -f compose.dev.yml down
```

Zum Stoppen von Yerds PostgreSQL:

```sh
yerd service stop postgres
```

Das stoppt auch die von Yerd verwalteten lokalen Datenbanken, nicht nur die für dieses Projekt verwendete `identity`-Datenbank.
