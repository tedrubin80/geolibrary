<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI\Commands;

use GEOOptimizer\GEOOptimizer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class IndustriesCommand extends Command
{
    protected static $defaultName = 'industries';
    protected static $defaultDescription = 'List available GEO industry templates';

    protected function configure(): void
    {
        $this
            ->setHelp('Lists industries supported by GeoOptimizer templates and schema mappings.')
            ->addOption(
                'format',
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format: text or json',
                'text'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $geo = new GEOOptimizer();
        $industries = $geo->getAvailableIndustries();

        if ($input->getOption('format') === 'json') {
            $output->writeln(json_encode(['industries' => $industries], JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        $io->title('Supported Industries');
        foreach ($industries as $industry) {
            $io->writeln('- ' . ucwords(str_replace('_', ' ', $industry)));
        }

        return Command::SUCCESS;
    }
}
