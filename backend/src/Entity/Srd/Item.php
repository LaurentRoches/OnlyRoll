<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Entity\Reference\ItemCategory;
use App\Entity\Reference\ItemRarity;
use App\Repository\Srd\ItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: ItemRepository::class)]
#[ORM\Table(name: 'srd_item')]
class Item
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_item_source',
        joinColumns: [new JoinColumn(name: 'item_id', referencedColumnName: 'item_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\ManyToOne(targetEntity: ItemCategory::class)]
    #[ORM\JoinColumn(name: 'item_category_id', referencedColumnName: 'item_category_id', nullable: true)]
    private ?ItemCategory $category = null;

    #[ORM\ManyToOne(targetEntity: ItemRarity::class)]
    #[ORM\JoinColumn(name: 'item_rarity_id', referencedColumnName: 'item_rarity_id', nullable: true)]
    private ?ItemRarity $rarity = null;

    #[ORM\Column(name: 'item_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'item_weight', type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $weight = null;

    #[ORM\Column(name: 'item_value_gp', type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $valueGp = null;

    #[ORM\Column(name: 'item_is_magical', type: 'boolean', options: ['default' => false])]
    private bool $isMagical = false;

    #[ORM\Column(name: 'item_requires_attunement', type: 'boolean', options: ['default' => false])]
    private bool $requiresAttunement = false;

    #[ORM\Column(name: 'item_attunement_text', length: 200, nullable: true)]
    private ?string $attunementText = null;

    #[ORM\Column(name: 'item_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'item_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToOne(targetEntity: ItemArmor::class, mappedBy: 'item', cascade: ['persist', 'remove'])]
    private ?ItemArmor $armor = null;

    #[ORM\OneToOne(targetEntity: ItemWeapon::class, mappedBy: 'item', cascade: ['persist', 'remove'])]
    private ?ItemWeapon $weapon = null;

    #[ORM\OneToMany(targetEntity: ItemWeaponDamage::class, mappedBy: 'item', cascade: ['persist', 'remove'])]
    private Collection $weaponDamages;

    #[ORM\OneToMany(targetEntity: ItemWeaponProperty::class, mappedBy: 'item', cascade: ['persist', 'remove'])]
    private Collection $weaponProperties;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->weaponDamages = new ArrayCollection();
        $this->weaponProperties = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getCategory(): ?ItemCategory { return $this->category; }
    public function setCategory(?ItemCategory $category): static { $this->category = $category; return $this; }
    public function getRarity(): ?ItemRarity { return $this->rarity; }
    public function setRarity(?ItemRarity $rarity): static { $this->rarity = $rarity; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getWeight(): ?string { return $this->weight; }
    public function setWeight(?string $weight): static { $this->weight = $weight; return $this; }
    public function getValueGp(): ?string { return $this->valueGp; }
    public function setValueGp(?string $valueGp): static { $this->valueGp = $valueGp; return $this; }
    public function isMagical(): bool { return $this->isMagical; }
    public function setIsMagical(bool $isMagical): static { $this->isMagical = $isMagical; return $this; }
    public function isRequiresAttunement(): bool { return $this->requiresAttunement; }
    public function setRequiresAttunement(bool $requiresAttunement): static { $this->requiresAttunement = $requiresAttunement; return $this; }
    public function getAttunementText(): ?string { return $this->attunementText; }
    public function setAttunementText(?string $attunementText): static { $this->attunementText = $attunementText; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getArmor(): ?ItemArmor { return $this->armor; }
    public function getWeapon(): ?ItemWeapon { return $this->weapon; }
    public function getWeaponDamages(): Collection { return $this->weaponDamages; }
    public function getWeaponProperties(): Collection { return $this->weaponProperties; }
}
