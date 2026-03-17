<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_trait')]
class MonsterTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_trait_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'traits')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'trait_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'trait_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
