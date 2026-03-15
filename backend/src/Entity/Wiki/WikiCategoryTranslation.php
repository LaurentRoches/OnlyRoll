<?php

namespace App\Entity\Wiki;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wiki_category_translation')]
class WikiCategoryTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'wiki_category_translation_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WikiCategory::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'wiki_category_id', referencedColumnName: 'wiki_category_id', nullable: false, onDelete: 'CASCADE')]
    private WikiCategory $category;

    #[ORM\Column(name: 'locale', length: 5)]
    private string $locale;

    #[ORM\Column(name: 'name', length: 100)]
    private string $name;

    public function getId(): int { return $this->id; }
    public function getCategory(): WikiCategory { return $this->category; }
    public function setCategory(WikiCategory $category): static { $this->category = $category; return $this; }
    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
}
