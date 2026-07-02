<?php

declare(strict_types=1);

namespace App\Tests\Application;

use App\Application\Gerant\EnregistrerVenteCommande;
use App\Application\Gerant\EnregistrerVenteHandler;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Tests\Integration\DoctrineTestCase;

final class GerantEnregistrerVenteHandlerTest extends DoctrineTestCase
{
    private EnregistrerVenteHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $pointVenteRepository = $this->getService(PointVenteRepositoryInterface::class);
        $transactionRepository = $this->getService(TransactionRepositoryInterface::class);

        $this->handler = new EnregistrerVenteHandler($pointVenteRepository, $transactionRepository);
    }

    public function test_vente_enregistree_est_validee_directement(): void
    {
        $gerant = $this->creerUtilisateur('gerant@mtnpdv.local', 'GERANT');
        $pointVente = $this->creerPointVente('Kiosque Test', $gerant);

        $commande = new EnregistrerVenteCommande(
            pointVenteId: $pointVente->getId(),
            produitId: 1,
            quantite: 5,
            montantCentimes: 50000, // 500 €
            latitude: 3.848,
            longitude: 11.5021,
            commentaire: 'Vente testée',
        );

        $vente = $this->handler->handle($commande);

        // Vérifier que la vente est VALIDEE immédiatement (pas EN_ATTENTE)
        $this->assertSame(StatutTransaction::VALIDEE, $vente->getStatut());
        $this->assertSame($pointVente->getId(), $vente->getPointVente()->getId());
        $this->assertSame('Vente testée', $vente->getCommentaireRapport());
    }

    public function test_point_vente_introuvable_leve_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Point de vente #9999 introuvable');

        $commande = new EnregistrerVenteCommande(
            pointVenteId: 9999,
            produitId: 1,
            quantite: 1,
            montantCentimes: 10000,
            latitude: 0.0,
            longitude: 0.0,
        );

        $this->handler->handle($commande);
    }

    public function test_vente_sans_commentaire(): void
    {
        $gerant = $this->creerUtilisateur('gerant2@mtnpdv.local', 'GERANT');
        $pointVente = $this->creerPointVente('Kiosque Sans Commentaire', $gerant);

        $commande = new EnregistrerVenteCommande(
            pointVenteId: $pointVente->getId(),
            produitId: 1,
            quantite: 3,
            montantCentimes: 30000,
            latitude: 3.848,
            longitude: 11.5021,
            commentaire: null,
        );

        $vente = $this->handler->handle($commande);

        $this->assertNull($vente->getCommentaireRapport());
        $this->assertSame(StatutTransaction::VALIDEE, $vente->getStatut());
    }

    public function test_montant_est_bien_enregistre(): void
    {
        $gerant = $this->creerUtilisateur('gerant3@mtnpdv.local', 'GERANT');
        $pointVente = $this->creerPointVente('Kiosque Montant', $gerant);

        $montantCentimes = 123456; // 1234.56 €

        $commande = new EnregistrerVenteCommande(
            pointVenteId: $pointVente->getId(),
            produitId: 1,
            quantite: 10,
            montantCentimes: $montantCentimes,
            latitude: 3.848,
            longitude: 11.5021,
        );

        $vente = $this->handler->handle($commande);

        $this->assertSame($montantCentimes, $vente->getMontant()->montantCentimes());
    }

    public function test_coordonnees_gps_sont_enregistrees(): void
    {
        $gerant = $this->creerUtilisateur('gerant4@mtnpdv.local', 'GERANT');
        $pointVente = $this->creerPointVente('Kiosque GPS', $gerant);

        $latitude = 3.8480000;
        $longitude = 11.5021000;

        $commande = new EnregistrerVenteCommande(
            pointVenteId: $pointVente->getId(),
            produitId: 1,
            quantite: 1,
            montantCentimes: 10000,
            latitude: $latitude,
            longitude: $longitude,
        );

        $vente = $this->handler->handle($commande);

        $coordonnees = $vente->getCoordonneesCapture();
        $this->assertEqualsWithDelta($latitude, $coordonnees->latitude(), 0.00001);
        $this->assertEqualsWithDelta($longitude, $coordonnees->longitude(), 0.00001);
    }
}
