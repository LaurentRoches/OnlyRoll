<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'spell_school')]
class SpellSchool
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'spell_school_id')]
    private int $id;

    #[ORM\Column(name: 'spell_school_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'spell_school_label', length: 100)]
    private string $label;

    /** 5etools single-letter code: A, C, D, E, I, N, T, V */
    #[ORM\Column(name: 'spell_school_code', length: 2, unique: true)]
    private string $code;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
}
