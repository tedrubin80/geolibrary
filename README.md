# GeoOptimizer

[![CI](https://github.com/tedrubin80/geolibrary/actions/workflows/ci.yml/badge.svg)](https://github.com/tedrubin80/geolibrary/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/github/license/tedrubin80/geolibrary)](https://github.com/tedrubin80/geolibrary/blob/main/LICENSE)
[![PHP](https://img.shields.io/badge/php-%3E%3D7.4-777bb4)](composer.json)

**Generative Engine Optimization (GEO) for PHP** — generate `llms.txt` files, Schema.org structured data, and citation-ready markup so AI search engines (ChatGPT, Claude, Perplexity, Google AI Overviews) can find and cite your site accurately.

- **Website:** [geooptimizer.dev](https://geooptimizer.dev)
- **Repository:** [github.com/tedrubin80/geolibrary](https://github.com/tedrubin80/geolibrary)

## What is GEO?

Generative Engine Optimization is the practice of structuring web content so AI-powered search systems represent your business correctly. GeoOptimizer handles formatting, industry templates, structured data, and readiness scoring — without requiring deep AI expertise.

## Features

- **llms.txt generation** — AI-oriented site summaries following the emerging llms.txt convention
- **Structured data** — Schema.org markup for local business, services, FAQs, and more
- **Content analysis** — GEO readiness scoring with actionable recommendations
- **Industry templates** — Pre-built baselines for 12+ industries
- **Citation tracking** — Monitor mentions across major AI platforms (optional API keys)
- **WordPress plugin** — Admin UI, automatic `llms.txt` endpoint, structured data in `wp_head`
- **Framework integrations** — Laravel service provider, Symfony bundle
- **REST API** — Automation endpoints with optional API key auth and rate limiting

## Requirements

- PHP 7.4+ (PHP 8.2+ recommended for development tooling)
- [Composer](https://getcomposer.org/)

## Installation

```bash
composer require geooptimizer/php-geo-optimizer
```

For local development from this repository:

```bash
git clone https://github.com/tedrubin80/geolibrary.git
cd geolibrary
composer install
```

## Quick start

```php
<?php
require 'vendor/autoload.php';

use GEOOptimizer\GEOOptimizer;

$geo = new GEOOptimizer();

$results = $geo->optimize([
    'business_name' => 'Acme Coffee',
    'description'   => 'Specialty coffee roaster and café.',
    'industry'      => 'restaurant',
    'location'      => 'San Francisco, CA',
    'services'      => ['Espresso', 'Pour Over', 'Retail Beans'],
]);

file_put_contents('public/llms.txt', $results['llms_txt']);
```

## Project layout

```
src/                          PHP library (PSR-4: GEOOptimizer\)
wordpress-plugin/geooptimizer/ WordPress plugin distribution
public/                       Static site (geooptimizer.dev)
docs/                         Usage and development guides
tools/build.php               Build library / plugin zip artifacts
```

## WordPress plugin

```bash
cd wordpress-plugin/geooptimizer
composer install
```

Activate **GeoOptimizer** in wp-admin, configure business details under **Settings → GEO Optimizer**, and visit `/llms.txt` on your site.

Build a distributable zip:

```bash
php tools/build.php --plugin
# → dist/geooptimizer-wordpress-plugin.zip
```

## Development

```bash
composer test          # PHPUnit
composer phpstan       # Static analysis
composer audit         # Dependency security audit
php tools/build.php    # Package artifacts into dist/
```

See [docs/development.md](docs/development.md) for build, CI, and security notes.

## Documentation

- [Development guide](docs/development.md)
- [Publishing guide](docs/publishing.md)
- [Framework integrations](docs/integrations.md)
- [Usage guide](docs/usage-guide.md)
- [How to build](docs/how-to-build.md)
- [Project plan](docs/project-plan.md)

## License

This project is licensed under the [MIT License](LICENSE).
