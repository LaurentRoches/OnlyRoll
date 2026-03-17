<?php

namespace App\Command\Import;

use App\Entity\Reference\Skill;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:import:skills',
    description: 'Import D&D skills from 5etools JSON file (updates descriptions)',
)]
class ImportSkillsCommand extends Command
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

        $file = $this->projectDir . '/data/srd/en/skills.json';
        if (!file_exists($file)) {
            $io->error('File not found: ' . $file);
            return Command::FAILURE;
        }

        $data = json_decode(file_get_contents($file), true);
        $updated = 0;
        $notFound = 0;

        foreach ($data['skill'] ?? [] as $skillData) {
            if (($skillData['source'] ?? '') !== 'PHB') {
                continue;
            }

            $slug = strtolower(str_replace([' ', "'"], ['-', ''], $skillData['name']));
            $skill = $this->em->getRepository(Skill::class)->findOneBy(['slug' => $slug]);

            if (!$skill) {
                $io->warning('Skill not found in fixtures: ' . $skillData['name'] . ' (slug: ' . $slug . ')');
                $notFound++;
                continue;
            }

            if (isset($skillData['ability'])) {
                $skill->setAbility($skillData['ability']);
                $updated++;
            }
        }

        if (!$isDryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('%sUpdated %d skills, %d not found', $isDryRun ? '[DRY RUN] ' : '', $updated, $notFound));
        return Command::SUCCESS;
    }
}
