<?php

require_once 'vendor/autoload.php';

use GEOOptimizer\GEOOptimizer;

// Example: Complete GEO optimization for a client website

// 1. Initialize the library
$geo = new GEOOptimizer([
    'cache_enabled' => true,
    'validation_strict' => true
]);

// 2. Prepare client business data
$businessData = [
    // Basic Information
    'name' => 'Springfield Web Solutions',
    'description' => 'Professional web development and digital marketing services for small businesses in Springfield, Illinois',
    'industry' => 'Web Development',
    'founded' => '2020',
    'location' => 'Springfield, Illinois',
    
    // Services & Expertise
    'services' => [
        'Custom Website Development',
        'E-commerce Solutions',
        'SEO Optimization',
        'Digital Marketing',
        'Website Maintenance'
    ],
    'specialties' => [
        'PHP Development',
        'Bootstrap Framework',
        'WordPress Customization',
        'Local SEO'
    ],
    'target_market' => 'Small to medium businesses in Central Illinois',
    'service_area' => 'Springfield, Decatur, Bloomington, Champaign, Illinois',
    
    // Authority Signals
    'certifications' => [
        'Google Analytics Certified',
        'Google Ads Certified',
        'WordPress Developer Certification'
    ],
    'awards' => [
        'Best Local Web Developer 2023 - Springfield Business Journal',
        'Top Digital Agency - Central Illinois Chamber'
    ],
    'years_experience' => '8',
    'team_size' => '5-10 employees',
    
    // Contact Information
    'website' => 'https://springfieldwebsolutions.com',
    'phone' => '(217) 555-0123',
    'email' => 'info@springfieldwebsolutions.com',
    'address' => [
        'street' => '123 Capitol Avenue',
        'city' => 'Springfield',
        'state' => 'Illinois',
        'zip' => '62701',
        'country' => 'United States'
    ],
    
    // Content Guidelines
    'brand_voice' => 'Professional, helpful, and approachable',
    'key_messages' => [
        'Local expertise with global standards',
        'Affordable web solutions for small business',
        'Personal service and ongoing support'
    ],
    'avoid_topics' => ['Politics', 'Controversial subjects'],
    'language_style' => 'Clear,