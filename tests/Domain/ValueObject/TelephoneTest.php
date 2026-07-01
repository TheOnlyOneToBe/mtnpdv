<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TelephoneTest extends TestCase
{
    #[DataProvider('provideNumerosValides')]
    public function testNormalisation(string $entree, string $attendu): void
    {
        self::assertSame($attendu, Telephone::fromString($entree)->value());
    }

    /** @return iterable<array{string, string}> */
    public static function provideNumerosValides(): iterable
    {
        yield 'international avec espaces et tirets' => ['+237 6 90-12-34-56', '+237690123456'];
        yield 'points et parenthèses' => ['(690) 12.34.56', '690123456'];
        yield 'simple' => ['690123456', '690123456'];
    }

    #[DataProvider('provideNumerosInvalides')]
    public function testNumeroInvalideRejete(string $entree): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Telephone::fromString($entree);
    }

    /** @return iterable<array{string}> */
    public static function provideNumerosInvalides(): iterable
    {
        yield 'trop court' => ['12345'];
        yield 'lettres' => ['69O12E456'];
        yield 'vide' => [''];
        yield 'plus au milieu' => ['690+123456'];
    }
}
