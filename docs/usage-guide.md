# How to Use the PHP GEO Optimization Library

## Table of Contents
1. [Quick Start](#quick-start)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Core Features](#core-features)
5. [Schema Types](#schema-types)
6. [Caching System](#caching-system)
7. [GEO Readiness Score](#geo-readiness-score)
8. [Real-World Examples](#real-world-examples)
9. [Integration Guides](#integration-guides)
10. [Advanced Usage](#advanced-usage)
11. [Troubleshooting](#troubleshooting)

## Quick Start

### 30-Second Setup
```bash
# Install via Composer
composer require geooptimizer/php-geo-optimizer

# Basic usage
<?php
require_once 'vendor/autoload.php';

use GEOOptimizer\GEOOptimizer;

$geo = new GEOOptimizer();
$result = $geo->optimize([
    'business_name' => 'Your Business Name',
    'description' => 'What your business does',
    'industry' => 'restaurant'  // or any supported industry
]);

// Generate llms.txt file
file_put_contents('public/llms.txt', $result['llms_txt']);

// Add structured data to your HTML
echo '<script type="application/ld+json">' . json_encode($result['schema']) . '</script>';
```

## Installation

### Via Composer (Recommended)
```bash
composer require geooptimizer/php-geo-optimizer
```

### Manual Installation
1. Clone the repository:
```bash
git clone https://github.com/tedrubin80/geolibrary.git
cd geolibrary
composer install
```

2. Include the autoloader in your project:
```php
require_once 'path/to/geolibrary/vendor/autoload.php';
```

## Basic Usage

### Initialize the Library
```php
use GEOOptimizer\GEOOptimizer;

// Basic initialization
$geo = new GEOOptimizer();

// With custom configuration
$geo = new GEOOptimizer([
    'cache_enabled' => true,
    'cache_ttl' => 7200,
    'cache' => [
        'adapter' => 'file',  // or 'redis', 'memory', 'null'
        'path' => '/tmp/geo_cache'
    ]
]);
```

### Generate llms.txt File
```php
$llmsTxt = $geo->generateLLMSTxt([
    'business_name' => 'Joe\'s Pizza Palace',
    'description' => 'Family-owned pizzeria serving authentic Italian pizza since 1985',
    'industry' => 'restaurant',
    'location' => 'New York, NY',
    'services' => ['Dine-in', 'Takeout', 'Delivery'],
    'specialties' => ['Wood-fired pizza', 'Homemade pasta', 'Tiramisu']
]);

file_put_contents('public/llms.txt', $llmsTxt);
```

## Core Features

### 1. LLMs.txt Generation
Creates AI-optimized text files that help AI systems understand your business:

```php
use GEOOptimizer\LLMSTxt\Generator;

$generator = new Generator();

$businessData = [
    'name' => 'Tech Solutions Inc',
    'description' => 'Leading provider of cloud infrastructure solutions',
    'industry' => 'technology',
    'services' => ['Cloud Migration', 'DevOps Consulting', 'Security Audits'],
    'established' => '2010',
    'team_size' => '50-100'
];

$llmsTxt = $generator->generate($businessData, 'professional');
$generator->save($businessData, 'public/llms.txt');
```

### 2. Structured Data Generation
Generate rich Schema.org markup for better AI understanding:

```php
use GEOOptimizer\StructuredData\SchemaGenerator;

$schemaGen = new SchemaGenerator();

// Generate business schema
$schema = $schemaGen->generate('LocalBusiness', [
    'business_name' => 'Downtown Auto Repair',
    'description' => 'Expert auto repair and maintenance services',
    'phone' => '555-0123',
    'address' => [
        'street_address' => '123 Main St',
        'city' => 'Springfield',
        'state' => 'IL',
        'postal_code' => '62701'
    ],
    'hours' => [
        'Mon-Fri' => '8:00 AM - 6:00 PM',
        'Sat' => '9:00 AM - 3:00 PM',
        'Sun' => 'Closed'
    ]
]);

// Convert to JSON-LD
$jsonLd = $schemaGen->toJsonLd($schema);
```

### 3. Content Analysis
Analyze your content for AI optimization:

```php
use GEOOptimizer\Analysis\ContentAnalyzer;

$analyzer = new ContentAnalyzer();

$content = "Your website content here...";
$analysis = $analyzer->analyzeForGEO($content);

// Results include:
// - Readability score
// - Authority signals
// - Keyword density
// - Content structure
// - Recommendations
```

### 4. GEO Readiness Score
Evaluate how well-optimized your site is for AI discovery:

```php
use GEOOptimizer\Analytics\GEOReadinessScore;

$scoreCalculator = new GEOReadinessScore();

$score = $scoreCalculator->calculate([
    'has_llms_txt' => true,
    'has_schema' => true,
    'schema_types' => ['LocalBusiness', 'FAQ', 'HowTo'],
    'content' => $yourContent,
    'mobile_friendly' => true,
    'https' => true,
    'has_reviews' => true,
    'average_rating' => 4.5,
    'last_updated' => '2025-01-01'
]);

// Returns:
// - overall_score: 0-100
// - grade: A+, A, B, etc.
// - recommendations: Array of actionable items
// - strengths: What you're doing well
// - weaknesses: Areas for improvement
```

## Schema Types

### Supported Business Types
The library now supports 11+ schema types:

1. **LocalBusiness** - General business schema
2. **Restaurant** - Food service establishments
3. **LegalService** - Law firms and legal professionals
4. **MedicalBusiness** - Healthcare providers
5. **AutoRepair** - Auto service centers
6. **HomeAndConstructionBusiness** - Contractors and home services
7. **Store** - Retail establishments
8. **RealEstateAgent** - Real estate professionals
9. **HealthAndBeautyBusiness** - Salons, spas, fitness centers
10. **EducationalOrganization** - Schools and training centers
11. **ProfessionalService** - Consultants and B2B services

### Critical Schema Types for AI

#### HowTo Schema
Perfect for instructional content:
```php
$howTo = $schemaGen->generateHowTo([
    'title' => 'How to Change Your Oil',
    'description' => 'Step-by-step guide to changing your car oil',
    'time_required' => 'PT30M',  // 30 minutes
    'difficulty' => 'Beginner',
    'supplies' => [
        ['name' => 'Motor oil', 'quantity' => '5 quarts'],
        ['name' => 'Oil filter', 'quantity' => '1']
    ],
    'tools' => ['Oil pan', 'Wrench set', 'Funnel'],
    'steps' => [
        ['text' => 'Warm up the engine for 2-3 minutes'],
        ['text' => 'Jack up the car safely'],
        ['text' => 'Drain the old oil'],
        ['text' => 'Replace the oil filter'],
        ['text' => 'Add new oil']
    ]
]);
```

#### Product Schema
For e-commerce and product listings:
```php
$product = $schemaGen->generateProduct([
    'name' => 'Professional Coffee Maker',
    'description' => 'High-end espresso machine for home use',
    'brand' => 'BrewMaster',
    'sku' => 'BM-ESP-3000',
    'offers' => [
        [
            'price' => 899.99,
            'currency' => 'USD',
            'availability' => 'InStock',
            'seller' => 'Coffee Equipment Co'
        ]
    ],
    'rating' => [
        'value' => 4.8,
        'count' => 234
    ]
]);
```

#### BreadcrumbList Schema
Helps AI understand site structure:
```php
$breadcrumbs = $schemaGen->generateBreadcrumbList([
    ['name' => 'Home', 'url' => 'https://example.com'],
    ['name' => 'Products', 'url' => 'https://example.com/products'],
    ['name' => 'Coffee Makers', 'url' => 'https://example.com/products/coffee-makers']
]);
```

#### Review Schema
Add social proof:
```php
$review = $schemaGen->generateReview([
    'item_name' => 'Downtown Pizza',
    'review_body' => 'Best pizza in town! The crust is perfect.',
    'rating' => 5,
    'author' => ['name' => 'John Smith'],
    'date_published' => '2025-01-15'
]);
```

## Caching System

### Enable Caching
The library includes a sophisticated caching system to improve performance:

```php
$geo = new GEOOptimizer([
    'cache_enabled' => true,
    'cache' => [
        'adapter' => 'file',  // Choose: 'file', 'redis', 'memory', 'null'
        'path' => '/tmp/geo_cache',
        'ttl' => 3600  // 1 hour default
    ]
]);
```

### Cache Adapters

#### File Cache (Default)
Best for development and small deployments:
```php
'cache' => [
    'adapter' => 'file',
    'path' => '/var/cache/geo',
    'prefix' => 'geo_'
]
```

#### Redis Cache
High-performance for production:
```php
'cache' => [
    'adapter' => 'redis',
    'host' => '127.0.0.1',
    'port' => 6379,
    'password' => 'your-password',
    'database' => 0
]
```

#### Memory Cache
In-request caching only:
```php
'cache' => [
    'adapter' => 'memory'
]
```

### Manual Cache Control
```php
use GEOOptimizer\Cache\CacheManager;

// Clear all cache
$cache = CacheManager::getInstance();
$cache->clear();

// Remove specific cache entry
$cache->delete('schema_Restaurant_hash');

// Check if cached
if ($cache->has('llms_txt_hash')) {
    $cached = $cache->get('llms_txt_hash');
}
```

## GEO Readiness Score

### Complete Assessment
Get a comprehensive evaluation of your GEO optimization:

```php
use GEOOptimizer\Analytics\GEOReadinessScore;

$scorer = new GEOReadinessScore();

$assessment = $scorer->calculate([
    // Content signals
    'has_llms_txt' => true,
    'content' => $yourPageContent,

    // Structured data
    'has_schema' => true,
    'schema_types' => ['LocalBusiness', 'FAQ', 'Review'],
    'schema_properties' => 25,

    // Authority signals
    'verified_business' => true,
    'has_reviews' => true,
    'average_rating' => 4.7,
    'external_citations' => ['wikipedia.org', 'industry-site.com'],
    'certifications' => ['ISO 9001', 'BBB Accredited'],

    // Technical optimization
    'mobile_friendly' => true,
    'page_speed_score' => 85,
    'https' => true,
    'has_sitemap' => true,
    'has_robots_txt' => true,

    // Content freshness
    'last_updated' => '2025-01-15',
    'update_frequency' => 'regular',

    // Comprehensiveness
    'has_faq' => true,
    'has_howto' => true,
    'content_formats' => ['text', 'images', 'videos'],
    'detailed_descriptions' => true,

    // Citation potential
    'unique_content' => true,
    'has_statistics' => true,
    'original_research' => false,
    'expert_quotes' => true
]);

// Use the results
echo "GEO Score: " . $assessment['overall_score'] . "/100\n";
echo "Grade: " . $assessment['grade'] . "\n";
echo "AI Readiness: " . $assessment['ai_readiness_level'] . "\n";

// Display recommendations
foreach ($assessment['recommendations'] as $recommendation) {
    echo "- $recommendation\n";
}
```

### Score Interpretation
- **90-100 (A+)**: Excellent - Highly optimized for AI discovery
- **80-89 (A)**: Very Good - Well-positioned for AI citations
- **70-79 (B)**: Good - Solid foundation with room for improvement
- **60-69 (C)**: Fair - Some optimization needed
- **50-59 (D)**: Poor - Significant improvements required
- **Below 50 (F)**: Critical - Urgent optimization needed

## Real-World Examples

### Restaurant Implementation
```php
$geo = new GEOOptimizer(['cache_enabled' => true]);

// Restaurant data
$restaurantData = [
    'business_name' => 'Bella Italia Ristorante',
    'business_type' => 'Restaurant',
    'description' => 'Authentic Italian cuisine in the heart of downtown',
    'cuisine_type' => 'Italian',
    'phone' => '(555) 123-4567',
    'email' => 'info@bellaitalia.com',
    'website' => 'https://bellaitalia.com',
    'menu_url' => 'https://bellaitalia.com/menu',
    'street_address' => '789 Pasta Lane',
    'city' => 'Chicago',
    'state' => 'IL',
    'postal_code' => '60601',
    'country' => 'US',
    'latitude' => 41.8781,
    'longitude' => -87.6298,
    'price_range' => '$$',
    'services' => ['Dine-in', 'Takeout', 'Delivery', 'Catering'],
    'payment_methods' => ['Cash', 'Credit Cards', 'Mobile Payments'],
    'hours' => [
        'Monday' => '11:00 AM - 10:00 PM',
        'Tuesday' => '11:00 AM - 10:00 PM',
        'Wednesday' => '11:00 AM - 10:00 PM',
        'Thursday' => '11:00 AM - 11:00 PM',
        'Friday' => '11:00 AM - 12:00 AM',
        'Saturday' => '10:00 AM - 12:00 AM',
        'Sunday' => '10:00 AM - 9:00 PM'
    ],
    'dietary_options' => ['Vegetarian', 'Vegan', 'Gluten-Free'],
    'delivery' => true,
    'takeout' => true
];

// Generate everything
$result = $geo->optimize($restaurantData);

// Output files
file_put_contents('public/llms.txt', $result['llms_txt']);
file_put_contents('includes/schema.json', json_encode($result['schema']));
```

### E-commerce Product Page
```php
// Product optimization
$productData = [
    'name' => 'Smart Home Security Camera',
    'description' => 'AI-powered security camera with night vision',
    'brand' => 'SecureHome',
    'sku' => 'SH-CAM-001',
    'category' => 'Electronics > Security > Cameras',
    'images' => [
        'https://example.com/images/camera-front.jpg',
        'https://example.com/images/camera-side.jpg'
    ],
    'offers' => [
        [
            'price' => 199.99,
            'currency' => 'USD',
            'availability' => 'InStock',
            'seller' => 'TechStore Online',
            'url' => 'https://example.com/products/smart-camera',
            'valid_from' => '2025-01-01',
            'valid_through' => '2025-12-31'
        ]
    ],
    'specifications' => [
        'Resolution' => '4K Ultra HD',
        'Night Vision' => 'Up to 30 feet',
        'Storage' => 'Cloud & Local',
        'Power' => 'Wired/Battery',
        'Weather Resistant' => 'IP65'
    ],
    'rating' => [
        'value' => 4.6,
        'count' => 1847,
        'best' => 5,
        'worst' => 1
    ],
    'reviews' => [
        [
            'author' => 'Jane Doe',
            'rating' => 5,
            'body' => 'Excellent camera with crystal clear footage!',
            'date' => '2025-01-10'
        ]
    ]
];

$productSchema = $geo->generateSchema('Product', $productData);
```

### Professional Service Website
```php
// Law firm implementation
$lawFirmData = [
    'business_name' => 'Smith & Associates Law Firm',
    'business_type' => 'LegalService',
    'description' => 'Experienced legal representation for personal injury and family law',
    'practice_areas' => [
        'Personal Injury',
        'Family Law',
        'Estate Planning',
        'Criminal Defense',
        'Business Law'
    ],
    'attorneys' => [
        ['name' => 'John Smith, Esq.', 'credentials' => 'JD, Harvard Law School'],
        ['name' => 'Sarah Johnson, Esq.', 'credentials' => 'JD, Yale Law School']
    ],
    'phone' => '(555) LAW-FIRM',
    'email' => 'contact@smithlaw.com',
    'website' => 'https://smithlaw.com',
    'street_address' => '100 Legal Plaza',
    'city' => 'Boston',
    'state' => 'MA',
    'postal_code' => '02108'
];

// Generate comprehensive optimization
$result = $geo->optimize($lawFirmData);

// Also generate FAQ schema for common questions
$faqSchema = $geo->generateSchema('FAQ', [
    ['question' => 'How much does a consultation cost?',
     'answer' => 'We offer free initial consultations for all personal injury cases.'],
    ['question' => 'What types of cases do you handle?',
     'answer' => 'We specialize in personal injury, family law, estate planning, and more.'],
    ['question' => 'How long have you been practicing?',
     'answer' => 'Our firm has over 30 years of combined legal experience.']
]);
```

## Integration Guides

### WordPress Integration
```php
// In your theme's functions.php
add_action('wp_head', 'add_geo_optimization');

function add_geo_optimization() {
    $geo = new GEOOptimizer();

    // Get business data from WordPress options
    $businessData = [
        'business_name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'website' => home_url(),
        // Add more data from your settings
    ];

    $schema = $geo->generateSchema('LocalBusiness', $businessData);

    echo '<script type="application/ld+json">';
    echo json_encode($schema);
    echo '</script>';
}
```

### Laravel Integration
```php
// In a Service Provider
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GEOOptimizer\GEOOptimizer;

class GEOServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(GEOOptimizer::class, function ($app) {
            return new GEOOptimizer([
                'cache_enabled' => config('geo.cache_enabled'),
                'cache' => [
                    'adapter' => 'redis',
                    'host' => config('database.redis.default.host'),
                    'port' => config('database.redis.default.port'),
                    'password' => config('database.redis.default.password'),
                ]
            ]);
        });
    }
}

// In a controller
public function generateLLMSTxt(GEOOptimizer $geo)
{
    $businessData = Business::first()->toArray();
    $llmsTxt = $geo->generateLLMSTxt($businessData);

    return response($llmsTxt)
        ->header('Content-Type', 'text/plain');
}
```

## Advanced Usage

### Custom Templates
Create your own industry-specific templates:

```php
use GEOOptimizer\LLMSTxt\Generator;

$generator = new Generator();

// Create custom template
$customTemplate = <<<TEMPLATE
# {{ business_name }}

## About Our Services
{{ description }}

## Specializations
{% for specialty in specialties %}
- {{ specialty }}
{% endfor %}

## Contact Information
- Phone: {{ phone }}
- Email: {{ email }}
- Address: {{ full_address }}

## Business Hours
{% for day, hours in business_hours %}
{{ day }}: {{ hours }}
{% endfor %}

Last Updated: {{ generated_date }}
TEMPLATE;

$generator->createCustomTemplate('my-industry', $customTemplate);

// Use custom template
$llmsTxt = $generator->generate($businessData, 'my-industry');
```

### Batch Processing
Process multiple businesses or pages:

```php
$businesses = [
    ['name' => 'Business 1', 'industry' => 'restaurant', ...],
    ['name' => 'Business 2', 'industry' => 'retail', ...],
    ['name' => 'Business 3', 'industry' => 'medical', ...]
];

$results = [];
foreach ($businesses as $business) {
    $results[$business['name']] = $geo->optimize($business);

    // Save each llms.txt
    $filename = 'output/' . slugify($business['name']) . '_llms.txt';
    file_put_contents($filename, $results[$business['name']]['llms_txt']);
}
```

### Content Analysis Pipeline
Analyze and improve existing content:

```php
use GEOOptimizer\Analysis\ContentAnalyzer;

$analyzer = new ContentAnalyzer([
    'min_word_count' => 500,
    'target_keyword_density' => 0.025,
    'enable_readability' => true
]);

// Analyze existing page
$content = file_get_contents('https://example.com/page');
$analysis = $analyzer->analyzeForGEO($content);

if ($analysis['geo_score'] < 70) {
    echo "Content needs optimization:\n";
    foreach ($analysis['recommendations'] as $rec) {
        echo "- $rec\n";
    }

    // Suggest authority keywords to add
    echo "\nConsider adding these authority signals:\n";
    $suggestedKeywords = [
        'industry-leading', 'certified', 'award-winning',
        'trusted', 'established', 'proven track record'
    ];

    foreach ($suggestedKeywords as $keyword) {
        if (stripos($content, $keyword) === false) {
            echo "- $keyword\n";
        }
    }
}
```

### Monitoring and Reporting
Track your GEO optimization over time:

```php
use GEOOptimizer\Analytics\GEOReadinessScore;

$scorer = new GEOReadinessScore();

// Weekly monitoring
$sites = [
    'https://site1.com' => ['industry' => 'restaurant'],
    'https://site2.com' => ['industry' => 'legal'],
    'https://site3.com' => ['industry' => 'medical']
];

$report = [];
foreach ($sites as $url => $data) {
    // Fetch and analyze site data
    $siteData = fetchSiteData($url); // Your implementation

    $score = $scorer->calculate($siteData);

    $report[] = [
        'site' => $url,
        'score' => $score['overall_score'],
        'grade' => $score['grade'],
        'trend' => compareWithLastWeek($url, $score['overall_score']),
        'top_issue' => $score['recommendations'][0] ?? 'None'
    ];
}

// Generate report
generateReport($report); // Your reporting implementation
```

## Troubleshooting

### Common Issues

#### 1. Cache Directory Not Writable
```php
// Error: Cannot create cache directory
// Solution: Ensure directory is writable
chmod 755 /path/to/cache
chown www-data:www-data /path/to/cache
```

#### 2. Missing Templates
```php
// Error: Template not found
// Solution: Verify templates directory
$config = [
    'templates_path' => __DIR__ . '/vendor/geooptimizer/templates'
];
$geo = new GEOOptimizer($config);
```

#### 3. Redis Connection Failed
```php
// Error: Redis connection failed
// Solution: Check Redis configuration
$config = [
    'cache' => [
        'adapter' => 'redis',
        'host' => '127.0.0.1',  // Verify host
        'port' => 6379,         // Verify port
        'password' => null      // Add if required
    ]
];
```

#### 4. Schema Validation Errors
```php
// Error: Invalid schema type
// Solution: Use supported types
$supportedTypes = $schemaGen->getSupportedTypes();
print_r($supportedTypes);
```

### Performance Optimization

#### Enable Caching
```php
// Always enable caching in production
$geo = new GEOOptimizer([
    'cache_enabled' => true,
    'cache_ttl' => 7200  // 2 hours
]);
```

#### Use Redis for High Traffic
```php
// For high-traffic sites
$config = [
    'cache' => [
        'adapter' => 'redis',
        'host' => 'redis-server.local'
    ]
];
```

#### Batch Process During Off-Peak
```php
// Schedule batch operations
if (date('H') >= 2 && date('H') <= 5) {  // 2 AM - 5 AM
    processAllBusinesses();
}
```

### Debugging

#### Enable Debug Mode
```php
// Get detailed error information
$geo = new GEOOptimizer([
    'debug' => true,
    'log_path' => '/var/log/geo-optimizer.log'
]);
```

#### Check Component Status
```php
// Verify components are working
try {
    $test = $geo->generateSchema('LocalBusiness', [
        'business_name' => 'Test',
        'description' => 'Test business'
    ]);
    echo "Schema generator: OK\n";
} catch (\Exception $e) {
    echo "Schema generator: FAILED - " . $e->getMessage() . "\n";
}
```

## Support and Resources

- **Documentation**: [GitHub Wiki](https://github.com/tedrubin80/geolibrary/wiki)
- **Issues**: [GitHub Issues](https://github.com/tedrubin80/geolibrary/issues)
- **Examples**: See the `examples/` directory
- **Community**: Join our Discord server (coming soon)

## License

This library is open-source software licensed under the MIT license.