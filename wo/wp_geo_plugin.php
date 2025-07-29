<?php
/**
 * Plugin Name: GEO Optimizer
 * Plugin URI: https://geooptimizer.dev
 * Description: The first comprehensive WordPress plugin for Generative Engine Optimization (GEO) - optimize your website for AI-powered search engines like ChatGPT, Claude, and Perplexity.
 * Version: 1.0.0
 * Author: GEO Optimizer Team
 * Author URI: https://geooptimizer.dev
 * License: MIT
 * Text Domain: geo-optimizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('GEO_OPTIMIZER_VERSION', '1.0.0');
define('GEO_OPTIMIZER_PLUGIN_FILE', __FILE__);
define('GEO_OPTIMIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEO_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEO_OPTIMIZER_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'GEOOptimizer\\') === 0) {
        $file = GEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-' . 
                strtolower(str_replace(['GEOOptimizer\\', '\\'], ['', '-'], $class)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

/**
 * Main GEO Optimizer Plugin Class
 */
class GEO_Optimizer_Plugin {
    
    private static $instance = null;
    private $admin;
    private $public;
    private $api;
    
    /**
     * Singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
        
        add_action('init', [$this, 'init']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'public_enqueue_scripts']);
        add_action('wp_head', [$this, 'add_structured_data']);
        add_action('wp_head', [$this, 'add_geo_meta_tags']);
        
        // AJAX handlers
        add_action('wp_ajax_geo_analyze_content', [$this, 'ajax_analyze_content']);
        add_action('wp_ajax_geo_generate_llms_txt', [$this, 'ajax_generate_llms_txt']);
        add_action('wp_ajax_geo_optimize_post', [$this, 'ajax_optimize_post']);
        
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_post_meta']);
        
        // Custom endpoint for llms.txt
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('template_redirect', [$this, 'handle_llms_txt_request']);
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once GEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-geo-optimizer-admin.php';
        require_once GEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-geo-optimizer-public.php';
        require_once GEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-geo-optimizer-api.php';
        require_once GEO_OPTIMIZER_PLUGIN_DIR . 'includes/class-geo-optimizer-core.php';
    }
    
    /**
     * Plugin initialization
     */
    public function init() {
        // Load text domain
        load_plugin_textdomain('geo-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize classes
        $this->admin = new GEO_Optimizer_Admin();
        $this->public = new GEO_Optimizer_Public();
        $this->api = new GEO_Optimizer_API();
        
        // Flush rewrite rules if needed
        if (get_option('geo_optimizer_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('geo_optimizer_flush_rewrite_rules');
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Schedule cron jobs
        $this->schedule_cron_jobs();
        
        // Flag to flush rewrite rules
        add_option('geo_optimizer_flush_rewrite_rules', true);
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('geo_optimizer_daily_analysis');
        wp_clear_scheduled_hook('geo_optimizer_weekly_report');
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$wpdb->prefix}geo_optimizer_analytics (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
            geo_score int(3) NOT NULL,
            citations_found int(5) DEFAULT 0,
            optimization_suggestions text,
            performance_data longtext,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY analysis_date (analysis_date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = [
            'geo_optimizer_business_type' => 'general',
            'geo_optimizer_auto_generate_llms' => true,
            'geo_optimizer_auto_optimize_content' => false,
            'geo_optimizer_citation_tracking' => true,
            'geo_optimizer_structured_data' => true,
            'geo_optimizer_meta_optimization' => true,
        ];
        
        foreach ($defaults as $option => $value) {
            if (false === get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule cron jobs
     */
    private function schedule_cron_jobs() {
        if (!wp_next_scheduled('geo_optimizer_daily_analysis')) {
            wp_schedule_event(time(), 'daily', 'geo_optimizer_daily_analysis');
        }
        
        if (!wp_next_scheduled('geo_optimizer_weekly_report')) {
            wp_schedule_event(time(), 'weekly', 'geo_optimizer_weekly_report');
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'geo-optimizer') !== false || $hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script(
                'geo-optimizer-admin',
                GEO_OPTIMIZER_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery', 'wp-api'],
                GEO_OPTIMIZER_VERSION,
                true
            );
            
            wp_enqueue_style(
                'geo-optimizer-admin',
                GEO_OPTIMIZER_PLUGIN_URL . 'assets/css/admin.css',
                [],
                GEO_OPTIMIZER_VERSION
            );
            
            wp_localize_script('geo-optimizer-admin', 'geoOptimizer', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('geo_optimizer_nonce'),
                'strings' => [
                    'analyzing' => __('Analyzing content...', 'geo-optimizer'),
                    'optimizing' => __('Optimizing...', 'geo-optimizer'),
                    'success' => __('Optimization complete!', 'geo-optimizer'),
                    'error' => __('An error occurred. Please try again.', 'geo-optimizer'),
                ]
            ]);
        }
    }
    
    /**
     * Enqueue public scripts and styles
     */
    public function public_enqueue_scripts() {
        if (get_option('geo_optimizer_enable_public_scripts', false)) {
            wp_enqueue_script(
                'geo-optimizer-public',
                GEO_OPTIMIZER_PLUGIN_URL . 'assets/js/public.js',
                ['jquery'],
                GEO_OPTIMIZER_VERSION,
                true
            );
        }
    }
    
    /**
     * Add structured data to head
     */
    public function add_structured_data() {
        if (!get_option('geo_optimizer_structured_data', true)) {
            return;
        }
        
        $core = new GEO_Optimizer_Core();
        $structured_data = $core->generate_structured_data();
        
        if (!empty($structured_data)) {
            echo '<script type="application/ld+json">' . 
                 wp_json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
                 '</script>' . "\n";
        }
    }
    
    /**
     * Add GEO-optimized meta tags
     */
    public function add_geo_meta_tags() {
        if (!get_option('geo_optimizer_meta_optimization', true)) {
            return;
        }
        
        $core = new GEO_Optimizer_Core();
        $meta_tags = $core->generate_geo_meta_tags();
        
        foreach ($meta_tags as $tag) {
            echo $tag . "\n";
        }
    }
    
    /**
     * Add meta boxes to post edit screens
     */
    public function add_meta_boxes() {
        $post_types = get_post_types(['public' => true]);
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'geo-optimizer-analysis',
                __('GEO Optimizer Analysis', 'geo-optimizer'),
                [$this, 'render_analysis_meta_box'],
                $post_type,
                'side',
                'high'
            );
            
            add_meta_box(
                'geo-optimizer-optimization',
                __('GEO Optimization', 'geo-optimizer'),
                [$this, 'render_optimization_meta_box'],
                $post_type,
                'normal',
                'default'
            );
        }
    }
    
    /**
     * Render analysis meta box
     */
    public function render_analysis_meta_box($post) {
        wp_nonce_field('geo_optimizer_meta_box', 'geo_optimizer_meta_box_nonce');
        
        $core = new GEO_Optimizer_Core();
        $analysis = $core->analyze_post($post->ID);
        
        echo '<div id="geo-optimizer-analysis-results">';
        if ($analysis) {
            echo '<div class="geo-score-display">';
            echo '<div class="geo-score-circle geo-score-' . $this->get_score_class($analysis['score']) . '">';
            echo '<span class="geo-score-number">' . $analysis['score'] . '</span>';
            echo '<span class="geo-score-label">GEO Score</span>';
            echo '</div>';
            echo '</div>';
            
            if (!empty($analysis['suggestions'])) {
                echo '<div class="geo-suggestions">';
                echo '<h4>' . __('Optimization Suggestions:', 'geo-optimizer') . '</h4>';
                echo '<ul>';
                foreach ($analysis['suggestions'] as $suggestion) {
                    echo '<li>' . esc_html($suggestion) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('Click "Analyze Content" to get your GEO score.', 'geo-optimizer') . '</p>';
        }
        echo '</div>';
        
        echo '<div class="geo-optimizer-actions">';
        echo '<button type="button" class="button button-secondary" id="geo-analyze-content">';
        echo __('Analyze Content', 'geo-optimizer');
        echo '</button>';
        echo '<button type="button" class="button button-primary" id="geo-optimize-content">';
        echo __('Auto-Optimize', 'geo-optimizer');
        echo '</button>';
        echo '</div>';
    }
    
    /**
     * Render optimization meta box
     */
    public function render_optimization_meta_box($post) {
        $geo_keywords = get_post_meta($post->ID, '_geo_keywords', true);
        $geo_description = get_post_meta($post->ID, '_geo_description', true);
        $geo_questions = get_post_meta($post->ID, '_geo_questions', true);
        
        echo '<table class="form-table">';
        
        echo '<tr>';
        echo '<th><label for="geo_keywords">' . __('GEO Keywords', 'geo-optimizer') . '</label></th>';
        echo '<td>';
        echo '<input type="text" id="geo_keywords" name="geo_keywords" value="' . esc_attr($geo_keywords) . '" class="large-text" />';
        echo '<p class="description">' . __('Comma-separated keywords for AI optimization', 'geo-optimizer') . '</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="geo_description">' . __('GEO Description', 'geo-optimizer') . '</label></th>';
        echo '<td>';
        echo '<textarea id="geo_description" name="geo_description" rows="3" class="large-text">' . esc_textarea($geo_description) . '</textarea>';
        echo '<p class="description">' . __('AI-optimized description for search engines', 'geo-optimizer') . '</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="geo_questions">' . __('Related Questions', 'geo-optimizer') . '</label></th>';
        echo '<td>';
        echo '<textarea id="geo_questions" name="geo_questions" rows="5" class="large-text">' . esc_textarea($geo_questions) . '</textarea>';
        echo '<p class="description">' . __('One question per line - helps AI understand your content context', 'geo-optimizer') . '</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * Save post meta data
     */
    public function save_post_meta($post_id) {
        if (!isset($_POST['geo_optimizer_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['geo_optimizer_meta_box_nonce'], 'geo_optimizer_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $fields = ['geo_keywords', 'geo_description', 'geo_questions'];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, sanitize_textarea_field($_POST[$field]));
            }
        }
    }
    
    /**
     * Add rewrite rules for llms.txt
     */
    public function add_rewrite_rules() {
        add_rewrite_rule('^llms\.txt$', 'index.php?geo_llms_txt=1', 'top');
        add_rewrite_tag('%geo_llms_txt%', '([0-9]+)');
    }
    
    /**
     * Handle llms.txt requests
     */
    public function handle_llms_txt_request() {
        if (get_query_var('geo_llms_txt')) {
            $core = new GEO_Optimizer_Core();
            $llms_content = $core->generate_llms_txt();
            
            header('Content-Type: text/plain; charset=utf-8');
            echo $llms_content;
            exit;
        }
    }
    
    /**
     * AJAX: Analyze content
     */
    public function ajax_analyze_content() {
        check_ajax_referer('geo_optimizer_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('Permission denied', 'geo-optimizer'));
        }
        
        $core = new GEO_Optimizer_Core();
        $analysis = $core->analyze_post($post_id);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * AJAX: Generate llms.txt
     */
    public function ajax_generate_llms_txt() {
        check_ajax_referer('geo_optimizer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'geo-optimizer'));
        }
        
        $core = new GEO_Optimizer_Core();
        $llms_content = $core->generate_llms_txt();
        
        wp_send_json_success(['content' => $llms_content]);
    }
    
    /**
     * AJAX: Optimize post
     */
    public function ajax_optimize_post() {
        check_ajax_referer('geo_optimizer_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die(__('Permission denied', 'geo-optimizer'));
        }
        
        $core = new GEO_Optimizer_Core();
        $result = $core->optimize_post($post_id);
        
        wp_send_json_success($result);
    }
    
    /**
     * Get CSS class for GEO score
     */
    private function get_score_class($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
}

// Initialize the plugin
GEO_Optimizer_Plugin::get_instance();