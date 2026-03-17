<?php

declare(strict_types=1);

namespace App\Enum;

enum SrdTable: string
{
    case SPELL      = 'spell';
    case RACE       = 'race';
    case CLASS_     = 'class';
    case ITEM       = 'item';
    case MONSTER    = 'monster';
    case BACKGROUND = 'background';
    case FEAT       = 'feat';

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
