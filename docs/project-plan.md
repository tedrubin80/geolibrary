# GeoOptimizer Project Plan

Last updated: July 2026

## Overview

GeoOptimizer is a PHP library and WordPress plugin for **Generative Engine Optimization (GEO)** — helping sites publish `llms.txt`, Schema.org JSON-LD, and GEO readiness analysis for AI-powered search systems.

- **Website:** [geooptimizer.dev](https://geooptimizer.dev)
- **Repository:** [github.com/tedrubin80/geolibrary](https://github.com/tedrubin80/geolibrary)
- **Current version:** `2.2.0`

---

## Current Repository Layout

```
src/                              PHP library (GEOOptimizer\)
wordpress-plugin/geooptimizer/    WordPress plugin distribution
public/                           Static marketing site
docs/                             Usage and development guides
tools/build.php                   Build library / plugin zip artifacts
tests/                            PHPUnit unit tests
```

---

## Implemented Components

### Core library

| Component | Path | Status |
|-----------|------|--------|
| Main API | `src/GEOOptimizer.php` | Done |
| Version constant | `src/Version.php` | Done |
| llms.txt generator | `src/LLMSTxt/Generator.php` | Done |
| Schema.org generator | `src/StructuredData/SchemaGenerator.php` | Done |
| Content analyzer | `src/Analysis/ContentAnalyzer.php` | Done |
| Industry templates | `src/Templates/IndustryTemplateManager.php` | Done |
| Citation tracker | `src/Analytics/CitationTracker.php` | Done |
| Citation dashboard | `src/Analytics/CitationDashboard.php` | Done |
| Bulk site analyzer | `src/Analysis/BulkSiteAnalyzer.php` | Done |
| Competitor analyzer | `src/Analysis/CompetitorAnalyzer.php` | Done |
| GEO readiness score | `src/Analytics/GEOReadinessScore.php` | Done |
| Laravel integration | `src/Integrations/Laravel/*` | Done |
| Symfony integration | `src/Integrations/Symfony/*` | Done |
| REST API | `src/API/*`, `bin/geo-api` | Done |
| Web dashboard UI | `public/dashboard/*` | Done |
| WordPress premium tier | `src/Integrations/WordPress/Premium/*` | Done |
| Platform adapters | `src/Platforms/*` | Done |
| Cache layer | `src/Cache/*` | Done |
| URL validation | `src/Http/UrlValidator.php` | Done |
| Security helpers | `src/Security/*` | Done |
| CLI commands | `src/CLI/Commands/*` | Done |

### WordPress plugin

| Feature | Status |
|---------|--------|
| Settings page under **Settings → GEO Optimizer** | Done |
| Business profile fields (name, description, industry, location, phone, email) | Done |
| Services and business hours fields | Done |
| Industry dropdown from library templates | Done |
| Feature toggles for JSON-LD and `/llms.txt` | Done |
| `/llms.txt` rewrite endpoint with cache | Done |
| Schema.org JSON-LD in `wp_head` | Done |
| Admin AJAX: generate llms.txt, analyze content | Done |
| Premium tier: license, dashboard, bulk/compare | Done |
| llms.txt cache refresh on post save | Done |
| Brain Monkey unit tests for sanitization/helpers | Done |

### Tooling and distribution

| Item | Status |
|------|--------|
| GitHub Actions CI (PHPUnit, PHPStan, audit) | Done |
| GitHub release workflow on version tags | Done |
| Publish verification script (`tools/publish-verify.php`) | Done |
| `tools/build.php` library zip | Done |
| `tools/build.php --plugin` WordPress zip | Done |
| Composer archive excludes for lean packages | Done |
| Static site at `public/` | Done |

---

## Test Coverage

Current unit tests cover:

- `GEOOptimizer`
- `SchemaGenerator`
- `ContentAnalyzer`
- `LLMSTxt\Generator`
- `IndustryTemplateManager`
- `GEOReadinessScore`
- Cache adapters (`FileCache`, `MemoryCache`)
- `UrlValidator`
- `BulkAndCompetitorAnalyzerTest`
- `CitationDashboard` / REST API endpoint tests
- WordPress `Plugin` sanitization and data helpers
- WordPress premium license tests

Run the suite:

```bash
composer test
composer phpstan
```

PHPStan analyzes the full `src/` tree with WordPress stubs for the plugin integration.

---

## Build Artifacts

```bash
php tools/build.php           # dist/geooptimizer-php-library.zip
php tools/build.php --plugin  # dist/geooptimizer-wordpress-plugin.zip
```

The plugin build uses a staged copy of the library (`dist/library-staging`) so the distributable zip does not include tests, docs, nested `dist/`, or other repository-only files.

---

## Future / Not Yet Implemented

| Area | Notes |
|------|-------|
| Shopify integration | Not started |
| REST webhooks | Not started |
| A/B testing framework | Not started |
| Content optimizer modules (FAQ generator, meta optimizer) | Not started |
| Keyword analyzer | Not started |

## External publishing (manual account steps)

Release artifacts, readme, and workflows are ready. These steps require Packagist and WordPress.org accounts:

| Channel | Status | Action |
|---------|--------|--------|
| GitHub releases | Done (v2.2.0) | Automatic on tag push |
| Packagist | Live | [packagist.org/packages/geooptimizer/php-geo-optimizer](https://packagist.org/packages/geooptimizer/php-geo-optimizer) |
| WordPress.org | Not submitted | Upload release zip — see [publishing.md](publishing.md) |

---

## Near-Term Roadmap

### Phase 1 — Stabilize

- [x] Fix WordPress structured data integration
- [x] Align version numbers across library and plugin
- [x] Add services/hours settings to plugin
- [x] Expand PHPUnit and PHPStan coverage
- [x] Reduce plugin build artifact bloat
- [x] Add WordPress.org readme and publishing docs
- [x] Add GitHub release workflow and version tags through v2.2.0
- [x] Add publish verification script (`tools/publish-verify.php`)
- [x] Publish to Packagist — [geooptimizer/php-geo-optimizer](https://packagist.org/packages/geooptimizer/php-geo-optimizer)
- [ ] Submit WordPress plugin to wordpress.org (manual — see [publishing.md](publishing.md))

### Phase 2 — Developer experience

- [x] Laravel service provider
- [x] Symfony bundle
- [x] REST API for SaaS / automation use cases
- [x] More CLI validation and reporting commands
- [x] Integration tests against a WordPress test harness

### Phase 3 — Product features

- [x] Citation tracking dashboard (web UI)
- [x] Bulk site analysis tools
- [x] Competitor comparison
- [x] Premium WordPress plugin tier

---

## Development Commands

```bash
composer install
composer test
composer phpstan
composer audit
php tools/build.php --plugin
```

See [development.md](development.md) for environment and security notes. Framework integrations are documented in [integrations.md](integrations.md).

---

## Success Metrics (targets)

| Metric | Target |
|--------|--------|
| Composer installs | 1,000+ |
| WordPress active installs | 10,000+ |
| Test suite | Green on every main-branch push |
| Static analysis | PHPStan level 5, zero errors |

This document reflects the **actual** codebase state and replaces the earlier aspirational file tree that listed many modules not yet built.
