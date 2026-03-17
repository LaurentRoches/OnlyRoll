<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use App\Repository\Srd\BackgroundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity(repositoryClass: BackgroundRepository::class)]
#[ORM\Table(name: 'srd_background')]
class Background
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'background_id')]
    private int $id;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_background_source',
        joinColumns: [new JoinColumn(name: 'background_id', referencedColumnName: 'background_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\Column(name: 'background_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'background_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'background_feature_name', length: 200, nullable: true)]
    private ?string $featureName = null;

    #[ORM\Column(name: 'background_feature_description', type: 'text', nullable: true)]
    private ?string $featureDescription = null;

    #[ORM\Column(name: 'background_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\OneToMany(targetEntity: BackgroundEquipment::class, mappedBy: 'background', cascade: ['persist', 'remove'])]
    private Collection $equipment;

    #[ORM\OneToMany(targetEntity: BackgroundLanguage::class, mappedBy: 'background', cascade: ['persist', 'remove'])]
    private Collection $languages;

    #[ORM\OneToMany(targetEntity: BackgroundSkill::class, mappedBy: 'background', cascade: ['persist', 'remove'])]
    private Collection $skills;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->equipment = new ArrayCollection();
        $this->languages = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getFeatureName(): ?string { return $this->featureName; }
    public function setFeatureName(?string $featureName): static { $this->featureName = $featureName; return $this; }
    public function getFeatureDescription(): ?string { return $this->featureDescription; }
    public function setFeatureDescription(?string $featureDescription): static { $this->featureDescription = $featureDescription; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getEquipment(): Collection { return $this->equipment; }
    public function getLanguages(): Collection { return $this->languages; }
    public function getSkills(): Collection { return $this->skills; }
}
