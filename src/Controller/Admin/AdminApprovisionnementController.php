<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Notification\NotificationService;
use App\Domain\Entity\PointVente;
use App\Domain\Entity\Transaction;
use App\Domain\Entity\Utilisateur;
use App\Domain\Enum\StatutTransaction;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Coordonnees;
use App\Domain\ValueObject\Montant;
use App\Infrastructure\Pagination\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/approvisionnement', name: 'app_admin_approvisionnement_')]
class AdminApprovisionnementController extends AbstractController
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
        private readonly PaginationService $paginationService,
        private readonly NotificationService $notificationService,
    ) {
    }

    #[Route('', name: 'list')]
    public function list(Request $request): Response
    {
        try {
            $page = max(1, (int) $request->query->get('page', 1));

            $allApprovisionnements = $this->transactions->findByType(TypeTransaction::APPROVISIONNEMENT_FLOTTE);

            $pagination = $this->paginationService->paginate($allApprovisionnements, $page);

            return $this->render('admin/approvisionnement/list.html.twig', [
                'approvisionnements' => $pagination['items'],
                'pagination' => $pagination,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/new', name: 'create')]
    public function create(Request $request): Response
    {
        try {
            $pdvs = $this->pointVentes->findAll();

            if ($request->isMethod('POST')) {
                $pdvId = (int) $request->request->get('pdv');
                $montantCentimes = (int) $request->request->get('montant');

                $pdv = $this->pointVentes->find($pdvId);
                if (!$pdv) {
                    $this->addFlash('danger', 'Point de vente non trouvé.');
                    return $this->redirectToRoute('app_admin_approvisionnement_create');
                }

                $montant = Montant::fromCentimes($montantCentimes);

                // Create transaction (we'll use dummy coordinates for admin-created transactions)
                $transaction = new Transaction(
                    type: TypeTransaction::APPROVISIONNEMENT_FLOTTE,
                    montant: $montant,
                    coordonneesCapture: new Coordonnees('0', '0'),
                );
                $transaction->setPointVente($pdv);

                $this->transactions->save($transaction);

                // Notify assigned agents
                foreach ($pdv->getAttributions() as $attribution) {
                    if ($attribution->isActif()) {
                        $this->notificationService->notifierAgentApprovisionnementDemande(
                            agent: $attribution->getAgent(),
                            pdvNom: $pdv->getNomPdv(),
                            montant: $montant->toDecimal(),
                            lien: $this->generateUrl('app_agent_visite_show', ['id' => $transaction->getId()]),
                        );
                    }
                }

                $this->addFlash('success', 'Demande d\'approvisionnement créée avec succès.');
                return $this->redirectToRoute('app_admin_approvisionnement_list');
            }

            return $this->render('admin/approvisionnement/create.html.twig', [
                'pdvs' => $pdvs,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_approvisionnement_list');
        }
    }

    #[Route('/{id}/terminer', name: 'terminer', methods: ['POST'])]
    public function terminer(Request $request, Transaction $transaction): Response
    {
        try {
            if ($transaction->getType() !== TypeTransaction::APPROVISIONNEMENT_FLOTTE) {
                $this->addFlash('danger', 'Ceci n\'est pas une demande d\'approvisionnement.');
                return $this->redirectToRoute('app_admin_approvisionnement_list');
            }

            $transaction->terminer();
            $this->transactions->save($transaction);

            // Notify agent
            $agent = $transaction->getAgent();
            if ($agent) {
                $this->notificationService->notifierAgentApprovisionnementTermine(
                    agent: $agent,
                    pdvNom: $transaction->getPointVente()?->getNomPdv() ?? 'Point de vente inconnu',
                    montant: $transaction->getMontant()->toDecimal(),
                );
            }

            $this->addFlash('success', 'Approvisionnement marqué comme terminé.');
            return $this->redirectToRoute('app_admin_approvisionnement_list');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_approvisionnement_list');
        }
    }
}
