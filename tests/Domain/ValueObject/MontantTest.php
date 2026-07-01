<?php

declare(strict_types=1);

namespace App\Tests\Domain\ValueObject;

use App\Domain\ValueObject\Montant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MontantTest extends TestCase
{
    #[DataProvider('provideMontantsValides')]
    public function testParsingEtRepresentationDecimale(string $entree, int $centimesAttendus, string $decimalAttendu): void
    {
        $montant = Montant::fromString($entree);

        self::assertSame($centimesAttendus, $montant->centimes());
        self::assertSame($decimalAttendu, $montant->toDecimal());
    }

    /** @return iterable<array{string, int, string}> */
    public static function provideMontantsValides(): iterable
    {
        yield 'entier' => ['1250', 125000, '1250.00'];
        yield 'deux décimales' => ['1250.50', 125050, '1250.50'];
        yield 'une décimale' => ['9.5', 950, '9.50'];
        yield 'virgule française' => ['12,75', 1275, '12.75'];
        yield 'zéro' => ['0', 0, '0.00'];
        yield 'négatif' => ['-42.10', -4210, '-42.10'];
    }

    #[DataProvider('provideMontantsInvalides')]
    public function testMontantInvalideRejete(string $entree): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Montant::fromString($entree);
    }

    /** @return iterable<array{string}> */
    public static function provideMontantsInvalides(): iterable
    {
        yield 'texte' => ['abc'];
        yield 'trois décimales' => ['10.123'];
        yield 'vide' => [''];
        yield 'double point' => ['1.2.3'];
    }

    public function testOperationsArithmetiques(): void
    {
        $montant = Montant::fromString('100.25')
            ->ajouter(Montant::fromString('49.75'))
            ->multiplier(2)
            ->soustraire(Montant::fromString('0.50'));

        self::assertSame('299.50', $montant->toDecimal());
    }

    public function testComparaisons(): void
    {
        $petit = Montant::fromString('10.00');
        $grand = Montant::fromString('20.00');

        self::assertTrue($petit->equals(Montant::fromCentimes(1000)));
        self::assertSame(-1, $petit->compare($grand));
        self::assertSame(1, $grand->compare($petit));
        self::assertTrue($grand->estPositif());
        self::assertFalse(Montant::zero()->estPositif());
    }

    public function testPasDErreurDArrondiFlottant(): void
    {
        // 0.1 + 0.2 != 0.3 en flottant ; en centimes le résultat est exact
        $somme = Montant::fromString('0.10')->ajouter(Montant::fromString('0.20'));

        self::assertTrue($somme->equals(Montant::fromString('0.30')));
    }
}
