<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Enum\StatutPointVente;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Tests\Integration\DoctrineTestCase;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

final class PointVenteRepositoryTest extends DoctrineTestCase
{
    private PointVenteRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(PointVenteRepositoryInterface::class);
    }

    public function testRechercherParNomVilleOuCodeRef(): void
    {
        $this->creerPointVente('PDV-DLA');
        $this->em->flush();

        self::assertCount(1, $this->repo->rechercher('kiosque'));
        self::assertCount(1, $this->repo->rechercher('DOUALA'));
        self::assertCount(1, $this->repo->rechercher('pdv-dla'));
        self::assertCount(0, $this->repo->rechercher('yaounde'));
    }

    public function testCompterParStatut(): void
    {
        $this->creerPointVente('PDV-1');
        $this->creerPointVente('PDV-2');
        $this->creerPointVente('PDV-3')->setStatutActuel(StatutPointVente::FERME);
        $this->em->flush();

        $compte = $this->repo->compterParStatut();

        self::assertSame(2, $compte['ACTIF']);
        self::assertSame(1, $compte['FERME']);
    }

    public function testFindByGerant(): void
    {
        $gerant = $this->creerUtilisateur('gerant@exemple.com', 'GERANT');
        $this->creerPointVente('PDV-G1', $gerant);
        $this->creerPointVente('PDV-SANS');
        $this->em->flush();

        $kiosques = $this->repo->findByGerant($gerant);

        self::assertCount(1, $kiosques);
        self::assertSame('PDV-G1', $kiosques[0]->getCodeRef());
    }

    public function testRoundtripDesCoordonneesEmbarquees(): void
    {
        $this->creerPointVente('PDV-GPS');
        $this->em->flush();
        $this->em->clear();

        $pdv = $this->repo->findOneByCodeRef('PDV-GPS');

        self::assertNotNull($pdv);
        self::assertEqualsWithDelta(4.0511, $pdv->getCoordonnees()->latitude(), 1e-6);
        self::assertEqualsWithDelta(9.7679, $pdv->getCoordonnees()->longitude(), 1e-6);
    }

    public function testFindProchesNecessiteMysql(): void
    {
        $platform = $this->em->getConnection()->getDatabasePlatform();

        if (!$platform instanceof AbstractMySQLPlatform) {
            self::markTestSkipped('findProches() utilise des fonctions SQL natives MySQL (ACOS, RADIANS).');
        }

        $this->creerPointVente('PDV-PROCHE');
        $this->em->flush();

        $proches = $this->repo->findProches(new Coordonnees(4.0511, 9.7679), 1.0);

        self::assertCount(1, $proches);
    }
}
