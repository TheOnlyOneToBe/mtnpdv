<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Role;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use App\Infrastructure\Security\Voter\TransactionVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class TransactionVoterTest extends TestCase
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

    private static function creerTransaction(?Utilisateur $agent = null, ?Utilisateur $gerantDuPdv = null): Transaction
    {
        $transaction = new Transaction(TypeTransaction::VISITE, Montant::zero(), new Coordonnees(4.05, 9.76));
        $transaction->setUtilisateur($agent);

        $pdv = new PointVente('Kiosque', 'PDV-T', new Coordonnees(4.05, 9.76), 'Douala', Telephone::fromString('+237691111111'));
        $pdv->setGerant($gerantDuPdv);
        $transaction->setPointVente($pdv);

        return $transaction;
    }

    private function voter(Utilisateur $utilisateur, string $attribut, ?Transaction $sujet): int
    {
        $token = new UsernamePasswordToken($utilisateur, 'main', $utilisateur->getRoles());

        return (new TransactionVoter())->vote($token, $sujet, [$attribut]);
    }

    public function testAdminVoitToutEtValide(): void
    {
        $admin = self::creerUtilisateur('admin@exemple.com', 'ADMIN');
        $transaction = self::creerTransaction();

        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter($admin, TransactionVoter::VOIR, $transaction));
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter($admin, TransactionVoter::VALIDER, $transaction));
        // La matrice interdit à l'admin d'enregistrer une visite terrain
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter($admin, TransactionVoter::CREER, null));
    }

    public function testAgentVoitUniquementSesVisites(): void
    {
        $agent = self::creerUtilisateur('agent@exemple.com', 'AGENT');
        $autreAgent = self::creerUtilisateur('autre@exemple.com', 'AGENT');

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($agent, TransactionVoter::VOIR, self::creerTransaction(agent: $agent)),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($agent, TransactionVoter::VOIR, self::creerTransaction(agent: $autreAgent)),
        );
        self::assertSame(VoterInterface::ACCESS_GRANTED, $this->voter($agent, TransactionVoter::CREER, null));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($agent, TransactionVoter::VALIDER, self::creerTransaction(agent: $agent)),
        );
    }

    public function testGerantVoitUniquementLesVisitesDeSonKiosque(): void
    {
        $gerant = self::creerUtilisateur('gerant@exemple.com', 'GERANT');

        self::assertSame(
            VoterInterface::ACCESS_GRANTED,
            $this->voter($gerant, TransactionVoter::VOIR, self::creerTransaction(gerantDuPdv: $gerant)),
        );
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($gerant, TransactionVoter::VOIR, self::creerTransaction()),
        );
        self::assertSame(VoterInterface::ACCESS_DENIED, $this->voter($gerant, TransactionVoter::CREER, null));
        self::assertSame(
            VoterInterface::ACCESS_DENIED,
            $this->voter($gerant, TransactionVoter::VALIDER, self::creerTransaction(gerantDuPdv: $gerant)),
        );
    }
}
