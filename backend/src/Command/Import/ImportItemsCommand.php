<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\ItemRepository;
use App\Service\Import\EntryParser;
use App\Service\Import\ItemImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:items',
    description: 'Import D&D items from 5etools JSON files',
)]
class ImportItemsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ItemRepository $itemRepo,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing')
             ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Only import items from this source code (e.g. PHB)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $sourceFilter = $input->getOption('source');

        $dataDir = $this->projectDir . '/data/srd/en';
        $files = [
            $dataDir . '/items.json',
            $dataDir . '/items-base.json',
        ];

        $mapper = new ItemImportMapper($this->em, new EntryParser());

        if (!$isDryRun && !$sourceFilter) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['item_weapon_property', 'item_weapon_damage'] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Child tables truncated (item_weapon_property, item_weapon_damage).');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $batchSize = 50;
        $i = 0;
        $processedIds = [];

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $io->warning('File not found: ' . $file);
                continue;
            }

            $data = json_decode(file_get_contents($file), true);
            $items = array_merge($data['item'] ?? [], $data['baseitem'] ?? []);

            foreach ($items as $itemData) {
                $sourceCode = $itemData['source'] ?? 'PHB';
                if ($sourceFilter && strtoupper($sourceFilter) !== strtoupper($sourceCode)) {
                    $skipped++;
                    continue;
                }

                /** @var ContentSource|null $source */
                $source = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $sourceCode]);
                if (!$source) {
                    $skipped++;
                    continue;
                }

                $existing = $this->itemRepo->findByName($itemData['name']);

                if ($existing && isset($processedIds[$existing->getId()])) {
                    if (!$existing->hasSource($source)) {
                        $existing->addSource($source);
                    }
                    $skipped++;
                    if (!$isDryRun && (++$i % $batchSize === 0)) {
                        $this->em->flush();
                        $this->em->clear();
                        $processedIds = [];
                    }
                    continue;
                }

                $item = $mapper->map($itemData, $existing);

                if (!$existing) {
                    $item->addSource($source);
                    $this->em->persist($item);
                    $imported++;
                } else {
                    if (!$existing->hasSource($source)) {
                        $existing->addSource($source);
                    }
                    $processedIds[$existing->getId()] = true;
                    $updated++;
                }

                if (!$isDryRun && (++$i % $batchSize === 0)) {
                    $this->em->flush();
                    $this->em->clear();
                    $processedIds = [];
                }
            }

            $io->info('Processed: ' . basename($file));
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d, update %d, skip %d items', $imported, $updated, $skipped));
        } else {
            $io->success(sprintf('Imported %d new, updated %d, skipped %d items', $imported, $updated, $skipped));
        }

        return Command::SUCCESS;
    }
}
