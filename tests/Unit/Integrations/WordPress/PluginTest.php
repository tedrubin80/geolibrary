<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\Integrations\WordPress;

use Brain\Monkey;
use Brain\Monkey\Functions;
use GEOOptimizer\Integrations\WordPress\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PluginTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('get_bloginfo')->alias(static function (string $key): string {
            return $key === 'name' ? 'Test Site' : 'A test site tagline';
        });
        Functions\when('get_option')->alias(static function (string $key, $default = false) {
            if ($key === 'admin_email') {
                return 'admin@example.com';
            }

            return $default;
        });
        Functions\when('__')->returnArg(1);
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('sanitize_textarea_field')->returnArg(1);
        Functions\when('sanitize_email')->returnArg(1);
        Functions\when('sanitize_key')->returnArg(1);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testSanitizeOptionsParsesServicesAndHours(): void
    {
        $plugin = $this->createPluginInstance();
        $method = $this->getMethod('sanitizeOptions');

        $result = $method->invoke($plugin, [
            'business_name' => 'Acme Coffee',
            'description' => 'Specialty coffee.',
            'industry' => 'restaurant',
            'location' => 'San Francisco, CA',
            'phone' => '555-0100',
            'email' => 'hello@acme.test',
            'services' => "Espresso\nPour Over\n\nRetail Beans\n",
            'hours' => "Monday: 9:00 AM - 5:00 PM\nTuesday: 9:00 AM - 5:00 PM\ninvalid line\n",
            'enable_structured_data' => '1',
            'enable_llms_txt' => '1',
        ]);

        $this->assertSame(['Espresso', 'Pour Over', 'Retail Beans'], $result['services']);
        $this->assertSame(
            [
                'Monday' => '9:00 AM - 5:00 PM',
                'Tuesday' => '9:00 AM - 5:00 PM',
            ],
            $result['hours']
        );
        $this->assertTrue($result['enable_structured_data']);
        $this->assertTrue($result['enable_llms_txt']);
    }

    public function testSanitizeOptionsRejectsUnknownIndustryWhenOptimizerAvailable(): void
    {
        $plugin = $this->createPluginInstance();
        $this->setProperty($plugin, 'geoOptimizer', new \GEOOptimizer\GEOOptimizer());

        $method = $this->getMethod('sanitizeOptions');
        $result = $method->invoke($plugin, [
            'business_name' => 'Acme Coffee',
            'description' => 'Specialty coffee.',
            'industry' => 'not-a-real-industry',
            'services' => '',
            'hours' => '',
        ]);

        $this->assertSame('', $result['industry']);
    }

    public function testBuildBusinessDataIncludesWebsiteAndAddress(): void
    {
        Functions\when('home_url')->justReturn('https://example.test/');

        $plugin = $this->createPluginInstance();
        $this->setProperty($plugin, 'options', [
            'business_name' => 'Acme Coffee',
            'description' => 'Specialty coffee.',
            'location' => 'San Francisco, CA',
            'services' => ['Espresso'],
            'hours' => ['Monday' => '9:00 AM - 5:00 PM'],
        ]);

        $method = $this->getMethod('buildBusinessData');
        $result = $method->invoke($plugin);

        $this->assertSame('https://example.test/', $result['website']);
        $this->assertSame('San Francisco, CA', $result['address']);
        $this->assertSame(['Espresso'], $result['services']);
    }

    public function testDefaultOptionsUsesWordPressSiteMetadata(): void
    {
        $plugin = $this->createPluginInstance();
        $method = $this->getMethod('defaultOptions');
        $result = $method->invoke($plugin);

        $this->assertSame('Test Site', $result['business_name']);
        $this->assertSame('A test site tagline', $result['description']);
        $this->assertSame('admin@example.com', $result['email']);
        $this->assertSame([], $result['services']);
        $this->assertSame([], $result['hours']);
        $this->assertTrue($result['enable_structured_data']);
        $this->assertTrue($result['enable_llms_txt']);
    }

    public function testParseServicesListIgnoresBlankLines(): void
    {
        $plugin = $this->createPluginInstance();
        $method = $this->getMethod('parseServicesList');

        $result = $method->invoke($plugin, "One\n\n Two \n");

        $this->assertSame(['One', 'Two'], $result);
    }

    public function testParseHoursListRequiresDayTimePairs(): void
    {
        $plugin = $this->createPluginInstance();
        $method = $this->getMethod('parseHoursList');

        $result = $method->invoke($plugin, "Monday: 9-5\nNo colon line\nTuesday: 10-6");

        $this->assertSame(
            [
                'Monday' => '9-5',
                'Tuesday' => '10-6',
            ],
            $result
        );
    }

    private function createPluginInstance(): Plugin
    {
        $reflection = new ReflectionClass(Plugin::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    private function getMethod(string $name): \ReflectionMethod
    {
        $method = (new ReflectionClass(Plugin::class))->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param mixed $value
     */
    private function setProperty(object $object, string $name, $value): void
    {
        $property = (new ReflectionClass($object))->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
