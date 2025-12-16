<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\TokenType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'énumération TokenType.
 */
final class TokenTypeTest extends TestCase
{
    public function testCasesExist(): void
    {
        $cases = TokenType::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(TokenType::CHARACTER, $cases);
        $this->assertContains(TokenType::MONSTER, $cases);
        $this->assertContains(TokenType::NPC, $cases);
        $this->assertContains(TokenType::OBJECT, $cases);
    }

    public function testValues(): void
    {
        $this->assertSame('character', TokenType::CHARACTER->value);
        $this->assertSame('monster', TokenType::MONSTER->value);
        $this->assertSame('npc', TokenType::NPC->value);
        $this->assertSame('object', TokenType::OBJECT->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Personnage', TokenType::CHARACTER->getLabel());
        $this->assertSame('Monstre', TokenType::MONSTER->getLabel());
        $this->assertSame('PNJ', TokenType::NPC->getLabel());
        $this->assertSame('Objet', TokenType::OBJECT->getLabel());
    }

    public function testIsPlayerControllable(): void
    {
        $this->assertTrue(TokenType::CHARACTER->isPlayerControllable());
        $this->assertFalse(TokenType::MONSTER->isPlayerControllable());
        $this->assertFalse(TokenType::NPC->isPlayerControllable());
        $this->assertFalse(TokenType::OBJECT->isPlayerControllable());
    }

    public function testCanHaveHealthPoints(): void
    {
        $this->assertTrue(TokenType::CHARACTER->canHaveHealthPoints());
        $this->assertTrue(TokenType::MONSTER->canHaveHealthPoints());
        $this->assertTrue(TokenType::NPC->canHaveHealthPoints());
        $this->assertFalse(TokenType::OBJECT->canHaveHealthPoints());
    }

    public function testIsCreature(): void
    {
        $this->assertTrue(TokenType::CHARACTER->isCreature());
        $this->assertTrue(TokenType::MONSTER->isCreature());
        $this->assertTrue(TokenType::NPC->isCreature());
        $this->assertFalse(TokenType::OBJECT->isCreature());
    }

    public function testIsHostileByDefault(): void
    {
        $this->assertFalse(TokenType::CHARACTER->isHostileByDefault());
        $this->assertTrue(TokenType::MONSTER->isHostileByDefault());
        $this->assertFalse(TokenType::NPC->isHostileByDefault());
        $this->assertFalse(TokenType::OBJECT->isHostileByDefault());
    }

    public function testGetDefaultIcon(): void
    {
        $this->assertSame('🧙', TokenType::CHARACTER->getDefaultIcon());
        $this->assertSame('👹', TokenType::MONSTER->getDefaultIcon());
        $this->assertSame('👤', TokenType::NPC->getDefaultIcon());
        $this->assertSame('📦', TokenType::OBJECT->getDefaultIcon());
    }

    public function testFromString(): void
    {
        $this->assertSame(TokenType::CHARACTER, TokenType::from('character'));
        $this->assertSame(TokenType::MONSTER, TokenType::from('monster'));
        $this->assertSame(TokenType::NPC, TokenType::from('npc'));
        $this->assertSame(TokenType::OBJECT, TokenType::from('object'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(TokenType::CHARACTER, TokenType::tryFrom('character'));
        $this->assertNull(TokenType::tryFrom('invalid'));
    }
}
