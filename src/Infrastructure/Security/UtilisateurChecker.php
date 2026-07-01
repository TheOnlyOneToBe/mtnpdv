<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Refuse l'authentification des comptes désactivés (statut = INACTIF).
 */
final class UtilisateurChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Utilisateur) {
            return;
        }

        if (!$user->estActif()) {
            throw new CustomUserMessageAccountStatusException('Ce compte a été désactivé. Contactez un administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
    }
}
