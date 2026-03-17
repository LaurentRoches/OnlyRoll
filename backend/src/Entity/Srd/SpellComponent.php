<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'spell_component')]
class SpellComponent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'spell_component_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Spell::class, inversedBy: 'components')]
    #[ORM\JoinColumn(name: 'spell_id', referencedColumnName: 'spell_id', nullable: false, onDelete: 'CASCADE')]
    private Spell $spell;

    #[ORM\Column(name: 'component_type', length: 1)]
    private string $componentType;

    #[ORM\Column(name: 'component_material_description', type: 'text', nullable: true)]
    private ?string $materialDescription = null;

    #[ORM\Column(name: 'component_material_cost_gp', nullable: true)]
    private ?int $materialCostGp = null;

    #[ORM\Column(name: 'component_material_consumed', type: 'boolean', options: ['default' => false])]
    private bool $materialConsumed = false;

    public function getId(): int { return $this->id; }
    public function getSpell(): Spell { return $this->spell; }
    public function setSpell(Spell $spell): static { $this->spell = $spell; return $this; }
    public function getComponentType(): string { return $this->componentType; }
    public function setComponentType(string $componentType): static { $this->componentType = $componentType; return $this; }
    public function getMaterialDescription(): ?string { return $this->materialDescription; }
    public function setMaterialDescription(?string $materialDescription): static { $this->materialDescription = $materialDescription; return $this; }
    public function getMaterialCostGp(): ?int { return $this->materialCostGp; }
    public function setMaterialCostGp(?int $materialCostGp): static { $this->materialCostGp = $materialCostGp; return $this; }
    public function isMaterialConsumed(): bool { return $this->materialConsumed; }
    public function setMaterialConsumed(bool $materialConsumed): static { $this->materialConsumed = $materialConsumed; return $this; }
}
