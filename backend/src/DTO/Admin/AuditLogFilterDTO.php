<?php

declare(strict_types=1);

namespace App\DTO\Admin;

use App\Enum\AuditAction;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour filtrer les logs d'audit.
 */
final class AuditLogFilterDTO
{
    public ?int $userId = null;

    public ?int $targetUserId = null;

    public ?AuditAction $action = null;

    #[Assert\Length(max: 50)]
    public ?string $entityType = null;

    public ?DateTimeImmutable $dateFrom = null;

    public ?DateTimeImmutable $dateTo = null;

    #[Assert\Choice(choices: ['info', 'warning', 'high'], message: 'Sévérité invalide')]
    public ?string $severity = null;

    #[Assert\Range(min: 1, max: 1000)]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 50;
}
