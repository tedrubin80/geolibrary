# Publishing GeoOptimizer

This guide covers releasing the PHP library to Packagist and the WordPress plugin to WordPress.org.

Current release version: **2.0.0**

## Before you publish

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
git tag -a v2.0.0 -m "GeoOptimizer 2.0.0"
git push origin main --tags
```

Or create the release from the GitHub UI after pushing the tag.

## Packagist (PHP library)

1. Create a [Packagist](https://packagist.org/) account.
2. Submit the package: `https://github.com/tedrubin80/geolibrary`
3. Packagist reads `composer.json` (`geooptimizer/php-geo-optimizer`).
4. Enable the GitHub webhook (Packagist shows the hook URL after submission).
5. Push tag `v2.0.0` — Packagist auto-updates when the webhook is configured.

Install after publish:

```bash
composer require geooptimizer/php-geo-optimizer:^2.0
```

## WordPress.org

1. Register at [wordpress.org/plugins/developers](https://wordpress.org/plugins/developers/).
2. Submit a new plugin using the built zip from `dist/geooptimizer-wordpress-plugin.zip`.
3. After approval, check out the plugin SVN repository WordPress provides.
4. Copy build contents into `svn/trunk/` (plugin files under `geooptimizer/` in the zip → `trunk/` in SVN).
5. Copy `readme.txt` from `wordpress-plugin/geooptimizer/readme.txt`.
6. Tag the release:

```bash
svn cp trunk tags/2.0.0
svn commit -m "Tag version 2.0.0"
```

Update `Stable tag` in `readme.txt` for each new release.

## Version bumps

1. Update `src/Version.php`
2. Update `wordpress-plugin/geooptimizer/geooptimizer.php` header and `GEOOPTIMIZER_VERSION`
3. Update `wordpress-plugin/geooptimizer/readme.txt` stable tag and changelog
4. Run tests and build
5. Commit, tag `vX.Y.Z`, push tags
