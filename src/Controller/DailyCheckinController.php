<?php

namespace App\Controller;

use App\Entity\DailyCheckin;
use App\Entity\User;
use App\Form\DailyCheckinType;
use App\Repository\DailyCheckinRepository;
use App\Service\DailyCheckinAnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/daily/checkin')]
#[IsGranted('ROLE_USER')]
class DailyCheckinController extends AbstractController
{
    public function __construct(
        private readonly DailyCheckinAnalyticsService $analyticsService,
    ) {
    }

    private function getAppUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    #[Route('/', name: 'app_daily_checkin_index', methods: ['GET'])]
    public function index(Request $request, DailyCheckinRepository $repo): Response
    {
        $user = $this->getAppUser();
        $search = $request->query->getString('q', '');
        $period = $request->query->getString('period', 'all');
        if (!in_array($period, ['all', 'today', 'week', 'month'], true)) {
            $period = 'all';
        }

        $dailyCheckins = $repo->findFilteredForUser($user, $search !== '' ? $search : null, $period);

        $last30 = $repo->findLastDaysForUser($user, 30);
        $radar = $this->buildWellnessRadarDataset($last30);

        return $this->render('daily_checkin/index.html.twig', [
            'daily_checkins' => $dailyCheckins,
            'stats' => [
                'total' => $repo->countForUser($user),
                'avg_mood_7' => $repo->averageMoodLastDays($user, 7),
                'avg_productivity_7' => $repo->averageProductivityLastDays($user, 7),
                'today_done' => $repo->hasCheckinToday($user),
            ],
            'radar' => $radar,
            'filter_q' => $search,
            'filter_period' => $period,
        ]);
    }

    #[Route('/analysis', name: 'app_daily_checkin_analysis', methods: ['GET'])]
    public function analysis(DailyCheckinRepository $repo): Response
    {
        $user = $this->getAppUser();
        $checkins = $repo->findLastDaysForUser($user, 30);
        $data = $this->analyticsService->buildThirtyDayAnalysis($user, $checkins);

        return $this->render('daily_checkin/analysis.html.twig', $data);
    }

    #[Route('/suggestions', name: 'app_daily_checkin_suggestions', methods: ['GET'])]
    public function suggestions(DailyCheckinRepository $repo): Response
    {
        $user = $this->getAppUser();
        $checkins = $repo->findLastDaysForUser($user, 30);
        $result = $this->analyticsService->buildSuggestions($checkins);

        $latestFeedback = [];
        if (!empty($checkins)) {
            // Checkins are newest first
            $latestFeedback = $this->analyticsService->getAIFeedbackForJournal($checkins[0]);
        }

        return $this->render('daily_checkin/suggestions.html.twig', [
            'categories' => $result['categories'],
            'averages'   => $result['averages'] ?? null,
            'challenge'  => $result['challenge'] ?? null,
            'ai_feedback' => $latestFeedback,
        ]);
    }

    #[Route('/export/pdf', name: 'app_daily_checkin_export_pdf', methods: ['GET'])]
    public function exportPdfList(Request $request, DailyCheckinRepository $repo): Response
    {
        $user = $this->getAppUser();
        $search = $request->query->getString('q', '');
        $period = $request->query->getString('period', 'all');
        if (!in_array($period, ['all', 'today', 'week', 'month'], true)) {
            $period = 'all';
        }

        $items = $repo->findFilteredForUser($user, $search !== '' ? $search : null, $period);

        return $this->renderPdf(
            'daily_checkin/pdf/list.html.twig',
            [
                'daily_checkins' => $items,
                'exported_at' => new \DateTimeImmutable(),
                'title' => 'Rapport check-ins',
            ],
            sprintf('checkins-%s.pdf', (new \DateTimeImmutable())->format('Y-m-d_His'))
        );
    }

    #[Route('/{id}/export/pdf', name: 'app_daily_checkin_export_pdf_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function exportPdfOne(DailyCheckin $dailyCheckin): Response
    {
        $this->denyUnlessOwner($dailyCheckin);

        return $this->renderPdf(
            'daily_checkin/pdf/single.html.twig',
            [
                'c' => $dailyCheckin,
                'exported_at' => new \DateTimeImmutable(),
            ],
            sprintf('checkin-%s.pdf', $dailyCheckin->getCheckinDate()?->format('Y-m-d') ?? 'export')
        );
    }

    #[Route('/new', name: 'app_daily_checkin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, DailyCheckinRepository $repo): Response
    {
        $user = $this->getAppUser();

        $today = new \DateTimeImmutable('today');
        $existing = $repo->findOneByUserAndDate($user, $today);
        if ($existing !== null) {
            $this->addFlash('info', 'Vous avez déjà un check-in pour aujourd’hui. Vous pouvez le modifier ci-dessous.');

            return $this->redirectToRoute('app_daily_checkin_edit', ['id' => $existing->getId()], Response::HTTP_SEE_OTHER);
        }

        $dailyCheckin = new DailyCheckin();
        $dailyCheckin->setCheckinDate($today);
        $dailyCheckin->setMoodRating(5);
        $dailyCheckin->setEnergyLevel(5);
        $dailyCheckin->setProductivityLevel(5);
        $dailyCheckin->setStressLevel(5);
        $dailyCheckin->setSleepQuality(5);
        $dailyCheckin->setSleepHours(7.0);

        $form = $this->createForm(DailyCheckinType::class, $dailyCheckin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dailyCheckin->setUser($user);
            $em->persist($dailyCheckin);
            $em->flush();

            return $this->redirectToRoute('app_daily_checkin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('daily_checkin/new.html.twig', [
            'daily_checkin' => $dailyCheckin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_daily_checkin_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, DailyCheckin $dailyCheckin, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($dailyCheckin);

        $form = $this->createForm(DailyCheckinType::class, $dailyCheckin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($dailyCheckin->getUser() === null) {
                $dailyCheckin->setUser($this->getAppUser());
            }
            $em->flush();

            return $this->redirectToRoute('app_daily_checkin_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('daily_checkin/edit.html.twig', [
            'daily_checkin' => $dailyCheckin,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_daily_checkin_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(DailyCheckin $dailyCheckin): Response
    {
        $this->denyUnlessOwner($dailyCheckin);

        return $this->render('daily_checkin/show.html.twig', [
            'daily_checkin' => $dailyCheckin,
        ]);
    }

    #[Route('/{id}', name: 'app_daily_checkin_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, DailyCheckin $dailyCheckin, EntityManagerInterface $em): Response
    {
        $this->denyUnlessOwner($dailyCheckin);

        if ($this->isCsrfTokenValid('delete'.$dailyCheckin->getId(), (string) $request->getPayload()->get('_token'))) {
            $em->remove($dailyCheckin);
            $em->flush();
        }

        return $this->redirectToRoute('app_daily_checkin_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @param DailyCheckin[] $checkins
     * @return array{labels: string[], values: float[]}
     */
    private function buildWellnessRadarDataset(array $checkins): array
    {
        if ($checkins === []) {
            return [
                'labels' => ['Humeur', 'Énergie', 'Productivité', 'Calme', 'Sommeil'],
                'values' => [0, 0, 0, 0, 0],
            ];
        }

        $n = count($checkins);
        $sumMood = 0.0;
        $sumEnergy = 0.0;
        $sumProd = 0.0;
        $sumStress = 0.0;
        $sumSleepQ = 0.0;

        foreach ($checkins as $c) {
            $sumMood += $c->getMoodRating() ?? 0;
            $sumEnergy += $c->getEnergyLevel() ?? 0;
            $sumProd += $c->getProductivityLevel() ?? 0;
            $sumStress += $c->getStressLevel() ?? 0;
            $sumSleepQ += $c->getSleepQuality() ?? 0;
        }

        $calm = max(0.0, min(10.0, 10 - ($sumStress / $n)));

        return [
            'labels' => ['Humeur', 'Énergie', 'Productivité', 'Calme (inv. stress)', 'Qualité sommeil'],
            'values' => [
                round($sumMood / $n, 2),
                round($sumEnergy / $n, 2),
                round($sumProd / $n, 2),
                round($calm, 2),
                round($sumSleepQ / $n, 2),
            ],
        ];
    }

    private function denyUnlessOwner(DailyCheckin $dailyCheckin): void
    {
        /** @var User|null $current */
        $current = $this->getUser();
        $owner = $dailyCheckin->getUser();
        if ($owner !== null && $current !== $owner) {
            throw $this->createAccessDeniedException();
        }
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function renderPdf(string $view, array $vars, string $filename): Response
    {
        $html = $this->renderView($view, $vars);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]
        );
    }
}
