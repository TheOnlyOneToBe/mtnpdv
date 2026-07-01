<?php

declare(strict_types=1);

namespace App\Tests\Domain\Enum;

use App\Domain\Enum\StatutFlux;
use PHPUnit\Framework\TestCase;

final class StatutFluxTest extends TestCase
{
    public function testTransitionsAutorisees(): void
    {
        self::assertSame([StatutFlux::VALIDE, StatutFlux::ANNULE], StatutFlux::EN_ATTENTE->transitionsPossibles());
        self::assertSame([StatutFlux::EXPEDIE, StatutFlux::ANNULE], StatutFlux::VALIDE->transitionsPossibles());
        self::assertSame([StatutFlux::LIVRE, StatutFlux::ANNULE], StatutFlux::EXPEDIE->transitionsPossibles());
        self::assertSame([], StatutFlux::LIVRE->transitionsPossibles());
        self::assertSame([], StatutFlux::ANNULE->transitionsPossibles());
    }

    public function testStatutsTermines(): void
    {
        self::assertTrue(StatutFlux::LIVRE->estTermine());
        self::assertTrue(StatutFlux::ANNULE->estTermine());
        self::assertFalse(StatutFlux::EN_ATTENTE->estTermine());
        self::assertFalse(StatutFlux::VALIDE->estTermine());
        self::assertFalse(StatutFlux::EXPEDIE->estTermine());
    }
}
