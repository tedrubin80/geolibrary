<?php
/**
 * Plugin Name: GeoOptimizer
 * Plugin URI: https://geooptimizer.dev
 * Description: Generative Engine Optimization for WordPress — llms.txt, structured data, and GEO content analysis.
 * Version: 2.0.0
 * Author: GeoOptimizer
 * Author URI: https://geooptimizer.dev
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: geooptimizer
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('GEOOPTIMIZER_VERSION', '2.0.0');
define('GEOOPTIMIZER_PLUGIN_FILE', __FILE__);
define('GEOOPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEOOPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));

$autoloadPaths = [
    GEOOPTIMIZER_PLUGIN_DIR . 'vendor/autoload.php',
    dirname(GEOOPTIMIZER_PLUGIN_DIR, 2) . '/vendor/autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

if (!class_exists(\GEOOptimizer\Integrations\WordPress\Plugin::class)) {
    add_action('admin_notices', static function (): void {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'GeoOptimizer requires the PHP library. Run composer install in the plugin directory or install via the release zip.',
            'geooptimizer'
        );
        echo '</p></div>';
    });
    return;
}

\GEOOptimizer\Integrations\WordPress\Plugin::register();
