<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_rarity')]
class ItemRarity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_rarity_id')]
    private int $id;

    #[ORM\Column(name: 'item_rarity_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'item_rarity_label', length: 100)]
    private string $label;

    #[ORM\Column(name: 'item_rarity_sort_order', type: 'smallint', options: ['default' => 0])]
    private int $sortOrder = 0;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
}
