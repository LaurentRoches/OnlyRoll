<?php

namespace App\Entity\Srd;

use App\Entity\Reference\DamageType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_damage_resistance')]
class MonsterDamageResistance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_damage_resistance_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'damageResistances')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: DamageType::class)]
    #[ORM\JoinColumn(name: 'damage_type_id', referencedColumnName: 'damage_type_id', nullable: false)]
    private DamageType $damageType;

    #[ORM\Column(name: 'resistance_type', length: 20)]
    private string $resistanceType;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getDamageType(): DamageType { return $this->damageType; }
    public function setDamageType(DamageType $damageType): static { $this->damageType = $damageType; return $this; }
    public function getResistanceType(): string { return $this->resistanceType; }
    public function setResistanceType(string $resistanceType): static { $this->resistanceType = $resistanceType; return $this; }
}
