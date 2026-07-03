<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\WordPress;

/**
 * WordPress integration for GeoOptimizer (Generative Engine Optimization).
 */
final class Plugin
{
    private const OPTION_KEY = 'geo_optimizer_options';
    private const LLMS_TXT_CACHE_KEY = 'geo_optimizer_llms_txt';
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
        $this->options = $this->loadOptions();

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

        (new \GEOOptimizer\Integrations\WordPress\Premium\PremiumModule())->register();

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
            static function (): void {
            },
            'geo-optimizer'
        );

        add_settings_section(
            'geo_optimizer_features',
            __('Features', 'geooptimizer'),
            [$this, 'featuresSectionCallback'],
            'geo-optimizer'
        );

        $fields = [
            'business_name' => __('Business Name', 'geooptimizer'),
            'description' => __('Business Description', 'geooptimizer'),
            'industry' => __('Industry', 'geooptimizer'),
            'location' => __('Location', 'geooptimizer'),
            'phone' => __('Phone', 'geooptimizer'),
            'email' => __('Email', 'geooptimizer'),
            'services' => __('Services', 'geooptimizer'),
            'hours' => __('Business Hours', 'geooptimizer'),
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

        add_settings_field(
            'enable_structured_data',
            __('Structured Data', 'geooptimizer'),
            [$this, 'checkboxFieldCallback'],
            'geo-optimizer',
            'geo_optimizer_features',
            [
                'field' => 'enable_structured_data',
                'label' => __('Output Schema.org JSON-LD in wp_head', 'geooptimizer'),
            ]
        );

        add_settings_field(
            'enable_llms_txt',
            __('llms.txt Endpoint', 'geooptimizer'),
            [$this, 'checkboxFieldCallback'],
            'geo-optimizer',
            'geo_optimizer_features',
            [
                'field' => 'enable_llms_txt',
                'label' => __('Serve llms.txt at /llms.txt', 'geooptimizer'),
            ]
        );
    }

    public function featuresSectionCallback(): void
    {
        echo '<p>' . esc_html__(
            'Control which GEO outputs are published on the public site.',
            'geooptimizer'
        ) . '</p>';
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function sanitizeOptions(array $input): array
    {
        $industries = $this->geoOptimizer
            ? $this->geoOptimizer->getAvailableIndustries()
            : [];

        $industry = sanitize_key($input['industry'] ?? '');
        if ($industry !== '' && $industries !== [] && !in_array($industry, $industries, true)) {
            $industry = '';
        }

        return [
            'business_name' => sanitize_text_field($input['business_name'] ?? ''),
            'description' => sanitize_textarea_field($input['description'] ?? ''),
            'industry' => $industry,
            'location' => sanitize_text_field($input['location'] ?? ''),
            'phone' => sanitize_text_field($input['phone'] ?? ''),
            'email' => sanitize_email($input['email'] ?? ''),
            'services' => $this->parseServicesList((string) ($input['services'] ?? '')),
            'hours' => $this->parseHoursList((string) ($input['hours'] ?? '')),
            'enable_structured_data' => !empty($input['enable_structured_data']),
            'enable_llms_txt' => !empty($input['enable_llms_txt']),
            'license_key' => sanitize_text_field($input['license_key'] ?? ''),
        ];
    }

    /**
     * @param array<string, string> $args
     */
    public function fieldCallback(array $args): void
    {
        $this->options = $this->loadOptions();
        $field = $args['field'];
        $value = $this->options[$field] ?? '';

        if ($field === 'description' || $field === 'services' || $field === 'hours') {
            if ($field === 'services' && is_array($value)) {
                $value = $this->formatServicesList($value);
            }

            if ($field === 'hours' && is_array($value)) {
                $value = $this->formatHoursList($value);
            }

            printf(
                '<textarea name="%1$s[%2$s]" rows="%4$d" cols="50" class="large-text">%3$s</textarea>',
                esc_attr(self::OPTION_KEY),
                esc_attr($field),
                esc_textarea((string) $value),
                $field === 'description' ? 3 : 5
            );

            if ($field === 'services') {
                echo '<p class="description">' . esc_html__(
                    'Enter one service per line (for example: Espresso, Pour Over, Retail Beans).',
                    'geooptimizer'
                ) . '</p>';
            }

            if ($field === 'hours') {
                echo '<p class="description">' . esc_html__(
                    'Enter one day per line using the format Monday: 9:00 AM - 5:00 PM.',
                    'geooptimizer'
                ) . '</p>';
            }

            return;
        }

        if ($field === 'industry') {
            $industries = $this->geoOptimizer
                ? $this->geoOptimizer->getAvailableIndustries()
                : [];

            echo '<select name="' . esc_attr(self::OPTION_KEY) . '[industry]" class="regular-text">';
            echo '<option value="">' . esc_html__('Select an industry', 'geooptimizer') . '</option>';

            foreach ($industries as $industry) {
                printf(
                    '<option value="%1$s" %2$s>%3$s</option>',
                    esc_attr($industry),
                    selected((string) $value, $industry, false),
                    esc_html(ucwords(str_replace('_', ' ', $industry)))
                );
            }

            echo '</select>';
            return;
        }

        printf(
            '<input type="text" name="%1$s[%2$s]" value="%3$s" class="regular-text" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($field),
            esc_attr((string) $value)
        );
    }

    /**
     * @param array<string, string> $args
     */
    public function checkboxFieldCallback(array $args): void
    {
        $this->options = $this->loadOptions();
        $field = $args['field'];
        $checked = !empty($this->options[$field]);

        printf(
            '<label><input type="checkbox" name="%1$s[%2$s]" value="1" %3$s /> %4$s</label>',
            esc_attr(self::OPTION_KEY),
            esc_attr($field),
            checked($checked, true, false),
            esc_html($args['label'])
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
            defined('GEOOPTIMIZER_VERSION') ? GEOOPTIMIZER_VERSION : \GEOOptimizer\Version::VERSION,
            true
        );

        wp_enqueue_style(
            'geooptimizer-admin',
            plugins_url('admin/css/admin.css', $this->pluginFile()),
            [],
            defined('GEOOPTIMIZER_VERSION') ? GEOOPTIMIZER_VERSION : \GEOOptimizer\Version::VERSION
        );

        wp_localize_script('geooptimizer-admin', 'geoOptimizerAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'llmsTxtUrl' => home_url('/llms.txt'),
            'strings' => [
                'generating' => __('Generating llms.txt…', 'geooptimizer'),
                'analyzing' => __('Analyzing site content…', 'geooptimizer'),
                'copied' => __('Copied to clipboard.', 'geooptimizer'),
                'copyFailed' => __('Copy failed. Select the text manually.', 'geooptimizer'),
            ],
        ]);
    }

    public function adminPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->options = $this->loadOptions();
        $llmsEnabled = !empty($this->options['enable_llms_txt']);
        ?>
        <div class="wrap geooptimizer-admin">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if ($llmsEnabled) : ?>
                <p class="geooptimizer-meta">
                    <?php
                    printf(
                        /* translators: %s: llms.txt URL */
                        esc_html__('Public llms.txt URL: %s', 'geooptimizer'),
                        '<a href="' . esc_url(home_url('/llms.txt')) . '" target="_blank" rel="noopener noreferrer">' . esc_html(home_url('/llms.txt')) . '</a>'
                    );
                    ?>
                </p>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_KEY);
                do_settings_sections('geo-optimizer');
                submit_button();
                ?>
            </form>

            <div class="geooptimizer-actions">
                <h2><?php esc_html_e('Actions', 'geooptimizer'); ?></h2>
                <p class="geooptimizer-actions__buttons">
                    <button type="button" class="button button-primary" id="geo-generate-llms-txt">
                        <?php esc_html_e('Generate llms.txt', 'geooptimizer'); ?>
                    </button>
                    <button type="button" class="button" id="geo-analyze-content">
                        <?php esc_html_e('Analyze Site Content', 'geooptimizer'); ?>
                    </button>
                    <button type="button" class="button" id="geo-copy-results" hidden>
                        <?php esc_html_e('Copy Results', 'geooptimizer'); ?>
                    </button>
                </p>
                <pre id="geo-results" class="geooptimizer-results" aria-live="polite"></pre>
            </div>
        </div>
        <?php
    }

    public function addStructuredData(): void
    {
        $this->options = $this->loadOptions();

        if (
            !$this->geoOptimizer
            || empty($this->options['business_name'])
            || empty($this->options['enable_structured_data'])
        ) {
            return;
        }

        $businessData = $this->buildBusinessData();
        $structuredData = $this->geoOptimizer->generateStructuredData($businessData);
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

        $this->options = $this->loadOptions();

        if (empty($this->options['enable_llms_txt'])) {
            status_header(404);
            exit;
        }

        header('Content-Type: text/plain; charset=UTF-8');
        // Plain text output; business fields are sanitized on save.
        echo $this->generateLLMSTxtContent();
        exit;
    }

    private function generateLLMSTxtContent(bool $refreshCache = false): string
    {
        if (!$this->geoOptimizer || empty($this->options['business_name'])) {
            return "# llms.txt\n# Please configure GEO Optimizer in WordPress admin";
        }

        if (!$refreshCache) {
            $cached = get_option(self::LLMS_TXT_CACHE_KEY, '');
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        $content = $this->geoOptimizer->generateLLMSTxt($this->buildBusinessData());
        update_option(self::LLMS_TXT_CACHE_KEY, $content, false);

        return $content;
    }

    public function ajaxGenerateLLMSTxt(): void
    {
        $this->assertAdminAjax();
        $this->options = $this->loadOptions();

        wp_send_json_success([
            'content' => $this->generateLLMSTxtContent(true),
        ]);
    }

    public function ajaxAnalyzeContent(): void
    {
        $this->assertAdminAjax();
        $this->options = $this->loadOptions();

        if (!$this->geoOptimizer) {
            wp_send_json_error(['message' => 'GEO Optimizer not available'], 500);
        }

        $content = $this->collectSiteContentForAnalysis();
        $analysis = $this->geoOptimizer->analyzeContent($content, $this->buildBusinessData());

        wp_send_json_success($analysis);
    }

    public function regenerateLLMSTxt(int $postId): void
    {
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        $postType = get_post_type($postId);
        if (!$postType || !is_post_type_viewable($postType)) {
            return;
        }

        $this->options = $this->loadOptions();
        update_option(self::LLMS_TXT_CACHE_KEY, $this->generateLLMSTxtContent(true), false);
    }

    public function activate(): void
    {
        if (get_option(self::OPTION_KEY) === false) {
            add_option(self::OPTION_KEY, $this->defaultOptions());
        }

        $this->options = $this->loadOptions();

        $this->addLLMSTxtEndpoint();
        flush_rewrite_rules();

        if (!empty($this->options['business_name']) && class_exists(\GEOOptimizer\GEOOptimizer::class)) {
            $this->geoOptimizer = new \GEOOptimizer\GEOOptimizer();
            update_option(self::LLMS_TXT_CACHE_KEY, $this->generateLLMSTxtContent(true), false);
        }
    }

    public function deactivate(): void
    {
        flush_rewrite_rules();
    }

    /**
     * @return array<string, mixed>
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
            'services' => [],
            'hours' => [],
            'enable_structured_data' => true,
            'enable_llms_txt' => true,
            'license_key' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBusinessData(): array
    {
        $data = array_merge($this->options, [
            'website' => home_url('/'),
        ]);

        if (!empty($data['location']) && empty($data['address'])) {
            $data['address'] = $data['location'];
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function parseServicesList(string $input): array
    {
        $services = [];

        foreach (preg_split('/\r\n|\r|\n/', $input) ?: [] as $line) {
            $line = sanitize_text_field(trim($line));
            if ($line !== '') {
                $services[] = $line;
            }
        }

        return $services;
    }

    /**
     * @param array<int, string>|array<string, string> $services
     */
    private function formatServicesList(array $services): string
    {
        return implode("\n", array_values($services));
    }

    /**
     * @return array<string, string>
     */
    private function parseHoursList(string $input): array
    {
        $hours = [];

        foreach (preg_split('/\r\n|\r|\n/', $input) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, ':')) {
                continue;
            }

            [$day, $time] = array_map('trim', explode(':', $line, 2));
            if ($day === '' || $time === '') {
                continue;
            }

            $hours[sanitize_text_field($day)] = sanitize_text_field($time);
        }

        return $hours;
    }

    /**
     * @param array<string, string> $hours
     */
    private function formatHoursList(array $hours): string
    {
        $lines = [];

        foreach ($hours as $day => $time) {
            $lines[] = $day . ': ' . $time;
        }

        return implode("\n", $lines);
    }

    private function collectSiteContentForAnalysis(): string
    {
        $parts = array_filter([
            (string) get_bloginfo('name'),
            (string) get_bloginfo('description'),
            (string) ($this->options['description'] ?? ''),
        ]);

        $frontPageId = (int) get_option('page_on_front');
        if ($frontPageId > 0) {
            $post = get_post($frontPageId);
            if ($post instanceof \WP_Post) {
                $parts[] = wp_strip_all_tags((string) $post->post_content);
            }
        }

        $content = trim(implode("\n\n", $parts));

        return $content !== '' ? $content : (string) get_bloginfo('description');
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOptions(): array
    {
        $options = get_option(self::OPTION_KEY, []);

        return is_array($options) ? array_merge($this->defaultOptions(), $options) : $this->defaultOptions();
    }

    private function assertAdminAjax(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }
    }
}
