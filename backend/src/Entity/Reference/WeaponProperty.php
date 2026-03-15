<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'weapon_property')]
class WeaponProperty
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'weapon_property_id')]
    private int $id;

    #[ORM\Column(name: 'weapon_property_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'weapon_property_label', length: 100)]
    private string $label;

    #[ORM\Column(name: 'weapon_property_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
