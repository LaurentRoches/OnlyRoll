<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'race_speed')]
class RaceSpeed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'race_speed_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Race::class, inversedBy: 'speeds')]
    #[ORM\JoinColumn(name: 'race_id', referencedColumnName: 'race_id', nullable: false, onDelete: 'CASCADE')]
    private Race $race;

    #[ORM\Column(name: 'speed_type', length: 20)]
    private string $speedType;

    #[ORM\Column(name: 'speed_value', type: 'smallint')]
    private int $speedValue;

    public function getId(): int { return $this->id; }
    public function getRace(): Race { return $this->race; }
    public function setRace(Race $race): static { $this->race = $race; return $this; }
    public function getSpeedType(): string { return $this->speedType; }
    public function setSpeedType(string $speedType): static { $this->speedType = $speedType; return $this; }
    public function getSpeedValue(): int { return $this->speedValue; }
    public function setSpeedValue(int $speedValue): static { $this->speedValue = $speedValue; return $this; }
}
