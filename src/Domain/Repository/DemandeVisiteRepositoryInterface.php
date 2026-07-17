<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\DemandeVisite;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Utilisateur;

interface DemandeVisiteRepositoryInterface
{
    public function save(DemandeVisite $entity): void;

    public function findByPointVente(PointVente $pointVente): array;

    public function findByAgent(Utilisateur $agent): array;

    public function findByCreateur(Utilisateur $createur): array;

    public function findPendantesParAgent(Utilisateur $agent): array;

    public function findAValiderParAdmin(): array;

    public function compterParStatut(): array;

    public function findDemandesPourPdv(PointVente $pointVente, string $statut = null): array;

    public function findAll(): array;
}
