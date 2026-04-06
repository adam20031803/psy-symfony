<?php

namespace App\Controller;

use App\Entity\Habitude;
use App\Entity\HabitCompletions;
use App\Entity\HabitStreaks;
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

#[Route('/habitude')]
class HabitudeController extends AbstractController
{
    #[Route('/', name: 'app_habitude_index', methods: ['GET'])]
    public function index(HabitudeRepository $habitudeRepository, Request $request): Response
    {
        $category = $request->query->get('category');
        $search = $request->query->get('search');
        
        $habitudes = $habitudeRepository->searchByCategoryAndText($category, $search);
        $globalStats = $habitudeRepository->getGlobalStats($this->getUser()?->getId());
        
        return $this->render('habitude/index.html.twig', [
            'habitudes' => $habitudes,
            'globalStats' => $globalStats,
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

    #[Route('/{id}', name: 'app_habitude_show', methods: ['GET'])]
    public function show(Habitude $habitude): Response
    {
        return $this->render('habitude/show.html.twig', [
            'habitude' => $habitude,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_habitude_edit', methods: ['GET', 'POST'])]
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

    #[Route('/{id}', name: 'app_habitude_delete', methods: ['POST'])]
    public function delete(Request $request, Habitude $habitude, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$habitude->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($habitude);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_habitude_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/complete', name: 'app_habitude_complete', methods: ['POST'])]
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

    #[Route('/{id}/toggle-active', name: 'app_habitude_toggle_active', methods: ['POST'])]
    public function toggleActive(Habitude $habitude, EntityManagerInterface $entityManager): JsonResponse
    {
        $habitude->setActive(!$habitude->isActive());
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'active' => $habitude->isActive(),
        ]);
    }

    #[Route('/api/stats/{id}', name: 'app_habitude_api_stats', methods: ['GET'])]
    public function getStats(
        Habitude $habitude,
        HabitCompletionsRepository $completionsRepo
    ): JsonResponse {
        $completions = $completionsRepo->findBy(
            ['habitude' => $habitude],
            ['completedAt' => 'DESC']
        );

        // Calculate weekly completion
        $weekDays = [];
        $today = new \DateTime();
        for ($i = 6; $i >= 0; $i--) {
            $date = clone $today;
            $date->modify("-$i days");
            $date->setTime(0, 0, 0);
            $nextDate = clone $date;
            $nextDate->modify('+1 day');

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
                'isToday' => $i === 0,
            ];
        }

        // Calculate 4-week history
        $weekHistory = [];
        for ($week = 0; $week < 4; $week++) {
            $weekStart = clone $today;
            $weekStart->modify('-' . ($week * 7) . ' days');
            $weekEnd = clone $weekStart;
            $weekEnd->modify('+7 days');

            $completedCount = 0;
            foreach ($completions as $completion) {
                $completedAt = $completion->getCompletedAt();
                if ($completedAt >= $weekStart && $completedAt < $weekEnd) {
                    $completedCount++;
                }
            }

            $percentage = min(100, round(($completedCount / 7) * 100));
            $weekHistory[] = [
                'week' => $week + 1,
                'completed' => $completedCount,
                'percentage' => $percentage,
            ];
        }

        // Calculate monthly completion
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
        $monthPercentage = round(($monthCompleted / $daysInMonth) * 100, 1);

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
}
