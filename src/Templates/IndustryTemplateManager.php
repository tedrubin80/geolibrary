<?php

namespace GEOOptimizer\Templates;

use GEOOptimizer\Exceptions\ValidationException;

/**
 * Industry-Specific Template Manager
 * 
 * Manages templates optimized for different business types and industries
 */
class IndustryTemplateManager
{
    private $templates = [];
    private $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->initializeTemplates();
    }

    /**
     * Get template for specific industry
     */
    public function getTemplate(string $industry): array
    {
        $industry = strtolower($industry);
        
        if (!isset($this->templates[$industry])) {
            throw new ValidationException("Template not found for industry: {$industry}");
        }

        return $this->templates[$industry];
    }

    /**
     * Generate industry-optimized llms.txt content
     */
    public function generateIndustryLLMSTxt(array $businessData, string $industry): string
    {
        $template = $this->getTemplate($industry);
        return $this->processTemplate($template['llms_template'], $businessData);
    }

    /**
     * Get industry-specific structured data schema
     */
    public function getIndustrySchema(array $businessData, string $industry): array
    {
        $template = $this->getTemplate($industry);
        
        return [
            'primary_schema' => $template['primary_schema'],
            'additional_schemas' => $template['additional_schemas'],
            'required_fields' => $template['required_fields'],
            'recommended_fields' => $template['recommended_fields']
        ];
    }

    /**
     * Get available industries
     */
    public function getAvailableIndustries(): array
    {
        return array_keys($this->templates);
    }

    /**
     * Initialize all industry templates
     */
    private function initializeTemplates(): void
    {
        // Restaurant Template
        $this->templates['restaurant'] = [
            'llms_template' => '# {{ business_name }} - Restaurant
# Cuisine: {{ cuisine_type }}
# Description: {{ description }}
# Location: {{ location }}
# Hours: {{ hours }}
# Phone: {{ phone }}
# Specialties: {{ specialties }}
# Price Range: {{ price_range }}
# Reservations: {{ accepts_reservations }}',
            
            'primary_schema' => 'Restaurant',
            'additional_schemas' => ['LocalBusiness', 'Menu'],
            'required_fields' => ['name', 'description', 'cuisine_type', 'address'],
            'recommended_fields' => ['hours', 'menu', 'accepts_reservations', 'rating'],
            
            'faq_suggestions' => [
                'What are your hours?',
                'Do you take reservations?',
                'What cuisine do you serve?',
                'Do you have vegetarian options?',
                'Is parking available?'
            ],
            
            'authority_signals' => [
                'Years in business',
                'Chef credentials',
                'Restaurant awards',
                'Customer reviews',
                'Health ratings'
            ]
        ];

        // Legal Services Template
        $this->templates['legal'] = [
            'llms_template' => '# {{ business_name }} - Legal Services
# Practice Areas: {{ practice_areas }}
# Description: {{ description }}
# Location: {{ location }}
# Experience: {{ years_experience }} years
# Attorneys: {{ attorney_names }}
# Bar Admission: {{ bar_certifications }}
# Consultation: {{ free_consultation }}
# Phone: {{ phone }}',
            
            'primary_schema' => 'LegalService',
            'additional_schemas' => ['LocalBusiness', 'Attorney'],
            'required_fields' => ['name', 'practice_areas', 'attorney_names'],
            'recommended_fields' => ['years_experience', 'bar_certifications', 'consultation'],
            
            'faq_suggestions' => [
                'What cases do you handle?',
                'How much do you charge?',
                'Do you offer free consultations?',
                'How long will my case take?',
                'What areas do you serve?'
            ],
            
            'authority_signals' => [
                'Years practicing',
                'Bar certifications',
                'Case results',
                'Client testimonials',
                'Professional awards'
            ]
        ];

        // Medical Practice Template
        $this->templates['medical'] = [
            'llms_template' => '# {{ business_name }} - Medical Practice
# Specialty: {{ medical_specialty }}
# Description: {{ description }}
# Services: {{ services }}
# Doctors: {{ doctor_names }}
# Certifications: {{ board_certifications }}
# Insurance: {{ insurance_accepted }}
# Hours: {{ office_hours }}
# Phone: {{ phone }}',
            
            'primary_schema' => 'MedicalBusiness',
            'additional_schemas' => ['LocalBusiness', 'Physician'],
            'required_fields' => ['name', 'medical_specialty', 'doctor_names'],
            'recommended_fields' => ['board_certifications', 'insurance_accepted', 'services'],
            
            'faq_suggestions' => [
                'What insurance do you accept?',
                'How do I schedule an appointment?',
                'What services do you offer?',
                'What are your office hours?',
                'Do you offer telehealth?'
            ],
            
            'authority_signals' => [
                'Board certifications',
                'Medical education',
                'Years of experience',
                'Hospital affiliations',
                'Patient reviews'
            ]
        ];

        // Home Services Template
        $this->templates['home_services'] = [
            'llms_template' => '# {{ business_name }} - {{ service_type }}
# Services: {{ services }}
# Description: {{ description }}
# Service Area: {{ service_area }}
# Experience: {{ years_experience }} years
# Licensed: {{ license_number }}
# Insurance: {{ insurance_info }}
# Emergency: {{ emergency_services }}
# Phone: {{ phone }}',
            
            'primary_schema' => 'HomeAndConstructionBusiness',
            'additional_schemas' => ['LocalBusiness', 'Service'],
            'required_fields' => ['name', 'service_type', 'services', 'license_number'],
            'recommended_fields' => ['years_experience', 'insurance_info', 'emergency_services'],
            
            'faq_suggestions' => [
                'Are you licensed and insured?',
                'What areas do you serve?',
                'Do you offer emergency services?',
                'How do you price your services?',
                'What is your warranty policy?'
            ],
            
            'authority_signals' => [
                'Professional licenses',
                'Insurance coverage',
                'Years of experience',
                'Customer reviews',
                'Industry certifications'
            ]
        ];

        // Add more condensed templates...
        $this->templates['automotive'] = $this->getCondensedTemplate('AutomotiveBusiness');
        $this->templates['retail'] = $this->getCondensedTemplate('Store');
        $this->templates['real_estate'] = $this->getCondensedTemplate('RealEstateAgent');
        $this->templates['fitness'] = $this->getCondensedTemplate('ExerciseGym');
        $this->templates['beauty'] = $this->getCondensedTemplate('BeautySalon');
        $this->templates['technology'] = $this->getCondensedTemplate('LocalBusiness');
    }

    /**
     * Get condensed template for industries
     */
    private function getCondensedTemplate(string $schemaType): array
    {
        return [
            'llms_template' => '# {{ business_name }}
# Industry: {{ industry }}
# Description: {{ description }}
# Services: {{ services }}
# Location: {{ location }}
# Experience: {{ years_experience }}
# Contact: {{ phone }}
# Website: {{ website }}',
            
            'primary_schema' => $schemaType,
            'additional_schemas' => ['LocalBusiness'],
            'required_fields' => ['name', 'description', 'services'],
            'recommended_fields' => ['location', 'phone', 'years_experience'],
            
            'faq_suggestions' => [
                'What services do you offer?',
                'What are your hours?',
                'How much do you charge?',
                'What areas do you serve?',
                'How do I contact you?'
            ],
            
            'authority_signals' => [
                'Years in business',
                'Professional certifications',
                'Customer testimonials',
                'Industry awards',
                'Positive reviews'
            ]
        ];
    }

    /**
     * Process template with business data
     */
    private function processTemplate(string $template, array $data): string
    {
        $processed = $template;
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $processed = str_replace('{{ ' . $key . ' }}', $value ?? '', $processed);
        }
        
        // Clean up empty lines
        $processed = preg_replace('/^#[^:]*:\s*$/m', '', $processed);
        $processed = preg_replace('/\n\s*\n/', "\n", $processed);
        
        return trim($processed);
    }

    /**
     * Get content suggestions for industry
     */
    public function getContentSuggestions(string $industry): array
    {
        $template = $this->getTemplate($industry);
        
        return [
            'faq_suggestions' => $template['faq_suggestions'] ?? [],
            'authority_signals' => $template['authority_signals'] ?? [],
            'required_content' => $this->getRequiredContent($industry),
            'optimization_tips' => $this->getOptimizationTips($industry)
        ];
    }

    private function getRequiredContent(string $industry): array
    {
        $common = ['About page', 'Services', 'Contact info', 'Location/Hours'];
        
        $specific = [
            'restaurant' => ['Menu', 'Reservations', 'Hours'],
            'legal' => ['Practice areas', 'Attorney bios', 'Case results'],
            'medical' => ['Services', 'Doctor profiles', 'Insurance info'],
            'home_services' => ['License info', 'Service area', 'Emergency services']
        ];
        
        return array_merge($common, $specific[$industry] ?? []);
    }

    private function getOptimizationTips(string $industry): array
    {
        return [
            'Include specific location information',
            'Add years of experience prominently',
            'List certifications and credentials',
            'Include customer testimonials',
            'Specify service areas clearly',
            'Add emergency contact info if applicable',
            'Include pricing information when possible'
        ];
    }
}