<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if (null !== $this->getUser()) {
            return $this->redirectToRoute('app_accueil');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): never
    {
        throw new \LogicException('Interceptée par le firewall : cette méthode ne doit jamais être exécutée.');
    }

    #[Route('/', name: 'app_accueil')]
    public function accueil(): Response
    {
        /** @var \App\Domain\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        if ($user->aLeRole('ADMIN')) {
            return $this->redirectToRoute('app_admin_dashboard');
        }

        if ($user->aLeRole('AGENT')) {
            return $this->redirectToRoute('app_agent_dashboard');
        }

        if ($user->aLeRole('GERANT')) {
            return $this->redirectToRoute('app_gerant_dashboard');
        }

        return $this->render('accueil/index.html.twig');
    }

    #[Route('/session/lock', name: 'app_session_lock', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function lockSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        // Store lock information in session
        $session = $request->getSession();
        $session->set('session_locked', true);
        $session->set('lock_time', time());
        $session->set('page_state', $data['pageState'] ?? null);
        $session->set('page_url', $data['pageUrl'] ?? null);

        return new JsonResponse([
            'success' => true,
            'message' => 'Session verrouillée',
        ]);
    }

    #[Route('/session/unlock', name: 'app_session_unlock', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function unlockSession(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $password = $data['password'] ?? '';

        /** @var \App\Domain\Entity\Utilisateur|null $user */
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Utilisateur non authentifié',
            ], 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Mot de passe incorrect',
            ], 401);
        }

        // Clear lock
        $session = $request->getSession();
        $session->remove('session_locked');
        $session->remove('lock_time');
        $session->remove('page_state');
        $session->remove('page_url');

        return new JsonResponse([
            'success' => true,
            'message' => 'Session déverrouillée',
        ]);
    }

    #[Route('/session/activity', name: 'app_session_activity', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function trackActivity(Request $request): JsonResponse
    {
        // Touch the session to prevent timeout
        $session = $request->getSession();
        $session->set('last_activity', time());

        return new JsonResponse(['success' => true]);
    }
}
