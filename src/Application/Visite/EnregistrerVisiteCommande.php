<?php

declare(strict_types=1);

namespace App\Application\Visite;

use App\Domain\Entity\DemandeVisite;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeProblemeSupervision;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Données nécessaires à l'enregistrement d'une visite ou transaction terrain.
 */
final readonly class EnregistrerVisiteCommande
{
    public function __construct(
        public PointVente $pointVente,
        public Utilisateur $agent,
        public TypeTransaction $type,
        public Coordonnees $positionAgent,
        public Montant $montant,
        public ?string $commentaire = null,
        public ?UploadedFile $photo = null,
        public ?TypeProblemeSupervision $typeProbleme = null,
        public ?DemandeVisite $demandeVisite = null,
    ) {
    }
}
