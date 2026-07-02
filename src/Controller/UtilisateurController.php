<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\ValueObject\Telephone;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
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
        try {
            $user = $this->getUser();

            return $this->render('utilisateur/profil/show.html.twig', [
                'utilisateur' => $user,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du profil: '.$e->getMessage());
            return $this->redirectToRoute('app_accueil');
        }
    }

    #[Route('/profil/edit', name: 'app_profil_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $em): Response
    {
        try {
            $user = $this->getUser();

            $form = $this->createForm(UtilisateurType::class, $user, [
                'is_edit' => true,
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $photoFile = $form->get('photoFile')->getData();
                    if ($photoFile) {
                        $user->setPhotoFile($photoFile);
                    }

                    $telephoneStr = $form->get('telephone')->getData();
                    if ($telephoneStr) {
                        $user->setTelephone(Telephone::fromString($telephoneStr));
                    }

                    $em->flush();
                    $this->addFlash('success', 'Profil mis à jour avec succès !');
                    return $this->redirectToRoute('app_profil_show');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la mise à jour: '.$e->getMessage());
                }
            }

            return $this->render('utilisateur/profil/edit.html.twig', [
                'utilisateur' => $user,
                'form' => $form,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du profil: '.$e->getMessage());
            return $this->redirectToRoute('app_profil_show');
        }
    }

    #[Route('/profil/change-password', name: 'app_profil_change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, EntityManagerInterface $em): Response
    {
        try {
            if ($request->isMethod('POST')) {
                try {
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

                    $em->flush();

                    $this->addFlash('success', 'Mot de passe changé avec succès !');
                    return $this->redirectToRoute('app_profil_show');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors du changement de mot de passe: '.$e->getMessage());
                }
            }

            return $this->render('utilisateur/profil/change_password.html.twig');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de la page: '.$e->getMessage());
            return $this->redirectToRoute('app_profil_show');
        }
    }
}
