<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Utilisateur\ChangerMotDePasseHandler;
use App\Application\Utilisateur\MotDePasseInvalideException;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangerMotDePasseHandlerTest extends TestCase
{
    private function creerUtilisateur(): Utilisateur
    {
        return new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean@exemple.com'),
            'ancien-hash',
            Telephone::fromString('+237690123456'),
        );
    }

    public function testChangementReussiAvecAncienMotDePasseCorrect(): void
    {
        $utilisateur = $this->creerUtilisateur();

        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('isPasswordValid')->with($utilisateur, 'ancien-secret')->willReturn(true);
        $hasher->expects(self::once())->method('hashPassword')->with($utilisateur, 'nouveau-secret')->willReturn('nouveau-hash');

        $repo = $this->createMock(UtilisateurRepositoryInterface::class);
        $repo->expects(self::once())->method('save')->with($utilisateur);

        (new ChangerMotDePasseHandler($repo, $hasher))($utilisateur, 'ancien-secret', 'nouveau-secret');

        self::assertSame('nouveau-hash', $utilisateur->getMotPass());
    }

    public function testAncienMotDePasseIncorrectRejeteSansPersister(): void
    {
        $utilisateur = $this->creerUtilisateur();

        $hasher = $this->createStub(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(false);

        $repo = $this->createMock(UtilisateurRepositoryInterface::class);
        $repo->expects(self::never())->method('save');

        $this->expectException(MotDePasseInvalideException::class);

        (new ChangerMotDePasseHandler($repo, $hasher))($utilisateur, 'mauvais', 'nouveau-secret');
    }
}
