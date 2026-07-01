<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Email;

interface UtilisateurRepositoryInterface
{
    public function find(int $id): ?Utilisateur;

    /** @return list<Utilisateur> */
    public function findAll(): array;

    public function findOneByEmail(Email $email): ?Utilisateur;

    /** @return list<Utilisateur> */
    public function findActifs(): array;

    /**
     * @param string $codeRole code sans le préfixe ROLE_ (ex. "ADMIN")
     *
     * @return list<Utilisateur>
     */
    public function findByRole(string $codeRole): array;

    /**
     * Recherche par nom, prénom ou e-mail (insensible à la casse).
     *
     * @return list<Utilisateur>
     */
    public function rechercher(string $terme): array;

    public function save(Utilisateur $utilisateur, bool $flush = true): void;

    public function remove(Utilisateur $utilisateur, bool $flush = true): void;
}
