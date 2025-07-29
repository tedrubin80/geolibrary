<?php
/**
 * GEO Optimizer Admin Interface
 * 
 * Handles WordPress admin dashboard, settings, and user interface
 */

class GEO_Optimizer_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        
        // Admin bar integration
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        
        // Plugin action links
        add_filter('plugin_action_links_' . GEO_OPTIMIZER_PLUGIN_BASENAME, [$this, 'add_plugin_action_links']);
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('GEO Optimizer', 'geo-optimizer'),
            __('GEO Optimizer', 'geo-optimizer'),
            'manage_options',
            'geo-optimizer',
            [$this, 'render_dashboard_page'],
            'dashicons-search',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'geo-optimizer',
            __('Dashboard', 'geo-optimizer'),
            __('Dashboard', 'geo-optimizer'),
            'manage_options',
            'geo-optimizer',
            [$this, 'render_dashboard_page']
        );
        
        // Analytics submenu
        add_submenu_page(
            'geo-optimizer',
            __('Analytics', 'geo-optimizer'),
            __('Analytics', 'geo-optimizer'),
            'manage_options',
            'geo-optimizer-analytics',
            [$this, 'render_analytics_page']
        );
        
        // Content Analysis submenu
        add_submenu_page(
            'geo-optimizer',
            __('Content Analysis', 'geo-optimizer'),
            __('Content Analysis', 'geo-optimizer'),
            'edit_posts',
            'geo-optimizer-analysis',
            [$this, 'render_content_analysis_page']
        );
        
        // Settings submenu
        add_submenu_page(
            'geo-optimizer',
            __('Settings', 'geo-optimizer'),
            __('Settings', 'geo-optimizer'),
            'manage_options',
            'geo-optimizer-settings',
            [$this, 'render_settings_page']
        );
        
        // Tools submenu
        add_submenu_page(
            'geo-optimizer',
            __('Tools', 'geo-optimizer'),
            __('Tools', 'geo-optimizer'),
            'manage_options',
            'geo-optimizer-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('geo_optimizer_settings', 'geo_optimizer_business_info');
        register_setting('geo_optimizer_settings', 'geo_optimizer_business_type');
        register_setting('geo_optimizer_settings', 'geo_optimizer_auto_generate_llms');
        register_setting('geo_optimizer_settings', 'geo_optimizer_auto_optimize_content');
        register_setting('geo_optimizer_settings', 'geo_optimizer_citation_tracking');
        register_setting('geo_optimizer_settings', 'geo_optimizer_structured_data');
        register_setting('geo_optimizer_settings', 'geo_optimizer_meta_optimization');
        register_setting('geo_optimizer_settings', 'geo_optimizer_api_key');
    }
    
    /**
     * Render dashboard page
     */
    public function render_dashboard_page() {
        $core = new GEO_Optimizer_Core();
        $stats = $core->get_site_stats();
        
        ?>
        <div class="wrap">
            <h1><?php _e('GEO Optimizer Dashboard', 'geo-optimizer'); ?></h1>
            
            <div class="geo-optimizer-dashboard">
                <!-- Stats Overview -->
                <div class="geo-stats-grid">
                    <div class="geo-stat-card">
                        <div class="geo-stat-icon">📊</div>
                        <div class="geo-stat-content">
                            <h3><?php echo esc_html($stats['average_score']); ?></h3>
                            <p><?php _e('Average GEO Score', 'geo-optimizer'); ?></p>
                        </div>
                    </div>
                    
                    <div class="geo-stat-card">
                        <div class="geo-stat-icon">📝</div>
                        <div class="geo-stat-content">
                            <h3><?php echo esc_html($stats['optimized_posts']); ?></h3>
                            <p><?php _e('Optimized Posts', 'geo-optimizer'); ?></p>
                        </div>
                    </div>
                    
                    <div class="geo-stat-card">
                        <div class="geo-stat-icon">🎯</div>
                        <div class="geo-stat-content">
                            <h3><?php echo esc_html($stats['citations_found']); ?></h3>
                            <p><?php _e('Citations Found', 'geo-optimizer'); ?></p>
                        </div>
                    </div>
                    
                    <div class="geo-stat-card">
                        <div class="geo-stat-icon">⚡</div>
                        <div class="geo-stat-content">
                            <h3><?php echo esc_html($stats['improvements']); ?>%</h3>
                            <p><?php _e('Performance Boost', 'geo-optimizer'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="geo-quick-actions">
                    <h2><?php _e('Quick Actions', 'geo-optimizer'); ?></h2>
                    <div class="geo-action-buttons">
                        <button class="button button-primary" id="generate-llms-txt">
                            <?php _e('Generate llms.txt', 'geo-optimizer'); ?>
                        </button>
                        <button class="button button-secondary" id="analyze-all-content">
                            <?php _e('Analyze All Content', 'geo-optimizer'); ?>
                        </button>
                        <button class="button button-secondary" id="export-data">
                            <?php _e('Export Analytics', 'geo-optimizer'); ?>
                        </button>
                        <a href="<?php echo admin_url('admin.php?page=geo-optimizer-settings'); ?>" class="button">
                            <?php _e('Configure Settings', 'geo-optimizer'); ?>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="geo-recent-activity">
                    <h2><?php _e('Recent Activity', 'geo-optimizer'); ?></h2>
                    <div class="geo-activity-list">
                        <?php
                        $recent_activity = $core->get_recent_activity();
                        if (!empty($recent_activity)) {
                            foreach ($recent_activity as $activity) {
                                echo '<div class="geo-activity-item">';
                                echo '<span class="geo-activity-time">' . human_time_diff(strtotime($activity['date'])) . ' ago</span>';
                                echo '<span class="geo-activity-description">' . esc_html($activity['description']) . '</span>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>' . __('No recent activity. Start optimizing your content!', 'geo-optimizer') . '</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- llms.txt Status -->
                <div class="geo-llms-status">
                    <h2><?php _e('llms.txt Status', 'geo-optimizer'); ?></h2>
                    <div class="geo-llms-info">
                        <?php
                        $llms_url = home_url('/llms.txt');
                        $llms_exists = $core->check_llms_txt_exists();
                        ?>
                        <p>
                            <strong><?php _e('File URL:', 'geo-optimizer'); ?></strong>
                            <a href="<?php echo esc_url($llms_url); ?>" target="_blank"><?php echo esc_url($llms_url); ?></a>
                        </p>
                        <p>
                            <strong><?php _e('Status:', 'geo-optimizer'); ?></strong>
                            <span class="geo-status-<?php echo $llms_exists ? 'active' : 'inactive'; ?>">
                                <?php echo $llms_exists ? __('Active', 'geo-optimizer') : __('Not Generated', 'geo-optimizer'); ?>
                            </span>
                        </p>
                        <?php if (!$llms_exists): ?>
                        <p class="geo-notice">
                            <?php _e('Generate your llms.txt file to help AI search engines understand your website better.', 'geo-optimizer'); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render analytics page
     */
    public function render_analytics_page() {
        $core = new GEO_Optimizer_Core();
        $analytics_data = $core->get_analytics_data();
        
        ?>
        <div class="wrap">
            <h1><?php _e('GEO Analytics', 'geo-optimizer'); ?></h1>
            
            <div class="geo-analytics-dashboard">
                <!-- Performance Chart -->
                <div class="geo-chart-container">
                    <h2><?php _e('GEO Score Trends', 'geo-optimizer'); ?></h2>
                    <canvas id="geo-performance-chart"></canvas>
                </div>
                
                <!-- Citation Tracking -->
                <div class="geo-citations-container">
                    <h2><?php _e('Citation Tracking', 'geo-optimizer'); ?></h2>
                    <div class="geo-citations-list">
                        <?php
                        if (!empty($analytics_data['citations'])) {
                            foreach ($analytics_data['citations'] as $citation) {
                                echo '<div class="geo-citation-item">';
                                echo '<h4>' . esc_html($citation['source']) . '</h4>';
                                echo '<p>' . esc_html($citation['content']) . '</p>';
                                echo '<span class="geo-citation-date">' . esc_html($citation['date']) . '</span>';
                                echo '<a href="' . esc_url($citation['url']) . '" target="_blank" class="geo-citation-link">View Source</a>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p>' . __('No citations found yet. Keep creating quality content!', 'geo-optimizer') . '</p>';
                        }
                        ?>
                    </div>
                </div>
                
                <!-- Top Performing Content -->
                <div class="geo-top-content">
                    <h2><?php _e('Top Performing Content', 'geo-optimizer'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Title', 'geo-optimizer'); ?></th>
                                <th><?php _e('GEO Score', 'geo-optimizer'); ?></th>
                                <th><?php _e('Citations', 'geo-optimizer'); ?></th>
                                <th><?php _e('Last Updated', 'geo-optimizer'); ?></th>
                                <th><?php _e('Actions', 'geo-optimizer'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($analytics_data['top_content'])) {
                                foreach ($analytics_data['top_content'] as $content) {
                                    echo '<tr>';
                                    echo '<td><a href="' . get_edit_post_link($content['id']) . '">' . esc_html($content['title']) . '</a></td>';
                                    echo '<td><span class="geo-score-badge geo-score-' . $this->get_score_class($content['score']) . '">' . $content['score'] . '</span></td>';
                                    echo '<td>' . $content['citations'] . '</td>';
                                    echo '<td>' . human_time_diff(strtotime($content['updated'])) . ' ago</td>';
                                    echo '<td>';
                                    echo '<a href="' . get_edit_post_link($content['id']) . '" class="button button-small">' . __('Edit', 'geo-optimizer') . '</a> ';
                                    echo '<button class="button button-small geo-analyze-single" data-post-id="' . $content['id'] . '">' . __('Analyze', 'geo-optimizer') . '</button>';
                                    echo '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="5">' . __('No analyzed content yet.', 'geo-optimizer') . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render content analysis page
     */
    public function render_content_analysis_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Content Analysis', 'geo-optimizer'); ?></h1>
            
            <div class="geo-content-analysis">
                <!-- Bulk Actions -->
                <div class="geo-bulk-actions">
                    <h2><?php _e('Bulk Analysis', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Analyze multiple posts at once to improve your GEO scores.', 'geo-optimizer'); ?></p>
                    
                    <div class="geo-bulk-controls">
                        <select id="geo-post-type-filter">
                            <option value=""><?php _e('All Post Types', 'geo-optimizer'); ?></option>
                            <?php
                            $post_types = get_post_types(['public' => true], 'objects');
                            foreach ($post_types as $post_type) {
                                echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
                            }
                            ?>
                        </select>
                        
                        <select id="geo-score-filter">
                            <option value=""><?php _e('All Scores', 'geo-optimizer'); ?></option>
                            <option value="0-40"><?php _e('Poor (0-40)', 'geo-optimizer'); ?></option>
                            <option value="41-60"><?php _e('Fair (41-60)', 'geo-optimizer'); ?></option>
                            <option value="61-80"><?php _e('Good (61-80)', 'geo-optimizer'); ?></option>
                            <option value="81-100"><?php _e('Excellent (81-100)', 'geo-optimizer'); ?></option>
                        </select>
                        
                        <button class="button button-primary" id="geo-bulk-analyze">
                            <?php _e('Analyze Selected', 'geo-optimizer'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Content List -->
                <div class="geo-content-list">
                    <div id="geo-content-table-container">
                        <!-- Content will be loaded via AJAX -->
                        <div class="geo-loading"><?php _e('Loading content...', 'geo-optimizer'); ?></div>
                    </div>
                </div>
                
                <!-- Analysis Results -->
                <div id="geo-analysis-results" style="display: none;">
                    <h2><?php _e('Analysis Results', 'geo-optimizer'); ?></h2>
                    <div id="geo-results-content"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $business_info = get_option('geo_optimizer_business_info', []);
        $business_type = get_option('geo_optimizer_business_type', 'general');
        ?>
        <div class="wrap">
            <h1><?php _e('GEO Optimizer Settings', 'geo-optimizer'); ?></h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('geo_optimizer_settings', 'geo_optimizer_settings_nonce'); ?>
                
                <!-- Business Information -->
                <div class="geo-settings-section">
                    <h2><?php _e('Business Information', 'geo-optimizer'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Business Name', 'geo-optimizer'); ?></th>
                            <td>
                                <input type="text" name="business_info[name]" value="<?php echo esc_attr($business_info['name'] ?? ''); ?>" class="regular-text" />
                                <p class="description"><?php _e('Your business or organization name', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Business Type', 'geo-optimizer'); ?></th>
                            <td>
                                <select name="business_type">
                                    <option value="general" <?php selected($business_type, 'general'); ?>><?php _e('General Business', 'geo-optimizer'); ?></option>
                                    <option value="restaurant" <?php selected($business_type, 'restaurant'); ?>><?php _e('Restaurant', 'geo-optimizer'); ?></option>
                                    <option value="legal" <?php selected($business_type, 'legal'); ?>><?php _e('Legal Services', 'geo-optimizer'); ?></option>
                                    <option value="medical" <?php selected($business_type, 'medical'); ?>><?php _e('Medical/Healthcare', 'geo-optimizer'); ?></option>
                                    <option value="automotive" <?php selected($business_type, 'automotive'); ?>><?php _e('Automotive', 'geo-optimizer'); ?></option>
                                    <option value="home-services" <?php selected($business_type, 'home-services'); ?>><?php _e('Home Services', 'geo-optimizer'); ?></option>
                                    <option value="retail" <?php selected($business_type, 'retail'); ?>><?php _e('Retail', 'geo-optimizer'); ?></option>
                                    <option value="real-estate" <?php selected($business_type, 'real-estate'); ?>><?php _e('Real Estate', 'geo-optimizer'); ?></option>
                                    <option value="fitness" <?php selected($business_type, 'fitness'); ?>><?php _e('Fitness/Wellness', 'geo-optimizer'); ?></option>
                                    <option value="beauty" <?php selected($business_type, 'beauty'); ?>><?php _e('Beauty/Salon', 'geo-optimizer'); ?></option>
                                    <option value="education" <?php selected($business_type, 'education'); ?>><?php _e('Education', 'geo-optimizer'); ?></option>
                                    <option value="technology" <?php selected($business_type, 'technology'); ?>><?php _e('Technology', 'geo-optimizer'); ?></option>
                                </select>
                                <p class="description"><?php _e('Select your business type for optimized templates', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Description', 'geo-optimizer'); ?></th>
                            <td>
                                <textarea name="business_info[description]" rows="3" class="large-text"><?php echo esc_textarea($business_info['description'] ?? ''); ?></textarea>
                                <p class="description"><?php _e('Brief description of your business for AI optimization', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Location', 'geo-optimizer'); ?></th>
                            <td>
                                <input type="text" name="business_info[location]" value="<?php echo esc_attr($business_info['location'] ?? ''); ?>" class="regular-text" />
                                <p class="description"><?php _e('City, State (e.g., Springfield, Illinois)', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Phone', 'geo-optimizer'); ?></th>
                            <td>
                                <input type="tel" name="business_info[phone]" value="<?php echo esc_attr($business_info['phone'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Email', 'geo-optimizer'); ?></th>
                            <td>
                                <input type="email" name="business_info[email]" value="<?php echo esc_attr($business_info['email'] ?? ''); ?>" class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Services', 'geo-optimizer'); ?></th>
                            <td>
                                <textarea name="business_info[services]" rows="3" class="large-text"><?php echo esc_textarea($business_info['services'] ?? ''); ?></textarea>
                                <p class="description"><?php _e('List your main services, one per line', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Optimization Settings -->
                <div class="geo-settings-section">
                    <h2><?php _e('Optimization Settings', 'geo-optimizer'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Auto-Generate llms.txt', 'geo-optimizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_generate_llms" value="1" <?php checked(get_option('geo_optimizer_auto_generate_llms', true)); ?> />
                                    <?php _e('Automatically generate and update llms.txt file', 'geo-optimizer'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Auto-Optimize Content', 'geo-optimizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="auto_optimize_content" value="1" <?php checked(get_option('geo_optimizer_auto_optimize_content', false)); ?> />
                                    <?php _e('Automatically optimize new content for GEO', 'geo-optimizer'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Citation Tracking', 'geo-optimizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="citation_tracking" value="1" <?php checked(get_option('geo_optimizer_citation_tracking', true)); ?> />
                                    <?php _e('Monitor AI citations of your content', 'geo-optimizer'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Structured Data', 'geo-optimizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="structured_data" value="1" <?php checked(get_option('geo_optimizer_structured_data', true)); ?> />
                                    <?php _e('Add structured data markup to pages', 'geo-optimizer'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Meta Optimization', 'geo-optimizer'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="meta_optimization" value="1" <?php checked(get_option('geo_optimizer_meta_optimization', true)); ?> />
                                    <?php _e('Optimize meta tags for AI search engines', 'geo-optimizer'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- API Settings -->
                <div class="geo-settings-section">
                    <h2><?php _e('API Settings', 'geo-optimizer'); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'geo-optimizer'); ?></th>
                            <td>
                                <input type="password" name="api_key" value="<?php echo esc_attr(get_option('geo_optimizer_api_key', '')); ?>" class="regular-text" />
                                <p class="description"><?php _e('Optional: API key for premium features', 'geo-optimizer'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render tools page
     */
    public function render_tools_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('GEO Optimizer Tools', 'geo-optimizer'); ?></h1>
            
            <div class="geo-tools-grid">
                <!-- llms.txt Generator -->
                <div class="geo-tool-card">
                    <h2><?php _e('llms.txt Generator', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Generate or update your llms.txt file for AI search engines.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button button-primary" id="generate-llms-tool">
                            <?php _e('Generate llms.txt', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="preview-llms-tool">
                            <?php _e('Preview', 'geo-optimizer'); ?>
                        </button>
                    </div>
                    <div id="llms-preview" class="geo-tool-result" style="display: none;"></div>
                </div>
                
                <!-- Content Audit -->
                <div class="geo-tool-card">
                    <h2><?php _e('Site Content Audit', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Analyze all your content for GEO optimization opportunities.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button button-primary" id="audit-content-tool">
                            <?php _e('Start Audit', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="export-audit-tool">
                            <?php _e('Export Results', 'geo-optimizer'); ?>
                        </button>
                    </div>
                    <div id="audit-progress" class="geo-tool-result" style="display: none;"></div>
                </div>
                
                <!-- Schema Validator -->
                <div class="geo-tool-card">
                    <h2><?php _e('Schema Validator', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Validate your structured data markup.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button button-primary" id="validate-schema-tool">
                            <?php _e('Validate Schema', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="test-schema-tool">
                            <?php _e('Test with Google', 'geo-optimizer'); ?>
                        </button>
                    </div>
                    <div id="schema-results" class="geo-tool-result" style="display: none;"></div>
                </div>
                
                <!-- Citation Monitor -->
                <div class="geo-tool-card">
                    <h2><?php _e('Citation Monitor', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Monitor where your content is being cited by AI systems.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button button-primary" id="check-citations-tool">
                            <?php _e('Check Citations', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="setup-alerts-tool">
                            <?php _e('Setup Alerts', 'geo-optimizer'); ?>
                        </button>
                    </div>
                    <div id="citation-results" class="geo-tool-result" style="display: none;"></div>
                </div>
                
                <!-- Import/Export -->
                <div class="geo-tool-card">
                    <h2><?php _e('Import/Export', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Backup and restore your GEO optimization settings.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button" id="export-settings-tool">
                            <?php _e('Export Settings', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="import-settings-tool">
                            <?php _e('Import Settings', 'geo-optimizer'); ?>
                        </button>
                        <input type="file" id="import-file" accept=".json" style="display: none;" />
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="geo-tool-card">
                    <h2><?php _e('System Information', 'geo-optimizer'); ?></h2>
                    <p><?php _e('Check system requirements and plugin status.', 'geo-optimizer'); ?></p>
                    <div class="geo-tool-actions">
                        <button class="button" id="system-info-tool">
                            <?php _e('Show System Info', 'geo-optimizer'); ?>
                        </button>
                        <button class="button" id="run-diagnostics-tool">
                            <?php _e('Run Diagnostics', 'geo-optimizer'); ?>
                        </button>
                    </div>
                    <div id="system-info-results" class="geo-tool-result" style="display: none;"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        if (!isset($_POST['geo_optimizer_settings_nonce']) || 
            !wp_verify_nonce($_POST['geo_optimizer_settings_nonce'], 'geo_optimizer_settings')) {
            return;
        }
        
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save business info
        if (isset($_POST['business_info'])) {
            update_option('geo_optimizer_business_info', $_POST['business_info']);
        }
        
        // Save other settings
        $settings = [
            'business_type',
            'auto_generate_llms',
            'auto_optimize_content',
            'citation_tracking',
            'structured_data',
            'meta_optimization',
            'api_key'
        ];
        
        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                update_option('geo_optimizer_' . $setting, $_POST[$setting]);
            } else {
                update_option('geo_optimizer_' . $setting, false);
            }
        }
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'geo-optimizer') . '</p></div>';
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $screen = get_current_screen();
        
        // Only show on GEO Optimizer pages
        if (strpos($screen->id, 'geo-optimizer') === false) {
            return;
        }
        
        // Check if llms.txt exists
        $core = new GEO_Optimizer_Core();
        if (!$core->check_llms_txt_exists()) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p>' . __('Your llms.txt file has not been generated yet. ', 'geo-optimizer');
            echo '<a href="' . admin_url('admin.php?page=geo-optimizer-tools') . '">' . __('Generate it now', 'geo-optimizer') . '</a>';
            echo ' to help AI search engines understand your website better.</p>';
            echo '</div>';
        }
        
        // Check if business info is complete
        $business_info = get_option('geo_optimizer_business_info', []);
        if (empty($business_info['name']) || empty($business_info['description'])) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . __('Complete your business information in ', 'geo-optimizer');
            echo '<a href="' . admin_url('admin.php?page=geo-optimizer-settings') . '">' . __('settings', 'geo-optimizer') . '</a>';
            echo ' for better GEO optimization.</p>';
            echo '</div>';
        }
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'geo_optimizer_dashboard_widget',
            __('GEO Optimizer Status', 'geo-optimizer'),
            [$this, 'render_dashboard_widget']
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $core = new GEO_Optimizer_Core();
        $stats = $core->get_site_stats();
        
        echo '<div class="geo-dashboard-widget">';
        echo '<p><strong>' . __('Average GEO Score:', 'geo-optimizer') . '</strong> ' . $stats['average_score'] . '</p>';
        echo '<p><strong>' . __('Optimized Posts:', 'geo-optimizer') . '</strong> ' . $stats['optimized_posts'] . '</p>';
        echo '<p><strong>' . __('Citations Found:', 'geo-optimizer') . '</strong> ' . $stats['citations_found'] . '</p>';
        echo '<p><a href="' . admin_url('admin.php?page=geo-optimizer') . '" class="button button-primary">' . __('View Full Dashboard', 'geo-optimizer') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Add admin bar menu
     */
    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        $wp_admin_bar->add_menu([
            'id' => 'geo-optimizer',
            'title' => __('GEO', 'geo-optimizer'),
            'href' => admin_url('admin.php?page=geo-optimizer'),
        ]);
        
        $wp_admin_bar->add_menu([
            'parent' => 'geo-optimizer',
            'id' => 'geo-optimizer-analyze',
            'title' => __('Analyze Current Page', 'geo-optimizer'),
            'href' => '#',
            'meta' => ['class' => 'geo-analyze-current-page']
        ]);
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=geo-optimizer-settings') . '">' . __('Settings', 'geo-optimizer') . '</a>';
        $dashboard_link = '<a href="' . admin_url('admin.php?page=geo-optimizer') . '">' . __('Dashboard', 'geo-optimizer') . '</a>';
        
        array_unshift($links, $settings_link, $dashboard_link);
        
        return $links;
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
                                    