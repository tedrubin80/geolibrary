<?php

namespace GEOOptimizer\StructuredData;

use Spatie\SchemaOrg\Schema;
use GEOOptimizer\Exceptions\ValidationException;

/**
 * Schema.org Structured Data Generator
 * 
 * Generates JSON-LD structured data optimized for AI search engines
 */
class SchemaGenerator
{
    private $supportedTypes = [
        'LocalBusiness',
        'Restaurant', 
        'LegalService',
        'MedicalBusiness',
        'AutoRepair',
        'HomeAndConstructionBusiness',
        'Store',
        'RealEstateAgent',
        'HealthAndBeautyBusiness',
        'EducationalOrganization',
        'ProfessionalService'
    ];

    /**
     * Generate structured data schema
     */
    public function generate(string $type, array $data): array
    {
        if (!in_array($type, $this->supportedTypes)) {
            throw new ValidationException("Unsupported schema type: {$type}");
        }

        $methodName = 'generate' . $type;
        if (!method_exists($this, $methodName)) {
            throw new ValidationException("Generator method not found for type: {$type}");
        }

        return $this->$methodName($data);
    }

    /**
     * Generate LocalBusiness schema
     */
    protected function generateLocalBusiness(array $data): array
    {
        $business = Schema::localBusiness()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add address if provided
        if (!empty($data['address'])) {
            $address = Schema::postalAddress();
            
            if (!empty($data['street_address'])) {
                $address->streetAddress($data['street_address']);
            }
            if (!empty($data['city'])) {
                $address->addressLocality($data['city']);
            }
            if (!empty($data['state'])) {
                $address->addressRegion($data['state']);
            }
            if (!empty($data['postal_code'])) {
                $address->postalCode($data['postal_code']);
            }
            if (!empty($data['country'])) {
                $address->addressCountry($data['country']);
            }

            $business->address($address);
        }

        // Add contact information
        if (!empty($data['phone'])) {
            $business->telephone($data['phone']);
        }
        if (!empty($data['email'])) {
            $business->email($data['email']);
        }
        if (!empty($data['website'])) {
            $business->url($data['website']);
        }

        // Add business hours
        if (!empty($data['hours'])) {
            $openingHours = [];
            foreach ($data['hours'] as $day => $hours) {
                if ($hours !== 'Closed') {
                    $openingHours[] = $day . ' ' . $hours;
                }
            }
            if (!empty($openingHours)) {
                $business->openingHours($openingHours);
            }
        }

        // Add geo coordinates if provided
        if (!empty($data['latitude']) && !empty($data['longitude'])) {
            $geo = Schema::geoCoordinates()
                ->latitude($data['latitude'])
                ->longitude($data['longitude']);
            $business->geo($geo);
        }

        // Add services
        if (!empty($data['services'])) {
            $business->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($service) {
                        return Schema::offer()->itemOffered(
                            Schema::service()->name($service)
                        );
                    }, $data['services'])
                )
            );
        }

        // Add payment methods
        if (!empty($data['payment_methods'])) {
            $business->paymentAccepted($data['payment_methods']);
        }

        // Add price range
        if (!empty($data['price_range'])) {
            $business->priceRange($data['price_range']);
        }

        return $business->toArray();
    }

    /**
     * Generate Restaurant schema
     */
    protected function generateRestaurant(array $data): array
    {
        $restaurant = Schema::restaurant()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add cuisine type
        if (!empty($data['cuisine_type'])) {
            $restaurant->servesCuisine($data['cuisine_type']);
        }

        // Add menu URL
        if (!empty($data['menu_url'])) {
            $restaurant->hasMenu($data['menu_url']);
        }

        // Add delivery/takeout options
        if (!empty($data['delivery'])) {
            $restaurant->hasDeliveryMethod('http://purl.org/goodrelations/v1#DeliveryModeDirectDownload');
        }
        if (!empty($data['takeout'])) {
            $restaurant->hasDeliveryMethod('http://purl.org/goodrelations/v1#DeliveryModePickup');
        }

        // Add dietary options
        if (!empty($data['dietary_options'])) {
            foreach ($data['dietary_options'] as $option) {
                $restaurant->hasMenuSection(
                    Schema::menuSection()->name($option)
                );
            }
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $restaurant->toArray());
    }

    /**
     * Generate LegalService schema
     */
    protected function generateLegalService(array $data): array
    {
        $legalService = Schema::legalService()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add practice areas
        if (!empty($data['practice_areas'])) {
            $legalService->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($area) {
                        return Schema::offer()->itemOffered(
                            Schema::legalService()->name($area)
                        );
                    }, $data['practice_areas'])
                )
            );
        }

        // Add attorney information
        if (!empty($data['attorneys'])) {
            $attorneys = [];
            foreach ($data['attorneys'] as $attorney) {
                $person = Schema::person()
                    ->name($attorney['name']);
                
                if (!empty($attorney['credentials'])) {
                    $person->hasCredential($attorney['credentials']);
                }
                
                $attorneys[] = $person;
            }
            $legalService->employee($attorneys);
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $legalService->toArray());
    }

    /**
     * Generate MedicalBusiness schema
     */
    protected function generateMedicalBusiness(array $data): array
    {
        $medicalBusiness = Schema::medicalBusiness()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add medical specialties
        if (!empty($data['medical_specialties'])) {
            foreach ($data['medical_specialties'] as $specialty) {
                $medicalBusiness->medicalSpecialty($specialty);
            }
        }

        // Add insurance accepted
        if (!empty($data['insurance_accepted'])) {
            $medicalBusiness->paymentAccepted($data['insurance_accepted']);
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $medicalBusiness->toArray());
    }

    /**
     * Generate FAQ schema
     */
    public function generateFAQ(array $faqs): array
    {
        $faqPage = Schema::faqPage()
            ->mainEntity(
                array_map(function($faq) {
                    return Schema::question()
                        ->name($faq['question'])
                        ->acceptedAnswer(
                            Schema::answer()->text($faq['answer'])
                        );
                }, $faqs)
            );

        return $faqPage->toArray();
    }

    /**
     * Generate Article schema
     */
    public function generateArticle(array $data): array
    {
        $article = Schema::article()
            ->headline($data['title'])
            ->description($data['description'])
            ->datePublished($data['published_date'] ?? date('c'))
            ->dateModified($data['modified_date'] ?? date('c'));

        // Add author
        if (!empty($data['author'])) {
            $article->author(
                Schema::person()->name($data['author'])
            );
        }

        // Add publisher
        if (!empty($data['publisher'])) {
            $article->publisher(
                Schema::organization()->name($data['publisher'])
            );
        }

        // Add image
        if (!empty($data['image'])) {
            $article->image($data['image']);
        }

        return $article->toArray();
    }

    /**
     * Get all supported schema types
     */
    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * Convert schema array to JSON-LD string
     */
    public function toJsonLd(array $schema): string
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
        ];

        return json_encode(array_merge($jsonLd, $schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}