<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Statuts possibles d'un joueur dans une partie.
 */
enum PlayerStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case KICKED = 'kicked';
    case LEFT = 'left';

    public function isParticipating(): bool
    {
        return match ($this) {
            self::ACTIVE, self::INACTIVE, self::PENDING => true,
            self::KICKED, self::LEFT => false,
        };
    }

    public function canReactivate(): bool
    {
        return match ($this) {
            self::INACTIVE, self::LEFT, self::KICKED => true,
            default => false,
        };
    }

    public function needsNewInvite(): bool
    {
        return match ($this) {
            self::LEFT, self::KICKED => true,
            default => false,
        };
    }

    public function hasLeft(): bool
    {
        return match ($this) {
            self::KICKED, self::LEFT => true,
            default => false,
        };
    }

    public function canAccessGame(): bool
    {
        return $this->isParticipating();
    }
}
