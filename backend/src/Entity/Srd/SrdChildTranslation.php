<?php

declare(strict_types=1);

namespace App\Entity\Srd;

use App\Repository\Srd\SrdChildTranslationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SrdChildTranslationRepository::class)]
#[ORM\Table(name: 'srd_child_translation')]
#[ORM\UniqueConstraint(columns: ['child_table', 'child_id', 'locale'])]
class SrdChildTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'srd_child_translation_id')]
    private int $id;

    #[ORM\Column(name: 'child_table', length: 50)]
    private string $childTable;

    #[ORM\Column(name: 'child_id')]
    private int $childId;

    #[ORM\Column(name: 'locale', length: 5)]
    private string $locale;

    #[ORM\Column(name: 'translated_fields', type: 'json')]
    private array $translatedFields = [];

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }

    public function getChildTable(): string { return $this->childTable; }
    public function setChildTable(string $childTable): static { $this->childTable = $childTable; return $this; }

    public function getChildId(): int { return $this->childId; }
    public function setChildId(int $childId): static { $this->childId = $childId; return $this; }

    public function getLocale(): string { return $this->locale; }
    public function setLocale(string $locale): static { $this->locale = $locale; return $this; }

    public function getTranslatedFields(): array { return $this->translatedFields; }
    public function setTranslatedFields(array $translatedFields): static
    {
        $this->translatedFields = $translatedFields;
        return $this;
    }

    public function getField(string $field): ?string
    {
        return $this->translatedFields[$field] ?? null;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
