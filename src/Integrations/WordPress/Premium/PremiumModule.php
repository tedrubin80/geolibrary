<?php

declare(strict_types=1);

namespace GEOOptimizer\Integrations\WordPress\Premium;

use GEOOptimizer\GEOOptimizer;
use GEOOptimizer\Integrations\WordPress\Plugin;

/**
 * Premium WordPress features: dashboard, bulk analysis, competitor comparison.
 */
final class PremiumModule
{
    private const OPTION_KEY = 'geo_optimizer_options';
    private const NONCE_ACTION = 'geo_optimizer_admin';

    private ?GEOOptimizer $geoOptimizer = null;

    public function register(): void
    {
        add_action('admin_menu', [$this, 'registerMenus'], 20);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_ajax_geo_premium_dashboard', [$this, 'ajaxDashboard']);
        add_action('wp_ajax_geo_bulk_analyze', [$this, 'ajaxBulkAnalyze']);
        add_action('wp_ajax_geo_compare_competitors', [$this, 'ajaxCompareCompetitors']);
        add_action('admin_init', [$this, 'registerLicenseField']);
    }

    public function registerLicenseField(): void
    {
        add_settings_field(
            'license_key',
            __('Premium License Key', 'geooptimizer'),
            [$this, 'renderLicenseField'],
            'geo-optimizer',
            'geo_optimizer_features'
        );
    }

    public function renderLicenseField(): void
    {
        $options = $this->loadOptions();
        $value = (string) ($options['license_key'] ?? '');

        printf(
            '<input type="text" name="%1$s[license_key]" value="%2$s" class="regular-text" autocomplete="off" />',
            esc_attr(self::OPTION_KEY),
            esc_attr($value)
        );

        echo '<p class="description">';
        if ($this->isPremiumActive($options)) {
            esc_html_e('Premium features are active.', 'geooptimizer');
        } else {
            printf(
                /* translators: %s: demo license key */
                esc_html__('Enter a valid premium key to unlock the dashboard and advanced analysis tools. Demo key: %s', 'geooptimizer'),
                esc_html(PremiumLicense::DEMO_KEY)
            );
        }
        echo '</p>';
    }

    public function registerMenus(): void
    {
        if (!$this->isPremiumActive()) {
            return;
        }

        add_submenu_page(
            'options-general.php',
            __('GEO Dashboard', 'geooptimizer'),
            __('GEO Dashboard', 'geooptimizer'),
            'manage_options',
            'geo-optimizer-dashboard',
            [$this, 'renderDashboardPage']
        );
    }

    public function enqueueAssets(string $hook): void
    {
        if (!in_array($hook, ['settings_page_geo-optimizer', 'settings_page_geo-optimizer-dashboard'], true)) {
            return;
        }

        wp_enqueue_style(
            'geooptimizer-premium',
            plugins_url('admin/css/premium.css', GEOOPTIMIZER_PLUGIN_FILE),
            [],
            GEOOPTIMIZER_VERSION
        );

        if ($hook !== 'settings_page_geo-optimizer-dashboard') {
            return;
        }

        wp_enqueue_script(
            'geooptimizer-premium',
            plugins_url('admin/js/premium.js', GEOOPTIMIZER_PLUGIN_FILE),
            ['jquery'],
            GEOOPTIMIZER_VERSION,
            true
        );

        wp_localize_script('geooptimizer-premium', 'geoOptimizerPremium', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'identifier' => (string) ($this->loadOptions()['business_name'] ?? get_bloginfo('name')),
        ]);
    }

    public function renderDashboardPage(): void
    {
        if (!current_user_can('manage_options') || !$this->isPremiumActive()) {
            wp_die(esc_html__('Premium license required.', 'geooptimizer'));
        }
        ?>
        <div class="wrap geooptimizer-premium">
            <h1><?php esc_html_e('GEO Premium Dashboard', 'geooptimizer'); ?></h1>
            <p><?php esc_html_e('Track citations, run bulk analysis, and compare competitors from WordPress admin.', 'geooptimizer'); ?></p>

            <div class="geooptimizer-premium__panel">
                <button type="button" class="button button-primary" id="geo-load-dashboard">
                    <?php esc_html_e('Load Citation Dashboard', 'geooptimizer'); ?>
                </button>
                <pre id="geo-dashboard-output" class="geooptimizer-results" aria-live="polite"></pre>
            </div>

            <div class="geooptimizer-premium__panel">
                <h2><?php esc_html_e('Bulk Analysis', 'geooptimizer'); ?></h2>
                <textarea id="geo-bulk-items" rows="8" class="large-text" placeholder='{"id":"home","content":"..."}'></textarea>
                <button type="button" class="button" id="geo-run-bulk"><?php esc_html_e('Run Bulk Analyze', 'geooptimizer'); ?></button>
                <pre id="geo-bulk-output" class="geooptimizer-results"></pre>
            </div>

            <div class="geooptimizer-premium__panel">
                <h2><?php esc_html_e('Competitor Comparison', 'geooptimizer'); ?></h2>
                <textarea id="geo-primary-content" rows="4" class="large-text"></textarea>
                <textarea id="geo-competitors-json" rows="5" class="large-text" placeholder='[{"name":"Competitor","content":"..."}]'></textarea>
                <button type="button" class="button" id="geo-run-compare"><?php esc_html_e('Compare Competitors', 'geooptimizer'); ?></button>
                <pre id="geo-compare-output" class="geooptimizer-results"></pre>
            </div>
        </div>
        <?php
    }

    public function ajaxDashboard(): void
    {
        $this->assertPremiumAjax();
        $identifier = sanitize_text_field((string) ($_POST['identifier'] ?? $this->loadOptions()['business_name'] ?? ''));

        wp_send_json_success($this->optimizer()->getCitationDashboard($identifier));
    }

    public function ajaxBulkAnalyze(): void
    {
        $this->assertPremiumAjax();
        $rawItems = wp_unslash((string) ($_POST['items'] ?? ''));
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $rawItems) ?: []));
        $items = [];

        foreach ($lines as $line) {
            $decoded = json_decode($line, true);
            if (is_array($decoded)) {
                $items[] = $decoded;
            }
        }

        wp_send_json_success($this->optimizer()->bulkAnalyze($items));
    }

    public function ajaxCompareCompetitors(): void
    {
        $this->assertPremiumAjax();

        $competitors = json_decode(wp_unslash((string) ($_POST['competitors'] ?? '[]')), true);
        if (!is_array($competitors)) {
            wp_send_json_error(['message' => 'Invalid competitors JSON'], 422);
        }

        $options = $this->loadOptions();
        $result = $this->optimizer()->compareCompetitors(
            (string) ($options['business_name'] ?? get_bloginfo('name')),
            wp_unslash((string) ($_POST['primary_content'] ?? $this->collectSiteContent())),
            $competitors
        );

        wp_send_json_success($result);
    }

    /**
     * @param array<string, mixed>|null $options
     */
    public function isPremiumActive(?array $options = null): bool
    {
        $options = $options ?? $this->loadOptions();

        return PremiumLicense::isValid((string) ($options['license_key'] ?? ''));
    }

    private function optimizer(): GEOOptimizer
    {
        if ($this->geoOptimizer === null) {
            $this->geoOptimizer = new GEOOptimizer();
        }

        return $this->geoOptimizer;
    }

    private function assertPremiumAjax(): void
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        if (!$this->isPremiumActive()) {
            wp_send_json_error(['message' => 'Premium license required'], 403);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadOptions(): array
    {
        $options = get_option(self::OPTION_KEY, []);

        return is_array($options) ? $options : [];
    }

    private function collectSiteContent(): string
    {
        $parts = array_filter([
            (string) get_bloginfo('name'),
            (string) get_bloginfo('description'),
        ]);

        return trim(implode("\n\n", $parts));
    }
}
