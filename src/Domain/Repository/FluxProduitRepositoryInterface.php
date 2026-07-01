<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\Produit;

interface FluxProduitRepositoryInterface
{
    public function find(int $id): ?FluxProduit;

    /** @return list<FluxProduit> */
    public function findByFlux(FluxRavitaillement $flux): array;

    /**
     * Quantité totale ravitaillée pour un produit, tous flux confondus.
     */
    public function quantiteTotalePourProduit(Produit $produit): int;

    /**
     * Quantités totales par produit, triées de la plus grande à la plus petite.
     *
     * @return list<array{produitId: int, nomProd: string, quantiteTotale: int}>
     */
    public function quantitesTotalesParProduit(): array;

    public function save(FluxProduit $ligne, bool $flush = true): void;

    public function remove(FluxProduit $ligne, bool $flush = true): void;
}
