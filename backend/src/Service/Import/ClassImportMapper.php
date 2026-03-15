<?php

namespace App\Service\Import;

use App\Entity\Srd\ClassFeature;
use App\Entity\Srd\SrdClass;
use App\Entity\Srd\Subclass;
use App\Entity\Srd\SubclassFeature;
use Doctrine\ORM\EntityManagerInterface;

class ClassImportMapper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    /**
     * @param array $allClassFeatures Top-level classFeature[] array from the JSON file
     */
    public function map(array $data, array $allClassFeatures = [], ?SrdClass $existing = null): SrdClass
    {
        $class = $existing ?? new SrdClass();

        $class->setName($data['name']);
        $class->setPage(isset($data['page']) ? (int)$data['page'] : null);
        $class->setHitDie($data['hd']['faces'] ?? 8);
        $class->setSavingThrows($data['proficiency'] ?? []);
        $class->setSpellcastingAbility($data['spellcastingAbility'] ?? null);
        $class->setCasterProgression($data['casterProgression'] ?? null);

        $prof = $data['startingProficiencies'] ?? [];
        $class->setProficiencies([
            'armor'   => $this->flattenProficiencies($prof['armor'] ?? []),
            'weapons' => $this->flattenProficiencies($prof['weapons'] ?? []),
            'tools'   => $this->flattenProficiencies($prof['tools'] ?? []),
            'skills'  => $prof['skills'] ?? [],
        ]);

        $class->setStartingEquipment($data['startingEquipment']['default'] ?? []);

        $mc = $data['multiclassing'] ?? null;
        $class->setMulticlassing($mc);

        $tableGroups = $data['classTableGroups'] ?? [];
        $class->setClassTableGroups($tableGroups ?: null);

        if ($existing) {
            foreach ($existing->getFeatures() as $feature) {
                $this->em->remove($feature);
            }
        }

        $className = $data['name'];
        $classSource = $data['source'] ?? 'PHB';

        foreach ($allClassFeatures as $featureData) {
            if (($featureData['className'] ?? '') !== $className) {
                continue;
            }
            if (($featureData['classSource'] ?? 'PHB') !== $classSource) {
                continue;
            }
            $feature = new ClassFeature();
            $feature->setSrdClass($class)
                    ->setName($featureData['name'] ?? 'Feature')
                    ->setLevel((int)($featureData['level'] ?? 1))
                    ->setDescription($this->parser->entriesToMarkdown($featureData['entries'] ?? []));
            $this->em->persist($feature);
        }

        return $class;
    }

    /**
     * @param array $allSubclassFeatures Top-level subclassFeature[] array from the JSON file
     */
    public function mapSubclass(array $data, SrdClass $class, ?Subclass $existing = null, ?string $description = null, array $allSubclassFeatures = []): Subclass
    {
        $subclass = $existing ?? new Subclass();
        $subclass->setSrdClass($class);
        $subclass->setName($data['name']);
        $subclass->setShortName($data['shortName'] ?? null);
        $subclass->setPage(isset($data['page']) ? (int)$data['page'] : null);
        $subclass->setDescription($description);

        $shortName = $data['shortName'] ?? $data['name'];
        $subSource = $data['source'] ?? 'PHB';

        if ($existing) {
            foreach ($existing->getSubclassFeatures() as $sf) {
                $this->em->remove($sf);
            }
        }

        foreach ($allSubclassFeatures as $sfData) {
            if (($sfData['subclassShortName'] ?? '') !== $shortName) {
                continue;
            }
            if (($sfData['subclassSource'] ?? 'PHB') !== $subSource) {
                continue;
            }
            $description = $this->parser->entriesToMarkdown($sfData['entries'] ?? []);
            if ($description === '' && empty($sfData['entries'])) {
                continue;
            }
            $sf = new SubclassFeature();
            $sf->setSubclass($subclass)
               ->setName($sfData['name'] ?? 'Feature')
               ->setLevel((int)($sfData['level'] ?? 1))
               ->setDescription($description ?: null);
            $this->em->persist($sf);
        }

        return $subclass;
    }

    /**
     * Flatten a proficiency list that may contain strings or objects with 'full'/'srd' keys.
     */
    private function flattenProficiencies(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $result[] = $item;
            } elseif (is_array($item)) {
                $result[] = $item['full'] ?? $item['srd'] ?? json_encode($item);
            }
        }
        return $result;
    }
}
