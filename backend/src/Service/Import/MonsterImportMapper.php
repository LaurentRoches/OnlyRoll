<?php

namespace App\Service\Import;

use App\Entity\Reference\Alignment;
use App\Entity\Reference\ConditionType;
use App\Entity\Reference\CreatureSize;
use App\Entity\Reference\CreatureType;
use App\Entity\Reference\DamageType;
use App\Entity\Reference\Skill;
use App\Entity\Srd\Monster;
use App\Entity\Srd\MonsterAction;
use App\Entity\Srd\MonsterConditionImmunity;
use App\Entity\Srd\MonsterDamageResistance;
use App\Entity\Srd\MonsterEnvironment;
use App\Entity\Srd\MonsterLanguage;
use App\Entity\Srd\MonsterSavingThrow;
use App\Entity\Srd\MonsterSense;
use App\Entity\Srd\MonsterSkill;
use App\Entity\Srd\MonsterSubtype;
use App\Entity\Srd\MonsterTrait;
use Doctrine\ORM\EntityManagerInterface;

class MonsterImportMapper
{
    private const ALIGNMENT_MAP = [
        'L' => 'lawful',
        'N' => 'neutral',
        'C' => 'chaotic',
        'G' => 'good',
        'E' => 'evil',
        'U' => 'unaligned',
        'A' => 'any-alignment',
    ];

    private const DAMAGE_TYPE_MAP = [
        'A' => 'acid',
        'B' => 'bludgeoning',
        'C' => 'cold',
        'F' => 'fire',
        'O' => 'force',
        'L' => 'lightning',
        'N' => 'necrotic',
        'P' => 'piercing',
        'I' => 'poison',
        'Y' => 'psychic',
        'R' => 'radiant',
        'S' => 'slashing',
        'T' => 'thunder',
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    public function map(array $data, ?Monster $existing = null): Monster
    {
        $monster = $existing ?? new Monster();

        $monster->setName($data['name']);
        $monster->setPage(isset($data['page']) ? (int)$data['page'] : null);

        $sizeCode = $data['size'][0] ?? 'M';
        $size = $this->em->getRepository(CreatureSize::class)->findOneBy(['code' => $sizeCode]);
        $monster->setSize($size);

        $typeData = $data['type'] ?? null;
        $rawTypeName = is_string($typeData) ? $typeData : ($typeData['type'] ?? null);
        $typeName = is_string($rawTypeName) ? $rawTypeName : (is_array($rawTypeName) ? ($rawTypeName['choose'][0] ?? null) : null);
        if ($typeName) {
            $creatureType = $this->em->getRepository(CreatureType::class)->findOneBy(['slug' => $typeName]);
            $monster->setType($creatureType);
        }

        $alignmentCodes = $data['alignment'] ?? [];
        $monster->setAlignment($this->resolveAlignment($alignmentCodes));

        $acData = $data['ac'] ?? null;
        if (is_array($acData) && !empty($acData)) {
            $firstAc = $acData[0];
            if (is_int($firstAc)) {
                $monster->setArmorClass($firstAc);
            } elseif (is_array($firstAc)) {
                $monster->setArmorClass((int)($firstAc['ac'] ?? 0));
                $fromParts = [];
                foreach ($firstAc['from'] ?? [] as $fromItem) {
                    $fromParts[] = is_string($fromItem) ? $fromItem : ($fromItem['name'] ?? '');
                }
                if ($fromParts) {
                    $monster->setArmorDesc(implode(', ', $fromParts));
                }
            }
        }

        $hpData = $data['hp'] ?? null;
        if ($hpData) {
            $monster->setHitPointsAvg((int)($hpData['average'] ?? 0));
            $formula = $hpData['formula'] ?? null;
            $monster->setHitDiceFormula(is_string($formula) ? $formula : null);
        }

        $speedData = $data['speed'] ?? [];
        if (isset($speedData['walk'])) {
            $walk = $speedData['walk'];
            $monster->setWalkSpeed(is_array($walk) ? (int)($walk['number'] ?? 0) : (int)$walk);
        }
        $otherSpeeds = [];
        foreach (['fly', 'swim', 'climb', 'burrow'] as $speedType) {
            if (isset($speedData[$speedType])) {
                $val = $speedData[$speedType];
                $otherSpeeds[$speedType] = is_array($val) ? (int)($val['number'] ?? 0) : (int)$val;
            }
        }
        if ($otherSpeeds) {
            $monster->setSpeeds($otherSpeeds);
        }

        $monster->setStr($data['str'] ?? null);
        $monster->setDex($data['dex'] ?? null);
        $monster->setCon($data['con'] ?? null);
        $monster->setInt($data['int'] ?? null);
        $monster->setWis($data['wis'] ?? null);
        $monster->setCha($data['cha'] ?? null);
        $monster->setPassivePerception(isset($data['passive']) ? (int)$data['passive'] : null);

        $crData = $data['cr'] ?? null;
        if (is_array($crData)) {
            $monster->setCr($crData['cr'] ?? null);
        } else {
            $monster->setCr($crData ? (string)$crData : null);
        }

        $monster->setXp($this->crToXp($monster->getCr()));

        if ($existing) {
            $this->removeRelations($existing);
        }

        foreach ($data['trait'] ?? [] as $traitData) {
            $trait = new MonsterTrait();
            $trait->setMonster($monster)
                  ->setName($traitData['name'] ?? 'Trait')
                  ->setDescription($this->parser->entriesToMarkdown($traitData['entries'] ?? []));
            $this->em->persist($trait);
        }

        foreach ($data['action'] ?? [] as $actionData) {
            $action = new MonsterAction();
            $action->setMonster($monster)
                   ->setName($actionData['name'] ?? 'Action')
                   ->setDescription($this->parser->entriesToMarkdown($actionData['entries'] ?? []))
                   ->setIsLegendary(false)
                   ->setIsReaction(false)
                   ->setIsBonus(false);
            $this->em->persist($action);
        }

        foreach ($data['legendary'] ?? [] as $actionData) {
            $action = new MonsterAction();
            $action->setMonster($monster)
                   ->setName($actionData['name'] ?? 'Legendary Action')
                   ->setDescription($this->parser->entriesToMarkdown($actionData['entries'] ?? []))
                   ->setIsLegendary(true)
                   ->setIsReaction(false)
                   ->setIsBonus(false);
            $this->em->persist($action);
        }

        foreach ($data['reaction'] ?? [] as $actionData) {
            $action = new MonsterAction();
            $action->setMonster($monster)
                   ->setName($actionData['name'] ?? 'Reaction')
                   ->setDescription($this->parser->entriesToMarkdown($actionData['entries'] ?? []))
                   ->setIsLegendary(false)
                   ->setIsReaction(true)
                   ->setIsBonus(false);
            $this->em->persist($action);
        }

        foreach ($data['save'] ?? [] as $ability => $bonus) {
            $st = new MonsterSavingThrow();
            $st->setMonster($monster)
               ->setSaveAbility($ability)
               ->setSaveBonus((int)$bonus);
            $this->em->persist($st);
        }

        foreach ($data['skill'] ?? [] as $skillName => $bonus) {
            $slug = strtolower(str_replace(' ', '-', $skillName));
            $skill = $this->em->getRepository(Skill::class)->findOneBy(['slug' => $slug]);
            if ($skill) {
                $ms = new MonsterSkill();
                $ms->setMonster($monster)->setSkill($skill)->setSkillBonus((int)$bonus);
                $this->em->persist($ms);
            }
        }

        if (!empty($data['senses'])) {
            $senses = is_array($data['senses']) ? $data['senses'] : [$data['senses']];
            foreach ($senses as $sense) {
                if (!is_string($sense)) continue;
                if (preg_match('/^([a-z\s]+?)\s+(\d+)\s*ft/i', $sense, $m)) {
                    $ms = new MonsterSense();
                    $ms->setMonster($monster)->setSenseType(trim($m[1]))->setRangeFt((int)$m[2]);
                    $this->em->persist($ms);
                }
            }
        }

        $this->mapDamageResistances($monster, $data);

        foreach ($data['conditionImmune'] ?? [] as $condData) {
            if (is_string($condData)) {
                $condNames = [$condData];
            } elseif (is_array($condData) && isset($condData['conditionImmune'])) {
                $condNames = (array)$condData['conditionImmune'];
            } elseif (is_array($condData) && isset($condData['condition'])) {
                $condNames = [$condData['condition']];
            } else {
                continue;
            }
            foreach ($condNames as $condName) {
                if (!$condName) continue;
                $condSlug = strtolower($condName);
                $cond = $this->em->getRepository(ConditionType::class)->findOneBy(['slug' => $condSlug]);
                if ($cond) {
                    $ci = new MonsterConditionImmunity();
                    $ci->setMonster($monster)->setConditionType($cond);
                    $this->em->persist($ci);
                }
            }
        }

        if (is_array($typeData) && !empty($typeData['tags'])) {
            foreach ($typeData['tags'] as $tag) {
                $subtype = new MonsterSubtype();
                $subtype->setMonster($monster)->setName(is_string($tag) ? $tag : ($tag['tag'] ?? ''));
                $this->em->persist($subtype);
            }
        }

        foreach ($data['languages'] ?? [] as $lang) {
            $langNote = is_string($lang) ? $lang : ($lang['note'] ?? $lang['language'] ?? null);
            if (!$langNote) continue;
            $language = new MonsterLanguage();
            $language->setMonster($monster)->setNote($langNote)->setCanSpeak(true);
            $this->em->persist($language);
        }

        foreach ($data['environment'] ?? [] as $env) {
            if (!is_string($env)) continue;
            $environment = new MonsterEnvironment();
            $environment->setMonster($monster)->setName($env);
            $this->em->persist($environment);
        }

        return $monster;
    }

    private function mapDamageResistances(Monster $monster, array $data): void
    {
        $types = [
            'resist'    => 'resistance',
            'immune'    => 'immunity',
            'vulnerable'=> 'vulnerability',
        ];

        foreach ($types as $key => $resistanceType) {
            foreach ($data[$key] ?? [] as $dmgData) {
                if (is_string($dmgData)) {
                    $dmgNames = [$dmgData];
                } elseif (is_array($dmgData)) {
                    if (isset($dmgData['special'])) continue;
                    $innerList = $dmgData[$key] ?? null;
                    if (is_array($innerList)) {
                        $dmgNames = array_filter($innerList, 'is_string');
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
                foreach ($dmgNames as $dmgName) {
                    $dmgSlug = self::DAMAGE_TYPE_MAP[$dmgName] ?? strtolower($dmgName ?? '');
                    $damageType = $this->em->getRepository(DamageType::class)->findOneBy(['slug' => $dmgSlug]);
                    if (!$damageType) continue;
                    $dr = new MonsterDamageResistance();
                    $dr->setMonster($monster)
                    ->setDamageType($damageType)
                    ->setResistanceType($resistanceType);
                    $this->em->persist($dr);
                }
            }
        }
    }

    private function resolveAlignment(array $codes): ?Alignment
    {
        if (empty($codes)) {
            return null;
        }
        if (in_array('U', $codes)) {
            return $this->em->getRepository(Alignment::class)->findOneBy(['slug' => 'unaligned']);
        }
        if (in_array('A', $codes)) {
            return $this->em->getRepository(Alignment::class)->findOneBy(['slug' => 'any-alignment']);
        }
        $slugParts = [];
        foreach ($codes as $code) {
            if (!is_string($code)) continue;
            $part = self::ALIGNMENT_MAP[$code] ?? null;
            if ($part) {
                $slugParts[] = $part;
            }
        }
        $slug = implode('-', array_unique($slugParts));
        return $this->em->getRepository(Alignment::class)->findOneBy(['slug' => $slug]);
    }

    private function removeRelations(Monster $monster): void
    {
        foreach ($monster->getActions() as $e) { $this->em->remove($e); }
        foreach ($monster->getTraits() as $e) { $this->em->remove($e); }
        foreach ($monster->getSavingThrows() as $e) { $this->em->remove($e); }
        foreach ($monster->getSkills() as $e) { $this->em->remove($e); }
        foreach ($monster->getSenses() as $e) { $this->em->remove($e); }
        foreach ($monster->getDamageResistances() as $e) { $this->em->remove($e); }
        foreach ($monster->getConditionImmunities() as $e) { $this->em->remove($e); }
        foreach ($monster->getSubtypes() as $e) { $this->em->remove($e); }
        foreach ($monster->getLanguages() as $e) { $this->em->remove($e); }
        foreach ($monster->getEnvironments() as $e) { $this->em->remove($e); }
    }

    private function crToXp(?string $cr): ?int
    {
        return match ($cr) {
            '0'     => 10,
            '1/8'   => 25,
            '1/4'   => 50,
            '1/2'   => 100,
            '1'     => 200,
            '2'     => 450,
            '3'     => 700,
            '4'     => 1100,
            '5'     => 1800,
            '6'     => 2300,
            '7'     => 2900,
            '8'     => 3900,
            '9'     => 5000,
            '10'    => 5900,
            '11'    => 7200,
            '12'    => 8400,
            '13'    => 10000,
            '14'    => 11500,
            '15'    => 13000,
            '16'    => 15000,
            '17'    => 18000,
            '18'    => 20000,
            '19'    => 22000,
            '20'    => 25000,
            '21'    => 33000,
            '22'    => 41000,
            '23'    => 50000,
            '24'    => 62000,
            '25'    => 75000,
            '26'    => 90000,
            '27'    => 105000,
            '28'    => 120000,
            '29'    => 135000,
            '30'    => 155000,
            default => null,
        };
    }
}
