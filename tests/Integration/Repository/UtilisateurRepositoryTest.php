<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use App\Tests\Integration\DoctrineTestCase;

final class UtilisateurRepositoryTest extends DoctrineTestCase
{
    private UtilisateurRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(UtilisateurRepositoryInterface::class);
    }

    public function testRoundtripDesValueObjects(): void
    {
        $this->creerUtilisateur('marie.ngo@exemple.com');
        $this->em->flush();
        $this->em->clear();

        $recharge = $this->repo->findOneByEmail(Email::fromString('MARIE.NGO@exemple.com'));

        self::assertInstanceOf(Utilisateur::class, $recharge);
        // Les colonnes VARCHAR sont reconstruites en value objects par les types DBAL
        self::assertInstanceOf(Email::class, $recharge->getEmail());
        self::assertSame('marie.ngo@exemple.com', $recharge->getEmail()->value());
        self::assertInstanceOf(Telephone::class, $recharge->getTelephone());
        self::assertSame('+237690123456', $recharge->getTelephone()->value());
    }

    public function testFindByRole(): void
    {
        $this->creerUtilisateur('admin@exemple.com', 'ADMIN');
        $this->creerUtilisateur('agent1@exemple.com', 'AGENT');
        $this->creerUtilisateur('agent2@exemple.com', 'AGENT');
        $this->em->flush();

        $agents = $this->repo->findByRole('agent');

        self::assertCount(2, $agents);
        self::assertEmpty($this->repo->findByRole('GERANT'));
    }

    public function testRechercherParNomPrenomOuEmail(): void
    {
        $this->creerUtilisateur('paul.mbarga@exemple.com');
        $this->em->flush();

        self::assertCount(1, $this->repo->rechercher('DUPONT'));
        self::assertCount(1, $this->repo->rechercher('jean'));
        self::assertCount(1, $this->repo->rechercher('mbarga@'));
        self::assertCount(0, $this->repo->rechercher('inexistant'));
    }

    public function testFindActifsExclutLesDesactives(): void
    {
        $this->creerUtilisateur('actif@exemple.com');
        $this->creerUtilisateur('inactif@exemple.com')->desactiver();
        $this->em->flush();

        $actifs = $this->repo->findActifs();

        self::assertCount(1, $actifs);
        self::assertSame('actif@exemple.com', $actifs[0]->getUserIdentifier());
    }
}
