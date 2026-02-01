<?php

declare(strict_types=1);

namespace App\DTO\Admin;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour filtrer la liste des utilisateurs en admin.
 */
final class UserFilterDTO
{
    #[Assert\Length(max: 100)]
    public ?string $search = null;

    #[Assert\Choice(choices: ['active', 'inactive', 'deleted', 'locked', 'all'], message: 'Statut invalide')]
    public string $status = 'all';

    #[Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'], message: 'Rôle invalide')]
    public ?string $role = null;

    #[Assert\Range(min: 1, max: 1000)]
    public int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 20;

    #[Assert\Choice(choices: ['id', 'email', 'pseudo', 'createdAt', 'lastLogin'], message: 'Champ de tri invalide')]
    public string $sortBy = 'createdAt';

    #[Assert\Choice(choices: ['ASC', 'DESC'], message: 'Direction de tri invalide')]
    public string $sortDirection = 'DESC';
}
