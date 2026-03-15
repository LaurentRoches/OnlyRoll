<?php

namespace App\DataFixtures;

use App\Entity\Wiki\WikiCategory;
use App\Entity\Wiki\WikiCategoryTranslation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class WikiCategoryFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['wiki'];
    }

    public function load(ObjectManager $manager): void
    {
        $categories = [
            [
                'slug' => 'spells',
                'icon' => 'SparklesIcon',
                'sortOrder' => 1,
                'translations' => [
                    'en' => 'Spells',
                    'fr' => 'Sorts',
                ],
            ],
            [
                'slug' => 'races',
                'icon' => 'UserGroupIcon',
                'sortOrder' => 2,
                'translations' => [
                    'en' => 'Races',
                    'fr' => 'Races',
                ],
            ],
            [
                'slug' => 'classes',
                'icon' => 'AcademicCapIcon',
                'sortOrder' => 3,
                'translations' => [
                    'en' => 'Classes',
                    'fr' => 'Classes',
                ],
            ],
            [
                'slug' => 'backgrounds',
                'icon' => 'BookOpenIcon',
                'sortOrder' => 4,
                'translations' => [
                    'en' => 'Backgrounds',
                    'fr' => 'Historiques',
                ],
            ],
            [
                'slug' => 'feats',
                'icon' => 'HandRaisedIcon',
                'sortOrder' => 5,
                'translations' => [
                    'en' => 'Feats',
                    'fr' => 'Dons',
                ],
            ],
            [
                'slug' => 'items',
                'icon' => 'ShieldCheckIcon',
                'sortOrder' => 6,
                'translations' => [
                    'en' => 'Equipment',
                    'fr' => 'Équipement',
                ],
            ],
            [
                'slug' => 'monsters',
                'icon' => 'BugAntIcon',
                'sortOrder' => 7,
                'translations' => [
                    'en' => 'Monsters',
                    'fr' => 'Monstres',
                ],
            ],
        ];

        foreach ($categories as $data) {
            $category = new WikiCategory();
            $category->setSlug($data['slug'])
                     ->setIcon($data['icon'])
                     ->setSortOrder($data['sortOrder']);

            foreach ($data['translations'] as $locale => $name) {
                $translation = new WikiCategoryTranslation();
                $translation->setCategory($category)
                            ->setLocale($locale)
                            ->setName($name);
                $manager->persist($translation);
            }

            $manager->persist($category);
            $this->addReference('wiki_category_' . $data['slug'], $category);
        }

        $manager->flush();
    }
}
