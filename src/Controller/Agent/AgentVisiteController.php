<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Application\Visite\EnregistrerVisiteHandler;
use App\Domain\Entity\Transaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Form\VisiteType;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_AGENT')]
#[Route('/agent/visite', name: 'app_agent_visite_')]
class AgentVisiteController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly EnregistrerVisiteHandler $enregistrerVisiteHandler,
        private readonly PaginationService $paginationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $user = $this->getUser();
            $page = max(1, (int) $request->query->get('page', 1));
            $statut = $request->query->get('statut');

            // Récupérer les visites de l'agent
            $allVisites = $this->transactions->findByUtilisateur($user);

            // Filtrer par statut si demandé
            if ($statut) {
                $allVisites = array_filter($allVisites, fn($v) => $v->getStatut()->name === $statut);
            }

            $pagination = $this->paginationService->paginate($allVisites, $page);
            $pageMetadata = $this->paginationService->getPageMetadata($pagination);
            $itemRange = $this->paginationService->getItemRange($pagination);

            return $this->render('agent/visite/list.html.twig', [
                'visites' => $pagination['items'],
                'pagination' => $pagination,
                'pageMetadata' => $pageMetadata,
                'itemRange' => $itemRange,
                'statut' => $statut,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement: '.$e->getMessage());
            return $this->redirectToRoute('app_agent_dashboard');
        }
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        try {
            $user = $this->getUser();

            // Créer une nouvelle visite vide
            $visite = new Transaction(
                agent: $user,
                pointVente: null,
                type: null,
                montant: null,
                photoPreuveUrl: null,
                position: null,
                commentaire: null,
            );

            $form = $this->createForm(VisiteType::class, $visite, [
                'pointVentes' => $this->pointVentes->findAll(),
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    // Enregistrer la visite via le handler
                    $resultat = $this->enregistrerVisiteHandler->handle($visite, $user);

                    if ($request->getPreferredFormat() === 'turbo_stream') {
                        return $this->render('agent/visite/turbo/create.stream.twig', [
                            'visite' => $resultat['transaction'],
                            'distanceMetres' => $resultat['distanceMetres'],
                            'dansLaZone' => $resultat['dansLaZone'],
                        ]);
                    }

                    $message = $resultat['dansLaZone']
                        ? 'Visite enregistrée avec succès.'
                        : sprintf('Visite enregistrée mais hors de la zone de tolérance (distance: %.0f m)', $resultat['distanceMetres']);

                    $flashType = $resultat['dansLaZone'] ? 'success' : 'warning';
                    $this->addFlash($flashType, $message);

                    return $this->redirectToRoute('app_agent_visite_show', ['id' => $resultat['transaction']->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'enregistrement: '.$e->getMessage());
                }
            }

            return $this->render('agent/visite/form.html.twig', [
                'form' => $form,
                'visite' => $visite,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_agent_dashboard');
        }
    }

    #[Route('/{id}', name: 'show')]
    public function show(Transaction $visite): Response
    {
        try {
            $user = $this->getUser();

            // Vérifier que la visite appartient à l'agent
            if ($visite->getAgent()->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Accès refusé.');
                return $this->redirectToRoute('app_agent_dashboard');
            }

            return $this->render('agent/visite/show.html.twig', [
                'visite' => $visite,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_agent_visite_list');
        }
    }
}
