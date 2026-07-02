<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\PointVente;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Form\PointVenteType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/pdv', name: 'app_admin_pdv_')]
class AdminPointVenteController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(): Response
    {
        $pointVentes = $this->pointVentes->findAll();

        return $this->render('admin/pdv/list.html.twig', [
            'pointVentes' => $pointVentes,
        ]);
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        $pointVente = new PointVente(
            nomPdv: '',
            codeRef: '',
            coordonnees: \App\Domain\ValueObject\Coordonnees::fromArray([0, 0]),
            ville: '',
            telephone: new \App\Domain\ValueObject\Telephone(''),
        );

        $form = $this->createForm(PointVenteType::class, $pointVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->pointVentes->save($pointVente);

                if ($request->getPreferredFormat() === 'turbo_stream') {
                    return $this->render('admin/pdv/turbo/create.stream.twig', [
                        'pointVente' => $pointVente,
                    ]);
                }

                $this->addFlash('success', 'Point de vente créé avec succès.');
                return $this->redirectToRoute('app_admin_pdv_show', ['id' => $pointVente->getId()]);
            } catch (\Exception $e) {
                if ($request->getPreferredFormat() === 'turbo_stream') {
                    return $this->render('admin/pdv/turbo/error.stream.twig', [
                        'message' => $e->getMessage(),
                    ]);
                }

                $this->addFlash('danger', 'Erreur lors de la création: '.$e->getMessage());
            }
        }

        if ($request->getPreferredFormat() === 'turbo_stream') {
            return $this->render('admin/pdv/turbo/form.stream.twig', [
                'form' => $form,
                'mode' => 'create',
            ]);
        }

        return $this->render('admin/pdv/form.html.twig', [
            'form' => $form,
            'mode' => 'create',
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(PointVente $pointVente): Response
    {
        return $this->render('admin/pdv/show.html.twig', [
            'pointVente' => $pointVente,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(PointVente $pointVente, Request $request): Response
    {
        $form = $this->createForm(PointVenteType::class, $pointVente);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->pointVentes->save($pointVente);

                if ($request->getPreferredFormat() === 'turbo_stream') {
                    return $this->render('admin/pdv/turbo/update.stream.twig', [
                        'pointVente' => $pointVente,
                    ]);
                }

                $this->addFlash('success', 'Point de vente modifié avec succès.');
                return $this->redirectToRoute('app_admin_pdv_show', ['id' => $pointVente->getId()]);
            } catch (\Exception $e) {
                if ($request->getPreferredFormat() === 'turbo_stream') {
                    return $this->render('admin/pdv/turbo/error.stream.twig', [
                        'message' => $e->getMessage(),
                    ]);
                }

                $this->addFlash('danger', 'Erreur lors de la modification: '.$e->getMessage());
            }
        }

        if ($request->getPreferredFormat() === 'turbo_stream') {
            return $this->render('admin/pdv/turbo/form.stream.twig', [
                'form' => $form,
                'pointVente' => $pointVente,
                'mode' => 'edit',
            ]);
        }

        return $this->render('admin/pdv/form.html.twig', [
            'form' => $form,
            'pointVente' => $pointVente,
            'mode' => 'edit',
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(PointVente $pointVente, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete-pdv-'.$pointVente->getId(), $request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $pdvId = $pointVente->getId();
            $this->pointVentes->remove($pointVente);

            if ($request->getPreferredFormat() === 'turbo_stream') {
                return $this->render('admin/pdv/turbo/delete.stream.twig', [
                    'pdvId' => $pdvId,
                ]);
            }

            $this->addFlash('success', 'Point de vente supprimé avec succès.');
        } catch (\Exception $e) {
            if ($request->getPreferredFormat() === 'turbo_stream') {
                return $this->render('admin/pdv/turbo/error.stream.twig', [
                    'message' => $e->getMessage(),
                ]);
            }

            $this->addFlash('danger', 'Erreur lors de la suppression: '.$e->getMessage());
        }

        return $this->redirectToRoute('app_admin_pdv_list');
    }
}
