<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\AttributionPdv;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;

interface AttributionPdvRepositoryInterface
{
    public function find(int $id): ?AttributionPdv;

    /** @return list<AttributionPdv> */
    public function findAll(): array;

    /** @return list<AttributionPdv> */
    public function findByAgent(Utilisateur $agent): array;

    /** @return list<AttributionPdv> */
    public function findByPointVente(int $pointVenteId): array;

    /** @return list<AttributionPdv> */
    public function findAttives(): array;

    /** @return list<AttributionPdv> */
    public function findAttivesByAgent(Utilisateur $agent): array;

    /** @return list<AttributionPdv> */
    public function findAttivesByPointVente(PointVente $pointVente): array;

    /** @return list<AttributionPdv> */
    public function search(string $terme, ?bool $actif = null): array;

    public function save(AttributionPdv $attribution, bool $flush = true): void;

    public function remove(AttributionPdv $attribution, bool $flush = true): void;
}