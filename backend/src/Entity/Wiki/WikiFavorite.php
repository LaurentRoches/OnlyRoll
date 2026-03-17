<?php

namespace App\Entity\Wiki;

use App\Entity\User;
use App\Repository\Wiki\WikiFavoriteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WikiFavoriteRepository::class)]
#[ORM\Table(name: 'wiki_favorite')]
class WikiFavorite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'wiki_favorite_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'srd_table', length: 30)]
    private string $srdTable;

    #[ORM\Column(name: 'srd_id')]
    private int $srdId;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): static { $this->user = $user; return $this; }
    public function getSrdTable(): string { return $this->srdTable; }
    public function setSrdTable(string $srdTable): static { $this->srdTable = $srdTable; return $this; }
    public function getSrdId(): int { return $this->srdId; }
    public function setSrdId(int $srdId): static { $this->srdId = $srdId; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
