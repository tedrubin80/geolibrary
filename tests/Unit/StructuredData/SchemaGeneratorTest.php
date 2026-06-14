<?php

declare(strict_types=1);

namespace GEOOptimizer\Tests\Unit\StructuredData;

use PHPUnit\Framework\TestCase;
use GEOOptimizer\StructuredData\SchemaGenerator;
use GEOOptimizer\Exceptions\ValidationException;

class SchemaGeneratorTest extends TestCase
{
    private SchemaGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SchemaGenerator();
    }

    public function testGenerateRestaurantSchema(): void
    {
        $data = [
            'name' => 'Joe\'s Pizza',
            'description' => 'Authentic Italian pizza',
            'cuisine_type' => 'Italian',
            'address' => '123 Main St, NY',
            'phone' => '555-123-4567',
            'price_range' => '$$',
            'accepts_reservations' => true
        ];

        $schema = $this->generator->generate('Restaurant', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('Restaurant', $schema['@type']);
    }

    public function testGenerateAutoRepairSchema(): void
    {
        $data = [
            'name' => 'Quick Fix Auto',
            'description' => 'Professional auto repair',
            'services' => ['Oil Change', 'Brake Repair'],
            'address' => '456 Auto Lane',
            'phone' => '555-234-5678'
        ];

        $schema = $this->generator->generate('AutoRepair', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('AutoRepair', $schema['@type']);
    }


    public function testGenerateHomeAndConstructionBusinessSchema(): void
    {
        $data = [
            'name' => 'Bob\'s Construction',
            'description' => 'Professional home construction',
            'services' => ['Remodeling', 'New Construction'],
            'license' => 'LIC-123456'
        ];

        $schema = $this->generator->generate('HomeAndConstructionBusiness', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('HomeAndConstructionBusiness', $schema['@type']);
    }

    public function testGenerateStoreSchema(): void
    {
        $data = [
            'name' => 'Corner Store',
            'description' => 'Your neighborhood store',
            'department' => 'Grocery',
            'currency' => 'USD'
        ];

        $schema = $this->generator->generate('Store', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('Store', $schema['@type']);
    }

    public function testGenerateRealEstateAgentSchema(): void
    {
        $data = [
            'name' => 'Jane Smith Realty',
            'description' => 'Your trusted real estate agent',
            'license' => 'RE-789012',
            'service_area' => 'New York Metro'
        ];

        $schema = $this->generator->generate('RealEstateAgent', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('RealEstateAgent', $schema['@type']);
    }

    public function testGenerateHealthAndBeautyBusinessSchema(): void
    {
        $data = [
            'name' => 'Spa Retreat',
            'description' => 'Luxury spa services',
            'services' => ['Massage', 'Facial', 'Manicure']
        ];

        $schema = $this->generator->generate('HealthAndBeautyBusiness', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('HealthAndBeautyBusiness', $schema['@type']);
    }

    public function testGenerateEducationalOrganizationSchema(): void
    {
        $data = [
            'name' => 'City University',
            'description' => 'Leading educational institution',
            'accreditation' => 'Regional Accreditation',
            'programs' => ['Computer Science', 'Business']
        ];

        $schema = $this->generator->generate('EducationalOrganization', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('EducationalOrganization', $schema['@type']);
    }

    public function testGenerateProfessionalServiceSchema(): void
    {
        $data = [
            'name' => 'Smith & Associates',
            'description' => 'Professional consulting services',
            'expertise' => ['Business Strategy'],
            'years_experience' => 20
        ];

        $schema = $this->generator->generate('ProfessionalService', $data);

        $this->assertIsArray($schema);
        $this->assertEquals('ProfessionalService', $schema['@type']);
    }

    public function testInvalidSchemaTypeThrowsException(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Unsupported schema type: InvalidType');

        $this->generator->generate('InvalidType', ['name' => 'Test']);
    }

    public function testGetSupportedTypes(): void
    {
        $types = $this->generator->getSupportedTypes();

        $this->assertIsArray($types);
        $this->assertContains('LocalBusiness', $types);
        $this->assertContains('Restaurant', $types);
        $this->assertContains('AutoRepair', $types);
    }

    public function testSchemaHasRequiredFields(): void
    {
        $data = [
            'name' => 'Test Business',
            'description' => 'A test business'
        ];

        $schema = $this->generator->generate('LocalBusiness', $data);

        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('LocalBusiness', $schema['@type']);
    }

    public function testLocalBusinessBasicSchema(): void
    {
        $data = [
            'name' => 'Business with Address',
            'description' => 'A local business',
            'address' => '123 Main St, New York, NY 10001',
            'phone' => '555-123-4567'
        ];

        $schema = $this->generator->generate('LocalBusiness', $data);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('@context', $schema);
        $this->assertArrayHasKey('@type', $schema);
        $this->assertEquals('LocalBusiness', $schema['@type']);
    }
}