<?php

declare(strict_types=1);

namespace App\Application\Visite;

use App\Domain\Entity\Transaction;
use App\Domain\Repository\TransactionRepositoryInterface;

/**
 * Validation ou rejet d'une visite par un administrateur.
 * Les gardes de cycle de vie (statut final) sont portées par l'entité.
 */
final class ValiderVisiteHandler
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function valider(Transaction $transaction): void
    {
        $transaction->valider();
        $this->transactions->save($transaction);
    }

    public function rejeter(Transaction $transaction): void
    {
        $transaction->rejeter();
        $this->transactions->save($transaction);
    }
}
