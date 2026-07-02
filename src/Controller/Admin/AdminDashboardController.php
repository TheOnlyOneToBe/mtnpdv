<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Dashboard\DashboardStatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin/dashboard', name: 'app_admin_dashboard')]
class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly DashboardStatisticsService $statisticsService,
    ) {
    }

    public function __invoke(): Response
    {
        $statistics = $this->statisticsService->getAdminStatistics();
        $reportData = $this->statisticsService->getReportData();

        return $this->render('admin/dashboard.html.twig', [
            'statistics' => $statistics,
            'reportData' => $reportData,
        ]);
    }
}
