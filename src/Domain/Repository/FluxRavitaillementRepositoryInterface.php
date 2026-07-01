<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\PointVente;
use App\Domain\Enum\StatutFlux;
use App\Domain\ValueObject\Montant;

interface FluxRavitaillementRepositoryInterface
{
    public function find(int $id): ?FluxRavitaillement;

    /** @return list<FluxRavitaillement> */
    public function findAll(): array;

    public function findOneByFacture(string $factureUniq): ?FluxRavitaillement;

    /** @return list<FluxRavitaillement> */
    public function findByStatut(StatutFlux $statut): array;

    /** @return list<FluxRavitaillement> */
    public function findByPointVente(PointVente $pointVente): array;

    /** @return list<FluxRavitaillement> */
    public function findEntre(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array;

    /**
     * Somme des montants des flux livrés à un point de vente.
     */
    public function montantTotalLivre(PointVente $pointVente): Montant;

    public function save(FluxRavitaillement $flux, bool $flush = true): void;

    public function remove(FluxRavitaillement $flux, bool $flush = true): void;
}
