<?php

namespace App\Entity\Srd;

use App\Entity\Reference\Skill;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'monster_skill')]
class MonsterSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'monster_skill_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Monster::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'monster_id', referencedColumnName: 'monster_id', nullable: false, onDelete: 'CASCADE')]
    private Monster $monster;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'skill_id', nullable: false)]
    private Skill $skill;

    #[ORM\Column(name: 'skill_bonus', type: 'smallint')]
    private int $skillBonus;

    public function getId(): int { return $this->id; }
    public function getMonster(): Monster { return $this->monster; }
    public function setMonster(Monster $monster): static { $this->monster = $monster; return $this; }
    public function getSkill(): Skill { return $this->skill; }
    public function setSkill(Skill $skill): static { $this->skill = $skill; return $this; }
    public function getSkillBonus(): int { return $this->skillBonus; }
    public function setSkillBonus(int $skillBonus): static { $this->skillBonus = $skillBonus; return $this; }
}
