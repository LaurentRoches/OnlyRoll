<?php

namespace App\Entity\Wiki;

use App\Repository\Wiki\WikiCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikiCategoryRepository::class)]
#[ORM\Table(name: 'wiki_category')]
class WikiCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'wiki_category_id')]
    private int $id;

    #[ORM\Column(name: 'wiki_category_slug', length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(name: 'wiki_category_icon', length: 50, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(name: 'wiki_category_sort_order', type: 'smallint', options: ['default' => 0])]
    private int $sortOrder = 0;

    #[ORM\OneToMany(targetEntity: WikiCategoryTranslation::class, mappedBy: 'category', cascade: ['persist', 'remove'])]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId(): int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(?string $icon): static { $this->icon = $icon; return $this; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): static { $this->sortOrder = $sortOrder; return $this; }
    public function getTranslations(): Collection { return $this->translations; }

    public function getTranslationForLocale(string $locale): ?WikiCategoryTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }
        return null;
    }
}
