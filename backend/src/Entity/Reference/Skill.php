<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'skill')]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'skill_id')]
    private int $id;

    #[ORM\Column(name: 'skill_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'skill_label', length: 100)]
    private string $label;

    /** Associated ability: str, dex, con, int, wis, cha */
    #[ORM\Column(name: 'skill_ability', length: 3)]
    private string $ability;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getAbility(): string { return $this->ability; }
    public function setAbility(string $ability): static { $this->ability = $ability; return $this; }
}
