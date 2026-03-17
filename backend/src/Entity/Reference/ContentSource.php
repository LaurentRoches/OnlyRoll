<?php

namespace App\Entity\Reference;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'content_source')]
class ContentSource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'content_source_id')]
    private int $id;

    /** Short code used in 5etools JSON: PHB, DMG, XGE, etc. */
    #[ORM\Column(name: 'content_source_code', length: 20, unique: true)]
    private string $code;

    #[ORM\Column(name: 'content_source_name', length: 200)]
    private string $name;

    #[ORM\Column(name: 'content_source_abbreviation', length: 20, nullable: true)]
    private ?string $abbreviation = null;

    #[ORM\Column(name: 'content_source_year', type: 'smallint', nullable: true)]
    private ?int $year = null;

    #[ORM\Column(name: 'content_source_is_official', type: 'boolean', options: ['default' => true])]
    private bool $isOfficial = true;

    public function getId(): int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function setCode(string $code): static { $this->code = $code; return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
    public function getAbbreviation(): ?string { return $this->abbreviation; }
    public function setAbbreviation(?string $abbreviation): static { $this->abbreviation = $abbreviation; return $this; }
    public function getYear(): ?int { return $this->year; }
    public function setYear(?int $year): static { $this->year = $year; return $this; }
    public function isOfficial(): bool { return $this->isOfficial; }
    public function setIsOfficial(bool $isOfficial): static { $this->isOfficial = $isOfficial; return $this; }
}
