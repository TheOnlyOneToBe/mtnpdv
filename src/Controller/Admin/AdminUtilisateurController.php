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
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('admin/utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        $utilisateur = new Utilisateur(
            email: new Email(''),
            prenomUt: '',
            nomUt: '',
            telephone: new Telephone(''),
            motDePasse: '',
        );

        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'is_edit' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $plainPassword = $form->get('motDePasse')->getData();
                $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
                $utilisateur->setMotDePasse($hashedPassword);

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
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Utilisateur $utilisateur, Request $request): Response
    {
        $form = $this->createForm(UtilisateurType::class, $utilisateur, [
            'is_edit' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($plainPassword = $form->get('motDePasse')->getData()) {
                    $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $plainPassword);
                    $utilisateur->setMotDePasse($hashedPassword);
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
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Utilisateur $utilisateur, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-user-'.$utilisateur->getId(), $request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $this->utilisateurs->remove($utilisateur);

        $this->addFlash('success', 'Utilisateur supprimé avec succès.');

        return $this->redirectToRoute('app_admin_utilisateur_list');
    }
}
