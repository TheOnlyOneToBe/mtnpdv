<?php

declare(strict_types=1);

namespace App\Controller\Agent;

use App\Application\Visite\EnregistrerVisiteCommande;
use App\Application\Visite\EnregistrerVisiteHandler;
use App\Domain\Entity\DemandeVisite;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\DemandeVisiteRepositoryInterface;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
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
        private readonly DemandeVisiteRepositoryInterface $demandeVisiteRepository,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $page = max(1, (int) $request->query->get('page', 1));
            $statut = $request->query->get('statut');

            // Récupérer les visites de l'agent
            $allVisites = $this->transactions->findByUtilisateur($user);

            // Filtrer par statut si demandé
            if ($statut) {
                $allVisites = array_filter($allVisites, fn($v) => $v->getStatut()->value === $statut);
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

    #[Route('/new/{demandeId?}', name: 'create')]
    public function create(Request $request, ?DemandeVisite $demandeId = null): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            $pdvs = $this->pointVentes->findAll();

            $defaultData = [];
            if ($demandeId !== null) {
                $defaultData = [
                    'pointVente' => $demandeId->getPointVente(),
                    'type' => $demandeId->getType(),
                    'montant' => $demandeId->getMontant()->montantCentimes(),
                ];
            }

            $form = $this->createForm(VisiteType::class, $defaultData, [
                'pointVentes' => $pdvs,
            ]);

            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                try {
                    $data = $form->getData();

                    $pointVente = $data['pointVente'] ?? null;
                    $type = $data['type'] ?? TypeTransaction::VISITE;
                    $montantCentimes = $data['montant'] ?? 0;
                    $montant = Montant::fromCentimes((int) $montantCentimes);
                    $commentaire = $data['commentaire'] ?? null;
                    $typeProbleme = $data['typeProbleme'] ?? null;
                    $photoFile = $form->get('photoFile')->getData();

                    if (!$pointVente) {
                        throw new \InvalidArgumentException('Point de vente manquant');
                    }

                    $latitude = (float) ($request->request->get('latitude') ?? 0);
                    $longitude = (float) ($request->request->get('longitude') ?? 0);

                    if ($latitude === 0.0 || $longitude === 0.0) {
                        $this->addFlash('danger', 'Position GPS manquante ou invalide');
                        return $this->redirectToRoute('app_agent_visite_create', ['demandeId' => $demandeId?->getId()]);
                    }

                    $position = new Coordonnees((string) $latitude, (string) $longitude);

                    $commande = new EnregistrerVisiteCommande(
                        pointVente: $pointVente,
                        agent: $user,
                        type: $type,
                        positionAgent: $position,
                        montant: $montant,
                        commentaire: $commentaire,
                        photo: $photoFile,
                        typeProbleme: $typeProbleme,
                        demandeVisite: $demandeId,
                    );

                    $resultat = ($this->enregistrerVisiteHandler)($commande);

                    $message = $resultat->dansLaZone
                        ? 'Visite enregistrée avec succès.'
                        : sprintf('Visite enregistrée mais hors de la zone de tolérance (distance: %.0f m)', $resultat->distanceMetres);

                    $flashType = $resultat->dansLaZone ? 'success' : 'warning';
                    $this->addFlash($flashType, $message);

                    return $this->redirectToRoute('app_agent_visite_show', ['id' => $resultat->transaction->getId()]);
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'Erreur lors de l\'enregistrement: '.$e->getMessage());
                }
            }

            return $this->render('agent/visite/form.html.twig', [
                'form' => $form,
                'demande' => $demandeId,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_agent_dashboard');
        }
    }

    #[Route('/demande/{id}/accept', name: 'demande_accept')]
    public function acceptDemande(DemandeVisite $demande): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();
            
            if ($demande->getAgent()?->getId() !== $user->getId()) {
                $this->addFlash('danger', 'Accès refusé.');
                return $this->redirectToRoute('app_agent_dashboard');
            }

            $demande->accepter();
            $this->demandeVisiteRepository->save($demande);

            $this->addFlash('success', 'Mission acceptée avec succès.');
            return $this->redirectToRoute('app_agent_dashboard');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_agent_dashboard');
        }
    }

    #[Route('/{id}', name: 'show')]
    public function show(Transaction $visite): Response
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->getUser();

            // Vérifier que la visite appartient à l'agent
            if ($visite->getAgent()?->getId() !== $user->getId()) {
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
