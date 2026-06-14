<?php
/**
 * Plugin Name: GeoOptimizer
 * Plugin URI: https://geooptimizer.dev
 * Description: Geospatial optimization for WordPress - intelligent geolocation-based content optimization and delivery.
 * Version: 1.0.0
 * Author: GeoOptimizer
 * Author URI: https://geooptimizer.dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: geooptimizer
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GEOOPTIMIZER_VERSION', '1.0.0');
define('GEOOPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEOOPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEOOPTIMIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main GeoOptimizer Plugin Class
 */
class GeoOptimizer_Plugin {

    /**
     * Single instance of the plugin
     */
    private static $instance = null;

    /**
     * Plugin options
     */
    private $options;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->options = get_option('geooptimizer_options', []);

        // Load dependencies
        $this->load_dependencies();

        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once GEOOPTIMIZER_PLUGIN_DIR . 'includes/class-geooptimizer-core.php';

        if (is_admin()) {
            require_once GEOOPTIMIZER_PLUGIN_DIR . 'admin/class-geooptimizer-admin.php';
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/deactivation
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Init
        add_action('init', [$this, 'init']);

        // Admin
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('admin_init', [$this, 'register_settings']);
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = [
            'enabled' => true,
            'cache_enabled' => true,
            'cache_ttl' => 3600,
        ];

        add_option('geooptimizer_options', $default_options);

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Initialize plugin
     */
    public function init() {
        load_plugin_textdomain('geooptimizer', false, dirname(GEOOPTIMIZER_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('GeoOptimizer', 'geooptimizer'),
            __('GeoOptimizer', 'geooptimizer'),
            'manage_options',
            'geooptimizer',
            [$this, 'render_admin_page'],
            'dashicons-location-alt',
            80
        );

        add_submenu_page(
            'geooptimizer',
            __('Settings', 'geooptimizer'),
            __('Settings', 'geooptimizer'),
            'manage_options',
            'geooptimizer-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('geooptimizer_options_group', 'geooptimizer_options');
    }

    /**
     * Render main admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <div class="geooptimizer-dashboard">
                <div class="geooptimizer-card">
                    <h2><?php _e('Welcome to GeoOptimizer', 'geooptimizer'); ?></h2>
                    <p><?php _e('Geospatial optimization for your WordPress site.', 'geooptimizer'); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('geooptimizer_options_group');
                do_settings_sections('geooptimizer-settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable GeoOptimizer', 'geooptimizer'); ?></th>
                        <td>
                            <input type="checkbox" name="geooptimizer_options[enabled]" value="1"
                                <?php checked(1, $this->options['enabled'] ?? 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Enable Caching', 'geooptimizer'); ?></th>
                        <td>
                            <input type="checkbox" name="geooptimizer_options[cache_enabled]" value="1"
                                <?php checked(1, $this->options['cache_enabled'] ?? 1); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Cache TTL (seconds)', 'geooptimizer'); ?></th>
                        <td>
                            <input type="number" name="geooptimizer_options[cache_ttl]"
                                value="<?php echo esc_attr($this->options['cache_ttl'] ?? 3600); ?>"
                                min="60" max="86400" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Get option value
     */
    public function get_option($key, $default = null) {
        return $this->options[$key] ?? $default;
    }
}

// Initialize plugin
function geooptimizer() {
    return GeoOptimizer_Plugin::get_instance();
}

// Start the plugin
geooptimizer();
