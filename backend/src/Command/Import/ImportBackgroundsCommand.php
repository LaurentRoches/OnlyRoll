<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\BackgroundRepository;
use App\Service\Import\BackgroundImportMapper;
use App\Service\Import\EntryParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:backgrounds',
    description: 'Import D&D backgrounds from 5etools JSON file',
)]
class ImportBackgroundsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BackgroundRepository $bgRepo,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing')
             ->addOption('source', null, InputOption::VALUE_REQUIRED, 'Only import from this source code');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $sourceFilter = $input->getOption('source');

        $file = $this->projectDir . '/data/srd/en/backgrounds.json';
        if (!file_exists($file)) {
            $io->error('File not found: ' . $file);
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        $mapper = new BackgroundImportMapper($this->em, new EntryParser());

        if (!$isDryRun && !$sourceFilter) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['background_skill', 'background_language', 'background_equipment'] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Child tables truncated (background_skill, background_language, background_equipment).');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $batchSize = 50;
        $i = 0;
        $processedIds = [];

        $allByKey = [];
        foreach ($data['background'] ?? [] as $bg) {
            $allByKey[($bg['name'] ?? '') . '|' . ($bg['source'] ?? '')] = $bg;
        }

        foreach ($data['background'] ?? [] as $bgData) {
            if (isset($bgData['_copy'])) {
                $bgData = $this->resolveCopy($bgData, $allByKey);
            }
            $sourceCode = $bgData['source'] ?? 'PHB';
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

            $existing = $this->bgRepo->findByName($bgData['name']);

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

            $background = $mapper->map($bgData, $existing);

            if (!$existing) {
                $background->addSource($source);
                $this->em->persist($background);
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

        if (!$isDryRun) {
            $this->em->flush();
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d, update %d, skip %d backgrounds', $imported, $updated, $skipped));
        } else {
            $io->success(sprintf('Imported %d new, updated %d, skipped %d backgrounds', $imported, $updated, $skipped));
        }

        return Command::SUCCESS;
    }

    private function resolveCopy(array $data, array $index): array
    {
        $copy = $data['_copy'];
        $refKey = ($copy['name'] ?? '') . '|' . ($copy['source'] ?? '');
        $base = $index[$refKey] ?? null;

        if (!$base) {
            return $data;
        }

        if (isset($base['_copy'])) {
            $base = $this->resolveCopy($base, $index);
        }

        $resolved = array_merge($base, $data);
        unset($resolved['_copy']);

        foreach ($copy['_mod'] ?? [] as $field => $mod) {
            if (!isset($resolved[$field])) {
                continue;
            }
            if (($mod['mode'] ?? null) === 'insertArr') {
                $items = $mod['items'] ?? [];
                if (isset($items['type']) || is_string($items)) {
                    $items = [$items];
                }
                array_splice($resolved[$field], $mod['index'] ?? 0, 0, $items);
            }
        }

        return $resolved;
    }
}
