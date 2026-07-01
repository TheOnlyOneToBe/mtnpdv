<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Role;
use App\Domain\Entity\Utilisateur;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use App\Infrastructure\Security\Voter\PointVenteVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class PointVenteVoterTest extends TestCase
{
    private static function creerUtilisateur(string $email, string ...$roles): Utilisateur
    {
        $utilisateur = new Utilisateur(
            'Test',
            'Test',
            Email::fromString($email),
            'hash',
            Telephone::fromString('+237690000000'),
        );

        foreach ($roles as $code) {
            $utilisateur->addRole(new Role($code, $code));
        }

        return $utilisateur;
    }

    private static function creerPointVente(?Utilisateur $gerant = null): PointVente
    {
        $pdv = new PointVente('Kiosque', 'PDV-V', new Coordonnees(4.05, 9.76), 'Douala', Telephone::fromString('+237691111111'));
        $pdv->setGerant($gerant);

        return $pdv;
    }

    private function voter(Utilisateur $utilisateur, string $attribut, ?PointVente $sujet): int
    {
        $token = new UsernamePasswordToken($utilisateur, 'main', $utilisateur->getRoles());

        return (new PointVenteVoter())->vote($token, $sujet, [$attribut]);
    }

    #[DataProvider('provideMatrice')]
    public function testMatriceDesPermissions(string $role, string $attribut, int $attendu): void
    {
        $utilisateur = self::creerUtilisateur('u@exemple.com', $role);

        self::assertSame($attendu, $this->voter($utilisateur, $attribut, self::creerPointVente()));
    }

    /** @return iterable<array{string, string, int}> */
    public static function provideMatrice(): iterable
    {
        yield 'admin voit' => ['ADMIN', PointVenteVoter::VOIR, VoterInterface::ACCESS_GRANTED];
        yield 'admin modifie' => ['ADMIN', PointVenteVoter::MODIFIER, VoterInterface::ACCESS_GRANTED];
        yield 'admin supprime' => ['ADMIN', PointVenteVoter::SUPPRIMER, VoterInterface::ACCESS_GRANTED];
        yield 'admin crée' => ['ADMIN', PointVenteVoter::CREER, VoterInterface::ACCESS_GRANTED];
        yield 'agent voit' => ['AGENT', PointVenteVoter::VOIR, VoterInterface::ACCESS_GRANTED];
        yield 'agent crée' => ['AGENT', PointVenteVoter::CREER, VoterInterface::ACCESS_GRANTED];
        yield 'agent ne modifie pas' => ['AGENT', PointVenteVoter::MODIFIER, VoterInterface::ACCESS_DENIED];
        yield 'agent ne supprime pas' => ['AGENT', PointVenteVoter::SUPPRIMER, VoterInterface::ACCESS_DENIED];
        yield 'gérant ne crée pas' => ['GERANT', PointVenteVoter::CREER, VoterInterface::ACCESS_DENIED];
        yield 'gérant ne modifie pas' => ['GERANT', PointVenteVoter::MODIFIER, VoterInterface::ACCESS_DENIED];
        yield 'gérant ne voit pas un kiosque étranger' => ['GERANT', PointVenteVoter::VOIR, VoterInterface::ACCESS_DENIED];
    }

    public function testGerantVoitUniquementSonKiosque(): void
    {
        $gerant = self::creerUtilisateur('gerant@exemple.com', 'GERANT');

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($gerant, PointVenteVoter::VOIR, self::creerPointVente($gerant)),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($gerant, PointVenteVoter::VOIR, self::creerPointVente()),
        );
    }

    public function testCreationSansSujetAutoriseePourAgent(): void
    {
        $agent = self::creerUtilisateur('agent@exemple.com', 'AGENT');

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter($agent, PointVenteVoter::CREER, null));
    }

    public function testAttributInconnuAbstention(): void
    {
        $admin = self::creerUtilisateur('admin@exemple.com', 'ADMIN');

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $this->voter($admin, 'AUTRE_CHOSE', self::creerPointVente()));
    }
}
