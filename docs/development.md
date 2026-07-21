# GeoOptimizer

PHP library and WordPress plugin for Generative Engine Optimization (GEO).

## Layout

- `src/` — canonical PHP library (Composer package `geooptimizer/php-geo-optimizer`)
- `wordpress-plugin/geooptimizer/` — WordPress plugin distribution
- `public/` — marketing site, dashboard UI, and API front controller for geooptimizer.dev
- `tools/build.php` — build library and plugin zip artifacts into `dist/`

## Development

```bash
composer install
composer test
composer phpstan
composer audit
```

PHPStan analyzes most of `src/` (WordPress stubs enabled for the plugin integration). `SchemaGenerator` and CLI commands are excluded until Spatie/Symfony typing issues are resolved.

WordPress plugin helpers are covered with Brain Monkey unit tests under `tests/Unit/Integrations/WordPress/`.

## Local full-stack server

Serve the marketing site, dashboard, and REST API the same way Railway does:

```bash
composer install
php -S 0.0.0.0:8080 -t public public/router.php
```

- Site: `http://127.0.0.1:8080/`
- Dashboard: `http://127.0.0.1:8080/dashboard/`
- API health: `http://127.0.0.1:8080/api/health`

### API environment variables

| Variable | Purpose |
|----------|---------|
| `GEO_API_KEY` | When set, protected API routes require matching `X-Api-Key` header. `/api/health` stays public. |
| `PORT` | Listen port used by the Docker/Railway start command (default `8080`). |

Plugin and library download artifacts are published on [GitHub Releases](https://github.com/tedrubin80/geolibrary/releases), not from the Railway service.

## Railway deploy

The repo includes a `Dockerfile` and `railway.toml`. Railway builds with Composer (`--no-dev`) and starts:

```bash
php -S 0.0.0.0:$PORT -t public public/router.php
```

Health check path: `/api/health`.

## Build artifacts

```bash
php tools/build.php           # dist/geooptimizer-php-library.zip
php tools/build.php --plugin  # also dist/geooptimizer-wordpress-plugin.zip
```

## WordPress plugin

```bash
cd wordpress-plugin/geooptimizer
composer install
```

Then activate the plugin in WordPress. Admin AJAX actions require `manage_options` and a valid nonce.

Download the packaged zip from GitHub Releases:

https://github.com/tedrubin80/geolibrary/releases/latest/download/geooptimizer-wordpress-plugin.zip

## Security notes

- Cache layers use JSON serialization (not PHP `unserialize`).
- Outbound webhooks are restricted to public HTTPS URLs (`UrlValidator`).
- Set `GEO_API_KEY` in production before exposing the API beyond local use.
