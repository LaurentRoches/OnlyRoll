<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\FeatRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: FeatRepository::class)]
#[ORM\Table(name: 'srd_feat')]
class Feat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'feat_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_feat_source',
        joinColumns: [new JoinColumn(name: 'feat_id', referencedColumnName: 'feat_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\Column(name: 'feat_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'feat_prerequisite_text', type: 'text', nullable: true)]
    private ?string $prerequisiteText = null;

    #[ORM\Column(name: 'feat_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'feat_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToMany(targetEntity: FeatAbilityModifier::class, mappedBy: 'feat', cascade: ['persist', 'remove'])]
    private Collection $abilityModifiers;

    #[ORM\OneToMany(targetEntity: FeatBenefit::class, mappedBy: 'feat', cascade: ['persist', 'remove'])]
    private Collection $benefits;

    #[ORM\OneToMany(targetEntity: FeatPrerequisite::class, mappedBy: 'feat', cascade: ['persist', 'remove'])]
    private Collection $prerequisites;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->abilityModifiers = new ArrayCollection();
        $this->benefits = new ArrayCollection();
        $this->prerequisites = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getPrerequisiteText(): ?string { return $this->prerequisiteText; }
    public function setPrerequisiteText(?string $prerequisiteText): static { $this->prerequisiteText = $prerequisiteText; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getAbilityModifiers(): Collection { return $this->abilityModifiers; }
    public function getBenefits(): Collection { return $this->benefits; }
    public function getPrerequisites(): Collection { return $this->prerequisites; }
}
