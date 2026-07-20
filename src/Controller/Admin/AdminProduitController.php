<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\Produit;
use App\Domain\Enum\StatutProduit;
use App\Domain\Repository\ProduitRepositoryInterface;
use App\Form\ProduitType;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/produits', name: 'app_admin_produit_')]
class AdminProduitController extends AbstractController
{
    public function __construct(
        private readonly ProduitRepositoryInterface $produits,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $terme = (string) $request->query->get('q', '');
            $statut = $request->query->get('statut');

            $allProduits = $this->produits->findAll();
            if ($terme !== '') {
                $allProduits = $this->produits->rechercherParNom($terme);
            }
            if (null !== $statut && $statut !== '') {
                $statutEnum = StatutProduit::tryFrom((int) $statut);
                if (!$statutEnum) {
                    $statutEnum = null;
                }
                if ($statutEnum) {
                    $allProduits = array_filter($allProduits, static fn (Produit $p): bool => $p->getStatutProd() === $statutEnum);
                }
            }

            $pagination = $this->paginationService->paginate($allProduits, $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);

            return $this->render('admin/produit/list.html.twig', [
                'produits' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
                'statuts' => StatutProduit::cases(),
                'q' => $terme,
                'statutSelectionne' => $statut !== '' ? (int) $statut : null,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement de la liste: '.$e->getMessage());

            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        try {
            $produit = new Produit('', '', \App\Domain\ValueObject\Montant::zero());
            $form = $this->createForm(ProduitType::class, $produit);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $form->getData();
                    $prix = $form->get('prixUnitaire')->getData();

                    $produit->setPrixUnitaire(\App\Domain\ValueObject\Montant::fromCentimes((int) ($prix * 100)));

                    $this->produits->save($produit);

                    $this->addFlash('success', 'Produit créé avec succès.');

                    return $this->redirectToRoute('app_admin_produit_list');
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la création: '.$e->getMessage());
                }
            }

            return $this->render('admin/produit/form.html.twig', [
                'form' => $form,
                'mode' => 'create',
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());

            return $this->redirectToRoute('app_admin_produit_list');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Produit $produit): Response
    {
        try {
            return $this->render('admin/produit/show.html.twig', [
                'produit' => $produit,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du produit: '.$e->getMessage());

            return $this->redirectToRoute('app_admin_produit_list');
        }
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(Produit $produit, Request $request): Response
    {
        try {
            $form = $this->createForm(ProduitType::class, $produit);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $prix = $form->get('prixUnitaire')->getData();
                    $produit->setPrixUnitaire(\App\Domain\ValueObject\Montant::fromCentimes((int) ($prix * 100)));
                    $this->produits->save($produit);

                    $this->addFlash('success', 'Produit modifié avec succès.');

                    return $this->redirectToRoute('app_admin_produit_show', ['id' => $produit->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de la modification: '.$e->getMessage());
                }
            }

            return $this->render('admin/produit/form.html.twig', [
                'form' => $form,
                'mode' => 'edit',
                'produit' => $produit,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());

            return $this->redirectToRoute('app_admin_produit_list');
        }
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Produit $produit, Request $request): Response
    {
        try {
            if (!$this->isCsrfTokenValid('delete-produit-'.$produit->getId(), $request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            try {
                $this->produits->remove($produit);
                $this->addFlash('success', 'Produit supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Erreur lors de la suppression: '.$e->getMessage());
            }

            return $this->redirectToRoute('app_admin_produit_list');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur critique: '.$e->getMessage());

            return $this->redirectToRoute('app_admin_produit_list');
        }
    }
}