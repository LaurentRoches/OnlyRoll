<?php

namespace App\Command\Import;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import:all',
    description: 'Run all D&D import commands in dependency order',
)]
class ImportAllCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Pass --dry-run to all sub-commands')
             ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Filter source for all sub-commands')
             ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Import locale: en, fr, or all (default: all)', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $source = $input->getOption('source');
        $locale = $input->getOption('locale');

        $application = $this->getApplication();

        if ($locale === 'en' || $locale === 'all') {
            $io->title('Importing English SRD data');

            $commands = [
                ['app:import:skills',      []],
                ['app:import:conditions',  []],
                ['app:import:spells',      []],
                ['app:import:races',       []],
                ['app:import:classes',     []],
                ['app:import:spells',      ['--link-classes' => true]],
                ['app:import:backgrounds', []],
                ['app:import:feats',       []],
                ['app:import:items',       []],
                ['app:import:monsters',    []],
            ];

            foreach ($commands as [$commandName, $extraArgs]) {
                $label = $commandName . ($extraArgs ? ' ' . implode(' ', array_keys($extraArgs)) : '');
                $io->section('Running: ' . $label);

                $command = $application->find($commandName);
                $args = array_merge(['command' => $commandName], $extraArgs);

                if ($isDryRun) {
                    $args['--dry-run'] = true;
                }
                if ($source) {
                    $args['--source'] = $source;
                }

                $definition = $command->getDefinition();
                if ($source && !$definition->hasOption('source')) {
                    unset($args['--source']);
                }

                $subInput = new ArrayInput($args);
                $subInput->setInteractive(false);

                $returnCode = $command->run($subInput, $output);

                if ($returnCode !== Command::SUCCESS) {
                    $io->error('Command ' . $commandName . ' failed with code ' . $returnCode);
                    return Command::FAILURE;
                }
            }
        }

        if ($locale === 'fr' || $locale === 'all') {
            $io->title('Importing French translations');

            $command = $application->find('app:import:translations');
            $args = [
                'command' => 'app:import:translations',
                '--locale' => 'fr',
            ];

            if ($isDryRun) {
                $args['--dry-run'] = true;
            }

            $subInput = new ArrayInput($args);
            $subInput->setInteractive(false);

            $returnCode = $command->run($subInput, $output);

            if ($returnCode !== Command::SUCCESS) {
                $io->warning('French translation import failed (non-blocking). Continuing...');
            }
        }

        $io->success('All imports completed successfully.');
        return Command::SUCCESS;
    }
}
