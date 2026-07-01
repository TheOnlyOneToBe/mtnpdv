<?php

declare(strict_types=1);

namespace App\Application\PointVente;

use App\Domain\Entity\CategoriePdv;
use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Telephone;

/**
 * Données d'enregistrement d'un point de vente (admin ou agent sur le terrain).
 */
final readonly class EnregistrerPointVenteCommande
{
    public function __construct(
        public string $nomPdv,
        public string $codeRef,
        public Coordonnees $coordonnees,
        public string $ville,
        public Telephone $telephone,
        public ?string $adresse = null,
        public ?CategoriePdv $categorie = null,
        public ?Utilisateur $gerant = null,
    ) {
    }
}
