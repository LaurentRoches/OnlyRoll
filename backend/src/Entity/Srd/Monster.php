<?php

namespace App\Entity\Srd;

use App\Entity\Reference\Alignment;
use App\Entity\Reference\ContentSource;
use App\Entity\Reference\CreatureSize;
use App\Entity\Reference\CreatureType;
use App\Repository\Srd\MonsterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: MonsterRepository::class)]
#[ORM\Table(name: 'srd_monster')]
class Monster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_monster_source',
        joinColumns: [new JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\ManyToOne(targetEntity: CreatureSize::class)]
    #[ORM\JoinColumn(name: 'monster_size_id', referencedColumnName: 'creature_size_id', nullable: true)]
    private ?CreatureSize $size = null;

    #[ORM\ManyToOne(targetEntity: CreatureType::class)]
    #[ORM\JoinColumn(name: 'monster_type_id', referencedColumnName: 'creature_type_id', nullable: true)]
    private ?CreatureType $type = null;

    #[ORM\ManyToOne(targetEntity: Alignment::class)]
    #[ORM\JoinColumn(name: 'monster_alignment_id', referencedColumnName: 'alignment_id', nullable: true)]
    private ?Alignment $alignment = null;

    #[ORM\Column(name: 'monster_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'monster_armor_class', type: 'smallint', nullable: true)]
    private ?int $armorClass = null;

    #[ORM\Column(name: 'monster_armor_desc', length: 200, nullable: true)]
    private ?string $armorDesc = null;

    #[ORM\Column(name: 'monster_hit_points_avg', type: 'smallint', nullable: true)]
    private ?int $hitPointsAvg = null;

    #[ORM\Column(name: 'monster_hit_dice_formula', length: 20, nullable: true)]
    private ?string $hitDiceFormula = null;

    #[ORM\Column(name: 'monster_walk_speed', type: 'smallint', nullable: true)]
    private ?int $walkSpeed = null;

    #[ORM\Column(name: 'monster_speeds', type: 'json', nullable: true)]
    private ?array $speeds = null;

    #[ORM\Column(name: 'monster_str', type: 'smallint', nullable: true)]
    private ?int $str = null;

    #[ORM\Column(name: 'monster_dex', type: 'smallint', nullable: true)]
    private ?int $dex = null;

    #[ORM\Column(name: 'monster_con', type: 'smallint', nullable: true)]
    private ?int $con = null;

    #[ORM\Column(name: 'monster_int', type: 'smallint', nullable: true)]
    private ?int $int = null;

    #[ORM\Column(name: 'monster_wis', type: 'smallint', nullable: true)]
    private ?int $wis = null;

    #[ORM\Column(name: 'monster_cha', type: 'smallint', nullable: true)]
    private ?int $cha = null;

    #[ORM\Column(name: 'monster_passive_perception', type: 'smallint', nullable: true)]
    private ?int $passivePerception = null;

    #[ORM\Column(name: 'monster_cr', length: 10, nullable: true)]
    private ?string $cr = null;

    #[ORM\Column(name: 'monster_xp', nullable: true)]
    private ?int $xp = null;

    #[ORM\Column(name: 'monster_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToMany(targetEntity: MonsterAction::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $actions;

    #[ORM\OneToMany(targetEntity: MonsterConditionImmunity::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $conditionImmunities;

    #[ORM\OneToMany(targetEntity: MonsterDamageResistance::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $damageResistances;

    #[ORM\OneToMany(targetEntity: MonsterEnvironment::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $environments;

    #[ORM\OneToMany(targetEntity: MonsterLanguage::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $languages;

    #[ORM\OneToMany(targetEntity: MonsterSavingThrow::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $savingThrows;

    #[ORM\OneToMany(targetEntity: MonsterSense::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $senses;

    #[ORM\OneToMany(targetEntity: MonsterSkill::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $skills;

    #[ORM\OneToMany(targetEntity: MonsterSubtype::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $subtypes;

    #[ORM\OneToMany(targetEntity: MonsterTrait::class, mappedBy: 'monster', cascade: ['persist', 'remove'])]
    private Collection $traits;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->conditionImmunities = new ArrayCollection();
        $this->damageResistances = new ArrayCollection();
        $this->environments = new ArrayCollection();
        $this->languages = new ArrayCollection();
        $this->savingThrows = new ArrayCollection();
        $this->senses = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->subtypes = new ArrayCollection();
        $this->traits = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getSize(): ?CreatureSize { return $this->size; }
    public function setSize(?CreatureSize $size): static { $this->size = $size; return $this; }
    public function getType(): ?CreatureType { return $this->type; }
    public function setType(?CreatureType $type): static { $this->type = $type; return $this; }
    public function getAlignment(): ?Alignment { return $this->alignment; }
    public function setAlignment(?Alignment $alignment): static { $this->alignment = $alignment; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getArmorClass(): ?int { return $this->armorClass; }
    public function setArmorClass(?int $armorClass): static { $this->armorClass = $armorClass; return $this; }
    public function getArmorDesc(): ?string { return $this->armorDesc; }
    public function setArmorDesc(?string $armorDesc): static { $this->armorDesc = $armorDesc; return $this; }
    public function getHitPointsAvg(): ?int { return $this->hitPointsAvg; }
    public function setHitPointsAvg(?int $hitPointsAvg): static { $this->hitPointsAvg = $hitPointsAvg; return $this; }
    public function getHitDiceFormula(): ?string { return $this->hitDiceFormula; }
    public function setHitDiceFormula(?string $hitDiceFormula): static { $this->hitDiceFormula = $hitDiceFormula; return $this; }
    public function getWalkSpeed(): ?int { return $this->walkSpeed; }
    public function setWalkSpeed(?int $walkSpeed): static { $this->walkSpeed = $walkSpeed; return $this; }
    public function getSpeeds(): ?array { return $this->speeds; }
    public function setSpeeds(?array $speeds): static { $this->speeds = $speeds; return $this; }
    public function getStr(): ?int { return $this->str; }
    public function setStr(?int $str): static { $this->str = $str; return $this; }
    public function getDex(): ?int { return $this->dex; }
    public function setDex(?int $dex): static { $this->dex = $dex; return $this; }
    public function getCon(): ?int { return $this->con; }
    public function setCon(?int $con): static { $this->con = $con; return $this; }
    public function getInt(): ?int { return $this->int; }
    public function setInt(?int $int): static { $this->int = $int; return $this; }
    public function getWis(): ?int { return $this->wis; }
    public function setWis(?int $wis): static { $this->wis = $wis; return $this; }
    public function getCha(): ?int { return $this->cha; }
    public function setCha(?int $cha): static { $this->cha = $cha; return $this; }
    public function getPassivePerception(): ?int { return $this->passivePerception; }
    public function setPassivePerception(?int $passivePerception): static { $this->passivePerception = $passivePerception; return $this; }
    public function getCr(): ?string { return $this->cr; }
    public function setCr(?string $cr): static { $this->cr = $cr; return $this; }
    public function getXp(): ?int { return $this->xp; }
    public function setXp(?int $xp): static { $this->xp = $xp; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getActions(): Collection { return $this->actions; }
    public function getConditionImmunities(): Collection { return $this->conditionImmunities; }
    public function getDamageResistances(): Collection { return $this->damageResistances; }
    public function getEnvironments(): Collection { return $this->environments; }
    public function getLanguages(): Collection { return $this->languages; }
    public function getSavingThrows(): Collection { return $this->savingThrows; }
    public function getSenses(): Collection { return $this->senses; }
    public function getSkills(): Collection { return $this->skills; }
    public function getSubtypes(): Collection { return $this->subtypes; }
    public function getTraits(): Collection { return $this->traits; }
}
