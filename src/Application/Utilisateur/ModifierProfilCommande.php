<?php

declare(strict_types=1);

namespace App\Application\Utilisateur;

use App\Domain\Entity\Utilisateur;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class ModifierProfilCommande
{
    public function __construct(
        public Utilisateur $utilisateur,
        public ?UploadedFile $photo = null,
        public ?string $telephone = null,
    ) {
    }
}
