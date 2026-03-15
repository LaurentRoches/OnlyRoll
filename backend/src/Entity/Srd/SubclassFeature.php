<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'subclass_feature')]
class SubclassFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'subclass_feature_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Subclass::class, inversedBy: 'subclassFeatures')]
    #[ORM\JoinColumn(name: 'subclass_id', referencedColumnName: 'subclass_id', nullable: false, onDelete: 'CASCADE')]
    private Subclass $subclass;

    #[ORM\Column(name: 'feature_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'feature_level', type: 'smallint')]
    private int $level;

    #[ORM\Column(name: 'feature_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): int { return $this->id; }
    public function getSubclass(): Subclass { return $this->subclass; }
    public function setSubclass(Subclass $subclass): static { $this->subclass = $subclass; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): static { $this->level = $level; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
