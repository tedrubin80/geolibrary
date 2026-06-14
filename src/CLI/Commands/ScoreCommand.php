<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use GEOOptimizer\Analytics\GEOReadinessScore;

/**
 * Calculate GEO Readiness Score command
 */
class ScoreCommand extends Command
{
    protected static $defaultName = 'score';
    protected static $defaultDescription = 'Calculate your website\'s GEO Readiness Score';

    protected function configure(): void
    {
        $this
            ->setHelp('Analyzes your website and calculates a comprehensive GEO Readiness Score')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to website root or URL',
                '.'
            )
            ->addOption(
                'detailed',
                'd',
                InputOption::VALUE_NONE,
                'Show detailed breakdown of all scoring factors'
            )
            ->addOption(
                'recommendations',
                'r',
                InputOption::VALUE_NONE,
                'Show actionable recommendations'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (text, json, html)',
                'text'
            )
            ->addOption(
                'save',
                's',
                InputOption::VALUE_REQUIRED,
                'Save report to file'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GEO Readiness Score Calculator');

        $path = $input->getArgument('path');

        try {
            $io->section('Analyzing your website...');

            // Create progress bar
            $progressBar = new ProgressBar($output, 7);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->start();

            // Collect data for scoring
            $data = [];

            // Check content quality
            $progressBar->setMessage('Checking content quality...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessContent($path));

            // Check structured data
            $progressBar->setMessage('Analyzing structured data...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessStructuredData($path));

            // Check authority signals
            $progressBar->setMessage('Evaluating authority signals...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessAuthoritySignals($path));

            // Check technical optimization
            $progressBar->setMessage('Checking technical optimization...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessTechnicalOptimization($path));

            // Check freshness
            $progressBar->setMessage('Evaluating content freshness...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessFreshness($path));

            // Check comprehensiveness
            $progressBar->setMessage('Assessing content comprehensiveness...');
            $progressBar->advance();
            $data = array_merge($data, $this->assessComprehensiveness($path));

            // Calculate final score
            $progressBar->setMessage('Calculating final score...');
            $progressBar->advance();

            $progressBar->finish();
            $io->newLine(2);

            // Calculate score
            $scorer = new GEOReadinessScore();
            $result = $scorer->calculate($data);

            // Display results based on format
            $format = $input->getOption('format');

            switch ($format) {
                case 'json':
                    $output->writeln(json_encode($result, JSON_PRETTY_PRINT));
                    break;

                case 'html':
                    $this->outputHTML($result, $output);
                    break;

                case 'text':
                default:
                    $this->outputText($result, $io, $input);
                    break;
            }

            // Save report if requested
            if ($savePath = $input->getOption('save')) {
                $this->saveReport($result, $savePath, $format, $io);
            }

            // Return appropriate exit code based on score
            if ($result['overall_score'] >= 70) {
                return Command::SUCCESS;
            } else {
                $io->warning('Your GEO score needs improvement. Follow the recommendations above.');
                return Command::SUCCESS; // Still success, just low score
            }

        } catch (\Exception $e) {
            $io->error([
                'Score calculation failed',
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
     * Assess content quality
     */
    private function assessContent(string $path): array
    {
        $data = [];

        // Check for llms.txt
        $llmsPath = $this->findFile($path, 'llms.txt');
        $data['has_llms_txt'] = $llmsPath !== null;

        // Check for content files
        if (is_dir($path)) {
            $htmlFiles = glob($path . '/*.html');
            $mdFiles = glob($path . '/*.md');

            if (!empty($htmlFiles)) {
                $content = file_get_contents($htmlFiles[0]);
                $data['content'] = strip_tags($content);
            } elseif (!empty($mdFiles)) {
                $data['content'] = file_get_contents($mdFiles[0]);
            }
        }

        return $data;
    }

    /**
     * Assess structured data
     */
    private function assessStructuredData(string $path): array
    {
        $data = [
            'has_schema' => false,
            'schema_types' => [],
            'schema_properties' => 0
        ];

        if (is_dir($path)) {
            $htmlFiles = glob($path . '/*.html');
            if (empty($htmlFiles)) {
                $htmlFiles = glob($path . '/public/*.html');
            }

            foreach ($htmlFiles as $file) {
                $content = file_get_contents($file);

                // Check for JSON-LD
                if (preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $content, $matches)) {
                    $data['has_schema'] = true;

                    foreach ($matches[1] as $jsonLd) {
                        $decoded = json_decode($jsonLd, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            if (isset($decoded['@type'])) {
                                $data['schema_types'][] = $decoded['@type'];
                                $data['schema_properties'] += count($decoded);
                            }
                        }
                    }
                }

                // Check for microdata
                if (strpos($content, 'itemscope') !== false) {
                    $data['has_schema'] = true;
                    $data['schema_types'][] = 'Microdata';
                }
            }
        }

        $data['schema_types'] = array_unique($data['schema_types']);

        return $data;
    }

    /**
     * Assess authority signals
     */
    private function assessAuthoritySignals(string $path): array
    {
        return [
            'verified_business' => false, // Would need external verification
            'has_reviews' => false, // Would need to check for review markup
            'average_rating' => 0,
            'external_citations' => [],
            'certifications' => []
        ];
    }

    /**
     * Assess technical optimization
     */
    private function assessTechnicalOptimization(string $path): array
    {
        $data = [
            'mobile_friendly' => false,
            'https' => true, // Assume HTTPS in production
            'has_sitemap' => false,
            'has_robots_txt' => false,
            'page_speed_score' => 75 // Default estimate
        ];

        // Check for sitemap
        $data['has_sitemap'] = $this->findFile($path, 'sitemap.xml') !== null;

        // Check for robots.txt
        $data['has_robots_txt'] = $this->findFile($path, 'robots.txt') !== null;

        // Check for mobile viewport
        if (is_dir($path)) {
            $htmlFiles = glob($path . '/*.html');
            foreach ($htmlFiles as $file) {
                $content = file_get_contents($file);
                if (strpos($content, 'viewport') !== false) {
                    $data['mobile_friendly'] = true;
                    break;
                }
            }
        }

        return $data;
    }

    /**
     * Assess content freshness
     */
    private function assessFreshness(string $path): array
    {
        $data = [
            'last_updated' => null,
            'update_frequency' => 'unknown'
        ];

        if (is_dir($path)) {
            // Get most recent file modification
            $files = glob($path . '/*');
            $mostRecent = 0;

            foreach ($files as $file) {
                if (is_file($file)) {
                    $mtime = filemtime($file);
                    if ($mtime > $mostRecent) {
                        $mostRecent = $mtime;
                    }
                }
            }

            if ($mostRecent > 0) {
                $data['last_updated'] = date('Y-m-d', $mostRecent);

                // Determine update frequency
                $daysSince = (time() - $mostRecent) / 86400;
                if ($daysSince <= 7) {
                    $data['update_frequency'] = 'regular';
                } elseif ($daysSince <= 30) {
                    $data['update_frequency'] = 'monthly';
                } else {
                    $data['update_frequency'] = 'infrequent';
                }
            }
        }

        return $data;
    }

    /**
     * Assess content comprehensiveness
     */
    private function assessComprehensiveness(string $path): array
    {
        $data = [
            'has_faq' => false,
            'has_howto' => false,
            'content_formats' => ['text'],
            'detailed_descriptions' => false
        ];

        if (is_dir($path)) {
            $files = glob($path . '/*');

            foreach ($files as $file) {
                if (is_file($file)) {
                    $basename = basename($file);
                    $content = file_get_contents($file);

                    // Check for FAQ
                    if (stripos($basename, 'faq') !== false ||
                        stripos($content, 'frequently asked') !== false) {
                        $data['has_faq'] = true;
                    }

                    // Check for How-to
                    if (stripos($content, 'how to') !== false ||
                        stripos($content, 'step by step') !== false) {
                        $data['has_howto'] = true;
                    }

                    // Check for images
                    if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $basename)) {
                        $data['content_formats'][] = 'images';
                    }

                    // Check for videos
                    if (preg_match('/\.(mp4|webm|avi|mov)$/i', $basename)) {
                        $data['content_formats'][] = 'videos';
                    }
                }
            }
        }

        $data['content_formats'] = array_unique($data['content_formats']);

        return $data;
    }

    /**
     * Find file in path or subdirectories
     */
    private function findFile(string $path, string $filename): ?string
    {
        $searchPaths = [
            $path . '/' . $filename,
            $path . '/public/' . $filename,
            $path . '/www/' . $filename,
            $path . '/public_html/' . $filename
        ];

        foreach ($searchPaths as $searchPath) {
            if (file_exists($searchPath)) {
                return $searchPath;
            }
        }

        return null;
    }

    /**
     * Output results as text
     */
    private function outputText(array $result, SymfonyStyle $io, InputInterface $input): void
    {
        // Main score display
        $io->section('GEO Readiness Score');

        $score = $result['overall_score'];
        $grade = $result['grade'];

        // Create visual score bar
        $barLength = 50;
        $filledLength = (int)($score / 100 * $barLength);
        $bar = str_repeat('█', $filledLength) . str_repeat('░', $barLength - $filledLength);

        $io->text([
            '',
            "  Score: <comment>{$score}/100</comment> ({$grade})",
            "  [$bar]",
            "  {$result['ai_readiness_level']}",
            ''
        ]);

        // Score breakdown table
        if ($input->getOption('detailed')) {
            $io->section('Detailed Score Breakdown');

            $table = new Table($io);
            $table->setHeaders(['Category', 'Score', 'Weight', 'Contribution']);

            foreach ($result['scores'] as $category => $categoryScore) {
                $weight = $this->getCategoryWeight($category);
                $contribution = round($categoryScore * $weight, 1);

                $table->addRow([
                    ucfirst(str_replace('_', ' ', $category)),
                    round($categoryScore, 1) . '/100',
                    ($weight * 100) . '%',
                    $contribution . ' points'
                ]);
            }

            $table->render();
        }

        // Strengths and weaknesses
        $io->section('Analysis');

        if (!empty($result['strengths'])) {
            $io->text('<info>Strengths:</info>');
            foreach ($result['strengths'] as $strength) {
                $io->text("  ✅ $strength");
            }
        }

        if (!empty($result['weaknesses'])) {
            $io->text('');
            $io->text('<comment>Areas for Improvement:</comment>');
            foreach ($result['weaknesses'] as $weakness) {
                $io->text("  ⚠️  $weakness");
            }
        }

        // Recommendations
        if ($input->getOption('recommendations') || $score < 80) {
            if (!empty($result['recommendations'])) {
                $io->section('Recommendations');
                foreach ($result['recommendations'] as $index => $rec) {
                    $io->text(sprintf('%d. %s', $index + 1, $rec));
                }
            }
        }

        // AI visibility estimate
        $io->section('AI Visibility Potential');
        $io->text($result['estimated_visibility']);
    }

    /**
     * Output results as HTML
     */
    private function outputHTML(array $result, OutputInterface $output): void
    {
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>GEO Readiness Score Report</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .score-display { text-align: center; padding: 30px; background: #f0f0f0; border-radius: 10px; }
        .score { font-size: 48px; font-weight: bold; }
        .grade { font-size: 24px; color: #666; }
        .progress-bar { width: 100%; height: 30px; background: #ddd; border-radius: 15px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #4CAF50, #8BC34A); }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .recommendation { padding: 10px; margin: 10px 0; background: #fff3cd; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <h1>GEO Readiness Score Report</h1>
    <div class="score-display">
        <div class="score">{$result['overall_score']}/100</div>
        <div class="grade">Grade: {$result['grade']}</div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: {$result['overall_score']}%"></div>
        </div>
        <p>{$result['ai_readiness_level']}</p>
    </div>
HTML;

        $html .= $this->generateHTMLTable($result);
        $html .= $this->generateHTMLRecommendations($result);
        $html .= '</body></html>';

        $output->writeln($html);
    }

    /**
     * Generate HTML table for scores
     */
    private function generateHTMLTable(array $result): string
    {
        $html = '<h2>Score Breakdown</h2><table><tr><th>Category</th><th>Score</th></tr>';

        foreach ($result['scores'] as $category => $score) {
            $name = ucfirst(str_replace('_', ' ', $category));
            $html .= "<tr><td>$name</td><td>" . round($score, 1) . "/100</td></tr>";
        }

        $html .= '</table>';
        return $html;
    }

    /**
     * Generate HTML recommendations
     */
    private function generateHTMLRecommendations(array $result): string
    {
        if (empty($result['recommendations'])) {
            return '';
        }

        $html = '<h2>Recommendations</h2>';
        foreach ($result['recommendations'] as $rec) {
            $html .= "<div class=\"recommendation\">$rec</div>";
        }

        return $html;
    }

    /**
     * Get category weight
     */
    private function getCategoryWeight(string $category): float
    {
        $weights = [
            'content_quality' => 0.25,
            'structured_data' => 0.20,
            'authority_signals' => 0.15,
            'technical_optimization' => 0.15,
            'freshness' => 0.10,
            'comprehensiveness' => 0.10,
            'citations' => 0.05
        ];

        return $weights[$category] ?? 0;
    }

    /**
     * Save report to file
     */
    private function saveReport(array $result, string $path, string $format, SymfonyStyle $io): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        switch ($format) {
            case 'json':
                $content = json_encode($result, JSON_PRETTY_PRINT);
                break;

            case 'html':
                ob_start();
                $this->outputHTML($result, new \Symfony\Component\Console\Output\BufferedOutput());
                $content = ob_get_clean();
                break;

            default:
                $content = $this->generateTextReport($result);
        }

        file_put_contents($path, $content);
        $io->success("Report saved to: $path");
    }

    /**
     * Generate text report
     */
    private function generateTextReport(array $result): string
    {
        $report = "GEO READINESS SCORE REPORT\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat('=', 50) . "\n\n";

        $report .= "Overall Score: {$result['overall_score']}/100 ({$result['grade']})\n";
        $report .= "AI Readiness: {$result['ai_readiness_level']}\n\n";

        $report .= "SCORE BREAKDOWN:\n";
        foreach ($result['scores'] as $category => $score) {
            $name = ucfirst(str_replace('_', ' ', $category));
            $report .= sprintf("  %-25s %5.1f/100\n", $name . ':', $score);
        }

        if (!empty($result['recommendations'])) {
            $report .= "\nRECOMMENDATIONS:\n";
            foreach ($result['recommendations'] as $index => $rec) {
                $report .= sprintf("%d. %s\n", $index + 1, $rec);
            }
        }

        return $report;
    }
}