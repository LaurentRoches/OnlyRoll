<?php

namespace App\Entity\Wiki;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wiki_article_translation')]
class WikiArticleTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'wiki_article_translation_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: WikiArticle::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'wiki_article_id', referencedColumnName: 'wiki_article_id', nullable: false, onDelete: 'CASCADE')]
    private WikiArticle $article;

    #[ORM\Column(name: 'locale', length: 5)]
    private string $locale;

    #[ORM\Column(name: 'title', length: 300)]
    private string $title;

    #[ORM\Column(name: 'content_markdown', type: 'text', nullable: true)]
    private ?string $contentMarkdown = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getArticle(): WikiArticle { return $this->article; }
    public function setArticle(WikiArticle $article): static { $this->article = $article; return $this; }
    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getContentMarkdown(): ?string { return $this->contentMarkdown; }
    public function setContentMarkdown(?string $contentMarkdown): static { $this->contentMarkdown = $contentMarkdown; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
