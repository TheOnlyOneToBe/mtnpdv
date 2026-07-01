<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutPointVente;
use App\Domain\ValueObject\Coordonnees;

interface PointVenteRepositoryInterface
{
    public function find(int $id): ?PointVente;

    /** @return list<PointVente> */
    public function findAll(): array;

    public function findOneByCodeRef(string $codeRef): ?PointVente;

    /** @return list<PointVente> */
    public function findByVille(string $ville): array;

    /**
     * Recherche par nom, ville ou code de référence (insensible à la casse).
     *
     * @return list<PointVente>
     */
    public function rechercher(string $terme): array;

    /** @return list<PointVente> */
    public function findByStatut(StatutPointVente $statut): array;

    /** @return list<PointVente> */
    public function findByGerant(Utilisateur $gerant): array;

    /**
     * Points de vente dans un rayon donné (km) autour d'une position, du plus proche au plus éloigné.
     *
     * @return list<PointVente>
     */
    public function findProches(Coordonnees $position, float $rayonKm): array;

    /** @return array<string, int> statut => nombre de points de vente */
    public function compterParStatut(): array;

    public function save(PointVente $pointVente, bool $flush = true): void;

    public function remove(PointVente $pointVente, bool $flush = true): void;
}
