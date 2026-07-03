<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use GEOOptimizer\GEOOptimizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CompareCommand extends Command
{
    protected static $defaultName = 'compare';
    protected static $defaultDescription = 'Compare primary content against competitor pages';

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'JSON file with primary and competitors')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getOption('file');

        if ($file === '' || !is_file($file)) {
            $io->error('Provide a valid JSON file via --file.');

            return Command::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($file), true);
        if (!is_array($payload)) {
            $io->error('Invalid JSON file.');

            return Command::FAILURE;
        }

        $geo = new GEOOptimizer();
        $result = $geo->compareCompetitors(
            (string) ($payload['primary_name'] ?? 'Primary'),
            (string) ($payload['primary_content'] ?? ''),
            is_array($payload['competitors'] ?? null) ? $payload['competitors'] : [],
            is_array($payload['options'] ?? null) ? $payload['options'] : []
        );

        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->title('Competitor Comparison');
        $io->writeln(sprintf(
            '%s ranks #%d with a score of %s.',
            $result['primary']['name'],
            $result['primary']['rank'],
            $result['primary']['score']
        ));

        foreach ($result['entries'] as $entry) {
            $io->writeln(sprintf('- %s (%s): %s/100', $entry['name'], $entry['role'], $entry['overall_score']));
        }

        return Command::SUCCESS;
    }
}
