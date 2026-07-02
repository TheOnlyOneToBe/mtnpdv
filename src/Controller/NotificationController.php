<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Notification\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notifications', name: 'app_notification_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('/non-lues', name: 'unread', methods: ['GET'])]
    public function nonLues(): JsonResponse
    {
        try {
            $user = $this->getUser();
            $notifications = $this->notificationService->obtenirNonLuesUtilisateur($user, 10);
            $count = $this->notificationService->compterNonLuesUtilisateur($user);

            return new JsonResponse([
                'count' => $count,
                'notifications' => array_map(fn($n) => [
                    'id' => (string) $n->getId(),
                    'titre' => $n->getTitre(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType()->value,
                    'icone' => $n->getType()->getIcone(),
                    'couleur' => $n->getType()->getCouleur(),
                    'lien' => $n->getLien(),
                    'dateCreation' => $n->getDateCreation()->format('Y-m-d H:i:s'),
                ], $notifications),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id<\d+>}/lire', name: 'mark_read', methods: ['POST'])]
    public function marquerCommeLue(Request $request, int $id): JsonResponse
    {
        try {
            $this->notificationService->marquerCommeLue($id);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        try {
            $user = $this->getUser();
            $page = max(1, (int) $request->query->get('page', 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;

            $notifications = $this->notificationService->obtenirNonLuesUtilisateur($user, $limit + $offset);
            $paginatedNotifications = array_slice($notifications, $offset, $limit);

            return $this->render('notification/list.html.twig', [
                'notifications' => $paginatedNotifications,
                'currentPage' => $page,
                'hasMore' => count($notifications) > ($offset + $limit),
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement des notifications: '.$e->getMessage());
            return $this->redirectToRoute('app_accueil');
        }
    }
}
