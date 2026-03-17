<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Service\Import\SpellImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:spells',
    description: 'Import D&D spells from 5etools JSON files',
)]
class ImportSpellsCommand extends Command
{
    public function __construct(
        private readonly SpellImportService $importService,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Only import a specific source (e.g. PHB)')
             ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing to database')
             ->addOption('link-classes', null, InputOption::VALUE_NONE, 'Also link spells to classes (run after ImportClassesCommand)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io           = new SymfonyStyle($input, $output);
        $isDryRun     = $input->getOption('dry-run');
        $filterSource = $input->getOption('source');
        $linkClasses  = $input->getOption('link-classes');

        $dataDir   = $this->projectDir . '/data/srd/en/spells';
        $indexFile = $dataDir . '/index.json';

        if (!file_exists($indexFile)) {
            $io->error('Spells index not found: ' . $indexFile);
            return Command::FAILURE;
        }

        $index = json_decode(file_get_contents($indexFile), true);

        if (!$isDryRun && !$filterSource) {
            $this->importService->truncateChildTables();
            $io->note('Child tables truncated (spell_component, spell_class, spell_damage_type).');
        }

        $totalImported = 0;
        $totalUpdated  = 0;
        $totalSkipped  = 0;

        foreach ($index as $sourceCode => $filename) {
            if ($filterSource && strtoupper($filterSource) !== strtoupper($sourceCode)) {
                continue;
            }

            $filePath = $dataDir . '/' . $filename;
            if (!file_exists($filePath)) {
                $io->warning("File not found: $filePath, skipping.");
                continue;
            }

            $io->section("Importing spells from $sourceCode ($filename)");

            $data   = json_decode(file_get_contents($filePath), true);
            $spells = $data['spell'] ?? [];

            $io->progressStart(count($spells));

            $result = $this->importService->importSpells(
                spells: $spells,
                sourceCode: $sourceCode,
                isDryRun: $isDryRun,
                onProgress: fn() => $io->progressAdvance(),
            );

            if ($result['skipped'] === count($spells)) {
                $io->warning("Unknown source: $sourceCode — skipping.");
            }

            $totalImported += $result['imported'];
            $totalUpdated  += $result['updated'];
            $totalSkipped  += $result['skipped'];

            $io->progressFinish();
            $io->info(sprintf('Processed %d spells from %s', count($spells), $sourceCode));
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d new, update %d existing, skip %d spells', $totalImported, $totalUpdated, $totalSkipped));
            return Command::SUCCESS;
        }

        $io->success(sprintf('Imported %d new, updated %d existing, skipped %d spells', $totalImported, $totalUpdated, $totalSkipped));

        if ($linkClasses) {
            $io->section('Linking spells to classes...');
            foreach ($index as $sourceCode => $filename) {
                if ($filterSource && strtoupper($filterSource) !== strtoupper($sourceCode)) {
                    continue;
                }
                $filePath = $dataDir . '/' . $filename;
                if (!file_exists($filePath)) {
                    continue;
                }
                $data = json_decode(file_get_contents($filePath), true);
                $this->importService->linkSpellRelations($data['spell'] ?? []);
            }
            $io->success('Class links updated.');
        }

        return Command::SUCCESS;
    }
}
