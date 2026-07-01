<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use App\Tests\Integration\DoctrineTestCase;

final class TransactionRepositoryTest extends DoctrineTestCase
{
    private TransactionRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(TransactionRepositoryInterface::class);
    }

    private function creerTransaction(
        PointVente $pdv,
        TypeTransaction $type,
        string $montant,
        bool $validee = false,
    ): Transaction {
        $transaction = new Transaction($type, Montant::fromString($montant), new Coordonnees(4.05, 9.76));
        $transaction->setPointVente($pdv);

        if ($validee) {
            $transaction->valider();
        }

        $this->em->persist($transaction);

        return $transaction;
    }

    public function testChiffreAffairesNeCompteQueLesVentesValidees(): void
    {
        $pdv = $this->creerPointVente('PDV-CA');
        $autrePdv = $this->creerPointVente('PDV-AUTRE');

        $this->creerTransaction($pdv, TypeTransaction::VENTE, '10000.00', validee: true);
        $this->creerTransaction($pdv, TypeTransaction::VENTE, '5000.50', validee: true);
        $this->creerTransaction($pdv, TypeTransaction::VENTE, '99999.00');                    // en attente
        $this->creerTransaction($pdv, TypeTransaction::RETOUR, '2000.00', validee: true);     // pas une vente
        $this->creerTransaction($pdv, TypeTransaction::VISITE, '0.00', validee: true);        // visite
        $this->creerTransaction($autrePdv, TypeTransaction::VENTE, '7777.00', validee: true); // autre PDV
        $this->em->flush();

        $chiffreAffaires = $this->repo->chiffreAffaires($pdv);

        self::assertSame('15000.50', $chiffreAffaires->toDecimal());
    }

    public function testChiffreAffairesSansVenteEstZero(): void
    {
        $pdv = $this->creerPointVente('PDV-VIDE');
        $this->em->flush();

        self::assertTrue($this->repo->chiffreAffaires($pdv)->equals(Montant::zero()));
    }

    public function testChiffreAffairesBorneDansLeTemps(): void
    {
        $pdv = $this->creerPointVente('PDV-PERIODE');
        $this->creerTransaction($pdv, TypeTransaction::VENTE, '1000.00', validee: true);
        $this->em->flush();

        $hier = new \DateTimeImmutable('-1 day');
        $demain = new \DateTimeImmutable('+1 day');

        self::assertSame('1000.00', $this->repo->chiffreAffaires($pdv, $hier, $demain)->toDecimal());
        self::assertSame('0.00', $this->repo->chiffreAffaires($pdv, fin: $hier)->toDecimal());
        self::assertSame('0.00', $this->repo->chiffreAffaires($pdv, debut: $demain)->toDecimal());
    }

    public function testFindByTypeEtStatut(): void
    {
        $pdv = $this->creerPointVente('PDV-FILTRES');
        $this->creerTransaction($pdv, TypeTransaction::VISITE, '0.00');
        $this->creerTransaction($pdv, TypeTransaction::VENTE, '100.00', validee: true);
        $this->em->flush();

        self::assertCount(1, $this->repo->findByType(TypeTransaction::VISITE));
        self::assertCount(1, $this->repo->findByStatut(\App\Domain\Enum\StatutTransaction::EN_ATTENTE));
        self::assertCount(1, $this->repo->findByStatut(\App\Domain\Enum\StatutTransaction::VALIDEE));
    }
}
