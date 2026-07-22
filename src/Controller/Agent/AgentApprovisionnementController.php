<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\AttributionPdvRepositoryInterface;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Montant;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
#[Route('/agent/approvisionnement', name: 'app_agent_approvisionnement_')]
class AgentApprovisionnementController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
        private readonly PaginationService $paginationService,
        private readonly AttributionPdvRepositoryInterface $attributions,
        private readonly PointVenteRepositoryInterface $pointVentes,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $page = max(1, (int) $request->query->get('page', 1));
            $statut = $request->query->get('statut');
            $pdvId = $request->query->get('pdv') ? (int)$request->query->get('pdv') : null;
            $montantMin = $request->query->get('montantMin') ? Montant::fromCentimes((int)$request->query->get('montantMin') * 100) : null;
            $montantMax = $request->query->get('montantMax') ? Montant::fromCentimes((int)$request->query->get('montantMax') * 100) : null;

            // Get PDVs assigned to the agent
            $agentAttributions = $this->attributions->findAttivesByAgent($user);
            $pdvs = array_map(fn($a) => $a->getPointVente(), $agentAttributions);

            $selectedPdv = $pdvId ? $this->pointVentes->find($pdvId) : null;

            // Get all approvisionnements for the agent using findByFiltres
            $allApprovisionnements = $this->transactions->findByFiltres(
                type: TypeTransaction::APPROVISIONNEMENT_FLOTTE,
                pointVente: $selectedPdv,
                agent: $user,
                montantMin: $montantMin,
                montantMax: $montantMax,
            );

            // Filter by status if needed
            if ($statut) {
                $allApprovisionnements = array_filter(
                    $allApprovisionnements,
                    fn ($t) => $t->getStatut()->value === $statut,
                );
            }

            $pagination = $this->paginationService->paginate(array_values($allApprovisionnements), $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);
            $pendingCount = count($this->transactions->findPendingApprovisionnementsForAgent($user));

            return $this->render('agent/approvisionnement/list.html.twig', [
                'approvisionnements' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
                'statut' => $statut,
                'pdvId' => $pdvId,
                'montantMin' => $montantMin?->montantCentimes() / 100,
                'montantMax' => $montantMax?->montantCentimes() / 100,
                'pdvs' => $pdvs,
                'pendingCount' => $pendingCount,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement: '.$e->getMessage());

            return $this->redirectToRoute('app_agent_dashboard');
        }
    }

    #[Route('/pending-count', name: 'pending_count', methods: ['GET'])]
    public function pendingCount(): JsonResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();

        return new JsonResponse([
            'count' => count($this->transactions->findPendingApprovisionnementsForAgent($user)),
        ]);
    }
}
