<?php

declare(strict_types=1);

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
    /**
     * @var array<string>
     */
    private array $supportedTypes = [
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     * Generate AutoRepair schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateAutoRepair(array $data): array
    {
        $autoRepair = Schema::autoRepair()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add services offered
        if (!empty($data['services'])) {
            $autoRepair->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($service) {
                        return Schema::offer()->itemOffered(
                            Schema::service()->name($service)
                        );
                    }, $data['services'])
                )
            );
        }

        // Add brands serviced
        if (!empty($data['brands_serviced'])) {
            $autoRepair->brand($data['brands_serviced']);
        }

        // Add certifications
        if (!empty($data['certifications'])) {
            $autoRepair->hasCredential($data['certifications']);
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $autoRepair->toArray());
    }

    /**
     * Generate HomeAndConstructionBusiness schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateHomeAndConstructionBusiness(array $data): array
    {
        $homeBusiness = Schema::homeAndConstructionBusiness()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add service areas
        if (!empty($data['service_areas'])) {
            $homeBusiness->areaServed($data['service_areas']);
        }

        // Add license number
        if (!empty($data['license_number'])) {
            $homeBusiness->hasCredential(
                Schema::educationalOccupationalCredential()
                    ->credentialCategory('License')
                    ->identifier($data['license_number'])
            );
        }

        // Add specialties
        if (!empty($data['specialties'])) {
            $homeBusiness->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($specialty) {
                        return Schema::offer()->itemOffered(
                            Schema::service()->name($specialty)
                        );
                    }, $data['specialties'])
                )
            );
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $homeBusiness->toArray());
    }

    /**
     * Generate Store schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateStore(array $data): array
    {
        $store = Schema::store()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add product categories
        if (!empty($data['product_categories'])) {
            $store->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($category) {
                        return Schema::offerCatalog()->name($category);
                    }, $data['product_categories'])
                )
            );
        }

        // Add brand information
        if (!empty($data['brands_carried'])) {
            $store->brand($data['brands_carried']);
        }

        // Add online store URL
        if (!empty($data['online_store_url'])) {
            $store->sameAs($data['online_store_url']);
        }

        // Add return policy
        if (!empty($data['return_policy'])) {
            $store->hasMerchantReturnPolicy(
                Schema::merchantReturnPolicy()
                    ->returnPolicyCategory($data['return_policy'])
            );
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $store->toArray());
    }

    /**
     * Generate RealEstateAgent schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateRealEstateAgent(array $data): array
    {
        $realEstateAgent = Schema::realEstateAgent()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add service areas
        if (!empty($data['service_areas'])) {
            $realEstateAgent->areaServed($data['service_areas']);
        }

        // Add license number
        if (!empty($data['license_number'])) {
            $realEstateAgent->hasCredential(
                Schema::educationalOccupationalCredential()
                    ->credentialCategory('Real Estate License')
                    ->identifier($data['license_number'])
            );
        }

        // Add specializations
        if (!empty($data['specializations'])) {
            $realEstateAgent->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($specialization) {
                        return Schema::offer()->itemOffered(
                            Schema::service()->name($specialization)
                        );
                    }, $data['specializations'])
                )
            );
        }

        // Add team members
        if (!empty($data['team_members'])) {
            $team = [];
            foreach ($data['team_members'] as $member) {
                $person = Schema::person()->name($member['name']);
                if (!empty($member['title'])) {
                    $person->jobTitle($member['title']);
                }
                $team[] = $person;
            }
            $realEstateAgent->employee($team);
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $realEstateAgent->toArray());
    }

    /**
     * Generate HealthAndBeautyBusiness schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateHealthAndBeautyBusiness(array $data): array
    {
        $healthBeauty = Schema::healthAndBeautyBusiness()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add services offered
        if (!empty($data['services'])) {
            $healthBeauty->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($service) use ($data) {
                        $offer = Schema::offer()->itemOffered(
                            Schema::service()->name($service)
                        );
                        // Add service duration if available
                        if (!empty($data['service_durations'][$service])) {
                            $offer->duration($data['service_durations'][$service]);
                        }
                        // Add service price if available
                        if (!empty($data['service_prices'][$service])) {
                            $offer->price($data['service_prices'][$service]);
                        }
                        return $offer;
                    }, $data['services'])
                )
            );
        }

        // Add practitioners
        if (!empty($data['practitioners'])) {
            $practitioners = [];
            foreach ($data['practitioners'] as $practitioner) {
                $person = Schema::person()->name($practitioner['name']);
                if (!empty($practitioner['specialization'])) {
                    $person->jobTitle($practitioner['specialization']);
                }
                $practitioners[] = $person;
            }
            $healthBeauty->employee($practitioners);
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $healthBeauty->toArray());
    }

    /**
     * Generate EducationalOrganization schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateEducationalOrganization(array $data): array
    {
        $educational = Schema::educationalOrganization()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add education level
        if (!empty($data['education_level'])) {
            $educational->educationalLevel($data['education_level']);
        }

        // Add accreditation
        if (!empty($data['accreditation'])) {
            $educational->hasCredential(
                Schema::educationalOccupationalCredential()
                    ->credentialCategory('Accreditation')
                    ->name($data['accreditation'])
            );
        }

        // Add programs offered
        if (!empty($data['programs'])) {
            $educational->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($program) {
                        return Schema::educationalOccupationalProgram()
                            ->name($program);
                    }, $data['programs'])
                )
            );
        }

        // Add alumni information
        if (!empty($data['alumni_count'])) {
            $educational->alumni(
                Schema::quantitativeValue()->value($data['alumni_count'])
            );
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $educational->toArray());
    }

    /**
     * Generate ProfessionalService schema
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateProfessionalService(array $data): array
    {
        $professional = Schema::professionalService()
            ->name($data['business_name'])
            ->description($data['description']);

        // Add service type
        if (!empty($data['service_type'])) {
            $professional->serviceType($data['service_type']);
        }

        // Add qualifications
        if (!empty($data['qualifications'])) {
            foreach ($data['qualifications'] as $qualification) {
                $professional->hasCredential(
                    Schema::educationalOccupationalCredential()
                        ->credentialCategory('Professional Qualification')
                        ->name($qualification)
                );
            }
        }

        // Add service areas
        if (!empty($data['service_areas'])) {
            $professional->areaServed($data['service_areas']);
        }

        // Add expertise areas
        if (!empty($data['expertise'])) {
            $professional->hasOfferCatalog(
                Schema::offerCatalog()->itemListElement(
                    array_map(function($expertise) {
                        return Schema::offer()->itemOffered(
                            Schema::service()->name($expertise)
                        );
                    }, $data['expertise'])
                )
            );
        }

        // Use LocalBusiness as base and merge
        $localBusiness = $this->generateLocalBusiness($data);
        return array_merge($localBusiness, $professional->toArray());
    }

    /**
     * Generate FAQ schema
     *
     * @param array<int, array<string, string>> $faqs
     * @return array<string, mixed>
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
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
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
     * Generate HowTo schema - Critical for AI responses with instructions
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function generateHowTo(array $data): array
    {
        $howTo = Schema::howTo()
            ->name($data['title'])
            ->description($data['description']);

        // Add estimated cost
        if (!empty($data['estimated_cost'])) {
            $howTo->estimatedCost(
                Schema::monetaryAmount()
                    ->value($data['estimated_cost']['value'])
                    ->currency($data['estimated_cost']['currency'] ?? 'USD')
            );
        }

        // Add time required
        if (!empty($data['time_required'])) {
            $howTo->totalTime($data['time_required']);
        }

        // Add difficulty level
        if (!empty($data['difficulty'])) {
            $howTo->difficulty($data['difficulty']);
        }

        // Add supply list
        if (!empty($data['supplies'])) {
            $supplies = array_map(function($supply) {
                $item = Schema::howToSupply()->name($supply['name']);
                if (!empty($supply['quantity'])) {
                    $item->requiredQuantity($supply['quantity']);
                }
                if (!empty($supply['cost'])) {
                    $item->estimatedCost(
                        Schema::monetaryAmount()
                            ->value($supply['cost'])
                            ->currency('USD')
                    );
                }
                return $item;
            }, $data['supplies']);
            $howTo->supply($supplies);
        }

        // Add tools
        if (!empty($data['tools'])) {
            $tools = array_map(function($tool) {
                return Schema::howToTool()->name($tool);
            }, $data['tools']);
            $howTo->tool($tools);
        }

        // Add steps
        if (!empty($data['steps'])) {
            $steps = array_map(function($step, $index) {
                $howToStep = Schema::howToStep()
                    ->name($step['name'] ?? "Step " . ($index + 1))
                    ->text($step['text']);

                if (!empty($step['image'])) {
                    $howToStep->image($step['image']);
                }
                if (!empty($step['url'])) {
                    $howToStep->url($step['url']);
                }

                return $howToStep;
            }, $data['steps'], array_keys($data['steps']));
            $howTo->step($steps);
        }

        // Add yield/result
        if (!empty($data['yield'])) {
            $howTo->yield($data['yield']);
        }

        return $howTo->toArray();
    }

    /**
     * Generate Product schema - E-commerce support
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function generateProduct(array $data): array
    {
        $product = Schema::product()
            ->name($data['name'])
            ->description($data['description']);

        // Add brand
        if (!empty($data['brand'])) {
            $product->brand(
                Schema::brand()->name($data['brand'])
            );
        }

        // Add SKU
        if (!empty($data['sku'])) {
            $product->sku($data['sku']);
        }

        // Add category
        if (!empty($data['category'])) {
            $product->category($data['category']);
        }

        // Add images
        if (!empty($data['images'])) {
            $product->image($data['images']);
        }

        // Add price/offers
        if (!empty($data['offers'])) {
            $offers = array_map(function($offer) {
                $offerSchema = Schema::offer()
                    ->price($offer['price'])
                    ->priceCurrency($offer['currency'] ?? 'USD');

                if (!empty($offer['availability'])) {
                    $offerSchema->availability($offer['availability']);
                }
                if (!empty($offer['url'])) {
                    $offerSchema->url($offer['url']);
                }
                if (!empty($offer['seller'])) {
                    $offerSchema->seller(
                        Schema::organization()->name($offer['seller'])
                    );
                }
                if (!empty($offer['valid_from'])) {
                    $offerSchema->validFrom($offer['valid_from']);
                }
                if (!empty($offer['valid_through'])) {
                    $offerSchema->validThrough($offer['valid_through']);
                }

                return $offerSchema;
            }, $data['offers']);
            $product->offers($offers);
        }

        // Add aggregate rating
        if (!empty($data['rating'])) {
            $product->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($data['rating']['value'])
                    ->reviewCount($data['rating']['count'])
                    ->bestRating($data['rating']['best'] ?? 5)
                    ->worstRating($data['rating']['worst'] ?? 1)
            );
        }

        // Add reviews
        if (!empty($data['reviews'])) {
            $reviews = array_map(function($review) {
                $reviewSchema = Schema::review()
                    ->reviewBody($review['body'])
                    ->reviewRating(
                        Schema::rating()
                            ->ratingValue($review['rating'])
                            ->bestRating(5)
                            ->worstRating(1)
                    );

                if (!empty($review['author'])) {
                    $reviewSchema->author(
                        Schema::person()->name($review['author'])
                    );
                }
                if (!empty($review['date'])) {
                    $reviewSchema->datePublished($review['date']);
                }

                return $reviewSchema;
            }, $data['reviews']);
            $product->review($reviews);
        }

        // Add specifications
        if (!empty($data['specifications'])) {
            foreach ($data['specifications'] as $spec => $value) {
                $product->additionalProperty(
                    Schema::propertyValue()
                        ->name($spec)
                        ->value($value)
                );
            }
        }

        return $product->toArray();
    }

    /**
     * Generate BreadcrumbList schema - Helps AI understand site structure
     *
     * @param array<int, array<string, string>> $breadcrumbs
     * @return array<string, mixed>
     */
    public function generateBreadcrumbList(array $breadcrumbs): array
    {
        $itemListElement = [];

        foreach ($breadcrumbs as $index => $breadcrumb) {
            $itemListElement[] = Schema::listItem()
                ->position($index + 1)
                ->name($breadcrumb['name'])
                ->item($breadcrumb['url']);
        }

        $breadcrumbList = Schema::breadcrumbList()
            ->itemListElement($itemListElement);

        return $breadcrumbList->toArray();
    }

    /**
     * Generate Review/Rating schema - Social proof signals
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function generateReview(array $data): array
    {
        $review = Schema::review()
            ->itemReviewed(
                Schema::thing()
                    ->name($data['item_name'])
                    ->description($data['item_description'] ?? '')
            )
            ->reviewBody($data['review_body']);

        // Add rating
        if (!empty($data['rating'])) {
            $review->reviewRating(
                Schema::rating()
                    ->ratingValue($data['rating'])
                    ->bestRating($data['best_rating'] ?? 5)
                    ->worstRating($data['worst_rating'] ?? 1)
            );
        }

        // Add author
        if (!empty($data['author'])) {
            $author = Schema::person()->name($data['author']['name']);
            if (!empty($data['author']['url'])) {
                $author->url($data['author']['url']);
            }
            $review->author($author);
        }

        // Add date published
        $review->datePublished($data['date_published'] ?? date('c'));

        // Add publisher if different from author
        if (!empty($data['publisher'])) {
            $review->publisher(
                Schema::organization()->name($data['publisher'])
            );
        }

        return $review->toArray();
    }

    /**
     * Get all supported schema types
     *
     * @return array<string>
     */
    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    /**
     * Convert schema array to JSON-LD string
     *
     * @param array<string, mixed> $schema
     */
    public function toJsonLd(array $schema): string
    {
        $jsonLd = [
            '@context' => 'https://schema.org',
        ];

        return json_encode(array_merge($jsonLd, $schema), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}