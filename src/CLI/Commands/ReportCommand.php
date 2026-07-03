<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use GEOOptimizer\Analytics\GEOReadinessScore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReportCommand extends Command
{
    protected static $defaultName = 'report';
    protected static $defaultDescription = 'Generate a combined GEO validation and readiness report';

    protected function configure(): void
    {
        $this
            ->setHelp('Runs validation checks and readiness scoring, then outputs a combined report.')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Path to website root',
                '.'
            )
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: text or json',
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
        $path = (string) $input->getArgument('path');

        if (!is_dir($path)) {
            $io->error("Path not found: {$path}");

            return Command::FAILURE;
        }

        $validateInput = new ArrayInput([
            'path' => $path,
            '--all' => true,
        ]);
        $validateInput->setInteractive(false);

        $validateCommand = new ValidateCommand();
        $validationExitCode = $validateCommand->run($validateInput, $output);

        $scoreData = $this->collectScoreData($path);
        $score = (new GEOReadinessScore())->calculate($scoreData);

        $report = [
            'generated_at' => date('c'),
            'path' => realpath($path) ?: $path,
            'validation_passed' => $validationExitCode === Command::SUCCESS,
            'readiness' => $score,
        ];

        if ($input->getOption('format') === 'json') {
            $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $output->writeln($encoded !== false ? $encoded : '{}');
        } else {
            $io->section('Combined GEO Report');
            $io->writeln('Validation: ' . ($report['validation_passed'] ? 'passed' : 'needs attention'));
            $io->writeln(sprintf(
                'Readiness score: %s/100 (%s)',
                $score['overall_score'],
                $score['grade']
            ));

            if (!empty($score['recommendations'])) {
                $io->section('Top Recommendations');
                foreach (array_slice($score['recommendations'], 0, 5) as $index => $recommendation) {
                    $io->writeln(sprintf('%d. %s', $index + 1, $recommendation));
                }
            }
        }

        if ($savePath = $input->getOption('save')) {
            $contents = $input->getOption('format') === 'json'
                ? json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                : $this->buildTextReport($report);

            file_put_contents($savePath, $contents !== false ? $contents : '');
            $io->success("Report saved to {$savePath}");
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectScoreData(string $path): array
    {
        $data = [
            'has_llms_txt' => $this->findFile($path, 'llms.txt') !== null,
            'has_schema' => false,
            'schema_types' => [],
            'schema_properties' => 0,
            'has_sitemap' => $this->findFile($path, 'sitemap.xml') !== null,
            'has_robots_txt' => $this->findFile($path, 'robots.txt') !== null,
            'mobile_friendly' => false,
            'https' => true,
        ];

        $htmlFiles = array_merge(
            glob($path . '/*.html') ?: [],
            glob($path . '/public/*.html') ?: []
        );

        foreach ($htmlFiles as $file) {
            $content = (string) file_get_contents($file);
            if (str_contains($content, 'application/ld+json')) {
                $data['has_schema'] = true;
            }
            if (str_contains($content, 'viewport')) {
                $data['mobile_friendly'] = true;
            }
            if ($data['content'] ?? null) {
                continue;
            }
            $data['content'] = trim(strip_tags($content));
        }

        return $data;
    }

    private function findFile(string $path, string $filename): ?string
    {
        foreach ([$path, $path . '/public', $path . '/www'] as $base) {
            $candidate = $base . '/' . $filename;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildTextReport(array $report): string
    {
        $score = $report['readiness'];
        $text = "GEO COMBINED REPORT\n";
        $text .= 'Generated: ' . $report['generated_at'] . "\n";
        $text .= 'Path: ' . $report['path'] . "\n";
        $text .= 'Validation: ' . ($report['validation_passed'] ? 'passed' : 'needs attention') . "\n";
        $text .= sprintf("Readiness: %s/100 (%s)\n", $score['overall_score'], $score['grade']);

        return $text;
    }
}
