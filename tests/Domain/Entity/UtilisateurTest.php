<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\Role;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutUtilisateur;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\TestCase;

final class UtilisateurTest extends TestCase
{
    private function creerUtilisateur(): Utilisateur
    {
        return new Utilisateur(
            'Dupont',
            'Jean',
            Email::fromString('jean.dupont@exemple.com'),
            'hash',
            Telephone::fromString('+237690123456'),
        );
    }

    public function testIdentifiantSecurityEstLEmail(): void
    {
        self::assertSame('jean.dupont@exemple.com', $this->creerUtilisateur()->getUserIdentifier());
    }

    public function testGetRolesPrefixeEtGarantitRoleUser(): void
    {
        $utilisateur = $this->creerUtilisateur();

        self::assertSame(['ROLE_USER'], $utilisateur->getRoles());

        $utilisateur->addRole(new Role('ADMIN', 'Administrateur'));
        $utilisateur->addRole(new Role('ROLE_AGENT', 'Agent'));

        self::assertSame(['ROLE_ADMIN', 'ROLE_AGENT', 'ROLE_USER'], $utilisateur->getRoles());
    }

    public function testALeRoleInsensibleAuPrefixeEtALaCasse(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $utilisateur->addRole(new Role('GERANT', 'Gérant'));

        self::assertTrue($utilisateur->aLeRole('gerant'));
        self::assertTrue($utilisateur->aLeRole('ROLE_GERANT'));
        self::assertFalse($utilisateur->aLeRole('ADMIN'));
    }

    public function testAddRoleIdempotent(): void
    {
        $utilisateur = $this->creerUtilisateur();
        $role = new Role('AGENT', 'Agent');

        $utilisateur->addRole($role);
        $utilisateur->addRole($role);

        self::assertCount(1, $utilisateur->getRolesEntites());
    }

    public function testActivationDesactivation(): void
    {
        $utilisateur = $this->creerUtilisateur();

        self::assertTrue($utilisateur->estActif());

        $utilisateur->desactiver();
        self::assertFalse($utilisateur->estActif());
        self::assertSame(StatutUtilisateur::INACTIF, $utilisateur->getStatut());

        $utilisateur->activer();
        self::assertTrue($utilisateur->estActif());
    }

    public function testNomComplet(): void
    {
        self::assertSame('Jean Dupont', $this->creerUtilisateur()->getNomComplet());
    }
}
