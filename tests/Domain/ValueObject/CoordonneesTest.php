<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Coordonnees;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CoordonneesTest extends TestCase
{
    public function testConstructionEtLecture(): void
    {
        $coordonnees = new Coordonnees(4.0511, 9.7679);

        self::assertEqualsWithDelta(4.0511, $coordonnees->latitude(), 1e-8);
        self::assertEqualsWithDelta(9.7679, $coordonnees->longitude(), 1e-8);
    }

    #[DataProvider('provideHorsLimites')]
    public function testBornesRejetees(float $latitude, float $longitude): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Coordonnees($latitude, $longitude);
    }

    /** @return iterable<array{float, float}> */
    public static function provideHorsLimites(): iterable
    {
        yield 'latitude trop grande' => [90.1, 0.0];
        yield 'latitude trop petite' => [-90.1, 0.0];
        yield 'longitude trop grande' => [0.0, 180.1];
        yield 'longitude trop petite' => [0.0, -180.1];
    }

    public function testDistanceDoualaYaounde(): void
    {
        $douala = new Coordonnees(4.0511, 9.7679);
        $yaounde = new Coordonnees(3.8480, 11.5021);

        // Distance à vol d'oiseau connue : ≈ 194 km
        self::assertEqualsWithDelta(194.0, $douala->distanceVers($yaounde), 1.0);
        // Symétrie
        self::assertEqualsWithDelta($douala->distanceVers($yaounde), $yaounde->distanceVers($douala), 1e-9);
    }

    public function testDistanceNulleVersSoiMeme(): void
    {
        $point = new Coordonnees(4.0511, 9.7679);

        self::assertSame(0.0, $point->distanceVers($point));
    }

    public function testEgalite(): void
    {
        self::assertTrue((new Coordonnees(4.05, 9.76))->equals(new Coordonnees(4.05, 9.76)));
        self::assertFalse((new Coordonnees(4.05, 9.76))->equals(new Coordonnees(4.06, 9.76)));
    }
}
