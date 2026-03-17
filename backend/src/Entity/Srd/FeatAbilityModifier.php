<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feat_ability_modifier')]
class FeatAbilityModifier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'feat_ability_modifier_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Feat::class, inversedBy: 'abilityModifiers')]
    #[ORM\JoinColumn(name: 'feat_id', referencedColumnName: 'feat_id', nullable: false, onDelete: 'CASCADE')]
    private Feat $feat;

    #[ORM\Column(name: 'ability_code', length: 3)]
    private string $abilityCode;

    #[ORM\Column(name: 'ability_value', type: 'smallint')]
    private int $abilityValue;

    public function getId(): int { return $this->id; }
    public function getFeat(): Feat { return $this->feat; }
    public function setFeat(Feat $feat): static { $this->feat = $feat; return $this; }
    public function getAbilityCode(): string { return $this->abilityCode; }
    public function setAbilityCode(string $abilityCode): static { $this->abilityCode = $abilityCode; return $this; }
    public function getAbilityValue(): int { return $this->abilityValue; }
    public function setAbilityValue(int $abilityValue): static { $this->abilityValue = $abilityValue; return $this; }
}
