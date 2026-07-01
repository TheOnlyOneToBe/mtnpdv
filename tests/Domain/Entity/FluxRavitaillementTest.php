<?php

declare(strict_types=1);

namespace App\Tests\Domain\Entity;

use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Entity\Produit;
use App\Domain\Enum\StatutFlux;
use App\Domain\ValueObject\Montant;
use PHPUnit\Framework\TestCase;

final class FluxRavitaillementTest extends TestCase
{
    private function creerProduit(string $prix = '500.00'): Produit
    {
        return new Produit('Carte SIM', 'TELECOM', Montant::fromString($prix));
    }

    public function testMontantTotalRecalculeALAjoutDeLignes(): void
    {
        $flux = new FluxRavitaillement('FACT-001');

        self::assertTrue($flux->getMontantTotal()->equals(Montant::zero()));

        $flux->ajouterLigne($this->creerProduit(), 10);            // 5 000.00
        $flux->ajouterLigne($this->creerProduit('250.50'), 2);     //   501.00

        self::assertSame('5501.00', $flux->getMontantTotal()->toDecimal());
    }

    public function testPrixUnitaireFigeAuMomentDuFlux(): void
    {
        $flux = new FluxRavitaillement('FACT-002');
        $produit = $this->creerProduit('500.00');

        $ligne = $flux->ajouterLigne($produit, 5, Montant::fromString('450.00'));

        self::assertSame('450.00', $ligne->getPrixUnitaireFlux()->toDecimal());
        self::assertSame('2250.00', $ligne->getSousTotal()->toDecimal());
        // Le prix catalogue du produit n'a pas bougé
        self::assertSame('500.00', $produit->getPrixUnitaire()->toDecimal());
    }

    public function testRetraitDeLigneRecalculeLeTotal(): void
    {
        $flux = new FluxRavitaillement('FACT-003');
        $ligne = $flux->ajouterLigne($this->creerProduit(), 10);
        $flux->ajouterLigne($this->creerProduit('100.00'), 1);

        $flux->retirerLigne($ligne);

        self::assertSame('100.00', $flux->getMontantTotal()->toDecimal());
        self::assertCount(1, $flux->getLignes());
    }

    public function testChangementDeQuantiteRecalculeLeTotal(): void
    {
        $flux = new FluxRavitaillement('FACT-004');
        $ligne = $flux->ajouterLigne($this->creerProduit('500.00'), 10);

        $ligne->changerQuantite(3);

        self::assertSame('1500.00', $flux->getMontantTotal()->toDecimal());
    }

    public function testCycleDeVieDuStatut(): void
    {
        $flux = new FluxRavitaillement('FACT-005');

        $flux->changerStatut(StatutFlux::VALIDE);
        $flux->changerStatut(StatutFlux::EXPEDIE);
        $flux->changerStatut(StatutFlux::LIVRE);

        self::assertSame(StatutFlux::LIVRE, $flux->getStatutFlux());
    }

    public function testTransitionInterditeRejetee(): void
    {
        $flux = new FluxRavitaillement('FACT-006');

        $this->expectException(\DomainException::class);

        // EN_ATTENTE -> LIVRE sans passer par VALIDE/EXPEDIE
        $flux->changerStatut(StatutFlux::LIVRE);
    }
}
