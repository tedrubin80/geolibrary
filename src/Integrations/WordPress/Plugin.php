<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\WordPress;

/**
 * WordPress integration for GeoOptimizer (Generative Engine Optimization).
 */
final class Plugin
{
    private const OPTION_KEY = 'geo_optimizer_options';
    private const NONCE_ACTION = 'geo_optimizer_admin';

    private ?\GEOOptimizer\GEOOptimizer $geoOptimizer = null;

    /** @var array<string, mixed> */
    private array $options = [];

    public static function register(): void
    {
        new self();
    }

    private function __construct()
    {
        $this->options = get_option(self::OPTION_KEY, []);

        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'addAdminMenu']);
        add_action('admin_init', [$this, 'adminInit']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('wp_head', [$this, 'addStructuredData']);
        add_action('save_post', [$this, 'regenerateLLMSTxt']);
        add_action('init', [$this, 'addLLMSTxtEndpoint']);
        add_action('template_redirect', [$this, 'handleLLMSTxtRequest']);
        add_action('wp_ajax_geo_generate_llms_txt', [$this, 'ajaxGenerateLLMSTxt']);
        add_action('wp_ajax_geo_analyze_content', [$this, 'ajaxAnalyzeContent']);

        register_activation_hook($this->pluginFile(), [$this, 'activate']);
        register_deactivation_hook($this->pluginFile(), [$this, 'deactivate']);
    }

    private function pluginFile(): string
    {
        return defined('GEOOPTIMIZER_PLUGIN_FILE')
            ? GEOOPTIMIZER_PLUGIN_FILE
            : __FILE__;
    }

    public function init(): void
    {
        if (class_exists(\GEOOptimizer\GEOOptimizer::class)) {
            $this->geoOptimizer = new \GEOOptimizer\GEOOptimizer();
        }
    }

    public function addAdminMenu(): void
    {
        add_options_page(
            __('GEO Optimizer Settings', 'geooptimizer'),
            __('GEO Optimizer', 'geooptimizer'),
            'manage_options',
            'geo-optimizer',
            [$this, 'adminPage']
        );
    }

    public function adminInit(): void
    {
        register_setting(
            self::OPTION_KEY,
            self::OPTION_KEY,
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitizeOptions'],
                'default' => $this->defaultOptions(),
            ]
        );

        add_settings_section(
            'geo_optimizer_main',
            __('Business Information', 'geooptimizer'),
            null,
            'geo-optimizer'
        );

        $fields = [
            'business_name' => __('Business Name', 'geooptimizer'),
            'description' => __('Business Description', 'geooptimizer'),
            'industry' => __('Industry', 'geooptimizer'),
            'location' => __('Location', 'geooptimizer'),
            'phone' => __('Phone', 'geooptimizer'),
            'email' => __('Email', 'geooptimizer'),
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
     * @param array<string, mixed> $input
     * @return array<string, string>
     */
    public function sanitizeOptions(array $input): array
    {
        return [
            'business_name' => sanitize_text_field($input['business_name'] ?? ''),
            'description' => sanitize_textarea_field($input['description'] ?? ''),
            'industry' => sanitize_key($input['industry'] ?? ''),
            'location' => sanitize_text_field($input['location'] ?? ''),
            'phone' => sanitize_text_field($input['phone'] ?? ''),
            'email' => sanitize_email($input['email'] ?? ''),
        ];
    }

    /**
     * @param array<string, string> $args
     */
    public function fieldCallback(array $args): void
    {
        $field = $args['field'];
        $value = $this->options[$field] ?? '';

        if ($field === 'description') {
            printf(
                '<textarea name="%1$s[%2$s]" rows="3" cols="50" class="large-text">%3$s</textarea>',
                esc_attr(self::OPTION_KEY),
                esc_attr($field),
                esc_textarea((string) $value)
            );
            return;
        }

        printf(
            '<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($field),
            esc_attr((string) $value)
        );
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if ($hook !== 'settings_page_geo-optimizer') {
            return;
        }

        wp_enqueue_script(
            'geooptimizer-admin',
            plugins_url('admin/js/admin.js', $this->pluginFile()),
            ['jquery'],
            defined('GEOOPTIMIZER_VERSION') ? GEOOPTIMIZER_VERSION : '1.0.0',
            true
        );

        wp_enqueue_style(
            'geooptimizer-admin',
            plugins_url('admin/css/admin.css', $this->pluginFile()),
            [],
            defined('GEOOPTIMIZER_VERSION') ? GEOOPTIMIZER_VERSION : '1.0.0'
        );

        wp_localize_script('geooptimizer-admin', 'geoOptimizerAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
        ]);
    }

    public function adminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_KEY);
                do_settings_sections('geo-optimizer');
                submit_button();
                ?>
            </form>

            <h2><?php esc_html_e('Actions', 'geooptimizer'); ?></h2>
            <p>
                <button type="button" class="button button-primary" id="geo-generate-llms-txt">
                    <?php esc_html_e('Generate llms.txt', 'geooptimizer'); ?>
                </button>
                <button type="button" class="button" id="geo-analyze-content">
                    <?php esc_html_e('Analyze Site Content', 'geooptimizer'); ?>
                </button>
            </p>
            <pre id="geo-results" class="geooptimizer-results" aria-live="polite"></pre>
        </div>
        <?php
    }

    public function addStructuredData(): void
    {
        if (!$this->geoOptimizer || empty($this->options['business_name'])) {
            return;
        }

        $structuredData = $this->geoOptimizer->generateStructuredData($this->options);
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD script block
        echo $structuredData;
    }

    public function addLLMSTxtEndpoint(): void
    {
        add_rewrite_rule('^llms\.txt$', 'index.php?llms_txt=1', 'top');
        add_rewrite_tag('%llms_txt%', '1');
    }

    public function handleLLMSTxtRequest(): void
    {
        if (!get_query_var('llms_txt')) {
            return;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        // Plain text output; business fields are sanitized on save.
        echo $this->generateLLMSTxtContent();
        exit;
    }

    private function generateLLMSTxtContent(): string
    {
        if (!$this->geoOptimizer || empty($this->options['business_name'])) {
            return "# llms.txt\n# Please configure GEO Optimizer in WordPress admin";
        }

        return $this->geoOptimizer->generateLLMSTxt($this->options);
    }

    public function ajaxGenerateLLMSTxt(): void
    {
        $this->assertAdminAjax();

        wp_send_json_success([
            'content' => $this->generateLLMSTxtContent(),
        ]);
    }

    public function ajaxAnalyzeContent(): void
    {
        $this->assertAdminAjax();

        if (!$this->geoOptimizer) {
            wp_send_json_error(['message' => 'GEO Optimizer not available'], 500);
        }

        $content = (string) get_bloginfo('description');
        $analyzer = new \GEOOptimizer\Analysis\ContentAnalyzer();
        $analysis = $analyzer->analyzeForGEO($content, $this->options);

        wp_send_json_success($analysis);
    }

    public function regenerateLLMSTxt(int $postId): void
    {
        if (get_post_type($postId) === 'page' && !wp_is_post_revision($postId)) {
            update_option('geo_optimizer_llms_txt', $this->generateLLMSTxtContent());
        }
    }

    public function activate(): void
    {
        flush_rewrite_rules();
        add_option(self::OPTION_KEY, $this->defaultOptions());
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * @return array<string, string>
     */
    private function defaultOptions(): array
    {
        return [
            'business_name' => (string) get_bloginfo('name'),
            'description' => (string) get_bloginfo('description'),
            'industry' => '',
            'location' => '',
            'phone' => '',
            'email' => (string) get_option('admin_email'),
        ];
    }

    private function assertAdminAjax(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
    }
}
