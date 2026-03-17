<?php

namespace App\Service\Import;

use App\Entity\Reference\Skill;
use App\Entity\Srd\Background;
use App\Entity\Srd\BackgroundEquipment;
use App\Entity\Srd\BackgroundLanguage;
use App\Entity\Srd\BackgroundSkill;
use Doctrine\ORM\EntityManagerInterface;

class BackgroundImportMapper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    public function map(array $data, ?Background $existing = null): Background
    {
        $background = $existing ?? new Background();

        $background->setName($data['name']);
        $background->setPage(isset($data['page']) ? (int)$data['page'] : null);

        $description = '';
        $featureName = null;
        $featureDesc = '';

        foreach ($data['entries'] ?? [] as $entry) {
            if (is_string($entry)) {
                $description .= $entry . "\n\n";
            } elseif (is_array($entry)) {
                $type = $entry['type'] ?? '';
                if ($type === 'entries' && isset($entry['data']['isFeature'])) {
                    $featureName = $entry['name'] ?? null;
                    $featureDesc = $this->parser->entriesToMarkdown($entry['entries'] ?? []);
                } elseif ($type != 'list') {
                    $description .= $this->parser->entriesToMarkdown([$entry]);
                }
            }
        }

        $background->setDescription(trim($description) ?: null);
        $background->setFeatureName($featureName);
        $background->setFeatureDescription($featureDesc ?: null);

        if ($existing) {
            foreach ($existing->getSkills() as $s) {
                $this->em->remove($s);
            }
            foreach ($existing->getLanguages() as $l) {
                $this->em->remove($l);
            }
            foreach ($existing->getEquipment() as $e) {
                $this->em->remove($e);
            }
        }

        foreach ($data['skillProficiencies'] ?? [] as $skillGroup) {
            foreach ($skillGroup as $skillName => $value) {
                if (!$value || $skillName === 'choose' || $skillName === 'any') {
                    continue;
                }
                $slug = $this->skillNameToSlug($skillName);
                $skill = $this->em->getRepository(Skill::class)->findOneBy(['slug' => $slug]);
                if ($skill) {
                    $bs = new BackgroundSkill();
                    $bs->setBackground($background)->setSkill($skill);
                    $this->em->persist($bs);
                }
            }
        }

        foreach ($data['languageProficiencies'] ?? [] as $langGroup) {
            $anyStandard = (int)($langGroup['anyStandard'] ?? 0);
            $anyExotic = (int)($langGroup['anyExotic'] ?? 0);
            $total = $anyStandard + $anyExotic + (int)($langGroup['any'] ?? 0);
            if ($total > 0) {
                $bl = new BackgroundLanguage();
                $bl->setBackground($background)
                   ->setLanguageCount($total)
                   ->setLanguageChoices(array_keys(array_filter($langGroup, fn($v) => is_bool($v) && $v)));
                $this->em->persist($bl);
            }
        }

        foreach ($data['startingEquipment'] ?? [] as $equipGroup) {
            $items = $equipGroup['_'] ?? $equipGroup['a'] ?? [];
            foreach ($items as $equipData) {
                if (is_array($equipData)) {
                    $itemName = $equipData['displayName'] ?? ($equipData['item'] ?? ($equipData['special'] ?? null));
                    if ($itemName) {
                        $eq = new BackgroundEquipment();
                        $eq->setBackground($background)
                           ->setItemName((string)$itemName)
                           ->setQuantity((int)($equipData['quantity'] ?? 1));
                        $this->em->persist($eq);
                    }
                } elseif (is_string($equipData)) {
                    $eq = new BackgroundEquipment();
                    $eq->setBackground($background)->setItemName($equipData)->setQuantity(1);
                    $this->em->persist($eq);
                }
            }
        }

        return $background;
    }

    private function skillNameToSlug(string $name): string
    {
        return strtolower(str_replace([' ', "'"], ['-', ''], $name));
    }
}
