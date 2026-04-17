<?php

namespace App\Controller;

use App\Entity\Habitude;
use App\Entity\HabitCompletions;
use App\Entity\HabitStreaks;
use App\Entity\User;
use App\Form\HabitudeType;
use App\Repository\HabitudeRepository;
use App\Repository\HabitCompletionsRepository;
use App\Repository\HabitStreaksRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/habitude')]
class HabitudeController extends AbstractController
{
    #[Route('/', name: 'app_habitude_index', methods: ['GET'])]
    public function index(
        HabitudeRepository $habitudeRepository,
        HabitCompletionsRepository $completionsRepo,
        Request $request
    ): Response {
        $category = $request->query->get('category');
        $search = $request->query->get('search');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $userId = $user ? $user->getId() : null;
        
        $habitudes = $habitudeRepository->searchByCategoryAndText($category, $search, $userId);
        $globalStats = $habitudeRepository->getGlobalStats($userId);
        
        $completedTodayCount = 0;
        $completedTodayIds = [];
        if ($user) {
            $completedTodayCount = $completionsRepo->countTodayCompletions($user);
            $completedTodayIds = $completionsRepo->findIdsCompletedToday($user);
        }
        
        return $this->render('habitude/index.html.twig', [
            'habitudes' => $habitudes,
            'globalStats' => $globalStats,
            'completedToday' => $completedTodayCount,
            'completedTodayIds' => $completedTodayIds,
        ]);
    }

    #[Route('/new', name: 'app_habitude_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $habitude = new Habitude();
        $habitude->setStartDate(new \DateTime());
        $endDate = new \DateTime();
        $endDate->modify('+3 months');
        $habitude->setEndDate($endDate);
        
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $habitude->setUser($user);
            }
            $entityManager->persist($habitude);
            $entityManager->flush();

            return $this->redirectToRoute('app_habitude_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('habitude/new.html.twig', [
            'habitude' => $habitude,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_habitude_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Habitude $habitude): Response
    {
        return $this->render('habitude/show.html.twig', [
            'habitude' => $habitude,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_habitude_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HabitudeType::class, $habitude);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_habitude_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('habitude/edit.html.twig', [
            'habitude' => $habitude,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_habitude_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$habitude->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($habitude);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_habitude_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_habitude_complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function complete(
        Habitude $habitude,
        EntityManagerInterface $entityManager,
        HabitCompletionsRepository $completionsRepo,
        HabitStreaksRepository $streaksRepo
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Check if already completed today
        $today = new \DateTime();
        $today->setTime(0, 0, 0);
        $tomorrow = clone $today;
        $tomorrow->modify('+1 day');

        $existingCompletion = $completionsRepo->findOneBy([
            'habitude' => $habitude,
            'user' => $user,
        ]);

        // Check for completion today
        $completedToday = false;
        if ($existingCompletion) {
            $completedAt = $existingCompletion->getCompletedAt();
            if ($completedAt >= $today && $completedAt < $tomorrow) {
                $completedToday = true;
            }
        }

        if ($completedToday) {
            return new JsonResponse(['success' => false, 'message' => 'Already completed today'], 400);
        }

        // Create completion record
        $completion = new HabitCompletions();
        $completion->setHabitude($habitude);
        $completion->setUser($user);
        $entityManager->persist($completion);

        // Update streak
        $streak = $streaksRepo->findOneBy(['habitude' => $habitude, 'user' => $user]);
        if (!$streak) {
            $streak = new HabitStreaks();
            $streak->setHabitude($habitude);
            $streak->setUser($user);
            $streak->setCurrentStreak(0);
            $streak->setLongestStreak(0);
            $entityManager->persist($streak);
        }

        // Calculate streak
        $lastCompleted = $streak->getLastCompleted();
        $yesterday = clone $today;
        $yesterday->modify('-1 day');

        if ($lastCompleted && $lastCompleted->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $streak->setCurrentStreak($streak->getCurrentStreak() + 1);
        } else {
            $streak->setCurrentStreak(1);
        }

        if ($streak->getCurrentStreak() > $streak->getLongestStreak()) {
            $streak->setLongestStreak($streak->getCurrentStreak());
        }

        $streak->setLastCompleted($today);

        // Update habit stats
        $habitude->setTotalCompletions($habitude->getTotalCompletions() + 1);
        $habitude->setCurrentStreak($streak->getCurrentStreak());
        $habitude->setLongestStreak($streak->getLongestStreak());

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Habit completed!',
            'currentStreak' => $streak->getCurrentStreak(),
            'longestStreak' => $streak->getLongestStreak(),
            'totalCompletions' => $habitude->getTotalCompletions(),
        ]);
    }

    #[Route('/{id}/toggle-active', name: 'app_habitude_toggle_active', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleActive(Habitude $habitude, EntityManagerInterface $entityManager): JsonResponse
    {
        $habitude->setActive(!$habitude->isActive());
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'active' => $habitude->isActive(),
        ]);
    }

    #[Route('/api/stats/{id}', name: 'app_habitude_api_stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getStats(
        Habitude $habitude,
        HabitCompletionsRepository $completionsRepo
    ): JsonResponse {
        $completions = $completionsRepo->findBy(
            ['habitude' => $habitude],
            ['completedAt' => 'DESC']
        );

        // Calculate weekly completion (Strict Monday to Sunday)
        $weekDays = [];
        $today = new \DateTime('today');
        $dayOfWeek = (int) $today->format('N'); // 1 (Mon) to 7 (Sun)
        $mondayOfThisWeek = (clone $today)->modify('-' . ($dayOfWeek - 1) . ' days');

        for ($i = 0; $i < 7; $i++) {
            $date = (clone $mondayOfThisWeek)->modify("+$i days");
            $nextDate = (clone $date)->modify('+1 day');

            $completed = false;
            foreach ($completions as $completion) {
                $completedAt = $completion->getCompletedAt();
                if ($completedAt >= $date && $completedAt < $nextDate) {
                    $completed = true;
                    break;
                }
            }

            $weekDays[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'completed' => $completed,
                'isToday' => $date == $today,
            ];
        }

        // Calculate 4-week history based on calendar weeks (Mon-Sun)
        $target = $habitude->getFrequencyType() ?: 7;
        $weekHistory = [];
        for ($week = 0; $week < 4; $week++) {
            // week 0 is this week's mon-sun
            // week 1 is last week's mon-sun
            $offset = $week * 7;
            $startOfBlock = (clone $mondayOfThisWeek)->modify("-$offset days");
            $endOfBlock = (clone $startOfBlock)->modify('+7 days');

            $completedCount = 0;
            foreach ($completions as $completion) {
                $completedAt = $completion->getCompletedAt();
                if ($completedAt >= $startOfBlock && $completedAt < $endOfBlock) {
                    $completedCount++;
                }
            }

            $percentage = min(100, round(($completedCount / $target) * 100));
            $weekHistory[] = [
                'week' => $week + 1,
                'completed' => $completedCount,
                'target' => $target,
                'percentage' => $percentage,
            ];
        }

        // Calculate monthly completion relative to frequency
        $monthStart = new \DateTime('first day of this month');
        $monthStart->setTime(0, 0, 0);
        $monthEnd = new \DateTime('first day of next month');
        $monthEnd->setTime(0, 0, 0);

        $monthCompleted = 0;
        foreach ($completions as $completion) {
            $completedAt = $completion->getCompletedAt();
            if ($completedAt >= $monthStart && $completedAt < $monthEnd) {
                $monthCompleted++;
            }
        }

        $daysInMonth = (int) $today->format('t');
        // Monthly target: fixed at 4 weeks per user request (e.g., 1/week = 4/month target)
        $monthTarget = $target * 4.0;
        $monthPercentage = $monthTarget > 0 ? min(100, round(($monthCompleted / $monthTarget) * 100, 1)) : 0;

        return new JsonResponse([
            'currentStreak' => $habitude->getCurrentStreak(),
            'longestStreak' => $habitude->getLongestStreak(),
            'totalCompletions' => $habitude->getTotalCompletions(),
            'monthlyPercentage' => $monthPercentage,
            'weekDays' => $weekDays,
            'weekHistory' => $weekHistory,
        ]);
    }

    #[Route('/api/search', name: 'app_habitude_api_search', methods: ['GET'])]
    public function search(HabitudeRepository $habitudeRepository, Request $request): JsonResponse
    {
        $category = $request->query->get('category');
        $search = $request->query->get('search');

        $habitudes = $habitudeRepository->searchByCategoryAndText($category, $search);

        $data = [];
        foreach ($habitudes as $habitude) {
            $data[] = [
                'id' => $habitude->getId(),
                'title' => $habitude->getTitle(),
                'category' => $habitude->getCategory(),
                'description' => $habitude->getDescription(),
                'active' => $habitude->isActive(),
                'currentStreak' => $habitude->getCurrentStreak(),
                'longestStreak' => $habitude->getLongestStreak(),
                'totalCompletions' => $habitude->getTotalCompletions(),
                'startDate' => $habitude->getStartDate()?->format('Y-m-d'),
                'endDate' => $habitude->getEndDate()?->format('Y-m-d'),
            ];
        }

        return new JsonResponse(['habitudes' => $data]);
    }

    // ──────────────────────────────────────────────────────────────────────
    //  HABITUDE INSIGHTS  (scoped to habitude only — no cross-module deps)
    // ──────────────────────────────────────────────────────────────────────

    #[Route('/insights', name: 'app_habitude_insights', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function insights(HabitudeRepository $habitudeRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user instanceof User ? $user->getId() : null;

        $habitudes = $habitudeRepository->findBy(['user' => $user], ['totalCompletions' => 'DESC']);

        $totalHabits     = count($habitudes);
        $activeHabits    = 0;
        $totalCompletions = 0;
        $maxStreak       = 0;
        $avgStreak       = 0.0;
        $bestHabit       = null;
        $atRiskHabits    = [];
        $categoryStats   = [];

        foreach ($habitudes as $h) {
            if ($h->isActive()) { ++$activeHabits; }
            $totalCompletions += $h->getTotalCompletions() ?? 0;
            $cs = $h->getCurrentStreak() ?? 0;
            if ($cs > $maxStreak) {
                $maxStreak = $cs;
                $bestHabit = $h;
            }
            $avgStreak += $cs;

            $cat = $h->getCategory() ?? 'Autre';
            $categoryStats[$cat] = ($categoryStats[$cat] ?? 0) + 1;

            // At-risk: active but no completion in last 3 days (streak broken)
            if ($h->isActive() && $cs === 0 && ($h->getTotalCompletions() ?? 0) > 0) {
                $atRiskHabits[] = $h;
            }
        }
        $avgStreak = $totalHabits > 0 ? round($avgStreak / $totalHabits, 1) : 0.0;

        // Consistency score (0–100)
        $consistencyScore = 0;
        if ($totalHabits > 0) {
            $consistencyScore = min(100, (int) round(($avgStreak / max(1, $maxStreak)) * 100));
        }

        // Categorised suggestions for habits
        $suggestions = $this->buildHabitSuggestions($habitudes, $atRiskHabits, $avgStreak, $consistencyScore);

        arsort($categoryStats);

        return $this->render('habitude/insights.html.twig', [
            'totalHabits'       => $totalHabits,
            'activeHabits'      => $activeHabits,
            'totalCompletions'  => $totalCompletions,
            'maxStreak'         => $maxStreak,
            'avgStreak'         => $avgStreak,
            'bestHabit'         => $bestHabit,
            'atRiskHabits'      => $atRiskHabits,
            'categoryStats'     => $categoryStats,
            'consistencyScore'  => $consistencyScore,
            'suggestions'       => $suggestions,
            'habitudes'         => $habitudes,
        ]);
    }

    /**
     * @param Habitude[] $habitudes
     * @param Habitude[] $atRisk
     * @return array<int, array<string, mixed>>
     */
    private function buildHabitSuggestions(array $habitudes, array $atRisk, float $avgStreak, int $consistencyScore): array
    {
        $categories = [];

        // Consistency category
        $consistItems = [];
        if ($consistencyScore < 40) {
            $consistItems[] = '📅 Liez chaque habitude à un déclencheur fixe : après le café du matin, avant le dîner, etc.';
            $consistItems[] = '🔔 Activez des rappels quotidiens à heure fixe pour chaque habitude inactive.';
            $consistItems[] = '🎯 Réduisez à 2-3 habitudes actives simultanées pour maximiser la régularité.';
        } elseif ($consistencyScore < 70) {
            $consistItems[] = '📈 Vous êtes sur la bonne voie ! Identifiez les jours où vous sautez le plus souvent.';
            $consistItems[] = '🔗 Enchaînez vos habitudes les unes aux autres (habit stacking) pour renforcer l\'automatisme.';
        } else {
            $consistItems[] = '🏆 Excellente régularité ! Envisagez d\'ajouter une nouvelle habitude plus ambitieuse.';
            $consistItems[] = '💎 Partagez vos accomplissements pour vous motiver davantage.';
        }
        $categories[] = [
            'icon'  => '🎯',
            'label' => 'Régularité & Consistance',
            'color' => $consistencyScore < 40 ? '#f43f5e' : ($consistencyScore < 70 ? '#f59e0b' : '#22c55e'),
            'items' => $consistItems,
        ];

        // At-risk habits category
        if (!empty($atRisk)) {
            $atRiskItems = [
                '⚠️ Habitudes à relancer : ' . implode(', ', array_map(fn($h) => '"' . $h->getTitle() . '"', array_slice($atRisk, 0, 3))),
                '🔄 Reprenez avec un objectif minimal : même 1 minute compte pour remettre la série en route.',
                '❓ Questionnez-vous : quel obstacle a interrompu ces habitudes ? Réduisez la friction.',
            ];
            $categories[] = [
                'icon'  => '⚠️',
                'label' => 'Habitudes à relancer',
                'color' => '#f43f5e',
                'items' => $atRiskItems,
            ];
        }

        // Streak building
        $streakItems = [];
        if ($avgStreak < 5) {
            $streakItems[] = '🌱 Concentrez vos efforts sur 1 habitude prioritaire pour construire une série solide.';
            $streakItems[] = '📊 Suivez visuellement votre streak : cochez chaque jour sur un calendrier papier.';
        } elseif ($avgStreak < 14) {
            $streakItems[] = '🔥 Votre série moyenne est de ' . $avgStreak . ' jours. Visez les 21 jours pour ancrer l\'habitude.';
            $streakItems[] = '💪 Ne brisez jamais deux jours consécutifs : c\'est la règle d\'or.';
        } else {
            $streakItems[] = '🌟 Série moyenne impressionnante (' . $avgStreak . ' jours) — vous avez développé de vraies habitudes ancrées !';
        }
        $categories[] = [
            'icon'  => '🔥',
            'label' => 'Construire des séries',
            'color' => '#f97316',
            'items' => $streakItems,
        ];

        // General habit health
        $categories[] = [
            'icon'  => '🌿',
            'label' => 'Santé générale des habitudes',
            'color' => '#22c55e',
            'items' => [
                '📚 Révisez vos habitudes chaque dimanche : lesquelles vous rapprochent vraiment de vos objectifs ?',
                '⚖️ Équilibrez : 1 habitude physique, 1 mentale, 1 sociale pour un développement équilibré.',
                '🎁 Récompensez-vous après une série de 7, 21, et 30 jours pour renforcer la motivation.',
            ],
        ];

        return $categories;
    }
}

