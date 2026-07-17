<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la création d'un utilisateur via le formulaire.
 * Mappage intermédiaire entre les données POST et l'entité Utilisateur.
 */
final class UtilisateurCreateDTO
{
    #[Assert\NotBlank(message: 'Le prénom est requis.')]
    #[Assert\Length(min: 2, max: 100)]
    public string $prenomUt = '';

    #[Assert\NotBlank(message: 'Le nom est requis.')]
    #[Assert\Length(min: 2, max: 100)]
    public string $nomUt = '';

    #[Assert\NotBlank(message: 'L\'email est requis.')]
    #[Assert\Email(message: 'L\'email n\'est pas valide.')]
    public string $email = '';

    #[Assert\NotBlank(message: 'Le téléphone est requis.')]
    #[Assert\Regex(
        pattern: '/^\+?[0-9\s.\-()]{8,20}$/',
        message: 'Le numéro de téléphone n\'est pas valide.'
    )]
    public string $telephone = '';



    public string $motDePasse = '';

    public array $rolesEntites = [];
}

