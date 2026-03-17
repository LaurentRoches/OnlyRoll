<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Entity\Reference\CreatureSize;
use App\Repository\Srd\RaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: RaceRepository::class)]
#[ORM\Table(name: 'srd_race')]
class Race
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'race_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_race_source',
        joinColumns: [new JoinColumn(name: 'race_id', referencedColumnName: 'race_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\ManyToOne(targetEntity: CreatureSize::class)]
    #[ORM\JoinColumn(name: 'race_size_id', referencedColumnName: 'creature_size_id', nullable: true)]
    private ?CreatureSize $size = null;

    #[ORM\Column(name: 'race_name', length: 100)]
    private string $name;

    #[ORM\Column(name: 'race_walk_speed', type: 'smallint', options: ['default' => 30])]
    private int $walkSpeed = 30;

    #[ORM\Column(name: 'race_ability_modifiers', type: 'json', nullable: true)]
    private ?array $abilityModifiers = null;

    #[ORM\Column(name: 'race_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'race_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToMany(targetEntity: RaceLanguage::class, mappedBy: 'race', cascade: ['persist', 'remove'])]
    private Collection $languages;

    #[ORM\OneToMany(targetEntity: RaceSpeed::class, mappedBy: 'race', cascade: ['persist', 'remove'])]
    private Collection $speeds;

    #[ORM\OneToMany(targetEntity: RaceTrait::class, mappedBy: 'race', cascade: ['persist', 'remove'])]
    private Collection $traits;

    #[ORM\OneToMany(targetEntity: Subrace::class, mappedBy: 'race', cascade: ['persist', 'remove'])]
    private Collection $subraces;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->languages = new ArrayCollection();
        $this->speeds = new ArrayCollection();
        $this->traits = new ArrayCollection();
        $this->subraces = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getSize(): ?CreatureSize { return $this->size; }
    public function setSize(?CreatureSize $size): static { $this->size = $size; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getWalkSpeed(): int { return $this->walkSpeed; }
    public function setWalkSpeed(int $walkSpeed): static { $this->walkSpeed = $walkSpeed; return $this; }
    public function getAbilityModifiers(): ?array { return $this->abilityModifiers; }
    public function setAbilityModifiers(?array $abilityModifiers): static { $this->abilityModifiers = $abilityModifiers; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getLanguages(): Collection { return $this->languages; }
    public function getSpeeds(): Collection { return $this->speeds; }
    public function getTraits(): Collection { return $this->traits; }
    public function getSubraces(): Collection { return $this->subraces; }
}
