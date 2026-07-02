<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/reports', name: 'app_reports_')]
class ReportsController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly ProduitRepositoryInterface $produits,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        try {
            $allTransactions = $this->transactions->findAll();
            $allVisites = array_filter($allTransactions, fn($t) => $t->getType()->name === 'VISITE');
            $allVentes = array_filter($allTransactions, fn($t) => $t->getType()->name === 'VENTE');

            $chiffresData = $this->getChiffresAffairesData();
            $visiteStatusData = $this->getVisitesStatusData();
            $topProduits = $this->getTopProduits();
            $agentActivity = $this->getAgentActivityData();

            return $this->render('reports/dashboard.html.twig', [
                'chiffres' => $chiffresData,
                'visitesStatus' => $visiteStatusData,
                'topProduits' => $topProduits,
                'agentActivity' => $agentActivity,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/api/chiffres-affaires', name: 'api_chiffres')]
    public function apiChiffres(): JsonResponse
    {
        return new JsonResponse($this->getChiffresAffairesData());
    }

    #[Route('/api/visites-status', name: 'api_visites_status')]
    public function apiVisitesStatus(): JsonResponse
    {
        return new JsonResponse($this->getVisitesStatusData());
    }

    #[Route('/api/top-produits', name: 'api_top_produits')]
    public function apiTopProduits(): JsonResponse
    {
        return new JsonResponse($this->getTopProduits());
    }

    #[Route('/api/agent-activity', name: 'api_agent_activity')]
    public function apiAgentActivity(): JsonResponse
    {
        return new JsonResponse($this->getAgentActivityData());
    }

    private function getChiffresAffairesData(): array
    {
        $transactions = $this->transactions->findAll();
        $ventes = array_filter(
            $transactions,
            fn($t) => $t->getType()->name === 'VENTE' && $t->getStatut()->name === 'VALIDEE'
        );

        // Grouper par semaine
        $data = [];
        foreach ($ventes as $vente) {
            $week = $vente->getDateTransac()->format('Y-W');
            if (!isset($data[$week])) {
                $data[$week] = 0;
            }
            $data[$week] += $vente->getMontant()->centimes();
        }

        ksort($data);
        $data = array_slice($data, -12, 12);

        return [
            'labels' => array_map(fn($w) => 'Sem. ' . substr($w, -2), array_keys($data)),
            'values' => array_map(fn($v) => round($v / 100, 0), array_values($data)),
        ];
    }

    private function getVisitesStatusData(): array
    {
        $transactions = $this->transactions->findAll();
        $visites = array_filter($transactions, fn($t) => $t->getType()->name === 'VISITE');

        $statuts = [
            'EN_ATTENTE' => 0,
            'VALIDEE' => 0,
            'REJETEE' => 0,
            'ANNULEE' => 0,
        ];

        foreach ($visites as $visite) {
            $statuts[$visite->getStatut()->name]++;
        }

        $labels = [];
        $values = [];
        foreach ($statuts as $status => $count) {
            if ($count > 0) {
                $labels[] = ucfirst(strtolower($status));
                $values[] = $count;
            }
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }

    private function getTopProduits(): array
    {
        $transactions = $this->transactions->findAll();
        $ventes = array_filter(
            $transactions,
            fn($t) => $t->getType()->name === 'VENTE' && $t->getStatut()->name === 'VALIDEE'
        );

        $produitCounts = [];
        foreach ($ventes as $vente) {
            // Note: Transaction n'a pas de produit, c'est un exemple
            // En production, il faudrait une relation Transaction->Produit
        }

        $topProduits = array_slice($produitCounts, 0, 10, true);

        return [
            'labels' => array_keys($topProduits),
            'values' => array_values($topProduits),
        ];
    }

    private function getAgentActivityData(): array
    {
        $agents = $this->utilisateurs->findByRole('AGENT');
        $allTransactions = $this->transactions->findAll();

        $labels = [];
        $values = [];

        foreach ($agents as $agent) {
            $agentVisites = array_filter(
                $allTransactions,
                fn($t) => $t->getUtilisateur()?->getId() === $agent->getId()
            );
            if (count($agentVisites) > 0) {
                $labels[] = $agent->getNomComplet();
                $values[] = count($agentVisites);
            }
        }

        return [
            'labels' => $labels,
            'values' => $values,
        ];
    }
}
