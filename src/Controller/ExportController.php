<?php

declare(strict_types=1);

namespace App\Controller;

use App\Domain\Repository\TransactionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/export', name: 'app_export_')]
class ExportController extends AbstractController
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactions,
    ) {
    }

    #[Route('/pdf/rapports', name: 'pdf_rapports')]
    public function exportRapportsPdf(): Response
    {
        try {
            $allTransactions = $this->transactions->findAll();

            // Générer HTML
            $html = $this->renderView('export/rapports_pdf.html.twig', [
                'transactions' => $allTransactions,
                'generatedAt' => new \DateTimeImmutable(),
            ]);

            // Créer PDF
            $pdf = new \Dompdf\Dompdf();
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            return new Response(
                $pdf->output(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="rapports_' . date('Y-m-d') . '.pdf"',
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur génération PDF: ' . $e->getMessage());
            return $this->redirectToRoute('app_reports_dashboard');
        }
    }

    #[Route('/pdf/visite/{id}', name: 'pdf_visite')]
    public function exportVisitePdf(int $id): Response
    {
        try {
            $visite = $this->transactions->find($id);
            if (!$visite) {
                throw new \Exception('Visite non trouvée');
            }

            $html = $this->renderView('export/visite_pdf.html.twig', [
                'visite' => $visite,
                'generatedAt' => new \DateTimeImmutable(),
            ]);

            $pdf = new \Dompdf\Dompdf();
            $pdf->loadHtml($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            return new Response(
                $pdf->output(),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="visite_' . $id . '_' . date('Y-m-d') . '.pdf"',
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur: ' . $e->getMessage());
            return $this->redirectToRoute('app_agent_visite_list');
        }
    }

    #[Route('/csv/transactions', name: 'csv_transactions')]
    public function exportTransactionsCsv(): Response
    {
        try {
            $transactions = $this->transactions->findAll();

            $csv = "Date,Type,Statut,Montant (FCFA),Point Vente,Agent,Localisation\n";

            foreach ($transactions as $transaction) {
                $csv .= sprintf(
                    '"%s","%s","%s","%d","%s","%s","%f,%f"' . "\n",
                    $transaction->getDateTransac()->format('Y-m-d H:i'),
                    $transaction->getType()->value,
                    $transaction->getStatut()->value,
                    (int) ($transaction->getMontant()->centimes() / 100),
                    $transaction->getPointVente()?->getNomPdv() ?? 'N/A',
                    $transaction->getUtilisateur()?->getNomComplet() ?? 'N/A',
                    $transaction->getCoordonneesCapture()->latitude(),
                    $transaction->getCoordonneesCapture()->longitude()
                );
            }

            return new Response(
                $csv,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'text/csv; charset=utf-8',
                    'Content-Disposition' => 'attachment; filename="transactions_' . date('Y-m-d') . '.csv"',
                ]
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Erreur export CSV: ' . $e->getMessage());
            return $this->redirectToRoute('app_reports_dashboard');
        }
    }
}
