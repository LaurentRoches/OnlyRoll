<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_sense')]
class MonsterSense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_sense_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'senses')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'sense_type', length: 50)]
    private string $senseType;

    #[ORM\Column(name: 'sense_range_ft', type: 'smallint')]
    private int $rangeFt;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getSenseType(): string { return $this->senseType; }
    public function setSenseType(string $senseType): static { $this->senseType = $senseType; return $this; }
    public function getRangeFt(): int { return $this->rangeFt; }
    public function setRangeFt(int $rangeFt): static { $this->rangeFt = $rangeFt; return $this; }
}
