<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;

/**
 * Service responsable du parsing, de la validation et du calcul des jets de dés.
 *
 * Formats supportés : XdY, XdY+Z, XdYkhN, XdYklN, XdYkhN+Z, XdYklN+Z
 */
class DiceService
{
    private const REGEX = '/^(\d+)d(\d+)(?:(kh|kl)(\d+))?([+-]\d+)?$/i';

    /**
     * Valide et parse une formule de dés.
     *
     * @return array{
     *   numberOfDice: int,
     *   sidesPerDie: int,
     *   keepType: string|null,
     *   keepCount: int|null,
     *   modifier: int
     * }
     *
     * @throws InvalidArgumentException si la formule est invalide
     */
    public function parseFormula(string $formula): array
    {
        if (!preg_match(self::REGEX, $formula, $matches)) {
            throw new InvalidArgumentException(
                'Format de dés invalide. Utilisez le format XdY, XdY+Z, XdYkhN ou XdYklN',
            );
        }

        $numberOfDice = (int) $matches[1];
        $sidesPerDie = (int) $matches[2];
        $keepType = isset($matches[3]) && $matches[3] !== '' ? strtolower($matches[3]) : null;
        $keepCount = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : null;
        $modifier = !empty($matches[5]) ? (int) $matches[5] : 0;

        if ($numberOfDice < 1 || $numberOfDice > 100) {
            throw new InvalidArgumentException('Le nombre de dés doit être entre 1 et 100');
        }

        if ($sidesPerDie < 2 || $sidesPerDie > 1000) {
            throw new InvalidArgumentException('Le nombre de faces doit être entre 2 et 1000');
        }

        if (null !== $keepCount) {
            if ($keepCount < 1 || $keepCount >= $numberOfDice) {
                throw new InvalidArgumentException(
                    \sprintf(
                        'keepCount doit être entre 1 et %d (strictement inférieur au nombre de dés)',
                        $numberOfDice - 1,
                    ),
                );
            }
        }

        return [
            'numberOfDice' => $numberOfDice,
            'sidesPerDie' => $sidesPerDie,
            'keepType' => $keepType,
            'keepCount' => $keepCount,
            'modifier' => $modifier,
        ];
    }

    /**
     * Lance un nombre donné de dés et retourne tous les résultats bruts.
     *
     * @return int[]
     */
    public function rollDice(int $count, int $sides): array
    {
        $rolls = [];

        for ($i = 0; $i < $count; ++$i) {
            $rolls[] = random_int(1, $sides);
        }

        return $rolls;
    }

    /**
     * Applique la règle kh (keep highest) ou kl (keep lowest) sur un tableau de résultats.
     *
     * @param int[] $rolls
     * @param string|null $keepType 'kh' | 'kl' | null
     * @param int|null $keepCount Nombre de dés à garder (null = garder tous)
     *
     * @return array{keptRolls: int[], dropped: int[], total: int}
     */
    public function applyKeep(array $rolls, ?string $keepType, ?int $keepCount, int $sidesPerDie, int $modifier): array
    {
        if (null === $keepType || null === $keepCount) {
            return [
                'keptRolls' => $rolls,
                'dropped' => [],
                'total' => array_sum($rolls) + $modifier,
            ];
        }

        $sorted = $rolls;

        if ('kh' === $keepType) {
            rsort($sorted);
        }
        else {
            sort($sorted);
        }

        $keptRolls = \array_slice($sorted, 0, $keepCount);
        $dropped = \array_slice($sorted, $keepCount);

        return [
            'keptRolls' => $keptRolls,
            'dropped' => $dropped,
            'total' => array_sum($keptRolls) + $modifier,
        ];
    }
}
