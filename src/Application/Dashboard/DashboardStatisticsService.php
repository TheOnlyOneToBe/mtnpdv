<?php

declare(strict_types=1);

namespace App\Application\Dashboard;

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

        return [
            'totalPdv' => count($pointVentes),
            'totalTransactions' => count($transactions),
            'totalUsers' => count($utilisateurs),
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
                if ($transaction->getPointVente()->getId() === $pdv->getId() && $transaction->getStatut()->value === 'VALIDEE') {
                    $revenue += $transaction->getMontant()->value();
                }
            }
            $revenueByPdv[$pdv->getNomPdv()] = $revenue / 100; // Convert to decimal
        }

        // Transactions over time (last 7 days)
        $transactionsByDay = array_fill(0, 7, 0);
        $today = new \DateTime();
        foreach ($transactions as $transaction) {
            $diff = $today->diff($transaction->getDateCreation())->days;
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
