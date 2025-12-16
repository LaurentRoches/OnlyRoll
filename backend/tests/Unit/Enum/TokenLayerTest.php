<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\TokenLayer;
use App\Enum\TokenType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'énumération TokenLayer.
 */
final class TokenLayerTest extends TestCase
{
    public function testCasesExist(): void
    {
        $cases = TokenLayer::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(TokenLayer::BACKGROUND, $cases);
        $this->assertContains(TokenLayer::OBJECTS, $cases);
        $this->assertContains(TokenLayer::TOKENS, $cases);
        $this->assertContains(TokenLayer::EFFECTS, $cases);
    }

    public function testValues(): void
    {
        $this->assertSame('background', TokenLayer::BACKGROUND->value);
        $this->assertSame('objects', TokenLayer::OBJECTS->value);
        $this->assertSame('tokens', TokenLayer::TOKENS->value);
        $this->assertSame('effects', TokenLayer::EFFECTS->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Arrière-plan', TokenLayer::BACKGROUND->getLabel());
        $this->assertSame('Objets', TokenLayer::OBJECTS->getLabel());
        $this->assertSame('Personnages', TokenLayer::TOKENS->getLabel());
        $this->assertSame('Effets', TokenLayer::EFFECTS->getLabel());
    }

    public function testGetZIndex(): void
    {
        $this->assertSame(1, TokenLayer::BACKGROUND->getZIndex());
        $this->assertSame(2, TokenLayer::OBJECTS->getZIndex());
        $this->assertSame(3, TokenLayer::TOKENS->getZIndex());
        $this->assertSame(4, TokenLayer::EFFECTS->getZIndex());
    }

    public function testIsSelectable(): void
    {
        $this->assertFalse(TokenLayer::BACKGROUND->isSelectable());
        $this->assertTrue(TokenLayer::OBJECTS->isSelectable());
        $this->assertTrue(TokenLayer::TOKENS->isSelectable());
        $this->assertFalse(TokenLayer::EFFECTS->isSelectable());
    }

    public function testBlocksMovement(): void
    {
        $this->assertFalse(TokenLayer::BACKGROUND->blocksMovement());
        $this->assertTrue(TokenLayer::OBJECTS->blocksMovement());
        $this->assertTrue(TokenLayer::TOKENS->blocksMovement());
        $this->assertFalse(TokenLayer::EFFECTS->blocksMovement());
    }

    public function testIsVisibleByDefault(): void
    {
        $this->assertTrue(TokenLayer::BACKGROUND->isVisibleByDefault());
        $this->assertTrue(TokenLayer::OBJECTS->isVisibleByDefault());
        $this->assertTrue(TokenLayer::TOKENS->isVisibleByDefault());
        $this->assertTrue(TokenLayer::EFFECTS->isVisibleByDefault());
    }

    public function testGetDefaultForTokenType(): void
    {
        $this->assertSame(TokenLayer::TOKENS, TokenLayer::getDefaultForTokenType(TokenType::CHARACTER));
        $this->assertSame(TokenLayer::TOKENS, TokenLayer::getDefaultForTokenType(TokenType::MONSTER));
        $this->assertSame(TokenLayer::TOKENS, TokenLayer::getDefaultForTokenType(TokenType::NPC));
        $this->assertSame(TokenLayer::OBJECTS, TokenLayer::getDefaultForTokenType(TokenType::OBJECT));
    }

    public function testFromString(): void
    {
        $this->assertSame(TokenLayer::BACKGROUND, TokenLayer::from('background'));
        $this->assertSame(TokenLayer::OBJECTS, TokenLayer::from('objects'));
        $this->assertSame(TokenLayer::TOKENS, TokenLayer::from('tokens'));
        $this->assertSame(TokenLayer::EFFECTS, TokenLayer::from('effects'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(TokenLayer::TOKENS, TokenLayer::tryFrom('tokens'));
        $this->assertNull(TokenLayer::tryFrom('invalid'));
    }
}
