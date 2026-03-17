<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'background_equipment')]
class BackgroundEquipment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'background_equipment_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Background::class, inversedBy: 'equipment')]
    #[ORM\JoinColumn(name: 'background_id', referencedColumnName: 'background_id', nullable: false, onDelete: 'CASCADE')]
    private Background $background;

    #[ORM\Column(name: 'equipment_item_name', length: 200)]
    private string $itemName;

    #[ORM\Column(name: 'equipment_quantity', type: 'smallint', options: ['default' => 1])]
    private int $quantity = 1;

    public function getId(): int { return $this->id; }
    public function getBackground(): Background { return $this->background; }
    public function setBackground(Background $background): static { $this->background = $background; return $this; }
    public function getItemName(): string { return $this->itemName; }
    public function setItemName(string $itemName): static { $this->itemName = $itemName; return $this; }
    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }
}
