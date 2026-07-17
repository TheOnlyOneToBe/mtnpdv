<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Supervision\SupervisionFiltreTransaction;
use App\Application\Supervision\SupervisionService;
use App\Domain\Enum\TypeTransaction;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;
use App\Domain\ValueObject\Montant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/supervision', name: 'app_admin_supervision_')]
class AdminSupervisionController extends AbstractController
{
    public function __construct(
        private readonly SupervisionService $supervisionService,
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    #[Route('', name: 'index')]
    public function index(Request $request): Response
    {
        $periodeJours = max(1, min(365, (int) $request->query->get('periode', 30)));

        return $this->render('admin/supervision/index.html.twig', [
            'data' => $this->supervisionService->getDashboardData($periodeJours),
            'periodeJours' => $periodeJours,
        ]);
    }

    #[Route('/carte', name: 'carte')]
    public function carte(Request $request): Response
    {
        $periodeJours = max(1, min(365, (int) $request->query->get('periode', 30)));

        return $this->render('admin/supervision/carte.html.twig', [
            'markers' => $this->supervisionService->getPdvMapData($periodeJours),
            'periodeJours' => $periodeJours,
        ]);
    }

    #[Route('/transactions', name: 'transactions')]
    public function transactions(Request $request): Response
    {
        $filtre = $this->construireFiltre($request);
        $transactions = $this->supervisionService->getTransactionsFiltrees($filtre);

        return $this->render('admin/supervision/transactions.html.twig', [
            'transactions' => $transactions,
            'pointVentes' => $this->pointVentes->findAll(),
            'agents' => $this->utilisateurs->findByRole('AGENT'),
            'filtre' => $request->query->all(),
            'typesSupervision' => [
                TypeTransaction::DISTRIBUTION_CASH,
                TypeTransaction::APPROVISIONNEMENT_FLOTTE,
                TypeTransaction::VISITE,
            ],
        ]);
    }

    #[Route('/agents', name: 'agents')]
    public function agents(Request $request): Response
    {
        $debut = $this->parseDate($request->query->get('debut'));
        $fin = $this->parseDate($request->query->get('fin'), true);
        $agentId = $request->query->get('agent');

        $activite = $this->supervisionService->getActiviteAgents($debut, $fin);
        $historique = null;
        $agentSelectionne = null;

        if ($agentId) {
            $agentSelectionne = $this->utilisateurs->find((int) $agentId);
            if ($agentSelectionne) {
                $historique = $this->supervisionService->getHistoriqueAgent($agentSelectionne, $debut, $fin);
            }
        }

        return $this->render('admin/supervision/agents.html.twig', [
            'activite' => $activite,
            'historique' => $historique,
            'agentSelectionne' => $agentSelectionne,
            'agents' => $this->utilisateurs->findByRole('AGENT'),
            'filtre' => $request->query->all(),
        ]);
    }

    private function construireFiltre(Request $request): SupervisionFiltreTransaction
    {
        $type = $request->query->get('type');
        $pdvId = $request->query->get('pdv');
        $agentId = $request->query->get('agent');
        $montantMin = $request->query->get('montant_min');
        $montantMax = $request->query->get('montant_max');

        return new SupervisionFiltreTransaction(
            type: $type ? TypeTransaction::tryFrom($type) : null,
            pointVente: $pdvId ? $this->pointVentes->find((int) $pdvId) : null,
            agent: $agentId ? $this->utilisateurs->find((int) $agentId) : null,
            debut: $this->parseDate($request->query->get('debut')),
            fin: $this->parseDate($request->query->get('fin'), true),
            montantMin: is_numeric($montantMin) ? Montant::fromString((string) $montantMin) : null,
            montantMax: is_numeric($montantMax) ? Montant::fromString((string) $montantMax) : null,
        );
    }

    private function parseDate(?string $value, bool $finDeJournee = false): ?\DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date) {
            return null;
        }

        return $finDeJournee ? $date->setTime(23, 59, 59) : $date->setTime(0, 0);
    }
}
