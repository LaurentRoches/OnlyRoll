<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity]
#[ORM\Table(name: 'srd_subrace')]
class Subrace
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'subrace_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Race::class, inversedBy: 'subraces')]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'race_id', nullable: false, onDelete: 'CASCADE')]
    private Race $race;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_subrace_source',
        joinColumns: [new JoinColumn(name: 'subrace_id', referencedColumnName: 'subrace_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\Column(name: 'subrace_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'subrace_ability_modifiers', type: 'json', nullable: true)]
    private ?array $abilityModifiers = null;

    #[ORM\Column(name: 'subrace_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function __construct() { $this->sources = new ArrayCollection(); }

    public function getId(): int { return $this->id; }
    public function getRace(): Race { return $this->race; }
    public function setRace(Race $race): static { $this->race = $race; return $this; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getAbilityModifiers(): ?array { return $this->abilityModifiers; }
    public function setAbilityModifiers(?array $abilityModifiers): static { $this->abilityModifiers = $abilityModifiers; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
