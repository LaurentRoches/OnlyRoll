<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'spell_class')]
class SpellClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'spell_class_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Spell::class, inversedBy: 'spellClasses')]
    #[ORM\JoinColumn(name: 'spell_id', referencedColumnName: 'spell_id', nullable: false, onDelete: 'CASCADE')]
    private Spell $spell;

    #[ORM\ManyToOne(targetEntity: SrdClass::class)]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'class_id', nullable: false, onDelete: 'CASCADE')]
    private SrdClass $srdClass;

    public function getId(): int { return $this->id; }
    public function getSpell(): Spell { return $this->spell; }
    public function setSpell(Spell $spell): static { $this->spell = $spell; return $this; }
    public function getSrdClass(): SrdClass { return $this->srdClass; }
    public function setSrdClass(SrdClass $srdClass): static { $this->srdClass = $srdClass; return $this; }
}
