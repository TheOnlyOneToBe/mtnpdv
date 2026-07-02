<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Notification\NotificationService;
use App\Application\Visite\ValiderVisiteHandler;
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
        private readonly ValiderVisiteHandler $validerVisiteHandler,
        private readonly NotificationService $notificationService,
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

    #[Route('/validations/{id}/valider', name: 'validation_approve', methods: ['POST'])]
    public function valider(Request $request): Response
    {
        try {
            $id = $request->attributes->get('id');
            $transaction = $this->transactions->findById($id);

            if (!$transaction) {
                $this->addFlash('danger', 'Visite non trouvée.');
                return $this->redirectToRoute('app_admin_validations');
            }

            $this->validerVisiteHandler->valider($transaction);

            // Notifier l'agent
            $this->notificationService->notifierVisiteValidee(
                $transaction->getAgent(),
                $transaction->getPointVente()->getNomPdv(),
                $this->generateUrl('app_agent_visite_show', ['id' => $transaction->getId()])
            );

            $this->addFlash('success', 'Visite validée avec succès.');

            if ($request->getPreferredFormat() === 'turbo_stream') {
                return $this->render('admin/validations/turbo/approve.stream.twig', [
                    'transaction' => $transaction,
                ]);
            }

            return $this->redirectToRoute('app_admin_validations', ['tab' => 'en_attente']);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de la validation: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_validations');
        }
    }

    #[Route('/validations/{id}/rejeter', name: 'validation_reject', methods: ['POST'])]
    public function rejeter(Request $request): Response
    {
        try {
            $id = $request->attributes->get('id');
            $transaction = $this->transactions->findById($id);

            if (!$transaction) {
                $this->addFlash('danger', 'Visite non trouvée.');
                return $this->redirectToRoute('app_admin_validations');
            }

            $this->validerVisiteHandler->rejeter($transaction);

            // Récupérer la raison du rejet si fournie
            $raison = $request->request->get('reason', '');

            // Notifier l'agent
            $this->notificationService->notifierVisiteRejetee(
                $transaction->getAgent(),
                $transaction->getPointVente()->getNomPdv(),
                $raison,
                $this->generateUrl('app_agent_visite_show', ['id' => $transaction->getId()])
            );

            $this->addFlash('warning', 'Visite rejetée.');

            if ($request->getPreferredFormat() === 'turbo_stream') {
                return $this->render('admin/validations/turbo/reject.stream.twig', [
                    'transaction' => $transaction,
                ]);
            }

            return $this->redirectToRoute('app_admin_validations', ['tab' => 'en_attente']);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du rejet: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_validations');
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
