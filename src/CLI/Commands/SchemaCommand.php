<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GEOOptimizer\StructuredData\SchemaGenerator;

/**
 * Generate structured data schema command
 */
class SchemaCommand extends Command
{
    protected static $defaultName = 'schema';
    protected static $defaultDescription = 'Generate Schema.org structured data markup';

    protected function configure(): void
    {
        $this
            ->setHelp('Generate Schema.org JSON-LD markup for AI and search engines')
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Schema type (LocalBusiness, Restaurant, Product, HowTo, etc.)'
            )
            ->addOption(
                'data',
                'd',
                InputOption::VALUE_REQUIRED,
                'Path to JSON file with schema data'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output file path (default: stdout)'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (json-ld, microdata, rdfa)',
                'json-ld'
            )
            ->addOption(
                'validate',
                null,
                InputOption::VALUE_NONE,
                'Validate generated schema'
            )
            ->addOption(
                'list-types',
                null,
                InputOption::VALUE_NONE,
                'List all supported schema types'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $generator = new SchemaGenerator();

        // List supported types if requested
        if ($input->getOption('list-types')) {
            return $this->listSupportedTypes($generator, $io);
        }

        $io->title('GEO Optimizer - Schema Generator');

        $type = $input->getArgument('type');

        // Validate schema type
        $supportedTypes = $generator->getSupportedTypes();
        if (!in_array($type, $supportedTypes)) {
            // Also check for special types
            $specialTypes = ['FAQ', 'Article', 'HowTo', 'Product', 'BreadcrumbList', 'Review'];
            if (!in_array($type, $specialTypes)) {
                $io->error([
                    "Unsupported schema type: $type",
                    'Use --list-types to see all supported types'
                ]);
                return Command::FAILURE;
            }
        }

        try {
            // Get schema data
            $data = $this->getSchemaData($input, $io, $type);

            if (empty($data)) {
                $io->error('No data provided for schema generation');
                return Command::FAILURE;
            }

            $io->section("Generating $type schema...");

            // Generate schema based on type
            if ($type === 'FAQ') {
                $schema = $generator->generateFAQ($data);
            } elseif ($type === 'Article') {
                $schema = $generator->generateArticle($data);
            } elseif ($type === 'HowTo') {
                $schema = $generator->generateHowTo($data);
            } elseif ($type === 'Product') {
                $schema = $generator->generateProduct($data);
            } elseif ($type === 'BreadcrumbList') {
                $schema = $generator->generateBreadcrumbList($data);
            } elseif ($type === 'Review') {
                $schema = $generator->generateReview($data);
            } else {
                $schema = $generator->generate($type, $data);
            }

            // Format output based on selected format
            $format = $input->getOption('format');
            $formattedOutput = $this->formatSchema($schema, $format, $generator);

            // Validate if requested
            if ($input->getOption('validate')) {
                $this->validateSchema($formattedOutput, $io);
            }

            // Output or save
            if ($outputPath = $input->getOption('output')) {
                $this->saveSchema($formattedOutput, $outputPath, $io);
            } else {
                $output->writeln($formattedOutput);
            }

            $io->success("$type schema generated successfully!");

            // Show implementation instructions
            if (!$input->getOption('output')) {
                $io->section('Implementation');
                $io->text([
                    'Add this schema to your HTML <head> section:',
                    '',
                    '<script type="application/ld+json">',
                    '  ' . substr($formattedOutput, 0, 100) . '...',
                    '</script>'
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Schema generation failed',
                'Error: ' . $e->getMessage()
            ]);

            if ($io->isVerbose()) {
                $io->section('Stack Trace:');
                $io->text($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * List all supported schema types
     */
    private function listSupportedTypes(SchemaGenerator $generator, SymfonyStyle $io): int
    {
        $io->title('Supported Schema Types');

        $io->section('Business Types');
        $businessTypes = $generator->getSupportedTypes();
        $io->listing($businessTypes);

        $io->section('Content Types');
        $contentTypes = [
            'FAQ - Frequently Asked Questions',
            'Article - News articles, blog posts',
            'HowTo - Step-by-step guides',
            'Product - E-commerce products',
            'BreadcrumbList - Site navigation',
            'Review - Product/service reviews'
        ];
        $io->listing($contentTypes);

        $io->section('Example Usage');
        $io->text([
            '  <comment>Generate restaurant schema:</comment>',
            '  $ geo-optimizer schema Restaurant --data restaurant.json',
            '',
            '  <comment>Generate FAQ schema:</comment>',
            '  $ geo-optimizer schema FAQ --data faq.json',
            '',
            '  <comment>Generate product schema with validation:</comment>',
            '  $ geo-optimizer schema Product --data product.json --validate'
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get schema data from file or create sample data
     */
    private function getSchemaData(InputInterface $input, SymfonyStyle $io, string $type): array
    {
        // Load from file if provided
        if ($dataPath = $input->getOption('data')) {
            if (!file_exists($dataPath)) {
                throw new \RuntimeException("Data file not found: $dataPath");
            }

            $content = file_get_contents($dataPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON: ' . json_last_error_msg());
            }

            return $data;
        }

        // Generate sample data for demonstration
        $io->warning('No data file provided. Using sample data for demonstration.');

        switch ($type) {
            case 'Restaurant':
                return [
                    'business_name' => 'Sample Restaurant',
                    'description' => 'A sample restaurant for demonstration',
                    'cuisine_type' => 'Italian',
                    'phone' => '555-0123',
                    'street_address' => '123 Main St',
                    'city' => 'Sample City',
                    'state' => 'SC',
                    'postal_code' => '12345'
                ];

            case 'Product':
                return [
                    'name' => 'Sample Product',
                    'description' => 'A sample product for demonstration',
                    'brand' => 'Sample Brand',
                    'sku' => 'SAMPLE-001',
                    'offers' => [
                        [
                            'price' => 99.99,
                            'currency' => 'USD',
                            'availability' => 'InStock'
                        ]
                    ]
                ];

            case 'FAQ':
                return [
                    [
                        'question' => 'What is GEO?',
                        'answer' => 'Generative Engine Optimization is the practice of optimizing content for AI search engines.'
                    ],
                    [
                        'question' => 'How does it work?',
                        'answer' => 'It works by providing structured data and optimized content that AI systems can understand.'
                    ]
                ];

            case 'HowTo':
                return [
                    'title' => 'How to Optimize for AI Search',
                    'description' => 'A guide to optimizing your content for AI search engines',
                    'steps' => [
                        ['text' => 'Install the GEO Optimizer library'],
                        ['text' => 'Generate llms.txt file'],
                        ['text' => 'Add structured data'],
                        ['text' => 'Monitor your GEO score']
                    ]
                ];

            default:
                return [
                    'business_name' => 'Sample Business',
                    'description' => 'A sample business for demonstration',
                    'phone' => '555-0123',
                    'email' => 'info@example.com',
                    'website' => 'https://example.com'
                ];
        }
    }

    /**
     * Format schema based on output format
     */
    private function formatSchema(array $schema, string $format, SchemaGenerator $generator): string
    {
        switch ($format) {
            case 'json-ld':
                return $generator->toJsonLd($schema);

            case 'microdata':
                // Note: This would require additional implementation
                return "<!-- Microdata format not yet implemented -->\n" .
                       $generator->toJsonLd($schema);

            case 'rdfa':
                // Note: This would require additional implementation
                return "<!-- RDFa format not yet implemented -->\n" .
                       $generator->toJsonLd($schema);

            default:
                return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Validate schema against Schema.org specifications
     */
    private function validateSchema(string $schema, SymfonyStyle $io): void
    {
        $io->section('Validating schema...');

        // Basic JSON validation
        $decoded = json_decode($schema);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $io->error('Invalid JSON: ' . json_last_error_msg());
            return;
        }

        // Check for required @context
        if (!isset($decoded->{'@context'})) {
            $io->warning('Missing @context property');
        } elseif ($decoded->{'@context'} !== 'https://schema.org') {
            $io->warning('Non-standard @context: ' . $decoded->{'@context'});
        }

        // Check for @type
        if (!isset($decoded->{'@type'})) {
            $io->error('Missing @type property');
            return;
        }

        $io->success('Schema validation passed!');

        // Suggest testing with Google's tool
        $io->note([
            'For complete validation, test your schema with:',
            'Google Rich Results Test: https://search.google.com/test/rich-results',
            'Schema.org Validator: https://validator.schema.org/'
        ]);
    }

    /**
     * Save schema to file
     */
    private function saveSchema(string $schema, string $path, SymfonyStyle $io): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $schema);
        $io->success("Schema saved to: $path");

        // Show file size
        $size = filesize($path);
        $io->text("File size: " . $this->formatBytes($size));
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}