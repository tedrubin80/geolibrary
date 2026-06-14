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
use GEOOptimizer\Analysis\ContentAnalyzer;

/**
 * Analyze content for GEO optimization
 */
class AnalyzeCommand extends Command
{
    protected static $defaultName = 'analyze';
    protected static $defaultDescription = 'Analyze content for AI search engine optimization';

    protected function configure(): void
    {
        $this
            ->setHelp('This command analyzes content and provides GEO optimization recommendations')
            ->addArgument(
                'source',
                InputArgument::REQUIRED,
                'Content source (file path or URL)'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format (text, json, table)',
                'table'
            )
            ->addOption(
                'save',
                's',
                InputOption::VALUE_REQUIRED,
                'Save analysis results to file'
            )
            ->addOption(
                'verbose-analysis',
                null,
                InputOption::VALUE_NONE,
                'Show detailed analysis breakdown'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('GEO Optimizer - Content Analysis');

        $source = $input->getArgument('source');

        try {
            // Get content
            $content = $this->getContent($source, $io);

            if (empty($content)) {
                $io->error('No content found to analyze');
                return Command::FAILURE;
            }

            $io->section('Analyzing content...');
            $io->progressStart(100);

            // Initialize analyzer
            $analyzer = new ContentAnalyzer();

            // Analyze content
            $io->progressAdvance(30);
            $results = $analyzer->analyzeForGEO($content);

            $io->progressAdvance(40);

            // Calculate additional metrics
            $wordCount = str_word_count($content);
            $charCount = strlen($content);
            $readingTime = ceil($wordCount / 200); // Average reading speed

            $io->progressAdvance(30);
            $io->progressFinish();

            // Display results based on format
            $format = $input->getOption('format');

            switch ($format) {
                case 'json':
                    $this->outputJson($results, $output);
                    break;

                case 'text':
                    $this->outputText($results, $io, [
                        'word_count' => $wordCount,
                        'char_count' => $charCount,
                        'reading_time' => $readingTime
                    ]);
                    break;

                case 'table':
                default:
                    $this->outputTable($results, $io, [
                        'word_count' => $wordCount,
                        'char_count' => $charCount,
                        'reading_time' => $readingTime
                    ]);
                    break;
            }

            // Save results if requested
            if ($savePath = $input->getOption('save')) {
                $this->saveResults($results, $savePath, $io);
            }

            // Show detailed breakdown if requested
            if ($input->getOption('verbose-analysis')) {
                $this->showDetailedAnalysis($results, $io);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Analysis failed',
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
     * Get content from file or URL
     */
    private function getContent(string $source, SymfonyStyle $io): string
    {
        // Check if it's a URL
        if (filter_var($source, FILTER_VALIDATE_URL)) {
            $io->text("Fetching content from URL: $source");
            $content = file_get_contents($source);

            if ($content === false) {
                throw new \RuntimeException("Failed to fetch content from URL");
            }

            // Strip HTML tags if present
            $content = strip_tags($content);
        } elseif (file_exists($source)) {
            $io->text("Reading content from file: $source");
            $content = file_get_contents($source);

            if ($content === false) {
                throw new \RuntimeException("Failed to read file");
            }

            // Strip HTML tags if it's an HTML file
            if (pathinfo($source, PATHINFO_EXTENSION) === 'html') {
                $content = strip_tags($content);
            }
        } else {
            // Treat as direct content
            $content = $source;
        }

        return trim($content);
    }

    /**
     * Output results as JSON
     */
    private function outputJson(array $results, OutputInterface $output): void
    {
        $output->writeln(json_encode($results, JSON_PRETTY_PRINT));
    }

    /**
     * Output results as formatted text
     */
    private function outputText(array $results, SymfonyStyle $io, array $stats): void
    {
        $io->section('Content Statistics');
        $io->listing([
            "Words: {$stats['word_count']}",
            "Characters: {$stats['char_count']}",
            "Reading Time: {$stats['reading_time']} minutes"
        ]);

        $io->section('GEO Analysis Results');
        $io->text([
            "GEO Score: <comment>{$results['geo_score']}/100</comment>",
            "Readability Score: <comment>{$results['readability_score']}/100</comment>",
            "Authority Score: <comment>{$results['authority_score']}/10</comment>",
            "Keyword Density: <comment>" . round($results['keyword_density'] * 100, 2) . "%</comment>",
            "Content Structure: <comment>{$results['structure_score']}/100</comment>"
        ]);

        if (!empty($results['recommendations'])) {
            $io->section('Recommendations');
            $io->listing($results['recommendations']);
        }
    }

    /**
     * Output results as table
     */
    private function outputTable(array $results, SymfonyStyle $io, array $stats): void
    {
        // Content statistics table
        $io->section('Content Statistics');
        $statsTable = new Table($io);
        $statsTable->setHeaders(['Metric', 'Value']);
        $statsTable->addRows([
            ['Word Count', number_format($stats['word_count'])],
            ['Character Count', number_format($stats['char_count'])],
            ['Estimated Reading Time', "{$stats['reading_time']} minutes"],
        ]);
        $statsTable->render();

        // GEO scores table
        $io->section('GEO Analysis Scores');
        $scoresTable = new Table($io);
        $scoresTable->setHeaders(['Metric', 'Score', 'Status']);
        $scoresTable->addRows([
            [
                'Overall GEO Score',
                "{$results['geo_score']}/100",
                $this->getStatus($results['geo_score'])
            ],
            [
                'Readability',
                "{$results['readability_score']}/100",
                $this->getStatus($results['readability_score'])
            ],
            [
                'Authority Signals',
                "{$results['authority_score']}/10",
                $this->getStatus($results['authority_score'] * 10)
            ],
            [
                'Keyword Density',
                round($results['keyword_density'] * 100, 2) . '%',
                $results['keyword_density'] > 0.01 && $results['keyword_density'] < 0.03 ? '✅' : '⚠️'
            ],
            [
                'Content Structure',
                "{$results['structure_score']}/100",
                $this->getStatus($results['structure_score'])
            ]
        ]);
        $scoresTable->render();

        // Recommendations
        if (!empty($results['recommendations'])) {
            $io->section('Optimization Recommendations');
            foreach ($results['recommendations'] as $index => $recommendation) {
                $io->text(sprintf(
                    '<comment>%d.</comment> %s',
                    $index + 1,
                    $recommendation
                ));
            }
        }
    }

    /**
     * Get status emoji based on score
     */
    private function getStatus(float $score): string
    {
        if ($score >= 80) return '✅ Excellent';
        if ($score >= 60) return '👍 Good';
        if ($score >= 40) return '⚠️ Fair';
        return '❌ Poor';
    }

    /**
     * Save analysis results to file
     */
    private function saveResults(array $results, string $path, SymfonyStyle $io): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        switch ($extension) {
            case 'json':
                $content = json_encode($results, JSON_PRETTY_PRINT);
                break;

            case 'md':
            case 'markdown':
                $content = $this->formatAsMarkdown($results);
                break;

            default:
                $content = print_r($results, true);
        }

        file_put_contents($path, $content);
        $io->success("Analysis results saved to: $path");
    }

    /**
     * Format results as markdown
     */
    private function formatAsMarkdown(array $results): string
    {
        $md = "# GEO Content Analysis Report\n\n";
        $md .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";

        $md .= "## Scores\n\n";
        $md .= "| Metric | Score |\n";
        $md .= "|--------|-------|\n";
        $md .= "| GEO Score | {$results['geo_score']}/100 |\n";
        $md .= "| Readability | {$results['readability_score']}/100 |\n";
        $md .= "| Authority | {$results['authority_score']}/10 |\n";
        $md .= "| Keyword Density | " . round($results['keyword_density'] * 100, 2) . "% |\n";
        $md .= "| Structure | {$results['structure_score']}/100 |\n\n";

        if (!empty($results['recommendations'])) {
            $md .= "## Recommendations\n\n";
            foreach ($results['recommendations'] as $rec) {
                $md .= "- $rec\n";
            }
        }

        return $md;
    }

    /**
     * Show detailed analysis breakdown
     */
    private function showDetailedAnalysis(array $results, SymfonyStyle $io): void
    {
        $io->section('Detailed Analysis Breakdown');

        // Authority keywords found
        if (!empty($results['authority_keywords_found'])) {
            $io->text('Authority Keywords Detected:');
            $io->listing($results['authority_keywords_found']);
        }

        // Readability metrics
        $io->text('Readability Metrics:');
        $io->listing([
            'Average sentence length: ' . ($results['avg_sentence_length'] ?? 'N/A'),
            'Complex word ratio: ' . ($results['complex_word_ratio'] ?? 'N/A'),
            'Paragraph count: ' . ($results['paragraph_count'] ?? 'N/A')
        ]);

        // Structure analysis
        if (!empty($results['has_headings'])) {
            $io->text('Content Structure:');
            $io->listing([
                'Has headings: ' . ($results['has_headings'] ? 'Yes' : 'No'),
                'Has lists: ' . ($results['has_lists'] ?? false ? 'Yes' : 'No'),
                'Has questions: ' . ($results['has_questions'] ?? false ? 'Yes' : 'No')
            ]);
        }
    }
}