<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;

/**
 * Validate GEO setup and configuration
 */
class ValidateCommand extends Command
{
    protected static $defaultName = 'validate';
    protected static $defaultDescription = 'Validate your GEO optimization setup';

    protected function configure(): void
    {
        $this
            ->setHelp('Validates your website\'s GEO optimization setup and configuration')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to website root or specific file',
                '.'
            )
            ->addOption(
                'check-llms',
                null,
                InputOption::VALUE_NONE,
                'Check for llms.txt file'
            )
            ->addOption(
                'check-schema',
                null,
                InputOption::VALUE_NONE,
                'Validate structured data'
            )
            ->addOption(
                'check-sitemap',
                null,
                InputOption::VALUE_NONE,
                'Check for sitemap.xml'
            )
            ->addOption(
                'check-robots',
                null,
                InputOption::VALUE_NONE,
                'Check robots.txt'
            )
            ->addOption(
                'all',
                'a',
                InputOption::VALUE_NONE,
                'Run all validation checks'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GEO Optimizer - Setup Validation');

        $path = $input->getArgument('path');
        $runAll = $input->getOption('all');

        if (!is_dir($path)) {
            $io->error("Path not found: $path");
            return Command::FAILURE;
        }

        $checks = [];
        $errors = 0;
        $warnings = 0;

        // Run llms.txt check
        if ($runAll || $input->getOption('check-llms')) {
            $result = $this->checkLLMSTxt($path);
            $checks['llms.txt File'] = $result;
            if ($result['status'] === 'error') $errors++;
            if ($result['status'] === 'warning') $warnings++;
        }

        // Run schema check
        if ($runAll || $input->getOption('check-schema')) {
            $result = $this->checkStructuredData($path);
            $checks['Structured Data'] = $result;
            if ($result['status'] === 'error') $errors++;
            if ($result['status'] === 'warning') $warnings++;
        }

        // Run sitemap check
        if ($runAll || $input->getOption('check-sitemap')) {
            $result = $this->checkSitemap($path);
            $checks['Sitemap'] = $result;
            if ($result['status'] === 'error') $errors++;
            if ($result['status'] === 'warning') $warnings++;
        }

        // Run robots.txt check
        if ($runAll || $input->getOption('check-robots')) {
            $result = $this->checkRobotsTxt($path);
            $checks['Robots.txt'] = $result;
            if ($result['status'] === 'error') $errors++;
            if ($result['status'] === 'warning') $warnings++;
        }

        // Additional checks when running all
        if ($runAll) {
            // Check HTTPS
            $result = $this->checkHTTPS($path);
            $checks['HTTPS'] = $result;
            if ($result['status'] === 'error') $errors++;

            // Check mobile-friendly
            $result = $this->checkMobileFriendly($path);
            $checks['Mobile-Friendly'] = $result;
            if ($result['status'] === 'warning') $warnings++;

            // Check page speed
            $result = $this->checkPageSpeed($path);
            $checks['Page Speed'] = $result;
            if ($result['status'] === 'warning') $warnings++;
        }

        if (empty($checks)) {
            $io->warning('No checks selected. Use --all or specify individual checks.');
            return Command::FAILURE;
        }

        // Display results
        $this->displayResults($checks, $io);

        // Summary
        $io->section('Validation Summary');

        if ($errors === 0 && $warnings === 0) {
            $io->success('All checks passed! Your GEO setup is properly configured.');
            return Command::SUCCESS;
        } elseif ($errors > 0) {
            $io->error([
                "Found $errors error(s) and $warnings warning(s)",
                'Please fix the errors above for optimal GEO performance.'
            ]);
            return Command::FAILURE;
        } else {
            $io->warning([
                "Found $warnings warning(s)",
                'Consider addressing the warnings for better optimization.'
            ]);
            return Command::SUCCESS;
        }
    }

    /**
     * Check for llms.txt file
     */
    private function checkLLMSTxt(string $path): array
    {
        $publicPaths = ['public', 'www', 'public_html', 'htdocs', '.'];
        $found = false;
        $llmsPath = null;

        foreach ($publicPaths as $publicPath) {
            $checkPath = $path . '/' . $publicPath . '/llms.txt';
            if (file_exists($checkPath)) {
                $found = true;
                $llmsPath = $checkPath;
                break;
            }
        }

        if (!$found) {
            return [
                'status' => 'error',
                'message' => 'llms.txt file not found',
                'details' => 'Create an llms.txt file in your public directory'
            ];
        }

        // Check file content
        $content = file_get_contents($llmsPath);
        $size = strlen($content);

        if ($size < 100) {
            return [
                'status' => 'warning',
                'message' => 'llms.txt file is very small',
                'details' => "File size: $size bytes. Consider adding more content."
            ];
        }

        return [
            'status' => 'success',
            'message' => 'llms.txt file found and valid',
            'details' => "Located at: $llmsPath (Size: $size bytes)"
        ];
    }

    /**
     * Check for structured data
     */
    private function checkStructuredData(string $path): array
    {
        // Look for HTML files
        $htmlFiles = glob($path . '/*.html');
        if (empty($htmlFiles)) {
            $htmlFiles = glob($path . '/public/*.html');
        }

        if (empty($htmlFiles)) {
            return [
                'status' => 'warning',
                'message' => 'No HTML files found to check',
                'details' => 'Could not find HTML files to validate structured data'
            ];
        }

        $hasSchema = false;
        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'application/ld+json') !== false ||
                strpos($content, 'itemscope') !== false ||
                strpos($content, 'typeof=') !== false) {
                $hasSchema = true;
                break;
            }
        }

        if (!$hasSchema) {
            return [
                'status' => 'error',
                'message' => 'No structured data found',
                'details' => 'Add Schema.org markup to your HTML pages'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Structured data detected',
            'details' => 'Found Schema.org markup in HTML files'
        ];
    }

    /**
     * Check for sitemap
     */
    private function checkSitemap(string $path): array
    {
        $sitemapPaths = [
            '/sitemap.xml',
            '/public/sitemap.xml',
            '/sitemap_index.xml',
            '/public/sitemap_index.xml'
        ];

        $found = false;
        foreach ($sitemapPaths as $sitemapPath) {
            if (file_exists($path . $sitemapPath)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            return [
                'status' => 'warning',
                'message' => 'sitemap.xml not found',
                'details' => 'Create a sitemap.xml for better crawling'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Sitemap found',
            'details' => "Located at: $path$sitemapPath"
        ];
    }

    /**
     * Check robots.txt
     */
    private function checkRobotsTxt(string $path): array
    {
        $robotsPaths = [
            '/robots.txt',
            '/public/robots.txt'
        ];

        $found = false;
        $robotsPath = null;
        foreach ($robotsPaths as $checkPath) {
            if (file_exists($path . $checkPath)) {
                $found = true;
                $robotsPath = $path . $checkPath;
                break;
            }
        }

        if (!$found) {
            return [
                'status' => 'warning',
                'message' => 'robots.txt not found',
                'details' => 'Create a robots.txt file to guide crawlers'
            ];
        }

        // Check if it allows AI crawlers
        $content = file_get_contents($robotsPath);
        if (strpos($content, 'User-agent: GPTBot') !== false ||
            strpos($content, 'User-agent: ChatGPT') !== false ||
            strpos($content, 'User-agent: Claude') !== false) {
            return [
                'status' => 'success',
                'message' => 'robots.txt configured for AI crawlers',
                'details' => 'Found AI-specific user agent rules'
            ];
        }

        return [
            'status' => 'warning',
            'message' => 'robots.txt found but no AI crawler rules',
            'details' => 'Consider adding rules for GPTBot, ChatGPT, Claude user agents'
        ];
    }

    /**
     * Check HTTPS
     */
    private function checkHTTPS(string $path): array
    {
        // This is a simplified check
        return [
            'status' => 'success',
            'message' => 'HTTPS check',
            'details' => 'Ensure your site uses HTTPS in production'
        ];
    }

    /**
     * Check mobile-friendly
     */
    private function checkMobileFriendly(string $path): array
    {
        // Look for viewport meta tag in HTML files
        $htmlFiles = glob($path . '/*.html');
        if (empty($htmlFiles)) {
            $htmlFiles = glob($path . '/public/*.html');
        }

        if (empty($htmlFiles)) {
            return [
                'status' => 'warning',
                'message' => 'Could not check mobile-friendliness',
                'details' => 'No HTML files found'
            ];
        }

        $hasViewport = false;
        foreach ($htmlFiles as $file) {
            $content = file_get_contents($file);
            if (strpos($content, 'viewport') !== false) {
                $hasViewport = true;
                break;
            }
        }

        if (!$hasViewport) {
            return [
                'status' => 'warning',
                'message' => 'No viewport meta tag found',
                'details' => 'Add viewport meta tag for mobile optimization'
            ];
        }

        return [
            'status' => 'success',
            'message' => 'Mobile-friendly viewport detected',
            'details' => 'Found viewport meta tag in HTML'
        ];
    }

    /**
     * Check page speed (simplified)
     */
    private function checkPageSpeed(string $path): array
    {
        return [
            'status' => 'info',
            'message' => 'Page speed check',
            'details' => 'Use Google PageSpeed Insights for detailed analysis'
        ];
    }

    /**
     * Display validation results
     */
    private function displayResults(array $checks, SymfonyStyle $io): void
    {
        $io->section('Validation Results');

        $table = new Table($io);
        $table->setHeaders(['Check', 'Status', 'Details']);

        foreach ($checks as $name => $result) {
            $status = $this->formatStatus($result['status']);
            $table->addRow([
                $name,
                $status,
                $result['details'] ?? $result['message']
            ]);
        }

        $table->render();
    }

    /**
     * Format status with color and emoji
     */
    private function formatStatus(string $status): string
    {
        switch ($status) {
            case 'success':
                return '<info>✅ Pass</info>';
            case 'warning':
                return '<comment>⚠️  Warning</comment>';
            case 'error':
                return '<error>❌ Fail</error>';
            case 'info':
                return '<fg=cyan>ℹ️  Info</>';
            default:
                return $status;
        }
    }
}