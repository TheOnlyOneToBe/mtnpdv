<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Enum\StatutFlux;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\ValueObject\Montant;
use App\Tests\Integration\DoctrineTestCase;

final class ProduitRepositoryTest extends DoctrineTestCase
{
    private ProduitRepositoryInterface $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(ProduitRepositoryInterface::class);
    }

    public function testFindLivresAuPointVenteNeCompteQueLesFluxLivres(): void
    {
        $pdv = $this->creerPointVente('PDV-A');
        $autrePdv = $this->creerPointVente('PDV-B');
        $sim = $this->creerProduit('Carte SIM');
        $recharge = $this->creerProduit('Recharge 1000', '1000.00');

        // Deux flux livrés au PDV-A
        $fluxLivre1 = new FluxRavitaillement('FACT-L1');
        $fluxLivre1->setPointVente($pdv);
        $fluxLivre1->ajouterLigne($sim, 10);
        $fluxLivre1->changerStatut(StatutFlux::VALIDE);
        $fluxLivre1->changerStatut(StatutFlux::EXPEDIE);
        $fluxLivre1->changerStatut(StatutFlux::LIVRE);

        $fluxLivre2 = new FluxRavitaillement('FACT-L2');
        $fluxLivre2->setPointVente($pdv);
        $fluxLivre2->ajouterLigne($sim, 5);
        $fluxLivre2->ajouterLigne($recharge, 20);
        $fluxLivre2->changerStatut(StatutFlux::VALIDE);
        $fluxLivre2->changerStatut(StatutFlux::EXPEDIE);
        $fluxLivre2->changerStatut(StatutFlux::LIVRE);

        // Un flux encore en attente : ne doit pas compter
        $fluxEnAttente = new FluxRavitaillement('FACT-EA');
        $fluxEnAttente->setPointVente($pdv);
        $fluxEnAttente->ajouterLigne($sim, 100);

        // Un flux livré à un autre PDV : ne doit pas compter
        $fluxAutre = new FluxRavitaillement('FACT-AUTRE');
        $fluxAutre->setPointVente($autrePdv);
        $fluxAutre->ajouterLigne($sim, 50);
        $fluxAutre->changerStatut(StatutFlux::VALIDE);
        $fluxAutre->changerStatut(StatutFlux::EXPEDIE);
        $fluxAutre->changerStatut(StatutFlux::LIVRE);

        foreach ([$fluxLivre1, $fluxLivre2, $fluxEnAttente, $fluxAutre] as $flux) {
            $this->em->persist($flux);
        }
        $this->em->flush();

        $resultat = $this->repo->findLivresAuPointVente($pdv);

        self::assertCount(2, $resultat);
        // Trié par nom : Carte SIM puis Recharge 1000
        self::assertSame('Carte SIM', $resultat[0]['produit']->getNomProd());
        self::assertSame(15, $resultat[0]['quantiteLivree']);
        self::assertSame('Recharge 1000', $resultat[1]['produit']->getNomProd());
        self::assertSame(20, $resultat[1]['quantiteLivree']);
    }

    public function testFindDansFourchettePrix(): void
    {
        $this->creerProduit('Pas cher', '100.00');
        $this->creerProduit('Moyen', '500.00');
        $this->creerProduit('Cher', '5000.00');
        $this->em->flush();

        $resultat = $this->repo->findDansFourchettePrix(
            Montant::fromString('200.00'),
            Montant::fromString('1000.00'),
        );

        self::assertCount(1, $resultat);
        self::assertSame('Moyen', $resultat[0]->getNomProd());
    }

    public function testRoundtripDuPrixEnMontant(): void
    {
        $this->creerProduit('Recharge', '1250.50');
        $this->em->flush();
        $this->em->clear();

        $produits = $this->repo->rechercherParNom('recharge');

        self::assertCount(1, $produits);
        self::assertInstanceOf(Montant::class, $produits[0]->getPrixUnitaire());
        self::assertSame('1250.50', $produits[0]->getPrixUnitaire()->toDecimal());
    }
}
