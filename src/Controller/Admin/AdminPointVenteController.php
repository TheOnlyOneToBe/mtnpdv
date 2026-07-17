<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Domain\Entity\PointVente;
use App\Domain\Enum\VilleCameroon;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use App\Domain\ValueObject\Telephone;
use App\Form\PointVenteType;
use App\Infrastructure\Pagination\PaginationService;
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
        private readonly PaginationService $paginationService,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));
            $allPointVentes = $this->pointVentes->findAll();
            $gerants = $this->utilisateurs->findByRole('GERANT');

            $pagination = $this->paginationService->paginate($allPointVentes, $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);

            return $this->render('admin/pdv/list.html.twig', [
                'pointVentes' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
                'gerants' => $gerants,
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
            // Les value objects (Coordonnees, Telephone) refusent les valeurs vides :
            // l'entité est construite à partir des données du formulaire une fois validées.
            $form = $this->createForm(PointVenteType::class, null, ['data_class' => null]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $form->getData();

                    // Check if PDV with this codeRef already exists
                    $existingPdv = $this->pointVentes->findOneByCodeRef($data['codeRef']);
                    if ($existingPdv) {
                        $this->addFlash('danger', 'Un point de vente avec ce code existe déjà.');
                        return $this->render('admin/pdv/form.html.twig', [
                            'form' => $form,
                            'mode' => 'create',
                        ]);
                    }

                    $seuilMinCash = $form->get('seuilMinCash')->getData();
                    $seuilMinFlotte = $form->get('seuilMinFlotte')->getData();

                    $pointVente = new PointVente(
                        nomPdv: $data['nomPdv'],
                        codeRef: $data['codeRef'],
                        coordonnees: new Coordonnees(
                            (float) $form->get('latitude')->getData(),
                            (float) $form->get('longitude')->getData(),
                        ),
                        ville: $data['ville']->value,
                        telephone: new Telephone((string) $form->get('telephone')->getData()),
                        seuilMinCash: $seuilMinCash !== null ? Montant::fromCentimes((int) ($seuilMinCash * 100)) : Montant::zero(),
                        seuilMinFlotte: $seuilMinFlotte !== null ? Montant::fromCentimes((int) ($seuilMinFlotte * 100)) : Montant::zero(),
                    );

                    if (!empty($data['adresse'])) {
                        $pointVente->setAdresse($data['adresse']);
                    }
                    if (!empty($data['statutActuel'])) {
                        $pointVente->setStatutActuel($data['statutActuel']);
                    }

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
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_pdv_list');
        }
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(PointVente $pointVente): Response
    {
        try {
            return $this->render('admin/pdv/show.html.twig', [
                'pointVente' => $pointVente,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du point de vente: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_pdv_list');
        }
    }

    #[Route('/{id}/edit', name: 'edit', requirements: ['id' => '\d+'])]
    public function edit(PointVente $pointVente, Request $request): Response
    {
        try {
            $form = $this->createForm(PointVenteType::class, $pointVente);
            // Pré-remplir les champs non mappés depuis les value objects
            $form->get('latitude')->setData($pointVente->getCoordonnees()->latitude());
            $form->get('longitude')->setData($pointVente->getCoordonnees()->longitude());
            $form->get('telephone')->setData($pointVente->getTelephone()->value());
            $form->get('seuilMinCash')->setData($pointVente->getSeuilMinCash()->toDecimal());
            $form->get('seuilMinFlotte')->setData($pointVente->getSeuilMinFlotte()->toDecimal());
            
            // Pré-remplir la ville en trouvant la VilleCameroon correspondante
            $currentVille = $pointVente->getVille();
            $matchingVilleEnum = null;
            foreach (VilleCameroon::cases() as $villeCase) {
                if ($villeCase->value === $currentVille) {
                    $matchingVilleEnum = $villeCase;
                    break;
                }
            }
            if ($matchingVilleEnum) {
                $form->get('ville')->setData($matchingVilleEnum);
            }

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $pointVente->setCoordonnees(new Coordonnees(
                        (float) $form->get('latitude')->getData(),
                        (float) $form->get('longitude')->getData(),
                    ));
                    $pointVente->setTelephone(new Telephone((string) $form->get('telephone')->getData()));

                    // Mettre à jour la ville
                    /** @var VilleCameroon $newVille */
                    $newVille = $form->get('ville')->getData();
                    $pointVente->setVille($newVille->value);

                    $seuilMinCash = $form->get('seuilMinCash')->getData();
                    $seuilMinFlotte = $form->get('seuilMinFlotte')->getData();

                    if ($seuilMinCash !== null) {
                        $pointVente->setSeuilMinCash(Montant::fromCentimes((int) ($seuilMinCash * 100)));
                    }
                    if ($seuilMinFlotte !== null) {
                        $pointVente->setSeuilMinFlotte(Montant::fromCentimes((int) ($seuilMinFlotte * 100)));
                    }

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
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du formulaire: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_pdv_list');
        }
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(PointVente $pointVente, Request $request): Response
    {
        try {
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
        } catch (\Throwable $e) {
            $this->addFlash('danger', 'Erreur critique: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_pdv_list');
        }
    }
}
