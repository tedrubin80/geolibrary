<?php
/**
 * GeoOptimizer Admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class GeoOptimizer_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /**
     * Enqueue admin CSS/JS
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'geooptimizer') === false) {
            return;
        }

        wp_enqueue_style(
            'geooptimizer-admin',
            GEOOPTIMIZER_PLUGIN_URL . 'admin/css/admin.css',
            [],
            GEOOPTIMIZER_VERSION
        );
    }
}

// Initialize admin
new GeoOptimizer_Admin();
