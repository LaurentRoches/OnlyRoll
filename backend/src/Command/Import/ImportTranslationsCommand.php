<?php

declare(strict_types=1);

namespace App\Command\Import;

use App\Service\Import\SrdTranslationImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:translations',
    description: 'Import SRD translations from 5etools-translated JSON files',
)]
class ImportTranslationsCommand extends Command
{
    /** Maps entity types to their JSON file config (relative to locale data dir) */
    private const ENTITY_CONFIG = [
        'spell'      => ['type' => 'dir', 'dir' => 'spells', 'pattern' => 'spells-*.json', 'key' => 'spell'],
        'race'       => ['type' => 'single', 'file' => 'races.json', 'key' => 'race'],
        'class'      => ['type' => 'dir', 'dir' => 'class', 'pattern' => 'class-*.json', 'key' => 'class'],
        'monster'    => ['type' => 'dir', 'dir' => 'bestiary', 'pattern' => 'bestiary-*.json', 'key' => 'monster'],
        'item'       => ['type' => 'multi_files', 'files' => ['items.json', 'items-base.json'], 'key' => 'item'],
        'background' => ['type' => 'single', 'file' => 'backgrounds.json', 'key' => 'background'],
        'feat'       => ['type' => 'single', 'file' => 'feats.json', 'key' => 'feat'],
    ];

    public function __construct(
        private readonly SrdTranslationImportService $importService,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('locale', null, InputOption::VALUE_REQUIRED, 'Target locale (e.g. fr)', 'fr')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'Only import a specific entity type (spell, race, class, monster, item, background, feat)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing to database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getOption('locale');
        $entityFilter = $input->getOption('entity');
        $isDryRun = $input->getOption('dry-run');

        $dataDir = $this->projectDir . '/data/srd/' . $locale;
        if (!is_dir($dataDir)) {
            $io->error("Data directory not found: {$dataDir}");
            $io->note("Please place translated JSON files in: {$dataDir}");
            return Command::FAILURE;
        }

        $entities = $entityFilter
            ? [$entityFilter => self::ENTITY_CONFIG[$entityFilter] ?? null]
            : self::ENTITY_CONFIG;

        $totalImported = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;

        foreach ($entities as $srdTable => $config) {
            if ($config === null) {
                $io->warning("Unknown entity type: {$srdTable}");
                continue;
            }

            $io->section("Importing {$locale} translations for: {$srdTable}");

            $entries = $this->loadEntries($dataDir, $srdTable, $config, $io);
            if (empty($entries)) {
                $io->note("No entries found for {$srdTable}, skipping.");
                continue;
            }

            $progressBar = new ProgressBar($output, count($entries));
            $progressBar->start();

            $result = $this->importService->importTranslations(
                srdTable: $srdTable,
                frEntries: $entries,
                locale: $locale,
                isDryRun: $isDryRun,
                onProgress: fn() => $progressBar->advance(),
            );

            $progressBar->finish();
            $io->newLine(2);

            $io->text(sprintf(
                'Imported: %d | Updated: %d | Skipped: %d',
                $result['imported'], $result['updated'], $result['skipped']
            ));

            $totalImported += $result['imported'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];

            if ($srdTable === 'class') {
                $classFeatures = $this->loadRootKey($dataDir, $config, 'classFeature');
                if (!empty($classFeatures)) {
                    $io->text(sprintf('Importing %d class feature translations...', count($classFeatures)));
                    $cfResult = $this->importService->importClassFeatureTranslations($classFeatures, $locale, $isDryRun);
                    $io->text(sprintf('  Class features — Imported: %d | Skipped: %d', $cfResult['imported'], $cfResult['skipped']));
                }
            }
        }

        $prefix = $isDryRun ? '[DRY RUN] ' : '';
        $io->success(sprintf(
            '%sTranslation import complete. Imported: %d | Updated: %d | Skipped: %d',
            $prefix, $totalImported, $totalUpdated, $totalSkipped
        ));

        return Command::SUCCESS;
    }

    /**
     * Load JSON entries based on entity configuration.
     */
    private function loadEntries(string $dataDir, string $srdTable, array $config, SymfonyStyle $io): array
    {
        $entries = [];

        switch ($config['type']) {
            case 'single':
                $file = $dataDir . '/' . $config['file'];
                if (!file_exists($file)) {
                    $io->warning("File not found: {$file}");
                    return [];
                }
                $data = json_decode(file_get_contents($file), true);
                $entries = $data[$config['key']] ?? [];
                break;

            case 'multi':
                $indexFile = $dataDir . '/' . $config['indexFile'];
                if (!file_exists($indexFile)) {
                    $io->warning("Index file not found: {$indexFile}");
                    return [];
                }
                $index = json_decode(file_get_contents($indexFile), true);
                $spellDir = dirname($indexFile);
                foreach ($index as $sourceCode => $filename) {
                    $sourceFile = $spellDir . '/' . $filename;
                    if (file_exists($sourceFile)) {
                        $data = json_decode(file_get_contents($sourceFile), true);
                        $sourceEntries = $data[$config['key']] ?? [];
                        $entries = array_merge($entries, $sourceEntries);
                    }
                }
                break;

            case 'dir':
                $dir = $dataDir . '/' . $config['dir'];
                if (!is_dir($dir)) {
                    $io->warning("Directory not found: {$dir}");
                    return [];
                }
                $files = glob($dir . '/' . $config['pattern']);
                foreach ($files as $file) {
                    $data = json_decode(file_get_contents($file), true);
                    $fileEntries = $data[$config['key']] ?? [];
                    $entries = array_merge($entries, $fileEntries);
                }
                break;

            case 'multi_files':
                foreach ($config['files'] as $filename) {
                    $file = $dataDir . '/' . $filename;
                    if (file_exists($file)) {
                        $data = json_decode(file_get_contents($file), true);
                        $fileEntries = $data[$config['key']] ?? [];
                        $entries = array_merge($entries, $fileEntries);
                    }
                }
                break;
        }

        return $entries;
    }

    /**
     * Load entries from a specific root-level key across all files
     */
    private function loadRootKey(string $dataDir, array $config, string $rootKey): array
    {
        $entries = [];

        if ($config['type'] === 'dir') {
            $dir = $dataDir . '/' . $config['dir'];
            if (!is_dir($dir)) {
                return [];
            }
            $files = glob($dir . '/' . $config['pattern']);
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                $fileEntries = $data[$rootKey] ?? [];
                $entries = array_merge($entries, $fileEntries);
            }
        }

        return $entries;
    }
}
