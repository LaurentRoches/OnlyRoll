<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Entity\Srd\Subclass;
use App\Repository\Srd\ClassRepository;
use App\Service\Import\ClassImportMapper;
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
    name: 'app:import:classes',
    description: 'Import D&D classes from 5etools JSON files',
)]
class ImportClassesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClassRepository $classRepo,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate without writing');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');

        $classDir = $this->projectDir . '/data/srd/en/class';
        if (!is_dir($classDir)) {
            $io->error('Class directory not found: ' . $classDir);
            return Command::FAILURE;
        }

        $files = glob($classDir . '/class-*.json');
        $mapper = new ClassImportMapper($this->em, new EntryParser());

        if (!$isDryRun) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['class_feature', 'subclass_feature'] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Child tables truncated (class_feature, subclass_feature).');
        }

        $imported = 0;
        $updated = 0;

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);

            $allClassFeatures    = $data['classFeature'] ?? [];
            $allSubclassFeatures = $data['subclassFeature'] ?? [];

            foreach ($data['class'] ?? [] as $classData) {
                $sourceCode = $classData['source'] ?? 'PHB';
                /** @var ContentSource|null $source */
                $source = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $sourceCode]);
                if (!$source) {
                    continue;
                }

                $existing = $this->classRepo->findByName($classData['name']);
                $class = $mapper->map($classData, $allClassFeatures, $existing);

                if (!$existing) {
                    $class->addSource($source);
                    $this->em->persist($class);
                    $imported++;
                } else {
                    if (!$existing->hasSource($source)) {
                        $existing->addSource($source);
                    }
                    $updated++;
                }
            }

            $subclassDescriptions = [];
            foreach ($allSubclassFeatures as $featureData) {
                $shortName = $featureData['subclassShortName'] ?? '';
                $src = $featureData['subclassSource'] ?? '';
                $key = $shortName . '|' . $src;
                if (!isset($subclassDescriptions[$key]) && !isset($featureData['header'])) {
                    $firstEntry = $featureData['entries'][0] ?? null;
                    if (is_string($firstEntry) && $firstEntry !== '') {
                        $subclassDescriptions[$key] = $firstEntry;
                    }
                }
            }

            foreach ($data['subclass'] ?? [] as $subData) {
                $className = $subData['className'] ?? '';
                $sourceCode = $subData['classSource'] ?? 'PHB';
                $class = $this->classRepo->findByName($className);
                if (!$class) {
                    continue;
                }

                $subSourceCode = $subData['source'] ?? 'PHB';
                $subSource = $this->em->getRepository(ContentSource::class)->findOneBy(['code' => $subSourceCode]);
                if (!$subSource) {
                    continue;
                }

                $shortName = $subData['shortName'] ?? $subData['name'];
                $descKey = $shortName . '|' . $subSourceCode;
                $description = $subclassDescriptions[$descKey] ?? null;

                $existing = $this->em->getRepository(Subclass::class)->findOneBy([
                    'srdClass' => $class,
                    'name' => $subData['name'],
                ]);
                $sub = $mapper->mapSubclass($subData, $class, $existing, $description, $allSubclassFeatures);
                if (!$existing) {
                    $sub->addSource($subSource);
                    $this->em->persist($sub);
                } else {
                    if (!$existing->hasSource($subSource)) {
                        $existing->addSource($subSource);
                    }
                }
            }

            if (!$isDryRun) {
                $this->em->flush();
            }

            $io->info('Processed: ' . basename($file));
        }

        if ($isDryRun) {
            $io->success(sprintf('[DRY RUN] Would import %d, update %d classes', $imported, $updated));
        } else {
            $io->success(sprintf('Imported %d new, updated %d classes', $imported, $updated));
        }

        return Command::SUCCESS;
    }
}
