<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'creature_size')]
class CreatureSize
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'creature_size_id')]
    private int $id;

    #[ORM\Column(name: 'creature_size_slug', length: 20, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'creature_size_label', length: 50)]
    private string $label;

    /** 5etools code: T, S, M, L, H, G */
    #[ORM\Column(name: 'creature_size_code', length: 2, unique: true)]
    private string $code;

    #[ORM\Column(name: 'creature_size_sort_order', type: 'smallint', options: ['default' => 0])]
    private int $sortOrder = 0;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
}
