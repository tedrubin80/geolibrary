<?php
/**
 * Plugin Name: GEO Optimizer
 * Plugin URI: https://geooptimizer.dev
 * Description: Optimize your WordPress site for AI-powered search engines with automatic llms.txt generation and structured data.
 * Version: 1.0.0
 * Author: Your Name
 * License: MIT
 * Text Domain: geo-optimizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GEO_OPTIMIZER_VERSION', '1.0.0');
define('GEO_OPTIMIZER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEO_OPTIMIZER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include Composer autoloader if available
if (file_exists(GEO_OPTIMIZER_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once GEO_OPTIMIZER_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * Main GEO Optimizer WordPress Plugin Class
 */
class GEOOptimizerWordPress
{
    private $geoOptimizer;
    private $options;

    public function __construct()
    {
        $this->options = get_option('geo_optimizer_options', []);
        
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('wp_head', [$this, 'addStructuredData']);
        add_action('save_post', [$this, 'regenerateLLMSTxt']);
        
        // Custom endpoint for llms.txt
        add_action('init', [$this, 'addLLMSTxtEndpoint']);
        add_action('template_redirect', [$this, 'handleLLMSTxtRequest']);
        
        // AJAX handlers
        add_action('wp_ajax_geo_generate_llms_txt', [$this, 'ajaxGenerateLLMSTxt']);
        add_action('wp_ajax_geo_analyze_content', [$this, 'ajaxAnalyzeContent']);
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        if (class_exists('GEOOptimizer\\GEOOptimizer')) {
            $this->geoOptimizer = new GEOOptimizer\GEOOptimizer();
        }
    }

    /**
     * Add admin menu
     */
    public function addAdminMenu()
    {
        add_options_page(
            'GEO Optimizer Settings',
            'GEO Optimizer',
            'manage_options',
            'geo-optimizer',
            [$this, 'adminPage']
        );
    }

    /**
     * Initialize admin settings
     */
    public function adminInit()
    {
        register_setting('geo_optimizer_options', 'geo_optimizer_options');
        
        add_settings_section(
            'geo_optimizer_main',
            'Business Information',
            null,
            'geo-optimizer'
        );
        
        $fields = [
            'business_name' => 'Business Name',
            'description' => 'Business Description',
            'industry' => 'Industry',
            'location' => 'Location',
            'phone' => 'Phone',
            'email' => 'Email'
        ];
        
        foreach ($fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                [$this, 'fieldCallback'],
                'geo-optimizer',
                'geo_optimizer_main',
                ['field' => $field, 'label' => $label]
            );
        }
    }

    /**
     * Settings field callback
     */
    public function fieldCallback($args)
    {
        $field = $args['field'];
        $value = $this->options[$field] ?? '';
        
        if ($field === 'description') {
            echo "<textarea name='geo_optimizer_options[{$field}]' rows='3' cols='50'>{$value}</textarea>";
        } else {
            echo "<input type='text' name='geo_optimizer_options[{$field}]' value='{$value}' size='50' />";
        }
    }

    /**
     * Admin page HTML
     */
    public function adminPage()
    {
        ?>
        <div class="wrap">
            <h1>GEO Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('geo_optimizer_options');
                do_settings_sections('geo-optimizer');
                submit_button();
                ?>
            </form>
            
            <h2>Actions</h2>
            <p>
                <button class="button button-primary" onclick="generateLLMSTxt()">
                    Generate llms.txt
                </button>
                <button class="button" onclick="analyzeContent()">
                    Analyze Site Content
                </button>
            </p>
            
            <div id="geo-results"></div>
        </div>
        
        <script>
        function generateLLMSTxt() {
            jQuery.post(ajaxurl, {
                action: 'geo_generate_llms_txt'
            }, function(response) {
                document.getElementById('geo-results').innerHTML = 
                    '<h3>llms.txt Generated</h3><pre>' + response.data + '</pre>';
            });
        }
        
        function analyzeContent() {
            jQuery.post(ajaxurl, {
                action: 'geo_analyze_content'
            }, function(response) {
                document.getElementById('geo-results').innerHTML = 
                    '<h3>Content Analysis</h3><pre>' + JSON.stringify(response.data, null, 2) + '</pre>';
            });
        }
        </script>
        <?php
    }

    /**
     * Add structured data to head
     */
    public function addStructuredData()
    {
        if (!$this->geoOptimizer || empty($this->options['business_name'])) {
            return;
        }

        $structuredData = $this->geoOptimizer->generateStructuredData($this->options);
        echo $structuredData;
    }

    /**
     * Add llms.txt endpoint
     */
    public function addLLMSTxtEndpoint()
    {
        add_rewrite_rule('^llms\.txt$', 'index.php?llms_txt=1', 'top');
    }

    /**
     * Handle llms.txt requests
     */
    public function handleLLMSTxtRequest()
    {
        if (get_query_var('llms_txt')) {
            header('Content-Type: text/plain');
            echo $this->generateLLMSTxtContent();
            exit;
        }
    }

    /**
     * Generate llms.txt content
     */
    private function generateLLMSTxtContent()
    {
        if (!$this->geoOptimizer || empty($this->options['business_name'])) {
            return "# llms.txt\n# Please configure GEO Optimizer in WordPress admin";
        }

        return $this->geoOptimizer->generateLLMSTxt($this->options);
    }

    /**
     * AJAX handlers
     */
    public function ajaxGenerateLLMSTxt()
    {
        wp_send_json_success($this->generateLLMSTxtContent());
    }

    public function ajaxAnalyzeContent()
    {
        if (!$this->geoOptimizer) {
            wp_send_json_error('GEO Optimizer not available');
        }

        $content = get_bloginfo('description');
        $analyzer = new GEOOptimizer\Analysis\ContentAnalyzer();
        $analysis = $analyzer->analyzeForGEO($content, $this->options);
        
        wp_send_json_success($analysis);
    }

    /**
     * Regenerate llms.txt when content changes
     */
    public function regenerateLLMSTxt($postId)
    {
        if (get_post_type($postId) === 'page' && !wp_is_post_revision($postId)) {
            update_option('geo_optimizer_llms_txt', $this->generateLLMSTxtContent());
        }
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        flush_rewrite_rules();
        
        $defaults = [
            'business_name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'industry' => '',
            'location' => '',
            'phone' => '',
            'email' => get_option('admin_email')
        ];
        
        add_option('geo_optimizer_options', $defaults);
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }
}

// Initialize plugin
new GEOOptimizerWordPress();