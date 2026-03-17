<?php

namespace App\Entity\Srd;

use App\Entity\Reference\Language;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'race_language')]
class RaceLanguage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'race_language_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Race::class, inversedBy: 'languages')]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'race_id', nullable: false, onDelete: 'CASCADE')]
    private Race $race;

    #[ORM\ManyToOne(targetEntity: Language::class)]
    #[ORM\JoinColumn(name: 'language_id', referencedColumnName: 'language_id', nullable: false)]
    private Language $language;

    public function getId(): int { return $this->id; }
    public function getRace(): Race { return $this->race; }
    public function setRace(Race $race): static { $this->race = $race; return $this; }
    public function getLanguage(): Language { return $this->language; }
    public function setLanguage(Language $language): static { $this->language = $language; return $this; }
}
