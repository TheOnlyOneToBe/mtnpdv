<?php

declare(strict_types=1);

namespace App\Application\PointVente;

use App\Domain\Entity\PointVente;
use App\Domain\Repository\PointVenteRepositoryInterface;

/**
 * Enregistrement d'un nouveau point de vente, avec contrôle d'unicité
 * du code de référence. Le statut initial est ACTIF.
 */
final class EnregistrerPointVenteHandler
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointsVente,
    ) {
    }

    /**
     * @throws CodeRefDejaUtiliseException
     */
    public function __invoke(EnregistrerPointVenteCommande $commande): PointVente
    {
        if (null !== $this->pointsVente->findOneByCodeRef($commande->codeRef)) {
            throw new CodeRefDejaUtiliseException($commande->codeRef);
        }

        $pointVente = new PointVente(
            $commande->nomPdv,
            $commande->codeRef,
            $commande->coordonnees,
            $commande->ville,
            $commande->telephone,
        );

        $pointVente
            ->setAdresse($commande->adresse)
            ->setCategoriePdv($commande->categorie)
            ->setGerant($commande->gerant);

        $this->pointsVente->save($pointVente);

        return $pointVente;
    }
}
