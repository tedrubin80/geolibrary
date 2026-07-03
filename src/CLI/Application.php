<?php

declare(strict_types=1);

namespace GEOOptimizer\CLI;

use Symfony\Component\Console\Application as ConsoleApplication;
use GEOOptimizer\CLI\Commands\GenerateCommand;
use GEOOptimizer\CLI\Commands\AnalyzeCommand;
use GEOOptimizer\CLI\Commands\SchemaCommand;
use GEOOptimizer\CLI\Commands\ValidateCommand;
use GEOOptimizer\CLI\Commands\ScoreCommand;
use GEOOptimizer\CLI\Commands\CacheClearCommand;
use GEOOptimizer\CLI\Commands\BulkAnalyzeCommand;
use GEOOptimizer\CLI\Commands\CompareCommand;
use GEOOptimizer\CLI\Commands\IndustriesCommand;
use GEOOptimizer\CLI\Commands\ReportCommand;

/**
 * GEO Optimizer CLI Application
 *
 * Main entry point for the command-line interface
 */
class Application extends ConsoleApplication
{
    const VERSION = \GEOOptimizer\Version::VERSION;
    const NAME = 'GEO Optimizer';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->setLogo();
        $this->registerCommands();
    }

    /**
     * Register all available commands
     */
    private function registerCommands(): void
    {
        $this->addCommands([
            new GenerateCommand(),
            new AnalyzeCommand(),
            new SchemaCommand(),
            new ValidateCommand(),
            new ScoreCommand(),
            new ReportCommand(),
            new IndustriesCommand(),
            new BulkAnalyzeCommand(),
            new CompareCommand(),
            new CacheClearCommand(),
        ]);
    }

    /**
     * Set custom logo for the application
     */
    private function setLogo(): void
    {
        // Logo is displayed via getLongVersion instead
    }

    /**
     * Get the application's long version string
     */
    public function getLongVersion(): string
    {
        $logo = <<<'LOGO'

   _____ ______ ____     ____        _   _           _
  / ____|  ____/ __ \   / __ \      | | (_)         (_)
 | |  __| |__ | |  | | | |  | |_ __ | |_ _ _ __ ___  _ _______ _ __
 | | |_ |  __|| |  | | | |  | | '_ \| __| | '_ ` _ \| |_  / _ \ '__|
 | |__| | |___| |__| | | |__| | |_) | |_| | | | | | | |/ /  __/ |
  \_____|______\____/   \____/| .__/ \__|_|_| |_| |_|_/___\___|_|
                               | |
                               |_|

LOGO;

        return $logo . PHP_EOL . sprintf(
            '<info>%s</info> version <comment>%s</comment>',
            $this->getName(),
            $this->getVersion()
        ) . PHP_EOL . 'The first comprehensive PHP library for AI search optimization';
    }
}