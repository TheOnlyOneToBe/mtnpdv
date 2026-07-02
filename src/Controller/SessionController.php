<?php

declare(strict_types=1);

namespace App\Controller;

use App\Application\Session\SessionLockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/session', name: 'app_session_')]
class SessionController extends AbstractController
{
    public function __construct(
        private readonly SessionLockService $sessionLockService,
    ) {
    }

    #[Route('/activity', name: 'activity', methods: ['POST'])]
    public function trackActivity(SessionInterface $session): JsonResponse
    {
        $this->sessionLockService->updateLastActivity($session);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/lock', name: 'lock', methods: ['POST'])]
    public function lockSession(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $pageUrl = $data['pageUrl'] ?? null;
            $pageState = $data['pageState'] ?? null;

            $this->sessionLockService->lockSession($session, $pageUrl, $pageState);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/unlock', name: 'unlock', methods: ['POST'])]
    public function unlockSession(Request $request, SessionInterface $session): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $data = json_decode($request->getContent(), true);
            $password = $data['password'] ?? '';

            if (!$password) {
                return new JsonResponse(['error' => 'Password required'], Response::HTTP_BAD_REQUEST);
            }

            $success = $this->sessionLockService->unlockSession($session, $user, $password);

            if (!$success) {
                return new JsonResponse(['error' => 'Incorrect password'], Response::HTTP_UNAUTHORIZED);
            }

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/state', name: 'state', methods: ['GET'])]
    public function getSessionState(SessionInterface $session): JsonResponse
    {
        $state = $this->sessionLockService->getSessionLockState($session);

        return new JsonResponse($state);
    }
}
