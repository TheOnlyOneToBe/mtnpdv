<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Domain\Entity\FluxProduit;
use App\Domain\Entity\FluxRavitaillement;
use App\Domain\Enum\StatutFlux;
use App\Domain\Repository\FluxProduitRepositoryInterface;
use App\Domain\Repository\FluxRavitaillementRepositoryInterface;
use App\Tests\Integration\DoctrineTestCase;

final class FluxRavitaillementRepositoryTest extends DoctrineTestCase
{
    private FluxRavitaillementRepositoryInterface $repo;
    private FluxProduitRepositoryInterface $repoLignes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = self::getContainer()->get(FluxRavitaillementRepositoryInterface::class);
        $this->repoLignes = self::getContainer()->get(FluxProduitRepositoryInterface::class);
    }

    private function livrerFlux(FluxRavitaillement $flux): void
    {
        $flux->changerStatut(StatutFlux::VALIDE);
        $flux->changerStatut(StatutFlux::EXPEDIE);
        $flux->changerStatut(StatutFlux::LIVRE);
    }

    public function testMontantTotalLivreIgnoreLesFluxNonLivres(): void
    {
        $pdv = $this->creerPointVente('PDV-FLUX');
        $produit = $this->creerProduit('Recharge', '1000.00');

        $livre = new FluxRavitaillement('FACT-OK');
        $livre->setPointVente($pdv);
        $livre->ajouterLigne($produit, 10); // 10 000.00
        $this->livrerFlux($livre);

        $enAttente = new FluxRavitaillement('FACT-KO');
        $enAttente->setPointVente($pdv);
        $enAttente->ajouterLigne($produit, 99);

        $this->em->persist($livre);
        $this->em->persist($enAttente);
        $this->em->flush();

        self::assertSame('10000.00', $this->repo->montantTotalLivre($pdv)->toDecimal());
    }

    public function testCascadeDesLignesEtOrphanRemoval(): void
    {
        $produit = $this->creerProduit();

        $flux = new FluxRavitaillement('FACT-CASCADE');
        $flux->ajouterLigne($produit, 3);
        $ligneRetiree = $flux->ajouterLigne($produit, 7);

        $this->em->persist($flux); // cascade: persist sur les lignes
        $this->em->flush();

        self::assertCount(2, $this->repoLignes->findByFlux($flux));

        // orphanRemoval : retirer la ligne de la collection la supprime en base
        $flux->retirerLigne($ligneRetiree);
        $this->em->flush();
        $this->em->clear();

        $lignes = $this->em->getRepository(FluxProduit::class)->findAll();
        self::assertCount(1, $lignes);
        self::assertSame(3, $lignes[0]->getQuantite());
    }

    public function testQuantitesTotalesParProduit(): void
    {
        $sim = $this->creerProduit('Carte SIM');
        $recharge = $this->creerProduit('Recharge', '1000.00');

        $flux = new FluxRavitaillement('FACT-QTES');
        $flux->ajouterLigne($sim, 4);
        $flux->ajouterLigne($recharge, 25);
        $this->em->persist($flux);
        $this->em->flush();

        $quantites = $this->repoLignes->quantitesTotalesParProduit();

        self::assertCount(2, $quantites);
        // Triées par quantité décroissante
        self::assertSame('Recharge', $quantites[0]['nomProd']);
        self::assertSame(25, $quantites[0]['quantiteTotale']);
        self::assertSame(4, $quantites[1]['quantiteTotale']);
    }

    public function testFindOneByFacture(): void
    {
        $flux = new FluxRavitaillement('FACT-UNIQUE');
        $this->em->persist($flux);
        $this->em->flush();

        self::assertNotNull($this->repo->findOneByFacture('FACT-UNIQUE'));
        self::assertNull($this->repo->findOneByFacture('FACT-INCONNUE'));
    }
}
