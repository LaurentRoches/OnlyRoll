<?php

namespace App\Entity\Srd;

use App\Entity\Reference\DamageType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'spell_damage_type')]
class SpellDamageType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'spell_damage_type_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Spell::class, inversedBy: 'damageTypes')]
    #[ORM\JoinColumn(name: 'spell_id', referencedColumnName: 'spell_id', nullable: false, onDelete: 'CASCADE')]
    private Spell $spell;

    #[ORM\ManyToOne(targetEntity: DamageType::class)]
    #[ORM\JoinColumn(name: 'damage_type_id', referencedColumnName: 'damage_type_id', nullable: false)]
    private DamageType $damageType;

    public function getId(): int { return $this->id; }
    public function getSpell(): Spell { return $this->spell; }
    public function setSpell(Spell $spell): static { $this->spell = $spell; return $this; }
    public function getDamageType(): DamageType { return $this->damageType; }
    public function setDamageType(DamageType $damageType): static { $this->damageType = $damageType; return $this; }
}
