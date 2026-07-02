<?php

declare(strict_types=1);

namespace App\Infrastructure\Export;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

final class PdfExportService
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        $this->dompdf = new Dompdf($options);
        $this->dompdf->setPaper('A4', 'portrait');
    }

    public function generatePdfFromHtml(string $html, string $filename = 'document.pdf'): Response
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();

        $pdfContent = $this->dompdf->output();

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }

    public function savePdfToFile(string $html, string $filepath): bool
    {
        $this->dompdf->loadHtml($html);
        $this->dompdf->render();

        $pdfContent = $this->dompdf->output();

        return file_put_contents($filepath, $pdfContent) !== false;
    }
}
