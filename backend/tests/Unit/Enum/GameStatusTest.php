<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\GameStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'énumération GameStatus.
 */
final class GameStatusTest extends TestCase
{
    public function testCasesExist(): void
    {
        $cases = GameStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContains(GameStatus::PREPARATION, $cases);
        $this->assertContains(GameStatus::IN_PROGRESS, $cases);
        $this->assertContains(GameStatus::PAUSED, $cases);
        $this->assertContains(GameStatus::COMPLETED, $cases);
        $this->assertContains(GameStatus::ARCHIVED, $cases);
    }

    public function testValues(): void
    {
        $this->assertSame('preparation', GameStatus::PREPARATION->value);
        $this->assertSame('in_progress', GameStatus::IN_PROGRESS->value);
        $this->assertSame('paused', GameStatus::PAUSED->value);
        $this->assertSame('completed', GameStatus::COMPLETED->value);
        $this->assertSame('archived', GameStatus::ARCHIVED->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('En préparation', GameStatus::PREPARATION->getLabel());
        $this->assertSame('En cours', GameStatus::IN_PROGRESS->getLabel());
        $this->assertSame('En pause', GameStatus::PAUSED->getLabel());
        $this->assertSame('Terminée', GameStatus::COMPLETED->getLabel());
        $this->assertSame('Archivée', GameStatus::ARCHIVED->getLabel());
    }

    public function testFromString(): void
    {
        $this->assertSame(GameStatus::PREPARATION, GameStatus::from('preparation'));
        $this->assertSame(GameStatus::IN_PROGRESS, GameStatus::from('in_progress'));
        $this->assertSame(GameStatus::PAUSED, GameStatus::from('paused'));
        $this->assertSame(GameStatus::COMPLETED, GameStatus::from('completed'));
        $this->assertSame(GameStatus::ARCHIVED, GameStatus::from('archived'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(GameStatus::PREPARATION, GameStatus::tryFrom('preparation'));
        $this->assertNull(GameStatus::tryFrom('invalid'));
    }
}
