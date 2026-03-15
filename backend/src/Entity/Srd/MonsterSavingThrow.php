<?php

namespace App\Entity\Srd;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_saving_throw')]
class MonsterSavingThrow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_saving_throw_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'savingThrows')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\Column(name: 'save_ability', length: 3)]
    private string $saveAbility;

    #[ORM\Column(name: 'save_bonus', type: 'smallint')]
    private int $saveBonus;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getSaveAbility(): string { return $this->saveAbility; }
    public function setSaveAbility(string $saveAbility): static { $this->saveAbility = $saveAbility; return $this; }
    public function getSaveBonus(): int { return $this->saveBonus; }
    public function setSaveBonus(int $saveBonus): static { $this->saveBonus = $saveBonus; return $this; }
}
