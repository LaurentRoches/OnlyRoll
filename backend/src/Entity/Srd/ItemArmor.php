<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_armor')]
class ItemArmor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_armor_id')]
    private int $id;

    #[ORM\OneToOne(targetEntity: Item::class, inversedBy: 'armor')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\Column(name: 'armor_class_base', type: 'smallint')]
    private int $armorClassBase;

    #[ORM\Column(name: 'armor_max_dex_bonus', type: 'smallint', nullable: true)]
    private ?int $maxDexBonus = null;

    #[ORM\Column(name: 'armor_strength_requirement', type: 'smallint', nullable: true)]
    private ?int $strengthRequirement = null;

    #[ORM\Column(name: 'armor_stealth_disadvantage', type: 'boolean', options: ['default' => false])]
    private bool $stealthDisadvantage = false;

    #[ORM\Column(name: 'armor_type', length: 20, nullable: true)]
    private ?string $armorType = null;

    public function getId(): int { return $this->id; }
    public function getItem(): Item { return $this->item; }
    public function setItem(Item $item): static { $this->item = $item; return $this; }
    public function getArmorClassBase(): int { return $this->armorClassBase; }
    public function setArmorClassBase(int $armorClassBase): static { $this->armorClassBase = $armorClassBase; return $this; }
    public function getMaxDexBonus(): ?int { return $this->maxDexBonus; }
    public function setMaxDexBonus(?int $maxDexBonus): static { $this->maxDexBonus = $maxDexBonus; return $this; }
    public function getStrengthRequirement(): ?int { return $this->strengthRequirement; }
    public function setStrengthRequirement(?int $strengthRequirement): static { $this->strengthRequirement = $strengthRequirement; return $this; }
    public function isStealthDisadvantage(): bool { return $this->stealthDisadvantage; }
    public function setStealthDisadvantage(bool $stealthDisadvantage): static { $this->stealthDisadvantage = $stealthDisadvantage; return $this; }
    public function getArmorType(): ?string { return $this->armorType; }
    public function setArmorType(?string $armorType): static { $this->armorType = $armorType; return $this; }
}
