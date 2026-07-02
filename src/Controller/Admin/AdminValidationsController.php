<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Repository\TransactionRepositoryInterface;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin', name: 'app_admin_')]
class AdminValidationsController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('/validations', name: 'validations')]
    public function validations(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $tab = $request->query->get('tab', 'en_attente');

            $visitesEnAttente = $this->transactions->findByStatut('EN_ATTENTE');
            $visitesValidees = $this->transactions->findByStatut('VALIDEE');
            $visitesRejetees = $this->transactions->findByStatut('REJETEE');

            $paginationEnAttente = $this->paginationService->paginate($visitesEnAttente, $tab === 'en_attente' ? $page : 1);
            $paginationValidees = $this->paginationService->paginate($visitesValidees, $tab === 'validees' ? $page : 1);
            $paginationRejetees = $this->paginationService->paginate($visitesRejetees, $tab === 'rejetees' ? $page : 1);

            return $this->render('admin/validations.html.twig', [
                'visitesEnAttente' => $paginationEnAttente['items'],
                'visitesValidees' => $paginationValidees['items'],
                'visitesRejetees' => $paginationRejetees['items'],
                'paginationEnAttente' => $paginationEnAttente,
                'paginationValidees' => $paginationValidees,
                'paginationRejetees' => $paginationRejetees,
                'nbEnAttente' => $paginationEnAttente['totalItems'],
                'nbValidees' => $paginationValidees['totalItems'],
                'nbRejetees' => $paginationRejetees['totalItems'],
                'activeTab' => $tab,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement des validations: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/map', name: 'map')]
    public function map(): Response
    {
        try {
            return $this->render('admin/map.html.twig');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de la carte: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }
}
