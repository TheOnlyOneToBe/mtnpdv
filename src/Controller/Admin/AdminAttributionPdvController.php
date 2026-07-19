<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Service\AttributionPdvService;
use App\Domain\Entity\AttributionPdv;
use App\Domain\Repository\AttributionPdvRepositoryInterface;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/attributions', name: 'app_admin_attribution_')]
class AdminAttributionPdvController extends AbstractController
{
    public function __construct(
        private readonly AttributionPdvRepositoryInterface $attributions,
        private readonly AttributionPdvService $service,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $terme = (string) $request->query->get('q', '');
        $actif = $request->query->get('actif');

        $items = $this->attributions->search($terme, null !== $actif && $actif !== '' ? (bool) $actif : null);

        $pagination = $this->paginationService->paginate($items, $page);
        $pageMetadata = $this->paginationService->getPageMetadata($pagination);
        $itemRange = $this->paginationService->getItemRange($pagination);
        $agents = $this->service->listerAgentsDisponibles();
        $pointsDeVente = $this->service->listerPointsDeVenteDisponibles();

        return $this->render('admin/attribution_pdv/list.html.twig', [
            'attributions' => $pagination['items'],
            'pagination' => $pagination,
            'pageMetadata' => $pageMetadata,
            'itemRange' => $itemRange,
            'agents' => $agents,
            'pointsDeVente' => $pointsDeVente,
            'q' => $terme,
            'actifSelectionne' => $actif !== '' ? (bool) $actif : null,
        ]);
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        $agents = $this->service->listerAgentsDisponibles();
        $pointsDeVente = $this->service->listerPointsDeVenteDisponibles();

        if ($request->isMethod('POST')) {
            try {
                $agentId = (int) $request->request->get('agent_id');
                $pdvId = (int) $request->request->get('point_vente_id');

                $agent = $this->service->findAgent($agentId);
                $pointVente = $this->service->findPointVente($pdvId);

                $this->service->attribuer($agent, $pointVente);
                $this->addFlash('success', 'Attribution créée avec succès.');

                return $this->redirectToRoute('app_admin_attribution_list');
            } catch (\Throwable $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/attribution_pdv/form.html.twig', [
            'mode' => 'create',
            'agents' => $agents,
            'pointsDeVente' => $pointsDeVente,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(AttributionPdv $attribution): Response
    {
        return $this->render('admin/attribution_pdv/show.html.twig', [
            'attribution' => $attribution,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(AttributionPdv $attribution, Request $request): Response
    {
        $agents = $this->service->listerAgentsDisponibles();
        $pointsDeVente = $this->service->listerPointsDeVenteDisponibles();

        if ($request->isMethod('POST')) {
            try {
                $agentId = (int) $request->request->get('agent_id');
                $pdvId = (int) $request->request->get('point_vente_id');

                $agent = $this->service->findAgent($agentId);
                $pointVente = $this->service->findPointVente($pdvId);

                $this->service->attribuer($agent, $pointVente);
                $this->addFlash('success', 'Attribution modifiée avec succès.');

                return $this->redirectToRoute('app_admin_attribution_show', ['id' => $attribution->getId()]);
            } catch (\Throwable $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render('admin/attribution_pdv/form.html.twig', [
            'mode' => 'edit',
            'attribution' => $attribution,
            'agents' => $agents,
            'pointsDeVente' => $pointsDeVente,
        ]);
    }

    #[Route('/{id}/revoke', name: 'revoke', requirements: ['id' => '\d+'])]
    public function revoke(AttributionPdv $attribution, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('revoke-attribution-'.$attribution->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->service->revoquer($attribution);
            $this->addFlash('success', 'Attribution révoquée.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_attribution_list');
    }

    #[Route('/{id}/restore', name: 'restore', requirements: ['id' => '\d+'])]
    public function restore(AttributionPdv $attribution, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('restore-attribution-'.$attribution->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $this->service->retablir($attribution);
            $this->addFlash('success', 'Attribution rétablie.');
        } catch (\Throwable $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('app_admin_attribution_list');
    }
}
