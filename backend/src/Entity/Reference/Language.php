<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'language')]
class Language
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'language_id')]
    private int $id;

    #[ORM\Column(name: 'language_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'language_label', length: 100)]
    private string $label;

    /** standard, exotic, secret */
    #[ORM\Column(name: 'language_type', length: 20, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'language_script', length: 50, nullable: true)]
    private ?string $script = null;

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): static { $this->label = $label; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $type): static { $this->type = $type; return $this; }
    public function getScript(): ?string { return $this->script; }
    public function setScript(?string $script): static { $this->script = $script; return $this; }
}
