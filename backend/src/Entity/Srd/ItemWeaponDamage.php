<?php

namespace App\Entity\Srd;

use App\Entity\Reference\DamageType;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_weapon_damage')]
class ItemWeaponDamage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_weapon_damage_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Item::class, inversedBy: 'weaponDamages')]
    #[ORM\JoinColumn(name: 'item_id', referencedColumnName: 'item_id', nullable: false, onDelete: 'CASCADE')]
    private Item $item;

    #[ORM\ManyToOne(targetEntity: DamageType::class)]
    #[ORM\JoinColumn(name: 'damage_type_id', referencedColumnName: 'damage_type_id', nullable: false)]
    private DamageType $damageType;

    #[ORM\Column(name: 'damage_dice_count', type: 'smallint')]
    private int $diceCnt;

    #[ORM\Column(name: 'damage_dice_faces', type: 'smallint')]
    private int $diceFaces;

    #[ORM\Column(name: 'damage_versatile_formula', length: 20, nullable: true)]
    private ?string $versatileFormula = null;

    public function getId(): int { return $this->id; }
    public function getItem(): Item { return $this->item; }
    public function setItem(Item $item): static { $this->item = $item; return $this; }
    public function getDamageType(): DamageType { return $this->damageType; }
    public function setDamageType(DamageType $damageType): static { $this->damageType = $damageType; return $this; }
    public function getDiceCnt(): int { return $this->diceCnt; }
    public function setDiceCnt(int $diceCnt): static { $this->diceCnt = $diceCnt; return $this; }
    public function getDiceFaces(): int { return $this->diceFaces; }
    public function setDiceFaces(int $diceFaces): static { $this->diceFaces = $diceFaces; return $this; }
    public function getVersatileFormula(): ?string { return $this->versatileFormula; }
    public function setVersatileFormula(?string $versatileFormula): static { $this->versatileFormula = $versatileFormula; return $this; }
}
