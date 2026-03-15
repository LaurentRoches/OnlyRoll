<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'race_trait')]
class RaceTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'race_trait_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Race::class, inversedBy: 'traits')]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'race_id', nullable: false, onDelete: 'CASCADE')]
    private Race $race;

    #[ORM\Column(name: 'trait_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'trait_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): int { return $this->id; }
    public function getRace(): Race { return $this->race; }
    public function setRace(Race $race): static { $this->race = $race; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
