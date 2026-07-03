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
        'ProfessionalService',
        'Event',
        'Course',
        'SoftwareApplication',
        'Organization',
        'Person',
        'Service',
        'JobPosting'
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

        return $this->$methodName($this->normalizeData($data));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeData(array $data): array
    {
        if (empty($data['business_name']) && !empty($data['name'])) {
            $data['business_name'] = $data['name'];
        }

        if (!isset($data['description'])) {
            $data['description'] = '';
        }

        return $data;
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
     * Generate Event schema - Critical for AI event recommendations
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateEvent(array $data): array
    {
        $event = Schema::event()
            ->name($data['name'])
            ->description($data['description']);

        // Add dates
        if (!empty($data['start_date'])) {
            $event->startDate($data['start_date']);
        }
        if (!empty($data['end_date'])) {
            $event->endDate($data['end_date']);
        }

        // Add location
        if (!empty($data['location'])) {
            if (is_array($data['location'])) {
                $place = Schema::place()->name($data['location']['name']);
                if (!empty($data['location']['address'])) {
                    $place->address($data['location']['address']);
                }
                $event->location($place);
            } else {
                $event->location(Schema::place()->name($data['location']));
            }
        }

        // Virtual event location
        if (!empty($data['virtual_location'])) {
            $event->location(
                Schema::virtualLocation()->url($data['virtual_location'])
            );
        }

        // Add organizer
        if (!empty($data['organizer'])) {
            $event->organizer(
                Schema::organization()->name($data['organizer'])
            );
        }

        // Add performer(s)
        if (!empty($data['performers'])) {
            $performers = array_map(function($performer) {
                return Schema::person()->name($performer);
            }, $data['performers']);
            $event->performer($performers);
        }

        // Add ticket offers
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
                if (!empty($offer['valid_from'])) {
                    $offerSchema->validFrom($offer['valid_from']);
                }

                return $offerSchema;
            }, $data['offers']);
            $event->offers($offers);
        }

        // Add event status
        if (!empty($data['event_status'])) {
            $event->eventStatus($data['event_status']);
        }

        // Add event attendance mode
        if (!empty($data['attendance_mode'])) {
            $event->eventAttendanceMode($data['attendance_mode']);
        }

        // Add image
        if (!empty($data['image'])) {
            $event->image($data['image']);
        }

        return $event->toArray();
    }

    /**
     * Generate Course schema - For educational content
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateCourse(array $data): array
    {
        $course = Schema::course()
            ->name($data['name'])
            ->description($data['description']);

        // Add provider
        if (!empty($data['provider'])) {
            $course->provider(
                Schema::organization()->name($data['provider'])
            );
        }

        // Add instructor
        if (!empty($data['instructor'])) {
            $instructor = Schema::person()->name($data['instructor']['name']);
            if (!empty($data['instructor']['credentials'])) {
                $instructor->hasCredential($data['instructor']['credentials']);
            }
            $course->instructor($instructor);
        }

        // Add course prerequisites
        if (!empty($data['prerequisites'])) {
            $course->coursePrerequisites($data['prerequisites']);
        }

        // Add educational level
        if (!empty($data['educational_level'])) {
            $course->educationalLevel($data['educational_level']);
        }

        // Add duration
        if (!empty($data['duration'])) {
            $course->timeRequired($data['duration']);
        }

        // Add course instance(s)
        if (!empty($data['instances'])) {
            $instances = array_map(function($instance) {
                $courseInstance = Schema::courseInstance();

                if (!empty($instance['start_date'])) {
                    $courseInstance->startDate($instance['start_date']);
                }
                if (!empty($instance['end_date'])) {
                    $courseInstance->endDate($instance['end_date']);
                }
                if (!empty($instance['course_mode'])) {
                    $courseInstance->courseMode($instance['course_mode']);
                }
                if (!empty($instance['location'])) {
                    $courseInstance->location(Schema::place()->name($instance['location']));
                }

                return $courseInstance;
            }, $data['instances']);
            $course->hasCourseInstance($instances);
        }

        // Add offers/pricing
        if (!empty($data['offers'])) {
            $offers = array_map(function($offer) {
                return Schema::offer()
                    ->price($offer['price'])
                    ->priceCurrency($offer['currency'] ?? 'USD');
            }, $data['offers']);
            $course->offers($offers);
        }

        // Add skills gained
        if (!empty($data['skills'])) {
            $course->teaches($data['skills']);
        }

        // Add credential earned
        if (!empty($data['credential'])) {
            $course->educationalCredentialAwarded($data['credential']);
        }

        // Add rating
        if (!empty($data['rating'])) {
            $course->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($data['rating']['value'])
                    ->reviewCount($data['rating']['count'])
            );
        }

        return $course->toArray();
    }

    /**
     * Generate SoftwareApplication schema - For apps, tools, and software
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateSoftwareApplication(array $data): array
    {
        $app = Schema::softwareApplication()
            ->name($data['name'])
            ->description($data['description']);

        // Add application category
        if (!empty($data['category'])) {
            $app->applicationCategory($data['category']);
        }

        // Add operating system
        if (!empty($data['operating_system'])) {
            $app->operatingSystem($data['operating_system']);
        }

        // Add software version
        if (!empty($data['version'])) {
            $app->softwareVersion($data['version']);
        }

        // Add download URL
        if (!empty($data['download_url'])) {
            $app->downloadUrl($data['download_url']);
        }

        // Add install URL
        if (!empty($data['install_url'])) {
            $app->installUrl($data['install_url']);
        }

        // Add screenshot(s)
        if (!empty($data['screenshots'])) {
            $app->screenshot($data['screenshots']);
        }

        // Add feature list
        if (!empty($data['features'])) {
            $app->featureList($data['features']);
        }

        // Add software requirements
        if (!empty($data['requirements'])) {
            $app->softwareRequirements($data['requirements']);
        }

        // Add offers/pricing
        if (!empty($data['offers'])) {
            $offers = array_map(function($offer) {
                $offerSchema = Schema::offer()
                    ->price($offer['price'])
                    ->priceCurrency($offer['currency'] ?? 'USD');

                if (!empty($offer['availability'])) {
                    $offerSchema->availability($offer['availability']);
                }

                return $offerSchema;
            }, $data['offers']);
            $app->offers($offers);
        }

        // Add aggregate rating
        if (!empty($data['rating'])) {
            $app->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($data['rating']['value'])
                    ->reviewCount($data['rating']['count'])
                    ->bestRating($data['rating']['best'] ?? 5)
                    ->worstRating($data['rating']['worst'] ?? 1)
            );
        }

        // Add developer/author
        if (!empty($data['developer'])) {
            $app->author(
                Schema::organization()->name($data['developer'])
            );
        }

        // Add release date
        if (!empty($data['release_date'])) {
            $app->datePublished($data['release_date']);
        }

        // Add file size
        if (!empty($data['file_size'])) {
            $app->fileSize($data['file_size']);
        }

        return $app->toArray();
    }

    /**
     * Generate Organization schema - For companies and organizations
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateOrganization(array $data): array
    {
        $org = Schema::organization()
            ->name($data['name'])
            ->description($data['description'] ?? '');

        // Add logo
        if (!empty($data['logo'])) {
            $org->logo($data['logo']);
        }

        // Add URL
        if (!empty($data['url'])) {
            $org->url($data['url']);
        }

        // Add contact point
        if (!empty($data['contact'])) {
            $contactPoint = Schema::contactPoint()
                ->contactType($data['contact']['type'] ?? 'customer service');

            if (!empty($data['contact']['phone'])) {
                $contactPoint->telephone($data['contact']['phone']);
            }
            if (!empty($data['contact']['email'])) {
                $contactPoint->email($data['contact']['email']);
            }

            $org->contactPoint($contactPoint);
        }

        // Add address
        if (!empty($data['address'])) {
            $address = Schema::postalAddress();
            if (!empty($data['address']['street'])) {
                $address->streetAddress($data['address']['street']);
            }
            if (!empty($data['address']['city'])) {
                $address->addressLocality($data['address']['city']);
            }
            if (!empty($data['address']['state'])) {
                $address->addressRegion($data['address']['state']);
            }
            if (!empty($data['address']['postal_code'])) {
                $address->postalCode($data['address']['postal_code']);
            }
            if (!empty($data['address']['country'])) {
                $address->addressCountry($data['address']['country']);
            }
            $org->address($address);
        }

        // Add social profiles
        if (!empty($data['social_profiles'])) {
            $org->sameAs($data['social_profiles']);
        }

        // Add founding date
        if (!empty($data['founding_date'])) {
            $org->foundingDate($data['founding_date']);
        }

        // Add founders
        if (!empty($data['founders'])) {
            $founders = array_map(function($founder) {
                return Schema::person()->name($founder);
            }, $data['founders']);
            $org->founder($founders);
        }

        // Add number of employees
        if (!empty($data['employee_count'])) {
            $org->numberOfEmployees(
                Schema::quantitativeValue()->value($data['employee_count'])
            );
        }

        return $org->toArray();
    }

    /**
     * Generate Person schema - For individuals, authors, experts
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generatePerson(array $data): array
    {
        $person = Schema::person()
            ->name($data['name']);

        // Add job title
        if (!empty($data['job_title'])) {
            $person->jobTitle($data['job_title']);
        }

        // Add description/bio
        if (!empty($data['description'])) {
            $person->description($data['description']);
        }

        // Add image
        if (!empty($data['image'])) {
            $person->image($data['image']);
        }

        // Add email
        if (!empty($data['email'])) {
            $person->email($data['email']);
        }

        // Add URL
        if (!empty($data['url'])) {
            $person->url($data['url']);
        }

        // Add works for
        if (!empty($data['works_for'])) {
            $person->worksFor(
                Schema::organization()->name($data['works_for'])
            );
        }

        // Add credentials
        if (!empty($data['credentials'])) {
            foreach ($data['credentials'] as $credential) {
                $person->hasCredential(
                    Schema::educationalOccupationalCredential()->name($credential)
                );
            }
        }

        // Add social profiles
        if (!empty($data['social_profiles'])) {
            $person->sameAs($data['social_profiles']);
        }

        // Add alumni of
        if (!empty($data['alumni_of'])) {
            $person->alumniOf(
                Schema::educationalOrganization()->name($data['alumni_of'])
            );
        }

        // Add knows about (expertise)
        if (!empty($data['expertise'])) {
            $person->knowsAbout($data['expertise']);
        }

        return $person->toArray();
    }

    /**
     * Generate Service schema - For services offered
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateService(array $data): array
    {
        $service = Schema::service()
            ->name($data['name'])
            ->description($data['description']);

        // Add service type
        if (!empty($data['service_type'])) {
            $service->serviceType($data['service_type']);
        }

        // Add provider
        if (!empty($data['provider'])) {
            $service->provider(
                Schema::organization()->name($data['provider'])
            );
        }

        // Add area served
        if (!empty($data['area_served'])) {
            $service->areaServed($data['area_served']);
        }

        // Add audience
        if (!empty($data['audience'])) {
            $service->audience(
                Schema::audience()->audienceType($data['audience'])
            );
        }

        // Add offers/pricing
        if (!empty($data['offers'])) {
            $offers = array_map(function($offer) {
                $offerSchema = Schema::offer()
                    ->price($offer['price'])
                    ->priceCurrency($offer['currency'] ?? 'USD');

                if (!empty($offer['description'])) {
                    $offerSchema->description($offer['description']);
                }

                return $offerSchema;
            }, $data['offers']);
            $service->offers($offers);
        }

        // Add service output
        if (!empty($data['output'])) {
            $service->serviceOutput($data['output']);
        }

        // Add category
        if (!empty($data['category'])) {
            $service->category($data['category']);
        }

        // Add brand
        if (!empty($data['brand'])) {
            $service->brand(Schema::brand()->name($data['brand']));
        }

        // Add aggregate rating
        if (!empty($data['rating'])) {
            $service->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($data['rating']['value'])
                    ->reviewCount($data['rating']['count'])
            );
        }

        return $service->toArray();
    }

    /**
     * Generate JobPosting schema - For job listings
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateJobPosting(array $data): array
    {
        $job = Schema::jobPosting()
            ->title($data['title'])
            ->description($data['description']);

        // Add hiring organization
        if (!empty($data['hiring_organization'])) {
            $org = Schema::organization()->name($data['hiring_organization']['name']);
            if (!empty($data['hiring_organization']['logo'])) {
                $org->logo($data['hiring_organization']['logo']);
            }
            if (!empty($data['hiring_organization']['url'])) {
                $org->url($data['hiring_organization']['url']);
            }
            $job->hiringOrganization($org);
        }

        // Add job location
        if (!empty($data['location'])) {
            $place = Schema::place();
            if (!empty($data['location']['address'])) {
                $address = Schema::postalAddress();
                if (!empty($data['location']['address']['city'])) {
                    $address->addressLocality($data['location']['address']['city']);
                }
                if (!empty($data['location']['address']['state'])) {
                    $address->addressRegion($data['location']['address']['state']);
                }
                if (!empty($data['location']['address']['country'])) {
                    $address->addressCountry($data['location']['address']['country']);
                }
                $place->address($address);
            }
            $job->jobLocation($place);
        }

        // Remote work option
        if (!empty($data['remote'])) {
            $job->jobLocationType('TELECOMMUTE');
        }

        // Add employment type
        if (!empty($data['employment_type'])) {
            $job->employmentType($data['employment_type']);
        }

        // Add date posted
        $job->datePosted($data['date_posted'] ?? date('Y-m-d'));

        // Add valid through
        if (!empty($data['valid_through'])) {
            $job->validThrough($data['valid_through']);
        }

        // Add salary
        if (!empty($data['salary'])) {
            $salary = Schema::monetaryAmount()
                ->currency($data['salary']['currency'] ?? 'USD');

            if (!empty($data['salary']['value'])) {
                $salary->value($data['salary']['value']);
            }
            if (!empty($data['salary']['min']) && !empty($data['salary']['max'])) {
                $salary->minValue($data['salary']['min']);
                $salary->maxValue($data['salary']['max']);
            }

            $job->baseSalary($salary);
        }

        // Add responsibilities
        if (!empty($data['responsibilities'])) {
            $job->responsibilities($data['responsibilities']);
        }

        // Add qualifications
        if (!empty($data['qualifications'])) {
            $job->qualifications($data['qualifications']);
        }

        // Add skills
        if (!empty($data['skills'])) {
            $job->skills($data['skills']);
        }

        // Add education requirements
        if (!empty($data['education_requirements'])) {
            $job->educationRequirements($data['education_requirements']);
        }

        // Add experience requirements
        if (!empty($data['experience_requirements'])) {
            $job->experienceRequirements($data['experience_requirements']);
        }

        // Add industry
        if (!empty($data['industry'])) {
            $job->industry($data['industry']);
        }

        // Add application URL
        if (!empty($data['apply_url'])) {
            $job->applicationContact(
                Schema::contactPoint()->url($data['apply_url'])
            );
        }

        return $job->toArray();
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

    /**
     * Generate multiple schemas and combine them
     *
     * @param array<array{type: string, data: array<string, mixed>}> $schemas
     * @return string Combined JSON-LD
     */
    public function generateMultiple(array $schemas): string
    {
        $combined = [
            '@context' => 'https://schema.org',
            '@graph' => []
        ];

        foreach ($schemas as $schema) {
            $generated = $this->generate($schema['type'], $schema['data']);
            unset($generated['@context']);
            $combined['@graph'][] = $generated;
        }

        return json_encode($combined, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}