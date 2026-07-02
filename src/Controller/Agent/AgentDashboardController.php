<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
#[Route('/agent/dashboard', name: 'app_agent_dashboard')]
class AgentDashboardController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $user = $this->getUser();

            // Récupérer tous les PDV pour la carte
            $allPointVentes = $this->pointVentes->findAll();

            // Récupérer les visites de l'agent (les 10 dernières)
            $agentVisites = $this->transactions->findByUtilisateur($user);
            $recentVisites = array_slice($agentVisites, 0, 10);

            // Statistiques personnelles
            $statistics = [
                'totalVisites' => count($agentVisites),
                'visitesEnAttente' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->name === 'EN_ATTENTE')),
                'visitesValidees' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->name === 'VALIDEE')),
                'visitesRejetees' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->name === 'REJETEE')),
            ];

            return $this->render('agent/dashboard.html.twig', [
                'pointVentes' => $allPointVentes,
                'recentVisites' => $recentVisites,
                'statistics' => $statistics,
                'userCoordinates' => $user->getCoordonnees() ? [
                    'lat' => $user->getCoordonnees()->getLatitude(),
                    'lng' => $user->getCoordonnees()->getLongitude(),
                ] : null,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du tableau de bord: '.$e->getMessage());
            return $this->render('agent/dashboard.html.twig', [
                'pointVentes' => [],
                'recentVisites' => [],
                'statistics' => [
                    'totalVisites' => 0,
                    'visitesEnAttente' => 0,
                    'visitesValidees' => 0,
                    'visitesRejetees' => 0,
                ],
                'userCoordinates' => null,
            ]);
        }
    }
}
