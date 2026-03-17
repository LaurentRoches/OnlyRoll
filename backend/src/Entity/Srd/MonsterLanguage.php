<?php

namespace App\Entity\Srd;

use App\Entity\Reference\Language;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_language')]
class MonsterLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_language_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'languages')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'language_id', nullable: true)]
    private ?Language $language = null;

    #[ORM\Column(name: 'language_note', length: 200, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(name: 'language_can_speak', type: 'boolean', options: ['default' => true])]
    private bool $canSpeak = true;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getLanguage(): ?Language { return $this->language; }
    public function setLanguage(?Language $language): static { $this->language = $language; return $this; }
    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): static { $this->note = $note; return $this; }
    public function isCanSpeak(): bool { return $this->canSpeak; }
    public function setCanSpeak(bool $canSpeak): static { $this->canSpeak = $canSpeak; return $this; }
}
