<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'background_language')]
class BackgroundLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'background_language_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Background::class, inversedBy: 'languages')]
    #[ORM\JoinColumn(name: 'background_id', referencedColumnName: 'background_id', nullable: false, onDelete: 'CASCADE')]
    private Background $background;

    #[ORM\Column(name: 'language_count', type: 'smallint', options: ['default' => 0])]
    private int $languageCount = 0;

    #[ORM\Column(name: 'language_choices', type: 'json', nullable: true)]
    private ?array $languageChoices = null;

    public function getId(): int { return $this->id; }
    public function getBackground(): Background { return $this->background; }
    public function setBackground(Background $background): static { $this->background = $background; return $this; }
    public function getLanguageCount(): int { return $this->languageCount; }
    public function setLanguageCount(int $languageCount): static { $this->languageCount = $languageCount; return $this; }
    public function getLanguageChoices(): ?array { return $this->languageChoices; }
    public function setLanguageChoices(?array $languageChoices): static { $this->languageChoices = $languageChoices; return $this; }
}
