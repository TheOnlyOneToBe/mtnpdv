<?php

declare(strict_types=1);

namespace App\Application\Utilisateur;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Changement de mot de passe par l'utilisateur lui-même :
 * l'ancien mot de passe doit être correct.
 */
final class ChangerMotDePasseHandler
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly UserPasswordHasherInterface $hasher,
    ) {
    }

    /**
     * @throws MotDePasseInvalideException si l'ancien mot de passe ne correspond pas
     */
    public function __invoke(Utilisateur $utilisateur, string $ancienMotDePasse, string $nouveauMotDePasse): void
    {
        if (!$this->hasher->isPasswordValid($utilisateur, $ancienMotDePasse)) {
            throw new MotDePasseInvalideException('L\'ancien mot de passe est incorrect.');
        }

        $utilisateur->setMotPass($this->hasher->hashPassword($utilisateur, $nouveauMotDePasse));
        $this->utilisateurs->save($utilisateur);
    }
}
