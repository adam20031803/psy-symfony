<?php

namespace App\Controller\Admin;

use App\Repository\ExerciseRepository;
use App\Repository\ProgramRepository;
use App\Service\GeminiService;
use App\Service\GroqService;
use App\Service\PdfRenderService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use Shuchkin\SimpleXLSXGen;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/programs-dashboard', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'dashboard', methods: ['GET'])]
    public function index(
        ExerciseRepository $exerciseRepo,
        ProgramRepository  $programRepo,
    ): Response {
        // Stats per category
        $categoryStats = $exerciseRepo->countByCategory();

        // Total counts
        $totalExercises = $exerciseRepo->count([]);
        $totalPrograms  = $programRepo->count([]);
        $publishedCount = $programRepo->countPublished();
        $draftCount     = $programRepo->countDraft();

        // Latest entries
        $latestExercises = $exerciseRepo->findLatest(5);
        $latestPrograms  = $programRepo->findLatest(5);

        return $this->render('admin/dashboard.html.twig', [
            'categoryStats'   => $categoryStats,
            'totalExercises'  => $totalExercises,
            'totalPrograms'   => $totalPrograms,
            'publishedCount'  => $publishedCount,
            'draftCount'      => $draftCount,
            'latestExercises' => $latestExercises,
            'latestPrograms'  => $latestPrograms,
        ]);
    }

    #[Route('/ai-analyse', name: 'ai_analyse', methods: ['POST'])]
    public function aiAnalyse(
        ExerciseRepository $exerciseRepo,
        ProgramRepository  $programRepo,
        GeminiService      $gemini,
        GroqService        $groq
    ): JsonResponse {
        // Gather context about current programs & exercises
        $programs  = $programRepo->findAll();
        $exercises = $exerciseRepo->findAll();

        $progSummary = [];
        foreach ($programs as $p) {
            $progSummary[] = sprintf(
                '- "%s" (objectif: %s, niveau: %s, %d sem., %s)',
                $p->getTitle(),
                $p->getGoal(),
                $p->getLevel(),
                $p->getDurationWeeks(),
                $p->isPublished() ? 'publié' : 'brouillon'
            );
        }

        $exSummary = [];
        foreach ($exercises as $ex) {
            $exSummary[] = sprintf(
                '- "%s" [%s / %s / %d min / %d kcal]',
                $ex->getName(),
                $ex->getCategory(),
                $ex->getDifficulty(),
                $ex->getDuration(),
                $ex->getCalories()
            );
        }

        $prompt = "Tu es un expert en coaching sportif. Analyse les données suivantes et fournis des conseils au coach.\n" .
            "Réponds UNIQUEMENT au format JSON strict avec cette structure exact:\n" .
            "{\n" .
            "  \"summary\": \"Bref résumé (max 200 chars)\",\n" .
            "  \"kpis\": [\n" .
            "    {\"label\": \"Diversité\", \"value\": \"85\", \"unit\": \"%\", \"icon\": \"bi-star\"},\n" .
            "    {\"label\": \"Couverture\", \"value\": \"12\", \"unit\": \"cats\", \"icon\": \"bi-grid\"},\n" .
            "    {\"label\": \"Intensité\", \"value\": \"Moyenne\", \"unit\": \"\", \"icon\": \"bi-lightning\"}\n" .
            "  ],\n" .
            "  \"chartData\": {\n" .
            "    \"labels\": [\"Cardio\", \"Muscu\", \"Yoga\", \"HIIT\", \"Flex\"],\n" .
            "    \"values\": [10, 20, 5, 15, 8]\n" .
            "  },\n" .
            "  \"advice\": \"Tes conseils détaillés ici...\"\n" .
            "}\n\n" .
            "=== PROGRAMMES ACTUELS (" . count($programs) . ") ===\n" .
            (empty($progSummary) ? "Aucun programme.\n" : implode("\n", $progSummary)) .
            "\n\n=== EXERCICES ACTUELS (" . count($exercises) . ") ===\n" .
            (empty($exSummary) ? "Aucun exercice.\n" : implode("\n", $exSummary));

        // Try Gemini, fallback to Groq
        $response = $gemini->generateResponse($prompt);
        if (str_starts_with($response, 'API Key is missing') || str_starts_with($response, 'Erreur')) {
            $response = $groq->generateResponseFreeform($prompt);
        }

        // Clean markdown if AI wrapped JSON in ```json
        $response = preg_replace('/^```json\s*|\s*```$/i', '', trim($response));
        $data = json_decode($response, true);

        // If JSON fails, return a safe structure with the original text as advice
        if (!$data) {
            return new JsonResponse([
                'summary' => 'Analyse disponible',
                'kpis' => [],
                'chartData' => null,
                'advice' => $response
            ]);
        }

        return new JsonResponse($data);
    }

    #[Route('/ai-analyse/pdf', name: 'ai_analyse_pdf', methods: ['POST'])]
    public function exportPdf(Request $request, PdfRenderService $pdfService): Response
    {
        $summary = $request->request->get('summary', '');
        $advice = $request->request->get('advice', '');
        $kpisJson = $request->request->get('kpis', '[]');
        $chartDataJson = $request->request->get('chartData', '{}');

        $kpis = json_decode($kpisJson, true) ?: [];
        $chartData = json_decode($chartDataJson, true) ?: [];

        return $pdfService->renderPdf(
            'admin/pdf/analysis_report.html.twig',
            [
                'summary' => $summary,
                'advice' => $advice,
                'kpis' => $kpis,
                'chartData' => $chartData,
                'exported_at' => new \DateTimeImmutable(),
            ],
            sprintf('analyse-ia-fitness-%s.pdf', date('Y-m-d_H-i'))
        );
    }

    #[Route('/ai-analyse/excel', name: 'ai_analyse_excel', methods: ['POST'])]
    public function exportExcel(
        Request            $request,
        ProgramRepository  $programRepo,
        ExerciseRepository $exerciseRepo
    ): Response {
        // ── POST data from AI analysis ──
        $summary   = $request->request->get('summary', '');
        $advice    = $request->request->get('advice', '');
        $kpis      = json_decode($request->request->get('kpis', '[]'), true) ?: [];
        $chartData = json_decode($request->request->get('chartData', '{}'), true) ?: [];

        // ── Live DB data ──
        $programs  = $programRepo->findAll();
        $exercises = $exerciseRepo->findAll();

        $catFrequency = [];
        foreach ($exercises as $ex) {
            $cat = $ex->getCategory() ?? 'Autre';
            $catFrequency[$cat] = ($catFrequency[$cat] ?? 0) + 1;
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Analyse Fitness IA')
            ->setCreator('Atomic You');

        // ── Helper: style a header cell (dark bg, white bold text) ──
        $styleHeader = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF3730A3']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $styleSubHeader = [
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF6366F1']],
        ];
        $styleTitle = [
            'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF1E1B4B']],
        ];
        $styleAltRow = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0F4FF']],
        ];

        // ══════════════════════════════════════
        // SHEET 1 — Analyse IA
        // ══════════════════════════════════════
        $ws1 = $spreadsheet->getActiveSheet()->setTitle('Analyse IA');

        $ws1->setCellValue('A1', 'RAPPORT D\'INTELLIGENCE FITNESS — ATOMIC YOU');
        $ws1->getStyle('A1')->applyFromArray($styleTitle);
        $ws1->mergeCells('A1:D1');

        $ws1->setCellValue('A2', 'Généré le :');
        $ws1->setCellValue('B2', date('d/m/Y H:i'));
        $ws1->getStyle('A2')->getFont()->setBold(true);

        // KPIs section
        $ws1->setCellValue('A4', 'INDICATEURS CLÉS');
        $ws1->mergeCells('A4:D4');
        $ws1->getStyle('A4')->applyFromArray($styleHeader);

        $ws1->setCellValue('A5', 'Indicateur');
        $ws1->setCellValue('B5', 'Valeur');
        $ws1->setCellValue('C5', 'Unité');
        $ws1->getStyle('A5:C5')->applyFromArray($styleSubHeader);

        $row = 6;
        foreach ($kpis as $i => $kpi) {
            $ws1->setCellValue('A' . $row, $kpi['label'] ?? '');
            $ws1->setCellValue('B' . $row, $kpi['value'] ?? '');
            $ws1->setCellValue('C' . $row, $kpi['unit'] ?? '');
            if ($i % 2 === 0) {
                $ws1->getStyle("A{$row}:C{$row}")->applyFromArray($styleAltRow);
            }
            $row++;
        }

        // Summary section
        $row += 1;
        $ws1->setCellValue('A' . $row, 'RÉSUMÉ EXÉCUTIF');
        $ws1->mergeCells("A{$row}:D{$row}");
        $ws1->getStyle("A{$row}")->applyFromArray($styleHeader);
        $row++;
        $ws1->setCellValue('A' . $row, $summary);
        $ws1->mergeCells("A{$row}:D{$row}");
        $ws1->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        $ws1->getRowDimension($row)->setRowHeight(60);

        // Advice section
        $row += 2;
        $ws1->setCellValue('A' . $row, 'RECOMMANDATIONS DU COACH EXPERT');
        $ws1->mergeCells("A{$row}:D{$row}");
        $ws1->getStyle("A{$row}")->applyFromArray($styleHeader);
        $row++;
        $ws1->setCellValue('A' . $row, $advice);
        $ws1->mergeCells("A{$row}:D{$row}");
        $ws1->getStyle("A{$row}")->getAlignment()->setWrapText(true);
        $ws1->getRowDimension($row)->setRowHeight(120);

        foreach (['A' => 35, 'B' => 20, 'C' => 15, 'D' => 20] as $col => $width) {
            $ws1->getColumnDimension($col)->setWidth($width);
        }

        // ══════════════════════════════════════
        // SHEET 2 — Calories par Programme
        // ══════════════════════════════════════
        $ws2 = $spreadsheet->createSheet()->setTitle('Calories par Programme');

        $ws2->setCellValue('A1', 'CALORIES TOTALES PAR PROGRAMME');
        $ws2->mergeCells('A1:G1');
        $ws2->getStyle('A1')->applyFromArray($styleTitle);

        $headers2 = ['Programme', 'Objectif', 'Niveau', 'Durée (sem.)', 'Nb Exercices', 'Calories (kcal)', 'Statut'];
        foreach ($headers2 as $i => $h) {
            $col = chr(65 + $i);
            $ws2->setCellValue("{$col}3", $h);
        }
        $ws2->getStyle('A3:G3')->applyFromArray($styleSubHeader);

        $row = 4;
        foreach ($programs as $i => $prog) {
            $totalCal = 0;
            $nbEx = 0;
            foreach ($prog->getExercises() as $ex) {
                $totalCal += (int) ($ex->getCalories() ?? 0);
                $nbEx++;
            }
            $ws2->setCellValue('A' . $row, $prog->getTitle());
            $ws2->setCellValue('B' . $row, $prog->getGoal());
            $ws2->setCellValue('C' . $row, $prog->getLevel());
            $ws2->setCellValue('D' . $row, $prog->getDurationWeeks());
            $ws2->setCellValue('E' . $row, $nbEx);
            $ws2->setCellValue('F' . $row, $totalCal);
            $ws2->setCellValue('G' . $row, $prog->isPublished() ? '✔ Publié' : '• Brouillon');

            if ($i % 2 === 0) {
                $ws2->getStyle("A{$row}:G{$row}")->applyFromArray($styleAltRow);
            }
            // Color-code calories column
            if ($totalCal > 2000) {
                $ws2->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setARGB('FF16A34A');
            }
            $row++;
        }
        foreach (['A' => 30, 'B' => 30, 'C' => 18, 'D' => 14, 'E' => 14, 'F' => 18, 'G' => 14] as $col => $w) {
            $ws2->getColumnDimension($col)->setWidth($w);
        }

        // ══════════════════════════════════════
        // SHEET 3 — Équilibre Catalogue
        // ══════════════════════════════════════
        $ws3 = $spreadsheet->createSheet()->setTitle('Equilibre Catalogue');

        $ws3->setCellValue('A1', 'ÉQUILIBRE DU CATALOGUE D\'EXERCICES');
        $ws3->mergeCells('A1:C1');
        $ws3->getStyle('A1')->applyFromArray($styleTitle);

        $ws3->setCellValue('A3', 'Catégorie');
        $ws3->setCellValue('B3', 'Score IA');
        $ws3->setCellValue('C3', 'Exercices réels (DB)');
        $ws3->getStyle('A3:C3')->applyFromArray($styleSubHeader);

        $aiLabels = $chartData['labels'] ?? [];
        $aiValues = $chartData['values'] ?? [];
        $row = 4;
        foreach ($aiLabels as $i => $label) {
            $ws3->setCellValue('A' . $row, $label);
            $ws3->setCellValue('B' . $row, $aiValues[$i] ?? 0);
            $ws3->setCellValue('C' . $row, $catFrequency[$label] ?? 0);
            if ($i % 2 === 0) {
                $ws3->getStyle("A{$row}:C{$row}")->applyFromArray($styleAltRow);
            }
            $row++;
        }

        $row += 1;
        $ws3->setCellValue('A' . $row, 'TOUS LES EXERCICES PAR CATÉGORIE (base de données)');
        $ws3->mergeCells("A{$row}:C{$row}");
        $ws3->getStyle("A{$row}")->applyFromArray($styleHeader);
        $row++;
        $ws3->setCellValue('A' . $row, 'Catégorie');
        $ws3->setCellValue('B' . $row, 'Nbre d\'exercices');
        $ws3->getStyle("A{$row}:B{$row}")->applyFromArray($styleSubHeader);
        $row++;
        arsort($catFrequency);
        foreach ($catFrequency as $cat => $count) {
            $ws3->setCellValue('A' . $row, $cat);
            $ws3->setCellValue('B' . $row, $count);
            $row++;
        }
        foreach (['A' => 30, 'B' => 18, 'C' => 22] as $col => $w) {
            $ws3->getColumnDimension($col)->setWidth($w);
        }

        // ── Output as .xls (no ext-zip required) ──
        $spreadsheet->setActiveSheetIndex(0);
        $filename = sprintf('analyse-fitness-%s.xls', date('Y-m-d_H-i'));

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xls($spreadsheet);
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.ms-excel');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}

