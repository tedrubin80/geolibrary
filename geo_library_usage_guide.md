# How to Use the PHP GEO Optimization Library

## Table of Contents
1. [Quick Start](#quick-start)
2. [Installation](#installation)
3. [Basic Usage](#basic-usage)
4. [Core Features](#core-features)
5. [Real-World Examples](#real-world-examples)
6. [Integration Guides](#integration-guides)
7. [Advanced Usage](#advanced-usage)
8. [Troubleshooting](#troubleshooting)

## Quick Start

### 30-Second Setup
```bash
# Install via Composer
composer require yourname/php-geo-optimizer

# Basic usage
<?php
require_once 'vendor/autoload.php';

use GEOOptimizer\GEOOptimizer;

$geo = new GEOOptimizer();
$result = $geo->optimize([
    'name' => 'Your Business Name',
    'description' => 'What your business does',
    'industry' => 'Your Industry'
]);

// Generate llms.txt file
file_put_contents('public/llms.txt', $result['llms_txt']);
echo $result['structured_data']; // Add to your HTML <head>
```

## Installation

### Via Composer (Recommended)
```bash
composer require yourname/php-geo-optimizer
```

### Manual Installation
1. Download the library from GitHub
2. Include the autoloader:
```php
require_once 'path/to/geo-optimizer/vendor/autoload.php';
```

### Requirements
- PHP 7.4 or higher
- Composer
- Web server with write permissions (for llms.txt generation)

## Basic Usage

### Step 1: Initialize the Library
```php
<?php
use GEOOptimizer\GEOOptimizer;

// Basic initialization
$geo = new GEOOptimizer();

// With custom configuration
$geo = new GEOOptimizer([
    'cache_enabled' => true,
    'validation_strict' => true,
    'templates_path' => '/custom/templates'
]);
```

### Step 2: Prepare Your Business Data
```php
$businessData = [
    // Required fields
    'name' => 'Springfield Web Solutions',
    'description' => 'Professional web development services in Springfield, IL',
    'industry' => 'Web Development',
    
    // Optional but recommended
    'location' => 'Springfield, Illinois',
    'founded' => '2020',
    'website' => 'https://example.com',
    'phone' => '(217) 555-0123',
    'email' => 'info@example.com'
];
```

### Step 3: Generate Optimizations
```php
// Generate all optimizations at once
$results = $geo->optimize($businessData);

// Or generate specific components
$llmsTxt = $geo->generateLLMSTxt($businessData);
$structuredData = $geo->generateStructuredData($businessData);
$metaTags = $geo->optimizeMeta($businessData);
```

## Core Features

### 1. LLMs.txt Generation

#### Basic llms.txt
```php
$businessData = [
    'name' => 'Acme Plumbing',
    'description' => 'Emergency plumbing services in downtown Chicago',
    'industry' => 'Plumbing Services',
    'location' => 'Chicago, Illinois',
    'services' => [
        'Emergency Plumbing',
        'Drain Cleaning',
        'Water Heater Repair',
        'Pipe Installation'
    ],
    'certifications' => [
        'Licensed Illinois Plumber',
        'EPA Certified',
        'BBB A+ Rating'
    ]
];

// Generate and save llms.txt
$geo = new GEOOptimizer();
$llmsTxt = $geo->generateLLMSTxt($businessData);
file_put_contents('public/llms.txt', $llmsTxt);
```

#### Using Different Templates
```php
// E-commerce template
$llmsTxt = $geo->generateLLMSTxt($businessData, 'ecommerce');

// Service business template
$llmsTxt = $geo->generateLLMSTxt($businessData, 'service');

// Professional services template
$llmsTxt = $geo->generateLLMSTxt($businessData, 'professional');
```

### 2. Structured Data Generation

#### Local Business Schema
```php
$businessData = [
    'name' => 'Joe\'s Pizza',
    'description' => 'Authentic New York style pizza',
    'address' => [
        'street' => '123 Main Street',
        'city' => 'Springfield',
        'state' => 'Illinois',
        'zip' => '62701'
    ],
    'hours' => [
        'Monday' => '11:00-22:00',
        'Tuesday' => '11:00-22:00',
        'Wednesday' => '11:00-22:00',
        'Thursday' => '11:00-22:00',
        'Friday' => '11:00-23:00',
        'Saturday' => '11:00-23:00',
        'Sunday' => '12:00-21:00'
    ],
    'rating' => [
        'value' => 4.5,
        'count' => 127
    ]
];

$structuredData = $geo->generateStructuredData($businessData, 'LocalBusiness');

// Add to your HTML head
echo $structuredData;
```

#### FAQ Schema
```php
$faqData = [
    'faqs' => [
        [
            'question' => 'What are your business hours?',
            'answer' => 'We are open Monday through Friday 9am to 5pm, and weekends by appointment.'
        ],
        [
            'question' => 'Do you offer emergency services?',
            'answer' => 'Yes, we provide 24/7 emergency services for urgent issues.'
        ],
        [
            'question' => 'What areas do you serve?',
            'answer' => 'We serve Springfield and surrounding areas within a 50-mile radius.'
        ]
    ]
];

$faqSchema = $geo->generateStructuredData($faqData, 'FAQ');
```

### 3. Meta Tag Optimization
```php
$pageData = [
    'title' => 'Professional Plumbing Services in Springfield',
    'description' => 'Expert plumbing repairs, installations, and emergency services in Springfield, IL. Licensed, insured, and available 24/7.',
    'keywords' => ['plumbing', 'Springfield', 'emergency', 'repair'],
    'page_type' => 'service'
];

$metaTags = $geo->optimizeMeta($pageData);

// Output optimized meta tags
foreach ($metaTags as $tag => $content) {
    echo "<meta name=\"{$tag}\" content=\"{$content}\">\n";
}
```

### 4. Bootstrap Components

#### Service Cards
```php
$services = [
    [
        'name' => 'Website Design',
        'description' => 'Custom responsive websites that convert visitors to customers',
        'category' => 'Design',
        'price' => 1500
    ],
    [
        'name' => 'SEO Optimization',
        'description' => 'Improve your search rankings and drive organic traffic',
        'category' => 'Marketing',
        'price' => 800
    ]
];

$components = $geo->generateComponents(['services' => $services]);
echo $components['service_cards'];
```

#### FAQ Section
```php
$faqs = [
    [
        'question' => 'How long does a typical project take?',
        'answer' => 'Most websites are completed within 2-4 weeks, depending on complexity.'
    ],
    [
        'question' => 'Do you provide ongoing support?',
        'answer' => 'Yes, we offer maintenance packages and ongoing support for all our clients.'
    ]
];

$components = $geo->generateComponents(['faqs' => $faqs]);
echo $components['faq_section'];
```

## Real-World Examples

### Example 1: Restaurant Website
```php
<?php
require_once 'vendor/autoload.php';
use GEOOptimizer\GEOOptimizer;

$restaurantData = [
    'name' => 'Mario\'s Italian Kitchen',
    'description' => 'Authentic Italian cuisine in the heart of downtown Springfield',
    'industry' => 'Restaurant',
    'location' => 'Springfield, Illinois',
    'founded' => '1995',
    'website' => 'https://mariositalian.com',
    'phone' => '(217) 555-MARIO',
    'email' => 'info@mariositalian.com',
    
    'address' => [
        'street' => '456 Capitol Avenue',
        'city' => 'Springfield',
        'state' => 'Illinois',
        'zip' => '62701'
    ],
    
    'services' => [
        'Dine-in',
        'Takeout',
        'Catering',
        'Private Events'
    ],
    
    'specialties' => [
        'Homemade Pasta',
        'Wood-fired Pizza',
        'Traditional Italian Desserts'
    ],
    
    'hours' => [
        'Monday' => 'Closed',
        'Tuesday' => '11:00-21:00',
        'Wednesday' => '11:00-21:00',
        'Thursday' => '11:00-21:00',
        'Friday' => '11:00-22:00',
        'Saturday' => '11:00-22:00',
        'Sunday' => '12:00-20:00'
    ],
    
    'certifications' => [
        'ServSafe Certified',
        'Local Business of the Year 2023'
    ],
    
    'rating' => [
        'value' => 4.7,
        'count' => 284
    ]
];

$geo = new GEOOptimizer();
$results = $geo->optimize($restaurantData);

// Save llms.txt
file_put_contents('public/llms.txt', $results['llms_txt']);

// Output structured data in HTML head
echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo $results['structured_data'];
echo "</head>\n<body>\n";

// Generate components
echo $results['components']['faq_section'] ?? '';
echo "</body>\n</html>";
```

### Example 2: Law Firm Website
```php
$lawFirmData = [
    'name' => 'Smith & Associates Law Firm',
    'description' => 'Experienced personal injury and family law attorneys serving Central Illinois',
    'industry' => 'Legal Services',
    'location' => 'Springfield, Illinois',
    'founded' => '1985',
    
    'services' => [
        'Personal Injury Law',
        'Family Law',
        'Criminal Defense',
        'Estate Planning'
    ],
    
    'specialties' => [
        'Car Accident Claims',
        'Divorce Proceedings',
        'Child Custody',
        'DUI Defense'
    ],
    
    'certifications' => [
        'Illinois Bar Association',
        'American Association for Justice',
        'Super Lawyers 2020-2023'
    ],
    
    'team_size' => '12 attorneys',
    'years_experience' => '38',
    
    'key_messages' => [
        'No fee unless we win your case',
        'Free initial consultation',
        'Aggressive representation with personal service'
    ]
];

$geo = new GEOOptimizer();
$results = $geo->optimize($lawFirmData);
```

### Example 3: E-commerce Integration
```php
$ecommerceData = [
    'name' => 'Springfield Electronics',
    'description' => 'Quality electronics and repair services for Central Illinois',
    'industry' => 'Electronics Retail',
    
    'services' => [
        'Electronics Sales',
        'Phone Repair',
        'Computer Repair',
        'Home Theater Setup'
    ],
    
    'products' => [
        'Smartphones',
        'Laptops',
        'Gaming Systems',
        'Audio Equipment'
    ]
];

// Use e-commerce template
$geo = new GEOOptimizer();
$llmsTxt = $geo->generateLLMSTxt($ecommerceData, 'ecommerce');
```

## Integration Guides

### WordPress Integration
```php
// functions.php
function add_geo_optimization() {
    require_once get_template_directory() . '/vendor/autoload.php';
    
    use GEOOptimizer\GEOOptimizer;
    
    $businessData = [
        'name' => get_bloginfo('name'),
        'description' => get_bloginfo('description'),
        'website' => home_url(),
        // Add more data from WordPress options
    ];
    
    $geo = new GEOOptimizer();
    $structuredData = $geo->generateStructuredData($businessData);
    
    echo $structuredData;
}
add_action('wp_head', 'add_geo_optimization');
```

### Laravel Integration
```php
// Create a service provider
class GEOOptimizerServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(GEOOptimizer::class, function ($app) {
            return new GEOOptimizer(config('geo.options', []));
        });
    }
}

// Controller usage
class HomeController extends Controller
{
    public function index(GEOOptimizer $geo)
    {
        $businessData = config('business.data');
        $geoResults = $geo->optimize($businessData);
        
        return view('home', compact('geoResults'));
    }
}
```

### Static Site Integration
```php
// build-geo.php - Run during build process
<?php
require_once 'vendor/autoload.php';
use GEOOptimizer\GEOOptimizer;

$businessData = json_decode(file_get_contents('data/business.json'), true);
$geo = new GEOOptimizer();
$results = $geo->optimize($businessData);

// Generate llms.txt
file_put_contents('dist/llms.txt', $results['llms_txt']);

// Generate structured data file
file_put_contents('dist/structured-data.json', $results['structured_data']);

// Generate components
file_put_contents('dist/components.html', implode("\n", $results['components']));
```

## Advanced Usage

### Custom Templates
```php
// Create custom llms.txt template
$geo = new GEOOptimizer();
$customTemplate = "
# {{ business_name }} - Custom Template
# Industry: {{ industry }}

## Our Expertise
{{ specialties }}

## Contact
Email: {{ email }}
Phone: {{ phone }}
";

// Save custom template
$generator = new \GEOOptimizer\LLMSTxt\Generator();
$generator->createCustomTemplate('custom', $customTemplate);

// Use custom template
$llmsTxt = $geo->generateLLMSTxt($businessData, 'custom');
```

### Batch Processing
```php
// Process multiple businesses
$businesses = [
    ['name' => 'Business 1', 'description' => '...'],
    ['name' => 'Business 2', 'description' => '...'],
    ['name' => 'Business 3', 'description' => '...']
];

$geo = new GEOOptimizer();
$results = [];

foreach ($businesses as $business) {
    $results[] = $geo->optimize($business);
}

// Bulk save llms.txt files
foreach ($results as $index => $result) {
    file_put_contents("public/business-{$index}-llms.txt", $result['llms_txt']);
}
```

### Configuration Options
```php
$geo = new GEOOptimizer([
    'cache_enabled' => true,          // Enable caching
    'cache_ttl' => 3600,             // Cache duration
    'validation_strict' => false,    // Relaxed validation
    'templates_path' => '/custom',   // Custom templates
    'output_format' => 'html5',      // Output format
    'schema_version' => '13.0'       // Schema.org version
]);
```

## Troubleshooting

### Common Issues

#### Issue: "Required field missing"
```php
// Problem: Missing required fields
$businessData = [
    'name' => 'My Business'
    // Missing 'description' and 'industry'
];

// Solution: Add all required fields
$businessData = [
    'name' => 'My Business',
    'description' => 'What my business does',
    'industry' => 'My Industry'
];
```

#### Issue: "Template not found"
```php
// Problem: Using non-existent template
$llmsTxt = $geo->generateLLMSTxt($data, 'nonexistent');

// Solution: Check available templates
$generator = new \GEOOptimizer\LLMSTxt\Generator();
$templates = $generator->getAvailableTemplates();
print_r($templates);
```

#### Issue: "Permission denied writing llms.txt"
```bash
# Solution: Set proper permissions
chmod 755 public/
chmod 644 public/llms.txt
```

### Debug Mode
```php
// Enable debug mode for troubleshooting
$geo = new GEOOptimizer(['debug' => true]);

try {
    $results = $geo->optimize($businessData);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    echo "Debug info: " . $e->getTraceAsString();
}
```

### Validation
```php
// Validate data before processing
$schemaGenerator = new \GEOOptimizer\StructuredData\SchemaGenerator();

if (!$schemaGenerator->validateData('LocalBusiness', $businessData)) {
    echo "Data validation failed";
    // Check required fields
}
```

## Best Practices

### 1. Data Preparation
- Always include the three required fields: `name`, `description`, `industry`
- Use complete, descriptive content for better AI understanding
- Include location information for local businesses
- Add authority signals (certifications, awards, years in business)

### 2. File Management
- Place llms.txt in your website root (`/llms.txt`)
- Ensure the file is publicly accessible
- Update llms.txt when business information changes
- Include llms.txt in your sitemap.xml

### 3. Structured Data
- Add structured data to every relevant page
- Use the most specific schema type available
- Include as much relevant information as possible
- Validate structured data with Google's Rich Results Test

### 4. Performance
- Enable caching for production use
- Generate static files during build process when possible
- Use appropriate templates for your business type
- Monitor file sizes and loading times

## Need Help?

- **Documentation**: Check the full API documentation
- **Examples**: Browse the examples directory
- **Issues**: Report bugs on GitHub
- **Support**: Contact support for enterprise users

## What's Next?

After implementing basic GEO optimization:

1. Monitor AI citation performance
2. A/B test different content structures
3. Expand to industry-specific optimizations
4. Integrate with analytics tools
5. Consider premium features for advanced optimization