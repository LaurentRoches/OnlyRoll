<?php

namespace App\Service\Import;

use App\Entity\Srd\Feat;
use App\Entity\Srd\FeatAbilityModifier;
use App\Entity\Srd\FeatBenefit;
use App\Entity\Srd\FeatPrerequisite;
use Doctrine\ORM\EntityManagerInterface;

class FeatImportMapper
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EntryParser $parser,
    ) {}

    public function map(array $data, ?Feat $existing = null): Feat
    {
        $feat = $existing ?? new Feat();

        $feat->setName($data['name']);
        $feat->setPage(isset($data['page']) ? (int)$data['page'] : null);
        $feat->setDescription($this->parser->entriesToMarkdown($data['entries'] ?? []));
        $feat->setPrerequisiteText($this->buildPrerequisiteText($data['prerequisite'] ?? []));

        if ($existing) {
            foreach ($existing->getAbilityModifiers() as $am) {
                $this->em->remove($am);
            }
            foreach ($existing->getPrerequisites() as $pre) {
                $this->em->remove($pre);
            }
            foreach ($existing->getBenefits() as $ben) {
                $this->em->remove($ben);
            }
        }

        $validAbilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
        foreach ($data['ability'] ?? [] as $abilityGroup) {
            foreach ($abilityGroup as $ability => $value) {
                if (!in_array($ability, $validAbilities, true)) {
                    continue;
                }
                $am = new FeatAbilityModifier();
                $am->setFeat($feat)->setAbilityCode($ability)->setAbilityValue((int)$value);
                $this->em->persist($am);
            }
        }

        foreach ($data['prerequisite'] ?? [] as $prereq) {
            foreach ($prereq as $type => $value) {
                if ($type === 'other') {
                    $pre = new FeatPrerequisite();
                    $pre->setFeat($feat)->setPrerequisiteType('other')->setPrerequisiteValue((string)$value);
                    $this->em->persist($pre);
                } elseif ($type === 'level') {
                    $pre = new FeatPrerequisite();
                    $pre->setFeat($feat)->setPrerequisiteType('level')->setPrerequisiteValue((string)$value);
                    $this->em->persist($pre);
                } elseif ($type === 'ability') {
                    foreach ($value as $abilityReq) {
                        foreach ($abilityReq as $ab => $score) {
                            if ($ab === 'choose') {
                                continue;
                            }
                            $pre = new FeatPrerequisite();
                            $pre->setFeat($feat)->setPrerequisiteType('ability')->setPrerequisiteValue($ab . ':' . $score);
                            $this->em->persist($pre);
                        }
                    }
                } elseif ($type === 'race') {
                    foreach ((array)$value as $raceReq) {
                        $raceName = is_array($raceReq) ? ($raceReq['name'] ?? '') : (string)$raceReq;
                        $pre = new FeatPrerequisite();
                        $pre->setFeat($feat)->setPrerequisiteType('race')->setPrerequisiteValue($raceName);
                        $this->em->persist($pre);
                    }
                } elseif ($type === 'spellcasting' || $type === 'spellcastingFeature') {
                    $pre = new FeatPrerequisite();
                    $pre->setFeat($feat)->setPrerequisiteType('spellcasting')->setPrerequisiteValue('true');
                    $this->em->persist($pre);
                } elseif ($type === 'proficiency') {
                    foreach ((array)$value as $profReq) {
                        $profName = is_array($profReq) ? implode(':', array_values($profReq)) : (string)$profReq;
                        $pre = new FeatPrerequisite();
                        $pre->setFeat($feat)->setPrerequisiteType('proficiency')->setPrerequisiteValue($profName);
                        $this->em->persist($pre);
                    }
                }
            }
        }

        if (!empty($data['additionalSpells'])) {
            $ben = new FeatBenefit();
            $ben->setFeat($feat)->setBenefitType('spell')->setBenefitDescription('Grants additional spells');
            $this->em->persist($ben);
        }

        if (!empty($data['skillProficiencies'])) {
            $skillNames = [];
            foreach ($data['skillProficiencies'] as $sg) {
                foreach ($sg as $sk => $v) {
                    if ($sk !== 'choose' && $v) {
                        $skillNames[] = $sk;
                    }
                }
            }
            if ($skillNames) {
                $ben = new FeatBenefit();
                $ben->setFeat($feat)->setBenefitType('skill')->setBenefitDescription('Proficiency: ' . implode(', ', $skillNames));
                $this->em->persist($ben);
            }
        }

        return $feat;
    }

    private function buildPrerequisiteText(array $prerequisites): ?string
    {
        if (empty($prerequisites)) {
            return null;
        }
        $parts = [];
        foreach ($prerequisites as $prereq) {
            foreach ($prereq as $type => $value) {
                if ($type === 'other') {
                    $parts[] = (string)$value;
                } elseif ($type === 'level') {
                    $parts[] = 'Level ' . $value;
                } elseif ($type === 'ability') {
                    foreach ($value as $abilityReq) {
                        foreach ($abilityReq as $ab => $score) {
                            if ($ab !== 'choose') {
                                $parts[] = strtoupper($ab) . ' ' . $score . '+';
                            }
                        }
                    }
                } elseif ($type === 'race') {
                    foreach ((array)$value as $r) {
                        $parts[] = is_array($r) ? ($r['name'] ?? 'race') : (string)$r;
                    }
                } elseif ($type === 'spellcasting' || $type === 'spellcastingFeature') {
                    $parts[] = 'Spellcasting ability';
                }
            }
        }
        return implode(', ', $parts) ?: null;
    }
}
