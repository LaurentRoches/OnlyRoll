<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feat_prerequisite')]
class FeatPrerequisite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'feat_prerequisite_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Feat::class, inversedBy: 'prerequisites')]
    #[ORM\JoinColumn(name: 'feat_id', referencedColumnName: 'feat_id', nullable: false, onDelete: 'CASCADE')]
    private Feat $feat;

    #[ORM\Column(name: 'prerequisite_type', length: 30)]
    private string $prerequisiteType;

    #[ORM\Column(name: 'prerequisite_value', length: 200)]
    private string $prerequisiteValue;

    public function getId(): int { return $this->id; }
    public function getFeat(): Feat { return $this->feat; }
    public function setFeat(Feat $feat): static { $this->feat = $feat; return $this; }
    public function getPrerequisiteType(): string { return $this->prerequisiteType; }
    public function setPrerequisiteType(string $prerequisiteType): static { $this->prerequisiteType = $prerequisiteType; return $this; }
    public function getPrerequisiteValue(): string { return $this->prerequisiteValue; }
    public function setPrerequisiteValue(string $prerequisiteValue): static { $this->prerequisiteValue = $prerequisiteValue; return $this; }
}
