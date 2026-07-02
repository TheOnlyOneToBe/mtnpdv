<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Notification;
use App\Domain\Entity\Utilisateur;

interface NotificationRepositoryInterface
{
    public function save(Notification $notification): void;

    public function findById(int $id): ?Notification;

    public function findNonLuesParUtilisateur(Utilisateur $utilisateur, int $limit = 10): array;

    public function findParUtilisateur(Utilisateur $utilisateur, int $limit = 50): array;

    public function compterNonLues(Utilisateur $utilisateur): int;

    public function marquerCommeLue(Notification $notification): void;

    public function marquerToutesCommeLues(Utilisateur $utilisateur): void;

    public function supprimer(Notification $notification): void;
}
