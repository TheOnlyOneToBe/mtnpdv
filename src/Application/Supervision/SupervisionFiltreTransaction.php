<?php

declare(strict_types=1);

namespace App\Application\Supervision;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Montant;

final class SupervisionFiltreTransaction
{
    public function __construct(
        public readonly ?TypeTransaction $type = null,
        public readonly ?PointVente $pointVente = null,
        public readonly ?Utilisateur $agent = null,
        public readonly ?\DateTimeImmutable $debut = null,
        public readonly ?\DateTimeImmutable $fin = null,
        public readonly ?Montant $montantMin = null,
        public readonly ?Montant $montantMax = null,
    ) {
    }
}
