<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Application\Dashboard\DashboardStatisticsService;
use App\Infrastructure\Export\PdfExportService;
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
        private readonly PdfExportService $pdfExportService,
    ) {
    }

    public function __invoke(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();
            $reportData = $this->statisticsService->getReportData();

            return $this->render('admin/reports/index.html.twig', [
                'statistics' => $statistics,
                'reportData' => $reportData,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement des rapports: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_dashboard');
        }
    }

    #[Route('/pdv', name: 'app_admin_reports_pdv')]
    public function pdvReport(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();

            return $this->render('admin/reports/pdv.html.twig', [
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du rapport PDV: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports');
        }
    }

    #[Route('/transactions', name: 'app_admin_reports_transactions')]
    public function transactionsReport(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();
            $reportData = $this->statisticsService->getReportData();

            return $this->render('admin/reports/transactions.html.twig', [
                'statistics' => $statistics,
                'reportData' => $reportData,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du rapport transactions: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports');
        }
    }

    #[Route('/users', name: 'app_admin_reports_users')]
    public function usersReport(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();

            return $this->render('admin/reports/users.html.twig', [
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors du chargement du rapport utilisateurs: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports');
        }
    }

    #[Route('/pdv/export', name: 'app_admin_reports_pdv_export', methods: ['GET'])]
    public function exportPdvReportPdf(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();

            $html = $this->renderView('admin/reports/export/pdv_report.html.twig', [
                'statistics' => $statistics,
            ]);

            return $this->pdfExportService->generatePdfFromHtml(
                $html,
                'rapport_pdv_' . date('Y-m-d_His') . '.pdf'
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'export PDF: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports_pdv');
        }
    }

    #[Route('/transactions/export', name: 'app_admin_reports_transactions_export', methods: ['GET'])]
    public function exportTransactionsReportPdf(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();
            $reportData = $this->statisticsService->getReportData();

            $html = $this->renderView('admin/reports/export/transactions_report.html.twig', [
                'statistics' => $statistics,
                'reportData' => $reportData,
            ]);

            return $this->pdfExportService->generatePdfFromHtml(
                $html,
                'rapport_transactions_' . date('Y-m-d_His') . '.pdf'
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'export PDF: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports_transactions');
        }
    }

    #[Route('/users/export', name: 'app_admin_reports_users_export', methods: ['GET'])]
    public function exportUsersReportPdf(): Response
    {
        try {
            $statistics = $this->statisticsService->getAdminStatistics();

            $html = $this->renderView('admin/reports/export/users_report.html.twig', [
                'statistics' => $statistics,
            ]);

            return $this->pdfExportService->generatePdfFromHtml(
                $html,
                'rapport_utilisateurs_' . date('Y-m-d_His') . '.pdf'
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur lors de l\'export PDF: '.$e->getMessage());
            return $this->redirectToRoute('app_admin_reports_users');
        }
    }
}
