<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use GEOOptimizer\GEOOptimizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BulkAnalyzeCommand extends Command
{
    protected static $defaultName = 'bulk-analyze';
    protected static $defaultDescription = 'Analyze multiple content items in one batch';

    protected function configure(): void
    {
        $this
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'JSON file with items array')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text or json', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getOption('file');

        if ($file === '' || !is_file($file)) {
            $io->error('Provide a valid JSON file via --file containing an items array.');

            return Command::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        if (!is_array($decoded) || !is_array($decoded['items'] ?? null)) {
            $io->error('JSON file must contain an items array.');

            return Command::FAILURE;
        }

        $geo = new GEOOptimizer();
        $result = $geo->bulkAnalyze($decoded['items']);

        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return Command::SUCCESS;
        }

        $io->title('Bulk GEO Analysis');
        $io->writeln(sprintf(
            'Analyzed %d items. Average score: %s',
            $result['count'],
            $result['average_score']
        ));

        foreach ($result['results'] as $entry) {
            $io->writeln(sprintf('- %s: %s/100', $entry['id'], $entry['overall_score']));
        }

        return Command::SUCCESS;
    }
}
