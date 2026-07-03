# Publishing GeoOptimizer

This guide covers releasing the PHP library to Packagist and the WordPress plugin to WordPress.org.

Current release version: **2.2.1**

## Before you publish

Run the full verification script:

```bash
php tools/publish-verify.php
```

Or run each step manually:

```bash
composer test
composer phpstan
composer audit
php tools/build.php --plugin
```

Verify artifacts in `dist/`:

- `geooptimizer-php-library.zip`
- `geooptimizer-wordpress-plugin.zip`

## GitHub release

Tags matching `v*` trigger `.github/workflows/release.yml`, which runs tests, builds artifacts, and attaches them to a GitHub Release.

```bash
git tag -a v2.2.0 -m "GeoOptimizer 2.2.0"
git push origin main --tags
```

Latest release: [github.com/tedrubin80/geolibrary/releases](https://github.com/tedrubin80/geolibrary/releases)

## Packagist (PHP library)

Package: [packagist.org/packages/geooptimizer/php-geo-optimizer](https://packagist.org/packages/geooptimizer/php-geo-optimizer)

Install:

```bash
composer require geooptimizer/php-geo-optimizer:^2.2
```

Packagist auto-updates from GitHub when the webhook is configured. Update the `description` field in `composer.json` for listing copy changes.

## WordPress.org

1. Register at [wordpress.org/plugins/developers](https://wordpress.org/plugins/developers/).
2. Submit a new plugin using the built zip from `dist/geooptimizer-wordpress-plugin.zip` (or download from the GitHub release).
3. After approval, check out the plugin SVN repository WordPress provides.
4. Copy build contents into `svn/trunk/` (plugin files under `geooptimizer/` in the zip → `trunk/` in SVN).
5. Copy `readme.txt` from `wordpress-plugin/geooptimizer/readme.txt`.
6. Tag the release:

```bash
svn cp trunk tags/2.2.0
svn commit -m "Tag version 2.2.0"
```

Update `Stable tag` in `readme.txt` for each new release.

**Premium tier testing:** use demo license key `GEO-DEMO-9444` in **Settings → GEO Optimizer** before submitting to verify the dashboard and bulk/compare tools.

## Version bumps

1. Update `src/Version.php`
2. Update `wordpress-plugin/geooptimizer/geooptimizer.php` header and `GEOOPTIMIZER_VERSION`
3. Update `wordpress-plugin/geooptimizer/readme.txt` stable tag and changelog
4. Run `php tools/publish-verify.php`
5. Commit, tag `vX.Y.Z`, push tags
