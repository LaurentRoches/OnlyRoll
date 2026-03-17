<?php

namespace App\Entity\Wiki;

use App\Repository\Wiki\WikiArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikiArticleRepository::class)]
#[ORM\Table(name: 'wiki_article')]
class WikiArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'wiki_article_id')]
    private int $id;

    #[ORM\Column(name: 'srd_table', length: 30)]
    private string $srdTable;

    #[ORM\Column(name: 'srd_id')]
    private int $srdId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(targetEntity: WikiArticleTranslation::class, mappedBy: 'article', cascade: ['persist', 'remove'])]
    private Collection $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getSrdTable(): string { return $this->srdTable; }
    public function setSrdTable(string $srdTable): static { $this->srdTable = $srdTable; return $this; }
    public function getSrdId(): int { return $this->srdId; }
    public function setSrdId(int $srdId): static { $this->srdId = $srdId; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }
    public function getTranslations(): Collection { return $this->translations; }

    public function getTranslationForLocale(string $locale): ?WikiArticleTranslation
    {
        foreach ($this->translations as $translation) {
            if ($translation->getLocale() === $locale) {
                return $translation;
            }
        }
        return null;
    }
}
