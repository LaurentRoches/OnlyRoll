<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_action')]
class MonsterAction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_action_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'action_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'action_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'action_is_legendary', type: 'boolean', options: ['default' => false])]
    private bool $isLegendary = false;

    #[ORM\Column(name: 'action_is_reaction', type: 'boolean', options: ['default' => false])]
    private bool $isReaction = false;

    #[ORM\Column(name: 'action_is_bonus', type: 'boolean', options: ['default' => false])]
    private bool $isBonus = false;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function isLegendary(): bool { return $this->isLegendary; }
    public function setIsLegendary(bool $isLegendary): static { $this->isLegendary = $isLegendary; return $this; }
    public function isReaction(): bool { return $this->isReaction; }
    public function setIsReaction(bool $isReaction): static { $this->isReaction = $isReaction; return $this; }
    public function isBonus(): bool { return $this->isBonus; }
    public function setIsBonus(bool $isBonus): static { $this->isBonus = $isBonus; return $this; }
}
