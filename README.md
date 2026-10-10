# Identity

[English](README.md) | [Deutsch](README.de.md)

Laravel application providing the login and consent UI for Ory Hydra. Locally, Yerd serves Laravel at `identity.test`; Hydra and its dedicated PostgreSQL database run through Docker Compose.

## Prerequisites

- Yerd with PHP 8.5 and its local PostgreSQL service
- Composer and Node.js/npm
- Docker with Docker Compose
- Yerd's local CA installed and trusted for local HTTPS certificates

## 1. Set up Yerd and PostgreSQL

Run these commands from the project directory:

```sh
yerd link identity
yerd secure identity
yerd service start postgres
yerd db create postgres identity
```

`yerd link identity` and `yerd db create postgres identity` are one-time steps. Skip them if the site or database already exists. Check their status with `yerd sites` and `yerd services`.

Yerd's PostgreSQL service listens on `127.0.0.1:5432` by default. Its database name must match `DB_DATABASE` in `.env`.

## 2. Set up Laravel

```sh
test -f .env || cp .env.example .env
composer install
```

Run `php artisan key:generate` only during the initial setup when `APP_KEY` is empty. Then configure `.env` for Yerd's PostgreSQL service. Example for a local service without a database password:

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
HYDRA_SYSTEM_SECRET=<generated-hex-value>
```

`DB_USERNAME` and `DB_PASSWORD` must match your Yerd PostgreSQL configuration. `HYDRA_PUBLIC_URL` is Hydra's browser-facing address; the admin URL remains local. `.env` contains local keys and passwords and must not be committed.

Create the Laravel database tables:

```sh
php artisan migrate
```

## 3. Start Hydra through Yerd and Docker

Yerd needs a whole-host proxy from Hydra's subdomain to Hydra's public port:

```sh
yerd proxy add hydra http://127.0.0.1:4444
yerd domain add hydra hydra.identity.test
```

Set up this proxy and domain once. Skip these commands if they already exist.

Start Hydra and its dedicated PostgreSQL database:

```sh
docker compose -f compose.dev.yml up -d hydra
```

Compose waits for the Hydra database, automatically runs `hydra migrate sql up`, and then starts Hydra. The Hydra admin port `4445` is bound to localhost and is not exposed through the subdomain. Set `HYDRA_PUBLIC_URL` to `https://hydra.identity.test`. If `HYDRA_SYSTEM_SECRET` is not set yet, generate one with `openssl rand -hex 32` and put the resulting value in `.env`.

Check status and logs:

```sh
docker compose -f compose.dev.yml ps
docker compose -f compose.dev.yml logs -f hydra
```

The discovery metadata should be available at `https://hydra.identity.test/.well-known/openid-configuration`.

## 4. Start the frontend development server

In a separate terminal in the project directory, run:

```sh
npm install
npm run dev
```

The correct command is `npm install`, not `npm run install`. Keep `npm run dev` running during local development so Vite can serve the frontend assets. Yerd serves Laravel at `https://identity.test` at the same time.

## 5. Run the tests

```sh
php artisan test
```

The Express OIDC test client is optional and documented separately in [oauth-client-demo/README.md](oauth-client-demo/README.md). It runs at `http://localhost:3000`.

## Stop the services

Stop Hydra and its database without deleting the persistent Hydra database:

```sh
docker compose -f compose.dev.yml down
```

To stop Yerd's PostgreSQL service:

```sh
yerd service stop postgres
```

This stops all local databases managed by Yerd, not only the `identity` database used by this project.
