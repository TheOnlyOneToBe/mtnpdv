<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Dashboard\DashboardStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/reports', name: 'app_admin_reports')]
class AdminReportsController extends AbstractController
{
    public function __construct(
        private readonly DashboardStatisticsService $statisticsService,
    ) {
    }

    public function __invoke(): Response
    {
        $statistics = $this->statisticsService->getAdminStatistics();
        $reportData = $this->statisticsService->getReportData();

        return $this->render('admin/reports/index.html.twig', [
            'statistics' => $statistics,
            'reportData' => $reportData,
        ]);
    }

    #[Route('/admin/reports/pdv', name: 'app_admin_reports_pdv')]
    public function pdvReport(): Response
    {
        $statistics = $this->statisticsService->getAdminStatistics();

        return $this->render('admin/reports/pdv.html.twig', [
            'statistics' => $statistics,
        ]);
    }

    #[Route('/admin/reports/transactions', name: 'app_admin_reports_transactions')]
    public function transactionsReport(): Response
    {
        $statistics = $this->statisticsService->getAdminStatistics();
        $reportData = $this->statisticsService->getReportData();

        return $this->render('admin/reports/transactions.html.twig', [
            'statistics' => $statistics,
            'reportData' => $reportData,
        ]);
    }

    #[Route('/admin/reports/users', name: 'app_admin_reports_users')]
    public function usersReport(): Response
    {
        $statistics = $this->statisticsService->getAdminStatistics();

        return $this->render('admin/reports/users.html.twig', [
            'statistics' => $statistics,
        ]);
    }
}
