<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class PdfRenderService
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function renderPdf(string $twigTemplate, array $context, string $downloadFilename): Response
    {
        $html = $this->twig->render($twigTemplate, $context);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $output = $dompdf->output();
        if (!is_string($output) || '' === $output) {
            throw new \RuntimeException('Échec de la génération PDF.');
        }

        $safeName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $downloadFilename) ?? 'export.pdf';

        return new Response($output, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$safeName.'"',
        ]);
    }
}
