<?php

namespace App\Service\Import;

use App\Entity\Reference\DamageType;
use App\Entity\Reference\SpellSchool;
use App\Entity\Srd\Spell;
use App\Entity\Srd\SpellClass;
use App\Entity\Srd\SpellComponent;
use App\Entity\Srd\SpellDamageType;
use App\Repository\Srd\ClassRepository;
use Doctrine\ORM\EntityManagerInterface;

class SpellImportMapper
{
    /** Maps 5etools school codes to slugs */
    private const SCHOOL_MAP = [
        'A' => 'abjuration',
        'C' => 'conjuration',
        'D' => 'divination',
        'E' => 'enchantment',
        'V' => 'evocation',
        'I' => 'illusion',
        'N' => 'necromancy',
        'T' => 'transmutation',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
        private readonly SpellEntryParser $spellParser,
    ) {}

    /**
     * Map a 5etools spell JSON to a Spell entity.
     * Does NOT persist or assign source.
     *
     * @param array $data   Raw 5etools spell object
     * @param Spell|null $existing  Existing entity for upsert
     */
    public function map(array $data, ?Spell $existing = null): Spell
    {
        $spell = $existing ?? new Spell();

        $spell->setName($data['name']);
        $spell->setLevel((int)($data['level'] ?? 0));
        $spell->setPage(isset($data['page']) ? (int)$data['page'] : null);
        $spell->setIsRitual(!empty($data['meta']['ritual']));
        $spell->setIsConcentration($this->spellParser->parseDurationConcentration($data['duration'] ?? []));

        $schoolCode = $data['school'] ?? 'E';
        $schoolSlug = self::SCHOOL_MAP[strtoupper($schoolCode)] ?? 'enchantment';
        $school = $this->em->getRepository(SpellSchool::class)->findOneBy(['slug' => $schoolSlug]);
        if ($school) {
            $spell->setSchool($school);
        }

        $spell->setCastingTime($this->spellParser->parseCastingTime($data['time'] ?? []));

        [$rangeType, $rangeDistance] = $this->spellParser->parseRange($data['range'] ?? []);
        $spell->setRangeType($rangeType);
        $spell->setRangeDistance($rangeDistance);

        $spell->setDuration($this->spellParser->parseDuration($data['duration'] ?? []));

        $description = $this->parser->entriesToMarkdown($data['entries'] ?? []);
        if (isset($data['entriesHigherLevel'])) {
            $higherLevel = $this->parser->entriesToMarkdown($data['entriesHigherLevel']);
            if ($higherLevel) {
                $description .= "\n\n**At Higher Levels:** " . $higherLevel;
            }
        }
        $spell->setDescription($description ?: null);

        $this->parseDamageInfo($spell, $data);

        if ($existing) {
            foreach ($existing->getComponents() as $comp) {
                $this->em->remove($comp);
            }
        }
        $this->mapComponents($spell, $data['components'] ?? []);

        return $spell;
    }

    /**
     * Maps spell-class relationships. Call after all classes are imported.
     */
    public function mapClasses(Spell $spell, array $data, ClassRepository $classRepo): void
    {
        foreach ($spell->getSpellClasses() as $sc) {
            $this->em->remove($sc);
        }

        $classNames = [];

        foreach ($data['classes']['fromClassList'] ?? [] as $classRef) {
            $classNames[] = $classRef['name'];
        }

        foreach (array_unique($classNames) as $className) {
            $cls = $classRepo->findOneBy(['name' => $className]);
            if (!$cls) continue;
            $link = new SpellClass();
            $link->setSpell($spell)->setSrdClass($cls);
            $this->em->persist($link);
        }
    }

    /**
     * Maps damage types. Call after damage types are loaded.
     */
    public function mapDamageTypes(Spell $spell, array $data): void
    {
        foreach ($spell->getDamageTypes() as $dt) {
            $this->em->remove($dt);
        }

        foreach ($data['damageInflict'] ?? [] as $damageSlug) {
            $dt = $this->em->getRepository(DamageType::class)->findOneBy(['slug' => strtolower($damageSlug)]);
            if (!$dt) continue;
            $link = new SpellDamageType();
            $link->setSpell($spell)->setDamageType($dt);
            $this->em->persist($link);
        }
    }

    private function mapComponents(Spell $spell, array $components): void
    {
        if (!empty($components['v'])) {
            $comp = new SpellComponent();
            $comp->setSpell($spell)->setComponentType('V');
            $this->em->persist($comp);
        }
        if (!empty($components['s'])) {
            $comp = new SpellComponent();
            $comp->setSpell($spell)->setComponentType('S');
            $this->em->persist($comp);
        }
        if (!empty($components['m'])) {
            $comp = new SpellComponent();
            $comp->setSpell($spell)->setComponentType('M');
            $material = $components['m'];
            if (is_string($material)) {
                $comp->setMaterialDescription($material);
            } elseif (is_array($material)) {
                $comp->setMaterialDescription($material['text'] ?? null);
                if (isset($material['cost'])) {
                    $comp->setMaterialCostGp((int)($material['cost'] / 100));
                }
                $comp->setMaterialConsumed(!empty($material['consume']));
            }
            $this->em->persist($comp);
        }
    }

    private function parseDamageInfo(Spell $spell, array $data): void
    {
        if (!empty($data['scalingLevelDice'])) {
            $scaling = $data['scalingLevelDice'];
            $spell->setScalingLevelDice(is_array($scaling) ? $scaling : [$scaling]);

            $baseFormula = $this->extractDamageFormula($data['entries'] ?? []);
            if ($baseFormula) {
                $spell->setDamageFormula($baseFormula);
            }

            $scalingArr = is_array($scaling) ? $scaling : [$scaling];
            if (!empty($scalingArr[0])) {
                $this->parseUpcastDice($spell, $scalingArr[0]);
            }
            return;
        }

        if (!empty($data['dmg1'])) {
            $spell->setDamageFormula($data['dmg1']);
            return;
        }

        $baseFormula = $this->extractDamageFormula($data['entries'] ?? []);
        if ($baseFormula) {
            $spell->setDamageFormula($baseFormula);
        }

        if (isset($data['entriesHigherLevel'])) {
            $this->parseScaledamageTag($spell, $data['entriesHigherLevel']);
        }
    }

    private function extractDamageFormula(array $entries): ?string
    {
        $text = $this->parser->entriesToMarkdown($entries);
        if (preg_match('/(\d+d\d+)/', $text, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Parse {@scaledamage base|range|perLevel} from entriesHigherLevel.
     * Example: {@scaledamage 8d6|3-9|1d6} → upcastDicePerLevel=1, upcastDiceFaces=6
     */
    private function parseScaledamageTag(Spell $spell, array $entriesHigherLevel): void
    {
        $raw = json_encode($entriesHigherLevel);
        if (preg_match('/\{@scaledamage\s+\d+d\d+\|[\d\-]+\|(\d+)d(\d+)\}/', $raw, $m)) {
            $spell->setUpcastDicePerLevel((int)$m[1]);
            $spell->setUpcastDiceFaces((int)$m[2]);
        }
    }

    private function parseUpcastDice(Spell $spell, array $scaling): void
    {
        $scalingTable = $scaling['scaling'] ?? [];
        if (empty($scalingTable)) {
            return;
        }

        $values = array_values($scalingTable);
        if (count($values) < 2) {
            return;
        }

        if (preg_match('/(\d+)d(\d+)/', $values[0] ?? '', $firstMatch) &&
            preg_match('/(\d+)d(\d+)/', $values[1] ?? '', $secondMatch)) {
            $facesFirst = (int)$firstMatch[2];
            $facesSecond = (int)$secondMatch[2];

            if ($facesFirst === $facesSecond) {
                $spell->setDamageFormula($values[0]);
                $countDiff = (int)$secondMatch[1] - (int)$firstMatch[1];
                if ($countDiff > 0) {
                    $spell->setUpcastDicePerLevel($countDiff);
                    $spell->setUpcastDiceFaces($facesFirst);
                }
            }
        }
    }
}
