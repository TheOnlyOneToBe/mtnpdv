<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Notification\NotificationService;
use App\Domain\Entity\Notification;
use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\TransactionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/notifications', name: 'app_notification_')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/non-lues', name: 'unread', methods: ['GET'])]
    public function nonLues(): JsonResponse
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();

            return new JsonResponse($this->buildNotificationPayload($user));
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/stream', name: 'stream', methods: ['GET'])]
    public function streamNotifications(): StreamedResponse
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $userId = $user->getId();
        $isAgent = $user->aLeRole('AGENT');

        $response = new StreamedResponse(function () use ($userId, $isAgent): void {
            $lastFingerprint = null;
            $iterations = 0;
            $maxIterations = 120; // ~6 minutes puis reconnexion client

            while ($iterations < $maxIterations) {
                if (connection_aborted()) {
                    break;
                }

                /** @var Utilisateur|null $user */
                $user = $this->entityManager->find(Utilisateur::class, $userId);
                if (!$user) {
                    break;
                }

                $payload = $this->buildNotificationPayload($user, $isAgent);
                $fingerprint = md5(json_encode([
                    'count' => $payload['count'],
                    'ids' => array_column($payload['notifications'], 'id'),
                    'pending' => $payload['pendingApprovisionnements'] ?? 0,
                ]));

                if ($fingerprint !== $lastFingerprint) {
                    echo 'data: '.json_encode($payload, JSON_THROW_ON_ERROR)."\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                    $lastFingerprint = $fingerprint;
                } else {
                    echo ": keepalive\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }

                $this->entityManager->clear();
                ++$iterations;
                sleep(3);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    #[Route('/refresh', name: 'refresh', methods: ['GET'])]
    public function refresh(Request $request): Response
    {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        $isAgent = $user->aLeRole('AGENT');

        $notifications = $this->notificationService->obtenirNonLuesUtilisateur($user, 10);
        $count = $this->notificationService->compterNonLuesUtilisateur($user);

        $data = [
            'notifications' => $notifications,
            'count' => $count,
        ];

        if ($isAgent) {
            $data['pendingApprovisionnements'] = count(
                $this->transactionRepository->findPendingApprovisionnementsForAgent($user),
            );
        }

        if ($request->getPreferredFormat() === 'turbo_stream') {
            return $this->render('notification/turbo/refresh.stream.twig', $data);
        }

        return new JsonResponse($this->buildNotificationPayload($user, $isAgent));
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
            /** @var Utilisateur $user */
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

    /**
     * @return array{count: int, notifications: list<array<string, mixed>>, pendingApprovisionnements?: int}
     */
    private function buildNotificationPayload(Utilisateur $user, ?bool $isAgent = null): array
    {
        $isAgent ??= $user->aLeRole('AGENT');

        $notifications = $this->notificationService->obtenirNonLuesUtilisateur($user, 10);
        $count = $this->notificationService->compterNonLuesUtilisateur($user);

        $payload = [
            'count' => $count,
            'notifications' => array_map(
                static fn (Notification $n) => [
                    'id' => (string) $n->getId(),
                    'titre' => $n->getTitre(),
                    'message' => $n->getMessage(),
                    'type' => $n->getType()->value,
                    'icone' => $n->getType()->getIcone(),
                    'couleur' => $n->getType()->getCouleur(),
                    'lien' => $n->getLien(),
                    'dateCreation' => $n->getDateCreation()->format('Y-m-d H:i:s'),
                ],
                $notifications,
            ),
        ];

        if ($isAgent) {
            $payload['pendingApprovisionnements'] = count(
                $this->transactionRepository->findPendingApprovisionnementsForAgent($user),
            );
        }

        return $payload;
    }
}
