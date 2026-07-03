# GeoOptimizer

PHP library and WordPress plugin for Generative Engine Optimization (GEO).

## Layout

- `src/` — canonical PHP library (Composer package `geooptimizer/php-geo-optimizer`)
- `wordpress-plugin/geooptimizer/` — WordPress plugin distribution
- `public/` — static marketing site for geooptimizer.dev
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

## Security notes

- Cache layers use JSON serialization (not PHP `unserialize`).
- Outbound webhooks are restricted to public HTTPS URLs (`UrlValidator`).
- The marketing site serves external CSS with a strict Content-Security-Policy.
