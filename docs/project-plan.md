# GeoOptimizer Project Plan

Last updated: July 2026

## Overview

GeoOptimizer is a PHP library and WordPress plugin for **Generative Engine Optimization (GEO)** — helping sites publish `llms.txt`, Schema.org JSON-LD, and GEO readiness analysis for AI-powered search systems.

- **Website:** [geooptimizer.dev](https://geooptimizer.dev)
- **Repository:** [github.com/tedrubin80/geolibrary](https://github.com/tedrubin80/geolibrary)
- **Current version:** `2.1.0`

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
| GEO readiness score | `src/Analytics/GEOReadinessScore.php` | Done |
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
| llms.txt cache refresh on post save | Done |
| Brain Monkey unit tests for sanitization/helpers | Done |

### Tooling and distribution

| Item | Status |
|------|--------|
| GitHub Actions CI (PHPUnit, PHPStan, audit) | Done |
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
- WordPress `Plugin` sanitization and data helpers

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

## Planned / Not Yet Implemented

These items appear in earlier planning docs but are **not** in the codebase today:

| Area | Notes |
|------|-------|
| Laravel / Symfony integrations | Laravel service provider, facade, and Symfony bundle |
| Shopify integration | Not started |
| REST API / webhooks / rate limiting | REST API with rate limiting; webhooks not started |
| A/B testing framework | Not started |
| Competitor / keyword analyzers | Not started |
| Content optimizer modules (FAQ generator, meta optimizer) | Not started |
| Advanced analytics dashboard | Citation tracking exists; no UI |
| WordPress.org submission | readme.txt and release zip ready; manual SVN upload required |
| Packagist release | composer metadata and tag ready; manual Packagist submit required |

---

## Near-Term Roadmap

### Phase 1 — Stabilize (current)

- [x] Fix WordPress structured data integration
- [x] Align version numbers to `2.0.0`
- [x] Add services/hours settings to plugin
- [x] Expand PHPUnit and PHPStan coverage
- [x] Reduce plugin build artifact bloat
- [x] Add WordPress.org readme and publishing docs
- [x] Add GitHub release workflow and v2.0.0 tag
- [ ] Publish to Packagist (submit repo at packagist.org — see [publishing.md](publishing.md))
- [ ] Submit WordPress plugin to wordpress.org (upload release zip — see [publishing.md](publishing.md))

### Phase 2 — Developer experience

- [x] Laravel service provider
- [x] Symfony bundle
- [x] REST API for SaaS / automation use cases
- [x] More CLI validation and reporting commands
- [x] Integration tests against a WordPress test harness

### Phase 3 — Product features

- [ ] Citation tracking dashboard (web UI)
- [ ] Bulk site analysis tools
- [ ] Competitor comparison
- [ ] Premium WordPress plugin tier

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
