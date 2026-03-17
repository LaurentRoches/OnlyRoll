<?php

declare(strict_types=1);

namespace App\Entity\Srd;

use App\Repository\Srd\SrdTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SrdTranslationRepository::class)]
#[ORM\Table(name: 'srd_translation')]
#[ORM\UniqueConstraint(columns: ['srd_table', 'srd_id', 'locale'])]
class SrdTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'srd_translation_id')]
    private int $id;

    #[ORM\Column(name: 'srd_table', length: 30)]
    private string $srdTable;

    #[ORM\Column(name: 'srd_id')]
    private int $srdId;

    #[ORM\Column(name: 'locale', length: 5)]
    private string $locale;

    #[ORM\Column(name: 'translated_fields', type: 'json')]
    private array $translatedFields = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }

    public function getSrdTable(): string { return $this->srdTable; }
    public function setSrdTable(string $srdTable): static { $this->srdTable = $srdTable; return $this; }

    public function getSrdId(): int { return $this->srdId; }
    public function setSrdId(int $srdId): static { $this->srdId = $srdId; return $this; }

    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }

    public function getTranslatedFields(): array { return $this->translatedFields; }
    public function setTranslatedFields(array $translatedFields): static
    {
        $this->translatedFields = $translatedFields;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getField(string $field): ?string
    {
        return $this->translatedFields[$field] ?? null;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
