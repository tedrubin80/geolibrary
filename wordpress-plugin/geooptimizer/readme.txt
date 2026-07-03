=== GeoOptimizer ===
Contributors: geooptimizer
Tags: seo, schema, llms, ai, structured-data, local-business
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2.2.2
License: MIT
License URI: https://opensource.org/licenses/MIT

Generative Engine Optimization for WordPress — llms.txt, Schema.org JSON-LD, and GEO content analysis.

== Description ==

GeoOptimizer helps WordPress sites publish AI-friendly metadata so generative search engines can discover and cite your business accurately.

**Features**

* Generate and serve `/llms.txt` from your site
* Output Schema.org JSON-LD in `wp_head`
* Configure business profile, services, and hours
* Industry templates for restaurants, legal, medical, and more
* Admin tools to generate llms.txt and analyze site content
* Premium tier: citation dashboard, bulk analysis, and competitor comparison (license key)
* Automatic llms.txt cache refresh when content changes

**Requirements**

Install the plugin from the release zip (includes bundled PHP dependencies) or run `composer install` inside the plugin directory during development.

== Installation ==

1. Download the plugin zip from the [GitHub releases page](https://github.com/tedrubin80/geolibrary/releases/latest/download/geooptimizer-wordpress-plugin.zip).
2. In wp-admin, go to **Plugins → Add New → Upload Plugin**, choose the zip, and install.
3. Activate **GeoOptimizer**.
4. Open **Settings → GEO Optimizer** and enter your business details.
5. Visit `https://yoursite.example/llms.txt` to confirm output.

This plugin is not yet available from the WordPress.org plugin directory.

== Frequently Asked Questions ==

= What is llms.txt? =

llms.txt is an emerging convention for publishing a concise, machine-readable summary of your site for AI systems.

= Does this plugin call external APIs? =

Core llms.txt and structured data generation runs locally. Optional citation tracking features in the PHP library can use platform API keys if configured in custom integrations.

= Why is /llms.txt returning 404? =

Save the plugin settings once, then visit **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

== Screenshots ==

1. GeoOptimizer settings page with business profile and feature toggles.

== Changelog ==

= 2.2.2 =
* Update "Tested up to" for WordPress 7.0

= 2.2.1 =
* Fix Plugin URI and Author URI headers for WordPress.org submission

= 2.2.0 =
* Premium tier with license key, admin dashboard, bulk analysis, and competitor comparison
* Citation tracking dashboard UI at /dashboard/
* REST endpoints for dashboard, bulk analyze, compare, and track
* Bulk and competitor analyzers in the core library

= 2.1.0 =
* Laravel service provider and Symfony bundle integrations in the core library
* REST API and expanded CLI tooling for automation workflows

= 2.0.0 =
* WordPress plugin aligned with GeoOptimizer library 2.0.0
* Services and business hours settings
* Industry dropdown and feature toggles for JSON-LD and llms.txt
* Cached llms.txt endpoint and improved admin actions
* Structured data integration fixes

= 1.0.0 =
* Initial public release

== Upgrade Notice ==

= 2.0.0 =
Major update with new settings fields and llms.txt caching. Re-save plugin settings and flush permalinks after upgrading.
