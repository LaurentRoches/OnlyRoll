<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed:wiki-categories',
    description: 'Seed wiki categories with translations if empty — no FixturesBundle needed',
)]
class SeedWikiCategoriesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $count = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM wiki_category');
        if ($count > 0) {
            $io->info("wiki_category already has $count rows, skipping.");
            return Command::SUCCESS;
        }

        $categories = [
            ['slug' => 'spells',      'icon' => 'SparklesIcon',    'sortOrder' => 1, 'translations' => ['en' => 'Spells',      'fr' => 'Sorts']],
            ['slug' => 'races',       'icon' => 'UserGroupIcon',   'sortOrder' => 2, 'translations' => ['en' => 'Races',       'fr' => 'Races']],
            ['slug' => 'classes',     'icon' => 'AcademicCapIcon', 'sortOrder' => 3, 'translations' => ['en' => 'Classes',     'fr' => 'Classes']],
            ['slug' => 'backgrounds', 'icon' => 'BookOpenIcon',    'sortOrder' => 4, 'translations' => ['en' => 'Backgrounds', 'fr' => 'Historiques']],
            ['slug' => 'feats',       'icon' => 'HandRaisedIcon',  'sortOrder' => 5, 'translations' => ['en' => 'Feats',       'fr' => 'Dons']],
            ['slug' => 'items',       'icon' => 'ShieldCheckIcon', 'sortOrder' => 6, 'translations' => ['en' => 'Equipment',   'fr' => 'Équipement']],
            ['slug' => 'monsters',    'icon' => 'BugAntIcon',      'sortOrder' => 7, 'translations' => ['en' => 'Monsters',    'fr' => 'Monstres']],
        ];

        $inserted = 0;
        foreach ($categories as $data) {
            $this->connection->insert('wiki_category', [
                'wiki_category_slug' => $data['slug'],
                'wiki_category_icon' => $data['icon'],
                'wiki_category_sort_order' => $data['sortOrder'],
            ]);

            $categoryId = (int) $this->connection->lastInsertId();

            foreach ($data['translations'] as $locale => $name) {
                $this->connection->insert('wiki_category_translation', [
                    'wiki_category_id' => $categoryId,
                    'locale' => $locale,
                    'name' => $name,
                ]);
            }

            $inserted++;
        }

        $io->success("Seeded $inserted wiki categories with translations.");

        return Command::SUCCESS;
    }
}
