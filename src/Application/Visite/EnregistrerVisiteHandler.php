<?php

declare(strict_types=1);

namespace App\Application\Visite;

use App\Domain\Entity\Transaction;
use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Enregistre une visite ou transaction terrain avec contrôle de proximité GPS :
 * la position capturée de l'agent est comparée à celle du point de vente
 * (rayon de tolérance configurable via le paramètre app.rayon_tolerance_metres).
 */
final class EnregistrerVisiteHandler
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
        #[Autowire(param: 'app.rayon_tolerance_metres')]
        private readonly int $rayonToleranceMetres,
    ) {
    }

    public function __invoke(EnregistrerVisiteCommande $commande): EnregistrerVisiteResultat
    {
        $distanceMetres = $commande->positionAgent
            ->distanceVers($commande->pointVente->getCoordonnees()) * 1000;

        $transaction = new Transaction(
            $commande->type,
            $commande->montant,
            $commande->positionAgent,
        );

        $transaction
            ->setPointVente($commande->pointVente)
            ->setUtilisateur($commande->agent)
            ->setCommentaireRapport($commande->commentaire);

        if (null !== $commande->photo) {
            $transaction->setPhotoFile($commande->photo);
        }

        $this->transactions->save($transaction);

        return new EnregistrerVisiteResultat(
            $transaction,
            round($distanceMetres, 1),
            $distanceMetres <= $this->rayonToleranceMetres,
        );
    }
}
