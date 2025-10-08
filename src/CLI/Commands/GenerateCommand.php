<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GEOOptimizer\GEOOptimizer;

/**
 * Generate llms.txt file command
 */
class GenerateCommand extends Command
{
    protected static $defaultName = 'generate';
    protected static $defaultDescription = 'Generate llms.txt file for AI search engines';

    protected function configure(): void
    {
        $this
            ->setHelp('This command generates an optimized llms.txt file for AI search engines')
            ->addArgument(
                'output',
                InputArgument::OPTIONAL,
                'Output file path',
                'public/llms.txt'
            )
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Business type (restaurant, legal, medical, etc.)',
                'LocalBusiness'
            )
            ->addOption(
                'template',
                null,
                InputOption::VALUE_REQUIRED,
                'Template to use (business, ecommerce, service, professional, local)',
                'business'
            )
            ->addOption(
                'config',
                'c',
                InputOption::VALUE_REQUIRED,
                'Path to JSON configuration file with business data'
            )
            ->addOption(
                'interactive',
                'i',
                InputOption::VALUE_NONE,
                'Interactive mode - prompts for business information'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GEO Optimizer - Generate llms.txt');

        // Get business data
        $businessData = $this->getBusinessData($input, $io);

        if (empty($businessData)) {
            $io->error('No business data provided. Use --config or --interactive option.');
            return Command::FAILURE;
        }

        try {
            // Initialize GEO Optimizer
            $geo = new GEOOptimizer();

            // Generate llms.txt
            $io->section('Generating llms.txt file...');
            $llmsTxt = $geo->generateLLMSTxt($businessData);

            // Save to file
            $outputPath = $input->getArgument('output');
            $directory = dirname($outputPath);

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($outputPath, $llmsTxt);

            $io->success([
                'llms.txt file generated successfully!',
                'Output: ' . realpath($outputPath),
                'Size: ' . $this->formatBytes(strlen($llmsTxt))
            ]);

            // Show preview
            if ($io->isVerbose()) {
                $io->section('Preview (first 500 characters):');
                $io->text(substr($llmsTxt, 0, 500) . '...');
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Failed to generate llms.txt file',
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
     * Get business data from config file or interactive input
     */
    private function getBusinessData(InputInterface $input, SymfonyStyle $io): array
    {
        // Load from config file if provided
        if ($configPath = $input->getOption('config')) {
            if (!file_exists($configPath)) {
                $io->error("Config file not found: $configPath");
                return [];
            }

            $content = file_get_contents($configPath);
            $data = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error('Invalid JSON in config file: ' . json_last_error_msg());
                return [];
            }

            return $data;
        }

        // Interactive mode
        if ($input->getOption('interactive')) {
            $io->section('Enter Business Information');

            $data = [
                'business_name' => $io->ask('Business Name', null, function ($value) {
                    if (empty($value)) {
                        throw new \RuntimeException('Business name is required');
                    }
                    return $value;
                }),
                'description' => $io->ask('Business Description'),
                'industry' => $io->choice(
                    'Select Industry',
                    [
                        'restaurant', 'legal', 'medical', 'automotive',
                        'home_services', 'retail', 'real_estate', 'fitness',
                        'beauty', 'education', 'technology', 'professional'
                    ],
                    'professional'
                ),
                'phone' => $io->ask('Phone Number'),
                'email' => $io->ask('Email Address'),
                'website' => $io->ask('Website URL'),
                'street_address' => $io->ask('Street Address'),
                'city' => $io->ask('City'),
                'state' => $io->ask('State/Province'),
                'postal_code' => $io->ask('Postal Code'),
                'country' => $io->ask('Country', 'US')
            ];

            // Add services
            $io->section('Services Offered (press enter to skip)');
            $services = [];
            while ($service = $io->ask('Add a service (leave empty to finish)')) {
                $services[] = $service;
            }
            if (!empty($services)) {
                $data['services'] = $services;
            }

            // Business hours
            if ($io->confirm('Add business hours?', false)) {
                $data['hours'] = [];
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day) {
                    $hours = $io->ask("$day hours (e.g., '9:00 AM - 5:00 PM' or 'Closed')");
                    if ($hours) {
                        $data['hours'][$day] = $hours;
                    }
                }
            }

            return $data;
        }

        // Default minimal data
        return [
            'business_name' => 'Sample Business',
            'description' => 'A sample business for demonstration',
            'industry' => $input->getOption('type')
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}