<?php

declare(strict_types=1);

namespace App\Tests\Domain\Enum;

use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use PHPUnit\Framework\TestCase;

final class StatutTransactionTest extends TestCase
{
    public function testSeulEnAttenteNEstPasFinal(): void
    {
        self::assertFalse(StatutTransaction::EN_ATTENTE->estFinal());
        self::assertTrue(StatutTransaction::VALIDEE->estFinal());
        self::assertTrue(StatutTransaction::REJETEE->estFinal());
        self::assertTrue(StatutTransaction::ANNULEE->estFinal());
    }

    public function testTypeVisiteExisteEtNEstPasUnCredit(): void
    {
        self::assertSame('VISITE', TypeTransaction::VISITE->value);
        self::assertSame('Visite de contrôle', TypeTransaction::VISITE->libelle());
        self::assertFalse(TypeTransaction::VISITE->estCredit());
        self::assertTrue(TypeTransaction::VENTE->estCredit());
    }
}
