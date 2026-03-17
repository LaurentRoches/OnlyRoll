<?php

namespace App\Entity\Srd;

use App\Entity\Reference\ContentSource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;

#[ORM\Entity]
#[ORM\Table(name: 'srd_subclass')]
class Subclass
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'subclass_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: SrdClass::class, inversedBy: 'subclasses')]
    #[ORM\JoinColumn(name: 'class_id', referencedColumnName: 'class_id', nullable: false, onDelete: 'CASCADE')]
    private SrdClass $srdClass;

    #[ORM\ManyToMany(targetEntity: ContentSource::class)]
    #[JoinTable(
        name: 'srd_subclass_source',
        joinColumns: [new JoinColumn(name: 'subclass_id', referencedColumnName: 'subclass_id')],
        inverseJoinColumns: [new JoinColumn(name: 'content_source_id', referencedColumnName: 'content_source_id')]
    )]
    private Collection $sources;

    #[ORM\Column(name: 'subclass_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'subclass_short_name', length: 100, nullable: true)]
    private ?string $shortName = null;

    #[ORM\Column(name: 'subclass_page', type: 'smallint', nullable: true)]
    private ?int $page = null;

    #[ORM\Column(name: 'subclass_description', type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: SubclassFeature::class, mappedBy: 'subclass', cascade: ['persist', 'remove'])]
    #[OrderBy(['level' => 'ASC', 'name' => 'ASC'])]
    private Collection $subclassFeatures;

    public function __construct()
    {
        $this->sources = new ArrayCollection();
        $this->subclassFeatures = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSrdClass(): SrdClass { return $this->srdClass; }
    public function setSrdClass(SrdClass $srdClass): static { $this->srdClass = $srdClass; return $this; }
    public function getSources(): Collection { return $this->sources; }
    public function addSource(ContentSource $source): static { if (!$this->sources->contains($source)) { $this->sources->add($source); } return $this; }
    public function hasSource(ContentSource $source): bool { return $this->sources->contains($source); }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getShortName(): ?string { return $this->shortName; }
    public function setShortName(?string $shortName): static { $this->shortName = $shortName; return $this; }
    public function getPage(): ?int { return $this->page; }
    public function setPage(?int $page): static { $this->page = $page; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
    public function getSubclassFeatures(): Collection { return $this->subclassFeatures; }
}
