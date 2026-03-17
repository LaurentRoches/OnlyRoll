<?php

namespace App\Entity\Srd;

use App\Entity\Reference\WeaponProperty;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_weapon_property')]
class ItemWeaponProperty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_weapon_property_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'weaponProperties')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\ManyToOne(targetEntity: WeaponProperty::class)]
    #[ORM\JoinColumn(name: 'weapon_property_id', referencedColumnName: 'weapon_property_id', nullable: false)]
    private WeaponProperty $weaponProperty;

    public function getId(): int { return $this->id; }
    public function getItem(): Item { return $this->item; }
    public function setItem(Item $item): static { $this->item = $item; return $this; }
    public function getWeaponProperty(): WeaponProperty { return $this->weaponProperty; }
    public function setWeaponProperty(WeaponProperty $weaponProperty): static { $this->weaponProperty = $weaponProperty; return $this; }
}
