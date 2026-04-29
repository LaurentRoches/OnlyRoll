<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\DiceService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DiceServiceTest extends TestCase
{
    private DiceService $diceService;

    protected function setUp(): void
    {
        $this->diceService = new DiceService();
    }

    // ─── parseFormula ────────────────────────────────────────────────────────

    public function testParseFormulaStandardRoll(): void
    {
        $result = $this->diceService->parseFormula('3d6');

        $this->assertSame(3, $result['numberOfDice']);
        $this->assertSame(6, $result['sidesPerDie']);
        $this->assertNull($result['keepType']);
        $this->assertNull($result['keepCount']);
        $this->assertSame(0, $result['modifier']);
    }

    public function testParseFormulaWithPositiveModifier(): void
    {
        $result = $this->diceService->parseFormula('1d20+5');

        $this->assertSame(1, $result['numberOfDice']);
        $this->assertSame(20, $result['sidesPerDie']);
        $this->assertSame(5, $result['modifier']);
    }

    public function testParseFormulaWithNegativeModifier(): void
    {
        $result = $this->diceService->parseFormula('2d8-2');

        $this->assertSame(2, $result['numberOfDice']);
        $this->assertSame(8, $result['sidesPerDie']);
        $this->assertSame(-2, $result['modifier']);
    }

    public function testParseFormulaKeepHighest(): void
    {
        $result = $this->diceService->parseFormula('4d6kh3');

        $this->assertSame(4, $result['numberOfDice']);
        $this->assertSame(6, $result['sidesPerDie']);
        $this->assertSame('kh', $result['keepType']);
        $this->assertSame(3, $result['keepCount']);
        $this->assertSame(0, $result['modifier']);
    }

    public function testParseFormulaKeepLowest(): void
    {
        $result = $this->diceService->parseFormula('2d20kl1');

        $this->assertSame('kl', $result['keepType']);
        $this->assertSame(1, $result['keepCount']);
    }

    public function testParseFormulaInvalidFormatThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Format de dés invalide');

        $this->diceService->parseFormula('abc');
    }

    public function testParseFormulaZeroDiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de dés doit être entre 1 et 100');

        $this->diceService->parseFormula('0d6');
    }

    public function testParseFormulaTooManyDiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de dés doit être entre 1 et 100');

        $this->diceService->parseFormula('101d6');
    }

    public function testParseFormulaTooFewSidesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de faces doit être entre 2 et 1000');

        $this->diceService->parseFormula('1d1');
    }

    public function testParseFormulaKeepCountEqualsNumberOfDiceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('keepCount doit être entre 1');

        $this->diceService->parseFormula('3d6kh3');
    }

    // ─── rollDice ─────────────────────────────────────────────────────────────

    public function testRollDiceReturnsCorrectCount(): void
    {
        $rolls = $this->diceService->rollDice(3, 6);

        $this->assertCount(3, $rolls);
    }

    public function testRollDiceValuesWithinRange(): void
    {
        $rolls = $this->diceService->rollDice(100, 6);

        foreach ($rolls as $roll) {
            $this->assertGreaterThanOrEqual(1, $roll);
            $this->assertLessThanOrEqual(6, $roll);
        }
    }

    // ─── applyKeep ───────────────────────────────────────────────────────────

    public function testApplyKeepWithoutKeepRule(): void
    {
        $result = $this->diceService->applyKeep([3, 5, 1], null, null, 6, 0);

        $this->assertSame([3, 5, 1], $result['keptRolls']);
        $this->assertSame([], $result['dropped']);
        $this->assertSame(9, $result['total']);
    }

    public function testApplyKeepHighestWithModifier(): void
    {
        // 4d6kh3+2 : garder les 3 plus hauts parmi [3, 5, 1, 6]
        $result = $this->diceService->applyKeep([3, 5, 1, 6], 'kh', 3, 6, 2);

        $this->assertSame([6, 5, 3], $result['keptRolls']);
        $this->assertSame([1], $result['dropped']);
        $this->assertSame(16, $result['total']); // 6+5+3+2
    }

    public function testApplyKeepLowest(): void
    {
        // 2d20kl1 : désavantage D&D — garder le plus bas parmi [13, 7]
        $result = $this->diceService->applyKeep([13, 7], 'kl', 1, 20, 0);

        $this->assertSame([7], $result['keptRolls']);
        $this->assertSame([13], $result['dropped']);
        $this->assertSame(7, $result['total']);
    }
}
