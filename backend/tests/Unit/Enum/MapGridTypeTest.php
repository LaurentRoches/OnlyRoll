<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\MapGridType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'énumération MapGridType.
 */
final class MapGridTypeTest extends TestCase
{
    public function testCasesExist(): void
    {
        $cases = MapGridType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(MapGridType::SQUARE, $cases);
        $this->assertContains(MapGridType::HEX, $cases);
        $this->assertContains(MapGridType::NONE, $cases);
    }

    public function testValues(): void
    {
        $this->assertSame('square', MapGridType::SQUARE->value);
        $this->assertSame('hex', MapGridType::HEX->value);
        $this->assertSame('none', MapGridType::NONE->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Carrée', MapGridType::SQUARE->getLabel());
        $this->assertSame('Hexagonale', MapGridType::HEX->getLabel());
        $this->assertSame('Aucune', MapGridType::NONE->getLabel());
    }

    public function testHasDistanceCalculation(): void
    {
        $this->assertTrue(MapGridType::SQUARE->hasDistanceCalculation());
        $this->assertTrue(MapGridType::HEX->hasDistanceCalculation());
        $this->assertFalse(MapGridType::NONE->hasDistanceCalculation());
    }

    public function testGetDirectionCount(): void
    {
        $this->assertSame(8, MapGridType::SQUARE->getDirectionCount());
        $this->assertSame(6, MapGridType::HEX->getDirectionCount());
        $this->assertSame(0, MapGridType::NONE->getDirectionCount());
    }

    public function testSupportsDiagonalMovement(): void
    {
        $this->assertTrue(MapGridType::SQUARE->supportsDiagonalMovement());
        $this->assertFalse(MapGridType::HEX->supportsDiagonalMovement());
        $this->assertFalse(MapGridType::NONE->supportsDiagonalMovement());
    }

    public function testFromString(): void
    {
        $this->assertSame(MapGridType::SQUARE, MapGridType::from('square'));
        $this->assertSame(MapGridType::HEX, MapGridType::from('hex'));
        $this->assertSame(MapGridType::NONE, MapGridType::from('none'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(MapGridType::SQUARE, MapGridType::tryFrom('square'));
        $this->assertNull(MapGridType::tryFrom('invalid'));
    }
}
