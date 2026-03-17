<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Entity\Reference\SpellSchool;
use App\Repository\Srd\SpellRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: SpellRepository::class)]
#[ORM\Table(name: 'srd_spell')]
class Spell
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'spell_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_spell_source',
        joinColumns: [new JoinColumn(name: 'spell_id', referencedColumnName: 'spell_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\ManyToOne(targetEntity: SpellSchool::class)]
    #[ORM\JoinColumn(name: 'spell_school_id', referencedColumnName: 'spell_school_id', nullable: false)]
    private SpellSchool $school;

    #[ORM\Column(name: 'spell_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'spell_level', type: 'smallint')]
    private int $level;

    #[ORM\Column(name: 'spell_casting_time', length: 100)]
    private string $castingTime;

    #[ORM\Column(name: 'spell_range_type', length: 50)]
    private string $rangeType;

    #[ORM\Column(name: 'spell_range_distance', nullable: true)]
    private ?int $rangeDistance = null;

    #[ORM\Column(name: 'spell_duration', length: 200)]
    private string $duration;

    #[ORM\Column(name: 'spell_is_concentration', type: 'boolean', options: ['default' => false])]
    private bool $isConcentration = false;

    #[ORM\Column(name: 'spell_is_ritual', type: 'boolean', options: ['default' => false])]
    private bool $isRitual = false;

    #[ORM\Column(name: 'spell_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'spell_damage_formula', length: 50, nullable: true)]
    private ?string $damageFormula = null;

    #[ORM\Column(name: 'spell_upcast_dice_per_level', type: 'smallint', nullable: true)]
    private ?int $upcastDicePerLevel = null;

    #[ORM\Column(name: 'spell_upcast_dice_faces', type: 'smallint', nullable: true)]
    private ?int $upcastDiceFaces = null;

    #[ORM\Column(name: 'spell_scaling_level_dice', type: 'json', nullable: true)]
    private ?array $scalingLevelDice = null;

    #[ORM\Column(name: 'spell_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToMany(targetEntity: SpellClass::class, mappedBy: 'spell', cascade: ['persist', 'remove'])]
    private Collection $spellClasses;

    #[ORM\OneToMany(targetEntity: SpellComponent::class, mappedBy: 'spell', cascade: ['persist', 'remove'])]
    private Collection $components;

    #[ORM\OneToMany(targetEntity: SpellDamageType::class, mappedBy: 'spell', cascade: ['persist', 'remove'])]
    private Collection $damageTypes;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->spellClasses = new ArrayCollection();
        $this->components = new ArrayCollection();
        $this->damageTypes = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
        }
        return $this;
    }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getSchool(): SpellSchool { return $this->school; }
    public function setSchool(SpellSchool $school): static { $this->school = $school; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): static { $this->level = $level; return $this; }
    public function getCastingTime(): string { return $this->castingTime; }
    public function setCastingTime(string $castingTime): static { $this->castingTime = $castingTime; return $this; }
    public function getRangeType(): string { return $this->rangeType; }
    public function setRangeType(string $rangeType): static { $this->rangeType = $rangeType; return $this; }
    public function getRangeDistance(): ?int { return $this->rangeDistance; }
    public function setRangeDistance(?int $rangeDistance): static { $this->rangeDistance = $rangeDistance; return $this; }
    public function getDuration(): string { return $this->duration; }
    public function setDuration(string $duration): static { $this->duration = $duration; return $this; }
    public function isConcentration(): bool { return $this->isConcentration; }
    public function setIsConcentration(bool $isConcentration): static { $this->isConcentration = $isConcentration; return $this; }
    public function isRitual(): bool { return $this->isRitual; }
    public function setIsRitual(bool $isRitual): static { $this->isRitual = $isRitual; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getDamageFormula(): ?string { return $this->damageFormula; }
    public function setDamageFormula(?string $damageFormula): static { $this->damageFormula = $damageFormula; return $this; }
    public function getUpcastDicePerLevel(): ?int { return $this->upcastDicePerLevel; }
    public function setUpcastDicePerLevel(?int $upcastDicePerLevel): static { $this->upcastDicePerLevel = $upcastDicePerLevel; return $this; }
    public function getUpcastDiceFaces(): ?int { return $this->upcastDiceFaces; }
    public function setUpcastDiceFaces(?int $upcastDiceFaces): static { $this->upcastDiceFaces = $upcastDiceFaces; return $this; }
    public function getScalingLevelDice(): ?array { return $this->scalingLevelDice; }
    public function setScalingLevelDice(?array $scalingLevelDice): static { $this->scalingLevelDice = $scalingLevelDice; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getSpellClasses(): Collection { return $this->spellClasses; }
    public function getComponents(): Collection { return $this->components; }
    public function getDamageTypes(): Collection { return $this->damageTypes; }
}
