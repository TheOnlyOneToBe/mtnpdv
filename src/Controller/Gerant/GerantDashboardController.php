<?php

declare(strict_types=1);

namespace App\Controller\Gerant;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_GERANT')]
#[Route('/gerant/dashboard', name: 'app_gerant_dashboard')]
class GerantDashboardController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly ProduitRepositoryInterface $produits,
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $user = $this->getUser();

            // Récupérer le kiosque du gérant
            $pointVente = $this->pointVentes->findByGerant($user);

            if (!$pointVente) {
                $this->addFlash('warning', 'Vous n\'êtes pas assigné à un point de vente.');
                return $this->render('gerant/dashboard.html.twig', [
                    'pointVente' => null,
                    'produits' => [],
                    'transactions' => [],
                    'statistiques' => [
                        'totalProduits' => 0,
                        'totalTransactions' => 0,
                        'chiffreAffaires' => 0,
                    ],
                ]);
            }

            // Récupérer les produits livrés au kiosque
            $produitsLivres = $this->produits->findLivresAuPointVente($pointVente);

            // Récupérer les transactions du kiosque
            $transactions = $this->transactions->findByPointVente($pointVente);

            // Calculer les statistiques
            $totalTransactions = count($transactions);
            $chiffreAffaires = 0;
            $transactionsValidees = array_filter($transactions, fn($t) => $t->getStatut()->name === 'VALIDEE' && $t->getType()->value === 'VENTE');

            foreach ($transactionsValidees as $transaction) {
                if ($transaction->getMontant()) {
                    $chiffreAffaires += $transaction->getMontant()->montantCentimes;
                }
            }

            return $this->render('gerant/dashboard.html.twig', [
                'pointVente' => $pointVente,
                'produits' => $produitsLivres,
                'transactions' => array_slice($transactions, 0, 10),
                'statistiques' => [
                    'totalProduits' => count($produitsLivres),
                    'totalTransactions' => $totalTransactions,
                    'chiffreAffaires' => $chiffreAffaires / 100,
                    'transactionsValidees' => count($transactionsValidees),
                    'transactionsEnAttente' => count(array_filter($transactions, fn($t) => $t->getStatut()->name === 'EN_ATTENTE')),
                ],
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du tableau de bord: '.$e->getMessage());
            return $this->render('gerant/dashboard.html.twig', [
                'pointVente' => null,
                'produits' => [],
                'transactions' => [],
                'statistiques' => [
                    'totalProduits' => 0,
                    'totalTransactions' => 0,
                    'chiffreAffaires' => 0,
                ],
            ]);
        }
    }
}
