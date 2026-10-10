# Production Compose Deployment

`compose.prod.yml` builds the existing Apache/PHP Dockerfile target and runs separate PostgreSQL databases for Laravel and Hydra. Laravel migrations and Hydra SQL migrations run as one-shot services before their applications start. The scheduler runs independently using `php artisan schedule:work`.

## Infisical variables

Provide these variables to the Compose process through the production Infisical environment:

- `APP_KEY`
- `APP_URL`, for example `https://identity.example.com` (no trailing slash)
- `DB_PASSWORD`
- `HYDRA_PUBLIC_URL`, for example `https://hydra.example.com` (no trailing slash)
- `HYDRA_DB_PASSWORD`
- `HYDRA_DSN`, a PostgreSQL URL for the `hydra-db` service using the same Hydra database credentials
- `HYDRA_SYSTEM_SECRET`, at least 32 random bytes encoded as hex

`HYDRA_DSN` must URL-encode reserved characters in its username or password. A hex-encoded `HYDRA_DB_PASSWORD` avoids URL-reserved characters. Optional overrides are `APP_PORT` (default `8080`), `HYDRA_PUBLIC_PORT` (default `4446`), `DB_DATABASE`, `DB_USERNAME`, `HYDRA_DB_USER`, and `HYDRA_LOG_LEVEL`.

Run Compose with the Infisical-injected environment and disable Compose's automatic loading of the repository's development `.env` file:

```sh
docker compose --env-file /dev/null -f compose.prod.yml up -d --build
```

The published upstreams are bound only to host loopback: Laravel at `127.0.0.1:8080` and Hydra Public at `127.0.0.1:4446` by default. Configure host Nginx to proxy the application hostname to the Laravel port and the Hydra hostname to the Hydra port, preserving `Host` and forwarding `X-Forwarded-Proto: https`. Hydra Admin (`4445`) and both database ports are not published to the host.

The scheduler container is running, but this repository currently defines no scheduled tasks in `routes/console.php`.