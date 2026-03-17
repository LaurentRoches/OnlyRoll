<?php

namespace App\Entity\Srd;

use App\Entity\Reference\Skill;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'background_skill')]
class BackgroundSkill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'background_skill_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Background::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'background_id', referencedColumnName: 'background_id', nullable: false, onDelete: 'CASCADE')]
    private Background $background;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(name: 'skill_id', referencedColumnName: 'skill_id', nullable: false)]
    private Skill $skill;

    public function getId(): int { return $this->id; }
    public function getBackground(): Background { return $this->background; }
    public function setBackground(Background $background): static { $this->background = $background; return $this; }
    public function getSkill(): Skill { return $this->skill; }
    public function setSkill(Skill $skill): static { $this->skill = $skill; return $this; }
}
