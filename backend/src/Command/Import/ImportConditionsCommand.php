<?php

namespace App\Command\Import;

use App\Entity\Reference\ConditionType;
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
    name: 'app:import:conditions',
    description: 'Import D&D conditions from 5etools JSON file (updates descriptions)',
)]
class ImportConditionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
        $parser = new EntryParser();

        $file = $this->projectDir . '/data/srd/en/conditionsdiseases.json';
        if (!file_exists($file)) {
            $io->error('File not found: ' . $file);
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        $updated = 0;
        $notFound = 0;

        foreach ($data['condition'] ?? [] as $condData) {
            if (($condData['source'] ?? '') !== 'PHB') {
                continue;
            }

            $slug = strtolower($condData['name']);
            $condition = $this->em->getRepository(ConditionType::class)->findOneBy(['slug' => $slug]);

            if (!$condition) {
                $io->warning('Condition not found in fixtures: ' . $condData['name'] . ' (slug: ' . $slug . ')');
                $notFound++;
                continue;
            }

            $description = $parser->entriesToMarkdown($condData['entries'] ?? []);
            $condition->setDescription($description);
            $updated++;
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('%sUpdated %d conditions, %d not found', $isDryRun ? '[DRY RUN] ' : '', $updated, $notFound));
        return Command::SUCCESS;
    }
}
