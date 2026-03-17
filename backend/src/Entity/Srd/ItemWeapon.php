<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_weapon')]
class ItemWeapon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_weapon_id')]
    private int $id;

    #[ORM\OneToOne(targetEntity: Item::class, inversedBy: 'weapon')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\Column(name: 'weapon_category', length: 20)]
    private string $weaponCategory;

    #[ORM\Column(name: 'weapon_range_normal', type: 'smallint', nullable: true)]
    private ?int $rangeNormal = null;

    #[ORM\Column(name: 'weapon_range_long', type: 'smallint', nullable: true)]
    private ?int $rangeLong = null;

    public function getId(): int { return $this->id; }
    public function getItem(): Item { return $this->item; }
    public function setItem(Item $item): static { $this->item = $item; return $this; }
    public function getWeaponCategory(): string { return $this->weaponCategory; }
    public function setWeaponCategory(string $weaponCategory): static { $this->weaponCategory = $weaponCategory; return $this; }
    public function getRangeNormal(): ?int { return $this->rangeNormal; }
    public function setRangeNormal(?int $rangeNormal): static { $this->rangeNormal = $rangeNormal; return $this; }
    public function getRangeLong(): ?int { return $this->rangeLong; }
    public function setRangeLong(?int $rangeLong): static { $this->rangeLong = $rangeLong; return $this; }
}
