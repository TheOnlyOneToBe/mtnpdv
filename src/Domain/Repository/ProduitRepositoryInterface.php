<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\CategorieProd;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Produit;
use App\Domain\ValueObject\Montant;

interface ProduitRepositoryInterface
{
    public function find(int $id): ?Produit;

    /** @return list<Produit> */
    public function findAll(): array;

    public function findOneByCodeBarre(string $codeBarre): ?Produit;

    /** @return list<Produit> */
    public function findActifs(): array;

    /** @return list<Produit> */
    public function findByCategorie(CategorieProd $categorie): array;

    /** @return list<Produit> */
    public function rechercherParNom(string $terme): array;

    /** @return list<Produit> */
    public function findDansFourchettePrix(Montant $min, Montant $max): array;

    /**
     * Produits livrés à un point de vente via les flux de ravitaillement LIVRE,
     * avec la quantité totale livrée pour chacun.
     *
     * @return list<array{produit: Produit, quantiteLivree: int}>
     */
    public function findLivresAuPointVente(PointVente $pointVente): array;

    public function save(Produit $produit, bool $flush = true): void;

    public function remove(Produit $produit, bool $flush = true): void;
}
