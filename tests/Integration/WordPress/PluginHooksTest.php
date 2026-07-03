<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Integration\WordPress;

use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use GEOOptimizer\Integrations\WordPress\Plugin;
use PHPUnit\Framework\TestCase;

class PluginHooksTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        if (!defined('GEOOPTIMIZER_PLUGIN_FILE')) {
            define('GEOOPTIMIZER_PLUGIN_FILE', '/tmp/geooptimizer/geooptimizer.php');
        }

        Functions\when('get_option')->justReturn(false);
        Functions\when('get_bloginfo')->justReturn('');
        Functions\when('__')->returnArg(1);
        Functions\when('add_options_page')->justReturn(null);
        Functions\when('register_setting')->justReturn(null);
        Functions\when('add_settings_section')->justReturn(null);
        Functions\when('add_settings_field')->justReturn(null);
        Functions\when('register_activation_hook')->justReturn(null);
        Functions\when('register_deactivation_hook')->justReturn(null);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testRegisterAddsCoreHooks(): void
    {
        Actions\expectAdded('init')->atLeast()->once();
        Actions\expectAdded('admin_menu')->once();
        Actions\expectAdded('admin_init')->once();
        Actions\expectAdded('admin_enqueue_scripts')->once();
        Actions\expectAdded('wp_head')->once();
        Actions\expectAdded('save_post')->once();
        Actions\expectAdded('template_redirect')->once();
        Actions\expectAdded('wp_ajax_geo_generate_llms_txt')->once();
        Actions\expectAdded('wp_ajax_geo_analyze_content')->once();

        Plugin::register();

        $this->addToAssertionCount(1);
    }
}
