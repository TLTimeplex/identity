# Production Compose Deployment

`compose.prod.yml` builds the existing Apache/PHP Dockerfile target and runs separate PostgreSQL databases for Laravel and Hydra. Laravel migrations and Hydra SQL migrations run as one-shot services before their applications start. The scheduler runs independently using `php artisan schedule:work`.

## Infisical variables

Provide these variables to the Compose process through the production Infisical environment:

- `APP_KEY`
- `APP_URL`, set to `http://identity.timeliners.org` while using the temporary HTTP-only Nginx setup
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, and `MAIL_FROM_NAME`
- `DB_PASSWORD`
- `HYDRA_PUBLIC_URL`, set to the same `http://identity.timeliners.org` URL
- `HYDRA_DB_PASSWORD`
- `HYDRA_SYSTEM_SECRET`, at least 32 random bytes encoded as hex

Hydra's `DSN` is assembled from `HYDRA_DB_USER` and `HYDRA_DB_PASSWORD` by Compose, so `HYDRA_DSN` is not required. Optional port overrides are `APP_PORT` (default `3501`) and `HYDRA_PUBLIC_PORT` (default `3502`); other optional overrides are `DB_DATABASE`, `DB_USERNAME`, `HYDRA_DB_USER`, and `HYDRA_LOG_LEVEL`.

Run Compose with the Infisical-injected environment and disable Compose's automatic loading of the repository's development `.env` file:

```sh
docker compose --env-file /dev/null -f compose.prod.yml up -d --build
```

The Nginx server block in [`nginx/identity.timeliners.org.conf`](./nginx/identity.timeliners.org.conf) is an HTTP-only starting point for `identity.timeliners.org`. It sends `/oauth2/...`, `/.well-known/...`, `/userinfo`, and Hydra health endpoints to Hydra at `127.0.0.1:3502`; Laravel login/consent routes and all other paths go to Laravel at `127.0.0.1:3501`. The upstream ports are bound only to host loopback. Hydra Admin (`4445`) and both database ports are not published to the host.

This HTTP-only configuration is not suitable for production credentials or user sessions. Laravel and Hydra currently mark their cookies as secure, so browser login/consent sessions will not work over plain HTTP. Enable HTTPS and use `https://identity.timeliners.org` for both `APP_URL` and `HYDRA_PUBLIC_URL` before production use.

The scheduler container is running, but this repository currently defines no scheduled tasks in `routes/console.php`.