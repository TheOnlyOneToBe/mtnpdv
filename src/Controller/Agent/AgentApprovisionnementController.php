<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Repository\TransactionRepositoryInterface;
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

            $allApprovisionnements = $this->transactions->findApprovisionnementsForAgent($user);

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
