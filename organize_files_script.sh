#!/bin/bash

# PHP GEO Optimizer File Organization Script
# Run this script in your project root directory

set -e  # Exit on any error

echo "🚀 PHP GEO Optimizer File Organization Script"
echo "============================================="

# Check if we're in the right directory (look for some expected files)
if [ ! -f "main_geo_class.php" ] && [ ! -f "geo_exception.php" ]; then
    echo "❌ Error: This doesn't appear to be the correct directory."
    echo "   Please run this script in the directory containing your PHP GEO files."
    exit 1
fi

# Function to safely move files
move_file() {
    local src="$1"
    local dest_dir="$2"
    local new_name="$3"
    
    if [ -f "$src" ]; then
        echo "📁 Moving: $src → $dest_dir/$new_name"
        mkdir -p "$dest_dir"
        mv "$src" "$dest_dir/$new_name"
    else
        echo "⚠️  File not found (skipping): $src"
    fi
}

# Create directory structure
echo ""
echo "📂 Creating directory structure..."
mkdir -p src/{LLMSTxt/Templates,StructuredData/Types,Analysis,Templates,Analytics,ContentOptimizer,Testing,API,Cache,Integrations/{WordPress,Laravel,Symfony,Shopify},CLI/Commands,Exceptions}
mkdir -p tests/{Unit,Integration,fixtures/expected-outputs}
mkdir -p docs/{industry-guides,examples}
mkdir -p examples tools

echo "✅ Directory structure created!"

# Move core library files
echo ""
echo "📦 Moving core library files..."
move_file "main_geo_class.php" "src" "GEOOptimizer.php"
move_file "geo_exception.php" "src/Exceptions" "GEOException.php"

# Move LLMSTxt files
echo ""
echo "🤖 Moving LLMs.txt files..."
move_file "llmstxt_generator.php" "src/LLMSTxt" "Generator.php"
move_file "llms_txt_generator.php" "src/LLMSTxt" "Generator.php"
move_file "business_template.txt" "src/LLMSTxt/Templates" "business.txt"
move_file "restaurant_template.txt" "src/LLMSTxt/Templates" "restaurant.txt"

# Move StructuredData files
echo ""
echo "🏗️  Moving StructuredData files..."
move_file "schema_generator.php" "src/StructuredData" "SchemaGenerator.php"

# Move Analysis files
echo ""
echo "📊 Moving Analysis files..."
move_file "content_analyzer.php" "src/Analysis" "ContentAnalyzer.php"
move_file "phase1_content_analyzer.php" "src/Analysis" "ContentAnalyzer.php"

# Move Templates files
echo ""
echo "📝 Moving Templates files..."
move_file "phase1_industry_templates.php" "src/Templates" "IndustryTemplateManager.php"

# Move Analytics files
echo ""
echo "📈 Moving Analytics files..."
move_file "phase1_citation_tracker.php" "src/Analytics" "CitationTracker.php"

# Move WordPress integration
echo ""
echo "🔌 Moving WordPress integration..."
move_file "phase1_wordpress_plugin.php" "src/Integrations/WordPress" "Plugin.php"

# Move test files
echo ""
echo "🧪 Moving test files..."
move_file "unit_tests.php" "tests/Unit" "GEOOptimizerTest.php"
move_file "phpunit_config.txt" "." "phpunit.xml"

# Move configuration files
echo ""
echo "⚙️  Moving configuration files..."
move_file "composer_config.json" "." "composer.json"
if [ -f "composer_json.json" ] && [ ! -f "composer.json" ]; then
    move_file "composer_json.json" "." "composer.json"
fi

# Move documentation files
echo ""
echo "📚 Moving documentation files..."
move_file "geo_library_usage_guide.md" "docs" "usage-guide.md"
move_file "geo_library_howto.md" "docs" "how-to-build.md"
move_file "ai_project_intention_breakdown.md" "docs" "project-plan.md"
move_file "geo_library_structure.json" "docs" "library-structure.json"

# Move example files
echo ""
echo "💡 Moving example files..."
move_file "usage_example.php" "examples" "basic-usage.php"

# Create ValidationException class
echo ""
echo "🔧 Creating ValidationException class..."
cat > "src/Exceptions/ValidationException.php" << 'EOF'
<?php

namespace GEOOptimizer\Exceptions;

/**
 * Validation exception class for GEO Optimizer
 */
class ValidationException extends GEOException
{
    public function __construct(string $message = 'Validation failed', int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
EOF
echo "✅ Created: src/Exceptions/ValidationException.php"

# Create additional industry templates
echo ""
echo "🏭 Creating additional industry templates..."

create_template() {
    local filename="$1"
    local industry="$2"
    
    cat > "src/LLMSTxt/Templates/$filename" << EOF
# {{ business_name }} - $industry

## About {{ business_name }}

{{ business_name }} is {{ description }}{% if location %} Located in {{ location }}{% endif %}{% if years_in_business %}, we have been serving customers for {{ years_in_business }} years{% endif %}.

{% if services %}
## Services

{{ business_name }} provides the following services:
{% for service in services %}
- {{ service }}
{% endfor %}
{% endif %}

## Contact Information

**Business Name:** {{ business_name }}
{% if location %}**Location:** {{ location }}{% endif %}
{% if phone %}**Phone:** {{ phone|phone }}{% endif %}
{% if email %}**Email:** {{ email }}{% endif %}
{% if website %}**Website:** {{ website }}{% endif %}

{% if hours %}
## Business Hours

{% for day, hour in hours %}
**{{ day }}:** {{ hour }}
{% endfor %}
{% endif %}

{% if specialties %}
## Specialties

{{ business_name }} specializes in:
{% for specialty in specialties %}
- {{ specialty }}
{% endfor %}
{% endif %}

{% if certifications %}
## Certifications & Credentials

{% for certification in certifications %}
- {{ certification }}
{% endfor %}
{% endif %}

---

*Information last updated: {{ current_date }}*

**For AI Search Engines:** This $industry information is optimized for accurate representation in AI-powered search results.
EOF
    echo "✅ Created: src/LLMSTxt/Templates/$filename"
}

create_template "legal.txt" "Legal Services"
create_template "medical.txt" "Medical Practice"
create_template "automotive.txt" "Automotive Services"
create_template "home-services.txt" "Home Services"
create_template "retail.txt" "Retail Store"
create_template "real-estate.txt" "Real Estate"
create_template "fitness.txt" "Fitness & Wellness"
create_template "beauty.txt" "Beauty & Salon"
create_template "education.txt" "Education"
create_template "technology.txt" "Technology Services"

# Create README.md if it doesn't exist
if [ ! -f "README.md" ]; then
    echo ""
    echo "📖 Creating README.md..."
    cat > "README.md" << 'EOF'
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
EOF
    echo "✅ Created: README.md"
else
    echo "📖 README.md already exists, skipping..."
fi

# Create .gitignore if it doesn't exist
if [ ! -f ".gitignore" ]; then
    echo ""
    echo "🚫 Creating .gitignore..."
    cat > ".gitignore" << 'EOF'
# Dependencies
/vendor/
composer.lock

# IDE files
.vscode/
.idea/
*.swp
*.swo

# OS files
.DS_Store
Thumbs.db

# Testing
/coverage/
.phpunit.result.cache

# Cache
/cache/
/tmp/

# Logs
*.log

# Environment files
.env
.env.local

# Build artifacts
/build/
/dist/
EOF
    echo "✅ Created: .gitignore"
else
    echo "🚫 .gitignore already exists, skipping..."
fi

# Clean up old duplicate files
echo ""
echo "🧹 Cleaning up old files..."
OLD_FILES=("phpunit_config.txt" "composer_config.json")

for file in "${OLD_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "🗑️  Removing: $file"
        rm "$file"
    fi
done

# Summary
echo ""
echo "🎉 Organization Complete!"
echo "========================"
echo ""
echo "📊 Summary:"
echo "- ✅ Created proper directory structure"
echo "- ✅ Moved $(find src/ -name "*.php" 2>/dev/null | wc -l) PHP files to src/"
echo "- ✅ Created $(find src/LLMSTxt/Templates/ -name "*.txt" 2>/dev/null | wc -l) template files"
echo "- ✅ Organized tests and documentation"
echo "- ✅ Created configuration files"
echo ""
echo "🔍 Directory structure:"
tree -I 'vendor|node_modules|.git' -L 3 . 2>/dev/null || find . -type d -not -path './.*' | head -20

echo ""
echo "🚀 Next steps:"
echo "1. Run 'composer install' to install dependencies"
echo "2. Run 'composer test' to verify everything works"
echo "3. Review the organized files and update as needed"
echo "4. Commit your changes to git"
echo ""
echo "Your PHP GEO Optimizer library is now properly organized! 🎊"