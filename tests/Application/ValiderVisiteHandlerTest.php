<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Visite\ValiderVisiteHandler;
use App\Domain\Entity\Transaction;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use PHPUnit\Framework\TestCase;

final class ValiderVisiteHandlerTest extends TestCase
{
    private function creerTransaction(): Transaction
    {
        return new Transaction(TypeTransaction::VISITE, Montant::zero(), new Coordonnees(4.05, 9.76));
    }

    public function testValidationPersistee(): void
    {
        $repo = $this->createMock(TransactionRepositoryInterface::class);
        $repo->expects(self::once())->method('save');

        $transaction = $this->creerTransaction();
        (new ValiderVisiteHandler($repo))->valider($transaction);

        self::assertSame(StatutTransaction::VALIDEE, $transaction->getStatut());
    }

    public function testRejetPersiste(): void
    {
        $repo = $this->createMock(TransactionRepositoryInterface::class);
        $repo->expects(self::once())->method('save');

        $transaction = $this->creerTransaction();
        (new ValiderVisiteHandler($repo))->rejeter($transaction);

        self::assertSame(StatutTransaction::REJETEE, $transaction->getStatut());
    }

    public function testValiderUneVisiteDejaTraiteeEchoueSansPersister(): void
    {
        $repo = $this->createMock(TransactionRepositoryInterface::class);
        $repo->expects(self::once())->method('save'); // uniquement le premier appel

        $handler = new ValiderVisiteHandler($repo);
        $transaction = $this->creerTransaction();
        $handler->valider($transaction);

        $this->expectException(\DomainException::class);

        $handler->rejeter($transaction);
    }
}
