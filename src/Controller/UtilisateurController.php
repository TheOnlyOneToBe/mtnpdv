<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UtilisateurController extends AbstractController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('/profil', name: 'app_profil_show')]
    public function show(): Response
    {
        $user = $this->getUser();

        return $this->render('utilisateur/profil/show.html.twig', [
            'utilisateur' => $user,
        ]);
    }

    #[Route('/profil/edit', name: 'app_profil_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $prenom = $request->request->get('prenom');
            $nom = $request->request->get('nom');
            $telephone = $request->request->get('telephone');

            if ($prenom) {
                $user->setPrenomUt($prenom);
            }
            if ($nom) {
                $user->setNomUt($nom);
            }
            if ($telephone) {
                $user->setTelephone($telephone);
            }

            // Get entity manager and persist
            $em = $this->getUser() ? $this->container->get('doctrine.orm.entity_manager') : null;
            if ($em) {
                $em->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès !');
                return $this->redirectToRoute('app_profil_show');
            }
        }

        return $this->render('utilisateur/profil/edit.html.twig', [
            'utilisateur' => $user,
        ]);
    }

    #[Route('/profil/change-password', name: 'app_profil_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $user = $this->getUser();
            $currentPassword = $request->request->get('current_password');
            $newPassword = $request->request->get('new_password');
            $confirmPassword = $request->request->get('confirm_password');

            // Verify current password
            if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
                return $this->redirectToRoute('app_profil_change_password');
            }

            // Check password confirmation
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_profil_change_password');
            }

            // Check password length
            if (strlen($newPassword) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_profil_change_password');
            }

            // Hash and update password
            $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
            $user->setPassword($hashedPassword);

            $em = $this->container->get('doctrine.orm.entity_manager');
            $em->flush();

            $this->addFlash('success', 'Mot de passe changé avec succès !');
            return $this->redirectToRoute('app_profil_show');
        }

        return $this->render('utilisateur/profil/change_password.html.twig');
    }
}
