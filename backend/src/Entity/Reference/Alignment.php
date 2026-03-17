<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'alignment')]
class Alignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'alignment_id')]
    private int $id;

    #[ORM\Column(name: 'alignment_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'alignment_label', length: 100)]
    private string $label;

    /** 5etools codes: L, N, C, G, E, U, A */
    #[ORM\Column(name: 'alignment_codes', type: 'json', nullable: true)]
    private ?array $codes = null;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getCodes(): ?array { return $this->codes; }
    public function setCodes(?array $codes): static { $this->codes = $codes; return $this; }
}
