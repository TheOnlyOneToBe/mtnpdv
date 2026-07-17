<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Repository\DemandeVisiteRepositoryInterface;
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
        private readonly DemandeVisiteRepositoryInterface $demandeVisiteRepository,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $user = $this->getUser();

            // Récupérer tous les PDV pour la carte
            $allPointVentes = $this->pointVentes->findAll();

            // Préparer les données PDV avec statut de seuil
            $pointVentesData = array_map(function ($pdv) {
                return [
                    'id' => $pdv->getId(),
                    'nom' => $pdv->getNomPdv(),
                    'lat' => $pdv->getCoordonnees()->latitude(),
                    'lng' => $pdv->getCoordonnees()->longitude(),
                    'ville' => $pdv->getVille(),
                    'soldeCash' => $pdv->getSoldeCash()->toDecimal(),
                    'soldeFlotte' => $pdv->getSoldeFlotte()->toDecimal(),
                    'seuilMinCash' => $pdv->getSeuilMinCash()->toDecimal(),
                    'seuilMinFlotte' => $pdv->getSeuilMinFlotte()->toDecimal(),
                    'isBelowThreshold' => $pdv->soldeCashEstSousSeuil() || $pdv->soldeFlotteEstSousSeuil(),
                    'cashBelow' => $pdv->soldeCashEstSousSeuil(),
                    'flotteBelow' => $pdv->soldeFlotteEstSousSeuil(),
                ];
            }, $allPointVentes);

            // Filtrer les PDVs en dessous du seuil
            $pdvsBelowThreshold = array_filter($pointVentesData, function ($pdv) {
                return $pdv['isBelowThreshold'];
            });

            // Récupérer les missions assignées à l'agent
            $demandes = $this->demandeVisiteRepository->findPendantesParAgent($user);

            // Récupérer les visites de l'agent (les 10 dernières)
            $agentVisites = $this->transactions->findByUtilisateur($user);
            $recentVisites = array_slice($agentVisites, 0, 10);

            // Statistiques personnelles
            $statistics = [
                'totalVisites' => count($agentVisites),
                'visitesEnAttente' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->value === 'EN_ATTENTE')),
                'visitesValidees' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->value === 'VALIDEE')),
                'visitesRejetees' => count(array_filter($agentVisites, fn($v) => $v->getStatut()->value === 'REJETEE')),
                'missionsAssignees' => count($demandes),
            ];

            return $this->render('agent/dashboard.html.twig', [
                'pointVentes' => $allPointVentes,
                'pointVentesData' => $pointVentesData,
                'pdvsBelowThreshold' => $pdvsBelowThreshold,
                'demandes' => $demandes,
                'recentVisites' => $recentVisites,
                'statistics' => $statistics,
                // L'entité Utilisateur ne porte pas de coordonnées : la position
                // est obtenue côté client via la géolocalisation du navigateur.
                'userCoordinates' => null,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du tableau de bord: '.$e->getMessage());
            return $this->render('agent/dashboard.html.twig', [
                'pointVentes' => [],
                'pointVentesData' => [],
                'pdvsBelowThreshold' => [],
                'demandes' => [],
                'recentVisites' => [],
                'statistics' => [
                    'totalVisites' => 0,
                    'visitesEnAttente' => 0,
                    'visitesValidees' => 0,
                    'visitesRejetees' => 0,
                    'missionsAssignees' => 0,
                ],
                'userCoordinates' => null,
            ]);
        }
    }
}
