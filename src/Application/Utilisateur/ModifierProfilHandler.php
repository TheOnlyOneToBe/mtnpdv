<?php

declare(strict_types=1);

namespace App\Application\Utilisateur;

use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Telephone;

final class ModifierProfilHandler
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    public function __invoke(ModifierProfilCommande $commande): void
    {
        $utilisateur = $commande->utilisateur;

        if (null !== $commande->photo) {
            $utilisateur->setPhotoFile($commande->photo);
        }

        if (null !== $commande->telephone) {
            $utilisateur->setTelephone(Telephone::fromString($commande->telephone));
        }

        $this->utilisateurs->save($utilisateur);
    }
}
