<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'feat_benefit')]
class FeatBenefit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'feat_benefit_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Feat::class, inversedBy: 'benefits')]
    #[ORM\JoinColumn(name: 'feat_id', referencedColumnName: 'feat_id', nullable: false, onDelete: 'CASCADE')]
    private Feat $feat;

    #[ORM\Column(name: 'benefit_type', length: 30)]
    private string $benefitType;

    #[ORM\Column(name: 'benefit_description', type: 'text')]
    private string $benefitDescription;

    public function getId(): int { return $this->id; }
    public function getFeat(): Feat { return $this->feat; }
    public function setFeat(Feat $feat): static { $this->feat = $feat; return $this; }
    public function getBenefitType(): string { return $this->benefitType; }
    public function setBenefitType(string $benefitType): static { $this->benefitType = $benefitType; return $this; }
    public function getBenefitDescription(): string { return $this->benefitDescription; }
    public function setBenefitDescription(string $benefitDescription): static { $this->benefitDescription = $benefitDescription; return $this; }
}
