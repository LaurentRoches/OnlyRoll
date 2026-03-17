<?php

namespace App\Command\Import;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\FeatRepository;
use App\Service\Import\EntryParser;
use App\Service\Import\FeatImportMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:feats',
    description: 'Import D&D feats from 5etools JSON file',
)]
class ImportFeatsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FeatRepository $featRepo,
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

        $file = $this->projectDir . '/data/srd/en/feats.json';
        if (!file_exists($file)) {
            $io->error('File not found: ' . $file);
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        $mapper = new FeatImportMapper($this->em, new EntryParser());

        if (!$isDryRun && !$sourceFilter) {
            $conn = $this->em->getConnection();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            foreach (['feat_ability_modifier', 'feat_prerequisite', 'feat_benefit'] as $table) {
                $conn->executeStatement('TRUNCATE TABLE ' . $table);
            }
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->note('Child tables truncated (feat_ability_modifier, feat_prerequisite, feat_benefit).');
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $batchSize = 50;
        $i = 0;
        $processedIds = [];

        foreach ($data['feat'] ?? [] as $featData) {
            $sourceCode = $featData['source'] ?? 'PHB';
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

            $existing = $this->featRepo->findByName($featData['name']);

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

            $feat = $mapper->map($featData, $existing);

            if (!$existing) {
                $feat->addSource($source);
                $this->em->persist($feat);
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
            $io->success(sprintf('[DRY RUN] Would import %d, update %d, skip %d feats', $imported, $updated, $skipped));
        } else {
            $io->success(sprintf('Imported %d new, updated %d, skipped %d feats', $imported, $updated, $skipped));
        }

        return Command::SUCCESS;
    }
}
