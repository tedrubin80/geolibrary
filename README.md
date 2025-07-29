# PHP GEO Optimizer Library

[![Latest Version](https://img.shields.io/packagist/v/geooptimizer/php-geo-optimizer)](https://packagist.org/packages/geooptimizer/php-geo-optimizer)
[![PHP Version](https://img.shields.io/packagist/php-v/geooptimizer/php-geo-optimizer)](https://packagist.org/packages/geooptimizer/php-geo-optimizer)
[![Tests](https://github.com/geooptimizer/php-geo-optimizer/workflows/Tests/badge.svg)](https://github.com/geooptimizer/php-geo-optimizer/actions)
[![License](https://img.shields.io/packagist/l/geooptimizer/php-geo-optimizer)](https://github.com/geooptimizer/php-geo-optimizer/blob/main/LICENSE)

**The first comprehensive PHP library for Generative Engine Optimization (GEO)** - optimizing websites for AI-powered search engines like ChatGPT, Claude, Perplexity, and Google AI Overviews.

## 🚀 What is GEO?

Generative Engine Optimization (GEO) is the practice of optimizing content to improve visibility and accuracy in AI-powered search results.

## ✨ Features

- **🤖 llms.txt Generation**: Create AI-optimized content files
- **📊 Structured Data**: Generate Schema.org markup for AI search engines  
- **📈 Content Analysis**: Analyze content for GEO optimization
- **🏭 Industry Templates**: Pre-built templates for 12+ industries
- **📍 Citation Tracking**: Monitor business mentions across AI platforms
- **🔗 Framework Integration**: WordPress, Laravel, Symfony support

## 📦 Installation

```bash
composer require geooptimizer/php-geo-optimizer
```

## 🏃‍♂️ Quick Start

```php
<?php
require 'vendor/autoload.php';

use GEOOptimizer\GEOOptimizer;

$geo = new GEOOptimizer();

$businessData = [
    'business_name' => 'Your Business',
    'description' => 'What your business does',
    'industry' => 'restaurant',
    'location' => 'Your City, State',
    'services' => ['Service 1', 'Service 2']
];

$results = $geo->optimize($businessData);
file_put_contents('public/llms.txt', $results['llms_txt']);
```

## 📚 Documentation

- [Usage Guide](docs/usage-guide.md)
- [How to Build Guide](docs/how-to-build.md)
- [Project Plan](docs/project-plan.md)

## 🧪 Testing

```bash
composer test
```
