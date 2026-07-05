<?php

declare(strict_types=1);

namespace App\Application\Dashboard;

use App\Domain\Enum\TypeProblemeSupervision;
use App\Domain\Repository\PointVenteRepositoryInterface;
use App\Domain\Repository\TransactionRepositoryInterface;
use App\Domain\Repository\UtilisateurRepositoryInterface;

class DashboardStatisticsService
{
    public function __construct(
        private readonly PointVenteRepositoryInterface $pointVentes,
        private readonly TransactionRepositoryInterface $transactions,
        private readonly UtilisateurRepositoryInterface $utilisateurs,
    ) {
    }

    public function getAdminStatistics(): array
    {
        $pointVentes = $this->pointVentes->findAll();
        $transactions = $this->transactions->findAll();
        $utilisateurs = $this->utilisateurs->findAll();

        // Compter les PDV par statut
        $pdvByStatus = [
            'ACTIF' => 0,
            'FERME' => 0,
            'SUSPENDU' => 0,
        ];

        foreach ($pointVentes as $pdv) {
            $status = $pdv->getStatutActuel()->value;
            if (isset($pdvByStatus[$status])) {
                $pdvByStatus[$status]++;
            }
        }

        // Compter les transactions par statut
        $transactionsByStatus = [
            'EN_ATTENTE' => 0,
            'VALIDEE' => 0,
            'REJETEE' => 0,
        ];

        foreach ($transactions as $transaction) {
            $status = $transaction->getStatut()->value;
            if (isset($transactionsByStatus[$status])) {
                $transactionsByStatus[$status]++;
            }
        }

        // Compter les utilisateurs par rôle
        $usersByRole = [
            'ADMIN' => 0,
            'AGENT' => 0,
            'GERANT' => 0,
        ];

        foreach ($utilisateurs as $user) {
            foreach ($user->getRoles() as $role) {
                if (str_contains($role, 'ADMIN')) {
                    $usersByRole['ADMIN']++;
                } elseif (str_contains($role, 'AGENT')) {
                    $usersByRole['AGENT']++;
                } elseif (str_contains($role, 'GERANT')) {
                    $usersByRole['GERANT']++;
                }
            }
        }

        // Compter les visites avec problèmes
        $visitsWithProblems = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->getTypeProbleme() !== null) {
                $visitsWithProblems++;
            }
        }

        return [
            'totalPdv' => count($pointVentes),
            'totalTransactions' => count($transactions),
            'totalUsers' => count($utilisateurs),
            'totalVisits' => count($transactions),
            'visitsWithProblems' => $visitsWithProblems,
            'pdvByStatus' => $pdvByStatus,
            'transactionsByStatus' => $transactionsByStatus,
            'usersByRole' => $usersByRole,
            'pendingValidations' => $transactionsByStatus['EN_ATTENTE'],
        ];
    }

    public function getReportData(): array
    {
        $pointVentes = $this->pointVentes->findAll();
        $transactions = $this->transactions->findAll();

        // Revenue by PDV
        $revenueByPdv = [];
        foreach ($pointVentes as $pdv) {
            $revenue = 0;
            foreach ($transactions as $transaction) {
                if ($transaction->getPointVente()?->getId() === $pdv->getId() && $transaction->getStatut()->value === 'VALIDEE') {
                    $revenue += $transaction->getMontant()->getValue();
                }
            }
            $revenueByPdv[$pdv->getNomPdv()] = $revenue / 100; // Convert to decimal
        }

        // Transactions over time (last 7 days)
        $transactionsByDay = array_fill(0, 7, 0);
        $today = new \DateTime();
        foreach ($transactions as $transaction) {
            $diff = $today->diff($transaction->getDateTransac())->days;
            if ($diff < 7) {
                $transactionsByDay[6 - $diff]++;
            }
        }

        return [
            'revenueByPdv' => $revenueByPdv,
            'transactionsByDay' => $transactionsByDay,
            'dayLabels' => $this->getLastSevenDays(),
        ];
    }

    public function getSupervisionStatistics(): array
    {
        $transactions = $this->transactions->findAll();
        $pointVentes = $this->pointVentes->findAll();

        $totalVisits = count($transactions);
        $visitsWithProblems = 0;
        $visitsNoProblems = 0;
        $criticalProblems = 0;

        // Compter les problèmes par type
        $problemTypes = [];
        foreach (TypeProblemeSupervision::cases() as $type) {
            $problemTypes[$type] = 0;
        }

        // Compter les visites avec/sans problèmes et par type
        $pdvProblems = [];
        $recentProblems = [];

        foreach ($transactions as $transaction) {
            if ($transaction->getTypeProbleme() !== null) {
                $visitsWithProblems++;
                $problemTypes[$transaction->getTypeProbleme()]++;

                if ($transaction->getTypeProbleme()->urgence() === 'CRITIQUE') {
                    $criticalProblems++;
                }

                // Ajouter à la liste des PDV avec problèmes
                $pdvId = $transaction->getPointVente()?->getId();
                if ($pdvId && $transaction->getPointVente()) {
                    if (!isset($pdvProblems[$pdvId])) {
                        $pdvProblems[$pdvId] = [
                            'pdv' => $transaction->getPointVente(),
                            'problems' => [],
                        ];
                    }
                    $pdvProblems[$pdvId]['problems'][] = $transaction;
                }

                // Ajouter aux problèmes récents
                $recentProblems[] = $transaction;
            } else {
                $visitsNoProblems++;
            }
        }

        // Trier les problèmes récents par date décroissante et prendre les 10 derniers
        usort($recentProblems, fn($a, $b) => $b->getDateTransac() <=> $a->getDateTransac());
        $recentProblems = array_slice($recentProblems, 0, 10);

        return [
            'totalVisits' => $totalVisits,
            'visitsWithProblems' => $visitsWithProblems,
            'visitsNoProblems' => $visitsNoProblems,
            'criticalProblems' => $criticalProblems,
            'problemTypes' => $problemTypes,
            'pdvWithProblems' => $pdvProblems,
            'recentProblems' => $recentProblems,
        ];
    }

    private function getLastSevenDays(): array
    {
        $days = [];
        $today = new \DateTime();
        for ($i = 6; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-$i days");
            $days[] = $date->format('d M');
        }
        return $days;
    }
}
