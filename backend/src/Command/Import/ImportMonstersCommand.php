<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\MonsterRepository;
use App\Service\Import\EntryParser;
use App\Service\Import\MonsterImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:monsters',
    description: 'Import D&D monsters from 5etools bestiary JSON files',
)]
class ImportMonstersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MonsterRepository $monsterRepo,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing')
             ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Only import from this source code (e.g. MM)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $sourceFilter = $input->getOption('source');


        $bestiaryDir = $this->projectDir . '/data/srd/en/bestiary';
        if (!is_dir($bestiaryDir)) {
            $io->error('Bestiary directory not found: ' . $bestiaryDir);
            return Command::FAILURE;
        }

        $files = glob($bestiaryDir . '/bestiary-*.json');
        if (!$files) {
            $io->warning('No bestiary JSON files found.');
            return Command::SUCCESS;
        }

        $mapper = new MonsterImportMapper($this->em, new EntryParser());

        if (!$isDryRun && !$sourceFilter) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach ([
                'monster_trait', 'monster_action', 'monster_saving_throw', 'monster_skill',
                'monster_sense', 'monster_damage_resistance', 'monster_condition_immunity',
                'monster_subtype', 'monster_language', 'monster_environment',
            ] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Monster child tables truncated.');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $batchSize = 10;
        $i = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            $monsters = $data['monster'] ?? [];
            unset($data);

            foreach ($monsters as $monsterData) {
                $sourceCode = $monsterData['source'] ?? 'MM';
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

                $existing = $this->monsterRepo->findByName($monsterData['name']);
                try {
                    $monster = $mapper->map($monsterData, $existing);
                } catch (\Throwable $e) {
                    $trace = $e->getTraceAsString();
                    $io->error('Map error for "' . ($monsterData['name'] ?? '?') . '" [' . $sourceCode . ']: ' . $e->getMessage() . "\n" . substr($trace, 0, 500));
                    $skipped++;
                    continue;
                }

                if (!$existing) {
                    $monster->addSource($source);
                    $this->em->persist($monster);
                    $imported++;
                } else {
                    if (!$existing->hasSource($source)) {
                        $existing->addSource($source);
                    }
                    $updated++;
                }

                if (!$isDryRun && (++$i % $batchSize === 0)) {
                    try {
                        $this->em->flush();
                    } catch (\Exception $e) {
                        $io->error('Flush failed near monster "' . ($monsterData['name'] ?? '?') . '": ' . $e->getMessage());
                        $this->em->clear();
                        continue;
                    }
                    $this->em->clear();
                }
            }

            if (!$isDryRun) {
                try {
                    $this->em->flush();
                } catch (\Exception $e) {
                    $io->error('Final flush failed for ' . basename($file) . ': ' . $e->getMessage());
                }
                $this->em->clear();
            }

            $io->info('Processed: ' . basename($file));
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d, update %d, skip %d monsters', $imported, $updated, $skipped));
        } else {
            $io->success(sprintf('Imported %d new, updated %d, skipped %d monsters', $imported, $updated, $skipped));
        }

        return Command::SUCCESS;
    }
}
