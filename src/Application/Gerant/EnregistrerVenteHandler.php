<?php

declare(strict_types=1);

namespace App\Application\Gerant;

use App\Domain\Entity\Transaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;

final class EnregistrerVenteHandler
{
    public function __construct(
        private PointVenteRepositoryInterface $pointVenteRepository,
        private TransactionRepositoryInterface $transactionRepository,
    ) {}

    public function handle(EnregistrerVenteCommande $commande): Transaction
    {
        $pointVente = $this->pointVenteRepository->trouverParId($commande->pointVenteId);

        if (null === $pointVente) {
            throw new \InvalidArgumentException(sprintf('Point de vente #%d introuvable', $commande->pointVenteId));
        }

        $coordonnees = new Coordonnees($commande->latitude, $commande->longitude);
        $montant = Montant::fromCentimes($commande->montantCentimes);

        $transaction = new Transaction(
            TypeTransaction::VENTE,
            $montant,
            $coordonnees,
        );

        $transaction->setPointVente($pointVente);
        $transaction->setCommentaireRapport($commande->commentaire);

        // Les gérants/filiales valident directement leurs propres ventes
        $transaction->valider();

        $this->transactionRepository->save($transaction);

        return $transaction;
    }
}
