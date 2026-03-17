<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\ClassRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: ClassRepository::class)]
#[ORM\Table(name: 'srd_class')]
class SrdClass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'class_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_class_source',
        joinColumns: [new JoinColumn(name: 'class_id', referencedColumnName: 'class_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\Column(name: 'class_name', length: 100)]
    private string $name;

    #[ORM\Column(name: 'class_hit_die', type: 'smallint')]
    private int $hitDie;

    #[ORM\Column(name: 'class_saving_throws', type: 'json')]
    private array $savingThrows = [];

    #[ORM\Column(name: 'class_spellcasting_ability', length: 3, nullable: true)]
    private ?string $spellcastingAbility = null;

    #[ORM\Column(name: 'class_caster_progression', length: 20, nullable: true)]
    private ?string $casterProgression = null;

    #[ORM\Column(name: 'class_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\Column(name: 'class_proficiencies', type: 'json', nullable: true)]
    private ?array $proficiencies = null;

    #[ORM\Column(name: 'class_starting_equipment', type: 'json', nullable: true)]
    private ?array $startingEquipment = null;

    #[ORM\Column(name: 'class_multiclassing', type: 'json', nullable: true)]
    private ?array $multiclassing = null;

    #[ORM\Column(name: 'class_table_groups', type: 'json', nullable: true)]
    private ?array $classTableGroups = null;

    #[ORM\OneToMany(targetEntity: ClassFeature::class, mappedBy: 'srdClass', cascade: ['persist', 'remove'])]
    private Collection $features;

    #[ORM\OneToMany(targetEntity: Subclass::class, mappedBy: 'srdClass', cascade: ['persist', 'remove'])]
    private Collection $subclasses;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->features = new ArrayCollection();
        $this->subclasses = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getHitDie(): int { return $this->hitDie; }
    public function setHitDie(int $hitDie): static { $this->hitDie = $hitDie; return $this; }
    public function getSavingThrows(): array { return $this->savingThrows; }
    public function setSavingThrows(array $savingThrows): static { $this->savingThrows = $savingThrows; return $this; }
    public function getSpellcastingAbility(): ?string { return $this->spellcastingAbility; }
    public function setSpellcastingAbility(?string $spellcastingAbility): static { $this->spellcastingAbility = $spellcastingAbility; return $this; }
    public function getCasterProgression(): ?string { return $this->casterProgression; }
    public function setCasterProgression(?string $casterProgression): static { $this->casterProgression = $casterProgression; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getProficiencies(): ?array { return $this->proficiencies; }
    public function setProficiencies(?array $proficiencies): static { $this->proficiencies = $proficiencies; return $this; }
    public function getStartingEquipment(): ?array { return $this->startingEquipment; }
    public function setStartingEquipment(?array $startingEquipment): static { $this->startingEquipment = $startingEquipment; return $this; }
    public function getMulticlassing(): ?array { return $this->multiclassing; }
    public function setMulticlassing(?array $multiclassing): static { $this->multiclassing = $multiclassing; return $this; }
    public function getClassTableGroups(): ?array { return $this->classTableGroups; }
    public function setClassTableGroups(?array $classTableGroups): static { $this->classTableGroups = $classTableGroups; return $this; }
    public function getFeatures(): Collection { return $this->features; }
    public function getSubclasses(): Collection { return $this->subclasses; }
}
