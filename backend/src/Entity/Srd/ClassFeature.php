<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'class_feature')]
class ClassFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'class_feature_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SrdClass::class, inversedBy: 'features')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'class_id', nullable: false, onDelete: 'CASCADE')]
    private SrdClass $srdClass;

    #[ORM\Column(name: 'feature_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'feature_level', type: 'smallint')]
    private int $level;

    #[ORM\Column(name: 'feature_description', type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): int { return $this->id; }
    public function getSrdClass(): SrdClass { return $this->srdClass; }
    public function setSrdClass(SrdClass $srdClass): static { $this->srdClass = $srdClass; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getLevel(): int { return $this->level; }
    public function setLevel(int $level): static { $this->level = $level; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
