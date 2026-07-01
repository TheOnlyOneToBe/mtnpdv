<?php

declare(strict_types=1);

namespace App\Infrastructure\Upload;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stocke les photos de preuve des visites sous public/uploads/preuves
 * et retourne l'URL relative à enregistrer dans transaction.photo_preuve_url.
 */
final class PhotoPreuveUploader
{
    private const EXTENSIONS_AUTORISEES = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return string URL relative de la photo, ex. "/uploads/preuves/ab12….jpg"
     *
     * @throws FileException si l'extension n'est pas autorisée ou si le déplacement échoue
     */
    public function upload(UploadedFile $photo): string
    {
        $extension = strtolower($photo->guessExtension() ?? $photo->getClientOriginalExtension());

        if (!\in_array($extension, self::EXTENSIONS_AUTORISEES, true)) {
            throw new FileException(sprintf(
                'Type de fichier non autorisé (%s). Formats acceptés : %s.',
                $extension,
                implode(', ', self::EXTENSIONS_AUTORISEES),
            ));
        }

        $nomFichier = bin2hex(random_bytes(16)).'.'.$extension;
        $photo->move($this->projectDir.'/public/uploads/preuves', $nomFichier);

        return '/uploads/preuves/'.$nomFichier;
    }
}
