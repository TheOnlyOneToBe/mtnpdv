<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Transaction;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    private function creerTransaction(): Transaction
    {
        return new Transaction(
            TypeTransaction::VISITE,
            Montant::zero(),
            new Coordonnees(4.0511, 9.7679),
        );
    }

    public function testEtatInitial(): void
    {
        $transaction = $this->creerTransaction();

        self::assertSame(StatutTransaction::EN_ATTENTE, $transaction->getStatut());
        self::assertEqualsWithDelta(time(), $transaction->getDateTransac()->getTimestamp(), 2);
        self::assertEqualsWithDelta(4.0511, $transaction->getCoordonneesCapture()->latitude(), 1e-6);
    }

    public function testValidation(): void
    {
        $transaction = $this->creerTransaction();
        $transaction->valider();

        self::assertSame(StatutTransaction::VALIDEE, $transaction->getStatut());
    }

    public function testRejet(): void
    {
        $transaction = $this->creerTransaction();
        $transaction->rejeter();

        self::assertSame(StatutTransaction::REJETEE, $transaction->getStatut());
    }

    public function testRevaliderUneTransactionFinaleEstInterdit(): void
    {
        $transaction = $this->creerTransaction();
        $transaction->valider();

        $this->expectException(\DomainException::class);

        $transaction->rejeter();
    }

    public function testCoordonneesCaptureModifiables(): void
    {
        $transaction = $this->creerTransaction();
        $transaction->setCoordonneesCapture(new Coordonnees(3.8480, 11.5021));

        self::assertEqualsWithDelta(11.5021, $transaction->getCoordonneesCapture()->longitude(), 1e-6);
    }
}
