<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'damage_type')]
class DamageType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'damage_type_id')]
    private int $id;

    #[ORM\Column(name: 'damage_type_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'damage_type_label', length: 100)]
    private string $label;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
}
