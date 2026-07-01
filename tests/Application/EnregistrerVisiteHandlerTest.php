<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Visite\EnregistrerVisiteCommande;
use App\Application\Visite\EnregistrerVisiteHandler;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use PHPUnit\Framework\TestCase;

final class EnregistrerVisiteHandlerTest extends TestCase
{
    private const RAYON_TOLERANCE = 100; // mètres

    /** Position du point de vente de référence. */
    private const PDV_LAT = 4.05110000;
    private const PDV_LNG = 9.76790000;

    private function creerHandler(TransactionRepositoryInterface $repo): EnregistrerVisiteHandler
    {
        return new EnregistrerVisiteHandler($repo, self::RAYON_TOLERANCE);
    }

    private function creerPointVente(): PointVente
    {
        return new PointVente(
            'Kiosque Akwa',
            'PDV-001',
            new Coordonnees(self::PDV_LAT, self::PDV_LNG),
            'Douala',
            Telephone::fromString('+237690123456'),
        );
    }

    private function creerAgent(): Utilisateur
    {
        return new Utilisateur(
            'Mbarga',
            'Paul',
            Email::fromString('paul.mbarga@exemple.com'),
            'hash',
            Telephone::fromString('+237691111111'),
        );
    }

    public function testVisiteDansLaZoneDeTolerance(): void
    {
        $repo = $this->createMock(TransactionRepositoryInterface::class);
        $repo->expects(self::once())->method('save')->with(self::isInstanceOf(Transaction::class));

        // ≈ 50 m au nord du PDV (0.00045° de latitude)
        $positionAgent = new Coordonnees(self::PDV_LAT + 0.00045, self::PDV_LNG);

        $resultat = ($this->creerHandler($repo))(new EnregistrerVisiteCommande(
            $this->creerPointVente(),
            $this->creerAgent(),
            TypeTransaction::VISITE,
            $positionAgent,
            Montant::zero(),
            'RAS, kiosque bien tenu',
        ));

        self::assertTrue($resultat->dansLaZone);
        self::assertGreaterThan(40, $resultat->distanceMetres);
        self::assertLessThan(60, $resultat->distanceMetres);
        self::assertSame(StatutTransaction::EN_ATTENTE, $resultat->transaction->getStatut());
        self::assertSame('RAS, kiosque bien tenu', $resultat->transaction->getCommentaireRapport());
    }

    public function testVisiteHorsZoneEnregistreeMaisSignalee(): void
    {
        $repo = $this->createMock(TransactionRepositoryInterface::class);
        // La visite hors zone est quand même persistée : l'admin tranche à la validation
        $repo->expects(self::once())->method('save');

        // ≈ 500 m du PDV
        $positionAgent = new Coordonnees(self::PDV_LAT + 0.0045, self::PDV_LNG);

        $resultat = ($this->creerHandler($repo))(new EnregistrerVisiteCommande(
            $this->creerPointVente(),
            $this->creerAgent(),
            TypeTransaction::VISITE,
            $positionAgent,
            Montant::zero(),
        ));

        self::assertFalse($resultat->dansLaZone);
        self::assertGreaterThan(450, $resultat->distanceMetres);
        self::assertLessThan(550, $resultat->distanceMetres);
    }

    public function testLaTransactionPorteToutesLesDonneesDeLaCommande(): void
    {
        $repo = $this->createStub(TransactionRepositoryInterface::class);
        $pdv = $this->creerPointVente();
        $agent = $this->creerAgent();

        $resultat = ($this->creerHandler($repo))(new EnregistrerVisiteCommande(
            $pdv,
            $agent,
            TypeTransaction::VENTE,
            new Coordonnees(self::PDV_LAT, self::PDV_LNG),
            Montant::fromString('15000.00'),
            photoPreuveUrl: '/uploads/preuves/abc.jpg',
        ));

        $transaction = $resultat->transaction;

        self::assertSame($pdv, $transaction->getPointVente());
        self::assertSame($agent, $transaction->getUtilisateur());
        self::assertSame(TypeTransaction::VENTE, $transaction->getType());
        self::assertSame('15000.00', $transaction->getMontant()->toDecimal());
        self::assertSame('/uploads/preuves/abc.jpg', $transaction->getPhotoPreuveUrl());
        self::assertEqualsWithDelta(time(), $transaction->getDateTransac()->getTimestamp(), 2);
    }

    public function testVisiteExactementSurLePointDeVente(): void
    {
        $repo = $this->createStub(TransactionRepositoryInterface::class);

        $resultat = ($this->creerHandler($repo))(new EnregistrerVisiteCommande(
            $this->creerPointVente(),
            $this->creerAgent(),
            TypeTransaction::VISITE,
            new Coordonnees(self::PDV_LAT, self::PDV_LNG),
            Montant::zero(),
        ));

        self::assertTrue($resultat->dansLaZone);
        self::assertSame(0.0, $resultat->distanceMetres);
    }
}
