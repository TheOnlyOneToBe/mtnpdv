<?php

declare(strict_types=1);

namespace App\Application\Visite;

use App\Domain\Entity\Transaction;

/**
 * Résultat de l'enregistrement d'une visite : la transaction créée et le
 * verdict de proximité GPS. Une visite hors zone est enregistrée mais
 * signalée — l'administrateur tranche lors de la validation.
 */
final readonly class EnregistrerVisiteResultat
{
    public function __construct(
        public Transaction $transaction,
        public float $distanceMetres,
        public bool $dansLaZone,
    ) {
    }
}
