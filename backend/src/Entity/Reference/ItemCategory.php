<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'item_category')]
class ItemCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'item_category_id')]
    private int $id;

    #[ORM\Column(name: 'item_category_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'item_category_label', length: 100)]
    private string $label;

    #[ORM\Column(name: 'category_abbreviation', length: 10, nullable: true)]
    private ?string $abbreviation = null;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getAbbreviation(): ?string { return $this->abbreviation; }
    public function setAbbreviation(?string $abbreviation): static { $this->abbreviation = $abbreviation; return $this; }
}
