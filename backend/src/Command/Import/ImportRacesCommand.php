<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Entity\Srd\Subrace;
use App\Repository\Srd\RaceRepository;
use App\Service\Import\EntryParser;
use App\Service\Import\RaceImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:races',
    description: 'Import D&D races from 5etools JSON',
)]
class ImportRacesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RaceRepository $raceRepo,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source', null, InputOption::VALUE_OPTIONAL, 'Filter by source code (e.g. PHB)')
             ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $filterSource = $input->getOption('source');

        $filePath = $this->projectDir . '/data/srd/en/races.json';
        if (!file_exists($filePath)) {
            $io->error('races.json not found: ' . $filePath);
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($filePath), true);
        $races = $data['race'] ?? [];
        $mapper = new RaceImportMapper($this->em, new EntryParser());

        if (!$isDryRun && !$filterSource) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['race_trait', 'race_speed', 'race_language'] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Child tables truncated (race_trait, race_speed, race_language).');
        }

        $io->progressStart(count($races));

        $imported = 0;
        $updated = 0;
        $batchSize = 20;
        $i = 0;
        $processedIds = [];

        foreach ($races as $raceData) {
            $sourceCode = $raceData['source'] ?? 'PHB';
            if ($filterSource && strtoupper($filterSource) !== strtoupper($sourceCode)) {
                $io->progressAdvance();
                continue;
            }

            /** @var ContentSource|null $source */
            $source = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $sourceCode]);
            if (!$source) {
                $io->progressAdvance();
                continue;
            }

            $existing = $this->raceRepo->findByName($raceData['name']);

            if ($existing && isset($processedIds[$existing->getId()])) {
                if (!$existing->hasSource($source)) {
                    $existing->addSource($source);
                }
                $io->progressAdvance();
                $i++;
                if (!$isDryRun && $i % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    $processedIds = [];
                }
                continue;
            }

            $race = $mapper->map($raceData, $existing);

            if (!$existing) {
                $race->addSource($source);
                $this->em->persist($race);
                $imported++;
            } else {
                if (!$existing->hasSource($source)) {
                    $existing->addSource($source);
                }
                $processedIds[$existing->getId()] = true;
                $updated++;
            }

            $io->progressAdvance();
            $i++;

            if (!$isDryRun && $i % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();
                $processedIds = [];
            }
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        $io->progressFinish();

        $subraces = $data['subrace'] ?? [];
        $io->info(sprintf('Processing %d subraces...', count($subraces)));
        $importedSub = 0;
        $updatedSub = 0;
        $j = 0;

        foreach ($subraces as $subData) {
            $raceName = $subData['raceName'] ?? '';
            if (empty($subData['name']) || empty($raceName)) {
                continue;
            }
            $race = $this->raceRepo->findByName($raceName);
            if (!$race) {
                continue;
            }

            $subSourceCode = $subData['source'] ?? 'PHB';
            $subSource = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $subSourceCode]);
            if (!$subSource) {
                continue;
            }

            $existingSub = $this->em->getRepository(Subrace::class)->findOneBy([
                'race' => $race,
                'name' => $subData['name'],
            ]);
            $sub = $mapper->mapSubrace($subData, $race, $existingSub);
            if (!$existingSub) {
                $sub->addSource($subSource);
                $this->em->persist($sub);
                $importedSub++;
            } else {
                if (!$existingSub->hasSource($subSource)) {
                    $existingSub->addSource($subSource);
                }
                $updatedSub++;
            }

            $j++;
            if (!$isDryRun && $j % $batchSize === 0) {
                $this->em->flush();
            }
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d, update %d races | %d, update %d subraces', $imported, $updated, $importedSub, $updatedSub));
        } else {
            $io->success(sprintf('Imported %d new, updated %d existing races | %d new, %d updated subraces', $imported, $updated, $importedSub, $updatedSub));
        }

        return Command::SUCCESS;
    }
}
