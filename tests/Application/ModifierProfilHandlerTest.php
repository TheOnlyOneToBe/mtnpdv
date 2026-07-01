<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Utilisateur\ModifierProfilCommande;
use App\Application\Utilisateur\ModifierProfilHandler;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ModifierProfilHandlerTest extends TestCase
{
    private ModifierProfilHandler $handler;
    private UtilisateurRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->handler = new ModifierProfilHandler($this->repository);
    }

    public function testModifierTelephoneSeul(): void
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean@exemple.com'),
            'hash',
            Telephone::fromString('+237690123456'),
        );

        $commande = new ModifierProfilCommande(
            $utilisateur,
            photo: null,
            telephone: '+237690987654',
        );

        $this->repository->expects(self::once())
            ->method('save')
            ->with($utilisateur);

        ($this->handler)($commande);

        self::assertSame('+237690987654', $utilisateur->getTelephone()->value());
    }

    public function testModifierSansMiseAJour(): void
    {
        $utilisateur = new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean@exemple.com'),
            'hash',
            Telephone::fromString('+237690123456'),
        );

        $commande = new ModifierProfilCommande($utilisateur);

        $this->repository->expects(self::once())
            ->method('save')
            ->with($utilisateur);

        ($this->handler)($commande);

        self::assertNull($utilisateur->getPhotoFile());
        self::assertNull($utilisateur->getPhotoProfilUrl());
    }
}
