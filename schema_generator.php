<?php

namespace GEOOptimizer\StructuredData;

use Spatie\SchemaOrg\Schema;
use GEOOptimizer\Exceptions\ValidationException;

/**
 * Schema.org Structured Data Generator
 * 
 * Generates JSON-LD structured data optimized for AI engines
 */
class SchemaGenerator
{
    private $config;
    private $schemaTypes;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeSchemaTypes();
    }

    /**
     * Generate structured data for specified type
     *
     * @param string $type Schema type
     * @param array $data Business/content data
     * @return string JSON-LD structured data
     */
    public function generate(string $type, array $data): string
    {
        if (!isset($this->schemaTypes[$type])) {
            throw new ValidationException("Unsupported schema type: {$type}");
        }

        $method = 'generate' . $type;
        
        if (!method_exists($this, $method)) {
            throw new ValidationException("Generator method not found for type: {$type}");
        }

        $schema = $this->$method($data);
        
        return $schema->toScript();
    }

    /**
     * Generate LocalBusiness schema
     *
     * @param array $data Business data
     * @return \Spatie\SchemaOrg\LocalBusiness
     */
    private function generateLocalBusiness(array $data)
    {
        $business = Schema::localBusiness()
            ->name($data['name'])
            ->description($data['description']);

        // Add address if provided
        if (isset($data['address'])) {
            $address = Schema::postalAddress();
            
            if (isset($data['address']['street'])) {
                $address->streetAddress($data['address']['street']);
            }
            if (isset($data['address']['city'])) {
                $address->addressLocality($data['address']['city']);
            }
            if (isset($data['address']['state'])) {
                $address->addressRegion($data['address']['state']);
            }
            if (isset($data['address']['zip'])) {
                $address->postalCode($data['address']['zip']);
            }
            if (isset($data['address']['country'])) {
                $address->addressCountry($data['address']['country']);
            }
            
            $business->address($address);
        }

        // Add contact information
        if (isset($data['phone'])) {
            $business->telephone($data['phone']);
        }
        if (isset($data['email'])) {
            $business->email($data['email']);
        }
        if (isset($data['website'])) {
            $business->url($data['website']);
        }

        // Add business hours
        if (isset($data['hours'])) {
            $openingHours = [];
            foreach ($data['hours'] as $day => $hours) {
                if ($hours !== 'closed') {
                    $openingHours[] = $day . ' ' . $hours;
                }
            }
            if (!empty($openingHours)) {
                $business->openingHours($openingHours);
            }
        }

        // Add services
        if (isset($data['services']) && is_array($data['services'])) {
            $serviceList = [];
            foreach ($data['services'] as $service) {
                if (is_string($service)) {
                    $serviceList[] = $service;
                } elseif (is_array($service) && isset($service['name'])) {
                    $serviceList[] = $service['name'];
                }
            }
            if (!empty($serviceList)) {
                $business->hasOfferCatalog(
                    Schema::offerCatalog()->name('Services')->itemListElement($serviceList)
                );
            }
        }

        // Add ratings if available
        if (isset($data['rating'])) {
            $rating = Schema::aggregateRating()
                ->ratingValue($data['rating']['value'])
                ->bestRating($data['rating']['best'] ?? 5)
                ->worstRating($data['rating']['worst'] ?? 1);
                
            if (isset($data['rating']['count'])) {
                $rating->ratingCount($data['rating']['count']);
            }
            
            $business->aggregateRating($rating);
        }

        return $business;
    }

    /**
     * Generate Organization schema
     *
     * @param array $data Organization data
     * @return \Spatie\SchemaOrg\Organization
     */
    private function generateOrganization(array $data)
    {
        $org = Schema::organization()
            ->name($data['name'])
            ->description($data['description']);

        if (isset($data['website'])) {
            $org->url($data['website']);
        }

        if (isset($data['logo'])) {
            $org->logo($data['logo']);
        }

        if (isset($data['founded'])) {
            $org->foundingDate($data['founded']);
        }

        if (isset($data['employees'])) {
            $org->numberOfEmployees($data['employees']);
        }

        return $org;
    }

    /**
     * Generate Service schema
     *
     * @param array $data Service data
     * @return \Spatie\SchemaOrg\Service
     */
    private function generateService(array $data)
    {
        $service = Schema::service()
            ->name($data['name'])
            ->description($data['description']);

        if (isset($data['provider'])) {
            $provider = is_array($data['provider']) 
                ? $this->generateOrganization($data['provider'])
                : Schema::organization()->name($data['provider']);
                
            $service->provider($provider);
        }

        if (isset($data['area_served'])) {
            $service->areaServed($data['area_served']);
        }

        if (isset($data['category'])) {
            $service->serviceType($data['category']);
        }

        return $service;
    }

    /**
     * Generate FAQ schema
     *
     * @param array $data FAQ data
     * @return \Spatie\SchemaOrg\FAQPage
     */
    private function generateFAQ(array $data)
    {
        $faqPage = Schema::fAQPage();
        $questions = [];

        foreach ($data['faqs'] as $faq) {
            $question = Schema::question()
                ->name($faq['question'])
                ->acceptedAnswer(
                    Schema::answer()->text($faq['answer'])
                );
            $questions[] = $question;
        }

        $faqPage->mainEntity($questions);

        return $faqPage;
    }

    /**
     * Generate Article schema
     *
     * @param array $data Article data
     * @return \Spatie\SchemaOrg\Article
     */
    private function generateArticle(array $data)
    {
        $article = Schema::article()
            ->headline($data['title'])
            ->description($data['description']);

        if (isset($data['author'])) {
            $author = is_array($data['author'])
                ? Schema::person()->name($data['author']['name'])
                : Schema::person()->name($data['author']);
            $article->author($author);
        }

        if (isset($data['published'])) {
            $article->datePublished($data['published']);
        }

        if (isset($data['modified'])) {
            $article->dateModified($data['modified']);
        }

        if (isset($data['image'])) {
            $article->image($data['image']);
        }

        return $article;
    }

    /**
     * Generate multiple schemas at once
     *
     * @param array $schemas Array of [type => data] pairs
     * @return array Generated schemas
     */
    public function generateMultiple(array $schemas): array
    {
        $results = [];
        
        foreach ($schemas as $type => $data) {
            $results[$type] = $this->generate($type, $data);
        }
        
        return $results;
    }

    /**
     * Initialize supported schema types
     */
    private function initializeSchemaTypes(): void
    {
        $this->schemaTypes = [
            'LocalBusiness' => 'Local business information',
            'Organization' => 'Organization/company information',
            'Service' => 'Service offerings',
            'FAQ' => 'Frequently asked questions',
            'Article' => 'Article/blog post content',
            'Product' => 'Product information',
            'Event' => 'Event information',
            'Review' => 'Review and rating data'
        ];
    }

    /**
     * Get supported schema types
     *
     * @return array Supported types
     */
    public function getSupportedTypes(): array
    {
        return array_keys($this->schemaTypes);
    }

    /**
     * Validate schema data
     *
     * @param string $type Schema type
     * @param array $data Data to validate
     * @return bool Validation result
     */
    public function validateData(string $type, array $data): bool
    {
        $required = $this->getRequiredFields($type);
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get required fields for schema type
     *
     * @param string $type Schema type
     * @return array Required fields
     */
    private function getRequiredFields(string $type): array
    {
        $requirements = [
            'LocalBusiness' => ['name', 'description'],
            'Organization' => ['name', 'description'],
            'Service' => ['name', 'description'],
            'FAQ' => ['faqs'],
            'Article' => ['title', 'description']
        ];

        return $requirements[$type] ?? [];
    }
}