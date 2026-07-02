<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\Utilisateur;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\Telephone;
use App\Form\UtilisateurType;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/utilisateurs', name: 'app_admin_utilisateur_')]
class AdminUtilisateurController extends AbstractController
{
    public function __construct(
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $allUtilisateurs = $this->utilisateurs->findAll();

            $pagination = $this->paginationService->paginate($allUtilisateurs, $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);

            return $this->render('admin/utilisateur/list.html.twig', [
                'utilisateurs' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de la liste: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Utilisateur $utilisateur): Response
    {
        try {
            return $this->render('admin/utilisateur/show.html.twig', [
                'utilisateur' => $utilisateur,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de l\'utilisateur: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_utilisateur_list');
        }
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        try {
            // Les value objects (Email, Telephone) refusent les valeurs vides :
            // l'entité est construite à partir des données du formulaire une fois validées.
            $form = $this->createForm(UtilisateurType::class, null, [
                'is_edit' => false,
                'data_class' => null,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $form->getData();

                    $utilisateur = new Utilisateur(
                        nomUt: $data['nomUt'],
                        prenomUt: $data['prenomUt'],
                        email: new Email((string) $form->get('email')->getData()),
                        motPassHache: '',
                        telephone: new Telephone((string) $form->get('telephone')->getData()),
                    );

                    $plainPassword = $form->get('motDePasse')->getData();
                    $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
                    $utilisateur->setPassword($hashedPassword);

                    foreach ($data['rolesEntites'] ?? [] as $role) {
                        $utilisateur->addRole($role);
                    }

                    $this->utilisateurs->save($utilisateur);

                    $this->addFlash('success', 'Utilisateur créé avec succès.');

                    return $this->redirectToRoute('app_admin_utilisateur_show', ['id' => $utilisateur->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la création: '.$e->getMessage());
                }
            }

            return $this->render('admin/utilisateur/form.html.twig', [
                'form' => $form,
                'mode' => 'create',
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_utilisateur_list');
        }
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Utilisateur $utilisateur, Request $request): Response
    {
        try {
            $form = $this->createForm(UtilisateurType::class, $utilisateur, [
                'is_edit' => true,
            ]);
            // Pré-remplir les champs non mappés depuis les value objects
            $form->get('email')->setData($utilisateur->getEmail()->value());
            $form->get('telephone')->setData($utilisateur->getTelephone()->value());
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    if ($plainPassword = $form->get('motDePasse')->getData()) {
                        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
                        $utilisateur->setPassword($hashedPassword);
                    }

                    $photoFile = $form->get('photoFile')->getData();
                    if ($photoFile) {
                        $utilisateur->setPhotoFile($photoFile);
                    }

                    $telephoneStr = $form->get('telephone')->getData();
                    if ($telephoneStr) {
                        $utilisateur->setTelephone(Telephone::fromString($telephoneStr));
                    }

                    $this->utilisateurs->save($utilisateur);

                    $this->addFlash('success', 'Utilisateur modifié avec succès.');

                    return $this->redirectToRoute('app_admin_utilisateur_show', ['id' => $utilisateur->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la modification: '.$e->getMessage());
                }
            }

            return $this->render('admin/utilisateur/form.html.twig', [
                'form' => $form,
                'utilisateur' => $utilisateur,
                'mode' => 'edit',
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_utilisateur_list');
        }
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Utilisateur $utilisateur, Request $request): Response
    {
        try {
            if (!$this->isCsrfTokenValid('delete-user-'.$utilisateur->getId(), $request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            try {
                $this->utilisateurs->remove($utilisateur);
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la suppression: '.$e->getMessage());
            }

            return $this->redirectToRoute('app_admin_utilisateur_list');
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur critique: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_utilisateur_list');
        }
    }
}
