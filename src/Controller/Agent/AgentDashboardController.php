<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\AttributionPdvRepositoryInterface;
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
        private readonly AttributionPdvRepositoryInterface $attributionPdvRepository,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();

            // Récupérer les PDV attribués à l'agent pour la carte
            $agentAttributions = $this->attributionPdvRepository->findAttivesByAgent($user);
            $allPointVentes = array_map(function ($attribution) {
                return $attribution->getPointVente();
            }, $agentAttributions);

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
            $attributedPdvIds = array_map(static fn ($attribution) => $attribution->getPointVente()->getId(), $agentAttributions);
            $demandes = array_values(array_filter($demandes, static fn ($demande) => in_array($demande->getPointVente()->getId(), $attributedPdvIds, true)));

            // Récupérer les visites de l'agent (les 10 dernières)
            $agentVisites = $this->transactions->findByUtilisateur($user);
            $recentVisites = array_slice($agentVisites, 0, 10);

            // Récupérer les approvisionnements de l'agent
            $agentApprovisionnements = $this->transactions->findApprovisionnementsForAgent($user);
            $pendingApprovisionnementsCount = count($this->transactions->findPendingApprovisionnementsForAgent($user));
            $recentApprovisionnements = array_slice($agentApprovisionnements, 0, 10);

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
                'recentApprovisionnements' => $recentApprovisionnements,
                'pendingApprovisionnementsCount' => $pendingApprovisionnementsCount,
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
                'recentApprovisionnements' => [],
                'pendingApprovisionnementsCount' => 0,
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
