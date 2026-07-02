<?php

declare(strict_types=1);

namespace App\Application\Gerant;

use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Coordonnees;

final class EnregistrerVenteCommande
{
    public function __construct(
        public int $pointVenteId,
        public int $produitId,
        public int $quantite,
        public int $montantCentimes,
        public float $latitude,
        public float $longitude,
        public ?string $commentaire = null,
    ) {}
}
