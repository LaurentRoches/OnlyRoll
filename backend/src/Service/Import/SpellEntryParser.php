<?php

declare(strict_types=1);

namespace App\Service\Import;

/**
 * Parses 5etools spell field structures (time, range, duration)
 * into human-readable strings used by the Spell entity.
 */
final class SpellEntryParser
{
    public function parseCastingTime(array $time): string
    {
        if (empty($time)) {
            return '1 action';
        }
        $first  = $time[0];
        $number = $first['number'] ?? 1;
        $unit   = $first['unit'] ?? 'action';

        return $number === 1 ? '1 ' . $unit : $number . ' ' . $unit . 's';
    }

    /** @return array{string, int|null} [rangeType, rangeDistance] */
    public function parseRange(array $range): array
    {
        $type = strtolower($range['type'] ?? 'point');

        if ($type === 'special') {
            return ['special', null];
        }
        if (in_array($type, ['self', 'touch', 'sight', 'unlimited'])) {
            return [$type, null];
        }

        $distance = $range['distance'] ?? [];
        $distType = strtolower($distance['type'] ?? 'feet');
        $amount   = $distance['amount'] ?? null;

        if ($distType === 'feet' && $amount !== null) {
            return ['point', (int)$amount];
        }
        if ($distType === 'miles' && $amount !== null) {
            return ['point', (int)$amount * 5280];
        }

        return [$type, null];
    }

    public function parseDuration(array $durations): string
    {
        if (empty($durations)) {
            return 'Instantaneous';
        }
        $first = $durations[0];
        $type  = $first['type'] ?? 'instant';

        if ($type === 'instant') {
            return 'Instantaneous';
        }
        if ($type === 'permanent') {
            return 'Until dispelled';
        }
        if ($type === 'special') {
            return 'Special';
        }

        $concentration = !empty($first['concentration']);
        $duration      = $first['duration'] ?? [];
        $amount        = $duration['amount'] ?? 1;
        $unit          = $duration['type'] ?? 'round';

        $text = $amount . ' ' . $unit . ($amount > 1 ? 's' : '');

        return $concentration ? 'Concentration, up to ' . $text : ucfirst($text);
    }

    public function parseDurationConcentration(array $durations): bool
    {
        return !empty($durations[0]['concentration']);
    }
}
