<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AuditAction;
use App\Repository\AuditLogRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['log_user_id'], name: 'idx_log_user')]
#[ORM\Index(columns: ['log_action'], name: 'idx_log_action')]
#[ORM\Index(columns: ['log_created_at'], name: 'idx_log_created')]
#[ORM\HasLifecycleCallbacks]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'log_id')]
    #[Groups(['audit:read', 'audit:list'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'log_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read', 'audit:list'])]
    private ?User $performer = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'log_target_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    #[Groups(['audit:read', 'audit:list'])]
    private ?User $targetUser = null;

    #[ORM\Column(name: 'log_action', type: Types::STRING, length: 50, enumType: AuditAction::class)]
    #[Groups(['audit:read', 'audit:list'])]
    private AuditAction $action;

    #[ORM\Column(name: 'log_entity_type', type: Types::STRING, length: 50, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $entityType = null;

    #[ORM\Column(name: 'log_entity_id', type: Types::INTEGER, nullable: true)]
    #[Groups(['audit:read'])]
    private ?int $entityId = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'log_details', type: Types::JSON, nullable: true)]
    #[Groups(['audit:read'])]
    private ?array $details = null;

    #[ORM\Column(name: 'log_ip_address', type: Types::STRING, length: 64, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'log_user_agent', type: Types::STRING, length: 500, nullable: true)]
    #[Groups(['audit:read'])]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'log_created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['audit:read', 'audit:list'])]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerformer(): ?User
    {
        return $this->performer;
    }

    public function setPerformer(?User $performer): static
    {
        $this->performer = $performer;

        return $this;
    }

    public function getTargetUser(): ?User
    {
        return $this->targetUser;
    }

    public function setTargetUser(?User $targetUser): static
    {
        $this->targetUser = $targetUser;

        return $this;
    }

    public function getAction(): AuditAction
    {
        return $this->action;
    }

    public function setAction(AuditAction $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDetails(): ?array
    {
        return $this->details;
    }

    /**
     * @param array<string, mixed>|null $details
     */
    public function setDetails(?array $details): static
    {
        $this->details = $details;

        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
