<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ConditionType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_condition_immunity')]
class MonsterConditionImmunity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_condition_immunity_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'conditionImmunities')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: ConditionType::class)]
    #[ORM\JoinColumn(name: 'condition_type_id', referencedColumnName: 'condition_type_id', nullable: false)]
    private ConditionType $conditionType;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getConditionType(): ConditionType { return $this->conditionType; }
    public function setConditionType(ConditionType $conditionType): static { $this->conditionType = $conditionType; return $this; }
}
